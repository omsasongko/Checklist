# LifeQuest — Agent Instructions
**Product:** LifeQuest — Daily Life RPG  
**Document Version:** 2.1.0 (aligned with PRD V2.1.0, Sections 30-31, post Design Review corrections)  
**Status:** For Review — Planning Only  
**Primary Documentation Language:** English  
**Application Interface Language:** Indonesian (Bahasa Indonesia)  
**Rule:** No source code, no migrations, no dependency installation until all documentation is approved.

---

## 1. Project Identity

- **Product:** LifeQuest — Daily Life RPG (Gamified Daily Activity & Habit Tracking)
- **Not a traditional RPG quest system** — do not reinterpret as quests, combat, or inventory
- **Project Root:** `D:\Project OpenCODE\Checklist`
- **Target Database:** `lifequest_db`
- **Technology Stack:** PHP 8.5.12 · CodeIgniter 4.7.0 · MySQL 9.7 LTS · Bootstrap 5.3.8 · CodeIgniter Shield
- **Architecture:** MVC with Service Layer

---

## 2. Mandatory Development Rules (PRD Section 30)

All 35 rules are binding. Key rules summarized:

1. Read the complete PRD before implementation.
2. Treat this document as the product requirements baseline.
3. **Do not reinterpret the application as a traditional RPG quest system.**
4. Follow the specified technology stack.
5. Do not change frameworks without approval.
6. Do not start all modules simultaneously — follow phases.
7. Create architectural documentation first.
8. Create database documentation first.
9. Create a development task breakdown.
10. Use CodeIgniter MVC conventions.
11. Separate controllers, services, and models.
12. Keep business logic on the server.
13. **Never trust client-supplied reward amounts.**
14. Use transactional reward processing.
15. Implement idempotent reward operations.
16. Enforce reward group budgets.
17. Implement repeatable activities safely.
18. Use automated verification for routine activities.
19. Avoid mandatory manual verification for every activity.
20. Treat risk scoring as a decision-support mechanism.
21. Protect private evidence.
22. Implement auditable point corrections.
23. Preserve historical season results.
24. Test season boundary conditions.
25. Test simultaneous reward requests.
26. Keep database migrations reversible where practical.
27. Maintain application documentation.
28. Maintain task completion status in TASKS.md.
29. Record relevant test results.
30. Do not claim success without verification.
31. Do not create production secrets in source code.
32. Use English for technical documentation.
33. Use Indonesian for default user-facing interface text.
34. Request approval for destructive operations.
35. Report implementation conflicts rather than silently changing requirements.

**Additional Design Review rules (from DESIGN_REVIEW.md):**
- **DR-1:** Verify Shield `users.id` PK type before creating any custom FOREIGN KEY to `users`.
- **DR-2:** Use Shield's official migrations only; do not create Shield auth tables in custom migrations.
- **DR-3:** No ON DELETE CASCADE from custom tables to `users` — use ON DELETE NO ACTION.
- **DR-4:** User deactivation via soft delete (`is_active`/`deleted_at`), never hard delete.
- **DR-5:** Daily XP and RP totals are separate tables (`daily_xp_totals`, `daily_rp_totals`) — never combined.
- **DR-6:** `daily_activity_instances.occurrence_identity` must be NOT NULL and server-generated.
- **DR-7:** Reward transaction lock order: `daily_rp_totals` → `daily_reward_budgets` → `daily_xp_totals`.
- **DR-8:** Use `INSERT ... ON DUPLICATE KEY` for aggregate row initialization (no SELECT-then-INSERT race).
- **DR-9:** Implement deadlock retry (3 attempts, exponential backoff) for reward transactions.
- **DR-10:** Evidence stored outside public web root; controller-mediated download with authorization on every request.
- **DR-11:** `daily_reward_budgets` includes `season_id` for season-scoped budget enforcement.
- **DR-12:** `user_achievements` uses `achievement_scope` (LIFETIME/SEASON) for season-aware uniqueness.
- **DR-13:** Timer ownership validation, overlap detection, idempotent finish, CANCEL stale sessions.
- **DR-14:** Point corrections use compensating transactions; originals are never modified or deleted.

