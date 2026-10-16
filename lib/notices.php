<?php
declare(strict_types=1);

/* The notice board.
 *
 * Somewhere staff can tell learners things that would otherwise travel by
 * WhatsApp and be missed: a class that will be held in Google Classroom, a
 * changed date, a reminder that a workbook is due. A notice is for everybody on
 * the academy (course_slug NULL) or for the learners on one course.
 *
 * WHO MAY POST. An administrator, for anything. A facilitator, for the courses
 * they are assigned to and for nobody else — never "everybody", because a
 * notice to the whole academy from one course's facilitator is how two cohorts
 * get told different things on the same morning. This is a write a trainer did
 * not have before (see lib/auth.php), added on 7 Oct 2026 on Sibusiso's
 * instruction, and it is deliberately the narrow kind: it announces, it does
 * not schedule a class, upload material or touch a learner's record.
 *
 * WHAT A LINK MAY POINT AT. A notice's whole purpose is often to carry the
 * address of a Classroom or Meet session, and a link in a notice is a link
 * every enrolled learner will click without thinking. So it must be https and
 * it must be Google. That is the same reasoning materials_url_allowed() applies
 * to course material, and it is a list to widen deliberately, not a check to
 * loosen when somebody pastes something else.
 */

defined('APP_BOOTED') or exit('lib/notices.php is not a page.');

const NOTICE_TITLE_MAX = 160;
const NOTICE_BODY_MAX  = 4000;
const NOTICE_LABEL_MAX = 80;

/* Hosts a notice link may point at: the host itself or any subdomain of it. */
const NOTICE_LINK_HOSTS = ['google.com', 'youtu.be', 'youtube.com'];

function notice_link_allowed(string $url): bool
{
    $p = parse_url($url);
    if (!is_array($p) || strtolower((string) ($p['scheme'] ?? '')) !== 'https') return false;
    if (isset($p['user']) || isset($p['pass'])) return false;   // https://google.com@evil.example/
    $host = strtolower((string) ($p['host'] ?? ''));
    if ($host === '') return false;
    foreach (NOTICE_LINK_HOSTS as $ok) {
        if ($host === $ok || str_ends_with($host, '.' . $ok)) return true;
    }
    return false;
}

/** May this person post a notice to this audience? $slug null means everybody. */
function notice_may_post(?string $slug, ?array $u = null): bool
{
    $u = $u ?? current_user();
    if ($u === null) return false;
    if ($u['role'] === 'admin') return true;
    if ($u['role'] !== 'trainer' || $slug === null || $slug === '') return false;
    return may_see_course($slug, $u);
}

/** May this person change or remove this notice? */
function notice_may_edit(array $notice, ?array $u = null): bool
{
    $slug = ($notice['course_slug'] ?? null) !== null ? (string) $notice['course_slug'] : null;
    return notice_may_post($slug, $u);
}

/**
 * What a learner sees: everybody notices, plus those for the courses they are
 * on, minus the ones that have expired. Pinned first, then newest.
 */
function notices_for_learner(int $userId, int $limit = 50): array
{
    $slugs = array_map(static fn($e) => (string) $e['course_slug'], learner_enrolments($userId));
    $in    = $slugs ? ' OR n.course_slug IN (' . implode(',', array_fill(0, count($slugs), '?')) . ')' : '';
    return db_all(
        'SELECT n.*, u.first_name AS by_first, u.last_name AS by_last
           FROM notices n
           LEFT JOIN users u ON u.id = n.created_by AND u.tenant_id = n.tenant_id
          WHERE n.tenant_id = ?
            AND (n.expires_on IS NULL OR n.expires_on >= ?)
            AND (n.course_slug IS NULL' . $in . ')
          ORDER BY n.pinned DESC, n.created_at DESC, n.id DESC
          LIMIT ' . (int) $limit,
        array_merge([tenant_id(), gmdate('Y-m-d')], $slugs)
    );
}

/**
 * What staff see on the manage page: an administrator sees all of it, a
 * facilitator sees the notices for their courses. Expired ones are included,
 * because the person who posted it may want to repost it.
 */
function notices_for_staff(?array $u = null, int $limit = 200): array
{
    $u      = $u ?? current_user();
    $slugs  = $u !== null ? trainer_slugs($u) : [];
    $params = [tenant_id()];
    $where  = '';
    if ($slugs !== null) {                       // null = administrator, no restriction
        if (!$slugs) return [];
        $where  = ' AND n.course_slug IN (' . implode(',', array_fill(0, count($slugs), '?')) . ')';
        $params = array_merge($params, $slugs);
    }
    return db_all(
        'SELECT n.*, u.first_name AS by_first, u.last_name AS by_last
           FROM notices n
           LEFT JOIN users u ON u.id = n.created_by AND u.tenant_id = n.tenant_id
          WHERE n.tenant_id = ?' . $where . '
          ORDER BY n.pinned DESC, n.created_at DESC, n.id DESC
          LIMIT ' . (int) $limit,
        $params
    );
}

