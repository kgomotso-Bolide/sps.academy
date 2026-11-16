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
 *     evidence for the portfolio, and the EISA is external. The tutor never
 *     reads quiz_questions or quiz_choices, so it cannot leak a question or
 *     an answer even by accident. It reads only the learner's own SCORES
 *     (quiz_attempts), to find weak spots: tutor_weak_modules().
 *
 * WHAT MAKES IT SMARTER THAN A KEYWORD SEARCH (16 Nov 2026, second pass)
 *   - Abbreviations the course spells out ("Work Breakdown Structure (WBS)")
 *     are learnt from the material and searched both ways; a short synonym
 *     list; British/American spelling; misspellings read as the nearest word
 *     the course uses, and the learner is told ("I read X as Y").
 *   - "What is X?" leads with the sentence that defines X.
 *   - Follow-ups carry on the last question: "tell me more" (passages not yet
 *     shown), "give me an example", "simpler". The browser holds that state.
 *   - "Difference between A and B" puts the two side by side.
 *   - "Quiz me" does not repeat a point in the same session, starts on the
 *     learner's weakest module, and the learner's typed answer is marked in
 *     the browser against the key words of the material's own explanation.
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
    /* British and American spellings meet: organisation / organization. */
    $w = (string) preg_replace('/is(ation|ations|e|ed|es|ing)$/u', 'iz$1', $w);
    foreach (['ational' => 'ate', 'ization' => 'ize', 'ments' => 'ment', 'ness' => '', 'ings' => '', 'ing' => '', 'ies' => 'y',
              'ied' => 'y', 'ed' => '', 'es' => '', 's' => ''] as $suf => $rep) {
        if (mb_strlen($w) > mb_strlen($suf) + 3 && str_ends_with($w, $suf)) return mb_substr($w, 0, -mb_strlen($suf)) . $rep;
    }
    return $w;
}

/* Words that mean the same thing in these courses, as stems. Small on purpose:
   each pair is one a learner plausibly types for the word the guide uses. */
const TUTOR_SYN = [
    'timeline' => ['schedule'], 'deadline' => ['schedule'], 'budget' => ['cost'], 'cost' => ['budget'],
    'vendor' => ['supplier'], 'supplier' => ['vendor'], 'bid' => ['tender'], 'tender' => ['bid'],
    'startup' => ['venture'], 'company' => ['business', 'organization'], 'firm' => ['business'],
    'boss' => ['manager'], 'client' => ['customer'], 'customer' => ['client'], 'goal' => ['objective'],
    'objective' => ['goal'], 'problem' => ['issue', 'risk'], 'buy' => ['purchase', 'procure'],
    'purchase' => ['procure', 'buy'], 'stock' => ['inventory'], 'inventory' => ['stock'],
    'profit' => ['income'], 'money' => ['finance', 'capital'], 'staff' => ['employee', 'team'],
    'program' => ['programme'], 'programme' => ['program'],
];

/** The search index for one course, built once per request. */
function tutor_index(string $slug): array
{
    static $cache = [];
    if (isset($cache[$slug])) return $cache[$slug];
    $P = tutor_passages($slug);
    $docs = []; $dl = []; $df = []; $len = 0;
    foreach ($P as $i => $p) {
        $tf = array_count_values(tutor_terms($p['title'] . ' ' . $p['text']));
        $docs[$i] = $tf; $dl[$i] = array_sum($tf); $len += $dl[$i];
        foreach ($tf as $t => $_) $df[$t] = ($df[$t] ?? 0) + 1;
    }
    return $cache[$slug] = ['P' => $P, 'docs' => $docs, 'dl' => $dl, 'df' => $df,
                            'N' => max(1, count($P)), 'avg' => max(1, $len / max(1, count($P)))];
}

/**
 * Abbreviations the course itself spells out, e.g. "Work Breakdown Structure
 * (WBS)": abbreviation (lower case) => the words. Read from the material, so
 * each academy's own terms are learnt without a list to keep up.
 *
 * @return array<string,string>
 */
function tutor_acronyms(string $slug): array
{
    static $cache = [];
    if (isset($cache[$slug])) return $cache[$slug];
    $out = [];
    $skip = ['of', 'and', 'the', 'to', 'for', 'in', 'on', 'a', '&'];
    foreach (tutor_passages($slug) as $p) {
        if (!preg_match_all('/((?:[\p{L}][\p{L}\-]*\s+){1,7})\(([A-Z][A-Za-z&]{1,7})\)/u', $p['text'], $m, PREG_SET_ORDER)) continue;
        foreach ($m as $hit) {
            $letters = strtolower(preg_replace('/[^A-Z]/', '', $hit[2]) ?? '');
            if (strlen($letters) < 2 || isset($out[$letters])) continue;
            $words = array_values(array_filter(preg_split('/[\s\-]+/u', trim($hit[1])) ?: [],
                fn($w) => $w !== '' && !in_array(mb_strtolower($w), $skip, true)));
            $take = array_slice($words, -strlen($letters));
            $ini = implode('', array_map(fn($w) => mb_strtolower(mb_substr($w, 0, 1)), $take));
            if ($ini === $letters) $out[$letters] = implode(' ', $take);
        }
    }
    return $cache[$slug] = $out;
}

/**
 * The question's search terms, widened: abbreviations both ways, the synonym
 * pairs, and a misspelt word read as the nearest word the course uses.
 *
 * @return array{terms:list<string>, fixed:array<string,string>}
 */
