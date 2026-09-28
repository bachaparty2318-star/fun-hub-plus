# Visitor backend guide

Implemented for Fan Hub Plus on Laravel. Visitor means an unauthenticated site user from the SRS: they can browse, search, view public material, discover events, submit feedback and choose to register.

## What is ready

- Separate visitor API prefix: `http://localhost/fanhub/public/visitor/api`
- Public categories and tags.
- Public combined explorer for content, published articles, character profiles, media and merchandise.
- SRS filters for category, fandom, genre, release year, popularity, type, tag and sorting.
- Public detail endpoints with drafts, scheduled articles and private admin fields hidden.
- Upcoming releases and upcoming merchandise.
- Share metadata that returns visitor API URLs.
- Event discovery by city, category, type, calendar range and GPS radius.
- Visitor feedback submission.
- Visitor entry into registration, login and password recovery.
- CSRF/session handling, throttling, validation and JSON errors.

Visitor routes reuse the public-safe catalog, event, feedback and auth controllers, so no duplicate business logic or extra database tables were added.

## Endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/auth/csrf` | CSRF token |
| POST | `/auth/register` | Register as a new member |
| POST | `/auth/login` | Login if the visitor already has a registered account |
| POST | `/auth/forgot-password` | Request password reset |
| GET | `/auth/reset-password/{token}?email=...` | Reset-link JSON handoff |
| POST | `/auth/reset-password` | Consume reset token |
| GET | `/categories` | Public fandom categories |
| GET | `/tags` | Public tag lookup |
| GET | `/explore` | Public content explorer |
| GET | `/upcoming` | Upcoming releases and merchandise |
| GET | `/catalog/{type}/{id}` | Public item detail |
| GET | `/share/{type}/{id}` | Share title and visitor API URL |
| GET | `/events` | Public event discovery |
| GET | `/events/{id}` | Public event detail |
| POST | `/feedback` | Submit bug, suggestion or query |

Visitor-only prefix does not expose dashboard, profile, bookmarks, ratings or fan submissions. Those require a registered, verified user under `/user/api`.

## Verification

The visitor backend is covered by `tests/Feature/VisitorBackendTest.php`. The suite checks public browsing, filtering, hidden drafts, no anonymous view-count writes, visitor share URLs, events, registration, password reset handoff, feedback and absence of member-only routes.
