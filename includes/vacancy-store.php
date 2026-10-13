<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/project-vacancies.php';

function gig_vacancy_budget_range(string $rawBudget): string
{
    $rawBudget = trim($rawBudget);
    if ($rawBudget === '') {
        return 'Gaji dapat dinegosiasikan';
    }

    // Keep non-numeric labels such as "Gaji dapat dinegosiasikan".
    if (!preg_match_all('/\d[\d\.]*/', $rawBudget, $m) || empty($m[0])) {
        return $rawBudget;
    }

    $nums = array_values(array_filter(array_map(static function (string $n): int {
        return (int)preg_replace('/[^\d]/', '', $n);
    }, $m[0]), static fn(int $n): bool => $n > 0));

    if ($nums === []) {
        return $rawBudget;
    }

    $val = max($nums);
    return 'Rp ' . number_format($val, 0, ',', '.');
}

function gig_vacancy_status_labels(): array
{
    return [
        'draft'    => 'Draft',
        'review'   => 'Menunggu Verifikasi',
        'revision' => 'Perlu Revisi',
        'rejected' => 'Ditolak',
        'active'   => 'Tayang Aktif',
    ];
}

function gig_vacancy_add_business_days(DateTimeImmutable $start, int $days): DateTimeImmutable
{
    $cursor = $start;
    $added = 0;
    while ($added < $days) {
        $cursor = $cursor->modify('+1 day');
        $day = (int)$cursor->format('N'); // 1=Mon ... 7=Sun
        if ($day <= 5) {
            $added++;
        }
    }
    return $cursor;
}

