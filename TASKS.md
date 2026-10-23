# LifeQuest — Development Tasks
**Product:** LifeQuest — Daily Life RPG  
**Document Version:** 2.1.0 (aligned with PRD V2.1.0, post Design Review corrections)  
**Status:** Phase 1 Final Hardening — PASS; Awaiting Approval for Phase 2  
**Primary Documentation Language:** English  
**Rule:** No Phase 2 implementation may begin until the Phase 1 final acceptance report is approved. Phase 0/1 statuses below reflect verified implementation; later phases remain planned.

---

## 1. Task Status Legend

| Status | Meaning |
|--------|---------|
| `PENDING` | Task has not started |
| `IN_PROGRESS` | Task is currently being worked on |
| `BLOCKED` | Task is blocked by a dependency or decision |
| `COMPLETED` | Task is complete and verified |
| `DEFERRED` | Task postponed to a later phase |

All tasks start at `PENDING`.

---

## 2. Phase 0 — Requirements and Architecture

| ID | Task | Description | Dependencies | Status | Acceptance Criteria |
|----|------|-------------|--------------|--------|---------------------|
| P0-01 | Read and analyze complete PRD V2.1.0 | Read all 35 sections; understand requirements | None | COMPLETED | PRD fully understood |
| P0-02 | Verify technology versions | Confirm PHP 8.5.12, CI 4.7.0, MySQL 9.7, Bootstrap 5.3.8, Shield availability | None | COMPLETED | Installed and verified: PHP 8.5.12 NTS, CI 4.7.5, MySQL 9.7.4, Bootstrap 5.3.8 (CDN), Shield 1.4.1, Composer 2.10.3; Shield `users.id` confirmed INT UNSIGNED |
| P0-03 | Identify and resolve conflicts with previous plans | Compare against prior DEVELOPMENT_PLAN.md; document resolutions | P0-01 | COMPLETED | Conflict log created |
| P0-04 | Create ARCHITECTURE.md | System architecture, services, controllers, routes, verification, security | P0-01, P0-02 | COMPLETED | ARCHITECTURE.md complete |
| P0-05 | Create DATABASE.md | Entity relationships, table specs, keys, indexes, transactions, migration strategy | P0-01, P0-02 | COMPLETED | DATABASE.md complete |
| P0-06 | Update DEVELOPMENT_PLAN.md | Phases, milestones, acceptance criteria mapping, unresolved decisions | P0-01, P0-03 | COMPLETED | DEVELOPMENT_PLAN.md complete |
| P0-07 | Create TASKS.md | Granular task breakdown with dependencies and acceptance criteria | P0-01, P0-06 | COMPLETED | TASKS.md complete |
| P0-08 | Create TESTING.md | Unit, integration, concurrency, security, performance, UI test scenarios | P0-01, P0-04 | COMPLETED | TESTING.md complete |
| P0-09 | Create AGENTS.md | Coding conventions, commands, structure, testing, security rules | P0-01, P0-04 | COMPLETED | AGENTS.md complete |
| P0-10 | Create DESIGN_REVIEW.md | Critical architecture corrections: Shield compatibility, occurrence_identity, separate aggregate tables, no cascade, evidence storage, lock ordering, season_id on budgets, timer integrity, scoped achievements | P0-04, P0-05 | COMPLETED | DESIGN_REVIEW.md complete |
| P0-11 | Present planning summary for approval | Consolidated review package for stakeholder approval | P0-04 through P0-10 | COMPLETED | Approval received; Phase 1 implementation authorized |

---

## 3. Phase 1 — Application Foundation

