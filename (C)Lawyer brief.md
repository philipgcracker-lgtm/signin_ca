# ScanIn: brief for legal review (DRAFT)

**Status: DRAFT for Philip Breton to review and edit before it goes to a lawyer.** Prepared 2026-11-03 from `PRD.md` and the code as it stands. **Updated 2026-11-07** (sections 4, 5, 6, 7 and 8: STOP/HELP/DELETE now live, privacy and terms pages published, pilot moved to Nov 15). Items in [square brackets] need Philip's input. This is a plain-language description of the product, not legal analysis.

## 1. Cover email (edit and send)

> Subject: Review request: privacy and CASL for a small Ontario open-house check-in app
>
> Hi [Lawyer name],
>
> I'm launching ScanIn (scanin.ca), a tool for Ontario real estate agents. Visitors at an open house scan a QR code, fill in a short form on their phone (name and mobile number required, a few optional questions), tick a consent box, and receive one text message with a link to the listing. After the event the agent can send the seller a summary PDF.
>
> The first live use is a real open house on Nov 15, 2026, so I'd like to know what must be fixed before then and what can wait. In particular I need:
>
> 1. The consent wording and form layout reviewed (draft attached, two options).
> 2. The privacy policy and terms pages, which are already live at https://scanin.ca/privacy and https://scanin.ca/terms (written by me, not reviewed by anyone), reviewed and corrected. The earlier draft is attached too.
> 3. Your view on the agent terms of service, especially the controller/processor split.
> 4. Answers to the questions in section 6 below.
>
> Attached: this brief, the consent wording draft, the privacy policy draft. Happy to do a call. What is your fee estimate and turnaround?
>
> Philip Breton, [phone]

## 2. What it is

- Customer: an Ontario listing agent (the "agent"), who runs an open house.
- Visitor: a member of the public who checks in. Not a customer; has no account.
- Seller: the homeowner. Receives a summary PDF from the agent. Not an account holder.
- ScanIn: the software operator. Operated by [legal entity name / sole proprietor], [address], [email]. [Confirm entity.]
- The agent (and brokerage) decide why visitor data is collected. ScanIn's position is that it is the agent's service provider (processor). **Please confirm.**
- ScanIn does not use, display or store MLS data. The agent links to their own listing page.

## 3. Data collected and stored

| Data | From | Required? | Purpose | Retention (planned) |
| --- | --- | --- | --- | --- |
| Full name | Visitor | Yes | Identify the visitor to the agent | 24 months after the event, then personal fields deleted (configurable per agent) |
| Mobile number | Visitor | Yes | Send the listing-link text | Same |
| Email | Visitor | No | Agent follow-up / seller recap | Same |
| Working with an agent (yes/no) | Visitor | No | Lead quality for the agent | Same |
| Buying timeline | Visitor | No | Same | Same |
| Pre-approved (yes/no) | Visitor | No | Same | Same |
| Consent record: wording version shown, timestamp, IP address, browser user agent, event, agent | Automatic | Yes | Proof of consent | [lawyer to advise; currently same as above] |
| Check-in IP address and user agent | Automatic | Yes | Abuse limits, proof of consent | Same |
| Text messages sent and received, delivery status | System | Yes | Delivery log, STOP handling | [lawyer to advise] |
| After a visitor texts DELETE: name, number, email and answers are erased; a consent-withdrawal record is kept without name or number | System | n/a | Proof that consent was given and withdrawn | [lawyer to advise; see question 22] |
| Link click counts | System | Yes | Agent sees whether the link was opened | Same |
| Feedback score (1-5) and notes | Visitor | No | Seller recap (planned) | Same |
| Agent account: name, email, phone, brokerage, password hash | Agent | Yes | Run the service | While account exists |

Notes:
- Raw IP addresses are stored as binary in the database. **Correction 2026-11-04:** visitor pages DO set two cookies (checked on the live site): `scanin_session` (HttpOnly, 2 hours) and `XSRF-TOKEN` (2 hours), which the framework uses to protect the form against forged submissions. Neither is used for tracking or advertising and no visitor identity is stored in them. [Lawyer: confirm these are "strictly necessary" and only need disclosure in the privacy policy.] Agents get the same session cookie when logged in.
- The check-in page loads no third-party scripts, fonts or analytics.
- A marketing page analytics tool (Plausible or Umami) is under consideration for the website and the agent dashboard only, not visitor pages. **Not decided.**
- A per-event page-view counter using a salted, daily-rotating hash of the IP is proposed but **not built or approved**; see PRD change pending. Flagging so the policy can be written correctly if it ships.

