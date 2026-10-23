# LifeQuest — Application Architecture
**Product:** LifeQuest — Daily Life RPG  
**Document Version:** 2.1.0 (aligned with PRD V2.1.0, Sections 20-21)  
**Status:** For Review — Planning Only (post Design Review corrections)  
**Primary Documentation Language:** English  
**Application Interface Language:** Indonesian (Bahasa Indonesia)  
**Rule:** No source code, no migrations, no dependency installation until approved.

---

## 1. Architectural Style

**CodeIgniter 4.7.0 MVC with a dedicated Service Layer.**

The application follows a layered architecture in which controllers are thin request handlers, services contain business logic, models/entities handle data access, and views handle presentation. Business rules must never be implemented exclusively in JavaScript; all reward calculation, verification, and authorization decisions are made server-side.

---

## 2. Layer Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│  Presentation Layer                                                 │
│  • Bootstrap 5.3.8 views (Indonesian UI)                            │
│  • Dark gaming theme                                                │
│  • Server-rendered pages + JSON API endpoints                       │
│  • Vanilla JavaScript, Fetch API                                    │
│  • Evidence downloads via controller (auth-checked)                │
└─────────────────────────────────────────────────────────────────────┘
                           │ HTTP / JSON
┌─────────────────────────────────────────────────────────────────────┐
│  Controller Layer                                                   │
│  • Request handling, input validation                               │
│  • Authentication & authorization checks                            │
│  • Service invocation, response formatting                          │
│  • Thin — no business logic                                         │
└─────────────────────────────────────────────────────────────────────┘
                           │
┌─────────────────────────────────────────────────────────────────────┐
│  Service Layer (Business Logic)                                     │
│  • ActivityService, ActivityScheduleService, ChecklistService       │
│  • TimerService, EvidenceService, VerificationService               │
│  • RiskScoringService, AntiCheatService                             │
│  • PointsService, RewardBudgetService                               │
│  • RankingService, LifetimeLevelService, LeaderboardService         │
│  • SeasonService, StreakService, AchievementService                 │
│  • NotificationService, AdminReviewService, ReportingService        │
│  • Transaction boundaries, idempotency, row-level locking           │
│  • Consistent lock ordering to prevent deadlocks                    │
└─────────────────────────────────────────────────────────────────────┘
                           │
┌─────────────────────────────────────────────────────────────────────┐
│  Data Access Layer                                                  │
│  • CodeIgniter Models + Query Builder                               │
│  • Entities where appropriate                                       │
│  • CodeIgniter Shield auth models via `users` table (official migrations only)        │
│  • Custom tables reference Shield `users` with ON DELETE NO ACTION    │
│  • Migrations, Seeders                                              │
└─────────────────────────────────────────────────────────────────────┘
                           │ SQL (InnoDB, transactions, row locks)
┌─────────────────────────────────────────────────────────────────────┐
│  MySQL 9.7 LTS — `lifequest_db`                                     │
│  • utf8mb4, InnoDB, foreign keys                                    │
│  • UTC timestamp storage                                            │
│  • Evidence stored OUTSIDE web root                                 │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Presentation Layer

### Responsibilities
- HTML rendering with Bootstrap 5.3.8 components
- Dark gaming theme (dark navy backgrounds, charcoal surfaces, electric blue primary, purple secondary, gold rank highlights)
- Client-side interactions via Vanilla JavaScript and Fetch API
- Form display and user feedback
- Charts (Chart.js) for weekly performance
- Responsive, mobile-first layouts
- **Evidence file downloads are NOT direct URLs** — served through controller with authorization check

### Constraints
- All user-facing text: Indonesian
- Business rules must not be implemented exclusively in JavaScript
- No permanent point updates before server confirmation
- No React, Vue, Angular, or other major frontend frameworks without approval
- No `public/uploads/` directory — evidence stored outside web root

