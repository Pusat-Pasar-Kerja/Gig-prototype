<?php
/**
 * ENDPOINT API: Progressive Profiling (Profile Completion Nudge)
 * File: get_nudge.php
 *
 * Logika Utama:
 * 1. Mengambil ID user yang sedang login dari $_SESSION['user_id'] (beserta sinkronisasi sesi SIAPKerja).
 * 2. Mengecek seluruh kolom profil user pada tabel `users` (yang terhubung langsung dengan `profil.php`).
 * 3. Mengambil daftar pertanyaan aktif dari tabel `profiling_questions` + `profiling_categories`.
 * 4. Memisahkan pertanyaan berdasarkan kondisi field di profil user:
 *    - Prioritas 1 (Field Kosong / `$emptyCandidates`): Jika masih ada field di profil user yang kosong (NULL / ""),
 *      maka sistem WAJIB menanyakan `question_new` untuk melengkapi field kosong tersebut terlebih dahulu.
 *    - Prioritas 2 (Verifikasi Data / `$filledCandidates`): Jika seluruh field di profil user sudah terisi,
 *      barulah sistem menanyakan `question_verify` untuk memverifikasi keakuratan data yang sudah ada.
 * 5. Mengembalikan maksimal 1 pertanyaan acak dalam bentuk JSON untuk dirender oleh frontend JavaScript.
 */
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/progressive-profiling.php';

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
    $allowedColumnsMap = gig_profiling_allowed_columns();
    $allowedColumns    = array_keys($allowedColumnsMap);

    // =========================================================================
    // 1. AMBIL ID USER YANG SEDANG LOGIN ($_SESSION['user_id'])
    // =========================================================================
    $userId = gig_profiling_resolve_user_id($pdo);
    if (!empty($_GET['user_id']) && ctype_digit((string)$_GET['user_id'])) {
        $userId = (int)$_GET['user_id'];
    }

    // =========================================================================
    // 2. CEK KE TABEL `users` (DATA PROFIL USER SAAT INI)
    // =========================================================================
    $stmtUser = $pdo->prepare('SELECT * FROM `users` WHERE `id` = :user_id LIMIT 1');
    $stmtUser->execute([':user_id' => $userId]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        http_response_code(404);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Data user tidak ditemukan pada tabel users.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Cek kolom mana saja di profil yang masih kosong vs sudah terisi
    $filledCount  = 0;
    $emptyColumns = [];
    $totalColumns = count($allowedColumns);

    foreach ($allowedColumns as $colName) {
        $val = $userData[$colName] ?? null;
        if ($val !== null && trim((string)$val) !== '') {
            $filledCount++;
        } else {
            $emptyColumns[] = $colName;
        }
    }
    $completionPercent = $totalColumns > 0 ? (int)round(($filledCount / $totalColumns) * 100) : 100;

    // =========================================================================
    // 3. AMBIL PERTANYAAN AKTIF DARI `profiling_questions` + `profiling_categories`
    // =========================================================================
    $sqlQuestions = "
        SELECT
            q.`id`,
            q.`category_id`,
            c.`name` AS `category_name`,
            q.`target_column`,
            q.`question_new`,
            q.`question_verify`,
            q.`input_type`,
            q.`options_json`
        FROM `profiling_questions` q
        INNER JOIN `profiling_categories` c ON c.`id` = q.`category_id`
        WHERE q.`is_active` = 1
    ";
    $questions = $pdo->query($sqlQuestions)->fetchAll(PDO::FETCH_ASSOC);

    if (empty($questions)) {
        echo json_encode([
            'status'  => 'empty',
            'message' => 'Belum ada pertanyaan profiling yang aktif.',
            'data'    => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =========================================================================
    // 4. CEK FIELD KOSONG TERLEBIH DAHULU SEBELUM MEMBERIKAN PERTANYAAN
    // =========================================================================
    $excludeId        = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;
    $preferMode       = isset($_GET['mode']) ? strtolower(trim((string)$_GET['mode'])) : '';
    $emptyCandidates  = [];
    $verifyCandidates = [];

    foreach ($questions as $q) {
        $targetCol = (string)$q['target_column'];

        // Pastikan target_column ada di whitelist dan merupakan kolom di tabel `users`
        if (!in_array($targetCol, $allowedColumns, true) || !array_key_exists($targetCol, $userData)) {
            continue;
        }

        $rawValue = $userData[$targetCol];
        $isEmpty  = ($rawValue === null || trim((string)$rawValue) === '');

        $options = [];
        if (!empty($q['options_json'])) {
            $decodedOpts = json_decode((string)$q['options_json'], true);
            if (is_array($decodedOpts)) {
                $options = array_values($decodedOpts);
            }
        }

        if ($isEmpty) {
            // Field di profil user MASIH KOSONG -> masukkan ke daftar prioritas question_new
            $emptyCandidates[] = [
                'question_id'   => (int)$q['id'],
                'category_id'   => (int)$q['category_id'],
                'category_name' => (string)$q['category_name'],
                'target_column' => $targetCol,
                'field_label'   => $allowedColumnsMap[$targetCol] ?? $targetCol,
                'mode'          => 'new',
                'question_text' => (string)$q['question_new'],
                'current_value' => null,
                'input_type'    => (string)$q['input_type'],
                'options'       => $options,
            ];
        } else {
            // Field di profil user SUDAH ADA -> masukkan ke daftar question_verify
            $questionText = str_replace('{value}', (string)$rawValue, (string)$q['question_verify']);
            if (!str_contains((string)$q['question_verify'], '{value}')) {
                $questionText .= ' (Data saat ini: ' . (string)$rawValue . ')';
            }

            $verifyCandidates[] = [
                'question_id'   => (int)$q['id'],
                'category_id'   => (int)$q['category_id'],
                'category_name' => (string)$q['category_name'],
                'target_column' => $targetCol,
                'field_label'   => $allowedColumnsMap[$targetCol] ?? $targetCol,
                'mode'          => 'verify',
                'question_text' => $questionText,
                'current_value' => (string)$rawValue,
                'input_type'    => (string)$q['input_type'],
                'options'       => $options,
            ];
        }
    }

    // Prioritaskan pertanyaan untuk field yang masih kosong (mode: 'new') terlebih dahulu!
    // Jika semua field sudah terisi (atau diminta mode=verify), baru ambil dari $verifyCandidates.
    if ($preferMode === 'verify' && !empty($verifyCandidates)) {
        $activePool = $verifyCandidates;
    } elseif ($preferMode === 'new' && !empty($emptyCandidates)) {
        $activePool = $emptyCandidates;
    } elseif (!empty($emptyCandidates)) {
        $activePool = $emptyCandidates;
    } else {
        $activePool = $verifyCandidates;
    }

    if (empty($activePool)) {
        echo json_encode([
            'status'  => 'empty',
            'message' => 'Tidak ada pertanyaan yang cocok untuk ditampilkan.',
            'data'    => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Hindari mengulang pertanyaan yang persis sama saat tombol "Pertanyaan Lainnya" diklik bila kandidat > 1
    if ($excludeId > 0 && count($activePool) > 1) {
        $filtered = array_values(array_filter($activePool, static fn($item) => $item['question_id'] !== $excludeId));
        if (!empty($filtered)) {
            $activePool = $filtered;
        }
    }

    // =========================================================================
    // 5. EVALUASI JADWAL KEMUNCULAN (DEFAULT: CUKUP 1X SEHARI / SESUAI ADMIN)
    // =========================================================================
    $settings   = gig_profiling_get_settings($pdo);
    $freqModes  = gig_profiling_frequency_modes();
    $schedule   = gig_profiling_evaluate_schedule($userData, $settings);
    $trigger    = strtolower(trim((string)($_GET['trigger'] ?? '')));
    $isPreview  = !empty($_GET['preview']);

    // Jika user sudah mengisi pertanyaan hari ini (quota_reached = true),
    // maka tidak ada pertanyaan lagi untuk hari ini (pertanyaan baru akan muncul esok hari).
    if ($schedule['quota_reached'] && !$isPreview) {
        echo json_encode([
            'status'   => 'completed_today',
            'message'  => 'Anda sudah mengisi pertanyaan hari ini. Pertanyaan baru akan muncul esok hari.',
            'user_id'  => $userId,
            'user'     => [
                'name'                => (string)$userData['name'],
                'email'               => (string)$userData['email'],
                'filled_columns'      => $filledCount,
                'empty_columns_count' => count($emptyColumns),
                'empty_columns'       => $emptyColumns,
                'total_columns'       => $totalColumns,
                'completion_percent'  => $completionPercent,
            ],
            'schedule' => [
                'is_enabled'             => (int)$settings['is_enabled'] === 1,
                'frequency_mode'         => $settings['frequency_mode'],
                'frequency_label'        => $freqModes[$settings['frequency_mode']] ?? $settings['frequency_mode'],
                'interval_hours'         => (int)$settings['interval_hours'],
                'max_questions_per_show' => (int)$settings['max_questions_per_show'],
                'answered_today'         => (int)$schedule['answered_today'],
                'quota_reached'          => true,
                'show_fab_button'        => false,
                'should_auto_show'       => false,
                'reason'                 => $schedule['reason'],
                'next_available_label'   => $schedule['next_available_label'],
            ],
            'data'     => null,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    if ($trigger === 'auto' && $schedule['should_auto_show']) {
        $stmtMarkShown = $pdo->prepare("
            UPDATE `users`
            SET `nudge_shown_count_today` = IF(DATE(`last_nudge_shown_at`) = CURDATE(), `nudge_shown_count_today` + 1, 1),
                `last_nudge_shown_at` = NOW()
            WHERE `id` = :id
        ");
        $stmtMarkShown->execute([':id' => $userId]);
        $_SESSION['pp_nudge_shown_session'] = true;
    }

    // =========================================================================
    // 6. PILIH MAKSIMAL 1 PERTANYAAN SECARA ACAK & RETURN JSON RESPONSE
    // =========================================================================
    $randomIndex   = array_rand($activePool);
    $selectedNudge = $activePool[$randomIndex];

    echo json_encode([
        'status'   => 'success',
        'user_id'  => $userId,
        'user'     => [
            'name'                => (string)$userData['name'],
            'email'               => (string)$userData['email'],
            'filled_columns'      => $filledCount,
            'empty_columns_count' => count($emptyColumns),
            'empty_columns'       => $emptyColumns,
            'total_columns'       => $totalColumns,
            'completion_percent'  => $completionPercent,
        ],
        'schedule' => [
            'is_enabled'             => (int)$settings['is_enabled'] === 1,
            'frequency_mode'         => $settings['frequency_mode'],
            'frequency_label'        => $freqModes[$settings['frequency_mode']] ?? $settings['frequency_mode'],
            'interval_hours'         => (int)$settings['interval_hours'],
            'max_questions_per_show' => (int)$settings['max_questions_per_show'],
            'answered_today'         => (int)$schedule['answered_today'],
            'quota_reached'          => (bool)$schedule['quota_reached'],
            'show_fab_button'        => (int)$settings['show_fab_button'] === 1,
            'should_auto_show'       => $schedule['should_auto_show'],
            'reason'                 => $schedule['reason'],
            'next_available_label'   => $schedule['next_available_label'],
        ],
        'data'     => $selectedNudge,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
