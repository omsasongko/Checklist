# LifeQuest — Development Plan
**Product:** LifeQuest — Daily Life RPG (Gamified Daily Activity & Habit Tracking)  
**Document Version:** 2.1.0 (aligned with PRD V2.1.0, post Design Review corrections)  
**Status:** For Review — Planning Only  
**Primary Documentation Language:** English  
**Application Interface Language:** Indonesian (Bahasa Indonesia)  
**Project Root:** `D:\Project OpenCODE\Checklist`  
**Target Database:** `lifequest_db`  
**Technology Stack:** PHP 8.5.12 · CodeIgniter 4.7.0 · MySQL 9.7 LTS · Bootstrap 5.3.8 · CodeIgniter Shield  
**Architecture:** MVC with Service Layer  
**Rule:** No source code, no migrations, no dependency installation until this plan is approved.

---

## 1. Executive Summary

This document is the implementation plan for LifeQuest V2.1.0. It replaces all previous planning assumptions and incorporates the corrections from DESIGN_REVIEW.md. The application is a **gamified daily activity tracker**, not a traditional RPG quest system. Users complete daily activities from wake-up to sleep, earn XP (lifetime) and RP (seasonal), progress through levels and competitive ranks, maintain streaks, unlock achievements, and compete in 60-day seasons. Verification is automated by default with optional administrator review for high-risk submissions.

All user-facing text is Indonesian. All technical documentation, code identifiers, database identifiers, and comments are English.

---

## 2. PRD Section Analysis (35 Sections)

| Section | Title | Key Content | Planning Impact |
|---------|-------|-------------|-----------------|
| 1 | Executive Summary | Product vision, objectives, target users, 28 core features | Defines feature scope |
| 2 | Technology Requirements | PHP 8.5.12, CI 4.7.0, Shield, MySQL 9.7, Bootstrap 5.3.8, JSON API | Stack locked; versions to verify |
| 3 | Product Scope | MVP vs future features | MVP includes all listed features; mobile apps, AI, subscriptions excluded |
| 4 | User Roles & Access Control | Guest, Registered User, Administrator, Super Administrator | Shield groups + permissions; backend-enforced authorization |
| 5 | Activity Management | Categories, activity types, scheduling, daily instances | Activity catalog, user plans, daily instances |
| 6 | Repeatable Activity System | ONCE_DAILY, FIXED_SESSIONS, REPEATABLE, WEEKLY; duplicate prevention | Session identity, occurrence uniqueness |
| 7 | Points & Reward System | XP (lifetime, max 150/day), RP (seasonal, max 140/day), reward group budgets | Point ledger, budget enforcement, daily caps |
| 8 | Activity Verification | SELF_CHECK, TIMER, PHOTO_PROOF, ADMIN_APPROVAL; verification states | Verification service, evidence storage |
| 9 | Risk-Based Anti-Cheat | Risk score 0-100, thresholds, secondary verification, review queue, appeals | Risk scoring service, admin review |
| 10 | Reward Processing & Concurrency | Atomic transactions, idempotency keys, row-level locking, pending rewards | Transactional service layer |
| 11 | Competitive Ranking | 10 rank tiers, stored in DB, promotion/demotion | Ranking service, rank configuration table |
| 12 | Lifetime XP & Level System | Formula: 100 × (L-1) × L / 2; lifetime XP persists | Lifetime level service |
| 13 | Leaderboard System | Top 10/100, personal ranking, tie-breaking, privacy opt-out | Leaderboard service, indexed queries |
| 14 | Season Management | 60 days, Asia/Jakarta, lifecycle states, finalization, grace period | Season service, scheduled jobs |
| 15 | Streak System | 60% completion, 5 activities, 3 reward groups, one bonus/day | Streak service, daily reconciliation |
| 16 | Achievements & Badges | 10 default achievements, configurable criteria, idempotent | Achievement service |
| 17 | Notification System | 12 notification types, in-app only for MVP | Notification service |
| 18 | Administrative Management | Admin dashboard, activity management, audit logs | Admin controllers/services |
| 19 | Database Architecture | Shield auth + domain tables with exact column definitions | Database design (see DATABASE.md) |
| 20 | Application Architecture | MVC + service layer; 19 required services | Architecture design (see ARCHITECTURE.md) |
| 21 | API Requirements | ~25 JSON endpoints for dashboard, activities, timer, evidence, ranking, seasons, achievements, appeals, admin | Route definitions in ARCHITECTURE.md |
| 22 | UI & UX | Dark gaming theme, Indonesian language, 18 main pages, dashboard components | Bootstrap views, Indonesian localization |
| 23 | Security Requirements | Shield, CSRF, input validation, file upload security, secrets, reward security | Security controls throughout |
| 24 | Privacy & Data Retention | Data minimization, evidence retention (30 days), sensitive activity protection | Evidence storage, retention jobs |
| 25 | Performance & Scalability | 1,000 DAU initial, 10,000 DAU future; response targets | Indexes, pagination, caching, monitoring |
| 26 | Functional User Flows | Registration, daily checklist, timer, evidence, season, appeal flows | End-to-end test scenarios |
| 27 | Acceptance Criteria | AC-001 through AC-040 | Test pass criteria |
| 28 | Testing Strategy | Unit, integration, concurrency, security, performance, UI testing | Testing plan (see TESTING.md) |
| 29 | Implementation Roadmap | Phase 0 through Phase 8 | Development phases (see Section 4) |
| 30 | OpenCode Development Rules | 35 mandatory rules | Governs all implementation |
| 31 | Required Project Documentation | PRD.md, ARCHITECTURE.md, DATABASE.md, DEVELOPMENT_PLAN.md, TASKS.md, TESTING.md, AGENTS.md, DESIGN_REVIEW.md | This document set |
| 32 | Definition of Done | 15 criteria per feature | Completion gate |
| 33 | Product Governance | Requirements baseline, change management, conflict resolution | PRD is single source of truth |
| 34 | Final Approved Product Decisions | 38 approved decisions | Immutable product behavior |
| 35 | Final Development Directive | Planning before implementation; approval required | This review gate |

