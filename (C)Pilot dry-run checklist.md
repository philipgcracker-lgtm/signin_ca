# ScanIn pilot dry-run checklist

Prepared 2026-11-07 for Philip Breton. Pilot: Derek Breton, 74 Pinehurst Drive, Welland, Sun Nov 15 2026, 2-4 PM (time assumed). Goal: find what confuses a stranger at a door before Derek does. Tick each box; write the fail and what you saw next to it.

**Rule:** do this on a **throwaway event** (for example "1 Test Street"), not Pinehurst, so Pinehurst stays clean. Delete the test rows afterwards (see the end).

## 0. Before you start
- [ ] A second phone, on **cellular with wifi off**. That is how visitors will arrive.
- [ ] A number that has **never texted STOP**. A number that did is blocked in ScanIn *and* in Twilio. Your own (289) 821-3576 is blocked today: text START to (289) 402-8756 and ask me to clear its test row first.
- [ ] Logged in as an agent. Create the throwaway event (**New open house**), set it to start about 10 minutes ago, so it is live.

## 1. The door sign
- [ ] Open the event, click **Door sign**, print at the real size you will use.
- [ ] Scan it from about 1 m and 2 m, in normal and dim light, with two phones.
- [ ] The QR opens `scanin.ca/e/<slug>` (not an old or wrong event).

## 2. The visitor flow (second phone, cellular)
- [ ] The page loads in under 3 seconds. Address, date and time are right.
- [ ] The consent box is **unticked**. Submitting without ticking it is stopped, with a clear message.
- [ ] A bad number (for example `555 1234`) gives a clear message, not an error page.
- [ ] A name with an accent (for example `Zoë Tremblay`) is saved correctly.
- [ ] After submitting, the thank-you page shows and has a **View the listing** button that works.
- [ ] The text arrives within a minute. It names the address, agent and brokerage, and the link works.
- [ ] The privacy link at the bottom of the form opens in a new tab and shows the agent's name and email.
- [ ] Submitting the same number a second time at the same event does not create a duplicate row.

## 3. The agent dashboard (your phone and a laptop)
- [ ] The visitor row appears with name, number, time and **Text: Sent/Delivered**.
- [ ] After tapping the listing link, **Listing opened** shows 1 click.
- [ ] **Add from paper sheet** works, and the row shows a "Paper" badge.
- [ ] **Download CSV** opens in Excel/Sheets with the right columns and no odd characters.
- [ ] The page is readable on a phone (no sideways scrolling).

## 4. Replies from a visitor's phone
- [ ] **HELP** gets one reply that names ScanIn.
- [ ] **STOP** makes the row show **Opted out**.
- [ ] Check in again with the same number: the text is **Suppressed**, not sent, and no error page appears.
- [ ] **START** does *not* bring texts back (expected, by design).
- [ ] **DELETE** (use another test number) makes the row show "Personal details removed" and sends a confirmation text.

## 5. Edit and cancel
- [ ] **Edit** the throwaway event's time. The QR and link stay the same.
- [ ] **Cancel** it (button at the bottom of the edit page). Scan the QR: a visitor sees a "not found" page. Write down how that page looks, because a stranger could land on it.
- [ ] **Reopen** brings the check-in page back.

## 6. The admin page (you)
- [ ] The overview counts match what you just did (check-ins, texts, opted out).
- [ ] **Scheduler: Running** and **Texts stuck in queue: 0**.

## 7. Day-of kit for Derek
- [ ] Two printed signs, plus the **paper sign-in sheet** (`(C)Paper sign-in sheet.md`). Fill in the brokerage name and address and Derek's phone and email first, then print at least 2 copies.
- [ ] Derek has read the "Notes for Derek" in that file: no initials means no follow-up, keep the originals, no bulk texts from his own phone.
- [ ] Do a trial run with Derek: someone signs the sheet, Derek enters the row with **Add from paper sheet** and leaves the "OK to text" box **unticked**.
- [ ] Derek is logged in on his phone and knows where **Add from paper sheet** is.
- [ ] Derek knows what to say if someone asks about the text: "Reply STOP any time."
- [ ] Charged phone, and a plan if the text is slow: the thank-you page still has the listing button.

## 8. Go / no-go
Go if sections 1 to 4 pass and the consent wording is settled with the lawyer. If the wording is not settled (no lawyer has approved it), run the pilot on the paper sheet, enter rows with the text box unticked, and keep the ScanIn check-in page off for real visitors. The paper sheet's own wording is unreviewed too: you accept that risk.

## 9. Clean up afterwards
- [ ] Tell me the throwaway event's slug and how many test rows it has. I delete them with the safe marker, which refuses if the count is wrong.
- [ ] Confirm the Pinehurst event is empty before Sunday and set to Nov 15.