function tutor_expand(string $slug, string $q): array
{
    static $memo = [];
    $mk = $slug . "\0" . $q;
    if (isset($memo[$mk])) return $memo[$mk];
    return $memo[$mk] = tutor_expand_uncached($slug, $q);
}

function tutor_expand_uncached(string $slug, string $q): array
{
    $idx = tutor_index($slug); $acr = tutor_acronyms($slug);
    $terms = tutor_terms($q); $fixed = [];
    preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($q), $raw);
    foreach ($raw[0] as $w) if (isset($acr[$w])) foreach (tutor_terms($acr[$w]) as $t) $terms[] = $t;
    foreach ($acr as $a => $phrase) {
        $pt = tutor_terms($phrase);
        if ($pt && !array_diff($pt, $terms)) $terms[] = tutor_stem($a);
    }
    foreach ($terms as $i => $t) {
        if (isset($idx['df'][$t]) || mb_strlen($t) < 5 || ctype_digit($t)) continue;
        $best = ''; $bestDf = 0; $lim = mb_strlen($t) >= 8 ? 2 : 1;
        foreach ($idx['df'] as $v => $n) {
            /* PHP turns numeric words ("2026") into integer keys. */
            if (!is_string($v) || $v[0] !== $t[0] || abs(strlen($v) - strlen($t)) > $lim || $n <= $bestDf) continue;
            if (levenshtein($t, $v) <= $lim) { $best = $v; $bestDf = $n; }
        }
        if ($best !== '') { $fixed[$t] = $best; $terms[$i] = $best; }
    }
    foreach ($terms as $t) foreach (TUTOR_SYN[$t] ?? [] as $s) $terms[] = tutor_stem($s);
    return ['terms' => array_values(array_unique($terms)), 'fixed' => $fixed];
}

/**
 * BM25 over one course, with a lift for the module and topic in view.
 *
 * $o: module, topic, limit (3), kind ('reading'|'outline'|''), only_topic,
 *     exclude (passage ids already shown), per_topic (true: one passage a topic)
 *
 * @return list<array{p:array<string,mixed>, score:float}> best first
 */
