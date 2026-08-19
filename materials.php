<?php
declare(strict_types=1);

/* Course material: handing a learner a link, and remembering that we did.
 *
 * Two jobs:
 *
 *   GET ?course=<slug>  — the links for a course this learner is enrolled on,
 *                         as JSON. Every value is a URL back to THIS file, not
 *                         the Google Drive address.
 *   GET ?open=<id>      — log it, then redirect to the real address.
 *
 * WHY THE PAGE NEVER CONTAINS THE DRIVE LINK
 *
 * The material is shared as "anyone with the link", so the URL is the whole
 * protection. If it were printed into the page then view-source, a screenshot
 * over somebody's shoulder, a shared browser history or a copied "open in new
 * tab" would all leak it permanently. Handing out ?open=17 instead means the
 * page carries a reference that is worthless to anybody who is not signed in
 * and enrolled, and the real address exists only in a redirect the learner's
 * own browser follows.
 *
 * It is not a wall. A learner who opens the file can copy the Drive URL out of
 * their address bar and send it to anybody. Nothing here prevents that, and
 * nothing sold as preventing it would be honest. What this does is stop the
 * link leaking by accident, and record who opened what.
 *
 * WHY THE REDIRECT IS NOT AN OPEN REDIRECT
 *
 * The destination is never taken from the request. ?open= carries a row id, we
 * look the row up scoped to this tenant, and we send the learner to the URL
 * that was stored — which materials_url_allowed() already restricted to Drive,
 * SharePoint, OneDrive or YouTube when an administrator pasted it. There is no
 * input that can steer somebody somewhere else.
 *
 * WHY EVERY OPEN IS LOGGED
 *
 * Not surveillance for its own sake. "Did this learner ever open the guide for
 * KM-04, and when" is the question the QCTO asks about evidence of learning,
 * and the question the academy needs in order to notice somebody who has been
 * stuck for three weeks. It is stated in the privacy notice.
 */

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/audit.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/learner.php';
require __DIR__ . '/lib/materials.php';

app_session_start();

/** @param array<string,mixed> $data */
function mout(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    /* Material links are per-learner and change when an administrator pastes a
       new one. A cached copy in a shared proxy is exactly the accident this
       whole file is arranged to avoid. */
    header('Cache-Control: no-store, private');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$me = current_user();

/* ---------------------------------------------------------------------------
   ?open=<id> — the logged redirect
   --------------------------------------------------------------------------- */

$open = (int) ($_GET['open'] ?? 0);

if ($open > 0) {
    /* Deliberately a plain page rather than JSON: this is a link a person
       clicked, so a failure has to be readable by a person. */
    $refuse = static function (string $why): void {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><meta charset="utf-8"><title>Not available</title>'
           . '<p style="font:16px/1.6 system-ui,sans-serif;max-width:34em;margin:12vh auto;padding:0 6vw">'
           . e($why) . '</p>';
        exit;
    };

    if ($me === null) {
        redirect('login');
    }

    $row = db_optional(fn() => materials_get($open));
    if ($row === null) {
        $refuse('That material is no longer available. If you had a link to it, ask the academy.');
    }

    /* Enrolment, checked at the moment of opening rather than only when the
       page was built. A learner taken off a course keeps whatever HTML their
       browser already has; this is what actually ends their access. */
    $ok = db_optional(fn() => learner_is_enrolled((int) $me['id'], (string) $row['course_slug']), false);
    if (!$ok) {
        audit('material.denied', 'materials', $open, (string) $row['course_slug']);
        $refuse('You are not on the course this belongs to. If that is wrong, ask the academy.');
    }

    audit('material.opened', 'materials', $open,
          $row['module_code'] . ' ' . $row['kind']);

    /* 302 and not 301. A permanent redirect would be cached by the browser, and
       the next person on a shared machine would be sent straight to the file
       without this file ever running — which is every check above, skipped. */
    header('Location: ' . (string) $row['url'], true, 302);
    header('Cache-Control: no-store, private');
    exit;
}

/* ---------------------------------------------------------------------------
   ?course=<slug> — what this learner may open
   --------------------------------------------------------------------------- */

$course = (string) ($_GET['course'] ?? '');

if ($me === null) {
    /* Same shape as account.php's anonymous answer, so materials.js can treat
       "signed out" and "no materials" identically and simply leave the page
       alone. */
    mout(['in' => false]);
}

if ($course === '' || !learner_valid_course_slug($course)) {
    mout(['in' => true, 'error' => 'course', 'message' => 'No such course.'], 400);
}

if (!db_optional(fn() => learner_is_enrolled((int) $me['id'], $course), false)) {
    mout(['in' => true, 'enrolled' => false, 'materials' => (object) []], 403);
}

$found = db_optional(fn() => materials_for_course($course), []);

$out = [];
foreach ($found as $module => $kinds) {
    $slots = [];
    foreach (MATERIAL_KINDS as $kind) {
        $slots[$kind] = isset($kinds[$kind])
            ? 'materials.php?open=' . (int) $kinds[$kind]['id']
            : null;
    }
    $out[$module] = $slots;
}

mout([
    'in'        => true,
    'enrolled'  => true,
    'course'    => $course,
    'materials' => (object) $out,
]);