---

## 3. Coding Conventions

### Language
| Layer | Language |
|-------|----------|
| Technical documentation | English |
| Code comments | English |
| Class names, methods, variables | English |
| Database tables, columns | English |
| User-facing UI text | Indonesian |
| Commit messages | English |

### PHP (PSR-12 compatible, CodeIgniter 4 style)
- Classes: `StudlyCase` (e.g., `ActivityService`, `UserProfileModel`)
- Methods: `camelCase` (e.g., `calculateXP()`, `verifyCompletion()`)
- Properties: `camelCase` (e.g., `$userId`, `$rewardGroupId`)
- Constants: `UPPER_SNAKE_CASE` (e.g., `MAX_DAILY_XP`, `VERIFICATION_SELF_CHECK`)
- Files: Match class name (e.g., `ActivityService.php`)
- Namespaces: `App\Services`, `App\Models`, `App\Controllers`, `App\Entities`

### Database
- Tables: `snake_case`, plural (e.g., `user_profiles`, `daily_activity_instances`, `daily_xp_totals`)
- Columns: `snake_case` (e.g., `activity_date`, `competition_date`, `occurrence_identity`, `idempotency_key`)
- Primary keys: `id` (BIGINT UNSIGNED, AUTO_INCREMENT)
- Foreign keys: `{table}_id` (e.g., `user_id`, `activity_id`, `season_id`)
- Timestamps: `created_at`, `updated_at` (DATETIME)
- Soft deletes: `deleted_at` (DATETIME, nullable)
- Soft deactivation: `is_active` (TINYINT(1), default 1)
- Charset: `utf8mb4`, Collation: `utf8mb4_unicode_ci`
- Engine: InnoDB
- **No ON DELETE CASCADE to `users` — use ON DELETE NO ACTION**

### Indonesian UI Terms (for views)
| English | Indonesian |
|---------|------------|
| Dashboard | Beranda |
| Daily Checklist | Checklist Harian |
| Achievements | Pencapaian |
| Leaderboard | Peringkat |
| Season | Musim Kompetisi |
| Verification Pending | Menunggu Verifikasi |
| Activity Completed | Aktivitas Selesai |
| Login | Masuk |
| Registration | Daftar |
| Forgot Password | Lupa Kata Sandi |
| Profile | Profil |
| Settings | Pengaturan |

Brand names, rank names, XP, and RP remain in English.

---

## 4. Project Structure (Planned)

```
lifequest/
├── app/
│   ├── Config/          # App, Database, Routes, Filters, Shield config
│   ├── Controllers/     # Thin request handlers (HTML + JSON API)
│   │   └── Evidence.php # Controller-mediated evidence download with auth check
│   ├── Services/        # Business logic (19 services per PRD Section 20.4)
│   ├── Models/          # CodeIgniter Models (30+ domain tables)
│   │   ├── DailyXpTotalModel.php
│   │   ├── DailyRpTotalModel.php
│   │   ├── DailyRewardBudgetModel.php
│   │   └── ... (no DailyPointTotalModel — removed)
│   ├── Entities/        # Domain entities where appropriate
│   ├── Views/           # Bootstrap 5.3.8 views (Indonesian UI)
│   ├── Database/
│   │   ├── Migrations/  # Versioned schema (custom tables only; Shield via Shield migrations)
│   │   └── Seeds/       # Initial data
│   ├── Filters/         # Auth, Role, CSRF filters
│   └── Libraries/       # Optional helper libraries
├── public/
│   ├── index.php
│   ├── .htaccess
│   ├── assets/          # CSS, JS, images
│   └── (NO uploads/ directory — evidence stored outside web root)
├── writable/
│   ├── cache/
│   ├── logs/
│   └── storage/
│       └── evidence/    # Private evidence storage (OUTSIDE web root)
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── _support/
├── composer.json
├── composer.lock
├── .env
├── spark
└── README.md
```

