<?php
declare(strict_types=1);

/* Recognition of prior learning: the Google certificate route.
 *
 * WHAT IT IS FOR (28 Oct 2026, Sibusiso and Kgomotso). A learner is enrolled on
 * the Project Manager and starts with the Google Project Management
 * Certificate on Coursera. When they finish, they hand the certificate in here.
 * The assessor records which of the eleven knowledge modules it is recognised
 * for, and the learner studies only the rest. It is offered on every academy,
 * because every academy carries the Project Manager.
 *
 * WHO DECIDES. The assessor, who on these academies is also the administrator,
 * so every write here is require_admin(). Nothing is ever recognised
 * automatically: handing in a certificate changes nothing until a person has
 * looked at it and saved a decision. pm-pathway.html says the same thing to
 * learners, and this file is what makes it true.
 *
 * THE STANDARD LIST. The assessor approves, once per route, which modules the
 * certificate is normally recognised for, and each learner's decision starts
 * from it. 'suggested' below is NOT that list. It is the pathway page's own
 * reading of the published Google syllabus — seven modules — offered so the
 * assessor has something to start from, and it is never applied to a learner
 * until the assessor has saved it as the standard.
 *
 * WHAT IT DOES NOT TOUCH. The practical and workplace modules, and the EISA.
 * RPL against workplace evidence is a different conversation with different
 * evidence, and the EISA cannot be exempted by anything.
 *
 * TABLES. rpl_claims (what was handed in, append-only), rpl_credits (one row per
 * recognised module), rpl_standard (the approved list). See the schema.
 */

defined('APP_BOOTED') or exit('lib/rpl.php is not a page.');

const RPL_STATUSES  = ['submitted', 'accepted', 'declined'];
const RPL_MAX_BYTES = 10 * 1024 * 1024;

/**
 * The routes, by course. One today; a second provider or a second
 * qualification is a new entry here and nothing else.
 */
function rpl_routes(): array
{
    return [
        'project-management' => [
            'google-pm' => [
                'title'     => 'Google Project Management Certificate',
                'provider'  => 'Coursera',
                'url'       => 'https://www.coursera.org/professional-certificates/google-project-management',
                // pm-pathway.html's "lands squarely" list. A suggestion, not a decision.
                'suggested' => ['KM-02', 'KM-03', 'KM-05', 'KM-06', 'KM-07', 'KM-08', 'KM-11'],
            ],
        ],
    ];
}

function rpl_route(string $course, string $route): ?array
{
    return rpl_routes()[$course][$route] ?? null;
}

/** The first (today: only) route for a course, or null if it has none. */
function rpl_default_route(string $course): ?string
{
    $r = rpl_routes()[$course] ?? [];
    return $r ? (string) array_key_first($r) : null;
}

/** "28 Oct 2026" in South African time. */
function rpl_when(?string $sqlDatetime): string
{
    if (!$sqlDatetime) return '';
    $t = strtotime($sqlDatetime . ' UTC');
    return $t ? gmdate('j M Y', $t + 2 * 3600) : (string) $sqlDatetime;
}

/* ---- what the learner hands in ------------------------------------------ */

/** Why this link is not a Coursera certificate link, or '' if it is. */
function rpl_url_problem(string $url): string
{
    if ($url === '') return '';
    if (mb_strlen($url) > 500) return 'That link is too long to be a certificate link.';
    $p = parse_url($url);
    $host = strtolower((string) ($p['host'] ?? ''));
    if (($p['scheme'] ?? '') !== 'https' || ($host !== 'coursera.org' && !str_ends_with($host, '.coursera.org'))) {
        return 'Paste the certificate link Coursera gives you — it starts with https://www.coursera.org/ '
             . 'or https://coursera.org/verify/.';
    }
    return '';
}

/** Why this upload is not a usable certificate PDF, or '' if it is. */
function rpl_file_problem(array $file): string
{
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) return 'That file is larger than this server accepts.';
    if ($err !== UPLOAD_ERR_OK) return 'That upload did not complete. Please try again.';
    if (strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'pdf') {
        return 'Upload the certificate as the PDF Coursera lets you download.';
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) return 'That file appears to be empty.';
    if ($size > RPL_MAX_BYTES) return 'A certificate PDF is far smaller than that — check it is the right file.';
    $head = (string) @file_get_contents((string) $file['tmp_name'], false, null, 0, 5);
    if (!str_starts_with($head, '%PDF-')) return 'That file does not look like a real PDF.';
    return '';
}

/** Every hand-in for one learner on one course, newest first. */
function rpl_claims_for(int $userId, string $course): array
{
    return db_all(
        'SELECT * FROM rpl_claims WHERE tenant_id = ? AND user_id = ? AND course_slug = ? ORDER BY id DESC',
        [tenant_id(), $userId, $course]
    );
}

function rpl_latest_claim(int $userId, string $course): ?array
{
    return rpl_claims_for($userId, $course)[0] ?? null;
}

/**
 * Hand in a certificate: a Coursera link, a PDF, or both.
 *
 * @return array{0:bool,1:string}
 */
