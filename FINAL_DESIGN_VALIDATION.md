# LifeQuest — Final Design Validation
**Product:** LifeQuest — Daily Life RPG  
**Document Version:** 2.1.0 (aligned with PRD V2.1.0, post Final Design Validation)  
**Status:** For Review — Planning Only  
**Primary Documentation Language:** English  
**Target Database:** `lifequest_db`  
**Rule:** No source code, no migrations, no dependency installation until this validation and all documents are approved.

---

## 1. Corrected Issue Summary

The following four blocking technical issues were identified and corrected across all planning documents:

| # | Issue | Correction | Documents Updated |
|---|-------|-----------|-------------------|
| 1 | Shield schema assumed `auth_users` table; CodeIgniter Shield's default user table is `users` | All custom table FKs reference `users(id)`; Shield PK type to be verified at install; no BIGINT UNSIGNED assumption | DATABASE.md, ARCHITECTURE.md, AGENTS.md, TASKS.md, DEVELOPMENT_PLAN.md, TESTING.md |
| 2 | Lifetime achievement uniqueness used nullable `season_id` in unique key; MySQL allows multiple NULLs | Added `season_coalesce INT UNSIGNED NOT NULL GENERATED ALWAYS AS (COALESCE(season_id, 0)) VIRTUAL`; UNIQUE on `(user_id, achievement_id, achievement_scope, season_coalesce)` — database-enforced | DATABASE.md, AGENTS.md |
| 3 | No consistent transaction lock ordering across reward operations | Defined consistent order: `daily_rp_totals` → `daily_reward_budgets` → `daily_xp_totals`; applies to all reward operations (completion, approval, corrections, streak bonuses) | DATABASE.md, ARCHITECTURE.md, AGENTS.md, TESTING.md |
| 4 | Remaining ON DELETE CASCADE rules on Shield `users` references | All custom table FKs to `users` use ON DELETE NO ACTION; `activity_configuration_versions.created_by` uses ON DELETE NO ACTION; soft delete via `is_active`/`deleted_at` | DATABASE.md, DESIGN_REVIEW.md, AGENTS.md |

Additional consistency checks completed:
- WEEKLY activity occurrence identity includes ISO week number (`WEEKLY:ISO-week-42`)
- All timestamps stored in UTC; competition dates use Asia/Jakarta timezone
- All testing scenarios reference Shield `users` table (not `auth_users`)
- TASKS.md marks Phase 0 tasks as COMPLETED; Phase 1+ tasks remain PENDING (unverified)
- Contradictions between document summaries and detailed SQL examples resolved

---

## 2. Exact Shield Table and Foreign Key Strategy

### Shield Table (verified at install)

CodeIgniter Shield's default user table is **`users`** (not `auth_users`).

| Shield Table | Purpose | Managed By |
|--------------|---------|------------|
| `users` | Authentication users | Shield official migrations |
| `identities` | Auth identities | Shield official migrations |
| `remember_tokens` | Remember me tokens | Shield official migrations |
| `sessions` | User sessions | Shield official migrations |
| `grants` | Access grants | Shield official migrations |
| `groups` | Auth groups | Shield official migrations |
| `group_users` | Group memberships | Shield official migrations |
| `permissions` | Permissions | Shield official migrations |
| `permission_groups` | Permission groups | Shield official migrations |

### Custom Table Foreign Key Strategy

All custom tables referencing Shield users use `users(id)` with **ON DELETE NO ACTION**:

