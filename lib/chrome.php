<?php
declare(strict_types=1);

/* The page furniture — nav, footer, logo, disclaimer — in one place.
 *
 * WHY THIS EXISTS
 *
 * Four academies run this same application: SPS, Fungi, Equinix, Maziv. Before
 * this file, each one carried its own copy of every page, and the copies
 * differed only in the nouns: a logo filename, a company name, "SP1234" instead
 * of "FU1234". That arrangement has one specific failure mode, and it is not
 * hypothetical — it is what the four copies of styles.css already do. A fix
 * gets made in the repository the person happened to be working in, and the
 * other three keep the bug. The fix that matters is a security fix, and the
 * academy that misses it is the one nobody looked at that week.
 *
 * So the pages are now identical files, byte for byte, and everything that
 * differs between academies is data in lib/brand.php. That file is the ONLY
 * per-site PHP file, and tools/sync-backend.php refuses to copy it.
 *
 * WHY BRANDING IS IN THE REPOSITORY AND NOT IN THE SERVER CONFIGURATION
 *
 * The obvious place for "company name" is the configuration file next to the
 * database password. It is the wrong place. Branding is not a secret, it is
 * content: it gets reviewed, corrected, and argued over, and when it changes we
 * want to know who changed it and when. In ~/private/fungiacademy-config.php it
 * would be hand-typed over SFTP with no history and no review, and a typo in a
 * company's registered name would sit on their site until somebody noticed.
 *
 * Credentials stay in the configuration file. Nouns stay in git.
 */

/**
 * One branding fact.
 *
 * Missing keys are fatal rather than blank. A footer that silently renders
 * "© 2026 . All rights reserved." is exactly the kind of thing that goes live,
 * because nothing errored and nobody reads the footer.
 */
function brand(string $key): string
{
    static $brand = null;
    if ($brand === null) {
        $file = __DIR__ . '/brand.php';
        if (!is_file($file)) {
            app_fail('This installation has no lib/brand.php. Every academy needs '
                   . 'its own; copy one from another academy and change the nouns.');
        }
        $brand = require $file;
        if (!is_array($brand)) {
            app_fail('lib/brand.php must return an array.');
        }
    }
    if (!array_key_exists($key, $brand)) {
        app_fail('lib/brand.php has no "' . $key . '". Add it — a blank brand value '
               . 'renders as an empty gap that nobody notices.');
    }
    return (string) $brand[$key];
}

/** True when the key is present and not empty — for the genuinely optional ones. */
function brand_has(string $key): bool
{
    static $brand = null;
    if ($brand === null) $brand = require __DIR__ . '/brand.php';
    return isset($brand[$key]) && (string) $brand[$key] !== '';
}

/**
 * A local asset URL with the cache-busting version on it.
 *
 * Hard-won. The parent site sets mod_expires on css and js, and mod_expires IS
 * inherited into subdirectories even though mod_rewrite is not. Returning
 * visitors held month-old JavaScript and a deploy did nothing for them at all.
 * The .htaccess header fixes that going forward, but only for a browser that
 * asks — this is what fixes it for a browser that does not.
 *
 * One constant per site, so a release changes a single line rather than
 * fourteen assets across twenty-three files by hand.
 */
function asset(string $path): string
{
    return $path . '?v=' . brand('asset_version');
}

/**
 * The name set beside an icon-only logo — "CRICKET SA", "M&T", "EQUINIX".
 *
 * OPTIONAL, AND ASKED FOR THROUGH brand_has() FIRST (13 Sep 2026). Some academies'
 * logos carry the company name in the artwork and need nothing beside them; others
 * are a bare mark. The static .html pages always paired a bare mark with a
 * .brand-word span, but these PHP pages never did, so contact, sign-in and every
 * admin page showed an unlabelled mark — on Cricket SA's, just a green and gold
 * ball, with nothing on the page saying whose academy it was.
 *
 * brand() fails the whole page on a missing key, by design. Calling it for a key
 * the logo-with-lettering academies do not define would have taken down every PHP
 * page on SPS, Fungi, Maziv and Tracker on their next deploy. So it is only called
 * once brand_has() says the site set one; everyone else renders exactly as before.
 */
function chrome_wordmark(): string
{
    return brand_has('wordmark')
        ? '<span class="brand-word">' . e(brand('wordmark')) . '</span>'
        : '';
}

