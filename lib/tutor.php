<?php
declare(strict_types=1);

/* The study tutor (16 Nov 2026, Kgomotso, via Sibusiso): "an online tutor",
 * static for now, answering from the course material the academy already has,
 * and built so a Claude model can be connected later without a rewrite.
 *
 * SHARED: synced to every academy. Switched on per site by 'tutor' in
 * lib/brand.php.
 *
 * WHAT IT IS MODELLED ON (research, 16 Nov 2026)
 *   - Grounded, like Coursera Coach: it answers from THIS course's material
 *     and says where each answer came from. Never from the open web.
 *   - Explain, then check, like the 2025 "study modes" (ChatGPT Study Mode,
 *     Claude learning mode, Gemini Guided Learning). Not Socratic-only: the
 *     2026 write-ups of Khanmigo found learners gave up when a tutor withheld
 *     everything, so this one explains first and then asks them to say it back.
 *   - Checkpoints, like Gemini: "Quiz me" is retrieval practice. It asks the
 *     learner to explain a point from the topic's outline, then shows what
 *     the material says so they can mark themselves.
 *   - Refuses assessment answers, like all of them. The 80% topic checks
 *     count towards the record (2 tries, facilitator reset), the workbooks are
 *     evidence for the portfolio, and the EISA is external. The tutor does not
 *     read the quiz tables at all, so it cannot leak them even by accident.
 *
 * WHERE THE ANSWERS COME FROM
 *   1. The Reading (topic_sections), published rows only. That is the
 *      learner guide's text, loaded on Administration > Reading.
 *   2. The module outline in pm-/po-/nvc-modules.js: each topic's "covers"
 *      list and its key idea. It is always there, so a course with no Reading
 *      loaded yet still gets useful (if shorter) answers.
 *
 * WHEN CLAUDE IS CONNECTED
 *   tutor_answer() is the only entry point. It retrieves the passages first,
 *   and that retrieval stays: a model is given these passages and told to
 *   answer only from them, which is what keeps it on the course. The provider
 *   is chosen by the server config 'tutor_provider' ('static' unless set).
 *   Only 'static' exists today; anything else falls back to it. Add the
 *   provider in tutor_provider_reply() and keep the reply shape the same.
 *
 * PRIVACY
 *   Questions are not stored. The audit log records that the tutor was used,
 *   with the kind of question and the module, never the words typed. Before
 *   a third-party model is connected, the privacy notice must say that the
 *   question and passages are sent to it (POPIA), so that is a decision for
 *   Kgomotso, not a config switch to flip quietly.
 */

const TUTOR_MAX_Q      = 600;    // characters in a question
const TUTOR_CHUNK      = 900;    // characters per searchable passage
const TUTOR_RATE_HOUR  = 120;    // questions per signed-in session per hour

/* ---------------------------------------------------------------------------
 * The material
 * ------------------------------------------------------------------------- */

/**
 * Each topic's outline from the module file: the "covers" bullets and the key
 * idea. Read with the same cut curriculum_modules() uses (only the MODULES
 * array), so the registry block at the end of a file cannot leak in.
 *
 * @return array<string, array{covers:list<string>, key:string}> topic code =>
 */
