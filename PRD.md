# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# LIFEQUEST — DAILY LIFE RPG

**Document Version:** 2.1.0  
**Document Status:** Final Product Requirements Baseline  
**Document Date:** October 23, 2026  
**Product Name:** LifeQuest  
**Product Category:** Gamified Daily Activity and Habit Tracking  
**Application Type:** Responsive Web Application  
**Development Methodology:** PRD-Driven Development  
**Development Environment:** Windows  
**Development Agent:** OpenCode  
**Primary Documentation Language:** English  
**Application Interface Language:** Indonesian (Bahasa Indonesia)  
**Project Root:** `D:\Project OpenCODE\Checklist`  
**Target Database:** `lifequest_db`

---

# 1. EXECUTIVE SUMMARY

## 1.1 Product Description

LifeQuest is a gamified daily activity management application designed to help users develop positive habits, improve personal productivity, and maintain consistent routines.

The application allows users to organize and complete daily activities from waking up in the morning until going to sleep at night.

Users receive Experience Points (XP) and Rank Points (RP) for completing eligible activities.

The application includes competitive ranking, seasonal leaderboards, achievements, streaks, and automated activity verification.

LifeQuest combines habit tracking, productivity management, and game-inspired progression while maintaining user privacy and fair competition.

## 1.2 Product Vision

Transform everyday activities into meaningful, measurable, and rewarding personal achievements.

The application must encourage consistent personal improvement without creating excessive pressure, unnecessary surveillance, or unhealthy competition.

## 1.3 Business Objectives

The application must:

1. Encourage positive daily habits.
2. Increase consistency and discipline.
3. Improve personal productivity.
4. Provide measurable activity progress.
5. Motivate users through gamification.
6. Support fair seasonal competition.
7. Minimize fraudulent reward claims.
8. Automate routine verification.
9. Reduce administrator workload.
10. Scale to thousands of active users.
11. Protect user privacy.
12. Provide reliable activity and reward histories.

## 1.4 Target Users

- Students.
- University students.
- Office employees.
- Shift workers.
- Fitness enthusiasts.
- Personal development enthusiasts.
- Productivity communities.
- General users seeking structured daily routines.

## 1.5 Core Product Features

LifeQuest must include:

1. User registration and authentication.
2. User profiles.
3. Daily activity checklists.
4. Activity categories.
5. Administrator-managed activity catalogs.
6. Custom personal activities.
7. Repeatable activity sessions.
8. Daily schedule management.
9. Experience Points.
10. Rank Points.
11. Lifetime levels.
12. Competitive rank progression.
13. Global leaderboards.
14. Achievement badges.
15. Daily streaks.
16. Sixty-day competitive seasons.
17. Automated activity verification.
18. Optional photo evidence.
19. Timer-based activity verification.
20. Rule-based anti-cheat monitoring.
21. Risk scoring.
22. Administrator review queues.
23. Verification appeals.
24. Notifications.
25. Statistics and reporting.
26. Administrative configuration.
27. Audit logging.
28. Responsive interfaces.

---

# 2. TECHNOLOGY REQUIREMENTS

## 2.1 Backend Technology

| Component | Required Technology |
|---|---|
| Programming Language | PHP 8.5.12 |
| Framework | CodeIgniter 4.7.0 |
| Authentication | CodeIgniter Shield |
| Architecture | MVC with Service Layer |
| Dependency Manager | Composer |
| Database Access | CodeIgniter Models and Query Builder |
| Database Schema Management | CodeIgniter Migrations |
| Initial Data | CodeIgniter Seeders |
| API Format | JSON over HTTP |

The specified versions are the requested development baseline.

Before installation, the development agent must verify official package availability, compatibility, and security advisories.

Security patch updates may be proposed, but framework replacement requires explicit approval.

## 2.2 Database Technology

| Component | Requirement |
|---|---|
| Database Engine | MySQL 9.7 LTS |
| Database Name | lifequest_db |
| Storage Engine | InnoDB |
| Character Set | utf8mb4 |
| Collation | Compatible utf8mb4 collation |
| Relationship Integrity | Foreign Keys |
| Transaction Support | Required |
| Migration Support | Required |
| Time Storage | UTC for event timestamps |

The database must support transactional consistency, concurrent requests, historical records, and efficient leaderboard queries.

## 2.3 Frontend Technology

| Component | Requirement |
|---|---|
| CSS Framework | Bootstrap 5.3.8 |
| Icon Library | Compatible Bootstrap Icons version |
| Markup | HTML5 |
| Styling | CSS3 |
| JavaScript | Vanilla JavaScript |
| HTTP Requests | Fetch API |
| Charts | Compatible stable Chart.js release |
| Layout Strategy | Mobile-First Responsive Design |
| Default Theme | Dark Gaming Theme |

Do not introduce React, Vue, Angular, or another major frontend framework without approval.

## 2.4 Development Environment

Development operating system: Windows.

Project directory:

`D:\Project OpenCODE\Checklist`

Required tools:

- PHP.
- Composer.
- MySQL.
- Git.
- OpenCode.
- Supported local web server.

The development agent must inspect installed dependencies before installing additional software.

## 2.5 Production Environment

The initial production architecture must support:

- Linux hosting.
- Nginx or Apache.
- HTTPS.
- PHP-FPM where appropriate.
- MySQL.
- Scheduled jobs.
- Application logging.
- Automated backups.
- Environment-based secrets.
- Restricted file storage.

## 2.6 Version Management

The project must use reproducible dependency versions.

Requirements:

- Commit composer.lock.
- Record installed framework versions.
- Record database version.
- Review dependency security advisories.
- Avoid unapproved major upgrades.
- Maintain installation documentation.

---

# 3. PRODUCT SCOPE

## 3.1 Minimum Viable Product

The MVP includes:

- Authentication.
- User profiles.
- Activity management.
- Daily checklists.
- Repeatable activities.
- Activity scheduling.
- XP and RP calculation.
- Reward group budgets.
- Lifetime levels.
- Competitive ranks.
- Leaderboards.
- Streaks.
- Achievements.
- Sixty-day seasons.
- Automatic verification.
- Timer verification.
- Optional photo evidence.
- Rule-based risk scoring.
- Administrator review.
- Appeals.
- Notifications.
- Statistics.
- Administrative dashboard.
- Audit logs.

## 3.2 Future Features

The following features are excluded from the initial MVP:

- Native Android applications.
- Native iOS applications.
- AI-based coaching.
- AI photo assessment.
- Wearable device integrations.
- GPS-based verification.
- Paid subscriptions.
- Purchased XP or RP.
- Team-based competitions.
- Social messaging.
- Advanced machine-learning fraud detection.

The architecture should support future expansion without making these features mandatory dependencies.

---

# 4. USER ROLES AND ACCESS CONTROL

## 4.1 Guest

Guests may:

- View the landing page.
- Register an account.
- Access the login page.
- Request password recovery.
- Read public application information.

Guests must not access private activity records.

## 4.2 Registered User

Registered users may:

- Manage personal profiles.
- Configure daily schedules.
- Select predefined activities.
- Create personal activities.
- Complete checklists.
- Use activity timers.
- Upload eligible evidence.
- View earned XP and RP.
- View lifetime levels.
- View competitive ranks.
- View leaderboards.
- View achievements.
- View season history.
- Submit verification appeals.
- Manage privacy settings.

Users must not modify another user's activity, rewards, evidence, or profile.

## 4.3 Administrator

Administrators may:

- Manage activity categories.
- Manage standard activities.
- Configure verification methods.
- Configure repeatable sessions.
- Manage reward groups.
- Review flagged submissions.
- Review appeals.
- Manage achievements.
- Manage users within authorized permissions.
- View reports.
- Review audit logs.
- Schedule future competitive configuration changes.

Administrators must not directly manipulate historical rewards without an auditable correction process.

## 4.4 Super Administrator

Super Administrators may:

- Manage administrator permissions.
- Configure application-wide settings.
- Manage season configuration.
- Review sensitive system events.
- Authorize exceptional correction workflows.
- Manage operational settings.

Super Administrator actions must be audited.

## 4.5 Authorization Requirements

Authorization must be enforced by the backend.

Interface visibility alone is not sufficient access control.

Use CodeIgniter Shield groups and permissions wherever applicable.

---

# 5. ACTIVITY MANAGEMENT

## 5.1 Activity Categories

Default categories:

1. Morning Routine.
2. Personal Hygiene.
3. Nutrition.
4. Health and Fitness.
5. Work and Productivity.
6. Learning and Education.
7. Household.
8. Social and Family.
9. Evening Routine.
10. Sleep and Recovery.

Administrators may create additional categories.

## 5.2 Activity Definition

Each activity must contain:

- Activity ID.
- Activity Code.
- Activity Name.
- Description.
- Category ID.
- Reward Group ID.
- Activity Type.
- Repeat Type.
- Default XP.
- Default RP.
- Verification Policy.
- Maximum Daily Completions.
- Maximum Weekly Completions.
- Minimum Duration.
- Cooldown Duration.
- Schedule Rules.
- Active Status.
- Creation Timestamp.
- Modification Timestamp.

## 5.3 Activity Types

### Standard Activity

Created and managed by administrators.

Eligible to award competitive RP.

Subject to reward group allocation, verification rules, and season configuration.

### Custom Activity

Created by registered users.

Available for personal tracking.

Eligible for limited lifetime XP.

Not eligible for competitive RP in MVP.

### Alternative Standard Activity

An administrator-approved activity that serves the same purpose as another standard activity.

Shares the relevant reward group budget.

Examples:

- Walking instead of running.
- Reading instead of watching an educational lesson.
- Tidying a desk instead of tidying a bedroom.

Alternative activities must not increase the user's maximum competitive RP opportunity.

## 5.4 Activity Scheduling

Users may define:

- Wake-up time.
- Morning routine.
- Work or study periods.
- Exercise schedule.
- Learning periods.
- Household activities.
- Evening routine.
- Sleep schedule.

Scheduling must support users working nonstandard shifts.

The system must not assume that all users wake up in the morning.

## 5.5 Daily Activity Instances

Daily checklist records must be generated from approved activity plans.

Each instance must contain:

- User ID.
- Activity ID.
- Activity Session ID when applicable.
- Activity Date.
- Occurrence Number.
- Scheduled Start.
- Scheduled End.
- Completion Status.
- Verification Status.
- Reward Status.
- Related Season.
- Configuration Version.

Existing instances must retain the configuration applicable when they were created or rewarded.

---

# 6. REPEATABLE ACTIVITY SYSTEM

## 6.1 Supported Repeat Types

The system must support:

**ONCE_DAILY**

One eligible completion per day.

**FIXED_SESSIONS**

One completion per configured session.

**REPEATABLE**

Multiple sessions subject to limits and cooldowns.

**WEEKLY**

Limited completions within an ISO calendar week.

## 6.2 Example: Bedroom Cleaning

Activity: Bedroom Tidying.

Reward Group: Household.

Daily Reward Group Budget: 10 RP.

Configuration:

| Session | Schedule | XP | RP |
|---|---|---:|---:|
| Morning Tidying | 06:00–09:00 | 5 | 5 |
| Evening Tidying | 18:00–22:00 | 5 | 5 |

The user may complete both sessions.

Maximum combined Household RP remains 10.

The same session cannot generate multiple rewards.

## 6.3 Repeatable Activity Requirements

Each repeatable activity must support:

- Maximum completions per day.
- Maximum completions per week.
- Minimum cooldown.
- Independent session identity.
- Session-level verification.
- Session-level reward calculation.
- Reward group budget enforcement.

## 6.4 Duplicate Completion Prevention

The backend must prevent duplicate rewards based on the unique activity occurrence identity.

For fixed sessions, uniqueness must include:

- User.
- Activity.
- Activity Date.
- Session.

For repeatable activities, a distinct occurrence identifier must be generated and validated server-side.

The user must not be able to create unlimited rewardable occurrences by manipulating request parameters.

---

# 7. POINTS AND REWARD SYSTEM

## 7.1 Point Types

LifeQuest uses two primary point types.

### Experience Points (XP)

XP represents long-term personal progress.

Characteristics:

- Accumulates over time.
- Does not reset between seasons.
- Determines lifetime levels.
- Supports historical analysis.
- May be adjusted only through an audited correction transaction.

### Rank Points (RP)

RP represents seasonal competitive progress.

Characteristics:

- Earned during an active season.
- Determines competitive rank.
- Determines leaderboard position.
- Subject to reward group budgets.
- Subject to daily caps.
- Archived after season finalization.
- Starts from zero in each new season.

## 7.2 Default Activity Points

| No. | Activity | Reward Group | XP | RP | Verification |
|---|---|---|---:|---:|---|
| 1 | Wake Up According to Personal Schedule | Morning | 10 | 10 | Self Check |
| 2 | Complete Personal Hygiene Routine | Hygiene | 10 | 10 | Self Check |
| 3 | Complete Planned Breakfast Routine | Nutrition | 10 | 10 | Self Check |
| 4 | Complete Suitable Physical Exercise | Fitness | 20 | 20 | Timer |
| 5 | Complete Focused Work or Study Session | Productivity | 20 | 20 | Timer |
| 6 | Learn or Practice a New Skill | Learning | 15 | 15 | Timer |
| 7 | Tidy Personal Living Space | Household | 10 | 10 | Self Check |
| 8 | Complete Meaningful Social Interaction | Social | 10 | 10 | Self Check |
| 9 | Review the Day and Plan Tomorrow | Evening | 10 | 10 | Self Check |
| 10 | Follow Personal Sleep or Rest Plan | Sleep | 15 | 15 | Self Check |

Standard activity maximum:

**130 XP and 130 RP per day.**

Daily streak bonus:

**10 XP and 10 RP.**

Custom activity allowance:

**Maximum 10 additional XP per day and 0 RP.**

Final limits:

**Maximum Daily XP: 150.**

**Maximum Daily RP: 140.**

## 7.3 Reward Group Budget

Every standard activity eligible for RP must belong to one reward group.

Initial configuration:

| Reward Group | Daily RP Budget |
|---|---:|
| Morning | 10 |
| Hygiene | 10 |
| Nutrition | 10 |
| Fitness | 20 |
| Productivity | 20 |
| Learning | 15 |
| Household | 10 |
| Social | 10 |
| Evening | 10 |
| Sleep | 15 |
| Streak | 10 |
| **Total** | **140** |

Reward group budgets represent the maximum competitive points available within each category.

## 7.4 Administrator Point Management

Administrators may:

- Create standard activities.
- Create alternative activities.
- Configure repeatable sessions.
- Split reward budgets between sessions.
- Adjust point values for future seasons.
- Disable obsolete activities.
- Configure verification requirements.

Competitive point configuration must be versioned and locked for active seasons.

An administrator may not introduce additional competitive reward opportunities during an active season in a way that disadvantages existing users.

## 7.5 Reward Calculation

The backend must calculate RP using:

`Awarded RP = MIN(Activity RP, Remaining Group RP, Remaining Daily RP)`

XP must be calculated independently:

`Awarded XP = MIN(Activity XP, Remaining Daily XP)`

The following constraints must be validated before committing rewards:

1. User authentication.
2. Activity ownership.
3. Activity eligibility.
4. Valid occurrence.
5. Schedule eligibility.
6. Repeat limits.
7. Verification policy.
8. Reward group budget.
9. Daily point caps.
10. Season eligibility.
11. Duplicate transaction prevention.

All related changes must be committed atomically.

## 7.6 Partial Rewards

If only 5 RP remain in a reward group and the activity is worth 10 RP:

- Award 5 RP.
- Record the nominal reward as 10 RP.
- Record the actual reward as 5 RP.
- Do not carry unused RP to another day.

