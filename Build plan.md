# Build plan

Decided 2026-11-01: Laravel on cPanel, built evenings with Claude Code, Derek's current open house as the pilot. Goal: a working check-in + SMS at Derek's next open house, the seller recap by the one after.

## Stack, pinned

| Layer | Choice | Why |
| --- | --- | --- |
| Framework | Laravel 11, PHP 8.2+ | Auth, queues, mail, validation, migrations out of the box |
| Auth scaffold | Laravel Breeze (Blade stack) | Simplest; no SPA to maintain |
| Interactivity | Alpine.js via Breeze; Livewire only if a page needs it | Keep the public check-in page plain HTML |
| CSS | Tailwind (ships with Breeze) |  |
| DB | MySQL on cPanel |  |
| Queue | `database` driver, drained by `php artisan schedule:run` on a one-minute cPanel cron | No daemon needed on shared hosting |
| SMS | Twilio via `SmsProvider` interface | Best docs for the agent; swap later if cost matters |
| Email | Postmark (or cPanel SMTP for the pilot) |  |
| PDF | `spatie/laravel-pdf` (Browsershot) if Chrome is installable on the host, else `barryvdh/laravel-dompdf` | HTML templates either way; decide in M0 |
| QR | `simplesoftwareio/simple-qrcode` |  |
| Hosting | Existing cPanel, PHP 8.2 selected, Composer available via SSH | Verify in M0 |

## Milestones

| # | Milestone | Done when | Evenings |
| --- | --- | --- | --- |
| M0 | Environment | Laravel runs on the cPanel subdomain; migrations run; cron fires `schedule:run`; Twilio account created and A2P registration submitted | 1-2 |
| M1 | Event + QR | Agent logs in, creates an event with `listing_url`, downloads a QR that opens `/e/{slug}` | 2 |
| M2 | Check-in + SMS | Visitor submits name, mobile, consent; row + consent record written; SMS with short link arrives within 30 s; thank-you page shows the link as fallback | 3 |
| **Pilot 1** | **Derek's open house** | Real visitors check in; you watch the logs | — |
| M3 | Dashboard + feedback | Visitor list per event; feedback SMS 2 h after end; feedback form; STOP handling via Twilio webhook | 3 |
| M4 | Seller recap | HTML template, PDF render, emailed to seller with agent on CC, stored for re-download | 2 |
| **Pilot 2** | **Derek's next open house** | Full loop: check-in, feedback, recap delivered | — |
| M5 | Compliance + polish | Retention sweep, DELETE keyword, CSV export, privacy page, consent text versioning UI, lawyer wording dropped in | 2-3 |
| M6 | Billing | Laravel Cashier + Stripe, Free/Pro gating, SMS quota | 2-3 |

Pilot 1 is Derek's next open house, expected the weekend of 2026-11-07. That leaves five evenings, so M1 is skipped for pilot 1: Derek's event is seeded into the database by hand and the dashboard comes after. Schedule: M0 Monday to Tuesday, M2 Wednesday to Friday, Saturday morning a real-phone test at your own door. Use a Canadian local long-code number for the pilot; Canadian long codes are not subject to 10DLC registration and send at low volume immediately. Submit toll-free verification in parallel for later.

## Working with Claude Code

- Put this PRD (exported as Markdown) in the repo as `docs/PRD.md` and point `CLAUDE.md` at it.
- `CLAUDE.md` holds: stack choices above, "never store MLS data", "every agent query scopes by agent\_id", "consent wording lives in `consent_texts`, never in a Blade file", test command, deploy command.
- One milestone per session. Start each session with the milestone's done-when line and end with `php artisan test` green.
- Deploy = `git pull` on the server + `composer install --no-dev` + `php artisan migrate --force`. Script it in M0 so every later evening ends with a deploy.
- Keep the public check-in page as a Blade view with no build step dependency, so it still renders if Vite assets fail.

## This week

- [ ] Get the address, date and listing URL for Derek's next open house (expected 2026-11-07 weekend) on Monday
- [ ] Register `scanin.ca`
- [ ] Create the Twilio account, buy a Canadian local number for the pilot, submit toll-free verification for later
- [ ] Confirm cPanel has PHP 8.2, Composer, SSH and cron
- [ ] M0
