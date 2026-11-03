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

require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/project-history.php';
require_once __DIR__ . '/includes/project-schedule.php';

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
        'employer'    => 'PT ABC',
        'employer_category' => 'IT & Software Partner',
        'workerId'    => 'theressaz@pasker.id',
        'workerName'  => 'Theressa Zaratrusha',
        'duration'    => '3 Minggu',
        'budget'      => 'Rp 8.500.000',
        'deliverables'=> '12 Layar Prototype Interaktif Figma, UI Kit Design System, User Testing Report',
    ],
    'CTR-GIG-2026-0905' => [
        'id'          => 'CTR-GIG-2026-0905',
        'title'       => 'Desain UI/UX Mobile App E-Commerce UMKM',
        'employer'    => 'CV Visual Studio Creative',
        'employer_category' => 'Design & Creative Agency',
        'workerId'    => 'theressaz@pasker.id',
        'workerName'  => 'Theressa Zaratrusha',
        'duration'    => '2 Minggu',
        'budget'      => 'Rp 6.500.000',
        'deliverables'=> 'Wireframe & High Fidelity Screens Mobile App E-Commerce',
    ],
];

$contractId  = trim((string)($_GET['contract'] ?? 'CTR-GIG-2026-0811'));
$projectData = gig_demo_active_project_by_id($contractId) ?? ($activeContracts[$contractId] ?? reset($activeContracts));
$contractKey = (string)($projectData['contract_id'] ?? $projectData['id']);
$completionState = gig_project_completion_status($contractKey);
$canSubmitReview = !empty($completionState['both_confirmed']);

$workerId = trim((string)($_SESSION['username'] ?? 'theressaz@pasker.id'));
$worker   = gig_find_worker($workerId) ?? ['id' => $workerId, 'name' => 'Theressa Zaratrusha', 'title' => 'Lead UI/UX Designer'];

$pdo          = gig_db();
$submitted    = false;
$errorMessage = '';

// ── Handle UNDO (delete rating) ──────────────────────────────────────────────
if (isset($_GET['undo']) && $_GET['undo'] === '1') {
    $undoContract = trim((string)($_GET['contract'] ?? ''));
    if ($undoContract !== '' && $pdo !== null) {
        gig_ensure_employer_reviews_table($pdo);
        $stmt = $pdo->prepare("DELETE FROM `employer_reviews` WHERE `contract_id` = :cid AND `worker_id` = :wid");
        $stmt->execute([':cid' => $undoContract, ':wid' => (string)($worker['id'] ?? $username)]);
    }
    if (isset($_SESSION['project_review_status'][$undoContract]) && is_array($_SESSION['project_review_status'][$undoContract])) {
        $_SESSION['project_review_status'][$undoContract]['worker_reviewed'] = false;
    }
    if (isset($_SESSION['worker_employer_reviews'][$undoContract])) {
        unset($_SESSION['worker_employer_reviews'][$undoContract]);
    }
    header('Location: worker-tugas.php');
    exit;
}

// ── Handle SUBMIT ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $overallRating  = min(5, max(1, (int)($_POST['overall_rating'] ?? 5)));
    $comment        = trim((string)($_POST['comment'] ?? ''));
    $selectedBadges = is_array($_POST['badges'] ?? null) ? $_POST['badges'] : [];
    $recommend      = !empty($_POST['recommend_employer']);
    if (!$canSubmitReview) {
        $errorMessage = 'Ulasan belum bisa dikirim. Kedua pihak harus mengonfirmasi bahwa proyek selesai terlebih dahulu.';
    } elseif ($comment === '') {
        $errorMessage = 'Mohon tuliskan ulasan atau testimoni singkat untuk pemberi kerja.';
    } elseif (mb_strlen($comment) < 100) {
        $errorMessage = 'Ulasan wajib diisi minimal 100 karakter sesuai standar evaluasi KarirHub (saat ini ' . mb_strlen($comment) . ' karakter).';
    } else {
        $badgesJson = implode('||', $selectedBadges);
        $todayDate  = date('Y-m-d');

        if ($pdo !== null) {
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
                ':cid'     => $contractKey,
                ':wid'     => (string)($worker['id'] ?? $username),
                ':emp'     => (string)($projectData['employer'] ?? 'PT Perusahaan'),
                ':title'   => (string)($projectData['title'] ?? 'Proyek'),
                ':overall' => $overallRating,
                ':comment' => $comment,
                ':badges'  => $badgesJson,
                ':rec'     => $recommend ? 1 : 0,
            ]);
        }

        if (!isset($_SESSION['project_review_status']) || !is_array($_SESSION['project_review_status'])) {
            $_SESSION['project_review_status'] = [];
        }
        if (!isset($_SESSION['project_review_status'][$contractKey]) || !is_array($_SESSION['project_review_status'][$contractKey])) {
            $_SESSION['project_review_status'][$contractKey] = [
                'employer_reviewed' => false,
                'worker_reviewed' => false,
            ];
        }
        $_SESSION['project_review_status'][$contractKey]['worker_reviewed'] = true;

        if (!isset($_SESSION['worker_employer_reviews'])) {
            $_SESSION['worker_employer_reviews'] = [];
        }
        $_SESSION['worker_employer_reviews'][$contractKey] = [
            'overall_rating' => $overallRating,
            'comment' => $comment,
            'badges' => $selectedBadges,
        ];

        $submitted = true;
    }
}

