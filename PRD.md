# ScanIn PRD v2

Nov 1, 2026 · @Flea

## Problem and product

Listing agents in Ontario still collect open-house visitors on paper sign-in sheets, then re-type them, lose them, or never follow up. Sellers get a vague verbal recap. ScanIn (scanin.ca, formerly OpenHousePulse) replaces the clipboard with a QR check-in that takes under 30 seconds, texts the visitor a link to the listing, and emails the seller a PDF recap after the event.

**Users**

| User | What they want | What they get |
| --- | --- | --- |
| Listing agent (paying customer) | Captured, consented leads with zero typing; something to show the seller | Dashboard of visitors per event, CASL-clean consent records, seller recap sent for them |
| Visitor (buyer) | Get in the door, get the listing details on their phone | 30-second form, instant SMS with the listing link |
| Seller | Proof the agent is working | A clean PDF: who came, how interested, what they said |

**What v1 ships**

1. Agent signs up, creates an event: address, date and time, price, bedrooms/bathrooms, one photo, and a link to their own listing page (their DDF-powered site or realtor.ca).
2. System generates a QR code and a public check-in page.
3. Visitor scans, fills name + mobile + consent (email and the qualifying questions are optional), submits.
4. Visitor gets an SMS with the listing link within seconds.
5. After the event ends, the agent reviews feedback and sends the seller recap PDF.

**Positioning:** the Canadian open-house tool. Competitors (Spacio, Curb Hero, Open House Pro) are US-built and treat CASL and PIPEDA as an afterthought. ScanIn is built around them.

## Scope

V1 is three things: check-in, SMS link, seller recap. Everything else waits until there are paying agents asking for it.

| Feature | Status | Why |
| --- | --- | --- |
| QR check-in page per event | v1 | Core |
| SMS with listing link on check-in | v1 | Core; the link is the agent's own listing page |
| Consent log (CASL) | v1 | Legal; cannot be retrofitted |
| Seller recap PDF | v1 | The hook that sells it to listing agents |
| Agent dashboard: events, visitors, feedback | v1 | Core |
| Post-event QR redirect to listing page | v1 | Trivial once the link exists |
| Visitor feedback (1-5 + notes), sent by SMS after the event | v1 | Feeds the seller recap |
| Feedback page second question about the agent, ending in the agent's Google review link, shown to everyone whatever they rated | v1 | Reviews from people who met the agent. No review gating, no suggested review text, no reward |
| Manual add of a visitor by the agent (paper sign-in sheet) | v1 | Same fields as the check-in form; `source = paper`; text queued only if the SMS box was ticked on paper |
| Optional separate "new listings" checkbox on the check-in form | v1 | Own consent record (`purpose = marketing`); shown only once the lawyer approves the wording |
| CSV export of an event's visitors, with consent columns | v1 | Moved up from M5; Derek needs it for his CRM and newsletter |
| Agent webhook / CRM push | v2 | Nice to have; agents will ask for Follow Up Boss first |
| Agent-side SMS follow-ups to visitors | v2 | Needs express consent flow and STOP handling done right |
| DDF listing auto-fill | Deferred, maybe never | Requires a CREA Technology Provider agreement; saves 60 seconds of typing |
| Phone verification (OTP) at check-in | Cut | Kills completion at the front door; bad cell service is common |
| Generated digital feature sheet | Cut | The agent already has one; link to it |
| Programmatic SEO city hubs | Cut | Needs volume you will not have at launch; DDF data cannot be used on your own site anyway |

**Not an MLS product.** ScanIn never pulls, stores, or displays MLS listing data. The agent enters the few fields they want shown and links to their own listing page. Compliance with CREA display rules stays on the agent's site where it already lives. Never scrape realtor.ca.

## Core flows

**Event setup (agent, \~2 minutes)**

1. Create event: street address, city, postal code, date, start and end time.
2. Add display fields: price, bedrooms, bathrooms, one photo, a two-line blurb. All optional except address.
3. Paste listing URL (`listing_url`). This is what the SMS links to and where the QR redirects after the event.
4. Optionally enter seller name and email for the recap.
5. Download or print the QR (PNG and PDF sign). The QR encodes `app.domain/e/{slug}`.