function tutor_find(string $slug, string $q, array $o = []): array
{
    $idx = tutor_index($slug);
    $ex = tutor_expand($slug, $q);
    $terms = $ex['terms'];
    if (!$terms) return [];
    $module = (string) ($o['module'] ?? ''); $topic = (string) ($o['topic'] ?? '');
    $kind = (string) ($o['kind'] ?? ''); $only = (string) ($o['only_topic'] ?? '');
    $exclude = array_flip(array_map('intval', (array) ($o['exclude'] ?? [])));
    $k1 = 1.4; $b = 0.75; $hits = [];
    foreach ($idx['docs'] as $i => $tf) {
        $p = $idx['P'][$i];
        if (isset($exclude[$i]) || ($kind !== '' && $p['kind'] !== $kind) || ($only !== '' && $p['topic'] !== $only)) continue;
        $s = 0.0; $matched = 0;
        foreach ($terms as $t) {
            if (!isset($tf[$t])) continue;
            $matched++;
            $df = $idx['df'][$t];
            $s += log(1 + ($idx['N'] - $df + 0.5) / ($df + 0.5))
                * ($tf[$t] * ($k1 + 1)) / ($tf[$t] + $k1 * (1 - $b + $b * $idx['dl'][$i] / $idx['avg']));
        }
        if ($s <= 0) continue;
        $s *= 0.6 + 0.4 * ($matched / count($terms));          // all the words, not one of them many times
        /* A run of headings from the guide matches every word and explains nothing. */
        $s *= (float) ($p['prose'] ?? 1.0);
        if ($topic !== '' && $p['topic'] === $topic) $s *= 1.5;
        elseif ($module !== '' && $p['module'] === $module) $s *= 1.25;
        $hits[] = ['p' => $p + ['id' => $i], 'score' => $s];
    }
    usort($hits, fn($a, $b) => $b['score'] <=> $a['score']);
    $limit = (int) ($o['limit'] ?? 3);
    if (($o['per_topic'] ?? true) === false) return array_slice($hits, 0, $limit);
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

/** Kept for the callers that pass a module and topic positionally. */
function tutor_search(string $q, string $slug, string $module = '', string $topic = '', int $limit = 3): array
{
    return tutor_find($slug, $q, ['module' => $module, 'topic' => $topic, 'limit' => $limit]);
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
    if (preg_match('/\b(weak|struggl|bad at|what (should|do) i (revise|go over)|where am i (going wrong|weak)|need (more )?practi[cs]e)/u', $s)) return 'weak';
    if (preg_match('/\b(quiz me|test me|question me|ask me|practi[cs]e|flash ?cards?|revise|revision)\b/u', $s)) return 'quiz';
    if (preg_match('/\b(summar|overview|key points|recap|outline|main ideas)|what(\'s| is) (this|the) (topic|module) about/u', $s)) return 'summary';
    if (preg_match('/\bwhat(\'s| is)? next\b|where (do|should) i (start|go)|what should i (do|study|work on)|my progress|how am i doing/u', $s)) return 'next';
    /* Follow-ups: they lean on the last question and the topic in focus. */
    if (preg_match('/^(an? )?(example|examples)\b|\b(give|show) me an? example|\bfor (example|instance)\??$|\bin practi[cs]e\??$|real[- ]life|real world/u', $s)) return 'example';
    if (preg_match('/\b(simpler|simple terms|simply|plain (english|words)|eli5|dumb it down|in short|i (still )?don\'?t (get|understand)|confus)/u', $s)) return 'simpler';
    if (preg_match('/^(tell me )?more\b|\b(tell me more|explain more|more detail|go on|continue|elaborate|expand on|go deeper|anything else)\b/u', $s)) return 'more';
    if (preg_match('/\b(difference|differences|differ)\b.*\b(between|from)\b|\bcompare\b|\b(vs\.?|versus)\b/u', $s)) return 'compare';
    return 'explain';
}

/* ---------------------------------------------------------------------------
 * The answer
 * ------------------------------------------------------------------------- */

/**
 * @param array{q:string, course:string, module:string, topic:string, uid:int, first:string,
 *              lastq?:string, seen?:list<int>, avoid?:list<int>} $ctx
 * @return array{intent:string, blocks:list<array<string,mixed>>, chips:list<string>, provider:string}
 */
function tutor_answer(array $ctx): array
{
    $ctx += ['lastq' => '', 'seen' => [], 'avoid' => []];
    /* "Summarise KM-04", "quiz me on SP1-02": a module named in the question wins. */
    $mods = curriculum_modules($ctx['course']);
    if (preg_match('/\b([A-Z]{1,3}\d?-\d{2})\b/i', $ctx['q'], $mm) && isset($mods[strtoupper($mm[1])])
        && strtoupper($mm[1]) !== $ctx['module']) {
        $ctx['module'] = strtoupper($mm[1]); $ctx['topic'] = '';
    }
    $intent = tutor_intent($ctx['q']);
    /* A follow-up with nothing to follow is just a question. */
    if (in_array($intent, ['more', 'example', 'simpler'], true) && $ctx['lastq'] === '' && $ctx['topic'] === '') $intent = 'explain';
    $reply = tutor_provider_reply($intent, $ctx);
    return $reply + ['intent' => $intent];
}

/** The seam for a model provider. Only 'static' exists today. */
function tutor_provider_reply(string $intent, array $ctx): array
{
    $provider = (string) (app_config_safe('tutor_provider') ?? 'static');
    // When a model is connected: retrieve with tutor_find() exactly as the
    // static tutor does, send those passages with the question (and lastq for
    // follow-ups), return the same {blocks, chips} shape. Until then, every
    // provider is static.
    return tutor_static_reply($intent, $ctx) + ['provider' => 'static'];
}

function tutor_source(array $p): array
{
    $q = $p['course'] === 'project-management' ? '' : '&c=' . rawurlencode($p['course']);
    return ['label' => $p['module'] . ' · ' . $p['title'],
            'href'  => 'module?m=' . rawurlencode($p['module']) . $q . '#' . rawurlencode($p['topic'])];
}

/** "what is a project charter?" -> "project charter". '' when it is not a what-is question. */
function tutor_subject(string $q): string
{
    $s = trim(mb_strtolower($q), " ?.!");
    if (!preg_match('/^(?:what(?:\'s| is| are| does| do)|define|meaning of|explain|tell me about|describe)\s+(?:an?\s+|the\s+)?(.+?)(?:\s+(?:mean|means|stand for|is))?$/u', $s, $m)) return '';
    $sub = trim(preg_replace('/^(?:an?|the)\s+/u', '', $m[1]) ?? '');
    return mb_strlen($sub) >= 2 && mb_strlen($sub) <= 60 ? $sub : '';
}

/**
 * The sentence in the material that defines $subject, if there is one:
 * "A project charter is ...", "WBS refers to ...".
 */
function tutor_definition(string $slug, string $subject, array $hits): ?array
{
    if ($subject === '') return null;
    $acr = tutor_acronyms($slug);
    $names = [$subject];
    if (isset($acr[$subject])) $names[] = $acr[$subject];
    foreach ($acr as $a => $phrase) if (mb_strtolower($phrase) === $subject) $names[] = $a;
    $alt = implode('|', array_map(fn($n) => preg_quote($n, '/'), $names)) . '|' . preg_quote(str_replace('program', 'programme', $subject), '/');
    $verb = '(?:is|are|refers to|means|can be defined as|is defined as|describes|is known as)\s+[^.]{12,}';
    /* Best: the sentence STARTS with the term ("A project charter is ...").
       Next best: the term anywhere, then the defining verb. */
    $start = '/^\s*(?:an?\s+|the\s+)?(?:' . $alt . ')s?\b(?:\s*\([^)]{1,12}\))?\s+' . $verb . '/iu';
    $any   = '/(?:^|[\s(])(?:an?\s+|the\s+)?(?:' . $alt . ')s?\b(?:\s*\([^)]{1,12}\))?\s+' . $verb . '/iu';
    /* The best few passages first, then the rest of the course. */
    $pool = array_map(fn($h) => $h['p'], $hits);
    foreach (tutor_find($slug, $subject, ['kind' => 'reading', 'limit' => 10, 'per_topic' => false]) as $h) $pool[] = $h['p'];
    $fallback = null;
    foreach ($pool as $p) {
        if (($p['kind'] ?? '') !== 'reading') continue;
        foreach (preg_split('/(?<=[.!?])\s+|\n/u', $p['text']) ?: [] as $sent) {
            $sent = trim($sent);
            if (mb_strlen($sent) < 25 || mb_strlen($sent) > 420) continue;
            /* "Work Breakdown Structure (WBS) A work ... is": drop a heading glued on the front. */
            $clean = (string) preg_replace('/^(?:[\p{Lu}][\p{L}\-]*\s*(?:\([A-Z]{2,7}\)\s*)?){1,6}(?=\b(?:An?|The)\s)/u', '', $sent);
            if (preg_match($start, $clean)) return ['text' => $clean, 'p' => $p];
            if ($fallback === null && preg_match($any, ' ' . $clean) && !preg_match('/characteristic|another|one of/iu', $clean)) $fallback = ['text' => $clean, 'p' => $p];
        }
    }
    return $fallback;
}

/** Sentences in a set of passages that give an example. */
function tutor_examples(array $passages, string $q, int $max = 2): array
{
    $terms = tutor_terms($q); $found = [];
    foreach ($passages as $p) {
        $sents = preg_split('/(?<=[.!?])\s+|\n/u', $p['text']) ?: [];
        foreach ($sents as $i => $s) {
            if (!preg_match('/\b(for example|for instance|e\.g\.|such as|an example|example of|consider|imagine|suppose|case study)\b/iu', $s)) continue;
            $txt = trim($s);
            /* "For example:" or "such as:" ending the line: the example is what follows. */
            for ($k = 1; $k <= 3 && (mb_strlen($txt) < 90 || str_ends_with($txt, ':')) && isset($sents[$i + $k]); $k++) $txt .= ' ' . trim($sents[$i + $k]);
            if (mb_strlen($txt) < 80 || str_ends_with($txt, ':')) continue;
            $score = count(array_intersect($terms, tutor_terms($txt))) + 0.5;
            $found[] = ['text' => mb_strimwidth($txt, 0, 480, '…'), 'p' => $p, 'score' => $score];
        }
    }
    usort($found, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($found, 0, $max);
}

/** The words a good answer to a quiz prompt would use: [shown word, stem]. */
function tutor_keywords(string $slug, string $text, string $prompt, int $max = 7): array
{
    $idx = tutor_index($slug);
    $idf = static fn(string $st): float => log(1 + $idx['N'] / ($idx['df'][$st] ?? 1));
    /* "Risk identification tools (Document reviews, Checklist analysis, SWOT
       analysis, ...)": when the point lists its parts, the parts ARE the key
       ideas. Each is matched on its most telling word. */
    if (preg_match('/\((.{6,})\)\s*\)?\s*$/su', $prompt, $pm)) {
        $flat = str_replace(['(', ')'], ',', $pm[1]);
        $out = []; $seen = [];
        foreach (preg_split('/\s*,\s*|\s+and\s+/u', $flat) ?: [] as $item) {
            $item = trim($item);
            if ($item === '' || mb_strlen($item) > 60) continue;
            $best = ''; $bi = -1.0;
            foreach (tutor_terms($item) as $st) if ($idf($st) > $bi) { $bi = $idf($st); $best = $st; }
            if ($best === '' || isset($seen[$best])) continue;
            $seen[$best] = true; $out[] = [mb_strtolower($item), $best];
            if (count($out) >= 8) break;
        }
        if (count($out) >= 2) return $out;
    }
    /* From the material's explanation only, and not the question's own words:
       repeating "risk" back is not knowing the answer. */
    $inPrompt = array_flip(tutor_terms($prompt));
    $filler = ['explained', 'runs', 'relies', 'method', 'thought', 'source', 'application', 'concepts', 'concept', 'within',
               'should', 'which', 'these', 'those', 'their', 'being', 'other', 'makes', 'using', 'often', 'order', 'various'];
    preg_match_all('/[\p{L}][\p{L}\-]{4,}/u', $text, $m);
    $best = [];
    foreach ($m[0] as $w) {
        $lw = mb_strtolower($w);
        if (in_array($lw, TUTOR_STOP, true) || in_array($lw, $filler, true) || str_contains($lw, '-')) continue;
        $st = tutor_stem($lw);
        if (isset($inPrompt[$st]) || !isset($idx['df'][$st])) continue;
        if (isset($best[$st])) { $best[$st]['n']++; continue; }
        $df = $idx['df'][$st] ?? 1;
        $best[$st] = ['w' => $lw, 'n' => 1, 'idf' => log(1 + $idx['N'] / $df)];
    }
    /* Common words (in most passages) say nothing about this point. */
    $best = array_filter($best, fn($b) => $b['idf'] > 1.1);
    uasort($best, fn($a, $b) => ($b['n'] * $b['idf']) <=> ($a['n'] * $a['idf']));
    $out = [];
    foreach (array_slice($best, 0, $max, true) as $st => $b) $out[] = [$b['w'], $st];
    return $out;
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
                'Follow up with "give me an example", "simpler" or "tell me more"',
                'Ask me to compare two ideas, like "difference between a project and operations"',
                'Say "quiz me" to practise, and I\'ll mark what you write',
                'Ask "where am I weak?" or "what should I do next?"',
            ]],
            $P("I answer from your course material and show you where it came from. I won't give answers to your topic checks or workbooks, because those are your own work."),
        ], 'chips' => ['Summarise this topic', 'Quiz me', 'Where am I weak?']];

    case 'thanks':
        return ['blocks' => [$P("You're welcome. Keep going. When you can explain it in your own words, you know it.")],
                'chips' => ['Quiz me', 'What should I do next?']];

    case 'assessment':
        return ['blocks' => [
            $P("I can't give you answers to your topic checks, workbooks or the final assessment. They count towards your qualification, so they have to be your own work."),
            $P("What I can do is help you understand the topic well enough to answer them yourself. Tell me which idea you're stuck on, or let me quiz you on it."),
        ], 'chips' => ['Summarise this topic', 'Quiz me']];

    case 'next':    return tutor_reply_next($ctx);
    case 'weak':    return tutor_reply_weak($ctx);
    case 'summary': return tutor_reply_summary($slug, $mid, $tc);
    case 'quiz':    return tutor_reply_quiz($ctx);
    case 'compare': return tutor_reply_compare($ctx);
    case 'more':
    case 'example':
    case 'simpler': return tutor_reply_follow($intent, $ctx);
    }
    return tutor_reply_explain($ctx);
}

