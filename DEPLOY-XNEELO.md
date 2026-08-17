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

Kgomotso needs two bookmarks: **`/spsacademy/admin`** for registrations and
**`/spsacademy/admin-progress`** for progress reports. There is no sign-in link in the site's
navigation yet — that arrives with the shared page header, so until then the URL is how she
gets there.

## If something goes wrong

Delete `public_html/spsacademy/`. Nothing else on the domain was touched, so the client's site is
already back to exactly what it was. The database can stay — it is unreachable without the
config file.

## Known gaps at this point

- No self-service password reset — resets are done from a command line, which means locally.
- Course materials are not yet gated behind sign-in; the `DOCS` links in `pm-modules.js` are
  still empty and every module reads "Ask HR for a copy".
- The privacy notice at `/spsacademy/privacy` is a **draft** with three items marked *to confirm*.