---

## 3. Design Review Corrections Applied

The following corrections from DESIGN_REVIEW.md are incorporated into this plan:

| # | Correction | Affected Documents |
|---|-----------|-------------------|
| C1 | Shield compatibility — verify `users` PK type, no CASCADE FKs to Shield tables | DATABASE.md, ARCHITECTURE.md, TASKS.md (P1-04) |
| C2 | Separate `daily_xp_totals` and `daily_rp_totals` tables (replaces `daily_point_totals`) | DATABASE.md, TASKS.md (P3-06, P3-07), TESTING.md |
| C3 | Non-null `occurrence_identity` column on `daily_activity_instances` for uniqueness | DATABASE.md, TASKS.md (P2-07), TESTING.md |
| C4 | No ON DELETE CASCADE to Shield tables; soft delete via `is_active/deleted_at` | DATABASE.md, ARCHITECTURE.md, TASKS.md |
| C5 | `user_achievements` with `achievement_scope` (LIFETIME/SEASON) for season-aware uniqueness | DATABASE.md, TASKS.md (P5-06), TESTING.md |
| C6 | Evidence storage outside web root; controller-mediated download with authorization | ARCHITECTURE.md, DATABASE.md, TASKS.md (P4-03), TESTING.md |
| C7 | Consistent lock ordering + deadlock retry for reward concurrency | ARCHITECTURE.md, DATABASE.md, TASKS.md (P3-05), TESTING.md |
| C8 | `daily_reward_budgets` includes `season_id` for grace-period budget enforcement | DATABASE.md, ARCHITECTURE.md, TASKS.md (P6-05, P6-06) |
| C9 | Timer integrity — ownership validation, overlap detection, idempotent finish | ARCHITECTURE.md, DATABASE.md, TASKS.md (P4-02), TESTING.md |
| C10 | Cross-document consistency across all 5 docs + DESIGN_REVIEW.md | All documents |

---

## 4. Conflicts with Previous Planning Documents

The previous DEVELOPMENT_PLAN.md (based on the incomplete PRD brief) contained the following conflicts with PRD V2.1.0. All are resolved in this updated plan:

