<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/admin-store.php';
require_once __DIR__ . '/includes/vacancy-store.php';

$vacancyId = trim((string)($_GET['id'] ?? ''));
$flash = (string)($_GET['msg'] ?? '');
$flashType = (string)($_GET['type'] ?? 'success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $note = trim((string)($_POST['admin_note'] ?? ''));
    if (!empty($_POST['compliance_reasons']) && is_array($_POST['compliance_reasons'])) {
        $reasons = array_filter(array_map(static fn($r) => trim((string)$r), $_POST['compliance_reasons']));
        if ($reasons !== []) {
            $reasonsText = 'Ketidakpatuhan: ' . implode(', ', $reasons);
            $note = $note !== '' ? $reasonsText . '. ' . $note : $reasonsText;
        }
    }
    if ($action === 'vacancy_decision') {
        $decision = (string)($_POST['decision'] ?? '');
        $targetId = trim((string)($_POST['vacancy_id'] ?? $vacancyId));
        if ($decision === 'approve') {
            $ok = gig_vacancy_set_status($targetId, 'active', $note !== '' ? $note : 'Disetujui Admin KarirHub.');
            $flash = $ok ? 'Lowongan disetujui dan ditayangkan.' : 'Gagal menyetujui lowongan.';
        } elseif ($decision === 'reject') {
            if ($note === '') {
                $note = 'Lowongan tidak memenuhi Syarat & Ketentuan KarirHub.';
            }
            $ok = gig_vacancy_set_status($targetId, 'rejected', $note);
            $flash = $ok ? 'Lowongan ditolak.' : 'Gagal menolak lowongan.';
        } else {
            if ($note === '') {
                $note = 'Harap perbaiki detail lowongan sesuai catatan Admin.';
            }
            $ok = gig_vacancy_set_status($targetId, 'revision', $note);
            $flash = $ok ? 'Lowongan dikembalikan untuk revisi.' : 'Gagal mengirim catatan revisi.';
        }
        $flashType = !empty($ok) ? 'success' : 'error';
        $params = ['id' => $targetId, 'msg' => $flash, 'type' => $flashType];
        header('Location: admin-project-detail.php?' . http_build_query($params));
        exit;
    }
}

$vacancy = $vacancyId !== '' ? gig_find_vacancy($vacancyId) : null;

$dbMeta = [];
$submittedAt = '';
$reviewedAt = '';
if ($vacancy !== null && function_exists('gig_db')) {
    $db = gig_db();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT `created_at`, `reviewed_at`, `status`, `admin_note` FROM `project_vacancies` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => (string)$vacancy['id']]);
            $dbMeta = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $submittedAt = trim((string)($dbMeta['created_at'] ?? ''));
            $reviewedAt = trim((string)($dbMeta['reviewed_at'] ?? ''));
        } catch (Throwable $e) {
        }
    }
}

if ($vacancy === null) {
    $adminTab = 'projects';
    $pageTitle = 'Detail Verifikasi Lowongan';
    $breadcrumbCurrent = 'Detail Verifikasi Lowongan';
    require __DIR__ . '/includes/admin-layout-start.php';
    echo '<div class="empty-state">Data lowongan tidak ditemukan. <a href="dashboard-admin.php?tab=projects">Kembali ke verifikasi lowongan</a>.</div>';
    require __DIR__ . '/includes/admin-layout-end.php';
    exit;
}

$status = (string)($vacancy['status'] ?? 'review');
$statusLabel = (string)($vacancy['statusLabel'] ?? 'Menunggu Verifikasi');
$statusBadgeClass = 'is-blue';
if ($status === 'active') {
    $statusLabel = 'Disetujui';
    $statusBadgeClass = 'is-green';
} elseif ($status === 'revision') {
    $statusLabel = 'Revisi';
    $statusBadgeClass = 'is-amber';
} elseif ($status === 'rejected') {
    $statusLabel = 'Ditolak';
    $statusBadgeClass = 'is-red';
}

