<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/project-vacancies.php';

function gig_vacancy_budget_range(string $rawBudget): string
{
    $rawBudget = trim($rawBudget);
    if ($rawBudget === '') {
        return 'Nilai proyek dapat dinegosiasikan';
    }

    // Keep non-numeric labels such as "Nilai proyek dapat dinegosiasikan".
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
        'draft'     => 'Draft',
        'review'    => 'Menunggu Verifikasi',
        'revision'  => 'Perlu Revisi',
        'rejected'  => 'Ditolak',
        'active'    => 'Tayang',
        'filled'    => 'Terisi',
        'cancelled' => 'Dibatalkan',
    ];
}

/**
 * Supported special qualification requirement types that an employer can request
 * when posting a project vacancy, mapping each type to the worker input mode.
 */
function gig_special_requirement_types(): array
{
    return [
        'certificate' => [
            'label'       => 'Sertifikat / Sertifikasi Keahlian',
            'input_mode'  => 'file',
            'accept'      => '.pdf,.jpg,.jpeg,.png',
            'format_hint' => 'Upload File (PDF atau Gambar)',
            'placeholder' => 'Contoh: Sertifikat AWS Cloud Practitioner / BNSP',
            'worker_hint' => 'Unggah bukti sertifikat dalam format PDF atau gambar (JPG, JPEG, PNG, maks. 5 MB).',
        ],
        'license' => [
            'label'       => 'Lisensi / Surat Izin Praktik / SKK',
            'input_mode'  => 'file',
            'accept'      => '.pdf,.jpg,.jpeg,.png',
            'format_hint' => 'Upload File (PDF atau Gambar)',
            'placeholder' => 'Contoh: SKK Konstruksi / Surat Izin Praktik Aktif',
            'worker_hint' => 'Unggah dokumen lisensi atau izin resmi dalam format PDF atau gambar (JPG, JPEG, PNG, maks. 5 MB).',
        ],
        'social_media' => [
            'label'       => 'Username / Akun Media Sosial',
            'input_mode'  => 'text',
            'accept'      => '',
            'format_hint' => 'Teks Bebas (Username / Handle Media Sosial)',
            'placeholder' => 'Contoh: Username Instagram atau TikTok aktif',
            'worker_hint' => 'Masukkan username atau tautan profil media sosial yang diminta.',
        ],
        'portfolio_link' => [
            'label'       => 'Tautan Portofolio / Repositori Online',
            'input_mode'  => 'url',
            'accept'      => '',
            'format_hint' => 'Tautan URL (GitHub, Figma, Behance, Google Drive)',
            'placeholder' => 'Contoh: Link repositori GitHub atau file Figma',
            'worker_hint' => 'Masukkan URL valid menuju portofolio atau repositori Anda.',
        ],
        'other' => [
            'label'       => 'Lainnya',
            'input_mode'  => 'file',
            'accept'      => '.pdf,.jpg,.jpeg,.png,.doc,.docx',
            'format_hint' => 'Upload Berkas atau Tautan',
            'placeholder' => 'Contoh: Tuliskan nama kualifikasi atau berkas khusus yang diminta',
            'worker_hint' => 'Sediakan kualifikasi atau dokumen sesuai ketentuan yang diminta.',
        ],
    ];
}

/**
 * Supported file format options when an employer requires file uploads for special qualifications.
 */
