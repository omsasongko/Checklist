# LifeQuest — Testing Plan
**Product:** LifeQuest — Daily Life RPG  
**Document Version:** 2.1.0 (aligned with PRD V2.1.0, Section 28, post Design Review corrections)  
**Status:** For Review — Planning Only  
**Primary Documentation Language:** English  
**Rule:** No test code may be written until this plan and all documentation are approved.

---

## 1. Testing Strategy Overview

Testing follows the pyramid model: unit tests (foundation), integration tests (middle), concurrency/security/performance/UI tests (top). All acceptance criteria from PRD Section 27 (AC-001 through AC-040) must be covered by at least one test type.

**Testing framework:** PHPUnit (CodeIgniter 4 built-in support)  
**Test environments:** Local Windows development machine, staging (production-like)  
**Test data:** Seed data from database seeders; isolated test database  
**Shield schema:** Test database must use Shield's actual `users` schema (verified during Phase 0)

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
- NEW-01 through NEW-15 design review correction tests pass
- NEW-01 through NEW-10 design review correction tests pass

---

## 13. Unresolved Testing Decisions

| # | Decision | Options | Impact |
|---|----------|---------|--------|
| T1 | Browser automation | Manual UI testing vs Selenium/Playwright | Affects UI test coverage |
| T2 | Load testing tool | Apache JMeter vs custom script vs cloud service | Affects performance test implementation |
| T3 | Test database | Separate MySQL database vs SQLite in-memory | Affects test fidelity (MySQL-specific features like row locking need MySQL) |
| T4 | Shield test schema | Use actual Shield migrations in test DB vs mock | Affects FK compatibility testing |

These will be confirmed during Phase 0 verification.

---

## 14. Next Step

Wait for implementation approval. No test code may be written until this plan and all documentation are reviewed and approved.