function tutor_outline(string $slug): array
{
    static $cache = [];
    if (isset($cache[$slug])) return $cache[$slug];
    $file = curriculum_files()[$slug] ?? '';
    $js = $file !== '' ? (string) @file_get_contents(APP_ROOT . '/' . $file) : '';
    $start = strpos($js, 'const MODULES = [');
    $end   = $start === false ? false : strpos($js, "\n  ];", $start);
    if ($start === false || $end === false) return $cache[$slug] = [];
    $js = substr($js, $start, $end - $start);

    $out = [];
    foreach (curriculum_modules($slug) as $mid => $m) {
        foreach (array_keys($m['topics']) as $tc) {
            $at = strpos($js, 'code: "' . $tc . '"');
            if ($at === false) continue;
            $next = strpos($js, '{ code: "', $at + 10);
            $nextMod = strpos($js, 'id: "', $at);
            $stop = min($next === false ? strlen($js) : $next, $nextMod === false ? strlen($js) : $nextMod);
            $block = substr($js, $at, $stop - $at);
            $covers = [];
            if (preg_match('/covers:\s*\[(.*?)\]/s', $block, $cm)) {
                preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"/', $cm[1], $sm);
                $covers = array_map('stripcslashes', $sm[1]);
            }
            $key = preg_match('/\bkey:\s*"((?:[^"\\\\]|\\\\.)*)"/', $block, $km) ? stripcslashes($km[1]) : '';
            $out[$tc] = ['covers' => $covers, 'key' => $key];
        }
    }
    return $cache[$slug] = $out;
}

/**
 * Every searchable passage on one course: the Reading cut into paragraph-sized
 * pieces, plus one passage per topic outline.
 *
 * @return list<array{course:string, module:string, topic:string, title:string, text:string, kind:string}>
 */
