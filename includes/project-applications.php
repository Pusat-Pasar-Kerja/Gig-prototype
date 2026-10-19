<?php
/**
 * Application & Recruitment Lifecycle Management for Gig Workers & Employers.
 * Lifecycle:
 *  1. 'applied'                     : Worker applied for vacancy
 *  2. 'accepted_by_employer'        : Legacy accept status (treated as hired)
 *  3. 'confirmed_by_worker'         : Officially hired (employer accepted an application, or worker accepted a direct offer)
 *  4. 'declined_by_worker'          : Worker declined confirmation
 *  5. 'rejected_by_employer'        : Employer rejected applicant
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/project-vacancies.php';
require_once __DIR__ . '/project-schedule.php';

function gig_is_hired_status(string $status): bool
{
    return in_array($status, ['confirmed_by_worker', 'accepted_by_employer'], true);
}

function gig_vacancy_hired_application(string $vacancyId, string $excludeAppId = ''): ?array
{
    $vacancyKey = strtolower(trim($vacancyId));
    if ($vacancyKey === '') {
        return null;
    }
    $excludeKey = strtolower(trim($excludeAppId));
    foreach (gig_get_all_applications() as $app) {
        $appId = strtolower(trim((string)($app['id'] ?? '')));
        if ($excludeKey !== '' && $appId === $excludeKey) {
            continue;
        }
        $appVacancy = strtolower(trim((string)($app['vacancy_id'] ?? '')));
        if ($appVacancy !== $vacancyKey) {
            continue;
        }
        if (gig_is_hired_status((string)($app['status'] ?? ''))) {
            return $app;
        }
    }
    return null;
}

function gig_apps_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['gig_applications']) || !is_array($_SESSION['gig_applications'])) {
        $_SESSION['gig_applications'] = [];
    }
    if (!isset($_SESSION['gig_employer_notifications']) || !is_array($_SESSION['gig_employer_notifications'])) {
        $_SESSION['gig_employer_notifications'] = [];
    }
    if (!isset($_SESSION['gig_worker_notifications']) || !is_array($_SESSION['gig_worker_notifications'])) {
        $_SESSION['gig_worker_notifications'] = [];
    }
}

function gig_apps_ensure_tables(?PDO $pdo): void
{
    if (!$pdo) {
        return;
    }
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `project_applications` (
                `id`                 VARCHAR(100) PRIMARY KEY,
                `vacancy_id`         VARCHAR(50)  NOT NULL,
                `worker_id`          VARCHAR(50)  NOT NULL,
                `worker_name`        VARCHAR(100) NOT NULL,
                `employer_username`  VARCHAR(100) NOT NULL,
                `bid_amount`         VARCHAR(50)  NOT NULL DEFAULT '',
                `status`             VARCHAR(40)  NOT NULL DEFAULT 'applied',
                `note`               TEXT,
                `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY `idx_app_vac` (`vacancy_id`),
                KEY `idx_app_worker` (`worker_id`),
                KEY `idx_app_emp` (`employer_username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `employer_notifications` (
                `id`                 VARCHAR(100) PRIMARY KEY,
                `employer_username`  VARCHAR(100) NOT NULL,
                `type`               VARCHAR(50)  NOT NULL DEFAULT 'info',
                `title`              VARCHAR(150) NOT NULL,
                `message`            TEXT         NOT NULL,
                `vacancy_id`         VARCHAR(50)  NOT NULL DEFAULT '',
                `is_read`            TINYINT(1)   NOT NULL DEFAULT 0,
                `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_notif_emp` (`employer_username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        // Add vacancy_id column if it was created before this migration
        try { $pdo->exec("ALTER TABLE `employer_notifications` ADD COLUMN `vacancy_id` VARCHAR(50) NOT NULL DEFAULT '' AFTER `message`"); } catch (Throwable) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `worker_notifications` (
                `id`                 VARCHAR(100) PRIMARY KEY,
                `worker_id`          VARCHAR(50)  NOT NULL,
                `type`               VARCHAR(50)  NOT NULL DEFAULT 'info',
                `title`              VARCHAR(150) NOT NULL,
                `message`            TEXT         NOT NULL,
                `vacancy_id`         VARCHAR(50)  NOT NULL DEFAULT '',
                `is_read`            TINYINT(1)   NOT NULL DEFAULT 0,
                `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_notif_worker` (`worker_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        // Add vacancy_id column if it was created before this migration
        try { $pdo->exec("ALTER TABLE `worker_notifications` ADD COLUMN `vacancy_id` VARCHAR(50) NOT NULL DEFAULT '' AFTER `message`"); } catch (Throwable) {}
    } catch (Throwable $e) {
        // Table fallback
    }
}

/**
 * Seeds ALL demo data (applications, notifications, project_completions) into MySQL
 * using INSERT IGNORE so it is safe to call on every request.
 */
