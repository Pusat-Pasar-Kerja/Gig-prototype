<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/employer-auth.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-applications.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/project-schedule.php';

$allApps = gig_get_all_applications();
$appMapByWorker = [];
foreach ($allApps as $ap) {
    $wKey = strtolower(trim((string)$ap['worker_id']));
    $appMapByWorker[$wKey] = $ap;
}

$rawWorkerProfiles = gig_worker_profiles();
$workerProfiles = array_filter($rawWorkerProfiles, function($w) use ($appMapByWorker) {
    $wKey = strtolower(trim((string)$w['id']));
    $appData = $appMapByWorker[$wKey] ?? null;
    $status = $appData['status'] ?? 'applied';
    return !gig_is_hired_status($status);
});

$vacancies = gig_project_vacancies();
$vacancyTitleMap = [
    'GIG-2026-09-001' => 'Redesign UI/UX Dashboard Prototype KarirHub',
    'GIG-2026-09-002' => 'Integrasi REST API Modul Notifikasi SMS & WhatsApp',
    'GIG-2026-09-003' => 'Social Media Campaign & Copywriting Manager',
    'GIG-2026-09-005' => 'Desain UI/UX Mobile App E-Commerce UMKM',
    'GIG-2026-09-012' => 'Audit Design System & Aksesibilitas Web Portal',
];
foreach ($vacancies as $v) {
    if (!empty($v['id']) && !empty($v['title'])) {
        $vacancyTitleMap[(string)$v['id']] = (string)$v['title'];
    }
}
$p1 = gig_demo_active_project_by_id('GIG-2026-09-001');
$p2 = gig_demo_active_project_by_id('GIG-2026-09-002');

$completedContracts = [];
$pdo = gig_db();
if ($pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT `contract_id` FROM `project_completions` WHERE `employer_username` = :emp");
        $stmt->execute([':emp' => $username]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $cid) {
            $completedContracts[(string)$cid] = true;
        }
    } catch (Throwable $ignored) {
    }
}
if (isset($_SESSION['completed_projects']) && is_array($_SESSION['completed_projects'])) {
    foreach (array_keys($_SESSION['completed_projects']) as $cid) {
        $completedContracts[(string)$cid] = true;
    }
}

$activeProjects = [];
if (is_array($p1)) {
    $p1Contract = (string)($p1['contract_id'] ?? '');
    if ($p1Contract === '' || !isset($completedContracts[$p1Contract])) {
        $activeProjects[] = $p1;
    }
}
if (is_array($p2)) {
    $p2Contract = (string)($p2['contract_id'] ?? '');
    if ($p2Contract === '' || !isset($completedContracts[$p2Contract])) {
        $activeProjects[] = $p2;
    }
}
usort($activeProjects, static fn($a, $b) => strcmp((string)($a['deadline_iso'] ?? ''), (string)($b['deadline_iso'] ?? '')));