// ── Check if already rated ───────────────────────────────────────────────────
$alreadyRated = false;
$existingRating = null;
if ($pdo !== null) {
    gig_ensure_employer_reviews_table($pdo);
    $stmt = $pdo->prepare("SELECT * FROM `employer_reviews` WHERE `contract_id` = :cid AND `worker_id` = :wid LIMIT 1");
    $stmt->execute([
        ':cid' => $contractKey,
        ':wid' => (string)($worker['id'] ?? $username),
    ]);
    $existingRating = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $alreadyRated = ($existingRating !== null);
} elseif (isset($_SESSION['worker_employer_reviews'][$contractKey])) {
    $alreadyRated = true;
}

$employerName = (string)($projectData['employer'] ?? 'Pemberi Kerja');
$employerCat  = (string)($projectData['employer_category'] ?? 'Pemberi Kerja');
$deliverablesText = trim((string)($projectData['deliverables'] ?? 'Deliverable proyek telah diselesaikan dan dikonfirmasi.'));
$undoLink = 'worker-rating-employer.php?contract=' . urlencode($contractKey) . '&undo=1';

$pageTitle         = 'Beri Ulasan Pemberi Kerja';
$pageKey           = 'tugas';
$breadcrumbCurrent = 'Ulasan Pemberi Kerja';

require __DIR__ . '/includes/worker-layout-start.php';
?>

<div class="page-toolbar" style="margin-bottom: 20px;">
  <div>
    <h1>Penilaian &amp; Ulasan Pemberi Kerja</h1>
    <p style="font-size:0.86rem;color:var(--text-muted);margin-top:4px;">
      Berikan penilaian dan masukan profesional terhadap pengalaman bekerja sama dengan Pemberi Kerja.
    </p>
  </div>
  <a href="worker-tugas.php" class="btn-create-post" style="text-decoration:none;background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:8px 14px;border-radius:8px;font-weight:700;">
    ← Kembali ke Proyek Aktif
  </a>
</div>

<?php if ($submitted): ?>
  <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;padding:24px;margin-bottom:24px;text-align:center;">
    <div style="font-size:2.5rem;margin-bottom:8px;">🎉</div>
    <h2 style="font-size:1.2rem;font-weight:800;color:#065f46;margin-bottom:6px;">Terima Kasih! Ulasan Anda Berhasil Dikirim</h2>
    <p style="font-size:0.88rem;color:#047857;max-width:560px;margin:0 auto 16px auto;line-height:1.5;">
      Penilaian Anda untuk <strong><?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?></strong> telah tersimpan dalam sistem KarirHub Gig.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="worker-tugas.php" class="btn-create-post" style="text-decoration:none;background:#059669;color:#fff;padding:9px 18px;border-radius:8px;font-weight:700;">
        Lihat Proyek Aktif
      </a>
      <a href="<?php echo htmlspecialchars($undoLink, ENT_QUOTES, 'UTF-8'); ?>" class="btn-create-post" style="text-decoration:none;background:#ffffff;color:#dc2626;border:1px solid #fca5a5;padding:9px 18px;border-radius:8px;font-weight:700;" onclick="return confirm('Apakah Anda yakin ingin menghapus ulasan ini?');">
        Hapus / Ubah Ulasan
      </a>
    </div>
  </div>
