# LifeQuest — Phase 2 Schedule Conflict Validation Report
**Product:** LifeQuest — Daily Life RPG  
**Document Version:** 2.1.0  
**Status:** Fix implemented and automatically verified; **original manual acceptance test remains FAILED pending manual retest**  
**Project Root:** `D:\Project OpenCODE\Checklist`  
**Verification Date:** October 26, 2026

---

## 1. Summary

The Phase 2 manual acceptance test failed for one real reason: a user could save two **active** schedules that occupied the same time, and nothing warned or blocked it. The application then generated two checklist entries competing for the same slot.

This report documents the fix:

- An **activity compatibility policy** (`EXCLUSIVE` / `PARALLEL_ALLOWED`) on the activity catalog.
- A **pure conflict resolver** that classifies every pair of the same user's active schedules as `HARD_CONFLICT`, `SOFT_CONFLICT`, or `NO_CONFLICT`.
- **Server-side validation on every write path** that can create or activate an overlap: create, update, and re-enable.
- **Per-user serialization** in a single transaction, so simultaneous submissions cannot both pass validation.
- **Non-destructive reporting** of already-stored overlaps; nothing is ever auto-fixed, modified, or deleted.

Nothing else in Phase 2 changed. Completion still records `reward_status = 'NOT_ELIGIBLE'` with zero XP/RP, and no reward-engine table was introduced.

### Original manual acceptance verdict

| Item | Verdict |
|------|---------|
| Original Phase 2 manual acceptance test | **FAILED** — overlapping active schedules were accepted |
| Root cause | No schedule-overlap validation existed on any write path |
| Status after this fix | Automated equivalent passes (65/65 HTTP+MySQL checks); manual retest still required |
| Phase 3 | **Not started** |

---

## 2. Requirement Traceability

No PRD boundary was reinterpreted. This work strengthens existing requirements rather than changing them.

| Source | Relevant requirement | How the fix applies it |
|--------|---------------------|-------------------------|
| PRD §8 / AC-003 | Repeatable activity and session integrity | Prevents two competing instances of the day's schedule |
| PRD §10 / AC-035 | Timezone boundaries | Windows are built from the plan's local clock time and recurrence days |
| PRD §30 rule 12 | Business logic on the server | All validation and classification is server-side; the browser only displays the result |
| PRD §30 rule 30 | No success claims without verification | Reported with executed check counts and a pending manual retest |
| PRD §30 rule 35 | Report conflicts, do not silently change requirements | This enhancement is documented here; `PRD.md` was not edited |
| AGENTS.md §8 | Reward processing untouched | No XP/RP code path was modified |

**Documented interpretation (not a PRD change):** "a user's schedule is a personal plan with at most one active exclusive activity at a given clock time" is treated as the intent of the personal-schedule feature. Two activities may still run side by side only where the catalog explicitly allows it (`PARALLEL_ALLOWED`) and the soft restrictions hold.

---

## 3. Changed Files

### 3.1 New files

| File | Purpose |
|------|---------|
| `app/Libraries/ScheduleConflictResolver.php` | Pure, database-free conflict classification: interval building, half-open overlap test, recurrence intersection, policy resolution, parallel restrictions, Indonesian messages, classification constants |
| `app/Exceptions/ScheduleConflictException.php` | Domain exception carrying the structured conflict payload (classification, conflicting plans, reason codes) |
| `app/Database/Migrations/2026-10-26-090000_AddActivitySchedulePolicy.php` | Additive schema change: `activities.schedule_policy` + `idx_activities_schedule_policy`, with a reversible `down()` |

### 3.2 Modified files

| File | Change |
|------|--------|
| `app/Services/ActivityScheduleService.php` | Rewritten write paths (`savePlan()`, `setEnabled()`, `disableAllForActivity()`), per-user row-lock serialization (`withUserScheduleLock()`, `acquireLock()`), validation helpers (`validateCandidate()`, `normalizeConflictPlan()`, `isConflictEligible()`, `sessionBound()`, `assertTimeOrder()`), and non-destructive reporting (`existingConflicts()`) |
| `app/Controllers/ActivityPlans.php` | Handles `ScheduleConflictException`; renders hard-conflict errors as `error` flash and soft conflicts as `warning` flash with an Indonesian explanation; passes `existingConflicts` to the view |
| `app/Views/plans/index.php` | Alert list of existing overlaps, per-plan `Bertabrakan` badge, and helper text describing the rule |
| `app/Views/layout/flash.php` | Adds a `warning` flash block (previously only `success` / `error`) |
| `app/Services/ActivityService.php` | Plan/catalog selects include `vp.requires_evidence` and `vp.requires_approval` (needed by the parallel-evidence restriction); custom activities are created with `schedule_policy = EXCLUSIVE` |
| `app/Models/ActivityModel.php` | `schedule_policy` added to `$allowedFields` |
| `app/Database/Seeds/ActivityCatalogSeeder.php` | Sets an explicit `schedule_policy` for all 10 standard activities plus an idempotent backfill, so re-seeding never leaves the column empty |