Completing an activity with no remaining RP must still be recorded as a valid personal completion when all other rules are satisfied.

## 7.7 Daily Point Boundaries

Competitive RP days use the `Asia/Jakarta` timezone.

The RP daily cap is applied per user and competition calendar date.

XP daily caps use the user's configured local calendar date.

All timestamps must be stored in UTC.

The system must retain both:

- Local activity date.
- Competition date.

A transaction must not be counted twice due to timezone conversion.

## 7.8 Custom Activity Rewards

User-created activities may earn up to 10 XP per user per local day.

Custom activities must never generate competitive RP in MVP.

Users cannot define their own XP or RP reward values.

Custom XP rewards are calculated according to administrator-defined rules.

## 7.9 Point Ledger

Every reward adjustment must create a point transaction.

Required transaction types:

- ACTIVITY_REWARD.
- STREAK_BONUS.
- ADMIN_CORRECTION.
- REWARD_REVERSAL.

Point transactions must be immutable after posting.

Corrections must use compensating transactions.

The same reward event must never create more than one successful point award.

---

# 8. ACTIVITY VERIFICATION SYSTEM

## 8.1 Verification Objective

The verification system must reduce invalid reward claims without requiring administrator review of every activity.

Verification must be primarily automated.

The system must distinguish policy compliance from independently verified activity performance.

No verification method can guarantee absolute truthfulness.

## 8.2 Verification Methods

The following methods must be supported:

1. SELF_CHECK.
2. TIMER.
3. PHOTO_PROOF.
4. ADMIN_APPROVAL.

Activities may use a combination of methods.

## 8.3 Self-Check Verification

Users confirm activity completion through the interface.

The backend validates:

- User identity.
- Activity eligibility.
- Correct activity occurrence.
- Completion limits.
- Schedule restrictions.
- Duplicate submissions.
- Season eligibility.
- Reward budgets.

Self-check is suitable for ordinary private routines.

It does not constitute independent proof that the activity occurred.

## 8.4 Timer Verification

Timer-based activities must support:

- Server-recorded start time.
- Server-recorded end time.
- Elapsed duration.
- Activity association.
- User association.
- Valid timer session ID.
- Minimum duration.
- Maximum eligible duration.
- Session overlap detection.

Client-side clocks must not determine reward eligibility.

A browser refresh must not automatically reset a running server-side session.

Timers measure elapsed session time, not guaranteed attention or effort.

## 8.5 Photo Evidence Verification

Photo evidence must be supported for eligible activities.

Default requirements:

- JPEG, PNG, or WebP.
- Maximum file size: 5 MB.
- Validate actual file contents.
- Generate randomized filenames.
- Store files outside the public web root.
- Remove unnecessary metadata.
- Calculate file hashes.
- Restrict access by authorization.
- Apply evidence retention rules.

Photo evidence must not be mandatory for intimate or sensitive personal routines.

An uploaded photograph is supporting evidence, not definitive proof of performance.

## 8.6 Administrator Approval

Manual approval applies only when required by policy or risk assessment.

Examples:

- Special activities.
- High-value challenges.
- Suspicious evidence.
- Appeal cases.
- Exceptional verification disputes.

Routine low-risk activities must not require administrator approval.

## 8.7 Verification States

Required verification states:

- NOT_REQUIRED.
- PENDING.
- AUTO_VALIDATED.
- MANUALLY_APPROVED.
- REJECTED.

Required reward states:

- NOT_ELIGIBLE.
- PENDING.
- CONFIRMED.
- REJECTED.
- REVERSED.

Only confirmed RP contributes to seasonal competitive standings.

## 8.8 Verification Processing

The verification process must:

1. Validate authentication.
2. Validate activity eligibility.
3. Validate timing and occurrence.
4. Validate completion limits.
5. Validate applicable evidence.
6. Evaluate risk signals.
7. Determine the verification outcome.
8. Hold rewards requiring review.
9. Award eligible rewards atomically after approval.
10. Record all decisions and reason codes.

---

# 9. RISK-BASED ANTI-CHEAT SYSTEM

## 9.1 Anti-Cheat Architecture

The MVP must use deterministic rules and explainable risk scoring.

AI-based fraud detection is not required.

The system must avoid automatically treating suspicious behavior as confirmed fraud.

## 9.2 Risk Score

Each eligible completion may receive a risk score between 0 and 100.

Initial policy:

| Risk Score | Classification | Action |
|---|---|---|
| 0–29 | Low | Automatic Validation |
| 30–69 | Medium | Secondary Verification |
| 70–100 | High | Administrator Review |

Threshold values must be configurable.

## 9.3 Risk Indicators

Example indicators:

- Multiple completions submitted unusually quickly.
- Repeated duplicate requests.
- Conflicting timer sessions.
- Reused evidence hashes.
- Unusual submission patterns.
- Completion outside permitted schedules.
- Repeated failed validation attempts.
- Unexpected changes to activity identifiers.

A failed or repeated network request alone must not be interpreted as fraud.

## 9.4 Deterministic Validation Rules

The following conditions must be handled by direct validation rather than risk scoring alone:

- Invalid authentication.
- Unauthorized resource access.
- Duplicate reward transactions.
- Exceeded completion limits.
- Exceeded reward budgets.
- Invalid season assignment.
- Invalid activity/session identity.

These requests must not create unauthorized rewards.

## 9.5 Secondary Verification

Medium-risk submissions may require:

- Additional rule checks.
- Eligible supporting evidence.
- Timer history validation.
- Duplicate evidence analysis.
- Manual review if automation remains inconclusive.

If optional evidence is declined, the system may record the personal activity without competitive RP when the verification policy requires further proof.

## 9.6 Administrator Review Queue

The administrator review interface must provide:

- Submission ID.
- User.
- Activity.
- Submission Time.
- Verification Method.
- Risk Score.
- Risk Reason Codes.
- Evidence Preview.
- Review Status.
- Approve.
- Reject.
- Request Additional Information.
- Review Notes.
- Review History.

Administrator decisions must include a reason.

## 9.7 Review Priority

Review queues must prioritize:

1. Season-ending cases approaching the review deadline.
2. Appeals.
3. High-risk submissions.
4. Older unresolved submissions.

The system must report pending volume and processing age.

## 9.8 Appeals

Users must be able to appeal rejected submissions.

Appeals must record:

- User ID.
- Submission ID.
- Appeal Reason.
- Submission Time.
- Review Status.
- Administrator Decision.
- Decision Reason.
- Resolution Time.

The original decision must remain in the audit history.

## 9.9 Anti-Cheat Limitations

The system must not:

- Claim that a risk score proves dishonesty.
- Automatically permanently ban users based solely on a score.
- Require intrusive evidence for personal routines.
- Publicly expose suspected violations.
- Delete historical transactions to hide corrections.

---

# 10. REWARD PROCESSING AND CONCURRENCY

## 10.1 Atomic Reward Processing

All reward operations must use database transactions.

The operation must atomically update:

- Activity completion.
- Verification outcome.
- Point ledger.
- Daily reward budget.
- Daily point totals.
- User lifetime statistics.
- User season statistics.

If any required operation fails, the transaction must roll back.

## 10.2 Idempotency

Every reward event must use a server-controlled unique idempotency key.

Repeated HTTP requests must return the existing result without creating duplicate rewards.

Idempotency must be enforced through a database unique constraint.

## 10.3 Concurrent Requests

The system must prevent race conditions caused by:

- Multiple browser tabs.
- Rapid repeated clicks.
- Network retries.
- Concurrent approvals.
- Parallel timer completions.

Use database transactions, unique constraints, and row-level locking as appropriate.

## 10.4 Daily Reward Budget Locking

The reward engine must serialize conflicting updates to the same user's daily reward budget.

A user must never exceed:

- Daily reward group limits.
- Daily XP limits.
- Daily RP limits.

## 10.5 Pending Rewards

Pending submissions must not increase confirmed RP.

For the MVP, pending submissions do not reserve daily competitive budget.