### Main User Pages (PRD Section 22.3)
1. Landing Page (Beranda)
2. Login (Masuk)
3. Registration (Daftar)
4. Forgot Password (Lupa Kata Sandi)
5. User Onboarding (Pendahuluan Pengguna)
6. Dashboard (Beranda Pengguna)
7. Daily Checklist (Checklist Harian)
8. My Activities (Aktivitas Saya)
9. Activity Schedule (Jadwal Aktivitas)
10. Ranking (Peringkat)
11. Leaderboard (Peringkat Global)
12. Achievements (Pencapaian)
13. Season Overview (Ringkasan Musim)
14. Statistics (Statistik)
15. Verification History (Riwayat Verifikasi)
16. Appeals (Ajukan Banding)
17. User Profile (Profil Pengguna)
18. Settings (Pengaturan)

### Dashboard Components (PRD Section 22.4)
- User greeting, avatar, current date
- Current rank, rank emblem, rank progress
- Lifetime level, lifetime XP
- Current season RP, season countdown
- Daily progress, activity checklist
- Streak counter
- Pending verification summary
- Leaderboard preview
- Recent achievements
- Weekly performance chart

---

## 4. Controller Layer

Controllers are thin and handle:
- Request handling
- Input validation
- Authentication checks (Shield)
- Authorization checks (Shield groups/permissions)
- Service invocation
- Response formatting (HTML views or JSON)
- **Evidence download authorization** — controller verifies user owns evidence or has admin permission before serving file

Controllers must not contain business logic. All calculations, validations, and state changes are delegated to services.

---

## 5. Service Layer

The following 19 services are required by PRD Section 20.4. Each service owns a bounded business domain.

| Service | Responsibility |
|---------|----------------|
| `ActivityService` | Activity CRUD, catalog, activity type logic, activation/deactivation |
| `ActivityScheduleService` | User schedules, wake-up/sleep times, nonstandard shift support |
| `ChecklistService` | Daily activity instance generation, checklist state |
| `TimerService` | Server-recorded timer start/finish, elapsed duration, session overlap detection, ownership validation |
| `EvidenceService` | Photo evidence upload, validation, secure storage (outside web root), controller-mediated retrieval |
| `VerificationService` | Verification state machine, method routing (SELF_CHECK/TIMER/PHOTO_PROOF/ADMIN_APPROVAL) |
| `RiskScoringService` | Deterministic risk score 0-100, reason codes, threshold classification |
| `AntiCheatService` | Anti-cheat flag creation, duplicate detection, pattern analysis |
| `PointsService` | XP and RP calculation, daily caps, reward group budget enforcement |
| `RewardBudgetService` | Reward group daily budget tracking, partial rewards, budget locking |
| `RankingService` | Competitive rank determination, promotion/demotion events |
| `LifetimeLevelService` | Lifetime XP level calculation (formula: 100 × (L-1) × L / 2) |
| `LeaderboardService` | Leaderboard queries, tie-breaking, pagination, privacy opt-out |
| `SeasonService` | Season lifecycle, transitions, finalization, grace period |
| `StreakService` | Streak eligibility, daily reconciliation, streak bonuses |
| `AchievementService` | Achievement criteria evaluation, idempotent awards (lifetime + season-scoped) |
| `NotificationService` | In-app notifications, read/unread state, 12 notification types |
| `AdminReviewService` | Administrator review queue, appeals, decision recording |
| `ReportingService` | Statistics, admin dashboard aggregates, audit reports |

### Service Constraints
- Services may be refined during architecture review without changing approved business rules
- Services manage database transactions for atomic operations
- Services enforce idempotency via server-controlled keys
- Services never trust client-supplied reward amounts
- Services use row-level locking for concurrent budget updates
- **Services follow consistent lock ordering to prevent deadlocks** (see Section 10)
- **Services implement deadlock retry logic** (up to 3 retries with exponential backoff)

---

## 6. Data Access Layer