| # | Previous Assumption | PRD V2.1.0 Requirement | Resolution |
|---|---------------------|------------------------|------------|
| 1 | "Quest" system with XP/leveling | **Activity** tracking system (standard, custom, alternative activities) | Removed quest terminology; use activity/session/instance model |
| 2 | Habit tracking with check-ins | Daily activity instances with repeat types and schedules | Replaced with `daily_activity_instances`, `activity_sessions`, `user_activity_plans` |
| 3 | Single `quest_categories` table | Separate `activity_categories` AND `reward_groups` tables | Both tables created; categories classify activities, reward groups govern RP budgets |
| 4 | Badges separate from achievements | Achievements and badges are the same concept (`achievements`/`user_achievements`) | Unified achievement model with `achievement_scope` |
| 5 | Server-rendered views only | JSON API endpoints required (Section 21) | API + server-rendered hybrid; routes documented in ARCHITECTURE.md |
| 6 | No timer or photo verification | Timer, photo evidence, self-check, admin approval | Verification service with all four methods |
| 7 | No risk scoring or anti-cheat | Deterministic risk scoring 0-100, review queue, appeals | RiskScoringService, AntiCheatService, admin review |
| 8 | No season system | 60-day seasons with lifecycle, finalization, grace period | SeasonService, scheduled jobs |
| 9 | No competitive ranking | 10 rank tiers stored in database | RankingService, `ranks` and `season_rank_configurations` tables |
| 10 | No point ledger or idempotency | Immutable point transactions with idempotency keys | `point_transactions` table, atomic reward processing |
| 11 | Generic user profile | `user_profiles` with display_name, avatar, timezone, leaderboard_visible | Exact columns per Section 19 |
| 12 | No schedule/shift support | Nonstandard shift support; Asia/Jakarta competition dates; user timezone | Separate activity_date and competition_date columns |
| 13 | `daily_point_totals` combined table | Separate `daily_xp_totals` and `daily_rp_totals` | Independent caps with correct date dimensions |
| 14 | NULLable unique keys for occurrence | Non-null `occurrence_identity` column | MySQL NULL uniqueness avoided |
| 15 | ON DELETE CASCADE to Shield tables | ON DELETE NO ACTION; soft delete users | Historical records preserved |
| 16 | Public uploads directory | Evidence outside web root; controller-mediated download | Private storage, authorization on every retrieval |

---

## 5. Technology Verification & Compatibility

| Component | Required Version | Status | Notes |
|-----------|-----------------|--------|-------|
| PHP | 8.5.12 | To verify | Must confirm availability on Windows dev machine |
| CodeIgniter | 4.7.0 | To verify | Composer package availability |
| CodeIgniter Shield | Latest compatible with CI 4.7.0 | To verify | **BLOCKING: Verify `users.id` type before custom FKs** |
| MySQL | 9.7 LTS | To verify | Confirm LTS status; InnoDB, utf8mb4 |
| Bootstrap | 5.3.8 | To verify | CDN or local assets |
| Chart.js | Compatible stable release | To verify | For weekly performance charts |
| Bootstrap Icons | Compatible version | To verify | Icon library |

**Verification actions (Phase 0):**
- Confirm PHP 8.5.12 is installable on Windows
- Confirm CodeIgniter 4.7.0 exists on Packagist
- Confirm MySQL 9.7 availability (or closest LTS alternative)
- **Run Shield installer and inspect `users` table schema** (BLOCKING for custom FKs)
- Check Shield compatibility with CI 4.7.0
- Review security advisories for all packages

---

## 6. Development Phases (PRD Section 29 Roadmap)

### Phase 0 — Requirements and Architecture (Current)
**Objective:** Complete planning deliverables; no application source code.

| Task | Description |
|------|-------------|
| P0-01 | Read and analyze complete PRD V2.1.0 (all 35 sections) |
| P0-02 | Verify technology versions and compatibility |
| P0-03 | Identify and resolve conflicts with previous plans |
| P0-04 | Create ARCHITECTURE.md |
| P0-05 | Create DATABASE.md |
| P0-06 | Create/update DEVELOPMENT_PLAN.md |
| P0-07 | Create TASKS.md with granular task breakdown |
| P0-08 | Create TESTING.md |
| P0-09 | Create AGENTS.md |
| P0-10 | Create DESIGN_REVIEW.md (critical architecture corrections) |
| P0-11 | Present planning summary for approval |

