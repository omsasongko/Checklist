# LifeQuest — Database Design
**Product:** LifeQuest — Daily Life RPG  
**Document Version:** 2.1.0 (aligned with PRD V2.1.0, Section 19)  
**Status:** For Review — Planning Only (post Design Review corrections)  
**Primary Documentation Language:** English  
**Target Database:** `lifequest_db`  
**Database Engine:** MySQL 9.7 LTS  
**Storage Engine:** InnoDB  
**Character Set:** utf8mb4  
**Collation:** utf8mb4_unicode_ci  
**Rule:** No migrations, no table creation, no database changes until approved.

---

## 1. Database Principles (PRD Section 19.4)

- Use CodeIgniter Migrations for schema versioning
- Use CodeIgniter Seeders for initial data
- Use database transactions for atomic operations
- Use row-level locking (SELECT ... FOR UPDATE) where required
- Use prepared or parameterized queries (CodeIgniter Query Builder)
- Avoid N+1 queries
- Never store plaintext passwords (handled by Shield)
- Never trust point values received from the browser
- Preserve point ledger history (immutable transactions)
- Keep season results immutable after finalization
- All timestamps stored in UTC
- **No ON DELETE CASCADE from custom tables to Shield `users` table** — use ON DELETE NO ACTION
- **User deactivation via soft delete (is_active/deleted_at), not hard delete**
- **Consistent lock ordering: daily_rp_totals → daily_reward_budgets → daily_xp_totals** (prevents deadlocks)

---

## 2. Authentication Schema (Shield — PRD Section 19.1)

Use the database structure installed by CodeIgniter Shield's official migrations. Do not create duplicate password or authentication identity tables. Do not recreate authentication tables manually.

**Critical:** Before defining any FOREIGN KEY from custom tables to Shield tables, verify Shield's actual `users` primary key type (INT UNSIGNED vs BIGINT UNSIGNED vs other). Custom table `user_id` columns must match Shield's actual PK type.

**Shield's default user table is `users`, not `auth_users`.** All custom table foreign keys referencing Shield users must reference `users(id)`.

Shield-managed tables (do not modify, do not create FKs with CASCADE to these):
- `users` — id: INT UNSIGNED AUTO_INCREMENT PK
- `identities` — id: INT UNSIGNED AUTO_INCREMENT PK, user_id: INT UNSIGNED FK → users(id) CASCADE
- `logins` — id: INT UNSIGNED AUTO_INCREMENT PK, user_id: INT UNSIGNED FK (nullable, no cascade)
- `token_logins` — id: INT UNSIGNED AUTO_INCREMENT PK, user_id: INT UNSIGNED FK (nullable, no cascade)
- `remember_tokens` — id: INT UNSIGNED AUTO_INCREMENT PK, user_id: INT UNSIGNED FK → users(id) CASCADE
- `groups_users` — id: INT UNSIGNED AUTO_INCREMENT PK, user_id: INT UNSIGNED FK → users(id) CASCADE
- `permissions_users` — id: INT UNSIGNED AUTO_INCREMENT PK, user_id: INT UNSIGNED FK → users(id) CASCADE

Table names are configurable via `$authConfig->tables` but default to the names above.

Custom Shield groups to be seeded:
- `user` — Registered users
- `administrator` — Administrators
- `super_administrator` — Super administrators

---

## 3. Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Tables | snake_case, plural | `user_profiles`, `daily_xp_totals` |
| Primary keys | `id` (BIGINT UNSIGNED, AUTO_INCREMENT) | `id` |
| Foreign keys | `{referenced_table}_id` | `user_id`, `activity_id`, `season_id` |
| Unique constraints | `uk_{table}_{column(s)}` | `uk_user_profiles_user_id` |
| Generated columns | `snake_case`, NOT NULL | `season_coalesce` |
| Indexes | `idx_{table}_{column(s)}` | `idx_daily_activity_instances_user_date` |
| Timestamps | `created_at`, `updated_at` (DATETIME, nullable) | |
| Soft deletes | `deleted_at` (DATETIME, nullable) | |
| Soft deactivation | `is_active` (TINYINT(1), default 1) | |
| Occurrence identity | `occurrence_identity` (VARCHAR(255), NOT NULL) | Server-generated unique string |
| Codes | VARCHAR, UPPER_CASE with underscores | `MORNING`, `FITNESS`, `SELF_CHECK` |
| Timezone | UTC for all stored timestamps | |

---

## 4. Identity and Profiles

### user_profiles

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id, UNIQUE | | Link to Shield user |
| display_name | VARCHAR(100) | YES | | | Public display name (Indonesian UI) |
| avatar_path | VARCHAR(255) | YES | | | Path to avatar (private storage) |
| bio | TEXT | YES | | | User biography |
| timezone | VARCHAR(64) | NO | | 'Asia/Jakarta' | User's configured timezone |
| leaderboard_visible | TINYINT(1) | NO | | 1 | Opt-out of public leaderboard |
| is_active | TINYINT(1) | NO | | 1 | Soft deactivation flag |
| deleted_at | DATETIME | YES | | | Soft delete timestamp |
| created_at | DATETIME | YES | | | Record creation |
| updated_at | DATETIME | YES | | | Record last update |

