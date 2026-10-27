<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/admin-store.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/vacancy-store.php';

$flash = '';
$flashType = 'success';
$tab = (string)($_GET['tab'] ?? 'verification');
if ($tab === 'overview') {
    $tab = 'verification';
}
$allowedTabs = ['verification', 'workers', 'projects'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'verification';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
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
    $decisionChoice = (string)($_POST['decision'] ?? '');
    $redirectTab = (string)($_POST['tab'] ?? 'verification');
    if ($redirectTab === 'overview') {
        $redirectTab = 'verification';
    }

    if ($action === 'worker_approve') {
        $ok = gig_admin_set_worker_status((string)($_POST['username'] ?? ''), 'approved', $note);
        $flash = $ok ? 'Pendaftaran Gig Worker disetujui.' : 'Gagal memproses pendaftaran worker.';
        $flashType = $ok ? 'success' : 'error';
        $redirectTab = 'workers';
    } elseif ($action === 'worker_reject') {
        $ok = gig_admin_set_worker_status((string)($_POST['username'] ?? ''), 'rejected', $note);
        $flash = $ok ? 'Pendaftaran Gig Worker ditolak.' : 'Gagal menolak pendaftaran worker.';
        $flashType = $ok ? 'success' : 'error';
        $redirectTab = 'workers';
    } elseif ($action === 'worker_decision' || $action === 'worker_take_decision') {
        $username = (string)($_POST['username'] ?? '');
        if ($decisionChoice === 'approve') {
            $ok = gig_admin_set_worker_status($username, 'approved', $note);
            $flash = $ok ? 'Pendaftaran Gig Worker disetujui.' : 'Gagal memproses pendaftaran worker.';
        } elseif ($decisionChoice === 'reject') {
            if ($note === '') { $note = 'Data/berkas profil Gig Worker tidak memenuhi syarat.'; }
            $ok = gig_admin_set_worker_status($username, 'rejected', $note);
            $flash = $ok ? 'Pendaftaran Gig Worker ditolak.' : 'Gagal menolak pendaftaran worker.';
        } else {
            if ($note === '') { $note = 'Harap perbaiki data/berkas pendaftaran worker.'; }
            $ok = gig_admin_set_worker_status($username, 'revision', $note);
            $flash = $ok ? 'Permintaan revisi dikirim. Status diubah menjadi Perlu Revisi.' : 'Gagal mengirim catatan revisi.';
        }
        $flashType = $ok ? 'success' : 'error';
        $redirectTab = 'workers';
    } elseif ($action === 'worker_edit_approve') {
        $ok = gig_admin_set_worker_profile_edit_status((int)($_POST['edit_id'] ?? 0), 'approved', $note);
        $flash = $ok ? 'Pengajuan edit profil worker disetujui.' : 'Gagal menyetujui pengajuan edit profil.';
        $flashType = $ok ? 'success' : 'error';
        $redirectTab = 'workers';
    } elseif ($action === 'worker_edit_reject') {
        $ok = gig_admin_set_worker_profile_edit_status((int)($_POST['edit_id'] ?? 0), 'rejected', $note);
        $flash = $ok ? 'Pengajuan edit profil worker ditolak.' : 'Gagal menolak pengajuan edit profil.';
        $flashType = $ok ? 'success' : 'error';
        $redirectTab = 'workers';
    } elseif ($action === 'worker_edit_decision') {
        $editId = (int)($_POST['edit_id'] ?? 0);
        if ($decisionChoice === 'approve') {
            $ok = gig_admin_set_worker_profile_edit_status($editId, 'approved', $note);
            $flash = $ok ? 'Pengajuan edit profil worker disetujui.' : 'Gagal menyetujui pengajuan edit profil.';
        } else {
            $ok = gig_admin_set_worker_profile_edit_status($editId, 'rejected', $note);
            $flash = $ok ? 'Pengajuan edit profil worker ditolak.' : 'Gagal menolak pengajuan edit profil.';
        }
        $flashType = $ok ? 'success' : 'error';
        $redirectTab = 'workers';
    } elseif ($action === 'vacancy_approve') {
        $id = (string)($_POST['vacancy_id'] ?? $_POST['id'] ?? '');
        $ok = gig_vacancy_set_status($id, 'active', $note !== '' ? $note : 'Disetujui Admin KarirHub.');
        $flash = $ok ? 'Lowongan proyek disetujui dan dapat ditayangkan.' : 'Gagal menyetujui lowongan.';
        $flashType = $ok ? 'success' : 'error';
        $redirectTab = 'projects';
    } elseif ($action === 'vacancy_revision') {
        $id = (string)($_POST['vacancy_id'] ?? $_POST['id'] ?? '');
        if ($note === '') {
            $note = 'Harap perbaiki detail lowongan sesuai catatan Admin.';
        }
        $ok = gig_vacancy_set_status($id, 'revision', $note);
        $flash = $ok ? 'Lowongan dikembalikan untuk revisi.' : 'Gagal mengirim permintaan revisi.';
        $flashType = $ok ? 'success' : 'error';
        $redirectTab = 'projects';
    } elseif ($action === 'vacancy_reject') {
        $id = (string)($_POST['vacancy_id'] ?? $_POST['id'] ?? '');
        if ($note === '') {
            $note = 'Lowongan tidak memenuhi Syarat & Ketentuan KarirHub.';
        }
        $ok = gig_vacancy_set_status($id, 'rejected', $note);
        $flash = $ok ? 'Lowongan proyek ditolak.' : 'Gagal menolak lowongan.';
        $flashType = $ok ? 'success' : 'error';
        $redirectTab = 'projects';
    } elseif ($action === 'vacancy_decision') {
        $id = (string)($_POST['vacancy_id'] ?? $_POST['id'] ?? '');
        if ($decisionChoice === 'approve') {
            $ok = gig_vacancy_set_status($id, 'active', $note !== '' ? $note : 'Disetujui Admin KarirHub.');
            $flash = $ok ? 'Lowongan proyek disetujui dan dapat ditayangkan.' : 'Gagal menyetujui lowongan.';
        } elseif ($decisionChoice === 'reject') {
            if ($note === '') { $note = 'Lowongan tidak memenuhi Syarat & Ketentuan KarirHub.'; }
            $ok = gig_vacancy_set_status($id, 'rejected', $note);
            $flash = $ok ? 'Lowongan proyek ditolak.' : 'Gagal menolak lowongan.';
        } else {
            if ($note === '') { $note = 'Harap perbaiki detail lowongan sesuai catatan Admin.'; }
            $ok = gig_vacancy_set_status($id, 'revision', $note);
            $flash = $ok ? 'Lowongan dikembalikan untuk revisi.' : 'Gagal mengirim permintaan revisi.';
        }
        $flashType = $ok ? 'success' : 'error';
        $redirectTab = 'projects';
    }

    header('Location: dashboard-admin.php?tab=' . urlencode($redirectTab) . '&msg=' . urlencode($flash) . '&type=' . urlencode($flashType));
    exit;
}

