<?php
declare(strict_types=1);

function gig_id_month_names(): array
{
    return [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
}

function gig_format_id_date(DateTimeInterface $dt): string
{
    $months = gig_id_month_names();
    return $dt->format('d') . ' ' . $months[(int)$dt->format('n')] . ' ' . $dt->format('Y');
}

function gig_duration_days(string $label): int
{
    $normalized = strtolower(str_replace(',', '.', $label));
    if (preg_match('/(\d+(?:\.\d+)?)\s*minggu/', $normalized, $m)) {
        return (int)round((float)$m[1] * 7);
    }
    if (preg_match('/(\d+(?:\.\d+)?)\s*bulan/', $normalized, $m)) {
        return (int)round((float)$m[1] * 30);
    }
    if (preg_match('/(\d+)\s*hari/', $normalized, $m)) {
        return (int)$m[1];
    }
    return 21;
}

function gig_add_duration(DateTimeInterface $start, string $durationLabel): DateTimeImmutable
{
    $startImm = DateTimeImmutable::createFromInterface($start);
    return $startImm->modify('+' . gig_duration_days($durationLabel) . ' days');
}

function gig_countdown_parts(DateTimeInterface $deadline, ?DateTimeInterface $now = null): array
{
    $nowImm = $now ? DateTimeImmutable::createFromInterface($now) : new DateTimeImmutable('now');
    $end = DateTimeImmutable::createFromInterface($deadline);
    if ($end <= $nowImm) {
        // Prototype mode safeguard: active projects countdown should never expire.
        // Automatically roll deadline forward so it is always actively counting down.
        $end = $nowImm->modify('+7 days +14 hours +30 minutes +15 seconds');
    }
    $diff = $nowImm->diff($end);
    return [
        'days' => (int)$diff->days,
        'hours' => (int)$diff->h,
        'mins' => (int)$diff->i,
        'secs' => (int)$diff->s,
        'expired' => false,
    ];
}

function gig_extension_ensure_table(?PDO $pdo): void
{
    if (!$pdo) {
        return;
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `project_extension_requests` (
            `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `contract_id`      VARCHAR(50) NOT NULL,
            `requester_role`   VARCHAR(20) NOT NULL,
            `requester_id`     VARCHAR(100) NOT NULL DEFAULT '',
            `amount`           INT NOT NULL,
            `unit`             VARCHAR(10) NOT NULL,
            `days_delta`       INT NOT NULL,
            `reason_note`      VARCHAR(500) NOT NULL DEFAULT '',
            `status`           VARCHAR(20) NOT NULL DEFAULT 'pending',
            `decision_by_role` VARCHAR(20) NOT NULL DEFAULT '',
            `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `reviewed_at`      DATETIME NULL,
            KEY `idx_ext_contract` (`contract_id`),
            KEY `idx_ext_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function gig_extension_unit_to_days(int $amount, string $unit): int
{
    $u = strtolower(trim($unit));
    $amount = max(1, $amount);
    if ($u === 'week') return $amount * 7;
    if ($u === 'month') return $amount * 30;
    return $amount;
}

/** @return list<array<string,mixed>> */
function gig_project_extension_requests(string $contractId): array
{
    $pdo = function_exists('gig_db') ? gig_db() : null;
    if (!$pdo) {
        return [];
    }
    gig_extension_ensure_table($pdo);
    $stmt = $pdo->prepare(
        "SELECT * FROM `project_extension_requests`
         WHERE `contract_id` = :cid
         ORDER BY `id` DESC"
    );
    $stmt->execute([':cid' => $contractId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function gig_project_pending_extension(string $contractId): ?array
{
    // Pending extension request undone per user request
    return null;
}

function gig_project_total_approved_extension_days(string $contractId): int
{
    $days = 0;
    foreach (gig_project_extension_requests($contractId) as $row) {
        if (($row['status'] ?? '') === 'approved') {
            $days += (int)($row['days_delta'] ?? 0);
        }
    }
    return max(0, $days);
}

function gig_request_project_extension(string $contractId, string $requesterRole, string $requesterId, int $amount, string $unit, string $reason = ''): array
{
    $role = strtolower(trim($requesterRole));
    if (!in_array($role, ['worker', 'employer'], true)) {
        return ['ok' => false, 'error' => 'Peran pengaju tidak valid.'];
    }
    $unit = strtolower(trim($unit));
    if (!in_array($unit, ['day', 'week', 'month'], true)) {
        return ['ok' => false, 'error' => 'Satuan harus hari/minggu/bulan.'];
    }
    if ($amount < 1 || $amount > 12) {
        return ['ok' => false, 'error' => 'Jumlah perpanjangan harus 1-12.'];
    }
    if (gig_project_pending_extension($contractId)) {
        return ['ok' => false, 'error' => 'Masih ada pengajuan perpanjangan yang menunggu konfirmasi.'];
    }

    $pdo = function_exists('gig_db') ? gig_db() : null;
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Database tidak tersedia.'];
    }
    gig_extension_ensure_table($pdo);
    $days = gig_extension_unit_to_days($amount, $unit);
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO `project_extension_requests`
             (`contract_id`,`requester_role`,`requester_id`,`amount`,`unit`,`days_delta`,`reason_note`,`status`,`decision_by_role`)
             VALUES (:cid,:role,:rid,:amt,:unit,:days,:reason,'pending','')"
        );
        $stmt->execute([
            ':cid' => $contractId,
            ':role' => $role,
            ':rid' => $requesterId,
            ':amt' => $amount,
            ':unit' => $unit,
            ':days' => $days,
            ':reason' => $reason,
        ]);
        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Gagal menyimpan pengajuan perpanjangan.'];
    }
}

function gig_decide_project_extension(int $requestId, string $deciderRole, bool $approve): array
{
    $role = strtolower(trim($deciderRole));
    if (!in_array($role, ['worker', 'employer'], true)) {
        return ['ok' => false, 'error' => 'Peran konfirmasi tidak valid.'];
    }
    $pdo = function_exists('gig_db') ? gig_db() : null;
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Database tidak tersedia.'];
    }
    gig_extension_ensure_table($pdo);
    try {
        $stmt = $pdo->prepare("SELECT * FROM `project_extension_requests` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['ok' => false, 'error' => 'Pengajuan tidak ditemukan.'];
        }
        if (($row['status'] ?? '') !== 'pending') {
            return ['ok' => false, 'error' => 'Pengajuan sudah diproses.'];
        }
        if (($row['requester_role'] ?? '') === $role) {
            return ['ok' => false, 'error' => 'Pengaju tidak bisa memproses pengajuan sendiri.'];
        }
        $up = $pdo->prepare(
            "UPDATE `project_extension_requests`
             SET `status` = :st, `decision_by_role` = :role, `reviewed_at` = NOW()
             WHERE `id` = :id"
        );
        $up->execute([
            ':st' => $approve ? 'approved' : 'rejected',
            ':role' => $role,
            ':id' => $requestId,
        ]);
        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Gagal memproses pengajuan.'];
    }
}

function gig_cancel_project_extension(int $requestId, string $requesterRole, string $requesterId): array
{
    $role = strtolower(trim($requesterRole));
    $reqId = strtolower(trim($requesterId));
    if (!in_array($role, ['worker', 'employer'], true)) {
        return ['ok' => false, 'error' => 'Peran pengaju tidak valid.'];
    }
    $pdo = function_exists('gig_db') ? gig_db() : null;
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Database tidak tersedia.'];
    }
    gig_extension_ensure_table($pdo);
    try {
        $stmt = $pdo->prepare("SELECT * FROM `project_extension_requests` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['ok' => false, 'error' => 'Pengajuan tidak ditemukan.'];
        }
        if (($row['status'] ?? '') !== 'pending') {
            return ['ok' => false, 'error' => 'Pengajuan tidak bisa dibatalkan karena sudah diproses.'];
        }
        if (($row['requester_role'] ?? '') !== $role) {
            return ['ok' => false, 'error' => 'Hanya pengaju yang bisa membatalkan.'];
        }
        $owner = strtolower(trim((string)($row['requester_id'] ?? '')));
        if ($owner !== '' && $reqId !== '' && $owner !== $reqId) {
            return ['ok' => false, 'error' => 'Anda bukan pemilik pengajuan ini.'];
        }

        $up = $pdo->prepare(
            "UPDATE `project_extension_requests`
             SET `status` = 'cancelled', `decision_by_role` = :role, `reviewed_at` = NOW()
             WHERE `id` = :id"
        );
        $up->execute([':role' => $role, ':id' => $requestId]);
        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Gagal membatalkan pengajuan perpanjangan.'];
    }
}

function gig_format_extension_label(int $amount, string $unit): string
{
    if ($unit === 'week') return $amount . ' minggu';
    if ($unit === 'month') return $amount . ' bulan';
    return $amount . ' hari';
}

function gig_completion_confirmation_ensure_table(?PDO $pdo): void
{
    if (!$pdo) {
        return;
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `project_completion_confirmations` (
            `contract_id`            VARCHAR(50)  NOT NULL PRIMARY KEY,
            `employer_confirmed_at`  DATETIME NULL,
            `employer_confirmed_by`  VARCHAR(100) NOT NULL DEFAULT '',
            `worker_confirmed_at`    DATETIME NULL,
            `worker_confirmed_by`    VARCHAR(100) NOT NULL DEFAULT '',
            `both_confirmed_at`      DATETIME NULL,
            `created_at`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function gig_project_completion_status(string $contractId): array
{
    $state = [
        'contract_id' => $contractId,
        'employer_confirmed' => false,
        'worker_confirmed' => false,
        'both_confirmed' => false,
        'employer_confirmed_at' => null,
        'worker_confirmed_at' => null,
        'both_confirmed_at' => null,
    ];
    if ($contractId === '') {
        return $state;
    }

    $pdo = function_exists('gig_db') ? gig_db() : null;
    if ($pdo !== null) {
        try {
            gig_completion_confirmation_ensure_table($pdo);
            $stmt = $pdo->prepare("SELECT * FROM `project_completion_confirmations` WHERE `contract_id` = :cid LIMIT 1");
            $stmt->execute([':cid' => $contractId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($row) {
                $state['employer_confirmed'] = !empty($row['employer_confirmed_at']);
                $state['worker_confirmed'] = !empty($row['worker_confirmed_at']);
                $state['both_confirmed'] = !empty($row['employer_confirmed_at']) && !empty($row['worker_confirmed_at']);
                $state['employer_confirmed_at'] = $row['employer_confirmed_at'] ?? null;
                $state['worker_confirmed_at'] = $row['worker_confirmed_at'] ?? null;
                $state['both_confirmed_at'] = $row['both_confirmed_at'] ?? null;
            }
        } catch (Throwable) {
            // fall back to session state
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE
        && isset($_SESSION['project_completion_confirmations'][$contractId])
        && is_array($_SESSION['project_completion_confirmations'][$contractId])) {
        $s = $_SESSION['project_completion_confirmations'][$contractId];
        $state['employer_confirmed'] = !empty($s['employer_confirmed']) || $state['employer_confirmed'];
        $state['worker_confirmed'] = !empty($s['worker_confirmed']) || $state['worker_confirmed'];
        $state['both_confirmed'] = ($state['employer_confirmed'] && $state['worker_confirmed']);
    }

    return $state;
}

function gig_confirm_project_finished(string $contractId, string $role, string $actorId = ''): array
{
    $role = strtolower(trim($role));
    if (!in_array($role, ['employer', 'worker'], true)) {
        return ['ok' => false, 'error' => 'Peran konfirmasi tidak valid.'];
    }
    if ($contractId === '') {
        return ['ok' => false, 'error' => 'Kontrak proyek tidak ditemukan.'];
    }

    $pdo = function_exists('gig_db') ? gig_db() : null;
    $alreadyConfirmed = false;
    if ($pdo !== null) {
        try {
            gig_completion_confirmation_ensure_table($pdo);
            $stmt = $pdo->prepare("SELECT * FROM `project_completion_confirmations` WHERE `contract_id` = :cid LIMIT 1");
            $stmt->execute([':cid' => $contractId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if (!$row) {
                $ins = $pdo->prepare(
                    "INSERT INTO `project_completion_confirmations`
                     (`contract_id`, `employer_confirmed_at`, `employer_confirmed_by`, `worker_confirmed_at`, `worker_confirmed_by`, `both_confirmed_at`)
                     VALUES (:cid, :eAt, :eBy, :wAt, :wBy, :bothAt)"
                );
                $isEmployer = $role === 'employer';
                $isWorker = $role === 'worker';
                $ins->execute([
                    ':cid' => $contractId,
                    ':eAt' => $isEmployer ? date('Y-m-d H:i:s') : null,
                    ':eBy' => $isEmployer ? $actorId : '',
                    ':wAt' => $isWorker ? date('Y-m-d H:i:s') : null,
                    ':wBy' => $isWorker ? $actorId : '',
                    ':bothAt' => null,
                ]);
            } else {
                $roleAtCol = $role === 'employer' ? 'employer_confirmed_at' : 'worker_confirmed_at';
                $roleByCol = $role === 'employer' ? 'employer_confirmed_by' : 'worker_confirmed_by';
                $alreadyConfirmed = !empty($row[$roleAtCol]);
                if (!$alreadyConfirmed) {
                    $up = $pdo->prepare(
                        "UPDATE `project_completion_confirmations`
                         SET `$roleAtCol` = NOW(), `$roleByCol` = :actor
                         WHERE `contract_id` = :cid"
                    );
                    $up->execute([':actor' => $actorId, ':cid' => $contractId]);
                }
            }

            $state = gig_project_completion_status($contractId);
            if ($state['both_confirmed']) {
                $both = $pdo->prepare(
                    "UPDATE `project_completion_confirmations`
                     SET `both_confirmed_at` = COALESCE(`both_confirmed_at`, NOW())
                     WHERE `contract_id` = :cid"
                );
                $both->execute([':cid' => $contractId]);
                $state = gig_project_completion_status($contractId);
            }
        } catch (Throwable) {
            $state = gig_project_completion_status($contractId);
        }
    } else {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION['project_completion_confirmations'][$contractId]) || !is_array($_SESSION['project_completion_confirmations'][$contractId])) {
            $_SESSION['project_completion_confirmations'][$contractId] = [
                'employer_confirmed' => false,
                'worker_confirmed' => false,
            ];
        }
        $key = $role . '_confirmed';
        $alreadyConfirmed = !empty($_SESSION['project_completion_confirmations'][$contractId][$key]);
        $_SESSION['project_completion_confirmations'][$contractId][$key] = true;
        $state = gig_project_completion_status($contractId);
    }

    return [
        'ok' => true,
        'already_confirmed' => $alreadyConfirmed,
        'state' => $state,
    ];
}

function gig_project_review_status(string $contractId): array
{
    $status = [
        'employer_reviewed' => false,
        'worker_reviewed' => false,
        'both_reviewed' => false,
    ];
    if ($contractId === '') {
        return $status;
    }

    $pdo = function_exists('gig_db') ? gig_db() : null;
    if ($pdo !== null) {
        try {
            $stEmp = $pdo->prepare("SELECT 1 FROM `project_reviews` WHERE `contract_id` = :cid LIMIT 1");
            $stEmp->execute([':cid' => $contractId]);
            $status['employer_reviewed'] = (bool)$stEmp->fetchColumn();
        } catch (Throwable) {
            // ignore
        }
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `employer_reviews` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `contract_id` VARCHAR(50) NOT NULL,
                    `worker_id` VARCHAR(50) NOT NULL,
                    `employer_username` VARCHAR(100) NOT NULL,
                    `project_title` VARCHAR(255) NOT NULL,
                    `overall_rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
                    `comment` TEXT NOT NULL,
                    `badges` TEXT NOT NULL DEFAULT '',
                    `recommend_employer` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `uniq_contract_worker` (`contract_id`, `worker_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $stWork = $pdo->prepare("SELECT 1 FROM `employer_reviews` WHERE `contract_id` = :cid LIMIT 1");
            $stWork->execute([':cid' => $contractId]);
            $status['worker_reviewed'] = (bool)$stWork->fetchColumn();
        } catch (Throwable) {
            // ignore
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE
        && isset($_SESSION['project_review_status'][$contractId])
        && is_array($_SESSION['project_review_status'][$contractId])) {
        $s = $_SESSION['project_review_status'][$contractId];
        $status['employer_reviewed'] = !empty($s['employer_reviewed']) || $status['employer_reviewed'];
        $status['worker_reviewed'] = !empty($s['worker_reviewed']) || $status['worker_reviewed'];
    }
    $status['both_reviewed'] = $status['employer_reviewed'] && $status['worker_reviewed'];
    return $status;
}

/**
 * Canonical active demo contracts.
 * Project tenggat = hire date + agreed duration (not the vacancy apply deadline).
 */
function gig_demo_active_projects(): array
{
    $now = new DateTimeImmutable('now');

    $uiDuration = '3 Minggu';
    $apiDuration = '2 Minggu';
    $mobDuration = '2 Minggu';
    $auditDuration = '10 Hari';

    // Prototype mode: dynamically set active project deadlines into the future so countdown never runs out.
    // P1 (UI/UX 3 Minggu): ~14 days 8 hours remaining
    $endUi = $now->modify('+14 days +8 hours +32 minutes +40 seconds');
    $hireUi = $endUi->modify('-21 days');

    // P2 (REST API 2 Minggu): ~5 days 16 hours remaining
    $endApi = $now->modify('+5 days +16 hours +45 minutes +15 seconds');
    $hireApi = $endApi->modify('-14 days');

    // P3 (Mobile App 2 Minggu): ~8 days 12 hours remaining
    $endMob = $now->modify('+8 days +12 hours +20 minutes');
    $hireMob = $endMob->modify('-14 days');

    // P4 (Audit Design System 10 Hari): ~6 days 18 hours remaining
    $endAudit = $now->modify('+6 days +18 hours +40 minutes');
    $hireAudit = $endAudit->modify('-10 days');

    $cdUi = gig_countdown_parts($endUi, $now);
    $cdApi = gig_countdown_parts($endApi, $now);
    $cdMob = gig_countdown_parts($endMob, $now);
    $cdAudit = gig_countdown_parts($endAudit, $now);

    $projects = [
        [
            'contract_id' => 'CTR-GIG-2026-0811',
            'id' => 'GIG-2026-09-001',
            'title' => 'Redesign UI/UX Dashboard Prototype KarirHub',
            'employer' => 'PT ABC',
            'employer_category' => 'IT & Software Partner',
            'employer_phone' => '0812-9988-7766',
            'employer_email' => 'project@ptabc.co.id',
            'worker_id' => 'theressaz@pasker.id',
            'worker_name' => 'Theressa Zaratrusha',
            'worker_role' => 'Lead UI/UX Designer',
            'budget' => 'Rp 8.500.000',
            'duration' => $uiDuration,
            'location' => 'Jakarta Selatan',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'category' => 'Desain & Kreatif',
            'hired_at' => $hireUi->format('Y-m-d H:i:s'),
            'hired_label' => gig_format_id_date($hireUi),
            'deadline' => gig_format_id_date($endUi),
            'deadline_iso' => $endUi->format(DateTimeInterface::ATOM),
            'days_left' => $cdUi['days'],
            'hours_left' => $cdUi['hours'],
            'mins_left' => $cdUi['mins'],
            'secs_left' => $cdUi['secs'],
            'progress' => 65,
            'status_label' => 'Sedang Berjalan',
            'status_badge_class' => 'badge-status active',
            'deliverable_status' => 'Review Prototype UI/UX',
            'deliverable_note' => 'Prototype interaktif 12+ layar, design system component kit di Figma, dan panduan handoff developer',
            'deliverables' => 'Prototype interaktif 12+ layar, design system component kit di Figma, dan panduan handoff developer',
            'is_completed' => false,
            'countdown_id' => 'countdown-proj-ui',
        ],
        [
            'contract_id' => 'CTR-GIG-2026-0905',
            'id' => 'GIG-2026-09-005',
            'title' => 'Desain UI/UX Mobile App E-Commerce UMKM',
            'employer' => 'CV Visual Studio Creative',
            'employer_category' => 'Design & Creative Agency',
            'employer_phone' => '0811-2233-4455',
            'employer_email' => 'project@visualstudio.co.id',
            'worker_id' => 'theressaz@pasker.id',
            'worker_name' => 'Theressa Zaratrusha',
            'worker_role' => 'Lead UI/UX Designer',
            'budget' => 'Rp 6.500.000',
            'duration' => $mobDuration,
            'location' => 'Yogyakarta',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'category' => 'Desain & Kreatif',
            'hired_at' => $hireMob->format('Y-m-d H:i:s'),
            'hired_label' => gig_format_id_date($hireMob),
            'deadline' => gig_format_id_date($endMob),
            'deadline_iso' => $endMob->format(DateTimeInterface::ATOM),
            'days_left' => $cdMob['days'],
            'hours_left' => $cdMob['hours'],
            'mins_left' => $cdMob['mins'],
            'secs_left' => $cdMob['secs'],
            'progress' => 40,
            'status_label' => 'Sedang Berjalan',
            'status_badge_class' => 'badge-status active',
            'deliverable_status' => 'Wireframe & High Fidelity Screens',
            'deliverable_note' => 'Wireframe & High Fidelity Screens, alur checkout, dan halaman katalog produk di Figma',
            'deliverables' => 'Wireframe & High Fidelity Screens, alur checkout, dan halaman katalog produk di Figma',
            'is_completed' => false,
            'countdown_id' => 'countdown-proj-mob',
        ],
        [
            'contract_id' => 'CTR-GIG-2026-0912',
            'id' => 'GIG-2026-09-012',
            'title' => 'Audit Design System & Aksesibilitas Web Portal',
            'employer' => 'PT Nusantara Media Technologi',
            'employer_category' => 'Media & Enterprise Tech',
            'employer_phone' => '0815-6677-8899',
            'employer_email' => 'tech@nusantaramedia.id',
            'worker_id' => 'dimas',
            'worker_name' => 'Dimas Prasetyo',
            'worker_role' => 'Cloud API Engineer',
            'budget' => 'Rp 5.000.000',
            'duration' => $auditDuration,
            'location' => 'Jakarta Pusat',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'category' => 'Desain & Kreatif',
            'hired_at' => $hireAudit->format('Y-m-d H:i:s'),
            'hired_label' => gig_format_id_date($hireAudit),
            'deadline' => gig_format_id_date($endAudit),
            'deadline_iso' => $endAudit->format(DateTimeInterface::ATOM),
            'days_left' => $cdAudit['days'],
            'hours_left' => $cdAudit['hours'],
            'mins_left' => $cdAudit['mins'],
            'secs_left' => $cdAudit['secs'],
            'progress' => 20,
            'status_label' => 'Sedang Berjalan',
            'status_badge_class' => 'badge-status active',
            'deliverable_status' => 'Evaluasi WCAG 2.1 & Token Warna',
            'deliverable_note' => 'Dokumen laporan evaluasi WCAG 2.1 AA, token warna Figma, dan panduan perbaikan komponen',
            'deliverables' => 'Dokumen laporan evaluasi WCAG 2.1 AA, token warna Figma, dan panduan perbaikan komponen',
            'is_completed' => false,
            'countdown_id' => 'countdown-proj-audit',
        ],
        [
            'contract_id' => 'CTR-GIG-2026-0819',
            'id' => 'GIG-2026-09-002',
            'title' => 'Integrasi REST API Modul Notifikasi SMS & WhatsApp',
            'employer' => 'PT ABC',
            'employer_category' => 'Cloud & Infrastructure',
            'employer_phone' => '0813-7766-5544',
            'employer_email' => 'tech@ptabc.co.id',
            'worker_id' => 'rian',
            'worker_name' => 'Rian Ardiansyah',
            'worker_role' => 'Backend API Developer',
            'budget' => 'Rp 6.000.000',
            'duration' => $apiDuration,
            'location' => 'Bandung',
            'work_type' => 'On-site/Hybrid',
            'workType' => 'On-site/Hybrid',
            'is_remote' => false,
            'category' => 'IT & Pemrograman',
            'hired_at' => $hireApi->format('Y-m-d H:i:s'),
            'hired_label' => gig_format_id_date($hireApi),
            'deadline' => gig_format_id_date($endApi),
            'deadline_iso' => $endApi->format(DateTimeInterface::ATOM),
            'days_left' => $cdApi['days'],
            'hours_left' => $cdApi['hours'],
            'mins_left' => $cdApi['mins'],
            'secs_left' => $cdApi['secs'],
            'progress' => 90,
            'status_label' => 'Sedang Berjalan',
            'status_badge_class' => 'badge-status active',
            'deliverable_status' => 'Endpoint Webhook & Integrasi Provider Siap',
            'deliverable_note' => 'Endpoint REST API webhook, integrasi modul notifikasi SMS & WhatsApp, serta dokumentasi integrasi API',
            'deliverables' => 'Endpoint REST API webhook, integrasi modul notifikasi SMS & WhatsApp, serta dokumentasi integrasi API',
            'is_completed' => false,
            'countdown_id' => 'countdown-proj-api',
        ],
    ];

    static $recursionLock = false;
    if ($recursionLock) {
        return $projects;
    }
    $recursionLock = true;

    try {
        // Include dynamically hired projects from posted vacancies
        if (function_exists('gig_get_all_applications')) {
        $existingVacIds = array_column($projects, 'id');
        $existingContracts = array_column($projects, 'contract_id');
        foreach (gig_get_all_applications() as $app) {
            $status = (string)($app['status'] ?? '');
            if (!gig_is_hired_status($status)) {
                continue;
            }
            $vacId = (string)($app['vacancy_id'] ?? '');
            $cid = 'CTR-' . (string)($app['id'] ?? $vacId);
            if (in_array($vacId, $existingVacIds, true) || in_array($cid, $existingContracts, true)) {
                continue;
            }
            $vac = gig_find_vacancy($vacId);
            if (!$vac) {
                continue;
            }
            $hireDate = new DateTimeImmutable(!empty($app['updated_at']) ? (string)$app['updated_at'] : (!empty($app['created_at']) ? (string)$app['created_at'] : 'now'));
            $durationStr = !empty($vac['duration']) ? (string)$vac['duration'] : '2 Minggu';
            $endDate = gig_add_duration($hireDate, $durationStr);
            $cdPart = gig_countdown_parts($endDate);
            $isRem = gig_is_remote_work($vac);
            $wt = gig_work_type_label($vac);

            $projects[] = [
                'contract_id' => $cid,
                'id' => $vacId,
                'title' => (string)($vac['title'] ?? 'Proyek'),
                'employer' => (string)($vac['employer'] ?? ($app['employer_username'] ?? 'Pemberi Kerja')),
                'employer_category' => (string)($vac['category'] ?? 'Partner KarirHub'),
                'employer_phone' => '0812-3456-7890',
                'employer_email' => 'contact@perusahaan.co.id',
                'worker_id' => (string)($app['worker_id'] ?? ''),
                'worker_name' => (string)($app['worker_name'] ?? 'Gig Worker'),
                'worker_role' => (string)($vac['category'] ?? 'Gig Worker'),
                'budget' => (string)($vac['budget'] ?? 'Rp 5.000.000'),
                'duration' => $durationStr,
                'location' => (string)($vac['location'] ?? 'Jakarta Selatan'),
                'work_type' => $wt,
                'workType' => $wt,
                'is_remote' => $isRem,
                'category' => (string)($vac['category'] ?? ''),
                'deliverables' => (string)($vac['deliverables'] ?? 'Hasil akhir proyek diserahkan sesuai kesepakatan ruang lingkup kerja.'),
                'hired_at' => $hireDate->format('Y-m-d H:i:s'),
                'hired_label' => gig_format_id_date($hireDate),
                'deadline' => gig_format_id_date($endDate),
                'deadline_iso' => $endDate->format(DateTimeInterface::ATOM),
                'days_left' => $cdPart['days'],
                'hours_left' => $cdPart['hours'],
                'mins_left' => $cdPart['mins'],
                'secs_left' => $cdPart['secs'],
                'progress' => 50,
                'status_label' => 'Sedang Berjalan',
                'status_badge_class' => 'badge-status active',
                'deliverable_status' => 'Pengerjaan Proyek Aktif',
                'deliverable_note' => (string)($vac['deliverables'] ?? 'Pengerjaan deliverable sedang berlangsung sesuai kesepakatan lowongan.'),
                'is_completed' => false,
                'countdown_id' => 'countdown-proj-' . strtolower(preg_replace('/[^a-z0-9]/', '', $cid)),
            ];
        }
    }

    $completionMap = gig_worker_project_completions_map();
    foreach ($projects as &$proj) {
        $vac = function_exists('gig_find_vacancy') ? gig_find_vacancy((string)$proj['id']) : null;
        if ($vac) {
            if (empty($proj['location'])) $proj['location'] = (string)($vac['location'] ?? 'Jakarta Selatan');
            if (empty($proj['work_type'])) $proj['work_type'] = gig_work_type_label($vac);
            $proj['workType'] = $proj['work_type'];
            $proj['is_remote'] = gig_is_remote_work($vac);
            if (empty($proj['deliverables'])) $proj['deliverables'] = (string)($vac['deliverables'] ?? '');
            if (empty($proj['category'])) $proj['category'] = (string)($vac['category'] ?? '');
        }
        $contractId = (string)$proj['contract_id'];
        $baseDeadline = new DateTimeImmutable((string)$proj['deadline_iso']);
        $approvedDays = gig_project_total_approved_extension_days($contractId);
        $pendingReq = gig_project_pending_extension($contractId);
        $finalDeadline = $baseDeadline->modify('+' . $approvedDays . ' days');
        $cd = gig_countdown_parts($finalDeadline);

        $proj['base_deadline_iso'] = $baseDeadline->format(DateTimeInterface::ATOM);
        $proj['approved_extension_days'] = $approvedDays;
        $proj['pending_extension'] = $pendingReq;
        $proj['deadline_iso'] = $finalDeadline->format(DateTimeInterface::ATOM);
        $proj['deadline'] = gig_format_id_date($finalDeadline);
        $proj['days_left'] = $cd['days'];
        $proj['hours_left'] = $cd['hours'];
        $proj['mins_left'] = $cd['mins'];
        $proj['secs_left'] = $cd['secs'];
        $proj['is_expired'] = (bool)$cd['expired'];
        $proj['completion_confirmation'] = gig_project_completion_status($contractId);
        $proj['review_status'] = gig_project_review_status($contractId);

        $isCompleted = isset($completionMap[$contractId]);
        // Prototype safeguard: demo contracts always remain active with live countdown
        if (in_array($contractId, ['CTR-GIG-2026-0811', 'CTR-GIG-2026-0819', 'CTR-GIG-2026-0905', 'CTR-GIG-2026-0912'], true)) {
            $proj['is_expired'] = false;
        }
        if (!$isCompleted && $proj['is_expired']) {
            $proj['status_label'] = 'Tidak Selesai';
            $proj['status_badge_class'] = 'badge-status cancelled';
            $proj['deliverable_note'] = 'Deadline terlewati dan proyek belum diselesaikan.';
        } elseif (!$isCompleted) {
            $proj['status_label'] = 'Sedang Berjalan';
            $proj['status_badge_class'] = 'badge-status active';
        }
    }
    unset($proj);
    } finally {
        $recursionLock = false;
    }

    return $projects;
}

function gig_deadline_notice_message(string $vacancyId): string
{
    $proj = gig_demo_active_project_by_id($vacancyId);
    if (!$proj) {
        return 'Buka Proyek Aktif untuk melihat countdown tenggat pengerjaan.';
    }
    return 'Proyek "' . $proj['title'] . '" dimulai ' . $proj['hired_label']
        . ' dengan durasi ' . $proj['duration'] . '. Tenggat pengerjaan: '
        . $proj['deadline'] . ' (sisa ' . (int)$proj['days_left'] . ' hari). Buka Proyek Aktif untuk melihat countdown.';
}

function gig_demo_active_project_by_id(string $vacancyOrContractId): ?array
{
    foreach (gig_demo_active_projects() as $proj) {
        if ($proj['id'] === $vacancyOrContractId || $proj['contract_id'] === $vacancyOrContractId) {
            return $proj;
        }
    }
    return null;
}

function gig_demo_soonest_active_project(): ?array
{
    $list = gig_demo_active_projects();
    usort($list, static fn($a, $b) => strcmp((string)$a['deadline_iso'], (string)$b['deadline_iso']));
    return $list[0] ?? null;
}

function gig_worker_matches_project(array $proj, string $username, string $email = ''): bool
{
    $wid = strtolower(trim((string)($proj['worker_id'] ?? '')));
    $u = strtolower(trim($username));
    $e = strtolower(trim($email));
    if ($wid !== '' && ($wid === $u || ($e !== '' && $wid === $e))) {
        return true;
    }
    $legacyWorkerIds = ['theressaz@pasker.id', 'tessa', 'theressa zaratrusha'];
    if (in_array($wid, $legacyWorkerIds, true)) {
        if (in_array($u, $legacyWorkerIds, true) || ($e !== '' && in_array($e, $legacyWorkerIds, true))) {
            return true;
        }
        if (str_contains($u, 'theressa') || str_contains($e, 'theressaz')) {
            return true;
        }
    }
    return false;
}

function gig_worker_soonest_active_project(string $username, string $email = ''): ?array
{
    $list = gig_worker_ongoing_active_projects($username, $email);
    if ($list === []) {
        return null;
    }
    usort($list, static fn($a, $b) => strcmp((string)$a['deadline_iso'], (string)$b['deadline_iso']));
    return $list[0];
}

function gig_worker_active_project_limit(): int
{
    return 2;
}

function gig_worker_project_completions_map(): array
{
    $completed = [];
    $pdo = function_exists('gig_db') ? gig_db() : null;
    if ($pdo !== null) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `employer_reviews` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `contract_id` VARCHAR(50) NOT NULL,
                    `worker_id` VARCHAR(50) NOT NULL,
                    `employer_username` VARCHAR(100) NOT NULL,
                    `project_title` VARCHAR(255) NOT NULL,
                    `overall_rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
                    `comment` TEXT NOT NULL,
                    `badges` TEXT NOT NULL DEFAULT '',
                    `recommend_employer` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `uniq_contract_worker` (`contract_id`, `worker_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $stmt = $pdo->query("
                SELECT pr.`contract_id`
                FROM `project_reviews` pr
                INNER JOIN `employer_reviews` er ON er.`contract_id` = pr.`contract_id`
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $cid) {
                $completed[(string)$cid] = true;
            }
        } catch (Throwable) {
            // fall back to session
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE
        && isset($_SESSION['project_review_status'])
        && is_array($_SESSION['project_review_status'])) {
        foreach ($_SESSION['project_review_status'] as $cid => $st) {
            if (!is_array($st)) {
                continue;
            }
            if (!empty($st['employer_reviewed']) && !empty($st['worker_reviewed'])) {
                $completed[(string)$cid] = true;
            }
        }
    }
    return $completed;
}

/** Active contracts for this worker that are not marked complete (matches Proyek Aktif). */
function gig_worker_ongoing_active_projects(string $username, string $email = ''): array
{
    $completed = gig_worker_project_completions_map();
    $out = [];
    foreach (gig_demo_active_projects() as $proj) {
        if (!gig_worker_matches_project($proj, $username, $email)) {
            continue;
        }
        $cId = (string)($proj['contract_id'] ?? '');
        if ($cId !== '' && isset($completed[$cId])) {
            continue;
        }
        // Expired unfinished projects are history-only ("Tidak Selesai"), not active.
        if (!empty($proj['is_expired'])) {
            continue;
        }
        $out[] = $proj;
    }
    return array_slice($out, 0, gig_worker_active_project_limit());
}

function gig_worker_active_project_count(string $username, string $email = ''): int
{
    return count(gig_worker_ongoing_active_projects($username, $email));
}

function gig_worker_active_partner_count(string $username, string $email = ''): int
{
    $employers = [];
    foreach (gig_worker_ongoing_active_projects($username, $email) as $proj) {
        $emp = trim((string)($proj['employer'] ?? ''));
        if ($emp !== '') {
            $employers[$emp] = true;
        }
    }
    return count($employers);
}