$soonest = $activeProjects[0] ?? null;
$otherActive = array_slice($activeProjects, 1);
$pageTitle = 'Ringkasan';
$pageKey = 'overview';
$breadcrumbCurrent = 'Ringkasan';
require __DIR__ . '/includes/employer-layout-start.php';
?>

    <div class="page-toolbar">
      <h1>Ringkasan</h1>
    </div>

    <section class="hero-banner">
      <div class="hero-header">
        <div>
          <div class="hero-badge">PORTAL PEMBERI KERJA</div>
          <h2 class="hero-title">Halo, <a href="employer-profile.php" style="color:inherit;text-decoration:none;" title="Lihat Profil Perusahaan"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></a></h2>
          <p class="hero-desc">
            Pantau lowongan, pelamar, dan proyek berjalan dari satu tempat.
          </p>
        </div>
        <div class="hero-quick-stats">
          <div class="hero-stat-pill"><span class="hero-stat-val"><?php echo count($vacancies); ?></span><span class="hero-stat-lbl">Lowongan Terdaftar</span></div>
          <div class="hero-stat-pill"><span class="hero-stat-val"><?php echo count($workerProfiles); ?></span><span class="hero-stat-lbl">Pelamar Masuk</span></div>
          <div class="hero-stat-pill"><span class="hero-stat-val">2</span><span class="hero-stat-lbl">Proyek Berjalan</span></div>
        </div>
      </div>
    </section>

    <section class="stats-grid">
      <a class="stat-card" href="employer-lowongan.php">
        <div class="stat-card-header"><span class="stat-label">LOWONGAN PROYEK</span></div>
        <div class="stat-number"><?php echo count($vacancies); ?></div>
        <div class="stat-caption"><span class="stat-trend-positive">2 tayang aktif</span> · 1 verifikasi · 1 revisi</div>
      </a>
      <a class="stat-card" href="employer-cari-mitra.php">
        <div class="stat-card-header"><span class="stat-label">CARI MITRA GIG</span></div>
        <div class="stat-number"><?php echo count($workerProfiles); ?></div>
        <div class="stat-caption"><span class="stat-trend-positive">Tersedia siap kerja</span> di bursa</div>
      </a>
      <a class="stat-card" href="employer-proyek-aktif.php">
        <div class="stat-card-header"><span class="stat-label">PROYEK AKTIF</span></div>
        <div class="stat-number">2</div>
        <div class="stat-caption">Sedang dikerjakan mitra gig</div>
      </a>
      <a class="stat-card" href="employer-riwayat-proyek.php">
        <div class="stat-card-header"><span class="stat-label">RIWAYAT PROYEK</span></div>
        <div class="stat-number">3</div>
        <div class="stat-caption">Kontrak selesai &amp; tidak dilanjutkan</div>
      </a>
    </section>

    <div class="overview-dual-grid">
      <section class="white-card">
        <div class="card-header-flex">
          <div class="card-title-group">
            <h2>Countdown Proyek Aktif</h2>
            <p>Proyek dengan sisa waktu pengerjaan terdekat</p>
          </div>
          <a class="btn-action-sm" href="employer-proyek-aktif.php">Lihat Semua Proyek</a>
        </div>

        <?php if ($soonest): ?>
        <div style="margin-top:14px;padding:14px;background:linear-gradient(135deg, #eff6ff, #f0fdf4);border:1px solid #bfdbfe;border-radius:12px;position:relative;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-size:0.7rem;font-weight:800;background:#ef4444;color:#fff;padding:3px 8px;border-radius:9999px;letter-spacing:0.5px;">🔥 TENGGAT TERDEKAT</span>
            <span style="font-size:0.75rem;color:#1e40af;font-weight:700;">Tenggat: <?php echo htmlspecialchars($soonest['deadline'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>

          <h3 style="font-size:0.95rem;font-weight:800;color:#1e293b;margin:0 0 4px 0;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span><?php echo htmlspecialchars($soonest['title'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php if (gig_is_remote_work($soonest)): ?>
              <span style="font-size:0.68rem;font-weight:700;color:#1d4ed8;background:#eff6ff;border:1px solid #bfdbfe;padding:2px 7px;border-radius:6px;">🌐 Remote</span>
            <?php else: ?>
              <span style="font-size:0.68rem;font-weight:700;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;padding:2px 7px;border-radius:6px;">🏢 On-site</span>
            <?php endif; ?>
          </h3>
          <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:12px;">Mitra Gig: <strong><?php echo htmlspecialchars($soonest['worker_name'], ENT_QUOTES, 'UTF-8'); ?></strong> · <?php echo htmlspecialchars($soonest['worker_role'], ENT_QUOTES, 'UTF-8'); ?> · Mulai <?php echo htmlspecialchars($soonest['hired_label'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($soonest['duration'], ENT_QUOTES, 'UTF-8'); ?>)</div>

          <div style="display:flex;gap:8px;text-align:center;" class="js-project-countdown" data-deadline="<?php echo htmlspecialchars($soonest['deadline_iso'], ENT_QUOTES, 'UTF-8'); ?>" id="dash-shortest-countdown">
            <div style="background:#fff;border:1px solid #93c5fd;padding:6px 10px;border-radius:8px;flex:1;">
              <span class="c-days" style="font-size:1.2rem;font-weight:800;color:#1e40af;display:block;"><?php echo (int)$soonest['days_left']; ?></span>
              <span style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Hari</span>
            </div>
            <div style="background:#fff;border:1px solid #93c5fd;padding:6px 10px;border-radius:8px;flex:1;">
              <span class="c-hours" style="font-size:1.2rem;font-weight:800;color:#1e40af;display:block;"><?php echo sprintf('%02d', (int)$soonest['hours_left']); ?></span>
              <span style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Jam</span>
            </div>
            <div style="background:#fff;border:1px solid #93c5fd;padding:6px 10px;border-radius:8px;flex:1;">
              <span class="c-mins" style="font-size:1.2rem;font-weight:800;color:#1e40af;display:block;"><?php echo sprintf('%02d', (int)$soonest['mins_left']); ?></span>
              <span style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Menit</span>
            </div>
            <div style="background:#fff;border:1px solid #93c5fd;padding:6px 10px;border-radius:8px;flex:1;">
              <span class="c-secs" style="font-size:1.2rem;font-weight:800;color:#ef4444;display:block;"><?php echo sprintf('%02d', (int)$soonest['secs_left']); ?></span>
              <span style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Detik</span>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div style="margin-top:14px;padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
          <div style="font-size:0.84rem;color:#64748b;">Belum ada proyek aktif yang perlu countdown.</div>
        </div>
        <?php endif; ?>

        <?php foreach ($otherActive as $other): ?>
        <div style="margin-top:12px;padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;display:flex;justify-content:space-between;align-items:center;">
          <div>
            <div style="font-size:0.84rem;font-weight:700;color:var(--text-dark);display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
              <span><?php echo htmlspecialchars($other['title'], ENT_QUOTES, 'UTF-8'); ?></span>
              <?php if (gig_is_remote_work($other)): ?>
                <span style="font-size:0.68rem;font-weight:700;color:#1d4ed8;background:#eff6ff;border:1px solid #bfdbfe;padding:1px 6px;border-radius:6px;">🌐 Remote</span>
              <?php else: ?>
                <span style="font-size:0.68rem;font-weight:700;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;padding:1px 6px;border-radius:6px;">🏢 On-site</span>
              <?php endif; ?>
            </div>
            <div style="font-size:0.74rem;color:var(--text-muted);">Mitra: <?php echo htmlspecialchars($other['worker_name'], ENT_QUOTES, 'UTF-8'); ?> · Mulai <?php echo htmlspecialchars($other['hired_label'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($other['duration'], ENT_QUOTES, 'UTF-8'); ?> · Sisa <?php echo (int)$other['days_left']; ?> hari (Tenggat: <?php echo htmlspecialchars($other['deadline'], ENT_QUOTES, 'UTF-8'); ?>)</div>
          </div>
          <a class="btn-action-sm" href="employer-proyek-aktif.php" style="text-decoration:none;">Pantau →</a>
        </div>
        <?php endforeach; ?>
      </section>

      <section class="white-card">
        <div class="card-header-flex">
          <div class="card-title-group">
            <h2>Pelamar Terbaru</h2>
            <p>Klik untuk membuka detail lowongan</p>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <?php foreach (array_slice($workerProfiles, 0, 3) as $recent): ?>
          <?php
            $wKey = strtolower(trim((string)($recent['id'] ?? '')));
            $appData = $appMapByWorker[$wKey] ?? null;
            $vacId = (string)($appData['vacancy_id'] ?? 'GIG-2026-09-001');
            if ($vacId === '') {
                $vacId = 'GIG-2026-09-001';
            }
            $appliedTitle = $vacancyTitleMap[$vacId] ?? 'Redesign UI/UX Dashboard Prototype KarirHub';
          ?>
          <div class="recent-applicant-row" style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;gap:12px;">
            <div class="recent-applicant-left" style="display:flex;align-items:center;gap:12px;flex:1;min-width:0;">
              <a href="employer-detail-lowongan.php?id=<?php echo urlencode($vacId); ?>" style="text-decoration:none;">
                <div class="recent-avatar" style="background:<?php echo htmlspecialchars($recent['color'], ENT_QUOTES, 'UTF-8'); ?>;display:flex;align-items:center;justify-content:center;color:#ffffff;font-weight:800;font-size:1.05rem;border-radius:50%;width:40px;height:40px;flex-shrink:0;">
                  <?php echo htmlspecialchars(strtoupper(substr((string)$recent['name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                </div>
              </a>
              <div style="min-width:0;flex:1;">
                <a href="employer-detail-lowongan.php?id=<?php echo urlencode($vacId); ?>" style="font-size:0.88rem;font-weight:800;color:#0f172a;text-decoration:none;display:block;">
                  <?php echo htmlspecialchars($recent['name'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <div style="font-size:0.74rem;color:var(--text-muted);"><?php echo htmlspecialchars($recent['title'], ENT_QUOTES, 'UTF-8'); ?> · ★ <?php echo (int)$recent['rating']; ?></div>
                <div style="margin-top:3px;">
                  <a href="employer-detail-lowongan.php?id=<?php echo urlencode($vacId); ?>" style="font-size:0.78rem;color:#2563eb;font-weight:700;text-decoration:underline;display:inline-flex;align-items:center;gap:4px;" title="Buka Detail Lowongan Proyek">
                    📋 Melamar: <?php echo htmlspecialchars($appliedTitle, ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                </div>
              </div>
            </div>
            <a class="btn-action-sm" href="employer-detail-lowongan.php?id=<?php echo urlencode($vacId); ?>" style="text-decoration:none;white-space:nowrap;flex-shrink:0;">Lihat Profil</a>
          </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>

    <section class="white-card" style="margin-top:20px;">
      <div class="card-header-flex">
        <div class="card-title-group">
          <h2>Lowongan Terkini</h2>
          <p>Cuplikan proyek yang sedang Anda kelola (Klik untuk rincian detail)</p>
        </div>
        <a class="btn-action-sm" href="employer-lowongan.php">Kelola Semua Lowongan</a>
      </div>
      <div class="project-grid">
        <?php foreach (array_slice($vacancies, 0, 3) as $proj): ?>
        <article class="project-card">
          <div class="project-top-meta">
            <span class="project-category-tag"><?php echo htmlspecialchars($proj['category'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="badge-status <?php echo $proj['status'] === 'active' ? 'active' : 'review'; ?>"><?php echo htmlspecialchars($proj['statusLabel'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <h3 class="project-card-title">
            <a href="employer-detail-lowongan.php?id=<?php echo urlencode($proj['id']); ?>" style="color:inherit;text-decoration:none;">
              <?php echo htmlspecialchars($proj['title'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </h3>
          <p class="project-card-desc"><?php echo htmlspecialchars($proj['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
          <div class="project-card-footer">
            <span class="applicants-count-badge" style="cursor:default;"><?php echo (int)$proj['applicantsCount']; ?> Pelamar</span>
            <a class="btn-action-sm" href="employer-detail-lowongan.php?id=<?php echo urlencode($proj['id']); ?>">Detail →</a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <script>
      (function startDashboardCountdown() {
        function pad(n) { return n < 10 ? '0' + n : String(n); }
        function tick() {
          document.querySelectorAll('.js-project-countdown').forEach(function(container) {
            const iso = container.getAttribute('data-deadline');
            if (!iso) return;
            const end = new Date(iso).getTime();
            let ms = end - Date.now();
            if (ms <= 0) {
              const fallbackCycleMs = 7 * 86400000;
              ms = fallbackCycleMs + (ms % fallbackCycleMs);
              if (ms <= 0) ms = fallbackCycleMs;
            }
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
            if (d) d.textContent = String(days);
            if (h) h.textContent = pad(hours);
            if (m) m.textContent = pad(mins);
            if (s) s.textContent = pad(secs);
          });
        }
        tick();
        setInterval(tick, 1000);
      })();
    </script>

<?php require __DIR__ . '/includes/employer-layout-end.php'; ?>