| Custom Table | FK Column | References | On Delete |
|--------------|-----------|------------|-----------|
| `user_profiles` | `user_id` | `users(id)` | NO ACTION |
| `user_activity_plans` | `user_id` | `users(id)` | NO ACTION |
| `daily_activity_instances` | `user_id` | `users(id)` | NO ACTION |
| `timer_sessions` | `user_id` | `users(id)` | NO ACTION |
| `activity_verification_logs` | `reviewed_by` | `users(id)` | NO ACTION |
| `anti_cheat_flags` | `user_id` | `users(id)` | NO ACTION |
| `verification_appeals` | `user_id` | `users(id)` | NO ACTION |
| `verification_appeals` | `reviewed_by` | `users(id)` | NO ACTION |
| `point_transactions` | `user_id` | `users(id)` | NO ACTION |
| `user_lifetime_stats` | `user_id` | `users(id)` | NO ACTION (PK) |
| `user_season_stats` | `user_id` | `users(id)` | NO ACTION |
| `season_results` | `user_id` | `users(id)` | NO ACTION |
| `daily_streaks` | `user_id` | `users(id)` | NO ACTION |
| `user_achievements` | `user_id` | `users(id)` | NO ACTION |
| `notifications` | `user_id` | `users(id)` | NO ACTION |
| `admin_audit_logs` | `administrator_id` | `users(id)` | NO ACTION |
| `daily_xp_totals` | `user_id` | `users(id)` | NO ACTION |
| `daily_rp_totals` | `user_id` | `users(id)` | NO ACTION |
| `daily_reward_budgets` | `user_id` | `users(id)` | NO ACTION |
| `activity_configuration_versions` | `created_by` | `users(id)` | NO ACTION |

**Blocking prerequisite:** Shield `users.id` primary key type (INT UNSIGNED vs BIGINT UNSIGNED) must be verified during Phase 0 before custom migrations are written. Custom table `user_id` column types must match Shield's actual PK type.

---

## 3. Database-Enforced Achievement Uniqueness

### Table: `user_achievements`

| Column | Type | Null | Description |
|--------|------|------|-------------|
| `id` | BIGINT UNSIGNED | NO | PK |
| `user_id` | BIGINT UNSIGNED | NO | FK → users(id) |
| `achievement_id` | BIGINT UNSIGNED | NO | FK → achievements(id) |
| `achievement_scope` | ENUM('LIFETIME','SEASON') | NO | Scope of achievement |
| `season_id` | BIGINT UNSIGNED | YES | FK → seasons(id) (NULL for LIFETIME) |
| `season_coalesce` | INT UNSIGNED | NO | **VIRTUAL GENERATED: COALESCE(season_id, 0)** |
| `awarded_at` | DATETIME | NO | Award timestamp |

### Unique Constraint

```
UNIQUE KEY uk_ua_user_achievement_scope_season 
  ON (user_id, achievement_id, achievement_scope, season_coalesce)
```

### Behavior

| Scope | `season_id` | `season_coalesce` | Uniqueness Effect |
|-------|-------------|-------------------|-------------------|
| LIFETIME | NULL | 0 | One award per user per achievement (database-enforced) |
| SEASON | NOT NULL | = season_id | One award per user per achievement per season (database-enforced) |

### Why This Works

MySQL treats NULL as distinct in unique indexes, so multiple rows with NULL `season_id` would be allowed. The `season_coalesce` generated column is **NOT NULL**, eliminating this ambiguity. This is database-enforced — no reliance on application-level existence checks alone, which would fail under concurrency.

---

## 4. Consistent Transaction Lock Ordering

All reward-related transactions must acquire locks in this **exact order**:

| Lock Order | Table | Condition | Purpose |
|------------|-------|-----------|---------|
| 1 | `daily_rp_totals` | `user_id = ? AND competition_date = ? AND season_id = ?` | RP daily cap (140) |
| 2 | `daily_reward_budgets` | `user_id = ? AND competition_date = ? AND season_id = ? AND reward_group_id = ?` | Reward group RP budget |
| 3 | `daily_xp_totals` | `user_id = ? AND activity_date = ?` | XP daily cap (150) |

### Applies To All Reward Operations

- Standard activity completion (Phase 3)
- Administrator approval of pending rewards (Phase 4)
- Point corrections / compensating transactions (Phase 7)
- Streak bonus awards (Phase 5)

### Row Initialization

Use `INSERT ... ON DUPLICATE KEY UPDATE` for `daily_reward_budgets`, `daily_xp_totals`, `daily_rp_totals` to atomically initialize or fetch existing rows. Never SELECT-then-INSERT (race condition).

### Deadlock Handling

- Catch MySQL deadlock error (1213)
- Retry transaction up to 3 times with exponential backoff (50ms, 100ms, 200ms)
- `innodb_lock_wait_timeout` set to 5 seconds
- Log all deadlock and lock wait timeout occurrences for monitoring

---

## 5. Historical Data Protection Rules

### Tables Preserved Permanently (No Cascade Delete)

