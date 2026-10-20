<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/worker-auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/project-offers.php';
require_once __DIR__ . '/includes/project-applications.php';

$flashMsg = '';
$flashErr = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_action'])) {
    $appId  = trim((string)($_POST['app_id'] ?? ''));
    $action = trim((string)($_POST['confirm_action'] ?? ''));
    $rejectionReason = trim((string)($_POST['rejection_reason'] ?? ''));
    if ($appId !== '' && in_array($action, ['confirm', 'decline'], true)) {
        if ($action === 'decline' && $rejectionReason === '') {
            $flashMsg = 'Alasan penolakan penawaran proyek wajib diisi (maksimal 100 karakter).';
            $flashErr = true;
        } elseif ($action === 'decline' && mb_strlen($rejectionReason) > 100) {
            $flashMsg = 'Alasan penolakan maksimal 100 karakter.';
            $flashErr = true;
        } else {
            $res = gig_worker_confirm_application($appId, $action, $username, $rejectionReason);
            if (!empty($res['ok'])) {
                $flashMsg = ($action === 'confirm')
                    ? 'Selamat! Anda resmi direkrut untuk proyek ini. Pemberi kerja telah diberitahukan.'
                    : 'Penawaran proyek telah ditolak. Pemberi kerja telah diberitahukan alasan penolakan Anda.';
            } else {
                $flashMsg = $res['error'] ?? 'Gagal memproses penawaran.';
                $flashErr = true;
            }
        }
    }
}

gig_seed_demo_offers_if_needed($username);
$offers = gig_offers_for_worker($username);
$workerApps = gig_get_applications_for_worker($username);

// Banner, category label & avatar helpers are loaded from includes/project-vacancies.php


$pageTitle = 'Penawaran Proyek';
$pageKey = 'penawaran';
$breadcrumbCurrent = 'Penawaran Proyek';
require __DIR__ . '/includes/worker-layout-start.php';
?>

<style>
.penawaran-page {
  max-width: 1400px;
  margin: 0 auto;
}

.penawaran-notice {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e40af;
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 0.86rem;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 22px;
}

.penawaran-count-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
  font-size: 0.88rem;
  color: #64748b;
}

.penawaran-list-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.penawaran-card-item {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.penawaran-card-item:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.07);
  border-color: #cbd5e1;
}

.penawaran-card-header {
  margin-bottom: 14px;
}

.penawaran-title-group {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 6px;
}

.penawaran-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  line-height: 1.35;
}

.penawaran-title a {
  color: inherit;
  text-decoration: none;
}

.penawaran-title a:hover {
  color: #1d4ed8;
}

.penawaran-badge-pill {
  display: inline-block;
  padding: 3px 12px;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
}