function chrome_logo(string $class = 'brand'): string
{
    return '<a href="./" class="' . e($class) . '"><img src="' . e(brand('logo'))
         . '" alt="' . e(brand('logo_alt')) . '">' . chrome_wordmark() . '</a>';
}

/**
 * The hamburger. EVERY nav needs one — this is not a decoration.
 *
 * Below 900px `.nav-links` is `display:none` and only `.nav-links.open` shows,
 * and site.js adds that class only when it finds BOTH `#navToggle` and
 * `#navLinks`. So a nav rendered without this button is not a nav that looks a
 * bit plain on a phone: it is a nav with no links at all, permanently.
 *
 * That is what happened to the admin pages. They had four links and a sign-out,
 * none of which existed on a phone, and it went unnoticed because it is
 * invisible on a laptop — which is where a page like that gets built and
 * checked. The auth pages had it too. If you add a fifth nav variant, give it
 * a toggle and an id="navLinks", and open it at 390px before you believe it.
 */
function chrome_nav_toggle(): string
{
    return '<button class="nav-toggle" id="navToggle" aria-label="Menu">'
         . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
         . 'stroke-linecap="round" style="width:26px;height:26px;display:block">'
         . '<path d="M3 6h18M3 12h18M3 18h18"/></svg></button>';
}

/**
 * The sign-out control.
 *
 * A POST form and not a link, deliberately. Signing out over GET means any page
 * anywhere can sign our learners out with an <img src>, and it means a link
 * prefetcher can do it by accident. The CSRF token comes along for the same
 * reason it does everywhere else.
 */
function chrome_signout(string $label = 'Sign out'): string
{
    return '<form method="POST" action="logout" style="display:inline">'
         . csrf_field()
         . '<button type="submit" class="linkish">' . e($label) . '</button></form>';
}

/**
 * The signed-in app frame (16 Nov 2026, from the sps-newui prototype; Sibusiso:
 * "only implement the signed-in app UI into our current live academies and
 * everything else remains the same", for learners AND staff).
 *
 * A fixed left sidebar and a slim top bar in place of the top nav, on every
 * page that calls chrome_nav('learner') or chrome_nav('admin'), where the
 * site's lib/brand.php sets 'app_shell'. The public pages, sign-in and the
 * static course pages keep the ordinary nav (learner-nav.js gives a signed-in
 * learner the study links there).
 *
 * The sidebar is #navLinks and the menu button #navToggle, so site.js's existing
 * toggle opens it on a phone with no new script. The menu is a div with
 * role=navigation, not a <nav>: styles.css styles every nav element as the
 * fixed public top bar.
 *
 * @param list<array{0:string,1:string,2:string,3:string}|string> $items
 *        [href, key, label, icon-path], or a string for a group heading.
 *        An href starting "http" opens in a new tab.
 */
function chrome_shell_frame(array $items, string $here, string $home, array $foot,
                            string $bell, string $avHref, string $label): void
{
    $u = function_exists('current_user') ? current_user() : null;
    $ini = $u ? mb_strtoupper(mb_substr((string) $u['first_name'], 0, 1) . mb_substr((string) $u['last_name'], 0, 1)) : '';
    $svg = static fn(string $p): string => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';

    echo '<link rel="stylesheet" href="' . e(asset('app.css')) . '">' . "\n";
    echo '<aside class="app-side" id="navLinks">' . "\n";
    echo '  <a href="' . e($home) . '" class="app-logo"><img src="' . e(brand('logo')) . '" alt="' . e(brand('logo_alt')) . '"></a>' . "\n";
    echo '  <div class="app-menu" role="navigation" aria-label="' . e($label) . '">' . "\n";
    foreach ($items as $it) {
        if (is_string($it)) {
            echo '    <span class="app-group">' . e($it) . '</span>' . "\n";
            continue;
        }
        [$href, $key, $text, $path] = $it;
        $ext = str_starts_with($href, 'http');
        echo '    <a href="' . e($href) . '"'
           . ($ext ? ' target="_blank" rel="noopener"' : '')
           . ($here === $key ? ' class="on" aria-current="page"' : '') . '>'
           . $svg($path) . '<span>' . e($text) . ($ext ? ' &#8599;' : '') . '</span></a>' . "\n";
    }
    echo '  </div>' . "\n";
    echo '  <div class="app-side-foot">' . "\n";
    foreach ($foot as $href => $text) {
        echo '    <a href="' . e((string) $href) . '">' . e((string) $text) . '</a>' . "\n";
    }
    echo '    ' . chrome_signout() . "\n";
    echo '  </div>' . "\n";
    /* profile.js adds a "Sign in" link to #navLinks unless one is already
       there. Never wanted inside the signed-in frame. */
    echo '  <span class="nav-signin" hidden></span>' . "\n";
    echo '</aside>' . "\n";
    echo '<header class="app-top" id="nav">' . "\n";
    echo '  <button class="app-burger" id="navToggle" aria-label="Menu">' . $svg('<path d="M3 6h18M3 12h18M3 18h18"/>') . '</button>' . "\n";
    echo '  <a href="' . e($home) . '" class="app-top-logo"><img src="' . e(brand('logo')) . '" alt=""></a>' . "\n";
    echo '  <span class="app-top-gap"></span>' . "\n";
    echo '  <a href="' . e($bell) . '" class="app-bell" title="Notice board">' . $svg('<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>') . '</a>' . "\n";
    $who = $u ? trim((string) $u['first_name'] . ' ' . (string) $u['last_name']) : '';
    echo $avHref !== ''
        ? '  <a href="' . e($avHref) . '" class="app-av" title="' . e($who) . '">' . e($ini) . '</a>' . "\n"
        : '  <span class="app-av" title="' . e($who) . '">' . e($ini) . '</span>' . "\n";
    echo '</header>' . "\n";
}