**Constraints:**
- FK `user_profiles_user_id_fk` → `users(id)` ON DELETE NO ACTION ON UPDATE CASCADE
- UK `uk_user_profiles_user_id` on `user_id`

> User deactivation: set `is_active = 0` and `deleted_at` timestamp. Do NOT delete the row — this would break FK references from historical tables (point_transactions, season_results, etc.).

---

## 5. Activities Domain

### activity_categories

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| category_code | VARCHAR(50) | NO | UNIQUE | | Code like `MORNING_ROUTINE` |
| name | VARCHAR(100) | NO | | | Category name (Indonesian) |
| icon | VARCHAR(100) | YES | | | Bootstrap icon class |
| sort_order | INT UNSIGNED | NO | | 0 | Display sort order |
| is_active | TINYINT(1) | NO | | 1 | Whether category is active |

---

### reward_groups

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| group_code | VARCHAR(50) | NO | UNIQUE | | Code like `MORNING`, `FITNESS` |
| name | VARCHAR(100) | NO | | | Reward group name (Indonesian) |
| description | TEXT | YES | | | Group description |
| is_active | TINYINT(1) | NO | | 1 | Whether group is active |

---

### activities

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| category_id | BIGINT UNSIGNED | YES | FK → activity_categories.id | | Activity category |
| reward_group_id | BIGINT UNSIGNED | YES | FK → reward_groups.id | | Reward group for RP budget |
| activity_code | VARCHAR(50) | NO | UNIQUE | | Unique activity code |
| name | VARCHAR(255) | NO | | | Activity name (Indonesian) |
| description | TEXT | YES | | | Activity description |
| activity_type | VARCHAR(20) | NO | | | `STANDARD`, `CUSTOM`, `ALTERNATIVE` |
| repeat_type | VARCHAR(20) | NO | | | `ONCE_DAILY`, `FIXED_SESSIONS`, `REPEATABLE`, `WEEKLY` |
| verification_policy_id | BIGINT UNSIGNED | YES | FK → verification_policies.id | | Verification policy |
| default_xp | INT UNSIGNED | NO | | 0 | Default XP reward |
| default_rp | INT UNSIGNED | NO | | 0 | Default RP reward |
| minimum_duration_seconds | INT UNSIGNED | YES | | | Minimum timer duration |
| cooldown_seconds | INT UNSIGNED | YES | | | Cooldown between completions |
| maximum_daily_completions | INT UNSIGNED | NO | | 1 | Max completions per day |
| maximum_weekly_completions | INT UNSIGNED | NO | | 7 | Max completions per week |
| is_active | TINYINT(1) | NO | | 1 | Whether activity is active |
| created_at | DATETIME | YES | | | Record creation |
| updated_at | DATETIME | YES | | | Record last update |

**Constraints:**
- UK `uk_activities_activity_code` on `activity_code`
- FK → `activity_categories(id)` ON DELETE SET NULL
- FK → `reward_groups(id)` ON DELETE SET NULL
- FK → `verification_policies(id)` ON DELETE SET NULL

---

### activity_sessions

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| activity_id | BIGINT UNSIGNED | NO | FK → activities.id | | Parent activity |
| session_code | VARCHAR(50) | NO | | | Session code (e.g., `MORNING_TIDYING`) |
| session_name | VARCHAR(255) | NO | | | Session name (Indonesian) |
| scheduled_start | TIME | YES | | | Scheduled start time |
| scheduled_end | TIME | YES | | | Scheduled end time |
| xp_value | INT UNSIGNED | NO | | 0 | XP for this session |
| rp_value | INT UNSIGNED | NO | | 0 | RP for this session |
| sort_order | INT UNSIGNED | NO | | 0 | Display sort order |
| is_active | TINYINT(1) | NO | | 1 | Whether session is active |

**Constraints:**
- FK → `activities(id)` ON DELETE CASCADE

---

### user_activity_plans

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User who owns the plan |
| activity_id | BIGINT UNSIGNED | NO | FK → activities.id | | Activity in the plan |
| recurrence_rule | VARCHAR(255) | YES | | | Recurrence rule (e.g., daily, weekly) |
| timezone | VARCHAR(64) | NO | | 'Asia/Jakarta' | User's timezone for this plan |
| is_enabled | TINYINT(1) | NO | | 1 | Whether plan is enabled |
| created_at | DATETIME | YES | | | Record creation |
| updated_at | DATETIME | YES | | | Record last update |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- FK → `activities(id)` ON DELETE CASCADE

---