.penawaran-badge-pill.blue {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

.penawaran-badge-pill.amber {
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
}

.penawaran-badge-pill.green {
  background: #d1fae5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

.penawaran-badge-pill.gray {
  background: #f8fafc;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.penawaran-meta-sub {
  font-size: 0.82rem;
  color: #64748b;
}

.penawaran-meta-sub strong {
  color: #334155;
}

.penawaran-inner-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  flex-wrap: wrap;
}

.employer-info-group {
  display: flex;
  align-items: center;
  gap: 14px;
}

.employer-avatar-circle {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: #1d4ed8;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  font-weight: 800;
  flex-shrink: 0;
}

.employer-name-row {
  font-size: 0.94rem;
  font-weight: 800;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 8px;
}

.employer-rating-tag {
  font-size: 0.74rem;
  font-weight: 700;
  color: #d97706;
  background: #fef3c7;
  padding: 2px 6px;
  border-radius: 6px;
}

.employer-sub-info {
  font-size: 0.78rem;
  color: #64748b;
  margin-top: 2px;
}

.employer-verified-check {
  font-size: 0.74rem;
  color: #059669;
  font-weight: 700;
  margin-top: 3px;
}

.offer-skills-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.skill-pill-tag {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #334155;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 8px;
}

.skill-pill-tag.more {
  background: #e2e8f0;
  color: #475569;
  font-weight: 700;
}

.penawaran-card-footer {
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.footer-notice-label {
  font-size: 0.82rem;
  color: #64748b;
}

.footer-actions-group {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-act-green {
  background: #2563eb;
  color: #ffffff;
  font-weight: 700;
  font-size: 0.86rem;
  padding: 9px 18px;
  border-radius: 10px;
  text-decoration: none;
  border: none;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-act-green:hover {
  background: #1d4ed8;
  color: #ffffff;
}

.btn-act-outline {
  background: #ffffff;
  color: #334155;
  border: 1px solid #cbd5e1;
  font-weight: 700;
  font-size: 0.86rem;
  padding: 8px 16px;
  border-radius: 10px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-act-outline:hover {
  background: #f8fafc;
  color: #1d4ed8;
  border-color: #93c5fd;
}

.btn-act-danger {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecdd3;
  font-weight: 700;
  font-size: 0.82rem;
  padding: 8px 14px;
  border-radius: 10px;
  cursor: pointer;
}

.btn-act-danger:hover {
  background: #fee2e2;
}

.offer-empty-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 48px 20px;
  text-align: center;
  color: #64748b;
}

.offer-empty-card h3 {
  font-size: 1.1rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
}

.offer-empty-card p {
  font-size: 0.9rem;
  margin-bottom: 16px;
}

.flash-ok {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 0.86rem;
  margin-bottom: 18px;
  font-weight: 700;
}

@media (max-width: 768px) {
  .penawaran-inner-box {
    flex-direction: column;
    align-items: flex-start;
  }
  .penawaran-card-footer {
    flex-direction: column;
    align-items: flex-start;
  }
  .footer-actions-group {
    width: 100%;
    flex-direction: column;
  }
  .footer-actions-group .btn-act-green,
  .footer-actions-group .btn-act-outline,
  .footer-actions-group .btn-act-danger {
    width: 100%;
    justify-content: center;
  }
}
</style>

<div class="penawaran-page">
  <div class="page-toolbar">
    <h1>Penawaran Proyek</h1>
  </div>

  <?php if ($flashMsg !== ''): ?>
    <div class="<?php echo !empty($flashErr) ? 'flash-err' : 'flash-ok'; ?>" style="<?php echo !empty($flashErr) ? 'background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:10px;font-size:0.86rem;margin-bottom:18px;font-weight:600;' : ''; ?>"><?php echo htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <div class="penawaran-notice">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span>Pemberi kerja dapat menawarkan lowongan yang sudah tayang. Klik judul atau <strong>Detail Proyek</strong> untuk membuka informasi lengkapnya.</span>
  </div>

  <?php if (count($offers) === 0): ?>
    <div class="offer-empty-card">
      <h3>Belum ada penawaran</h3>
      <p>Ketika pemberi kerja menawarkan proyek yang sudah diposting, tawaran itu akan muncul di halaman ini.</p>
      <a class="btn-act-outline" href="worker-bursa.php" style="margin:0 auto;">Cari Proyek Sendiri</a>
    </div>
  <?php else: ?>
    <div class="penawaran-count-row">
      <div>Menampilkan <strong><?php echo count($offers); ?></strong> penawaran proyek</div>
    </div>
    
    <div class="penawaran-list-container">
      <?php foreach ($offers as $idx => $offer):
          $detailUrl = 'worker-project-detail.php?id=' . urlencode((string)$offer['detail_id']) . '&from=penawaran';
          $created = strtotime((string)($offer['created_at'] ?? ''));
          $createdLabel = $created ? date('d M Y', $created) : 'Baru saja';
          $skills = is_array($offer['skills'] ?? null) ? $offer['skills'] : [];
          $skillsToShow = array_slice($skills, 0, 4);
          $remaining = count($skills) - count($skillsToShow);
          $badgeLabel = getCategoryShortLabel((string)$offer['category']);

          $appMatch = null;
          foreach ($workerApps as $wa) {
              if (strtolower((string)($wa['vacancy_id'] ?? '')) === strtolower((string)$offer['detail_id'])) {
                  $appMatch = $wa;
                  break;
              }
          }
          $appId = (string)($appMatch['id'] ?? ('APP-' . $offer['detail_id']));
          $appStatus = (string)($appMatch['status'] ?? 'pending');
      ?>
        <article class="penawaran-card-item">
          <!-- CARD HEADER -->
          <div class="penawaran-card-header">
            <div class="penawaran-title-group">
              <h3 class="penawaran-title">
                <a href="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo htmlspecialchars((string)$offer['project_title'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </h3>

              <?php if (gig_is_hired_status($appStatus)): ?>
                <span class="penawaran-badge-pill green">✓ Resmi Direkrut · Proyek Aktif</span>
              <?php elseif ($appStatus === 'declined_by_worker'): ?>
                <span class="penawaran-badge-pill gray">✕ Penawaran Ditolak</span>
              <?php else: ?>
                <span class="penawaran-badge-pill blue">📩 Menunggu Tanggapan</span>
              <?php endif; ?>
            </div>

            <div class="penawaran-meta-sub">
              Kategori: <strong><?php echo htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8'); ?></strong> &bull;
              Durasi: <strong><?php echo htmlspecialchars((string)$offer['duration'], ENT_QUOTES, 'UTF-8'); ?></strong> &bull;
              Gaji: <strong style="color:#1d4ed8;font-weight:800;"><?php echo htmlspecialchars((string)$offer['budget'], ENT_QUOTES, 'UTF-8'); ?></strong> &bull;
              Ditawarkan: <strong><?php echo htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
          </div>

          <!-- MIDDLE INNER BOX -->
          <div class="penawaran-inner-box">
            <div class="employer-info-group">
              <div class="employer-avatar-circle">🏢</div>
              <div>
                <div class="employer-name-row">
                  <span><?php echo htmlspecialchars((string)$offer['employer_display'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <span class="employer-rating-tag">★ 4.9</span>
                </div>
                <div class="employer-sub-info">Pemberi Kerja Verifikasi &bull; KarirHub Partner</div>
                <div class="employer-verified-check">&check; Penawaran resmi dikirimkan ke profil Anda</div>
              </div>
            </div>

            <div class="offer-skills-list">
              <?php foreach ($skillsToShow as $skill): ?>
                <span class="skill-pill-tag"><?php echo htmlspecialchars((string)$skill, ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endforeach; ?>
              <?php if ($remaining > 0): ?>
                <span class="skill-pill-tag more">+<?php echo $remaining; ?></span>
              <?php endif; ?>
            </div>
          </div>

          <!-- CARD FOOTER ACTIONS -->
          <div class="penawaran-card-footer">
            <div class="footer-notice-label">
              <?php if (gig_is_hired_status($appStatus)): ?>
                Proyek ini sudah resmi aktif dan dapat dipantau di Tugas Aktif.
              <?php elseif ($appStatus === 'declined_by_worker'): ?>
                Anda telah menolak penawaran proyek ini.
                <?php 
                  $rejReason = (string)($appMatch['rejection_reason'] ?? '');
                  if ($rejReason === '' && isset($_SESSION['gig_applications'][$appId]['rejection_reason'])) {
                      $rejReason = (string)$_SESSION['gig_applications'][$appId]['rejection_reason'];
                  }
                  if ($rejReason !== ''):
                ?>
                  <div style="font-size:0.78rem;color:#b91c1c;margin-top:4px;font-style:italic;">
                    Alasan: &ldquo;<?php echo htmlspecialchars($rejReason, ENT_QUOTES, 'UTF-8'); ?>&rdquo;
                  </div>
                <?php endif; ?>
              <?php else: ?>
                Penawaran dikirimkan pemberi kerja. Buka detail proyek untuk meninjau persyaratan.
              <?php endif; ?>
            </div>

            <div class="footer-actions-group">
              <?php if (gig_is_hired_status($appStatus)): ?>
                <a class="btn-act-green" href="worker-tugas.php">Buka di Proyek Aktif &rarr;</a>
              <?php elseif ($appStatus === 'declined_by_worker'): ?>
                <a class="btn-act-outline" href="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>">Lihat Detail Proyek</a>
              <?php else: ?>
                <form method="post" action="" style="display:inline-flex;gap:8px;align-items:center;margin:0;">
                  <input type="hidden" name="app_id" value="<?php echo htmlspecialchars($appId, ENT_QUOTES, 'UTF-8'); ?>" />
                  <button type="submit" name="confirm_action" value="confirm" class="btn-act-green">Terima Penawaran</button>
                  <a class="btn-act-outline" href="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>">Profil &amp; Detail Proyek</a>
                  <button type="button" class="btn-act-danger" onclick="openRejectOfferModal('<?php echo htmlspecialchars($appId, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes((string)$offer['project_title']), ENT_QUOTES, 'UTF-8'); ?>')">Tolak Penawaran</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

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
    <form method="post" action="" id="reject-offer-form" style="margin:0;" onsubmit="return validateRejectForm()">
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

<?php require __DIR__ . '/includes/worker-layout-end.php'; ?>