### 3.3 Documentation

| File | Change |
|------|--------|
| `TASKS.md` | Adds task `P2-09`, a manual-acceptance status table (original test = **FAILED**), updated verification summary and status line |
| `TESTING.md` | Adds the conflict unit/integration suites, boundary cases, concurrency rationale, fixture note, `NEW-16`–`NEW-18`, and an open-item notice |
| `PHASE2_SCHEDULE_CONFLICT_REPORT.md` | This report |

### 3.4 Deliberately unchanged

- `PRD.md` — no requirement was edited or reinterpreted.
- `DATABASE.md` / `ARCHITECTURE.md` — the Phase 2 entries already describe the model; the single additive column is documented in §6 of this report.
- Any XP/RP, verification, evidence, timer, or season code.
- Existing schedule rows: the migration adds a column only, and no row is rewritten, disabled, or deleted.

---

## 4. Compatibility Rules

### 4.1 Activity compatibility policy

Stored on the catalog row as `activities.schedule_policy`:

| Policy | Meaning |
|--------|---------|
| `EXCLUSIVE` | The activity holds its time slot exclusively. Any overlap with another active plan is a hard conflict. **Default for every existing and newly created activity.** |
| `PARALLEL_ALLOWED` | The activity may share its slot with another `PARALLEL_ALLOWED` activity while all soft restrictions (§4.4) hold. |

An unknown, empty, or missing value is treated as `EXCLUSIVE` — failing closed.

### 4.2 Conflict classification

| Classification | When | Effect |
|----------------|------|--------|
| `HARD_CONFLICT` | Any overlap where **at least one** side is `EXCLUSIVE` (reason code `EXCLUSIVE_OVERLAP`) | Write rejected; Indonesian error message |
| `SOFT_CONFLICT` | Both sides `PARALLEL_ALLOWED`, windows overlap, and at least one soft restriction is violated | Write **saved**, with an Indonesian warning naming the activities |
| `NO_CONFLICT` | No overlap, or both parallel with all soft restrictions satisfied | Write accepted silently |

When a pair satisfies multiple conditions, only the worst classification is reported. The exclusive rule is evaluated first, so an `EXCLUSIVE` activity overlapping a parallel one is always a hard conflict.

### 4.3 Time windows and overlap test

Each active plan is reduced to a window on the **user's local same-day clock**, in minutes:

| Window basis | Source | Behaviour |
|--------------|--------|-----------|
| `ORDERED` | Explicit `preferred_start_time` / `preferred_end_time` | Uses both endpoints |
| `SESSION_DEFINED` | No explicit times, activity has active sessions | Earliest session `scheduled_start` to latest session `scheduled_end` |
| `UNBOUNDED` | No explicit times and no sessions | The whole local day, `00:00`–`24:00` |

Additional rules:

- **Half-open intervals.** A window is `[start, end)`. A window ending at `06:00` and one starting at `06:00` are adjacent, not overlapping. So 05:30–06:00 followed by 06:00–06:30 is accepted.
- **All bases share one clock.** `ORDERED`, `SESSION_DEFINED`, and `UNBOUNDED` windows are compared directly; the basis is reported in the conflict payload but never used to exempt a pair.
- **Partial endpoints.** A missing start falls back to `00:00`; a missing end falls back to `24:00`.
- **Invalid order.** An end time less than or equal to the start time is rejected before conflict evaluation: *"Jam selesai harus lebih lambat daripada jam mulai. Jadwal yang melewati tengah malam belum didukung."* Cross-midnight windows are not supported.
- **Recurrence awareness.** Two plans conflict only if their recurrence sets intersect: `DAILY` intersects everything; `WEEKDAYS`, `WEEKLY`, and `CUSTOM_DAYS` expand to day-of-week sets and are intersected. A Monday-only weekly plan therefore does not block a Wednesday-only weekly plan at the same clock time.
- **Timezone.** Each plan's `timezone` is validated with `ScheduleRules::assertTimezone()` before saving and defaults to `Asia/Jakarta`. Because every plan stores a wall-clock `HH:MM` time and recurrence days rather than absolute instants, overlap is evaluated directly on that local clock; no UTC conversion is needed for the comparison. Stored timestamps remain UTC per project rules.
- **Per user.** Only the same user's plans are compared. Different users may hold identical slots.