function tutor_passages(string $slug): array
{
    static $cache = [];
    if (isset($cache[$slug])) return $cache[$slug];
    $mods = curriculum_modules($slug);
    $out = [];

    $rows = db_optional(fn() => db_all(
        'SELECT module_code, topic_code, area_title, body FROM topic_sections
          WHERE tenant_id = ? AND course_slug = ? AND published = 1
          ORDER BY module_code, topic_code, area_index', [tenant_id(), $slug]), []) ?: [];
    foreach ($rows as $r) {
        $mid = (string) $r['module_code']; $tc = (string) $r['topic_code'];
        $title = (string) ($mods[$mid]['topics'][$tc] ?? $r['area_title'] ?? '');
        $buf = '';
        foreach (preg_split('/\R\s*\R/u', trim((string) $r['body'])) ?: [] as $para) {
            $para = trim(preg_replace('/\s+/u', ' ', $para) ?? '');
            if ($para === '') continue;
            if ($buf !== '' && mb_strlen($buf) + mb_strlen($para) > TUTOR_CHUNK) {
                $out[] = ['course' => $slug, 'module' => $mid, 'topic' => $tc, 'title' => $title, 'text' => $buf, 'kind' => 'reading', 'prose' => tutor_prose($buf)];
                $buf = '';
            }
            $buf = $buf === '' ? $para : $buf . "\n" . $para;
        }
        if ($buf !== '') $out[] = ['course' => $slug, 'module' => $mid, 'topic' => $tc, 'title' => $title, 'text' => $buf, 'kind' => 'reading', 'prose' => tutor_prose($buf)];
    }

    foreach (tutor_outline($slug) as $tc => $o) {
        $mid = curriculum_module_of($tc, $slug);
        $title = (string) ($mods[$mid]['topics'][$tc] ?? '');
        $text = trim($title . '. ' . ($o['key'] !== '' ? $o['key'] . ' ' : '') . implode('. ', $o['covers']));
        $out[] = ['course' => $slug, 'module' => $mid, 'topic' => $tc, 'title' => $title, 'text' => $text, 'kind' => 'outline'];
    }
    return $cache[$slug] = $out;
}

/* ---------------------------------------------------------------------------
 * Search: BM25 over the passages, with a lift for the module being studied
 * ------------------------------------------------------------------------- */

const TUTOR_STOP = ['a','an','the','and','or','of','to','in','on','for','is','are','was','be','it','this','that','what','which',
    'how','why','when','who','do','does','did','i','me','my','you','your','we','with','as','at','by','from','can','could','should',
    'would','about','explain','tell','mean','means','meaning','define','definition','please','some','any','there','their','them',
    'into','its','also','not','but','if','so','than','then','more','most','give','show','help','understand','between','difference'];

/** @return list<string> */
function tutor_terms(string $s): array
{
    $s = mb_strtolower($s);
    preg_match_all('/[\p{L}\p{N}]+/u', $s, $m);
    $out = [];
    foreach ($m[0] as $w) {
        if (mb_strlen($w) < 2 || in_array($w, TUTOR_STOP, true)) continue;
        $out[] = tutor_stem($w);
    }
    return $out;
}

/** A light stemmer: enough that "risks", "risk" and "risky" meet. */
function tutor_stem(string $w): string
{
    foreach (['ational' => 'ate', 'ization' => 'ize', 'ments' => 'ment', 'ness' => '', 'ings' => '', 'ing' => '', 'ies' => 'y',
              'ied' => 'y', 'ed' => '', 'es' => '', 's' => ''] as $suf => $rep) {
        if (mb_strlen($w) > mb_strlen($suf) + 3 && str_ends_with($w, $suf)) return mb_substr($w, 0, -mb_strlen($suf)) . $rep;
    }
    return $w;
}

/**
 * @param list<array<string,string>> $passages
 * @return list<array{p:array<string,string>, score:float}> best first
 */
function tutor_search(string $q, array $passages, string $module = '', string $topic = '', int $limit = 3): array
{
    $terms = array_values(array_unique(tutor_terms($q)));
    if (!$terms || !$passages) return [];
    $N = count($passages); $df = []; $docs = []; $len = 0;
    foreach ($passages as $i => $p) {
        $tf = array_count_values(tutor_terms($p['title'] . ' ' . $p['text']));
        $docs[$i] = $tf; $len += array_sum($tf);
        foreach ($terms as $t) if (isset($tf[$t])) $df[$t] = ($df[$t] ?? 0) + 1;
    }
    $avg = max(1, $len / $N); $k1 = 1.4; $b = 0.75;
    $hits = [];
    foreach ($docs as $i => $tf) {
        $dl = array_sum($tf); $s = 0.0; $matched = 0;
        foreach ($terms as $t) {
            if (!isset($tf[$t])) continue;
            $matched++;
            $idf = log(1 + ($N - $df[$t] + 0.5) / ($df[$t] + 0.5));
            $s += $idf * ($tf[$t] * ($k1 + 1)) / ($tf[$t] + $k1 * (1 - $b + $b * $dl / $avg));
        }
        if ($s <= 0) continue;
        $p = $passages[$i];
        $s *= 0.6 + 0.4 * ($matched / count($terms));          // all the words, not one of them many times
        /* A run of headings from the guide matches every word and explains nothing. */
        $s *= (float) ($p['prose'] ?? 1.0);
        if ($topic !== '' && $p['topic'] === $topic) $s *= 1.5;
        elseif ($module !== '' && $p['module'] === $module) $s *= 1.25;
        $hits[] = ['p' => $p, 'score' => $s];
    }
    usort($hits, fn($a, $b) => $b['score'] <=> $a['score']);
    /* One passage per topic, so three answers are three different places. */
    $seen = []; $out = [];
    foreach ($hits as $h) {
        $k = $h['p']['topic'];
        if (isset($seen[$k])) continue;
        $seen[$k] = true; $out[] = $h;
        if (count($out) >= $limit) break;
    }
    return $out;
}

/** The two or three sentences of a passage that carry the question's words. */
function tutor_excerpt(string $text, string $q, int $max = 520): string
{
    /* Sentences, and the guide's own line breaks: a heading has no full stop,
       so without the second rule it is glued to the sentence after it. */
    $sent = [];
    foreach (preg_split('/\n/u', $text) ?: [$text] as $line) {
        foreach (preg_split('/(?<=[.!?])\s+(?=[\p{Lu}\d"“(])/u', trim($line)) ?: [] as $s) {
            if (trim($s) !== '') $sent[] = trim($s);
        }
    }
    if (!$sent) return mb_strimwidth($text, 0, $max, '…');
    $terms = tutor_terms($q);
    /* A line that only repeats the question (a heading, usually) tells the
       learner nothing they did not already read, so it is not where to start. */
    $echo = static function (string $s) use ($terms): bool {
        $st = array_unique(tutor_terms($s));
        return $st && !preg_match('/[.!?]$/u', $s) && count(array_intersect($st, $terms)) / count($st) >= 0.6;
    };
    $best = 0; $bestScore = -1.0;
    foreach ($sent as $i => $s) {
        if ($echo($s)) continue;
        $score = count(array_intersect($terms, tutor_terms($s))) * (preg_match('/[.!?]$/u', $s) ? 1.5 : 1.0);
        if ($score > $bestScore) { $bestScore = $score; $best = $i; }
    }
    $out = '';
    for ($i = $best; $i < count($sent); $i++) {
        if ($out !== '' && $echo($sent[$i])) continue;
        if ($out !== '' && mb_strlen($out) + mb_strlen($sent[$i]) > $max) break;
        $out .= ($out === '' ? '' : ' ') . $sent[$i];
    }
    if ($out === '') $out = mb_strimwidth($text, 0, $max, '…');
    return mb_strlen($out) > $max + 80 ? mb_strimwidth($out, 0, $max, '…') : $out;
}

/* ---------------------------------------------------------------------------
 * What kind of question it is
 * ------------------------------------------------------------------------- */

function tutor_intent(string $q): string
{
    $s = mb_strtolower(trim($q));
    $assess = '(quiz|test|check|assessment|exam|eisa|workbook|assignment|memo|poe|logbook)';
    if (preg_match("/\\b(answers?|memo|solutions?|marking)\\b.*\\b$assess|\\b$assess\\b.*\\b(answers?|solutions?)\\b|\\b(do|write|complete|fill in) my $assess/u", $s)) return 'assessment';
    if (preg_match('/^(hi|hello|hey|hallo|sawubona|good (morning|afternoon|evening)|howzit)\b/u', $s)) return 'greet';
    if (preg_match('/^(thanks|thank you|ok thanks|cool|great)\b/u', $s)) return 'thanks';
    if (preg_match('/^(help|what can you do|how do (i|you) use)/u', $s)) return 'help';
    if (preg_match('/\b(quiz me|test me|question me|ask me|practi[cs]e|flash ?cards?|revise|revision)\b/u', $s)) return 'quiz';
    if (preg_match('/\b(summar|overview|key points|recap|outline|main ideas)|what(\'s| is) (this|the) (topic|module) about/u', $s)) return 'summary';
    if (preg_match('/\bwhat(\'s| is)? next\b|where (do|should) i (start|go)|what should i (do|study|work on)|my progress|how am i doing/u', $s)) return 'next';
    return 'explain';
}

/* ---------------------------------------------------------------------------
 * The answer
 * ------------------------------------------------------------------------- */

/**
 * @param array{q:string, course:string, module:string, topic:string, uid:int, first:string} $ctx
 * @return array{intent:string, blocks:list<array<string,mixed>>, chips:list<string>, provider:string}
 */
function tutor_answer(array $ctx): array
{
    $intent = tutor_intent($ctx['q']);
    $reply = tutor_provider_reply($intent, $ctx);
    return $reply + ['intent' => $intent];
}

/** The seam for a model provider. Only 'static' exists today. */
function tutor_provider_reply(string $intent, array $ctx): array
{
    $provider = (string) (app_config_safe('tutor_provider') ?? 'static');
    // When a model is connected: retrieve with tutor_search() exactly as the
    // static tutor does, send those passages with the question, return the
    // same {blocks, chips} shape. Until then, every provider is static.
    return tutor_static_reply($intent, $ctx) + ['provider' => 'static'];
}

function tutor_source(array $p): array
{
    $q = $p['course'] === 'project-management' ? '' : '&c=' . rawurlencode($p['course']);
    return ['label' => $p['module'] . ' · ' . $p['title'],
            'href'  => 'module?m=' . rawurlencode($p['module']) . $q . '#' . rawurlencode($p['topic'])];
}

/** @return array{blocks:list<array<string,mixed>>, chips:list<string>} */
function tutor_static_reply(string $intent, array $ctx): array
{
    $slug = $ctx['course']; $mid = $ctx['module']; $tc = $ctx['topic'];
    $mods = curriculum_modules($slug);
    $here = $mid !== '' && isset($mods[$mid]) ? $mid . ' · ' . $mods[$mid]['title'] : '';
    $P = static fn(string $t): array => ['type' => 'p', 'text' => $t];

    switch ($intent) {
    case 'greet':
    case 'help':
        return ['blocks' => [
            $P(($intent === 'greet' ? 'Hello ' . $ctx['first'] . '. ' : '') . "I'm your study tutor for "
               . learner_course_title($slug) . ($here !== '' ? ", and I can see you're on $here." : '.')),
            ['type' => 'list', 'items' => [
                'Ask me about anything in your course, like "what is a project charter?"',
                'Say "summarise this topic" for the main points',
                'Say "quiz me" to practise explaining it yourself',
                'Ask "what should I do next?" and I\'ll look at your progress',
            ]],
            $P("I answer from your course material and show you where it came from. I won't give answers to your topic checks or workbooks, because those are your own work."),
        ], 'chips' => ['Summarise this topic', 'Quiz me', 'What should I do next?']];

    case 'thanks':
        return ['blocks' => [$P("You're welcome. Keep going. When you can explain it in your own words, you know it.")],
                'chips' => ['Quiz me', 'What should I do next?']];

    case 'assessment':
        return ['blocks' => [
            $P("I can't give you answers to your topic checks, workbooks or the final assessment. They count towards your qualification, so they have to be your own work."),
            $P("What I can do is help you understand the topic well enough to answer them yourself. Tell me which idea you're stuck on, or let me quiz you on it."),
        ], 'chips' => ['Summarise this topic', 'Quiz me']];

    case 'next':
        return tutor_reply_next($ctx);

    case 'summary':
        return tutor_reply_summary($slug, $mid, $tc);

    case 'quiz':
        return tutor_reply_quiz($slug, $mid, $tc, $ctx['q']);
    }

    /* explain */
    $hits = tutor_search($ctx['q'], tutor_passages($slug), $mid, $tc, 2);
    if (!$hits || $hits[0]['score'] < 1.2) {
        return ['blocks' => [
            $P("I couldn't find that in your " . learner_course_title($slug) . " material."),
            $P('Try different words, or the name of the idea as it appears in the topic. If it isn\'t part of this course, your facilitator is the best person to ask. Use "Contact the academy" in the menu.'),
        ], 'chips' => ['Summarise this topic', 'Help']];
    }
    /* Where no Reading is loaded the best match is only an outline. That says
       WHERE the idea is taught, not what it is, and the reply says so. */
    if ($hits[0]['p']['kind'] === 'outline') {
        $outl = tutor_outline($slug);
        $blocks = [$P('Your course covers this here:')];
        foreach ($hits as $i => $h) {
            if ($i > 0 && ($h['score'] < $hits[0]['score'] * 0.55 || $h['p']['kind'] !== 'outline')) break;
            $o = $outl[$h['p']['topic']] ?? ['covers' => [], 'key' => ''];
            $blocks[] = ['type' => 'topic', 'title' => $h['p']['module'] . ' · ' . $h['p']['title'], 'items' => $o['covers'],
                         'key' => $o['key'] !== '' ? mb_strimwidth($o['key'], 0, 320, '…') : '', 'source' => tutor_source($h['p'])];
        }
        $blocks[] = $P('Open the topic and your learner guide for the full explanation. The written Reading for this course is not on the site yet, so I can point you to the right place but not quote it.');
        $top = $hits[0]['p'];
        return ['blocks' => $blocks, 'chips' => ['Quiz me on ' . $top['title'], 'What should I do next?'],
                'focus' => ['module' => $top['module'], 'topic' => $top['topic']]];
    }
    $blocks = [$P('Here is what your course material says:')];
    foreach ($hits as $i => $h) {
        if ($i > 0 && $h['score'] < $hits[0]['score'] * 0.55) break;
        if ($h['p']['kind'] !== 'reading') continue;
        $blocks[] = ['type' => 'quote', 'text' => tutor_excerpt($h['p']['text'], $ctx['q']), 'source' => tutor_source($h['p'])];
    }
    /* Explain, then check: one question that makes them use it. */
    $top = $hits[0]['p'];
    $blocks[] = ['type' => 'check', 'text' => 'Check yourself: how would you explain this to a colleague in one or two sentences, using an example from your own work?'];
    return ['blocks' => $blocks, 'chips' => ['Quiz me on ' . $top['title'], 'Summarise this topic', 'What should I do next?'],
            'focus' => ['module' => $top['module'], 'topic' => $top['topic']]];
}

function tutor_reply_summary(string $slug, string $mid, string $tc): array
{
    $mods = curriculum_modules($slug); $out = tutor_outline($slug);
    if ($mid === '' || !isset($mods[$mid])) {
        $items = [];
        foreach ($mods as $id => $m) $items[] = $id . ' · ' . $m['title'];
        return ['blocks' => [['type' => 'p', 'text' => learner_course_title($slug) . ' has ' . count($mods) . ' modules:'],
                             ['type' => 'list', 'items' => $items],
                             ['type' => 'p', 'text' => 'Open a module and ask me again, and I\'ll summarise it topic by topic.']],
                'chips' => ['What should I do next?']];
    }
    $topics = $tc !== '' && isset($mods[$mid]['topics'][$tc]) ? [$tc => $mods[$mid]['topics'][$tc]] : $mods[$mid]['topics'];
    $blocks = [['type' => 'p', 'text' => ($tc !== '' && count($topics) === 1 ? 'The main points of this topic:' : $mid . ' · ' . $mods[$mid]['title'] . ', topic by topic:')]];
    foreach ($topics as $code => $name) {
        $o = $out[$code] ?? ['covers' => [], 'key' => ''];
        $blocks[] = ['type' => 'topic', 'title' => $name, 'items' => $o['covers'],
                     'key' => $o['key'] !== '' ? mb_strimwidth($o['key'], 0, 320, '…') : '',
                     'source' => tutor_source(['course' => $slug, 'module' => $mid, 'topic' => $code, 'title' => $name])];
    }
    return ['blocks' => $blocks, 'chips' => ['Quiz me', 'What should I do next?']];
}

/**
 * Retrieval practice: one point from the outline to explain unprompted, with
 * the material's own words held back until the learner asks to see them.
 */
function tutor_reply_quiz(string $slug, string $mid, string $tc, string $q): array
{
    $mods = curriculum_modules($slug); $out = tutor_outline($slug);
    /* "Quiz me on <topic name>": find that topic. Otherwise the topic in view, then the module, then the course. */
    $pool = [];
    $named = trim((string) preg_replace('/^.*?\b(quiz me|test me|practi[cs]e)\b( on)?/iu', '', $q));
    if ($named !== '') {
        foreach (tutor_search($named, array_values(array_filter(tutor_passages($slug), fn($p) => $p['kind'] === 'outline')), $mid, $tc, 1) as $h) {
            $tc = $h['p']['topic']; $mid = $h['p']['module'];
        }
    }
    foreach ($mods as $id => $m) {
        if ($mid !== '' && $id !== $mid) continue;
        foreach ($m['topics'] as $code => $name) {
            if ($tc !== '' && $code !== $tc) continue;
            foreach (($out[$code]['covers'] ?? []) as $item) $pool[] = [$id, $code, $name, $item];
        }
    }
    if (!$pool) return ['blocks' => [['type' => 'p', 'text' => "There's no outline for me to quiz you from here yet. Open a module and try again."]], 'chips' => ['Help']];
    [$id, $code, $name, $item] = $pool[random_int(0, count($pool) - 1)];

    $p = ['course' => $slug, 'module' => $id, 'topic' => $code, 'title' => $name];
    $hit = tutor_search($item, array_values(array_filter(tutor_passages($slug), fn($x) => $x['topic'] === $code && $x['kind'] === 'reading')), $id, $code, 1);
    $reveal = $hit ? tutor_excerpt($hit[0]['p']['text'], $item, 640)
                   : (($out[$code]['key'] ?? '') !== '' ? mb_strimwidth($out[$code]['key'], 0, 640, '…') : '');
    return ['blocks' => [
        ['type' => 'p', 'text' => 'From ' . $id . ' · ' . $name . '. Without looking it up, explain this in your own words:'],
        ['type' => 'practice', 'prompt' => $item,
         'reveal' => $reveal !== '' ? $reveal : 'Open the topic and compare what you said with the reading.',
         'source' => tutor_source($p)],
        ['type' => 'p', 'text' => 'Say it out loud or type it in the box below, then press "Show what the material says" and mark yourself.'],
    ], 'chips' => ['Another one', 'Summarise this topic', 'What should I do next?'],
       'focus' => ['module' => $id, 'topic' => $code]];
}

function tutor_reply_next(array $ctx): array
{
    if (!function_exists('journey_for')) require_once __DIR__ . '/journey.php';
    $j = journey_for($ctx['uid']);
    $n = journey_next($j);
    if ($n === null) {
        return ['blocks' => [['type' => 'p', 'text' => $j['courses']
            ? 'Every step on your path is done. Your facilitator will be in touch about the final assessment.'
            : "You're not on a course yet. Choose one on Home."]], 'chips' => []];
    }
    $blocks = [['type' => 'p', 'text' => 'Your next step on ' . $n['course'] . ':'],
               ['type' => 'next', 'title' => ($n['code'] === 'WM' ? $n['title'] : $n['code'] . ' · ' . $n['title']),
                'sub' => !empty($n['next']) ? 'Up next: ' . $n['next'] : (string) $n['sub'], 'href' => (string) $n['href']]];
    if ($j['streak'] > 0) $blocks[] = ['type' => 'p', 'text' => 'You\'re on a ' . $j['streak'] . '-day streak. Do one topic today to keep it.'];
    return ['blocks' => $blocks, 'chips' => ['Summarise this topic', 'Quiz me']];
}

/* ---------------------------------------------------------------------------
 * Limits
 * ------------------------------------------------------------------------- */

/** True while this session is under the hourly question limit (counts this one). */
function tutor_rate_ok(): bool
{
    app_session_start();
    $now = time();
    $log = array_values(array_filter((array) ($_SESSION['tutor_q'] ?? []), fn($t) => is_int($t) && $t > $now - 3600));
    if (count($log) >= TUTOR_RATE_HOUR) { $_SESSION['tutor_q'] = $log; return false; }
    $log[] = $now; $_SESSION['tutor_q'] = $log;
    return true;
}

/**
 * How much a passage reads as explanation rather than a list of headings, from
 * 0.35 to 1. Learner guides open many sections with their contents, and those
 * lines carry every keyword with no sentence around them.
 */
function tutor_prose(string $text): float
{
    $len = max(1, mb_strlen($text));
    $ends = preg_match_all('/[.!?](\s|$)/u', $text);
    $perSentence = $len / max(1, $ends);       // characters per sentence
    if ($perSentence <= 220) return 1.0;
    if ($perSentence >= 700) return 0.35;
    return round(1.0 - 0.65 * ($perSentence - 220) / 480, 2);
}
