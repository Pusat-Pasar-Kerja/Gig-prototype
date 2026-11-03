<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION["username"])) {
    header("Location: welcome-screen.php");
    exit;
}
$username = $_SESSION["username"];
$userRole = $_SESSION["role"] ?? 'employer';
$isWorker = ($userRole === 'worker') || (isset($_GET['from']) && $_GET['from'] === 'worker');

if ($isWorker) {
    $cId = urlencode((string)($_GET['contract'] ?? ''));
    header("Location: worker-rating-employer.php?contract=" . $cId);
    exit;
}

require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/project-history.php';
require_once __DIR__ . '/includes/project-schedule.php';

/**
 * Store worker -> employer ratings separately so they don't overwrite
 * employer -> worker reputation data.
 */
if (!function_exists('gig_ensure_employer_reviews_table')) {
    function gig_ensure_employer_reviews_table(PDO $pdo): void
    {
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
    }
}

// ── Active contracts ─────────────────────────────────────────────────────────
$activeContracts = [
    'CTR-GIG-2026-0811' => [
        'id'          => 'CTR-GIG-2026-0811',
        'title'       => 'Redesign UI/UX Dashboard Prototype KarirHub',
        'workerId'    => 'tessa',
        'workerName'  => 'Theressa Zaratrusha',
        'workerRole'  => 'Lead UI/UX Designer',
        'workerAvatar'=> 'https://api.dicebear.com/9.x/notionists/svg?seed=Theressa&backgroundColor=dbeafe',
        'duration'    => '3 Minggu',
        'budget'      => 'Rp 8.000.000',
        'deliverables'=> '12 Layar Prototype Interaktif Figma, UI Kit Design System, User Testing Report',
    ],
    'CTR-GIG-2026-0819' => [
        'id'          => 'CTR-GIG-2026-0819',
        'title'       => 'Integrasi REST API Modul Notifikasi SMS & WhatsApp',
        'workerId'    => 'rian',
        'workerName'  => 'Rian Ardiansyah',
        'workerRole'  => 'Backend API Developer',
        'workerAvatar'=> 'https://api.dicebear.com/9.x/notionists/svg?seed=Rian&backgroundColor=cffafe',
        'duration'    => '2 Minggu',
        'budget'      => 'Rp 6.000.000',
        'deliverables'=> 'Webhook Handler, Dokumentasi Postman, Stress Testing k6 Report',
    ],
];

$contractId  = trim((string)($_GET['contract'] ?? 'CTR-GIG-2026-0811'));
$projectData = gig_demo_active_project_by_id($contractId) ?? ($activeContracts[$contractId] ?? reset($activeContracts));
$contractKey = (string)($projectData['contract_id'] ?? $projectData['id']);
$completionState = gig_project_completion_status($contractKey);
$canSubmitReview = !empty($completionState['both_confirmed']);

$workerId = trim((string)($_GET['worker'] ?? ($projectData['workerId'] ?? ($projectData['worker_id'] ?? 'tessa'))));
$worker   = ($workerId !== '') ? gig_find_worker($workerId) : null;
if (!$worker) {
    $worker = [
        'id'     => $workerId ?: 'tessa',
        'name'   => (string)($projectData['workerName'] ?? ($projectData['worker_name'] ?? 'Theressa Zaratrusha')),
        'title'  => (string)($projectData['workerRole'] ?? ($projectData['worker_role'] ?? 'Lead UI/UX Designer')),
        'avatar' => (string)($projectData['workerAvatar'] ?? 'https://api.dicebear.com/9.x/notionists/svg?seed=Theressa&backgroundColor=dbeafe'),
    ];
}

$pdo          = gig_db();
$submitted    = false;
$errorMessage = '';

