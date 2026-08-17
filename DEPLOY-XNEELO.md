# Putting SPS Academy on centenarynetworks.com/sps

Written 17 Aug 2026, for the **folder** layout — the interim arrangement while the
subdomains wait on GoDaddy access. It moves to `sps.centenarynetworks.com` after the first
intake, and the code already works in both places without changes.

`.md` files are excluded from the deploy, so this note never reaches the server.

---

## The one rule

**Everything goes in `public_html/sps/`. Nothing outside it is ours.**

`centenarynetworks.com` is a live site — *Youth Work Placement & Entrepreneurship Academies* —
and it is not ours to change. In particular, do not edit, replace or delete:

| Leave alone | Why |
|---|---|
| `public_html/.htaccess` | Holds the existing site's rewrite rules. Ours lives in `sps/` and does not need theirs touched. |
| `public_html/index.html` | Their homepage. |
| `public_html/assets/` | Their images. |
| Anything else at the root | Not ours. |

If a deploy ever offers to delete files it does not recognise, **say no.** The
`delete_removed` option on the deploy workflow is off by default and must stay off here —
with the wrong target directory it would erase the client's site.

## Why a folder works at all

Checked against the live site before committing to this, because it was not obvious:

- The root site rewrites unknown paths to its homepage — `/sps`, `/anything` all return the
  Centenary homepage with a 200.
- **But that rule exempts real directories.** `/assets/` returns **403**, not the homepage.
- So once `public_html/sps/` genuinely exists, requests to `/sps/…` are served from it, and
  Apache does not inherit the parent's rewrite rules into a subdirectory that has its own.

That is what makes this safe. If the root site is ever rebuilt with an unconditional
catch-all, `/sps/` would stop working — the symptom would be the Centenary homepage
appearing at our URLs, and the fix would be a conversation, not an edit to their file.

## Steps

**1 — Upload the site.** Deploy the `xneelo-backend` branch to `public_html/sps`. Run the
workflow with **`dry_run` on first** and read the list. Expect roughly 40 files; if it
mentions anything at the root, stop.

**2 — Create the database.** In the Xneelo panel: one MySQL database and one user. Note the
database name, user and password. **Do not send them over WhatsApp or paste them into chat** —
they go straight into the file in step 3.

**3 — Place the configuration.** Copy `lib/config.sample.php`, fill it in, and upload it over
SFTP to:

```
~/private/sps-config.php          <-- NOT inside public_html
```

Create `~/private/` if it does not exist. This is deliberate: `public_html/sps/private/`
and `public_html/private/` are both reachable over HTTP, and the loader **refuses** a config
found inside the web root rather than using it. Also create `~/private/logs/`.

Two values to generate, both with `php -r "echo bin2hex(random_bytes(32));"` or any random
64-character hex string:

- `ip_pepper` — set once and never changed; changing it orphans every stored hash.
- `setup_token` — set for the next five minutes only.

**4 — Run the installer.** Xneelo gives SFTP but no shell, so this happens in a browser.
Open **`https://centenarynetworks.com/sps/setup`**, enter the token, and give Kgomotso's name
and email as the first administrator. It creates the tables, seeds the four companies, and
shows a password **once** — write it down.

The installer only ever creates. It cannot drop or empty a table, and running it twice
reports that there was nothing to do.

**5 — Switch the installer off.** Go back to `~/private/sps-config.php` and set
`'setup_token' => ''`. Reload `/sps/setup` and confirm it now returns a **404**. Until you
do this, anyone who learns the token can reach it.

**6 — SPF, so the notification emails arrive.** At GoDaddy, the domain's SPF currently
authorises Google only. Mail sent from Xneelo will fail SPF and land in spam. This does not
block launch — the database is the record, the email is only a notification — but it should
be fixed before Kgomotso relies on the alerts.

## Check before telling anyone

- [ ] `https://centenarynetworks.com/` — the **Centenary homepage**, unchanged.
- [ ] `https://centenarynetworks.com/assets/cn-logo.png` — still loads.
- [ ] `https://centenarynetworks.com/sps/` — the SPS Academy homepage.
- [ ] `/sps/courses`, `/sps/contact`, `/sps/privacy` — all load, styled.
- [ ] `/sps/lib/config.sample.php` — **404**. Then try `/sps/tools/migrate.php` — **404**.
- [ ] `/sps/setup` — **404** (step 5 done).
- [ ] Send one real registration through `/sps/contact`, then find it in `/sps/admin`.
- [ ] Sign out, then load `/sps/admin` — it must send you to the sign-in page.

## If something goes wrong

Delete `public_html/sps/`. Nothing else on the domain was touched, so the client's site is
already back to exactly what it was. The database can stay — it is unreachable without the
config file.

## Known gaps at this point

- `pm-progress.html` still posts to FormSubmit; it has not been moved to the database yet.
- No self-service password reset — resets are done from a command line, which means locally.
- Course materials are not yet gated behind sign-in; the `DOCS` links in `pm-modules.js` are
  still empty and every module reads "Ask HR for a copy".
- The privacy notice at `/sps/privacy` is a **draft** with three items marked *to confirm*.