function tutor_not_found(string $slug): array
{
    return ['blocks' => [
        ['type' => 'p', 'text' => "I couldn't find that in your " . learner_course_title($slug) . " material."],
        ['type' => 'p', 'text' => 'Try different words, or the name of the idea as it appears in the topic. If it isn\'t part of this course, your facilitator is the best person to ask. Use "Contact the academy" in the menu.'],
    ], 'chips' => ['Summarise this topic', 'Help']];
}

/** "(I read “riks” as “risk”.)" when a word was corrected. */
function tutor_fixed_note(string $slug, string $q): ?array
{
    $fx = tutor_expand($slug, $q)['fixed'];
    if (!$fx) return null;
    $bits = [];
    foreach ($fx as $from => $to) $bits[] = '"' . $from . '" as "' . $to . '"';
    return ['type' => 'note', 'text' => 'I read ' . implode(', ', $bits) . '.'];
}

function tutor_reply_explain(array $ctx): array
{
    $slug = $ctx['course']; $mid = $ctx['module']; $tc = $ctx['topic']; $q = $ctx['q'];
    $P = static fn(string $t): array => ['type' => 'p', 'text' => $t];
    /* "Why is it important?": too few words of its own, so it borrows the last question's. */
    $own = tutor_terms($q);
    $search = count($own) < 2 && $ctx['lastq'] !== '' ? $q . ' ' . $ctx['lastq'] : $q;
    $hits = tutor_find($slug, $search, ['module' => $mid, 'topic' => $tc, 'limit' => 3]);
    if (!$hits || $hits[0]['score'] < 1.2) return tutor_not_found($slug);
    /* An outline tops the list easily (it is short and all keywords). Where the
       Reading also answers it reasonably well, the Reading is the better answer. */
    if ($hits[0]['p']['kind'] === 'outline') {
        $read = tutor_find($slug, $search, ['module' => $mid, 'topic' => $tc, 'limit' => 3, 'kind' => 'reading']);
        if ($read && $read[0]['score'] >= $hits[0]['score'] * 0.45) $hits = $read;
    }

    /* Where no Reading is loaded the best match is only an outline. That says
       WHERE the idea is taught, not what it is, and the reply says so. */
    if ($hits[0]['p']['kind'] === 'outline') {
        $outl = tutor_outline($slug);
        $blocks = array_values(array_filter([tutor_fixed_note($slug, $q), $P('Your course covers this here:')]));
        foreach ($hits as $i => $h) {
            if ($i > 1 || ($i > 0 && ($h['score'] < $hits[0]['score'] * 0.55 || $h['p']['kind'] !== 'outline'))) break;
            $o = $outl[$h['p']['topic']] ?? ['covers' => [], 'key' => ''];
            $blocks[] = ['type' => 'topic', 'title' => $h['p']['module'] . ' · ' . $h['p']['title'], 'items' => $o['covers'],
                         'key' => $o['key'] !== '' ? mb_strimwidth($o['key'], 0, 320, '…') : '', 'source' => tutor_source($h['p'])];
        }
        $blocks[] = $P('Open the topic and your learner guide for the full explanation. The written Reading for this course is not on the site yet, so I can point you to the right place but not quote it.');
        $top = $hits[0]['p'];
        return ['blocks' => $blocks, 'chips' => ['Quiz me on ' . $top['title'], 'What should I do next?'],
                'focus' => ['module' => $top['module'], 'topic' => $top['topic']], 'lastq' => $search];
    }

    $blocks = array_values(array_filter([tutor_fixed_note($slug, $q)]));
    $shown = [];
    /* A what-is question gets the definition first, in the course's own words. */
    $def = tutor_definition($slug, tutor_subject($q), $hits);
    if ($def) {
        $blocks[] = ['type' => 'define', 'text' => $def['text'], 'source' => tutor_source($def['p'])];
        $blocks[] = $P('More from your material:');
    } else {
        $blocks[] = $P('Here is what your course material says:');
    }
    $n = 0;
    foreach ($hits as $i => $h) {
        if ($i > 0 && $h['score'] < $hits[0]['score'] * 0.55) break;
        if ($h['p']['kind'] !== 'reading' || $n >= 2) continue;
        $text = $h['p']['text'];
        /* Not the definition twice: quote what the passage says after it. */
        if ($def && str_contains($text, $def['text'])) $text = trim(str_replace($def['text'], '', $text));
        if (mb_strlen($text) < 60) continue;
        $ex = tutor_excerpt($text, $search);
        if ($def && str_contains($ex, mb_substr($def['text'], 0, 50))) continue;
        $blocks[] = ['type' => 'quote', 'text' => $ex, 'source' => tutor_source($h['p'])];
        $shown[] = $h['p']['id']; $n++;
    }
    $top = $hits[0]['p'];
    /* Explain, then check: one question that makes them use it. */
    $blocks[] = ['type' => 'check', 'text' => 'Check yourself: how would you explain this to a colleague in one or two sentences, using an example from your own work?'];
    return ['blocks' => $blocks, 'chips' => ['Give me an example', 'Simpler', 'Tell me more', 'Quiz me on ' . $top['title']],
            'focus' => ['module' => $top['module'], 'topic' => $top['topic']], 'shown' => $shown, 'lastq' => $search];
}