---

## 5. Development Commands (To Be Confirmed in Phase 1)

These commands are planned and will be verified once the project is initialized:

| Purpose | Command (Planned) |
|---------|-------------------|
| Initialize CI4 project | `composer create-project codeigniter4/appstarter lifequest` |
| Install Shield | `composer require codeigniter4/shield` |
| Run Shield migrations | `php spark migrate` (after Shield install) |
| Run custom migrations | `php spark migrate` (custom tables only) |
| Rollback migration | `php spark migrate:rollback` |
| Run seeders | `php spark db:seed SeedClassName` |
| Run all tests | `php spark test` |
| Run unit tests | `php spark test --group unit` |
| Run integration tests | `php spark test --group integration` |
| Clear cache | `php spark cache:clear` |
| Route list | `php spark routes` |

**Do not run any of these commands until implementation is approved.**

---

## 6. Service Layer Responsibilities

Each service owns a bounded business domain. Controllers call services; services call models.

| Service | File | Core Responsibility |
|---------|------|---------------------|
| ActivityService | `app/Services/ActivityService.php` | Activity CRUD, catalog, activation/deactivation |
| ActivityScheduleService | `app/Services/ActivityScheduleService.php` | User schedules, recurrence, timezone support |
| ChecklistService | `app/Services/ChecklistService.php` | Daily activity instance generation (with `occurrence_identity`) |
| TimerService | `app/Services/TimerService.php` | Server-recorded timer start/finish, ownership validation, overlap detection |
| EvidenceService | `app/Services/EvidenceService.php` | Photo upload, validation, private storage (outside web root), controller-mediated retrieval |
| VerificationService | `app/Services/VerificationService.php` | Verification state machine, method routing |
| RiskScoringService | `app/Services/RiskScoringService.php` | Risk score 0-100, thresholds, reason codes |
| AntiCheatService | `app/Services/AntiCheatService.php` | Anti-cheat flags, duplicate detection |
| PointsService | `app/Services/PointsService.php` | XP and RP calculation, daily caps |
| RewardBudgetService | `app/Services/RewardBudgetService.php` | Reward group budget tracking, partial rewards, budget locking |
| RankingService | `app/Services/RankingService.php` | Competitive rank determination, promotion |
| LifetimeLevelService | `app/Services/LifetimeLevelService.php` | XP level formula: 100 × (L-1) × L / 2 |
| LeaderboardService | `app/Services/LeaderboardService.php` | Leaderboard queries, tie-breaking, pagination |
| SeasonService | `app/Services/SeasonService.php` | Season lifecycle, transitions, idempotent finalization |
| StreakService | `app/Services/StreakService.php` | Streak eligibility, daily reconciliation |
| AchievementService | `app/Services/AchievementService.php` | Achievement criteria, scoped awards (LIFETIME/SEASON) |
| NotificationService | `app/Services/NotificationService.php` | In-app notifications (12 types) |
| AdminReviewService | `app/Services/AdminReviewService.php` | Review queue, appeals, decisions |
| ReportingService | `app/Services/ReportingService.php` | Statistics, admin dashboard aggregates |

### Critical Service Constraints
- **Lock ordering:** `daily_rp_totals` → `daily_reward_budgets` → `daily_xp_totals` (prevents deadlocks)
- **Deadlock retry:** Up to 3 attempts with exponential backoff (50ms, 100ms, 200ms)
- **Row initialization:** `INSERT ... ON DUPLICATE KEY UPDATE` (no SELECT-then-INSERT race)
- **Idempotency:** Server-controlled `idempotency_key` on `point_transactions`
- **No client-trusted rewards:** All XP/RP calculated server-side
- **Evidence outside web root:** `EvidenceService` stores outside public directory; retrieval via controller with auth check

---

## 7. Security Rules