<?php else: ?>

  <?php if ($errorMessage !== ''): ?>
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:0.88rem;font-weight:600;">
      ⚠️ <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php endif; ?>

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
      <a href="worker-tugas.php" class="btn-action-sm" style="text-decoration:none;display:inline-flex;">
        Kembali ke Proyek Aktif
      </a>
    </div>
  <?php else: ?>

    <?php if ($alreadyRated): ?>
      <div style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;padding:14px 18px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="font-size:0.88rem;">
          ✓ <strong>Anda sudah memberikan ulasan untuk pemberi kerja ini.</strong> Mengirim ulang form di bawah akan memperbarui ulasan sebelumnya.
        </div>
        <a href="<?php echo htmlspecialchars($undoLink, ENT_QUOTES, 'UTF-8'); ?>" style="font-size:0.82rem;font-weight:700;color:#dc2626;text-decoration:none;" onclick="return confirm('Apakah Anda yakin ingin menghapus ulasan ini?');">
          [Hapus Ulasan Saya]
        </a>
      </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">
      <form method="post" action="" class="white-card" style="padding:28px;">
        <div style="padding-bottom:18px;border-bottom:1px solid var(--border-subtle);margin-bottom:24px;">
          <h2 style="font-size:1.2rem;font-weight:800;color:var(--text-main);display:flex;align-items:center;gap:8px;">
            <span>⭐</span> Form Evaluasi &amp; Rating Pemberi Kerja
          </h2>
          <p style="font-size:0.84rem;color:var(--text-muted);margin-top:4px;">
            Penilaian Anda membantu menjaga kualitas ekosistem kerja Gig dan transparansi reputasi pemberi kerja.
          </p>
        </div>

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

        <div style="margin-bottom:28px;">
          <label style="display:block;font-size:0.92rem;font-weight:800;color:var(--text-main);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em;">Lencana Apresiasi (Opsional)</label>
          <span style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:12px;">Pilih apresiasi yang paling sesuai terhadap pemberi kerja:</span>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php
            $badgesList = [
                'Pembayaran Tepat Waktu',
                'Instruksi Jelas & Detail',
                'Komunikasi Profesional & Respektif',
                'Lingkungan Kerja Positif',
                'Umpan Balik Membantu',
            ];
            foreach ($badgesList as $bName): ?>
              <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border:1px solid #cbd5e1;padding:6px 12px;border-radius:9999px;font-size:0.8rem;font-weight:600;color:#334155;transition:all 0.15s;">
                <input type="checkbox" name="badges[]" value="<?php echo htmlspecialchars($bName, ENT_QUOTES, 'UTF-8'); ?>" style="accent-color:#2563eb;"
                  onchange="this.parentElement.style.background=this.checked?'#eff6ff':'#f1f5f9';this.parentElement.style.borderColor=this.checked?'#3b82f6':'#cbd5e1';this.parentElement.style.color=this.checked?'#1d4ed8':'#334155';" />
                <?php echo htmlspecialchars($bName, ENT_QUOTES, 'UTF-8'); ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="margin-bottom:28px;">
          <label for="comment" style="display:block;font-size:0.92rem;font-weight:800;color:var(--text-main);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em;">
            Ulasan &amp; Testimoni <span style="color:#ef4444;">*</span>
          </label>
          <textarea id="comment" name="comment" rows="4" required minlength="100" placeholder="Tuliskan pengalaman Anda bekerja bersama pemberi kerja ini (minimal 100 karakter)..." style="width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:8px;font-size:0.88rem;line-height:1.5;outline:none;font-family:inherit;resize:vertical;"></textarea>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;font-size:0.75rem;">
            <span style="color:#64748b;">Wajib diisi minimal 100 karakter</span>
            <span id="comment-char-count" style="color:#ef4444;font-weight:700;">0/100 karakter</span>
          </div>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end;align-items:center;margin-top:20px;">
          <a href="worker-tugas.php" class="filter-btn-pill" style="text-decoration:none;padding:10px 18px;font-size:0.88rem;border:1px solid #cbd5e1;border-radius:8px;color:#475569;">Batal</a>
          <button type="submit" id="submit_review_btn" name="submit_review" value="1" class="btn-create-post" disabled aria-disabled="true" style="padding:10px 24px;font-size:0.9rem;background:#cbd5e1;border:none;color:#64748b;cursor:not-allowed;opacity:0.7;border-radius:8px;font-weight:700;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Kirim Ulasan
          </button>
        </div>
      </form>

      <aside style="display:flex;flex-direction:column;gap:18px;">
        <div class="white-card" style="padding:20px;">
          <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:12px;">Pemberi Kerja yang Dinilai</div>
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
            <div style="width:50px;height:50px;border-radius:50%;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.2rem;flex-shrink:0;">🏢</div>
            <div>
              <h3 style="font-size:1.05rem;font-weight:800;margin:0;color:var(--text-main);"><?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?></h3>
              <span style="font-size:0.78rem;color:var(--text-muted);"><?php echo htmlspecialchars($employerCat, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          </div>
          <a href="employer-profile.php?employer=<?php echo urlencode($employerName); ?>" target="_blank" style="font-size:0.8rem;color:var(--primary-blue);text-decoration:none;font-weight:700;">Lihat Profil Pemberi Kerja ↗</a>
        </div>

        <div class="white-card" style="padding:20px;">
          <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:12px;">Detail Proyek</div>
          <div style="margin-bottom:12px;">
            <span style="font-size:0.74rem;color:var(--text-muted);display:block;">Judul Proyek:</span>
            <strong style="font-size:0.88rem;color:var(--text-main);line-height:1.4;"><?php echo htmlspecialchars((string)($projectData['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
          </div>
          <div style="padding-top:10px;border-top:1px solid #f1f5f9;margin-bottom:12px;">
            <div>
              <span style="font-size:0.72rem;color:var(--text-muted);display:block;">Nilai Proyek:</span>
              <span style="font-size:0.85rem;font-weight:800;color:var(--primary-blue);"><?php echo htmlspecialchars((string)($projectData['budget'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          </div>
          <div style="padding-top:10px;border-top:1px solid #f1f5f9;">
            <span style="font-size:0.72rem;color:var(--text-muted);display:block;margin-bottom:4px;">Deliverable yang Diterima:</span>
            <p style="font-size:0.78rem;color:#334155;background:#f8fafc;padding:8px 10px;border-radius:6px;margin:0;"><?php echo htmlspecialchars($deliverablesText, ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </div>
      </aside>
    </div>
  <?php endif; ?>

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

<?php require __DIR__ . '/includes/worker-layout-end.php'; ?>
