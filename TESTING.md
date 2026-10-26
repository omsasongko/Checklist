# LifeQuest — Testing Plan
**Product:** LifeQuest — Daily Life RPG  
**Document Version:** 2.1.0 (aligned with PRD V2.1.0, Section 28, post Design Review corrections)  
**Status:** Phase 1 and Phase 2 test execution complete (including Phase 2 schedule conflict validation and the duplicate/silent-replacement fix); Phase 3+ tests remain planned  
**Primary Documentation Language:** English  
**Rule:** Phase 3+ test implementation requires approval of the Phase 2 acceptance report, which is still pending a user re-run of the manual acceptance test.

---

## 1. Testing Strategy Overview

Testing follows the pyramid model: unit tests (foundation), integration tests (middle), concurrency/security/performance/UI tests (top). All acceptance criteria from PRD Section 27 (AC-001 through AC-040) must be covered by at least one test type.

**Testing framework:** PHPUnit (CodeIgniter 4 built-in support)  
**Test environments:** Local Windows development machine, staging (production-like)  
**Test data:** Seed data from database seeders; isolated test database  
**Shield schema:** Test database must use Shield's actual `users` schema (verified during Phase 0)

---

## 1.1 Phase 2 Executed Verification (2026-10-23)

Phase 2 verification was executed against the running application at `http://127.0.0.1:8080/` using the real `lifequest_db` with Shield users 1-9 preserved. Test accounts created for the runs are outside the preserved range. The temporary harnesses are intentionally outside the repository.

| Suite | Checks | Result | Scope |
|-------|--------|--------|-------|
| Phase 2 integration | 170 | 170 / 170 passed (8 consecutive runs) | Seed idempotency, schema guards, catalog/search/category, custom activity CRUD, plans, instance generation, once-daily, TIMER policy state, fixed sessions, repeatable cooldown/daily/weekly limits, weekly recurrence, toggles, future-date protection, duplicates, history, APIs, timezone dates, ownership/IDOR, auth regression, zero XP/RP |
| Phase 2 duplicate schedule acceptance | 86 | 86 / 86 passed | `DS-001`–`DS-015`: duplicate detection, confirmation dialog, cancel preservation, confirmed replacement, hard-conflict rejection, ONCE_DAILY session rejection, repeatable sessions, duplicate submissions, unauthorized replacement, concurrent replacements, completion-history preservation, CSRF regeneration, conflict regression, XP/RP unchanged |
| Phase 2 schedule conflict unit | 25 | 25 / 25 passed | Resolver classification, half-open intervals, window bases, policy resolution, parallel restrictions, Indonesian messages |
| Phase 2 schedule conflict integration | 65 | 65 / 65 passed | HTTP acceptance scenarios for overlap rejection/acceptance, recurrence awareness, per-user isolation, edit and re-enable validation, existing-overlap reporting, and 2-way/4-way concurrent submissions |
| Phase 2 desktop/mobile browser | 42 | 42 / 42 passed | Dashboard, catalog, detail, custom form, plan form, checklist, AJAX completion + toast, history, mobile navigation, 14 screenshots, zero uncaught JS errors, no horizontal overflow at 1440px or 390px |
| Phase 2 JSON API | 7 | 7 / 7 passed | JSON-body completion, duplicate idempotency, zero XP/RP, malformed JSON 400 with Indonesian message |
| Phase 2 schema verification | Passed | Passed | `activities.schedule_policy` type/default/index, policy distribution, plan and instance counts, users 1-9 intact |
| Phase 1 authentication hardening | 37 | 37 / 37 passed | Registration, login, POST logout, CSRF rejection, rate limiting, session survival, dashboard regression |
| Phase 1 rate limit | 13 | 13 / 13 passed | 10 allowed attempts followed by 429 with Indonesian message and Retry-After |
| Phase 1 responsive | 18 | 18 / 18 passed | Desktop/mobile landing, login, dashboard, navigation, logout, no horizontal overflow |

**Phase 2 database assertions:**

