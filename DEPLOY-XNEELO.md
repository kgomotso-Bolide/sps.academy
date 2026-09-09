# Putting SPS Academy on centenarynetworks.com/spsacademy

Written 17 Aug 2026, for the **folder** layout — the interim arrangement while the
subdomains wait on GoDaddy access. It moves to `sps.centenarynetworks.com` after the first
intake, and the code already works in both places without changes.

`.md` files are excluded from the deploy, so this note never reaches the server.

---

## The one rule

**Everything goes in `public_html/spsacademy/`. Nothing outside it is ours.**

The folder name is yours to choose — nothing in the code refers to it. Every link on the
site is relative, and the session cookie path and the sign-in redirects are all derived from
wherever the site finds itself. `spsacademy` gives `centenarynetworks.com/spsacademy/`.

`centenarynetworks.com` is a live site — *Youth Work Placement & Entrepreneurship Academies* —
and it is not ours to change. In particular, do not edit, replace or delete:

| Leave alone | Why |
|---|---|
| `public_html/.htaccess` | Holds the existing site's rewrite rules. Ours lives in `spsacademy/` and does not need theirs touched. |
| `public_html/index.html` | Their homepage. |
| `public_html/assets/` | Their images. |
| Anything else at the root | Not ours. |

If a deploy ever offers to delete files it does not recognise, **say no.** The
`delete_removed` option on the deploy workflow is off by default and must stay off here —
with the wrong target directory it would erase the client's site.

## Why a folder works at all

Checked against the live site before committing to this, because it was not obvious:

- The root site rewrites unknown paths to its homepage — `/spsacademy`, `/anything` all return the
  Centenary homepage with a 200.
- **But that rule exempts real directories.** `/assets/` returns **403**, not the homepage.
- So once `public_html/spsacademy/` genuinely exists, requests to `/spsacademy/…` are served from it, and
  Apache does not inherit the parent's rewrite rules into a subdirectory that has its own.

That is what makes this safe. If the root site is ever rebuilt with an unconditional
catch-all, `/spsacademy/` would stop working — the symptom would be the Centenary homepage
appearing at our URLs, and the fix would be a conversation, not an edit to their file.

## Steps

**1 — Build the archive.** From this folder:

```
php tools/make-deploy-zip.php
```

It writes `sps-deploy-<date>.zip` one level up and prints what went in.

**Do not zip this folder by hand.** It contains `_drafts/` (client emails), `.git/` (every
version of everything) and `lib/config.local.php` (the IP hashing key) — a plain zip uploads
all three to a public web server, and `.gitignore` does nothing to stop it. The script also
guarantees the four `.htaccess` files are included, which is what a hand-made zip is most
likely to drop: a leading dot hides them in Explorer, and losing the root one breaks every
link on the site *and* removes the rule that stops `/lib/` being browsable. The script
refuses to write the archive if any of that is wrong.

**2 — Upload and extract.** Create `public_html/spsacademy/` in the Xneelo file manager,
upload the zip **inside that folder**, and extract it there. The files sit at the top level
of the archive, so you should end up with `spsacademy/index.html` — *not*
`spsacademy/sps/index.html`. Delete the zip afterwards. If you see a nested folder, move the
contents up one level before going further.

**3 — Create the database.** In the Xneelo panel: one MySQL database and one user. Note the
database name, user and password. **Do not send them over WhatsApp or paste them into chat** —
they go straight into the file in step 4.

**4 — Place the configuration.** Copy `lib/config.sample.php`, fill it in, and upload it over
SFTP to:

```
~/private/sps-config.php          <-- NOT inside public_html
```

Create `~/private/` if it does not exist. This is deliberate: `public_html/spsacademy/private/`
and `public_html/private/` are both reachable over HTTP, and the loader **refuses** a config
found inside the web root rather than using it. Also create `~/private/logs/`.

Two values to generate, both with `php -r "echo bin2hex(random_bytes(32));"` or any random
64-character hex string:

- `ip_pepper` — set once and never changed; changing it orphans every stored hash.
- `setup_token` — set for the next five minutes only.

**5 — Run the installer.** Xneelo gives SFTP but no shell, so this happens in a browser.
Open **`https://centenarynetworks.com/spsacademy/setup`**, enter the token, and give Kgomotso's name
and email as the first administrator. It creates the tables, seeds the four companies, and
shows a password **once** — write it down.