### Authentication & Authorization
- Use CodeIgniter Shield for all authentication
- **Verify Shield `users.id` PK type before creating custom FKs** (DR-1)
- Use Shield's official migrations only (DR-2)
- Enforce role-based permissions on every endpoint
- Validate ownership of all user resources (no IDOR)
- Apply login rate limiting
- Use secure session configuration
- HTTPS required in production

### Input & Output
- Validate all inputs server-side (never trust client data)
- Escape all untrusted output (XSS prevention)
- Use parameterized queries (CodeIgniter Query Builder) — no raw SQL concatenation
- Enable CSRF protection on state-changing requests
- Reject unauthorized resource changes

### File Uploads (Evidence)
- **Store outside public web root** — `writable/storage/evidence/` (DR-10)
- Validate MIME type and actual file contents (finfo)
- Enforce 5 MB maximum file size
- Generate randomized filenames
- Remove unnecessary metadata
- Calculate file hashes (SHA-256)
- Restrict access by authorization — every download through controller with ownership check
- Apply evidence retention rules (30 days standard)

### Secrets
- Use environment variables (`.env`) for all secrets
- Never hardcode credentials in source code
- Never commit production secrets to Git
- Restrict database permissions
- Disable debug mode in production

### Reward Security
- Calculate all points on the server — never trust client-supplied amounts
- Enforce transaction idempotency via unique keys
- Prevent duplicate rewards
- Prevent client-side reward manipulation
- Protect season transitions
- Audit all point corrections (compensating transactions, originals preserved)

---

## 8. Reward Processing Rules (Critical)

### Atomic Transactions
All reward operations must use database transactions that atomically update:
1. Activity completion status
2. Verification outcome
3. Point ledger (`point_transactions`)
4. Daily RP totals (`daily_rp_totals`)
5. Daily reward budgets (`daily_reward_budgets`)
6. Daily XP totals (`daily_xp_totals`)
7. User lifetime statistics (`user_lifetime_stats`)
8. User season statistics (`user_season_stats`)

### Lock Ordering (DR-7 — Prevents Deadlocks)
1. `daily_rp_totals` — `SELECT ... FOR UPDATE WHERE user_id = ? AND competition_date = ? AND season_id = ?`
2. `daily_reward_budgets` — `SELECT ... FOR UPDATE WHERE user_id = ? AND competition_date = ? AND season_id = ? AND reward_group_id = ?`
3. `daily_xp_totals` — `SELECT ... FOR UPDATE WHERE user_id = ? AND activity_date = ?`
4. Insert into `point_transactions` with idempotency key
5. Update aggregate rows
6. Commit

### Row Initialization (DR-8)
- Use `INSERT ... ON DUPLICATE KEY UPDATE` for `daily_reward_budgets`, `daily_xp_totals`, `daily_rp_totals`
- Never SELECT-then-INSERT (race condition on row creation)

### Deadlock Handling (DR-9)
- Catch MySQL deadlock error (1213)
- Retry transaction up to 3 times with exponential backoff (50ms, 100ms, 200ms)
- Log all deadlock occurrences for monitoring

### Idempotency (DR-14 context)
- Every reward event uses a server-controlled unique idempotency key
- Repeated HTTP requests return the existing result without creating duplicate rewards
- Idempotency enforced through database unique constraint on `point_transactions.idempotency_key`

### Daily Caps
- XP: max 150 per user per local `activity_date` (via `daily_xp_totals`)
- RP: max 140 per user per `competition_date` per `season_id` (via `daily_rp_totals`)
- RP reward group budgets: per user per `competition_date` per `season_id` per `reward_group_id`

### Partial Rewards
- If remaining budget < activity value, award remaining amount
- Record nominal reward and actual reward separately
- Do not carry unused RP to another day

### Point Corrections
- Use compensating transactions (new rows with negative deltas)
- Never update or delete existing point transactions
- Preserve original transaction history
- Record administrator and reason for each correction
- Recalculate affected rank if season is not finalized
- Finalized season standings must not be silently modified

---

## 9. Verification Rules