- `users.id` and all Phase 2 user FKs are `INT UNSIGNED`.
- `daily_activity_instances.occurrence_identity` is `NOT NULL` and has a unique index.
- `activities.schedule_policy` is `VARCHAR(20) NOT NULL DEFAULT 'EXCLUSIVE'` with index `idx_activities_schedule_policy`.
- `user_activity_plans.session_ordinal` is `TINYINT UNSIGNED NOT NULL DEFAULT 0`, and the unique key is `uk_uap_user_activity_session (user_id, activity_id, session_ordinal)`. Existing rows all carry ordinal `0`, so no schedule changed.
- The `AddPlanSessionOrdinal` rollback guard was verified: while any activity holds more than one session, `down()` throws an Indonesian error and refuses to run rather than deleting schedules.
- Seeded counts: 10 categories, 11 reward groups, 4 verification policies, 10 standard activities, 2 fixed sessions, and baseline configuration versions.
- Reward group budget total is 140 RP including the `STREAK` group.
- Phase 2 has no reward-engine tables: `point_transactions`, `daily_xp_totals`, `daily_rp_totals`, `daily_reward_budgets`, `user_lifetime_stats`, or `user_season_stats`.
- Existing users 1-9 and all Shield authentication tables remain intact.

**Screenshots:** Phase 2 desktop and mobile screenshots are stored in the local test artifact directory `C:\Users\pujiste001\AppData\Local\Temp\opencode\lifequest_phase2_screens`. They are not committed to the repository.

### Phase 2 Test Boundaries

The following are intentionally deferred and were not treated as Phase 2 failures:

- XP/RP calculation, point ledger, daily caps, reward budgets, partial rewards, and deadlock/lock-order tests (Phase 3).
- Timer start/finish, evidence upload, risk scoring, review queue, and appeals (Phase 4).
- Rank, leaderboard, achievements, streaks, and season lifecycle (Phase 5/6).
- True simultaneous multi-process completion tests; Phase 2 verifies duplicate-click idempotency and unique occurrence constraints in integration tests.
- Reward-engine concurrency (lock ordering, deadlock retry, budget serialization) remains Phase 3. The concurrency checks below cover the schedule-write lock only.

### Phase 2 Schedule Conflict Validation Tests (P2-09)

Added after the schedule-overlap defect reported during Phase 2 manual acceptance. Full detail, including the defect analysis and manual retest steps, is in `PHASE2_SCHEDULE_CONFLICT_REPORT.md`.

**Resolver unit tests — `phase2_conflict_unit_test.php` (25 checks):**

| # | Case | Expected |
|---|------|----------|
| U-01 | Identical exclusive windows | `HARD_CONFLICT` / `EXCLUSIVE_OVERLAP` |
| U-02 | Partial overlap exclusive | `HARD_CONFLICT` |
| U-03 | One window contained in the other | `HARD_CONFLICT` |
| U-04 | Adjacent windows (05:30-06:00, 06:00-06:30) | `NO_CONFLICT` (half-open interval) |
| U-05 | Disjoint windows | `NO_CONFLICT` |
| U-06 | Overlap on non-shared recurrence dates | `NO_CONFLICT` |
| U-07 | Different users, identical windows | Not evaluated against each other |
| U-08 | Both `PARALLEL_ALLOWED`, restrictions satisfied | `NO_CONFLICT` |
| U-09 | Either side `EXCLUSIVE` | `HARD_CONFLICT` (exclusive wins) |
| U-10 | Parallel pair, same category | `SOFT_CONFLICT` |
| U-11 | Parallel pair, different verification family | `SOFT_CONFLICT` |
| U-12 | Parallel pair, both require evidence | `SOFT_CONFLICT` |
| U-13 | Session-defined window derived from sessions | `SESSION_DEFINED` bounds |
| U-14 | No times and no sessions | `UNBOUNDED` 00:00-24:00 |
| U-15 | Partial endpoint fallbacks | Start 00:00 / end 24:00 |
| U-16 | Missing policy treated as `EXCLUSIVE` | `HARD_CONFLICT` |
| U-17 | Invalid policy value rejected | Validation error |
| U-18 | Indonesian messages name both activities and times | Message assertions |

**HTTP/MySQL integration tests — `phase2_conflict_integration_test.php` (65 checks):**

