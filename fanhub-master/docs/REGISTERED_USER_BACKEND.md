# Registered user backend guide

Implemented for Fan Hub Plus on Laravel, using the existing `db_fanhub` database.

## What is ready

- Public registration, login, logout, current-user lookup, password reset and signed email verification.
- Active-account and verified-email checks for member-only actions.
- Profile editing with avatar upload, bio, dark mode, font size, favorite fandoms, favorite categories and safe display preferences.
- Registered-user dashboard with greeting, preferences, favorite categories/fandoms, recent bookmarks, activity and owner counts.
- Public category/tag browsing, combined explorer, item detail, upcoming releases and share metadata.
- SRS explorer filters for category, fandom, genre, release year, popularity, type, tag and sorting by latest, popular or alphabetical.
- Public media/article/character/merchandise views with private creator/admin fields hidden.
- Bookmarks with notes for content, articles, characters, media and merchandise.
- Media ratings using either 1-5 stars or thumbs up/down, with per-user ownership and aggregate results.
- Fan submissions with sanitized rich text, image uploads, owner edits while pending, withdrawal and admin approval publication.
- Public feedback submission for guests or members, plus member-only feedback history.
- Location-aware event discovery with city/calendar filters and GPS radius search.
- View/activity logging for verified members, including view-count deduplication.
- CSRF/session protection, throttling, JSON errors and route caching compatibility.

No extra database tables were needed for the registered-user work. It uses the simplified schema already aligned to the SRS. The optional chatbot is still excluded because the SRS marks it optional.

Visitor-only public browsing is also available separately under `/visitor/api`; see `docs/VISITOR_BACKEND.md`.

## Local URL

Base API URL:

    http://localhost/fanhub/public/user/api

These endpoints use Laravel web sessions and CSRF tokens. Keep cookies between requests and send `Accept: application/json`.

## Authentication flow

1. `GET /auth/csrf`
2. `POST /auth/register` or `POST /auth/login` with `X-CSRF-TOKEN`
3. Save the refreshed `csrf_token` returned by auth-changing endpoints
4. Use the session cookie and current CSRF token for member mutations
5. `POST /auth/logout` ends the session

Registration creates a registered active user and profile. Role, active state, IDs and verification state are server-controlled even if a request tries to send them.

Email verification links are signed Laravel URLs and expire after 60 minutes. Locally, `MAIL_MAILER=log`, so verification/reset links are written to `storage/logs/laravel.log`. Configure SMTP before expecting real email delivery.

## Main endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/auth/csrf` | CSRF token |
| POST | `/auth/register` | Create member account and send verification |
| POST | `/auth/login` | Login active registered user |
| GET | `/auth/me` | Current user, profile and favorite categories |
| POST | `/auth/logout` | Logout |
| POST | `/auth/forgot-password` | Send reset link for active registered accounts |
| GET | `/auth/reset-password/{token}?email=...` | Reset-link JSON handoff |
| POST | `/auth/reset-password` | Consume reset token |
| GET | `/auth/email/verify/{id}/{hash}` | Signed email verification |
| POST | `/auth/email/resend` | Resend verification |
| PUT | `/auth/password` | Change own password |
| PUT | `/auth/email` | Change own email and require re-verification |
| GET / DELETE | `/auth/sessions` and `/auth/sessions/{id}` | List/revoke own sessions |
| GET / PATCH | `/profile` | Show/edit own profile |
| POST | `/profile/avatar` | Upload own avatar |
| GET | `/dashboard` | Verified member dashboard |
| GET | `/dashboard/activity` | Own activity feed |
| GET | `/categories`, `/tags` | Public lookup data |
| GET | `/explore` | Combined content explorer |
| GET | `/upcoming` | Upcoming releases and merchandise |
| GET | `/catalog/{type}/{id}` | Public item detail |
| GET | `/share/{type}/{id}` | Share metadata/API URL |
| GET | `/events`, `/events/{id}` | Public events |
| GET / POST | `/bookmarks` | List/create own bookmarks |
| PATCH / DELETE | `/bookmarks/{id}` | Edit note/remove own bookmark |
| GET / PUT / DELETE | `/media/{id}/rating` | Own media rating |
| GET / POST | `/submissions` | List/create own fan submissions |
| GET / PATCH / DELETE | `/submissions/{id}` | View/edit/withdraw pending own submission |
| POST | `/submissions/{id}/image` | Upload pending submission image |
| POST | `/feedback` | Guest/member feedback |
| GET | `/feedback` | Own feedback history |
| GET | `/feedback/{id}` | Own feedback detail |

## Explorer notes

`/explore` combines content, published articles, characters, media and merchandise. Draft or future-scheduled articles are hidden. Media rows appear as their own items, and their parent content is omitted from the combined listing to avoid duplicate cards.

Useful query parameters:

    q, category_id, genre, fandom, content_type, item_type, release_year,
    min_popularity, is_featured, upcoming, tag_id, merchandise_tag,
    sort=latest|popular|alphabetical|release_date,
    direction=asc|desc, page, per_page

`/upcoming` shows future release dates plus merchandise marked as upcoming, even when the merchandise date is not set.

## Events

Events support city, category, type, search and date-range filters. Date ranges include overlapping multi-day events. GPS search requires both `latitude` and `longitude`; `radius_km` defaults to 50 and accepts 1 to 500.

## Response notes

Errors use JSON responses: 401 unauthenticated, 403 inactive/unverified/forbidden, 404 hidden or missing, 409 reviewed/conflict, 419 CSRF, 422 validation and 429 throttled.

Lists use Laravel pagination. Creation generally returns 201, deletion returns 204. Password hashes, remember tokens, admin reviewer IDs and private notes for other users are never exposed.

## Verification

PowerShell:

    $env:FANHUB_MYSQL_TESTS = '1'
    & C:/xampp/php/php.exe artisan test

The full suite passed with 53 tests and 380 assertions. The registered-user suite covers registration, signed verification, login/logout, password/email changes, CSRF, throttling, profile/avatar, explorer filters, drafts, bookmarks, ratings, submissions, admin approval publishing, feedback, dashboard, GPS events and access control.

Import `docs/registered-user-api.postman_collection.json` into Postman for endpoint examples. Keep Postman's cookie jar enabled, call CSRF first, and fill the placeholder member credentials before running auth requests.