## 4. The visitor flow, exactly

1. Visitor scans QR (`https://scanin.ca/e/{event}`). Page shows the property, agent name and brokerage.
2. Form: full name, mobile, consent checkbox (unticked by default); optional email, working with an agent, timeline, pre-approved.
3. On submit: visitor and consent records are saved; one text is queued.
4. Text sent immediately: `Thanks for visiting {address}. Listing details: {short link} Reply STOP to opt out. -{agent name}, {brokerage}`
5. Thank-you page shows the same link, in case the text fails.
6. Planned: 2 hours after the event ends, a second text asks for a 1-5 rating via a link (see questions 2 and 3 below).
7. Visitors can reply to the text (live since 2026-11-07):
   - **STOP**: applies to the phone number across all events. Every visitor row with that number is marked opted out, consents are revoked, and no further text is sent from ScanIn (also checked again at send time).
   - **DELETE**: anonymizes the visitor's record (see the table). Confirmation text sent.
   - **HELP / INFO**: one reply naming ScanIn, how to STOP, how to DELETE, and the privacy page link.
   - **START / UNSTOP**: deliberately does NOT re-enable texts. A visitor would have to check in again and re-consent. (Twilio's own default may still tell the sender they are re-subscribed; I have not verified that.)
   - An unknown number gets the HELP reply and nothing is stored.
8. If the event has ended, the page shows no form and redirects to the agent's listing page.

Anti-abuse: honeypot field, per-IP and per-event rate limits, one text per phone number per event. No phone verification (deliberately, to avoid losing visitors at the door). A visitor could therefore enter someone else's number.

## 5. Vendors and data location

| Vendor | Role | Location | Status |
| --- | --- | --- | --- |
| Web hosting (cPanel, host s2919.can1.stableserver.net) | Stores everything | Server IP geolocates to Toronto (WHG Hosting Services). That is an IP lookup only; [written confirmation from the host that data and backups stay in Canada is still outstanding]. The live privacy page already says "hosted in Canada". | Live |
| Twilio | Sends and receives SMS; sees number and message text | United States (message logs stored there; the privacy page says so) | Live, sending and receiving. [Philip: confirm account is upgraded out of trial] |
| Email: own cPanel mail server (noreply@scanin.ca) for password resets; Postmark planned for seller recaps | Sends email | [Postmark region to confirm] | Resets live; recaps not built |
| GitHub | Holds the source code only, not data | US | Live |

PRD position: database and file storage in Canada; SMS and email providers are the named exceptions and will be disclosed in the privacy policy.

## 6. Questions for the lawyer

Consent (full text of the first eight is in `(C)Consent wording draft.md`):
1. Is the check-in text a response to a request (implied footing) or does it need express consent? Is the checkbox wording enough?
2. Can the post-event feedback text rely on the same consent, or does it need its own checkbox?
3. Is replying STOP sufficient, and what sender identification must each message carry?
4. What contact details must appear on the form itself?
5. Records: is what we store enough to prove consent, and for how long must we keep it?
6. How long does express consent last; does a second open house need fresh consent?
7. Minors, and visitors who enter someone else's number (no verification): any extra step needed?
8. Is the check-in text a "commercial electronic message" at all in this setting?

Privacy (PIPEDA and Ontario):
9. Is our controller/processor split right (agent as the organization responsible, ScanIn as service provider)? Does the brokerage also carry responsibility?
10. Is storing IP address and user agent justified, and does hashing or truncating them change the answer?
11. Is a 24-month default retention defensible? Should it be shorter for visitors who never consented to follow-up?
12. Is disclosure of Twilio (US) storage in the policy enough, or is separate consent needed for cross-border transfer?
13. Does the seller recap raise issues? It shows counts and anonymous comments, never names or contact details.
14. Is DELETE-by-text (live, immediate, automatic) an acceptable way to receive deletion requests? What about access requests: today there is no automated path; a visitor would contact the agent whose email is shown on the privacy page. What response time applies?

Agent terms:
15. Terms of service for agents: processor language, the agent's responsibility to use the consent properly and not to import visitors into other messaging without separate consent, indemnity, limitation of liability, termination and data return.
16. Anything specific to Ontario real estate advertising or RECO rules for the QR sign and check-in page (the sign shows agent name and brokerage)?

Added 2026-11-03 (paper sheet, follow-up list, Google reviews; see `(C)Proposed PRD changes - follow-up and reviews.md`):
17. Paper consent: is a tick on a paper sheet, entered into the app later, valid express consent for a text? What must the sheet say, and what must we keep (the sheet itself, a photo of it, or just the typed record)?
18. Follow-up to visitors who answered "working with an agent: yes" (or left it blank): any restriction under the Ontario real estate rules (TRESA / RECO) or the brokerage's obligations?
19. Newsletter ("new listings", market updates) by email and/or text: does it need a separate checkbox (we assume yes)? Can an open-house visit alone ever support implied consent, and for how long?
20. Google review request: may it be included in the feedback text if the visitor consented to a feedback text, or does it need separate wording? Is a page-only link safer? (We will show the link to everyone regardless of rating, with no suggested review text and no reward.)

Added 2026-11-07 (built since the first draft):
21. The privacy page tells visitors that the listing link is tracked (a click count per visitor, shown to the agent). Is that line enough, and does click tracking in an SMS link need anything on the consent box itself?
22. After DELETE we keep a consent-withdrawal record (consent wording version, timestamps, IP and user agent) with no name or number. Is keeping that defensible as proof of consent and withdrawal, and for how long?
23. START/UNSTOP does not restore texting; the visitor must re-consent by checking in again. Acceptable, or must a START be honoured?
24. ScanIn has no contact email or mailing address of its own yet. The privacy page gives the event's agent as the contact. Does the page need ScanIn's own address and a named privacy contact? [Mailing address left blank by Philip for now; ScanIn operator entity still to be confirmed.]
25. The privacy page names Twilio (US) and Canadian hosting, and describes cookies. Please check those statements against what you would require.

Added 2026-11-08 (from my own pre-review of the drafts against public sources; full text and 20 merged questions in `(C)Legal pre-review.md`, which is not legal advice):
26. Is the check-in text a commercial message, and is it enough to identify the agent and brokerage in the text and link to a page with the mailing address, phone and unsubscribe?
27. Is the CASL private right of action in force? (Public sources disagree.)
28. Does ScanIn need its own named privacy contact and mailbox, and is it responsible in its own right for consent records, abuse logs and the phone-wide opt-out list?
29. Terms: please add or review the breach-notice clause, the processor clause (subprocessors, data return on account closure), an indemnity and a liability cap.
30. May "hosted in Canada" stay on the privacy page before the host confirms in writing, including backups?

## 7. What is built today (honest status, 2026-11-07)

- Built and live: check-in page, consent record, the check-in text (delivering since the Twilio fix), listing short links, agent login, password reset, agent dashboard with visitor list, paper-sheet entry, CSV export, STOP / DELETE / HELP keyword handling (webhook, signature-checked), event edit and cancel, public privacy and terms pages (linked from the check-in page footer and the consent line, and from the signup form), an admin overview showing counts only.
- Not built yet: the feedback text and form, the seller recap PDF, retention deletion (the 24-month sweep), consent wording versioning screen.
- The privacy and terms pages were written by me from the drafts and have NOT been reviewed by a lawyer.
- The consent wording currently live is a marked placeholder (`v0-draft`, "NOT YET REVIEWED BY A LAWYER"). It will be replaced with the approved version before any real visitor uses it.

## 8. Deadline

First real open house: **Sunday Nov 15, 2026, 2 to 4 PM** (moved from Nov 8; the time is assumed), 74 Pinehurst Drive, Welland. If approved wording is not ready, check-ins will be recorded on paper for that event and ScanIn kept as a backup with the check-in page switched off.