function gig_seed_all_demo_data_to_db(): void
{
    $pdo = gig_db();
    if (!$pdo) {
        return;
    }
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    gig_apps_ensure_tables($pdo);

    // ── project_applications ────────────────────────────────────────────────
    $appStmt = $pdo->prepare("
        INSERT IGNORE INTO `project_applications`
            (`id`, `vacancy_id`, `worker_id`, `worker_name`, `employer_username`,
             `bid_amount`, `status`, `note`, `created_at`, `updated_at`)
        VALUES
            (:id, :vac, :wid, :wname, :emp, :bid, :status, :note, :created, :updated)
    ");

    $demoApps = [
        [
            'id'                => 'APP-2026-001',
            'vacancy_id'        => 'GIG-2026-09-001',
            'worker_id'         => 'tessa',
            'worker_name'       => 'Theressa Zaratrusha',
            'employer_username' => 'PT Talenta Digital Indonesia',
            'bid_amount'        => 'Rp 8.500.000',
            'status'            => 'applied',
            'note'              => 'Saya memiliki pengalaman 4+ tahun dalam merancang UI/UX dashboard SaaS.',
            'created_at'        => '2026-09-18 09:20:00',
            'updated_at'        => '2026-09-18 09:20:00',
        ],
        [
            'id'                => 'APP-2026-002',
            'vacancy_id'        => 'GIG-2026-09-002',
            'worker_id'         => 'rian',
            'worker_name'       => 'Rian Ardiansyah',
            'employer_username' => 'PT Solusi Awan Indonesia',
            'bid_amount'        => 'Rp 6.000.000',
            'status'            => 'applied',
            'note'              => 'Siap mengintegrasikan REST API SMS & WA dengan sertifikasi AWS Backend.',
            'created_at'        => '2026-09-16 10:00:00',
            'updated_at'        => '2026-09-16 10:00:00',
        ],
        [
            'id'                => 'APP-2026-003',
            'vacancy_id'        => 'GIG-2026-09-003',
            'worker_id'         => 'fajar',
            'worker_name'       => 'Fajar Pratama',
            'employer_username' => 'PT Media Digital Nusantara',
            'bid_amount'        => 'Rp 4.500.000',
            'status'            => 'applied',
            'note'              => 'Portofolio kampanye copywriting sosial media dengan engagement rate > 8%.',
            'created_at'        => '2026-09-21 08:30:00',
            'updated_at'        => '2026-09-21 08:30:00',
        ],
    ];

    foreach ($demoApps as $app) {
        try {
            $appStmt->execute([
                ':id'      => $app['id'],
                ':vac'     => $app['vacancy_id'],
                ':wid'     => $app['worker_id'],
                ':wname'   => $app['worker_name'],
                ':emp'     => $app['employer_username'],
                ':bid'     => $app['bid_amount'],
                ':status'  => $app['status'],
                ':note'    => $app['note'],
                ':created' => $app['created_at'],
                ':updated' => $app['updated_at'],
            ]);
        } catch (Throwable) {}
    }
    // Keep demo applications in pending-candidate state until employer decision.
    try {
        $pdo->exec("UPDATE `project_applications` SET `status`='applied' WHERE `id` IN ('APP-2026-001','APP-2026-002')");
    } catch (Throwable) {}

    // ── employer_notifications ───────────────────────────────────────────────
    $enStmt = $pdo->prepare("
        INSERT IGNORE INTO `employer_notifications`
            (`id`, `employer_username`, `type`, `title`, `message`, `vacancy_id`, `is_read`, `created_at`)
        VALUES
            (:id, :emp, :type, :title, :msg, :vid, :read, :created)
    ");

    $deadlineMsg1 = gig_deadline_notice_message('GIG-2026-09-001');

    $empNotifs = [
        [
            'id'                => 'NOTIF-2026-001',
            'employer_username' => 'PT Talenta Digital Indonesia',
            'type'              => 'worker_confirmed',
            'title'             => '🎉 Theressa Zaratrusha RESMI DIREKRUT!',
            'message'           => 'Anda telah menerima lamaran Theressa Zaratrusha untuk proyek "Redesign UI/UX Dashboard Prototype KarirHub". Kerja sama aktif (mulai 20 Sep 2026, durasi 3 minggu, tenggat 11 Okt 2026).',
            'vacancy_id'        => 'GIG-2026-09-001',
            'is_read'           => 0,
            'created_at'        => '2026-09-20 11:13:00',
        ],
        [
            'id'                => 'NOTIF-2026-002',
            'employer_username' => 'PT Solusi Awan Indonesia',
            'type'              => 'worker_confirmed',
            'title'             => '🎉 Rian Ardiansyah RESMI DIREKRUT!',
            'message'           => 'Rian Ardiansyah telah MENGONFIRMASI dan RESMI DIREKRUT untuk proyek "Integrasi REST API Modul Notifikasi SMS & WhatsApp". Kontrak proyek telah aktif.',
            'vacancy_id'        => 'GIG-2026-09-002',
            'is_read'           => 0,
            'created_at'        => '2026-09-19 09:00:00',
        ],
        [
            'id'                => 'NOTIF-2026-003',
            'employer_username' => 'PT Talenta Digital Indonesia',
            'type'              => 'deadline',
            'title'             => '⏰ Tenggat proyek semakin dekat',
            'message'           => $deadlineMsg1,
            'vacancy_id'        => 'GIG-2026-09-001',
            'is_read'           => 0,
            'created_at'        => '2026-09-22 08:00:00',
        ],
    ];

    foreach ($empNotifs as $n) {
        try {
            $enStmt->execute([
                ':id'      => $n['id'],
                ':emp'     => $n['employer_username'],
                ':type'    => $n['type'],
                ':title'   => $n['title'],
                ':msg'     => $n['message'],
                ':vid'     => $n['vacancy_id'],
                ':read'    => $n['is_read'],
                ':created' => $n['created_at'],
            ]);
        } catch (Throwable) {}
    }

    // ── worker_notifications ────────────────────────────────────────────────
    $wnStmt = $pdo->prepare("
        INSERT IGNORE INTO `worker_notifications`
            (`id`, `worker_id`, `type`, `title`, `message`, `vacancy_id`, `is_read`, `created_at`)
        VALUES
            (:id, :wid, :type, :title, :msg, :vid, :read, :created)
    ");

    $workerNotifs = [];

    foreach ($workerNotifs as $n) {
        try {
            $wnStmt->execute([
                ':id'      => $n['id'],
                ':wid'     => $n['worker_id'],
                ':type'    => $n['type'],
                ':title'   => $n['title'],
                ':msg'     => $n['message'],
                ':vid'     => $n['vacancy_id'],
                ':read'    => $n['is_read'],
                ':created' => $n['created_at'],
            ]);
        } catch (Throwable) {}
    }

    // ── project_completions (active contracts) ──────────────────────────────
    // The active demo contracts (CTR-GIG-2026-0811, CTR-GIG-2026-0819) are in
    // project_schedule but NOT yet in project_completions since they're ongoing.
    // We record them here so the employer rating flow can reference them.
    $compStmt = $pdo->prepare("
        INSERT IGNORE INTO `project_completions`
            (`contract_id`, `worker_id`, `employer_username`, `rating_given`,
             `review_given`, `completed_date`, `created_at`)
        VALUES
            (:cid, :wid, :emp, :rating, :review, :cdate, :created)
    ");

    $activeContracts = [
        [
            'contract_id'       => 'CTR-GIG-2026-0811',
            'worker_id'         => 'tessa',
            'employer_username' => 'PT Talenta Digital Indonesia',
            'rating_given'      => 0,
            'review_given'      => '',
            'completed_date'    => '2026-10-11',
            'created_at'        => '2026-09-20 11:13:00',
        ],
        [
            'contract_id'       => 'CTR-GIG-2026-0819',
            'worker_id'         => 'rian',
            'employer_username' => 'PT Solusi Awan Indonesia',
            'rating_given'      => 0,
            'review_given'      => '',
            'completed_date'    => '2026-10-03',
            'created_at'        => '2026-09-19 09:00:00',
        ],
    ];

    foreach ($activeContracts as $c) {
        try {
            $compStmt->execute([
                ':cid'     => $c['contract_id'],
                ':wid'     => $c['worker_id'],
                ':emp'     => $c['employer_username'],
                ':rating'  => $c['rating_given'],
                ':review'  => $c['review_given'],
                ':cdate'   => $c['completed_date'],
                ':created' => $c['created_at'],
            ]);
        } catch (Throwable) {}
    }
}

function gig_seed_demo_applications_if_needed(): void
{
    static $seeded = false;
    if ($seeded) {
        return;
    }
    $seeded = true;

    gig_apps_session_start();
    if (empty($_SESSION['gig_applications_seeded'])) {

    $defaultApps = [
        [
            'id'                 => 'APP-2026-001',
            'vacancy_id'         => 'GIG-2026-09-001',
            'worker_id'          => 'tessa',
            'worker_name'        => 'Theressa Zaratrusha',
            'employer_username'  => 'PT Talenta Digital Indonesia',
            'bid_amount'         => 'Rp 8.500.000',
            'status'             => 'applied', // Candidate submitted application, awaiting employer decision
            'note'               => 'Saya memiliki pengalaman 4+ tahun dalam merancang UI/UX dashboard SaaS.',
            'created_at'         => '2026-09-18 09:20:00',
            'updated_at'         => '2026-09-18 09:20:00',
        ],
        [
            'id'                 => 'APP-2026-002',
            'vacancy_id'         => 'GIG-2026-09-002',
            'worker_id'          => 'rian',
            'worker_name'        => 'Rian Ardiansyah',
            'employer_username'  => 'PT Solusi Awan Indonesia',
            'bid_amount'         => 'Rp 6.000.000',
            'status'             => 'applied', // Candidate submitted application, awaiting employer decision
            'note'               => 'Siap mengintegrasikan REST API SMS & WA dengan sertifikasi AWS Backend.',
            'created_at'         => '2026-09-16 10:00:00',
            'updated_at'         => '2026-09-16 10:00:00',
        ],
        [
            'id'                 => 'APP-2026-003',
            'vacancy_id'         => 'GIG-2026-09-003',
            'worker_id'          => 'fajar',
            'worker_name'        => 'Fajar Pratama',
            'employer_username'  => 'PT Media Digital Nusantara',
            'bid_amount'         => 'Rp 4.500.000',
            'status'             => 'applied', // Applied, waiting employer decision
            'note'               => 'Portofolio kampanye copywriting sosial media dengan engagement rate > 8%.',
            'created_at'         => '2026-09-21 08:30:00',
            'updated_at'         => '2026-09-21 08:30:00',
        ],
    ];

    foreach ($defaultApps as $app) {
        $_SESSION['gig_applications'][$app['id']] = $app;
    }

    // Default seed notifications for employer
    $_SESSION['gig_employer_notifications'][] = [
        'id'                => 'NOTIF-2026-001',
        'employer_username' => 'PT Talenta Digital Indonesia',
        'type'              => 'worker_confirmed',
        'title'             => '🎉 Theressa Zaratrusha RESMI DIREKRUT!',
        'message'           => 'Anda telah menerima lamaran Theressa Zaratrusha untuk proyek "Redesign UI/UX Dashboard Prototype KarirHub". Kerja sama aktif dan kontak resmi terbuka di Proyek Aktif.',
        'vacancy_id'        => 'GIG-2026-09-001',
        'is_read'           => 0,
        'created_at'        => '2026-09-20 11:13:00',
    ];

    $_SESSION['gig_employer_notifications'][] = [
        'id'                => 'NOTIF-2026-002',
        'employer_username' => 'PT Solusi Awan Indonesia',
        'type'              => 'worker_confirmed',
        'title'             => '🎉 Rian Ardiansyah RESMI DIREKRUT!',
        'message'           => 'Rian Ardiansyah telah MENGONFIRMASI dan RESMI DIREKRUT untuk proyek "Integrasi REST API Modul Notifikasi SMS & WhatsApp". Kontrak proyek telah aktif.',
        'vacancy_id'        => 'GIG-2026-09-002',
        'is_read'           => 0,
        'created_at'        => '2026-09-19 09:00:00',
    ];

    $_SESSION['gig_employer_notifications'][] = [
        'id'                => 'NOTIF-2026-003',
        'employer_username' => 'PT Talenta Digital Indonesia',
        'type'              => 'deadline',
        'title'             => '⏰ Tenggat proyek semakin dekat',
        'message'           => gig_deadline_notice_message('GIG-2026-09-001'),
        'vacancy_id'        => 'GIG-2026-09-001',
        'is_read'           => 0,
        'created_at'        => '2026-09-22 08:00:00',
    ];

    // Default seed notifications for Gig Worker theressaz@pasker.id (cleared)
    $_SESSION['gig_worker_notifications'] = [];

    $_SESSION['gig_applications_seeded'] = true;
    }

    gig_ensure_demo_notifications();

    // Persist all demo data to MySQL on the first request where DB is available
    gig_seed_all_demo_data_to_db();
}

function gig_ensure_demo_notifications(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    gig_apps_session_start();
    $deadlineMsg = gig_deadline_notice_message('GIG-2026-09-001');
    $patches = [
        'gig_employer_notifications' => [
            'NOTIF-2026-001' => [
                'type' => 'worker_confirmed',
                'title' => '🎉 Theressa Zaratrusha RESMI DIREKRUT!',
                'message' => 'Anda telah menerima lamaran Theressa Zaratrusha untuk proyek "Redesign UI/UX Dashboard Prototype KarirHub". Kerja sama aktif (mulai 20 Sep 2026, durasi 3 minggu, tenggat 11 Okt 2026).',
                'created_at' => '2026-09-20 11:13:00',
                'vacancy_id' => 'GIG-2026-09-001',
            ],
            'NOTIF-2026-003' => [
                'type' => 'deadline',
                'title' => '⏰ Tenggat proyek semakin dekat',
                'message' => $deadlineMsg,
                'created_at' => '2026-09-22 08:00:00',
                'vacancy_id' => 'GIG-2026-09-001',
            ],
        ],
        'gig_worker_notifications' => [],
    ];

    foreach ($patches as $bucket => $byId) {
        if (!isset($_SESSION[$bucket]) || !is_array($_SESSION[$bucket])) {
            $_SESSION[$bucket] = [];
        }
        $seen = [];
        foreach ($_SESSION[$bucket] as $idx => $n) {
            $id = (string)($n['id'] ?? '');
            $seen[$id] = true;
            if (isset($byId[$id])) {
                $_SESSION[$bucket][$idx] = array_merge($n, $byId[$id]);
            }
        }
        foreach ($byId as $id => $fields) {
            if (!empty($seen[$id])) {
                continue;
            }
            $_SESSION[$bucket][] = array_merge([
                'id' => $id,
                'is_read' => 0,
            ], $bucket === 'gig_worker_notifications'
                ? ['worker_id' => 'tessa']
                : ['employer_username' => 'PT Talenta Digital Indonesia'],
            $fields);
        }
    }
}

function gig_get_all_applications(): array
{
    gig_apps_session_start();
    gig_seed_demo_applications_if_needed();

    $merged = [];
    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_apps_ensure_tables($pdo);
            $rows = $pdo->query("SELECT * FROM `project_applications` ORDER BY `updated_at` DESC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $merged[$r['id']] = $r;
            }
        } catch (Throwable $ignored) {}
    }

    foreach ($_SESSION['gig_applications'] as $id => $app) {
        if (!isset($merged[$id])) {
            $merged[$id] = $app;
        }
    }

    return array_values($merged);
}

function gig_get_application_by_id(string $appId): ?array
{
    foreach (gig_get_all_applications() as $app) {
        if ($app['id'] === $appId) {
            return $app;
        }
    }
    return null;
}

function gig_get_applications_for_worker(string $workerId): array
{
    $cleanId = strtolower(trim($workerId));
    $out = [];
    foreach (gig_get_all_applications() as $app) {
        if (strtolower(trim((string)$app['worker_id'])) === $cleanId) {
            $out[] = $app;
        }
    }
    return $out;
}

function gig_get_applications_for_employer(string $employerName): array
{
    $cleanEmp = strtolower(trim($employerName));
    $out = [];
    foreach (gig_get_all_applications() as $app) {
        $empInApp = strtolower(trim((string)$app['employer_username']));
        if ($empInApp === $cleanEmp || str_contains($cleanEmp, $empInApp) || str_contains($empInApp, $cleanEmp)) {
            $out[] = $app;
        }
    }
    return $out;
}

function gig_worker_has_active_project_capacity(string $workerId): bool
{
    if (!function_exists('gig_worker_active_project_count')) {
        require_once __DIR__ . '/project-schedule.php';
    }
    if (!function_exists('gig_worker_active_project_limit')) {
        return true;
    }
    return gig_worker_active_project_count($workerId, $workerId) < gig_worker_active_project_limit();
}

function gig_apply_for_project(string $workerId, string $workerName, string $vacancyId, string $note = ''): array
{
    gig_apps_session_start();
    $vacancy = gig_find_vacancy($vacancyId);
    if (!$vacancy) {
        return ['ok' => false, 'error' => 'Lowongan proyek tidak ditemukan.'];
    }
    if (gig_vacancy_hired_application((string)$vacancy['id']) !== null) {
        return ['ok' => false, 'error' => 'Lowongan ini sudah terisi oleh 1 Gig Worker.'];
    }

    // Check if worker already applied
    foreach (gig_get_applications_for_worker($workerId) as $existing) {
        if ($existing['vacancy_id'] === $vacancyId && !in_array($existing['status'], ['rejected_by_employer', 'declined_by_worker'], true)) {
            return ['ok' => false, 'error' => 'Anda sudah mengajukan lamaran untuk proyek ini.'];
        }
    }

    $appId = 'APP-' . date('Ymd') . '-' . rand(1000, 9999);
    $appData = [
        'id'                 => $appId,
        'vacancy_id'         => $vacancyId,
        'worker_id'          => $workerId,
        'worker_name'        => $workerName,
        'employer_username'  => $vacancy['employer'] ?? $vacancy['client'] ?? 'PT Perusahaan',
        'bid_amount'         => $vacancy['budget'] ?? 'Rp 5.000.000',
        'status'             => 'applied',
        'note'               => $note,
        'created_at'         => date('Y-m-d H:i:s'),
        'updated_at'         => date('Y-m-d H:i:s'),
    ];

    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_apps_ensure_tables($pdo);
            $stmt = $pdo->prepare("
                INSERT INTO `project_applications`
                    (`id`, `vacancy_id`, `worker_id`, `worker_name`, `employer_username`, `bid_amount`, `status`, `note`, `created_at`, `updated_at`)
                VALUES
                    (:id, :vac, :wid, :wname, :emp, :bid, 'applied', :note, NOW(), NOW())
            ");
            $stmt->execute([
                ':id'    => $appData['id'],
                ':vac'   => $appData['vacancy_id'],
                ':wid'   => $appData['worker_id'],
                ':wname' => $appData['worker_name'],
                ':emp'   => $appData['employer_username'],
                ':bid'   => $appData['bid_amount'],
                ':note'  => $appData['note'],
            ]);
        } catch (Throwable $ignored) {}
    }

    $_SESSION['gig_applications'][$appId] = $appData;

    // Notify Employer of new application
    gig_add_employer_notification(
        $appData['employer_username'],
        'new_application',
        '📩 Lamaran Proyek Baru!',
        'Gig Worker ' . $workerName . ' telah mengajukan lamaran untuk proyek "' . ($vacancy['title'] ?? 'Proyek') . '". Kunjungi menu Kandidat untuk meninjau profil.',
        (string)($appData['vacancy_id'] ?? '')
    );

    // Notify worker that the employer has received the application.
    gig_add_worker_notification(
        $appData['worker_id'],
        'application_received',
        'Lamaran Berhasil Dikirim',
        'Lamaran Anda untuk proyek "' . ($vacancy['title'] ?? 'Proyek') . '" telah diterima oleh pemberi kerja ' . $appData['employer_username'] . '.',
        (string)($appData['vacancy_id'] ?? '')
    );

    return ['ok' => true, 'application' => $appData];
}

/**
 * Employer decides to accept or reject candidate's application.
 */
function gig_employer_respond_application(string $appId, string $decision, string $employerUsername): array
{
    gig_apps_session_start();
    $app = gig_get_application_by_id($appId);
    if (!$app) {
        return ['ok' => false, 'error' => 'Lamaran tidak ditemukan.'];
    }

    $vacancy = gig_find_vacancy($app['vacancy_id']);
    $projectTitle = $vacancy['title'] ?? 'Proyek';

    if ($decision === 'accept' && $vacancy && gig_vacancy_hired_application((string)$vacancy['id'], $appId) !== null) {
        return ['ok' => false, 'error' => 'Lowongan ini sudah memiliki Gig Worker terpilih.'];
    }
    if ($decision === 'accept' && !gig_worker_has_active_project_capacity((string)$app['worker_id'])) {
        return ['ok' => false, 'error' => 'Gig Worker sudah memiliki 2 proyek aktif. Selesaikan salah satu proyek aktif terlebih dahulu.'];
    }

    $newStatus = ($decision === 'accept') ? 'confirmed_by_worker' : 'rejected_by_employer';
    $app['status'] = $newStatus;
    $app['updated_at'] = date('Y-m-d H:i:s');

    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_apps_ensure_tables($pdo);
            $stmt = $pdo->prepare("UPDATE `project_applications` SET `status` = :st, `updated_at` = NOW() WHERE `id` = :id");
            $stmt->execute([':st' => $newStatus, ':id' => $appId]);
        } catch (Throwable $ignored) {}
    }

    $_SESSION['gig_applications'][$appId] = $app;

    if ($decision === 'accept') {
        gig_add_employer_notification(
            $employerUsername,
            'worker_confirmed',
            '🎉 ' . $app['worker_name'] . ' RESMI DIREKRUT!',
            'Anda menerima lamaran ' . $app['worker_name'] . ' untuk proyek "' . $projectTitle . '". Kerja sama aktif dan kontak resmi terbuka di Proyek Aktif.',
            (string)($app['vacancy_id'] ?? '')
        );

        gig_add_worker_notification(
            $app['worker_id'],
            'recruited',
            '🎉 Anda Resmi Direkrut!',
            'Perusahaan ' . $employerUsername . ' menerima lamaran Anda untuk proyek "' . $projectTitle . '". Proyek kini aktif. Buka Proyek Aktif untuk melihat countdown dan rincian kerja.',
            (string)($app['vacancy_id'] ?? '')
        );
    } else {
        // NOTIFY WORKER OF REJECTION
        gig_add_worker_notification(
            $app['worker_id'],
            'app_rejected',
            'Lamaran Proyek Belum Disetujui',
            'Lamaran Anda untuk proyek "' . $projectTitle . '" belum dapat disetujui oleh ' . $employerUsername . '.',
            (string)($app['vacancy_id'] ?? '')
        );
    }

    return ['ok' => true, 'application' => $app];
}

