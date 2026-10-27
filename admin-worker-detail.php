<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/admin-store.php';
require_once __DIR__ . '/includes/worker-profiles.php';

$workerParam = trim((string)($_GET['u'] ?? ''));
$emailParam = trim((string)($_GET['email'] ?? ''));
$editIdParam = (int)($_GET['edit_id'] ?? 0);
$flash = '';
$flashType = 'success';

$workers = gig_admin_list_worker_registrations();
$worker = null;
foreach ($workers as $row) {
    $username = trim((string)($row['username'] ?? ''));
    $email = trim((string)($row['contact_email'] ?? ''));
    $isUserMatch = $workerParam !== '' && strcasecmp($username, $workerParam) === 0;
    $isEmailMatch = $emailParam !== '' && strcasecmp($email, $emailParam) === 0;
    if ($isUserMatch || $isEmailMatch) {
        $worker = $row;
        break;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $worker !== null) {
    $action = (string)($_POST['action'] ?? '');
    $note = trim((string)($_POST['admin_note'] ?? ''));
    if (!empty($_POST['compliance_reasons']) && is_array($_POST['compliance_reasons'])) {
        $reasonsList = array_map(static fn($r) => trim((string)$r), $_POST['compliance_reasons']);
        $reasonsList = array_filter($reasonsList, static fn($r) => $r !== '');
        if ($reasonsList !== []) {
            $reasonsStr = 'Ketidakpatuhan: ' . implode(', ', $reasonsList);
            $note = $note !== '' ? $reasonsStr . '. ' . $note : $reasonsStr;
        }
    }
    if ($action === 'worker_take_decision') {
        $decision = (string)($_POST['decision'] ?? '');
        if ($decision === 'approve') {
            $ok = gig_admin_set_worker_status((string)$worker['username'], 'approved', $note);
            $flash = $ok ? 'Keputusan verifikasi: Gig Worker disetujui.' : 'Gagal memproses persetujuan.';
            $flashType = $ok ? 'success' : 'error';
        } elseif ($decision === 'reject') {
            if ($note === '') {
                $note = 'Data/berkas profil Gig Worker tidak memenuhi syarat.';
            }
            $ok = gig_admin_set_worker_status((string)$worker['username'], 'rejected', $note);
            $flash = $ok ? 'Keputusan verifikasi: Gig Worker ditolak.' : 'Gagal memproses penolakan.';
            $flashType = $ok ? 'success' : 'error';
        } elseif ($decision === 'revision') {
            if ($note === '') {
                $note = 'Harap perbaiki data/berkas profil Gig Worker sesuai catatan.';
            }
            $ok = gig_admin_set_worker_status((string)$worker['username'], 'revision', $note);
            $flash = $ok ? 'Permintaan revisi dikirim ke Gig Worker.' : 'Gagal mengirim catatan revisi.';
            $flashType = $ok ? 'success' : 'error';
        } else {
            $ok = false;
            $flash = 'Pilihan keputusan tidak valid.';
            $flashType = 'error';
        }
    } elseif ($action === 'worker_approve') {
        $ok = gig_admin_set_worker_status((string)$worker['username'], 'approved', $note);
        $flash = $ok ? 'Verifikasi Gig Worker disetujui.' : 'Gagal menyetujui verifikasi.';
        $flashType = $ok ? 'success' : 'error';
    } elseif ($action === 'worker_reject') {
        $ok = gig_admin_set_worker_status((string)$worker['username'], 'rejected', $note);
        $flash = $ok ? 'Verifikasi Gig Worker ditolak.' : 'Gagal menolak verifikasi.';
        $flashType = $ok ? 'success' : 'error';
    } elseif ($action === 'worker_edit_approve') {
        $ok = gig_admin_set_worker_profile_edit_status((int)($_POST['edit_id'] ?? 0), 'approved', $note);
        $flash = $ok ? 'Permintaan edit profil disetujui.' : 'Gagal menyetujui permintaan edit profil.';
        $flashType = $ok ? 'success' : 'error';
    } elseif ($action === 'worker_edit_reject') {
        $ok = gig_admin_set_worker_profile_edit_status((int)($_POST['edit_id'] ?? 0), 'rejected', $note);
        $flash = $ok ? 'Permintaan edit profil ditolak.' : 'Gagal menolak permintaan edit profil.';
        $flashType = $ok ? 'success' : 'error';
    }

    if ($workerParam !== '' || $emailParam !== '') {
        $params = [];
        if ($workerParam !== '') {
            $params['u'] = $workerParam;
        }
        if ($emailParam !== '') {
            $params['email'] = $emailParam;
        }
        $params['msg'] = $flash;
        $params['type'] = $flashType;
        header('Location: admin-worker-detail.php?' . http_build_query($params));
        exit;
    }
}

if (isset($_GET['msg'])) {
    $flash = (string)$_GET['msg'];
    $flashType = (string)($_GET['type'] ?? 'success');
}

$adminTab = 'workers';
$pageTitle = 'Detail Gig Worker';
$breadcrumbCurrent = 'Detail Gig Worker';
require __DIR__ . '/includes/admin-layout-start.php';

if ($worker === null):
?>
  <div class="empty-state">Data Gig Worker tidak ditemukan. <a href="dashboard-admin.php?tab=workers">Kembali ke verifikasi worker</a>.</div>
<?php
require __DIR__ . '/includes/admin-layout-end.php';
exit;
endif;

$displayName = trim((string)($worker['display_name'] ?? '')) !== '' ? (string)$worker['display_name'] : (string)$worker['username'];
$domicile = trim((string)($worker['domicile'] ?? '')) !== '' ? (string)$worker['domicile'] : '-';
$skills = gig_normalize_skills($worker['skills'] ?? []);
$projects = is_array($worker['previous_projects'] ?? null) ? $worker['previous_projects'] : [];
$portfolio = is_array($worker['portfolio'] ?? null) ? $worker['portfolio'] : [];
$status = (string)($worker['status'] ?? 'pending');

$profileLookup = gig_find_worker((string)$worker['username']);
if (!$profileLookup && !empty($worker['contact_email'])) {
    $emailStem = explode('@', (string)$worker['contact_email'])[0] ?? '';
    if ($emailStem !== '') {
        $profileLookup = gig_find_worker($emailStem);
    }
}
if ($projects === [] && is_array($profileLookup['experience'] ?? null)) {
    $projects = $profileLookup['experience'];
}
if ($portfolio === [] && is_array($profileLookup['portfolio'] ?? null)) {
    $portfolio = $profileLookup['portfolio'];
}
if ($skills === [] && is_array($profileLookup['skills'] ?? null)) {
    $skills = $profileLookup['skills'];
}
$allProfileEdits = gig_admin_list_worker_profile_edits();
$workerEditKey = gig_worker_profile_edit_key((string)($worker['username'] ?? ''));
$relatedEdits = array_values(array_filter($allProfileEdits, static function (array $edit) use ($workerEditKey, $worker): bool {
    $editKey = gig_worker_profile_edit_key((string)($edit['worker_username'] ?? ''));
    $emailMatch = !empty($edit['worker_email']) && strcasecmp((string)$edit['worker_email'], (string)($worker['contact_email'] ?? '')) === 0;
    return $editKey === $workerEditKey || $emailMatch;
}));
$selectedEdit = null;
if ($editIdParam > 0) {
    foreach ($relatedEdits as $edit) {
        if ((int)($edit['id'] ?? 0) === $editIdParam) {
            $selectedEdit = $edit;
            break;
        }
    }
}
if ($selectedEdit === null) {
    foreach ($relatedEdits as $edit) {
        if ((string)($edit['status'] ?? '') === 'pending') {
            $selectedEdit = $edit;
            break;
        }
    }
}
if ($selectedEdit === null && $relatedEdits !== []) {
    $selectedEdit = $relatedEdits[0];
}

$auditLogs = [];
$submittedAt = trim((string)($worker['created_at'] ?? ''));
if ($submittedAt !== '') {
    $auditLogs[] = [
        'time' => $submittedAt,
        'title' => 'Gig Worker mendaftar di platform',
        'detail' => 'Data registrasi awal dikirim untuk verifikasi admin.',
    ];
}

foreach ($relatedEdits as $editLog) {
    $createdEdit = trim((string)($editLog['created_at'] ?? ''));
    if ($createdEdit !== '') {
        $auditLogs[] = [
            'time' => $createdEdit,
            'title' => 'Permintaan edit profil diajukan',
            'detail' => 'Bidang: ' . (string)($editLog['edited_field'] ?? '-') . ' · Alasan: ' . (string)($editLog['reason_code'] ?? '-'),
        ];
    }
    $reviewedEdit = trim((string)($editLog['reviewed_at'] ?? ''));
    if ($reviewedEdit !== '') {
        $editStatus = (string)($editLog['status'] ?? '');
        $statusText = $editStatus === 'approved' ? 'disetujui' : ($editStatus === 'rejected' ? 'ditolak' : $editStatus);
        $auditLogs[] = [
            'time' => $reviewedEdit,
            'title' => 'Permintaan edit profil ' . $statusText,
            'detail' => trim((string)($editLog['admin_note'] ?? '')) !== '' ? (string)$editLog['admin_note'] : 'Diproses oleh admin.',
        ];
    }
}

$reviewedAt = trim((string)($worker['reviewed_at'] ?? ''));
if ($reviewedAt !== '') {
    $statusText = $status === 'approved' ? 'disetujui' : ($status === 'rejected' ? 'ditolak' : 'dikembalikan untuk revisi');
    $auditLogs[] = [
        'time' => $reviewedAt,
        'title' => 'Status verifikasi akun ' . $statusText,
        'detail' => trim((string)($worker['admin_note'] ?? '')) !== '' ? (string)$worker['admin_note'] : 'Keputusan admin tercatat.',
    ];
}

usort($auditLogs, static function (array $a, array $b): int {
    return strtotime((string)$b['time']) <=> strtotime((string)$a['time']);
});

$editStatusBadge = static function (string $st): string {
    $map = [
        'pending' => ['Menunggu Review', '#dbeafe', '#1d4ed8'],
        'approved' => ['Disetujui', '#d1fae5', '#065f46'],
        'rejected' => ['Ditolak', '#fee2e2', '#991b1b'],
    ];
    $item = $map[$st] ?? [$st, '#f1f5f9', '#334155'];
    return '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;white-space:nowrap;background:' . $item[1] . ';color:' . $item[2] . ';">' . htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8') . '</span>';
};

$statusLabel = 'Belum Terverifikasi';
$statusColor = '#334155';
$statusBg = '#e2e8f0';
if ($status === 'pending' && trim((string)($worker['reviewed_at'] ?? '')) !== '') {
    $statusLabel = 'Menunggu Verifikasi';
    $statusColor = '#92400e';
    $statusBg = '#fef3c7';
}
if ($status === 'revision') {
    $statusLabel = 'Perlu Revisi';
    $statusColor = '#9a3412';
    $statusBg = '#ffedd5';
} elseif ($status === 'approved') {
    $statusLabel = 'Terverifikasi';
    $statusColor = '#065f46';
    $statusBg = '#d1fae5';
} elseif ($status === 'rejected') {
    $statusLabel = 'Ditolak';
    $statusColor = '#991b1b';
    $statusBg = '#fee2e2';
}

$extractPortfolioLinks = static function (array $portItem): array {
    $links = [];
    $seen = [];

    $addLink = static function (string $url, string $name, string $type) use (&$links, &$seen): void {
        $cleanUrl = trim($url);
        if ($cleanUrl === '' || $cleanUrl === '#') {
            return;
        }
        $key = strtolower($cleanUrl);
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
        $links[] = [
            'url' => $cleanUrl,
            'name' => trim($name) !== '' ? trim($name) : 'Tautan Portofolio',
            'type' => trim($type),
        ];
    };

    if (!empty($portItem['files']) && is_array($portItem['files'])) {
        foreach ($portItem['files'] as $fileItem) {
            if (is_array($fileItem)) {
                $addLink(
                    (string)($fileItem['url'] ?? $fileItem['link'] ?? ''),
                    (string)($fileItem['name'] ?? ''),
                    (string)($fileItem['type'] ?? '')
                );
            } elseif (is_string($fileItem)) {
                $addLink($fileItem, '', '');
            }
        }
    }

    if (!empty($portItem['links']) && is_array($portItem['links'])) {
        foreach ($portItem['links'] as $linkItem) {
            if (is_string($linkItem)) {
                $addLink($linkItem, '', '');
            } elseif (is_array($linkItem)) {
                $addLink(
                    (string)($linkItem['url'] ?? $linkItem['link'] ?? ''),
                    (string)($linkItem['name'] ?? ''),
                    (string)($linkItem['type'] ?? '')
                );
            }
        }
    }

    $addLink(
        (string)($portItem['url'] ?? $portItem['link'] ?? ''),
        (string)($portItem['title'] ?? ''),
        (string)($portItem['type'] ?? '')
    );

    return $links;
};
?>

<style>
  .detail-wrap { background:#fff;border:1px solid var(--border-subtle);border-radius:14px;box-shadow:var(--shadow-xs);overflow:hidden; }
  .detail-top { padding:18px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap; }
  .detail-id { font-size:0.74rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.03em; }
  .detail-name { font-size:1.8rem;font-weight:800;color:#0f172a;line-height:1.2;margin:3px 0 8px; }
  .detail-meta { display:flex;gap:14px;flex-wrap:wrap;font-size:0.84rem;color:#64748b; }
  .detail-actions { display:flex;gap:8px;flex-wrap:wrap; }
  .detail-btn { border:1px solid #e2e8f0;background:#fff;border-radius:10px;padding:8px 12px;font-size:0.8rem;font-weight:700;color:#0f172a; }
  .detail-btn.primary { border-color:#99f6e4;background:#ecfeff;color:#0f766e; }
  .detail-tabs { padding:0 18px;display:flex;gap:14px;border-bottom:1px solid #e2e8f0; }
  .detail-tabs a { display:inline-flex;padding:12px 2px;font-size:0.82rem;font-weight:700;color:#64748b;border-bottom:2px solid transparent; }
  .detail-tabs a.active { color:#0ea5e9;border-bottom-color:#0ea5e9; }
  .detail-grid { padding:18px;display:grid;grid-template-columns:2fr 1fr;gap:14px; }
  .detail-card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px; }
  .detail-card h3 { margin:0 0 10px;font-size:1rem;font-weight:800;color:#0f172a; }
  .audit-log-list { display:grid; gap:10px; }
  .audit-log-item { border-left:2px solid #bfdbfe; padding-left:10px; }
  .audit-log-time { font-size:0.72rem; color:#64748b; font-weight:700; margin-bottom:2px; }
  .audit-log-title { font-size:0.82rem; color:#0f172a; font-weight:700; margin-bottom:2px; }
  .audit-log-detail { font-size:0.78rem; color:#475569; line-height:1.45; }
  .detail-item { border:1px solid #eef2f7;background:#f8fafc;border-radius:10px;padding:10px;margin-bottom:8px; }
  .detail-item:last-child { margin-bottom:0; }
  .detail-item-title { font-size:0.86rem;font-weight:700;color:#0f172a; }
  .detail-item-sub { font-size:0.78rem;color:#64748b;margin-top:2px;line-height:1.45; }
.portfolio-files { margin-top:8px; display:grid; gap:6px; }
.portfolio-file-link { display:inline-flex; align-items:center; gap:6px; font-size:0.78rem; font-weight:700; color:#1d4ed8; text-decoration:none; }
.portfolio-file-link:hover { text-decoration:underline; }
  .portfolio-file-list {
    margin-top: 10px;
    display: grid;
    gap: 8px;
  }
  .portfolio-file-item {
    background: #ffffff;
    border: 1px solid #dbeafe;
    border-radius: 10px;
    padding: 9px 10px;
  }
  .portfolio-file-meta {
    font-size: 0.76rem;
    color: #64748b;
    line-height: 1.4;
    margin-bottom: 7px;
  }
  .portfolio-file-name {
    font-size: 0.82rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 3px;
  }
  .portfolio-file-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    color: #1d4ed8;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 0.76rem;
    font-weight: 800;
    text-decoration: none;
  }
  .portfolio-file-btn:hover {
    background: #dbeafe;
  }
  .portfolio-empty-note {
    margin-top: 8px;
    border: 1px dashed #cbd5e1;
    background: #f8fafc;
    color: #64748b;
    border-radius: 10px;
    padding: 9px 10px;
    font-size: 0.78rem;
    line-height: 1.45;
  }
  .edit-request-panel {
    border: 1px solid #dbeafe;
    background: #f8fbff;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 6px;
  }
  .edit-request-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e2e8f0;
  }
  .edit-request-title {
    font-size: 0.88rem;
    font-weight: 800;
    color: #0f172a;
  }
  .edit-request-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 14px;
  }
  .edit-field {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 10px;
  }
  .edit-field-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 4px;
  }
  .edit-field-value {
    font-size: 0.82rem;
    color: #1e293b;
    line-height: 1.45;
    font-weight: 600;
  }
  .edit-help-note {
    margin-top: 8px;
    font-size: 0.78rem;
    color: #475569;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 8px 10px;
    line-height: 1.45;
  }
  .edit-compare-grid {
    margin-top: 8px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }
  .edit-compare-col {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 10px;
    background: #ffffff;
  }
  .edit-compare-col h4 {
    margin: 0 0 6px 0;
    font-size: 0.76rem;
    font-weight: 800;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }
  .edit-compare-col p {
    margin: 0;
    font-size: 0.8rem;
    color: #1e293b;
    line-height: 1.45;
    font-weight: 600;
  }
  .edit-field.full { grid-column: 1 / -1; }
  .profile-row { display:grid;grid-template-columns:140px 1fr;gap:8px;font-size:0.82rem;padding:6px 0;border-bottom:1px dashed #e2e8f0; }
  .profile-row:last-child { border-bottom:none; }
  .profile-row .k { color:#64748b; font-weight:600; }
  .profile-row .v { color:#0f172a; font-weight:600; }
  .verify-panel { margin-top:14px;padding-top:12px;border-top:1px solid #e2e8f0; }
  .verify-panel textarea { width:100%;min-height:70px;border:1px solid #cbd5e1;border-radius:10px;padding:9px;font:inherit;margin:8px 0; }
  .edit-verify-actions { display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start;margin-top:8px; }
  .edit-verify-actions textarea { width:100%;min-height:70px;border:1px solid #cbd5e1;border-radius:10px;padding:9px;font:inherit; }
  .flash { padding:11px 13px;border-radius:10px;margin-bottom:14px;font-size:0.84rem;font-weight:700; }
  .flash.success { background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0; }
  .flash.error { background:#fef2f2;color:#991b1b;border:1px solid #fecaca; }
  @media (max-width: 980px) { .detail-grid { grid-template-columns:1fr; } .detail-name { font-size:1.45rem; } .edit-request-grid { grid-template-columns:1fr; } .edit-compare-grid { grid-template-columns:1fr; } }
</style>

<div style="margin-bottom:10px;">
  <a href="dashboard-admin.php?tab=workers" style="font-size:0.84rem;font-weight:700;color:#334155;">&larr; Kembali</a>
</div>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<section class="detail-wrap">
  <div class="detail-top">
    <div>
      <div class="detail-id">Detail Gig Worker</div>
      <div class="detail-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="detail-meta">
        <span><?php echo htmlspecialchars((string)$worker['contact_email'], ENT_QUOTES, 'UTF-8'); ?></span>
        <span><?php echo htmlspecialchars((string)$worker['contact_wa'], ENT_QUOTES, 'UTF-8'); ?></span>
        <span><?php echo htmlspecialchars((string)$worker['bidang_keahlian'], ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    </div>
    <div class="detail-actions">
      <button type="button" class="detail-btn" onclick="openAdminDecisionModal({entityType:'worker', entityName:'profil Gig Worker', username:'<?php echo htmlspecialchars((string)$worker['username'], ENT_QUOTES, 'UTF-8'); ?>', action:'worker_take_decision'})">Ambil Keputusan</button>
      <button type="button" class="detail-btn" onclick="window.print()">Cetak Kartu</button>
    </div>
  </div>

  <nav class="detail-tabs" aria-label="Detail tabs">
    <a href="#" class="active">Biodata</a>
    <a href="#">Pengalaman</a>
    <a href="#">Portofolio</a>
    <a href="#">Riwayat Verifikasi</a>
  </nav>

  <div class="detail-grid">
    <div>
      <article class="detail-card">
        <?php if ($selectedEdit !== null): ?>
          <?php $editPayload = is_array($selectedEdit['proposed_payload'] ?? null) ? $selectedEdit['proposed_payload'] : []; ?>
          <h3>Permintaan Edit Profil</h3>
          <div class="edit-request-panel">
            <div class="edit-request-head">
              <div class="edit-request-title">Ringkasan Pengajuan</div>
              <div><?php echo $editStatusBadge((string)($selectedEdit['status'] ?? 'pending')); ?></div>
            </div>
            <div class="edit-request-grid">
              <div class="edit-field">
                <div class="edit-field-label">Bidang Diubah</div>
                <div class="edit-field-value"><?php echo htmlspecialchars((string)($selectedEdit['edited_field'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="edit-field">
                <div class="edit-field-label">Alasan</div>
                <div class="edit-field-value"><?php echo htmlspecialchars((string)($selectedEdit['reason_code'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="edit-field full">
                <div class="edit-field-label">Ringkasan Perubahan</div>
                <div class="edit-field-value"><?php echo htmlspecialchars((string)($selectedEdit['change_summary'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="edit-field full">
                <div class="edit-field-label">Penjelasan User</div>
                <div class="edit-field-value"><?php echo htmlspecialchars((string)($selectedEdit['reason_detail'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="edit-field full">
                <div class="edit-field-label">Data Profil yang Diajukan</div>
                <div class="edit-help-note">
                  Ini adalah versi data profil baru yang diajukan oleh Gig Worker. Jika admin menyetujui pengajuan, data di kolom <strong>Data Diajukan</strong> akan menggantikan data saat ini.
                </div>
                <div class="edit-compare-grid">
                  <div class="edit-compare-col">
                    <h4>Data Saat Ini</h4>
                    <p>
                      Nama: <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?><br>
                      Bidang: <?php echo htmlspecialchars((string)($worker['bidang_keahlian'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?><br>
                      Skill: <?php echo htmlspecialchars(gig_format_skills_summary($worker['skills'] ?? []), ENT_QUOTES, 'UTF-8'); ?><br>
                      Lokasi: <?php echo htmlspecialchars($domicile, ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                  </div>
                  <div class="edit-compare-col">
                    <h4>Data Diajukan</h4>
                    <p>
                      Nama: <?php echo htmlspecialchars((string)($editPayload['name'] ?? $displayName), ENT_QUOTES, 'UTF-8'); ?><br>
                      Bidang: <?php echo htmlspecialchars((string)($editPayload['title'] ?? ($worker['bidang_keahlian'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?><br>
                      Skill: <?php echo htmlspecialchars(gig_format_skills_summary($editPayload['skills'] ?? ($worker['skills'] ?? [])), ENT_QUOTES, 'UTF-8'); ?><br>
                      Lokasi: <?php echo htmlspecialchars((string)($editPayload['location'] ?? $domicile), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <?php if ((string)($selectedEdit['status'] ?? '') === 'pending'): ?>
              <form method="post" class="edit-verify-actions">
                <input type="hidden" name="edit_id" value="<?php echo (int)$selectedEdit['id']; ?>">
                <textarea name="admin_note" placeholder="Catatan verifikasi edit profil (opsional)"></textarea>
                <button class="btn-approve" name="action" value="worker_edit_approve" type="submit">Setujui Edit Profil</button>
                <button class="btn-reject" name="action" value="worker_edit_reject" type="submit">Tolak Edit Profil</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <h3 style="margin-top:<?php echo $selectedEdit !== null ? '14px' : '0'; ?>;">Pengalaman</h3>
        <?php if ($projects === []): ?>
          <div class="detail-item"><div class="detail-item-sub">Belum ada pengalaman proyek yang diisi.</div></div>
        <?php endif; ?>
        <?php foreach ($projects as $exp): ?>
          <?php
            $outTitle = !empty($exp['output_title']) ? $exp['output_title'] : ('Output Proyek - ' . ($exp['project'] ?? 'Deliverables Proyek'));
            $outFiles = !empty($exp['files']) && is_array($exp['files']) ? $exp['files'] : [
                ['name' => 'Tautan Output Proyek (' . ($exp['project'] ?? 'Hasil Karya') . ')', 'url' => 'https://figma.com/@gigworker/' . urlencode(strtolower(str_replace([' ', '(', ')', '/'], ['-', '', '', '-'], $exp['project'] ?? 'output-proyek')))]
            ];
          ?>
          <div class="detail-item" style="margin-bottom:14px;">
            <div class="detail-item-title" style="font-weight:700;color:#0f172a;"><?php echo htmlspecialchars((string)($exp['role'] ?? $exp['project'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="detail-item-sub" style="color:#64748b;"><?php echo htmlspecialchars((string)($exp['project'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string)($exp['period'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php if (!empty($exp['summary'])): ?>
              <div class="detail-item-sub" style="margin-top:4px;color:#334155;"><?php echo htmlspecialchars((string)$exp['summary'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div style="margin-top:10px;padding:10px 12px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:8px;">
              <div style="font-size:0.8rem;font-weight:700;color:#1e40af;margin-bottom:6px;">
                📁 Output Proyek: <?php echo htmlspecialchars((string)$outTitle, ENT_QUOTES, 'UTF-8'); ?>
              </div>
              <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($outFiles as $f): ?>
                  <?php 
                    $fUrl = trim((string)($f['url'] ?? '')); 
                    if ($fUrl === '' || $fUrl === '#') {
                        $fUrl = 'https://figma.com/@gigworker/' . urlencode(strtolower(str_replace([' ', '(', ')', '/'], ['-', '', '', '-'], $exp['project'] ?? 'output')));
                    }
                  ?>
                  <a href="<?php echo htmlspecialchars($fUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:6px;background:#ffffff;color:#1d4ed8;border:1px solid #93c5fd;padding:5px 10px;border-radius:6px;font-size:0.75rem;font-weight:700;text-decoration:none;">
                    🔗 <?php echo htmlspecialchars((string)($f['name'] ?? 'Lihat Tautan Output Proyek'), ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </article>

      <article class="detail-card" style="margin-top:12px;">
        <h3>Portofolio</h3>
        <?php if ($portfolio === []): ?>
          <div class="detail-item"><div class="detail-item-sub">Belum ada portofolio yang diisi.</div></div>
        <?php endif; ?>
        <?php foreach ($portfolio as $port): ?>
          <?php
            $portFiles = $extractPortfolioLinks(is_array($port) ? $port : []);
          ?>
          <div class="detail-item">
            <div class="detail-item-title"><?php echo htmlspecialchars((string)($port['title'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="detail-item-sub"><?php echo htmlspecialchars((string)($port['type'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php if (!empty($port['deliverable'])): ?>
              <div class="detail-item-sub"><?php echo htmlspecialchars((string)$port['deliverable'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($portFiles !== []): ?>
              <div class="portfolio-file-list">
                <?php foreach ($portFiles as $idxFile => $file): ?>
                  <div class="portfolio-file-item">
                    <div class="portfolio-file-name">File <?php echo (int)$idxFile + 1; ?>: <?php echo htmlspecialchars((string)$file['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="portfolio-file-meta">
                      Tipe: <?php echo $file['type'] !== '' ? htmlspecialchars((string)$file['type'], ENT_QUOTES, 'UTF-8') : 'Dokumen/Link'; ?>
                    </div>
                    <a class="portfolio-file-btn" href="<?php echo htmlspecialchars((string)$file['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                      Buka Tautan <?php echo (int)$idxFile + 1; ?>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="portfolio-empty-note">
                Gig Worker belum menyertakan tautan akses pada item portofolio ini.
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </article>
    </div>

    <aside>
      <article class="detail-card">
        <h3>Profil</h3>
        <div class="profile-row"><div class="k">Nama</div><div class="v"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="profile-row"><div class="k">Domisili</div><div class="v"><?php echo htmlspecialchars($domicile, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="profile-row"><div class="k">Bidang</div><div class="v"><?php echo htmlspecialchars((string)$worker['bidang_keahlian'], ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="profile-row"><div class="k">Kontak</div><div class="v"><?php echo htmlspecialchars((string)$worker['contact_email'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars((string)$worker['contact_wa'], ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="profile-row">
          <div class="k">Skill</div>
          <div class="v">
            <?php if ($skills === []): ?>
              -
            <?php else: ?>
              <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:2px;">
                <?php foreach ($skills as $sk): ?>
                  <?php
                    $skName = gig_skill_name($sk);
                    $skLvl = gig_skill_level($sk);
                    $skStyle = gig_skill_level_badge_style($skLvl);
                  ?>
                  <span style="display:inline-flex;align-items:center;gap:4px;background:#f8fafc;border:1px solid #cbd5e1;padding:2px 8px;border-radius:6px;font-size:0.75rem;">
                    <strong><?php echo htmlspecialchars($skName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span style="background:<?php echo $skStyle['bg']; ?>;color:<?php echo $skStyle['color']; ?>;padding:1px 5px;border-radius:4px;font-size:0.68rem;font-weight:700;"><?php echo htmlspecialchars($skLvl, ENT_QUOTES, 'UTF-8'); ?></span>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="profile-row"><div class="k">Video</div><div class="v"><?php echo !empty($worker['video_url']) ? '<a href="' . htmlspecialchars((string)$worker['video_url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Lihat video</a>' : '-'; ?></div></div>
        <div class="profile-row"><div class="k">Dikirim</div><div class="v"><?php echo htmlspecialchars((string)($worker['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="profile-row"><div class="k">Status</div><div class="v"><span style="background:<?php echo htmlspecialchars($statusBg, ENT_QUOTES, 'UTF-8'); ?>;color:<?php echo htmlspecialchars($statusColor, ENT_QUOTES, 'UTF-8'); ?>;padding:4px 10px;border-radius:999px;font-size:0.74rem;font-weight:800;white-space:nowrap;"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span></div></div>
        <div class="profile-row"><div class="k">Edit Profil</div><div class="v"><?php echo count($relatedEdits); ?> pengajuan</div></div>

        <?php if (!empty($worker['admin_note'])): ?>
          <div class="verify-panel">
            <div style="font-size:0.8rem;color:#334155;"><strong>Catatan Admin:</strong><br><?php echo htmlspecialchars((string)$worker['admin_note'], ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        <?php endif; ?>
      </article>

      <article class="detail-card" style="margin-top:12px;">
        <h3>Aktivitas &amp; Audit Log</h3>
        <?php if ($auditLogs === []): ?>
          <div class="detail-item"><div class="detail-item-sub">Belum ada aktivitas tercatat.</div></div>
        <?php else: ?>
          <div class="audit-log-list">
            <?php foreach ($auditLogs as $log): ?>
              <div class="audit-log-item">
                <div class="audit-log-time"><?php echo htmlspecialchars((string)$log['time'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="audit-log-title"><?php echo htmlspecialchars((string)$log['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="audit-log-detail"><?php echo htmlspecialchars((string)$log['detail'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>
    </aside>
  </div>
</section>

<?php require __DIR__ . '/includes/admin-layout-end.php'; ?>
