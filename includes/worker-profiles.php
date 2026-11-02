<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Calculates a numeric timeline score for a project period string (newest period = higher score).
 */
function gig_parse_period_score(string $period): int
{
    $p = strtolower(trim($period));
    if ($p === '') {
        return 0;
    }

    $monthMap = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
        'mei' => 5, 'may' => 5, 'jun' => 6, 'jul' => 7,
        'agu' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10,
        'oct' => 10, 'nov' => 11, 'des' => 12, 'dec' => 12
    ];

    // Check for ongoing / present / sekarang / berjalan
    if (preg_match('/(sekarang|present|berjalan|ongoing|current)/i', $p)) {
        preg_match_all('/\b(19\d\d|20\d\d)\b/', $p, $matches);
        $startYear = !empty($matches[1]) ? (int)end($matches[1]) : (int)date('Y');
        
        $startMonth = 0;
        foreach ($monthMap as $mStr => $mNum) {
            if (str_contains($p, $mStr)) {
                $startMonth = max($startMonth, $mNum);
            }
        }
        return 90000000 + ($startYear * 100) + $startMonth;
    }

    preg_match_all('/\b(19\d\d|20\d\d)\b/', $p, $matches);
    if (empty($matches[1])) {
        return 100000;
    }

    $years = array_map('intval', $matches[1]);
    $endYear = max($years);
    $startYear = min($years);

    $endMonth = 0;
    foreach ($monthMap as $mStr => $mNum) {
        if (str_contains($p, $mStr)) {
            $endMonth = max($endMonth, $mNum);
        }
    }

    return ($endYear * 10000) + ($endMonth * 100) + ($startYear % 100);
}

/**
 * Sorts an array of experience/project items chronologically by period (newest project first).
 */
function gig_sort_experience_timeline(array &$experience): void
{
    usort($experience, function ($a, $b) {
        $periodA = (string)($a['period'] ?? '');
        $periodB = (string)($b['period'] ?? '');
        $scoreA = gig_parse_period_score($periodA);
        $scoreB = gig_parse_period_score($periodB);
        return $scoreB <=> $scoreA;
    });
}

/**
 * Standard expertise levels for Gig Worker skills.
 *
 * @return list<string>
 */
function gig_skill_levels(): array
{
    return ['Pemula', 'Menengah', 'Mahir', 'Ahli'];
}

/**
 * Normalizes skill entries into a uniform list of ['name' => string, 'level' => string].
 * Supports JSON string, comma-separated string, array of strings, or array of associative arrays.
 *
 * @param mixed $skills
 * @return list<array{name: string, level: string}>
 */
function gig_normalize_skills(mixed $skills): array
{
    if ($skills === null || $skills === false || $skills === '') {
        return [];
    }

    if (is_string($skills)) {
        $trimmed = trim($skills);
        if ($trimmed === '') {
            return [];
        }
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            $skills = $decoded;
        } else {
            $skills = array_filter(array_map('trim', explode(',', $trimmed)));
        }
    }

    if (!is_array($skills)) {
        return [];
    }

    $validLevels = ['Pemula', 'Menengah', 'Mahir', 'Ahli'];
    $normalized = [];

    foreach ($skills as $item) {
        $name = '';
        $level = 'Menengah';

        if (is_array($item)) {
            $name = trim((string)($item['name'] ?? $item['skill'] ?? ''));
            $lvlInput = trim((string)($item['level'] ?? $item['expertise'] ?? ''));
            if ($lvlInput !== '') {
                foreach ($validLevels as $vl) {
                    if (strcasecmp($vl, $lvlInput) === 0) {
                        $level = $vl;
                        break;
                    }
                }
            }
        } elseif (is_string($item)) {
            $str = trim($item);
            if (preg_match('/^(.*?)\s*[\(\[]\s*(Pemula|Menengah|Mahir|Ahli|Beginner|Intermediate|Advanced|Expert)\s*[\)\]]$/i', $str, $matches)) {
                $name = trim($matches[1]);
                $matchedLvl = strtolower($matches[2]);
                if (in_array($matchedLvl, ['pemula', 'beginner'], true)) {
                    $level = 'Pemula';
                } elseif (in_array($matchedLvl, ['menengah', 'intermediate'], true)) {
                    $level = 'Menengah';
                } elseif (in_array($matchedLvl, ['mahir', 'advanced'], true)) {
                    $level = 'Mahir';
                } elseif (in_array($matchedLvl, ['ahli', 'expert'], true)) {
                    $level = 'Ahli';
                }
            } else {
                $name = $str;
                $level = 'Menengah';
            }
        }

        if ($name !== '') {
            $normalized[] = [
                'name'  => $name,
                'level' => $level,
            ];
        }
    }

    return $normalized;
}

/**
 * Returns the name of a skill item.
 *
 * @param mixed $skill
 * @return string
 */
function gig_skill_name(mixed $skill): string
{
    if (is_array($skill)) {
        return trim((string)($skill['name'] ?? $skill['skill'] ?? ''));
    }
    if (is_string($skill)) {
        if (preg_match('/^(.*?)\s*[\(\[]\s*(Pemula|Menengah|Mahir|Ahli)\s*[\)\]]$/i', trim($skill), $m)) {
            return trim($m[1]);
        }
        return trim($skill);
    }
    return '';
}

/**
 * Returns the expertise level of a skill item.
 *
 * @param mixed $skill
 * @return string
 */
function gig_skill_level(mixed $skill): string
{
    if (is_array($skill)) {
        $lvl = trim((string)($skill['level'] ?? $skill['expertise'] ?? 'Menengah'));
        return $lvl !== '' ? $lvl : 'Menengah';
    }
    if (is_string($skill)) {
        if (preg_match('/[\(\[]\s*(Pemula|Menengah|Mahir|Ahli)\s*[\)\]]$/i', trim($skill), $m)) {
            return ucfirst(strtolower($m[1]));
        }
    }
    return 'Menengah';
}

