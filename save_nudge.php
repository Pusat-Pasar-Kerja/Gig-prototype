<?php
/**
 * ENDPOINT API: Menyimpan / Memverifikasi Jawaban Progressive Profiling
 * File: save_nudge.php
 *
 * Menyimpan jawaban ke tabel `users` dan menyinkronkannya dengan data profil
 * sehingga tampilan pada `profil.php` langsung ikut berubah.
 */
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/progressive-profiling.php';
require_once __DIR__ . '/includes/worker-profiles.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method Not Allowed. Gunakan POST.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = gig_db();
    if ($pdo === null) {
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Koneksi database MySQL tidak tersedia.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    gig_profiling_ensure_schema($pdo);
    $allowedColumns = gig_profiling_allowed_columns();

    // Terima payload baik dari JSON body maupun form-urlencoded
    $rawBody     = file_get_contents('php://input');
    $jsonPayload = json_decode((string)$rawBody, true);
    $input       = is_array($jsonPayload) ? $jsonPayload : $_POST;

    $userId       = gig_profiling_resolve_user_id($pdo);
    $targetColumn = trim((string)($input['target_column'] ?? ''));
    $actionType   = strtolower(trim((string)($input['action_type'] ?? 'save'))); // 'save' atau 'verify_ok'
    $answerValue  = trim((string)($input['answer_value'] ?? ''));

    // Validasi keamanan: pastikan target_column ada di dalam Whitelist
    if (!array_key_exists($targetColumn, $allowedColumns)) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Kolom target tidak valid atau tidak diizinkan.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Jika user memilih "Ya, Masih Benar" pada mode verifikasi
    if ($actionType === 'verify_ok') {
        $stmtTouch = $pdo->prepare("
            UPDATE `users`
            SET `nudge_answered_count_today` = IF(DATE(`last_nudge_answered_at`) = CURDATE(), `nudge_answered_count_today` + 1, 1),
                `last_nudge_answered_at` = NOW(),
                `last_nudge_shown_at` = COALESCE(`last_nudge_shown_at`, NOW()),
                `updated_at` = NOW()
            WHERE `id` = :id
        ");
        $stmtTouch->execute([':id' => $userId]);
        $_SESSION['pp_nudge_shown_session'] = true;
        $_SESSION['pp_nudge_answered_session_count'] = ((int)($_SESSION['pp_nudge_answered_session_count'] ?? 0)) + 1;

        $stmtAfter = $pdo->prepare('SELECT * FROM `users` WHERE `id` = :id LIMIT 1');
        $stmtAfter->execute([':id' => $userId]);
        $afterUser = $stmtAfter->fetch(PDO::FETCH_ASSOC) ?: [];
        $schedule  = gig_profiling_evaluate_schedule($afterUser, gig_profiling_get_settings($pdo));

        echo json_encode([
            'status'        => 'success',
            'action_type'   => 'verify_ok',
            'target_column' => $targetColumn,
            'quota_reached' => $schedule['quota_reached'],
            'message'       => $schedule['quota_reached']
                ? 'Terima kasih! Data Anda telah diverifikasi. Pertanyaan berikutnya akan muncul esok hari.'
                : 'Terima kasih! Data profil Anda telah berhasil diverifikasi.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($answerValue === '') {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Jawaban tidak boleh kosong.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 1. Update kolom terkait di tabel `users` menggunakan PDO Prepared Statement
    $sqlUpdate = "
        UPDATE `users`
        SET `{$targetColumn}` = :val,
            `nudge_answered_count_today` = IF(DATE(`last_nudge_answered_at`) = CURDATE(), `nudge_answered_count_today` + 1, 1),
            `last_nudge_answered_at` = NOW(),
            `last_nudge_shown_at` = COALESCE(`last_nudge_shown_at`, NOW()),
            `updated_at` = NOW()
        WHERE `id` = :id
    ";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':val' => $answerValue,
        ':id'  => $userId,
    ]);
    $_SESSION['pp_nudge_shown_session'] = true;
    $_SESSION['pp_nudge_answered_session_count'] = ((int)($_SESSION['pp_nudge_answered_session_count'] ?? 0)) + 1;

    // 2. Sinkronkan ke Session & tabel profil terkait agar seluruh halaman (termasuk profil.php) berubah
    $stmtUser = $pdo->prepare('SELECT * FROM `users` WHERE `id` = :id LIMIT 1');
    $stmtUser->execute([':id' => $userId]);
    $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: [];
    $schedule = gig_profiling_evaluate_schedule($userRow, gig_profiling_get_settings($pdo));

    $userEmail = (string)($userRow['email'] ?? ($_SESSION['siapkerja_email'] ?? 'theressaz@pasker.id'));
    $workerObj = gig_find_worker($userEmail);
    $workerId  = (string)($workerObj['id'] ?? 'tessa');

    if ($targetColumn === 'phone') {
        $_SESSION['siapkerja_phone'] = $answerValue;
        if ($workerObj !== null) {
            $contact = is_array($workerObj['contact'] ?? null) ? $workerObj['contact'] : [];
            $contact['wa'] = $answerValue;
            gig_save_worker_profile($workerId, ['contact' => $contact], $pdo);
        }
    } elseif ($targetColumn === 'domicile') {
        if ($workerObj !== null) {
            gig_save_worker_profile($workerId, ['location' => $answerValue], $pdo);
        }
    } elseif ($targetColumn === 'bio') {
        if ($workerObj !== null) {
            gig_save_worker_profile($workerId, ['proposal' => $answerValue], $pdo);
        }
    } elseif ($targetColumn === 'skills_text') {
        if ($workerObj !== null) {
            $normalizedSkills = gig_normalize_skills($answerValue);
            gig_save_worker_profile($workerId, ['skills' => $normalizedSkills], $pdo);
        }
    }

    echo json_encode([
        'status'        => 'success',
        'action_type'   => 'save',
        'quota_reached' => $schedule['quota_reached'],
        'message'       => $schedule['quota_reached']
            ? 'Terima kasih! Jawaban berhasil disimpan. Pertanyaan baru akan muncul esok hari.'
            : 'Profil berhasil diperbarui!',
        'target_column' => $targetColumn,
        'saved_value'   => $answerValue,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