/**
 * Gig Worker confirms or declines the accepted project application/offer.
 */
function gig_worker_confirm_application(string $appId, string $action, string $workerId): array
{
    gig_apps_session_start();
    $app = gig_get_application_by_id($appId);
    if (!$app) {
        return ['ok' => false, 'error' => 'Data lamaran/penawaran tidak ditemukan.'];
    }

    $vacancy = gig_find_vacancy($app['vacancy_id']);
    $projectTitle = $vacancy['title'] ?? 'Proyek';

    if ($action === 'confirm' && $vacancy && gig_vacancy_hired_application((string)$vacancy['id'], $appId) !== null) {
        return ['ok' => false, 'error' => 'Proyek ini sudah terisi oleh Gig Worker lain.'];
    }
    if ($action === 'confirm' && !gig_worker_has_active_project_capacity((string)$workerId)) {
        return ['ok' => false, 'error' => 'Anda sudah memiliki 2 proyek aktif. Selesaikan salah satu proyek aktif terlebih dahulu.'];
    }

    if ($action === 'confirm') {
        $newStatus = 'confirmed_by_worker';
        $app['status'] = $newStatus;
        $app['updated_at'] = date('Y-m-d H:i:s');

        // Notify Employer that worker officially confirmed & is recruited!
        gig_add_employer_notification(
            $app['employer_username'],
            'worker_confirmed',
            '🎉 ' . $app['worker_name'] . ' RESMI DIREKRUT!',
            'Gig Worker ' . $app['worker_name'] . ' telah MENGONFIRMASI persetujuan dan RESMI DIREKRUT untuk proyek "' . $projectTitle . '"! Kontak komunikasi resmi kini terbuka di Proyek Aktif.',
            (string)($app['vacancy_id'] ?? '')
        );

        // Notify Worker of recruitment confirmation
        gig_add_worker_notification(
            $workerId,
            'recruited',
            '🚀 Resmi Direkrut!',
            'Anda telah MENGONFIRMASI proyek "' . $projectTitle . '" bersama ' . $app['employer_username'] . '. Selamat bekerja! Rincian proyek kini aktif di menu Proyek Aktif.',
            (string)($app['vacancy_id'] ?? '')
        );

    } else {
        $newStatus = 'declined_by_worker';
        $app['status'] = $newStatus;
        $app['updated_at'] = date('Y-m-d H:i:s');

        // Notify Employer that worker declined
        gig_add_employer_notification(
            $app['employer_username'],
            'worker_declined',
            '⚠️ Penawaran Ditolak oleh ' . $app['worker_name'],
            'Gig Worker ' . $app['worker_name'] . ' MENOLAK penawaran/kesepakatan untuk proyek "' . $projectTitle . '". Lowongan proyek tetap dibuka bagi kandidat lain.',
            (string)($app['vacancy_id'] ?? '')
        );

        // Notify Worker of decline confirmation
        gig_add_worker_notification(
            $workerId,
            'declined',
            'Penawaran Ditolak',
            'Anda telah menolak penawaran proyek "' . $projectTitle . '". Pemberi kerja telah diberitahukan.',
            (string)($app['vacancy_id'] ?? '')
        );
    }

    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_apps_ensure_tables($pdo);
            $stmt = $pdo->prepare("UPDATE `project_applications` SET `status` = :st, `updated_at` = NOW() WHERE `id` = :id");
            $stmt->execute([':st' => $newStatus, ':id' => $appId]);
        } catch (Throwable $ignored) {}
    }

    $_SESSION['gig_applications'][$appId] = $app;

    return [
        'ok' => true,
        'action' => $action,
        'application' => $app,
    ];
}