- CodeIgniter Models and Query Builder
- Entities where appropriate (domain objects)
- **CodeIgniter Shield authentication models via Shield's official migrations only** — custom migrations do not create Shield tables
- Custom tables referencing Shield users use `ON DELETE NO ACTION` (not CASCADE)
- Migrations for schema versioning
- Seeders for initial data
- Explicit transactional boundaries
- Parameterized queries (no raw SQL string concatenation)

---

## 7. Background Processing (PRD Section 20.6)

Background processing must support:
- Season transitions
- Season finalization
- Achievement reconciliation
- Streak reconciliation
- Evidence cleanup (30-day retention)
- Notification delivery
- Review monitoring
- Aggregate reconciliation
- Timer cleanup (CANCEL stale RUNNING sessions)

All scheduled operations must be:
- Idempotent
- Retry-safe
- Lock-protected (via `scheduled_job_runs.execution_key`)
- Logged
- Recoverable after interruption

A repeated scheduler execution must never duplicate season results or rewards.

---

## 8. Verification Architecture (PRD Section 8)

### Verification Methods
| Method | Description |
|--------|-------------|
| `SELF_CHECK` | User confirms completion; backend validates eligibility, limits, schedule, duplicates |
| `TIMER` | Server-recorded start/end times; minimum/maximum duration; overlap detection; ownership validation |
| `PHOTO_PROOF` | JPEG/PNG/WebP, max 5 MB, content validation, secure storage outside web root, hash calculation |
| `ADMIN_APPROVAL` | Manual approval for special/high-risk/dispute activities |

### Verification States
| State | Meaning |
|-------|---------|
| `NOT_REQUIRED` | No verification needed |
| `PENDING` | Awaiting processing/review |
| `AUTO_VALIDATED` | Automatically validated |
| `MANUALLY_APPROVED` | Approved by administrator |
| `REJECTED` | Rejected |

### Reward States
| State | Meaning |
|-------|---------|
| `NOT_ELIGIBLE` | Not eligible for rewards |
| `PENDING` | Reward held pending verification |
| `CONFIRMED` | Reward confirmed; contributes to standings |
| `REJECTED` | Reward rejected |
| `REVERSED` | Reward reversed via compensating transaction |

Only confirmed RP contributes to seasonal competitive standings.

### Verification Processing Steps
1. Validate authentication
2. Validate activity eligibility
3. Validate timing and occurrence
4. Validate completion limits
5. Validate applicable evidence
6. Evaluate risk signals
7. Determine verification outcome
8. Hold rewards requiring review
9. Award eligible rewards atomically after approval
10. Record all decisions and reason codes

---

## 9. Risk-Based Anti-Cheat Architecture (PRD Section 9)

### Risk Score Classification
| Risk Score | Classification | Action |
|------------|----------------|--------|
| 0–29 | Low | Automatic Validation |
| 30–69 | Medium | Secondary Verification |
| 70–100 | High | Administrator Review |

### Risk Indicators
- Multiple completions submitted unusually quickly
- Repeated duplicate requests
- Conflicting timer sessions
- Reused evidence hashes
- Unusual submission patterns
- Completion outside permitted schedules
- Repeated failed validation attempts
- Unexpected changes to activity identifiers

### Constraints
- Threshold values must be configurable
- Risk scoring is decision-support, not automatic fraud determination
- No AI-based fraud detection in MVP
- No automatic permanent bans based solely on risk score
- No public exposure of suspected violations

---

## 10. Reward Processing & Concurrency (PRD Section 10)

### Atomic Reward Processing
All reward operations use database transactions that atomically update:
- Activity completion
- Verification outcome
- Point ledger (`point_transactions`)
- Daily XP totals (`daily_xp_totals`)
- Daily RP totals (`daily_rp_totals`)
- Daily reward budgets (`daily_reward_budgets`)
- User lifetime statistics (`user_lifetime_stats`)
- User season statistics (`user_season_stats`)

### Idempotency
- Every reward event uses a server-controlled unique idempotency key
- Repeated HTTP requests return existing results without duplicate rewards
- Idempotency enforced through database unique constraint on `point_transactions.idempotency_key`