**Exit criteria:** All seven documentation files created and approved. Shield `users` schema verified.

---

### Phase 1 — Application Foundation
**Objective:** Working CI4 app with Shield auth and base layout.

| Task | Description | Dependencies | Status | Acceptance Criteria |
|------|-------------|--------------|--------|---------------------|
| P1-01 | Initialize CodeIgniter 4.7.0 project | P0-11 (approval) | PENDING | CI4 project boots |
| P1-02 | Configure MySQL database `lifequest_db` | P1-01 | PENDING | Database connection works |
| P1-03 | Configure `.env` and environment variables | P1-02 | PENDING | Environment config loaded |
| P1-04 | Install and configure CodeIgniter Shield | P1-03 | PENDING | Shield installed; `users` schema verified |
| P1-05 | Configure authentication (login, register, password recovery) | P1-04 | PENDING | Register/login/logout works |
| P1-06 | Initialize Git repository | P1-01 | PENDING | Git repo initialized |
| P1-07 | Create base layouts (dark gaming theme, Bootstrap 5.3.8) | P1-01 | PENDING | Base layout renders |
| P1-08 | Create initial migrations (custom tables only; Shield tables via Shield migrations) | P1-02, P1-04 | PENDING | Migrations create all custom tables with corrected constraints |
| P1-09 | Create base controller and service classes | P1-01 | PENDING | Base classes in place |

---

### Phase 2 — Activity Management
**Objective:** Users can browse activities, set up routines, and view daily checklists.

| Task | Description | Dependencies | Status | Acceptance Criteria |
|------|-------------|--------------|--------|---------------------|
| P2-01 | Create activity categories (10 default categories) | P1-09 | PENDING | Categories seeded and queryable |
| P2-02 | Create reward groups (10 groups + Streak with daily RP budgets, total 140 RP) | P1-09 | PENDING | Reward groups seeded with budgets |
| P2-03 | Create activity catalog (standard activities) | P2-01, P2-02 | PENDING | Activity catalog populated |
| P2-04 | Create activity sessions (fixed sessions per activity) | P2-03 | PENDING | Sessions configured per activity |
| P2-05 | Implement repeatable activity types (ONCE_DAILY, FIXED_SESSIONS, REPEATABLE, WEEKLY) | P2-03, P2-04 | PENDING | Repeat types enforced server-side |
| P2-06 | Create user activity plans (personal schedules, recurrence rules, timezone support) | P2-03 | PENDING | Users can create activity plans |
| P2-07 | Generate daily activity instances (with non-null `occurrence_identity`, `activity_date`, `competition_date`) | P2-06 | PENDING | Daily instances generated correctly; occurrence_identity prevents duplicates |
| P2-08 | Build daily checklist UI (Indonesian) | P2-07 | PENDING | Checklist page renders correctly |

**Phase 2 Acceptance Criteria Coverage:** AC-003, AC-007, AC-035, AC-036, AC-039

---

### Phase 3 — Reward Engine
**Objective:** Atomic XP/RP calculation with independent daily caps, budgets, idempotency, and consistent lock ordering.

| Task | Description | Dependencies | Status | Acceptance Criteria |
|------|-------------|--------------|--------|---------------------|
| P3-01 | Implement XP calculation (max 150 per local activity_date via `daily_xp_totals`) | P2-07 | PENDING | XP calculation correct (unit tested) |
| P3-02 | Implement RP calculation (max 140 per competition_date per season via `daily_rp_totals`) | P2-07 | PENDING | RP calculation correct (unit tested) |
| P3-03 | Create point ledger (`point_transactions` with idempotency keys) | P3-01, P3-02 | PENDING | Point transactions recorded immutably |
| P3-04 | Implement idempotency keys (server-controlled, UNIQUE constraint) | P3-03 | PENDING | No duplicate rewards on repeat requests |
| P3-05 | Implement transaction locking (consistent order: rp_totals → reward_budgets → xp_totals; deadlock retry) | P3-03 | PENDING | Concurrent requests serialized; no deadlocks |
| P3-06 | Implement daily reward budgets (`daily_reward_budgets` with `season_id`) | P3-02 | PENDING | Budgets enforced correctly per season |
| P3-07 | Implement daily XP totals (`daily_xp_totals`) and daily RP totals (`daily_rp_totals`) independently | P3-01, P3-02 | PENDING | Daily caps enforced (150 XP, 140 RP) |
| P3-08 | Implement partial rewards (award remaining budget; nominal vs actual; no carry-over) | P3-06 | PENDING | Partial rewards work correctly |

