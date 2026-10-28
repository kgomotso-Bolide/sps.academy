<?php
declare(strict_types=1);

/* Workbooks and practical workbooks, completed as Word documents and
 * submitted on the platform.
 *
 * WHAT CHANGED, AND FROM WHAT. Until 7 Oct 2026 every submission was physical:
 * poe_submissions recorded where the paper was, and the comment in the schema
 * argued that an online copy "could only disagree with the signed hardcopy".
 * Sibusiso's instruction of 7 Oct reverses that for the workbook and the
 * practical workbook: the learner completes the Word document and uploads it
 * here. The LOGBOOK is untouched — it is still typed on the platform, printed
 * and signed, and still tracked by poe_submissions alone.
 *
 * TWO TABLES, ONE STATUS. poe_files holds the bytes and is append-only: every
 * upload is a new row, so what a facilitator assessed is still on file after
 * the learner sends a better one. Where the thing stands — handed in, assessed,
 * returned — stays in poe_submissions, which an upload writes to and a
 * facilitator updates. That is what keeps the Files grid facilitators already
 * use truthful for both routes, and it means there is exactly one place that
 * says "assessed".
 *
 * WHAT A LEARNER CAN AND CANNOT DO. They can upload until a facilitator marks
 * the slot assessed. After that it is locked, because an assessed file that can
 * be swapped is not evidence. A facilitator sets it to "returned" to reopen it.
 *
 * WHERE THE BYTES LIVE. app_private_dir('poe-files'): outside the web root and
 * outside the git-tracked tree, each file named by a random id with no
 * extension, exactly as lib/material_files.php does. Nothing here is reachable
 * by URL; poe-file.php checks who is asking and streams it.
 *
 * WHAT IS TRUSTED. The mime type stored and sent is the canonical one for the
 * extension, never the browser's claim.
 */

defined('APP_BOOTED') or exit('lib/submissions.php is not a page.');

const SUBMISSION_KINDS = ['workbook', 'practical'];

const SUBMISSION_EXT = [
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'doc'  => 'application/msword',
    'pdf'  => 'application/pdf',
];

const SUBMISSION_MAX_BYTES = 25 * 1024 * 1024;

/* Courses whose modules each have a workbook the learner completes. Procurement
   Officer's pack has none, and New Venture Creation pairs its guide with a
   Workplace Guide, which is a different document — add a course here only once
   somebody has said what its learners submit. */
const SUBMISSION_WORKBOOK_COURSES = ['project-management'];

/* The practical modules. They are not in pm-modules.js, which carries only the
   eleven knowledge modules, so they are listed here. Titles are the registered
   unit standard titles from the practical pack covers. */
function submission_practicals(string $courseSlug): array
{
    if ($courseSlug !== 'project-management') return [];
    return [
        'PM-01' => 'Initiate a project',
        'PM-02' => 'Plan and develop a project management approach and scope statement',
        'PM-03' => 'Plan and develop a project time line and schedule',
        'PM-04' => 'Plan for and project the cost of a project',
        'PM-05' => 'Plan project management systems',
        'PM-06' => 'Monitor and control the scope of a project',
        'PM-07' => 'Control the project delivery schedules and costs',
        'PM-08' => 'Control the project quality',
        'PM-09' => 'Manage and control the human resources of a project',
        'PM-10' => 'Conduct and control project communication and stakeholder interaction',
        'PM-11' => 'Manage and control project risks',
        'PM-12' => 'Manage and control project procurement activities',
        'PM-13' => 'Manage and control project close-out activities',
    ];
}

function submission_kind_label(string $kind): string
{
    return ['workbook' => 'Workbook', 'practical' => 'Practical workbook'][$kind] ?? $kind;
}

/** Does this course take uploaded workbooks at all? */
function submission_course_takes(string $courseSlug): bool
{
    return in_array($courseSlug, SUBMISSION_WORKBOOK_COURSES, true) || submission_practicals($courseSlug) !== [];
}

/**
 * Everything a learner on this course uploads, in the order they meet it.
 *
 * @return list<array{code:string,kind:string,title:string}>
 */