When approval occurs:

- Revalidate remaining budgets.
- Apply rewards in deterministic submission order.
- Use the original configuration snapshot.
- Apply applicable daily caps.
- Record the actual awarded amount.

This policy avoids granting more than the daily cap when multiple pending submissions are approved.

## 10.6 Corrections

Approved correction workflows may reverse previously confirmed rewards.

Corrections must:

- Use compensating transactions.
- Update current aggregates consistently.
- Preserve original transactions.
- Record the administrator and reason.
- Recalculate the affected rank if the season is not finalized.

Finalized season standings must not be silently modified.

Any post-finalization dispute requires a separately audited administrative resolution without rewriting the official archived standings.

---

# 11. COMPETITIVE RANKING SYSTEM

## 11.1 Rank Tiers

| Rank | Minimum RP | Maximum RP |
|---|---:|---:|
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

## 11.2 Rank Calculation

The current rank must be determined by the user's confirmed RP within the active season.

Rank thresholds must be stored in database configuration.

Do not hardcode rank thresholds inside controllers.

## 11.3 Rank Promotion

When a user's confirmed RP crosses a threshold:

1. Update the current rank.
2. Record the promotion event.
3. Generate a notification.
4. Update the dashboard.
5. Update leaderboard statistics.

## 11.4 Rank Demotion

Missing daily activities does not reduce RP.

An authorized reversal of invalid points may reduce the current rank before season finalization.

## 11.5 Rank Assets

LifeQuest must use original rank emblems and designs.

Do not use copyrighted rank images or logos from other games.

---

# 12. LIFETIME XP AND LEVEL SYSTEM

## 12.1 Lifetime XP

Lifetime XP accumulates across seasons.

It is not reset by season transitions.

## 12.2 Lifetime Level Formula

Initial formula:

`XP Required to Reach Level L = 100 × (L - 1) × L / 2`

Examples:

| Level | Required Cumulative XP |
|---|---:|
| 1 | 0 |
| 2 | 100 |
| 3 | 300 |
| 4 | 600 |
| 5 | 1,000 |
| 6 | 1,500 |

The formula must be implemented in a dedicated service.

## 12.3 Level Progress

The dashboard must display:

- Current Level.
- Total XP.
- XP for Current Level.
- XP Required for Next Level.
- Level Progress Bar.

Lifetime level calculation must support audited XP corrections.

---

# 13. LEADERBOARD SYSTEM

## 13.1 Leaderboard Features

- Global Top 10.
- Global Top 100.
- Personal Ranking.
- Current Season.
- Previous Seasons.
- Rank Filters.
- Confirmed RP.
- Valid Completion Count.

## 13.2 Leaderboard Ordering

Rank users by:

1. Confirmed RP descending.
2. Valid standard activity completion count descending.
3. Earliest timestamp at which the final RP total was first reached.
4. User ID ascending.

Sorting must be deterministic.

## 13.3 Privacy

Public leaderboard entries may display:

- Display Name.
- Avatar.
- Rank.
- Confirmed RP.
- Leaderboard Position.

They must not display:

- Email.
- Private activity notes.
- Evidence files.
- Personal schedules.
- Sensitive profile information.

Users may opt out of public leaderboard visibility.

Their internal competition results remain recorded.

Public leaderboard pagination must preserve the official competitive position of visible users rather than renumbering hidden participants as if they did not exist.

## 13.4 Leaderboard Performance

The system must use:

- Indexed queries.
- Pagination.
- Efficient aggregate statistics.
- Optional caching.
- Season-scoped filtering.

Avoid recalculating all point transactions for every leaderboard request.

---

# 14. SEASON MANAGEMENT

## 14.1 Season Duration

Every season lasts exactly 60 competition calendar days.

Competition timezone:

`Asia/Jakarta`

Season intervals must use:

`starts_at <= event_timestamp < ends_at`

Timestamps must be stored in UTC.

## 14.2 Season Start

The first season start date must be configured by the Super Administrator.

Each subsequent season begins exactly 60 calendar days after the previous season start, at 00:00 Asia/Jakarta.

## 14.3 Season Lifecycle

Supported states:

- SCHEDULED.
- ACTIVE.
- CLOSED_FOR_SUBMISSIONS.
- FINALIZING.
- FINALIZED.

A season pending finalization must not prevent the next scheduled season from becoming active.

At most one season may accept new competitive submissions at any moment.

## 14.4 Season Transition

At the season boundary:

1. Close the old season for new submissions.
2. Activate the next season.
3. Initialize new season RP statistics at zero.
4. Preserve lifetime XP.
5. Preserve user profiles.
6. Preserve achievements.
7. Preserve historical point transactions.
8. Begin finalization of the previous season.

## 14.5 Pending Verification at Season End

Eligible pending submissions must have been received before the old season cutoff.

A maximum 24-hour review grace period applies.

During this period:

- Pre-cutoff submissions may be reviewed.
- No new submission may be assigned competitive RP for the closed season.
- Approved submissions may receive eligible old-season RP.
- Old-season reward budgets and daily caps remain enforced.
- The new season operates independently.

At the end of the grace period, unresolved submissions become ineligible for old-season RP.

They may remain visible as personal activity records.

## 14.6 Season Finalization

Finalization must:

1. Resolve the applicable verification cutoff.
2. Reconcile confirmed point transactions.
3. Calculate final RP.
4. Calculate final ranks.
5. Calculate final leaderboard positions.
6. Save immutable result snapshots.
7. Assign season achievements.
8. Mark the season FINALIZED.
9. Record finalization execution logs.

## 14.7 Season Reset Policy

Season RP resets logically through new season statistics.

Do not delete historical RP transactions.

Do not reset lifetime XP.

Do not delete badges.

Do not delete previous season results.

## 14.8 Scheduler Reliability

Scheduled jobs must be:

- Idempotent.
- Retry-safe.
- Lock-protected.
- Logged.
- Recoverable after interruption.

A repeated scheduler execution must never duplicate season results or rewards.

---

# 15. STREAK SYSTEM

## 15.1 Streak Definition

A streak represents consecutive eligible activity days meeting the user's daily routine target.

## 15.2 Initial Eligibility Rule

A qualifying day requires:

- At least 60% completion of the valid daily target.
- At least five eligible standard activity targets or approved equivalent weighted targets.
- Coverage of at least three approved reward groups.
- No duplicated completion rewards.

The minimum target requirement must support approved accessibility alternatives.

## 15.3 Streak Rewards

Once an eligible day is finalized:

- Award 10 XP.
- Award 10 RP.

Only one streak bonus is permitted per eligible day.

The streak RP bonus belongs to the Streak reward group.

## 15.4 Streak Evaluation

Streak evaluation must use a server-controlled daily reconciliation process.

Daily streak rewards must not depend on how many times the user opens the dashboard.

## 15.5 Sleep and Overnight Activities

Sleep activities may be confirmed on the following morning until 12:00 in the user's configured timezone.

The activity remains associated with its original local activity date.

Late completion cannot create retroactive competitive RP after an applicable season submission cutoff.

The system must avoid awarding the streak bonus before all eligible completion grace periods for that activity date have closed.

---

# 16. ACHIEVEMENTS AND BADGES

## 16.1 Default Achievements

| Achievement | Requirement |
|---|---|
| First Step | First valid activity completion |
| Early Bird | Morning routine completed on 7 eligible days |
| Consistency Hero | 7-day streak |
| Discipline Master | 30-day streak |
| Fitness Warrior | 20 valid fitness completions |
| Learning Explorer | 20 valid learning completions |
| Season Finisher | At least 30 active days in one season |
| Legendary Discipline | Reach Legend |
| Mythic Achiever | Reach Mythic |
| Season Champion | Finish a season in first position |

## 16.2 Achievement Rules

- Achievement conditions must be configurable.
- Awarding must be idempotent.
- Users must not receive the same unique achievement multiple times.
- Season-specific achievements must reference the applicable season.
- Achievement history must be preserved.

---

