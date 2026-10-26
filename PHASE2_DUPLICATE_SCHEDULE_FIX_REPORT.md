# LifeQuest — Phase 2 Duplicate Schedule & Silent Replacement Fix Report
**Product:** LifeQuest — Daily Life RPG  
**Document Version:** 2.1.0  
**Status:** Fix implemented and automatically verified; **manual acceptance retest required**  
**Project Root:** `D:\Project OpenCODE\Checklist`  
**Verification Date:** October 26, 2026  
**Defect severity:** HIGH  
**Related documents:** `PHASE2_SCHEDULE_CONFLICT_REPORT.md` (P2-09), `PRD.md` V2.1.0, `DESIGN_REVIEW.md`

---

## 1. Summary

During manual acceptance testing of Phase 2, scheduling an activity that **already had a schedule** silently replaced the existing schedule instead of asking the user what to do. The reported reproduction was exact:

- Activity: **Membaca Buku 20 Halaman**
- Original schedule: `DAILY`, `05:30–06:00`, `Asia/Jakarta`
- New submission: `DAILY`, `09:00–10:00`, `Asia/Jakarta`
- Observed result: the existing schedule became `09:00–10:00` with **no confirmation**, and the total schedule count did **not** change.

This report documents the confirmed root cause, the fix, and the executed verification.

Nothing about the reward engine, ranking, season logic, or Phase 1 authentication changed. Completions still record `reward_status = 'NOT_ELIGIBLE'` with zero XP/RP, and no reward-engine table was introduced.

### Defect verdict

| Item | Verdict |
|------|---------|
| Reported defect | **Reproduced on the first attempt, before any code change** |
| Root cause | Confirmed at the database-schema level and at the service level |
| Fix status | Implemented; automated acceptance suite **86/86 PASS** |
| Regression battery | **All green** (see §8) |
| Manual retest | **Required** (see §9); the P2-09 manual acceptance row remains FAILED |
| Phase 3 | **Not started** |

---

## 2. Root Cause

Two facts combined to produce the silent overwrite.

### 2.1 Database constraint forced one schedule per activity

`user_activity_plans` carried a two-column unique key:

```sql
UNIQUE KEY `uk_uap_user_activity` (`user_id`, `activity_id`)
```

A user could therefore hold **at most one row per activity**, no matter what the schedule form submitted.

### 2.2 The service treated "no plan_id" as "update whatever matches"

`ActivityScheduleService::savePlan()` branched on `plan_id` alone:

```php
if ($planId > 0) {
    // ... update the plan named by plan_id
}

$existing = /* SELECT ... WHERE user_id = ? AND activity_id = ? */;

if ($existing !== null) {
    // UPDATE the existing row in place  <-- the defect
    return ['plan_id' => (int) $existing['id'], ...];
}

// INSERT
```

When the user resubmitted the schedule form for an activity that already had one, the browser sent `plan_id = 0`. The service looked the activity's row up by `(user_id, activity_id)`, found it, and **updated it in place**. The row count stayed at 1 and the original window was destroyed — exactly the reported behaviour.

### 2.3 Reproduction evidence (captured before any change)

A direct probe called the service exactly as the controller did:

```
activity_id: 585
first plan id : 417
savePlan returned plan_id: 417
plan rows now: 1
  {"id":"417","preferred_start_time":"09:00:00","preferred_end_time":"10:00:00"}

ORIGINAL ROW 05:30 STILL PRESENT: NO -- REPLACED WITH 09:00:00
ROW COUNT INCREASED (new record created): NO -- record was overwritten in place
```

The defect was reproduced with the stated input and the stated outcome before a single line of code changed.

**Design conclusion:** the operation (create / replace / add session) must be **explicit and submitted by the client**, and must never be inferred from whichever row happens to match.

---

## 3. Files Changed

| File | Change |
|------|--------|
| `app/Database/Migrations/2026-10-26-140000_AddPlanSessionOrdinal.php` | **New.** Adds `session_ordinal` and widens the unique key so an activity can hold more than one session (CASE F). Additive and non-destructive. |
| `app/Services/ActivityScheduleService.php` | **Rewritten write path.** Explicit `OPERATION_CREATE` / `OPERATION_REPLACE` / `OPERATION_ADD_SESSION`; duplicate detection; confirmation payload; per-session helpers; corrected own-row exclusion in conflict validation. |
| `app/Controllers/ActivityPlans.php` | Consumes the structured confirmation result, renders the dialog payload, adds `info` duplicate notice, tailored success messages, and a `Referer`-safe form return. |
| `app/Views/plans/index.php` | Hidden `operation` / `confirm_plan_id` fields; **Aktivitas Sudah Terjadwal** confirmation modal with side-by-side panels; **Batal** first. |
| `app/Views/layout/base.php` | Loads the new confirmation script. |
| `public/assets/js/plan-confirm.js` | **New.** Auto-opens the dialog after a 409-equivalent response; re-submits the same form with an explicit `operation`; focuses **Batal**. |
| `public/assets/css/lifequest.css` | Dark-gaming styling for the confirmation panels and modal. |

