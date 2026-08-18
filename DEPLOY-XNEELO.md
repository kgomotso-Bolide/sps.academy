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

To stand up a new academy:

1. Create `public_html/<name>academy/` and deploy the code there.
2. Copy `lib/config.sample.php` to `~/private/<name>academy-config.php`.
3. Set `'tenant' => 'fungi'` (or `equinix`, `maziv`) — the slug must already be in the
   `tenants` table; the installer seeds all four on any installation.
4. Use the **same** database name, user and password as SPS. One database, one row per
   company, a `tenant_id` on everything.
5. Give it its **own** `ip_pepper`. Sharing one would let the same hashed address be
   correlated across two companies' records, which is precisely what the hash is for.
6. Set `setup_token`, open `/<name>academy/setup`, run it, then empty the token.

Check it worked by signing in and looking at the registrations list. If you see SPS's
registrations under Fungi's logo, stop and check step 2 — that is the failure this is built
to prevent, and it should be impossible.