function submission_slots(string $courseSlug): array
{
    $slots = [];
    if (in_array($courseSlug, SUBMISSION_WORKBOOK_COURSES, true)) {
        foreach (curriculum_modules($courseSlug) as $code => $m) {
            $slots[] = ['code' => (string) $code, 'kind' => 'workbook', 'title' => (string) $m['title']];
        }
    }
    foreach (submission_practicals($courseSlug) as $code => $title) {
        $slots[] = ['code' => $code, 'kind' => 'practical', 'title' => $title];
    }
    return $slots;
}

function submission_slot_valid(string $courseSlug, string $code, string $kind): bool
{
    foreach (submission_slots($courseSlug) as $s) {
        if ($s['code'] === $code && $s['kind'] === $kind) return true;
    }
    return false;
}

/** Why this upload cannot be accepted, or '' if it can. */
function submission_file_problem(array $file): string
{
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) return 'Choose the file you completed first.';
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        return 'That file is larger than this server accepts ('
             . material_file_format_bytes(min(material_file_effective_upload_cap(), SUBMISSION_MAX_BYTES)) . ').';
    }
    if ($err !== UPLOAD_ERR_OK) return 'That upload did not complete. Please try again.';

    $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!isset(SUBMISSION_EXT[$ext])) return 'Upload the Word document itself (.docx), or a PDF.';

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) return 'That file appears to be empty.';
    if ($size > SUBMISSION_MAX_BYTES) {
        return 'That file is larger than the ' . material_file_format_bytes(SUBMISSION_MAX_BYTES) . ' limit.';
    }
    /* The extension is the learner's claim. The first bytes are cheap evidence: a .docx is a ZIP
       container and a PDF opens with its own marker. Anything else is renamed junk. */
    $head = (string) @file_get_contents((string) $file['tmp_name'], false, null, 0, 5);
    $isZip = str_starts_with($head, 'PK' . chr(3) . chr(4));
    $isPdf = str_starts_with($head, '%PDF-');
    $isOle = str_starts_with($head, chr(0xD0) . chr(0xCF) . chr(0x11) . chr(0xE0));
    if (($ext === 'docx' && !$isZip) || ($ext === 'pdf' && !$isPdf) || ($ext === 'doc' && !$isZip && !$isOle)) {
        return 'That file does not look like a real ' . strtoupper($ext) . '. Save it from Word again and retry.';
    }
    return '';
}

/** Where a slot stands, as the Files grid records it. */
function submission_status(int $userId, string $courseSlug, string $code, string $kind): ?array
{
    return db_one(
        'SELECT * FROM poe_submissions
          WHERE tenant_id = ? AND course_slug = ? AND module_code = ? AND user_id = ? AND kind = ?',
        [tenant_id(), $courseSlug, $code, $userId, $kind]
    );
}

/**
 * Store an upload. The learner must be signed in as themselves, enrolled, and
 * the slot must exist and not be locked.
 *
 * @return array{0:bool,1:string}
 */