The installer only ever creates. It cannot drop or empty a table, and running it twice
reports that there was nothing to do.

**6 — Switch the installer off.** Go back to `~/private/sps-config.php` and set
`'setup_token' => ''`. Reload `/spsacademy/setup` and confirm it now returns a **404**. Until you
do this, anyone who learns the token can reach it.

**7 — SPF, so the notification emails arrive.** At GoDaddy, the domain's SPF currently
authorises Google only. Mail sent from Xneelo will fail SPF and land in spam. This does not
block launch — the database is the record, the email is only a notification — but it should
be fixed before Kgomotso relies on the alerts.

## Updating a site that is already live

Deploying new code does **not** create new database tables. Two releases have needed a
second pass, and both are the same three moves.

**Learner accounts and password reset, 18 Aug 2026 — three new tables.** `enrolments`,
`learner_progress` and `password_resets`. Until they exist, everything to do with learner
accounts is unavailable and the admin pages say so — see "If you deploy before you migrate"
at the end of this file. Nothing returns a 500, and registrations and progress reports carry
on working untouched.

1. Deploy the code as usual (Actions → *Deploy SPS Academy*, ref `xneelo-backend`).
   `setup.php` and `phpcheck.php` were deleted off the live server by hand after the first
   install; the deploy puts them back, and they are both **404 while `setup_token` is empty**,
   so putting them back changes nothing until you choose to use them.
2. Set `'setup_token' => '<a fresh random value>'` in `~/private/sps-config.php`, open
   `/spsacademy/setup`, and run it. It reports **"3 tables created"** and leaves the existing
   five alone — the installer only ever creates. `/spsacademy/phpcheck.php` will now say
   `tables  9 of 9 present`, and names any that are missing.
3. Empty `setup_token` again and confirm `/spsacademy/setup` is a 404.

**Also bump `policy_version`** in `~/private/sps-config.php` to `2026-08-18`. The privacy
notice gained a section on learner accounts and progress; a consent row records the version
that was on screen, so leaving it at `2026-08-17` records agreement to wording that no longer
matches the page. Consents already stored keep their old version, which is the point.

**The invite route — one new table, `account_invites`.** Same shape as the release above:
deploy the code, set a fresh `setup_token`, open `/setup`, run it, empty the token again. Until
that is done, "Enrol" still works either way, but choosing "Email them a link" creates the
account and then reports the invite email could not be sent — nothing 500s, see `db_optional()`
in `lib/db.php` — so use "Show the password here" until the table exists. No `policy_version`
bump needed this time: nothing new is collected, only how an existing credential is delivered.

**File-backed materials and self-check quizzes, 1 Sep 2026 — six new tables.**
`material_files`, `quizzes`, `quiz_questions`, `quiz_choices`, `quiz_attempts`,
`quiz_attempt_answers`. Same three-step shape as above. Until they exist:
`admin-materials.php` still works for links (the existing behaviour, untouched) but an
uploaded file cannot be saved; `admin-quizzes.php`, `quiz.php` and the "Check yourself"
card on `module.html` all degrade to "nothing here yet" rather than a 500 — see
`db_optional()` in `lib/db.php`, and specifically the note on
`materials_slots_for_course()` in `lib/materials.php` about why the link half and the
file half of a material slot are wrapped in **separate** `db_optional()` calls: a missing
`material_files` table must not take the already-working `materials` link path down with it.

Two things this release also needs, beyond the usual three steps:

- **`.user.ini`** ships with the code and needs no separate action — but its effect is
  not instant (PHP-FPM's `user_ini.cache_ttl`, roughly five minutes) and Xneelo's package
  may cap it regardless of what the file asks for. Check the *live* number on
  `admin-materials.php` (it reads `ini_get('upload_max_filesize')` fresh on every load) a
  few minutes after deploying — that is the real ceiling, not the figures in `.user.ini`.
  Do one real upload test on the live account before deciding whether any given video goes
  file-backed, rather than assuming the request in `.user.ini` was honoured.
- **Bump `policy_version`** in `~/private/sps-config.php`. Quiz attempts are new personal
  data, kept without a `purge_after` on the same basis as `learner_progress` (part of the
  learner record) — the same reason the 18 Aug release needed the bump, and `privacy.php`
  has been updated to say so.

**Reading a module on the page, 2 Sep 2026 — one new table.** `topic_sections`, holding the
written content for one area of one topic, so a learner can work through a module a section at
a time instead of opening the whole guide. Same three-step shape as every release above: deploy,
set a fresh `setup_token`, open `/setup`, run it, empty the token again.

Until the table exists, `admin-lessons.php` and `lessons.php` degrade to "nothing written yet"
rather than a 500 — `db_optional()` again — and the module page renders exactly as it did
before, because `lessons.js` does nothing at all when the server has nothing to give it. That
is also what a module with no content written looks like, which is the normal state for ten of
the eleven modules, so it is worth checking the table really was created rather than assuming
an empty page means an empty table.

**No `policy_version` bump for this one.** Nothing new is collected about a learner: the reading
is content going out, not data coming in. The one record it creates is a `section.read` audit
line per module opened, which is the same kind of "which material did this learner open, and
when" entry `material.opened` has made since 1 Sep and which the privacy notice already
describes.

**Letters to learners, 9 Sep 2026 — one new table.** `letters_sent`, recording which
letter has already gone to which learner. Same three-step shape: deploy, set a fresh
`setup_token`, open `/setup`, run it, empty the token again. `/spsacademy/phpcheck.php`
will then say `tables  19 of 19 present`.

Two letters now go out automatically:

- **A welcome letter when a learner is enrolled**, confirming what they have been
  registered for — the course, the eleven modules, the credit total. When the account is
  new and you choose "Email them a link", this *is* the invite: one email carrying both
  the confirmation and the set-a-password link, rather than two arriving together. When
  you choose "Show the password here", the same letter goes out without any link, and
  **never with the password** — that one is read off the screen and handed over, and it
  is not something this site will put in an email.
- **A module report when a learner finishes a module's self-checks**, listing each topic,
  their best score, and the module percentage. It is sent **once per module** — the first
  time every published quiz in that module has an attempt. Retaking anything afterwards
  never sends a second copy. Eleven letters over the whole qualification, not one per
  quiz, and not one per attempt.

Until the table exists **nothing is sent at all** — `letter_send_once()` will not send a
letter it cannot record, because a letter sent without a record is one the learner gets
again on the next page load. Enrolment and quizzes carry on working untouched; the letters
simply do not go. `db_optional()` in `lib/db.php`, as everywhere else.

**No `policy_version` bump.** Nothing new is collected. What changes is that results the
learner can already see on `/my` are now also sent to the learner's own address, which is
the address they gave and the purpose they were registered for. Results are never sent to
anybody else.

### These letters will land in spam until DNS is fixed

This is the same SPF problem described at the top of `lib/mail.php`, and it now matters
much more, because these letters go to **learners** rather than to an internal address
somebody knows to check.

`centenarynetworks.com` receives mail through Google Workspace, so its SPF record
authorises Google's servers to send as that domain. Xneelo's server is not in that record.
Mail sent from the academy therefore fails SPF and is likely to be filed as spam or
rejected outright.

**At GoDaddy, add a TXT record for the host the academy actually sends from.** Ask Xneelo
support to confirm their outbound mail host first — do not guess it, and do not copy the
line below without checking, because an SPF record naming the wrong server is worse than
none:

```
Type: TXT
Name: spsacademy            (or whichever host the site is served from)
Value: v=spf1 include:<the include Xneelo gives you> -all
TTL: 1 hour
```

Then, before anyone is told this feature exists:

1. Enrol one test learner and confirm the welcome letter arrives in a **real inbox**, not
   the spam folder.
2. Have them finish one module's quizzes and confirm the report arrives.
3. Only then mention it to learners. A welcome letter that silently goes to spam is worse
   than no welcome letter, because everyone assumes it was sent and nobody checks.

Until that is done, treat both letters as best-effort. Every one of them is recorded in
`letters_sent` with a `delivered` flag, so what was attempted is always answerable, and
the learner's results are on `/my` regardless.

## Check before telling anyone

- [ ] `https://centenarynetworks.com/` — the **Centenary homepage**, unchanged.
- [ ] `https://centenarynetworks.com/assets/cn-logo.png` — still loads.
- [ ] `https://centenarynetworks.com/spsacademy/` — the SPS Academy homepage.
- [ ] `/spsacademy/courses`, `/spsacademy/contact`, `/spsacademy/privacy` — all load, styled.
- [ ] `/spsacademy/lib/config.sample.php` — **404**. Then try `/spsacademy/tools/migrate.php` — **404**.
- [ ] `/spsacademy/setup` — **404** (step 6 done).
- [ ] Send one real registration through `/spsacademy/contact`, then find it in `/spsacademy/admin`.
- [ ] Send one progress report from `/spsacademy/pm-progress`, then find it in
      `/spsacademy/admin-progress`.
- [ ] Sign out, then load `/spsacademy/admin` — it must send you to the sign-in page.

### Learner accounts

- [ ] `/spsacademy/account.php` signed out — returns `{"in":false,...}`, not a 404 and not a 500.
- [ ] On `/spsacademy/courses`, signed out, the nav shows **Sign in**.
- [ ] On `/admin`, press **Enrol this person** on a real registration. The password panel
      appears **once**. Write the password down before leaving the page — it is not recoverable.
- [ ] Sign in as that learner: you land on `/spsacademy/my`, greeted by name, with the course
      listed and a progress bar.
- [ ] Change the password from the box on `/my`, sign out, and sign in with the new one.
- [ ] Open `/spsacademy/module?m=KM-01`, tick a topic, reload — the tick is still there. Then
      open the same page in a **different browser** signed in as the same learner; the tick is
      there too. That is the whole point of the release, and it is the one check that proves it.
- [ ] `/spsacademy/my` signed out — redirects to sign-in, does not render.

### Getting back in

- [ ] `/spsacademy/login` shows a **Forgotten your password?** link.
- [ ] Ask for a reset with an address that has **no** account. The page must say exactly what
      it says for a real one — *"If that address has an academy account…"*. Anything that
      distinguishes the two turns the form into a way to find out who works at SPS.
- [ ] Ask for one with a **real** address and see whether the mail arrives, **including the
      junk folder**. This is the one thing that cannot be tested from here: if it does not
      arrive, that is the missing SPF record below, not a bug.
- [ ] Open the link, set a password, and confirm you land signed in on `/my`. Then open the
      same link again — it must say the link no longer works.
- [ ] `/admin-users` — the **Accounts** page. Press **Set a new password** on a test account,
      confirm the password appears once, and sign in with it.
- [ ] Switch a test account off, confirm it cannot sign in, and switch it back on.
- [ ] Confirm the **Switch this account off** link is absent on your own row.
- [ ] On `/admin`, enrol a **new** registration with **"Email them a link"** selected. The
      notice says an email is on its way rather than showing a password. Check whether it
      actually arrives, **including junk** — same honest limitation as the reset link above.
- [ ] Open that link at `/invite?t=…`, set a password, and confirm you land signed in on `/my`.
      Then open the same link again — it must say the link no longer works, same as `/reset`.
- [ ] Let an invite link sit unused: after seven days it should read the same "no longer
      works" message (not worth waiting for on a live check — read the code instead: it is
      `INVITE_TTL_SECONDS` in `lib/invite.php`, deliberately longer than the reset link's one
      hour because a new starter does not necessarily open the email the same day).

