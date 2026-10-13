<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/worker-auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/project-schedule.php';
require_once __DIR__ . '/includes/project-applications.php';

$flashMsg = '';
$flashErr = false;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $completionAction = (string)($_POST['completion_action'] ?? '');
    if ($completionAction === 'confirm_project_finished') {
        $contractId = trim((string)($_POST['contract_id'] ?? ''));
        $vacancyId = trim((string)($_POST['vacancy_id'] ?? ''));
        $projectTitle = trim((string)($_POST['project_title'] ?? 'proyek'));
        $employerUsername = trim((string)($_POST['employer_username'] ?? ''));
        $res = gig_confirm_project_finished($contractId, 'worker', $username);
        if (empty($res['ok'])) {
            $flashMsg = (string)($res['error'] ?? 'Gagal menyimpan konfirmasi penyelesaian proyek.');
            $flashErr = true;
        } else {
            $state = $res['state'] ?? gig_project_completion_status($contractId);
            if (!empty($state['both_confirmed'])) {
                $flashMsg = 'Konfirmasi penyelesaian lengkap dari kedua pihak. Anda dan pemberi kerja sekarang dapat saling memberi ulasan.';
                gig_add_worker_notification(
                    $username,
                    'project_finish_ready_review',
                    'Proyek Siap Dinilai',
                    'Kedua pihak sudah konfirmasi selesai untuk proyek "' . $projectTitle . '". Silakan beri ulasan.',
                    $vacancyId
                );
                if ($employerUsername !== '') {
                    gig_add_employer_notification(
                        $employerUsername,
                        'project_finish_ready_review',
                        'Proyek Siap Dinilai',
                        'Anda dan Gig Worker sudah sama-sama konfirmasi selesai untuk proyek "' . $projectTitle . '". Silakan beri ulasan.',
                        $vacancyId
                    );
                }
            } elseif (empty($res['already_confirmed'])) {
                $flashMsg = 'Konfirmasi Anda tersimpan. Menunggu konfirmasi dari pemberi kerja agar fitur ulasan terbuka.';
                gig_add_worker_notification(
                    $username,
                    'project_finish_waiting',
                    'Menunggu Konfirmasi Pemberi Kerja',
                    'Anda sudah konfirmasi proyek "' . $projectTitle . '" selesai. Menunggu pemberi kerja konfirmasi.',
                    $vacancyId
                );
            } else {
                $flashMsg = 'Anda sudah pernah mengonfirmasi penyelesaian proyek ini. Menunggu konfirmasi dari pemberi kerja.';
            }
            $flashErr = false;
        }
    }

    $action = (string)($_POST['ext_action'] ?? '');
    if ($action === 'request_extension') {
        $contractId = trim((string)($_POST['contract_id'] ?? ''));
        $amount = (int)($_POST['ext_amount'] ?? 0);
        $unit = trim((string)($_POST['ext_unit'] ?? 'day'));
        $reason = trim((string)($_POST['ext_reason'] ?? ''));
        $res = gig_request_project_extension($contractId, 'worker', $username, $amount, $unit, $reason);
        $flashMsg = !empty($res['ok'])
            ? 'Pengajuan perpanjangan dikirim. Menunggu konfirmasi pemberi kerja.'
            : (string)($res['error'] ?? 'Gagal mengajukan perpanjangan.');
        $flashErr = empty($res['ok']);
    } elseif ($action === 'approve_extension' || $action === 'reject_extension') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $res = gig_decide_project_extension($requestId, 'worker', $action === 'approve_extension');
        $flashMsg = !empty($res['ok'])
            ? (($action === 'approve_extension') ? 'Perpanjangan disetujui. Deadline proyek diperbarui.' : 'Perpanjangan ditolak. Deadline tetap sesuai kesepakatan.')
            : (string)($res['error'] ?? 'Gagal memproses konfirmasi perpanjangan.');
        $flashErr = empty($res['ok']);
    } elseif ($action === 'cancel_extension') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $res = gig_cancel_project_extension($requestId, 'worker', $username);
        $errMsg = (string)($res['error'] ?? '');
        if (str_contains(strtolower($errMsg), 'sudah diproses')) {
            // Treat as idempotent cancel: no need to show noisy error banner.
            $flashMsg = '';
            $flashErr = false;
        } else {
            $flashMsg = !empty($res['ok'])
                ? 'Pengajuan perpanjangan berhasil dibatalkan.'
                : ($errMsg !== '' ? $errMsg : 'Gagal membatalkan pengajuan perpanjangan.');
            $flashErr = empty($res['ok']);
        }
    }
}