// ── Handle UNDO (delete rating) ──────────────────────────────────────────────
if (isset($_GET['undo']) && $_GET['undo'] === '1') {
    $undoContract = trim((string)($_GET['contract'] ?? ''));
    if ($undoContract !== '' && $pdo !== null) {
        if ($isWorker) {
            gig_ensure_employer_reviews_table($pdo);
            $stmt = $pdo->prepare("DELETE FROM `employer_reviews` WHERE `contract_id` = :cid AND `worker_id` = :wid");
            $stmt->execute([':cid' => $undoContract, ':wid' => (string)($worker['id'] ?? $username)]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM `project_reviews` WHERE `contract_id` = :cid");
            $stmt->execute([':cid' => $undoContract]);
        }
        $stmt = $pdo->prepare("DELETE FROM `project_completions` WHERE `contract_id` = :cid");
        $stmt->execute([':cid' => $undoContract]);
    }
    // Also clear from session cache
    unset($_SESSION['completed_projects'][$undoContract]);
    if (isset($_SESSION['project_review_status'][$undoContract]) && is_array($_SESSION['project_review_status'][$undoContract])) {
        if ($isWorker) {
            $_SESSION['project_review_status'][$undoContract]['worker_reviewed'] = false;
        } else {
            $_SESSION['project_review_status'][$undoContract]['employer_reviewed'] = false;
        }
        if (empty($_SESSION['project_review_status'][$undoContract]['worker_reviewed'])
            && empty($_SESSION['project_review_status'][$undoContract]['employer_reviewed'])) {
            unset($_SESSION['project_review_status'][$undoContract]);
        }
    }
    if (isset($_SESSION['custom_reviews'])) {
        foreach ($_SESSION['custom_reviews'] as $wId => &$revs) {
            $revs = array_values(array_filter($revs, fn($r) => ($r['contractId'] ?? '') !== $undoContract));
        }
        unset($revs);
    }
    if (isset($_SESSION['worker_employer_reviews'][$undoContract])) {
        unset($_SESSION['worker_employer_reviews'][$undoContract]);
    }
    header('Location: ' . ($isWorker ? 'worker-tugas.php' : 'employer-proyek-aktif.php'));
    exit;
}

// ── Handle SUBMIT ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $overallRating       = min(5, max(1, (int)($_POST['overall_rating']      ?? 5)));
    $ratingQuality       = $overallRating;
    $ratingCommunication = $overallRating;
    $ratingTimeliness    = $overallRating;

    $comment        = trim((string)($_POST['comment'] ?? ''));
    $selectedBadges = is_array($_POST['badges'] ?? null) ? $_POST['badges'] : [];
    $recommend      = !empty($_POST['recommend_worker']) || !empty($_POST['recommend_employer']);
    $confirmDone    = !empty($_POST['confirm_deliverables']);
    $confirmGiven   = !empty($_POST['confirm_given_deliverables']);

    if (!$canSubmitReview) {
        $errorMessage = 'Ulasan belum bisa dikirim. Kedua pihak harus mengonfirmasi bahwa proyek selesai terlebih dahulu.';
    } elseif ($comment === '') {
        $errorMessage = 'Mohon tuliskan ulasan atau testimoni untuk mitra proyek.';
    } elseif (mb_strlen($comment) < 100) {
        $errorMessage = 'Ulasan wajib diisi minimal 100 karakter sesuai standar evaluasi KarirHub (saat ini ' . mb_strlen($comment) . ' karakter).';
    } else {
        $badgesJson   = implode('||', $selectedBadges);
        $todayDate    = date('Y-m-d');

        if ($pdo !== null) {
            if ($isWorker) {
                gig_ensure_employer_reviews_table($pdo);
                $stmt = $pdo->prepare("
                    INSERT INTO `employer_reviews`
                        (`contract_id`, `worker_id`, `employer_username`, `project_title`,
                         `overall_rating`, `comment`, `badges`, `recommend_employer`)
                    VALUES
                        (:cid, :wid, :emp, :title,
                         :overall, :comment, :badges, :rec)
                    ON DUPLICATE KEY UPDATE
                        `overall_rating`      = VALUES(`overall_rating`),
                        `comment`             = VALUES(`comment`),
                        `badges`              = VALUES(`badges`),
                        `recommend_employer`  = VALUES(`recommend_employer`)
                ");
                $stmt->execute([
                    ':cid'     => (string)($projectData['contract_id'] ?? $projectData['id']),
                    ':wid'     => (string)($worker['id'] ?? $username),
                    ':emp'     => (string)($projectData['employer'] ?? 'PT Perusahaan'),
                    ':title'   => (string)($projectData['title'] ?? 'Proyek'),
                    ':overall' => $overallRating,
                    ':comment' => $comment,
                    ':badges'  => $badgesJson,
                    ':rec'     => $recommend ? 1 : 0,
                ]);
            } else {
                // Insert or update in project_reviews (UNIQUE on contract_id)
                $stmt = $pdo->prepare("
                    INSERT INTO `project_reviews`
                        (`contract_id`, `worker_id`, `employer_username`, `project_title`,
                         `overall_rating`, `rating_quality`, `rating_communication`, `rating_timeliness`,
                         `comment`, `badges`, `recommend_worker`)
                    VALUES
                        (:cid, :wid, :emp, :title,
                         :overall, :quality, :comm, :time,
                         :comment, :badges, :rec)
                    ON DUPLICATE KEY UPDATE
                        `overall_rating`       = VALUES(`overall_rating`),
                        `rating_quality`       = VALUES(`rating_quality`),
                        `rating_communication` = VALUES(`rating_communication`),
                        `rating_timeliness`    = VALUES(`rating_timeliness`),
                        `comment`              = VALUES(`comment`),
                        `badges`               = VALUES(`badges`),
                        `recommend_worker`     = VALUES(`recommend_worker`)
                ");
                $stmt->execute([
                    ':cid'     => (string)($projectData['contract_id'] ?? $projectData['id']),
                    ':wid'     => (string)$worker['id'],
                    ':emp'     => $username,
                    ':title'   => (string)$projectData['title'],
                    ':overall' => $overallRating,
                    ':quality' => $ratingQuality,
                    ':comm'    => $ratingCommunication,
                    ':time'    => $ratingTimeliness,
                    ':comment' => $comment,
                    ':badges'  => $badgesJson,
                    ':rec'     => $recommend ? 1 : 0,
                ]);
            }

            // Insert or update project_completions
            $stmt2 = $pdo->prepare("
                INSERT INTO `project_completions`
                    (`contract_id`, `worker_id`, `employer_username`, `rating_given`, `review_given`, `completed_date`)
                VALUES
                    (:cid, :wid, :emp, :rating, :review, :date)
                ON DUPLICATE KEY UPDATE
                    `rating_given`  = VALUES(`rating_given`),
                    `review_given`  = VALUES(`review_given`),
                    `completed_date`= VALUES(`completed_date`)
            ");
            $stmt2->execute([
                ':cid'    => $contractKey,
                ':wid'    => (string)($worker['id'] ?? $username),
                ':emp'    => (string)($projectData['employer'] ?? $username),
                ':rating' => $overallRating,
                ':review' => $comment,
                ':date'   => $todayDate,
            ]);

            gig_upsert_project_history([
                'contract_id' => (string)($projectData['contract_id'] ?? $projectData['id']),
                'worker_id' => (string)($worker['id'] ?? $username),
                'worker_name' => $worker['name'] ?? '',
                'worker_role' => $worker['title'] ?? '',
                'worker_avatar' => $worker['photo'] ?? '',
                'employer_username' => (string)($projectData['employer'] ?? ($isWorker ? 'PT ABC' : $username)),
                'project_title' => $projectData['title'],
                'status' => 'completed',
                'budget' => $projectData['budget'] ?? '',
                'duration' => $projectData['duration'] ?? '',
                'start_date' => '',
                'end_date' => date('d M Y'),
                'summary' => 'Proyek telah selesai dikerjakan, seluruh deliverable diterima dan pembayaran berhasil dituntaskan.',
            ]);
        }

        // Also cache in session for immediate page rendering
        if (!isset($_SESSION['completed_projects'])) $_SESSION['completed_projects'] = [];
        $_SESSION['completed_projects'][$contractKey] = [
            'status'        => 'Selesai',
            'statusCode'    => 'completed',
            'ratingGiven'   => $overallRating,
            'reviewGiven'   => $comment,
            'completedDate' => date('d M Y'),
        ];
        if (!isset($_SESSION['project_review_status']) || !is_array($_SESSION['project_review_status'])) {
            $_SESSION['project_review_status'] = [];
        }
        if (!isset($_SESSION['project_review_status'][$contractKey]) || !is_array($_SESSION['project_review_status'][$contractKey])) {
            $_SESSION['project_review_status'][$contractKey] = [
                'employer_reviewed' => false,
                'worker_reviewed' => false,
            ];
        }
        if ($isWorker) {
            $_SESSION['project_review_status'][$contractKey]['worker_reviewed'] = true;
        } else {
            $_SESSION['project_review_status'][$contractKey]['employer_reviewed'] = true;
        }
        $reviewStatus = gig_project_review_status($contractKey);
        if (!empty($reviewStatus['both_reviewed'])) {
            $_SESSION['completed_projects'][$contractKey]['bothReviewed'] = true;
        }
        if ($isWorker) {
            if (!isset($_SESSION['worker_employer_reviews'])) {
                $_SESSION['worker_employer_reviews'] = [];
            }
            $_SESSION['worker_employer_reviews'][$contractKey] = [
                'overall_rating' => $overallRating,
                'comment' => $comment,
            ];
        }

        $submitted = true;
    }
}

// ── Check if this contract is already rated (from DB first, then session) ────
$alreadyRated = false;
$existingRating = null;
if ($pdo !== null) {
    if ($isWorker) {
        gig_ensure_employer_reviews_table($pdo);
        $stmt = $pdo->prepare("SELECT * FROM `employer_reviews` WHERE `contract_id` = :cid AND `worker_id` = :wid LIMIT 1");
        $stmt->execute([
            ':cid' => (string)($projectData['contract_id'] ?? $projectData['id']),
            ':wid' => (string)($worker['id'] ?? $username),
        ]);
        $existingRating = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } else {
        $stmt = $pdo->prepare("SELECT * FROM `project_reviews` WHERE `contract_id` = :cid LIMIT 1");
        $stmt->execute([':cid' => (string)($projectData['contract_id'] ?? $projectData['id'])]);
        $existingRating = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    $alreadyRated   = ($existingRating !== null);
} elseif (
    isset($_SESSION['completed_projects'][$contractKey])
    || ($isWorker && isset($_SESSION['worker_employer_reviews'][$contractKey]))
) {
    $alreadyRated = true;
}

$targetName = $isWorker
    ? (string)($projectData['employer'] ?? 'Pemberi Kerja')
    : (string)($worker['name'] ?? 'Gig Worker');
$targetRole = $isWorker
    ? (string)($projectData['employer_category'] ?? 'Pemberi Kerja')
    : (string)($worker['title'] ?? 'Gig Worker');
$targetTitle = $isWorker ? 'Pemberi Kerja yang Dinilai' : 'Pekerja Gig yang Dinilai';
$targetLink = $isWorker
    ? 'employer-profile.php?employer=' . urlencode($targetName)
    : 'worker-profile.php?id=' . urlencode((string)($worker['id'] ?? '')) . '&active=1';
$targetLinkText = $isWorker ? 'Lihat Profil Perusahaan ↗' : 'Lihat Profil Lengkap & Portofolio ↗';
$deliverablesReceivedText = trim((string)($projectData['deliverables'] ?? ''));
if ($deliverablesReceivedText === '') {
    $deliverableStatus = trim((string)($projectData['deliverable_status'] ?? ''));
    $deliverableNote = trim((string)($projectData['deliverable_note'] ?? ''));
    $deliverablesReceivedText = trim($deliverableStatus . ($deliverableNote !== '' ? ' — ' . $deliverableNote : ''));
}
if ($deliverablesReceivedText === '') {
    $deliverablesReceivedText = 'Deliverable proyek telah diserahkan sesuai ruang lingkup pekerjaan.';
}
$undoLink = 'employer-rating-worker.php?contract=' . urlencode($contractKey)
    . '&worker=' . urlencode((string)($worker['id'] ?? ''))
    . ($isWorker ? '&from=worker' : '')
    . '&undo=1';

$pageTitle          = 'Beri Ulasan & Selesaikan Proyek';
$pageKey            = $isWorker ? 'tugas' : 'aktif';
$breadcrumbCurrent  = 'Beri Ulasan';

if ($isWorker) {
    require __DIR__ . '/includes/worker-layout-start.php';
} else {
    require __DIR__ . '/includes/employer-layout-start.php';
}
?>

<div class="page-toolbar" style="margin-bottom: 20px;">
  <div>
    <h1>Selesaikan Proyek &amp; Berikan Penilaian</h1>
    <p style="font-size:0.86rem;color:var(--text-muted);margin-top:4px;">
      Konfirmasi penyelesaian pekerjaan dan berikan ulasan objektif bagi mitra kerja.
    </p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;">
    <a class="btn-action-sm" href="<?php echo $isWorker ? 'worker-tugas.php' : 'employer-proyek-aktif.php'; ?>" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12" 19 5 12 12 5"/></svg>
      Kembali ke Proyek Aktif
    </a>
    <?php if ($alreadyRated && !$submitted): ?>
      <a class="btn-action-sm" href="<?php echo htmlspecialchars($undoLink, ENT_QUOTES, 'UTF-8'); ?>"
         style="background:#fef2f2;border-color:#fecdd3;color:#b91c1c;text-decoration:none;"
         onclick="return confirm('Batalkan rating untuk proyek ini? Data penilaian akan dihapus permanen.')">
        🗑 Batalkan Rating
      </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($submitted || ($alreadyRated && !isset($_POST['submit_review']))): ?>
<?php
// Determine which rating to display
$displayRating  = $submitted
    ? (int)($_POST['overall_rating'] ?? 5)
    : ($existingRating ? (int)$existingRating['overall_rating'] : ($alreadyRated ? (int)($_SESSION['completed_projects'][$contractKey]['ratingGiven'] ?? 5) : 5));
$displayComment = $submitted
    ? (string)($_POST['comment'] ?? '')
    : ($existingRating ? $existingRating['comment'] : ($alreadyRated ? ($_SESSION['completed_projects'][$contractKey]['reviewGiven'] ?? '') : ''));
?>
  <!-- SUCCESS / ALREADY RATED STATE -->
  <div class="white-card" style="text-align:center;padding:48px 24px;border:2px solid #10b981;background:linear-gradient(to bottom,#ffffff,#f0fdf4);max-width:760px;margin:0 auto;">
    <div style="width:72px;height:72px;background:#10b981;color:#ffffff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.2rem;margin:0 auto 18px;box-shadow:0 8px 20px rgba(16,185,129,0.3);">✓</div>
    <h2 style="font-size:1.5rem;font-weight:800;color:#065f46;margin-bottom:8px;">Penilaian Berhasil Disimpan!</h2>
    <p style="font-size:0.95rem;color:#047857;max-width:540px;margin:0 auto 24px;line-height:1.5;">
      Rating untuk <strong><?php echo htmlspecialchars($targetName, ENT_QUOTES, 'UTF-8'); ?></strong> telah disimpan.
    </p>

    <div style="background:#ffffff;border:1px solid #bbf7d0;border-radius:12px;padding:16px 20px;max-width:500px;margin:0 auto 28px;text-align:left;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
        <span style="font-size:0.8rem;color:var(--text-muted);font-weight:600;">Rating Diberikan:</span>
        <span style="font-size:1.1rem;color:#f59e0b;font-weight:800;"><?php echo gig_stars($displayRating); ?> (<?php echo $displayRating; ?>/5)</span>
      </div>
      <div style="font-size:0.86rem;color:var(--text-dark);font-style:italic;line-height:1.4;">"<?php echo htmlspecialchars($displayComment, ENT_QUOTES, 'UTF-8'); ?>"</div>
    </div>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="<?php echo htmlspecialchars($targetLink, ENT_QUOTES, 'UTF-8'); ?>" class="btn-create-post" style="background:#2563eb;text-decoration:none;padding:10px 24px;font-size:0.9rem;">
        Lihat Profil <?php echo htmlspecialchars($targetName, ENT_QUOTES, 'UTF-8'); ?> →
      </a>
      <a href="<?php echo $isWorker ? 'worker-riwayat.php' : 'employer-riwayat-proyek.php'; ?>" class="btn-create-post" style="background:#059669;border-color:#047857;text-decoration:none;padding:10px 24px;font-size:0.9rem;">
        Riwayat Proyek →
      </a>
      <a href="<?php echo htmlspecialchars($undoLink, ENT_QUOTES, 'UTF-8'); ?>"
         class="filter-btn-pill" style="text-decoration:none;padding:10px 20px;font-size:0.9rem;border-color:#fecdd3;color:#b91c1c;background:#fff1f2;"
         onclick="return confirm('Batalkan rating ini? Data penilaian akan dihapus permanen.')">
        🗑 Batalkan Rating
      </a>
    </div>
  </div>
<?php else: ?>
  <?php if (!$canSubmitReview): ?>
    <div class="white-card" style="max-width:760px;margin:0 auto;padding:34px 24px;border:1px solid #bfdbfe;background:#eff6ff;">
      <h2 style="margin:0 0 8px 0;font-size:1.2rem;font-weight:800;color:#1e3a8a;">Menunggu Konfirmasi Selesai dari Kedua Pihak</h2>
      <p style="font-size:0.9rem;color:#1e40af;line-height:1.55;margin-bottom:14px;">
        Ulasan baru dapat diberikan setelah <strong>Pemberi Kerja</strong> dan <strong>Gig Worker</strong> sama-sama menekan tombol konfirmasi proyek selesai di halaman Proyek Aktif masing-masing.
      </p>
      <div style="font-size:0.84rem;color:#334155;background:#ffffff;border:1px solid #dbeafe;border-radius:10px;padding:12px 14px;margin-bottom:16px;">
        Status saat ini:
        <ul style="margin:8px 0 0 18px;padding:0;line-height:1.6;">
          <li>Pemberi Kerja: <?php echo !empty($completionState['employer_confirmed']) ? 'Sudah konfirmasi' : 'Belum konfirmasi'; ?></li>
          <li>Gig Worker: <?php echo !empty($completionState['worker_confirmed']) ? 'Sudah konfirmasi' : 'Belum konfirmasi'; ?></li>
        </ul>
      </div>
      <a href="<?php echo $isWorker ? 'worker-tugas.php' : 'employer-proyek-aktif.php'; ?>" class="btn-action-sm" style="text-decoration:none;display:inline-flex;">
        Kembali ke Proyek Aktif
      </a>
    </div>
  <?php else: ?>

  <?php if ($errorMessage !== ''): ?>
    <div style="background:#fef2f2;border:1px solid #fecdd3;color:#991b1b;padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:0.88rem;display:flex;align-items:center;gap:10px;">
      <span style="font-size:1.2rem;">⚠️</span>
      <span><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
  <?php endif; ?>

  <?php if ($pdo === null): ?>
    <div style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:0.86rem;display:flex;align-items:center;gap:10px;">
      <span>⚠️</span>
      <span><strong>Database offline.</strong> Rating akan disimpan sementara di sesi browser dan tidak akan tersimpan permanen.</span>
    </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

    <!-- MAIN FORM -->
    <form method="post" action="" class="white-card" style="padding:28px;">
      <div style="padding-bottom:18px;border-bottom:1px solid var(--border-subtle);margin-bottom:24px;">
        <h2 style="font-size:1.2rem;font-weight:800;color:var(--text-main);display:flex;align-items:center;gap:8px;">
          <span>⭐</span> Form Evaluasi &amp; Rating Mitra Gig
        </h2>
        <p style="font-size:0.84rem;color:var(--text-muted);margin-top:4px;">
          Penilaian Anda sangat berharga untuk membangun reputasi dan rekam jejak pekerja gig di ekosistem KarirHub.
        </p>
      </div>

      <!-- 1. OVERALL STAR RATING -->
      <div style="margin-bottom:28px;text-align:center;background:#f8fafc;border:1px solid var(--border-subtle);border-radius:14px;padding:24px 16px;">
        <label style="display:block;font-size:0.95rem;font-weight:800;color:var(--text-main);margin-bottom:6px;">Rating Keseluruhan <span style="color:#ef4444;">*</span></label>
        <span style="font-size:0.8rem;color:var(--text-muted);display:block;margin-bottom:14px;">Klik bintang untuk memberikan skor (1–5)</span>
        <input type="hidden" name="overall_rating" id="overall_rating" value="5" />
        <div id="starContainer" style="display:inline-flex;gap:8px;font-size:2.6rem;cursor:pointer;user-select:none;color:#f59e0b;">
          <span class="star-item" data-val="1">★</span>
          <span class="star-item" data-val="2">★</span>
          <span class="star-item" data-val="3">★</span>
          <span class="star-item" data-val="4">★</span>
          <span class="star-item" data-val="5">★</span>
        </div>
        <div id="ratingLabel" style="font-size:0.92rem;font-weight:700;color:#059669;margin-top:10px;">★★★★★ 5.0 · Sangat Memuaskan (Luar Biasa)</div>
      </div>

      <!-- 2. BADGES -->
      <div style="margin-bottom:28px;">
        <label style="display:block;font-size:0.92rem;font-weight:800;color:var(--text-main);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em;">Lencana Apresiasi (Opsional)</label>
        <span style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:12px;">Pilih apresiasi yang mencerminkan kelebihan utama mitra:</span>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <?php
          $badges = [
              '✨ Sangat Direkomendasikan','⚡ Cepat Tanggap & Responsif',
              '🎯 Hasil Rapi & Teliti','💡 Solutif & Inisiatif Tinggi',
              '🤝 Kerjasama Luar Biasa','🎨 Kreativitas Tinggi',
              '🏆 Profesional & Berintegritas',
          ];
          foreach ($badges as $b): ?>
            <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border:1px solid #cbd5e1;padding:6px 12px;border-radius:9999px;font-size:0.8rem;font-weight:600;color:#334155;transition:all 0.15s;">
              <input type="checkbox" name="badges[]" value="<?php echo htmlspecialchars($b,ENT_QUOTES,'UTF-8'); ?>" style="accent-color:#2563eb;"
                onchange="this.parentElement.style.background=this.checked?'#eff6ff':'#f1f5f9';this.parentElement.style.borderColor=this.checked?'#3b82f6':'#cbd5e1';this.parentElement.style.color=this.checked?'#1d4ed8':'#334155';" />
              <?php echo htmlspecialchars($b,ENT_QUOTES,'UTF-8'); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 3. TESTIMONIAL -->
      <div style="margin-bottom:28px;">
        <label for="comment" style="display:block;font-size:0.92rem;font-weight:800;color:var(--text-main);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em;">
          Ulasan &amp; Testimoni <span style="color:#ef4444;">*</span>
        </label>
        <textarea id="comment" name="comment" rows="4" required minlength="100" placeholder="Tuliskan pengalaman Anda bekerja bersama mitra ini (minimal 100 karakter)..." style="width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:8px;font-size:0.88rem;line-height:1.5;outline:none;font-family:inherit;resize:vertical;"></textarea>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;font-size:0.75rem;">
          <span style="color:#64748b;">Wajib diisi minimal 100 karakter</span>
          <span id="comment-char-count" style="color:#ef4444;font-weight:700;">0/100 karakter</span>
        </div>
      </div>

      <div style="display:flex;gap:12px;justify-content:flex-end;align-items:center;margin-top:20px;">
        <a href="<?php echo $isWorker ? 'worker-tugas.php' : 'employer-proyek-aktif.php'; ?>" class="filter-btn-pill" style="text-decoration:none;padding:10px 18px;font-size:0.88rem;border:1px solid #cbd5e1;border-radius:8px;color:#475569;">Batal</a>
        <button type="submit" id="submit_review_btn" name="submit_review" value="1" class="btn-create-post" disabled aria-disabled="true" style="padding:10px 24px;font-size:0.9rem;background:#cbd5e1;border:none;color:#64748b;cursor:not-allowed;opacity:0.7;border-radius:8px;font-weight:700;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Kirim Ulasan
        </button>
      </div>
    </form>

    <!-- SIDEBAR -->
    <aside style="display:flex;flex-direction:column;gap:18px;">
      <div class="white-card" style="padding:20px;">
        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:12px;"><?php echo htmlspecialchars($targetTitle, ENT_QUOTES, 'UTF-8'); ?></div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
          <div style="width:50px;height:50px;border-radius:50%;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.2rem;flex-shrink:0;"><?php echo htmlspecialchars(strtoupper(substr($targetName, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
          <div>
            <h3 style="font-size:1.05rem;font-weight:800;margin:0;color:var(--text-main);"><?php echo htmlspecialchars($targetName,ENT_QUOTES,'UTF-8'); ?></h3>
            <span style="font-size:0.78rem;color:var(--text-muted);"><?php echo htmlspecialchars($targetRole,ENT_QUOTES,'UTF-8'); ?></span>
          </div>
        </div>
        <?php if (!$isWorker): ?>
          <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#f8fafc;border-radius:8px;font-size:0.8rem;margin-bottom:14px;">
            <span style="color:#f59e0b;font-weight:800;">★ <?php echo number_format((float)$worker['rating'],1); ?></span>
            <span style="color:var(--text-muted);">· <?php echo (int)$worker['reviews_count']; ?> ulasan sebelumnya</span>
          </div>
        <?php endif; ?>
        <a href="<?php echo htmlspecialchars($targetLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" style="font-size:0.8rem;color:var(--primary-blue);text-decoration:none;font-weight:700;"><?php echo htmlspecialchars($targetLinkText, ENT_QUOTES, 'UTF-8'); ?></a>
      </div>

      <div class="white-card" style="padding:20px;">
        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:12px;">Detail Proyek</div>
        <div style="margin-bottom:12px;">
          <span style="font-size:0.74rem;color:var(--text-muted);display:block;">Judul Proyek:</span>
          <strong style="font-size:0.88rem;color:var(--text-main);line-height:1.4;"><?php echo htmlspecialchars($projectData['title'],ENT_QUOTES,'UTF-8'); ?></strong>
        </div>
        <div style="padding-top:10px;border-top:1px solid #f1f5f9;margin-bottom:12px;">
          <div>
            <span style="font-size:0.72rem;color:var(--text-muted);display:block;">Nilai Proyek:</span>
            <span style="font-size:0.85rem;font-weight:800;color:var(--primary-blue);"><?php echo htmlspecialchars($projectData['budget'],ENT_QUOTES,'UTF-8'); ?></span>
          </div>
        </div>
        <div style="padding-top:10px;border-top:1px solid #f1f5f9;">
          <span style="font-size:0.72rem;color:var(--text-muted);display:block;margin-bottom:4px;">Deliverable yang Diterima:</span>
          <p style="font-size:0.78rem;color:#334155;background:#f8fafc;padding:8px 10px;border-radius:6px;margin:0;"><?php echo htmlspecialchars($deliverablesReceivedText,ENT_QUOTES,'UTF-8'); ?></p>
        </div>
      </div>
    </aside>
  </div>

  <script>
    (function() {
      const starContainer = document.getElementById('starContainer');
      const hiddenInput   = document.getElementById('overall_rating');
      const ratingLabel   = document.getElementById('ratingLabel');
      const confirmGivenDeliverables = document.getElementById('confirm_given_deliverables');
      const confirmDeliverables = document.getElementById('confirm_deliverables');
      const submitBtn = document.getElementById('submit_review_btn');

      if (starContainer && hiddenInput && ratingLabel) {
        const stars = starContainer.querySelectorAll('.star-item');
        const labels = {
          1: '★☆☆☆☆ 1.0 · Buruk (Tidak Memuaskan)',
          2: '★★☆☆☆ 2.0 · Kurang (Perlu Perbaikan)',
          3: '★★★☆☆ 3.0 · Cukup (Sesuai Standar)',
          4: '★★★★☆ 4.0 · Baik (Memuaskan)',
          5: '★★★★★ 5.0 · Sangat Memuaskan (Luar Biasa)',
        };

        function updateStars(val) {
          val = parseInt(val, 10);
          stars.forEach(function(star, idx) {
            star.innerText = idx < val ? '★' : '☆';
            star.style.color = idx < val ? '#f59e0b' : '#cbd5e1';
          });
          ratingLabel.innerText = labels[val] || '';
        }

        stars.forEach(function(star) {
          star.addEventListener('click', function() {
            hiddenInput.value = this.getAttribute('data-val');
            updateStars(hiddenInput.value);
          });
          star.addEventListener('mouseenter', function() {
            updateStars(this.getAttribute('data-val'));
          });
        });
        starContainer.addEventListener('mouseleave', function() {
          updateStars(hiddenInput.value);
        });

        updateStars(hiddenInput.value);
      }

      const commentInput = document.getElementById('comment');
      const submitBtn    = document.getElementById('submit_review_btn');

      function validateForm() {
        if (!commentInput || !submitBtn) return;
        const len = commentInput.value.trim().length;
        const countEl = document.getElementById('comment-char-count');
        if (countEl) {
          countEl.innerText = len + '/100 karakter';
          countEl.style.color = len >= 100 ? '#10b981' : '#ef4444';
        }
        const isValid = len >= 100;
        submitBtn.disabled = !isValid;
        submitBtn.setAttribute('aria-disabled', isValid ? 'false' : 'true');
        if (isValid) {
          submitBtn.style.background = 'linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%)';
          submitBtn.style.color = '#ffffff';
          submitBtn.style.cursor = 'pointer';
          submitBtn.style.opacity = '1';
        } else {
          submitBtn.style.background = '#cbd5e1';
          submitBtn.style.color = '#64748b';
          submitBtn.style.cursor = 'not-allowed';
          submitBtn.style.opacity = '0.7';
        }
      }

      if (commentInput) {
        commentInput.addEventListener('input', validateForm);
        commentInput.addEventListener('change', validateForm);
        validateForm();
      }
    })();
  </script>
  <?php endif; ?>

<?php endif; ?>

<?php 
if ($isWorker) {
    require __DIR__ . '/includes/worker-layout-end.php';
} else {
    require __DIR__ . '/includes/employer-layout-end.php';
}
?>