if (isset($_GET['msg'])) {
    $flash = (string)$_GET['msg'];
    $flashType = (string)($_GET['type'] ?? 'success');
}

$workers = gig_admin_list_worker_registrations();
$employers = gig_admin_list_employer_registrations();
$vacancies = gig_admin_list_project_vacancies();
$workerProfileEdits = gig_admin_list_worker_profile_edits();
$metrics = gig_admin_dashboard_metrics($workers, $employers, $vacancies);
$clusters = gig_admin_cluster_by_industry($employers, $vacancies);

$searchQ = trim((string)($_GET['q'] ?? ''));
$state = trim((string)($_GET['state'] ?? 'all'));
$allowedStates = ['all', 'pending', 'revision', 'approved', 'rejected'];
if (!in_array($state, $allowedStates, true)) {
    $state = 'all';
}
$matchesSearch = static function (array $row, array $fields) use ($searchQ): bool {
    if ($searchQ === '') {
        return true;
    }
    $needle = strtolower($searchQ);
    foreach ($fields as $field) {
        if (str_contains(strtolower((string)($row[$field] ?? '')), $needle)) {
            return true;
        }
    }
    return false;
};

if ($searchQ !== '') {
    $workers = array_values(array_filter($workers, fn($r) => $matchesSearch($r, ['username', 'bidang_keahlian', 'contact_email', 'skills'])));
    $employers = array_values(array_filter($employers, fn($r) => $matchesSearch($r, ['nama_pic', 'company_name', 'siapkerja_email', 'industry'])));
    $vacancies = array_values(array_filter($vacancies, fn($r) => $matchesSearch($r, ['title', 'id', 'employer', 'category', 'location'])));
    $workerProfileEdits = array_values(array_filter($workerProfileEdits, fn($r) => $matchesSearch($r, ['worker_username', 'worker_email', 'edited_field', 'reason_code', 'reason_detail', 'change_summary'])));
}

$filterByState = static function (array $rows, string $currentState): array {
    if ($currentState === 'all') {
        return $rows;
    }
    if ($currentState === 'revision') {
        return array_values(array_filter($rows, static fn($r) => ($r['status'] ?? '') === 'revision'));
    }
    return array_values(array_filter($rows, static fn($r) => ($r['status'] ?? 'pending') === $currentState));
};

$pendingWorkers = array_values(array_filter($workers, fn($r) => ($r['status'] ?? 'pending') === 'pending'));
$pendingEmployers = array_values(array_filter($employers, fn($r) => ($r['status'] ?? 'pending') === 'pending'));
$pendingProjects = array_values(array_filter($vacancies, fn($v) => ($v['status'] ?? '') === 'review'));
$pendingProfileEdits = array_values(array_filter($workerProfileEdits, fn($r) => ($r['status'] ?? 'pending') === 'pending'));
$pendingEditsByWorkerKey = [];
foreach ($pendingProfileEdits as $edit) {
    $wKey = gig_worker_profile_edit_key((string)($edit['worker_username'] ?? ''));
    if (!isset($pendingEditsByWorkerKey[$wKey])) {
        $pendingEditsByWorkerKey[$wKey] = [];
    }
    $pendingEditsByWorkerKey[$wKey][] = $edit;
}
$nextPendingTab = $metrics['workers_pending'] > 0
    ? 'workers'
    : ($metrics['vacancies_review'] > 0 ? 'projects' : 'verification');
if ($nextPendingTab === 'verification' && (int)$metrics['worker_profile_edits_pending'] > 0) {
    $nextPendingTab = 'workers';
}