### Course material and self-check quizzes

- [ ] `/admin-materials` shows the **live** upload cap ("Files can be up to…") — confirm it
      is a real number, not "any size (no server limit set)", which would mean neither
      `.user.ini` nor the host's own default is taking effect as expected.
- [ ] Upload a small PDF to a real module's guide slot, save, reload the page and confirm it
      shows as saved. Open the module on the public site while signed in and enrolled — the
      PDF should open. Sign out and confirm it does not.
- [ ] Paste a Drive link into a **different** slot on the same module, save, and confirm both
      the file and the link work side by side.
- [ ] Upload a file to the SAME slot as an existing link (or the reverse) and confirm the new
      one wins and the old one is actually gone — check `/admin-materials` shows only one.
- [ ] On `/admin-quizzes`, write a two-question quiz for one module, publish it, and confirm
      the **Publish** option is unavailable on a module with no questions yet.
- [ ] As a signed-in, enrolled learner, open that module and confirm a **Check yourself** card
      appears with a working link to the quiz. View the page source on the quiz page itself —
      confirm nothing marks which choice is correct before you submit.
- [ ] Take the quiz twice with different answers, and confirm `/my` shows the **better** of
      the two scores, not the more recent one.
- [ ] On `/admin-quizzes` → **View results**, confirm that attempt shows up, and that the CSV
      export downloads and matches.

### Reading a module on the page