| # | Case | Expected |
|---|------|----------|
| I-01 | Original manual scenario: first 05:30-06:00 exclusive plan | Saved |
| I-02 | Identical second exclusive plan | Rejected, first plan unchanged, Indonesian message names both activities |
| I-03 | Partial overlap (05:45-06:15) | Rejected |
| I-04 | Contained overlap | Rejected |
| I-05 | Adjacent slot (06:00-06:30) | Accepted |
| I-06 | OVERLAP `00:00` boundary and same-window different recurrence day | Accepted |
| I-07 | DAILY plan over a WEEKLY weekday slot | Rejected |
| I-08 | WEEKLY plans on non-overlapping weekdays | Accepted |
| I-09 | Different users share 05:30-06:00 | Accepted |
| I-10 | Updating a plan into an overlap | Rejected, original times preserved |
| I-11 | Re-saving a plan on its own times | Accepted |
| I-12 | Enabling a disabled overlapping plan | Rejected, plan stays disabled |
| I-13 | Parallel policy pair with soft violations | Saved with warning, not an error |
| I-14 | Session-defined activity without explicit times vs. explicit overlap | Rejected; adjacent to derived end accepted |
| I-15 | Unbounded plan with other enabled exclusive plans | Rejected; accepted when alone |
| I-16 | Explicit plan vs. existing unbounded plan | Rejected (same clock) |
| I-17 | End time before start time | Rejected with explanation |
| I-18 | Legacy overlapping rows inserted directly | Reported on `/jadwal`, never auto-modified |
| I-19 | Unrelated save while an existing overlap is reported | Accepted |
| I-20 | Two simultaneous submissions to one slot | Exactly one plan created |
| I-21 | Four simultaneous submissions to one slot | Exactly one plan created, no duplicates |
| I-22 | Existing pages and Today API after the change | Still render / still return JSON |
| I-23 | Reward tables after the change | Still absent; zero XP/RP |

**Why concurrency tests sign in per request:** CodeIgniter regenerates the CSRF token after each verified POST, so two simultaneous requests cannot share one token (the second would fail with 403 before reaching the validator). Each parallel request therefore uses its own signed-in session and token, while all of them target the same user's schedule. That keeps the race on the schedule-row lock, which is what the test is meant to prove.

**Fixture note:** the earlier Phase 2 suite and browser check originally scheduled every activity at the same `07:00-08:00` / `07:15-07:45` slot. That is now a real conflict, so their fixtures were changed to allocate non-overlapping slots. Two later attempts to derive the slot from the activity id, and then from a hash of the activity name, produced intermittent rejections; the suite now assigns each plan the next free hour from a table in order of first use. Asserting only the `303` status of a plan POST is not sufficient either — a rejected save redirects the same way — so those checks now read the flash message and require "berhasil disimpan". The application behaviour was not weakened to make the tests pass.

**Fixture note (duplicate-schedule suite):** the confirmation is rendered by the page the POST redirects to, and CodeIgniter consumes flash data on the first rendered page. The suite therefore captures the redirect target's body once and asserts both the flash text and the modal markup from that single response; a second `GET` would see neither. Successor fixtures also avoid reusing a window that a previous concurrency test may have left behind.

---

## 1.2 Phase 2 Duplicate Schedule & Silent Replacement Tests (P2-10)

**Harness:** `phase2_duplicate_schedule_test.php` — real HTTP against `http://127.0.0.1:8080/` plus direct PDO assertions. **86 / 86 checks passed.**

| ID | Scenario | Expected | Result |
|----|----------|----------|--------|
| DS-001 | Create a new schedule | ALLOW | PASS — one row, `session_ordinal = 0`, window stored |
| DS-002 | Create an identical schedule | BLOCK DUPLICATE | PASS — no row created, none modified, Indonesian notice |
| DS-003 | Same activity with a different time | SHOW CONFIRMATION | PASS — modal renders both schedules, both actions, existing plan id, **Batal** default |
| DS-004 | Cancel replacement | KEEP ORIGINAL | PASS — id, times, `is_enabled`, `updated_at` all unchanged |
| DS-005 | Confirm valid replacement | UPDATE | PASS — row id preserved, no new row, new window stored |
| DS-006 | Replacement introduces a hard conflict | REJECT | PASS — original window intact; conflict-free replacement still allowed |
| DS-007 | Add second session to ONCE_DAILY | REJECT if limits prohibit | PASS — rejected with a daily-limit message; dialog omits **Tambah Sesi** |
| DS-008 | Add supported repeatable session | ALLOW | PASS — distinct ordinal, original intact, limit enforced, over-limit and duplicate-session rejected |
| DS-009 | Duplicate form submission | NO DUPLICATE | PASS — one row after two identical submissions |
| DS-010 | Unauthorized replacement attempt | REJECT | PASS — cross-user id rejected, victim row unmodified, foreign private activity unschedulable |
| DS-011 | Concurrent replacement requests | CONSISTENT RESULT | PASS — 4 simultaneous replacements → 1 row, same id, consistent window; 4 simultaneous creates → 1 row |
| DS-012 | Existing completion history | PRESERVED | PASS — instance survives, status preserved, occurrence identity unchanged, no instances deleted |
| DS-013 | Repeated submission after CSRF regeneration | NO UNEXPECTED 403 | PASS — token rotates, no 403, handled as a confirmation |
| DS-014 | Existing schedule-conflict regression tests | PASS | PASS — adjacency allowed, overlap rejected, edit-into-conflict rejected, re-enable rejected, all pages render |
| DS-015 | XP/RP behavior | UNCHANGED | PASS — no reward tables, all instances `NOT_ELIGIBLE`, seeded XP untouched, API still JSON |