/**
 * Returns color tokens for an expertise level badge.
 *
 * @param string $level
 * @return array{bg: string, color: string, border: string}
 */
function gig_skill_level_badge_style(string $level): array
{
    return match (strtolower(trim($level))) {
        'pemula', 'beginner' => [
            'bg'     => '#f1f5f9',
            'color'  => '#475569',
            'border' => '#cbd5e1',
        ],
        'mahir', 'advanced' => [
            'bg'     => '#ecfdf5',
            'color'  => '#047857',
            'border' => '#a7f3d0',
        ],
        'ahli', 'expert' => [
            'bg'     => '#faf5ff',
            'color'  => '#7e22ce',
            'border' => '#e9d5ff',
        ],
        default => [
            'bg'     => '#eff6ff',
            'color'  => '#1d4ed8',
            'border' => '#bfdbfe',
        ],
    };
}

/**
 * Formats a list of skills into a readable summary string (e.g. "Figma (Mahir), React (Menengah)").
 *
 * @param mixed $skills
 * @return string
 */
function gig_format_skills_summary(mixed $skills): string
{
    $normalized = gig_normalize_skills($skills);
    if ($normalized === []) {
        return '-';
    }
    $parts = [];
    foreach ($normalized as $item) {
        $parts[] = $item['name'] . ' (' . $item['level'] . ')';
    }
    return implode(', ', $parts);
}

/**
 * Loads default seed Gig Worker account profiles from data/worker-profiles.json.
 */
function gig_worker_default_profiles(): array
{
    $jsonPath = __DIR__ . '/../data/worker-profiles.json';
    if (file_exists($jsonPath)) {
        $json = file_get_contents($jsonPath);
        $decoded = json_decode($json, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }
    }
    return [];
}

/**
 * Ensures worker_profiles table exists in MySQL and seeds default profiles if empty.
 */