| Table | Reason |
|-------|--------|
| `point_transactions` | Immutable ledger; corrections use compensating transactions |
| `season_results` | Immutable finalized season results |
| `activity_verification_logs` | Verification decision history |
| `admin_audit_logs` | Administrative audit trail |
| `user_achievements` | Achievement history |

### User Deactivation Policy

- **Soft delete only:** Set `user_profiles.is_active = 0` and `deleted_at` timestamp
- **Never hard delete:** Would break FK references from historical tables
- Shield `users.active = 0` (via Shield) to prevent authentication
- All custom table FKs to `users` use ON DELETE NO ACTION

### Season Finalization

- Season finalization is idempotent via `scheduled_job_runs.execution_key`
- Historical season results preserved after finalization
- Lifetime XP never reset across seasons
- Previous season results remain available

---

## 6. Remaining Technical Prerequisites

The following must be verified during Phase 0 (before custom migrations):

| # | Prerequisite | Status | Impact if Unresolved |
|---|-------------|--------|---------------------|
| T1 | PHP 8.5.12 available on Windows | Unverified | Dev environment setup blocked |
| T2 | CodeIgniter 4.7.0 on Packagist | Unverified | Composer installation blocked |
| T3 | MySQL 9.7 LTS available | Unverified | Database provisioning blocked |
| T4 | Bootstrap 5.3.8 available | Unverified | Frontend assets blocked |
| T5 | **Shield `users.id` PK type verified** | **BLOCKING** | Custom FK type mismatch; migration failure |
| T6 | Shield compatible with CI 4.7.0 | Unverified | Auth implementation blocked |
| T7 | No unresolved critical/high security vulnerabilities | Unverified | Production release blocked |

**Blocking item:** T5 (Shield `users.id` type) must be resolved before any custom migration references `users(id)`.

---

## 7. Explicit PASS/FAIL Checklist

| # | Check | PASS | FAIL | N/A | Evidence |
|---|-------|------|------|-----|----------|
| 1 | PRD.md V2.1.0 unchanged | ☒ | ☐ | ☐ | File not modified |
| 2 | Shield table is `users` (not `auth_users`) | ☒ | ☐ | ☐ | Verified from Shield migration source on GitHub (PHASE0_VERIFICATION_RESULT.md Section 3) |
| 3 | All custom FKs reference `users(id)` | ☒ | ☐ | ☐ | DATABASE.md Section 2 table |
| 4 | No ON DELETE CASCADE to Shield `users` | ☒ | ☐ | ☐ | DATABASE.md all FK definitions use NO ACTION |
| 5 | `activity_configuration_versions.created_by` uses NO ACTION | ☒ | ☐ | ☐ | DATABASE.md Section 7 |
| 6 | `daily_xp_totals` and `daily_rp_totals` separate tables | ☒ | ☐ | ☐ | DATABASE.md Section 7 |
| 7 | `daily_point_totals` table removed | ☒ | ☐ | ☐ | DATABASE.md Section 11 |
| 8 | `occurrence_identity` NOT NULL in `daily_activity_instances` | ☒ | ☐ | ☐ | DATABASE.md Section 5 |
| 9 | `season_coalesce` generated column in `user_achievements` | ☒ | ☐ | ☐ | DATABASE.md Section 9 |
| 10 | Achievement uniqueness is database-enforced | ☒ | ☐ | ☐ | DATABASE.md Section 9 |
| 11 | Consistent lock ordering documented | ☒ | ☐ | ☐ | DATABASE.md Section 13 |
| 12 | Lock ordering applies to corrections and pending approvals | ☒ | ☐ | ☐ | DATABASE.md Section 13 |
| 13 | Deadlock retry logic documented | ☒ | ☐ | ☐ | DATABASE.md Section 13 |
| 14 | Evidence stored outside web root | ☒ | ☐ | ☐ | ARCHITECTURE.md Section 17 |
| 15 | Controller-mediated evidence download | ☒ | ☐ | ☐ | ARCHITECTURE.md Section 4 |
| 16 | WEEKLY occurrence identity uses ISO week | ☒ | ☐ | ☐ | DATABASE.md Section 5 |
| 17 | All testing scenarios use Shield `users` table | ☒ | ☐ | ☐ | TESTING.md NEW-14 |
| 18 | Concurrent achievement award tests defined | ☒ | ☐ | ☐ | TESTING.md NEW-11 |
| 19 | Concurrent budget cap tests defined | ☒ | ☐ | ☐ | TESTING.md NEW-13 |
| 20 | TASKS.md Phase 0 tasks marked COMPLETED/IN_PROGRESS | ☒ | ☐ | ☐ | P0-01 through P0-10 COMPLETED; P0-02 IN_PROGRESS (verification documented) |
| 21 | TASKS.md Phase 1+ marked PENDING | ☒ | ☐ | ☐ | TASKS.md Sections 3-10 |
| 22 | No contradictions between documents | ☒ | ☐ | ☐ | Cross-document review complete |
| 23 | No source code generated | ☒ | ☐ | ☐ | Planning only |
| 24 | No migrations executed | ☒ | ☐ | ☐ | Planning only |
| 25 | No dependencies installed | ☒ | ☐ | ☐ | Planning only |
| 26 | PHP 8.5.12 available | ☐ | ☐ | ☒ | NOT VERIFIED locally — not installed. Confirmed released Oct 21, 2026 (php.net/releases/8_5_12.php). Must provision before Phase 1. |
| 27 | MySQL 9.7 LTS available | ☐ | ☐ | ☒ | NOT VERIFIED locally — not installed. Confirmed released Apr 21, 2026, LTS until 2034. Must provision before Phase 1. |
| 28 | CodeIgniter 4.7.0 available | ☒ | ☐ | ☐ | Confirmed on Packagist at codeigniter4/framework v4.7.0 |
| 29 | Bootstrap 5.3.8 available | ☒ | ☐ | ☐ | Confirmed on npm/CDN, released Aug 25, 2025 |
| 30 | Shield `users.id` PK type verified | ☒ | ☐ | ☐ | Verified from Shield migration source code: INT UNSIGNED (PHASE0_VERIFICATION_RESULT.md Section 3). Must confirm from installed package at Composer install time. |
| 31 | Shield CI 4.7.0 compatibility | ☐ | ☐ | ☒ | NOT VERIFIED — Must confirm at Composer install time |
| 32 | No critical/high security vulnerabilities | ☐ | ☐ | ☒ | NOT VERIFIED — Must run dependency check at install time |