### daily_activity_instances

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| activity_id | BIGINT UNSIGNED | NO | FK → activities.id | | Activity |
| activity_session_id | BIGINT UNSIGNED | YES | FK → activity_sessions.id | | Session (for fixed sessions) |
| occurrence_number | INT UNSIGNED | YES | | | Occurrence number for repeatable |
| **occurrence_identity** | **VARCHAR(255)** | **NO** | **UNIQUE** | | **Server-generated unique occurrence identity** |
| activity_date | DATE | NO | | | Local activity date |
| competition_date | DATE | NO | | | Competition date (Asia/Jakarta) |
| season_id | BIGINT UNSIGNED | YES | FK → seasons.id | | Related season |
| configuration_version_id | BIGINT UNSIGNED | YES | FK → activity_configuration_versions.id | | Config snapshot at creation |
| completion_status | VARCHAR(20) | NO | | 'PENDING' | `PENDING`, `COMPLETED`, `SKIPPED` |
| verification_status | VARCHAR(20) | NO | | 'NOT_REQUIRED' | Verification state |
| reward_status | VARCHAR(20) | NO | | 'NOT_ELIGIBLE' | `NOT_ELIGIBLE`, `PENDING`, `CONFIRMED`, `REJECTED`, `REVERSED` |
| scheduled_at | DATETIME | YES | | | Scheduled time |
| completed_at | DATETIME | YES | | | Completion time |
| created_at | DATETIME | YES | | | Record creation |
| updated_at | DATETIME | YES | | | Record last update |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- FK → `activities(id)` ON DELETE NO ACTION
- FK → `activity_sessions(id)` ON DELETE SET NULL
- FK → `seasons(id)` ON DELETE NO ACTION
- FK → `activity_configuration_versions(id)` ON DELETE NO ACTION
- **UK `uk_daily_activity_instances_occurrence_identity` on `occurrence_identity` (NON-NULL)**
- IDX `idx_daily_activity_instances_user_date` on `(user_id, activity_date)`
- IDX `idx_daily_activity_instances_competition_date` on `(competition_date)`
- IDX `idx_daily_activity_instances_verification` on `(verification_status)`
- IDX `idx_daily_activity_instances_reward` on `(reward_status)`
- IDX `idx_daily_activity_instances_season` on `(season_id)`

> `occurrence_identity` is generated server-side (never from client input) in format: `user:{user_id}:activity:{activity_id}:date:{activity_date}:{repeat_type}:{session_code_or_occurrence}`. Being NOT NULL, MySQL uniqueness behaves correctly (no NULL ambiguity).

> **WEEKLY repeat type:** The `occurrence_identity` includes the ISO week number (e.g., `WEEKLY:ISO-week-42`). ISO week boundaries follow Monday-start weeks per ISO-8601. The application must compute the ISO week from `activity_date` in the user's configured timezone to ensure consistent occurrence identity across timezone boundaries.

---

## 6. Verification Domain

### verification_policies

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| policy_code | VARCHAR(50) | NO | UNIQUE | | Code like `SELF_CHECK`, `TIMER_REQUIRED` |
| verification_methods | VARCHAR(255) | NO | | | JSON array of methods |
| requires_evidence | TINYINT(1) | NO | | 0 | Whether evidence is required |
| requires_approval | TINYINT(1) | NO | | 0 | Whether admin approval is required |
| minimum_duration_seconds | INT UNSIGNED | YES | | | Minimum duration for timer activities |
| is_active | TINYINT(1) | NO | | 1 | Whether policy is active |

---

### activity_evidence

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| activity_instance_id | BIGINT UNSIGNED | NO | FK → daily_activity_instances.id | | Related activity instance |
| evidence_type | VARCHAR(20) | NO | | | `PHOTO` |
| private_storage_key | VARCHAR(255) | NO | | | Randomized filename, stored OUTSIDE web root |
| file_hash | VARCHAR(64) | NO | | | SHA-256 hash of file contents |
| file_size | INT UNSIGNED | NO | | | File size in bytes |
| mime_type | VARCHAR(100) | NO | | | MIME type (image/jpeg, image/png, image/webp) |
| uploaded_at | DATETIME | NO | | | Upload timestamp |
| expires_at | DATETIME | YES | | | Evidence retention expiration |
| deleted_at | DATETIME | YES | | | Soft delete |

**Constraints:**
- FK → `daily_activity_instances(id)` ON DELETE NO ACTION
- IDX `idx_activity_evidence_expires` on `(expires_at)`
- IDX `idx_activity_evidence_hash` on `(file_hash)` — for duplicate evidence detection

> Evidence files are stored outside the public web root (e.g., `writable/storage/evidence/` or `/var/lifequest/storage/evidence/`). Retrieval is controller-mediated with authorization check on every request.

---

### timer_sessions

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| activity_instance_id | BIGINT UNSIGNED | YES | FK → daily_activity_instances.id | | Related activity instance |
| started_at | DATETIME | NO | | | Server-recorded start time (UTC) |
| finished_at | DATETIME | YES | | | Server-recorded finish time (UTC) |
| elapsed_seconds | INT UNSIGNED | YES | | | Calculated elapsed duration |
| status | VARCHAR(20) | NO | | 'RUNNING' | `RUNNING`, `FINISHED`, `CANCELLED` |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- FK → `daily_activity_instances(id)` ON DELETE SET NULL
- IDX `idx_timer_sessions_user_status` on `(user_id, status)`