**Concurrency approach:** each parallel worker signs in with its own cookie jar and obtains its own CSRF token before the simultaneous POST, because CodeIgniter rotates the token after every verified POST. All workers target the same user's plan, so the race exercises the schedule-row lock rather than the CSRF filter.

---

## 2. Unit Testing (PRD Section 28.1)

### Required Coverage

| Unit | Scenario | Test Cases | Related AC |
|------|----------|------------|------------|
| XP calculation | Max 150 XP per local activity date via `daily_xp_totals` | Normal completion, cap exceeded, partial cap, custom activity (max 10 XP), independent from RP | AC-006, AC-007 |
| RP calculation | Max 140 RP per competition date per season via `daily_rp_totals` | Normal completion, reward group budget exceeded, daily cap exceeded, partial reward, per-season enforcement | AC-004, AC-005 |
| Reward group budgets | Per user, per competition date, per season, per group | Budget remaining, budget exhausted, partial award, no carry-over, season-scoped | AC-004 |
| Lifetime level calculation | Formula: 100 × (L-1) × L / 2 | Level 1 (0 XP), Level 2 (100 XP), Level 3 (300 XP), boundary cases | AC-017 (indirect) |
| Rank determination | Confirmed RP → rank tier | Warrior (0-299), Elite (300-699), Master (700-1,199), Grandmaster, Epic, Legend, Mythic, Mythical Honor, Mythical Glory, Mythical Immortal | AC-017 |
| Streak eligibility | 60% completion, 5 activities, 3 reward groups | Eligible day, ineligible day (below 60%), below 5 activities, below 3 groups | AC-031 |
| Risk scoring | Score 0-100 with thresholds | Low (0-29), Medium (30-69), High (70-100), reason codes generated | AC-012 |
| Season date calculation | 60 days, Asia/Jakarta | Start/end boundaries, season transitions, grace period | AC-019, AC-022 |
| Activity repeat validation | ONCE_DAILY, FIXED_SESSIONS, REPEATABLE, WEEKLY | Duplicate prevention via `occurrence_identity`, session uniqueness, occurrence uniqueness | AC-003 |
| Occurrence identity uniqueness | Server-generated `occurrence_identity` | Non-null uniqueness, no NULL ambiguity, same user/activity/date different sessions allowed | AC-003, AC-024 |
| Scoped achievements | `achievement_scope` LIFETIME/SEASON | Lifetime award once, season award per season, no duplicates within scope | AC-016 |

### Unit Test Files (Planned)
- `tests/Unit/Services/XPServiceTest.php`
- `tests/Unit/Services/RPServiceTest.php`
- `tests/Unit/Services/RewardBudgetServiceTest.php`
- `tests/Unit/Services/LifetimeLevelServiceTest.php`
- `tests/Unit/Services/RankingServiceTest.php`
- `tests/Unit/Services/StreakServiceTest.php`
- `tests/Unit/Services/RiskScoringServiceTest.php`
- `tests/Unit/Services/SeasonServiceTest.php`
- `tests/Unit/Services/ActivityRepeatTest.php`
- `tests/Unit/Services/OccurrenceIdentityTest.php`
- `tests/Unit/Services/AchievementScopeTest.php`

---

## 3. Integration Testing (PRD Section 28.2)

### Required Scenarios