/** "Tell me more", "give me an example", "simpler": the last question, carried on. */
function tutor_reply_follow(string $intent, array $ctx): array
{
    $slug = $ctx['course']; $tc = $ctx['topic']; $mid = $ctx['module'];
    $mods = curriculum_modules($slug);
    if ($tc !== '' && $mid === '') $mid = curriculum_module_of($tc, $slug);
    $title = $tc !== '' ? (string) ($mods[$mid]['topics'][$tc] ?? '') : '';
    $about = trim($ctx['lastq'] !== '' ? $ctx['lastq'] : $title);
    $label = $title !== '' ? $title : $about;
    $P = static fn(string $t): array => ['type' => 'p', 'text' => $t];

    if ($intent === 'example') {
        $pool = array_map(fn($h) => $h['p'], tutor_find($slug, $about, ['kind' => 'reading', 'only_topic' => $tc, 'limit' => 12, 'per_topic' => false]));
        if (!$pool) $pool = array_map(fn($h) => $h['p'], tutor_find($slug, $about, ['kind' => 'reading', 'module' => $mid, 'limit' => 12, 'per_topic' => false]));
        $ex = tutor_examples($pool, $about);
        if (!$ex) {
            return ['blocks' => [$P("Your material doesn't give a worked example for this one."),
                    ['type' => 'check', 'text' => 'Try making your own: think of a project or task at work this year. Where would "' . $label . '" show up in it, and what would it look like?']],
                    'chips' => ['Simpler', 'Quiz me']];
        }
        $blocks = [$P('An example from your material:')];
        foreach ($ex as $e) $blocks[] = ['type' => 'quote', 'text' => $e['text'], 'source' => tutor_source($e['p'])];
        $blocks[] = ['type' => 'check', 'text' => 'Now one of your own: where have you seen this at work?'];
        return ['blocks' => $blocks, 'chips' => ['Tell me more', 'Quiz me'], 'lastq' => $about];
    }

    if ($intent === 'simpler') {
        $def = tutor_definition($slug, tutor_subject('what is ' . preg_replace('/^(what(\'s| is| are)|define|explain)\s+/iu', '', $about)), []);
        $outl = tutor_outline($slug)[$tc] ?? ['covers' => [], 'key' => ''];
        $blocks = [];
        if ($def) $blocks[] = ['type' => 'define', 'text' => $def['text'], 'source' => tutor_source($def['p'])];
        if ($outl['key'] !== '') $blocks[] = ['type' => 'quote', 'text' => 'The key idea of this topic: ' . mb_strimwidth($outl['key'], 0, 300, '…'),
                                              'source' => tutor_source(['course' => $slug, 'module' => $mid, 'topic' => $tc, 'title' => $title])];
        if ($outl['covers']) $blocks[] = ['type' => 'list', 'items' => array_slice($outl['covers'], 0, 4)];
        if (!$blocks) return tutor_reply_explain(['q' => $about] + $ctx);
        array_unshift($blocks, $P('In short:'));
        $blocks[] = ['type' => 'check', 'text' => 'Which part is still unclear? Ask me about just that word or step.'];
        return ['blocks' => $blocks, 'chips' => ['Give me an example', 'Quiz me'], 'lastq' => $about];
    }

    /* more */
    $hits = tutor_find($slug, $about, ['kind' => 'reading', 'topic' => $tc, 'module' => $mid, 'exclude' => $ctx['seen'], 'limit' => 2, 'per_topic' => false]);
    if (!$hits || $hits[0]['score'] < 1.0) {
        return ['blocks' => [$P("That's everything your material says about it that I haven't shown you."),
                $P('The best next step is to test yourself on it.')], 'chips' => ['Quiz me', 'Give me an example']];
    }
    $blocks = [$P('More from your material:')]; $shown = [];
    foreach ($hits as $h) {
        $blocks[] = ['type' => 'quote', 'text' => tutor_excerpt($h['p']['text'], $about, 700), 'source' => tutor_source($h['p'])];
        $shown[] = $h['p']['id'];
    }
    return ['blocks' => $blocks, 'chips' => ['Tell me more', 'Give me an example', 'Quiz me'], 'shown' => $shown, 'lastq' => $about,
            'focus' => ['module' => $hits[0]['p']['module'], 'topic' => $hits[0]['p']['topic']]];
}