$postedStamp = $submittedAt !== '' ? strtotime($submittedAt) : strtotime((string)($vacancy['posted'] ?? ''));
if (!$postedStamp) {
    $postedStamp = time();
}
$verifyDeadlineTs = strtotime('+3 days', $postedStamp);
$daysLeft = (int)ceil(($verifyDeadlineTs - time()) / 86400);
$slaLabel = $daysLeft > 0 ? $daysLeft . ' hari lagi' : ($daysLeft === 0 ? 'Hari ini' : 'Lewat tenggat');
$blacklistStatus = 'Tidak terdeteksi';

$auditLogs = [];
$auditLogs[] = [
    'time' => date('d M Y, H:i', $postedStamp),
    'title' => 'Pengajuan lowongan dibuat',
    'detail' => 'Auto-booked ke verifier.',
];
if ($reviewedAt !== '') {
    $reviewStatus = $status === 'active' ? 'disetujui' : ($status === 'rejected' ? 'ditolak' : 'dikembalikan untuk revisi');
    $auditLogs[] = [
        'time' => date('d M Y, H:i', strtotime($reviewedAt)),
        'title' => 'Lowongan ' . $reviewStatus,
        'detail' => trim((string)($vacancy['adminNote'] ?? '')) !== '' ? (string)$vacancy['adminNote'] : 'Keputusan admin tercatat.',
    ];
}

$employerName = (string)($vacancy['employer'] ?? 'Perusahaan');
$skills = is_array($vacancy['skills'] ?? null) ? $vacancy['skills'] : [];
$workType = trim((string)($vacancy['work_type'] ?? ''));
$projectLocation = trim((string)($vacancy['location'] ?? ''));
if ($projectLocation === '' || strcasecmp($projectLocation, 'Lokasi belum diisi') === 0) {
    $projectLocation = gig_random_location((string)($vacancy['id'] ?? ''));
}
$phone = trim((string)($vacancy['employer_phone'] ?? '-'));
$email = trim((string)($vacancy['employer_email'] ?? '-'));
$adminTab = 'projects';
$pageTitle = 'Detail Verifikasi Lowongan';
$breadcrumbCurrent = 'Detail Verifikasi Lowongan';
require __DIR__ . '/includes/admin-layout-start.php';
?>

