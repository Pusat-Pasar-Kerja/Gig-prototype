<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/worker-auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/project-offers.php';
require_once __DIR__ . '/includes/project-applications.php';

$projectId = trim((string)($_GET['id'] ?? ''));
$fromOffers = ($_GET['from'] ?? '') === 'penawaran';
$job = gig_find_vacancy($projectId);

if (!$job && $projectId === '') {
    $activeJobs = array_values(array_filter(gig_project_vacancies(), fn($j) => $j['status'] === 'active'));
    $job = $activeJobs[0] ?? null;
}

$flashMsg = '';
$flashError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $job && $fromOffers && isset($_POST['confirm_action'])) {
    $appId  = trim((string)($_POST['app_id'] ?? ''));
    $action = trim((string)($_POST['confirm_action'] ?? ''));
    $rejectionReason = trim((string)($_POST['rejection_reason'] ?? ''));
    if ($appId !== '' && in_array($action, ['confirm', 'decline'], true)) {
        if ($action === 'decline' && $rejectionReason === '') {
            $flashMsg = 'Alasan penolakan penawaran proyek wajib diisi (maksimal 100 karakter).';
            $flashError = true;
        } elseif ($action === 'decline' && mb_strlen($rejectionReason) > 100) {
            $flashMsg = 'Alasan penolakan maksimal 100 karakter.';
            $flashError = true;
        } else {
            $res = gig_worker_confirm_application($appId, $action, $username, $rejectionReason);
            if (!empty($res['ok'])) {
                $flashMsg = ($action === 'confirm')
                    ? 'Anda menerima penawaran. Proyek kini aktif.'
                    : 'Penawaran proyek telah ditolak. Pemberi kerja telah diberitahukan alasan penolakan Anda.';
            } else {
                $flashMsg = $res['error'] ?? 'Gagal memproses penawaran.';
                $flashError = true;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $job && !$fromOffers && isset($_POST['do_apply'])) {
    $note = trim((string)($_POST['apply_note'] ?? ''));
    $workerName = ucfirst($username);
    $res = gig_apply_for_project($username, $workerName, (string)$job['id'], $note);
    if (!empty($res['ok'])) {
        $flashMsg = 'Lamaran proyek berhasil dikirim. Status Anda saat ini: kandidat/pelamar. Anda akan resmi direkrut setelah pemberi kerja menerima lamaran.';
    } else {
        $flashMsg = $res['error'] ?? 'Gagal mengirimkan lamaran.';
        $flashError = true;
    }
}

$hasApplied = false;
$offerStatus = null;
$offerAppId = null;
if ($job) {
    foreach (gig_get_applications_for_worker($username) as $ap) {
        if (strcasecmp((string)$ap['vacancy_id'], (string)$job['id']) !== 0) {
            continue;
        }
        $hasApplied = true;
        $offerAppId = (string)$ap['id'];
        $offerStatus = (string)($ap['status'] ?? '');
        break;
    }
}

$displayLocation = trim((string)(($job ?? [])['location'] ?? ''));
if ($displayLocation === '' || strcasecmp($displayLocation, 'Lokasi belum diisi') === 0) {
    $displayLocation = gig_random_location((string)(($job ?? [])['id'] ?? ''));
}
$employerName = (string)(($job ?? [])['employer'] ?? ($job ?? [])['client'] ?? 'Pemberi kerja');

$matchedOffer = null;
if ($fromOffers && $job) {
    foreach (gig_offers_for_worker($username) as $offer) {
        if (strcasecmp((string)$offer['detail_id'], (string)$job['id']) === 0) {
            $matchedOffer = $offer;
            $employerName = (string)$offer['employer_display'];
            break;
        }
    }
    if (!$offerAppId) {
        $offerAppId = 'APP-' . $job['id'];
    }
    if (!$offerStatus) {
        $offerStatus = 'pending_offer';
    }
}

$backHref = $fromOffers ? 'worker-penawaran.php' : 'worker-bursa.php';
$backLabel = $fromOffers ? 'Kembali ke Penawaran Proyek' : 'Kembali ke Cari Proyek';

$pageTitle = $job ? $job['title'] : 'Detail Proyek';
$pageKey = $fromOffers ? 'penawaran' : 'bursa';
$breadcrumbCurrent = $fromOffers ? 'Detail Penawaran' : 'Detail Proyek';
require __DIR__ . '/includes/worker-layout-start.php';
?>

<style>
  .detail-header-card {
    background: #ffffff;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 28px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
  }

  .detail-back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--primary-blue);
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    margin-bottom: 16px;
    transition: transform 0.2s;
  }

  .detail-back-link:hover {
    transform: translateX(-3px);
  }

  .detail-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.3;
    margin: 0 0 10px 0;
    text-transform: uppercase;
  }

  .detail-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
  }

  @media (max-width: 992px) {
    .detail-grid {
      grid-template-columns: 1fr;
    }
  }

  .detail-section-card {
    background: #ffffff;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
  }

  .detail-section-card h3 {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 16px 0;
  }

  .notice-bar {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e40af;
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    font-size: 0.86rem;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .btn-apply-hero {
    width: 100%;
    padding: 14px 24px;
    font-size: 0.95rem;
    font-weight: 800;
    border-radius: var(--radius-sm);
    background: var(--primary-blue);
    color: #ffffff;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

  }

  .btn-apply-hero:hover {
    background: #1d4ed8;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
  }

  .btn-applied-done {
    background: #059669 !important;
    color: #ffffff !important;
    box-shadow: none !important;
    cursor: default !important;
  }

  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
  }

  .modal-backdrop.open {
    opacity: 1;
    pointer-events: auto;
  }

  .modal-card {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
  }

  .modal-header {
    background: #1e293b;
    color: #ffffff;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .modal-body {
    padding: 24px;
  }