| # | Scenario | Description | Related AC |
|---|----------|-------------|------------|
| INT-01 | Activity completion → reward ledger | Complete activity → verify `point_transactions`, `daily_xp_totals`, `daily_rp_totals`, `daily_reward_budgets`, `user_lifetime_stats`, `user_season_stats` all updated atomically with correct lock order | AC-001, AC-025 |
| INT-02 | Timer completion → verification | Start timer → finish timer → server validates duration → verification state → reward; verify ownership validation, overlap prevention, idempotent finish | AC-009 |
| INT-03 | Evidence submission → review | Upload photo → file validation → storage outside web root → risk evaluation → auto decision or admin queue; verify no direct URL access | AC-010, AC-011 |
| INT-04 | Approved verification → confirmed RP | Pending verification → admin approves → RP confirmed → leaderboard eligible; verify budget revalidation at approval time | AC-013, AC-014, AC-015 |
| INT-05 | Rejected verification → zero competitive reward | Rejected → RP not confirmed → leaderboard unaffected | AC-014 |
| INT-06 | Season closure and finalization | Close season → activate new → finalize old → verify idempotency via `scheduled_job_runs.execution_key` | AC-019, AC-020, AC-021, AC-023 |
| INT-07 | Appeal processing | Rejected → user appeals → admin reviews → decision → audit record | AC-016 |
| INT-08 | Administrative corrections | Compensating transaction → original preserved → aggregates updated → rank recalculated; verify original transaction never modified | AC-027 |
| INT-09 | Soft delete user | Deactivate user via `is_active` → verify history preserved in `point_transactions`, `season_results`, `activity_verification_logs`, `admin_audit_logs` | AC-028, AC-034 |
| INT-10 | Evidence authorization | Attempt direct URL access to evidence file → verify 403/404; controller-mediated download with ownership check → verify success for owner, failure for non-owner | AC-010, AC-029 |

### Integration Test Files (Planned)
- `tests/Integration/ActivityCompletionTest.php`
- `tests/Integration/TimerVerificationTest.php`
- `tests/Integration/EvidenceSubmissionTest.php`
- `tests/Integration/EvidenceAuthorizationTest.php`
- `tests/Integration/VerificationApprovalTest.php`
- `tests/Integration/SeasonFinalizationTest.php`
- `tests/Integration/AppealProcessingTest.php`
- `tests/Integration/AdminCorrectionTest.php`
- `tests/Integration/SoftDeleteTest.php`

> **Phase 2 note:** The executed Phase 2 integration harness covers the activity/catalog/plan/checklist subset only. It does not create or modify reward-ledger tables, so INT-01 and INT-03 through INT-10 remain planned for later phases.

---

## 4. Concurrency Testing (PRD Section 28.3)

### Required Scenarios

| # | Scenario | Description | Related AC |
|---|----------|-------------|------------|
| CONC-01 | Duplicate button clicks | Same completion request submitted twice rapidly → at most one reward transaction (idempotency key) | AC-002, AC-024 |
| CONC-02 | Multiple simultaneous completion requests | Two concurrent requests for same activity occurrence → one success, one returns existing result | AC-024 |
| CONC-03 | Concurrent point awards | Parallel completions for different activities → daily caps not exceeded (150 XP, 140 RP) | AC-005, AC-006, AC-038 |
| CONC-04 | Concurrent administrator approvals | Two admins approving pending submissions for same user/day → budgets not exceeded | AC-038 |
| CONC-05 | Concurrent season scheduler executions | Season finalization run twice simultaneously → no duplicate results or rewards (execution_key idempotency) | AC-023, AC-037 |
| CONC-06 | Concurrent reward budget updates | Parallel budget decrements for same user/date/group → serialized, no over-award | AC-004 |
| CONC-07 | Deadlock recovery | Concurrent transactions acquire locks in different orders → deadlock detected → retry succeeds | AC-025 |

### Concurrency Test Approach
- Use database transactions with row-level locking (`SELECT ... FOR UPDATE`)
- Verify consistent lock ordering: `daily_rp_totals` → `daily_reward_budgets` → `daily_xp_totals`
- Simulate concurrent HTTP requests using parallel test clients
- Verify idempotency keys prevent duplicate transactions
- Verify unique constraints catch race conditions at database level
- Verify deadlock detection and retry logic

### Concurrency Test Files (Planned)
- `tests/Integration/ConcurrencyTest.php`

---

## 5. Security Testing (PRD Section 28.4)

### Required Coverage

| # | Area | Test Cases | Related AC |
|---|------|------------|------------|
| SEC-01 | Authentication | Valid login, invalid credentials, session hijacking, rate limiting on login | AC-028 |
| SEC-02 | Authorization | User cannot access admin endpoints; user cannot modify another user's data; IDOR prevention | AC-028, AC-029 |
| SEC-03 | CSRF | CSRF token validation on state-changing requests | AC-028 |
| SEC-04 | XSS | Input sanitization, output escaping, stored XSS in activity names, comments | AC-028 |
| SEC-05 | SQL injection | Parameterized queries; malicious input in activity IDs, user inputs | AC-028 |
| SEC-06 | File upload | MIME validation, content validation, file size (5 MB max), randomized filenames, path traversal prevention, storage outside web root | AC-010, AC-029 |
| SEC-07 | Evidence authorization | Direct URL access to evidence → blocked; controller-mediated download → owner authorized, non-owner denied | AC-010, AC-029 |
| SEC-08 | IDOR | Accessing another user's activity instance, evidence, profile via manipulated IDs | AC-010, AC-029 |
| SEC-09 | Session security | Session fixation, session timeout, secure cookies, HTTPS enforcement | AC-028 |
| SEC-10 | Sensitive information exposure | No private data in API responses, no evidence on public leaderboard, no email in public views | AC-029 |