---

### activity_verification_logs

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| activity_instance_id | BIGINT UNSIGNED | NO | FK → daily_activity_instances.id | | Related activity instance |
| verification_method | VARCHAR(20) | NO | | | `SELF_CHECK`, `TIMER`, `PHOTO_PROOF`, `ADMIN_APPROVAL` |
| decision | VARCHAR(20) | NO | | | `AUTO_VALIDATED`, `MANUALLY_APPROVED`, `REJECTED` |
| risk_score | INT UNSIGNED | NO | | 0 | Risk score 0-100 |
| reason_code | VARCHAR(50) | YES | | | Explainable reason code |
| reviewed_by | INT UNSIGNED | YES | FK → users.id | | Administrator who reviewed |
| reviewed_at | DATETIME | YES | | | Review timestamp |
| created_at | DATETIME | NO | | | Log creation timestamp |

**Constraints:**
- FK → `daily_activity_instances(id)` ON DELETE NO ACTION
- FK → `users(id)` (reviewed_by) ON DELETE NO ACTION
- IDX `idx_activity_verification_logs_risk` on `(risk_score)`
- IDX `idx_activity_verification_logs_decision` on `(decision)`
- IDX `idx_activity_verification_logs_created` on `(created_at)`

> Preserved permanently — no cascade delete.

---

### anti_cheat_flags

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| activity_instance_id | BIGINT UNSIGNED | YES | FK → daily_activity_instances.id | | Related activity instance |
| flag_type | VARCHAR(50) | NO | | | Type of flag |
| severity | VARCHAR(20) | NO | | | `LOW`, `MEDIUM`, `HIGH` |
| score | INT UNSIGNED | NO | | 0 | Risk score contribution |
| reason_code | VARCHAR(50) | NO | | | Reason code |
| review_status | VARCHAR(20) | NO | | 'PENDING' | `PENDING`, `REVIEWED`, `DISMISSED` |
| created_at | DATETIME | NO | | | Flag creation timestamp |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- FK → `daily_activity_instances(id)` ON DELETE SET NULL
- IDX `idx_anti_cheat_flags_user` on `(user_id)`
- IDX `idx_anti_cheat_flags_review` on `(review_status)`
- IDX `idx_anti_cheat_flags_severity` on `(severity)`

---

### verification_appeals

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User appealing |
| activity_instance_id | BIGINT UNSIGNED | NO | FK → daily_activity_instances.id | | Related activity instance |
| appeal_reason | TEXT | NO | | | User's appeal reason |
| status | VARCHAR(20) | NO | | 'PENDING' | `PENDING`, `APPROVED`, `REJECTED` |
| reviewed_by | INT UNSIGNED | YES | FK → users.id | | Administrator who decided |
| resolution_reason | TEXT | YES | | | Administrator's decision reason |
| created_at | DATETIME | NO | | | Appeal creation timestamp |
| resolved_at | DATETIME | YES | | | Resolution timestamp |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- FK → `daily_activity_instances(id)` ON DELETE NO ACTION
- FK → `users(id)` (reviewed_by) ON DELETE NO ACTION
- IDX `idx_verification_appeals_status` on `(status)`

> Preserved permanently — no cascade delete.

---

## 7. Rewards Domain

### activity_configuration_versions

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| activity_id | BIGINT UNSIGNED | NO | FK → activities.id | | Activity |
| version_number | INT UNSIGNED | NO | | 1 | Configuration version |
| configuration_snapshot | JSON | NO | | | Full configuration snapshot |
| effective_season_id | BIGINT UNSIGNED | YES | FK → seasons.id | | Effective season |
| created_by | INT UNSIGNED | NO | FK → users.id | | Administrator who created |
| created_at | DATETIME | NO | | | Creation timestamp |

**Constraints:**
- FK → `activities(id)` ON DELETE CASCADE
- FK → `seasons(id)` ON DELETE NO ACTION
- FK → `users(id)` ON DELETE NO ACTION
- UK `uk_activity_configuration_versions_activity_version` on `(activity_id, version_number)`

---

### season_reward_group_budgets

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| season_id | BIGINT UNSIGNED | NO | FK → seasons.id | | Season |
| reward_group_id | BIGINT UNSIGNED | NO | FK → reward_groups.id | | Reward group |
| daily_rp_budget | INT UNSIGNED | NO | | 0 | Daily RP budget for this group |
| configuration_version | VARCHAR(50) | YES | | | Configuration version reference |

**Constraints:**
- FK → `seasons(id)` ON DELETE CASCADE
- FK → `reward_groups(id)` ON DELETE CASCADE
- UK `uk_season_reward_group_budgets_season_group` on `(season_id, reward_group_id)`

---