### 4.4 Parallel soft restrictions

Both sides must be `PARALLEL_ALLOWED`, and all three restrictions must hold to reach `NO_CONFLICT`:

| Restriction | Rule | Why |
|-------------|------|-----|
| `DIFFERENT_CATEGORY` | The two activities must be in different categories | Two tasks from the same life domain overlapping almost always means one was duplicated |
| `SAME_VERIFICATION_FAMILY` | Both must use the same verification family (`TIMER` with `TIMER`; every non-timer policy with any other non-timer policy) | A timed task cannot be trusted while a self-check task competes for attention |
| `SINGLE_EVIDENCE_ACTIVITY` | At most one of the two may require evidence | One capture session cannot serve two tasks |

### 4.5 Which write paths are validated

| Path | Behaviour |
|------|-----------|
| Create a plan (`POST /jadwal`) | Validated against all other active plans |
| Update a plan (`POST /jadwal` with `plan_id`) | Validated, excluding the plan's own row; the activity cannot be swapped during an edit |
| Re-enable a disabled plan (`POST /jadwal/{id}/status`) | Validated as if it were active |
| Disable a plan, or save a plan as disabled | Always allowed — reducing coverage can never create an overlap |
| Deactivating a custom activity | Disables its plans; always allowed |
| Direct SQL / non-HTTP writes | Out of scope; the resolver is application-layer only (noted as a limitation) |

### 4.6 Schedules that already overlap

Already-stored overlapping rows are **reported, never repaired**:

- `/jadwal` shows an alert list of the conflicts and a `Bertabrakan` badge on each affected plan.
- No plan is disabled, re-timed, or deleted automatically.
- Unrelated edits still succeed; only a write that itself introduces a new hard conflict is blocked.
- This keeps historical user data intact and puts the resolution decision with the user.

---

## 5. Concurrency Design

Every write that can create or activate an overlap runs inside one transaction that first serializes per user:

1. `SELECT id FROM users WHERE id = ? FOR UPDATE` — serializes all schedule writes for that user.
2. `SELECT id FROM activities WHERE id = ? FOR UPDATE` — orders against the activity-deactivation path so the two never deadlock.
3. Re-read the user's active plans **inside** the lock.
4. Validate the candidate with `ScheduleConflictResolver`.
5. Insert or update, then commit.

Locking an immutable shared row (rather than the plan rows) avoids gap locks on a plan row that may not exist yet. The lock is released on commit or rollback, so the second of two simultaneous submissions re-reads the winner's row and is rejected. This was verified with both 2-way and 4-way simultaneous submissions: exactly one plan is created in each case.

---

## 6. Migration

**File:** `app/Database/Migrations/2026-10-26-090000_AddActivitySchedulePolicy.php`  
**Applied via:** `php spark migrate --all`  
**Migration batch:** 4 (see `php spark migrate:status`)

```sql
ALTER TABLE `activities`
  ADD COLUMN `schedule_policy` VARCHAR(20) NOT NULL DEFAULT 'EXCLUSIVE'
  AFTER `maximum_weekly_completions`;

ALTER TABLE `activities`
  ADD INDEX `idx_activities_schedule_policy` (`schedule_policy`);
```

| Property | Value |
|----------|-------|
| Additive only | One column, one index; no row is rewritten or removed |
| Reversible | `down()` drops the index and then the column |
| Default safety | `EXCLUSIVE` — the strictest policy — for every pre-existing activity |
| Shield impact | None; no Shield table is touched |
| Verified state | `schedule_policy varchar(20) NOT NULL DEFAULT 'EXCLUSIVE'`, key `MUL`, index present |

---

## 7. Verification Results

All suites ran against the live application at `http://127.0.0.1:8080/` on the real `lifequest_db`. Shield users 1–9 were preserved and verified intact.

