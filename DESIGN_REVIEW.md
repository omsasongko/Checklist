# LifeQuest — Design Review: Critical Corrections
**Product:** LifeQuest — Daily Life RPG  
**Document Version:** 2.1.0 (aligned with PRD V2.1.0, post Design Review corrections)  
**Status:** For Review — Planning Only  
**Primary Documentation Language:** English  
**Target Database:** `lifequest_db`  
**Rule:** No source code, no migrations, no dependency installation until this review and all corrected documents are approved.

---

## 1. Original Design Defects

The following defects were identified across all planning documents during critical review:

| # | Task | Defect | Risk |
|---|------|--------|------|
| 1 | Shield Compatibility | Assumed `auth_users` table; CodeIgniter Shield's default user table is `users` | Wrong table name in FKs will break migrations; wrong PK type will cause type mismatch errors |
| 2 | Daily XP/RP Aggregation | Single `daily_point_totals` table with both `activity_date` and `competition_date` | Two unique keys on same table do not enforce independent caps correctly |
| 3 | Activity Occurrence Uniqueness | NULLable `activity_session_id` in unique keys | MySQL treats NULL as distinct in unique indexes, defeating once-daily prevention |
| 4 | Protect Historical Records | ON DELETE CASCADE on FKs to Shield `users` table | Cascade deletes destroy Shield auth data and immutable history |
| 5 | Seasonal Achievements | `season_id` NULLable in unique key | MySQL allows multiple NULLs in unique indexes, allowing duplicate LIFETIME awards |
| 6 | Secure Private Evidence Storage | `public/uploads/` directory | Evidence files directly accessible via URL, bypassing authorization |
| 7 | Reward Concurrency and Locking | No consistent lock ordering; no deadlock handling | Concurrent requests can exceed caps; deadlocks possible |
| 8 | Pending Verification and Season Boundaries | `daily_reward_budgets` lacks `season_id` | Late approvals could charge against wrong season's budget |
| 9 | Timer Integrity | No timer ownership validation; no overlap detection | Overlapping timer sessions could both award rewards |
| 10 | Cross-Document Consistency | Inconsistencies between documents | Implementation would diverge from design |

---

## 2. Proposed Corrections

### Correction 1 — Shield Schema (`users` table)

- **CodeIgniter Shield's default user table is `users`, not `auth_users`.**
- Use Shield's official migrations exclusively; do not create `users`, `identities`, `remember_tokens`, `sessions`, `groups`, etc.
- All custom table foreign keys referencing Shield users must reference `users(id)`.
- Do not create FOREIGN KEY constraints from custom tables to Shield-managed tables with ON DELETE CASCADE.
- Custom tables referencing Shield users should use `user_id` matching Shield's actual PK type with ON DELETE NO ACTION.
- **Verify Shield's actual `users.id` primary key type before defining any FKs.** If Shield uses `INT UNSIGNED`, custom tables must match.
- Shield groups to seed: `user`, `administrator`, `super_administrator`.

### Correction 2 — Independent Daily XP and RP Aggregates

Replace `daily_point_totals` with two separate tables:

**`daily_xp_totals`** — XP capped by user's local activity date:
- `user_id`, `activity_date`, `awarded_xp`, `updated_at`
- UNIQUE on `(user_id, activity_date)`

**`daily_rp_totals`** — RP capped by Asia/Jakarta competition date and season:
- `user_id`, `competition_date`, `season_id`, `awarded_rp`, `updated_at`
- UNIQUE on `(user_id, competition_date, season_id)`

This ensures XP and RP caps are enforced independently with correct date dimensions and separate row-level locking.

### Correction 3 — Activity Occurrence Uniqueness

Add a server-generated, non-null `occurrence_identity` column to `daily_activity_instances`:

- `occurrence_identity VARCHAR(255) NOT NULL` — generated server-side, never from client input
- Format: `user:{user_id}:activity:{activity_id}:date:{activity_date}:{repeat_type}:{session_code_or_occurrence}`
  - ONCE_DAILY: `user:123:activity:45:date:2026-10-23:ONCE_DAILY`
  - FIXED_SESSIONS: `user:123:activity:45:date:2026-10-23:FIXED_SESSIONS:MORNING_TIDYING`
  - REPEATABLE: `user:123:activity:45:date:2026-10-23:REPEATABLE:2` (occurrence number)
  - WEEKLY: `user:123:activity:45:date:2026-10-23:WEEKLY:ISO-week-42`
- UNIQUE constraint on `occurrence_identity` (non-null, so MySQL uniqueness behaves correctly)
- Keep `activity_session_id` and `occurrence_number` as nullable informational columns only
- Application generates `occurrence_identity` before INSERT and uses INSERT ... ON DUPLICATE KEY to handle duplicates