function gig_notification_vacancy_id(array $n): string
{
    $id = trim((string)($n['vacancy_id'] ?? ''));
    if ($id !== '') {
        return $id;
    }
    $text = (string)($n['message'] ?? '') . ' ' . (string)($n['title'] ?? '');
    if (preg_match('/GIG-\d{4}-\d{2}-\d{3}/', $text, $m)) {
        return $m[0];
    }
    foreach (gig_project_vacancies() as $vacancy) {
        $title = trim((string)($vacancy['title'] ?? ''));
        if ($title !== '' && str_contains($text, $title)) {
            return (string)$vacancy['id'];
        }
    }
    return '';
}

function gig_notification_href(array $n, string $audience = 'worker'): string
{
    $explicit = trim((string)($n['link'] ?? ''));
    if ($explicit !== '') {
        return $explicit;
    }

    $type = (string)($n['type'] ?? '');
    $vacancyId = gig_notification_vacancy_id($n);
    $blob = strtolower((string)($n['title'] ?? '') . ' ' . (string)($n['message'] ?? ''));

    if ($audience === 'employer') {
        $page = match ($type) {
            'new_application', 'waiting_confirmation', 'worker_accepted', 'worker_declined' => 'employer-pelamar.php',
            'worker_confirmed', 'deadline', 'recruited' => 'employer-proyek-aktif.php',
            default => '',
        };
        if ($page === '') {
            if (str_contains($blob, 'lamaran') || str_contains($blob, 'kandidat') || str_contains($blob, 'pelamar')) {
                $page = 'employer-pelamar.php';
            } elseif (str_contains($blob, 'tenggat') || str_contains($blob, 'deadline') || str_contains($blob, 'countdown') || str_contains($blob, 'proyek aktif') || str_contains($blob, 'direkrut')) {
                $page = 'employer-proyek-aktif.php';
            } else {
                $page = 'dashboard-employer.php';
            }
        }
        if ($vacancyId !== '' && $page === 'employer-proyek-aktif.php') {
            return $page . '#project-' . rawurlencode($vacancyId);
        }
        if ($vacancyId !== '' && $page === 'employer-pelamar.php') {
            return 'employer-detail-lowongan.php?id=' . urlencode($vacancyId);
        }
        return $page;
    }

    $page = match ($type) {
        'direct_offer' => 'worker-penawaran.php',
        'app_rejected' => 'worker-bursa.php',
        'application_received' => 'worker-bursa.php',
        'app_approved', 'recruited', 'deadline' => 'worker-tugas.php',
        'declined' => 'worker-penawaran.php',
        default => '',
    };
    if ($page === '') {
        if (str_contains($blob, 'penawaran') || str_contains($blob, 'disetujui') || str_contains($blob, 'lamaran')) {
            $page = 'worker-penawaran.php';
        } elseif (str_contains($blob, 'tenggat') || str_contains($blob, 'deadline') || str_contains($blob, 'countdown') || str_contains($blob, 'proyek aktif') || str_contains($blob, 'direkrut')) {
            $page = 'worker-tugas.php';
        } elseif (str_contains($blob, 'ulasan')) {
            $page = 'worker-ulasan.php';
        } else {
            $page = 'worker-bursa.php';
        }
    }
    if ($vacancyId !== '' && $type === 'direct_offer') {
        return 'worker-project-detail.php?id=' . urlencode($vacancyId) . '&from=penawaran';
    }
    if ($vacancyId !== '' && $type === 'application_received') {
        return 'worker-project-detail.php?id=' . urlencode($vacancyId) . '&from=bursa';
    }
    if ($vacancyId !== '' && ($page === 'worker-tugas.php' || in_array($type, ['recruited', 'deadline', 'app_approved'], true))) {
        return 'worker-tugas.php#project-' . rawurlencode($vacancyId);
    }
    return $page;
}