| Suite | Checks | Result |
|-------|--------|--------|
| Schedule conflict resolver unit suite | 25 | **25 / 25 passed** |
| Schedule conflict integration suite (HTTP + MySQL) | 65 | **65 / 65 passed** |
| Phase 2 integration regression | 170 | **170 / 170 passed, 8 consecutive runs** (after the harness corrections in §7.3) |
| Phase 1 authentication hardening | 37 | **37 / 37 passed** |
| Phase 1 rate limiter | 13 | **13 / 13 passed** (200 × 10, then 429 with `Retry-After`) |
| Phase 2 desktop/mobile browser suite | 42 | **42 / 42 passed** |
| Phase 1 responsive suite | 18 | **18 / 18 passed** |
| Schema verification | — | Column, default, `MUL` key and index present; policy distribution; plan/instance counts; users 1–9 intact |
| PHP syntax scan (`app/**/*.php`) | — | No syntax errors |

### 7.1 Acceptance scenarios

| Required acceptance test | Result |
|--------------------------|--------|
| Identical exclusive times rejected | PASS — rejected, original plan unchanged, message names both activities |
| Partial overlap rejected | PASS |
| Adjacent windows accepted | PASS (half-open interval) |
| Non-overlapping recurring dates accepted | PASS (WEEKLY Monday vs. Wednesday, weekend vs. weekday) |
| Compatible parallel activities accepted | PASS — saved; soft violations saved **with a warning** |
| Different users may share a slot | PASS |
| Editing cannot introduce a conflict | PASS — rejected, stored times unchanged |
| Simultaneous submissions cannot bypass validation | PASS — 2-way and 4-way races each produced exactly one plan |
| Existing activities, auth, and checklist stay operational | PASS — dashboard, catalog, checklist, history, plans, and Today API all verified |

### 7.2 Defect found and fixed during verification

`ActivityScheduleService::setEnabled()` merged the "enable" intent with the stored plan using PHP's array-union operator:

```php
$candidate = $this->normalizeConflictPlan($plan + ['is_enabled' => 1, ...]);
```

The union operator **keeps the left-hand value**, and the stored plan already contains `is_enabled = 0`. The candidate therefore still looked disabled, `isConflictEligible()` returned false, and validation was skipped — so re-enabling an overlapping plan was accepted. It was replaced with `array_replace()`, and the case is now covered by an explicit check ("Enabling an overlapping disabled plan is rejected" / "Rejected enable leaves the plan disabled"). Direct service calls and HTTP requests both reject it.

### 7.3 Test-harness corrections (not application changes)

These were genuine problems in the test code, not defects in the application:

- **PHP 8.5 `$GLOBALS` aliasing.** After the first `$GLOBALS['name'] = ...` write, a top-level `$name` and `$GLOBALS['name']` become aliased. The integration suite's two cookie-jar variables silently collapsed into one, so it saved plans as the wrong user. Session state now lives only in `$GLOBALS`, with distinct local names.
- **CSRF rotation under concurrency.** CodeIgniter regenerates the CSRF token after each verified POST, so parallel requests cannot share one token. Each parallel request now signs in with its own session and token.
- **Fixture slots.** The earlier Phase 2 suite and the browser check scheduled every activity at the same `07:00–08:00` / `07:15–07:45` slot — which is now correctly a conflict. Fixtures now allocate one non-overlapping hour per plan; the application was not loosened.
- **Slot-ladder collision (intermittent failures).** The replacement ladder was `7 + (activityId % 15)`, which derived the hour from the auto-increment activity id. Custom activity ids climb forever, so whenever a new id satisfied `id % 15 == 4` or `7` the custom plan was handed 11:00 or 14:00 — the hours reserved for standard activities 4 and 7 — and the validator rejected the save. A later attempt hashing the activity *name* failed the same way, because the names differ only by a per-run timestamp. The suite now assigns slots from a free-hour table in order of first use, so a collision cannot occur regardless of ids.
- **Flash capture.** Plan saves always answer `303`, whether the plan was stored or rejected, so asserting the status code alone could not detect a rejection — which is why these failures first surfaced as unrelated "instance not generated" checks. The suite now follows the `Location` header immediately after the POST and asserts on the flash text (`"… berhasil disimpan"`). Two mistakes were made and corrected while building this: reading `CURLOPT_REDIRECT_URL` (unpopulated when not following), and calling the reader twice on one response (the first call consumes the flash).
- **Login field.** Login authenticates by **email**, not username.
- **Unsafe SQL bind types.** `mysqli` returns scalar columns as strings; `bind_param('i', "1")` silently coerced to `1`, creating test activities for the wrong user. Values are now cast with `(int)`.