function gig_worker_profiles_ensure_table(?PDO $pdo = null): void
{
    $pdo = $pdo ?? (function_exists('gig_db') ? gig_db() : null);
    if ($pdo === null) {
        return;
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `worker_profiles` (
                `id`                 VARCHAR(50)  NOT NULL PRIMARY KEY,
                `name`               VARCHAR(150) NOT NULL,
                `initials`           VARCHAR(10)  NOT NULL DEFAULT '',
                `color`              VARCHAR(30)  NOT NULL DEFAULT '#2563eb',
                `photo`              VARCHAR(500) NOT NULL DEFAULT '',
                `title`              VARCHAR(150) NOT NULL DEFAULT '',
                `location`           VARCHAR(100) NOT NULL DEFAULT '',
                `rating`             DECIMAL(3,1) NOT NULL DEFAULT 5.0,
                `reviews_count`      INT UNSIGNED NOT NULL DEFAULT 0,
                `completed_projects` INT UNSIGNED NOT NULL DEFAULT 0,
                `total_projects`     INT UNSIGNED NOT NULL DEFAULT 0,
                `verified`           TINYINT(1)   NOT NULL DEFAULT 1,
                `agreed`             TINYINT(1)   NOT NULL DEFAULT 0,
                `category`           VARCHAR(50)  NOT NULL DEFAULT '',
                `applied_project`    VARCHAR(255) NOT NULL DEFAULT '',
                `bid`                VARCHAR(50)  NOT NULL DEFAULT '',
                `eta`                VARCHAR(50)  NOT NULL DEFAULT '',
                `proposal`           TEXT,
                `skills`             LONGTEXT,
                `social_media`       LONGTEXT,
                `video_url`          VARCHAR(500) NOT NULL DEFAULT '',
                `contact`            LONGTEXT,
                `experience`         LONGTEXT,
                `portfolio`          LONGTEXT,
                `reviews`            LONGTEXT,
                `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $count = (int)$pdo->query("SELECT COUNT(*) FROM `worker_profiles`")->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare("
                INSERT INTO `worker_profiles`
                (`id`, `name`, `initials`, `color`, `photo`, `title`, `location`, `rating`, `reviews_count`, `completed_projects`, `total_projects`, `verified`, `agreed`, `category`, `applied_project`, `bid`, `eta`, `proposal`, `skills`, `social_media`, `video_url`, `contact`, `experience`, `portfolio`, `reviews`)
                VALUES
                (:id, :name, :initials, :color, :photo, :title, :location, :rating, :reviews_count, :completed_projects, :total_projects, :verified, :agreed, :category, :applied_project, :bid, :eta, :proposal, :skills, :social_media, :video_url, :contact, :experience, :portfolio, :reviews)
                ON DUPLICATE KEY UPDATE
                  `name` = VALUES(`name`),
                  `initials` = VALUES(`initials`),
                  `color` = VALUES(`color`),
                  `photo` = VALUES(`photo`),
                  `title` = VALUES(`title`),
                  `location` = VALUES(`location`),
                  `rating` = VALUES(`rating`),
                  `reviews_count` = VALUES(`reviews_count`),
                  `completed_projects` = VALUES(`completed_projects`),
                  `total_projects` = VALUES(`total_projects`),
                  `verified` = VALUES(`verified`),
                  `agreed` = VALUES(`agreed`),
                  `category` = VALUES(`category`),
                  `applied_project` = VALUES(`applied_project`),
                  `bid` = VALUES(`bid`),
                  `eta` = VALUES(`eta`),
                  `proposal` = VALUES(`proposal`),
                  `skills` = VALUES(`skills`),
                  `social_media` = VALUES(`social_media`),
                  `video_url` = VALUES(`video_url`),
                  `contact` = VALUES(`contact`),
                  `experience` = VALUES(`experience`),
                  `portfolio` = VALUES(`portfolio`),
                  `reviews` = VALUES(`reviews`)
            ");

            foreach (gig_worker_default_profiles() as $wid => $p) {
                $stmt->execute([
                    ':id' => $wid,
                    ':name' => $p['name'] ?? '',
                    ':initials' => $p['initials'] ?? '',
                    ':color' => $p['color'] ?? '#2563eb',
                    ':photo' => $p['photo'] ?? '',
                    ':title' => $p['title'] ?? '',
                    ':location' => $p['location'] ?? '',
                    ':rating' => (float)($p['rating'] ?? 5.0),
                    ':reviews_count' => (int)($p['reviews_count'] ?? 0),
                    ':completed_projects' => (int)($p['completed_projects'] ?? 0),
                    ':total_projects' => (int)($p['total_projects'] ?? 0),
                    ':verified' => !empty($p['verified']) ? 1 : 0,
                    ':agreed' => !empty($p['agreed']) ? 1 : 0,
                    ':category' => $p['category'] ?? '',
                    ':applied_project' => $p['applied_project'] ?? '',
                    ':bid' => $p['bid'] ?? '',
                    ':eta' => $p['eta'] ?? '',
                    ':proposal' => $p['proposal'] ?? '',
                    ':skills' => json_encode($p['skills'] ?? [], JSON_UNESCAPED_UNICODE),
                    ':social_media' => json_encode($p['social_media'] ?? [], JSON_UNESCAPED_UNICODE),
                    ':video_url' => $p['video_url'] ?? '',
                    ':contact' => json_encode($p['contact'] ?? [], JSON_UNESCAPED_UNICODE),
                    ':experience' => json_encode($p['experience'] ?? [], JSON_UNESCAPED_UNICODE),
                    ':portfolio' => json_encode($p['portfolio'] ?? [], JSON_UNESCAPED_UNICODE),
                    ':reviews' => json_encode($p['reviews'] ?? [], JSON_UNESCAPED_UNICODE),
                ]);
            }
        }
    } catch (Throwable $e) {}
}

/**
 * Updates a worker profile in the worker_profiles MySQL table.
 */
function gig_save_worker_profile(string $id, array $data, ?PDO $pdo = null): bool
{
    $pdo = $pdo ?? (function_exists('gig_db') ? gig_db() : null);
    if ($pdo === null) {
        return false;
    }

    try {
        gig_worker_profiles_ensure_table($pdo);
        $fields = [
            'name', 'initials', 'color', 'photo', 'title', 'location',
            'rating', 'reviews_count', 'completed_projects', 'total_projects',
            'verified', 'agreed', 'category', 'applied_project', 'bid', 'eta',
            'proposal', 'skills', 'social_media', 'video_url', 'contact',
            'experience', 'portfolio', 'reviews'
        ];
        $updates = [];
        $params = [':id' => $id];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $val = $data[$f];
                if (is_array($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                } elseif (is_bool($val)) {
                    $val = $val ? 1 : 0;
                }
                $updates[] = "`$f` = :$f";
                $params[":$f"] = $val;
            }
        }
        if (empty($updates)) {
            return false;
        }
        $sql = "UPDATE `worker_profiles` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Shared Gig Worker account profiles.
 * Loaded from the worker_profiles MySQL database table (with memory fallback).
 */
function gig_worker_profiles(): array
{
    if (!function_exists('gig_worker_project_stats')) {
        require_once __DIR__ . '/project-history.php';
    }

    $profiles = [];

    // Load from MySQL worker_profiles table if available
    if (function_exists('gig_db')) {
        $pdo = gig_db();
        if ($pdo !== null) {
            try {
                gig_worker_profiles_ensure_table($pdo);
                $stmt = $pdo->query("SELECT * FROM `worker_profiles` ORDER BY `id` ASC");
                $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($dbRows)) {
                    foreach ($dbRows as $row) {
                        $wid = (string)$row['id'];
                        $profiles[$wid] = [
                            'id' => $wid,
                            'name' => (string)$row['name'],
                            'initials' => (string)$row['initials'],
                            'color' => (string)$row['color'],
                            'photo' => (string)$row['photo'],
                            'title' => (string)$row['title'],
                            'location' => (string)$row['location'],
                            'rating' => (float)$row['rating'],
                            'reviews_count' => (int)$row['reviews_count'],
                            'completed_projects' => (int)$row['completed_projects'],
                            'total_projects' => (int)$row['total_projects'],
                            'verified' => !empty($row['verified']),
                            'agreed' => !empty($row['agreed']),
                            'category' => (string)$row['category'],
                            'applied_project' => (string)$row['applied_project'],
                            'bid' => (string)$row['bid'],
                            'eta' => (string)$row['eta'],
                            'proposal' => (string)($row['proposal'] ?? ''),
                            'skills' => json_decode((string)($row['skills'] ?? ''), true) ?: [],
                            'social_media' => json_decode((string)($row['social_media'] ?? ''), true) ?: [],
                            'video_url' => (string)($row['video_url'] ?? ''),
                            'contact' => json_decode((string)($row['contact'] ?? ''), true) ?: [],
                            'experience' => json_decode((string)($row['experience'] ?? ''), true) ?: [],
                            'portfolio' => json_decode((string)($row['portfolio'] ?? ''), true) ?: [],
                            'reviews' => json_decode((string)($row['reviews'] ?? ''), true) ?: [],
                        ];
                    }
                }
            } catch (Throwable $e) {
                $profiles = [];
            }
        }
    }

    if (empty($profiles)) {
        $profiles = gig_worker_default_profiles();
    }
    // db.php is conditionally included here so worker-profiles.php can work
    // standalone without requiring a DB connection in every consumer.
    if (function_exists('gig_db')) {
        $pdo = gig_db();
        if ($pdo !== null) {
            try {
                // Fetch all reviews stored by any employer for any worker
                $stmt = $pdo->query(
                    "SELECT `worker_id`, `employer_username`, `project_title`,
                            `overall_rating`, `comment`, `badges`, `created_at`
                     FROM `project_reviews`
                     ORDER BY `created_at` DESC"
                );
                $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Group by worker_id and track contracts already merged to avoid
                // double-inserting if both DB and session have the same review.
                $mergedContracts = [];

                foreach ($dbRows as $row) {
                    $wId = function_exists('gig_profile_key_for_worker_id')
                        ? gig_profile_key_for_worker_id((string)$row['worker_id'])
                        : (string)$row['worker_id'];
                    if (!isset($profiles[$wId])) continue;

                    $projectTitle = (string)$row['project_title'];
                    $alreadyIn = false;
                    foreach ($profiles[$wId]['reviews'] as $existing) {
                        if (strcasecmp((string)($existing['project'] ?? ''), $projectTitle) === 0
                            && strcasecmp((string)($existing['employer'] ?? ''), (string)$row['employer_username']) === 0
                        ) {
                            $alreadyIn = true;
                            break;
                        }
                    }
                    if ($alreadyIn) {
                        continue;
                    }

                    $contractKey = $row['employer_username'] . '||' . $projectTitle;
                    if (isset($mergedContracts[$wId][$contractKey])) continue;
                    $mergedContracts[$wId][$contractKey] = true;

                    $badgesArr = ($row['badges'] !== '')
                        ? explode('||', $row['badges'])
                        : [];

                    $reviewDate = date('M Y', strtotime($row['created_at']));

                    array_unshift($profiles[$wId]['reviews'], [
                        'employer' => $row['employer_username'],
                        'project'  => $projectTitle,
                        'rating'   => (int)$row['overall_rating'],
                        'date'     => $reviewDate,
                        'comment'  => $row['comment'],
                        'badges'   => $badgesArr,
                    ]);
                }

                foreach (array_keys($profiles) as $wId) {
                    $allRatings = array_column($profiles[$wId]['reviews'], 'rating');
                    $cnt = count($allRatings);
                    $profiles[$wId]['reviews_count'] = $cnt;
                    $profiles[$wId]['rating'] = $cnt > 0
                        ? round(array_sum($allRatings) / $cnt, 1)
                        : (float)($profiles[$wId]['rating'] ?? 5.0);
                }
            } catch (Throwable $e) {
                // DB error – fall through to session fallback
            }
        }
    }

    // ── Session fallback (used when DB is offline) ───────────────────────────
    if (session_status() === PHP_SESSION_ACTIVE
        && isset($_SESSION['custom_reviews'])
        && is_array($_SESSION['custom_reviews'])
    ) {
        foreach ($_SESSION['custom_reviews'] as $wId => $revList) {
            if (!isset($profiles[$wId]) || !is_array($revList)) continue;
            foreach ($revList as $r) {
                // Avoid duplicating if already merged from DB
                $contractKey = ($r['employer'] ?? '') . '||' . ($r['project'] ?? '');
                // Simple check: see if any existing review matches
                $alreadyIn = false;
                foreach ($profiles[$wId]['reviews'] as $existing) {
                    if (($existing['project'] ?? '') === ($r['project'] ?? '')
                        && ($existing['employer'] ?? '') === ($r['employer'] ?? '')
                    ) {
                        $alreadyIn = true;
                        break;
                    }
                }
                if ($alreadyIn) continue;

                array_unshift($profiles[$wId]['reviews'], $r);
            }
            $allRatings = array_column($profiles[$wId]['reviews'], 'rating');
            $cnt = count($allRatings);
            $profiles[$wId]['reviews_count'] = $cnt;
            $profiles[$wId]['rating'] = $cnt > 0
                ? round(array_sum($allRatings) / $cnt, 1)
                : 5.0;
        }
    }

    // ── Final normalisation ───────────────────────────────────────────────────
    foreach (array_keys($profiles) as $wId) {
        if (!empty($profiles[$wId]['experience']) && is_array($profiles[$wId]['experience'])) {
            gig_sort_experience_timeline($profiles[$wId]['experience']);
        }

        $profiles[$wId]['reviews_count'] = count($profiles[$wId]['reviews'] ?? []);

        $stats = gig_worker_project_stats($wId);
        $profiles[$wId]['completed_projects'] = $stats['completed_projects'];
        $profiles[$wId]['total_projects'] = $stats['total_projects'];
    }

    return $profiles;
}

function gig_find_worker(string $id): ?array
{
    $profiles = gig_worker_profiles();
    $cleanId = strtolower(trim($id));

    if ($cleanId === 'theressaz@pasker.id' || $cleanId === 'theressaz' || str_contains($cleanId, 'theressa') || $cleanId === 'tessa') {
        $cleanId = 'tessa';
    }

    if (!isset($profiles[$cleanId])) {
        foreach ($profiles as $k => $p) {
            if (strcasecmp((string)($p['name'] ?? ''), $id) === 0 || strcasecmp((string)($p['contact']['email'] ?? ''), $id) === 0) {
                $cleanId = $k;
                break;
            }
        }
    }

    if (isset($profiles[$cleanId])) {
        // If custom session profile exists, merge it
        if (!empty($_SESSION['worker_custom_profile']) && is_array($_SESSION['worker_custom_profile'])) {
            $custom = $_SESSION['worker_custom_profile'];
            if (!empty($custom['name'])) $profiles[$cleanId]['name'] = $custom['name'];
            if (!empty($custom['title'])) $profiles[$cleanId]['title'] = $custom['title'];
            if (!empty($custom['location'])) $profiles[$cleanId]['location'] = $custom['location'];
            if (!empty($custom['skills'])) $profiles[$cleanId]['skills'] = gig_normalize_skills($custom['skills']);
            if (!empty($custom['proposal'])) $profiles[$cleanId]['proposal'] = $custom['proposal'];
            if (!empty($custom['video_url'])) $profiles[$cleanId]['video_url'] = $custom['video_url'];
            if (!empty($custom['contact'])) {
                if (!empty($custom['contact']['email'])) $profiles[$cleanId]['contact']['email'] = $custom['contact']['email'];
                if (!empty($custom['contact']['wa'])) $profiles[$cleanId]['contact']['wa'] = $custom['contact']['wa'];
            }
        }

        // If registration data exists for this user, merge registered data
        $reg = gig_get_worker_registration($cleanId);
        if ($reg !== null && $cleanId !== 'tessa') {
            if (!empty($reg['display_name'])) {
                $profiles[$cleanId]['name'] = $reg['display_name'];
            }
            if (!empty($reg['bidang_keahlian'])) {
                $profiles[$cleanId]['title'] = $reg['bidang_keahlian'];
            }
            if (!empty($reg['domicile'])) {
                $profiles[$cleanId]['location'] = $reg['domicile'];
            }
            if (!empty($reg['skills'])) {
                $profiles[$cleanId]['skills'] = gig_normalize_skills($reg['skills']);
            }
            if (!empty($reg['profile_summary'])) {
                $profiles[$cleanId]['proposal'] = $reg['profile_summary'];
            }
            if (!empty($reg['contact_email']) || !empty($reg['contact_wa'])) {
                $profiles[$cleanId]['contact']['email'] = !empty($reg['contact_email']) ? $reg['contact_email'] : $profiles[$cleanId]['contact']['email'];
                $profiles[$cleanId]['contact']['wa'] = !empty($reg['contact_wa']) ? $reg['contact_wa'] : $profiles[$cleanId]['contact']['wa'];
            }
            if (!empty($reg['portfolio']) && is_array($reg['portfolio'])) {
                $profiles[$cleanId]['portfolio'] = array_merge($reg['portfolio'], $profiles[$cleanId]['portfolio'] ?? []);
            }
            if (!empty($reg['previous_projects']) && is_array($reg['previous_projects'])) {
                $profiles[$cleanId]['experience'] = array_merge($reg['previous_projects'], $profiles[$cleanId]['experience'] ?? []);
            }
            if (!empty($reg['video_url'])) {
                $profiles[$cleanId]['video_url'] = $reg['video_url'];
            }
            if (!empty($reg['social_media']) && is_array($reg['social_media'])) {
                $profiles[$cleanId]['social_media'] = $reg['social_media'];
            }
        }
        if (!empty($profiles[$cleanId]['experience']) && is_array($profiles[$cleanId]['experience'])) {
            gig_sort_experience_timeline($profiles[$cleanId]['experience']);
        }
        if ($cleanId !== 'tessa') {
            $stats = gig_worker_project_stats($cleanId);
            $profiles[$cleanId]['completed_projects'] = $stats['completed_projects'];
            $profiles[$cleanId]['total_projects'] = $stats['total_projects'];
        }
        if (!function_exists('gig_avatar_url_for_worker_id')) {
            require_once __DIR__ . '/user-avatars.php';
        }
        $savedAvatar = gig_avatar_url_for_worker_id($cleanId);
        if ($savedAvatar !== null) {
            $profiles[$cleanId]['photo'] = $savedAvatar;
        } elseif (
            session_status() === PHP_SESSION_ACTIVE
            && !empty($_SESSION['siapkerja_email'])
            && gig_avatar_worker_profile_id(gig_avatar_account_key()) === $cleanId
        ) {
            $profiles[$cleanId]['photo'] = gig_worker_display_photo($cleanId);
        }
        return $profiles[$cleanId];
    }

    // Check if there is a registration for a user not in preset array
    $reg = gig_get_worker_registration($id);
    if ($reg !== null || gig_is_worker_registered($id)) {
        $siapkerja = gig_get_siapkerja_profile($id);
        $reg = $reg ?? [];
        $exp = $reg['previous_projects'] ?? $siapkerja['pengalaman_siapkerja'];
        if (is_array($exp)) {
            gig_sort_experience_timeline($exp);
        }
        $stats = gig_worker_project_stats($cleanId);
        return [
            'id' => $cleanId,
            'name' => $reg['display_name'] ?? ($siapkerja['nama'] ?? ucwords($id)),
            'initials' => strtoupper(substr($id, 0, 2)),
            'color' => '#2563eb',
            'photo' => 'https://api.dicebear.com/9.x/notionists/svg?seed=' . urlencode($id) . '&backgroundColor=dbeafe',
            'title' => $reg['bidang_keahlian'] ?? 'Gig Worker Professional',
            'location' => $reg['domicile'] ?? ($siapkerja['lokasi'] ?? 'Jakarta, Indonesia'),
            'rating' => 5.0,
            'reviews_count' => 5,
            'completed_projects' => $stats['completed_projects'],
            'total_projects' => $stats['total_projects'],
            'verified' => true,
            'agreed' => true,
            'category' => 'general',
            'skills' => gig_normalize_skills($reg['skills'] ?? ['Figma', 'Web Development']),
            'contact' => [
                'wa' => $reg['contact_wa'] ?? $siapkerja['wa'],
                'email' => $reg['contact_email'] ?? $siapkerja['email'],
            ],
            'experience' => $exp,
            'portfolio' => $reg['portfolio'] ?? [],
            'reviews' => [],
            'video_url' => $reg['video_url'] ?? '',
            'social_media' => $reg['social_media'] ?? [],
            'proposal' => $reg['profile_summary'] ?? '',
        ];
    }

    return null;
}

function gig_stars(int|float $rating): string
{
    $intRating = (int) round((float)$rating);
    if ($intRating < 1) $intRating = 1;
    if ($intRating > 5) $intRating = 5;

    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= ($i <= $intRating) ? '★' : '☆';
    }
    return $html;
}

/**
 * Returns pre-filled profile information from the user's SIAPKerja account.
 */
function gig_get_siapkerja_profile(string $username): array
{
    $lower = strtolower(trim($username));
    if ($lower === 'pencaker' || $lower === 'pencaker@pasker.id') {
        $fullName = 'Pencaker';
        $userEmail = 'pencaker@pasker.id';
    } elseif ($lower === 'pemberi kerja' || $lower === 'calon.employer@pasker.id' || str_contains($lower, 'calon.employer')) {
        $fullName = 'Pemberi Kerja';
        $userEmail = 'calon.employer@pasker.id';
    } elseif ($lower === 'tessa' || $lower === 'theressaz@pasker.id' || str_contains($lower, 'theressa')) {
        $fullName = 'Theressa Zaratrusha';
        $userEmail = 'theressaz@pasker.id';
    } else {
        $fullName = ucwords(trim($username));
        $userEmail = strtolower(str_replace(' ', '.', $fullName)) . '@siapkerja.kemnaker.go.id';
    }

    $skExp = [
        [
            'role'        => 'UI/UX Designer & Digital Strategist',
            'institution' => 'PT Teknologi Digital Indonesia (SIAPKerja Verified)',
            'period'      => 'Jan 2024 — Agu 2026',
            'summary'     => 'Merancang desain antarmuka dashboard B2B SaaS dan memimpin pengujian ketergunaan bagi 500+ pengguna aktif.'
        ],
        [
            'role'        => 'Frontend & Product Specialist',
            'institution' => 'Proyek Digital KarirHub Kemnaker RI',
            'period'      => 'Mei 2025 — Des 2025',
            'summary'     => 'Mengembangkan komponen antarmuka web responsif dan standar aksesibilitas bagi calon tenaga kerja Indonesia.'
        ]
    ];
    gig_sort_experience_timeline($skExp);

    return [
        'username'        => $username,
        'nama'            => $fullName,
        'siapkerja_id'    => 'SK-2026-' . strtoupper(substr(md5($username), 0, 6)),
        'nik'             => '317409' . sprintf('%010d', abs(crc32($username) % 10000000000)),
        'email'           => $userEmail,
        'wa'              => '0812-3456-7890',
        'lokasi'          => 'Jakarta Selatan, DKI Jakarta',
        'status_akun'     => 'Terverifikasi (KYC Kemnaker RI)',
        'pengalaman_siapkerja' => $skExp
    ];
}

/**
 * Checks whether the logged in user has completed Gig Worker registration.
 */
function gig_is_worker_registered(string $username): bool
{
    $clean = strtolower(trim($username));
    if (in_array($clean, ['tessa', 'theressaz@pasker.id', 'theressaz', 'theressa zaratrusha'], true) || str_contains($clean, 'theressa')) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['gig_worker_registered_' . $username] = true;
        }
        return true;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $email = strtolower(trim((string)($_SESSION['siapkerja_email'] ?? '')));
        if (in_array($email, ['tessa', 'theressaz@pasker.id', 'theressaz', 'theressa zaratrusha'], true) || str_contains($email, 'theressa')) {
            $_SESSION['gig_worker_registered_' . $username] = true;
            return true;
        }
        if (($_SESSION['role'] ?? '') === 'worker') {
            $_SESSION['gig_worker_registered_' . $username] = true;
            return true;
        }
        if (!empty($_SESSION['gig_worker_registered_' . $username])) {
            return true;
        }
    }

    $db = gig_db();
    if ($db !== null) {
        try {
            $stmt = $db->prepare("SELECT `status` FROM `gig_worker_registrations` WHERE `username` = :u OR `contact_email` = :e LIMIT 1");
            $stmt->execute([':u' => $username, ':e' => $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && (string)($row['status'] ?? 'approved') === 'approved') {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION['gig_worker_registered_' . $username] = true;
                }
                return true;
            }
        } catch (Throwable $e) {
            // fallback check session
        }
    }

    return false;
}

/**
 * Gets registration details for a Gig Worker.
 */
function gig_get_worker_registration(string $username): ?array
{
    $db = gig_db();
    if ($db !== null) {
        try {
            $stmt = $db->prepare("SELECT * FROM `gig_worker_registrations` WHERE `username` = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['previous_projects'] = json_decode((string)$row['previous_projects'], true) ?: [];
                $row['portfolio'] = json_decode((string)$row['portfolio'], true) ?: [];
                $row['social_media'] = json_decode((string)($row['social_media'] ?? ''), true) ?: [];
                $rawSkills = $row['skills'] ?? '';
                $decodedSkills = json_decode((string)$rawSkills, true);
                $row['skills'] = is_array($decodedSkills) ? gig_normalize_skills($decodedSkills) : gig_normalize_skills($rawSkills);
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION['gig_worker_registration_data_' . $username] = $row;
                    if ((string)($row['status'] ?? 'approved') === 'approved') {
                        $_SESSION['gig_worker_registered_' . $username] = true;
                    }
                }
                return $row;
            }
        } catch (Throwable $e) {
            // fallback
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['gig_worker_registration_data_' . $username])) {
        $row = $_SESSION['gig_worker_registration_data_' . $username];
        if (!is_array($row)) {
            return null;
        }
        if (!is_array($row['social_media'] ?? null)) {
            $row['social_media'] = json_decode((string)($row['social_media'] ?? ''), true) ?: [];
        }
        if (!empty($row['skills'])) {
            $row['skills'] = gig_normalize_skills($row['skills']);
        }
        return $row;
    }

    return null;
}

/**
 * Saves a new Gig Worker registration.
 */
function gig_save_worker_registration(string $username, array $data): bool
{
    require_once __DIR__ . '/admin-store.php';
    gig_admin_ensure_schema();

    $db = gig_db();
    if ($db === null) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $data['skills'] = gig_normalize_skills($data['skills'] ?? []);
            $_SESSION['gig_worker_registration_data_' . $username] = $data;
            if ((string)($data['status'] ?? 'pending') === 'approved') {
                $_SESSION['gig_worker_registered_' . $username] = true;
            }
            return true;
        }
        return false;
    }

    try {
        $status = (string)($data['status'] ?? 'pending');
        $preserveNote = '';
        if ($status === 'approved') {
            $existing = gig_get_worker_registration($username);
            $preserveNote = (string)($existing['admin_note'] ?? '');
        }

        $stmt = $db->prepare("
            INSERT INTO `gig_worker_registrations`
            (`username`, `bidang_keahlian`, `skills`, `contact_choice`, `contact_email`, `contact_wa`,
             `previous_projects`, `portfolio`, `video_url`, `social_media`, `display_name`, `domicile`, `profile_summary`, `status`, `admin_note`)
            VALUES (:u, :bidang, :skills, :choice, :email, :wa, :projects, :portfolio, :video, :social_media, :display_name, :domicile, :profile_summary, :status, :note)
            ON DUPLICATE KEY UPDATE
              `bidang_keahlian` = VALUES(`bidang_keahlian`),
              `skills` = VALUES(`skills`),
              `contact_choice` = VALUES(`contact_choice`),
              `contact_email` = VALUES(`contact_email`),
              `contact_wa` = VALUES(`contact_wa`),
              `previous_projects` = VALUES(`previous_projects`),
              `portfolio` = VALUES(`portfolio`),
              `video_url` = VALUES(`video_url`),
              `social_media` = VALUES(`social_media`),
              `display_name` = VALUES(`display_name`),
              `domicile` = VALUES(`domicile`),
              `profile_summary` = VALUES(`profile_summary`),
              `status` = VALUES(`status`),
              `admin_note` = IF(VALUES(`status`) = 'pending', '', `admin_note`)
        ");
        $stmt->execute([
            ':u'        => $username,
            ':bidang'   => $data['bidang_keahlian'] ?? '',
            ':skills'   => json_encode(gig_normalize_skills($data['skills'] ?? [])),
            ':choice'   => $data['contact_choice'] ?? 'siapkerja',
            ':email'    => $data['contact_email'] ?? '',
            ':wa'       => $data['contact_wa'] ?? '',
            ':projects' => json_encode($data['previous_projects'] ?? []),
            ':portfolio'=> json_encode($data['portfolio'] ?? []),
            ':video'    => $data['video_url'] ?? '',
            ':social_media' => json_encode($data['social_media'] ?? []),
            ':display_name' => $data['display_name'] ?? '',
            ':domicile' => $data['domicile'] ?? '',
            ':profile_summary' => $data['profile_summary'] ?? '',
            ':status'   => $status,
            ':note'     => $status === 'approved' ? $preserveNote : '',
        ]);
    } catch (Throwable $e) {
        return false;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['gig_worker_registration_data_' . $username], $_SESSION['gig_worker_registered_' . $username]);
    }
    return true;
}

/**
 * Compiles an exhaustive search payload for a worker profile covering EVERYTHING:
 * Name, title, category, location, bio/proposal, all skills & levels, all experiences,
 * output deliverables, all portfolios, clients, deliverable files, all employer reviews,
 * comments, badges, social media links, video profile demos, contact details, and SIAPKerja background.
 *
 * @param array $worker
 * @return array{full_text: string, facets: array<string, mixed>}
 */
function gig_worker_search_metadata(array $worker): array
{
    $wId = strtolower(trim((string)($worker['id'] ?? '')));
    $reg = function_exists('gig_get_worker_registration') ? (gig_get_worker_registration($wId) ?? []) : [];
    $siap = function_exists('gig_get_siapkerja_profile') ? gig_get_siapkerja_profile($wId) : [];

    // Category mapping
    $cat = strtolower((string)($worker['category'] ?? ''));
    $catLabels = [
        'ui-ux' => 'UI/UX & Desain Grafis Frontend Prototype Wireframe',
        'backend' => 'Backend & API Cloud Database Developer Microservices REST Server',
        'marketing' => 'Digital Marketing Pemasaran Konten Copywriting Media Sosial Campaign',
    ];
    $catLabel = $catLabels[$cat] ?? 'Freelancer';

    // Skills
    $rawSkills = !empty($worker['skills']) ? $worker['skills'] : ($reg['skills'] ?? []);
    $skillsList = function_exists('gig_normalize_skills') ? gig_normalize_skills($rawSkills) : [];
    $skillFacets = [];
    foreach ($skillsList as $s) {
        $nm = gig_skill_name($s);
        $lvl = gig_skill_level($s);
        $skillFacets[] = [
            'name' => $nm,
            'level' => $lvl,
            'label' => $nm . ' (' . $lvl . ')'
        ];
    }

    // Experiences
    $experiences = !empty($worker['experience']) ? $worker['experience'] : ($reg['previous_projects'] ?? ($siap['pengalaman_siapkerja'] ?? []));
    $expFacets = [];
    if (is_array($experiences)) {
        foreach ($experiences as $e) {
            $eFiles = [];
            if (!empty($e['files']) && is_array($e['files'])) {
                foreach ($e['files'] as $f) {
                    $eFiles[] = trim(($f['name'] ?? '') . ' ' . ($f['type'] ?? ''));
                }
            }
            $expFacets[] = [
                'role' => (string)($e['role'] ?? ''),
                'project' => (string)($e['project'] ?? ($e['institution'] ?? '')),
                'period' => (string)($e['period'] ?? ''),
                'summary' => (string)($e['summary'] ?? ''),
                'output_title' => (string)($e['output_title'] ?? ''),
                'files' => implode(', ', $eFiles)
            ];
        }
    }

    // Portfolios
    $portfolios = !empty($worker['portfolio']) ? $worker['portfolio'] : ($reg['portfolio'] ?? []);
    $portFacets = [];
    if (is_array($portfolios)) {
        foreach ($portfolios as $p) {
            $pFiles = [];
            if (!empty($p['files']) && is_array($p['files'])) {
                foreach ($p['files'] as $f) {
                    $pFiles[] = trim(($f['name'] ?? '') . ' ' . ($f['type'] ?? '') . ' ' . ($f['size'] ?? ''));
                }
            }
            $portFacets[] = [
                'title' => (string)($p['title'] ?? ''),
                'type' => (string)($p['type'] ?? ''),
                'client' => (string)($p['client'] ?? ''),
                'year' => (string)($p['year'] ?? ''),
                'deliverable' => (string)($p['deliverable'] ?? ''),
                'files' => implode(', ', $pFiles)
            ];
        }
    }

    // Reviews
    $reviews = !empty($worker['reviews']) ? $worker['reviews'] : [];
    $revFacets = [];
    if (is_array($reviews)) {
        foreach ($reviews as $r) {
            $revFacets[] = [
                'employer' => (string)($r['employer'] ?? ''),
                'project' => (string)($r['project'] ?? ''),
                'comment' => (string)($r['comment'] ?? ''),
                'date' => (string)($r['date'] ?? ''),
                'badges' => is_array($r['badges'] ?? null) ? implode(', ', $r['badges']) : ''
            ];
        }
    }

    // Social Media
    $social = !empty($worker['social_media']) ? $worker['social_media'] : ($reg['social_media'] ?? []);
    $socList = [];
    if (is_array($social)) {
        foreach ($social as $sm) {
            $p = (string)($sm['platform'] ?? '');
            $u = (string)($sm['url'] ?? '');
            if ($p !== '' || $u !== '') {
                $socList[] = trim($p . ' ' . $u);
            }
        }
    }

    // Contact
    $contactWa = (string)($worker['contact']['wa'] ?? $reg['contact_wa'] ?? '');
    $contactEmail = (string)($worker['contact']['email'] ?? $reg['contact_email'] ?? '');

    // Video
    $videoUrl = (string)($worker['video_url'] ?? $reg['video_url'] ?? '');

    // Bio & Proposal
    $bio = (string)($worker['proposal'] ?? $reg['profile_summary'] ?? '');
    $appliedProject = (string)($worker['applied_project'] ?? '');
    $bid = (string)($worker['bid'] ?? '');
    $eta = (string)($worker['eta'] ?? '');

    // SIAPKerja background (only if worker doesn't have custom experience)
    $siapStrings = [];
    if (empty($worker['experience']) && !empty($siap['pengalaman_siapkerja']) && is_array($siap['pengalaman_siapkerja'])) {
        foreach ($siap['pengalaman_siapkerja'] as $skExp) {
            $siapStrings[] = ($skExp['role'] ?? '') . ' ' . ($skExp['institution'] ?? '') . ' ' . ($skExp['summary'] ?? '');
        }
    }

    // Construct flat text corpus for fast matching
    $textParts = [
        $wId,
        (string)($worker['name'] ?? $reg['display_name'] ?? ($siap['nama'] ?? '')),
        (string)($worker['title'] ?? $reg['bidang_keahlian'] ?? ''),
        $cat,
        $catLabel,
        (string)($worker['location'] ?? $reg['domicile'] ?? ($siap['lokasi'] ?? '')),
        $bio,
        $appliedProject,
        $bid,
        $eta,
        $contactWa,
        $contactEmail,
        $videoUrl,
        implode(' ', $socList),
        implode(' ', $siapStrings),
    ];

    foreach ($skillFacets as $sf) {
        $textParts[] = $sf['name'] . ' ' . $sf['level'] . ' ' . $sf['label'];
    }
    foreach ($expFacets as $ef) {
        $textParts[] = $ef['role'] . ' ' . $ef['project'] . ' ' . $ef['period'] . ' ' . $ef['summary'] . ' ' . $ef['output_title'] . ' ' . $ef['files'];
    }
    foreach ($portFacets as $pf) {
        $textParts[] = $pf['title'] . ' ' . $pf['type'] . ' ' . $pf['client'] . ' ' . $pf['year'] . ' ' . $pf['deliverable'] . ' ' . $pf['files'];
    }
    foreach ($revFacets as $rf) {
        $textParts[] = $rf['employer'] . ' ' . $rf['project'] . ' ' . $rf['comment'] . ' ' . $rf['date'] . ' ' . $rf['badges'];
    }

    $corpus = strtolower(preg_replace('/\s+/', ' ', implode(' ', array_filter($textParts))));

    return [
        'full_text' => $corpus,
        'facets' => [
            'name' => (string)($worker['name'] ?? ''),
            'title' => (string)($worker['title'] ?? ''),
            'location' => (string)($worker['location'] ?? ''),
            'bio' => $bio,
            'skills' => $skillFacets,
            'experiences' => $expFacets,
            'portfolios' => $portFacets,
            'reviews' => $revFacets,
            'social' => $socList,
            'contact' => trim($contactWa . ' ' . $contactEmail),
        ]
    ];
}