function gig_special_req_file_formats(): array
{
    $docFormat = [
        'label'        => 'Dokumen (PDF, DOC, DOCX)',
        'short_label'  => 'Dokumen (PDF, DOC, DOCX)',
        'accept'       => '.pdf,.doc,.docx',
        'allowed_exts' => ['pdf', 'doc', 'docx'],
        'format_hint'  => 'Dokumen (PDF, DOC, DOCX, maks. 5 MB)',
        'worker_hint'  => 'Unggah berkas dokumen dalam format PDF, DOC, atau DOCX (maks. 5 MB).',
    ];

    $imageFormat = [
        'label'        => 'Gambar (JPG, JPEG, PNG)',
        'short_label'  => 'Gambar (JPG, JPEG, PNG)',
        'accept'       => '.jpg,.jpeg,.png',
        'allowed_exts' => ['jpg', 'jpeg', 'png'],
        'format_hint'  => 'Gambar (JPG, JPEG, PNG, maks. 5 MB)',
        'worker_hint'  => 'Unggah gambar dalam format JPG, JPEG, atau PNG (maks. 5 MB).',
    ];

    $archiveFormat = [
        'label'        => 'Berkas Kompresi (ZIP, RAR, 7Z)',
        'short_label'  => 'Berkas Kompresi (ZIP, RAR, 7Z)',
        'accept'       => '.zip,.rar,.7z',
        'allowed_exts' => ['zip', 'rar', '7z'],
        'format_hint'  => 'Berkas Kompresi ZIP, RAR, 7Z (maks. 10 MB)',
        'worker_hint'  => 'Unggah berkas kompresi dalam format ZIP, RAR, atau 7Z (maks. 10 MB).',
    ];

    return [
        'document'     => $docFormat,
        'image'        => $imageFormat,
        'archive'      => $archiveFormat,

        // Backward-compatible aliases for document
        'pdf'          => $docFormat,
        'doc'          => $docFormat,
        'docx'         => $docFormat,
        'pdf_only'     => $docFormat,
        'pdf_image'    => $docFormat,
        'any_doc'      => $docFormat,

        // Backward-compatible aliases for image
        'jpg'          => $imageFormat,
        'jpeg'         => $imageFormat,
        'png'          => $imageFormat,
        'image_only'   => $imageFormat,
        'file_image'   => $imageFormat,

        // Backward-compatible aliases for archive
        'zip'          => $archiveFormat,
        'rar'          => $archiveFormat,
        '7z'           => $archiveFormat,
        'compressed'   => $archiveFormat,
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
    gig_vacancy_seed_special_requirement_demo($pdo);
}

function gig_vacancy_seed_special_requirement_demo(PDO $pdo): void
{
    require_once __DIR__ . '/project-vacancies.php';
    $baseItems = gig_project_vacancies_base();
    $demoIds = ['GIG-2026-11-001', 'GIG-2026-11-002', 'GIG-2026-11-003'];

    foreach ($baseItems as $item) {
        $id = (string)($item['id'] ?? '');
        if (!in_array($id, $demoIds, true)) {
            continue;
        }

        try {
            $exists = $pdo->prepare("SELECT `status` FROM `project_vacancies` WHERE `id` = :id LIMIT 1");
            $exists->execute([':id' => $id]);
            $existingStatus = $exists->fetchColumn();
            if ($existingStatus === false) {
                gig_vacancy_upsert_row($pdo, $item, 'seed', false);
            } else {
                // Update special requirement in DB if previously missing
                $upd = $pdo->prepare("SELECT `vacancy_json` FROM `project_vacancies` WHERE `id` = :id LIMIT 1");
                $upd->execute([':id' => $id]);
                $rawJson = (string)$upd->fetchColumn();
                $payload = json_decode($rawJson, true);
                if (is_array($payload) && !empty($item['special_requirement'])) {
                    $payload['special_requirement'] = $item['special_requirement'];
                    $payload['title'] = $item['title'];
                    $save = $pdo->prepare("UPDATE `project_vacancies` SET `title` = :t, `vacancy_json` = :j WHERE `id` = :id");
                    $save->execute([':t' => $item['title'], ':j' => json_encode($payload, JSON_UNESCAPED_UNICODE), ':id' => $id]);
                }
            }
        } catch (Throwable $e) {
            // Ignore DB errors during seed
        }
    }
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
            $budget = gig_vacancy_budget_range((string)($payload['budget'] ?? 'Nilai proyek dapat dinegosiasikan'));
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

    if (isset($vacancy['special_requirement']) && is_array($vacancy['special_requirement'])) {
        $sReq = $vacancy['special_requirement'];
        $type = (string)($sReq['type'] ?? '');
        $typesCatalog = gig_special_requirement_types();
        if ($type !== '' && isset($typesCatalog[$type])) {
            $baseMeta = $typesCatalog[$type];
            $customType = trim((string)($sReq['custom_type'] ?? ''));
            if ($type === 'other' && $customType !== '') {
                $sReq['type_label'] = $customType;
            } else {
                $sReq['type_label'] = (string)($sReq['type_label'] ?? $baseMeta['label']);
            }
            $sReq['input_mode'] = (string)($sReq['input_mode'] ?? $baseMeta['input_mode']);
            if ($sReq['input_mode'] === 'file') {
                $fileFormats = gig_special_req_file_formats();
                $fileType = (string)($sReq['file_type'] ?? 'document');
                if (!isset($fileFormats[$fileType])) {
                    $fileType = 'document';
                }
                $fileMeta = $fileFormats[$fileType];
                $sReq['file_type'] = $fileType;
                $sReq['file_type_label'] = (string)($sReq['file_type_label'] ?? $fileMeta['label']);
                if (empty($sReq['accept']) || $sReq['accept'] === $baseMeta['accept']) {
                    $sReq['accept'] = $fileMeta['accept'];
                }
                if (empty($sReq['format_hint']) || $sReq['format_hint'] === $baseMeta['format_hint']) {
                    $sReq['format_hint'] = $fileMeta['format_hint'];
                }
                if (empty($sReq['worker_hint']) || $sReq['worker_hint'] === $baseMeta['worker_hint']) {
                    $sReq['worker_hint'] = $fileMeta['worker_hint'];
                }
                $sReq['allowed_exts'] = $fileMeta['allowed_exts'];
            }
            $vacancy['special_requirement'] = $sReq;
        }
    }

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
        'budget' => gig_vacancy_budget_range($budgetRaw !== '' ? $budgetRaw : 'Nilai proyek dapat dinegosiasikan'),
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
        'special_requirement' => $vacancy['special_requirement'] ?? null,
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
    $allowed = ['draft', 'review', 'revision', 'rejected', 'active', 'filled', 'cancelled'];
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


/**
 * Updates or removes the special qualification requirement for a vacancy (Admin tool).
 * Allows admin to edit all fields: type, custom_type, label, format/input_type, or disable completely.
 *
 * @param string $vacancyId
 * @param array<string, mixed>|null $reqData Null or empty array to disable/remove
 * @param string $adminNote Optional admin audit note
 * @return bool
 */
function gig_vacancy_update_special_requirement(string $vacancyId, ?array $reqData, string $adminNote = ''): bool
{
    gig_vacancy_ensure_schema();
    $db = gig_db();
    $typesCatalog = gig_special_requirement_types();
    $fileFormats = gig_special_req_file_formats();

    $normalizedReq = null;
    if (is_array($reqData) && !empty($reqData)) {
        $type = trim((string)($reqData['type'] ?? 'certificate'));
        if (!isset($typesCatalog[$type])) {
            $type = 'other';
        }
        $baseMeta = $typesCatalog[$type];
        $customType = trim((string)($reqData['custom_type'] ?? ''));
        $label = trim((string)($reqData['label'] ?? ''));
        if ($label === '') {
            $label = $baseMeta['label'];
        }

        $typeLabel = ($type === 'other' && $customType !== '') ? $customType : $baseMeta['label'];
        $chosenInputType = strtolower(trim((string)($reqData['input_type'] ?? 'pdf')));

        if (in_array($chosenInputType, ['document', 'pdf', 'doc', 'docx'], true)) {
            $inputMode = 'file';
            $fileType = 'document';
            $fileTypeLabel = 'Dokumen (PDF, DOC, DOCX)';
            $accept = '.pdf,.doc,.docx';
            $formatHint = 'Dokumen (PDF, DOC, DOCX, maks. 5 MB)';
            $workerHint = 'Unggah berkas dokumen dalam format PDF, DOC, atau DOCX (maks. 5 MB).';
            $allowedExts = ['pdf', 'doc', 'docx'];
        } elseif (in_array($chosenInputType, ['image', 'jpg', 'jpeg', 'png'], true)) {
            $inputMode = 'file';
            $fileType = 'image';
            $fileTypeLabel = 'Gambar (JPG, JPEG, PNG)';
            $accept = '.jpg,.jpeg,.png';
            $formatHint = 'Gambar (JPG, JPEG, PNG, maks. 5 MB)';
            $workerHint = 'Unggah gambar dalam format JPG, JPEG, atau PNG (maks. 5 MB).';
            $allowedExts = ['jpg', 'jpeg', 'png'];
        } elseif (in_array($chosenInputType, ['archive', 'compressed', 'zip', 'rar', '7z'], true)) {
            $inputMode = 'file';
            $fileType = 'archive';
            $fileTypeLabel = 'Berkas Kompresi (ZIP, RAR, 7Z)';
            $accept = '.zip,.rar,.7z';
            $formatHint = 'Berkas Kompresi ZIP, RAR, 7Z (maks. 10 MB)';
            $workerHint = 'Unggah berkas kompresi dalam format ZIP, RAR, atau 7Z (maks. 10 MB).';
            $allowedExts = ['zip', 'rar', '7z'];
        } elseif ($chosenInputType === 'url') {
            $inputMode = 'url';
            $fileType = null;
            $fileTypeLabel = null;
            $accept = '';
            $formatHint = 'Link / URL';
            $workerHint = 'Masukkan tautan / URL yang valid.';
            $allowedExts = null;
        } elseif ($chosenInputType === 'text' || $chosenInputType === 'textarea') {
            $inputMode = 'text';
            $fileType = null;
            $fileTypeLabel = null;
            $accept = '';
            $formatHint = 'Text';
            $workerHint = 'Masukkan teks atau informasi singkat yang diminta.';
            $allowedExts = null;
        } else {
            $inputMode = (string)($baseMeta['input_mode'] ?? 'file');
            $fileType = 'document';
            $fileTypeLabel = 'Dokumen (PDF, DOC, DOCX)';
            $accept = '.pdf,.doc,.docx';
            $formatHint = 'Dokumen (PDF, DOC, DOCX, maks. 5 MB)';
            $workerHint = 'Unggah berkas dokumen dalam format PDF, DOC, atau DOCX (maks. 5 MB).';
            $allowedExts = ['pdf', 'doc', 'docx'];
        }

        $normalizedReq = [
            'type'            => $type,
            'custom_type'     => $customType,
            'type_label'      => $typeLabel,
            'label'           => $label,
            'input_type'      => $chosenInputType,
            'input_mode'      => $inputMode,
            'file_type'       => $fileType,
            'file_type_label' => $fileTypeLabel,
            'accept'          => $accept,
            'format_hint'     => $formatHint,
            'worker_hint'     => $workerHint,
            'allowed_exts'    => $allowedExts,
        ];
    }

    if ($db === null) {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        if (!isset($_SESSION['project_vacancies']) || !is_array($_SESSION['project_vacancies'])) {
            $_SESSION['project_vacancies'] = [];
        }
        $existing = $_SESSION['project_vacancies'][$vacancyId] ?? gig_find_vacancy($vacancyId);
        if ($existing !== null) {
            $existing['special_requirement'] = $normalizedReq;
            if ($adminNote !== '') {
                $existing['adminNote'] = $adminNote;
            }
            $_SESSION['project_vacancies'][$vacancyId] = gig_vacancy_normalize($existing);
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
        $vacancy['special_requirement'] = $normalizedReq;
        if ($adminNote !== '') {
            $vacancy['adminNote'] = $adminNote;
        }
        $vacancy = gig_vacancy_normalize($vacancy);

        $upd = $db->prepare(
            "UPDATE `project_vacancies`
             SET `vacancy_json` = :json, `admin_note` = COALESCE(:note, `admin_note`)
             WHERE `id` = :id"
        );
        $upd->execute([
            ':json' => json_encode($vacancy, JSON_UNESCAPED_UNICODE),
            ':note' => $adminNote !== '' ? $adminNote : null,
            ':id'   => $vacancyId,
        ]);

        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        if (isset($_SESSION['project_vacancies'][$vacancyId])) {
            $_SESSION['project_vacancies'][$vacancyId] = $vacancy;
        }

        return true;
    } catch (Throwable $e) {
        error_log('gig_vacancy_update_special_requirement error: ' . $e->getMessage());
        return false;
    }
}