**Outcome:** after these corrections the Phase 2 integration suite passed **8 consecutive runs at 170/170**. No application behaviour was changed to make it pass.

---

## 8. Manual Retest Instructions

Automated coverage passes, but the original manual acceptance test stays **FAILED** until a person repeats it. Please run these steps in a browser.

**Preparation**

1. Sign in as any test user (login uses **email**, e.g. `p2u100931492@lifequest.local`).
2. Open `/jadwal`.
3. If the account already shows a "Jadwal yang saling bertabrakan" alert, use a different account or resolve those rows first — this test needs a clean schedule.

**Conflict rejected**

4. Save a plan for any `EXCLUSIVE` activity at **05:30–06:00**, enabled. Expect *"Jadwal aktivitas berhasil disimpan."*
5. Save a second, different activity at **05:30–06:00**, enabled. Expect a red error starting *"Jadwal tidak dapat disimpan karena bertabrakan"* that names **both** activities and their times.
6. Confirm `/jadwal` still lists only the first plan at those times, still enabled.

**Partial overlap rejected**

7. Try **05:45–06:15** for the second activity. Expect the same conflict error.

**Adjacent accepted**

8. Try **06:00–06:30** for the second activity. Expect success.
9. Try **05:00–05:30**. Expect success. (Adjacent windows must never block each other.)

**Recurrence dates**

10. Save a `WEEKLY` plan for a third activity on **Monday** at **20:00–21:00**. Expect success.
11. Save a `WEEKLY` plan for a fourth activity on **Wednesday** at **20:00–21:00**. Expect success — different days, same clock time.
12. Change the fourth activity to a `DAILY` plan at **20:00–21:00**. Expect rejection, because it now collides with the Monday plan.

**Editing**

13. Edit the plan from step 8 and move it onto **05:30–06:00**. Expect rejection.
14. Reload the page and confirm the plan's original times are unchanged.

**Parallel policy**

15. Two `PARALLEL_ALLOWED` activities sharing a slot must save. If none is available in your catalog, an admin can set `schedule_policy = 'PARALLEL_ALLOWED'` on two activities to check this.
16. A parallel pair in the **same category** must save with a **yellow warning** (*"… perlu Anda tinjau"*), not a red error.

**Enable/disable**

17. Disable a plan, then save a new plan on its now-free slot. Expect success.
18. Re-enable the disabled plan. Expect rejection with the conflict message, and the plan must remain disabled.
19. Disable any plan. Expect success — disabling is always allowed.

**Other users, existing features, and existing data**

20. Sign in as a **different** user and save a plan at the same time used above. Expect success — slots are per user.
21. Visit `/beranda`, `/aktivitas`, `/checklist`, `/riwayat`, and `/jadwal`. All must render normally, and the checklist must still list the day's activities.
22. If a plan on `/jadwal` shows a `Bertabrakan` badge, confirm it is only a warning: the row still exists with unchanged times, and no plan was disabled automatically.

**Report the result** with any step that behaved differently from the expectation. A pass at every step converts the FAILED row in `TASKS.md` to PASSED and clears the Phase 2 blocker.

---

## 9. Limitations and Follow-ups

| # | Item | Status |
|---|------|--------|
| L-1 | Direct SQL writes bypass validation; the rule is enforced at the service layer | Accepted — all user-facing writes go through the service |
| L-2 | Cross-midnight windows (e.g. 23:00–01:00) are rejected rather than split across days | Accepted and documented; a follow-up if sleep schedules need it |
| L-3 | Local test database still contains leftover test users and activities from repeated runs (users 1–9 are untouched) | Cleanup requires explicit approval; not performed |
| L-4 | Manual retest still required before Phase 2 can be accepted | Open |
| L-5 | Phase 2 changes are uncommitted on `main` (last commit `ebbb37a`) | Awaiting review |

---

## 10. Next Step

Paused for review. Phase 3 (Reward Engine) has **not** been started, no XP/RP behaviour was changed, and no PRD requirement was edited. Re-run §8, then this report, `TASKS.md`, and the Phase 2 acceptance decision can be finalized.