### Methods
| Method | When Used | Auto-Validated? |
|--------|-----------|-----------------|
| SELF_CHECK | Ordinary private routines | Yes (low risk) |
| TIMER | Duration-based activities | Yes (if duration valid; ownership validated; overlap checked) |
| PHOTO_PROOF | Eligible activities requiring evidence | Depends on risk score |
| ADMIN_APPROVAL | Special activities, high-risk, appeals | Yes (manual) |

### Timer Integrity (DR-13)
- `timer_sessions.user_id` must match `daily_activity_instances.user_id`
- Overlap detection: reject new timer start if overlapping FINISHED timer exists for same user/activity/date
- Idempotent finish: only one FINISHED timer per activity instance
- CANCEL stale RUNNING sessions older than maximum eligible duration

### Verification States
`NOT_REQUIRED` → `PENDING` → `AUTO_VALIDATED` / `MANUALLY_APPROVED` / `REJECTED`

### Reward States
`NOT_ELIGIBLE` → `PENDING` → `CONFIRMED` / `REJECTED` / `REVERSED`

Only confirmed RP contributes to seasonal competitive standings.

### Risk Scoring
| Score | Classification | Action |
|-------|----------------|--------|
| 0–29 | Low | Automatic Validation |
| 30–69 | Medium | Secondary Verification |
| 70–100 | High | Administrator Review |

Risk scoring is decision-support only — never automatic fraud determination.

---

## 10. User Achievement Scoping (DR-12)

- `user_achievements.achievement_scope` = `LIFETIME` or `SEASON`
- For LIFETIME: `season_id` is NULL; `season_coalesce` generated column = 0
- For SEASON: `season_id` is NOT NULL; `season_coalesce` = `season_id`
- `season_coalesce INT UNSIGNED NOT NULL GENERATED ALWAYS AS (COALESCE(season_id, 0)) VIRTUAL`
- UNIQUE on `(user_id, achievement_id, achievement_scope, season_coalesce)` — database-enforced
- No reliance on application-level checks alone (MySQL NULL uniqueness avoided via NOT NULL generated column)

---

## 11. Season Rules (Critical)

### Duration
- Exactly 60 competition calendar days
- Timezone: Asia/Jakarta
- Interval: `starts_at <= event_timestamp < ends_at`
- Timestamps stored in UTC

### States
`SCHEDULED` → `ACTIVE` → `CLOSED_FOR_SUBMISSIONS` → `FINALIZING` → `FINALIZED`

### Constraints
- At most one season accepts new competitive submissions at any moment
- A season pending finalization must not prevent the next season from becoming active
- First season start configured by Super Administrator
- Subsequent seasons begin exactly 60 days after previous start, 00:00 Asia/Jakarta
- Maximum 24-hour review grace period after season cutoff
- Season finalization idempotent via `scheduled_job_runs.execution_key`

### Reset Policy
- Season RP resets logically through new season statistics (zero baseline)
- Do NOT delete historical RP transactions
- Do NOT reset lifetime XP
- Do NOT delete badges/achievements
- Do NOT delete previous season results

---

## 11. UI / UX Rules

### Theme (Dark Gaming)
- Dark navy backgrounds
- Charcoal surfaces
- Electric blue primary accents
- Purple secondary accents
- Gold rank highlights
- Rounded cards
- High-contrast typography
- Smooth, restrained animations
- Responsive components
- Clear progress indicators

### Language
- All user-facing text: Indonesian
- Brand names, rank names, XP, RP: English
- Future localization mechanism must be supported (not required for MVP)

### Critical UI Rules
- Do not permanently update displayed points before server confirmation
- Mobile-first responsive design
- Touch-friendly controls
- Accessible dialogs and keyboard interaction
- Readable contrast ratios
- **No `public/uploads/` directory** — evidence not directly accessible via URL

---

## 12. Database Rules