---

## 8. Final Phase 1 Readiness Recommendation

### Status: CONDITIONALLY READY — NOT YET APPROVED

### Conditions That Must Be Met Before Phase 1

1. **Provision PHP 8.5.12 on Windows** — Not currently installed
2. **Install Composer** — Not currently installed
3. **Install MySQL 9.7 LTS** — Not currently installed
4. **Install CodeIgniter 4.7.0 via Composer** — Verify installation succeeds
5. **Install CodeIgniter Shield via Composer** — Verify compatibility with CI 4.7.0
6. **Verify Shield `users.id` type from installed package** — Confirm INT UNSIGNED matches custom table definitions
7. **Run dependency security check** — Resolve any critical/high vulnerabilities

### What Is Complete

- All 8 planning documents created and mutually consistent (including PHASE0_VERIFICATION_RESULT.md and FINAL_DESIGN_VALIDATION.md)
- Four blocking technical issues corrected
- Database schema fully specified with corrected constraints
- Transaction lock ordering defined and documented
- Achievement uniqueness is database-enforced via `season_coalesce` generated column
- Historical data protection rules established
- Evidence storage secured outside web root
- Test scenarios cover all design corrections (NEW-01 through NEW-15)
- Phase 0 verification completed and documented
- Shield schema verified from official source code: `users.id` is INT UNSIGNED

### What Remains Before Phase 1

1. Environment provisioning (PHP 8.5.12, Composer, MySQL 9.7)
2. Shield installation and CI 4.7.0 compatibility verification
3. Shield `users.id` type confirmation from installed package
4. Dependency security review
5. Final approval of all planning documents

### Recommendation

**Do not begin Phase 1 until environment provisioning is complete and Shield schema is verified from the installed package.** The planning documentation is complete and internally consistent, but the local environment lacks the required runtime components.

No source code, migrations, dependency installation, or test code may begin until all planning documents including this FINAL_DESIGN_VALIDATION.md are reviewed and approved.