### Security Test Files (Planned)
- `tests/Security/AuthSecurityTest.php`
- `tests/Security/AuthorizationTest.php`
- `tests/Security/FileUploadSecurityTest.php`
- `tests/Security/EvidenceAuthorizationTest.php`
- `tests/Security/InputValidationTest.php`

---

## 6. Performance Testing (PRD Section 28.5)

### Workload Target (PRD Section 25.1)
- 1,000 Daily Active Users
- 10 activity submissions per active user per day
- ~10,000 activity submissions per day
- Future expansion target: 10,000 DAU

### Performance Targets (PRD Section 25.2)
| Endpoint | Target | Measurement |
|----------|--------|-------------|
| Dashboard | < 2 seconds | Page load time |
| Checklist API | < 500 milliseconds | API response time |
| Leaderboard (paginated) | < 2 seconds | Query + serialization time |

### Metrics to Measure
- Request latency (p50, p95, p99)
- Database query performance (slow query log)
- Reward transaction throughput (transactions/second)
- Leaderboard query latency
- Scheduler performance (season transition time)
- Error rates under load
- Deadlock frequency under concurrent load

### Performance Test Files (Planned)
- `tests/Performance/LoadTest.php`
- `tests/Performance/LeaderboardPerformanceTest.php`

---

## 7. User Interface Testing (PRD Section 28.6)

### Verification Items

| Item | Description |
|------|-------------|
| Desktop browsers | Chrome, Firefox, Edge on Windows |
| Mobile browsers | Chrome (Android), Safari (iOS) |
| Responsive layouts | Mobile-first Bootstrap grid, breakpoints |
| Checklist feedback | Completion state, pending indicator, success/error notification |
| Loading indicators | Spinner/skeleton during API calls |
| Error states | Validation errors, server errors, network errors |
| Rank visualization | Rank emblem, rank progress bar |
| Achievement notifications | Toast/alert when achievement unlocked |
| Indonesian language | All UI text in Indonesian |
| Accessibility | Keyboard interaction, readable contrast, responsive layouts | AC-032 |
| No public evidence URLs | Evidence not directly accessible via URL | AC-010 |

### UI Test Files (Planned)
- Browser-based manual testing checklist
- `tests/UI/` (if browser automation is introduced later)

---

## 8. Acceptance Criteria Test Mapping (PRD Section 27)