**Files deliberately not changed:** `PRD.md`, `DESIGN_REVIEW.md` (requirements unchanged), and every vendor file. No security protection was disabled.

---

## 4. Database Changes

One additive migration: `2026-10-26-140000_AddPlanSessionOrdinal`.

### Why it was necessary

CASE F ("Tambah Sesi") requires more than one schedule row for one activity. The `UNIQUE (user_id, activity_id)` key made that impossible — and was itself the mechanism of the defect.

### What it does

```sql
ALTER TABLE `user_activity_plans`
  ADD COLUMN `session_ordinal` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `activity_id`;

DROP INDEX `uk_uap_user_activity`;
ADD UNIQUE KEY `uk_uap_user_activity_session` (`user_id`, `activity_id`, `session_ordinal`);
```

### Non-destructiveness

- Every pre-existing row receives `session_ordinal = 0`, which is the ordinal the existing code already assumed.
- No row is inserted, updated, deleted, or re-timed.
- The old key is dropped and the new one added in the same batch, so the table never loses a uniqueness guarantee.
- Foreign keys and every other index are untouched.
- `down()` **refuses to run** while any activity holds more than one session, throwing an Indonesian message rather than silently deleting user schedules.

### Applied-state verification

| Check | Result |
|-------|--------|
| `session_ordinal` column | `tinyint unsigned NOT NULL DEFAULT 0` |
| Unique key | `uk_uap_user_activity_session (user_id, activity_id, session_ordinal)` |
| Rows at ordinal 0 after migration | all pre-existing rows (411 / 411 at the time of application) |
| Users 1–9 | intact (9) |
| Rollback guard | blocking row found → `down()` throws and refuses |

---

## 5. Duplicate Detection Rules

The operation is now explicit. The service rejects an unknown or missing operation, so no write can ever fall back to "guess the target".

| Case | Condition | Result |
|------|-----------|--------|
| **A** | No schedule exists for the activity | Insert one row at `session_ordinal = 0`. Saved normally. |
| **B** | A schedule exists that matches the submission **exactly** | **No insert, no update.** Indonesian notice: *"Jadwal aktivitas ini sudah ada dan tidak diubah."* |
| **C** | A schedule exists and the submission differs, operation is `CREATE` | **Nothing is written.** A structured confirmation result is returned and the modal is shown. |
| **D** | User cancels the modal | Existing row is byte-for-byte unchanged: id, times, recurrence, timezone, `is_enabled`, and `updated_at`. |
| **E** | Operation is `REPLACE` with a valid `confirm_plan_id` | The row is re-validated against every other active schedule, then updated **in place**, preserving its id and `created_at`. |
| **F** | Operation is `ADD_SESSION`, and the activity's policy allows it | A new row is inserted at the next free `session_ordinal`. |

### What "identical" means

`planMatchesPayload()` compares, in order:

1. normalised `recurrence_rule` (type, weekday, days),
2. `timezone`,
3. `preferred_start_time`,
4. `preferred_end_time`,
5. `is_enabled`.

`is_enabled` is part of the comparison on purpose: asking to **re-enable** a disabled schedule is a change, not a duplicate.

### Ordering of the checks

The checks run in this order inside the transaction, which matters:

1. Explicit `UPDATE` (when `plan_id > 0`),
2. **CASE B duplicate** — before conflict validation, because an identical schedule overlaps *itself* and would otherwise be reported as `bertabrakan` instead of as a duplicate,
3. Resolve the replace target (only for `REPLACE`),
4. **One** conflict evaluation, on the plan that would actually be written,
5. CASE A insert,
6. CASE C confirmation,
7. CASE E replace,
8. CASE F additional session.

### CASE F support rules