/** The learner's sidebar: Home, Skills, My files, Account. */
function chrome_app_shell(array $o): void
{
    $here = (string) ($o['active'] ?? 'my');
    /* The study pages that left the menu light up the item they now sit under. */
    $here = ['my' => 'skills', 'logbook' => 'skills', 'submit' => 'skills', 'prior-learning' => 'skills',
             'notices' => 'home'][$here] ?? $here;
    $foot = [];
    /* Staff who open a learner page keep a way back to their own side. */
    if (!empty($o['admin']))       $foot['admin'] = 'Administration';
    elseif (!empty($o['trainer'])) $foot['trainer'] = 'My learners';
    $foot['contact'] = 'Contact the academy';
    $items = [
        ['dashboard', 'home',    'Home',     '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>'],
        ['skills',    'skills',  'Skills',   '<path d="M12 3l3 6H9zM12 9l4 7H8zM12 16v5M8 21h8"/>'],
        ['files',     'files',   'My files', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6"/>'],
        ['me',        'account', 'Account',  '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'],
    ];
    /* The study tutor, where 'tutor' is on (16 Nov 2026). See lib/tutor.php. */
    if (brand_has('tutor')) {
        array_splice($items, 3, 0, [['tutor', 'tutor', 'Tutor',
            '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 21l1.9-5.4A8 8 0 1 1 21 12z"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5M12 16.5h.01"/>']]);
    }
    chrome_shell_frame($items, $here, 'dashboard', $foot, 'notices', 'me', 'Your learning');
}

/**
 * The staff sidebar: the same links as the admin top nav below, grouped, for
 * an administrator or a trainer. Keep the two in step — which links a trainer
 * is offered is argued for there, and the pages enforce it themselves.
 */
function chrome_staff_shell(array $o): void
{
    $on = (string) ($o['active'] ?? '');
    $staff = function_exists('is_trainer') && is_trainer();
    $i = [
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13L22 12v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6z"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'users' => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 4a4 4 0 0 1 0 8M22 21a7 7 0 0 0-4-6.3"/>',
        'cert'  => '<circle cx="12" cy="9" r="6"/><path d="M8.5 14L7 22l5-3 5 3-1.5-8"/>',
        'file'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6"/>',
        'book'  => '<path d="M4 19V5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2zM8 7h7"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
        'cal'   => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'box'   => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
        'bell'  => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>',
        'clip'  => '<rect x="5" y="4" width="14" height="18" rx="2"/><path d="M9 4V2h6v2M9 11h6M9 15h6"/>',
        'score' => '<path d="M12 3l2.6 5.6L20 9.3l-4 4.1 1 5.6-5-2.8-5 2.8 1-5.6-4-4.1 5.4-.7z"/>',
        'tool'  => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.5-.5-.5-2.5z"/>',
    ];
    $items = [];
    if (!$staff) {
        $items[] = 'Learners';
        $items[] = ['admin',          'admin',          'Registrations', $i['inbox']];
        $items[] = ['admin-progress', 'admin-progress', 'Progress',      $i['chart']];
        $items[] = ['admin-users',    'admin-users',    'Accounts',      $i['users']];
        $items[] = ['admin-rpl',      'rpl',            'RPL',           $i['cert']];
    } else {
        $items[] = 'Learners';
        $items[] = ['trainer', 'trainer', 'My learners', $i['users']];
    }
    $items[] = 'Teaching';
    $items[] = ['admin-materials', 'admin-materials', 'Material', $i['file']];
    $items[] = ['admin-lessons',   'admin-lessons',   'Reading',  $i['book']];
    $items[] = ['admin-quizzes',   'admin-quizzes',   'Quizzes',  $i['check']];
    $items[] = ['admin-classes',   'classes',         'Classes',  $i['cal']];
    $items[] = ['admin-poe',       'poe',             'Files',    $i['box']];
    $items[] = ['admin-notices',   'notices',         'Notices',  $i['bell']];
    if (!$staff) {
        $items[] = 'B-BBEE';
        $items[] = ['admin-evidence', 'evidence', 'Evidence', $i['clip']];
        if (is_file(__DIR__ . '/../scorecard.php')) {
            $items[] = ['scorecard', 'scorecard', 'B-BBEE', $i['score']];
        }
        $items[] = ['https://centenarynetworks.com/beetool/', '', 'BEE Tool', $i['tool']];
    }
    $home = $staff ? 'trainer' : 'admin';
    chrome_shell_frame($items, $on, $home, ['./' => 'View the site'], 'admin-notices', '',
                       $staff ? 'Training' : 'Administration');
}

/**
 * The navigation bar.
 *
 * Four shapes, because there are four kinds of page and they want different
 * things within reach:
 *
 *   site    — the public site's full menu. Someone reading the catalogue.
 *   auth    — sign in, forgot, reset. Stripped to almost nothing: a person
 *             trying to get into their account should not be offered six ways
 *             to wander off, one of which is the page they just came from.
 *   learner — signed in and studying. Their own learning first, sign out reachable.
 *   admin   — the back office. Not the public site's menu at all.
 */
function chrome_nav(string $variant, array $o = []): void
{
    /* The signed-in app frame replaces the bar for learners and staff alike. */
    if (brand_has('app_shell') && $variant === 'learner') { chrome_app_shell($o); return; }
    if (brand_has('app_shell') && $variant === 'admin')   { chrome_staff_shell($o); return; }

    $active = (string) ($o['active'] ?? '');
    $on = static fn(string $k): string => $active === $k ? ' class="active"' : '';

    /* nav-admin: the back-office bar carries a dozen links, so it gets its own
       width rules in the shared styles (see "Admin nav" there). */
    $logo = chrome_logo();
    if ($variant === 'learner' && brand_has('self_signup')) {
        $logo = preg_replace('/^<a href="\.\/"/', '<a href="my"', $logo, 1);
    }
    echo '<nav id="nav"' . ($variant === 'admin' ? ' class="nav-admin"' : '') . '>' . "\n  " . '<div class="nav-inner">' . "\n    "
       . $logo . "\n";

    if ($variant === 'site') {
        echo '    <div class="nav-links" id="navLinks">' . "\n";
        echo '      <a href="./" data-nav="home">Home</a>' . "\n";
        echo '      <a href="about" data-nav="about">About</a>' . "\n";
        echo '      <a href="courses" data-nav="courses">Courses</a>' . "\n";
        /* ⚠ trainers.html is NOT on the sync manifest — like every other .html it
           carries its own site's chrome, so each academy needs its own copy. This
           file IS synced. Copy trainers.html (and the trainer styles in
           styles.css) into a target repo BEFORE syncing chrome.php into it, or
           this link 404s on that site. Same footing as the Graduates link below,
           which has always had the same requirement. */
        /* "Alumni" on Fungi (3 Nov 2026): 'graduates_label' in a site's
           lib/brand.php renames the link. The address stays /graduates. */
        /* 'hide_graduates' in a site's lib/brand.php drops the page (SPS, 10 Nov 2026). */
        if (!brand_has('hide_graduates')) {
            echo '      <a href="graduates" data-nav="graduates">' . e(chrome_graduates_label()) . '</a>' . "\n";
        }
        /* A site's own extra pages, from 'nav_extra' in its lib/brand.php
           (href => label) — see brand_list() in lib/bootstrap.php. */
        foreach (brand_list('nav_extra') as $href => $label) {
            echo '      <a href="' . e((string) $href) . '" data-nav="' . e((string) $href) . '">'
               . e((string) $label) . '</a>' . "\n";
        }
        echo '      <a href="contact" data-nav="contact"' . $on('contact') . '>Contact</a>' . "\n";
        /* The browser-only Profile is not offered where learners make real
           accounts (self_signup, SPS since 10 Nov 2026): the account holds it. */
        if (!brand_has('self_signup')) {
        echo '      <a href="profile" data-nav="profile" class="nav-profile" title="Your profile">'
           . '<span class="np-av" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" '
           . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
           . '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'
           . '</svg></span><span class="np-label">Profile</span></a>' . "\n";
        }
        echo '      <a href="' . (brand_has('self_signup') ? 'signup' : 'contact') . '" class="nav-cta">Upskill Yourself</a>' . "\n";
        echo '    </div>' . "\n";
        echo '    ' . chrome_nav_toggle() . "\n";

    } elseif ($variant === 'auth') {
        echo '    <div class="nav-links" id="navLinks">' . "\n";
        echo '      <a href="./" data-nav="home">Home</a>' . "\n";
        echo '      <a href="courses" data-nav="courses">Courses</a>' . "\n";
        echo ($o['tail'] ?? 'contact') === 'signin'
            ? '      <a href="login">Sign in</a>' . "\n"
            : '      <a href="contact" data-nav="contact">Contact</a>' . "\n";
        echo '    </div>' . "\n";
        echo '    ' . chrome_nav_toggle() . "\n";

    } elseif ($variant === 'learner' && brand_has('self_signup')) {
        /* Study-only nav (SPS, 10 Nov 2026, Sibusiso): "like a true learning
           management system, once logged in you only see studying tabs". No Home
           or Courses: clicking into the public site made learners think they had
           been signed out. The logo goes to My learning too — see learner-nav.js,
           which does the same on the static pages. Staff keep their way back. */
        $here = (string) ($o['active'] ?? 'my');
        $a = static fn(string $href, string $key, string $label): string =>
            '      <a href="' . $href . '"' . ($here === $key ? ' class="active"' : '') . '>' . $label . '</a>' . "\n";
        echo '    <div class="nav-links" id="navLinks">' . "\n";
        echo $a('my#account', 'account', 'My account');
        echo $a('my', 'my', 'My learning');
        echo $a('files', 'files', 'My files');
        echo $a('logbook', 'logbook', 'My logbook');
        echo $a('submit', 'submit', 'Submit work');
        echo $a('notices', 'notices', 'Notice board');
        if (!empty($o['admin']))       echo '      <a href="admin">Administration</a>' . "\n";
        elseif (!empty($o['trainer'])) echo '      <a href="trainer">My learners</a>' . "\n";
        echo $a('contact', 'contact', 'Contact');
        echo '      ' . chrome_signout() . "\n";
        echo '    </div>' . "\n";
        echo '    ' . chrome_nav_toggle() . "\n";

    } elseif ($variant === 'learner') {
        echo '    <div class="nav-links" id="navLinks">' . "\n";
        echo '      <a href="./" data-nav="home">Home</a>' . "\n";
        echo '      <a href="courses" data-nav="courses">Courses</a>' . "\n";
        /* "My learning" is no longer always the current page in this variant —
           logbook.php uses the same nav — so the active link is chosen rather
           than assumed. Defaulting to 'my' keeps every existing caller right. */
        $here = (string) ($o['active'] ?? 'my');
        echo '      <a href="my"' . ($here === 'my' ? ' class="active"' : '') . '>My learning</a>' . "\n";
        echo '      <a href="files"' . ($here === 'files' ? ' class="active"' : '') . '>My files</a>' . "\n";
        echo '      <a href="logbook"' . ($here === 'logbook' ? ' class="active"' : '') . '>My logbook</a>' . "\n";
        echo '      <a href="submit"' . ($here === 'submit' ? ' class="active"' : '') . '>Submit work</a>' . "\n";
        echo '      <a href="notices"' . ($here === 'notices' ? ' class="active"' : '') . '>Notice board</a>' . "\n";
        /* A trainer needs the same way through from the learner pages that an
           administrator has, or the only route to their own page is typing the
           URL. Two keys rather than one so the link can say where it goes. */
        if (!empty($o['admin'])) {
            echo '      <a href="admin">Administration</a>' . "\n";
        } elseif (!empty($o['trainer'])) {
            echo '      <a href="trainer">My learners</a>' . "\n";
        }
        echo '      <a href="contact" data-nav="contact">Contact</a>' . "\n";
        echo '      ' . chrome_signout() . "\n";
        echo '    </div>' . "\n";
        echo '    ' . chrome_nav_toggle() . "\n";

    } elseif ($variant === 'admin') {
        echo '    <div class="nav-links" id="navLinks">' . "\n";
        /* Two navs share this variant: a trainer signs in to the same chrome as
           an administrator and only the links differ. Registrations, Accounts,
           Progress and B-BBEE are administration; Material, Reading and Quizzes
           are the course, and a trainer reads those. The pages enforce this
           themselves — the nav only decides what is worth offering. */
        $staff = function_exists('is_trainer') && is_trainer();
        if (!$staff) {
            echo '      <a href="admin"' . $on('admin') . '>Registrations</a>' . "\n";
            echo '      <a href="admin-progress"' . $on('admin-progress') . '>Progress</a>' . "\n";
            echo '      <a href="admin-users"' . $on('admin-users') . '>Accounts</a>' . "\n";
        } else {
            echo '      <a href="trainer"' . $on('trainer') . '>My learners</a>' . "\n";
        }
        echo '      <a href="admin-materials"' . $on('admin-materials') . '>Material</a>' . "\n";
        echo '      <a href="admin-lessons"' . $on('admin-lessons') . '>Reading</a>' . "\n";
        echo '      <a href="admin-quizzes"' . $on('admin-quizzes') . '>Quizzes</a>' . "\n";
        /* Offered to both, unlike everything above: an admin schedules classes
           and a trainer marks the register of the ones they facilitate. It is
           the one page in this nav where a trainer can write — the narrow
           exception argued for in lib/classes.php. */
        echo '      <a href="admin-classes"' . $on('classes') . '>Classes</a>' . "\n";
        /* Offered to both for the same reason: an admin reads the whole grid and a
           facilitator records the hand-ins on their own courses. The narrow write
           is argued for at poe_may_record() in lib/logbook.php. Called "Files"
           rather than "POE" because that is the word used out loud about the
           physical folder this page tracks. */
        echo '      <a href="admin-poe"' . $on('poe') . '>Files</a>' . "\n";
        /* Offered to both: an admin posts to anybody and a facilitator to the courses
           they are assigned to. See lib/notices.php for why that is the narrow kind. */
        echo '      <a href="admin-notices"' . $on('notices') . '>Notices</a>' . "\n";
        /* The B-BBEE Scorecard Tool (centenarynetworks.com/beetool) — admins/HR
           only, on Sibusiso's instruction of 26 Oct 2026, following Kgomotso's
           25 Aug rule that B-BBEE is HR's business and not learners'. It is a
           separate application with its own logins per company, so this is
           only a doorway: signing in here does not sign anyone in there.

           "B-BBEE" is this academy's own verified-scorecard page, and only
           where one has been built (SPS today). The link used to be printed on
           every site and was a 404 on the ones without the page. */
        if (!$staff) {
            /* Training evidence for the B-BBEE submission: HR (admins) only,
               like everything else in this block. See admin-evidence.php. */
            echo '      <a href="admin-evidence"' . $on('evidence') . '>Evidence</a>' . "\n";
            /* Recognition of prior learning: the assessor is an admin. See admin-rpl.php. */
            echo '      <a href="admin-rpl"' . $on('rpl') . '>RPL</a>' . "\n";
            if (is_file(__DIR__ . '/../scorecard.php')) {
                echo '      <a href="scorecard"' . $on('scorecard') . '>B-BBEE</a>' . "\n";
            }
            echo '      <a href="https://centenarynetworks.com/beetool/" target="_blank" rel="noopener">BEE Tool &#8599;</a>' . "\n";
        }
        echo '      <a href="./">View the site</a>' . "\n";
        echo '      ' . chrome_signout('Sign out (' . (string) ($o['name'] ?? '') . ')') . "\n";
        echo '    </div>' . "\n";
        echo '    ' . chrome_nav_toggle() . "\n";

    } else {
        app_fail('Unknown nav variant "' . $variant . '".');
    }

    echo '  </div>' . "\n" . '</nav>' . "\n";
}

/**
 * The footer disclaimer, assembled from parts rather than stored as prose.
 *
 * It was stored as prose — four paragraphs per site across four sites — and it
 * had already drifted. Fungi's progress page claimed copyright for "Fungi —
 * Digital Utility" while its contact page said "Fungi Utilities (Pty) Ltd":
 * two different entities named on two pages of one site, because somebody
 * copied a paragraph and edited half of it.
 */
function chrome_disclaimer(string $extra = '', bool $employees = false): string
{
    $s = brand('academy') . ' is the in-house training academy of ' . brand('company');
    if ($employees) $s .= ', for ' . brand('company_short') . ' employees';
    $s .= '. ';
    if ($extra !== '') $s .= rtrim($extra) . ' ';
    $s .= brand('academy') . ' operates in association with Centenary Networks. '
        . '© 2026 ' . brand('company') . '. All rights reserved.';
    return $s;
}

/**
 * The QCTO accreditation block.
 *
 * The accreditation belongs to Centenary Networks and is therefore the same on
 * all four sites — Centenary holds it, not the client company. Worth saying out
 * loud, because "give each academy its own accreditation number" is a
 * reasonable-sounding change that would put a false statement on three sites.
 */
function chrome_accred(): string
{
    return '<div class="foot-accred" style="max-width:none;border-bottom:1px solid '
         . 'rgba(255,255,255,.1);padding-bottom:22px;margin-bottom:20px">' . "\n"
         . '      <strong>Accreditation</strong>' . "\n"
         . '      <span>Accredited qualifications are delivered in association with '
         . 'Centenary Networks (Pty) Ltd, accredited by the Quality Council for Trades '
         . 'and Occupations (QCTO) as a Skills Development Provider. Accreditation No. '
         . e(brand('accred_no')) . ', valid ' . e(brand('accred_valid')) . '.</span>' . "\n"
         . '    </div>';
}

/**
 * The footer.
 *
 *   slim — the account pages. Someone signing in does not need a site map.
 *   site — the public pages: brand, links, accreditation, disclaimer.
 */
function chrome_footer(string $variant, array $o = []): void
{
    echo '<footer>' . "\n" . '  <div class="wrap">' . "\n";

    if ($variant === 'site') {
        echo '    <div class="foot-top">' . "\n";
        echo '      <div class="foot-brand"><img src="' . e(brand('logo')) . '" alt="'
           . e(brand('logo_alt')) . '">' . chrome_wordmark() . '</div>' . "\n";
        echo '      <div class="foot-nav">' . "\n";
        foreach ([
            './'           => 'Home',
            'about'        => 'About',
            'courses'      => 'Courses',
            'trainers'     => 'Trainers',
        ] + (brand_has('hide_graduates') ? [] : [
            'graduates'    => e(chrome_graduates_label()),
        ]) + brand_list('nav_extra') + (brand_has('self_signup') ? [] : [
            'profile'      => 'Profile',
        ]) + [
            'contact'      => 'Contact',
            'privacy'      => 'Privacy',
        ] as $href => $label) {
            echo '        <a href="' . $href . '">' . $label . '</a>' . "\n";
        }
        echo '      </div>' . "\n";
        echo '    </div>' . "\n";
        if ($o['accred'] ?? true) echo '    ' . chrome_accred() . "\n";
    } elseif ($variant !== 'slim') {
        app_fail('Unknown footer variant "' . $variant . '".');
    }

    echo '    <p class="disclaimer">'
       . e(chrome_disclaimer((string) ($o['extra'] ?? ''), (bool) ($o['employees'] ?? false)))
       . '</p>' . "\n";
    echo '  </div>' . "\n" . '</footer>' . "\n";

    /* Deliberately NOT emitting <script src="site.js"> here, though every page
       that calls this loads it. Two pages load three more scripts after the
       footer and one of them reads what the others set up, so if this function
       owned site.js it would silently reorder them. A footer helper is not the
       right place to be making decisions about script execution order. */
}

/* What the Graduates page is called in the nav and footer. */
function chrome_graduates_label(): string
{
    return brand_has('graduates_label') ? (string) brand('graduates_label') : 'Graduates';
}