### Correction 4 — Protect Historical Records

- Remove ON DELETE CASCADE from all FKs referencing `users` in custom tables. Use ON DELETE NO ACTION.
- Add `is_active TINYINT(1) DEFAULT 1` and `deleted_at DATETIME NULL` to `user_profiles` for soft deactivation.
- Tables that must be preserved permanently:
  - `point_transactions` — immutable ledger, never cascade delete
  - `season_results` — immutable finalized results, never cascade delete
  - `activity_verification_logs` — verification decision history, never cascade delete
  - `admin_audit_logs` — administrative audit trail, never cascade delete
  - `user_achievements` — achievement history, never cascade delete
- User deactivation: set `user_profiles.is_active = 0` and `users.active = 0` (via Shield), do not delete rows.

### Correction 5 — Database-Enforced Achievement Uniqueness

Modify `user_achievements` to use a generated column for NULL-safe uniqueness:

- Add `achievement_scope ENUM('LIFETIME', 'SEASON') NOT NULL DEFAULT 'LIFETIME'`
- Keep `season_id BIGINT UNSIGNED NULL` (NULL for LIFETIME scope)
- Add **generated column** `season_coalesce INT UNSIGNED NOT NULL GENERATED ALWAYS AS (COALESCE(season_id, 0)) VIRTUAL`
- UNIQUE constraint on `(user_id, achievement_id, achievement_scope, season_coalesce)`
- This is database-enforced — no reliance on application-level checks alone

> For `achievement_scope = 'LIFETIME'`: `season_id` is NULL → `season_coalesce = 0` → unique on `(user_id, achievement_id, 'LIFETIME', 0)`. Only one LIFETIME award per user per achievement.
> For `achievement_scope = 'SEASON'`: `season_id` is NOT NULL → `season_coalesce = season_id` → unique on `(user_id, achievement_id, 'SEASON', {season_id})`. Only one SEASON award per user per achievement per season.

### Correction 6 — Secure Private Evidence Storage

- Remove `public/uploads/` entirely from folder structure
- Evidence stored outside web root: `writable/storage/evidence/` or `/var/lifequest/storage/evidence/`
- `activity_evidence.private_storage_key` stores the relative path within private storage, not a public URL
- Evidence retrieval only through controller-mediated download endpoint with authorization check
- File validation: MIME type + actual content inspection (finfo), max 5 MB, randomized filename (UUID + extension), SHA-256 hash, strip EXIF/metadata
- Retention: standard evidence deleted 30 days after verification completion; disputed evidence retained until dispute resolution

### Correction 7 — Consistent Transaction Lock Ordering

All reward-related transactions must acquire locks in this **exact order**:

1. `daily_rp_totals` — `SELECT ... FOR UPDATE WHERE user_id = ? AND competition_date = ? AND season_id = ?`
2. `daily_reward_budgets` — `SELECT ... FOR UPDATE WHERE user_id = ? AND competition_date = ? AND season_id = ? AND reward_group_id = ?`
3. `daily_xp_totals` — `SELECT ... FOR UPDATE WHERE user_id = ? AND activity_date = ?`
4. Insert into `point_transactions` with idempotency key
5. Update aggregate rows
6. Commit

This order applies to **all** reward operations:
- Standard activity completion
- Administrator approval of pending rewards
- Point corrections (compensating transactions)
- Streak bonus awards

### Correction 8 — Row Initialization and Deadlock Handling

**Row initialization:**
- Use `INSERT ... ON DUPLICATE KEY UPDATE` for `daily_reward_budgets`, `daily_xp_totals`, `daily_rp_totals`
- Never SELECT-then-INSERT (race condition)

**Deadlock handling:**
- Catch MySQL deadlock error (1213)
- Retry transaction up to 3 times with exponential backoff (50ms, 100ms, 200ms)
- Lock wait timeout: 5 seconds (innodb_lock_wait_timeout)
- Log all deadlock occurrences for monitoring

**Daily RP cap across groups:**
- Sum `awarded_rp` across all `daily_reward_budgets` rows for same user/date/season before awarding
- Enforce 140 RP total cap in addition to per-group budget

**Idempotency:**
- `point_transactions.idempotency_key` UNIQUE constraint prevents duplicate reward transactions
- Application generates idempotency key from occurrence_identity + action type

### Correction 9 — Pending Verification and Season Boundaries

**`daily_reward_budgets` includes `season_id`:**
- UNIQUE on `(user_id, competition_date, season_id, reward_group_id)`
- Pending submissions approved during 24-hour grace period charge against the original season's budget row

