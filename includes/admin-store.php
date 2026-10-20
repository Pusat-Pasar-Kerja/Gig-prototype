<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function gig_admin_ensure_schema(?PDO $pdo = null): void
{
    $pdo = $pdo ?? gig_db();
    if ($pdo === null) {
        return;
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `employer_gig_registrations` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `siapkerja_email` VARCHAR(150) NOT NULL,
                `company_name`    VARCHAR(150) NOT NULL DEFAULT '',
                `industry`        VARCHAR(150) NOT NULL DEFAULT '',
                `nama_pic`        VARCHAR(150) NOT NULL,
                `nik_pic`         VARCHAR(50)  NOT NULL DEFAULT '',
                `email_pic`       VARCHAR(150) NOT NULL,
                `phone_pic`       VARCHAR(50)  NOT NULL DEFAULT '',
                `status`          VARCHAR(20)  NOT NULL DEFAULT 'pending',
                `admin_note`      TEXT NOT NULL,
                `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `reviewed_at`     DATETIME NULL,
                KEY `idx_status` (`status`),
                KEY `idx_email` (`siapkerja_email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER `video_url`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `admin_note` TEXT NOT NULL AFTER `status`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `reviewed_at` DATETIME NULL AFTER `admin_note`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `display_name` VARCHAR(150) NOT NULL DEFAULT '' AFTER `video_url`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `social_media` LONGTEXT NOT NULL AFTER `video_url`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `domicile` VARCHAR(180) NOT NULL DEFAULT '' AFTER `display_name`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `profile_summary` TEXT NOT NULL AFTER `domicile`");
        } catch (Throwable $e) {
        }

        $pdo->exec("UPDATE `gig_worker_registrations` SET `status` = 'approved'
            WHERE `username` IN ('Theressa Zaratrusha', 'Tessa', 'theressaz@pasker.id')");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `worker_profile_edit_requests` (
                `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `worker_username`   VARCHAR(100) NOT NULL,
                `worker_email`      VARCHAR(150) NOT NULL DEFAULT '',
                `edited_field`      VARCHAR(60)  NOT NULL DEFAULT '',
                `reason_code`       VARCHAR(60)  NOT NULL DEFAULT '',
                `reason_detail`     TEXT NOT NULL,
                `change_summary`    TEXT NOT NULL,
                `proposed_payload`  LONGTEXT NOT NULL,
                `status`            VARCHAR(20)  NOT NULL DEFAULT 'pending',
                `admin_note`        TEXT NOT NULL,
                `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `reviewed_at`       DATETIME NULL,
                KEY `idx_worker_edit_status` (`status`),
                KEY `idx_worker_edit_username` (`worker_username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        require_once __DIR__ . '/vacancy-store.php';
        gig_vacancy_ensure_schema($pdo);
    } catch (Throwable $e) {
        // offline / partial schema
    }
}

/** @return list<array<string, mixed>> */
function gig_admin_list_worker_registrations(?string $statusFilter = null): array
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return [];
    }
    $sql = "SELECT * FROM `gig_worker_registrations`";
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
        foreach ($rows as &$row) {
            $row['previous_projects'] = json_decode((string)($row['previous_projects'] ?? ''), true) ?: [];
            $row['portfolio'] = json_decode((string)($row['portfolio'] ?? ''), true) ?: [];
            $rawSkills = (string)($row['skills'] ?? '');
            $decodedSkills = json_decode($rawSkills, true);
            $row['skills'] = is_array($decodedSkills) ? gig_normalize_skills($decodedSkills) : gig_normalize_skills($rawSkills);
        }
        unset($row);
        return $rows;
    } catch (Throwable $e) {
        return [];
    }
}

/** @return list<array<string, mixed>> */
function gig_admin_list_employer_registrations(?string $statusFilter = null): array
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return [];
    }
    $sql = "SELECT * FROM `employer_gig_registrations`";
    $params = [];
    if ($statusFilter !== null && $statusFilter !== '') {
        $sql .= " WHERE `status` = :st";
        $params[':st'] = $statusFilter;
    }
    $sql .= " ORDER BY `created_at` DESC";
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function gig_admin_set_worker_status(string $username, string $status, string $adminNote = ''): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return false;
    }
    $allowed = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }
    try {
        $stmt = $db->prepare(
            "UPDATE `gig_worker_registrations`
             SET `status` = :st, `admin_note` = :note, `reviewed_at` = NOW()
             WHERE `username` = :u"
        );
        $stmt->execute([':st' => $status, ':note' => $adminNote, ':u' => $username]);
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['gig_worker_registered_' . $username], $_SESSION['gig_worker_registration_data_' . $username]);
        }
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function gig_admin_set_employer_status(int $id, string $status, string $adminNote = ''): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return false;
    }
    $allowed = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }
    try {
        $stmt = $db->prepare(
            "UPDATE `employer_gig_registrations`
             SET `status` = :st, `admin_note` = :note, `reviewed_at` = NOW()
             WHERE `id` = :id"
        );
        $stmt->execute([':st' => $status, ':note' => $adminNote, ':id' => $id]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function gig_save_employer_gig_registration(array $data): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return false;
    }
    try {
        $stmt = $db->prepare(
            "INSERT INTO `employer_gig_registrations`
             (`siapkerja_email`, `company_name`, `industry`, `nama_pic`, `nik_pic`, `email_pic`, `phone_pic`, `status`, `admin_note`)
             VALUES (:email, :company, :industry, :nama, :nik, :epic, :phone, 'approved', '')"
        );
        $stmt->execute([
            ':email'   => $data['siapkerja_email'] ?? '',
            ':company' => $data['company_name'] ?? '',
            ':industry'=> $data['industry'] ?? '',
            ':nama'    => $data['nama_pic'] ?? '',
            ':nik'     => $data['nik_pic'] ?? '',
            ':epic'    => $data['email_pic'] ?? '',
            ':phone'   => $data['phone_pic'] ?? '',
        ]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function gig_employer_gig_registration_status(string $siapkerjaEmail): ?string
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return null;
    }
    try {
        $stmt = $db->prepare(
            "SELECT `status` FROM `employer_gig_registrations`
             WHERE `siapkerja_email` = :e ORDER BY `id` DESC LIMIT 1"
        );
        $stmt->execute([':e' => $siapkerjaEmail]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string)$row['status'] : null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * @param list<array<string, mixed>> $rows
 * @return array<string, int>
 */
function gig_admin_count_by_status(array $rows, string $field = 'status'): array
{
    $counts = [];
    foreach ($rows as $row) {
        $st = (string)($row[$field] ?? 'pending');
        $counts[$st] = ($counts[$st] ?? 0) + 1;
    }
    return $counts;
}

/**
 * @param list<array<string, mixed>> $workers
 * @param list<array<string, mixed>> $employers
 * @param list<array<string, mixed>> $vacancies
 * @return array<string, mixed>
 */
function gig_admin_dashboard_metrics(array $workers, array $employers, array $vacancies): array
{
    $wc = gig_admin_count_by_status($workers);
    $ec = gig_admin_count_by_status($employers);
    $vc = gig_admin_count_by_status($vacancies);
    $profileEdits = gig_admin_list_worker_profile_edits('pending');

    return [
        'workers_total' => count($workers),
        'workers_pending' => (int)($wc['pending'] ?? 0),
        'workers_approved' => (int)($wc['approved'] ?? 0),
        'workers_rejected' => (int)($wc['rejected'] ?? 0),
        'employers_total' => count($employers),
        'employers_pending' => 0,
        'employers_approved' => count($employers),
        'employers_rejected' => (int)($ec['rejected'] ?? 0),
        'vacancies_total' => count($vacancies),
        'vacancies_review' => (int)($vc['review'] ?? 0),
        'vacancies_active' => (int)($vc['active'] ?? 0),
        'vacancies_revision' => (int)($vc['revision'] ?? 0),
        'vacancies_rejected' => (int)($vc['rejected'] ?? 0),
        'vacancies_draft' => (int)($vc['draft'] ?? 0),
        'worker_profile_edits_pending' => count($profileEdits),
        'pending_all' => (int)($wc['pending'] ?? 0) + (int)($vc['review'] ?? 0) + count($profileEdits),
    ];
}

/** @return list<array{label: string, employers: int, vacancies: int}> */
function gig_admin_cluster_by_industry(array $employers, array $vacancies): array
{
    $clusters = [];
    foreach ($employers as $row) {
        $label = trim((string)($row['industry'] ?? ''));
        if ($label === '') {
            $label = 'Lainnya';
        }
        if (!isset($clusters[$label])) {
            $clusters[$label] = ['label' => $label, 'employers' => 0, 'vacancies' => 0];
        }
        $clusters[$label]['employers']++;
    }
    foreach ($vacancies as $row) {
        $label = trim((string)($row['location'] ?? ''));
        if ($label === '') {
            $label = 'Remote / Nasional';
        }
        if (!isset($clusters[$label])) {
            $clusters[$label] = ['label' => $label, 'employers' => 0, 'vacancies' => 0];
        }
        $clusters[$label]['vacancies']++;
    }
    $out = array_values($clusters);
    usort($out, static fn($a, $b) => ($b['employers'] + $b['vacancies']) <=> ($a['employers'] + $a['vacancies']));
    return array_slice($out, 0, 8);
}

function gig_worker_registration_status(string $username): ?string
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return null;
    }
    try {
        $stmt = $db->prepare("SELECT `status` FROM `gig_worker_registrations` WHERE `username` = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string)$row['status'] : null;
    } catch (Throwable $e) {
        return null;
    }
}

function gig_worker_profile_edit_key(string $username): string
{
    $clean = strtolower(trim($username));
    if (in_array($clean, ['theressaz@pasker.id', 'theressaz', 'theressa zaratrusha', 'tessa'], true) || str_contains($clean, 'theressa')) {
        return 'tessa';
    }
    $first = explode(' ', $clean)[0] ?? $clean;
    return preg_replace('/[^a-z0-9]+/i', '', $first) ?: $clean;
}

/** @return list<array<string,mixed>> */
function gig_admin_list_worker_profile_edits(?string $statusFilter = null): array
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return [];
    }
    $sql = "SELECT * FROM `worker_profile_edit_requests`";
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
        foreach ($rows as &$row) {
            $row['proposed_payload'] = json_decode((string)($row['proposed_payload'] ?? ''), true) ?: [];
        }
        unset($row);
        return $rows;
    } catch (Throwable $e) {
        return [];
    }
}

function gig_create_worker_profile_edit_request(string $username, array $request): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return false;
    }
    $key = gig_worker_profile_edit_key($username);
    try {
        $stmt = $db->prepare(
            "INSERT INTO `worker_profile_edit_requests`
             (`worker_username`,`worker_email`,`edited_field`,`reason_code`,`reason_detail`,`change_summary`,`proposed_payload`,`status`,`admin_note`)
             VALUES (:u,:email,:field,:reason,:detail,:summary,:payload,'pending','')"
        );
        $stmt->execute([
            ':u' => $key,
            ':email' => (string)($request['worker_email'] ?? ''),
            ':field' => (string)($request['edited_field'] ?? ''),
            ':reason' => (string)($request['reason_code'] ?? ''),
            ':detail' => (string)($request['reason_detail'] ?? ''),
            ':summary' => (string)($request['change_summary'] ?? ''),
            ':payload' => json_encode($request['proposed_payload'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function gig_worker_has_pending_profile_edit(string $username): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return false;
    }
    $key = gig_worker_profile_edit_key($username);
    try {
        $stmt = $db->prepare(
            "SELECT `id` FROM `worker_profile_edit_requests`
             WHERE `worker_username` = :u AND `status` = 'pending'
             ORDER BY `id` DESC LIMIT 1"
        );
        $stmt->execute([':u' => $key]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

function gig_admin_set_worker_profile_edit_status(int $id, string $status, string $adminNote = ''): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return false;
    }
    if (!in_array($status, ['approved', 'rejected'], true)) {
        return false;
    }
    try {
        $stmt = $db->prepare("SELECT * FROM `worker_profile_edit_requests` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        if ((string)$row['status'] !== 'pending') {
            return false;
        }

        if ($status === 'approved') {
            $payload = json_decode((string)$row['proposed_payload'], true) ?: [];
            $workerKey = (string)$row['worker_username'];
            $existing = gig_get_worker_registration($workerKey) ?? [];
            $data = [
                'bidang_keahlian' => $payload['bidang_keahlian'] ?? ($payload['title'] ?? ($existing['bidang_keahlian'] ?? '')),
                'skills' => $payload['skills'] ?? ($existing['skills'] ?? []),
                'contact_choice' => $payload['contact_choice'] ?? ($existing['contact_choice'] ?? 'new'),
                'contact_email' => $payload['contact_email'] ?? ($payload['email'] ?? ($existing['contact_email'] ?? '')),
                'contact_wa' => $payload['contact_wa'] ?? ($payload['wa'] ?? ($existing['contact_wa'] ?? '')),
                'previous_projects' => $payload['previous_projects'] ?? ($payload['experience'] ?? ($existing['previous_projects'] ?? [])),
                'portfolio' => $payload['portfolio'] ?? ($existing['portfolio'] ?? []),
                'video_url' => $payload['video_url'] ?? ($existing['video_url'] ?? ''),
                'social_media' => $payload['social_media'] ?? ($existing['social_media'] ?? []),
                'status' => 'approved',
                'display_name' => $payload['display_name'] ?? ($payload['name'] ?? ($existing['display_name'] ?? '')),
                'domicile' => $payload['domicile'] ?? ($payload['location'] ?? ($existing['domicile'] ?? '')),
                'profile_summary' => $payload['profile_summary'] ?? ($payload['proposal'] ?? ($existing['profile_summary'] ?? '')),
            ];
            if (!gig_save_worker_registration($workerKey, $data)) {
                return false;
            }
        }

        $up = $db->prepare(
            "UPDATE `worker_profile_edit_requests`
             SET `status` = :st, `admin_note` = :note, `reviewed_at` = NOW()
             WHERE `id` = :id"
        );
        $up->execute([':st' => $status, ':note' => $adminNote, ':id' => $id]);
        return $up->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}