- Use CodeIgniter Migrations for all schema changes
- **Shield tables created by Shield's official migrations only** — custom migrations do not create Shield tables
- Migrations reversible where practical
- Use CodeIgniter Seeders for initial data
- Use InnoDB storage engine
- Use utf8mb4 / utf8mb4_unicode_ci
- All timestamps stored in UTC
- Use parameterized queries (Query Builder) — never raw SQL string concatenation
- Never store plaintext passwords (Shield handles this)
- **No ON DELETE CASCADE to `users`** — use ON DELETE NO ACTION (DR-3)
- **User soft delete via `is_active`/`deleted_at`** — never hard delete (DR-4)
- Use transactions for all reward operations
- Use consistent lock ordering for concurrent budget updates (DR-7)
- Preserve point ledger history (immutable transactions)
- Keep season results immutable after finalization
- Index foreign keys and frequently queried columns
- `daily_activity_instances.occurrence_identity` is NOT NULL and server-generated (DR-6)
- `daily_reward_budgets` includes `season_id` (DR-11)

---

## 13. API Routes (Planned)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/dashboard` | Yes | Dashboard page |
| GET | `/api/me/summary` | Yes | User summary |
| GET | `/api/me/statistics` | Yes | User statistics |
| GET | `/api/activities` | Yes | List activities |
| GET | `/api/activities/today` | Yes | Today's activities |
| GET | `/api/activities/history` | Yes | Activity history |
| POST | `/api/activities/{id}/complete` | Yes | Complete activity |
| POST | `/api/activities/{id}/timer/start` | Yes | Start timer |
| POST | `/api/activities/{id}/timer/finish` | Yes | Finish timer |
| POST | `/api/activities/{id}/evidence` | Yes | Upload evidence (stored outside web root) |
| GET | `/api/evidence/{id}/status` | Yes | Evidence status |
| GET | `/api/evidence/{id}/download` | Yes | Controller-mediated download (authorization required) |
| GET | `/api/ranks` | Yes | Rank tiers |
| GET | `/api/me/rank` | Yes | User's current rank |
| GET | `/api/leaderboard` | Yes | Leaderboard |
| GET | `/api/leaderboard/me` | Yes | User's leaderboard position |
| GET | `/api/seasons/current` | Yes | Current season |
| GET | `/api/seasons/history` | Yes | Season history |
| GET | `/api/seasons/{id}/results` | Yes | Season results |
| GET | `/api/me/achievements` | Yes | User achievements |
| POST | `/api/verifications/{id}/appeal` | Yes | Submit appeal |
| GET | `/api/me/appeals` | Yes | User's appeals |

Admin endpoints (protected): activities, categories, reward groups, verification policies, season configurations, rank configurations, review queue, appeals, users, reports, audit logs.

All non-public endpoints require authentication and appropriate authorization (Shield groups/permissions).

---

## 14. Definition of Done (Per Feature)

A feature is complete only when all 15 criteria are met (PRD Section 32):
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

## 15. Documentation Files

| File | Purpose | Status |
|------|---------|--------|
| PRD.md | Approved product requirements | Complete (V2.1.0) |
| DESIGN_REVIEW.md | Critical architecture corrections | Created |
| ARCHITECTURE.md | System architecture, services, controllers, routes | Created |
| DATABASE.md | Entity relationships, table specs, keys, indexes | Created |
| DEVELOPMENT_PLAN.md | Phases, milestones, acceptance criteria mapping | Created |
| TASKS.md | Granular tasks, dependencies, status, acceptance criteria | Created |
| TESTING.md | Unit, integration, concurrency, security, performance tests | Created |
| AGENTS.md | Coding conventions, commands, structure, rules | This file |

All documentation must remain consistent with the approved PRD and DESIGN_REVIEW.md. Report conflicts rather than silently changing requirements.

---

## 16. Next Step

**Wait for implementation approval.** No source code, migrations, dependency installation, or test code may begin until DEVELOPMENT_PLAN.md, ARCHITECTURE.md, DATABASE.md, TASKS.md, TESTING.md, AGENTS.md, and DESIGN_REVIEW.md are reviewed and approved.