**Phase 3 Acceptance Criteria Coverage:** AC-001, AC-002, AC-004, AC-005, AC-006, AC-024, AC-025, AC-038

---

### Phase 4 — Verification Engine
**Objective:** Automated verification with timer, evidence (private storage), risk scoring, admin review, and appeals.

| Task | Description | Dependencies | Status | Acceptance Criteria |
|------|-------------|--------------|--------|---------------------|
| P4-01 | Implement SELF_CHECK verification | P3-08 | PENDING | Self-check activities auto-validated |
| P4-02 | Implement TIMER verification (server-recorded start/finish, ownership validation, overlap detection, idempotent finish) | P3-08 | PENDING | Timer sessions validated server-side |
| P4-03 | Implement PHOTO_PROOF evidence (private storage outside web root, file validation, hash, randomized filename, controller-mediated download) | P3-08 | PENDING | Evidence uploaded and stored securely |
| P4-04 | Implement ADMIN_APPROVAL verification path | P3-08 | PENDING | Admin approval workflow works |
| P4-05 | Create verification policies | P4-01 through P4-04 | PENDING | Policies configurable |
| P4-06 | Implement risk scoring service (0-100, thresholds, reason codes) | P4-05 | PENDING | Risk scores calculated with reason codes |
| P4-07 | Implement anti-cheat flags | P4-06 | PENDING | Flags created for suspicious activity |
| P4-08 | Build administrator review queue | P4-07 | PENDING | Review queue functional |
| P4-09 | Implement verification appeals | P4-08 | PENDING | Appeals workflow functional |
| P4-10 | Implement verification state machine | P4-01 through P4-09 | PENDING | State transitions correct |

**Phase 4 Acceptance Criteria Coverage:** AC-008, AC-009, AC-010, AC-011, AC-012, AC-013, AC-014, AC-015, AC-016, AC-029, AC-030

---

### Phase 5 — Gamification
**Objective:** Ranks, levels, streaks, achievements (lifetime + season-scoped), notifications.

| Task | Description | Dependencies | Status | Acceptance Criteria |
|------|-------------|--------------|--------|---------------------|
| P5-01 | Implement competitive ranks (10 tiers, DB-configured thresholds) | P3-08 | PENDING | Ranks queryable from database |
| P5-02 | Implement rank determination (current rank from confirmed RP in active season) | P5-01 | PENDING | Rank determined correctly |
| P5-03 | Implement lifetime levels (XP formula service) | P3-08 | PENDING | Level calculation correct |
| P5-04 | Implement streak calculation (60% completion, 5 activities, 3 reward groups, one bonus/day) | P2-07, P3-08 | PENDING | Streak eligibility correct |
| P5-05 | Implement streak reconciliation (server-controlled daily reconciliation) | P5-04 | PENDING | Streaks reconciled daily |
| P5-06 | Implement achievement system (10 default achievements, `achievement_scope` LIFETIME/SEASON, idempotent awards) | P5-04 | PENDING | Achievements awarded correctly; no duplicates within scope |
| P5-07 | Implement notification system (12 types) | P5-06 | PENDING | Notifications created and viewable |

**Phase 5 Acceptance Criteria Coverage:** AC-017, AC-031, AC-039

---

### Phase 6 — Competition
**Objective:** Leaderboards, seasons, season lifecycle with grace period and idempotent finalization.

