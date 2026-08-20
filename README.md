# SPS · AI Skills Programme — Website Blueprint

> **AI Skills, Built In-House**
> SPS Academy is SPS's own training academy, for SPS staff: a catalogue of 800
> qualifications and courses, fully funded by the company and delivered online. Accredited
> qualifications are delivered in association with Centenary Networks, a QCTO-accredited
> Skills Development Provider.

This repository is the public-facing website for the programme. This README is the **blueprint**:
it captures the positioning and messaging spine and documents how the site is built so anyone can
maintain or extend it.

---

## 1. The Spine (positioning — do not drift from this)

**What this is:** an **in-house academy**, not a product being sold. The site speaks to SPS staff
in SPS's voice: courses are **fully funded by SPS**, you **register your interest with HR**,
and the primary action is **Upskill Yourself** rather than "Talk to Our Team". The catalogue runs to
**800 qualifications and courses** (20 live on the site today), including the nationally accredited
**Occupational Certificate: Project Manager** — which is the one course open for enrolment, and the
first card in every catalogue. Computer Technician was withdrawn on 19 Aug 2026 (Kgomotso).

> ⚠️ **Do not reintroduce the "credit-bearing" framing.** An earlier revision positioned the product
> as credit-bearing modules laddering toward a qualification, with B-BBEE Skills Development returns
> as the buying reason. That was reverted (30 Jul 2026, boss's instruction): **no "credit-bearing,"
> no "stackable credits," no B-BBEE sales angle.** We offer courses, straight. Credit counts may
> still appear as a factual attribute of the accredited qualification (NQF 5, 282 credits) — never
> as the product model.

The three supporting pillars on the home page:

1. **Fully Funded** — SPS pays; you register your interest and square the timing with your manager.
2. **Built Around Your Job** — every course is grounded in the work we actually do, so what you learn this week you can use next week.
3. **Online or In Person** — study from home, from any site, or in a classroom with a facilitator.

> ⚠️ **Structure is shared with the Fungi and Equinix academies.** The three sites run the same pages,
> components and JS; only brand, palette, logo and industry vocabulary differ. Change the structure
> on one and it should be ported to the other two, or they drift apart.

**Audience:** SPS staff — a **solar / energy business**. Everyone from installers and PV technicians
to field, sales, admin and management. No technical background assumed.

**The energy-sector line:** *"Installers, field teams, sales and support staff — all building real
technical and AI capability. Practical courses that fit around the working day, and a credentialed
path for anyone who wants to take it further as the energy sector goes digital."*

**Industry vocabulary** (what changes from the Fungi original): metering → solar/PV.

> The Skills Gap tool was removed on 19 Aug 2026. Its eight role keys lived in `skills-gap.js`,
> `profile.html` and `profile-page.js` and had to agree; all three have been unwired, and the
> role/years/qualification/background block has gone from the profile form with it.

**Primary calls to action:** `Explore Courses` · `Upskill Yourself`

### Approved hero copy (current)
- **Badge / eyebrow:** AI · Live & Hands-On — Our In-House Academy · Online & In Person · For SPS Employees
- **Headline:** AI Skills, Built *In-House*
- **Subtext:** SPS Academy is our own training academy — open to you, funded by SPS, and delivered
  online so it fits around your work. AI is where we started, and our catalogue now runs to 800
  qualifications and courses across every part of the business.

---

## The Engine — One Platform, Many Companies (white-label)

This site is the **first instance of a reusable platform ("the engine")**. The *same* platform will be
**replicated across Newgx-invested companies** — e.g. **SPS, Maziv, Funig, Inhance** — each as its own
website: identical engine and product model, different company brand.

The **product never changes** between companies: professional online AI courses, plus an accredited
qualification delivered through an accredited provider.

| Changes per company (the re-skin) | Stays the same (the engine) |
|---|---|
| Company name & logo (`sps-dark-logo.svg`) | Page structure, sections & components |
| Brand palette (`:root` colour tokens in `styles.css`) | Design system & component styling |
| Contact details (email, phone, hours) | The three pillars + courses-first positioning |
| Industry line (SPS = solar; Maziv = fibre/telecoms; etc.) | `course.html` template + catalog/content model |
| Hero wording nuance | Video-lightbox + PDF-download mechanics |
| Accreditation partner/number (if not Centenary Networks) | Nav, footer, CTA patterns (`Explore Courses` / `Talk to Our Team`) |

**Recommended build:** centralise every company-specific value into a single **`BRAND` config block**
(name, logo path, accent colours, contact, industry line, accreditation details). Launching a new
company site then = copy the repo, edit one block, drop in the logo. *Status: proposed — not yet
refactored; the current site has SPS values inline.*

**Repo model:** one repo + GitHub Pages site per company (this one is `kgomotso-bolide/sps.academy`).

---

## 2. Brand & Design System

| Token | Value | Use |
|-------|-------|-----|
| **Orange (primary)** | `--orange: #f37424` / `--orange-deep: #e35e16` / `--orange-light: #faa819` | Buttons, eyebrows, nav active state, CTA bands, card titles |
| **Green (secondary)** | `--green: #4da446` / `--green-deep: #339346` | QCTO badge, live dot, accents |
| Canvas | `#ffffff` / `#f7f6f4` (white / warm off-white) | Backgrounds |
| Ink | `#2b2b2b` / `#4d4c4d` | Body & heading text |
| Dark | `--dark: #222021` | Footer, dark sections |

**Rule:** the site leads with the **SPS orange**, with green as the secondary accent, on a white
canvas. (A monochrome black/white/grey revision existed briefly and was reverted on 30 Jul 2026 —
don't reintroduce it.)

- **Logo:** `sps-dark-logo.svg` (inverted to white in the dark footer via CSS filter).
- **Type:** system font stack led by Inter — no external font files.
- **Imagery:** real topic-matched photography (Unsplash CDN) on course cards; the rest is
  self-contained CSS gradients + inline SVG, so there are almost no binary image assets to manage.
- **Motion:** subtle scroll-reveal; a low-key grey neural-network canvas in the hero; all respects
  `prefers-reduced-motion`.

---

## 3. Site Map & Files

```
sps/
├── index.html              # Home: hero + NQF explainer, 800-catalogue band, GM message,
│                           #   3 pillars, course teaser, photo posters, CTA
├── courses.html            # Courses hub: search + credential/subject filters, three bands
│                           #   (ours · more AI · free international), accreditation badges
├── about.html              # About: positioning, industry line, accreditation, photo posters
├── graduates.html          # Where our learners are now — Centenary graduates and interns
├── graduates.js            # THE GRADUATE LIST. One PEOPLE array, one entry per person.
│                           #   Nobody is published without consent:true — read the note
│                           #   at the top of the file before adding anyone
├── skills-gap.html         # STUB → /courses. The tool was removed 19 Aug 2026
├── rpl.html                # STUB → /contact. The explainer was removed 19 Aug 2026
├── locks.js                # WHICH COURSES ARE OPEN — single source of truth. Edit this
│                           #   file (OPEN_TITLES / OPEN_SLUGS) to reopen a course.
├── module.html             # ONE data-driven KNOWLEDGE MODULE template — renders any of
│                           #   the 11 via ?m=KM-02
├── pm-modules.js           # The 11 knowledge modules: topics, weightings, what each
│                           #   covers, the defining idea. NO LINKS HERE — this file is public;
│                           #   material links are pasted on /admin-materials
├── pm-progress.php         # Learner progress report: submit to HR, print for manager signature
├── pm-progress.js          # Progress store. TWO stores: the learner's ACCOUNT when signed in,
│                           #   localStorage when not. Fires 'pmprogress:sync' when it knows which.
├── pm-schedule.html        # Study planner: pace → module dates → calendar invites
├── pm-schedule.js          # Pacing maths + RFC 5545 .ics generation. Set OFFICIAL_DATES
│                           #   here once Centenary confirms a real programme calendar.
├── pm-pathway.html         # Google PM Certificate ↔ the NQF 5 Project Manager qualification:
│                           #   which knowledge modules it covers, which are taught here,
│                           #   and how the 240 credits are actually earned
├── profile.html            # Optional LOCAL profile (localStorage only) — NOT the academy
│                           #   account; the page says so and links to sign-in
├── ai-in-action.html       # STUB → /courses. The page was removed 19 Aug 2026
├── contact.php             # Contact details + working registration form (writes to the database)
│
│  ── the back end (xneelo-backend branch only; none of it runs on GitHub Pages) ──
├── login.php               # Sign in. Admins land on /admin, learners on /my
├── logout.php              # POST + CSRF only
├── forgot.php              # Ask for a reset link. Says the SAME thing whether or not the
│                           #   address has an account — read the note in lib/reset.php
├── reset.php               # Set a new password from the link. Single use, one hour
├── admin-users.php         # ACCOUNTS: set a password by hand, switch an account off.
│                           #   The route that works while the domain's SPF record is missing
├── my.php                  # THE LEARNER DASHBOARD — courses, progress, change password
├── account.php             # JSON: the session probe every page makes, and progress writes
├── admin.php               # Registrations list + the Enrol button that creates learner accounts
├── admin-progress.php      # Submitted progress reports
├── privacy.php             # Privacy notice — VERSIONED; bump policy_version when you edit it
├── setup.php               # Browser installer (404 unless setup_token is set in the config)
├── phpcheck.php            # Dependency-free preflight (same gate as setup.php)
├── lib/                    # bootstrap · db · auth · learner · registration · progress ·
│                           #   csrf · audit · mail · install. Denied to the web.
├── schema/                 # schema.mysql.sql is canonical; schema.sqlite.sql mirrors it so
│                           #   the whole site runs on a laptop. tools/migrate.php --check
│                           #   refuses to run if the two have drifted.
├── tools/                  # Command line only, never uploaded: dev-server, migrate,
│                           #   make-user, make-deploy-zip
├── DEPLOY-XNEELO.md        # The deployment procedure, and the 503 post-mortem
├── course.html             # ONE data-driven COURSE template — renders any course via ?c=<slug>
├── ai-fundamentals.html    # Legacy URL → redirects to course.html?c=ai-fundamentals
├── thanks.html             # Form submission confirmation page
│
├── styles.css              # SHARED stylesheet for every page (brand tokens in :root)
├── site.js                 # SHARED: nav toggle, scroll shadow, ?course= prefill
├── cards.js                # SHARED: course-card photo banners + links (Home + Courses)
├── courses-index.js        # Courses page: search/filter, external cards, badge popovers
├── profile.js              # SHARED: profile store, nav avatar, form prefill, save-a-course
├── profile-page.js         # Profile page: hydrate form, saved courses
├── assistant.js            # SHARED: client-side "Ask the Academy" AI assistant
├── neural.js               # Home hero neural-network canvas (motion PAUSED — see file)
│
├── images/gm-photo.svg     # PLACEHOLDER — General Manager portrait, awaiting the real photo
├── images/poster-1.svg     # PLACEHOLDER — academy photograph 1
├── images/poster-2.svg     # PLACEHOLDER — academy photograph 2
│
├── sps-dark-logo.svg       # Brand mark
├── resources/*.pdf         # Downloadable PDF resources (placeholders, swappable)
├── .gitattributes          # Marks PDFs/images as binary (prevents corruption)
└── README.md               # This blueprint
```

**Multi-page, shared-asset architecture** (no build step). Every page links `styles.css` + `site.js`
+ `assistant.js`, so design/behaviour lives in **one place** — ideal for the white-label goal
(re-skin = edit `styles.css` tokens + swap logo). Nav links navigate between real pages; the current
page is marked with `class="active"`.

### Key pages
- **Home / About / Courses / AI in Action / Contact** — one independent, lean, course-focused page each.
- **`course.html`** — the **Harvard-Online-style** COURSE template, **video-heavy with PDF
  downloads**, driven by a JS catalog (20 courses, each with its own curriculum). Sections: hero with
  intro video · "what you'll learn" · module/lesson accordion (video lightbox) · video gallery ·
  downloadable resources · facilitator · CTA. ("Module" here means a *unit inside a course* — a
  chapter of lessons — not a product.)

---

## 4. Content Model

All course content lives in one place: the **`COURSES` catalog** inside `course.html`.

```js
"<slug>": {
  title:  "…",            // course title
  cat:    "Business",     // category → drives the "what you'll learn" defaults
  mode:   "Online · Self-paced",
  level:  "Beginner",
  img:    "<unsplash-id>", // hero/card photo
  lead:   "…",            // description paragraph
  avail:  "Coming Soon",  // optional status
  accred: true,           // optional — flags the accredited qualification
  modules:[ { t:"Module title", lessons:[ "Lesson 1", … ] }, … ]  // optional bespoke curriculum
}
```

- **20 courses** are catalogued today. Cards on the home page auto-link to `course.html?c=<slug>`.
- **Videos:** each lesson has a `data-src`. Empty = "unlocks on enrolment"; `SAMPLE` = a placeholder
  preview stream. Swap in a real `.mp4`/Vimeo/YouTube URL to go live.
- **PDFs:** the five files in `resources/` are real, valid, downloadable placeholders. Replace the
  files (keep the names) to ship real content.

> ⚠️ **Placeholder content:** module outlines, durations ("6 weeks / 18 videos"), the facilitator,
> the sample videos and the PDFs are **demonstration placeholders**. The footers say so. Replace
> before any public launch.

---

## 5. Tech Stack & Hosting

Two answers, because there are two branches and they are genuinely different sites.

**`main` — the original static site, still live on GitHub Pages.**

- Static HTML/CSS/JS — no framework, no backend, no database, no build.
- Repo `https://github.com/kgomotso-bolide/sps.academy`, live at
  **https://kgomotso-bolide.github.io/sps.academy/**. Deploy = push to `main`.
- **Leave it working.** Kgomotso may have sent these links to managers. It must not be
  merged over until the professional URLs replace it: `contact.html` became `contact.php`,
  and PHP does not run on Pages, so merging 404s the registration form.

**`xneelo-backend` — what is actually live for SPS.**

- **PHP 8 + MySQL, no Composer, no build step** — matching Xneelo shared hosting.
- Live at **https://centenarynetworks.com/spsacademy/**, on Xneelo shared hosting in
  Johannesburg. The folder layout is interim; subdomains follow once GoDaddy DNS is sorted.
- **Deploy is manual**: GitHub → Actions → *Deploy SPS Academy*, and **the ref must be set to
  `xneelo-backend`** — it defaults to `main`, which would overwrite the working site with the
  old FormSubmit one.
- The configuration, with the database password and the IP pepper, lives at
  `~/private/sps-config.php` **outside the web root** and is never in the repo.
- Full procedure, including what to do when a release adds database tables:
  **`DEPLOY-XNEELO.md`**.

---

## 6. How To… (maintenance recipes)

- **Add a course** → add one entry to the `COURSES` object in `course.html`, and one
  `["<title fragment>","<slug>","<unsplash-id>"]` row to the `CARDS` array in `cards.js`.
  No new file needed.
- **Swap in a real video** → set the lesson's `data-src` to the video URL in the catalog.
- **Swap a PDF** → replace the file in `resources/` with the same filename.
- **Change copy** → hero copy lives in `index.html`; course copy in the `COURSES` catalog.
- **Adjust colour** → edit the CSS variables in `:root` in `styles.css`.

---

## 7. Accreditation (legal — keep verbatim)

> Accredited qualifications are delivered in association with **Centenary Networks (Pty) Ltd**,
> accredited by the **Quality Council for Trades and Occupations (QCTO)** as a Skills Development
> Provider. Accreditation No. **07-QCTO/SDP180526182035**, valid **15 May 2026 – 14 May 2031**.

The accredited qualification itself is the **Occupational Certificate: Project Manager**
(NQF 5, SAQA ID **101869**, 240 credits, curriculum code 121905000).

Computer Technician (NQF 5, ID 101408, 282 credits) was on this site until 19 Aug 2026 and was
withdrawn on Kgomotso's instruction. It is gone from the catalogue, the course template, the
assistant and `learner_catalogue()`. Anyone already enrolled against the old slug keeps their row.

Per the boss, this block should appear on the **credentials / about section** (it's rendered in the
About section's accreditation note) as well as the footer.

Non-accredited short courses must be labelled as such; only the Project Manager pathway is the
accredited qualification.

---

## 8. Roadmap / Open Items

- [x] **The three focus lines (20 Aug 2026)** — Kgomotso: Project Management, AI short
  courses, AI & Software Development, on all four sites. The third did not exist, so it
  was built: `ai-software-development`, eight modules, a card second in both catalogues,
  an entry in `cards.js`, the assistant and `learner_catalogue()` so somebody can be
  enrolled on it rather than merely shown it, and three tiles at the top of
  `/courses` naming the three lines. **Its syllabus is ours, not a registered
  curriculum** — the course page says so in a box until Kgomotso signs it off.
  The card artwork is drawn in `var(--accent)` and `var(--green)` rather than literal
  hexes, so the same markup is orange on SPS, teal on Fungi, purple on Maziv and red on
  Equinix. Every earlier card was hand-painted, which is why porting one has always
  meant repainting it.
- [x] **Course locks off (20 Aug 2026)** — `LOCKS_ON = false`, on Kgomotso's "no locking".
  Two of the three focus lines were padlocked here and open on the other three sites, so
  SPS was the odd one out. The lists in `locks.js` are left intact: one word puts it back.
- [x] **Kgomotso's five changes (19 Aug 2026)** — his list, in his order:
  1. **Computer Technician withdrawn.** Gone from `cards.js`, `courses.html`, `index.html`,
     `course.html` (record and its `MOD_TECH` curriculum), `assistant.js` and `learner_catalogue()`.
     The catalogue counter reads 27, the TECHNICAL subject chip disappears with the card that fed it,
     and a search for "technician" now returns nothing. Existing enrolment rows are untouched.
  2. **Project Manager is first** in both catalogues, first in `cards.js` and `assistant.js`, and is
     what a bare `/course` renders — the default was `ai-fundamentals`.
  3. **Online *and* in person.** Every in-house card reads ONLINE OR IN PERSON, the hero eyebrow and
     lede say it, and `mode:` on the six courses we deliver ourselves says it. The international
     courses still say ONLINE, because they are Coursera and Google and that is the truth.
  4. **Simplified.** ~19% of the words are gone from Home, Courses, About and Profile. The
     accreditation essay is one sentence, the "Ask us" panel is gone, About lost two brochure
     paragraphs and three of seven bullets. **Skills Gap, RPL and AI in Action were removed** —
     left as redirect stubs rather than deleted, because links to them have already been emailed
     to managers and a 404 on this host serves the client's homepage. `skills-gap.js` and `demo.js`
     are deleted; the profile page lost its skills snapshot and the four background selects that
     existed only to feed it.
  5. **Graduates page added** — `graduates.html` + `graduates.js`, in the nav between Courses and
     Contact. Ships with an empty list on purpose: it renders an honest "we are still collecting
     these" panel rather than invented people, and `consent:true` is required per entry because a
     name, a photograph and an employer on a public page are personal information under POPIA.

  SPS only for now, by instruction — Fungi, Maziv and Equinix still carry the old catalogue.
  `lib/learner.php`, `lib/chrome.php`, `profile.js`, `profile-page.js` and the shared region of
  `styles.css` are on the sync manifest, so `tools/sync-backend.php --check` will report drift
  against the other three until this is rolled out to them. That is expected, not a fault.
- [x] **Reverted to courses-first positioning (30 Jul 2026)** — removed all credit-bearing / stackable-credits / B-BBEE language site-wide, restored the SPS orange+green palette, renamed `modules.html` → `courses.html` and "Modules" → "Courses" throughout. See the warning in §1.
- [x] **Per-course outlines** — each of the 20 courses has its own tailored curriculum (`MOD_*` arrays in `course.html`).
- [x] **Working contact form** — wired to **FormSubmit.co → accounts@cn.co.za** (redirects to `thanks.html`); real email/phone in place. *First submission triggers a one-time activation email to accounts@cn.co.za — click it to start receiving enquiries.*
- [x] **AI assistant** — self-contained client-side helper (`Ask the Academy`) on every page: finds courses, explains how the training and accreditation work, routes to the team. No backend/API key (safe on static hosting); upgradeable to a live LLM later if a backend is added.
- [x] **Ported the Fungi structure (4 Aug 2026)** — same pages, components and JS as the Fungi and
  Equinix academies; only brand, palette, logo and industry vocabulary differ. Added Skills Gap,
  Profile, the course search/filter index, the 800-catalogue band, the GM message and the poster band.
- [x] **Internal voice** — the site speaks as SPS's own academy to SPS staff: "Fully funded by
  SPS", "register your interest with HR", "Upskill Yourself". No group rates, no corporate quotes.
- [x] **Intake announced: 10 September 2026 (12 Aug 2026)** — Centenary confirmed the start date, so it's
  on the landing page as a dark band under the hero, on the catalogue card, on the qualification page
  (via a `starts` field on the course record) and as the planner default. The date is the provider's;
  the per-module dates on `pm-schedule` are still ours, and that page now names the difference rather
  than blurring it. `OFFICIAL_DATES` stays null — we have a start, not a programme calendar.
- [ ] **GM name, photograph and message** — `images/gm-photo.svg` is a placeholder; the name reads
  "Name to follow" and the quote is generic. Replace all three when SPS supplies them.
- [ ] **Academy photographs** — `images/poster-1.svg` and `poster-2.svg` are placeholders on both the
  home and about pages, with "Caption to follow" captions. Swap the images and rewrite the captions.
- [x] **Project Management NQF 5 added (4 Aug 2026)** — the accredited qualification from Kgomotso's
  Skills Development Plan, wired the same way as Computer Technician: `course.html` catalogue entry
  (`?c=project-management`) with a five-module curriculum, cards on home and courses, assistant entry,
  and a Skills Gap recommendation for sales / team-lead / manager roles. Five SPS staff are being
  enrolled; the site holds no names — HR and the provider handle the enrolment itself.
- [x] **Project Management confirmed against the QCTO pack (11 Aug 2026)** — the provider supplied the
  full curriculum pack, so the placeholder entry was replaced with the registered qualification:
  **Occupational Certificate: Project Manager**, SAQA ID **101869**, NQF **5**, **240 credits**,
  curriculum code **121905000**. Note the registered title is *Project Manager*, not the
  *Project Management* the email used. The 28 modules (11 knowledge / 13 practical / 4 workplace) are
  transcribed from Form 3 and Form 4 and their credits reconcile to 240 — treat `QCTO_PROJECT_MANAGER`
  in `course.html` as the registered curriculum, not as copy to reword.
  A course carrying a `qcto` block renders differently: modules are listed with code and credit value
  instead of invented run times, the sample-video gallery and intro clip are removed, and the
  certificate card cites the external assessment rather than "finish all modules".
- [ ] **Learner material stays off the public site** — the pack includes facilitator guides, learner
  guides, workbooks **and summative assessment memos**. The memos are marking guides. None of it
  belongs in `resources/`, which is world-readable on GitHub Pages. The PDFs currently offered on the
  Project Manager page are still the generic AI placeholders; if real material is ever published it
  needs the provider's written go-ahead and a check for what must stay behind a login.
- [x] **Learner accounts (18 Aug 2026)** — learners sign in, and their progress lives on the
  account instead of in the browser. Kgomotso asked for this after showing us the GIBS
  Blackboard site, and it is the change that most of those features reduce to: the site now
  knows who you are.
  - **Getting an account**: an administrator presses **Enrol** on a registration in `/admin`.
    It creates the sign-in, records the enrolment, links the registration to the person, and
    shows the password **once**. Deliberately **no self-signup** — the site is on a public URL
    and mail from this server still fails the domain's SPF, so an address cannot be verified.
    When SPF is fixed the honest upgrade is an invite link and only the delivery step changes.
  - **Why it matters more than it sounds**: browser storage was tied to one device, was deleted
    by clearing history, and on a shared site machine showed the previous learner's record to
    the next one. That last is a disclosure, not an inconvenience.
  - **Nobody loses anything**: a learner signing in for the first time with ticks already in
    their browser is offered a one-press import that only ever adds. Original tick dates are
    preserved — they get printed on the report a manager signs.
  - **Sign in** is now in the navigation on every page, injected by `profile.js` rather than by
    editing seventeen navs. That is a stopgap the shared page header will absorb.
  - New tables `enrolments` and `learner_progress`. **Deploying the code is not enough** —
    see "Updating a site that is already live" in `DEPLOY-XNEELO.md`.
- [x] **Password reset, and the Accounts page (18 Aug 2026)** — two halves of one problem.
  - **Self-service** at `/forgot` → emailed link → `/reset`. The token is stored only as a
    SHA-256 hash, lasts an hour, works once, and issuing a new one retires the old. Requests
    are rate limited per address and per source — not because 256 bits is guessable, but
    because otherwise the endpoint is a way to make our server email anyone, repeatedly.
    The request form says the **same sentence** whether or not the address has an account;
    a helpful "no such user" would undo the effort `lib/auth.php` puts into not confirming
    which addresses exist.
  - **`/admin-users`** — set a password by hand, and switch an account off. This half exists
    because the sign-in page used to promise that Kgomotso would reset your password, and
    that promise could not be kept: `tools/make-user.php` is command line only and **Xneelo
    has no shell**. Anyone who forgot their password on the live site was locked out for good.
    It is also the route to use until the SPF record lands, since a reset email from this
    server may never arrive.
  - **Changing a password now ends every other session**, on every device. Done with a
    fingerprint of the password hash held in the session (`auth_password_stamp`) rather than a
    new column, so it needed no schema change to `users` — and `install_apply_schema()` only
    ever runs `CREATE TABLE IF NOT EXISTS`, so it could not have added one anyway.
  - New table `password_resets`. Same two-step deploy as above.
  - No deleting of accounts, deliberately: progress and enrolments hang off a user, and the
    real need — somebody has left SPS — is met by switching the account off while keeping the
    learner record the QCTO obliges us to hold.
- [ ] **Gate the learner material behind sign-in** — the site can now tell who is enrolled, so
  the 33 `DOCS` links in `pm-modules.js` can finally be served to enrolled learners only.
  Blocked on Kgomotso's Drive links, not on code. Assessment memos are never published.
- [ ] **Portfolio of evidence upload** — the real QCTO requirement, and the thing no
  international course can do for us. Needs an operator agreement in place first.
- [ ] **Entry requirements and purpose statement** — still unread. They are in `Qualification
  Document.pdf` in the provider's pack, which would not render for text extraction here. Worth adding
  to the course page once someone reads them off.
- [ ] **Port the `formalFor` change back to Fungi and Equinix** — adding a second formal qualification
  showed up a flaw in `skills-gap.js`: the credential boost assumed there was only ever one, so it
  pitched the project-management certificate to installers as "the full technical route". Each formal
  course now names the roles it fits (`formalFor`) and its own noun (`formalNoun`). The other two sites
  still have the single-qualification version.
- [x] **Recognition of Prior Learning (11 Aug 2026)** — `rpl.html` plus the Skills Gap wiring, ported
  from Fungi on Kgomotso's instruction that RPL is available to staff at all three companies. The Skills
  Gap result now reads an RPL case off answers it already collects (qualification, years, self-rating)
  and offers it in three tiers. Access is offered only to candidates holding nothing formal, since Matric
  is normally the NQF 5 entry requirement — telling a Matric holder they need RPL to get in would be
  wrong. Linked from every footer, from the accreditation explainer on `courses.html`, and from any
  course flagged `acc:"local"`.
- [x] **Project Management pathway (11 Aug 2026)** — `pm-pathway.html`, plus the Google Project
  Management Certificate as an `acc:"intl"` card on `courses.html`. Written because the business was
  working from the figure "Google covers 76% of the local course", which is a misreading: the mapping
  gave **74% of the 80-credit knowledge component**, which is **~25% of the 240-credit qualification**.
  *(Superseded 12 Aug 2026: the page now carries Centenary's own 50% figure — see below.)*
  The page states that plainly and deliberately shows **no per-module percentage** — exemption is
  granted per whole module, so it lists the 7 modules (52 credits) that are exemption candidates and
  the 4 (28 credits) that are taught here regardless. Three claims on the page must not be softened:
  the mechanism is RPL for credit and **not** SAQA's foreign-qualification evaluation (Google is not a
  national awarding body); the exemption decision rests with Centenary and the QCTO, never with us;
  and the EISA cannot be exempted by anything. The pathway hangs off a `pathway:{}` field on the course
  record rather than off `acc:"local"`, because the mapping is specific work per qualification.
  Reuses `.module`/`.lesson` as static always-open markup — no new CSS.
- [x] **Course locks (11 Aug 2026)** — only the Project Manager route is open for enrolment while
  its material is prepared. `locks.js` is the **single source of truth**: `OPEN_TITLES` unlocks a
  catalogue card, `OPEN_SLUGS` unlocks the course page behind it, and reopening a course means
  editing that file and nothing else. Currently open: the Occupational Certificate: Project Manager
  and the Google Project Management Certificate that feeds it — 26 of the 28 cards are locked.
  Locked courses stay **visible**: the breadth of the catalogue is the point of the courses page,
  so they get a padlock badge, a greyed banner and "Opening later" rather than being hidden.
  `cards.js` and `courses-index.js` both bail out on `data-locked`, so a locked card is never wired
  to a link; `course.html` refuses enrolment on a locked slug (bookmarks and the legacy
  `/ai-fundamentals` redirect still land somewhere sensible); and the Skills Gap keeps recommending
  locked courses but marks them and drops the link, since knowing what closes your gap is useful
  even when you can't start it yet. Copy on `index.html` and `courses.html` was rewritten — it
  previously said "Everything here is open to you", which the locks made false.
  - Note: the lock is JS-driven. With JS disabled the catalogue has no working links at all
    (`cards.js` is what adds them), so nothing can be enrolled in — it fails safe — but the static
    "Available Now" label would still show. Worth fixing in markup if a no-JS audience ever matters.
- [x] **Project Manager knowledge modules (12 Aug 2026)** — `module.html` + `pm-modules.js` turn the
  11 knowledge modules into study guides: 51 topics with their registered weightings, 265 sub-topic
  areas, and the defining paragraph for each topic. Built by parsing the provider's learner guides
  (`scratchpad/parse-km.js` → `gen-pm-modules.js`); credits sum to 80 and every module's weightings
  sum to 100, asserted both at build time and again in the browser so a bad edit fails loudly.
  Knowledge modules on the course page now link through; the 13 practicals and 4 workplace modules
  stay as flat rows because they are **mentor-signed logbooks, not material you read**, and need a
  different treatment.
  - **Nothing from the QCTO pack is committed to this repo.** The 24 summative assessments, 24
    marking memos and 24 facilitator guides are excluded outright — publishing marking memos on a
    public site would invalidate the assessments and put Centenary's accreditation at risk. The 52
    learner documents are not hosted here either: `DOCS` in `pm-modules.js` holds Drive/SharePoint
    links, and a `null` renders "Ask HR for a copy" rather than a dead button.
  - The lesson pages carry the study *structure*, not the full teaching prose — the guides are
    Centenary's material, and rendering every paragraph publicly would defeat the gated links.
- [x] **Study plan + calendar invites (12 Aug 2026)** — `pm-schedule.html` / `pm-schedule.js`.
  Built in answer to SPS: Babalwa asked how a self-paced programme gets tracked, and Mandy asked for
  "structured learning… sessions scheduled in their diaries". Kgomotso has confirmed to the client
  that the programme **is self-paced**, so this adds structure *on top of* self-paced material and
  never presents itself as fixed instructor-led classes. Pick a start date and weekly hours; it
  computes module-by-module dates from the registered credits (1 credit = 10 notional hours) and
  emits a `.ics` with a block per module plus recurring study sessions.
  - `OFFICIAL_DATES` is `null` and the page says so on every render: Centenary sets the real
    calendar, and the registered documents leave duration blank deliberately. **Do not let these
    dates reach a WSP or a learner agreement as though they were the provider's.**
  - The .ics is tested in Node (`scratchpad/test-ics.js`): CRLF, 75-octet folding that does not
    split multi-byte characters, escaping of `, ; \` and newlines, unique UIDs, exclusive all-day
    `DTEND`, and `RRULE UNTIL` matching the programme end.
  - **The arithmetic is sobering and worth knowing:** the knowledge component alone is 800 notional
    hours — ~1.9 years at 8 h/week, ~1.2 years at 15. That is 80 of 240 credits. If the real
    programme is meant to be shorter, the notional-hours figure and the provider's actual duration
    need reconciling before anyone commits to dates.
- [x] **Progress tracking (12 Aug 2026)** — `pm-progress.js` + `pm-progress.html`, answering
  Babalwa's "how will this be tracked… without placing a strain on HR or the provider". Learners
  tick topics off on the module pages; progress is stored under `pmProgress` **inside the existing
  profile object**, so the profile's "clear" wipes it too — the right behaviour on a shared site
  machine. The progress page shows all 11 modules, submits a dated structured record to HR through
  the existing FormSubmit endpoint, and prints with a **manager countersignature block** (print-only
  CSS — on screen it would read as a form nobody can fill in).
  - **Deliberately no backend.** Five learners; and the completion documents HR needs for reporting
    come from Centenary, not from us — a database would produce dashboards while the compliance
    artefact still arrives by email. Storing employee learning records on our own infrastructure
    would also make someone a responsible party under POPIA for data SPS already holds lawfully.
    Revisit at ~20 learners, multiple concurrent qualifications, or a roll-out to the other sites;
    the submitted records are structured plain text so they migrate cleanly.
  - Every surface says what it is: self-reported study, **not** an assessment result. Competence is
    Centenary's decision after assessment; the qualification is the QCTO's after the EISA.
  - Bug caught in testing and fixed: the hidden form fields were filled at paint time, so a learner
    who ticked topics in another tab could submit a stale record. The payload is now rebuilt on
    submit, and the page repaints on `visibilitychange`/`pageshow`/`focus`.
- [x] **Google overlap figure now Centenary's (12 Aug 2026)** — the page previously led with our own
  credit-weighted mapping (74% of the knowledge component, ~25% of the qualification). Kgomotso told
  SPS in writing that "50% of the course content matches the Google Career Certificates", and the
  provider's number governs because exemption is Centenary's decision. The page now carries **50%,
  explicitly attributed to Centenary**, rather than restating it as ours — we still do not know what
  the 50% is 50% *of*, and attribution is both honest and stronger. Our module-level split survives
  as supporting detail ("where it lands squarely" / "where it does not reach"), with the competing
  52-credit aggregate removed so two different arithmetics are not on the page at once.
  - Still worth asking her: what is the denominator, and which modules would she actually exempt?
    If 50% means half the *qualification*, that implies Google reaches practical or workplace
    credits, which is not how the qualification is built.
- [ ] **PMI claims** — Kgomotso told SPS candidates will be registered with PMI and that the Google
  certificates are "accredited by" PMI. Nothing about PMI is on the site yet, and that wording needs
  tightening before it is repeated: PMI does not accredit Google Career Certificates. Confirm
  whether "register" means PMI membership or a CAPM pathway (CAPM has its own eligibility rules).
- [ ] **Fill in the `DOCS` links in `pm-modules.js`** — all 33 slots (guide / workbook / video for
  11 modules) are `null`, so every module currently says "Ask HR for a copy". Set each file's
  sharing permissions *before* pasting its link: the page is public, so a link is only as private
  as its own sharing setting.
- [ ] **Practical and workplace modules** — 13 practicals and 4 workplace modules still need their
  logbook treatment (mentor sign-off, evidence checklists), which is a different page shape.
- [ ] **Confirm the QCTO accreditation number** — the site footer carries `07-QCTO/SDP180526182035`
  (valid 15 May 2026 – 14 May 2031); `Centenary_Course_Options_DRAFT.pdf` (29 Jul 2026) carries
  `07-QCTO/SDP120426174903`. Only one can be current, and it is on every page.
- [ ] **Sign off the Skills Gap role targets** — the eight per-role targets in `skills-gap.js` were
  written from this site's own description of the work and have **not** been reviewed by the people who
  run those teams. The page says so; get them checked before anyone treats the output as authoritative.
- [ ] **Load the remaining catalogue** — only 20 of the 800 are on the site, and the Skills Gap tool
  only recommends from those 20. The copy is explicit about this and points staff at HR.
- [ ] **Real content** — videos + PDFs + facilitator bios from SPS/Centenary.
- [ ] **Real phone number** — `012 345 6789` is still a placeholder on the contact page and in the assistant.
- [ ] **Custom domain** (e.g. `academy.sps…`) once decided.

---

*Project for SPS — Sustainable Power Solutions, in association with Centenary Networks (QCTO).
Blueprint derived from the SPS AI Skills Programme one-pager.*
