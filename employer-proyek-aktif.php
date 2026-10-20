<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/employer-auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/project-schedule.php';
require_once __DIR__ . '/includes/project-applications.php';

$flashMsg = '';
$flashErr = false;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string)($_POST['completion_action'] ?? '');
    if ($action === 'confirm_project_finished') {
        $contractId = trim((string)($_POST['contract_id'] ?? ''));
        $workerId = trim((string)($_POST['worker_id'] ?? ''));
        $workerName = trim((string)($_POST['worker_name'] ?? 'Gig Worker'));
        $vacancyId = trim((string)($_POST['vacancy_id'] ?? ''));
        $projectTitle = trim((string)($_POST['project_title'] ?? 'proyek'));
        $res = gig_confirm_project_finished($contractId, 'employer', $username);
        if (empty($res['ok'])) {
            $flashMsg = (string)($res['error'] ?? 'Gagal menyimpan konfirmasi penyelesaian proyek.');
            $flashErr = true;
        } else {
            $state = $res['state'] ?? gig_project_completion_status($contractId);
            if (!empty($state['both_confirmed'])) {
                $flashMsg = 'Konfirmasi penyelesaian lengkap dari kedua pihak. Anda dan Gig Worker sekarang dapat saling memberi ulasan.';
                gig_add_employer_notification(
                    $username,
                    'project_finish_ready_review',
                    'Proyek Siap Dinilai',
                    'Kedua pihak sudah konfirmasi selesai untuk proyek "' . $projectTitle . '". Silakan beri ulasan.',
                    $vacancyId
                );
                if ($workerId !== '') {
                    gig_add_worker_notification(
                        $workerId,
                        'project_finish_ready_review',
                        'Proyek Siap Dinilai',
                        'Anda dan pemberi kerja sudah sama-sama konfirmasi selesai untuk proyek "' . $projectTitle . '". Silakan beri ulasan.',
                        $vacancyId
                    );
                }
            } elseif (empty($res['already_confirmed'])) {
                $flashMsg = 'Konfirmasi Anda tersimpan. Menunggu konfirmasi dari Gig Worker agar fitur ulasan terbuka.';
                gig_add_employer_notification(
                    $username,
                    'project_finish_waiting',
                    'Menunggu Konfirmasi Gig Worker',
                    'Anda sudah konfirmasi proyek "' . $projectTitle . '" selesai. Menunggu ' . $workerName . ' konfirmasi.',
                    $vacancyId
                );
            } else {
                $flashMsg = 'Anda sudah pernah mengonfirmasi penyelesaian proyek ini. Menunggu konfirmasi dari Gig Worker.';
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
        $res = gig_request_project_extension($contractId, 'employer', $username, $amount, $unit, $reason);
        $flashMsg = !empty($res['ok'])
            ? 'Pengajuan perpanjangan dikirim. Menunggu konfirmasi gig worker.'
            : (string)($res['error'] ?? 'Gagal mengajukan perpanjangan.');
        $flashErr = empty($res['ok']);
    } elseif ($action === 'approve_extension' || $action === 'reject_extension') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $res = gig_decide_project_extension($requestId, 'employer', $action === 'approve_extension');
        $flashMsg = !empty($res['ok'])
            ? (($action === 'approve_extension') ? 'Perpanjangan disetujui. Deadline proyek diperbarui.' : 'Perpanjangan ditolak. Deadline tetap sesuai kesepakatan.')
            : (string)($res['error'] ?? 'Gagal memproses konfirmasi perpanjangan.');
        $flashErr = empty($res['ok']);
    }
}

$p1 = gig_demo_active_project_by_id('GIG-2026-09-001');
$p2 = gig_demo_active_project_by_id('GIG-2026-09-002');