function rpl_claim_store(int $userId, string $course, string $route, string $url, array $file): array
{
    if (rpl_route($course, $route) === null) return [false, 'That course has no certificate route.'];
    if (!learner_is_enrolled($userId, $course)) return [false, 'You are not enrolled on that course.'];

    $latest = rpl_latest_claim($userId, $course);
    if ($latest !== null && $latest['status'] === 'accepted') {
        return [false, 'Your certificate has already been assessed. If something needs changing, speak to the academy.'];
    }

    $url = trim($url);
    $hasFile = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($url === '' && !$hasFile) return [false, 'Paste your certificate link, upload the PDF, or both.'];
    if (($p = rpl_url_problem($url)) !== '') return [false, $p];
    if ($hasFile && ($p = rpl_file_problem($file)) !== '') return [false, $p];

    $disk = null;
    $dest = null;
    if ($hasFile) {
        $dir = app_private_dir('rpl-files');
        if ($dir === null) return [false, 'Storage is not available right now. Please try again shortly.'];
        $disk = bin2hex(random_bytes(16));
        $dest = $dir . '/' . $disk;
        if (!@move_uploaded_file((string) $file['tmp_name'], $dest)) {
            app_log('RPL UPLOAD FAILED to move to ' . $dest);
            return [false, 'That file could not be saved. Please try again.'];
        }
    }

    try {
        $id = db_insert('rpl_claims', [
            'tenant_id'     => tenant_id(),
            'user_id'       => $userId,
            'course_slug'   => $course,
            'route'         => $route,
            'verify_url'    => $url !== '' ? $url : null,
            'disk_name'     => $disk,
            'original_name' => $hasFile ? mb_substr((string) $file['name'], 0, 255) : null,
            'mime_type'     => $hasFile ? 'application/pdf' : null,
            'size_bytes'    => $hasFile ? (int) $file['size'] : null,
            'status'        => 'submitted',
            'submitted_at'  => now(),
            'reviewed_at'   => null,
            'reviewed_by'   => null,
            'review_note'   => null,
        ]);
    } catch (Throwable $e) {
        if ($dest !== null) @unlink($dest);
        app_log('RPL CLAIM DB WRITE FAILED: ' . $e->getMessage());
        return [false, 'That could not be saved. Nothing was changed. Please try again.'];
    }

    audit('rpl.claim_submitted', 'rpl_claims', $id, $course . ' ' . $route);
    return [true, 'Your certificate has been handed in. The academy will look at it and record which modules it covers.'];
}

function rpl_claim_get(int $id): ?array
{
    return db_one('SELECT * FROM rpl_claims WHERE id = ? AND tenant_id = ?', [$id, tenant_id()]);
}

/** Its owner, or an administrator (who is the assessor). */
function rpl_may_view(array $claim, array $u): bool
{
    return (int) $claim['user_id'] === (int) $u['id'] || $u['role'] === 'admin';
}

/* ---- the decision -------------------------------------------------------- */

/** Recognised modules for one learner on one course: module code => row. */
function rpl_credits_for(int $userId, string $course): array
{
    $out = [];
    foreach (db_all(
        'SELECT * FROM rpl_credits WHERE tenant_id = ? AND user_id = ? AND course_slug = ? ORDER BY module_code',
        [tenant_id(), $userId, $course]
    ) as $r) {
        $out[(string) $r['module_code']] = $r;
    }
    return $out;
}

/** The same, as module code => ISO date, for the browser. */
function rpl_credit_map(int $userId, string $course): array
{
    $out = [];
    foreach (rpl_credits_for($userId, $course) as $code => $r) {
        $out[$code] = learner_iso((string) $r['decided_at']);
    }
    return $out;
}

/** How many modules each learner on a course has recognised: user id => count. */
function rpl_credit_counts(string $course): array
{
    $out = [];
    foreach (db_all(
        'SELECT user_id, COUNT(*) AS n FROM rpl_credits WHERE tenant_id = ? AND course_slug = ? GROUP BY user_id',
        [tenant_id(), $course]
    ) as $r) {
        $out[(int) $r['user_id']] = (int) $r['n'];
    }
    return $out;
}

/** The newest hand-in per learner on a course: user id => row. */
function rpl_latest_claims(string $course): array
{
    $out = [];
    foreach (db_all(
        'SELECT * FROM rpl_claims WHERE tenant_id = ? AND course_slug = ? ORDER BY id ASC',
        [tenant_id(), $course]
    ) as $r) {
        $out[(int) $r['user_id']] = $r;   // later rows win
    }
    return $out;
}

/** Only the knowledge module codes this course really has, in curriculum order. */
function rpl_clean_modules(string $course, array $codes): array
{
    $codes = array_map('strval', $codes);
    return array_values(array_filter(
        array_keys(curriculum_modules($course)),
        fn($c) => in_array((string) $c, $codes, true)
    ));
}

/** The approved standard list for a route, or [] if the assessor has not saved one. */
function rpl_standard(string $course, string $route): array
{
    return array_map(fn($r) => (string) $r['module_code'], db_all(
        'SELECT module_code FROM rpl_standard WHERE tenant_id = ? AND course_slug = ? AND route = ? ORDER BY module_code',
        [tenant_id(), $course, $route]
    ));
}

