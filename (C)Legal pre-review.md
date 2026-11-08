# ScanIn legal pre-review (CASL, PIPEDA, terms)

**Status: pre-review from public sources, prepared 2026-11-08 for Philip Breton. NOT legal advice. No lawyer has seen it.** Two research passes (CASL and SMS; privacy and terms) read the code, the drafts and the live pages, and checked primary sources. "V" = read in a primary source. "I" = inference. Where a fetch was partial or a source 404'd, that is said. Purpose: get the drafts close, then give a lawyer a short list instead of a blank page.

Primary sources used: CASL (E-1.6, Justice Laws, current to 2026-10-19), CRTC FAQ and Compliance and Enforcement Information Bulletin 2012-548 (2012-549 only from search snippets), PIPEDA Schedule 1, OPC guidance on accountability, meaningful consent, cross-border processing and breach reporting (gd_pb_201810). TRESA/RECO text was not verified.

## A. CASL and the texts

| # | Finding | Severity | Source |
| --- | --- | --- | --- |
| A1 | The check-in text is probably a commercial electronic message. No exemption clearly fits a listing link (s.6(6) covers requested quotes and completed transactions). Do not rely on implied consent; keep express consent before the text. | Foundation | V s.1(2), s.6(6); I |
| A2 | The text names agent and brokerage and offers STOP, but has no mailing address and no phone, email or web contact. Fix: add them, or link to a page that shows them (the CRTC accepts a clear link). Make agent phone and brokerage address required profile fields. | Should-fix, possibly blocker | V s.6(2), Regs s.2, CRTC FAQ |
| A3 | The unsubscribe must work for at least 60 days and be honoured within 10 business days. STOP does this immediately. A link to an unsubscribe page is a nice extra. | OK | V s.6(3), s.11(3) |
| A4 | Consent wording needs: purpose, who is asking, mailing address plus phone/email/web, how to withdraw, and "sent through ScanIn on behalf of". The checkbox must be unticked and separate from the terms (it is). Option A bundles a feedback text; use Option B (two boxes) before message 2 is built. | Should-fix | V s.10(1), Regs s.4, 2012-548 |
| A5 | Paper sheet: use the printable with a per-row "OK to text" box, because it matches the app's `paper-v0-draft` wording. The markdown draft (one "initial" column for phone, text and email) does not match and is too thin. Add the brokerage address to the consent box. Keep the original sheets or a photo. | Blocker for entering paper rows as consent (if the sheets differ) | I |
| A6 | The sender bears the burden of proving consent. Express consent does not expire; keep records as long as you contact the person. The 24-month deletion default and the consent record must be aligned. | Should-fix (policy) | V s.13, CRTC FAQ |
| A7 | No phone verification means someone can type another person's number. Keep the one text as the only message to unverified numbers; send a later text only to people who replied or clicked the link. STOP is already phone-wide. | Should-fix before message 2 | I |
| A8 | Visitors who answer "working with an agent: yes": suppress follow-up (feedback text, newsletter, calls). RECO limits contacting another agent's client. Whether the single listing text is fine is a question for the lawyer. | Should-fix | RECO Bulletin 1.1 via search summary; I |
| A9 | Penalties: up to $1M (individual) and $10M (others) per violation. Whether the private right of action is repealed, suspended or in force could not be settled (Justice Laws shows ss.47-51 as repealed before coming into force; 2017 coverage says suspended). Ask the lawyer. | Informational | V s.20(4) |

## B. Privacy page and terms (PIPEDA)