</style>

<?php if (!$job): ?>
  <div class="detail-section-card" style="text-align: center; padding: 40px 20px;">
    <h2>Proyek Tidak Ditemukan</h2>
    <p>Lowongan proyek yang Anda cari tidak tersedia atau belum dipublikasikan.</p>
    <a href="<?php echo htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8'); ?>" class="btn-primary-add" style="display: inline-flex; margin-top: 16px;"><?php echo htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8'); ?></a>
  </div>
<?php else: ?>

  <?php if ($flashMsg !== ''): ?>
    <div style="padding: 12px 18px; border-radius: 10px; font-size: 0.88rem; font-weight: 600; margin-bottom: 18px; <?php echo $flashError ? 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;' : 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;'; ?>">
      <?php echo htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php endif; ?>

  <!-- TOP HEADER CARD -->
  <section class="detail-header-card">
    <a href="<?php echo htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8'); ?>" class="detail-back-link">&larr; <?php echo htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8'); ?></a>
    <?php if ($fromOffers): ?>
      <div class="notice-bar" style="margin-bottom:16px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        <span>Penawaran dari <strong><?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?></strong>. Terima atau tolak tawaran ini.</span>
      </div>
    <?php else: ?>
      <div class="notice-bar" style="margin-bottom:16px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>Lowongan terbuka. Kirim lamaran jika Anda ingin mengerjakan proyek ini.</span>
      </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
      <div style="display: flex; gap: 18px; align-items: flex-start; flex: 1; min-width: 280px;">
        <!-- Logo / Avatar Icon Thumbnail -->
        <div style="width: 64px; height: 64px; border-radius: 12px; background: #f1f5f9; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="var(--primary-blue)" stroke-width="1.8"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </div>

        <div>
          <h1 class="detail-title"><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></h1>

          <div style="display: flex; flex-direction: column; gap: 6px; font-size: 0.88rem; color: #64748b;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
              <span>📍</span> <strong><?php echo htmlspecialchars($displayLocation, ENT_QUOTES, 'UTF-8'); ?></strong>
              <?php if (gig_is_remote_work($job)): ?>
                <span style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:2px 8px;border-radius:9999px;font-size:0.75rem;font-weight:700;">🌐 Project Remote</span>
              <?php else: ?>
                <span style="background:#f8fafc;color:#475569;border:1px solid #e2e8f0;padding:2px 8px;border-radius:9999px;font-size:0.75rem;font-weight:700;">🏢 On-site / Hybrid</span>
              <?php endif; ?>
            </div>
            <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
              <span>📅 Diposting <?php echo htmlspecialchars($job['posted'], ENT_QUOTES, 'UTF-8'); ?></span>
              <span>&bull;</span>
              <span>👤 Penempatan: <strong>1 Gig Workers</strong></span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; color: #dc2626; font-weight: 600;">
              <span>🔔</span> Batas Lamaran: <strong><?php echo htmlspecialchars($job['deadline'] ?? '31 Des 2026', ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Header Action Button Right -->
      <div style="flex-shrink: 0; align-self: center;">
        <?php if ($fromOffers): ?>
          <?php if (gig_is_hired_status((string)$offerStatus)): ?>
            <a href="worker-tugas.php" style="padding: 12px 24px; font-size: 0.9rem; font-weight: 800; border-radius: 10px; background: #059669; color: #ffffff; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);">
              ✓ Resmi Direkrut · Proyek Aktif
            </a>
          <?php elseif ($offerStatus === 'declined_by_worker'): ?>
            <span style="padding: 10px 20px; font-size: 0.88rem; font-weight: 700; border-radius: 10px; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; display: inline-block;">
              ✕ Penawaran Ditolak
            </span>
            <?php 
              $rejReason = (string)($appOfferMatch['rejection_reason'] ?? '');
              if ($rejReason === '' && !empty($offerAppId) && isset($_SESSION['gig_applications'][$offerAppId]['rejection_reason'])) {
                  $rejReason = (string)$_SESSION['gig_applications'][$offerAppId]['rejection_reason'];
              }
              if ($rejReason !== ''):
            ?>
              <div style="font-size:0.8rem;color:#b91c1c;margin-top:6px;font-style:italic;">
                Alasan: &ldquo;<?php echo htmlspecialchars($rejReason, ENT_QUOTES, 'UTF-8'); ?>&rdquo;
              </div>
            <?php endif; ?>
          <?php else: ?>
            <form method="post" action="worker-project-detail.php?id=<?php echo urlencode((string)$job['id']); ?>&from=penawaran" style="display:inline-flex;gap:10px;align-items:center;margin:0;">
              <input type="hidden" name="app_id" value="<?php echo htmlspecialchars($offerAppId ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
              <button type="submit" name="confirm_action" value="confirm" style="padding: 12px 24px; font-size: 0.9rem; font-weight: 800; border-radius: 10px; background: #2563eb; color: #ffffff; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); white-space: nowrap;">
                Terima Penawaran
              </button>
              <button type="button" style="padding: 12px 18px; font-size: 0.86rem; font-weight: 700; border-radius: 10px; background: #fef2f2; color: #dc2626; border: 1px solid #fecdd3; cursor: pointer; white-space: nowrap;" onclick="openRejectOfferModal('<?php echo htmlspecialchars($offerAppId ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes((string)($job['title'] ?? 'Proyek')), ENT_QUOTES, 'UTF-8'); ?>')">
                Tolak Penawaran
              </button>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <?php if ($hasApplied): ?>
            <?php if (gig_is_hired_status((string)$offerStatus)): ?>
              <a href="worker-tugas.php" style="padding: 12px 28px; font-size: 0.95rem; font-weight: 800; border-radius: 10px; background: #059669; color: #ffffff; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);">
                ✓ Resmi Direkrut · Proyek Aktif
              </a>
            <?php else: ?>
            <button type="button" disabled style="padding: 12px 28px; font-size: 0.95rem; font-weight: 800; border-radius: 10px; background: #059669; color: #ffffff; border: none; cursor: default; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);">
              ✓ Lamaran Proyek Terkirim
            </button>
            <?php endif; ?>
          <?php else: ?>
            <button id="btnApplyHeader" type="button" onclick="openApplyModal()" style="padding: 12px 28px; font-size: 0.95rem; font-weight: 800; border-radius: 10px; background: #2563eb; color: #ffffff; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); transition: all 0.2s;">
              Lamar Proyek Ini
            </button>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($flashMsg !== ''): ?>
      <div style="margin-top:16px;background:<?php echo $flashError ? '#fef2f2' : '#f0fdf4'; ?>;border:1px solid <?php echo $flashError ? '#fecaca' : '#bbf7d0'; ?>;color:<?php echo $flashError ? '#991b1b' : '#166534'; ?>;padding:12px 16px;border-radius:10px;font-size:0.86rem;font-weight:700;">
        <?php echo htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <!-- Share Social Row -->
    <div style="margin-top: 20px; padding-top: 14px; border-top: 1px dashed #e2e8f0; display: flex; align-items: center; gap: 12px; font-size: 0.84rem; color: #64748b;">
      <span style="font-weight: 600;">Bagikan:</span>
      <a href="#" onclick="event.preventDefault(); alert('Link proyek berhasil disalin!');" style="color: #25d366; text-decoration: none; font-size: 1.1rem;" title="WhatsApp">💬</a>
      <a href="#" onclick="event.preventDefault(); alert('Link proyek berhasil disalin!');" style="color: #0a66c2; text-decoration: none; font-size: 1.1rem;" title="LinkedIn">🔗</a>
      <a href="#" onclick="event.preventDefault(); alert('Link proyek berhasil disalin!');" style="color: #1da1f2; text-decoration: none; font-size: 1.1rem;" title="Twitter / X">❌</a>
      <a href="#" onclick="event.preventDefault(); alert('Link proyek berhasil disalin!');" style="color: #1877f2; text-decoration: none; font-size: 1.1rem;" title="Facebook">📘</a>
      <a href="#" onclick="event.preventDefault(); alert('Link proyek disalin ke clipboard!');" style="color: #64748b; text-decoration: none; font-size: 0.82rem; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-weight: 600; margin-left: 4px;">📋 Salin Link</a>
    </div>
  </section>

  <!-- MAIN GRID -->
  <div class="detail-grid">
    <!-- LEFT CONTENT COLUMN -->
    <div>
      <!-- 1. RINCIAN PROYEK -->
      <section class="detail-section-card">
        <h3 style="margin-bottom: 16px;">Rincian Proyek</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 20px 16px; font-size: 0.86rem;">
          <div>
            <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 4px;">Kategori Proyek</div>
            <strong style="color: #0f172a;"><?php echo htmlspecialchars($job['category'], ENT_QUOTES, 'UTF-8'); ?></strong>
          </div>
          <div>
            <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 4px;">Gaji</div>
            <strong style="color: #2563eb; font-weight: 800;"><?php echo htmlspecialchars(gig_vacancy_budget_range((string)($job['budget'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></strong>
          </div>
          <div>
            <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 4px;">Estimasi Durasi</div>
            <strong style="color: #0f172a;"><?php echo htmlspecialchars($job['duration'], ENT_QUOTES, 'UTF-8'); ?></strong>
          </div>
          <div>
            <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 4px;">Lokasi</div>
            <strong style="color: #0f172a;"><?php echo htmlspecialchars($displayLocation, ENT_QUOTES, 'UTF-8'); ?></strong>
          </div>
          <div>
            <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 4px;">Mode Pengerjaan</div>
            <strong style="color: <?php echo gig_is_remote_work($job) ? '#1d4ed8' : '#0f172a'; ?>; font-weight: 800;">
              <?php echo gig_is_remote_work($job) ? '🌐 Remote (Kerja Jarak Jauh)' : '🏢 On-site / Hybrid'; ?>
            </strong>
          </div>
        </div>
      </section>

      <!-- 2. DESKRIPSI PROYEK -->
      <section class="detail-section-card">
        <h3>Deskripsi Proyek</h3>
        <p style="font-size: 0.92rem; line-height: 1.6; color: #334155; margin-bottom: 12px;">
          <?php echo nl2br(htmlspecialchars($job['desc'], ENT_QUOTES, 'UTF-8')); ?>
        </p>
        <p style="font-size: 0.88rem; line-height: 1.6; color: #64748b;">
          Seluruh pengerjaan proyek dilaksanakan secara profesional sesuai dengan kesepakatan dan standar kualitas SIAPKerja. Penyerahan hasil karya dilakukan melalui platform untuk menjamin perlindungan hak kedua belah pihak.
        </p>
      </section>

      <!-- 3. TARGET/DELIVERABLE PROYEK -->
      <section class="detail-section-card">
        <h3>Target/Deliverable Proyek</h3>
        <?php if (!empty($job['deliverables'])): ?>
          <p style="font-size: 0.92rem; line-height: 1.6; color: #334155; margin-bottom: 12px; font-weight: 600;">
            <?php echo nl2br(htmlspecialchars($job['deliverables'], ENT_QUOTES, 'UTF-8')); ?>
          </p>
        <?php else: ?>
          <p style="font-size: 0.9rem; line-height: 1.6; color: #334155;">
            Hasil akhir pengerjaan diserahkan sesuai kesepakatan ruang lingkup proyek dan tenggat waktu yang ditentukan.
          </p>
        <?php endif; ?>
      </section>

      <!-- 4. PERSYARATAN -->
      <section class="detail-section-card">
        <h3>Persyaratan</h3>
        <div style="margin-bottom: 14px;">
          <div style="font-size: 0.82rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">KEAHLIAN &amp; SKILL UTAMA</div>
          <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php foreach ($job['skills'] as $sk): ?>
              <span style="font-size: 0.84rem; font-weight: 600; padding: 6px 14px; background: #f1f5f9; color: #334155; border-radius: 9999px;">
                <?php echo htmlspecialchars($sk, ENT_QUOTES, 'UTF-8'); ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
        <ul style="font-size: 0.88rem; line-height: 1.7; color: #334155; padding-left: 20px; margin: 0;">
          <li>Memiliki portofolio dan rekam jejak pengerjaan proyek di bidang <?php echo htmlspecialchars($job['category'], ENT_QUOTES, 'UTF-8'); ?>.</li>
          <li>Mampu berkomunikasi dengan baik serta menyelesaikan target sesuai estimasi waktu <?php echo htmlspecialchars($job['duration'], ENT_QUOTES, 'UTF-8'); ?>.</li>
        </ul>
      </section>
    </div>

    <!-- RIGHT SIDEBAR -->
    <div>
      <!-- EMPLOYER COMPANY CARD -->
      <section class="detail-section-card" style="border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; background: #ffffff; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700; margin-bottom: 8px;">Pemberi Kerja / Perusahaan</div>
        <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 6px; line-height: 1.3;">
          <?php echo htmlspecialchars((string)($job['employer'] ?? 'PT Talenta Digital Indonesia'), ENT_QUOTES, 'UTF-8'); ?>
        </h3>
        
        <div style="margin-bottom: 14px;">
          <span style="font-size: 0.76rem; background: #f1f5f9; color: #475569; font-weight: 600; padding: 4px 10px; border-radius: 6px; display: inline-block;">
            <?php echo htmlspecialchars($job['category'], ENT_QUOTES, 'UTF-8'); ?>
          </span>
        </div>

        <a href="employer-profile.php?name=<?php echo urlencode($job['employer'] ?? 'PT Talenta Digital Indonesia'); ?>" style="font-size: 0.84rem; color: #2563eb; font-weight: 700; text-decoration: none; display: inline-block; margin-bottom: 16px;">
          Lihat Profil Pemberi Kerja &rarr;
        </a>

        <div style="padding-top: 14px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: #64748b;">
          <span>Proyek dari KarirHub</span>
          <span style="font-weight: 800; color: #2563eb; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            KarirHub
          </span>
        </div>
      </section>
    </div>
  </div>

  <?php if (!$fromOffers): ?>
  <!-- APPLY MODAL -->
  <div class="modal-backdrop" id="applyModal" onclick="if(event.target.id==='applyModal') closeApplyModal();">
    <div class="modal-card">
      <div class="modal-header">
        <h3 style="font-size: 1rem; font-weight: 800; color:#ffffff;">Persetujuan Risiko Pembayaran</h3>
        <button type="button" onclick="closeApplyModal()" style="background:none; border:none; color:#fff; font-size:1.4rem; cursor:pointer;">&times;</button>
      </div>
      <form method="post" action="" class="modal-body">
        <input type="hidden" name="do_apply" value="1" />

        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;margin-bottom:16px;">
          <div style="font-size:0.78rem;color:#334155;line-height:1.5;margin-bottom:10px;">
            Saya memahami bahwa Karirhub tidak menahan dana pembayaran. Segala risiko terkait penipuan atau gagal bayar oleh Pemberi Kerja berada di luar tanggung jawab Karirhub. Pastikan Anda telah menyepakati metode pembayaran sebelum mulai bekerja.
          </div>
          <label style="display:flex;align-items:flex-start;gap:8px;font-size:0.8rem;color:#0f172a;font-weight:600;cursor:pointer;">
            <input type="checkbox" id="applyConsentCheckbox" style="margin-top:2px;accent-color:#2563eb;" />
            <span>Saya menyetujui pernyataan di atas.</span>
          </label>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn-action-sm" onclick="closeApplyModal()" style="background:#f1f5f9; color:var(--text-main);">Batal</button>
          <button type="submit" id="applySubmitBtn" class="btn-primary-add" style="padding:10px 20px;background:#9ca3af;border-color:#9ca3af;cursor:not-allowed;opacity:0.9;" disabled>Kirim Lamaran Proyek</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <script>
    function syncApplyConsentState() {
      const cb = document.getElementById('applyConsentCheckbox');
      const submitBtn = document.getElementById('applySubmitBtn');
      if (!cb || !submitBtn) return;
      submitBtn.disabled = !cb.checked;
      if (cb.checked) {
        submitBtn.style.background = '';
        submitBtn.style.borderColor = '';
        submitBtn.style.cursor = '';
        submitBtn.style.opacity = '';
      } else {
        submitBtn.style.background = '#9ca3af';
        submitBtn.style.borderColor = '#9ca3af';
        submitBtn.style.cursor = 'not-allowed';
        submitBtn.style.opacity = '0.9';
      }
    }

    function openApplyModal() {
      const modal = document.getElementById('applyModal');
      if (modal) modal.classList.add('open');
      const cb = document.getElementById('applyConsentCheckbox');
      if (cb) cb.checked = false;
      syncApplyConsentState();
    }

    function closeApplyModal() {
      const modal = document.getElementById('applyModal');
      if (modal) modal.classList.remove('open');
    }

    function submitApplication() {
      closeApplyModal();
      const btns = [document.getElementById('btnApplyHeader')];
      btns.forEach(btn => {
        if (btn) {
          btn.classList.add('btn-applied-done');
          btn.disabled = true;
          btn.innerHTML = '✓ Lamaran Proyek Terkirim';
        }
      });
      
      if (typeof showToast === 'function') {
        showToast('Lamaran proyek berhasil dikirim ke Pemberi Kerja! Anda dapat memantau statusnya di menu Lamaran Saya.');
      } else {
        alert('Lamaran proyek berhasil dikirim ke Pemberi Kerja!');
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      const cb = document.getElementById('applyConsentCheckbox');
      if (cb) {
        cb.addEventListener('change', syncApplyConsentState);
      }
      syncApplyConsentState();
    });
  </script>

  <!-- REJECT OFFER MODAL -->
  <div id="reject-offer-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#ffffff;border-radius:16px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.2);overflow:hidden;">
      <div style="background:#dc2626;color:#ffffff;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:10px;">
          <span style="font-size:1.2rem;">⚠️</span>
          <h3 style="margin:0;font-size:1.05rem;font-weight:700;color:#ffffff;">Tolak Penawaran Proyek</h3>
        </div>
        <button type="button" onclick="closeRejectOfferModal()" style="background:transparent;border:none;color:#ffffff;font-size:1.4rem;cursor:pointer;line-height:1;padding:0 4px;">&times;</button>
      </div>
      <form method="post" action="worker-project-detail.php?id=<?php echo urlencode((string)$job['id']); ?>&from=penawaran" id="reject-offer-form" style="margin:0;" onsubmit="return validateRejectForm()">
        <div style="padding:20px 24px;">
          <input type="hidden" name="app_id" id="reject-modal-app-id" value="" />
          <input type="hidden" name="confirm_action" value="decline" />
          
          <p style="margin:0 0 14px 0;font-size:0.88rem;color:#475569;line-height:1.5;">
            Anda akan menolak penawaran untuk proyek <strong id="reject-modal-project-title" style="color:#0f172a;"></strong>.
          </p>

          <div style="margin-bottom:8px;">
            <label for="reject-reason-input" style="display:block;font-size:0.84rem;font-weight:700;color:#1e293b;margin-bottom:6px;">
              Alasan Penolakan <span style="color:#dc2626;">*</span>
            </label>
            <textarea 
              id="reject-reason-input" 
              name="rejection_reason" 
              rows="3" 
              maxlength="100" 
              required
              placeholder="Tulis alasan penolakan Anda di sini (contoh: Jadwal sudah penuh / Ruang lingkup tidak sesuai)..."
              style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;font-size:0.86rem;font-family:inherit;resize:none;outline:none;transition:border-color 0.2s, box-shadow 0.2s;"
              oninput="updateRejectCharCount(this)"
            ></textarea>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;font-size:0.75rem;color:#64748b;">
              <span>Maksimal 100 karakter (wajib diisi)</span>
              <span id="reject-char-counter"><strong id="reject-char-val">0</strong> / 100</span>
            </div>
            <div id="reject-error-msg" style="display:none;color:#dc2626;font-size:0.78rem;font-weight:600;margin-top:6px;">
              Alasan penolakan wajib diisi.
            </div>
          </div>
        </div>

        <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 24px;display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="closeRejectOfferModal()" style="padding:9px 18px;font-size:0.86rem;font-weight:600;border-radius:8px;border:1px solid #cbd5e1;background:#ffffff;color:#475569;cursor:pointer;">
            Batal
          </button>
          <button type="submit" id="reject-submit-btn" style="padding:9px 20px;font-size:0.86rem;font-weight:700;border-radius:8px;border:none;background:#dc2626;color:#ffffff;cursor:pointer;box-shadow:0 2px 6px rgba(220,38,38,0.3);">
            ✕ Tolak Penawaran
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function openRejectOfferModal(appId, projectTitle) {
    const modal = document.getElementById('reject-offer-modal');
    const appIdInput = document.getElementById('reject-modal-app-id');
    const titleEl = document.getElementById('reject-modal-project-title');
    const reasonInput = document.getElementById('reject-reason-input');
    const charVal = document.getElementById('reject-char-val');
    const errMsg = document.getElementById('reject-error-msg');

    if (appIdInput) appIdInput.value = appId;
    if (titleEl) titleEl.textContent = '"' + projectTitle + '"';
    if (reasonInput) {
      reasonInput.value = '';
      reasonInput.style.borderColor = '#cbd5e1';
    }
    if (charVal) charVal.textContent = '0';
    if (errMsg) errMsg.style.display = 'none';

    if (modal) {
      modal.style.display = 'flex';
      setTimeout(function() { if (reasonInput) reasonInput.focus(); }, 50);
    }
  }

  function closeRejectOfferModal() {
    const modal = document.getElementById('reject-offer-modal');
    if (modal) modal.style.display = 'none';
  }

  function updateRejectCharCount(textarea) {
    const val = textarea.value || '';
    const len = val.length;
    const charVal = document.getElementById('reject-char-val');
    const counter = document.getElementById('reject-char-counter');
    const errMsg = document.getElementById('reject-error-msg');
    
    if (charVal) charVal.textContent = len;
    if (errMsg && val.trim().length > 0) errMsg.style.display = 'none';

    if (len >= 100) {
      if (counter) counter.style.color = '#dc2626';
    } else {
      if (counter) counter.style.color = '#64748b';
    }
  }

  function validateRejectForm() {
    const reasonInput = document.getElementById('reject-reason-input');
    const errMsg = document.getElementById('reject-error-msg');
    const val = (reasonInput ? reasonInput.value : '').trim();

    if (!val) {
      if (errMsg) {
        errMsg.textContent = 'Alasan penolakan wajib diisi.';
        errMsg.style.display = 'block';
      }
      if (reasonInput) {
        reasonInput.style.borderColor = '#dc2626';
        reasonInput.focus();
      }
      return false;
    }

    if (val.length > 100) {
      if (errMsg) {
        errMsg.textContent = 'Alasan penolakan maksimal 100 karakter.';
        errMsg.style.display = 'block';
      }
      if (reasonInput) {
        reasonInput.style.borderColor = '#dc2626';
        reasonInput.focus();
      }
      return false;
    }

    return true;
  }

  document.addEventListener('click', function(e) {
    const modal = document.getElementById('reject-offer-modal');
    if (modal && e.target === modal) {
      closeRejectOfferModal();
    }
  });
  </script>

<?php endif; ?>

<?php require __DIR__ . '/includes/worker-layout-end.php'; ?>