# 17. NOTIFICATION SYSTEM

## 17.1 MVP Notifications

The system must support:

- Activity Completed.
- XP Earned.
- RP Earned.
- Rank Promotion.
- Level Up.
- Achievement Unlocked.
- Verification Pending.
- Verification Rejected.
- Appeal Resolved.
- Season Ending.
- Season Results.

## 17.2 Notification Interface

Users must be able to:

- View notifications.
- Mark notifications as read.
- View unread count.
- Open the relevant application page.

## 17.3 Future Notifications

Future enhancements may include:

- Email reminders.
- Browser push notifications.
- Scheduled activity reminders.

These must not be required for MVP completion.

---

# 18. ADMINISTRATIVE MANAGEMENT

## 18.1 Administrator Dashboard

The dashboard must display:

- Total Registered Users.
- Daily Active Users.
- Completed Activities.
- Total Awarded XP.
- Total Confirmed RP.
- Pending Verifications.
- High-Risk Submissions.
- Pending Appeals.
- Current Season.
- Season Remaining Days.
- Recent Administrative Activity.

## 18.2 Activity Management

Administrators must be able to:

- Create activities.
- Edit activities.
- Deactivate activities.
- Create sessions.
- Configure repeat limits.
- Configure verification methods.
- Configure reward allocation.
- Assign reward groups.
- Schedule configuration changes.

## 18.3 Reward Configuration

All competitive point configuration changes must be versioned.

Future configurations must not silently modify an active season.

## 18.4 Review Queue

Administrators must be able to:

- Filter submissions.
- View evidence within authorized permissions.
- Review risk reasons.
- Approve.
- Reject.
- Request additional evidence.
- Record decision notes.

## 18.5 Administrative Audit

Every sensitive administrative action must record:

- Administrator ID.
- Action.
- Target Record.
- Before Value.
- After Value.
- Reason.
- Timestamp.

---

# 19. DATABASE ARCHITECTURE

## 19.1 Authentication Schema

Use the database structure installed by CodeIgniter Shield.

Do not create duplicate password or authentication identity tables.

Application-specific user information must be stored separately.

## 19.2 Required Domain Tables

### Identity and Profiles

**user_profiles**
- id
- user_id
- display_name
- avatar_path
- bio
- timezone
- leaderboard_visible
- created_at
- updated_at

### Activities

**activity_categories**
- id
- category_code
- name
- icon
- sort_order
- is_active

**reward_groups**
- id
- group_code
- name
- description
- is_active

**activities**
- id
- category_id
- reward_group_id
- activity_code
- name
- description
- activity_type
- repeat_type
- verification_policy_id
- default_xp
- default_rp
- minimum_duration_seconds
- cooldown_seconds
- maximum_daily_completions
- maximum_weekly_completions
- is_active
- created_at
- updated_at

**activity_sessions**
- id
- activity_id
- session_code
- session_name
- scheduled_start
- scheduled_end
- xp_value
- rp_value
- sort_order
- is_active

**user_activity_plans**
- id
- user_id
- activity_id
- recurrence_rule
- timezone
- is_enabled
- created_at
- updated_at

**daily_activity_instances**
- id
- user_id
- activity_id
- activity_session_id
- occurrence_number
- activity_date
- competition_date
- season_id
- configuration_version_id
- completion_status
- verification_status
- reward_status
- scheduled_at
- completed_at
- created_at
- updated_at

### Verification

**verification_policies**
- id
- policy_code
- verification_methods
- requires_evidence
- requires_approval
- minimum_duration_seconds
- is_active

**activity_evidence**
- id
- activity_instance_id
- evidence_type
- private_storage_key
- file_hash
- file_size
- mime_type
- uploaded_at
- expires_at
- deleted_at

**timer_sessions**
- id
- user_id
- activity_instance_id
- started_at
- finished_at
- elapsed_seconds
- status

**activity_verification_logs**
- id
- activity_instance_id
- verification_method
- decision
- risk_score
- reason_code
- reviewed_by
- reviewed_at
- created_at

**anti_cheat_flags**
- id
- user_id
- activity_instance_id
- flag_type
- severity
- score
- reason_code
- review_status
- created_at

**verification_appeals**
- id
- user_id
- activity_instance_id
- appeal_reason
- status
- reviewed_by
- resolution_reason
- created_at
- resolved_at

### Rewards

**activity_configuration_versions**
- id
- activity_id
- version_number
- configuration_snapshot
- effective_season_id
- created_by
- created_at

**season_reward_group_budgets**
- id
- season_id
- reward_group_id
- daily_rp_budget
- configuration_version

**daily_reward_budgets**
- id
- user_id
- competition_date
- season_id
- reward_group_id
- awarded_rp
- updated_at

**daily_point_totals**
- id
- user_id
- activity_date
- competition_date
- awarded_xp
- awarded_rp
- updated_at

The database design must allow separate daily cap keys for XP and RP when user and competition dates differ.

Separate XP and RP aggregate tables may be used if required to enforce their respective unique date boundaries correctly.

**point_transactions**
- id
- user_id
- season_id
- activity_instance_id
- reward_group_id
- transaction_type
- nominal_xp
- nominal_rp
- xp_delta
- rp_delta
- idempotency_key
- created_at

**user_lifetime_stats**
- user_id
- total_xp
- lifetime_level
- total_completions
- longest_streak
- updated_at

### Competition

**ranks**
- id
- rank_code
- rank_name
- default_min_rp
- default_max_rp
- display_order
- icon_path

**seasons**
- id
- season_number
- season_name
- starts_at
- ends_at
- status
- submissions_closed_at
- finalized_at

**season_rank_configurations**
- id
- season_id
- rank_id
- minimum_rp
- maximum_rp

**user_season_stats**
- id
- user_id
- season_id
- confirmed_rp
- completed_count
- current_rank_id
- last_rp_reached_at
- updated_at

**season_results**
- id
- season_id
- user_id
- final_rp
- final_rank_id
- final_position
- finalized_at

### Progression

**daily_streaks**
- id
- user_id
- activity_date
- target_weight
- completion_percentage
- eligible
- bonus_awarded
- current_streak

**achievements**
- id
- achievement_code
- name
- description
- criteria_json
- icon_path

**user_achievements**
- id
- user_id
- achievement_id
- season_id
- awarded_at

### System

**notifications**
- id
- user_id
- notification_type
- title
- message
- read_at
- created_at

**admin_audit_logs**
- id
- administrator_id
- action
- target_type
- target_id
- previous_values
- new_values
- reason
- created_at

**system_settings**
- id
- setting_key
- setting_value
- updated_at

**scheduled_job_runs**
- id
- job_name
- execution_key
- status
- started_at
- finished_at
- error_message

## 19.3 Database Constraints

Required constraints include:

- Unique activity codes.
- Unique reward group codes.
- Unique season numbers.
- Unique user/season statistics.
- Unique user/season results.
- Unique point transaction idempotency keys.
- Unique user/activity/date/session for once-per-session activities.
- Unique occurrence identities for repeatable activities.
- Unique user/competition-date/group reward aggregates.
- Foreign keys for related records.
- Indexed leaderboard columns.
- Indexed verification status.
- Indexed review status.
- Indexed evidence expiration.
- Indexed transaction timestamps.

The detailed database document must specify exact keys, data types, nullability, and referential actions.

## 19.4 Database Principles

- Use migrations.
- Use seeders.
- Use transactions.
- Use row-level locking where required.
- Use prepared or parameterized queries.
- Avoid N+1 queries.
- Never store plaintext passwords.
- Never trust point values received from the browser.
- Preserve point ledger history.
- Keep season results immutable after finalization.

---

# 20. APPLICATION ARCHITECTURE

## 20.1 Architectural Style

Use CodeIgniter MVC with a dedicated service layer.

## 20.2 Presentation Layer

Responsibilities:

- HTML rendering.
- Bootstrap components.
- Client-side interactions.
- Form display.
- User feedback.
- Charts.
- Responsive layouts.