| Task | Description | Dependencies | Status | Acceptance Criteria |
|------|-------------|--------------|--------|---------------------|
| P6-01 | Implement leaderboards (Top 10, Top 100, personal ranking, confirmed RP ordering, tie-breaking) | P5-02 | PENDING | Leaderboard orders correctly |
| P6-02 | Implement leaderboard privacy (opt-out, no sensitive data, pagination preserves position) | P6-01 | PENDING | Privacy rules enforced |
| P6-03 | Implement season configurations (60-day cycles, Asia/Jakarta) | P5-03 | PENDING | Seasons configurable |
| P6-04 | Implement season scheduling (first season by Super Admin; subsequent every 60 days) | P6-03 | PENDING | Season schedule works |
| P6-05 | Implement season transitions (close old, activate new, preserve XP) | P6-04 | PENDING | Season transitions correct |
| P6-06 | Implement pending verification cutoff (24-hour grace period; `daily_reward_budgets.season_id` for old-season budget enforcement) | P6-05 | PENDING | Cutoff and grace period enforced |
| P6-07 | Implement season finalization (idempotent via `scheduled_job_runs.execution_key`; immutable snapshots) | P6-06 | PENDING | Finalization idempotent and correct |
| P6-08 | Store historical season results | P6-07 | PENDING | Historical results preserved |

**Phase 6 Acceptance Criteria Coverage:** AC-018, AC-019, AC-020, AC-021, AC-022, AC-023, AC-037

---

### Phase 7 — Administration and Analytics
**Objective:** Admin dashboard, user management (soft deactivate, not hard delete), reporting, audit.

| Task | Description | Dependencies | Status | Acceptance Criteria |
|------|-------------|--------------|--------|---------------------|
| P7-01 | Build administrator dashboard | P1-05, P4-08 | PENDING | Admin dashboard renders |
| P7-02 | Implement user management (soft deactivate via `is_active`; no hard delete) | P1-05 | PENDING | User management functional; history preserved |
| P7-03 | Implement activity configuration (create/edit/deactivate, sessions, verification, reward allocation) | P2-03 | PENDING | Activity config manageable |
| P7-04 | Implement verification monitoring (review queue, risk flags, pending volume, processing age) | P4-08 | PENDING | Monitoring functional |
| P7-05 | Implement point reporting (XP/RP reports, aggregates) | P3-08 | PENDING | Reports generated |
| P7-06 | Implement performance statistics | P2-07 | PENDING | Statistics available |
| P7-07 | Build audit log interface (administrator actions, before/after values, reasons, timestamps) | P7-01 | PENDING | Audit logs viewable |

**Phase 7 Acceptance Criteria Coverage:** AC-015, AC-026, AC-027, AC-028, AC-034

---

### Phase 8 — Quality Assurance and Deployment
**Objective:** Testing, security review, deployment readiness.

| Task | Description | Dependencies | Status | Acceptance Criteria |
|------|-------------|--------------|--------|---------------------|
| P8-01 | Write unit tests (XP, RP, budgets, level, rank, streak, risk scoring, season dates, repeat validation, occurrence_identity uniqueness) | P3-08, P5-03, P5-04, P4-06, P6-03 | PENDING | ≥80% service coverage |
| P8-02 | Write integration tests (activity completion → ledger, timer → verification, evidence → review, approval → confirmed RP, season finalization, appeals, corrections) | P4-10, P5-07, P6-07 | PENDING | All integration scenarios pass |
| P8-03 | Security review (auth, authorization, CSRF, XSS, SQL injection, file upload, IDOR, session, rate limiting, evidence authorization) | P1-05, P4-03 | PENDING | No critical/high vulnerabilities |
| P8-04 | Concurrency tests (duplicate clicks, simultaneous completions, concurrent awards, concurrent approvals, concurrent scheduler, deadlock recovery) | P3-05, P4-10 | PENDING | No race conditions |
| P8-05 | Performance tests (1,000 DAU workload; dashboard <2s; checklist API <500ms; leaderboard <2s) | P6-02 | PENDING | Performance targets met |
| P8-06 | UI tests (desktop, mobile, responsive, checklist feedback, loading, error states, rank viz, achievement notifications) | P2-08, P5-07 | PENDING | UI verified on all devices |
| P8-07 | Backup and restore tests | P1-02 | PENDING | Backup/restore works |
| P8-08 | Production configuration (Nginx/Apache, HTTPS, PHP-FPM, scheduled jobs, logging, backups, secrets, restricted storage) | P1-02 | PENDING | Production config documented |
| P8-09 | Release documentation | P8-08 | PENDING | Documentation complete |