if (!function_exists('admin_status_badge')) {
    function admin_status_badge(string $status): string
    {
        $map = [
            'pending' => ['Menunggu Verifikasi', '#dbeafe', '#1e40af'],
            'approved' => ['Disetujui', '#d1fae5', '#065f46'],
            'active' => ['Tayang', '#d1fae5', '#065f46'],
            'rejected' => ['Ditolak', '#fee2e2', '#991b1b'],
            'revision' => ['Revisi', '#ffedd5', '#9a3412'],
            'review' => ['Verifikasi', '#dbeafe', '#1e40af'],
        ];
        $item = $map[$status] ?? [$status, '#f1f5f9', '#334155'];
        return '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;white-space:nowrap;background:' . $item[1] . ';color:' . $item[2] . ';">' . htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

if (!function_exists('admin_worker_status_badge')) {
    function admin_worker_status_badge(array $row): string
    {
        $status = (string)($row['status'] ?? 'pending');
        if ($status === 'approved') {
            return '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;white-space:nowrap;background:#d1fae5;color:#065f46;">Terverifikasi</span>';
        }
        if ($status === 'revision') {
            return '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;white-space:nowrap;background:#ffedd5;color:#9a3412;">Perlu Revisi</span>';
        }
        if ($status === 'rejected') {
            return '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;white-space:nowrap;background:#fee2e2;color:#991b1b;">Ditolak</span>';
        }

        $isRevisionRequested = trim((string)($row['reviewed_at'] ?? '')) !== '';
        if ($isRevisionRequested) {
            return '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;white-space:nowrap;background:#fef3c7;color:#92400e;">Menunggu Verifikasi</span>';
        }

        return '<span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;white-space:nowrap;background:#e2e8f0;color:#334155;">Belum Terverifikasi</span>';
    }
}

if (!function_exists('admin_bar_height')) {
    function admin_bar_height(int $value, int $max, int $cap = 130): int
    {
        if ($value <= 0) {
            return 8;
        }
        $max = max($max, 1);
        return max(10, (int)round($value / $max * $cap));
    }
}

if (!function_exists('admin_render_chart')) {
    function admin_render_chart(string $title, array $segments): void
    {
        $max = max(1, ...array_map(static fn($s) => (int)$s['value'], $segments));
        echo '<article class="admin-chart-card"><h3>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3><div class="admin-bars">';
        foreach ($segments as $seg) {
            $val = (int)$seg['value'];
            $h = admin_bar_height($val, $max);
            $color = htmlspecialchars((string)$seg['color'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string)$seg['label'], ENT_QUOTES, 'UTF-8');
            echo '<div class="admin-bar-col">';
            echo '<div class="admin-bar-val">' . $val . '</div>';
            echo '<div class="admin-bar" style="height:' . $h . 'px;background:' . $color . ';"></div>';
            echo '<div class="admin-bar-label">' . $label . '</div>';
            echo '</div>';
        }
        echo '</div></article>';
    }
}

if (!function_exists('admin_state_tab_url')) {
    function admin_state_tab_url(string $tab, string $state, string $q, string $entity = 'all'): string
    {
        $params = ['tab' => $tab, 'state' => $state];
        if ($entity !== 'all') {
            $params['entity'] = $entity;
        }
        if ($q !== '') {
            $params['q'] = $q;
        }
        return 'dashboard-admin.php?' . http_build_query($params);
    }
}

$adminTab = $tab;
$pageTitle = 'Dashboard Admin';
$breadcrumbCurrent = 'Dashboard';
if ($tab === 'workers') {
    $breadcrumbCurrent = 'Verifikasi Gig Worker';
} elseif ($tab === 'projects') {
    $breadcrumbCurrent = 'Verifikasi Lowongan Proyek';
}
require __DIR__ . '/includes/admin-layout-start.php';
?>

    <?php
      $mainHeading = 'Dashboard';
      if ($tab === 'workers') {
          $mainHeading = 'Verifikasi Gig Worker';
      } elseif ($tab === 'projects') {
          $mainHeading = 'Verifikasi Lowongan Proyek';
      }
    ?>
    <div class="admin-page-head">
      <h1><?php echo htmlspecialchars($mainHeading, ENT_QUOTES, 'UTF-8'); ?></h1>
    </div>

    <?php if ($flash !== ''): ?>
      <div class="flash <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <nav class="admin-subtabs" aria-label="Tab dashboard admin">
      <a href="?tab=verification" class="<?php echo $tab === 'verification' ? 'active' : ''; ?>">Verifikasi</a>
      <a href="?tab=workers" class="<?php echo $tab === 'workers' ? 'active' : ''; ?>">Gig Worker<?php $wQueue = (int)$metrics['workers_pending'] + (int)$metrics['worker_profile_edits_pending']; echo $wQueue > 0 ? ' (' . $wQueue . ')' : ''; ?></a>
      <a href="?tab=projects" class="<?php echo $tab === 'projects' ? 'active' : ''; ?>">Lowongan Proyek<?php echo $metrics['vacancies_review'] > 0 ? ' (' . (int)$metrics['vacancies_review'] . ')' : ''; ?></a>
    </nav>

    <?php if ($tab === 'verification'): ?>
      <div class="admin-toolbar-row">
        <a href="?tab=<?php echo urlencode($nextPendingTab); ?>" class="admin-btn-ghost">Menunggu verifikasi: <strong><?php echo (int)$metrics['pending_all']; ?></strong></a>
      </div>

      <section class="admin-kpi-grid">
        <a href="?tab=workers" class="admin-kpi-link">
          <article class="admin-kpi-card">
            <div class="kpi-label">Pengajuan Gig Worker</div>
            <div class="kpi-value"><?php echo number_format($metrics['workers_total'], 0, ',', '.'); ?></div>
            <div class="kpi-sub"><?php echo (int)$metrics['workers_pending']; ?> pendaftaran · <?php echo (int)$metrics['worker_profile_edits_pending']; ?> edit profil</div>
          </article>
        </a>
        <a href="?tab=projects" class="admin-kpi-link">
          <article class="admin-kpi-card accent-teal">
            <div class="kpi-label">Pengajuan Lowongan Proyek</div>
            <div class="kpi-value"><?php echo number_format($metrics['vacancies_total'], 0, ',', '.'); ?></div>
            <div class="kpi-sub"><?php echo (int)$metrics['vacancies_review']; ?> menunggu verifikasi</div>
          </article>
        </a>
        <a href="?tab=projects" class="admin-kpi-link">
          <article class="admin-kpi-card accent-green">
            <div class="kpi-label">Disetujui / Tayang</div>
            <div class="kpi-value"><?php echo number_format($metrics['workers_approved'] + $metrics['employers_approved'] + $metrics['vacancies_active'], 0, ',', '.'); ?></div>
            <div class="kpi-sub"><?php echo (int)$metrics['workers_approved']; ?> worker · <?php echo (int)$metrics['employers_approved']; ?> employer · <?php echo (int)$metrics['vacancies_active']; ?> lowongan aktif</div>
          </article>
        </a>
      </section>

      <div class="admin-chart-grid">
        <?php
        admin_render_chart('Status Verifikasi Gig Worker', [
            ['label' => 'Menunggu Revisi', 'value' => $metrics['workers_pending'], 'color' => '#f59e0b'],
            ['label' => 'Disetujui', 'value' => $metrics['workers_approved'], 'color' => '#1e3a8a'],
            ['label' => 'Ditolak', 'value' => $metrics['workers_rejected'], 'color' => '#ef4444'],
        ]);
        admin_render_chart('Status Verifikasi Lowongan', [
            ['label' => 'Menunggu Revisi', 'value' => $metrics['vacancies_review'], 'color' => '#f59e0b'],
            ['label' => 'Revisi', 'value' => $metrics['vacancies_revision'], 'color' => '#14b8a6'],
            ['label' => 'Disetujui', 'value' => $metrics['vacancies_active'], 'color' => '#1e3a8a'],
            ['label' => 'Ditolak', 'value' => $metrics['vacancies_rejected'], 'color' => '#ef4444'],
        ]);
        ?>
      </div>
      <div class="admin-chart-grid">
        <article class="admin-chart-card">
          <h3>Antrian Verifikasi Terbaru</h3>
          <?php if ($metrics['pending_all'] === 0): ?>
            <p style="font-size:0.86rem;color:#64748b;">Tidak ada pengajuan yang menunggu verifikasi.</p>
          <?php else: ?>
            <?php foreach (array_slice($pendingWorkers, 0, 2) as $row): ?>
              <div class="admin-queue-item">
                <span><strong>Gig Worker</strong> · <?php echo htmlspecialchars((string)$row['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="?tab=workers">Proses →</a>
              </div>
            <?php endforeach; ?>
            <?php foreach (array_slice($pendingProfileEdits, 0, 2) as $edit): ?>
              <div class="admin-queue-item">
                <span><strong>Edit Profil</strong> · <?php echo htmlspecialchars((string)$edit['worker_username'], ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="?tab=workers">Proses →</a>
              </div>
            <?php endforeach; ?>
            <?php foreach (array_slice($pendingProjects, 0, 2) as $job): ?>
              <div class="admin-queue-item">
                <span><strong>Lowongan</strong> · <?php echo htmlspecialchars((string)$job['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="?tab=projects">Proses →</a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </article>
      </div>

      <section class="admin-region-card">
        <div class="admin-region-head">
          <div>
            <h3>Rekap Pengajuan per Klaster</h3>
            <p>Ringkasan pendaftaran pemberi kerja (per industri) dan lowongan proyek (per lokasi) dari database KarirHub Gig.</p>
          </div>
          <div class="admin-toggle" aria-hidden="true">
            <span class="on">Pemberi Kerja</span>
            <span>Lowongan</span>
          </div>
        </div>
        <?php if ($clusters === []): ?>
          <p style="font-size:0.86rem;color:#64748b;">Belum ada data klaster untuk ditampilkan.</p>
        <?php else: ?>
          <table class="admin-region-table">
            <thead>
              <tr>
                <th>Klaster</th>
                <th>Pemberi Kerja</th>
                <th>Lowongan Proyek</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($clusters as $cluster): ?>
                <tr>
                  <td><?php echo htmlspecialchars((string)$cluster['label'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo (int)$cluster['employers']; ?></td>
                  <td><?php echo (int)$cluster['vacancies']; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <?php if ($tab === 'workers'): ?>
      <?php
        $workerRevisionRegistrations = array_values(array_filter($workers, static fn($r) => ($r['status'] ?? '') === 'revision'));
        $workerStateCounts = [
            'all' => count($workers),
            'pending' => count(array_filter($workers, static fn($r) => ($r['status'] ?? 'pending') === 'pending')),
            'revision' => count($pendingProfileEdits) + count($workerRevisionRegistrations),
            'approved' => count(array_filter($workers, static fn($r) => ($r['status'] ?? '') === 'approved')),
            'rejected' => count(array_filter($workers, static fn($r) => ($r['status'] ?? '') === 'rejected')),
        ];
        $workerRows = $filterByState($workers, $state);
      ?>
      <section class="verify-board">
        <div class="verify-board-head">
          <h2>Verifikasi Gig Worker</h2>
          <div class="verify-status-tabs">
            <a class="<?php echo $state === 'all' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(admin_state_tab_url('workers', 'all', $searchQ), ENT_QUOTES, 'UTF-8'); ?>">Semua <span><?php echo (int)$workerStateCounts['all']; ?></span></a>
            <a class="<?php echo $state === 'pending' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(admin_state_tab_url('workers', 'pending', $searchQ), ENT_QUOTES, 'UTF-8'); ?>">Belum Terverifikasi <span><?php echo (int)$workerStateCounts['pending']; ?></span></a>
            <a class="<?php echo $state === 'revision' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(admin_state_tab_url('workers', 'revision', $searchQ), ENT_QUOTES, 'UTF-8'); ?>">Revisi <span><?php echo (int)$workerStateCounts['revision']; ?></span></a>
            <a class="<?php echo $state === 'approved' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(admin_state_tab_url('workers', 'approved', $searchQ), ENT_QUOTES, 'UTF-8'); ?>">Terverifikasi <span><?php echo (int)$workerStateCounts['approved']; ?></span></a>
            <a class="<?php echo $state === 'rejected' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(admin_state_tab_url('workers', 'rejected', $searchQ), ENT_QUOTES, 'UTF-8'); ?>">Ditolak <span><?php echo (int)$workerStateCounts['rejected']; ?></span></a>
          </div>
        </div>

        <div class="verify-table-wrap">
          <?php if ($state === 'revision'): ?>
            <?php if ($workerRevisionRegistrations !== []): ?>
              <div style="padding:10px 14px;background:#fff7ed;border-left:4px solid #f97316;margin-bottom:12px;border-radius:6px;font-size:0.85rem;color:#9a3412;">
                <strong>Pendaftaran Worker Perlu Revisi</strong> &mdash; Berkas atau data pendaftaran awal yang dikembalikan ke worker untuk perbaikan.
              </div>
              <table class="verify-table is-scrollable" style="margin-bottom:24px;">
                <thead>
                  <tr>
                    <th class="verify-col-name">Gig Worker</th>
                    <th class="verify-col-email">Email</th>
                    <th class="verify-col-phone">No. Telepon</th>
                    <th class="verify-col-field">Bidang Keahlian</th>
                    <th class="verify-col-status">Status</th>
                    <th class="verify-col-date">Catatan Revisi</th>
                    <th class="verify-col-action verify-sticky-action">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($workerRevisionRegistrations as $row): ?>
                    <?php
                      $rowKey = gig_worker_profile_edit_key((string)($row['username'] ?? ''));
                      $rowPendingEdits = $pendingEditsByWorkerKey[$rowKey] ?? [];
                      $firstPendingEdit = $rowPendingEdits[0] ?? null;
                      $adminNote = trim((string)($row['admin_note'] ?? ''));
                    ?>
                    <tr>
                      <td class="verify-col-name">
                        <a class="verify-main-text" href="admin-worker-detail.php?u=<?php echo urlencode((string)$row['username']); ?>&email=<?php echo urlencode((string)$row['contact_email']); ?><?php echo $firstPendingEdit ? '&edit_id=' . (int)$firstPendingEdit['id'] : ''; ?>" style="text-decoration:none;color:inherit;">
                          <?php echo htmlspecialchars((string)$row['username'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                      </td>
                      <td class="verify-col-email"><span class="verify-cell-ellipsis" title="<?php echo htmlspecialchars((string)$row['contact_email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$row['contact_email'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                      <td class="verify-col-phone"><?php echo htmlspecialchars((string)$row['contact_wa'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="verify-col-field"><span class="verify-cell-ellipsis" title="<?php echo htmlspecialchars((string)$row['bidang_keahlian'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$row['bidang_keahlian'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                      <td class="verify-col-status"><?php echo admin_worker_status_badge($row); ?></td>
                      <td class="verify-col-date"><span class="verify-cell-ellipsis" style="max-width:220px;font-size:0.78rem;color:#b45309;" title="<?php echo htmlspecialchars($adminNote, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($adminNote !== '' ? $adminNote : 'Perlu perbaikan data/berkas.', ENT_QUOTES, 'UTF-8'); ?></span></td>
                      <td class="verify-col-action verify-sticky-action">
                        <a class="verify-open-link" href="admin-worker-detail.php?u=<?php echo urlencode((string)$row['username']); ?>&email=<?php echo urlencode((string)$row['contact_email']); ?><?php echo $firstPendingEdit ? '&edit_id=' . (int)$firstPendingEdit['id'] : ''; ?>">Lihat Detail</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>

            <?php if ($pendingProfileEdits !== []): ?>
              <div style="padding:10px 14px;background:#eff6ff;border-left:4px solid #3b82f6;margin-bottom:12px;border-radius:6px;font-size:0.85rem;color:#1e40af;">
                <strong>Pengajuan Perubahan Profil Worker</strong> &mdash; Permintaan pembaruan data dari worker yang sudah terdaftar.
              </div>
              <table class="verify-table is-scrollable">
                <thead>
                  <tr>
                    <th>Gig Worker</th>
                    <th>Bidang Diubah</th>
                    <th>Alasan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pendingProfileEdits as $edit): ?>
                    <tr>
                      <td>
                        <div class="verify-main-text"><?php echo htmlspecialchars((string)$edit['worker_username'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="verify-sub-text"><?php echo htmlspecialchars((string)$edit['worker_email'], ENT_QUOTES, 'UTF-8'); ?></div>
                      </td>
                      <td><?php echo htmlspecialchars((string)$edit['edited_field'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string)$edit['reason_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo admin_status_badge((string)($edit['status'] ?? 'pending')); ?></td>
                      <td>
                        <a class="verify-open-link" href="admin-worker-detail.php?u=<?php echo urlencode((string)$edit['worker_username']); ?>&email=<?php echo urlencode((string)$edit['worker_email']); ?>&edit_id=<?php echo (int)$edit['id']; ?>">Lihat Detail</a>
                        <button type="button" class="verify-open-link" style="margin-left:4px;background:#0ea5e9;color:#fff;border:none;cursor:pointer;" onclick="openAdminDecisionModal({entityType:'worker', entityName:'edit profil <?php echo htmlspecialchars((string)$edit['worker_username'], ENT_QUOTES, 'UTF-8'); ?>', editId:<?php echo (int)$edit['id']; ?>, action:'worker_edit_decision', tab:'workers'})">Ambil Keputusan</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>

            <?php if ($workerRevisionRegistrations === [] && $pendingProfileEdits === []): ?>
              <table class="verify-table">
                <tbody>
                  <tr><td colspan="5"><div class="empty-state">Tidak ada pengajuan revisi worker.</div></td></tr>
                </tbody>
              </table>
            <?php endif; ?>
          <?php else: ?>
            <table class="verify-table is-scrollable">
              <thead>
                <tr>
                  <th class="verify-col-name">Gig Worker</th>
                  <th class="verify-col-email">Email</th>
                  <th class="verify-col-phone">No. Telepon</th>
                  <th class="verify-col-field">Bidang Keahlian</th>
                  <th class="verify-col-status">Status</th>
                  <th class="verify-col-date">Tanggal Daftar</th>
                  <th class="verify-col-action verify-sticky-action">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($workerRows === []): ?>
                  <tr><td colspan="7"><div class="empty-state">Belum ada data Gig Worker<?php echo $searchQ !== '' ? ' yang cocok dengan pencarian.' : '.'; ?></div></td></tr>
                <?php endif; ?>
                <?php foreach ($workerRows as $row): ?>
                  <?php
                    $rowKey = gig_worker_profile_edit_key((string)($row['username'] ?? ''));
                    $rowPendingEdits = $pendingEditsByWorkerKey[$rowKey] ?? [];
                    $firstPendingEdit = $rowPendingEdits[0] ?? null;
                    $createdAt = trim((string)($row['created_at'] ?? ''));
                    $createdLabel = $createdAt !== '' ? date('d M Y', strtotime($createdAt)) : '-';
                  ?>
                  <tr>
                    <td class="verify-col-name">
                      <a class="verify-main-text" href="admin-worker-detail.php?u=<?php echo urlencode((string)$row['username']); ?>&email=<?php echo urlencode((string)$row['contact_email']); ?><?php echo $firstPendingEdit ? '&edit_id=' . (int)$firstPendingEdit['id'] : ''; ?>" style="text-decoration:none;color:inherit;">
                        <?php echo htmlspecialchars((string)$row['username'], ENT_QUOTES, 'UTF-8'); ?>
                      </a>
                      <?php if ($rowPendingEdits !== []): ?>
                        <div class="verify-sub-text" style="margin-top:4px;">
                          <span class="verify-edit-chip">Permintaan Edit Profil: <?php echo count($rowPendingEdits); ?></span>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="verify-col-email"><span class="verify-cell-ellipsis" title="<?php echo htmlspecialchars((string)$row['contact_email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$row['contact_email'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td class="verify-col-phone"><?php echo htmlspecialchars((string)$row['contact_wa'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="verify-col-field"><span class="verify-cell-ellipsis" title="<?php echo htmlspecialchars((string)$row['bidang_keahlian'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$row['bidang_keahlian'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td class="verify-col-status"><?php echo admin_worker_status_badge($row); ?></td>
                    <td class="verify-col-date"><?php echo htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="verify-col-action verify-sticky-action">
                      <a class="verify-open-link" href="admin-worker-detail.php?u=<?php echo urlencode((string)$row['username']); ?>&email=<?php echo urlencode((string)$row['contact_email']); ?><?php echo $firstPendingEdit ? '&edit_id=' . (int)$firstPendingEdit['id'] : ''; ?>">Lihat Detail</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($tab === 'projects'): ?>
      <?php
        $entityFilter = strtolower(trim((string)($_GET['entity'] ?? 'all')));
        if (!in_array($entityFilter, ['all', 'perusahaan', 'individual', 'gig_worker'], true)) {
            $entityFilter = 'all';
        }

        $getEntityType = static function (array $v): string {
            $vacancyType = strtolower((string)($v['vacancy_type'] ?? ''));
            $entityType  = strtolower((string)($v['entity_type'] ?? ''));

            if ($vacancyType === '') {
                $id = strtolower((string)($v['id'] ?? ''));
                $vacancyType = str_starts_with($id, 'job-') ? 'job' : 'project';
            }

            if ($entityType === '') {
                $emp = strtolower((string)($v['employer'] ?? ''));
                if (preg_match('/(pt|cv|inc|corp|ltd|tbk|group|bumn|analytics|solusi|media|infrastruktur|talenta|mutualplus|indo hr|yayasan)/i', $emp)) {
                    $entityType = 'perusahaan';
                } elseif ($emp !== '') {
                    $entityType = 'individual';
                } else {
                    $entityType = 'perusahaan';
                }
            }

            // User classification rules:
            // Semua : all job and project vacancies
            // Perusahaan: JOB Vacancies from companies
            // Individual: JOB Vacancies from individuals
            // Gig Workers: PROJECT Vacancies from companies
            if ($vacancyType === 'job') {
                if ($entityType === 'individual') {
                    return 'individual';
                }
                return 'perusahaan';
            }

            return 'gig_worker';
        };

        // Filter vacancies matching search query first
        $vacanciesSearchFiltered = array_values(array_filter($vacancies, static function (array $v) use ($searchQ): bool {
            if ($searchQ === '') return true;
            $haystack = strtolower(($v['title'] ?? '') . ' ' . ($v['employer'] ?? '') . ' ' . ($v['desc'] ?? ''));
            return str_contains($haystack, strtolower($searchQ));
        }));

        // Filter search set by active entity pill selection to calculate status tab counts
        $vacanciesEntityFiltered = array_values(array_filter($vacanciesSearchFiltered, static function (array $v) use ($entityFilter, $getEntityType): bool {
            if ($entityFilter === 'all') return true;
            return $getEntityType($v) === $entityFilter;
        }));

        // Counts for top status tabs under active entity pill selection
        $projectStateCounts = [
            'all' => count($vacanciesEntityFiltered),
            'pending' => count(array_filter($vacanciesEntityFiltered, static fn($v) => (string)($v['status'] ?? '') === 'review')),
            'revision' => count(array_filter($vacanciesEntityFiltered, static fn($v) => (string)($v['status'] ?? '') === 'revision')),
            'approved' => count(array_filter($vacanciesEntityFiltered, static fn($v) => in_array((string)($v['status'] ?? ''), ['active', 'approved'], true))),
            'rejected' => count(array_filter($vacanciesEntityFiltered, static fn($v) => (string)($v['status'] ?? '') === 'rejected')),
        ];

        // Filter search set by active status state to calculate entity section pill counts
        $vacanciesStateFiltered = array_values(array_filter($vacanciesSearchFiltered, static function (array $v) use ($state): bool {
            $st = (string)($v['status'] ?? '');
            if ($state === 'all') return true;
            if ($state === 'pending') return $st === 'review';
            if ($state === 'approved') return in_array($st, ['active', 'approved'], true);
            return $st === $state;
        }));

        // Entity section counts under current active status state
        $entityCounts = [
            'all' => count($vacanciesStateFiltered),
            'perusahaan' => count(array_filter($vacanciesStateFiltered, static fn($v) => $getEntityType($v) === 'perusahaan')),
            'individual' => count(array_filter($vacanciesStateFiltered, static fn($v) => $getEntityType($v) === 'individual')),
            'gig_worker' => count(array_filter($vacanciesStateFiltered, static fn($v) => $getEntityType($v) === 'gig_worker')),
        ];

        // Final filtered rows matching both active status state and active entity filter
        $projectRows = array_values(array_filter($vacanciesStateFiltered, static function (array $v) use ($entityFilter, $getEntityType): bool {
            if ($entityFilter === 'all') return true;
            return $getEntityType($v) === $entityFilter;
        }));
      ?>
      <section class="verify-board project-verify-board">
        <div class="verify-board-head">
          <h2>Verifikasi Lowongan</h2>
          <div class="verify-status-tabs project-status-tabs">
            <a class="<?php echo $state === 'all' ? 'active' : ''; ?>" data-state-filter="all" href="<?php echo htmlspecialchars(admin_state_tab_url('projects', 'all', $searchQ, $entityFilter), ENT_QUOTES, 'UTF-8'); ?>">Semua <span><?php echo (int)$projectStateCounts['all']; ?></span></a>
            <a class="<?php echo $state === 'pending' ? 'active' : ''; ?>" data-state-filter="pending" href="<?php echo htmlspecialchars(admin_state_tab_url('projects', 'pending', $searchQ, $entityFilter), ENT_QUOTES, 'UTF-8'); ?>">Menunggu Verifikasi <span><?php echo (int)$projectStateCounts['pending']; ?></span></a>
            <a class="<?php echo $state === 'revision' ? 'active' : ''; ?>" data-state-filter="revision" href="<?php echo htmlspecialchars(admin_state_tab_url('projects', 'revision', $searchQ, $entityFilter), ENT_QUOTES, 'UTF-8'); ?>">Revisi <span><?php echo (int)$projectStateCounts['revision']; ?></span></a>
            <a class="<?php echo $state === 'approved' ? 'active' : ''; ?>" data-state-filter="approved" href="<?php echo htmlspecialchars(admin_state_tab_url('projects', 'approved', $searchQ, $entityFilter), ENT_QUOTES, 'UTF-8'); ?>">Disetujui <span><?php echo (int)$projectStateCounts['approved']; ?></span></a>
            <a class="<?php echo $state === 'rejected' ? 'active' : ''; ?>" data-state-filter="rejected" href="<?php echo htmlspecialchars(admin_state_tab_url('projects', 'rejected', $searchQ, $entityFilter), ENT_QUOTES, 'UTF-8'); ?>">Ditolak <span><?php echo (int)$projectStateCounts['rejected']; ?></span></a>
          </div>
        </div>

        <div class="project-toolbar" style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap;">
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;flex:1;">
            <form method="get" class="project-search-form" style="margin:0;" onsubmit="return false;">
              <input type="hidden" name="tab" value="projects">
              <input type="hidden" name="state" value="<?php echo htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="entity" value="<?php echo htmlspecialchars($entityFilter, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="text" name="q" value="<?php echo htmlspecialchars($searchQ, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari lowongan..." />
            </form>

            <div class="entity-pills-group">
              <a class="entity-pill <?php echo $entityFilter === 'all' ? 'active' : ''; ?>" data-entity-filter="all" href="<?php echo htmlspecialchars(admin_state_tab_url('projects', $state, $searchQ, 'all'), ENT_QUOTES, 'UTF-8'); ?>">
                Semua <span class="pill-badge"><?php echo (int)$entityCounts['all']; ?></span>
              </a>
              <a class="entity-pill <?php echo $entityFilter === 'perusahaan' ? 'active' : ''; ?>" data-entity-filter="perusahaan" href="<?php echo htmlspecialchars(admin_state_tab_url('projects', $state, $searchQ, 'perusahaan'), ENT_QUOTES, 'UTF-8'); ?>">
                Perusahaan <span class="pill-badge"><?php echo (int)$entityCounts['perusahaan']; ?></span>
              </a>
              <a class="entity-pill <?php echo $entityFilter === 'individual' ? 'active' : ''; ?>" data-entity-filter="individual" href="<?php echo htmlspecialchars(admin_state_tab_url('projects', $state, $searchQ, 'individual'), ENT_QUOTES, 'UTF-8'); ?>">
                Individual <span class="pill-badge"><?php echo (int)$entityCounts['individual']; ?></span>
              </a>
              <a class="entity-pill <?php echo $entityFilter === 'gig_worker' ? 'active' : ''; ?>" data-entity-filter="gig_worker" href="<?php echo htmlspecialchars(admin_state_tab_url('projects', $state, $searchQ, 'gig_worker'), ENT_QUOTES, 'UTF-8'); ?>">
                Gig Workers <span class="pill-badge"><?php echo (int)$entityCounts['gig_worker']; ?></span>
              </a>
            </div>
          </div>

          <a class="project-filter-btn" href="<?php echo htmlspecialchars(admin_state_tab_url('projects', $state, '', 'all'), ENT_QUOTES, 'UTF-8'); ?>">Filter</a>
        </div>

        <div class="verify-table-wrap">
          <table class="verify-table is-scrollable project-verify-table">
            <thead>
              <tr>
                <th class="project-col-title">Judul Lowongan</th>
                <th class="project-col-entity">Jenis Entitas</th>
                <th class="project-col-status">Status</th>
                <th class="project-col-deadline">Deadline Verifikasi</th>
                <th class="project-col-blacklist">Blacklist</th>
                <th class="project-col-date">Tanggal Pengajuan</th>
                <th class="project-col-action verify-sticky-action">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $hasVisible = false;
                foreach ($vacancies as $job):
                  $submittedTs = strtotime((string)($job['posted'] ?? ''));
                  if (!$submittedTs) {
                      $submittedTs = time();
                  }
                  $verifyDeadlineTs = strtotime('+3 days', $submittedTs);
                  $remainingDays = (int)ceil(($verifyDeadlineTs - time()) / 86400);
                  $deadlineLabel = $remainingDays > 0 ? $remainingDays . ' hari lagi' : 'Hari ini';
                  if ($remainingDays < 0) {
                      $deadlineLabel = 'Lewat tenggat';
                  }
                  $statusKey = (string)($job['status'] ?? 'review');
                  $statusText = (string)($job['statusLabel'] ?? 'Menunggu Verifikasi');
                  $statusClass = 'is-blue';
                  if ($statusKey === 'revision') {
                      $statusClass = 'is-amber';
                      $deadlineLabel = '-';
                  } elseif ($statusKey === 'active' || $statusKey === 'approved') {
                      $statusClass = 'is-green';
                      $statusText = 'Disetujui';
                      $deadlineLabel = '-';
                  } elseif ($statusKey === 'rejected') {
                      $statusClass = 'is-red';
                      $deadlineLabel = '-';
                  }

                  $entityKey = $getEntityType($job);
                  $entityLabel = match($entityKey) {
                      'individual' => 'Individual',
                      'gig_worker' => 'Gig Worker',
                      default => 'Perusahaan',
                  };

                  $matchState = ($state === 'all') ||
                                ($state === 'pending' && $statusKey === 'review') ||
                                ($state === 'approved' && in_array($statusKey, ['active', 'approved'], true)) ||
                                ($state === $statusKey);

                  $matchEntity = ($entityFilter === 'all') || ($entityKey === $entityFilter);

                  $matchSearch = true;
                  if ($searchQ !== '') {
                      $haystack = strtolower(($job['title'] ?? '') . ' ' . ($job['employer'] ?? '') . ' ' . ($job['desc'] ?? ''));
                      $matchSearch = str_contains($haystack, strtolower($searchQ));
                  }

                  $isVisible = $matchState && $matchEntity && $matchSearch;
                  if ($isVisible) {
                      $hasVisible = true;
                  }
                ?>
                <tr data-status="<?php echo htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>"
                    data-entity="<?php echo htmlspecialchars($entityKey, ENT_QUOTES, 'UTF-8'); ?>"
                    data-search="<?php echo htmlspecialchars(strtolower(($job['title'] ?? '') . ' ' . ($job['employer'] ?? '') . ' ' . ($job['desc'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                    style="<?php echo $isVisible ? '' : 'display:none;'; ?>">
                  <td class="project-col-title">
                    <div class="project-title-wrap">
                      <div class="project-title-avatar"><?php echo htmlspecialchars(strtoupper(substr((string)$job['title'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
                      <div>
                        <div class="verify-main-text"><?php echo htmlspecialchars((string)$job['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="verify-sub-text"><?php echo htmlspecialchars((string)($job['employer'] ?? 'Perusahaan'), ENT_QUOTES, 'UTF-8'); ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="project-col-entity"><?php echo htmlspecialchars($entityLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="project-col-status"><span class="project-status-chip <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8'); ?></span></td>
                  <td class="project-col-deadline"><?php echo $deadlineLabel === '-' ? '-' : '<span class="project-deadline-chip">' . htmlspecialchars($deadlineLabel, ENT_QUOTES, 'UTF-8') . '</span>'; ?></td>
                  <td class="project-col-blacklist"><span class="project-safe-chip">Aman</span></td>
                  <td class="project-col-date"><?php echo htmlspecialchars(date('d M Y, H:i', $submittedTs), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="project-col-action verify-sticky-action">
                    <a class="verify-open-link" href="admin-project-detail.php?id=<?php echo urlencode((string)$job['id']); ?>">Lihat Detail</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr class="empty-row-js" style="<?php echo $hasVisible ? 'display:none;' : ''; ?>">
                <td colspan="7"><div class="empty-state">Tidak ada lowongan pada kategori/status ini.</div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <script>
      document.addEventListener('DOMContentLoaded', function() {
        const statusTabs = document.querySelectorAll('.project-status-tabs a');
        const entityPills = document.querySelectorAll('.entity-pills-group a');
        const searchInput = document.querySelector('.project-search-form input[name="q"]');
        const tableRows = document.querySelectorAll('.project-verify-table tbody tr[data-status]');
        const emptyRow = document.querySelector('.project-verify-table tbody tr.empty-row-js');

        let currentState = '<?php echo htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?>';
        let currentEntity = '<?php echo htmlspecialchars($entityFilter, ENT_QUOTES, 'UTF-8'); ?>';

        function filterVacancies() {
          const searchQ = searchInput ? searchInput.value.toLowerCase().trim() : '';
          let visibleCount = 0;

          const stateCounts = { all: 0, pending: 0, revision: 0, approved: 0, rejected: 0 };
          const entityCounts = { all: 0, perusahaan: 0, individual: 0, gig_worker: 0 };

          tableRows.forEach(row => {
            const st = row.getAttribute('data-status');
            const ent = row.getAttribute('data-entity');
            const haystack = row.getAttribute('data-search') || '';

            const matchSearch = searchQ === '' || haystack.includes(searchQ);
            if (!matchSearch) {
              row.style.display = 'none';
              return;
            }

            const matchState = (currentState === 'all') ||
                               (currentState === 'pending' && st === 'review') ||
                               (currentState === 'approved' && (st === 'active' || st === 'approved')) ||
                               (currentState === st);

            const matchEntity = (currentEntity === 'all') || (currentEntity === ent);

            if (matchEntity) {
              stateCounts.all++;
              if (st === 'review') stateCounts.pending++;
              if (st === 'revision') stateCounts.revision++;
              if (st === 'active' || st === 'approved') stateCounts.approved++;
              if (st === 'rejected') stateCounts.rejected++;
            }

            if (matchState) {
              entityCounts.all++;
              if (ent === 'perusahaan') entityCounts.perusahaan++;
              if (ent === 'individual') entityCounts.individual++;
              if (ent === 'gig_worker') entityCounts.gig_worker++;
            }

            if (matchState && matchEntity) {
              row.style.display = '';
              visibleCount++;
            } else {
              row.style.display = 'none';
            }
          });

          statusTabs.forEach(tab => {
            const tabState = tab.getAttribute('data-state-filter') || 'all';
            const badge = tab.querySelector('span');
            if (badge && stateCounts[tabState] !== undefined) {
              badge.textContent = stateCounts[tabState];
            }
            if (tabState === currentState) {
              tab.classList.add('active');
            } else {
              tab.classList.remove('active');
            }
          });

          entityPills.forEach(pill => {
            const pillEntity = pill.getAttribute('data-entity-filter') || 'all';
            const badge = pill.querySelector('.pill-badge');
            if (badge && entityCounts[pillEntity] !== undefined) {
              badge.textContent = entityCounts[pillEntity];
            }

            if (pillEntity === currentEntity) {
              pill.classList.add('active');
            } else {
              pill.classList.remove('active');
            }
          });

          if (emptyRow) {
            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
          }

          const url = new URL(window.location.href);
          url.searchParams.set('tab', 'projects');
          url.searchParams.set('state', currentState);
          url.searchParams.set('entity', currentEntity);
          if (searchQ) {
            url.searchParams.set('q', searchQ);
          } else {
            url.searchParams.delete('q');
          }
          window.history.pushState(null, '', url.toString());
        }

        statusTabs.forEach(tab => {
          tab.addEventListener('click', function(e) {
            e.preventDefault();
            currentState = this.getAttribute('data-state-filter') || 'all';
            filterVacancies();
          });
        });

        entityPills.forEach(pill => {
          pill.addEventListener('click', function(e) {
            e.preventDefault();
            currentEntity = this.getAttribute('data-entity-filter') || 'all';
            filterVacancies();
          });
        });

        if (searchInput) {
          searchInput.addEventListener('input', filterVacancies);
        }
      });
      </script>
    <?php endif; ?>

<?php require __DIR__ . '/includes/admin-layout-end.php'; ?>