<style>
  .req-back-link { display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700;color:#334155;margin-bottom:10px; }
  .req-page { display:grid;gap:12px; }
  .req-hero { background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;display:flex;justify-content:space-between;gap:14px;align-items:flex-start; }
  .req-kicker { font-size:.7rem;color:#64748b;font-weight:700;letter-spacing:.04em;text-transform:uppercase;margin-bottom:4px; }
  .req-title { margin:0 0 4px 0;font-size:1.35rem;font-weight:800;color:#0f172a;line-height:1.2; }
  .req-meta { display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:.8rem;color:#64748b; }
  .req-badge { display:inline-flex;align-items:center;padding:4px 12px;border-radius:999px;font-size:.72rem;font-weight:800;white-space:nowrap; }
  .req-badge.is-blue { background:#dbeafe;color:#1d4ed8; }
  .req-badge.is-green { background:#dcfce7;color:#166534; }
  .req-badge.is-red { background:#fee2e2;color:#991b1b; }
  .req-badge.is-amber { background:#fef3c7;color:#92400e; }
  .req-btn { border:1px solid #0ea5e9;background:#0ea5e9;color:#fff;border-radius:9px;padding:8px 12px;font-weight:700;font-size:.78rem;cursor:pointer;white-space:nowrap; }
  .req-grid-top { display:grid;grid-template-columns:2fr 1fr;gap:12px; }
  .req-card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px; }
  .req-card h3 { margin:0 0 10px 0;font-size:.9rem;font-weight:800;color:#0f172a; }
  .req-summary-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px 16px; }
  .req-label { font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em; }
  .req-value { font-size:.8rem;font-weight:600;color:#0f172a;line-height:1.4;margin-top:2px; }
  .req-audit-item { border-left:2px solid #bfdbfe;padding-left:9px;margin-bottom:10px; }
  .req-audit-item:last-child { margin-bottom:0; }
  .req-audit-time { font-size:.7rem;font-weight:700;color:#64748b; }
  .req-audit-title { font-size:.8rem;font-weight:700;color:#0f172a;margin-top:2px; }
  .req-audit-detail { font-size:.76rem;color:#475569;line-height:1.45;margin-top:2px; }
  .req-main { display:grid;gap:12px; }
  .req-checklist-box { margin-top:10px;border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc;padding:18px;text-align:center;color:#64748b; }
  .req-check-icon { width:32px;height:32px;border-radius:999px;background:#e2e8f0;color:#64748b;display:inline-flex;align-items:center;justify-content:center;font-weight:800;margin-bottom:8px; }
  .req-lowongan-head { display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px; }
  .req-lowongan-title { margin:0;font-size:1.06rem;font-weight:800;color:#0f172a; }
  .req-company { margin:2px 0 10px 0;font-size:.8rem;color:#64748b; }
  .req-mini-kpi { display:grid;grid-template-columns:repeat(3,1fr);gap:10px;border-top:1px solid #eef2f7;border-bottom:1px solid #eef2f7;padding:10px 0;margin-bottom:10px; }
  .req-mini-kpi .item-label { font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.03em; }
  .req-mini-kpi .item-value { font-size:.79rem;color:#0f172a;font-weight:700;margin-top:2px; }
  .req-section-title { margin:10px 0 4px 0;font-size:.8rem;font-weight:700;color:#334155; }
  .req-paragraph { font-size:.8rem;color:#334155;line-height:1.55;margin:0; }
  .req-detail-grid { display:grid;grid-template-columns:1fr 1fr;gap:9px 14px;margin-top:10px; }
  .req-skill-chip { display:inline-flex;padding:4px 10px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-size:.74rem;font-weight:700;margin:2px 6px 2px 0; }
  .req-contact { margin-top:10px;padding-top:10px;border-top:1px solid #eef2f7;font-size:.78rem;color:#334155;display:grid;gap:4px; }
  @media (max-width: 980px) {
    .req-hero { flex-direction:column; }
    .req-grid-top { grid-template-columns:1fr; }
    .req-summary-grid, .req-mini-kpi, .req-detail-grid { grid-template-columns:1fr; }
  }
</style>

<a href="dashboard-admin.php?tab=projects" class="req-back-link">&larr; Kembali</a>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php
  $allSpecialReqs = function_exists('gig_vacancy_get_special_requirements') ? gig_vacancy_get_special_requirements($vacancy) : ($vacancy['special_requirements'] ?? ($vacancy['special_requirement'] ? [$vacancy['special_requirement']] : []));
  $reqTypesCatalog = function_exists('gig_special_requirement_types') ? gig_special_requirement_types() : [];
?>
<section class="req-page">
  <article class="req-hero">
    <div>
      <div class="req-kicker">Detail Pengajuan Verifikasi Lowongan</div>
      <h1 class="req-title"><?php echo htmlspecialchars((string)$vacancy['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
      <div class="req-meta">
        <span><?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?></span>
        <span>Perusahaan</span>
        <span>Diajukan <?php echo htmlspecialchars(date('d M Y, H:i', $postedStamp), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="req-badge <?php echo htmlspecialchars($statusBadgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    </div>
    <button type="button" class="req-btn" id="btnAdminTakeDecision" onclick="handleProjectDecisionClick()">Ambil Keputusan</button>
  </article>

  <div class="req-grid-top">
    <article class="req-card">
      <h3>Ringkasan Pengajuan</h3>
      <div class="req-summary-grid">
        <div><div class="req-label">Jenis Lowongan</div><div class="req-value">Lowongan Kerja Proyek</div></div>
        <div><div class="req-label">Tanggal Pengajuan</div><div class="req-value"><?php echo htmlspecialchars(date('d M Y, H:i', $postedStamp), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Wilayah</div><div class="req-value"><?php echo htmlspecialchars($projectLocation, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Blacklist</div><div class="req-value" style="color:#065f46;"><?php echo htmlspecialchars($blacklistStatus, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">SLA Verifikasi</div><div class="req-value"><?php echo htmlspecialchars($slaLabel, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <?php if (!empty($allSpecialReqs)): ?>
          <?php foreach ($allSpecialReqs as $sIdx => $sItem): 
            $sItemType = (string)($sItem['type'] ?? '');
            $sItemTypeLabel = (string)($sItem['type_label'] ?? ($reqTypesCatalog[$sItemType]['label'] ?? $sItemType));
            $sItemFormat = (string)(
                !empty($sItem['file_type_label']) ? $sItem['file_type_label'] : (
                    !empty($sItem['format_hint']) ? $sItem['format_hint'] : (
                        $reqTypesCatalog[$sItemType]['format_hint'] ?? (
                            ($sItem['input_mode'] ?? '') === 'file' ? 'PDF' :
                            (($sItem['input_mode'] ?? '') === 'url' ? 'Link / URL' : 'Text')
                        )
                    )
                )
            );
            $sItemIsPub = !isset($sItem['is_public']) || !empty($sItem['is_public']);
            $sNumLabel = count($allSpecialReqs) > 1 ? (' #' . ($sIdx + 1)) : '';
          ?>
            <div>
              <div class="req-label">Kualifikasi Khusus Pelamar<?php echo $sNumLabel; ?></div>
              <div class="req-value" style="color:#1e40af;"><?php echo htmlspecialchars($sItemTypeLabel, ENT_QUOTES, 'UTF-8'); ?></div>
              <div style="font-size:0.74rem;color:#334155;margin-top:2px;font-weight:600;"><?php echo htmlspecialchars((string)$sItem['label'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div style="font-size:0.7rem;color:#64748b;margin-top:2px;"><?php echo htmlspecialchars($sItemFormat, ENT_QUOTES, 'UTF-8'); ?></div>
              <div style="margin-top:6px;">
                <?php if ($sItemIsPub): ?>
                  <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.68rem;font-weight:700;color:#15803d;background:#dcfce7;border:1px solid #86efac;padding:2px 8px;border-radius:6px;" title="Syarat ini tampil terbuka di lowongan dan bursa kerja">
                    🌐 Publik (Terlihat)
                  </span>
                <?php else: ?>
                  <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.68rem;font-weight:700;color:#475569;background:#f1f5f9;border:1px solid #cbd5e1;padding:2px 8px;border-radius:6px;" title="Syarat ini disembunyikan dari publik, hanya diminta saat melamar">
                    🔒 Tersembunyi (Saat Melamar)
                  </span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </article>
    <article class="req-card">
      <h3>Aktivitas &amp; Audit Log</h3>
      <?php foreach ($auditLogs as $log): ?>
        <div class="req-audit-item">
          <div class="req-audit-time"><?php echo htmlspecialchars((string)$log['time'], ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="req-audit-title"><?php echo htmlspecialchars((string)$log['title'], ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="req-audit-detail"><?php echo htmlspecialchars((string)$log['detail'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endforeach; ?>
    </article>
  </div>

  <div class="req-main">
    <article class="req-card">
      <h3>Informasi Keputusan dan Verifikasi</h3>
      <div class="req-summary-grid">
        <div><div class="req-label">Deadline Verifikasi</div><div class="req-value"><?php echo in_array((string)($vacancy['status'] ?? ''), ['active', 'approved', 'rejected'], true) ? '-' : htmlspecialchars(date('d M Y, H:i', $verifyDeadlineTs), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Pemeriksaan pada</div><div class="req-value"><?php echo htmlspecialchars(date('d M Y, H:i', $postedStamp), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Nama Petugas</div><div class="req-value"><?php echo htmlspecialchars((string)($adminName ?? 'Admin KarirHub'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Email Petugas</div><div class="req-value"><?php echo htmlspecialchars((string)($_SESSION['admin_email'] ?? 'admin@kemnaker.go.id'), ENT_QUOTES, 'UTF-8'); ?></div></div>
      </div>
      <div class="req-checklist-box">
        <div class="req-check-icon">&#10003;</div>
        <div style="font-size:.78rem;font-weight:700;color:#334155;">Checklist verifikasi tidak tersedia</div>
        <div style="font-size:.74rem;color:#64748b;margin-top:4px;">Checklist akan muncul setelah admin mengambil keputusan verifikasi.</div>
      </div>
    </article>

    <article class="req-card">
      <h3>Informasi Lowongan</h3>
      <div class="req-lowongan-head">
        <h4 class="req-lowongan-title"><?php echo htmlspecialchars((string)$vacancy['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
      </div>
      <div class="req-company"><?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?></div>

      <div class="req-mini-kpi">
        <div>
          <div class="item-label">Jenis Entitas</div>
          <div class="item-value">Perusahaan</div>
        </div>
        <div>
          <div class="item-label">Wilayah</div>
          <div class="item-value"><?php echo htmlspecialchars($projectLocation, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div>
          <div class="item-label">Status Lowongan</div>
          <div class="item-value"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      </div>

      <div class="req-section-title">Deskripsi</div>
      <p class="req-paragraph"><?php echo nl2br(htmlspecialchars((string)($vacancy['desc'] ?? '-'), ENT_QUOTES, 'UTF-8')); ?></p>

      <div class="req-section-title">Persyaratan Khusus</div>
      <?php if (!empty($vacancy['qualifications']) && trim((string)$vacancy['qualifications']) !== '-'): ?>
        <p class="req-paragraph" style="margin-bottom:12px;"><?php echo nl2br(htmlspecialchars((string)$vacancy['qualifications'], ENT_QUOTES, 'UTF-8')); ?></p>
      <?php elseif (empty($allSpecialReqs)): ?>
        <p class="req-paragraph">-</p>
      <?php endif; ?>

      <?php if (!empty($allSpecialReqs)): ?>
      <div style="margin-top:14px;margin-bottom:18px;">
        <div style="font-size:0.82rem;font-weight:800;color:#1e293b;text-transform:uppercase;letter-spacing:0.03em;display:flex;align-items:center;gap:6px;margin-bottom:10px;">
          <span>📎</span>
          <span>Kualifikasi Khusus yang Diminta Pemberi Kerja</span>
          <span style="background:#e0f2fe;color:#0369a1;font-size:0.72rem;padding:2px 8px;border-radius:9999px;font-weight:700;"><?php echo count($allSpecialReqs); ?></span>
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;">
          <?php foreach ($allSpecialReqs as $sIdx => $sItem): 
            $sItemType = (string)($sItem['type'] ?? '');
            $sItemTypeLabel = (string)($sItem['type_label'] ?? ($reqTypesCatalog[$sItemType]['label'] ?? $sItemType));
            if ($sItemType === 'other' && !empty($sItem['custom_type'])) {
                $sItemTypeLabel = (string)$sItem['custom_type'];
            }
            $sItemFormat = (string)(
                !empty($sItem['file_type_label']) ? $sItem['file_type_label'] : (
                    !empty($sItem['format_hint']) ? $sItem['format_hint'] : (
                        $reqTypesCatalog[$sItemType]['format_hint'] ?? (
                            ($sItem['input_mode'] ?? '') === 'file' ? 'PDF' :
                            (($sItem['input_mode'] ?? '') === 'url' ? 'Link / URL' : 'Text')
                        )
                    )
                )
            );
            $isPublicVal = !isset($sItem['is_public']) || !empty($sItem['is_public']);
            $sNumText = count($allSpecialReqs) > 1 ? ('Kualifikasi #' . ($sIdx + 1) . ': ') : '';
          ?>
            <div style="background:#ffffff;border:1.5px solid <?php echo $isPublicVal ? '#bbf7d0' : '#e2e8f0'; ?>;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
              <!-- Status Visibilitas Banner (Read-only) -->
              <div style="padding:12px 14px;border-radius:10px;background:<?php echo $isPublicVal ? '#f0fdf4' : '#f8fafc'; ?>;border:1px solid <?php echo $isPublicVal ? '#86efac' : '#cbd5e1'; ?>;display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;">
                <div style="font-size:1.4rem;line-height:1;margin-top:1px;">
                  <?php echo $isPublicVal ? '🌐' : '🔒'; ?>
                </div>
                <div style="flex:1;">
                  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-size:0.86rem;font-weight:800;color:<?php echo $isPublicVal ? '#15803d' : '#334155'; ?>;">
                      Status Tampilan: <?php echo $isPublicVal ? 'Ditampilkan ke Publik' : 'Disembunyikan dari Publik'; ?>
                    </span>
                    <span style="font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:9999px;background:<?php echo $isPublicVal ? '#dcfce7;color:#166534;border:1px solid #86efac' : '#e2e8f0;color:#475569;border:1px solid #cbd5e1'; ?>;">
                      <?php echo $isPublicVal ? 'Terlihat oleh Pelamar' : 'Hanya Saat Melamar'; ?>
                    </span>
                  </div>
                  <div style="font-size:0.76rem;color:<?php echo $isPublicVal ? '#166534' : '#64748b'; ?>;margin-top:3px;line-height:1.4;">
                    <?php if ($isPublicVal): ?>
                      Syarat ini <strong>tampil terbuka</strong> pada halaman lowongan dan bursa kerja. Calon pelamar dapat langsung mengetahui syarat ini sebelum melamar.
                    <?php else: ?>
                      Syarat ini <strong>disembunyikan</strong> dari rincian lowongan publik. Calon pelamar baru akan diminta melampirkan berkas ini saat menekan tombol "Lamar Proyek".
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="req-detail-grid" style="margin-top:0;">
                <div>
                  <div class="req-label">Jenis Kualifikasi</div>
                  <div class="req-value" style="color:#1e40af;font-weight:700;"><?php echo htmlspecialchars($sNumText . $sItemTypeLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div>
                  <div class="req-label">Format Input Pelamar</div>
                  <div class="req-value" style="font-weight:600;color:#334155;">📁 <?php echo htmlspecialchars($sItemFormat, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
              <div style="margin-top:10px;padding-top:10px;border-top:1px dashed #e2e8f0;">
                <div class="req-label">Detail / Nama Kualifikasi yang Diminta</div>
                <div class="req-value" style="font-weight:800;color:#0f172a;font-size:0.92rem;margin-top:2px;"><?php echo htmlspecialchars((string)$sItem['label'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="req-section-title">Detail Lowongan</div>
      <div class="req-detail-grid">
        <div><div class="req-label">Bidang</div><div class="req-value"><?php echo htmlspecialchars((string)($vacancy['category'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Mode Kerja</div><div class="req-value"><?php echo htmlspecialchars($workType !== '' ? $workType : '-', ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Durasi Proyek</div><div class="req-value"><?php echo htmlspecialchars((string)($vacancy['duration'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Batas Lamaran</div><div class="req-value"><?php echo htmlspecialchars((string)($vacancy['deadline'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Nilai Proyek</div><div class="req-value"><?php echo htmlspecialchars((string)($vacancy['budget'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div><div class="req-label">Visibilitas</div><div class="req-value"><?php echo htmlspecialchars((string)($vacancy['visibility'] ?? 'public'), ENT_QUOTES, 'UTF-8'); ?></div></div>
      </div>

      <div class="req-section-title">Keahlian yang Dibutuhkan</div>
      <div>
        <?php if ($skills === []): ?>
          <span class="req-value">-</span>
        <?php else: ?>
          <?php foreach ($skills as $skill): ?>
            <span class="req-skill-chip"><?php echo htmlspecialchars((string)$skill, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="req-contact">
        <div><strong>Kontak Lowongan:</strong> <?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Email:</strong> <?php echo htmlspecialchars($email !== '' ? $email : '-', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Telepon:</strong> <?php echo htmlspecialchars($phone !== '' ? $phone : '-', ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    </article>
  </div>
</section>

<script>
function handleProjectDecisionClick() {
  if (typeof openAdminDecisionModal === 'function') {
    openAdminDecisionModal({
      entityType: 'vacancy',
      entityName: 'lowongan',
      vacancyId: <?php echo json_encode((string)$vacancy['id']); ?>,
      action: 'vacancy_decision'
    });
  } else {
    console.error('openAdminDecisionModal function not found');
  }
}
</script>

<?php require __DIR__ . '/includes/admin-layout-end.php'; ?>