**Phase 8 Acceptance Criteria Coverage:** AC-032, AC-033, AC-040, plus all remaining ACs

---

## 7. Phase Dependencies

```
Phase 0 (Planning)
    ↓ (Approval Gate — Shield schema verified)
Phase 1 (Foundation: CI4 + Shield + Auth)
    ↓
Phase 2 (Activity Management: categories, groups, catalog, plans, instances with occurrence_identity)
    ↓
Phase 3 (Reward Engine: separate xp_totals/rp_totals, lock ordering, idempotency)
    ↓
Phase 4 (Verification: self-check, timer, private evidence, risk, admin review, appeals)
    ↓
Phase 5 (Gamification: ranks, levels, streaks, scoped achievements, notifications)
    ↓
Phase 6 (Competition: leaderboards, seasons with season_id budgets, idempotent finalization)
    ↓
Phase 7 (Administration: soft-delete users, dashboard, reports, audit)
    ↓
Phase 8 (QA: tests, security, performance, deployment)
```

Phase 3 depends on Phase 2 (activities must exist before rewards). Phase 4 depends on Phase 3 (verification gates rewards). Phase 6 depends on Phase 5 (seasons use ranks and XP). Phase 7 depends on Phases 1-6 (admin manages all domain entities).

---

## 8. Milestones

| Milestone | Phase | Exit Criteria |
|-----------|-------|---------------|
| M1: Planning Complete | 0 | All 7 docs created and approved; Shield schema verified |
| M2: Foundation | 1 | Login/register works; Shield configured; base layout renders; custom migrations create corrected tables |
| M3: Activity MVP | 2 | Users can view activities, set routines, see daily checklist; occurrence_identity prevents duplicates |
| M4: Reward MVP | 3 | Completing activities awards XP/RP atomically with independent caps and lock ordering |
| M5: Verification MVP | 4 | Timer, evidence (private storage), risk scoring, admin review, appeals functional |
| M6: Gamification MVP | 5 | Ranks, levels, streaks, scoped achievements, notifications working |
| M7: Competition MVP | 6 | Leaderboards and 60-day seasons with idempotent finalization |
| M8: Administration | 7 | Admin dashboard, soft-delete user management, audit logs |
| M9: Release Ready | 8 | All tests pass; security review complete; deployment documented |

---

## 9. Acceptance Criteria Mapping (PRD Section 27)

| AC ID | Title | Phase | Verification Method |
|-------|-------|-------|---------------------|
| AC-001 | Standard Completion | 3 | Integration test |
| AC-002 | Duplicate Request | 3 | Concurrency test |
| AC-003 | Repeatable Sessions | 2/3 | Integration test |
| AC-004 | Reward Group Limit | 3 | Unit + integration test |
| AC-005 | Maximum Daily RP (140) | 3 | Unit test |
| AC-006 | Maximum Daily XP (150) | 3 | Unit test |
| AC-007 | Custom Activities (0 RP) | 2/3 | Unit test |
| AC-008 | Self-Check Validation | 4 | Integration test |
| AC-009 | Timer Validation | 4 | Integration test |
| AC-010 | Photo Evidence Access | 4 | Security test |
| AC-011 | Duplicate Evidence | 4 | Unit test |
| AC-012 | Risk Scoring | 4 | Unit test |
| AC-013 | High-Risk Submission | 4 | Integration test |
| AC-014 | Pending Rewards | 4 | Integration test |
| AC-015 | Administrator Review | 4/7 | Integration test |
| AC-016 | Appeals | 4 | Integration test |
| AC-017 | Rank Promotion | 5 | Unit test |
| AC-018 | Leaderboard Ordering | 6 | Unit + integration test |
| AC-019 | Season Duration (60 days) | 6 | Unit test |
| AC-020 | Season Reset | 6 | Integration test |
| AC-021 | Season History | 6 | Integration test |
| AC-022 | Pending Season Submissions | 6 | Integration test |
| AC-023 | Season Finalization | 6 | Concurrency test |
| AC-024 | Concurrent Completion | 3/4 | Concurrency test |
| AC-025 | Transaction Rollback | 3 | Integration test |
| AC-026 | Administrator Configuration | 7 | Integration test |
| AC-027 | Reward Reversal | 7 | Integration test |
| AC-028 | Authorization | 1/7 | Security test |
| AC-029 | Privacy | 4/7 | Security test |
| AC-030 | Evidence Retention | 4/8 | Integration test |
| AC-031 | Streak Calculation | 5 | Unit test |
| AC-032 | Accessibility | 8 | UI test |
| AC-033 | Performance | 8 | Performance test |
| AC-034 | Auditability | 7/8 | Integration test |
| AC-035 | Timezone Boundaries | 2/6 | Unit test |
| AC-036 | Configuration Versioning | 2/6 | Integration test |
| AC-037 | Season Concurrency | 6 | Concurrency test |
| AC-038 | Pending Reward Budget | 3/4 | Concurrency test |
| AC-039 | Daily Target Fairness | 5 | Unit test |
| AC-040 | Production Security | 8 | Security review |

