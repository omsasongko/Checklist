# LifeQuest — Phase 2 Explicit Schedule Replacement Selection Report

**Validation date:** October 26, 2026  
**Scope:** Explicit selection of an existing schedule during replacement  
**Status:** Implemented and automatically validated; manual acceptance remains required

## Summary

The replacement modal previously displayed one schedule selected by server-side
ordering and JavaScript automatically submitted that schedule's ID. This could
replace the wrong session when an activity had multiple schedules.

The modal now receives and displays every existing schedule for the activity.
Each schedule is selectable by radio button and includes recurrence, time
window, timezone, and active/inactive status. No schedule is preselected. The
replacement button remains disabled until a target is selected.

The server continues to resolve the submitted schedule ID against the current
authenticated user's activity plans while holding the existing per-user
schedule lock. It also verifies the selected row's `updated_at` version before
writing, rejects stale selections, rejects identical replacements, and retains
the existing conflict and recurrence-limit validation paths.

## Changed Files

- `app/Controllers/ActivityPlans.php`
  - Accepts `confirm_plan_updated_at` from the replacement form.
  - Carries the complete `existing_plans` payload to the view.
- `app/Services/ActivityScheduleService.php`
  - Returns all existing schedule summaries in confirmation data.
  - Includes the schedule ID, status, and `updated_at` version in each summary.
  - Validates the selected ID, ownership/activity membership, stale version,
    and identical replacement before updating.
  - No schema, migration, reward, or ranking changes were made.
- `app/Views/plans/index.php`
  - Renders all existing schedules as radio-selectable cards.
  - Displays recurrence, start/end time, timezone, and status.
  - Keeps the before/after comparison visible.
  - Disables replacement until a radio target is selected.
  - Adds the hidden selected ID and version fields.
- `public/assets/js/plan-confirm.js`
  - Copies the selected schedule ID/version into the form.
  - Prevents replacement without a selection.
  - Disables action buttons after submission to reduce duplicate submissions.

No database schema or migration was changed.

## Server-Side Guarantees

1. The selected ID must belong to the authenticated user and submitted activity.
2. Missing or invalid IDs are rejected; the service never falls back to a
   guessed schedule when multiple rows exist.
3. The submitted `updated_at` must match the row read inside the write lock;
   stale requests are rejected.
4. Replacement candidates are revalidated against other schedules using the
   existing HARD_CONFLICT/SOFT_CONFLICT/NO_CONFLICT resolver.
5. Existing recurrence and additional-session limits remain enforced.
6. Only the selected row is updated; its ID/session identity remains unchanged.
7. Batal performs no POST and therefore changes no database records.
8. Existing CSRF middleware and ownership checks remain active.

## Test Evidence

### Syntax and static checks

- `ActivityPlans.php`: PHP syntax PASS
- `ActivityScheduleService.php`: PHP syntax PASS
- `plans/index.php`: PHP syntax PASS
- `plan-confirm.js`: Node syntax PASS
- `git diff --check`: PASS

### Targeted and regression suites

| Test | Result |
|---|---:|
| Conflict resolver unit suite | 25/25 PASS |
| Conflict HTTP/MySQL integration suite | 65/65 PASS |
| Browser regression suite | 42/42 PASS |
| Existing duplicate/multi-session harness | 86/87 PASS |

The one existing-harness failure is the prior assertion that re-saving an
identical replacement should be allowed. The requested acceptance criteria
explicitly require **Identical replacement: rejected**, and the implementation
now rejects it without changing the row. This is an expected test expectation
change, not an unrelated regression.

The existing harness was also adapted outside the repository to provide the new
replacement version token for its replacement scenarios. Its remaining tests
cover ownership, hard conflicts, adjacent intervals, concurrent writes,
completion-history preservation, CSRF behavior, session identity, zero XP/RP,
and unchanged Phase 2 pages. The legacy concurrency fixture includes separate
CSRF timing assumptions and is not used as evidence that the new UI selection
flow was manually accepted.

## Acceptance-Test Status

| Acceptance test | Automated status |
|---|---|
| One existing schedule: correct target | Covered by selected-ID server path and existing replacement tests |
| Two or more schedules: all displayed | Covered by payload/view implementation; manual browser confirmation pending |
| No selection: replacement disabled | Covered by rendered disabled button and JS guard |
| Select A: only A changes | Server path preserves target ID; manual confirmation pending |
| Select B: only B changes | Server path preserves target ID; manual confirmation pending |
| Identical replacement rejected | PASS by service validation |
| Conflicting replacement rejected | PASS through conflict integration/regression tests |
| Cancel: no changes | PASS through existing confirmation/cancel coverage |
| Unauthorized schedule ID rejected | PASS through existing ownership coverage |
| Concurrent/stale replacement rejected | Stale version validation implemented; concurrency infrastructure remains covered by existing suite |
| Authentication and Phase 2 behavior unchanged | Conflict integration 65/65 and browser 42/42 PASS |

## Manual Acceptance Required

Use a dedicated account and an approved isolated fixture. Do not create or
delete shared-database fixtures without approval.

1. Create or use an eligible repeatable activity with two schedules.
2. Submit a changed schedule to open **Aktivitas Sudah Terjadwal**.
3. Confirm both existing schedules are displayed with recurrence, time,
   timezone, and status, and that neither radio button is preselected.
4. Confirm **Ganti Jadwal** is disabled before selection.
5. Select schedule A, replace it, and verify only A changes; B, session
   identity, completion records, and history remain unchanged.
6. Repeat with schedule B and verify only B changes.
7. Test Batal, identical replacement, hard conflict, unauthorized ID, and a
   stale request from a second tab.
8. Confirm Tambah Jadwal remains subject to daily/weekly activity limits.
9. Confirm CSRF, duplicate submission protection, and zero XP/RP behavior.

## Final Assessment

The surgical fix is implemented with minimal changes and no schema or reward
changes. Automated evidence supports the server-side selection, ownership,
stale-request, conflict, history, and regression protections. The UI behavior
for choosing schedule A versus B still requires manual browser acceptance.

**Do not mark the defect or Phase 2 fully accepted until manual acceptance and
explicit user approval are recorded.**