function notice_get(int $id): ?array
{
    return db_one('SELECT * FROM notices WHERE id = ? AND tenant_id = ?', [$id, tenant_id()]);
}

/**
 * Validate and store a notice. $id null creates, otherwise it edits.
 *
 * @return array{0:bool,1:string}
 */
function notice_save(array $in, int $by, ?int $id = null): array
{
    $slug = trim((string) ($in['course_slug'] ?? ''));
    $slug = $slug === '' ? null : $slug;

    if ($slug !== null && !learner_course_valid($slug)) return [false, 'That is not a course on this academy.'];
    if (!notice_may_post($slug)) {
        audit('write.denied', 'notices', $id, (string) $slug);
        return [false, 'Your account cannot post to that audience.'];
    }

    $title = trim((string) ($in['title'] ?? ''));
    $body  = trim((string) ($in['body'] ?? ''));
    if ($title === '') return [false, 'Give the notice a title.'];
    if (mb_strlen($title) > NOTICE_TITLE_MAX) return [false, 'Keep the title under ' . NOTICE_TITLE_MAX . ' characters.'];
    if ($body === '') return [false, 'Write what learners need to know.'];
    if (mb_strlen($body) > NOTICE_BODY_MAX) return [false, 'That notice is too long. Keep it under ' . NOTICE_BODY_MAX . ' characters.'];

    $url = trim((string) ($in['link_url'] ?? ''));
    if ($url !== '' && !notice_link_allowed($url)) {
        return [false, 'The link must start with https:// and be a Google address, such as a Classroom or Meet link.'];
    }
    $label = trim((string) ($in['link_label'] ?? ''));
    if ($url !== '' && $label === '') $label = 'Open the link';
    if (mb_strlen($label) > NOTICE_LABEL_MAX) return [false, 'Keep the link text under ' . NOTICE_LABEL_MAX . ' characters.'];

    $expires = trim((string) ($in['expires_on'] ?? ''));
    if ($expires !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $expires);
        if (!$d || $d->format('Y-m-d') !== $expires) return [false, 'Give the date as YYYY-MM-DD, or leave it blank.'];
    }

    $row = [
        'course_slug' => $slug,
        'title'       => $title,
        'body'        => $body,
        'link_url'    => $url !== '' ? $url : null,
        'link_label'  => $url !== '' ? $label : null,
        'pinned'      => !empty($in['pinned']) ? 1 : 0,
        'expires_on'  => $expires !== '' ? $expires : null,
        'updated_at'  => now(),
    ];

    if ($id !== null) {
        $have = notice_get($id);
        if ($have === null) return [false, 'That notice no longer exists.'];
        if (!notice_may_edit($have)) {
            audit('write.denied', 'notices', $id, (string) ($have['course_slug'] ?? ''));
            return [false, 'Your account cannot change that notice.'];
        }
        db_run('UPDATE notices SET course_slug = ?, title = ?, body = ?, link_url = ?, link_label = ?,
                       pinned = ?, expires_on = ?, updated_at = ?
                 WHERE id = ? AND tenant_id = ?',
               [$row['course_slug'], $row['title'], $row['body'], $row['link_url'], $row['link_label'],
                $row['pinned'], $row['expires_on'], $row['updated_at'], $id, tenant_id()]);
        audit('notice.updated', 'notices', $id, $title);
        return [true, 'Notice updated.'];
    }

    $newId = db_insert('notices', $row + ['tenant_id' => tenant_id(), 'created_at' => now(), 'created_by' => $by]);
    audit('notice.posted', 'notices', $newId, ($slug ?? 'everybody') . ' — ' . $title);
    return [true, 'Posted. Learners will see it on their notice board.'];
}

/** @return array{0:bool,1:string} */
function notice_delete(int $id): array
{
    $have = notice_get($id);
    if ($have === null) return [false, 'That notice no longer exists.'];
    if (!notice_may_edit($have)) {
        audit('write.denied', 'notices', $id, (string) ($have['course_slug'] ?? ''));
        return [false, 'Your account cannot remove that notice.'];
    }
    db_run('DELETE FROM notices WHERE id = ? AND tenant_id = ?', [$id, tenant_id()]);
    audit('notice.removed', 'notices', $id, (string) $have['title']);
    return [true, 'Notice removed.'];
}

/** "Everybody", or the course's title. */
function notice_audience(array $n): string
{
    $slug = $n['course_slug'] ?? null;
    return $slug === null || $slug === '' ? 'Everybody' : learner_course_title((string) $slug);
}

/** A date the way a learner reads it. */
function notice_when(string $sqlDatetime): string
{
    $t = strtotime($sqlDatetime . ' UTC');
    return $t ? gmdate('j M Y, H:i', $t + 2 * 3600) . ' SAST' : $sqlDatetime;
}