---

## 10. Unresolved Technical Decisions

| # | Decision | Options | Impact |
|---|----------|---------|--------|
| D1 | PHP 8.5.12 availability | PHP 8.5.12 vs fallback to PHP 8.4 | Affects dev environment setup |
| D2 | MySQL 9.7 LTS availability | MySQL 9.7 vs MySQL 8.4 LTS | Affects database provisioning |
| D3 | CodeIgniter 4.7.0 availability | CI 4.7.0 vs closest stable release | Affects composer installation |
| D4 | Bootstrap 5.3.8 availability | Bootstrap 5.3.8 vs latest 5.3.x | Affects frontend assets |
| D5 | Shield version compatibility | Shield version compatible with CI 4.7.0; verify `users.id` type | **BLOCKING** for custom FKs |
| D6 | API + server-rendered vs API-only | Hybrid (PRD shows both views and JSON endpoints) | Affects controller structure |
| D7 | Timer session storage | Database vs Redis for active timer sessions | Affects performance and complexity |
| D8 | Evidence storage | Local filesystem vs cloud storage (future) | Affects EvidenceService implementation |
| D9 | Scheduled job mechanism | CI4 cron scheduler vs external cron | Affects season transitions and streak reconciliation |
| D10 | Caching layer | File cache vs Redis vs none for MVP | Affects leaderboard performance |

**Blocking item:** D5 must be resolved during Phase 0 before custom migrations reference Shield tables.

---

## 11. Implementation Rules (PRD Section 30)

All 35 OpenCode development rules are binding. Key rules for this phase:
- Rule 1: Read the complete PRD before implementation ✅ (done)
- Rule 3: Do not reinterpret as traditional RPG quest system ✅ (activity model)
- Rule 7: Create architectural documentation first (ARCHITECTURE.md) ✅
- Rule 8: Create database documentation first (DATABASE.md) ✅
- Rule 9: Create development task breakdown (TASKS.md) ✅
- Rule 10: Use CodeIgniter MVC conventions
- Rule 11: Separate controllers, services, and models
- Rule 12: Keep business logic on the server
- Rule 13: Never trust client-supplied reward amounts
- Rule 14: Use transactional reward processing
- Rule 15: Implement idempotent reward operations
- Rule 16: Enforce reward group budgets
- Rule 17: Implement repeatable activities safely
- Rule 24: Test season boundary conditions
- Rule 25: Test simultaneous reward requests
- Rule 32: Use English for technical documentation
- Rule 33: Use Indonesian for default user-facing interface text
- Rule 35: Report implementation conflicts rather than silently changing requirements

---

## 12. Definition of Done (PRD Section 32)

A feature is complete only when all 15 criteria are met:
1. Approved requirements implemented
2. Backend validation operational
3. Authentication and authorization enforced
4. Required database migrations exist
5. User interface behavior correct
6. Relevant unit tests pass
7. Relevant integration tests pass
8. Applicable concurrency tests pass
9. Relevant security checks pass
10. Error handling implemented
11. Audit requirements satisfied
12. Documentation updated
13. Acceptance criteria satisfied
14. No unresolved critical/high-severity defects
15. Task status updated in TASKS.md

---

## 13. Next Step

**Wait for implementation approval.** No source code, migrations, or dependency installation may begin until this plan and the accompanying architecture, database, tasks, testing, agents, and design review documents are reviewed and approved.