function gig_add_employer_notification(string $employerUsername, string $type, string $title, string $message, string $vacancyId = ''): void
{
    gig_apps_session_start();
    $notifId = 'NOTIF-' . date('YmdHis') . '-' . rand(100, 999);
    $notif = [
        'id'                => $notifId,
        'employer_username' => $employerUsername,
        'type'              => $type,
        'title'             => $title,
        'message'           => $message,
        'vacancy_id'        => $vacancyId,
        'is_read'           => 0,
        'created_at'        => date('Y-m-d H:i:s'),
    ];

    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_apps_ensure_tables($pdo);
            $stmt = $pdo->prepare("
                INSERT INTO `employer_notifications`
                    (`id`, `employer_username`, `type`, `title`, `message`, `vacancy_id`, `is_read`, `created_at`)
                VALUES
                    (:id, :emp, :type, :title, :msg, :vid, 0, NOW())
            ");
            $stmt->execute([
                ':id'    => $notif['id'],
                ':emp'   => $notif['employer_username'],
                ':type'  => $notif['type'],
                ':title' => $notif['title'],
                ':msg'   => $notif['message'],
                ':vid'   => $notif['vacancy_id'],
            ]);
        } catch (Throwable $ignored) {}
    }

    $_SESSION['gig_employer_notifications'][] = $notif;
}