| # | Finding | Severity | Source |
| --- | --- | --- | --- |
| B1 | Hosting claim: `privacy.blade.php:62` says "servers in Canada (Ontario)". Evidence is an IP lookup only; the host's written confirmation (including backups) is pending. Reword to "a Canadian hosting provider" until confirmed. | Blocker (live claim) | I |
| B2 | Retention: line 66 says "as long as the agent needs". No retention job exists (only queue, send, heartbeat). Do not promise 24 months until it is built; say deletion on request works and automatic deletion is being built. | Should-fix | V PIPEDA Sch.1 4.5.2-4.5.3 |
| B3 | Purpose mismatch: the check-in form says details are used "only" to send the listing and ask for feedback; the privacy page says "follow up". Use one wording, matched to the consent text. | Should-fix | code |
| B4 | DELETE description is incomplete. Kept: the row, feedback score, message timestamps, and consent records with visitor ID, agent ID, IP and user agent (pseudonymous, not anonymous). Not covered: Twilio's own copy and the agent's CSV exports. | Should-fix | code |
| B5 | Accountability: a named privacy contact must be published; ScanIn has no mailbox or address of its own. Suggested: a `privacy@scanin.ca` mailbox plus a named person. | Should-fix | V OPC accountability |
| B6 | Cross-border: disclosing Twilio (US) storage and foreign-authority access is adequate; no separate consent needed. A short line on the check-in form is a nice extra. | OK | V OPC cross-border |
| B7 | Breach: the agent (the organization in control) reports to the Commissioner and individuals; ScanIn tells the agent promptly and keeps records 24 months. The terms have no such clause, and the privacy page wrongly says "we will notify". | Blocker for the terms | V OPC breach guidance |
| B8 | Retained consent record after DELETE is defensible (inference) but needs a stated retention period, and the IP could be truncated or hashed on withdrawal. The wording "without your name or number" understates what is kept. | Should-fix | I |
| B9 | ScanIn decides some things itself (abuse logging, phone-wide opt-outs, consent records after DELETE). For those, say ScanIn is itself responsible. | Should-fix | I |
| B10 | Terms: no indemnity, no liability cap, no processor clause (instructions, subprocessors, confidentiality, access-request help, data return on termination), nothing on what suspension means for data access, no entity or address, no third-party-number warning. "May tell you" about deletion implies a notice the code does not send. | Should-fix | I |
| B11 | Cookies, IP and user agent are disclosed accurately. The Secure cookie flag depends on `SESSION_SECURE_COOKIE`; confirm on the server `.env`. | OK / check | code |

## C. Questions for the lawyer (merged, 20)

1. Is a QR check-in an "inquiry" supporting implied consent for message 1, or is express consent required regardless?
2. Is the agent the only "sender", or is ScanIn also a person "on whose behalf", affecting identification?
3. Is STOP plus a link page enough, and may the mailing address live on a linked page instead of in the text?
4. Do the Regulations' s.3(b) exemption or the s.6(6) exemptions cover the check-in text at all?
5. Is the CASL private right of action repealed, suspended or in force?
6. For unverified numbers, is a single listing text acceptable, or must consent be reconfirmed before any message?
7. Does an initialled or ticked paper column work as express consent, and what must we keep and for how long?
8. Does the anonymized post-DELETE record still prove consent? How long may we keep it, and should the IP be truncated?
9. How should RECO/TRESA affect follow-up to "working with an agent: yes" visitors?
10. Can the feedback text also ask for a Google review, and does it need its own box?
11. Do Twilio's STOP handling and our phone-wide STOP satisfy the 10-business-day rule across sender identities?
12. What is the lowest-risk posture if the pilot runs before approval (paper only, no texts)?
13. Does ScanIn need to be treated as an organization responsible in its own right (consent logs, abuse logs, phone-wide opt-out list)?
14. Is a named privacy officer required for a sole-proprietor service provider, and must the page name that person?
15. Is it acceptable to publish no operating entity, address or mailbox until ScanIn incorporates?
16. Is 24-month default retention defensible, and is "as long as the agent needs" acceptable in the interim?
17. What breach-notice deadline should ScanIn promise the agent, and should the duty sit in the terms?
18. Is "hosted in Canada" safe wording before the host confirms in writing, including backups?
19. Should the terms add an indemnity and liability cap, and are the current exclusions enforceable against a consumer's own claim?
20. What happens to visitor data when an agent closes the account or leaves a brokerage, and does the brokerage hold responsibility?

## D. What I would change before Nov 15 (nothing done yet)

1. Privacy page: hosting wording, retention, purpose alignment, breach line, DELETE description (text only).
2. Printable paper sheet: add the brokerage address to the consent box; decide on the QR and the red draft banner.
3. A `privacy@scanin.ca` mailbox (Philip creates it in cPanel; then the pages point to it).
4. Check-in text: sender contact line or a contact-page link, plus required agent phone and address fields. Not urgent while the online form stays off.
5. Keep ScanIn texts off real visitors until the lawyer approves the wording.