Business rules must not be implemented exclusively in JavaScript.

## 20.3 Controller Layer

Responsibilities:

- Request handling.
- Input validation.
- Authentication checks.
- Authorization checks.
- Service invocation.
- Response formatting.

Controllers must remain focused and maintainable.

## 20.4 Service Layer

Required services:

- ActivityService.
- ActivityScheduleService.
- ChecklistService.
- TimerService.
- EvidenceService.
- VerificationService.
- RiskScoringService.
- AntiCheatService.
- PointsService.
- RewardBudgetService.
- RankingService.
- LifetimeLevelService.
- LeaderboardService.
- SeasonService.
- StreakService.
- AchievementService.
- NotificationService.
- AdminReviewService.
- ReportingService.

Services may be refined during architecture review without changing approved business rules.

## 20.5 Data Access Layer

Use:

- CodeIgniter Models.
- Query Builder.
- Entities where appropriate.
- Migrations.
- Seeders.
- Explicit transactional boundaries.

## 20.6 Background Processing

Background processing must support:

- Season transitions.
- Season finalization.
- Achievement reconciliation.
- Streak reconciliation.
- Evidence cleanup.
- Notification delivery.
- Review monitoring.
- Aggregate reconciliation.

All scheduled operations must be idempotent.

---

# 21. API REQUIREMENTS

## 21.1 Authentication

Use supported CodeIgniter Shield authentication routes.

## 21.2 Dashboard

- GET `/dashboard`
- GET `/api/me/summary`
- GET `/api/me/statistics`

## 21.3 Activities

- GET `/api/activities`
- GET `/api/activities/today`
- GET `/api/activities/history`
- POST `/api/activities/{id}/complete`

## 21.4 Timer

- POST `/api/activities/{id}/timer/start`
- POST `/api/activities/{id}/timer/finish`

## 21.5 Evidence

- POST `/api/activities/{id}/evidence`
- GET `/api/evidence/{id}/status`

## 21.6 Ranking

- GET `/api/ranks`
- GET `/api/me/rank`
- GET `/api/leaderboard`
- GET `/api/leaderboard/me`

## 21.7 Seasons

- GET `/api/seasons/current`
- GET `/api/seasons/history`
- GET `/api/seasons/{id}/results`

## 21.8 Achievements

- GET `/api/me/achievements`

## 21.9 Appeals

- POST `/api/verifications/{id}/appeal`
- GET `/api/me/appeals`

## 21.10 Administration

Provide protected endpoints for:

- Activities.
- Categories.
- Reward Groups.
- Verification Policies.
- Season Configurations.
- Rank Configurations.
- Review Queue.
- Appeals.
- Users.
- Reports.
- Audit Logs.

All non-public endpoints require authentication and appropriate authorization.

The final route definitions must be documented in ARCHITECTURE.md.

---

# 22. USER INTERFACE AND EXPERIENCE

## 22.1 Design Direction

LifeQuest must use a modern, polished, dark gaming interface.

Visual characteristics:

- Dark navy backgrounds.
- Charcoal surfaces.
- Electric blue primary accents.
- Purple secondary accents.
- Gold rank highlights.
- Rounded cards.
- High-contrast typography.
- Smooth, restrained animations.
- Responsive components.
- Clear progress indicators.

## 22.2 Application Language

All user-facing default interface labels, messages, navigation items, and notifications must use Indonesian.

Technical documentation, source identifiers, database identifiers, and code comments must use English.

Examples:

- Dashboard → Beranda.
- Daily Checklist → Checklist Harian.
- Achievements → Pencapaian.
- Leaderboard → Peringkat.
- Season → Musim Kompetisi.
- Verification Pending → Menunggu Verifikasi.
- Activity Completed → Aktivitas Selesai.

Brand names, rank names, XP, and RP may remain in English.

The application must support a future localization mechanism.

## 22.3 Main User Pages

1. Landing Page.
2. Login.
3. Registration.
4. Forgot Password.
5. User Onboarding.
6. Dashboard.
7. Daily Checklist.
8. My Activities.
9. Activity Schedule.
10. Ranking.
11. Leaderboard.
12. Achievements.
13. Season Overview.
14. Statistics.
15. Verification History.
16. Appeals.
17. User Profile.
18. Settings.

## 22.4 Dashboard Components

Required components:

- User Greeting.
- User Avatar.
- Current Date.
- Current Rank.
- Rank Emblem.
- Rank Progress.
- Lifetime Level.
- Lifetime XP.
- Current Season RP.
- Season Countdown.
- Daily Progress.
- Activity Checklist.
- Streak Counter.
- Pending Verification Summary.
- Leaderboard Preview.
- Recent Achievements.
- Weekly Performance Chart.

## 22.5 Checklist Interaction

The checklist must provide:

- Activity name.
- Category.
- Schedule.
- XP reward.
- RP reward.
- Verification method.
- Completion state.
- Start Timer where applicable.
- Upload Evidence where applicable.
- Complete action.
- Pending indicator.
- Success or error notification.

Do not permanently update displayed points before server confirmation.

## 22.6 Mobile Requirements

The application must support:

- Responsive cards.
- Touch-friendly controls.
- Mobile navigation.
- Flexible tables.
- Readable typography.
- Compact charts.
- Accessible dialogs.
- Comfortable checkbox interactions.

---

# 23. SECURITY REQUIREMENTS

## 23.1 Authentication and Authorization

- Use CodeIgniter Shield.
- Enforce role-based permissions.
- Validate ownership of all user resources.
- Apply login rate limiting.
- Use secure session configuration.
- Use HTTPS in production.

## 23.2 Input Security

- Validate all inputs server-side.
- Escape untrusted output.
- Use parameterized database queries.
- Enable CSRF protection as appropriate.
- Reject unauthorized resource changes.
- Validate uploaded files.

## 23.3 File Upload Security

Evidence storage must include:

- Private storage locations.
- MIME and content validation.
- File size restrictions.
- Random filenames.
- Controlled downloads.
- Restricted review access.
- Retention cleanup.
- Logging of sensitive access when appropriate.

## 23.4 Secrets Management

- Use environment variables.
- Never hardcode credentials.
- Never commit production secrets.
- Restrict database permissions.
- Disable debug mode in production.

## 23.5 Reward Security

- Calculate points on the server.
- Enforce transaction idempotency.
- Prevent duplicate rewards.
- Prevent client-side reward manipulation.
- Protect season transitions.
- Audit point corrections.

## 23.6 Dependency Security

Before deployment:

- Review framework security updates.
- Review third-party package advisories.
- Run dependency checks.
- Resolve critical vulnerabilities.
- Document remaining risks.

---

# 24. PRIVACY AND DATA RETENTION

## 24.1 Privacy Principles

The application must follow:

- Data minimization.
- Purpose limitation.
- Restricted access.
- Transparent processing.
- User privacy preferences.
- Configurable retention periods.

## 24.2 Evidence Retention

Initial policy:

- Standard evidence: delete within 30 days after verification completion.
- Disputed evidence: retain only as long as necessary to resolve the dispute under a documented policy.
- Administrative logs: follow the approved retention policy.

Retention jobs must be logged.

## 24.3 Sensitive Activity Protection

Do not require intrusive photo evidence for:

- Bathing.
- Personal hygiene involving intimate details.
- Sleeping.
- Private family interactions.
- Other sensitive personal routines.

Verification requirements must be proportional to the activity.

## 24.4 Data Access

Users may view their own records.

Administrators may access evidence only where required by assigned permissions.

Private evidence must never appear on the public leaderboard.

---

# 25. PERFORMANCE AND SCALABILITY

## 25.1 Initial Capacity Target

The initial system must be designed and tested for:

- At least 1,000 Daily Active Users.
- An average of 10 activity submissions per active user per day.
- Approximately 10,000 activity submissions per day.

The architecture must permit future expansion to 10,000 Daily Active Users.

## 25.2 Response Targets

Initial performance goals:

- Dashboard: under 2 seconds.
- Checklist API: under 500 milliseconds.
- Leaderboard: under 2 seconds for paginated queries.

These are targets to validate under a documented workload and test environment, not guaranteed performance claims.

## 25.3 Performance Practices

- Appropriate database indexes.
- Pagination.
- Efficient query design.
- Aggregate statistics.
- Optional caching.
- Background processing for expensive tasks.
- Connection management.
- Query monitoring.

## 25.4 Operational Monitoring

The administrator interface must expose:

- Failed scheduled jobs.
- Pending verification counts.
- Review queue age.
- Failed reward transactions.
- System errors.
- Database connectivity issues.
- Season transition status.

---

# 26. FUNCTIONAL USER FLOWS

## 26.1 Registration Flow

Landing Page → Registration → Account Validation → Account Creation → Email Verification if enabled → Login → Onboarding → Routine Setup → Dashboard.

## 26.2 Daily Checklist Flow

Login → Dashboard → Daily Checklist → Select Activity → Complete Requirements → Server Validation → Verification → Reward Processing → Dashboard Update.

## 26.3 Timer Flow

Select Activity → Start Timer → Server Records Start → Timer Runs → Finish Timer → Server Validates Duration → Verification → Reward Processing.

## 26.4 Evidence Flow

Select Activity → Upload Evidence → File Validation → Evidence Storage → Risk Evaluation → Automatic Decision or Administrator Queue → Reward Processing.

## 26.5 Season Flow

Active Season → Submission Cutoff → Next Season Activation → Previous Season Verification Grace Period → Previous Season Finalization → Historical Results.

## 26.6 Appeal Flow

Rejected Verification → User Submits Appeal → Review Queue → Administrator Decision → User Notification → Audit Record.

---

# 27. ACCEPTANCE CRITERIA

## AC-001 — Standard Completion

Given an authenticated user has an eligible daily activity, when the user completes the activity successfully, the system must store the completion and award the correct eligible points.

## AC-002 — Duplicate Request

Given an activity completion has already generated a reward, when the same completion request is submitted again, no additional points may be awarded.

## AC-003 — Repeatable Sessions

Given a user has separate morning and evening sessions, both sessions may award points independently within the shared reward group budget.

## AC-004 — Reward Group Limit

Given a reward group has reached its daily RP budget, additional activities in that group must not award more RP.

## AC-005 — Maximum Daily RP

A user must never exceed 140 confirmed RP for the same competition date.

## AC-006 — Maximum Daily XP

A user must never exceed 150 XP for the same local activity date.

## AC-007 — Custom Activities

User-created custom activities must not award competitive RP.

## AC-008 — Self-Check Validation

Eligible low-risk self-check activities must be processed without mandatory administrator review.

## AC-009 — Timer Validation

The backend must calculate elapsed time independently of the client device clock.

## AC-010 — Photo Evidence

Unauthorized users must not access another user's evidence files.

## AC-011 — Duplicate Evidence

Repeated use of identical evidence must be detectable and evaluated according to verification policy.

## AC-012 — Risk Scoring

Risk scoring must produce a valid score and explainable reason codes.

## AC-013 — High-Risk Submission

High-risk submissions requiring review must not immediately increase confirmed RP.

## AC-014 — Pending Rewards

Pending submissions must not appear as confirmed points on the leaderboard.

## AC-015 — Administrator Review

Authorized administrators must be able to approve, reject, or request more information, with an auditable decision.

## AC-016 — Appeals

Users must be able to appeal eligible rejected verification outcomes.

## AC-017 — Rank Promotion

A user moving from 690 RP to 710 confirmed RP must reach Master according to the configured thresholds.

## AC-018 — Leaderboard Ordering

Leaderboard ordering must use confirmed RP and the documented tie-breaking rules.

## AC-019 — Season Duration

Every season must cover exactly 60 competition calendar days.

## AC-020 — Season Reset

When a new season begins, new-season RP must start at zero while lifetime XP remains unchanged.

## AC-021 — Season History

Historical season results must remain available after reset.

## AC-022 — Pending Season Submissions

Eligible pre-cutoff submissions must follow the defined review grace period.

## AC-023 — Season Finalization

Repeated finalization attempts must not duplicate results or rewards.

## AC-024 — Concurrent Completion

Two simultaneous requests for the same activity occurrence must create at most one successful reward transaction.

## AC-025 — Transaction Rollback

When reward processing fails, all related database changes must roll back atomically.

## AC-026 — Administrator Configuration

Activity and point configuration changes must respect approved effective-season rules.

## AC-027 — Reward Reversal

Corrections must use compensating transactions and preserve the original reward history.

## AC-028 — Authorization

Ordinary users must not access administrator-only operations.

## AC-029 — Privacy

Private evidence, schedules, and user information must not be disclosed through public endpoints.

## AC-030 — Evidence Retention

Expired evidence must be deleted according to the configured retention policy.

## AC-031 — Streak Calculation

Streak rewards must be granted no more than once per eligible day.

## AC-032 — Accessibility

Core features must support keyboard interaction, readable contrast, and responsive layouts.

## AC-033 — Performance

The system must undergo workload testing against the specified initial capacity targets.

## AC-034 — Auditability

Every administrator correction and verification decision must be traceable.

## AC-035 — Timezone Boundaries

Activity dates, RP competition dates, and season timestamps must remain consistent across timezone conversions.

## AC-036 — Configuration Versioning

Historical rewards must retain their original applicable point configuration.

## AC-037 — Season Concurrency

A new season may become active while the previous season remains in finalization, without allocating points to the wrong season.

## AC-038 — Pending Reward Budget

Multiple pending approvals for the same user and day must never cause reward budgets or daily point caps to be exceeded.

## AC-039 — Daily Target Fairness

Users must not receive competitive streak rewards by configuring an invalid or trivially small activity plan.

## AC-040 — Production Security

No unresolved critical or high-severity security vulnerabilities may remain in the approved production release.

---

# 28. TESTING STRATEGY

## 28.1 Unit Testing

Required coverage:

- XP calculation.
- RP calculation.
- Reward group budgets.
- Lifetime level calculation.
- Rank determination.
- Streak eligibility.
- Risk scoring.
- Season date calculation.
- Activity repeat validation.

## 28.2 Integration Testing

Required scenarios:

- Activity completion to reward ledger.
- Timer completion to verification.
- Evidence submission to review.
- Approved verification to confirmed RP.
- Rejected verification to zero competitive reward.
- Season closure and finalization.
- Appeal processing.
- Administrative corrections.

## 28.3 Concurrency Testing

Required scenarios:

- Duplicate button clicks.
- Multiple simultaneous completion requests.
- Concurrent point awards.
- Concurrent administrator approvals.
- Concurrent season scheduler executions.
- Concurrent reward budget updates.

## 28.4 Security Testing

Required coverage:

- Authentication.
- Authorization.
- CSRF.
- XSS.
- SQL injection prevention.
- File upload validation.
- Insecure direct object references.
- Session security.
- Rate limiting.
- Sensitive information exposure.

## 28.5 Performance Testing

Test at the defined initial workload.

Measure:

- Request latency.
- Database query performance.
- Reward transaction throughput.
- Leaderboard query latency.
- Scheduler performance.
- Error rates.

## 28.6 User Interface Testing

Verify:

- Desktop browsers.
- Mobile browsers.
- Responsive layouts.
- Checklist feedback.
- Loading indicators.
- Error states.
- Rank visualization.
- Achievement notifications.

---

# 29. IMPLEMENTATION ROADMAP

## Phase 0 — Requirements and Architecture

Deliverables:

- PRD review.
- Compatibility verification.
- Architecture document.
- Database design.
- Development plan.
- Task breakdown.
- Testing plan.

No application source code development.

## Phase 1 — Application Foundation

Tasks:

- Initialize CodeIgniter.
- Configure MySQL.
- Configure environment.
- Install Shield.
- Configure authentication.
- Initialize Git.
- Create base layouts.
- Create initial migrations.