| AC ID | Title | Test Type | Test File |
|-------|-------|-----------|-----------|
| AC-001 | Standard Completion | Integration | ActivityCompletionTest |
| AC-002 | Duplicate Request | Concurrency | ConcurrencyTest |
| AC-003 | Repeatable Sessions | Unit + Integration | ActivityRepeatTest, ActivityCompletionTest |
| AC-004 | Reward Group Limit | Unit + Integration | RewardBudgetServiceTest, ActivityCompletionTest |
| AC-005 | Maximum Daily RP (140) | Unit | RPServiceTest |
| AC-006 | Maximum Daily XP (150) | Unit | XPServiceTest |
| AC-007 | Custom Activities (0 RP) | Unit | XPServiceTest, RPServiceTest |
| AC-008 | Self-Check Validation | Integration | VerificationApprovalTest |
| AC-009 | Timer Validation | Integration | TimerVerificationTest |
| AC-010 | Photo Evidence Access | Security | EvidenceAuthorizationTest |
| AC-011 | Duplicate Evidence | Unit | RiskScoringServiceTest |
| AC-012 | Risk Scoring | Unit | RiskScoringServiceTest |
| AC-013 | High-Risk Submission | Integration | VerificationApprovalTest |
| AC-014 | Pending Rewards | Integration | VerificationApprovalTest |
| AC-015 | Administrator Review | Integration | VerificationApprovalTest |
| AC-016 | Appeals | Integration | AppealProcessingTest |
| AC-017 | Rank Promotion | Unit | RankingServiceTest |
| AC-018 | Leaderboard Ordering | Unit + Integration | LeaderboardPerformanceTest |
| AC-019 | Season Duration (60 days) | Unit | SeasonServiceTest |
| AC-020 | Season Reset | Integration | SeasonFinalizationTest |
| AC-021 | Season History | Integration | SeasonFinalizationTest |
| AC-022 | Pending Season Submissions | Integration | SeasonFinalizationTest |
| AC-023 | Season Finalization | Concurrency | ConcurrencyTest |
| AC-024 | Concurrent Completion | Concurrency | ConcurrencyTest |
| AC-025 | Transaction Rollback | Integration | ActivityCompletionTest |
| AC-026 | Administrator Configuration | Integration | AdminCorrectionTest |
| AC-027 | Reward Reversal | Integration | AdminCorrectionTest |
| AC-028 | Authorization | Security | AuthorizationTest |
| AC-029 | Privacy | Security | AuthorizationTest, EvidenceAuthorizationTest |
| AC-030 | Evidence Retention | Integration | EvidenceSubmissionTest |
| AC-031 | Streak Calculation | Unit | StreakServiceTest |
| AC-032 | Accessibility | UI | UI manual checklist |
| AC-033 | Performance | Performance | LoadTest |
| AC-034 | Auditability | Integration | AdminCorrectionTest, SoftDeleteTest |
| AC-035 | Timezone Boundaries | Unit | SeasonServiceTest |
| AC-036 | Configuration Versioning | Integration | ActivityCompletionTest |
| AC-037 | Season Concurrency | Concurrency | ConcurrencyTest |
| AC-038 | Pending Reward Budget | Concurrency | ConcurrencyTest |
| AC-039 | Daily Target Fairness | Unit | StreakServiceTest |

> **Phase 2 schedule conflict validation** does not map to a new PRD acceptance criterion. It strengthens AC-003 (repeatable/scheduled activity integrity) and AC-035 (timezone boundaries) for personal plans, and is verified by the unit and integration suites listed in §1.1.

> **Phase 2 duplicate schedule fix** likewise maps to no new PRD acceptance criterion. It reinforces AC-003 by requiring explicit user confirmation before an existing schedule changes, and is verified by the `DS-001`–`DS-015` acceptance suite in §1.2.
| AC-040 | Production Security | Security Review | All security tests |

---

## 9. New Test Scenarios for Design Review Corrections

| # | Scenario | Description | Related Correction |
|---|----------|-------------|-------------------|
| NEW-01 | Shield PK type verification | Verify `users.id` type matches custom table `user_id` columns | C1 — Shield compatibility |
| NEW-02 | No cascade delete | Delete user → verify historical tables (`point_transactions`, `season_results`, etc.) still have records | C4 — Protect historical records |
| NEW-03 | Soft delete user | Set `is_active = 0` → verify user cannot authenticate but history is preserved | C4 — Protect historical records |
| NEW-04 | Occurrence identity non-null | Insert two rows with NULL `occurrence_identity` → verify MySQL rejects (NOT NULL constraint) | C3 — Activity occurrence uniqueness |
| NEW-05 | Separate XP/RP totals | Update XP total → verify RP total row is not locked; update RP total → verify XP total row is not locked | C2 — Independent aggregates |
| NEW-06 | Deadlock recovery | Two transactions with reversed lock order → verify deadlock detected and retried successfully | C7 — Lock ordering |
| NEW-07 | Evidence outside web root | Place file in `public/` → verify 404; place in `writable/storage/evidence/` → verify accessible only via controller with auth | C6 — Secure evidence storage |
| NEW-08 | Season-scoped achievement | Award same achievement in Season 1 and Season 2 → verify both allowed; award twice in Season 1 → verify rejected | C5 — Scoped achievements |
| NEW-09 | Timer overlap | Start two overlapping timers for same activity → verify second is rejected | C9 — Timer integrity |
| NEW-10 | Timer idempotent finish | Finish timer twice → verify only one reward transaction | C9 — Timer integrity |
| NEW-11 | Concurrent achievement award | Two simultaneous attempts to award same LIFETIME achievement → only one succeeds (database-enforced via `season_coalesce`) | C5 — Achievement uniqueness |
| NEW-12 | Lock order verification | Verify reward transactions acquire locks in order: rp_totals → reward_budgets → xp_totals | C7 — Lock ordering |
| NEW-13 | Concurrent budget cap enforcement | Parallel completions for different activities → daily XP (150) and RP (140) caps never exceeded | C7 — Lock ordering |
| NEW-14 | Shield `users` FK compatibility | Verify custom table `user_id` column type matches Shield `users.id` type | C1 — Shield compatibility |
| NEW-15 | Point correction concurrent safety | Simultaneous compensating transactions → no double reversal; original transaction preserved | C7 — Lock ordering |
| NEW-16 | Schedule conflict classification | Identical/partial/contained exclusive overlaps → `HARD_CONFLICT`; adjacent windows → `NO_CONFLICT`; parallel violations → `SOFT_CONFLICT` | P2-09 — Schedule integrity |
| NEW-17 | Schedule validation covers every write path | Create, update, and re-enable all run the same validation; a rejected write leaves stored times and `is_enabled` unchanged | P2-09 — Schedule integrity |
| NEW-18 | Concurrent schedule writes | Two and four simultaneous submissions for one slot → exactly one plan, validation never bypassed | P2-09 — Schedule integrity |
| NEW-19 | No silent schedule replacement | Resubmitting a schedule for an already-scheduled activity never changes the stored row; it returns a confirmation request | P2-10 — Duplicate schedule integrity |
| NEW-20 | Explicit plan operations | `CREATE`, `REPLACE`, and `ADD_SESSION` behave distinctly; an unknown or missing operation is rejected rather than inferred | P2-10 — Duplicate schedule integrity |
| NEW-21 | Cancel preserves the schedule | Cancelling a replacement leaves id, times, recurrence, `is_enabled`, and `updated_at` unchanged | P2-10 — Duplicate schedule integrity |
| NEW-22 | Session policy enforcement | Additional sessions allowed only for eligible repeat types within their completion limits; `ONCE_DAILY` always rejected | P2-10 — Duplicate schedule integrity |
| NEW-23 | Stale or foreign confirmation id | A confirmation naming a row that no longer exists or belongs to another user is rejected; it never falls back to another row | P2-10 — Duplicate schedule integrity |