**Visitor check-in (\~30 seconds, phone, possibly weak signal)**

1. Scan QR. Page loads with the photo, address, price, agent name and headshot. Must be under 100 KB and work on 3G.
2. Required: full name, mobile number, consent checkbox with the exact CASL wording.
3. Optional, collapsed by default: email, working with an agent, buying timeline, pre-approved.
4. Submit. Server stores the visitor, writes a consent record, queues the SMS, returns a thank-you page with the listing link as a fallback if the SMS fails.
5. If the event has ended, the page shows no form and redirects to `listing_url` (or the agent's profile if the listing is gone).

**SMS**

- Message 1, on check-in: `Thanks for visiting {address}. Listing details: {short_link}. Reply STOP to opt out.` One segment, under 160 characters.
- Message 2, 2 hours after the event ends, only if consented: `How did you like {address}? Rate it 1-5: {feedback_link}`.
- STOP, UNSUBSCRIBE and similar inbound keywords set `sms_opt_out` on the visitor and stop all further messages.
- Every outbound message is logged with provider ID, status and cost.

**Seller recap PDF (agent-triggered, after the event)**

- Header: property, date, agent branding.
- Numbers: visitors, consent rate, how many had an agent, how many pre-approved, timeline breakdown.
- Feedback: average score, every note verbatim, visitor names withheld (sellers get counts and comments, not contact details).
- Agent sends it from the dashboard; it emails the seller with the agent on CC. The PDF is stored and re-downloadable.

## Compliance

CASL and PIPEDA are the product's reason to exist, so they are built in, not bolted on. Nothing here is legal advice; have a lawyer review the consent wording and the privacy policy before launch.

**CASL**

- The check-in SMS (message 1) is sent in direct response to a visitor's request for listing details. Treat it as the response to an inquiry, but still send it only after the consent box is ticked, and keep it to the listing link and the STOP line.
- Message 2 and anything an agent sends later are commercial electronic messages. They require express consent, recorded with: the exact wording shown, its version, timestamp, IP, user agent, event ID, and the agent on whose behalf consent was collected.
- Every message identifies the sender (agent name and brokerage) and carries an unsubscribe mechanism. STOP is honoured within 10 business days by law; the system does it instantly.
- The consent wording is a versioned text in the database, not a string in the template. Changing it creates a new version; old records keep the one they were shown.

**Follow-up, newsletter, reviews and paper rows (added 2026-11-03; wording and scope subject to lawyer review)**

1. The newsletter ("new listings") checkbox is separate, optional and unticked. It is never bundled with the listing-link consent. It is stored as its own consent record with `purpose = marketing`.
2. Marketing is only ever sent to visitors with a granted, non-revoked `purpose = marketing` consent. "Working with an agent = yes" is a warning flag in the export, not an automatic exclusion, because the answer is optional and self-reported; the agent decides. [Lawyer to advise.]
3. The Google review link is shown to everyone on the feedback page. It is never hidden by score. No wording tells the visitor what to write. No reward is offered.
4. The review request is a page, not a text, until the lawyer says the feedback consent covers a second message that carries a review link.
5. Revoking a consent (STOP, DELETE, agent action) sets `revoked_at`; the CSV export respects it.
6. Manual add: the agent confirms on the form that the visitor ticked the box on the paper sheet. The consent record stores the paper wording version; IP and user agent are NULL.

**PIPEDA**

- Visitors see a one-line purpose statement on the form and a link to the privacy policy.
- Each agent is the organization collecting the data; ScanIn is their processor. The agent terms of service say so.
- Retention: visitor records are deleted 24 months after the event unless the agent exports them, configurable per agent. Deletion is a hard delete of personal fields with the aggregate stats kept.
- Visitors can request deletion by replying DELETE or via a link on the thank-you page. The request is fulfilled within 30 days, automatically where possible.
- Data stays in Canada: the database and file storage are on Canadian hosting. SMS provider and email provider are the exceptions and are named in the privacy policy.

**Open questions for the lawyer**

- [ ] Final CASL consent wording for the checkbox
- [ ] Whether message 2 can ride on the same consent as message 1 or needs a second checkbox
- [ ] Processor language in the agent terms
- [ ] Paper consent: is a tick on a sheet, typed in later, valid express consent for a text; what must be kept
- [ ] Follow-up to visitors who are working with another agent (TRESA / RECO / brokerage rules)
- [ ] Newsletter: separate checkbox required, and whether an open-house visit can support implied consent
- [ ] Whether the Google review link may go in the feedback text or only on the feedback page

## Architecture

**Open decision: one stack or two.** The original plan was a native PHP API on cPanel plus a separate Next.js frontend. That is two deploy targets, two auth surfaces, and hand-rolled routing, validation and migrations. Recommended: pick one of the two below before writing code.

| Option | Stack | Deploy | Pros | Cons |
| --- | --- | --- | --- | --- |
| A (recommended if cPanel is the constraint) | Laravel 11 + Livewire or Inertia/Vue, MySQL | One cPanel app, Canadian host | One codebase, auth/queues/mail/PDF solved, PHP you already know | Less fashionable; Node not needed at all |
| B | Next.js 14 full-stack (App Router, server actions), Postgres | Vercel + Canadian Postgres (e.g. Neon in a Canadian region) | One codebase, great DX, fast public pages | Data residency needs care; Vercel functions are not in Canada by default |
| Original | Native PHP API + Next.js | cPanel + Vercel | None that the other two lack | Everything above, doubled |

**Components (either option)**

- Public check-in page: server-rendered, no JS framework required, under 100 KB, cached per event.
- Agent dashboard: authenticated, session or JWT, email + password with magic-link reset.
- SMS: a `SmsProvider` interface with `send()`, `handleInbound()`, `status()`. First implementation Twilio or Telnyx. Not VoIP.ms: its API caps at 100 messages per day, may block links, and reserves the right to disable accounts that look automated.
- Canadian A2P registration on a toll-free or 10DLC-equivalent number before launch. Budget two to three weeks.
- Email: Postmark or SES for seller recaps and agent notifications.
- PDF: TCPDF or a headless-Chrome service rendering an HTML template. HTML-to-PDF is easier to brand.
- Queue: database-backed job queue for SMS, PDF and scheduled feedback texts. A cron every minute drives it; no long-running process needed on cPanel.
- Short links: own `/s/{code}` redirect table, so the SMS stays one segment and clicks are measured.
- Storage: photos and PDFs on local disk or Canadian S3-compatible storage.
- Abuse: rate limit check-ins per IP and per event, honeypot field, no CAPTCHA (it kills the door experience).

**Domains:** `scanin.ca` for marketing, dashboard, QR and SMS links. It is short enough that no separate link domain is needed.

## Database schema v2

Changes from v1: `realtor_ddf_id` and `digital_feature_sheet_url` replaced by `listing_url`; `phone_verified` dropped; `casl_opt_in` replaced by a `consents` table; new `messages`, `consent_texts`, `sellers` and `short_links` tables; ENUMs instead of free-text status strings; soft deletes on agents and events.

```sql
CREATE TABLE agents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid CHAR(36) NOT NULL UNIQUE,
  full_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  brokerage_name VARCHAR(255),
  headshot_url TEXT,
  google_review_url TEXT NULL,
  webhook_url TEXT,
  subscription_tier ENUM('free','pro') NOT NULL DEFAULT 'free',
  retention_months TINYINT UNSIGNED NOT NULL DEFAULT 24,
  approved_at DATETIME NULL,  -- owner-set; NULL = cannot create events (anti-abuse: listing_url is texted to strangers). Sign-up is closed by default (SCANIN_REGISTRATION_OPEN).
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agent_id INT NOT NULL,
  slug VARCHAR(64) NOT NULL UNIQUE,
  address_street VARCHAR(255) NOT NULL,
  city VARCHAR(100) NOT NULL,
  province CHAR(2) NOT NULL DEFAULT 'ON',
  postal_code VARCHAR(7),
  price DECIMAL(12,2),
  bedrooms TINYINT UNSIGNED,
  bathrooms DECIMAL(3,1),
  blurb VARCHAR(500),
  primary_image_url TEXT,
  listing_url TEXT NOT NULL,
  seller_id INT NULL,
  event_start DATETIME NOT NULL,
  event_end DATETIME NOT NULL,
  status ENUM('upcoming','live','ended','cancelled') NOT NULL DEFAULT 'upcoming',
  post_event_redirect ENUM('listing','agent_profile','custom') NOT NULL DEFAULT 'listing',
  custom_redirect_url TEXT,
  recap_sent_at DATETIME NULL,
  recap_pdf_url TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_events_agent (agent_id),
  INDEX idx_events_window (event_start, event_end),
  FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sellers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agent_id INT NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE visitors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  source ENUM('qr','paper') NOT NULL DEFAULT 'qr',
  full_name VARCHAR(255) NOT NULL,
  phone_e164 VARCHAR(16) NOT NULL,
  email VARCHAR(255),
  working_with_agent TINYINT(1),
  buying_timeline ENUM('immediate','1_3_months','3_6_months','browsing'),
  pre_approved TINYINT(1),
  sms_opt_out TINYINT(1) NOT NULL DEFAULT 0,
  feedback_score TINYINT UNSIGNED,
  feedback_notes TEXT,
  feedback_at DATETIME NULL,
  ip_address VARBINARY(16),
  user_agent VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  anonymized_at DATETIME NULL,
  INDEX idx_visitors_event (event_id),
  INDEX idx_visitors_phone (phone_e164),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE consent_texts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  version VARCHAR(20) NOT NULL UNIQUE,
  body TEXT NOT NULL,
  effective_from DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE consents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  visitor_id INT NOT NULL,
  agent_id INT NOT NULL,
  consent_text_id INT NOT NULL,
  channel ENUM('sms','email') NOT NULL,
  purpose ENUM('checkin','feedback','marketing') NOT NULL DEFAULT 'checkin',
  granted TINYINT(1) NOT NULL,
  ip_address VARBINARY(16),
  user_agent VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  revoked_at DATETIME NULL,
  FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
  FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
  FOREIGN KEY (consent_text_id) REFERENCES consent_texts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  visitor_id INT NULL,
  event_id INT NULL,
  direction ENUM('out','in') NOT NULL,
  channel ENUM('sms','email') NOT NULL,
  kind ENUM('checkin','feedback','recap','inbound','other') NOT NULL,
  to_addr VARCHAR(255) NOT NULL,
  from_addr VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  provider VARCHAR(20) NOT NULL,
  provider_message_id VARCHAR(64),
  status ENUM('queued','sent','delivered','failed','undelivered','received') NOT NULL DEFAULT 'queued',
  cost_cents SMALLINT UNSIGNED,
  error TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX idx_messages_visitor (visitor_id),
  INDEX idx_messages_provider (provider_message_id),
  FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE SET NULL,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE short_links (
  code VARCHAR(8) PRIMARY KEY,
  target_url TEXT NOT NULL,
  event_id INT NULL,
  visitor_id INT NULL,
  clicks INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Notes: `consents.purpose` records what was agreed to (listing-link text, feedback text, newsletter); existing rows default to `checkin`. `visitors.source` marks a paper row typed in by the agent; for those the consent record's IP and user agent are NULL. `anonymized_at` marks a visitor whose personal fields were wiped by the retention job; the row stays so event counts and feedback averages survive. `messages.visitor_id` is nullable so delivery logs outlive a deleted visitor. If Option B (Postgres) wins, the ENUMs become CHECK constraints and `VARBINARY(16)` becomes `inet`.

## API endpoints

Public endpoints are rate-limited and unauthenticated. Agent endpoints require a session or bearer token scoped to that agent; every query filters by `agent_id`, never trust an ID from the client.

| Method | Path | Auth | Purpose |
| --- | --- | --- | --- |
| GET | `/e/{slug}` | public | Check-in page, or redirect if event ended |
| POST | `/e/{slug}/checkin` | public, rate-limited | Create visitor + consent, queue SMS |
| GET | `/f/{token}` | public | Feedback form (token per visitor) |
| POST | `/f/{token}` | public | Store feedback score and notes |
| GET | `/s/{code}` | public | Short-link redirect, count click |
| POST | `/webhooks/sms/{provider}` | provider signature | Inbound SMS (STOP, DELETE) and delivery status |
| POST | `/auth/register`, `/auth/login`, `/auth/reset` | public | Agent accounts |
| GET/POST | `/api/events` | agent | List, create |
| GET/PATCH/DELETE | `/api/events/{id}` | agent | Read, update, cancel |
| GET | `/api/events/{id}/qr.png`, `/qr.pdf` | agent | QR image and printable sign |
| GET | `/api/events/{id}/visitors` | agent | Visitor list with consent and feedback |
| GET | `/api/events/{id}/visitors.csv` | agent | Export. Columns: name, phone, email, working with an agent, timeline, pre-approved, checked in at, source, texting consent (yes/no, date, wording version), marketing consent (yes/no, date, wording version), opted out. Optional filter: marketing consent only. Anonymized visitors excluded. |
| GET/POST | `/events/{id}/visitors/create` | agent | Manual add of a visitor (paper sheet); page routes, scoped by `agent_id` |
| POST | `/api/events/{id}/recap` | agent | Generate and email seller recap PDF |
| GET | `/api/events/{id}/recap.pdf` | agent | Download stored recap |
| GET/PATCH | `/api/me` | agent | Profile, brokerage, headshot, retention, webhook |
| DELETE | `/api/visitors/{id}` | agent | Hard-delete a visitor's personal data on request |

**Jobs (cron every minute)**

- `send_queued_messages`: drain the outbound queue through the provider interface.
- `transition_event_status`: upcoming → live → ended by clock.
- `schedule_feedback`: 2 hours after `event_end`, queue message 2 for consented, non-opted-out visitors.
- `retention_sweep`: daily; anonymize visitors older than the agent's `retention_months`.
- `agent_webhook`: on new visitor, POST JSON to `webhook_url` if set (v2).

## Pricing, metrics, open questions

**Pricing (proposal, untested)**

| Tier | Price | Includes |
| --- | --- | --- |
| Free | $0 | 1 active event at a time, ScanIn branding on the check-in page, 25 SMS/month |
| Pro | $29 CAD/month or $290/year | Unlimited events, agent branding, seller recaps, CSV export, 300 SMS/month |
| Brokerage | Custom | Multiple agents under one bill, shared branding |

SMS cost is roughly 1 cent per segment, so 300 messages costs about $3; margin is fine. The free tier exists to get agents to one real open house; the recap PDF is the upgrade trigger.

**Success metrics**

- Check-in completion rate (page load to submit): target 70% or better. If it is lower, the form is too long.
- SMS delivered within 30 seconds: 95%.
- Feedback response rate on message 2: 25%.
- Free to Pro conversion within 30 days of first event: 15%.
- Pilot: Derek runs 5 open houses on it before anyone else sees it.

**Open questions**

- [ ] Stack: decided 2026-11-01, Option A (Laravel on cPanel). See the Build plan tab.
- [ ] SMS provider: Twilio or Telnyx? Start A2P registration the same week either way.
- [ ] Does the seller get the recap directly, or does the agent always forward it? Direct email with agent on CC is the proposal.
- [ ] Agent webhook: wait for v2. CSV export ships in v1 (Build plan M3b), so Derek has it in the meantime.
- [ ] Domain: `scanin.ca` chosen 2026-11-01; CIPO search clear for "scanin". Register it.
- [ ] Lawyer review of consent wording and agent terms before the first non-Derek signup.

Milestones and this week's tasks: Build plan