**Configuration snapshot:**
- `activity_configuration_versions` stores full configuration at activity instance creation
- `daily_activity_instances.configuration_version_id` references the snapshot used
- When approving pending submissions after season close, use the original configuration_version_id snapshot

**24-hour grace period:**
- Season `submissions_closed_at` marks the cutoff
- During grace period: pre-cutoff submissions may be reviewed and awarded old-season RP
- After grace period: unresolved submissions become ineligible for old-season RP

**Season finalization idempotency:**
- `scheduled_job_runs.execution_key` UNIQUE prevents duplicate finalization runs
- Finalization checks `seasons.status = 'FINALIZED'` before proceeding

### Correction 10 — Timer Integrity

**Timer ownership:**
- `timer_sessions.user_id` must match `daily_activity_instances.user_id`
- Backend validates ownership before accepting timer start/finish

**Overlap detection:**
- Before starting a timer, check for existing `RUNNING` or `FINISHED` timer sessions for same user/activity/date with overlapping time windows
- Reject new timer start if overlap detected

**Prevent repeated completion rewards:**
- Only one FINISHED timer per activity instance; subsequent finish attempts return existing result

**Safe recovery:**
- Cleanup job marks `RUNNING` timers older than maximum eligible duration as `CANCELLED`

---

## 3. Updated Database Constraints

### 3.1 user_profiles (soft delete, no cascade from users)

```sql
CREATE TABLE user_profiles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    display_name VARCHAR(100) NULL,
    avatar_path VARCHAR(255) NULL,
    bio TEXT NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Jakarta',
    leaderboard_visible TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_user_profiles_user_id (user_id),
    CONSTRAINT fk_user_profiles_user_id FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 user_achievements (database-enforced uniqueness with generated column)

```sql
CREATE TABLE user_achievements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    achievement_id BIGINT UNSIGNED NOT NULL,
    achievement_scope ENUM('LIFETIME', 'SEASON') NOT NULL DEFAULT 'LIFETIME',
    season_id BIGINT UNSIGNED NULL,
    season_coalesce INT UNSIGNED NOT NULL GENERATED ALWAYS AS (COALESCE(season_id, 0)) VIRTUAL,
    awarded_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_ua_user_achievement_scope_season (user_id, achievement_id, achievement_scope, season_coalesce),
    CONSTRAINT fk_ua_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE NO ACTION,
    CONSTRAINT fk_ua_achievement_id FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE NO ACTION,
    CONSTRAINT fk_ua_season_id FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> `season_coalesce` is a MySQL VIRTUAL generated column: `COALESCE(season_id, 0)`. Being NOT NULL, it eliminates MySQL's NULL-uniqueness ambiguity.
> - LIFETIME: `season_id` NULL → `season_coalesce = 0` → one award per user per achievement
> - SEASON: `season_id` NOT NULL → `season_coalesce = season_id` → one award per user per achievement per season

### 3.3 activity_configuration_versions (no CASCADE on created_by)