| ID | Task | Description | Dependencies | Status | Acceptance Criteria |
|----|------|-------------|--------------|--------|---------------------|
| P1-01 | Initialize CodeIgniter 4.7.0 project | Composer install, project scaffold, directory structure (no `public/uploads/`) | P0-11 (approval) | COMPLETED | CI4 project boots |
| P1-02 | Configure MySQL database `lifequest_db` | Database creation, connection config, `.env` | P1-01 | COMPLETED | Database connection works |
| P1-03 | Configure environment variables | `.env` for app, database, secrets; no hardcoded credentials | P1-02 | COMPLETED | Environment config loaded |
| P1-04 | Install and configure CodeIgniter Shield | Shield setup via official migrations; **verify `users.id` PK type**; create groups (user, administrator, super_administrator) | P1-03 | COMPLETED | Shield installed; `users` schema verified; no custom FKs with wrong type |
| P1-05 | Configure authentication | Login, registration, POST logout, sessions; password recovery disabled until mail delivery is configured | P1-04 | COMPLETED | Register/login/logout works; logout is POST-only with CSRF; login/registration rate limiting active; password recovery clearly marked unavailable |
| P1-06 | Initialize Git repository | Git init, .gitignore, initial commit | P1-01 | COMPLETED | Git repo initialized on `main`; `.env`, logs, cache, sessions, vendor, and private evidence excluded; initial commit created |
| P1-07 | Create base layouts | Bootstrap 5.3.8, dark gaming theme, header/footer/navbar, Indonesian UI | P1-01 | COMPLETED | Base layout renders |
| P1-08 | Create initial migrations | Custom domain tables only (Shield tables via Shield migrations); corrected constraints: occurrence_identity, daily_xp_totals, daily_rp_totals, daily_reward_budgets with season_id, no ON DELETE CASCADE to users, user_achievements scoped | P1-02, P1-04 | PENDING | Migrations create all custom tables with corrected constraints |
| P1-09 | Create base controller and service classes | BaseController, BaseService, common patterns | P1-01 | PENDING | Base classes in place |

---

## 4. Phase 2 — Activity Management

| ID | Task | Description | Dependencies | Status | Acceptance Criteria |
|----|------|-------------|--------------|--------|---------------------|
| P2-01 | Create activity categories | 10 default categories (Morning, Hygiene, Nutrition, Fitness, Productivity, Learning, Household, Social, Evening, Sleep) | P1-09 | PENDING | Categories seeded and queryable |
| P2-02 | Create reward groups | 10 reward groups + Streak with daily RP budgets (total 140 RP) | P1-09 | PENDING | Reward groups seeded with budgets |
| P2-03 | Create activity catalog | Standard activities with codes, names, XP, RP, verification policies | P2-01, P2-02 | PENDING | Activity catalog populated |
| P2-04 | Create activity sessions | Fixed sessions per activity (morning/evening) with XP/RP values | P2-03 | PENDING | Sessions configured per activity |
| P2-05 | Implement repeatable activity types | ONCE_DAILY, FIXED_SESSIONS, REPEATABLE, WEEKLY logic | P2-03, P2-04 | PENDING | Repeat types enforced server-side |
| P2-06 | Create user activity plans | Personal schedules, recurrence rules, timezone support | P2-03 | PENDING | Users can create activity plans |
| P2-07 | Generate daily activity instances | From activity plans, with non-null `occurrence_identity` (server-generated), `activity_date`, `competition_date`, `season_id` | P2-06 | PENDING | Daily instances generated correctly; occurrence_identity prevents duplicate completions |
| P2-08 | Build daily checklist UI | Indonesian UI, activity name, category, schedule, XP/RP, verification method, completion state | P2-07 | PENDING | Checklist page renders correctly |

**Phase 2 Acceptance Criteria Coverage:** AC-003, AC-007, AC-035, AC-036, AC-039

---

## 5. Phase 3 — Reward Engine

