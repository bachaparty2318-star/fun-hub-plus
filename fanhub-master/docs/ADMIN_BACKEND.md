# Admin backend guide

Implemented for Fan Hub Plus on Laravel, using the existing db_fanhub database.

## What is ready

- 18 Eloquent models including User, with the existing primary keys, timestamps, casts and relationships. The two many-to-many tables are handled by belongsToMany relationships.
- Seven admin controllers: authentication, users, resources, submissions, feedback, dashboard and uploads.
- Admin-only session authentication, active-account checks, CSRF protection, login/API throttles and JSON errors.
- CRUD for categories, tags, content, media, characters, articles, article images, article timeline events, merchandise, merchandise images and events.
- Search, pagination, allowed sorting and resource-specific filtering.
- User create/edit/delete, activation/deactivation, role assignment, profile preferences and favorite categories/fandoms.
- Self-deletion/deactivation/demotion prevention; protection for the last active administrator; session revocation after account/password changes.
- Pending submission review: approval creates one sanitized published article and links it to the submission; repeated review returns 409. Rejection publishes nothing.
- Feedback status management, including resolution and reopening.
- Dashboard totals, recent activity, active users, category popularity and media ratings.
- Image/audio/video uploads, safe URL checks and rich-text HTML sanitization.
- A migration adding fan_submissions.published_article_id. Applied locally without recreating the database or changing its 28-table count.
- Existing idempotent seeder supplies eight categories and one admin account/profile. It preserves existing passwords. No fabricated content was inserted.

## Local URL and administrator credentials

Base API URL: http://localhost/fanhub/public/admin/api

Email: admin@fanhubplus.com

The original seeder assigned an unknown random password. Set your own password using the hidden terminal prompts:

    & C:/xampp/php/php.exe artisan admin:set-password

The password must have at least 12 characters, uppercase/lowercase letters and a number. The command revokes old sessions and reset tokens. It does not print or store a plain-text password in the project.

For a new local installation, create db_fanhub, configure .env, then run:

    & C:/xampp/php/php.exe artisan migrate --seed
    & C:/xampp/php/php.exe artisan storage:link
    & C:/xampp/php/php.exe artisan admin:set-password

APP_URL is configured for the XAMPP subfolder above. Change it if hosting under a virtual host or artisan serve.

## Authentication flow

These endpoints use Laravel's web/session middleware, not bearer tokens. Keep cookies between requests and send Accept: application/json.

1. GET /auth/csrf. Save csrf_token and the session cookie.
2. POST /auth/login with email/password and X-CSRF-TOKEN.
3. Use the new csrf_token returned after login for subsequent mutations.
4. GET /auth/me returns the authenticated admin and profile.
5. POST /auth/logout ends the session. Obtain a fresh CSRF token before logging in again.

All POST, PUT, PATCH and DELETE requests need the session cookie and X-CSRF-TOKEN header. Login does too. A frontend should use same-origin requests with credentials.

PUT /auth/password accepts current_password, password and password_confirmation. Save its returned csrf_token.

POST /auth/forgot-password accepts email and always returns a generic response.
POST /auth/reset-password accepts email, token, password and password_confirmation.
GET /auth/reset-password/{token}?email=... is a JSON handoff for the future reset-password screen.
Recovery is limited to active admin accounts. Tokens expire after 60 minutes and are single-use.

MAIL_MAILER is currently log: reset notifications are written to storage/logs/laravel.log. Configure SMTP before expecting real delivered emails.

## Resource CRUD

For each resource below:

| Method | Path | Purpose |
| --- | --- | --- |
| GET | /{resource} | Paginated list |
| POST | /{resource} | Create |
| GET | /{resource}/{id} | Detail |
| PUT or PATCH | /{resource}/{id} | Partial update |
| DELETE | /{resource}/{id} | Delete |

| Resource | Required creation fields |
| --- | --- |
| categories | name, slug |
| tags | tag_name |
| content | category_id, title, type |
| media | content_id, media_type, media_url |
| characters | category_id, name |
| articles | category_id, title, body_html |
| article-images | article_id, image_url |
| article-timeline-events | article_id, event_label |
| merchandise | category_id, name |
| merchandise-images | item_id, image_url |
| events | title, event_type, event_date, city |

Optional fields match the database where supported. The exact validation allowlist is in app/Services/AdminResources.php.

Common list parameters: q, page, per_page (1-100), sort and direction (asc/desc). Unknown sort fields return 422.
Content, articles, characters and merchandise support release_year.
Category/fandom/genre/type filters are available where relevant.
Media can be filtered by content_id/media_type; gallery/timeline rows by their parent ID; events by city/event_type/category_id.
Article lists support is_featured and article detail includes images and timeline.

Content tags are assigned using tag_ids: [1, 2]; an empty list removes the associations.
A media item's tags come from its parent content entry.
Article rich text is sanitized; scripts, event handlers, iframes and unsafe URLs are removed.
published_at null means draft; a timestamp can be used for publishing/scheduling.
Uploads are separate from CRUD: upload first, then use its returned URL in the resource payload.