### daily_reward_budgets (season-scoped)

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| competition_date | DATE | NO | | | Competition date (Asia/Jakarta) |
| season_id | BIGINT UNSIGNED | NO | FK → seasons.id | | Season (for grace-period budget enforcement) |
| reward_group_id | BIGINT UNSIGNED | NO | FK → reward_groups.id | | Reward group |
| awarded_rp | INT UNSIGNED | NO | | 0 | RP awarded so far today |
| updated_at | DATETIME | NO | | | Last update timestamp |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- FK → `seasons(id)` ON DELETE NO ACTION
- FK → `reward_groups(id)` ON DELETE NO ACTION
- **UK `uk_drb_user_date_season_group` on `(user_id, competition_date, season_id, reward_group_id)`**
- IDX `idx_drb_date` on `(competition_date)`
- IDX `idx_drb_season` on `(season_id)`

> Row-level locking required on this table for concurrent budget updates. Use `INSERT ... ON DUPLICATE KEY` for atomic row initialization.

---

### daily_xp_totals (independent XP cap by local activity date)

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| activity_date | DATE | NO | | | Local activity date (user's timezone) |
| awarded_xp | INT UNSIGNED | NO | | 0 | XP awarded today (local date) |
| updated_at | DATETIME | NO | | | Last update timestamp |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- **UK `uk_dxt_user_date` on `(user_id, activity_date)`**
- IDX `idx_dxt_date` on `(activity_date)`

> Separate from RP totals. XP cap is 150 per user per local activity_date.

---

### daily_rp_totals (independent RP cap by competition date + season)

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| competition_date | DATE | NO | | | Competition date (Asia/Jakarta) |
| season_id | BIGINT UNSIGNED | NO | FK → seasons.id | | Season |
| awarded_rp | INT UNSIGNED | NO | | 0 | RP awarded today (competition date) |
| updated_at | DATETIME | NO | | | Last update timestamp |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- FK → `seasons(id)` ON DELETE NO ACTION
- **UK `uk_drt_user_date_season` on `(user_id, competition_date, season_id)`**
- IDX `idx_drt_season` on `(season_id)`
- IDX `idx_drt_date` on `(competition_date)`

> Separate from XP totals. RP cap is 140 per user per competition_date per season. Enforced independently from XP.

---

### point_transactions (immutable ledger, preserved permanently)

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| season_id | BIGINT UNSIGNED | YES | FK → seasons.id | | Season |
| activity_instance_id | BIGINT UNSIGNED | YES | FK → daily_activity_instances.id | | Related activity instance |
| reward_group_id | BIGINT UNSIGNED | YES | FK → reward_groups.id | | Reward group |
| transaction_type | VARCHAR(30) | NO | | | `ACTIVITY_REWARD`, `STREAK_BONUS`, `ADMIN_CORRECTION`, `REWARD_REVERSAL` |
| nominal_xp | INT UNSIGNED | NO | | 0 | Nominal XP (before cap) |
| nominal_rp | INT UNSIGNED | NO | | 0 | Nominal RP (before cap) |
| xp_delta | INT | NO | | 0 | Actual XP delta (can be negative for reversals) |
| rp_delta | INT | NO | | 0 | Actual RP delta (can be negative for reversals) |
| idempotency_key | VARCHAR(100) | NO | UNIQUE | | Server-controlled unique key |
| created_at | DATETIME | NO | | | Transaction timestamp |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- FK → `seasons(id)` ON DELETE NO ACTION
- FK → `daily_activity_instances(id)` ON DELETE NO ACTION
- FK → `reward_groups(id)` ON DELETE NO ACTION
- **UK `uk_point_transactions_idempotency_key` on `idempotency_key`**
- IDX `idx_point_transactions_user` on `(user_id)`
- IDX `idx_point_transactions_season` on `(season_id)`
- IDX `idx_point_transactions_type` on `(transaction_type)`
- IDX `idx_point_transactions_created` on `(created_at)`

> Point transactions are immutable after posting. Corrections use compensating transactions (new rows with negative deltas), never updates to existing rows. Never cascade delete.

---

### user_lifetime_stats (preserved permanently)

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| user_id | INT UNSIGNED | NO | PK | FK → users.id | User |
| total_xp | BIGINT UNSIGNED | NO | | 0 | Lifetime accumulated XP |
| lifetime_level | INT UNSIGNED | NO | | 1 | Current lifetime level |
| total_completions | INT UNSIGNED | NO | | 0 | Total valid completions |
| longest_streak | INT UNSIGNED | NO | | 0 | Longest streak ever |
| updated_at | DATETIME | NO | | | Last update timestamp |

**Constraints:**
- PK/FK → `users(id)` ON DELETE NO ACTION

---

## 8. Competition Domain

### ranks

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| rank_code | VARCHAR(50) | NO | UNIQUE | | Code like `WARRIOR`, `MYTHIC_IMMORTAL` |
| rank_name | VARCHAR(100) | NO | | | Rank name (English) |
| default_min_rp | INT UNSIGNED | NO | | 0 | Default minimum RP |
| default_max_rp | INT UNSIGNED | YES | | NULL | NULL = unlimited |
| display_order | INT UNSIGNED | NO | | 0 | Display order |
| icon_path | VARCHAR(255) | YES | | | Path to rank emblem |

---

### seasons

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| season_number | INT UNSIGNED | NO | UNIQUE | | Season number (1, 2, 3...) |
| season_name | VARCHAR(100) | NO | | | Season name (Indonesian) |
| starts_at | DATETIME | NO | | | Season start (UTC) |
| ends_at | DATETIME | NO | | | Season end (UTC) |
| status | VARCHAR(20) | NO | | 'SCHEDULED' | `SCHEDULED`, `ACTIVE`, `CLOSED_FOR_SUBMISSIONS`, `FINALIZING`, `FINALIZED` |
| submissions_closed_at | DATETIME | YES | | | When submissions closed |
| finalized_at | DATETIME | YES | | | When season was finalized |

**Constraints:**
- UK `uk_seasons_season_number` on `season_number`
- IDX `idx_seasons_status` on `(status)`
- IDX `idx_seasons_dates` on `(starts_at, ends_at)`

---

### season_rank_configurations

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| season_id | BIGINT UNSIGNED | NO | FK → seasons.id | | Season |
| rank_id | BIGINT UNSIGNED | NO | FK → ranks.id | | Rank tier |
| minimum_rp | INT UNSIGNED | NO | | 0 | Minimum RP for this season |
| maximum_rp | INT UNSIGNED | YES | | NULL | Maximum RP (NULL = unlimited) |

**Constraints:**
- FK → `seasons(id)` ON DELETE CASCADE
- FK → `ranks(id)` ON DELETE CASCADE
- UK `uk_season_rank_configurations_season_rank` on `(season_id, rank_id)`

---

### user_season_stats (preserved permanently)

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| season_id | BIGINT UNSIGNED | NO | FK → seasons.id | | Season |
| confirmed_rp | INT UNSIGNED | NO | | 0 | Confirmed RP this season |
| completed_count | INT UNSIGNED | NO | | 0 | Valid completion count |
| current_rank_id | BIGINT UNSIGNED | YES | FK → ranks.id | | Current rank |
| last_rp_reached_at | DATETIME | YES | | | Timestamp when final RP total first reached |
| updated_at | DATETIME | NO | | | Last update timestamp |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- FK → `seasons(id)` ON DELETE NO ACTION
- FK → `ranks(id)` ON DELETE SET NULL
- UK `uk_user_season_stats_user_season` on `(user_id, season_id)`
- IDX `idx_user_season_stats_season_rp` on `(season_id, confirmed_rp DESC)`

---

### season_results (immutable, preserved permanently)

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| season_id | BIGINT UNSIGNED | NO | FK → seasons.id | | Season |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| final_rp | INT UNSIGNED | NO | | 0 | Final RP |
| final_rank_id | BIGINT UNSIGNED | YES | FK → ranks.id | | Final rank |
| final_position | INT UNSIGNED | NO | | 0 | Final leaderboard position |
| finalized_at | DATETIME | NO | | | Finalization timestamp |

**Constraints:**
- FK → `seasons(id)` ON DELETE NO ACTION
- FK → `users(id)` ON DELETE NO ACTION
- FK → `ranks(id)` ON DELETE SET NULL
- UK `uk_season_results_season_user` on `(season_id, user_id)`
- UK `uk_season_results_season_position` on `(season_id, final_position)`
- IDX `idx_season_results_season_rp` on `(season_id, final_rp DESC)`

---

## 9. Progression Domain

### daily_streaks

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| activity_date | DATE | NO | | | Local activity date |
| target_weight | DECIMAL(5,2) | NO | | 0.00 | Daily target weight |
| completion_percentage | DECIMAL(5,2) | NO | | 0.00 | Completion percentage |
| eligible | TINYINT(1) | NO | | 0 | Whether day is streak-eligible |
| bonus_awarded | TINYINT(1) | NO | | 0 | Whether streak bonus was awarded |
| current_streak | INT UNSIGNED | NO | | 0 | Current streak count |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- UK `uk_daily_streaks_user_date` on `(user_id, activity_date)`
- IDX `idx_daily_streaks_eligible` on `(user_id, eligible)`

---

### achievements

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| achievement_code | VARCHAR(50) | NO | UNIQUE | | Code like `FIRST_STEP`, `CONSISTENCY_HERO` |
| name | VARCHAR(100) | NO | | | Achievement name (Indonesian) |
| description | TEXT | YES | | | Achievement description |
| criteria_json | JSON | NO | | | Configurable achievement criteria |
| icon_path | VARCHAR(255) | YES | | | Path to achievement icon |

---

### user_achievements (database-enforced achievement uniqueness)

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| achievement_id | BIGINT UNSIGNED | NO | FK → achievements.id | | Achievement |
| achievement_scope | ENUM('LIFETIME', 'SEASON') | NO | | 'LIFETIME' | Scope of achievement |
| season_id | BIGINT UNSIGNED | YES | FK → seasons.id | | Season (NULL for LIFETIME scope) |
| **season_coalesce** | **INT UNSIGNED** | **NO** | **VIRTUAL** | | **COALESCE(season_id, 0) — database-enforced uniqueness** |
| awarded_at | DATETIME | NO | | | Award timestamp |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- FK → `achievements(id)` ON DELETE NO ACTION
- FK → `seasons(id)` ON DELETE NO ACTION
- **UK `uk_ua_user_achievement_scope_season` on `(user_id, achievement_id, achievement_scope, season_coalesce)`**

> `season_coalesce` is a MySQL VIRTUAL generated column: `COALESCE(season_id, 0)`. Being NOT NULL, it eliminates MySQL's NULL-uniqueness ambiguity.
> - For `achievement_scope = 'LIFETIME'`: `season_id` is NULL → `season_coalesce = 0` → unique on `(user_id, achievement_id, 'LIFETIME', 0)`. Only one LIFETIME award per user per achievement.
> - For `achievement_scope = 'SEASON'`: `season_id` is NOT NULL → `season_coalesce = season_id` → unique on `(user_id, achievement_id, 'SEASON', {season_id})`. Only one SEASON award per user per achievement per season.

---

## 10. System Domain

### notifications

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| user_id | INT UNSIGNED | NO | FK → users.id | | User |
| notification_type | VARCHAR(50) | NO | | | Type of notification |
| title | VARCHAR(255) | NO | | | Notification title (Indonesian) |
| message | TEXT | YES | | | Notification message (Indonesian) |
| read_at | DATETIME | YES | | | When marked as read |
| created_at | DATETIME | NO | | | Creation timestamp |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- IDX `idx_notifications_user_unread` on `(user_id, read_at)`
- IDX `idx_notifications_created` on `(created_at)`

---

### admin_audit_logs (preserved permanently)

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| administrator_id | INT UNSIGNED | NO | FK → users.id | | Administrator |
| action | VARCHAR(100) | NO | | | Action performed |
| target_type | VARCHAR(50) | YES | | | Type of target record |
| target_id | BIGINT UNSIGNED | YES | | | ID of target record |
| previous_values | JSON | YES | | | Values before change |
| new_values | JSON | YES | | | Values after change |
| reason | VARCHAR(255) | YES | | | Reason for action |
| created_at | DATETIME | NO | | | Action timestamp |

**Constraints:**
- FK → `users(id)` ON DELETE NO ACTION
- IDX `idx_admin_audit_logs_admin` on `(administrator_id)`
- IDX `idx_admin_audit_logs_created` on `(created_at)`

---

### system_settings

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| setting_key | VARCHAR(100) | NO | UNIQUE | | Setting key |
| setting_value | TEXT | YES | | | Setting value |
| updated_at | DATETIME | NO | | | Last update timestamp |

---

### scheduled_job_runs

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK | AI | Primary key |
| job_name | VARCHAR(100) | NO | | | Job name |
| execution_key | VARCHAR(100) | NO | UNIQUE | | Idempotency key for this run |
| status | VARCHAR(20) | NO | | 'RUNNING' | `RUNNING`, `COMPLETED`, `FAILED` |
| started_at | DATETIME | NO | | | Start timestamp |
| finished_at | DATETIME | YES | | | Finish timestamp |
| error_message | TEXT | YES | | | Error message if failed |

**Constraints:**
- UK `uk_scheduled_job_runs_execution_key` on `execution_key`
- IDX `idx_scheduled_job_runs_status` on `(status)`
- IDX `idx_scheduled_job_runs_job_name` on `(job_name)`

---

## 11. Tables Removed from Previous Design

| Table | Reason |
|-------|--------|
| `daily_point_totals` | Replaced by `daily_xp_totals` and `daily_rp_totals` — XP and RP caps use different date dimensions and must be enforced independently |

---

## 12. Index Summary

| Table | Index | Purpose |
|-------|-------|---------|
| `daily_activity_instances` | `(user_id, activity_date)` | Daily checklist queries |
| `daily_activity_instances` | `(competition_date)` | Season/leaderboard queries |
| `daily_activity_instances` | `(verification_status)` | Review queue queries |
| `daily_activity_instances` | `(reward_status)` | Reward processing queries |
| `daily_activity_instances` | `(occurrence_identity)` | UNIQUE — occurrence deduplication |
| `daily_xp_totals` | `(user_id, activity_date)` | UNIQUE — XP cap lookup |
| `daily_rp_totals` | `(user_id, competition_date, season_id)` | UNIQUE — RP cap lookup |
| `daily_reward_budgets` | `(user_id, competition_date, season_id, reward_group_id)` | UNIQUE — budget lookup |
| `point_transactions` | `(idempotency_key)` | UNIQUE — idempotency enforcement |
| `point_transactions` | `(user_id)` | User transaction history |
| `point_transactions` | `(season_id)` | Season transaction history |
| `user_season_stats` | `(season_id, confirmed_rp DESC)` | Leaderboard queries |
| `season_results` | `(season_id, final_rp DESC)` | Final leaderboard |
| `activity_evidence` | `(file_hash)` | Duplicate evidence detection |
| `activity_evidence` | `(expires_at)` | Retention cleanup |
| `activity_verification_logs` | `(risk_score)` | Risk-based filtering |
| `anti_cheat_flags` | `(review_status)` | Review queue |
| `notifications` | `(user_id, read_at)` | Unread count |
| `admin_audit_logs` | `(created_at)` | Audit timeline |
| `user_achievements` | `(user_id, achievement_id, achievement_scope, season_coalesce)` | UNIQUE — database-enforced achievement dedup |

---

## 13. Transaction Boundaries and Lock Ordering

All reward-related transactions must acquire locks in this **exact, consistent order** to prevent deadlocks:

| Lock Order | Table | Condition | Purpose |
|------------|-------|-----------|---------|
| 1 | `daily_rp_totals` | `user_id = ? AND competition_date = ? AND season_id = ?` | RP daily cap |
| 2 | `daily_reward_budgets` | `user_id = ? AND competition_date = ? AND season_id = ? AND reward_group_id = ?` | Reward group RP budget |
| 3 | `daily_xp_totals` | `user_id = ? AND activity_date = ?` | XP daily cap |

This ordering applies to **all** reward operations:
- Standard activity completion
- Administrator approval of pending rewards
- Point corrections (compensating transactions)
- Streak bonus awards

### Row Initialization (Race Condition Prevention)

Use `INSERT ... ON DUPLICATE KEY UPDATE` for `daily_reward_budgets`, `daily_xp_totals`, `daily_rp_totals` to atomically initialize or fetch existing rows. Never SELECT-then-INSERT.

### Deadlock Handling

- Catch MySQL deadlock error (1213)
- Retry transaction up to 3 times with exponential backoff (50ms, 100ms, 200ms)
- Log all deadlock occurrences for monitoring
- Lock wait timeout: 5 seconds (innodb_lock_wait_timeout)

### Transaction Boundaries

| Operation | Tables Involved | Locking Strategy |
|-----------|-----------------|------------------|
| Activity completion + reward | `daily_rp_totals`, `daily_reward_budgets`, `daily_xp_totals`, `point_transactions`, `daily_activity_instances`, `user_lifetime_stats`, `user_season_stats` | Lock in order: rp_totals → reward_budgets → xp_totals → insert point_transactions → update aggregates |
| Timer start/finish | `timer_sessions`, `daily_activity_instances` | Row lock on activity instance; overlap check within transaction |
| Evidence upload | `activity_evidence`, `activity_verification_logs`, `anti_cheat_flags` | Insert only |
| Administrator approval | `daily_rp_totals`, `daily_reward_budgets`, `daily_xp_totals`, `point_transactions`, `activity_verification_logs`, `daily_activity_instances` | Lock using original season_id; revalidate remaining budget; same lock order |
| Streak reconciliation | `daily_streaks`, `point_transactions`, `user_lifetime_stats` | Transaction per user per day |
| Season finalization | `seasons`, `user_season_stats`, `season_results`, `scheduled_job_runs` | Lock season row; idempotent execution_key |
| Point correction | `point_transactions` (compensating), `user_lifetime_stats`, `user_season_stats`, `admin_audit_logs` | Compensating transaction; preserve original; same lock order for aggregates |

---

## 14. Migration Strategy

- All schema changes via CodeIgniter Migrations
- Migrations must be reversible where practical
- Seeders for initial data: activity categories, reward groups, ranks, default activities, verification policies, default achievements, first season configuration
- **Shield tables created by Shield's official migrations only** — do not include Shield tables in custom migrations
- Version tracking via CodeIgniter's migration tracking table
- No destructive migrations without explicit approval
- Custom migrations reference Shield tables via FK with ON DELETE NO ACTION only
- **Shield user table is `users`** — all custom FKs reference `users(id)`

---

## 15. Unresolved Database Decisions

| # | Decision | Options | Impact |
|---|----------|---------|--------|
| DB1 | PHP 8.5.12 / MySQL 9.7 availability | Confirm vs fallback versions | Affects provisioning |
| DB2 | Shield `users.id` type | INT UNSIGNED vs BIGINT UNSIGNED | Affects custom table `user_id` column types — must verify before migration |
| DB3 | Timer session storage | MySQL table vs Redis | Affects `timer_sessions` table usage |
| DB4 | Evidence storage backend | Local filesystem vs object storage | Affects `private_storage_key` semantics |
| DB5 | Caching layer | File cache vs Redis vs none | Affects leaderboard performance |
| DB6 | Partitioning | None vs date-based partitioning on large tables | Affects long-term scalability |

**Blocking item:** DB2 must be resolved before any custom migration references `users`.

---

## 16. Next Step

Wait for implementation approval. No migrations or database changes may begin until DATABASE.md, ARCHITECTURE.md, DEVELOPMENT_PLAN.md, TASKS.md, TESTING.md, AGENTS.md, DESIGN_REVIEW.md, and FINAL_DESIGN_VALIDATION.md are reviewed and approved.