function gig_vacancy_apply_auto_sla(?PDO $pdo = null): void
{
    $pdo = $pdo ?? gig_db();
    if ($pdo === null) {
        return;
    }

    try {
        $rows = $pdo->query("SELECT `id`, `status`, `status_label`, `admin_note`, `vacancy_json`, `created_at` FROM `project_vacancies` WHERE `status` = 'review'")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return;
    }

    if ($rows === []) {
        return;
    }

    $now = new DateTimeImmutable('now');
    foreach ($rows as $row) {
        $payload = json_decode((string)($row['vacancy_json'] ?? ''), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        // SLA auto-accept applies only to project vacancy requests.
        $vacancyType = strtolower(trim((string)($payload['vacancy_type'] ?? 'project')));
        if ($vacancyType !== 'project') {
            continue;
        }

        $createdAtRaw = trim((string)($row['created_at'] ?? ''));
        if ($createdAtRaw === '') {
            continue;
        }

        try {
            $createdAt = new DateTimeImmutable($createdAtRaw);
        } catch (Throwable $e) {
            continue;
        }

        $slaDue = gig_vacancy_add_business_days($createdAt, 3);
        if ($now < $slaDue) {
            continue;
        }

        $payload['status'] = 'active';
        $payload['statusLabel'] = 'Fiktif Positif';
        $payload['adminNote'] = 'Disetujui otomatis (Fiktif Positif) karena SLA verifikasi 3x24 jam hari kerja telah terlewati.';
        $payload = gig_vacancy_normalize($payload);
        $payload['statusLabel'] = 'Fiktif Positif';
        $payload['adminNote'] = 'Disetujui otomatis (Fiktif Positif) karena SLA verifikasi 3x24 jam hari kerja telah terlewati.';

        try {
            $upd = $pdo->prepare(
                "UPDATE `project_vacancies`
                 SET `status` = 'active',
                     `status_label` = 'Fiktif Positif',
                     `admin_note` = :note,
                     `vacancy_json` = :json,
                     `reviewed_at` = NOW()
                 WHERE `id` = :id"
            );
            $upd->execute([
                ':note' => (string)$payload['adminNote'],
                ':json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ':id' => (string)$row['id'],
            ]);
        } catch (Throwable $e) {
            continue;
        }

        try {
            $sub = $pdo->prepare(
                "UPDATE `project_vacancy_submissions`
                 SET `status` = 'active',
                     `status_label` = 'Fiktif Positif',
                     `admin_note` = :note,
                     `reviewed_at` = NOW()
                 WHERE `id` = :id"
            );
            $sub->execute([
                ':note' => (string)$payload['adminNote'],
                ':id' => (string)$row['id'],
            ]);
        } catch (Throwable $e) {
        }
    }
}

function gig_vacancy_ensure_schema(?PDO $pdo = null): void
{
    $pdo = $pdo ?? gig_db();
    if ($pdo === null) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `project_vacancies` (
            `id`                VARCHAR(50) NOT NULL PRIMARY KEY,
            `employer_username` VARCHAR(100) NOT NULL DEFAULT '',
            `title`             VARCHAR(255) NOT NULL,
            `category`          VARCHAR(100) NOT NULL DEFAULT '',
            `status`            VARCHAR(20) NOT NULL DEFAULT 'review',
            `status_label`      VARCHAR(80) NOT NULL DEFAULT '',
            `admin_note`        TEXT NOT NULL,
            `vacancy_json`      LONGTEXT NOT NULL,
            `source`            VARCHAR(20) NOT NULL DEFAULT 'seed',
            `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `reviewed_at`       DATETIME NULL,
            KEY `idx_status` (`status`),
            KEY `idx_employer` (`employer_username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    gig_vacancy_seed_catalog_if_empty($pdo);
    gig_vacancy_migrate_legacy_tables($pdo);
    gig_vacancy_cleanup_removed_entries($pdo);
}

function gig_vacancy_cleanup_removed_entries(PDO $pdo): void
{
    // Product request: remove specific vacancy cards from all vacancy sources.
    $removedIds = ['JOB-2026-09-001', 'JOB-2026-09-006'];
    foreach ($removedIds as $id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `project_vacancies` WHERE `id` = :id");
            $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {}
        try {
            $stmt = $pdo->prepare("DELETE FROM `project_vacancy_submissions` WHERE `id` = :id");
            $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {}
    }

    // Safety net for old records that used title-based rows with different IDs.
    $removedTitles = [
        'Senior Frontend Developer (Full Time)',
        'Tutor Privat Pemrograman Python & Data Science',
    ];
    foreach ($removedTitles as $title) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `project_vacancies` WHERE `title` = :title");
            $stmt->execute([':title' => $title]);
        } catch (Throwable $e) {}
        try {
            $stmt = $pdo->prepare("DELETE FROM `project_vacancy_submissions` WHERE `title` = :title");
            $stmt->execute([':title' => $title]);
        } catch (Throwable $e) {}
    }
}

function gig_vacancy_seed_catalog_if_empty(PDO $pdo): void
{
    require_once __DIR__ . '/project-vacancies.php';
    $baseItems = gig_project_vacancies_base();

    $jobCount = 0;
    try {
        $jobCount = (int)$pdo->query("SELECT COUNT(*) FROM `project_vacancies` WHERE `id` LIKE 'JOB-%'")->fetchColumn();
    } catch (Throwable $e) {
    }

    if ($jobCount === 0) {
        foreach ($baseItems as $vacancy) {
            gig_vacancy_upsert_row($pdo, $vacancy, 'seed', false);
        }
    }
}

function gig_vacancy_migrate_legacy_tables(PDO $pdo): void
{
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `project_vacancy_overrides` (
                `vacancy_id` VARCHAR(50) NOT NULL PRIMARY KEY,
                `status` VARCHAR(20) NOT NULL,
                `status_label` VARCHAR(80) NOT NULL DEFAULT '',
                `admin_note` TEXT NOT NULL,
                `reviewed_at` DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $overrides = $pdo->query("SELECT * FROM `project_vacancy_overrides`")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($overrides as $ov) {
            gig_vacancy_set_status((string)$ov['vacancy_id'], (string)$ov['status'], (string)$ov['admin_note'], false);
        }
    } catch (Throwable $e) {
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `project_vacancy_submissions` (
                `id` VARCHAR(50) NOT NULL PRIMARY KEY,
                `employer_username` VARCHAR(100) NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `category` VARCHAR(100) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'review',
                `status_label` VARCHAR(80) NOT NULL DEFAULT 'Menunggu Verifikasi',
                `payload_json` LONGTEXT NOT NULL,
                `admin_note` TEXT NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `reviewed_at` DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $subs = $pdo->query("SELECT * FROM `project_vacancy_submissions`")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($subs as $sub) {
            $id = (string)$sub['id'];
            $exists = $pdo->prepare("SELECT 1 FROM `project_vacancies` WHERE `id` = :id LIMIT 1");
            $exists->execute([':id' => $id]);
            if ($exists->fetch()) {
                gig_vacancy_set_status($id, (string)$sub['status'], (string)$sub['admin_note'], false);
                continue;
            }
            $payload = json_decode((string)($sub['payload_json'] ?? ''), true) ?: [];
            $budget = gig_vacancy_budget_range((string)($payload['budget'] ?? 'Gaji dapat dinegosiasikan'));
            $vacancy = [
                'id' => $id,
                'title' => (string)$sub['title'],
                'category' => (string)$sub['category'],
                'status' => (string)$sub['status'],
                'statusLabel' => (string)$sub['status_label'],
                'budget' => $budget,
                'duration' => (string)($payload['duration'] ?? ''),
                'applicantsCount' => 0,
                'acceptedCount' => 0,
                'quota' => 1,
                'location' => (string)($payload['location'] ?? 'Lokasi belum diisi'),
                'posted' => date('d M Y', strtotime((string)$sub['created_at'])),
                'skills' => $payload['skills'] ?? [],
                'desc' => (string)($payload['desc'] ?? ''),
                'deadline' => (string)($payload['deadline'] ?? ''),
                'work_type' => (string)($payload['work_type'] ?? ''),
                'industry' => (string)($payload['industry'] ?? ''),
                'experience_level' => (string)($payload['experience_level'] ?? ''),
                'visibility' => (string)($payload['visibility'] ?? 'public'),
                'adminNote' => (string)$sub['admin_note'],
                'employer' => (string)$sub['employer_username'],
                'deliverables' => (string)($payload['deliverables'] ?? ''),
            ];
            gig_vacancy_upsert_row($pdo, $vacancy, 'submission', false);
        }
    } catch (Throwable $e) {
    }
}

/** @param array<string, mixed> $vacancy */
function gig_vacancy_normalize(array $vacancy): array
{
    $labels = gig_vacancy_status_labels();
    $status = (string)($vacancy['status'] ?? 'review');
    $vacancy['status'] = $status;
    $vacancy['statusLabel'] = (string)($vacancy['statusLabel'] ?? ($labels[$status] ?? $status));
    if ($status !== 'active') {
        $vacancy['applicantsCount'] = 0;
        $vacancy['acceptedCount'] = 0;
    }
    // Platform policy: each posting can only recruit one Gig Worker.
    $vacancy['quota'] = 1;
    $vacancy['budget'] = gig_vacancy_budget_range((string)($vacancy['budget'] ?? ''));

    $rawWorkType = trim((string)($vacancy['work_type'] ?? ($vacancy['workType'] ?? '')));
    $isRemote = false;
    if (isset($vacancy['is_remote'])) {
        $isRemote = (bool)$vacancy['is_remote'];
    } elseif (strcasecmp($rawWorkType, 'remote') === 0 || str_contains(strtolower($rawWorkType), 'remote')) {
        $isRemote = true;
    }

    // Keep "Lokasi" as a concrete city/province value, never plain "Remote" or "Lokasi belum diisi".
    $rawLocation = trim((string)($vacancy['location'] ?? ''));
    $idSeed = (string)($vacancy['id'] ?? ($vacancy['title'] ?? ''));
    if (preg_match('/^remote\s*\((.+)\)$/i', $rawLocation, $m) === 1) {
        $vacancy['location'] = trim((string)$m[1]);
        $isRemote = true;
    } elseif (strcasecmp($rawLocation, 'remote') === 0) {
        $isRemote = true;
        $city = trim((string)($vacancy['location_city'] ?? ''));
        $province = trim((string)($vacancy['location_province'] ?? ''));
        $resolved = trim($city . ', ' . $province, ', ');
        $vacancy['location'] = ($resolved !== '' && strcasecmp($resolved, 'Lokasi belum diisi') !== 0) ? $resolved : gig_random_location($idSeed);
    } elseif ($rawLocation === '' || strcasecmp($rawLocation, 'Lokasi belum diisi') === 0) {
        $vacancy['location'] = gig_random_location($idSeed);
    }

    $vacancy['is_remote'] = $isRemote;
    $vacancy['work_type'] = $isRemote ? 'Remote' : ($rawWorkType !== '' ? $rawWorkType : 'On-site/Hybrid');
    $vacancy['workType']  = $vacancy['work_type'];

    $vacancyType = strtolower((string)($vacancy['vacancy_type'] ?? ''));
    $entityType  = strtolower((string)($vacancy['entity_type'] ?? ''));

    if ($vacancyType === '') {
        $id = strtolower((string)($vacancy['id'] ?? ''));
        $vacancyType = str_starts_with($id, 'job-') ? 'job' : 'project';
    }

    if ($entityType === '') {
        $emp = strtolower((string)($vacancy['employer'] ?? ''));
        if (preg_match('/(pt|cv|inc|corp|ltd|tbk|group|bumn|analytics|solusi|media|infrastruktur|talenta|mutualplus|indo hr|yayasan)/i', $emp)) {
            $entityType = 'perusahaan';
        } elseif ($emp !== '') {
            $entityType = 'individual';
        } else {
            $entityType = 'perusahaan';
        }
    }

    $vacancy['vacancy_type'] = $vacancyType;
    $vacancy['entity_type']  = $entityType;
    return $vacancy;
}

/** @param array<string, mixed> $vacancy */
function gig_vacancy_upsert_row(PDO $pdo, array $vacancy, string $source = 'seed', bool $touchReviewed = false): bool
{
    $vacancy = gig_vacancy_normalize($vacancy);
    $id = (string)($vacancy['id'] ?? '');
    if ($id === '') {
        return false;
    }
    $labels = gig_vacancy_status_labels();
    $status = (string)$vacancy['status'];
    $label = (string)($vacancy['statusLabel'] ?? ($labels[$status] ?? $status));

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO `project_vacancies`
             (`id`, `employer_username`, `title`, `category`, `status`, `status_label`, `admin_note`, `vacancy_json`, `source`, `reviewed_at`)
             VALUES (:id, :emp, :title, :cat, :st, :lbl, :note, :json, :src, :rev)
             ON DUPLICATE KEY UPDATE
               `employer_username` = VALUES(`employer_username`),
               `title` = VALUES(`title`),
               `category` = VALUES(`category`),
               `status` = VALUES(`status`),
               `status_label` = VALUES(`status_label`),
               `admin_note` = VALUES(`admin_note`),
               `vacancy_json` = VALUES(`vacancy_json`),
               `source` = VALUES(`source`),
               `reviewed_at` = COALESCE(VALUES(`reviewed_at`), `reviewed_at`)"
        );
        $stmt->execute([
            ':id'    => $id,
            ':emp'   => (string)($vacancy['employer'] ?? ''),
            ':title' => (string)($vacancy['title'] ?? ''),
            ':cat'   => (string)($vacancy['category'] ?? ''),
            ':st'    => $status,
            ':lbl'   => $label,
            ':note'  => (string)($vacancy['adminNote'] ?? ''),
            ':json'  => json_encode($vacancy, JSON_UNESCAPED_UNICODE),
            ':src'   => $source,
            ':rev'   => $touchReviewed ? date('Y-m-d H:i:s') : null,
        ]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/** @return list<array<string, mixed>> */
function gig_vacancy_load_all(?string $statusFilter = null): array
{
    gig_vacancy_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        require_once __DIR__ . '/project-vacancies.php';
        $baseItems = gig_project_vacancies_base();
        $sessionItems = $_SESSION['project_vacancies'] ?? [];
        $all = [];
        foreach ($sessionItems as $id => $item) {
            $all[$id] = gig_vacancy_normalize($item);
        }
        foreach ($baseItems as $item) {
            $id = (string)($item['id'] ?? '');
            if (!isset($all[$id])) {
                $all[$id] = gig_vacancy_normalize($item);
            }
        }
        $out = array_values($all);
        if ($statusFilter !== null && $statusFilter !== '') {
            $out = array_values(array_filter($out, static fn($v) => ($v['status'] ?? '') === $statusFilter));
        }
        return $out;
    }
    // Enforce SLA: project vacancies pending over 3 business days auto-approved.
    gig_vacancy_apply_auto_sla($db);
    $sql = "SELECT * FROM `project_vacancies`";
    $params = [];
    if ($statusFilter !== null && $statusFilter !== '') {
        $sql .= " WHERE `status` = :st";
        $params[':st'] = $statusFilter;
    }
    $sql .= " ORDER BY `created_at` DESC";
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $row) {
            $vacancy = json_decode((string)($row['vacancy_json'] ?? ''), true);
            if (!is_array($vacancy)) {
                $vacancy = [];
            }
            $vacancy['id'] = (string)$row['id'];
            $vacancy['status'] = (string)$row['status'];
            $vacancy['statusLabel'] = (string)$row['status_label'];
            $vacancy['adminNote'] = (string)$row['admin_note'];
            $vacancy['employer'] = (string)$row['employer_username'];
            $vacancy['db_source'] = (string)$row['source'];
            $out[] = gig_vacancy_normalize($vacancy);
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

function gig_vacancy_save_submission(string $employerUsername, array $vacancy): ?string
{
    gig_vacancy_ensure_schema();
    $db = gig_db();
    $id = 'GIG-' . date('Y-m') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $budgetRaw = trim((string)($vacancy['budget'] ?? ''));

    $rawWorkType = trim((string)($vacancy['work_type'] ?? ($vacancy['workType'] ?? '')));
    $isRemote = !empty($vacancy['is_remote']) || strcasecmp($rawWorkType, 'remote') === 0;
    $workType = $isRemote ? 'Remote' : ($rawWorkType !== '' ? $rawWorkType : 'On-site/Hybrid');

    $record = [
        'id' => $id,
        'title' => (string)($vacancy['title'] ?? ''),
        'category' => (string)($vacancy['category'] ?? ''),
        'status' => 'review',
        'statusLabel' => 'Menunggu Verifikasi',
        'budget' => gig_vacancy_budget_range($budgetRaw !== '' ? $budgetRaw : 'Gaji dapat dinegosiasikan'),
        'duration' => (string)($vacancy['duration'] ?? ''),
        'applicantsCount' => 0,
        'acceptedCount' => 0,
        'quota' => 1,
        'location' => (string)($vacancy['location'] ?? 'Lokasi belum diisi'),
        'work_type' => $workType,
        'workType' => $workType,
        'is_remote' => $isRemote,
        'posted' => date('d M Y'),
        'skills' => $vacancy['skills'] ?? [],
        'desc' => (string)($vacancy['desc'] ?? ''),
        'deadline' => (string)($vacancy['deadline'] ?? ''),
        'industry' => (string)($vacancy['industry'] ?? ''),
        'experience_level' => (string)($vacancy['experience_level'] ?? ''),
        'visibility' => (string)($vacancy['visibility'] ?? 'public'),
        'adminNote' => '',
        'employer' => $employerUsername,
        'deliverables' => (string)($vacancy['deliverables'] ?? ''),
    ];

    if ($db === null) {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        if (!isset($_SESSION['project_vacancies']) || !is_array($_SESSION['project_vacancies'])) {
            $_SESSION['project_vacancies'] = [];
        }
        $_SESSION['project_vacancies'][$id] = gig_vacancy_normalize($record);
        return $id;
    }

    if (!gig_vacancy_upsert_row($db, $record, 'submission')) {
        return null;
    }

    try {
        $payload = json_encode($record, JSON_UNESCAPED_UNICODE);
        $stmt = $db->prepare(
            "INSERT INTO `project_vacancy_submissions`
             (`id`, `employer_username`, `title`, `category`, `status`, `status_label`, `payload_json`, `admin_note`)
             VALUES (:id, :emp, :title, :cat, 'review', 'Menunggu Verifikasi', :payload, '')
             ON DUPLICATE KEY UPDATE `payload_json` = VALUES(`payload_json`)"
        );
        $stmt->execute([
            ':id' => $id,
            ':emp' => $employerUsername,
            ':title' => $record['title'],
            ':cat' => $record['category'],
            ':payload' => $payload,
        ]);
    } catch (Throwable $e) {
    }

    return $id;
}

function gig_vacancy_set_status(string $vacancyId, string $status, string $adminNote = '', bool $touchReviewed = true): bool
{
    gig_vacancy_ensure_schema();
    $db = gig_db();
    $allowed = ['draft', 'review', 'revision', 'rejected', 'active'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }
    $labels = gig_vacancy_status_labels();
    $label = $labels[$status] ?? $status;

    if ($db === null) {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        if (isset($_SESSION['project_vacancies'][$vacancyId])) {
            $_SESSION['project_vacancies'][$vacancyId]['status'] = $status;
            $_SESSION['project_vacancies'][$vacancyId]['statusLabel'] = $label;
            $_SESSION['project_vacancies'][$vacancyId]['adminNote'] = $adminNote;
            return true;
        }
        return false;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM `project_vacancies` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $vacancyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        $vacancy = json_decode((string)($row['vacancy_json'] ?? ''), true);
        if (!is_array($vacancy)) {
            $vacancy = [];
        }
        $vacancy['id'] = $vacancyId;
        $vacancy['status'] = $status;
        $vacancy['statusLabel'] = $label;
        $vacancy['adminNote'] = $adminNote;
        $vacancy = gig_vacancy_normalize($vacancy);

        if ($touchReviewed) {
            $upd = $db->prepare(
                "UPDATE `project_vacancies`
                 SET `status` = :st, `status_label` = :lbl, `admin_note` = :note,
                     `vacancy_json` = :json, `reviewed_at` = NOW()
                 WHERE `id` = :id"
            );
        } else {
            $upd = $db->prepare(
                "UPDATE `project_vacancies`
                 SET `status` = :st, `status_label` = :lbl, `admin_note` = :note,
                     `vacancy_json` = :json
                 WHERE `id` = :id"
            );
        }
        $upd->execute([
            ':st' => $status,
            ':lbl' => $label,
            ':note' => $adminNote,
            ':json' => json_encode($vacancy, JSON_UNESCAPED_UNICODE),
            ':id' => $vacancyId,
        ]);

        try {
            $sub = $db->prepare(
                "UPDATE `project_vacancy_submissions`
                 SET `status` = :st, `status_label` = :lbl, `admin_note` = :note, `reviewed_at` = NOW()
                 WHERE `id` = :id"
            );
            $sub->execute([':st' => $status, ':lbl' => $label, ':note' => $adminNote, ':id' => $vacancyId]);
        } catch (Throwable $e) {
        }

        return $upd->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/** Backward-compatible aliases used by admin dashboard. */
function gig_admin_set_vacancy_override(string $vacancyId, string $status, string $adminNote): bool
{
    return gig_vacancy_set_status($vacancyId, $status, $adminNote);
}

function gig_admin_set_submission_status(string $vacancyId, string $status, string $adminNote): bool
{
    return gig_vacancy_set_status($vacancyId, $status, $adminNote);
}

/** @return list<array<string, mixed>> */
function gig_admin_list_project_vacancies(?string $statusFilter = null): array
{
    $vacancies = gig_vacancy_load_all($statusFilter);
    if ($vacancies === [] && $statusFilter === null) {
        require_once __DIR__ . '/project-vacancies.php';
        $vacancies = gig_project_vacancies_base();
    }
    return array_values(array_filter($vacancies, static function (array $v): bool {
        return ($v['status'] ?? '') !== 'draft';
    }));
}