### Concurrent Request Prevention
- Database transactions
- Unique constraints
- Row-level locking (`SELECT ... FOR UPDATE`) on aggregate rows

### Lock Ordering (Consistent — Prevents Deadlocks)

All reward transactions acquire locks in this exact order:

1. `daily_rp_totals` — `SELECT ... FOR UPDATE WHERE user_id = ? AND competition_date = ? AND season_id = ?`
2. `daily_reward_budgets` — `SELECT ... FOR UPDATE WHERE user_id = ? AND competition_date = ? AND season_id = ? AND reward_group_id = ?`
3. `daily_xp_totals` — `SELECT ... FOR UPDATE WHERE user_id = ? AND activity_date = ?`
4. `INSERT INTO point_transactions` with idempotency key
5. Update aggregate rows
6. `COMMIT`

### Deadlock Handling
- Catch MySQL deadlock error (1213)
- Retry transaction up to 3 times with exponential backoff (50ms, 100ms, 200ms)
- Log all deadlock occurrences for monitoring

### Row Initialization (Race Condition Prevention)
- Use `INSERT ... ON DUPLICATE KEY UPDATE` for `daily_reward_budgets`, `daily_xp_totals`, `daily_rp_totals`
- Never SELECT-then-INSERT (race condition on row creation)

### Daily RP Cap Across Groups
- Sum `awarded_rp` across all `daily_reward_budgets` rows for same user/date/season before awarding
- Enforce 140 RP total cap in addition to per-group budget

### Pending Rewards
- Pending submissions do not increase confirmed RP
- Pending submissions do not reserve daily competitive budget (MVP)
- On approval: revalidate remaining budgets using original configuration snapshot, apply in deterministic order, apply daily caps, record actual awarded amount

---

## 11. Competitive Ranking (PRD Section 11)

### Rank Tiers (10 tiers)
| Rank | Min RP | Max RP |
|------|--------|--------|
| Warrior | 0 | 299 |
| Elite | 300 | 699 |
| Master | 700 | 1,199 |
| Grandmaster | 1,200 | 1,899 |
| Epic | 1,900 | 2,699 |
| Legend | 2,700 | 3,699 |
| Mythic | 3,700 | 4,999 |
| Mythical Honor | 5,000 | 6,499 |
| Mythical Glory | 6,500 | 7,499 |
| Mythical Immortal | 7,500 | Unlimited |

### Constraints
- Rank thresholds stored in database configuration (not hardcoded)
- Current rank determined by confirmed RP within active season
- Rank promotion triggers: update rank, record promotion event, generate notification, update dashboard, update leaderboard statistics
- Missing daily activities does not reduce RP
- Authorized reversal may reduce rank before season finalization
- Original rank emblems and designs (no copyrighted images from other games)

---

## 12. Lifetime XP & Level System (PRD Section 12)

### Level Formula
`XP Required to Reach Level L = 100 × (L - 1) × L / 2`

| Level | Required Cumulative XP |
|-------|----------------------|
| 1 | 0 |
| 2 | 100 |
| 3 | 300 |
| 4 | 600 |
| 5 | 1,000 |
| 6 | 1,500 |

### Constraints
- Lifetime XP accumulates across seasons; never reset
- Formula implemented in dedicated `LifetimeLevelService`
- Supports audited XP corrections via compensating transactions

---

## 13. Leaderboard System (PRD Section 13)

### Features
- Global Top 10
- Global Top 100
- Personal ranking
- Current season
- Previous seasons
- Rank filters
- Confirmed RP
- Valid completion count

### Ordering (Deterministic)
1. Confirmed RP descending
2. Valid standard activity completion count descending
3. Earliest timestamp at which final RP total was first reached
4. User ID ascending

### Privacy
- Public entries: display name, avatar, rank, confirmed RP, leaderboard position
- Never public: email, private activity notes, evidence files, personal schedules, sensitive profile information
- Users may opt out of public leaderboard visibility
- Pagination preserves official competitive position of visible users