/** "Difference between A and B": the two side by side, then a question. */
function tutor_reply_compare(array $ctx): array
{
    $slug = $ctx['course'];
    $s = trim(mb_strtolower($ctx['q']), ' ?.');
    $a = $b = '';
    if (preg_match('/(?:difference|differences)\s+between\s+(?:an?\s+|the\s+)?(.+?)\s+(?:and|&|vs\.?|versus)\s+(?:an?\s+|the\s+)?(.+)$/u', $s, $m)
        || preg_match('/compare\s+(?:an?\s+|the\s+)?(.+?)\s+(?:and|with|to|vs\.?|versus)\s+(?:an?\s+|the\s+)?(.+)$/u', $s, $m)
        || preg_match('/^(?:an?\s+|the\s+)?(.+?)\s+(?:vs\.?|versus)\s+(?:an?\s+|the\s+)?(.+)$/u', $s, $m)
        || preg_match('/how (?:does|do|is|are)\s+(?:an?\s+|the\s+)?(.+?)\s+differ(?:ent)? from\s+(?:an?\s+|the\s+)?(.+)$/u', $s, $m)) {
        $a = trim($m[1]); $b = trim($m[2]);
    }
    if ($a === '' || $b === '') return tutor_reply_explain($ctx);
    $side = function (string $x) use ($slug, $ctx): ?array {
        $hits = tutor_find($slug, $x, ['module' => $ctx['module'], 'kind' => 'reading', 'limit' => 3]);
        $def = tutor_definition($slug, $x, $hits);
        if ($def) return ['name' => $x, 'text' => $def['text'], 'source' => tutor_source($def['p'])];
        if (!$hits || $hits[0]['score'] < 1.2) return null;
        return ['name' => $x, 'text' => tutor_excerpt($hits[0]['p']['text'], $x, 380), 'source' => tutor_source($hits[0]['p'])];
    };
    $L = $side($a); $R = $side($b);
    if (!$L && !$R) return tutor_not_found($slug);
    $blocks = [['type' => 'p', 'text' => 'Side by side, from your material:']];
    $blocks[] = ['type' => 'compare', 'items' => array_values(array_filter([$L, $R]))];
    if (!$L || !$R) $blocks[] = ['type' => 'p', 'text' => 'I could only find "' . ($L ? $b : $a) . '" by its own name in a few places. Try the exact term your guide uses.'];
    $blocks[] = ['type' => 'check', 'text' => 'In one sentence: what is the main difference, and when would you use each?'];
    return ['blocks' => $blocks, 'chips' => ['Give me an example', 'Quiz me'], 'lastq' => $a . ' ' . $b];
}

