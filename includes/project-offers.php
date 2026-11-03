<?php
/**
 * Employer → Gig Worker project offers.
 * Stored in MySQL when available, with a session fallback for offline/demo use.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/project-vacancies.php';
require_once __DIR__ . '/project-applications.php';

function gig_offer_worker_key(string $value): string
{
    $clean = strtolower(trim($value));
    // Normalise all Theressa Zaratrusha / tessa variants → 'theressaz@pasker.id'
    if ($clean === 'theressaz@pasker.id' || $clean === 'theressaz' || str_contains($clean, 'theressa') || $clean === 'tessa') {
        return 'theressaz@pasker.id';
    }
    $first = explode(' ', trim($value))[0] ?? $value;
    return strtolower(preg_replace('/[^a-z0-9]+/i', '', $first) ?: $value);
}

function gig_offers_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['gig_project_offers']) || !is_array($_SESSION['gig_project_offers'])) {
        $_SESSION['gig_project_offers'] = [];
    }
}

function gig_offers_ensure_table(?PDO $pdo): void
{
    if (!$pdo) {
        return;
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `project_offers` (
            `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `employer_username`  VARCHAR(100) NOT NULL,
            `worker_id`          VARCHAR(50)  NOT NULL,
            `vacancy_id`         VARCHAR(50)  NOT NULL,
            `message`            VARCHAR(500) NOT NULL DEFAULT '',
            `status`             VARCHAR(20)  NOT NULL DEFAULT 'pending',
            `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_offer` (`employer_username`, `worker_id`, `vacancy_id`),
            KEY `idx_worker` (`worker_id`),
            KEY `idx_vacancy` (`vacancy_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function gig_offer_fingerprint(array $offer): string
{
    return strtolower(
        ($offer['employer_username'] ?? '') . '|' .
        ($offer['worker_id'] ?? '') . '|' .
        ($offer['vacancy_id'] ?? '')
    );
}

function gig_offers_all_raw(): array
{
    gig_offers_session_start();
    $merged = [];

    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_offers_ensure_table($pdo);
            $rows = $pdo->query(
                "SELECT id, employer_username, worker_id, vacancy_id, message, status, created_at
                 FROM project_offers
                 ORDER BY created_at DESC"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $row['id'] = (string)$row['id'];
                $merged[gig_offer_fingerprint($row)] = $row;
            }
        } catch (Throwable $e) {
            // Fall through to session bag.
        }
    }

    foreach ($_SESSION['gig_project_offers'] as $row) {
        if (!is_array($row) || empty($row['vacancy_id']) || empty($row['worker_id'])) {
            continue;
        }
        $merged[gig_offer_fingerprint($row)] = $row;
    }

    $list = array_values($merged);
    usort($list, static function (array $a, array $b): int {
        return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
    });

    return $list;
}

function gig_offer_enrich(array $offer): array
{
    $vacancy = gig_find_vacancy((string)$offer['vacancy_id']);
    $offer['project'] = $vacancy;
    $offer['project_title'] = $vacancy['title'] ?? (string)$offer['vacancy_id'];
    $offer['budget'] = $vacancy['budget'] ?? '—';
    $offer['duration'] = $vacancy['duration'] ?? '—';
    $offerLoc = trim((string)($vacancy['location'] ?? ''));
    if ($offerLoc === '' || strcasecmp($offerLoc, 'Lokasi belum diisi') === 0) {
        $offerLoc = gig_random_location((string)($offer['vacancy_id'] ?? ''));
    }
    $offer['location'] = $offerLoc;
    $offer['skills'] = $vacancy['skills'] ?? [];
    $offer['deadline'] = $vacancy['deadline'] ?? '';
    $offer['employer_display'] = $vacancy['employer']
        ?? $vacancy['client']
        ?? (string)$offer['employer_username'];
    $offer['detail_id'] = $vacancy['id'] ?? (string)$offer['vacancy_id'];
    return $offer;
}

function gig_offers_for_worker(string $username): array
{
    $key = gig_offer_worker_key($username);
    $out = [];
    foreach (gig_offers_all_raw() as $offer) {
        if (gig_offer_worker_key((string)$offer['worker_id']) !== $key) {
            continue;
        }
        $out[] = gig_offer_enrich($offer);
    }
    return $out;
}

function gig_offers_all(): array
{
    $list = [];
    foreach (gig_offers_all_raw() as $raw) {
        $offer = gig_offer_enrich($raw);
        $wProf = function_exists('gig_find_worker') ? gig_find_worker((string)$offer['worker_id']) : null;
        $offer['worker_name'] = $wProf['name'] ?? ucfirst((string)$offer['worker_id']);

        $app = null;
        if (function_exists('gig_get_applications_for_worker')) {
            foreach (gig_get_applications_for_worker((string)$offer['worker_id']) as $a) {
                if (strcasecmp((string)($a['vacancy_id'] ?? ''), (string)$offer['vacancy_id']) === 0) {
                    $app = $a;
                    break;
                }
            }
        }
        $appStatus = $app['status'] ?? ($offer['status'] ?? 'sent');
        $offer['app_status'] = $appStatus;
        $offer['rejection_reason'] = $app['rejection_reason'] ?? '';
        $offer['responded_at'] = !empty($app['updated_at']) && $appStatus !== 'applied' && $appStatus !== 'sent' ? $app['updated_at'] : null;
        $list[] = $offer;
    }
    return $list;
}

function gig_count_vacancy_offers(string $vacancyId): int
{
    $clean = strtolower(trim($vacancyId));
    $count = 0;
    foreach (gig_offers_all_raw() as $offer) {
        if (strtolower((string)$offer['vacancy_id']) === $clean) {
            $count++;
        }
    }
    return $count;
}

function gig_has_offer(string $employer, string $workerId, string $vacancyId): bool
{
    $needle = gig_offer_fingerprint([
        'employer_username' => $employer,
        'worker_id' => gig_offer_worker_key($workerId),
        'vacancy_id' => $vacancyId,
    ]);
    foreach (gig_offers_all_raw() as $offer) {
        if (gig_offer_fingerprint($offer) === $needle) {
            return true;
        }
    }
    return false;
}

function gig_employer_offered_map(string $employer): array
{
    $map = [];
    foreach (gig_offers_all_raw() as $offer) {
        if (strcasecmp((string)$offer['employer_username'], $employer) !== 0) {
            continue;
        }
        $workerKey = gig_offer_worker_key((string)$offer['worker_id']);
        $map[$workerKey][] = (string)$offer['vacancy_id'];
    }
    return $map;
}

function gig_insert_offer_raw(string $employer, string $workerId, string $vacancyId, string $message = ''): array
{
    gig_offers_session_start();
    $offer = [
        'id' => uniqid('off-', true),
        'employer_username' => $employer,
        'worker_id' => gig_offer_worker_key($workerId),
        'vacancy_id' => $vacancyId,
        'message' => $message,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_offers_ensure_table($pdo);
            $stmt = $pdo->prepare(
                "INSERT INTO project_offers (employer_username, worker_id, vacancy_id, message, status)
                 VALUES (:employer, :worker, :vacancy, :message, 'pending')"
            );
            $stmt->execute([
                ':employer' => $offer['employer_username'],
                ':worker' => $offer['worker_id'],
                ':vacancy' => $offer['vacancy_id'],
                ':message' => $offer['message'],
            ]);
            $offer['id'] = (string)$pdo->lastInsertId();
        } catch (Throwable $e) {
            // Session copy still recorded below.
        }
    }

    $_SESSION['gig_project_offers'][] = $offer;
    return $offer;
}

function gig_save_offer(string $employer, string $workerId, string $vacancyId, string $message = ''): array
{
    $vacancy = gig_find_vacancy($vacancyId);
    if (!$vacancy || ($vacancy['status'] ?? '') !== 'active') {
        return ['ok' => false, 'error' => 'Hanya proyek yang sudah tayang yang dapat ditawarkan.'];
    }
    if (gig_vacancy_hired_application((string)$vacancy['id']) !== null) {
        return ['ok' => false, 'error' => 'Proyek ini sudah terisi oleh 1 Gig Worker.'];
    }

    $workerKey = gig_offer_worker_key($workerId);
    if ($workerKey === '') {
        return ['ok' => false, 'error' => 'Gig Worker tidak valid.'];
    }

    if (function_exists('gig_worker_has_active_project_capacity') && !gig_worker_has_active_project_capacity($workerKey)) {
        return ['ok' => false, 'error' => 'Gig Worker ini sedang Unavailable karena telah memiliki 2 Penugasan Proyek aktif.'];
    }

    if (gig_has_offer($employer, $workerKey, $vacancy['id'])) {
        return ['ok' => false, 'error' => 'Penawaran untuk proyek ini sudah dikirim ke Gig Worker tersebut.'];
    }

    $count = gig_count_vacancy_offers($vacancy['id']);
    if ($count >= 3) {
        return ['ok' => false, 'error' => 'Lowongan ini sudah mencapai batas 3 penawaran aktif.'];
    }

    $offer = gig_insert_offer_raw($employer, $workerKey, $vacancy['id'], $message);
    return [
        'ok' => true,
        'offer' => $offer,
        'offer_count' => $count + 1,
    ];
}

function gig_seed_demo_offers_if_needed(string $username): void
{
    $key = gig_offer_worker_key($username);
    if ($key !== 'theressaz@pasker.id') {
        return;
    }
    if (count(gig_offers_for_worker($username)) > 0) {
        return;
    }

    gig_insert_offer_raw('PT ABC', 'theressaz@pasker.id', 'GIG-2026-09-001');
    gig_insert_offer_raw('PT ABC', 'theressaz@pasker.id', 'GIG-2026-09-002');
    gig_insert_offer_raw('CV Kreasi Visual Nusantara', 'theressaz@pasker.id', 'GIG-2026-09-008');
}