- [ ] On `/admin-lessons`, choose **KM-01**. The five areas of KT01 should already have text in
      them, each with **Learners can read this** ticked.
- [ ] Signed in and enrolled, open `/module?m=KM-01`. The five areas under the first topic
      should now be clickable, with a caret, and expand to show the reading. Every other
      topic's areas stay plain text — that is correct, nothing is written for them yet.
- [ ] Sign out and open the same page. The areas must go back to being plain headings, with no
      reading behind them and no caret.
- [ ] Type a line containing `<b>test</b>` into any area, save, and confirm the learner sees
      those characters as text rather than bold. Content written here is never markup.

> **Until SPF is fixed, tell Kgomotso to use `/admin-users`.** The self-service reset is built
> and correct, but it depends on mail that this server cannot yet get delivered. The Accounts
> page needs no email at all and is the route that always works. This replaced the old promise
> on the sign-in page, which could not be kept: the only way to set a password used to be
> `tools/make-user.php`, and Xneelo has no shell to run it from.

Kgomotso needs two bookmarks: **`/spsacademy/admin`** for registrations and
**`/spsacademy/admin-progress`** for progress reports. Learners need only the site itself —
**Sign in** is now in the navigation on every page, added by `profile.js` rather than by
editing seventeen files, so it appears wherever that script is loaded.

## If the .php pages return 503 and the .html pages are fine

Seen on 17 Aug 2026. It means the upload worked and Apache is happy — `.html`,
`.css` and images all served 200 — and **PHP is not executing**. Work through it in
this order, stopping as soon as one of them answers:

**1. Load `/spsacademy/phpcheck.php`.** It depends on nothing — not `lib/`, not the
configuration, not the database.

- **It prints a report** → PHP works, and the fault is in our code or the config. Read
  what it says about the configuration file and the extensions.
- **It also returns 503** → PHP is not running for this folder. Continue.

**2. Rename `.htaccess` to `_htaccess` and reload `/spsacademy/phpcheck.php`.**

- **Now it works** → the fault was a directive in our `.htaccess`. Tell me which
  server this is and I will fix it; put the file back either way, because without it
  `/spsacademy/lib/` becomes browsable.
- **Still 503** → nothing of ours is involved. Continue.

**3. Check the Xneelo control panel for the domain's PHP setting.** A package with no
PHP version selected, or one pointing at a version that is no longer running, gives
exactly this: static files fine, every `.php` a 503. Set it to **PHP 8.0 or newer**.

**4. Check file permissions.** Extracting a zip can leave files group-writable, and
shared hosting refuses to execute those. Files should be **644**, directories **755**.

If steps 3 and 4 are both right and it still 503s, it is Xneelo's side — the PHP pool
for the account is not answering, and their support can see that from the server logs
in a way we cannot from outside.

**Delete `phpcheck.php` once the site is working.** It is harmless but it is a
diagnostic, not part of the site.

## If something goes wrong

Delete `public_html/spsacademy/`. Nothing else on the domain was touched, so the client's site is
already back to exactly what it was. The database can stay — it is unreachable without the
config file.

## Known gaps at this point

- No self-service password reset — resets are done from a command line, which means locally.
- Course materials are not yet gated behind sign-in; the `DOCS` links in `pm-modules.js` are
  still empty and every module reads "Ask HR for a copy".
- The privacy notice at `/spsacademy/privacy` is a **draft** with three items marked *to confirm*.

### If you deploy before you migrate

Nothing breaks. The code guards every query against a table that does not exist yet — see
`db_optional()` in `lib/db.php` — so `/admin` still lists registrations, `/admin-progress`
still works, and a learner can still sign in. What you get instead is a red notice on the
admin pages saying the database has not been updated, and the **Enrol** control reads
*"unavailable until /setup is run"*. Run the installer and both go away.

This was checked by dropping the three tables and running the whole site against them.

## Browser caching — read this before wondering why a deploy "did nothing"

The parent site sets `Cache-Control: max-age=2592000` on CSS and JavaScript, and those
directives **are** inherited into our folder (mod_rewrite rules are not; mod_expires ones
are). That is thirty days. On 18 Aug 2026 the learner-accounts deploy uploaded a new
`styles.css`, `profile.js` and `pm-progress.js`, the server served them correctly to anyone
who asked, and every browser that had visited the site before did not ask. The dashboard
rendered as unstyled text, the **Sign in** link never appeared, and progress carried on
going to `localStorage`. The deploy log was clean and the server was right — the release was
simply invisible.