$pageTitle = 'Proyek Aktif';
$pageKey = 'tugas';
$breadcrumbCurrent = 'Proyek Aktif';
require __DIR__ . '/includes/worker-layout-start.php';

$workerEmail = (string)($_SESSION['siapkerja_email'] ?? '');
$activeProjects = gig_worker_ongoing_active_projects($username, $workerEmail);
?>

<div class="page-toolbar">
  <h1>Proyek Aktif</h1>
  <div style="font-size:0.82rem;color:var(--text-muted);background:#f1f5f9;padding:6px 14px;border-radius:9999px;font-weight:600;">
    Koordinasi langsung mitra freelancer &amp; pemberi kerja
  </div>
</div>

<?php if ($flashMsg !== ''): ?>
  <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:0.84rem;font-weight:700;<?php echo $flashErr ? 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;' : 'background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;'; ?>">
    <?php echo htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8'); ?>
  </div>
<?php endif; ?>

<div class="active-projects-list">
  <?php if (count($activeProjects) === 0): ?>
    <div class="white-card" style="text-align:center;padding:48px 24px;">
      <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:6px;">Tidak ada proyek aktif</h3>
      <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:16px;">Proyek yang sudah selesai ada di Riwayat Proyek.</p>
      <a class="btn-primary-add" href="worker-riwayat.php" style="display:inline-flex;text-decoration:none;">Buka Riwayat Proyek</a>
    </div>
  <?php endif; ?>

  <?php foreach ($activeProjects as $idx => $proj): ?>
    <?php
      $pendingExt = is_array($proj['pending_extension'] ?? null) ? $proj['pending_extension'] : null;
      $pendingBy = (string)($pendingExt['requester_role'] ?? '');
      $isExpired = !empty($proj['is_expired']);
      $confirmState = is_array($proj['completion_confirmation'] ?? null) ? $proj['completion_confirmation'] : gig_project_completion_status((string)($proj['contract_id'] ?? ''));
      $reviewState = is_array($proj['review_status'] ?? null) ? $proj['review_status'] : gig_project_review_status((string)($proj['contract_id'] ?? ''));
      $canRequestExt = !$isExpired && !$pendingExt;
      $extModalId = 'ext-modal-worker-' . (int)$idx;
    ?>
    <div class="active-project-card" style="margin-bottom:20px;">
      <div class="active-proj-header">
        <div>
          <div class="active-proj-title" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <a href="worker-project-detail.php?id=<?php echo urlencode((string)$proj['id']); ?>" style="color:inherit;text-decoration:none;">
              <?php echo htmlspecialchars((string)$proj['title'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php if (gig_is_remote_work($proj)): ?>
              <span class="badge-status" style="padding:3px 10px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">🌐 Remote</span>
            <?php else: ?>
              <span class="badge-status" style="padding:3px 10px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;">🏢 On-site / Hybrid</span>
            <?php endif; ?>
            <?php if ($isExpired): ?>
              <span style="display:inline-block;padding:3px 10px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#fee2e2;color:#b91c1c;">Tidak Selesai</span>
            <?php else: ?>
              <span class="<?php echo htmlspecialchars((string)$proj['status_badge_class'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$proj['status_label'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          </div>
          <div style="font-size:0.78rem;color:var(--text-muted);margin-top:4px;">
            Mulai: <strong><?php echo htmlspecialchars((string)$proj['hired_label'], ENT_QUOTES, 'UTF-8'); ?></strong> &bull;
            Durasi Disepakati: <strong><?php echo htmlspecialchars((string)$proj['duration'], ENT_QUOTES, 'UTF-8'); ?></strong> &bull;
            Lokasi: <strong><?php echo htmlspecialchars((string)($proj['location'] ?? 'Indonesia'), ENT_QUOTES, 'UTF-8'); ?></strong>
          </div>
        </div>
      </div>

      <div class="active-proj-body" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
        <div class="freelancer-profile-box" style="flex:1;min-width:200px;">
          <div class="fl-avatar" style="background:#1d4ed8;color:#fff;font-weight:800;font-size:1.1rem;">🏢</div>
          <div>
            <div class="fl-info-name">
              <a href="employer-profile.php?name=<?php echo urlencode((string)$proj['employer']); ?>" style="color:inherit;text-decoration:none;">
                <?php echo htmlspecialchars((string)$proj['employer'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
              <span class="fl-rating-badge">★ 4.9</span>
            </div>
            <div class="fl-info-sub"><?php echo htmlspecialchars((string)$proj['employer_category'], ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        </div>

        <div class="proj-deliverable-box" style="flex:1.2;min-width:240px;background:#ffffff;border:1px solid #cbd5e1;padding:10px 14px;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
          <div style="font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:2px;">Target Deliverable</div>
          <div style="font-size:0.82rem;color:#1e293b;font-weight:600;line-height:1.35;">
            <?php echo htmlspecialchars((string)($proj['deliverables'] ?? ($proj['deliverable_note'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
          </div>
        </div>

        <div class="countdown-widget-box" style="background:#ffffff;border:1px solid #cbd5e1;padding:10px 14px;border-radius:10px;flex:1.1;min-width:280px;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
          <div style="display:flex;flex-direction:column;gap:1px;margin-bottom:6px;">
            <span style="font-size:0.78rem;font-weight:700;color:var(--text-dark);">Countdown Durasi Proyek</span>
            <span style="font-size:0.7rem;color:#475569;font-weight:600;">Tenggat: <?php echo htmlspecialchars((string)$proj['deadline'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="js-project-countdown"
               data-start="<?php echo htmlspecialchars((new DateTimeImmutable((string)$proj['hired_at']))->format(DateTimeInterface::ATOM), ENT_QUOTES, 'UTF-8'); ?>"
               data-deadline="<?php echo htmlspecialchars((string)$proj['deadline_iso'], ENT_QUOTES, 'UTF-8'); ?>"
               id="countdown-worker-<?php echo (int)$idx; ?>">
            <div style="display:flex;gap:6px;text-align:center;">
              <div style="background:#f8fafc;border:1px solid #cbd5e1;padding:4px 4px;border-radius:6px;flex:1;">
                <span class="c-days" style="font-size:1.05rem;font-weight:800;color:#1e293b;display:block;line-height:1.2;"><?php echo (int)$proj['days_left']; ?></span>
                <span style="font-size:0.6rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Hari</span>
              </div>
              <div style="background:#f8fafc;border:1px solid #cbd5e1;padding:4px 4px;border-radius:6px;flex:1;">
                <span class="c-hours" style="font-size:1.05rem;font-weight:800;color:#1e293b;display:block;line-height:1.2;"><?php echo sprintf('%02d', (int)$proj['hours_left']); ?></span>
                <span style="font-size:0.6rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Jam</span>
              </div>
              <div style="background:#f8fafc;border:1px solid #cbd5e1;padding:4px 4px;border-radius:6px;flex:1;">
                <span class="c-mins" style="font-size:1.05rem;font-weight:800;color:#1e293b;display:block;line-height:1.2;"><?php echo sprintf('%02d', (int)$proj['mins_left']); ?></span>
                <span style="font-size:0.6rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Menit</span>
              </div>
              <div style="background:#f8fafc;border:1px solid #cbd5e1;padding:4px 4px;border-radius:6px;flex:1;">
                <span class="c-secs" style="font-size:1.05rem;font-weight:800;color:#2563eb;display:block;line-height:1.2;"><?php echo sprintf('%02d', (int)$proj['secs_left']); ?></span>
                <span style="font-size:0.6rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Detik</span>
              </div>
            </div>
            <div style="margin-top:8px;">
              <div style="display:flex;justify-content:flex-end;font-size:0.68rem;font-weight:700;color:#2563eb;margin-bottom:4px;">
                <span class="c-progress-label">100%</span>
              </div>
              <div style="height:8px;border-radius:9999px;background:#ffffff;border:1px solid #bfdbfe;overflow:hidden;">
                <div class="c-progress-fill" style="height:100%;width:100%;background:linear-gradient(90deg,#3b82f6,#2563eb);border-radius:9999px;transition:width .7s linear;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="active-proj-actions" style="justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <?php if (!empty($confirmState['worker_confirmed']) && empty($confirmState['employer_confirmed'])): ?>
          <span style="font-size:0.8rem;color:#1d4ed8;font-weight:700;">✓ Anda sudah konfirmasi selesai. Menunggu konfirmasi pemberi kerja.</span>
        <?php elseif (!empty($confirmState['both_confirmed']) && !empty($reviewState['worker_reviewed'])): ?>
          <span style="font-size:0.8rem;color:var(--text-muted);">✓ Ulasan Anda sudah dikirim. Menunggu ulasan pemberi kerja.</span>
        <?php elseif (!empty($confirmState['both_confirmed'])): ?>
          <span style="font-size:0.8rem;color:#065f46;font-weight:700;">✓ Kedua pihak sudah konfirmasi selesai. Anda bisa memberi ulasan sekarang.</span>
        <?php else: ?>
          <span style="font-size:0.8rem;color:var(--text-muted);">Proyek aktif dan sedang berjalan.</span>
        <?php endif; ?>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <button class="btn-action-sm" type="button"
            data-contact-role="Pemberi Kerja"
            data-contact-name="<?php echo htmlspecialchars((string)$proj['employer'], ENT_QUOTES, 'UTF-8'); ?>"
            data-contact-phone="<?php echo htmlspecialchars((string)$proj['employer_phone'], ENT_QUOTES, 'UTF-8'); ?>"
            data-contact-email="<?php echo htmlspecialchars((string)$proj['employer_email'], ENT_QUOTES, 'UTF-8'); ?>"
            onclick="openContactModal(this)">
            Kontak Pemberi Kerja
          </button>
          <button class="btn-outline-blue" type="button"
            <?php if ($canRequestExt): ?>
              onclick="openExtensionModal('<?php echo $extModalId; ?>')"
            <?php else: ?>
              disabled style="background:#e5e7eb;color:#6b7280;border-color:#d1d5db;cursor:not-allowed;"
            <?php endif; ?>
          >
            Ajukan Perpanjangan
          </button>
          <?php if (empty($confirmState['worker_confirmed'])): ?>
            <form method="post" action="" style="margin:0;">
              <input type="hidden" name="completion_action" value="confirm_project_finished">
              <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars((string)($proj['contract_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="vacancy_id" value="<?php echo htmlspecialchars((string)($proj['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="project_title" value="<?php echo htmlspecialchars((string)($proj['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="employer_username" value="<?php echo htmlspecialchars((string)($proj['employer'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              <button class="btn-create-post" type="submit" style="text-decoration:none;padding:6px 14px;font-size:0.82rem;background:#059669;border-color:#047857;">
                ✓ Konfirmasi Proyek Selesai
              </button>
            </form>
          <?php elseif (empty($confirmState['both_confirmed'])): ?>
            <button class="btn-outline-blue" type="button" disabled style="padding:6px 14px;font-size:0.82rem;opacity:0.65;cursor:not-allowed;">
              Menunggu Konfirmasi Pemberi Kerja
            </button>
          <?php elseif (!empty($reviewState['worker_reviewed'])): ?>
            <button class="btn-outline-blue" type="button" disabled style="padding:6px 14px;font-size:0.82rem;opacity:0.65;cursor:not-allowed;">
              Ulasan Anda Terkirim
            </button>
          <?php else: ?>
            <a class="btn-create-post" href="worker-rating-employer.php?contract=<?php echo urlencode((string)$proj['contract_id']); ?>" style="text-decoration:none;padding:6px 14px;font-size:0.82rem;background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);box-shadow:0 4px 10px rgba(217,119,6,0.35);">
              ★ Selesaikan &amp; Beri Rating
            </a>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($proj['approved_extension_days']) || !empty($pendingExt)): ?>
        <div style="margin-top:10px;padding-top:10px;border-top:1px dashed #e2e8f0;">
          <?php if (!empty($proj['approved_extension_days'])): ?>
            <div style="font-size:0.78rem;color:#065f46;font-weight:700;margin-bottom:6px;">
              Durasi diperpanjang total <?php echo (int)$proj['approved_extension_days']; ?> hari. Deadline baru: <?php echo htmlspecialchars((string)$proj['deadline'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <?php if ($pendingExt && $pendingBy !== 'worker'): ?>
            <div style="font-size:0.78rem;color:#1e3a8a;font-weight:700;margin-bottom:6px;">
              Pemberi kerja mengajukan perpanjangan <?php echo htmlspecialchars(gig_format_extension_label((int)$pendingExt['amount'], (string)$pendingExt['unit']), ENT_QUOTES, 'UTF-8'); ?>.
            </div>
            <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;">
              <input type="hidden" name="ext_action" value="approve_extension">
              <input type="hidden" name="request_id" value="<?php echo (int)$pendingExt['id']; ?>">
              <button class="btn-action-sm" type="submit" style="background:#059669;color:#fff;border:none;">Setujui Perpanjangan</button>
            </form>
            <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;">
              <input type="hidden" name="ext_action" value="reject_extension">
              <input type="hidden" name="request_id" value="<?php echo (int)$pendingExt['id']; ?>">
              <button class="btn-action-sm" type="submit" style="background:#dc2626;color:#fff;border:none;">Tolak Perpanjangan</button>
            </form>
          <?php elseif ($pendingExt): ?>
            <div style="font-size:0.78rem;color:#92400e;background:#fffbeb;border:1px solid #fde68a;padding:8px 10px;border-radius:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <span>Pengajuan perpanjangan Anda (<?php echo htmlspecialchars(gig_format_extension_label((int)$pendingExt['amount'], (string)$pendingExt['unit']), ENT_QUOTES, 'UTF-8'); ?>) menunggu konfirmasi pemberi kerja.</span>
              <form method="post" style="margin:0;">
                <input type="hidden" name="ext_action" value="cancel_extension">
                <input type="hidden" name="request_id" value="<?php echo (int)$pendingExt['id']; ?>">
                <button class="btn-action-sm" type="submit" style="background:#f8fafc;border:1px solid #f59e0b;color:#92400e;padding:4px 10px;">Batalkan Pengajuan</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

        <?php if ($canRequestExt): ?>
          <div id="<?php echo $extModalId; ?>" style="display:none;position:fixed;inset:0;z-index:1200;background:rgba(15,23,42,0.45);padding:16px;">
            <div style="max-width:560px;margin:7vh auto 0;background:#fff;border-radius:14px;box-shadow:0 20px 50px rgba(15,23,42,0.24);overflow:hidden;">
              <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid #e2e8f0;">
                <strong style="font-size:0.95rem;color:#0f172a;">Ajukan Perpanjangan Durasi</strong>
                <button type="button" onclick="closeExtensionModal('<?php echo $extModalId; ?>')" style="border:none;background:#f1f5f9;color:#334155;border-radius:8px;padding:4px 8px;cursor:pointer;">Tutup</button>
              </div>
              <form method="post" style="padding:14px;display:flex;flex-direction:column;gap:10px;">
                <input type="hidden" name="ext_action" value="request_extension">
                <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars((string)$proj['contract_id'], ENT_QUOTES, 'UTF-8'); ?>">
                <label style="font-size:0.8rem;font-weight:700;color:#334155;">Jumlah Perpanjangan</label>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                  <input type="number" name="ext_amount" min="1" max="12" value="1" required style="width:92px;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;">
                  <select name="ext_unit" style="padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;">
                    <option value="day">Hari</option>
                    <option value="week">Minggu</option>
                    <option value="month">Bulan</option>
                  </select>
                </div>
                <label style="font-size:0.8rem;font-weight:700;color:#334155;">Alasan Perpanjangan</label>
                <textarea name="ext_reason" required rows="3" placeholder="Jelaskan alasan kenapa butuh tambahan durasi" style="resize:vertical;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;"></textarea>
                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px;">
                  <button type="button" class="btn-outline-blue" onclick="closeExtensionModal('<?php echo $extModalId; ?>')">Batal</button>
                  <button class="btn-action-sm" type="submit" style="background:#2563eb;color:#fff;border:none;">Kirim Pengajuan</button>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>
  <?php endforeach; ?>
</div>

<script>
function openExtensionModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  // Move modal to <body> so fixed positioning isn't trapped by transformed card containers.
  if (modal.parentElement !== document.body) {
    document.body.appendChild(modal);
  }
  modal.style.display = 'block';
}

function closeExtensionModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  modal.style.display = 'none';
}

document.addEventListener('click', function(e) {
  if (e.target && e.target.id && e.target.id.indexOf('ext-modal-worker-') === 0) {
    e.target.style.display = 'none';
  }
});

function openContactModal(button) {
  if (!button) return;
  const role = button.getAttribute('data-contact-role') || 'Kontak';
  const name = button.getAttribute('data-contact-name') || '-';
  const phone = button.getAttribute('data-contact-phone') || '-';
  const email = button.getAttribute('data-contact-email') || '-';
  const modal = document.getElementById('contact-info-modal');
  if (!modal) return;
  const title = modal.querySelector('[data-contact-title]');
  const nameEl = modal.querySelector('[data-contact-name]');
  const phoneEl = modal.querySelector('[data-contact-phone]');
  const emailEl = modal.querySelector('[data-contact-email]');
  if (title) title.textContent = 'Info Kontak ' + role;
  if (nameEl) nameEl.textContent = name;
  if (phoneEl) phoneEl.textContent = phone;
  if (emailEl) emailEl.textContent = email;
  modal.style.display = 'block';
}

function closeContactModal() {
  const modal = document.getElementById('contact-info-modal');
  if (!modal) return;
  modal.style.display = 'none';
}

(function startWorkerCountdowns() {
  function pad(n) { return n < 10 ? '0' + n : String(n); }
  function tick() {
    document.querySelectorAll('.js-project-countdown').forEach(function(container) {
      const iso = container.getAttribute('data-deadline');
      const startIso = container.getAttribute('data-start');
      if (!iso) return;
      const start = startIso ? new Date(startIso).getTime() : NaN;
      const end = new Date(iso).getTime();
      const remainingMs = Math.max(0, end - Date.now());
      let ms = remainingMs;
      const days = Math.floor(ms / 86400000);
      ms -= days * 86400000;
      const hours = Math.floor(ms / 3600000);
      ms -= hours * 3600000;
      const mins = Math.floor(ms / 60000);
      const secs = Math.floor((ms - mins * 60000) / 1000);
      const d = container.querySelector('.c-days');
      const h = container.querySelector('.c-hours');
      const m = container.querySelector('.c-mins');
      const s = container.querySelector('.c-secs');
      const fill = container.querySelector('.c-progress-fill');
      const label = container.querySelector('.c-progress-label');
      if (d) d.textContent = String(days);
      if (h) h.textContent = pad(hours);
      if (m) m.textContent = pad(mins);
      if (s) s.textContent = pad(secs);
      if (fill) {
        const total = Number.isFinite(start) && end > start ? (end - start) : 0;
        let pct = 0;
        if (total > 0) {
          pct = (remainingMs / total) * 100;
        } else if (remainingMs > 0) {
          pct = 100;
        }
        if (pct < 0) pct = 0;
        if (pct > 100) pct = 100;
        fill.style.width = pct.toFixed(2) + '%';
        if (label) label.textContent = Math.round(pct) + '%';
      }
    });
  }
  tick();
  setInterval(tick, 1000);
})();
</script>

<div id="contact-info-modal" style="display:none;position:fixed;inset:0;z-index:1250;background:rgba(15,23,42,0.45);padding:16px;" onclick="if(event.target===this){closeContactModal();}">
  <div style="max-width:500px;margin:10vh auto 0;background:#fff;border-radius:14px;box-shadow:0 20px 50px rgba(15,23,42,0.24);overflow:hidden;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid #e2e8f0;">
      <strong data-contact-title style="font-size:0.95rem;color:#0f172a;">Info Kontak</strong>
      <button type="button" onclick="closeContactModal()" style="border:none;background:#f1f5f9;color:#334155;border-radius:8px;padding:4px 8px;cursor:pointer;">Tutup</button>
    </div>
    <div style="padding:14px;display:grid;gap:8px;font-size:0.86rem;color:#1e293b;">
      <div><strong>Nama:</strong> <span data-contact-name>-</span></div>
      <div><strong>Telepon/WhatsApp:</strong> <span data-contact-phone>-</span></div>
      <div><strong>Email:</strong> <span data-contact-email>-</span></div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/worker-layout-end.php'; ?>