```sql
CREATE TABLE activity_configuration_versions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    activity_id BIGINT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL DEFAULT 1,
    configuration_snapshot JSON NOT NULL,
    effective_season_id BIGINT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_activity_configuration_versions_activity_version (activity_id, version_number),
    CONSTRAINT fk_acv_activity_id FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    CONSTRAINT fk_acv_season_id FOREIGN KEY (effective_season_id) REFERENCES seasons(id) ON DELETE NO ACTION,
    CONSTRAINT fk_acv_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> `created_by` FK uses ON DELETE NO ACTION — historical configuration records are preserved even if the administrator who created them is deactivated.

### 3.4 All Custom Tables Reference `users` (NOT `auth_users`)

Every custom table foreign key referencing Shield users uses `users(id)` with ON DELETE NO ACTION:
- `user_profiles.user_id` → `users(id)`
- `user_activity_plans.user_id` → `users(id)`
- `daily_activity_instances.user_id` → `users(id)`
- `timer_sessions.user_id` → `users(id)`
- `activity_verification_logs.reviewed_by` → `users(id)`
- `anti_cheat_flags.user_id` → `users(id)`
- `verification_appeals.user_id` → `users(id)`
- `verification_appeals.reviewed_by` → `users(id)`
- `point_transactions.user_id` → `users(id)`
- `user_lifetime_stats.user_id` → `users(id)`
- `user_season_stats.user_id` → `users(id)`
- `season_results.user_id` → `users(id)`
- `daily_streaks.user_id` → `users(id)`
- `user_achievements.user_id` → `users(id)`
- `notifications.user_id` → `users(id)`
- `admin_audit_logs.administrator_id` → `users(id)`
- `daily_xp_totals.user_id` → `users(id)`
- `daily_rp_totals.user_id` → `users(id)`
- `daily_reward_budgets.user_id` → `users(id)`

---

## 4. Updated Transaction Design

### 4.1 Consistent Lock Ordering (All Reward Operations)

**Lock order (must be identical across all transaction types):**

1. `daily_rp_totals` — `SELECT ... FOR UPDATE WHERE user_id = ? AND competition_date = ? AND season_id = ?`
2. `daily_reward_budgets` — `SELECT ... FOR UPDATE WHERE user_id = ? AND competition_date = ? AND season_id = ? AND reward_group_id = ?`
3. `daily_xp_totals` — `SELECT ... FOR UPDATE WHERE user_id = ? AND activity_date = ?`
4. Insert into `point_transactions` with idempotency key
5. Update aggregate rows
6. Commit

**Applies to:**
- Standard activity completion (Phase 3)
- Administrator approval of pending rewards (Phase 4)
- Point corrections / compensating transactions (Phase 7)
- Streak bonus awards (Phase 5)

### 4.2 Deadlock and Lock Wait Handling

- Catch MySQL deadlock error (1213), retry up to 3 times with exponential backoff
- `innodb_lock_wait_timeout` set to 5 seconds
- Log all deadlock and lock wait timeout occurrences for monitoring

### 4.3 Row Initialization

- Use `INSERT ... ON DUPLICATE KEY UPDATE` for `daily_reward_budgets`, `daily_xp_totals`, `daily_rp_totals`
- Never SELECT-then-INSERT (race condition on row creation)

---

## 5. Outstanding Technical Risks

| # | Risk | Severity | Mitigation |
|---|------|----------|------------|
| R1 | Shield `users.id` PK type mismatch | High | Verify Shield `users.id` type before creating any custom FKs; adjust custom table column types accordingly |
| R2 | MySQL NULL uniqueness in unique indexes | High | Mitigated by `season_coalesce` generated column (NOT NULL) and `occurrence_identity` (NOT NULL) |
| R3 | Deadlocks under concurrent reward processing | Medium | Consistent lock ordering + retry logic with exponential backoff |
| R4 | Shield migration incompatibility with CI 4.7.0 | Medium | Verify Shield version compatibility during Phase 0; pin versions in composer.lock |
| R5 | Evidence file served bypassing authorization | High | Store outside web root; controller-mediated download with auth check |
| R6 | Season finalization race condition | Medium | Idempotent execution_key; lock on seasons row |
| R7 | Late approval exceeding original-day budget | High | Revalidate budgets at approval time using original configuration snapshot |
| R8 | Timer overlap under concurrent starts | Medium | Row lock on activity instance; overlap check within transaction |
| R9 | User deactivation destroying history | High | ON DELETE NO ACTION on all users FKs; soft delete via is_active/deleted_at |
| R10 | PHP 8.5.12 / MySQL 9.7 availability | Medium | Verify during Phase 0; document fallback versions |

---

## 6. Readiness Assessment for Phase 1

**Status: NOT READY — Final validation required**

The following must be completed and approved before Phase 1:

| Item | Status | Required Action |
|------|--------|-----------------|
| DESIGN_REVIEW.md | Complete | This document — awaiting approval |
| DATABASE.md corrections | Complete | Shield `users` table, generated column, lock ordering, no CASCADE |
| ARCHITECTURE.md corrections | Pending | Update Shield table name references |
| DEVELOPMENT_PLAN.md corrections | Pending | Update tasks for Shield verification |
| TASKS.md corrections | Pending | Add tasks for generated column, lock order testing |
| TESTING.md corrections | Pending | Add concurrent achievement award tests, lock order tests |
| AGENTS.md corrections | Pending | Update DR rules for `users` table and generated column |
| FINAL_DESIGN_VALIDATION.md | Pending | Create final validation document |

**Blocking items for Phase 1:**
1. Shield `users.id` type verification (Task 1)
2. All documents use `users` consistently (not `auth_users`)
3. Database-enforced achievement uniqueness via `season_coalesce`
4. Consistent lock ordering documented in all relevant files
5. No ON DELETE CASCADE to Shield tables

---

## 7. Next Step

Present DESIGN_REVIEW.md, DATABASE.md, ARCHITECTURE.md, DEVELOPMENT_PLAN.md, TASKS.md, TESTING.md, AGENTS.md, and FINAL_DESIGN_VALIDATION.md for approval. No implementation (Phase 1) may begin until all corrections are approved.