---

## 10. Test Data Strategy

- Use CodeIgniter Seeders for baseline data (categories, reward groups, activities, ranks, achievements, first season)
- Each test class uses fresh database state (migrations rolled back and re-run, or transactions rolled back after each test)
- Test users created via Shield with known credentials
- Test evidence files are small placeholder images (not real photos)
- No production data in test database
- Shield `users` schema must match production Shield version

---

## 11. Test Execution Commands

| Command | Purpose |
|---------|---------|
| `php spark test` | Run all tests |
| `php spark test --group unit` | Run unit tests only |
| `php spark test --group integration` | Run integration tests only |
| `php spark test --group concurrency` | Run concurrency tests only |
| `php spark test --group security` | Run security tests only |
| `php spark test --group performance` | Run performance tests only |

Commands will be finalized in AGENTS.md once the project is initialized.

---

## 12. Release Validation

Before any phase is considered complete:
- All unit tests for that phase pass
- All integration tests for that phase pass
- All applicable concurrency tests pass
- All applicable security tests pass
- Performance targets met (for Phase 8)
- UI verified (for Phase 8)
- All acceptance criteria for that phase satisfied
- No unresolved critical or high-severity defects
- NEW-01 through NEW-18 design review correction tests pass

---

## 13. Unresolved Testing Decisions

| # | Decision | Options | Impact |
|---|----------|---------|--------|
| T1 | Browser automation | **Resolved:** Playwright with Microsoft Edge headless | Desktop/mobile checks and screenshots executed |
| T2 | Load testing tool | Apache JMeter vs custom script vs cloud service | Affects Phase 8 performance testing |
| T3 | Test database | **Resolved for Phase 2:** real MySQL `lifequest_db` with dedicated test accounts; isolated database remains a Phase 8 hardening item | Current runs preserve users 1-9 and verify real MySQL constraints |
| T4 | Shield test schema | **Resolved:** real Shield migrations and `INT UNSIGNED` user FKs | FK compatibility verified |

---

## 14. Next Step

Phase 2 testing is complete. Await approval of the Phase 2 acceptance report before writing Phase 3 reward-engine tests. Phase 3 tests must cover XP/RP calculation, idempotency, lock ordering, daily caps, reward group budgets, partial rewards, and deadlock retry.

**Open item:** the original Phase 2 manual acceptance test remains marked FAILED until the user re-runs it using the steps in `PHASE2_SCHEDULE_CONFLICT_REPORT.md` §8. Automated coverage for the same scenario passes (65/65), but a manual confirmation is still required.