Dates use YYYY-MM-DD; datetime fields accept a valid datetime string. Supply both latitude and longitude or neither. Event end_date cannot precede event_date, including partial edits.

An in-use category returns 409 on delete. Reassign/remove its dependent records first.
Deleting content also removes media-related bookmarks; other bookmarkable resource deletions clean their own bookmarks.
Shared uploaded files are retained when deleting database records; automatic file deletion is intentionally not performed.

## User management

| Method | Path | Purpose |
| --- | --- | --- |
| GET / POST | /users | List / create |
| GET | /users/{id} | Profile, favorites and bookmark/submission counts |
| PUT / PATCH | /users/{id} | Edit name, email, role, is_active, password or email_verified |
| PUT / PATCH | /users/{id}/profile | Edit avatar_url, bio, dark_mode_enabled, font_size, favorite_fandoms and category_ids |
| DELETE | /users/{id} | Delete another account |

Creation requires name, email and password. role defaults to registered; allowed roles are registered/admin.
Password hashes and remember tokens are never serialized.
Use the dedicated password endpoint to change your own password.
Changing email clears verification unless the admin explicitly supplies email_verified.
List filters: q, role, is_active, page, per_page.

## Moderation and feedback

GET /submissions supports status, category_id, q, page and per_page.
GET /submissions/{id} returns safe preview HTML.
POST /submissions/{id}/review accepts decision (approved/rejected) and optional review_notes.
On approval, title/body_html/category_id/cover_image_url/is_featured may be supplied as editorial corrections.
Approval publishes the article immediately, attributes it to the submitter, and records the reviewing admin separately.
DELETE /submissions/{id} removes the submission record; an already published article remains available through article management.

GET /feedback supports status, type, q, page and per_page.
GET /feedback/{id} shows one entry.
PATCH /feedback/{id} accepts status: open, in_progress or resolved.
DELETE /feedback/{id} removes an entry.

## Reports and uploads

GET /dashboard returns totals, pending submissions, unresolved feedback and upcoming counts.
Active users are distinct active accounts with activity in the last 30 days.
Online users are distinct active accounts with session activity in the last 15 minutes.
Category popularity ranks favorite-category followers, then content views.
GET /activity accepts user_id, page and per_page.
GET /ratings accepts media_id, page and per_page.
GET /media/{id}/ratings returns aggregate stars/thumbs ratings.

POST /uploads uses multipart/form-data with kind (image/video/audio) and file.
Accepted formats: JPG/JPEG/PNG/WebP/GIF, MP4/WebM, MP3/WAV/OGG/M4A.
Application limits: image 5 MB, video 50 MB, audio 20 MB.
The current PHP upload_max_filesize and post_max_size are both 40M; this means video uploads are limited further by PHP. Increase both PHP limits if you need the application's full 50 MB allowance.
SVG, HTML and executable script uploads are not accepted.
The response includes path, URL, MIME type and size. public/storage has been linked locally.

## Response and integration notes

Lists use Laravel pagination: data, current_page, last_page, total, etc.
Single records use {data: ...}. Creation returns 201, deletion 204.
Errors: 401 unauthenticated, 403 not an active admin, 404 missing record, 409 conflict, 419 expired/missing CSRF, 422 validation, 429 rate limit.
Only fields in server validation rules are accepted; creator/reviewer IDs are server-assigned.
Use text rendering for plain-text fields; only sanitized body_html is intended for rich HTML rendering.

Import docs/admin-api.postman_collection.json into Postman. Set admin_email/admin_password collection variables, then run CSRF followed by Login. The collection saves refreshed CSRF tokens and IDs automatically. Keep Postman's cookie jar enabled.
Sample resource requests create demonstration records when explicitly run. Use a test database if you do not want those records in db_fanhub.

## Verification

PowerShell:

    $env:FANHUB_MYSQL_TESTS = '1'
    & C:/xampp/php/php.exe artisan test

The admin-focused suite passed with 25 tests and 162 assertions. After adding registered-user endpoints, the combined backend suite passed with 53 tests and 380 assertions. The integration suite creates a uniquely named temporary MySQL database, runs all migrations and seeders there, wraps test records in transactions, and drops only that temporary database.
Without FANHUB_MYSQL_TESTS=1, MySQL integration tests are skipped.
Coverage includes role/active access, sessions, CSRF, rate limits, validation, CRUD, HTML/URL safety, uploads, publication, password recovery, deletion integrity and reports.
Route caching passed. Live XAMPP checks returned 200 for CSRF and 401 for the unauthenticated dashboard.

## Remaining application scope

This is the admin backend guide; visual admin screens and end-user pages still need implementation.
The optional chatbot remains excluded as agreed during the schema simplification.
Public registration, feedback and content browsing are implemented separately under the visitor API; member-only profile, dashboard, bookmarks, ratings and submissions are under the registered-user API. See docs/VISITOR_BACKEND.md and docs/REGISTERED_USER_BACKEND.md.
This guide describes actual backend behavior and is not the project's final academic report.