### Performance
- Indexed queries
- Pagination
- Efficient aggregate statistics
- Optional caching
- Season-scoped filtering
- Avoid recalculating all point transactions for every request

---

## 14. Season Management (PRD Section 14)

### Season Duration
- Exactly 60 competition calendar days
- Timezone: Asia/Jakarta
- Interval: `starts_at <= event_timestamp < ends_at`
- Timestamps stored in UTC

### Season States
- `SCHEDULED`
- `ACTIVE`
- `CLOSED_FOR_SUBMISSIONS`
- `FINALIZING`
- `FINALIZED`

### Constraints
- At most one season accepts new competitive submissions at any moment
- A season pending finalization must not prevent the next season from becoming active
- First season start date configured by Super Administrator
- Subsequent seasons begin exactly 60 days after previous start, 00:00 Asia/Jakarta

### Season Transition
1. Close old season for new submissions
2. Activate next season
3. Initialize new season RP statistics at zero
4. Preserve lifetime XP, profiles, achievements, historical transactions
5. Begin finalization of previous season

### Pending Verification at Season End
- Maximum 24-hour review grace period
- Pre-cutoff submissions may be reviewed
- No new competitive RP for closed season
- Approved submissions may receive eligible old-season RP (charged against old season's `daily_reward_budgets.season_id`)
- Old-season budgets and daily caps enforced
- New season operates independently
- Unresolved submissions become ineligible for old-season RP after grace period

### Season Finalization
1. Resolve verification cutoff
2. Reconcile confirmed point transactions
3. Calculate final RP, ranks, leaderboard positions
4. Save immutable result snapshots
5. Assign season achievements
6. Mark season FINALIZED
7. Record finalization execution logs

### Scheduler Reliability
- Idempotent, retry-safe, lock-protected, logged, recoverable
- Repeated execution never duplicates results or rewards
- `scheduled_job_runs.execution_key` UNIQUE constraint prevents duplicate runs

---

## 15. API Routes (PRD Section 21)

All non-public endpoints require authentication and appropriate authorization.

### Authentication
- Uses supported CodeIgniter Shield authentication routes

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dashboard` | Dashboard page |
| GET | `/api/me/summary` | User summary |
| GET | `/api/me/statistics` | User statistics |

### Activities
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/activities` | List activities |
| GET | `/api/activities/today` | Today's activities |
| GET | `/api/activities/history` | Activity history |
| POST | `/api/activities/{id}/complete` | Complete activity |

### Timer
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/activities/{id}/timer/start` | Start timer |
| POST | `/api/activities/{id}/timer/finish` | Finish timer |

### Evidence
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/activities/{id}/evidence` | Upload evidence (stored outside web root) |
| GET | `/api/evidence/{id}/status` | Evidence status |
| GET | `/api/evidence/{id}/download` | Controller-mediated download (authorization required) |

### Ranking
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/ranks` | Rank tiers |
| GET | `/api/me/rank` | User's current rank |
| GET | `/api/leaderboard` | Leaderboard |
| GET | `/api/leaderboard/me` | User's leaderboard position |

### Seasons
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/seasons/current` | Current season |
| GET | `/api/seasons/history` | Season history |
| GET | `/api/seasons/{id}/results` | Season results |

### Achievements
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/me/achievements` | User achievements |

### Appeals
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/verifications/{id}/appeal` | Submit appeal |
| GET | `/api/me/appeals` | User's appeals |

### Administration (Protected)
- Activities, Categories, Reward Groups, Verification Policies, Season Configurations, Rank Configurations, Review Queue, Appeals, Users, Reports, Audit Logs

---

## 16. Security Boundaries (PRD Section 23)

### Authentication & Authorization
- CodeIgniter Shield for authentication
- Role-based permissions enforced by backend
- Ownership validation on all user resources
- Login rate limiting
- Secure session configuration
- HTTPS in production

### Input Security
- All inputs validated server-side
- Untrusted output escaped
- Parameterized database queries
- CSRF protection as appropriate
- Unauthorized resource changes rejected
- Uploaded files validated

### File Upload Security
- **Evidence stored outside public web root** (e.g., `writable/storage/evidence/` or `/var/lifequest/storage/evidence/`)
- MIME and content validation (finfo)
- File size restrictions (max 5 MB for evidence)
- Random filenames (UUID)
- Controlled downloads via controller with authorization check
- Restricted review access
- Retention cleanup (30 days standard)
- Logging of sensitive access

### Secrets Management
- Environment variables for all secrets
- No hardcoded credentials
- No production secrets committed
- Restricted database permissions
- Debug mode disabled in production

### Reward Security
- Points calculated on server only
- Transaction idempotency enforced
- Duplicate rewards prevented
- Client-side reward manipulation prevented
- Season transitions protected
- Point corrections audited (compensating transactions)

---

## 17. Project Folder Structure

```
lifequest/
├── app/
│   ├── Config/
│   │   ├── App.php
│   │   ├── Database.php
│   │   ├── Routes.php
│   │   ├── Filters.php
│   │   └── Shield.php
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── Home.php
│   │   ├── Auth.php
│   │   ├── Dashboard.php
│   │   ├── Activities.php
│   │   ├── Checklist.php
│   │   ├── Timer.php
│   │   ├── Evidence.php
│   │   ├── Verification.php
│   │   ├── Ranking.php
│   │   ├── Leaderboard.php
│   │   ├── Seasons.php
│   │   ├── Achievements.php
│   │   ├── Appeals.php
│   │   ├── Notifications.php
│   │   ├── Profile.php
│   │   ├── Settings.php
│   │   └── Admin/
│   │       ├── Dashboard.php
│   │       ├── Activities.php
│   │       ├── Users.php
│   │       ├── Review.php
│   │       ├── Appeals.php
│   │       ├── Reports.php
│   │       └── Audit.php
│   ├── Services/
│   │   ├── BaseService.php
│   │   ├── ActivityService.php
│   │   ├── ActivityScheduleService.php
│   │   ├── ChecklistService.php
│   │   ├── TimerService.php
│   │   ├── EvidenceService.php
│   │   ├── VerificationService.php
│   │   ├── RiskScoringService.php
│   │   ├── AntiCheatService.php
│   │   ├── PointsService.php
│   │   ├── RewardBudgetService.php
│   │   ├── RankingService.php
│   │   ├── LifetimeLevelService.php
│   │   ├── LeaderboardService.php
│   │   ├── SeasonService.php
│   │   ├── StreakService.php
│   │   ├── AchievementService.php
│   │   ├── NotificationService.php
│   │   ├── AdminReviewService.php
│   │   └── ReportingService.php
│   ├── Models/
│   │   ├── UserProfileModel.php
│   │   ├── ActivityCategoryModel.php
│   │   ├── RewardGroupModel.php
│   │   ├── ActivityModel.php
│   │   ├── ActivitySessionModel.php
│   │   ├── UserActivityPlanModel.php
│   │   ├── DailyActivityInstanceModel.php
│   │   ├── VerificationPolicyModel.php
│   │   ├── ActivityEvidenceModel.php
│   │   ├── TimerSessionModel.php
│   │   ├── ActivityVerificationLogModel.php
│   │   ├── AntiCheatFlagModel.php
│   │   ├── VerificationAppealModel.php
│   │   ├── ActivityConfigurationVersionModel.php
│   │   ├── SeasonRewardGroupBudgetModel.php
│   │   ├── DailyRewardBudgetModel.php
│   │   ├── DailyXpTotalModel.php
│   │   ├── DailyRpTotalModel.php
│   │   ├── PointTransactionModel.php
│   │   ├── UserLifetimeStatModel.php
│   │   ├── RankModel.php
│   │   ├── SeasonModel.php
│   │   ├── SeasonRankConfigurationModel.php
│   │   ├── UserSeasonStatModel.php
│   │   ├── SeasonResultModel.php
│   │   ├── DailyStreakModel.php
│   │   ├── AchievementModel.php
│   │   ├── UserAchievementModel.php
│   │   ├── NotificationModel.php
│   │   ├── AdminAuditLogModel.php
│   │   ├── SystemSettingModel.php
│   │   └── ScheduledJobRunModel.php
│   ├── Entities/
│   │   ├── UserProfile.php
│   │   ├── Activity.php
│   │   ├── DailyActivityInstance.php
│   │   ├── PointTransaction.php
│   │   ├── UserSeasonStat.php
│   │   └── ... (other entities as needed)
│   ├── Views/
│   │   ├── layout/
│   │   │   ├── base.php
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   └── navbar.php
│   │   ├── partials/
│   │   │   ├── flash.php
│   │   │   ├── xp_bar.php
│   │   │   ├── rank_progress.php
│   │   │   └── streak_counter.php
│   │   ├── errors/
│   │   ├── home/
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── activities/
│   │   ├── checklist/
│   │   ├── timer/
│   │   ├── evidence/
│   │   ├── ranking/
│   │   ├── leaderboard/
│   │   ├── seasons/
│   │   ├── achievements/
│   │   ├── appeals/
│   │   ├── profile/
│   │   ├── settings/
│   │   └── admin/
│   ├── Database/
│   │   ├── Migrations/
│   │   └── Seeds/
│   ├── Filters/
│   │   ├── Auth.php
│   │   ├── Role.php
│   │   └── Csrf.php
│   └── Libraries/
│       └── GamificationLib.php (optional)
├── public/
│   ├── index.php
│   ├── .htaccess
│   ├── assets/
│   │   ├── css/
│   │   │   └── custom.css
│   │   └── js/
│   │       └── app.js
│   ├── uploads/  <-- REMOVED (no public uploads directory)
├── writable/
│   ├── cache/
│   ├── logs/
│   └── storage/
│       └── evidence/   <-- Private evidence storage (outside web root)
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

> **`writable/storage/evidence/`** is outside the public web root. All evidence retrieval goes through `EvidenceController::download()` with authorization check.

---

## 18. Language Strategy

| Layer | Language | Notes |
|-------|----------|-------|
| User-facing UI | Indonesian | All labels, messages, navigation, notifications |
| Technical documentation | English | All .md files, code comments |
| Code identifiers | English | Class names, method names, variables, database tables/columns |
| Database identifiers | English | Table names, column names |
| Brand names | English | LifeQuest, XP, RP, rank names |

Future localization mechanism must be supported but is not required for MVP.

---

## 19. Unresolved Architectural Decisions

| # | Decision | Options | Impact |
|---|----------|---------|--------|
| A1 | Shield `users.id` type | INT UNSIGNED vs BIGINT UNSIGNED | Must verify before creating custom FKs |
| A2 | Timer session storage | Database vs in-memory (Redis) | Affects TimerService complexity and performance |
| A3 | Evidence storage backend | Local filesystem vs cloud storage | Affects EvidenceService and deployment |
| A4 | Scheduled job runner | CI4 scheduler vs external cron | Affects season transitions and streak reconciliation |
| A5 | Caching layer | File cache vs Redis vs none | Affects leaderboard performance |
| A6 | PHP 8.5.12 / MySQL 9.7 availability | Confirm vs fallback versions | Affects environment setup |

**Blocking item:** A1 must be resolved during Phase 0 verification before custom migrations are written.

---

## 20. Next Step

Wait for implementation approval. No source code, migrations, or dependency installation may begin until ARCHITECTURE.md, DATABASE.md, DEVELOPMENT_PLAN.md, TASKS.md, TESTING.md, AGENTS.md, and DESIGN_REVIEW.md are reviewed and approved.