| ID | Task | Description | Dependencies | Status | Acceptance Criteria |
|----|------|-------------|--------------|--------|---------------------|
| P3-01 | Implement XP calculation | Max 150 XP per local activity date via `daily_xp_totals` | P2-07 | PENDING | XP calculation correct (unit tested) |
| P3-02 | Implement RP calculation | Max 140 RP per competition date per season via `daily_rp_totals` | P2-07 | PENDING | RP calculation correct (unit tested) |
| P3-03 | Create point ledger | `point_transactions` table with idempotency keys | P3-01, P3-02 | PENDING | Point transactions recorded immutably |
| P3-04 | Implement idempotency keys | Server-controlled unique keys; duplicate requests return existing results | P3-03 | PENDING | No duplicate rewards on repeat requests |
| P3-05 | Implement transaction locking | Consistent lock order: `daily_rp_totals` → `daily_reward_budgets` → `daily_xp_totals`; `INSERT ... ON DUPLICATE KEY` for row init; deadlock retry (3 attempts, exponential backoff) | P3-03 | PENDING | Concurrent requests serialized; no deadlocks; no duplicate aggregate rows |
| P3-06 | Implement daily reward budgets | `daily_reward_budgets` with `season_id`; per user, per competition date, per season, per reward group | P3-02 | PENDING | Budgets enforced correctly per season |
| P3-07 | Implement daily XP and RP totals independently | `daily_xp_totals` (user_id, activity_date) and `daily_rp_totals` (user_id, competition_date, season_id) | P3-01, P3-02 | PENDING | Daily caps enforced independently (150 XP, 140 RP) |
| P3-08 | Implement partial rewards | Award remaining budget; record nominal vs actual; no carry-over | P3-06 | PENDING | Partial rewards work correctly |

**Phase 3 Acceptance Criteria Coverage:** AC-001, AC-002, AC-004, AC-005, AC-006, AC-024, AC-025, AC-038

---

## 6. Phase 4 — Verification Engine

| ID | Task | Description | Dependencies | Status | Acceptance Criteria |
|----|------|-------------|--------------|--------|---------------------|
| P4-01 | Implement SELF_CHECK verification | User confirms; backend validates eligibility, limits, schedule, duplicates | P3-08 | PENDING | Self-check activities auto-validated |
| P4-02 | Implement TIMER verification | Server-recorded start/finish; ownership validation (timer_sessions.user_id = instance.user_id); overlap detection; idempotent finish (one FINISHED per instance); CANCEL stale RUNNING sessions | P3-08 | PENDING | Timer sessions validated server-side; no repeated completion rewards |
| P4-03 | Implement PHOTO_PROOF evidence | JPEG/PNG/WebP, max 5 MB, content validation, secure storage outside web root (`writable/storage/evidence/`), SHA-256 hash, randomized filename, strip metadata; controller-mediated download with authorization | P3-08 | PENDING | Evidence uploaded and stored securely; no direct URL access |
| P4-04 | Implement ADMIN_APPROVAL path | Manual approval for special/high-risk/dispute activities; revalidate budgets at approval time using original configuration snapshot | P3-08 | PENDING | Admin approval workflow works |
| P4-05 | Create verification policies | Policy codes, methods, evidence requirements, approval requirements | P4-01 through P4-04 | PENDING | Policies configurable |
| P4-06 | Implement risk scoring service | Score 0-100, thresholds (0-29 low, 30-69 medium, 70-100 high), reason codes | P4-05 | PENDING | Risk scores calculated with reason codes |
| P4-07 | Implement anti-cheat flags | Flag types, severity, review status, duplicate detection | P4-06 | PENDING | Flags created for suspicious activity |
| P4-08 | Build administrator review queue | Filter, view evidence, risk reasons, approve/reject/request more info, notes | P4-07 | PENDING | Review queue functional |
| P4-09 | Implement verification appeals | User submits appeal; review; administrator decision; resolution reason | P4-08 | PENDING | Appeals workflow functional |
| P4-10 | Implement verification state machine | NOT_REQUIRED → PENDING → AUTO_VALIDATED/MANUALLY_APPROVED/REJECTED | P4-01 through P4-09 | PENDING | State transitions correct |

**Phase 4 Acceptance Criteria Coverage:** AC-008, AC-009, AC-010, AC-011, AC-012, AC-013, AC-014, AC-015, AC-016, AC-029, AC-030

---

## 7. Phase 5 — Gamification