function gig_get_employer_notifications(?string $employerUsername = null): array
{
    gig_apps_session_start();
    gig_seed_demo_applications_if_needed();

    $merged = [];
    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_apps_ensure_tables($pdo);
            $rows = $pdo->query("SELECT * FROM `employer_notifications` ORDER BY `created_at` DESC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $merged[$r['id']] = $r;
            }
        } catch (Throwable $ignored) {}
    }

    foreach ($_SESSION['gig_employer_notifications'] as $n) {
        $id = (string)($n['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $merged[$id] = isset($merged[$id]) ? array_merge($merged[$id], $n) : $n;
    }

    $list = array_values($merged);
    usort($list, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    return $list;
}

function gig_add_worker_notification(string $workerId, string $type, string $title, string $message, string $vacancyId = ''): void
{
    gig_apps_session_start();
    $cleanId = strtolower(trim($workerId));
    $notifId = 'WNOTIF-' . date('YmdHis') . '-' . rand(100, 999);
    $notif = [
        'id'         => $notifId,
        'worker_id'  => $cleanId,
        'type'       => $type,
        'title'      => $title,
        'message'    => $message,
        'vacancy_id' => $vacancyId,
        'is_read'    => 0,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_apps_ensure_tables($pdo);
            $stmt = $pdo->prepare("
                INSERT INTO `worker_notifications`
                    (`id`, `worker_id`, `type`, `title`, `message`, `vacancy_id`, `is_read`, `created_at`)
                VALUES
                    (:id, :wid, :type, :title, :msg, :vid, 0, NOW())
            ");
            $stmt->execute([
                ':id'    => $notif['id'],
                ':wid'   => $notif['worker_id'],
                ':type'  => $notif['type'],
                ':title' => $notif['title'],
                ':msg'   => $notif['message'],
                ':vid'   => $notif['vacancy_id'],
            ]);
        } catch (Throwable $ignored) {}
    }

    $_SESSION['gig_worker_notifications'][] = $notif;
}

function gig_get_worker_notifications(?string $workerId = null): array
{
    gig_apps_session_start();
    gig_seed_demo_applications_if_needed();

    $cleanId = $workerId ? strtolower(trim($workerId)) : null;
    $normId = $cleanId ? (function_exists('gig_history_worker_key') ? gig_history_worker_key($cleanId) : $cleanId) : null;
    $merged = [];
    $pdo = gig_db();
    if ($pdo) {
        try {
            gig_apps_ensure_tables($pdo);
            $rows = $pdo->query("SELECT * FROM `worker_notifications` ORDER BY `created_at` DESC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $merged[$r['id']] = $r;
            }
        } catch (Throwable $ignored) {}
    }

    foreach ($_SESSION['gig_worker_notifications'] as $n) {
        $id = (string)($n['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $merged[$id] = isset($merged[$id]) ? array_merge($merged[$id], $n) : $n;
    }

    $list = array_values($merged);
    if ($cleanId !== null) {
        $list = array_values(array_filter($list, function ($n) use ($cleanId, $normId) {
            $w = strtolower(trim((string)($n['worker_id'] ?? '')));
            $wNorm = function_exists('gig_history_worker_key') ? gig_history_worker_key($w) : $w;
            return $w === $cleanId || ($normId !== null && $wNorm === $normId) || str_contains($cleanId, $w) || str_contains($w, $cleanId);
        }));
    }
    usort($list, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    return array_values($list);
}