Two things fix it, and both are in place:

- **`.htaccess` now sets five minutes with revalidation** on `.css` and `.js`, scoped to our
  folder only. Images and PDFs keep the parent's long cache.
- **Every asset reference carries `?v=20260818`.** The `.htaccess` change only helps a
  browser that makes a request, and a browser holding a thirty-day copy makes none — so the
  URL had to change once to break everyone out of it.

**You should not need to bump that version again.** With five-minute revalidation in place a
new deploy is picked up on the next page load. Bumping it is harmless if you ever want to be
certain — change every `?v=` in the `.html` and `.php` files to the same new date:

```
grep -rlE '\?v=[0-9]{8}' *.html *.php | xargs perl -pi -e 's/\?v=[0-9]{8}/?v=YYYYMMDD/g'
```

**To check a deploy actually reached people**, not just the server:

```
curl -sI https://centenarynetworks.com/spsacademy/styles.css | grep -i cache-control
```

It should say `max-age=300, must-revalidate`. If it says `max-age=2592000`, our `.htaccess`
did not take effect and the release will be invisible to returning visitors.

## Adding a second academy (Fungi, Equinix, Maziv)

Each academy is its own installation in its own folder — `public_html/fungiacademy/`,
`public_html/equinixacademy/` — sharing one MySQL database, one Xneelo account, and one home
directory. They are kept apart by the `tenant` line in their configuration file, and by
nothing else. So the configuration file is the thing that must not be shared.

**The filename is derived from the folder the site is installed in.** `public_html/fungiacademy/`
looks for `~/private/fungiacademy-config.php` (or `~/fungiacademy-config.php`) and will accept
nothing else. If it is missing, the site refuses to start and says which file it wanted.

That is deliberate. Before this, the name was hardcoded as `sps-config.php`, and a second
academy would have walked up, found SPS's file, and served **SPS's registrations, learners and
progress under Fungi's branding** — with no error, because nothing had gone wrong as far as
the code was concerned. A missing config has to be a loud failure, never a quiet substitution.

`sps-config.php` is still accepted, but only for a folder called `sps` or `spsacademy`, because
that installation already exists with a file of that name.

### Where the code comes from

The other academies do **not** get a hand-written copy of the back end. Every `.php` file
except `lib/brand.php` is identical across all four sites, and it is pushed from here:

```
php tools/sync-backend.php --check           # what has drifted, changes nothing
php tools/sync-backend.php --apply ../fungi  # write it
```

SPS is the source of truth. The tool copies `lib/`, `schema/`, the pages, `profile.js`,
`pm-progress.js`, `.htaccess` and `tools/`, plus the block of `styles.css` between the
`SHARED ACADEMY STYLES` markers — so each site keeps its own palette and gets the same
components. It refuses to copy `lib/brand.php`, and it refuses to run at all if any file it
would copy is uncommitted here, so what lands in another repository can always be traced back
to a commit in this one.

It does **not** commit, push, or deploy. Read the diff in the target repository first.

Run `--check` before any release that touches `lib/`. A security fix that reaches one academy
and not the other three is the failure this whole arrangement exists to prevent.

### The steps

1. Create `public_html/<name>academy/` and deploy the code there.
2. Copy `lib/config.sample.php` to `~/private/<name>academy-config.php`.
3. Set `'tenant' => 'fungi'` (or `equinix`, `maziv`) — the slug must already be in the
   `tenants` table; the installer seeds all four on any installation.
4. Use the **same** database name, user and password as SPS. One database, one row per
   company, a `tenant_id` on everything.
5. Give it its **own** `ip_pepper`. Sharing one would let the same hashed address be
   correlated across two companies' records, which is precisely what the hash is for.
6. Set `setup_token`, open `/<name>academy/setup`, run it, then empty the token.
7. Write that site's `lib/brand.php` — the company name, logo, contact details and form
   placeholders. It is the only per-site PHP file, and the site will not render without it;
   a missing key is a fatal error naming the key, never a blank on the page.

Check it worked by signing in and looking at the registrations list. If you see SPS's
registrations under Fungi's logo, stop and check step 2 — that is the failure this is built
to prevent, and it should be impossible. The Fungi deploy workflow also checks it
automatically: after uploading it fetches `/fungiacademy/contact` and fails the build if the
page comes back carrying SPS's branding.