| ID | Task | Description | Dependencies | Status | Acceptance Criteria |
|----|------|-------------|--------------|--------|---------------------|
| P5-01 | Implement competitive ranks | 10 tiers stored in DB; thresholds configurable, not hardcoded | P3-08 | PENDING | Ranks queryable from database |
| P5-02 | Implement rank determination | Current rank from confirmed RP in active season | P5-01 | PENDING | Rank determined correctly |
| P5-03 | Implement lifetime levels | Formula: 100 × (L-1) × L / 2; lifetime XP persists across seasons | P3-08 | PENDING | Level calculation correct |
| P5-04 | Implement streak calculation | 60% completion, 5 activities, 3 reward groups, one bonus per day | P2-07, P3-08 | PENDING | Streak eligibility correct |
| P5-05 | Implement streak reconciliation | Server-controlled daily reconciliation; not dashboard-dependent | P5-04 | PENDING | Streaks reconciled daily |
| P5-06 | Implement achievement system | 10 default achievements, `achievement_scope` (LIFETIME/SEASON), idempotent awards; database-enforced uniqueness via `season_coalesce` generated column; UNIQUE on (user_id, achievement_id, achievement_scope, season_coalesce); application checks for existing LIFETIME awards before insert | P5-04 | PENDING | Achievements awarded correctly; no duplicates within scope |
| P5-07 | Implement notification system | 12 types: activity completed, XP earned, RP earned, rank promotion, level up, achievement unlocked, verification pending/rejected, appeal resolved, season ending, season results | P5-06 | PENDING | Notifications created and viewable |

**Phase 5 Acceptance Criteria Coverage:** AC-017, AC-031, AC-039

---

## 8. Phase 6 — Competition

| ID | Task | Description | Dependencies | Status | Acceptance Criteria |
|----|------|-------------|--------------|--------|---------------------|
| P6-01 | Implement leaderboards | Global Top 10, Top 100, personal ranking, confirmed RP ordering, tie-breaking | P5-02 | PENDING | Leaderboard orders correctly |
| P6-02 | Implement leaderboard privacy | Opt-out, no sensitive data exposed, pagination preserves position | P6-01 | PENDING | Privacy rules enforced |
| P6-03 | Implement season configurations | 60-day cycles, Asia/Jakarta timezone, starts_at/ends_at | P5-03 | PENDING | Seasons configurable |
| P6-04 | Implement season scheduling | First season by Super Admin; subsequent every 60 days at 00:00 Asia/Jakarta | P6-03 | PENDING | Season schedule works |
| P6-05 | Implement season transitions | Close old, activate new, initialize RP at zero, preserve XP | P6-04 | PENDING | Season transitions correct |
| P6-06 | Implement pending verification cutoff | 24-hour grace period; pre-cutoff submissions reviewable; approved submissions charge against old season's `daily_reward_budgets.season_id` | P6-05 | PENDING | Cutoff and grace period enforced |
| P6-07 | Implement season finalization | Idempotent via `scheduled_job_runs.execution_key`; calculate final RP/ranks/positions; save immutable snapshots; mark FINALIZED | P6-06 | PENDING | Finalization idempotent and correct |
| P6-08 | Store historical season results | Previous seasons available after reset | P6-07 | PENDING | Historical results preserved |

**Phase 6 Acceptance Criteria Coverage:** AC-018, AC-019, AC-020, AC-021, AC-022, AC-023, AC-037

---

## 9. Phase 7 — Administration and Analytics

| ID | Task | Description | Dependencies | Status | Acceptance Criteria |
|----|------|-------------|--------------|--------|---------------------|
| P7-01 | Build administrator dashboard | Total users, DAU, completed activities, XP/RP totals, pending verifications, high-risk, appeals, current season | P1-05, P4-08 | PENDING | Admin dashboard renders |
| P7-02 | Implement user management | Soft deactivate via `is_active`/`deleted_at`; **no hard delete** (preserves history in point_transactions, season_results, verification logs, audit logs) | P1-05 | PENDING | User management functional; history preserved |
| P7-03 | Implement activity configuration | Create/edit/deactivate activities, sessions, verification methods, reward allocation | P2-03 | PENDING | Activity config manageable |
| P7-04 | Implement verification monitoring | Review queue, risk flags, pending volume, processing age | P4-08 | PENDING | Monitoring functional |
| P7-05 | Implement point reporting | XP/RP reports, aggregates | P3-08 | PENDING | Reports generated |
| P7-06 | Implement performance statistics | System stats for admin view | P2-07 | PENDING | Statistics available |
| P7-07 | Build audit log interface | Administrator actions, before/after values, reasons, timestamps | P7-01 | PENDING | Audit logs viewable |