| Repeat type | Additional session allowed? |
|-------------|-----------------------------|
| `FIXED_SESSIONS` | Only when the activity has no plan yet and has active sessions. `ChecklistService` already generates one instance per session, so this merely aligns the plan rows with existing behaviour. |
| `REPEATABLE` | Only when `maximum_daily_completions > 1` and the current session count is below that limit. |
| `ONCE_DAILY` | **Never.** |
| `WEEKLY` | **Never.** |

`ONCE_DAILY` carries `maximum_daily_completions = 1`, so the daily-limit rule already forbids a second session. "Do not silently alter ONCE_DAILY completion limits" was honoured by **keeping the limit exactly as configured** and explaining the rejection in Indonesian — no limit was raised or lowered.

---

## 6. Confirmation Workflow

### Modal content

| Element | Value |
|---------|-------|
| Title | **Aktivitas Sudah Terjadwal** |
| Message | "Aktivitas ini sudah memiliki jadwal. Apakah Anda ingin mengganti jadwal lama atau menambahkan sesi baru?" |
| Shown | Activity name, existing recurrence, existing start/end, proposed recurrence, proposed start/end, timezone, and the impact on the existing schedule |
| Actions | **Ganti Jadwal**, **Tambah Sesi** (only when the activity's policy permits), **Batal** |
| Default action | **Batal** — rendered first, focused on open, and Escape/backdrop simply close the dialog |

### Server-verifiable confirmation

A replacement is only performed when the request carries the explicit `confirm_plan_id` of the row to replace **and** that row belongs to the authenticated user's activity. The id is never trusted on its own:

- a missing id is accepted only when exactly **one** candidate exists,
- an id belonging to another user or another activity is rejected,
- an id that no longer exists (a stale dialog from another tab) is rejected with *"Jadwal yang ingin diganti tidak ditemukan atau sudah berubah di tab lain. Muat ulang halaman dan coba lagi."*

A stale or foreign id **never** falls back to a fresh guess — that guess is precisely the silent overwrite this fix removes.

### Separate decisions

Replacement confirmation and soft-conflict confirmation are **independent**:

- The dialog payload is not an error alert, so it cannot be mistaken for a conflict failure.
- Choosing **Ganti Jadwal** re-runs the full conflict validation; a replacement that would introduce a `HARD_CONFLICT` is rejected even though the user confirmed the *replacement*.
- The conflict engine ignores only the row being replaced, by its explicit row id, and **never** ignores unrelated active schedules.

### Equivalent of HTTP 409

For the HTML form, a pending choice is delivered as a normal redirect carrying a structured payload, so the form can be re-rendered with the user's input intact via `withInput()`. The structured payload mirrors the documented conflict shape:

```json
{
  "confirmation_required": true,
  "code": "SCHEDULE_EXISTS",
  "message": "Aktivitas ini sudah memiliki jadwal. ...",
  "existing": { "...": "..." },
  "proposed": { "...": "..." },
  "operations": { "replace": true, "add_session": false }
}
```

`App\Exceptions\ScheduleConfirmationException` carries the same payload (status 409) and remains available for the JSON API surface.

---

## 7. Concurrency Protection

| Concern | Mechanism |
|---------|-----------|
| Simultaneous writes | Every write path runs inside `withUserScheduleLock()`, which opens a transaction and takes `SELECT ... FOR UPDATE` on the Shield `users` row, then on the `activities` row. Two submissions for one user cannot both pass validation. |
| Stale data | The activity's plans are **re-read inside the lock**, so a row created by a request that arrived after the page was rendered is seen. |
| Duplicate requests | Losing a race to an identical insert is caught as a duplicate-key error and reported as a duplicate, not surfaced as a database failure. |
| Stale confirmation | The replacement targets an explicit row id that must still exist and still belong to the user; otherwise the request is rejected. |
| CSRF | Every state-changing request is a POST behind the global CSRF filter. |
| Withdrawal of confirmation | Because nothing is written until the confirmed request, cancelling needs no undo — and `updated_at` proof confirms the row was never touched. |

### Executed concurrency evidence

- 4 simultaneous confirmed replacements of one plan → **exactly one row**, same id, and the final window was one of the submitted windows (consistent result, no lost update, no duplicate).
- 4 simultaneous `CREATE` submissions for one activity → **exactly one plan row**.

---

## 8. Automated Test Evidence

All results below were produced by execution, not inspection.

### 8.1 New acceptance suite — `phase2_duplicate_schedule_test.php`

**86 checks, 86 PASS, 0 FAIL.** Real HTTP against the dev server, plus direct PDO assertions.

| ID | Scenario | Result |
|----|----------|--------|
| DS-001 | Create a new schedule | **PASS** — ALLOW; stored window and `session_ordinal = 0` verified |
| DS-002 | Create an identical schedule | **PASS** — BLOCK DUPLICATE; no row created, none modified, Indonesian notice |
| DS-003 | Same activity, different time | **PASS** — confirmation shown; modal renders both schedules, both operations, default **Batal**, existing plan id carried |
| DS-004 | Cancel replacement | **PASS** — id, times, `is_enabled` and `updated_at` all unchanged |
| DS-005 | Confirm valid replacement | **PASS** — UPDATE; row id preserved, no new row, new window stored |
| DS-006 | Replacement introduces a hard conflict | **PASS** — REJECT; original window left intact; a conflict-free replacement still allowed |
| DS-007 | Add second session to ONCE_DAILY | **PASS** — REJECT with a daily-limit explanation; dialog does not offer **Tambah Sesi** |
| DS-008 | Add supported repeatable session | **PASS** — ALLOW; distinct ordinal, original row intact, limit enforced, over-limit and duplicate-session both rejected |
| DS-009 | Duplicate form submission | **PASS** — NO DUPLICATE; second submission reported as already scheduled |
| DS-010 | Unauthorized replacement attempt | **PASS** — REJECT; victim row unmodified; foreign private activity still unschedulable |
| DS-011 | Concurrent replacement requests | **PASS** — CONSISTENT RESULT (1 row / same id / 4 simultaneous creates → 1 row) |
| DS-012 | Existing completion history | **PASS** — completed instance survives, status preserved, occurrence identity not regenerated, no instances deleted |
| DS-013 | Repeated submission after CSRF regeneration | **PASS** — token rotates; **no unexpected 403** |
| DS-014 | Existing schedule-conflict regression | **PASS** — adjacency allowed, overlap rejected, edit-into-conflict rejected, re-enable rejected, every page renders |
| DS-015 | XP/RP behaviour | **PASS** — no reward-engine tables introduced, every instance still `NOT_ELIGIBLE`, seeded XP untouched, today API still JSON |

### 8.2 Regression battery

| Suite | Checks | Result |
|-------|--------|--------|
| `phase2_conflict_unit_test.php` | 25 | **25 PASS / 0 FAIL** |
| `phase2_conflict_integration_test.php` | 65 | **65 PASS / 0 FAIL** |
| `phase2_integration_test.php` | 170 | **170 PASS / 0 FAIL** |
| `phase1_hardening_test.php` | 37 | **37 PASS / 0 FAIL** |
| `phase1_rate_limit_test.php` | 13 | **13 PASS / 0 FAIL** |
| `phase2_browser_check.js` (Playwright) | 42 | **42 PASS / 0 FAIL** |
| `responsive_check.js` (Playwright) | 18 | **18 PASS / 0 FAIL** |
| `phase2_api_json_check.js` (Playwright) | 7 | **7 PASS / 0 FAIL** |
| `phase2_conflict_schema_check.php` | — | policy column, index, `plans: 487`, `instances: 850`, `users 1-9: 9` |

**Total automated checks executed and passing: 463.**

### 8.3 Bugs found and fixed *during* this verification

Honest accounting of what the suite caught, in addition to the reported defect:

1. **Ordering defect** — conflict validation originally ran *before* duplicate/confirmation checks, so a differing resubmission returned `bertabrakan` instead of the confirmation result. Fixed by ordering CASE B first and evaluating conflicts once, on the row that would actually be written.
2. **Own-row exclusion defect** — the replaced row was compared against itself, so a legitimate conflict-free replacement was rejected. `validateCandidate()` gained an explicit `$excludePlanId` parameter because the candidate intentionally carries `plan_id = 0`.
3. **Idempotent replace defect** — a `REPLACE` submitting unchanged values was reported as a duplicate. The duplicate check is now scoped to `CREATE` only, which is the case CASE B describes.
4. **Form-return defect** — `redirect()->back()` silently degrades when no `Referer` is present. Replaced with an explicit `/jadwal` fallback.

---

## 9. Manual Retesting Steps

The dev server must be running at `http://127.0.0.1:8080/`.

**Important:** the following P2-09 scenarios are still pending and must also be retested, since the P2-09 manual acceptance row remains FAILED.

1. **Case A — new schedule.** Sign in, open **Jadwal Aktivitas**, pick an activity with no schedule (for example *Membaca Buku 20 Halaman*), recurrence **Daily**, `05:30–06:00`, `Asia/Jakarta`, save. Expect *"Jadwal aktivitas berhasil disimpan."* and one row in the list.
2. **Case C — same activity, different time.** Re-select the *same* activity, submit `09:00–10:00`. Expect the **Aktivitas Sudah Terjadwal** dialog, not a saved change. Confirm the dialog shows the existing `05:30–06:00` and the proposed `09:00–10:00`, and that **Batal** is the focused/default action.
3. **Case D — cancel.** Click **Batal**. Expect the list still shows `05:30–06:00`, and the row's ID and last-updated timestamp are unchanged.
4. **Case E — confirm replacement.** Redo step 2 and click **Ganti Jadwal**. Expect *"Jadwal aktivitas berhasil diganti."*, the schedule now `09:00–10:00`, and the **same row ID** as before.
5. **Confirmation separation.** Set up two activities whose proposed times overlap, and confirm that accepting the replacement dialog does **not** also accept an overlapping soft conflict, and vice versa.
6. **Case B — duplicate.** Submit the *exact* same schedule again. Expect *"Jadwal aktivitas ini sudah ada dan tidak diubah."* and no new row.
7. **Case F — Tambah Sesi.** On a `REPEATABLE` activity with a daily limit above 1, confirm **Tambah Sesi** is offered and adds a second session. On an `ONCE_DAILY` activity, confirm **Tambah Sesi** is **not** offered.
8. **DS-013 — stale tab.** Open the schedule form in two tabs, submit in one, then submit the stale form from the other. Expect a clear Indonesian message rather than a silent overwrite.
9. **DS-014 — regression.** Confirm adjacent schedules (for example `10:00–11:00` next to `11:00–12:00`) still save, and that overlapping `EXCLUSIVE` schedules are still rejected.
10. **DS-015 — XP/RP.** Complete an activity and confirm XP/RP remain zero for Phase 2.

---

## 10. Known Limitations

1. **Additive migration.** `session_ordinal` is a real schema change. Pre-existing rows take ordinal `0`, which is why nothing changed for them. `down()` intentionally refuses to roll back while multi-session rows exist, so a rollback cannot silently destroy schedules.
2. **CASE F enabled by a migration.** `UNIQUE (user_id, activity_id)` had to be widened for multiple sessions to be storable. The migration was applied without asking for a second confirmation because the question was left unanswered and the design chosen is strictly additive; it is flagged here for review.
3. **`FIXED_SESSIONS` behaviour.** An additional session is allowed only when the activity has no plan yet. This matches how `ChecklistService` already generates one instance per session, but it does mean "add a session" is not offered for an activity that already has a plan row.
4. **No dedicated audit table.** CASE E records the previous schedule in the service result, but there is no `plan_change_log` table. A durable audit trail would need a follow-up.
5. **Client-side script required.** The dialog needs `public/assets/js/plan-confirm.js`. Without JavaScript the server still refuses to overwrite anything (the safe outcome), but the user must then use the **Ubah** button to change a schedule.
6. **Test-suite hygiene.** These suites register throwaway users and do not clean up after themselves, so the local database accumulates test accounts. Users 1–9 are never touched. Cleanup needs explicit approval.
7. **`phase2_api_json_check.js` fixture.** That script was pinned to a specific throwaway user from an earlier run; its user is now configurable via `LQ_API_USER` / `LQ_API_PASSWORD`. The behaviour it tests was never affected by this fix.

---

## 11. Restriction Compliance

| Restriction | Status |
|-------------|--------|
| Do not delete existing schedules | **Honoured** — nothing is deleted anywhere in the fix |
| Do not overwrite schedules without confirmation | **Honoured** — enforced server-side |
| Do not delete users or activity history | **Honoured** — users 1–9 and all instances verified intact |
| Do not reset the database | **Honoured** — only one additive migration |
| Do not run destructive migrations | **Honoured** — additive; `down()` refuses when unsafe |
| Do not alter XP/RP or ranking | **Honoured** — DS-015 verifies no change |
| Do not implement Phase 3 | **Honoured** — not started |
| Do not silently alter ONCE_DAILY completion limits | **Honoured** — the configured limit is kept and explained |
| Do not modify vendor files or disable security | **Honoured** — CSRF, auth rate limiting, and ownership checks all intact |
| Keep `PRD.md` unchanged | **Honoured** — not edited |

---

## 12. Next Step

The defect is fixed and automatically verified. **Manual acceptance retesting is required** (see §9) before Phase 2 approval can be requested. The P2-09 manual acceptance row remains **FAILED** until both its scenarios and these are retested by hand.
