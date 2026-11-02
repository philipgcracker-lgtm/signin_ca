# ScanIn (scanin.ca)

QR open-house check-in for Ontario listing agents: check-in, SMS listing link, seller recap PDF. Owner: Philip Breton. Parent rules in `C:\Users\Flea\webdev\CLAUDE.md` still apply (blunt, never edit or delete his files without double permission).

## Source of truth

- `PRD.md` (project root): product, compliance, schema v2, endpoints, jobs. The schema in it is exact; migrations must match it.
- `Build plan.md` (project root): pinned stack, milestones, pilot dates, deploy approach.
- If the code and the PRD disagree, stop and ask. Do not silently pick one.

## Stack (pinned in Build plan)

Laravel 12 (plan said 11; moved to 12 on 2026-11-01 because 11.x has unpatched security advisories), PHP 8.2+, Breeze (Blade), Alpine, Tailwind, MySQL on cPanel, `database` queue driven by a one-minute `schedule:run` cron. Twilio behind an `SmsProvider` interface, Postmark for email, `simplesoftwareio/simple-qrcode`, Cashier later.

Ask before installing any package not listed in the Build plan. The PDF library (dompdf vs spatie/laravel-pdf) is an open M0 decision; do not install either until Philip decides.

## Non-negotiables

- Never store, pull, or display MLS data. Never scrape realtor.ca. Agents enter their own fields and link to their own listing page (`listing_url`).
- Every agent-facing query scopes by `agent_id`. Never trust an ID from the client.
- Consent wording lives in the `consent_texts` table (versioned), never in a Blade file. Consent records store wording version, timestamp, IP, user agent, event, agent.
- The public check-in page is a plain Blade view with no build-step dependency, under 100 KB, and must work if Vite assets fail.
- Do not build features ahead of the current milestone.

## Codebase gotchas

- The authenticatable model is `App\Models\Agent` on the `agents` table (not `users`). Password column is `password_hash` (`getAuthPasswordName()`), name column is `full_name`.
- No `remember_token`, `email_verified_at`, or `updated_at` on `agents` (PRD schema has none). "Remember me" and email verification were removed from Breeze on purpose. `$timestamps = false`; `created_at` comes from the DB default.
- Agents soft delete (`deleted_at`). IDs are signed `INT` per the PRD, so foreign keys must be `integer`, not `foreignId()` (bigint).
- `events.seller_id` has no foreign key in the PRD. Kept as specified.
- Tests run on in-memory SQLite (`phpunit.xml`); production is MySQL. Anything touching ENUMs, JSON, or raw SQL must be checked against MySQL too.
- Prefix AI-generated notes and docs with `(C)`. Framework and app code files are exempt.

## Commands

- Tests: `php artisan test` (must be green at the end of every session).
- Local dev: `php artisan serve` (SQLite via `.env`), `npm run dev` for assets.
- Deploy (live host, cPanel with no SSH, Terminal or Composer): `./release.sh` builds a local `release` branch (main + no-dev `vendor/` + `public/build`) in worktree `~/.scanin-release`; `./release.sh --push` pushes it. Then in cPanel > Git Version Control > `signin_ca` > Manage > Pull or Deploy: Update from Remote, then Deploy HEAD Commit. `.cpanel.yml` runs `scripts/post-deploy.sh` (migrate --force, optimize, queue:restart, `public_html` symlink); its log is `storage/logs/deploy.log` on the server. Ask before every push (main and release).
- `deploy.sh` (SSH + git pull flow) is unused on this host. Kept for a future host with a shell.
- Server facts: cPanel account is `scaninca` (with an n), app in `/home/scaninca/repositories/signin_ca`, `public_html` is a symlink to its `public/`, PHP at `/opt/cpanel/ea-php82/root/usr/bin/php`. The server `.env` is hand-made and never in git. Folders under `repositories/` must be 755 or LiteSpeed returns 404 for everything.
- Scheduler cron (installed in cPanel Cron Jobs, every minute): `/opt/cpanel/ea-php82/root/usr/bin/php /home/scaninca/repositories/signin_ca/artisan schedule:run >> /dev/null 2>&1`. Not yet proven to fire; the first real proof is an M2 SMS job.

## Working agreement

- One milestone per session. Start from the milestone's "done when" line in the Build plan; finish with `php artisan test` green and a deploy (release flow above, with Philip's approval at each push).
- Report outcomes plainly: if something was not verified (for example, no live MySQL or no server access), say so.
- Do not commit, push, or deploy unless asked.