**Phase 7 Acceptance Criteria Coverage:** AC-015, AC-026, AC-027, AC-028, AC-034

---

## 10. Phase 8 — Quality Assurance and Deployment

| ID | Task | Description | Dependencies | Status | Acceptance Criteria |
|----|------|-------------|--------------|--------|---------------------|
| P8-01 | Write unit tests | XP calc, RP calc, budgets, level calc, rank, streak, risk scoring, season dates, repeat validation, occurrence_identity uniqueness, scoped achievements | P3-08, P5-03, P5-04, P4-06, P6-03 | PENDING | ≥80% service coverage |
| P8-02 | Write integration tests | Activity completion → ledger, timer → verification, evidence → review, approval → confirmed RP, rejection → zero reward, season closure/finalization, appeal processing, admin corrections | P4-10, P5-07, P6-07 | PENDING | All integration scenarios pass |
| P8-03 | Security review | Authentication, authorization, CSRF, XSS, SQL injection, file upload, IDOR, session security, rate limiting, evidence authorization (controller-mediated download), sensitive info exposure | P1-05, P4-03 | PENDING | No critical/high vulnerabilities |
| P8-04 | Concurrency tests | Duplicate clicks, simultaneous completions, concurrent point awards, concurrent admin approvals, concurrent season scheduler, concurrent budget updates, deadlock recovery | P3-05, P4-10 | PENDING | No race conditions |
| P8-05 | Performance tests | 1,000 DAU workload; dashboard <2s; checklist API <500ms; leaderboard <2s | P6-02 | PENDING | Performance targets met |
| P8-06 | UI tests | Desktop, mobile, responsive layouts, checklist feedback, loading indicators, error states, rank visualization, achievement notifications | P2-08, P5-07 | PENDING | UI verified on all devices |
| P8-07 | Backup and restore tests | Database backup, restore verification | P1-02 | PENDING | Backup/restore works |
| P8-08 | Production configuration | Nginx/Apache, HTTPS, PHP-FPM, scheduled jobs, logging, backups, secrets, restricted storage, evidence outside web root | P1-02 | PENDING | Production config documented |
| P8-09 | Release documentation | Installation, deployment, operations, maintenance | P8-08 | PENDING | Documentation complete |

**Phase 8 Acceptance Criteria Coverage:** AC-032, AC-033, AC-040, plus all remaining ACs

---

## 11. Task Dependencies Summary

```
Phase 0 (P0-01 → P0-11)
    ↓ (Approval Gate — Shield schema verified)
Phase 1 (P1-01 → P1-09)
    ↓
Phase 2 (P2-01 → P2-08)
    ↓
Phase 3 (P3-01 → P3-08)
    ↓
Phase 4 (P4-01 → P4-10)
    ↓
Phase 5 (P5-01 → P5-07)
    ↓
Phase 6 (P6-01 → P6-08)
    ↓
Phase 7 (P7-01 → P7-07)
    ↓
Phase 8 (P8-01 → P8-09)
```

Critical path: P0-11 → P1-01 → P1-04 → P1-05 → P2-01 → P2-07 → P3-01 → P3-08 → P4-01 → P4-10 → P5-01 → P5-07 → P6-01 → P6-07 → P7-01 → P8-01 → P8-09

---

## 12. Definition of Done per Task

A task is complete only when (PRD Section 32):
1. Approved requirements are implemented
2. Backend validation is operational
3. Authentication and authorization are enforced
4. Required database migrations exist
5. User interface behavior is correct
6. Relevant unit tests pass
7. Relevant integration tests pass
8. Applicable concurrency tests pass
9. Relevant security checks pass
10. Error handling is implemented
11. Audit requirements are satisfied
12. Documentation is updated
13. Acceptance criteria are satisfied
14. No unresolved critical or high-severity defects remain
15. Task status is updated in TASKS.md

---

## 13. Next Step

Phase 1 final hardening is complete. Await stakeholder approval of the Phase 1 acceptance report before beginning any Phase 2 work.