/** When the standard list was last saved, and by whom, or null. */
function rpl_standard_meta(string $course, string $route): ?array
{
    return db_one(
        'SELECT s.set_at, u.first_name, u.last_name FROM rpl_standard s
           LEFT JOIN users u ON u.id = s.set_by
          WHERE s.tenant_id = ? AND s.course_slug = ? AND s.route = ?
          ORDER BY s.set_at DESC LIMIT 1',
        [tenant_id(), $course, $route]
    );
}

/** Replace the standard list. It only affects decisions saved from now on. */
function rpl_standard_set(string $course, string $route, array $codes, int $staffId): array
{
    if (rpl_route($course, $route) === null) return [false, 'Unknown route.'];
    $codes = rpl_clean_modules($course, $codes);
    if (!$codes) return [false, 'Tick at least one module. A standard list with nothing on it recognises nothing.'];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        db_run('DELETE FROM rpl_standard WHERE tenant_id = ? AND course_slug = ? AND route = ?',
               [tenant_id(), $course, $route]);
        foreach ($codes as $c) {
            db_insert('rpl_standard', [
                'tenant_id' => tenant_id(), 'course_slug' => $course, 'route' => $route,
                'module_code' => $c, 'set_at' => now(), 'set_by' => $staffId,
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        app_log('RPL STANDARD FAILED: ' . $e->getMessage());
        return [false, 'That could not be saved. Nothing was changed.'];
    }
    audit('rpl.standard_set', 'rpl_standard', null, $course . ' ' . $route . ': ' . implode(', ', $codes));
    return [true, 'Standard list saved: ' . implode(', ', $codes) . '. Learners already decided are not changed.'];
}

/**
 * Record the decision for one learner: exactly these modules are recognised.
 * Accepts the newest hand-in if it is still waiting.
 *
 * @return array{0:bool,1:string}
 */
function rpl_decide(int $userId, string $course, string $route, array $codes, string $note, int $staffId): array
{
    if (rpl_route($course, $route) === null) return [false, 'Unknown route.'];
    if (!learner_is_enrolled($userId, $course)) return [false, 'That learner is not enrolled on this course.'];

    $codes = rpl_clean_modules($course, $codes);
    $note  = mb_substr(trim($note), 0, 500);
    $have  = rpl_credits_for($userId, $course);
    $add   = array_values(array_diff($codes, array_keys($have)));
    $drop  = array_values(array_diff(array_keys($have), $codes));
    $claim = rpl_latest_claim($userId, $course);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach ($add as $c) {
            db_insert('rpl_credits', [
                'tenant_id' => tenant_id(), 'user_id' => $userId, 'course_slug' => $course,
                'module_code' => $c, 'route' => $route, 'note' => $note !== '' ? $note : null,
                'decided_at' => now(), 'decided_by' => $staffId,
            ]);
        }
        foreach ($drop as $c) {
            db_run('DELETE FROM rpl_credits WHERE tenant_id = ? AND user_id = ? AND course_slug = ? AND module_code = ?',
                   [tenant_id(), $userId, $course, $c]);
        }
        if ($claim !== null && $claim['status'] === 'submitted') {
            db_run('UPDATE rpl_claims SET status = ?, reviewed_at = ?, reviewed_by = ?, review_note = ?
                     WHERE id = ? AND tenant_id = ?',
                   ['accepted', now(), $staffId, $note !== '' ? $note : null, (int) $claim['id'], tenant_id()]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        app_log('RPL DECISION FAILED: ' . $e->getMessage());
        return [false, 'That could not be saved. Nothing was changed.'];
    }

    audit('rpl.decided', 'users', $userId, $course . ' ' . $route . ' — recognised: '
          . ($codes ? implode(', ', $codes) : 'none')
          . ($add ? ' · added ' . implode(', ', $add) : '') . ($drop ? ' · withdrew ' . implode(', ', $drop) : ''));

    return [true, $codes
        ? 'Saved. Recognised: ' . implode(', ', $codes) . '. The learner sees these as recognised now.'
        : 'Saved. No modules are recognised for this learner.'];
}

/** Send the newest hand-in back, with a reason. Recognised modules are not touched. */
function rpl_decline(int $userId, string $course, string $note, int $staffId): array
{
    $note = mb_substr(trim($note), 0, 500);
    if ($note === '') return [false, 'Say why, so the learner knows what to send instead.'];
    $claim = rpl_latest_claim($userId, $course);
    if ($claim === null || $claim['status'] !== 'submitted') return [false, 'There is no hand-in waiting to be declined.'];

    db_run('UPDATE rpl_claims SET status = ?, reviewed_at = ?, reviewed_by = ?, review_note = ? WHERE id = ? AND tenant_id = ?',
           ['declined', now(), $staffId, $note, (int) $claim['id'], tenant_id()]);
    audit('rpl.declined', 'rpl_claims', (int) $claim['id'], $note);
    return [true, 'Sent back to the learner with your note. They can hand in again.'];
}