function submission_store(int $userId, string $courseSlug, string $code, string $kind, array $file): array
{
    if (!submission_slot_valid($courseSlug, $code, $kind)) return [false, 'That is not something this course asks you to submit.'];
    if (!learner_is_enrolled($userId, $courseSlug))        return [false, 'You are not enrolled on that course.'];

    $status = submission_status($userId, $courseSlug, $code, $kind);
    if ($status !== null && $status['status'] === 'assessed') {
        return [false, 'That has already been assessed, so it is locked. If it needs another look, ask your facilitator to return it to you.'];
    }

    $problem = submission_file_problem($file);
    if ($problem !== '') return [false, $problem];

    $ext  = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $dir  = app_private_dir('poe-files');
    if ($dir === null) return [false, 'Storage is not available right now. Please try again shortly.'];

    $disk = bin2hex(random_bytes(16));
    $dest = $dir . '/' . $disk;
    if (!@move_uploaded_file((string) $file['tmp_name'], $dest)) {
        app_log('POE UPLOAD FAILED to move to ' . $dest);
        return [false, 'That file could not be saved. Please try again.'];
    }

    try {
        $fileId = db_insert('poe_files', [
            'tenant_id'     => tenant_id(),
            'user_id'       => $userId,
            'course_slug'   => $courseSlug,
            'module_code'   => $code,
            'kind'          => $kind,
            'disk_name'     => $disk,
            'original_name' => mb_substr((string) $file['name'], 0, 255),
            'mime_type'     => SUBMISSION_EXT[$ext],
            'size_bytes'    => (int) $file['size'],
            'submitted_at'  => now(),
        ]);

        /* The slot's status. updated_by is NULL on purpose: that column means "the
           person who took custody", and for an upload nobody did yet. A
           facilitator's later change fills it in. A returned slot goes back to
           handed in, which is the learner saying they have done what was asked. */
        $note = 'Submitted online';
        if ($status === null) {
            db_insert('poe_submissions', [
                'tenant_id' => tenant_id(), 'course_slug' => $courseSlug, 'module_code' => $code,
                'user_id' => $userId, 'kind' => $kind, 'status' => 'handed_in',
                'on_date' => gmdate('Y-m-d'), 'note' => $note, 'updated_at' => now(), 'updated_by' => null,
            ]);
        } else {
            db_run('UPDATE poe_submissions SET status = ?, on_date = ?, note = ?, updated_at = ?, updated_by = NULL
                     WHERE id = ? AND tenant_id = ?',
                   ['handed_in', gmdate('Y-m-d'), $note, now(), (int) $status['id'], tenant_id()]);
        }
    } catch (Throwable $e) {
        @unlink($dest);
        app_log('POE UPLOAD DB WRITE FAILED: ' . $e->getMessage());
        return [false, 'That could not be saved. Nothing was changed. Please try again.'];
    }

    audit('poe.uploaded', 'poe_files', $fileId, $courseSlug . ' ' . $code . ' ' . $kind);
    return [true, submission_kind_label($kind) . ' for ' . $code . ' submitted. Your facilitator can see it now.'];
}

/** One learner's uploads on one course, newest first, keyed "code:kind". */
function submission_history(int $userId, string $courseSlug): array
{
    $out = [];
    foreach (db_all(
        'SELECT * FROM poe_files WHERE tenant_id = ? AND user_id = ? AND course_slug = ?
          ORDER BY id DESC',
        [tenant_id(), $userId, $courseSlug]
    ) as $r) {
        $out[(string) $r['module_code'] . ':' . (string) $r['kind']][] = $r;
    }
    return $out;
}

/** The newest upload per slot for a whole course, keyed "userId:code:kind". */
function submission_latest_for_course(string $courseSlug): array
{
    $out = [];
    foreach (db_all(
        'SELECT * FROM poe_files WHERE tenant_id = ? AND course_slug = ? ORDER BY id ASC',
        [tenant_id(), $courseSlug]
    ) as $r) {
        $out[(int) $r['user_id'] . ':' . (string) $r['module_code'] . ':' . (string) $r['kind']] = $r;   // later rows win
    }
    return $out;
}

function submission_file_get(int $id): ?array
{
    return db_one('SELECT * FROM poe_files WHERE id = ? AND tenant_id = ?', [$id, tenant_id()]);
}

/** Who may download a submitted file: its owner, an administrator, or a facilitator on that course. */
function submission_may_view(array $row, array $u): bool
{
    if ((int) $row['user_id'] === (int) $u['id']) return true;
    return poe_may_record((string) $row['course_slug'], $u);
}

/** Send the file as a download. $sub is the private folder it lives in — rpl-file.php shares this. */
function submission_stream(array $row, string $sub = 'poe-files'): void
{
    $dir  = app_private_dir($sub);
    $path = $dir !== null ? $dir . '/' . $row['disk_name'] : null;
    if ($path === null || !is_file($path)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'That file is no longer available.';
        return;
    }
    $name = preg_replace('/[\r\n"]/', '', (string) $row['original_name']);
    header('Content-Type: ' . (string) $row['mime_type']);
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    readfile($path);
}

/** "12 Oct 2026, 14:05" — the way a learner reads when they sent it. */
function submission_when(string $sqlDatetime): string
{
    $t = strtotime($sqlDatetime . ' UTC');
    return $t ? gmdate('j M Y, H:i', $t + 2 * 3600) : $sqlDatetime;
}