function tutor_reply_summary(string $slug, string $mid, string $tc): array
{
    $mods = curriculum_modules($slug); $out = tutor_outline($slug);
    if ($mid === '' && $tc !== '') $mid = curriculum_module_of($tc, $slug);
    if ($mid === '' || !isset($mods[$mid])) {
        $items = [];
        foreach ($mods as $id => $m) $items[] = $id . ' · ' . $m['title'];
        return ['blocks' => [['type' => 'p', 'text' => learner_course_title($slug) . ' has ' . count($mods) . ' modules:'],
                             ['type' => 'list', 'items' => $items],
                             ['type' => 'p', 'text' => 'Choose a module above (or open one) and ask me again, and I\'ll summarise it topic by topic.']],
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
 * Modules worth going back to: a topic check below its pass mark (best try),
 * or started and left part-way. Scores only; the questions are never read.
 *
 * @return list<array{module:string, title:string, why:string, pct:?int}>
 */
function tutor_weak_modules(int $uid, string $slug): array
{
    $mods = curriculum_modules($slug); $out = [];
    $rows = db_optional(fn() => db_all('SELECT q.module_code, q.pass_pct, MAX(a.score_count * 100 / CASE WHEN a.question_count > 0 THEN a.question_count ELSE 1 END) AS best
              FROM quiz_attempts a JOIN quizzes q ON q.id = a.quiz_id
             WHERE a.tenant_id = ? AND a.user_id = ? AND q.course_slug = ? AND a.submitted_at IS NOT NULL
             GROUP BY q.module_code, q.pass_pct', [tenant_id(), $uid, $slug]), []) ?: [];
    foreach ($rows as $r) {
        $m = (string) $r['module_code']; $pass = (int) ($r['pass_pct'] ?? 80) ?: 80; $best = (int) $r['best'];
        if (isset($mods[$m]) && $best < $pass) $out[$m] = ['module' => $m, 'title' => $mods[$m]['title'], 'pct' => $best,
                                                           'why' => 'Your best topic check: ' . $best . '% (pass mark ' . $pass . '%)'];
    }
    $tree = db_optional(fn() => learner_progress_tree($uid, $slug), []) ?: [];
    foreach ($mods as $id => $m) {
        if (isset($out[$id])) continue;
        $total = count($m['topics']); $done = 0;
        foreach (array_keys($m['topics']) as $code) if (!empty($tree[$id]['topics'][$code])) $done++;
        if ($done > 0 && $done < $total) $out[$id] = ['module' => $id, 'title' => $m['title'], 'pct' => null,
                                                    'why' => $done . ' of ' . $total . ' topics done'];
    }
    usort($out, fn($a, $b) => ($a['pct'] ?? 101) <=> ($b['pct'] ?? 101));
    return array_values($out);
}

function tutor_reply_weak(array $ctx): array
{
    $slug = $ctx['course'];
    $weak = tutor_weak_modules($ctx['uid'], $slug);
    if (!$weak) {
        return ['blocks' => [['type' => 'p', 'text' => "Nothing stands out. You haven't missed a topic check, and every module you've started is finished."],
                ['type' => 'p', 'text' => 'Keep it that way by testing yourself on a module before you move on.']],
                'chips' => ['Quiz me', 'What should I do next?']];
    }
    $q = $slug === 'project-management' ? '' : '&c=' . rawurlencode($slug);
    $blocks = [['type' => 'p', 'text' => 'Worth going back to:']];
    foreach (array_slice($weak, 0, 4) as $w) {
        $blocks[] = ['type' => 'next', 'title' => $w['module'] . ' · ' . $w['title'], 'sub' => $w['why'],
                     'href' => 'module?m=' . rawurlencode($w['module']) . $q];
    }
    $blocks[] = ['type' => 'p', 'text' => 'I can quiz you on the first one now. A few rounds of explaining it in your own words is the fastest way to fix a weak spot.'];
    return ['blocks' => $blocks, 'chips' => ['Quiz me on ' . $weak[0]['module'], 'Summarise ' . $weak[0]['module']],
            'focus' => ['module' => $weak[0]['module'], 'topic' => '']];
}

/**
 * Retrieval practice: one point from the outline to explain unprompted, with
 * the material's own words held back until the learner asks to see them. The
 * reveal carries the words a good answer would use, so tutor.js can mark the
 * learner's attempt in the browser. Their answer is never sent here.
 */
function tutor_reply_quiz(array $ctx): array
{
    $slug = $ctx['course']; $mid = $ctx['module']; $tc = $ctx['topic']; $q = $ctx['q'];
    $mods = curriculum_modules($slug); $out = tutor_outline($slug);
    $named = trim((string) preg_replace('/^.*?\b(quiz me|test me|practi[cs]e)\b( on)?/iu', '', $q));
    if ($named !== '' && preg_match('/^[A-Z]{1,3}\d?-?\d{2}$/i', $named) && isset($mods[strtoupper($named)])) {
        $mid = strtoupper($named); $tc = '';                         // "quiz me on KM-04"
    } elseif ($named !== '') {
        foreach (tutor_find($slug, $named, ['kind' => 'outline', 'module' => $mid, 'limit' => 1]) as $h) {
            $tc = $h['p']['topic']; $mid = $h['p']['module'];
        }
    } elseif ($mid === '' && $tc === '') {
        /* Nothing chosen: the weakest module first. */
        $weak = tutor_weak_modules($ctx['uid'], $slug);
        if ($weak) $mid = $weak[0]['module'];
    }
    $pool = [];
    $avoid = array_flip(array_map('intval', $ctx['avoid']));
    foreach ($mods as $id => $m) {
        if ($mid !== '' && $id !== $mid) continue;
        foreach ($m['topics'] as $code => $name) {
            if ($tc !== '' && $code !== $tc) continue;
            foreach (($out[$code]['covers'] ?? []) as $item) {
                $qid = crc32($code . '|' . $item) & 0x7fffffff;
                if (!isset($avoid[$qid])) $pool[] = [$id, $code, $name, $item, $qid];
            }
        }
    }
    if (!$pool && $avoid) {
        return ['blocks' => [['type' => 'p', 'text' => "You've been through every point here. Well done."],
                ['type' => 'p', 'text' => 'Choose another module, or start the round again tomorrow. Spacing it out helps it stick.']],
                'chips' => ['Where am I weak?', 'What should I do next?'], 'reset_avoid' => true];
    }
    if (!$pool) return ['blocks' => [['type' => 'p', 'text' => "There's no outline for me to quiz you from here yet. Choose a module and try again."]], 'chips' => ['Help']];
    [$id, $code, $name, $item, $qid] = $pool[random_int(0, count($pool) - 1)];

    $p = ['course' => $slug, 'module' => $id, 'topic' => $code, 'title' => $name];
    $hit = tutor_find($slug, $item, ['kind' => 'reading', 'only_topic' => $code, 'limit' => 1]);
    $reveal = $hit ? tutor_excerpt($hit[0]['p']['text'], $item, 640)
                   : (($out[$code]['key'] ?? '') !== '' ? mb_strimwidth($out[$code]['key'], 0, 640, '…') : '');
    $left = count($pool) - 1;
    return ['blocks' => [
        ['type' => 'p', 'text' => 'From ' . $id . ' · ' . $name . '. Without looking it up, explain this in your own words:'],
        ['type' => 'practice', 'prompt' => $item,
         'reveal' => $reveal !== '' ? $reveal : 'Open the topic and compare what you said with the reading.',
         'keys' => $reveal !== '' ? tutor_keywords($slug, $reveal, $item) : [],
         'source' => tutor_source($p)],
        ['type' => 'p', 'text' => 'Type your answer in the box, then press "Check my answer". I\'ll show what the material says and which key ideas you covered.'
                                 . ($left > 0 ? ' (' . $left . ' more point' . ($left === 1 ? '' : 's') . ' in this round.)' : '')],
    ], 'chips' => ['Another one', 'Summarise this topic', 'Where am I weak?'],
       'focus' => ['module' => $id, 'topic' => $tc !== '' ? $code : ''], 'asked' => $qid];
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
    $weak = tutor_weak_modules($ctx['uid'], $ctx['course']);
    if ($weak && $weak[0]['pct'] !== null) $blocks[] = ['type' => 'p', 'text' => 'Also worth a look: ' . $weak[0]['module'] . ', where your best topic check is ' . $weak[0]['pct'] . '%.'];
    return ['blocks' => $blocks, 'chips' => ['Summarise this topic', 'Quiz me', 'Where am I weak?']];
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