## Phase 2 — Activity Management

Tasks:

- Activity categories.
- Reward groups.
- Activity catalog.
- User routines.
- Activity sessions.
- Repeatable activities.
- Daily checklist.

## Phase 3 — Reward Engine

Tasks:

- XP calculation.
- RP calculation.
- Daily caps.
- Reward group allocation.
- Point ledger.
- Idempotency.
- Transaction locking.

## Phase 4 — Verification Engine

Tasks:

- Self-check verification.
- Timer processing.
- Evidence upload.
- Verification policies.
- Risk scoring.
- Anti-cheat flags.
- Administrator review.
- Appeals.

## Phase 5 — Gamification

Tasks:

- Competitive ranks.
- Lifetime levels.
- Streak calculations.
- Achievements.
- Badge management.
- Reward notifications.

## Phase 6 — Competition

Tasks:

- Leaderboards.
- Season configurations.
- Season scheduling.
- Season transitions.
- Pending verification cutoff.
- Finalization.
- Historical results.

## Phase 7 — Administration and Analytics

Tasks:

- Administrator dashboard.
- User management.
- Activity configuration.
- Verification monitoring.
- Point reporting.
- Performance statistics.
- Audit log interface.

## Phase 8 — Quality Assurance and Deployment

Tasks:

- Unit tests.
- Integration tests.
- Security review.
- Concurrency tests.
- Performance tests.
- User interface tests.
- Backup and restore tests.
- Production configuration.
- Release documentation.

Each phase must be validated before it is considered complete.

---

# 30. OPENCODE DEVELOPMENT RULES

The coding agent must follow these requirements:

1. Read the complete PRD before implementation.
2. Treat this document as the product requirements baseline.
3. Do not reinterpret the application as a traditional RPG quest system.
4. Follow the specified technology stack.
5. Do not change frameworks without approval.
6. Do not start all modules simultaneously.
7. Create architectural documentation first.
8. Create database documentation first.
9. Create a development task breakdown.
10. Use CodeIgniter MVC conventions.
11. Separate controllers, services, and models.
12. Keep business logic on the server.
13. Never trust client-supplied reward amounts.
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
28. Maintain task completion status.
29. Record relevant test results.
30. Do not claim success without verification.
31. Do not create production secrets in source code.
32. Use English for technical documentation.
33. Use Indonesian for default user-facing interface text.
34. Request approval for destructive operations.
35. Report implementation conflicts rather than silently changing requirements.

---

# 31. REQUIRED PROJECT DOCUMENTATION

OpenCode must create and maintain the following files.

## PRD.md

Contains the approved product requirements.

## ARCHITECTURE.md

Contains:

- System architecture.
- Service responsibilities.
- Controller structure.
- Background processing.
- Verification architecture.
- Security boundaries.

## DATABASE.md

Contains:

- Entity relationships.
- Table specifications.
- Primary keys.
- Foreign keys.
- Unique constraints.
- Indexes.
- Transaction boundaries.
- Migration strategy.

## DEVELOPMENT_PLAN.md

Contains:

- Development phases.
- Dependencies.
- Milestones.
- Implementation sequence.

## TASKS.md

Contains:

- Granular tasks.
- Task dependencies.
- Task status.
- Acceptance criteria.
- Test results.

## TESTING.md

Contains:

- Unit test scenarios.
- Integration test scenarios.
- Concurrency tests.
- Security tests.
- Performance tests.
- Release validation.

## AGENTS.md

Contains:

- Coding conventions.
- Development commands.
- Project structure.
- Testing commands.
- Security rules.
- Project-specific agent instructions.

Documentation must remain consistent with the approved PRD.

---

# 32. DEFINITION OF DONE

A feature is complete only when:

1. Approved requirements are implemented.
2. Backend validation is operational.
3. Authentication and authorization are enforced.
4. Required database migrations exist.
5. User interface behavior is correct.
6. Relevant unit tests pass.
7. Relevant integration tests pass.
8. Applicable concurrency tests pass.
9. Relevant security checks pass.
10. Error handling is implemented.
11. Audit requirements are satisfied.
12. Documentation is updated.
13. Acceptance criteria are satisfied.
14. No unresolved critical or high-severity defects remain.
15. The task status is updated in TASKS.md.

---

# 33. PRODUCT GOVERNANCE

## 33.1 Requirements Baseline

This document represents the approved product requirements baseline for LifeQuest V2.1.0.

Previous PRD versions are superseded.

## 33.2 Change Management

Any change affecting:

- Competitive points.
- Rank thresholds.
- Reward budgets.
- Verification requirements.
- Season rules.
- Privacy policies.
- Database integrity.
- Application architecture.

Must be documented before implementation.

## 33.3 Conflict Resolution

If another project document conflicts with this PRD:

1. Identify the conflict.
2. Explain its impact.
3. Propose a resolution.
4. Obtain approval when business behavior changes.
5. Update the affected documentation consistently.

Do not silently override the PRD.

## 33.4 Technical Design Flexibility

The development agent may refine:

- Class names.
- Internal service boundaries.
- Database data types.
- Index structures.
- Internal API implementations.
- Background job implementation.

Such refinements must preserve approved functional behavior, security requirements, and acceptance criteria.

---

# 34. FINAL APPROVED PRODUCT DECISIONS

The following product decisions are approved:

1. The application name is LifeQuest.
2. The core experience is daily activity tracking.
3. Activities span the user's complete daily routine.
4. The application uses XP and RP.
5. Lifetime XP persists across seasons.
6. Competitive RP resets each season.
7. Maximum daily XP is 150.
8. Maximum daily RP is 140.
9. Standard activities use reward group budgets.
10. Administrators may create additional activities.
11. Administrators may configure repeatable sessions.
12. Repeated activities must not bypass point budgets.
13. Custom user activities cannot generate competitive RP.
14. Verification is automated by default.
15. Self-check is allowed for ordinary personal routines.
16. Timers may be used for duration-based activities.
17. Photo evidence is optional or policy-dependent.
18. Intrusive evidence is prohibited for sensitive routines.
19. Risk scoring is rule-based in the MVP.
20. High-risk submissions may require administrator review.
21. Administrator review is not required for every checklist.
22. Users may appeal rejected verification decisions.
23. Only confirmed RP contributes to competitive ranking.
24. The ranking system contains ten tiers.
25. Leaderboards are season-based.
26. Each season lasts 60 calendar days.
27. Historical season results are preserved.
28. Lifetime levels are XP-based.
29. Streak bonuses are limited to one per eligible day.
30. The default application language is Indonesian.
31. Technical documentation uses English.
32. The application uses PHP and CodeIgniter.
33. MySQL is the primary database.
34. Bootstrap is the frontend framework.
35. Development follows a phased PRD-driven methodology.
36. OpenCode must complete planning before implementation.
37. Security and data integrity take priority over convenience.
38. The application must support scalable activity processing.

---

# 35. FINAL DEVELOPMENT DIRECTIVE

This PRD fully replaces LifeQuest V1.0.0 and V2.0.0.

The development agent must:

1. Read the complete document.
2. Validate the technology requirements.
3. Review previously generated development plans.
4. Remove outdated or conflicting assumptions.
5. Create an application architecture.
6. Create a detailed database design.
7. Define transactional reward processing.
8. Define automated verification workflows.
9. Define anti-cheat and administrator review workflows.
10. Define season scheduling and finalization.
11. Create implementation phases.
12. Create granular development tasks.
13. Create measurable testing criteria.
14. Present the completed plan for review.
15. Wait for explicit implementation approval.

The implementation must prioritize:

- Correct business rules.
- Secure authentication.
- Reliable database transactions.
- Duplicate reward prevention.
- Fair competition.
- User privacy.
- Automated verification.
- Scalable system design.
- Maintainable source code.
- Comprehensive testing.

**No application implementation may begin until the planning deliverables have been reviewed and approved.**

---

# END OF PRD — LIFEQUEST V2.1.0