$pageTitle = 'Proyek Aktif';
$pageKey = 'aktif';
$breadcrumbCurrent = 'Proyek Aktif';
require __DIR__ . '/includes/employer-layout-start.php';
?>

    <div class="page-toolbar">
      <h1>Proyek Aktif</h1>
      <div style="font-size:0.82rem;color:var(--text-muted);background:#f1f5f9;padding:6px 12px;border-radius:9999px;">
        Koordinasi langsung perusahaan &amp; mitra
      </div>
    </div>
    <?php if ($flashMsg !== ''): ?>
      <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:0.84rem;font-weight:700;<?php echo $flashErr ? 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;' : 'background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;'; ?>">
        <?php echo htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <div class="active-projects-list">
      <?php
        $c1Id = 'CTR-GIG-2026-0811';
        $c2Id = 'CTR-GIG-2026-0819';
        $reviewP1 = is_array($p1['review_status'] ?? null) ? $p1['review_status'] : gig_project_review_status($c1Id);
        $reviewP2 = is_array($p2['review_status'] ?? null) ? $p2['review_status'] : gig_project_review_status($c2Id);
        $completedP1 = !empty($reviewP1['both_reviewed']);
        $completedP2 = !empty($reviewP2['both_reviewed']);
        $ratingP1 = $_SESSION['completed_projects'][$c1Id]['ratingGiven'] ?? 5;
        $ratingP2 = $_SESSION['completed_projects'][$c2Id]['ratingGiven'] ?? 5;
        $isExpiredP1 = !empty($p1['is_expired']);
        $isExpiredP2 = !empty($p2['is_expired']);
        $pendingExtP1 = is_array($p1['pending_extension'] ?? null) ? $p1['pending_extension'] : null;
        $pendingExtP2 = is_array($p2['pending_extension'] ?? null) ? $p2['pending_extension'] : null;
        $confirmP1 = is_array($p1['completion_confirmation'] ?? null) ? $p1['completion_confirmation'] : gig_project_completion_status($c1Id);
        $confirmP2 = is_array($p2['completion_confirmation'] ?? null) ? $p2['completion_confirmation'] : gig_project_completion_status($c2Id);
        $isDemoEmployer = in_array(strtolower(trim((string)$username)), ['pt abc', 'pt talenta digital indonesia', 'pt. abc', 'pt. talenta digital indonesia', 'perusahaan', 'pemberi kerja', 'employer'], true);
        $ownsP1 = $isDemoEmployer || strcasecmp((string)($p1['employer'] ?? ''), (string)$username) === 0;
        $ownsP2 = $isDemoEmployer || strcasecmp((string)($p2['employer'] ?? ''), (string)$username) === 0;
        $showP1 = !$completedP1 && !$isExpiredP1 && $ownsP1;
        $showP2 = !$completedP2 && !$isExpiredP2 && $ownsP2;
        $hasActive = $showP1 || $showP2;
      ?>

      <?php if (!$hasActive): ?>
        <div class="white-card" style="text-align:center;padding:48px 24px;">
          <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:6px;">Tidak ada proyek aktif</h3>
          <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:16px;">Semua proyek yang sudah selesai ada di Riwayat Proyek.</p>
          <a class="btn-create-post" href="employer-riwayat-proyek.php" style="text-decoration:none;display:inline-flex;">Buka Riwayat Proyek</a>
        </div>
      <?php endif; ?>

      <?php if ($showP1): ?>
      <!-- Project 1 -->
      <div class="active-project-card" id="project-GIG-2026-09-001" style="<?php echo $completedP1 ? 'border-left-color:#10b981;background:#f0fdf4;' : ''; ?>">
        <div class="active-proj-header">
          <div>
            <div class="active-proj-title">
              <span><?php echo htmlspecialchars((string)($p1['title'] ?? 'Redesign UI/UX Dashboard Prototype KarirHub'), ENT_QUOTES, 'UTF-8'); ?></span>
              <?php if (gig_is_remote_work($p1)): ?>
                <span class="badge-status" style="padding:4px 10px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">🌐 Remote</span>
              <?php else: ?>
                <span class="badge-status" style="padding:4px 10px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;">🏢 On-site / Hybrid</span>
              <?php endif; ?>
              <?php if ($isExpiredP1 && !$completedP1): ?>
                <span class="badge-status cancelled" style="padding:4px 12px;border-radius:9999px;font-size:0.75rem;font-weight:700;">Tidak Selesai</span>
              <?php else: ?>
                <span class="badge-status in-progress" style="padding:4px 12px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;display:inline-flex;align-items:center;gap:6px;">
                  <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#2563eb;"></span>
                  <?php echo htmlspecialchars((string)($p1['status_label'] ?? 'Berjalan'), ENT_QUOTES, 'UTF-8'); ?>
                </span>
              <?php endif; ?>
              <?php if ($completedP1): ?>
                <span style="display:inline-block;padding:4px 12px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#d1fae5;color:#047857;">✓ Selesai &amp; Dinilai</span>
              <?php endif; ?>
            </div>
            <div style="font-size:0.8rem;color:#64748b;margin-top:4px;">
              Mulai: <strong style="color:#334155;"><?php echo htmlspecialchars($p1['hired_label'], ENT_QUOTES, 'UTF-8'); ?></strong> &bull; Durasi Disepakati: <strong style="color:#334155;"><?php echo htmlspecialchars($p1['duration'], ENT_QUOTES, 'UTF-8'); ?></strong> &bull; Lokasi: <strong style="color:#334155;"><?php echo htmlspecialchars((string)($p1['location'] ?? 'Jakarta Selatan'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
          </div>
        </div>

        <div class="active-proj-body">
          <div class="freelancer-profile-box" style="flex:1;min-width:210px;">
            <div class="fl-avatar" style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;font-size:1.05rem;box-shadow:0 4px 12px rgba(37,99,235,0.25);flex-shrink:0;">T</div>
            <div>
              <div class="fl-info-name" style="display:flex;align-items:center;gap:8px;">
                <a href="worker-profile.php?active=1&id=tessa" style="color:#0f172a;text-decoration:none;font-weight:800;font-size:0.95rem;">Theressa Zaratrusha</a>
                <span class="fl-rating-badge" style="background:#fffbeeb;color:#b45309;border:1px solid #fef3c7;border-radius:9999px;padding:2px 8px;font-size:0.72rem;font-weight:700;">★ 5</span>
              </div>
              <div class="fl-info-sub" style="font-size:0.78rem;color:#64748b;margin-top:2px;">Lead UI/UX Designer</div>
            </div>
          </div>

          <div class="proj-deliverable-box" style="flex:1.2;min-width:240px;background:#ffffff;border:1px solid #cbd5e1;padding:12px 14px;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:3px;">Target Deliverable</div>
            <div style="font-size:0.83rem;color:#1e293b;font-weight:600;line-height:1.4;">
              <?php echo htmlspecialchars((string)($p1['deliverables'] ?? 'Prototype interaktif 12+ layar, design system component kit di Figma, dan panduan handoff developer'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
          </div>

          <!-- Countdown Widget -->
          <div class="countdown-widget-box" style="background:#ffffff;border:1px solid #cbd5e1;padding:12px 14px;border-radius:10px;flex:1.1;min-width:280px;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
              <span style="font-size:0.78rem;font-weight:700;color:#0f172a;">Countdown Durasi Proyek</span>
              <span style="font-size:0.7rem;color:#475569;font-weight:600;">Tenggat: <?php echo htmlspecialchars($p1['deadline'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="js-project-countdown"
                 data-start="<?php echo htmlspecialchars((new DateTimeImmutable((string)$p1['hired_at']))->format(DateTimeInterface::ATOM), ENT_QUOTES, 'UTF-8'); ?>"
                 data-deadline="<?php echo htmlspecialchars($p1['deadline_iso'], ENT_QUOTES, 'UTF-8'); ?>"
                 id="countdown-proj-1">
              <div style="display:flex;gap:6px;text-align:center;">
                <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:5px 4px;border-radius:8px;flex:1;">
                  <span class="c-days" style="font-size:1.1rem;font-weight:800;color:#0f172a;display:block;line-height:1.1;"><?php echo (int)$p1['days_left']; ?></span>
                  <span style="font-size:0.6rem;color:#64748b;text-transform:uppercase;font-weight:700;">Hari</span>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:5px 4px;border-radius:8px;flex:1;">
                  <span class="c-hours" style="font-size:1.1rem;font-weight:800;color:#0f172a;display:block;line-height:1.1;"><?php echo sprintf('%02d', (int)$p1['hours_left']); ?></span>
                  <span style="font-size:0.6rem;color:#64748b;text-transform:uppercase;font-weight:700;">Jam</span>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:5px 4px;border-radius:8px;flex:1;">
                  <span class="c-mins" style="font-size:1.1rem;font-weight:800;color:#0f172a;display:block;line-height:1.1;"><?php echo sprintf('%02d', (int)$p1['mins_left']); ?></span>
                  <span style="font-size:0.6rem;color:#64748b;text-transform:uppercase;font-weight:700;">Menit</span>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:5px 4px;border-radius:8px;flex:1;">
                  <span class="c-secs" style="font-size:1.1rem;font-weight:800;color:#2563eb;display:block;line-height:1.1;"><?php echo sprintf('%02d', (int)$p1['secs_left']); ?></span>
                  <span style="font-size:0.6rem;color:#64748b;text-transform:uppercase;font-weight:700;">Detik</span>
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

        <?php if (!empty($p1['approved_extension_days'])): ?>
          <div style="margin:12px 0 0 0;font-size:0.78rem;color:#065f46;background:#ecfdf5;border:1px solid #a7f3d0;padding:8px 12px;border-radius:8px;">
            Disetujui: <?php echo htmlspecialchars(gig_format_extension_label((int)$p1['approved_extension_days'], 'day'), ENT_QUOTES, 'UTF-8'); ?>. Deadline sudah diperbarui.
          </div>
        <?php endif; ?>

        <?php if ($isExpiredP1): ?>
          <div style="margin:12px 0 0 0;font-size:0.78rem;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;padding:8px 12px;border-radius:8px;">
            Deadline terlewati dan proyek belum selesai. Status otomatis menjadi <strong>Tidak Selesai</strong>.
          </div>
        <?php elseif ($pendingExtP1): ?>
          <div style="margin:12px 0 0 0;font-size:0.78rem;color:#1e293b;background:#eff6ff;border:1px solid #bfdbfe;padding:10px 12px;border-radius:8px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <div>
              Pengajuan perpanjangan pending dari <strong><?php echo ($pendingExtP1['requester_role'] ?? '') === 'worker' ? 'Gig Worker' : 'Pemberi Kerja'; ?></strong>:
              <strong><?php echo htmlspecialchars(gig_format_extension_label((int)($pendingExtP1['amount'] ?? 0), (string)($pendingExtP1['unit'] ?? 'day')), ENT_QUOTES, 'UTF-8'); ?></strong>
              <?php if (!empty($pendingExtP1['reason_note'])): ?>
                <span style="color:#334155;margin-left:6px;">(Alasan: <?php echo htmlspecialchars((string)$pendingExtP1['reason_note'], ENT_QUOTES, 'UTF-8'); ?>)</span>
              <?php endif; ?>
            </div>
            <?php if (($pendingExtP1['requester_role'] ?? '') !== 'employer'): ?>
              <div style="display:flex;gap:8px;align-items:center;">
                <form method="POST" action="" style="margin:0;">
                  <input type="hidden" name="ext_action" value="approve_extension">
                  <input type="hidden" name="request_id" value="<?php echo (int)$pendingExtP1['id']; ?>">
                  <button type="submit" class="btn-create-post" style="padding:4px 10px;font-size:0.75rem;background:#059669;border-color:#047857;">Setujui</button>
                </form>
                <form method="POST" action="" style="margin:0;">
                  <input type="hidden" name="ext_action" value="reject_extension">
                  <input type="hidden" name="request_id" value="<?php echo (int)$pendingExtP1['id']; ?>">
                  <button type="submit" class="btn-outline-blue" style="padding:4px 10px;font-size:0.75rem;">Tolak</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Extension Modal -->
        <div id="ext-modal-employer-p1" style="display:none;position:fixed;inset:0;z-index:1200;background:rgba(15,23,42,0.45);padding:16px;">
          <div style="max-width:560px;margin:7vh auto 0;background:#fff;border-radius:14px;box-shadow:0 20px 50px rgba(15,23,42,0.24);overflow:hidden;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid #e2e8f0;">
              <strong style="font-size:0.95rem;color:#0f172a;">Ajukan Perpanjangan Durasi</strong>
              <button type="button" onclick="closeExtensionModal('ext-modal-employer-p1')" style="border:none;background:#f1f5f9;color:#334155;border-radius:8px;padding:4px 8px;cursor:pointer;">Tutup</button>
            </div>
            <form method="POST" action="" style="padding:14px;display:flex;flex-direction:column;gap:10px;">
              <input type="hidden" name="ext_action" value="request_extension">
              <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars((string)$p1['contract_id'], ENT_QUOTES, 'UTF-8'); ?>">
              <label style="font-size:0.8rem;font-weight:700;color:#334155;">Jumlah Perpanjangan</label>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="number" min="1" name="ext_amount" value="1" required style="width:92px;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;">
                <select name="ext_unit" style="padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;">
                  <option value="day">Hari</option>
                  <option value="week">Minggu</option>
                  <option value="month">Bulan</option>
                </select>
              </div>
              <label style="font-size:0.8rem;font-weight:700;color:#334155;">Alasan Perpanjangan</label>
              <textarea name="ext_reason" required rows="3" placeholder="Jelaskan alasan kenapa butuh tambahan durasi" style="resize:vertical;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;"></textarea>
              <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px;">
                <button type="button" class="btn-outline-blue" onclick="closeExtensionModal('ext-modal-employer-p1')">Batal</button>
                <button type="submit" class="btn-action-sm">Kirim Pengajuan</button>
              </div>
            </form>
          </div>
        </div>

        <div class="active-proj-actions" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;">
          <div>
            <?php if ($completedP1): ?>
              <span style="font-size:0.82rem;color:#047857;font-weight:700;display:inline-flex;align-items:center;gap:6px;">
               <span>⭐</span> Rating Diberikan: <?php echo (int)$ratingP1; ?>/5
              </span>
            <?php else: ?>
              <?php if (!empty($confirmP1['employer_confirmed']) && empty($confirmP1['worker_confirmed'])): ?>
                <span style="font-size:0.8rem;color:#1d4ed8;font-weight:700;">✓ Anda sudah konfirmasi selesai. Menunggu konfirmasi Gig Worker.</span>
              <?php elseif (!empty($confirmP1['both_confirmed']) && !empty($reviewP1['employer_reviewed'])): ?>
                <span style="font-size:0.8rem;color:#64748b;">✓ Anda sudah kirim ulasan. Menunggu ulasan dari Gig Worker.</span>
              <?php elseif (!empty($confirmP1['both_confirmed'])): ?>
                <span style="font-size:0.8rem;color:#065f46;font-weight:700;">✓ Kedua pihak sudah konfirmasi selesai. Anda bisa memberi ulasan sekarang.</span>
              <?php else: ?>
                <span style="font-size:0.8rem;color:#64748b;">✓ Koordinasi aktif &bull; Konfirmasi selesai setelah deliverable benar-benar selesai.</span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <button class="btn-action-sm" type="button"
              data-contact-role="Gig Worker"
              data-contact-name="Theressa Zaratrusha"
              data-contact-phone="0812-3456-7890"
              data-contact-email="theressaz@pasker.id"
              onclick="openContactModal(this)"
              style="padding:7px 14px;font-size:0.82rem;font-weight:600;border-radius:8px;">Kontak Freelancer</button>

            <?php if (!$completedP1 && !$pendingExtP1 && !$isExpiredP1): ?>
              <button type="button" class="btn-outline-blue" onclick="openExtensionModal('ext-modal-employer-p1')" style="padding:7px 14px;font-size:0.82rem;font-weight:600;border-radius:8px;">Ajukan Perpanjangan</button>
            <?php elseif ($pendingExtP1): ?>
              <button type="button" class="btn-outline-blue" disabled style="padding:7px 14px;font-size:0.82rem;font-weight:600;border-radius:8px;opacity:0.65;cursor:not-allowed;">Perpanjangan Pending</button>
            <?php endif; ?>

            <?php if ($completedP1): ?>
              <a class="btn-create-post" href="employer-riwayat-proyek.php" style="text-decoration:none;padding:7px 16px;font-size:0.82rem;font-weight:700;border-radius:8px;background:#059669;border-color:#047857;">
                Buka di Riwayat →
              </a>
            <?php elseif (empty($confirmP1['employer_confirmed'])): ?>
              <form method="POST" action="" style="margin:0;">
                <input type="hidden" name="completion_action" value="confirm_project_finished">
                <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars((string)($p1['contract_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="worker_id" value="<?php echo htmlspecialchars((string)($p1['worker_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="worker_name" value="<?php echo htmlspecialchars((string)($p1['worker_name'] ?? 'Gig Worker'), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="vacancy_id" value="<?php echo htmlspecialchars((string)($p1['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="project_title" value="<?php echo htmlspecialchars((string)($p1['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn-create-post" style="text-decoration:none;padding:7px 16px;font-size:0.82rem;font-weight:700;border-radius:8px;background:#059669;border-color:#047857;">
                  ✓ Konfirmasi Proyek Selesai
                </button>
              </form>
            <?php elseif (empty($confirmP1['both_confirmed'])): ?>
              <button type="button" class="btn-outline-blue" disabled style="padding:7px 16px;font-size:0.82rem;font-weight:700;border-radius:8px;opacity:0.65;cursor:not-allowed;">
                Menunggu Konfirmasi Gig Worker
              </button>
            <?php elseif (!empty($reviewP1['employer_reviewed'])): ?>
              <button type="button" class="btn-outline-blue" disabled style="padding:7px 16px;font-size:0.82rem;font-weight:700;border-radius:8px;opacity:0.65;cursor:not-allowed;">
                Ulasan Anda Terkirim
              </button>
            <?php else: ?>
              <a class="btn-create-post" href="employer-rating-worker.php?contract=CTR-GIG-2026-0811&worker=tessa" style="text-decoration:none;padding:7px 16px;font-size:0.82rem;font-weight:700;border-radius:8px;background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);box-shadow:0 4px 12px rgba(217,119,6,0.3);border:none;">
                ★ Selesaikan &amp; Beri Rating
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($showP2): ?>
      <!-- Project 2 -->
      <div class="active-project-card" id="project-GIG-2026-09-002" style="<?php echo $completedP2 ? 'border-left-color:#10b981;background:#f0fdf4;' : ''; ?>">
        <div class="active-proj-header">
          <div>
            <div class="active-proj-title">
              <span><?php echo htmlspecialchars((string)($p2['title'] ?? 'Integrasi REST API Modul Notifikasi SMS & WhatsApp'), ENT_QUOTES, 'UTF-8'); ?></span>
              <?php if (gig_is_remote_work($p2)): ?>
                <span class="badge-status" style="padding:4px 10px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">🌐 Remote</span>
              <?php else: ?>
                <span class="badge-status" style="padding:4px 10px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;">🏢 On-site / Hybrid</span>
              <?php endif; ?>
              <?php if ($isExpiredP2 && !$completedP2): ?>
                <span class="badge-status cancelled" style="padding:4px 12px;border-radius:9999px;font-size:0.75rem;font-weight:700;">Tidak Selesai</span>
              <?php else: ?>
                <span class="badge-status in-progress" style="padding:4px 12px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;display:inline-flex;align-items:center;gap:6px;">
                  <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#2563eb;"></span>
                  <?php echo htmlspecialchars((string)($p2['status_label'] ?? 'Berjalan'), ENT_QUOTES, 'UTF-8'); ?>
                </span>
              <?php endif; ?>
              <?php if ($completedP2): ?>
                <span style="display:inline-block;padding:4px 12px;border-radius:9999px;font-size:0.75rem;font-weight:700;background:#d1fae5;color:#047857;">✓ Selesai &amp; Dinilai</span>
              <?php endif; ?>
            </div>
            <div style="font-size:0.8rem;color:#64748b;margin-top:4px;">
              Mulai: <strong style="color:#334155;"><?php echo htmlspecialchars($p2['hired_label'], ENT_QUOTES, 'UTF-8'); ?></strong> &bull; Durasi Disepakati: <strong style="color:#334155;"><?php echo htmlspecialchars($p2['duration'], ENT_QUOTES, 'UTF-8'); ?></strong> &bull; Lokasi: <strong style="color:#334155;"><?php echo htmlspecialchars((string)($p2['location'] ?? 'Bandung, Jawa Barat'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
          </div>
        </div>

        <div class="active-proj-body">
          <div class="freelancer-profile-box" style="flex:1;min-width:210px;">
            <div class="fl-avatar" style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#0891b2,#0e7490);color:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;font-size:1.05rem;box-shadow:0 4px 12px rgba(8,145,178,0.25);flex-shrink:0;">R</div>
            <div>
              <div class="fl-info-name" style="display:flex;align-items:center;gap:8px;">
                <a href="worker-profile.php?active=1&id=rian" style="color:#0f172a;text-decoration:none;font-weight:800;font-size:0.95rem;">Rian Ardiansyah</a>
                <span class="fl-rating-badge" style="background:#fffbeeb;color:#b45309;border:1px solid #fef3c7;border-radius:9999px;padding:2px 8px;font-size:0.72rem;font-weight:700;">★ 5</span>
              </div>
              <div class="fl-info-sub" style="font-size:0.78rem;color:#64748b;margin-top:2px;">Backend API Developer</div>
            </div>
          </div>

          <div class="proj-deliverable-box" style="flex:1.2;min-width:240px;background:#ffffff;border:1px solid #cbd5e1;padding:12px 14px;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:3px;">Target Deliverable</div>
            <div style="font-size:0.83rem;color:#1e293b;font-weight:600;line-height:1.4;">
              <?php echo htmlspecialchars((string)($p2['deliverables'] ?? 'Endpoint REST API webhook, integrasi modul notifikasi SMS & WhatsApp, serta dokumentasi integrasi API'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
          </div>

          <!-- Countdown Widget -->
          <div class="countdown-widget-box" style="background:#ffffff;border:1px solid #cbd5e1;padding:12px 14px;border-radius:10px;flex:1.1;min-width:280px;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
              <span style="font-size:0.78rem;font-weight:700;color:#0f172a;">Countdown Durasi Proyek</span>
              <span style="font-size:0.7rem;color:#475569;font-weight:600;">Tenggat: <?php echo htmlspecialchars($p2['deadline'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="js-project-countdown"
                 data-start="<?php echo htmlspecialchars((new DateTimeImmutable((string)$p2['hired_at']))->format(DateTimeInterface::ATOM), ENT_QUOTES, 'UTF-8'); ?>"
                 data-deadline="<?php echo htmlspecialchars($p2['deadline_iso'], ENT_QUOTES, 'UTF-8'); ?>"
                 id="countdown-proj-2">
              <div style="display:flex;gap:6px;text-align:center;">
                <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:5px 4px;border-radius:8px;flex:1;">
                  <span class="c-days" style="font-size:1.1rem;font-weight:800;color:#0f172a;display:block;line-height:1.1;"><?php echo (int)$p2['days_left']; ?></span>
                  <span style="font-size:0.6rem;color:#64748b;text-transform:uppercase;font-weight:700;">Hari</span>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:5px 4px;border-radius:8px;flex:1;">
                  <span class="c-hours" style="font-size:1.1rem;font-weight:800;color:#0f172a;display:block;line-height:1.1;"><?php echo sprintf('%02d', (int)$p2['hours_left']); ?></span>
                  <span style="font-size:0.6rem;color:#64748b;text-transform:uppercase;font-weight:700;">Jam</span>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:5px 4px;border-radius:8px;flex:1;">
                  <span class="c-mins" style="font-size:1.1rem;font-weight:800;color:#0f172a;display:block;line-height:1.1;"><?php echo sprintf('%02d', (int)$p2['mins_left']); ?></span>
                  <span style="font-size:0.6rem;color:#64748b;text-transform:uppercase;font-weight:700;">Menit</span>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:5px 4px;border-radius:8px;flex:1;">
                  <span class="c-secs" style="font-size:1.1rem;font-weight:800;color:#0891b2;display:block;line-height:1.1;"><?php echo sprintf('%02d', (int)$p2['secs_left']); ?></span>
                  <span style="font-size:0.6rem;color:#64748b;text-transform:uppercase;font-weight:700;">Detik</span>
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

        <?php if (!empty($p2['approved_extension_days'])): ?>
          <div style="margin:12px 0 0 0;font-size:0.78rem;color:#065f46;background:#ecfdf5;border:1px solid #a7f3d0;padding:8px 12px;border-radius:8px;">
            Disetujui: <?php echo htmlspecialchars(gig_format_extension_label((int)$p2['approved_extension_days'], 'day'), ENT_QUOTES, 'UTF-8'); ?>. Deadline sudah diperbarui.
          </div>
        <?php endif; ?>

        <?php if ($isExpiredP2): ?>
          <div style="margin:12px 0 0 0;font-size:0.78rem;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;padding:8px 12px;border-radius:8px;">
            Deadline terlewati dan proyek belum selesai. Status otomatis menjadi <strong>Tidak Selesai</strong>.
          </div>
        <?php elseif ($pendingExtP2): ?>
          <div style="margin:12px 0 0 0;font-size:0.78rem;color:#1e293b;background:#eff6ff;border:1px solid #bfdbfe;padding:10px 12px;border-radius:8px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <div>
              Pengajuan perpanjangan pending dari <strong><?php echo ($pendingExtP2['requester_role'] ?? '') === 'worker' ? 'Gig Worker' : 'Pemberi Kerja'; ?></strong>:
              <strong><?php echo htmlspecialchars(gig_format_extension_label((int)($pendingExtP2['amount'] ?? 0), (string)($pendingExtP2['unit'] ?? 'day')), ENT_QUOTES, 'UTF-8'); ?></strong>
              <?php if (!empty($pendingExtP2['reason_note'])): ?>
                <span style="color:#334155;margin-left:6px;">(Alasan: <?php echo htmlspecialchars((string)$pendingExtP2['reason_note'], ENT_QUOTES, 'UTF-8'); ?>)</span>
              <?php endif; ?>
            </div>
            <?php if (($pendingExtP2['requester_role'] ?? '') !== 'employer'): ?>
              <div style="display:flex;gap:8px;align-items:center;">
                <form method="POST" action="" style="margin:0;">
                  <input type="hidden" name="ext_action" value="approve_extension">
                  <input type="hidden" name="request_id" value="<?php echo (int)$pendingExtP2['id']; ?>">
                  <button type="submit" class="btn-create-post" style="padding:4px 10px;font-size:0.75rem;background:#059669;border-color:#047857;">Setujui</button>
                </form>
                <form method="POST" action="" style="margin:0;">
                  <input type="hidden" name="ext_action" value="reject_extension">
                  <input type="hidden" name="request_id" value="<?php echo (int)$pendingExtP2['id']; ?>">
                  <button type="submit" class="btn-outline-blue" style="padding:4px 10px;font-size:0.75rem;">Tolak</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Extension Modal -->
        <div id="ext-modal-employer-p2" style="display:none;position:fixed;inset:0;z-index:1200;background:rgba(15,23,42,0.45);padding:16px;">
          <div style="max-width:560px;margin:7vh auto 0;background:#fff;border-radius:14px;box-shadow:0 20px 50px rgba(15,23,42,0.24);overflow:hidden;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid #e2e8f0;">
              <strong style="font-size:0.95rem;color:#0f172a;">Ajukan Perpanjangan Durasi</strong>
              <button type="button" onclick="closeExtensionModal('ext-modal-employer-p2')" style="border:none;background:#f1f5f9;color:#334155;border-radius:8px;padding:4px 8px;cursor:pointer;">Tutup</button>
            </div>
            <form method="POST" action="" style="padding:14px;display:flex;flex-direction:column;gap:10px;">
              <input type="hidden" name="ext_action" value="request_extension">
              <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars((string)$p2['contract_id'], ENT_QUOTES, 'UTF-8'); ?>">
              <label style="font-size:0.8rem;font-weight:700;color:#334155;">Jumlah Perpanjangan</label>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="number" min="1" name="ext_amount" value="1" required style="width:92px;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;">
                <select name="ext_unit" style="padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;">
                  <option value="day">Hari</option>
                  <option value="week">Minggu</option>
                  <option value="month">Bulan</option>
                </select>
              </div>
              <label style="font-size:0.8rem;font-weight:700;color:#334155;">Alasan Perpanjangan</label>
              <textarea name="ext_reason" required rows="3" placeholder="Jelaskan alasan kenapa butuh tambahan durasi" style="resize:vertical;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;"></textarea>
              <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px;">
                <button type="button" class="btn-outline-blue" onclick="closeExtensionModal('ext-modal-employer-p2')">Batal</button>
                <button type="submit" class="btn-action-sm">Kirim Pengajuan</button>
              </div>
            </form>
          </div>
        </div>

        <div class="active-proj-actions" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;">
          <div>
            <?php if ($completedP2): ?>
              <span style="font-size:0.82rem;color:#047857;font-weight:700;display:inline-flex;align-items:center;gap:6px;">
               <span>⭐</span> Rating Diberikan: <?php echo (int)$ratingP2; ?>/5
              </span>
            <?php else: ?>
              <?php if (!empty($confirmP2['employer_confirmed']) && empty($confirmP2['worker_confirmed'])): ?>
                <span style="font-size:0.8rem;color:#1d4ed8;font-weight:700;">✓ Anda sudah konfirmasi selesai. Menunggu konfirmasi Gig Worker.</span>
              <?php elseif (!empty($confirmP2['both_confirmed']) && !empty($reviewP2['employer_reviewed'])): ?>
                <span style="font-size:0.8rem;color:#64748b;">✓ Anda sudah kirim ulasan. Menunggu ulasan dari Gig Worker.</span>
              <?php elseif (!empty($confirmP2['both_confirmed'])): ?>
                <span style="font-size:0.8rem;color:#065f46;font-weight:700;">✓ Kedua pihak sudah konfirmasi selesai. Anda bisa memberi ulasan sekarang.</span>
              <?php else: ?>
                <span style="font-size:0.8rem;color:#64748b;">✓ Koordinasi aktif &bull; Konfirmasi selesai setelah deliverable benar-benar selesai.</span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <button class="btn-action-sm" type="button"
              data-contact-role="Gig Worker"
              data-contact-name="Rian Ardiansyah"
              data-contact-phone="0813-8899-7711"
              data-contact-email="rian.dev@email.com"
              onclick="openContactModal(this)"
              style="padding:7px 14px;font-size:0.82rem;font-weight:600;border-radius:8px;">Kontak Freelancer</button>

            <?php if (!$completedP2 && !$pendingExtP2 && !$isExpiredP2): ?>
              <button type="button" class="btn-outline-blue" onclick="openExtensionModal('ext-modal-employer-p2')" style="padding:7px 14px;font-size:0.82rem;font-weight:600;border-radius:8px;">Ajukan Perpanjangan</button>
            <?php elseif ($pendingExtP2): ?>
              <button type="button" class="btn-outline-blue" disabled style="padding:7px 14px;font-size:0.82rem;font-weight:600;border-radius:8px;opacity:0.65;cursor:not-allowed;">Perpanjangan Pending</button>
            <?php endif; ?>

            <?php if ($completedP2): ?>
              <a class="btn-create-post" href="employer-riwayat-proyek.php" style="text-decoration:none;padding:7px 16px;font-size:0.82rem;font-weight:700;border-radius:8px;background:#059669;border-color:#047857;">
                Buka di Riwayat →
              </a>
            <?php elseif (empty($confirmP2['employer_confirmed'])): ?>
              <form method="POST" action="" style="margin:0;">
                <input type="hidden" name="completion_action" value="confirm_project_finished">
                <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars((string)($p2['contract_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="worker_id" value="<?php echo htmlspecialchars((string)($p2['worker_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="worker_name" value="<?php echo htmlspecialchars((string)($p2['worker_name'] ?? 'Gig Worker'), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="vacancy_id" value="<?php echo htmlspecialchars((string)($p2['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="project_title" value="<?php echo htmlspecialchars((string)($p2['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn-create-post" style="text-decoration:none;padding:7px 16px;font-size:0.82rem;font-weight:700;border-radius:8px;background:#059669;border-color:#047857;">
                  ✓ Konfirmasi Proyek Selesai
                </button>
              </form>
            <?php elseif (empty($confirmP2['both_confirmed'])): ?>
              <button type="button" class="btn-outline-blue" disabled style="padding:7px 16px;font-size:0.82rem;font-weight:700;border-radius:8px;opacity:0.65;cursor:not-allowed;">
                Menunggu Konfirmasi Gig Worker
              </button>
            <?php elseif (!empty($reviewP2['employer_reviewed'])): ?>
              <button type="button" class="btn-outline-blue" disabled style="padding:7px 16px;font-size:0.82rem;font-weight:700;border-radius:8px;opacity:0.65;cursor:not-allowed;">
                Ulasan Anda Terkirim
              </button>
            <?php else: ?>
              <a class="btn-create-post" href="employer-rating-worker.php?contract=CTR-GIG-2026-0819&worker=rian" style="text-decoration:none;padding:7px 16px;font-size:0.82rem;font-weight:700;border-radius:8px;background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);box-shadow:0 4px 12px rgba(217,119,6,0.3);border:none;">
                ★ Selesaikan &amp; Beri Rating
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

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

    <!-- Countdown Timer Script -->
    <script>
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
        if (e.target && e.target.id && e.target.id.indexOf('ext-modal-employer-') === 0) {
          e.target.style.display = 'none';
        }
      });

      (function startCountdowns() {
        function pad(n) { return n < 10 ? '0' + n : String(n); }
        function tick() {
          document.querySelectorAll('.js-project-countdown').forEach(function(container) {
            const iso = container.getAttribute('data-deadline');
            const startIso = container.getAttribute('data-start');
            if (!iso) return;
            const start = startIso ? new Date(startIso).getTime() : NaN;
            const end = new Date(iso).getTime();
            let remainingMs = end - Date.now();
            // Prototype safeguard: active projects countdown never runs out
            if (remainingMs <= 0) {
              const fallbackCycleMs = 7 * 86400000;
              remainingMs = fallbackCycleMs + (remainingMs % fallbackCycleMs);
              if (remainingMs <= 0) remainingMs = fallbackCycleMs;
            }
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

<?php require __DIR__ . '/includes/employer-layout-end.php'; ?>
