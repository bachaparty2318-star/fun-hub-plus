# Fan Hub database

XAMPP: MySQL/MariaDB, localhost:3306, db_fanhub, root, empty password.
Run php artisan migrate --seed. Never use migrate:fresh on valuable data.

## SRS review
Reviewed Fan Hub Plus SRS v1.0, pages 7-10 and 14.
Page 14 provides example entities, not a mandatory table layout.
Final schema: 20 application tables, 8 Laravel/support tables (including migrations), 3 views.
Table count alone does not determine performance. Media stays in files/URLs, not database blobs.

### Simplified
- Removed empty user_sessions: Laravel sessions already stores sessions.
- Removed empty password_resets: Laravel password_reset_tokens already supports reset tokens.
- Removed empty email_verifications and redundant users.is_email_verified: use Laravel signed verification links and email_verified_at.
- Removed empty usage_statistics: compute active users and popular categories from sessions, user_activity_log, content and bookmarks when building the admin dashboard. No historical daily snapshot requirement exists in the SRS.
- Removed empty chatbot_queries and chatbot_faq: chatbot is explicitly optional (page 8). Chat history/FAQ and interaction volume are deferred with that feature.
- Removed the optional chatbot activity enum value from the final activity log schema.
- Kept cache and queue tables because the application uses database drivers for them.
- Kept the three views: they store query definitions, not duplicate data.
- Kept galleries, timelines, media tags, moderation, favorites, notes, events and recent activity because they support requested features.

### Added missing support
- user_profiles.favorite_fandoms: JSON list of fandom names, distinct from category interests in user_favorite_categories.
- fandom_name on merchandise_items, content and articles for fandom grouping/filtering.
- genre, release_date and popularity_score on articles and character_profiles for explorer filters.
- content.type supports animated_explainer and release. Upcoming releases use future release_date plus category; no extra release table is needed.

### Implementation boundaries
Admin JSON backend is implemented; see ../docs/ADMIN_BACKEND.md. Registered-user JSON backend is implemented; see ../docs/REGISTERED_USER_BACKEND.md. Visitor JSON backend is implemented; see ../docs/VISITOR_BACKEND.md. Visual frontend screens remain separate application work.
Articles are stored in articles; characters in character_profiles; general curated entries and media parents in content.
The registered-user explorer combines these sources with an application UNION-style query; do not duplicate articles into content.
Media tagging uses media.content_id -> content_tags -> tags.
Bookings/purchases/payments are outside SRS scope and have no tables.
Sharing, breadcrumbs, GPS permission and loading spinners need application/UI logic, not extra tables.
Polymorphic bookmarks are validated and cleaned up by the application layer.
Gallery URLs and article HTML are validated/sanitized by the backend before use.
Email verification uses email_verified_at as its single source of truth.
The User model uses user_id and password_hash; a random hashed admin password was seeded.
Set a chosen admin password before building login. Seeders preserve existing account credentials.

### Migration safety
Historical migrations remain unchanged; 2026_09_24_000027_simplify_schema_for_srs applies the final layout.
Both existing installations and new installations run through the same history.
The migration refuses to remove any of its six obsolete tables if they contain records.
A local pre-change SQL backup is saved in storage/app/private/database-backups.
Rollback refuses to remove populated new fields or new content types.
Business content is initially empty; the seed supplies eight categories and one admin profile.
## Admin backend update
Migration 2026_09_24_000028 links approved fan submissions to their published articles. Admin approval now publishes sanitized content atomically. Database still has 28 tables and 3 views. See [Admin backend guide](../docs/ADMIN_BACKEND.md) for endpoints and tests.

## Registered-user backend update
Registered-user routes now cover account auth, email verification, profile, dashboard, explorer, events, bookmarks, ratings, submissions and feedback without adding new tables. See [Registered user backend guide](../docs/REGISTERED_USER_BACKEND.md). The combined backend suite passes with 53 tests and 380 assertions.

## Visitor backend update
Visitor routes now cover public SRS flows: browse categories/tags, explore/filter public content, view catalog details, upcoming releases, event discovery, feedback, registration and account recovery. They reuse public-safe services and add no tables. See [Visitor backend guide](../docs/VISITOR_BACKEND.md).
