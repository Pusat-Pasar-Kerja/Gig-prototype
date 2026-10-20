<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/worker-auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-vacancies.php';
require_once __DIR__ . '/includes/project-offers.php';
require_once __DIR__ . '/includes/project-schedule.php';
require_once __DIR__ . '/includes/project-history.php';

gig_seed_demo_offers_if_needed($username);
$workerEmail = (string)($_SESSION['siapkerja_email'] ?? '');
$dashStats = gig_worker_dashboard_stats($username, $workerEmail);
$soonestProject = gig_worker_soonest_active_project($username, $workerEmail);

$siapkerja = gig_get_siapkerja_profile($username);
$isRegistered = gig_is_worker_registered($username);
$workerRegData = $isRegistered ? gig_get_worker_registration($username) : null;
$profileId = strtolower(preg_replace('/[^a-z0-9]+/i', '', explode(' ', $username)[0] ?? $username));
$profileUrl = $isRegistered ? 'worker-profile.php?id=' . urlencode($profileId) : 'worker-register.php';

$pageTitle = 'Ringkasan';
$pageKey = 'overview';
$breadcrumbCurrent = 'Ringkasan';
require __DIR__ . '/includes/worker-layout-start.php';
?>

    <div class="page-toolbar">
      <h1>Ringkasan</h1>
    </div>

    <section class="hero-banner">
      <div class="hero-header">
        <div>
          <div class="hero-badge">PORTAL GIG WORKER</div>
          <h2 class="hero-title">Halo, <?php echo htmlspecialchars($siapkerja['nama'] ?? $username, ENT_QUOTES, 'UTF-8'); ?></h2>
          <p class="hero-desc">Pantau proyek, profil, dan aktivitas dari satu tempat.</p>
        </div>
        <div class="hero-quick-stats">
          <div class="hero-stat-pill"><span class="hero-stat-val"><?php echo (int)$dashStats['completed_projects']; ?></span><span class="hero-stat-lbl">Proyek Selesai</span></div>
          <div class="hero-stat-pill"><span class="hero-stat-val"><?php echo (int)$dashStats['active_projects']; ?></span><span class="hero-stat-lbl">Proyek Aktif</span></div>
          <div class="hero-stat-pill"><span class="hero-stat-val"><?php echo (int)$dashStats['active_partners']; ?></span><span class="hero-stat-lbl">Mitra Aktif</span></div>
        </div>
      </div>
    </section>

    <?php if (!$isRegistered): ?>
      <div class="reg-banner warn">
        <div class="reg-banner-left">
          <div class="reg-icon" style="background:var(--primary-blue);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg>
          </div>
          <div>
            <div class="chip" style="margin-bottom:6px;">AKUN SIAPKERJA TERKONEKSI</div>
            <h3 style="font-size:1.05rem;font-weight:800;">Anda belum terdaftar sebagai Gig Worker</h3>
            <p style="font-size:0.86rem;color:var(--text-muted);margin-top:4px;">Lengkapi profil keahlian, portofolio, dan kontak agar pemberi kerja bisa meninjau akun Anda.</p>
          </div>
        </div>
        <a class="btn-primary-add" href="worker-register.php">Daftar Sebagai Gig Worker</a>
      </div>
    <?php endif; ?>

    <section class="stats-grid">
      <a class="stat-card" href="worker-riwayat.php">
        <div class="stat-card-header"><span class="stat-label">PROYEK SELESAI</span></div>
        <div class="stat-number"><?php echo (int)$dashStats['completed_projects']; ?></div>
        <div class="stat-caption">Lihat di Riwayat Proyek</div>
      </a>
      <a class="stat-card" href="worker-tugas.php">
        <div class="stat-card-header"><span class="stat-label">PROYEK AKTIF</span></div>
        <div class="stat-number"><?php echo (int)$dashStats['active_projects']; ?></div>
        <div class="stat-caption">Sedang dikerjakan</div>
      </a>
      <a class="stat-card" href="worker-penawaran.php">
        <div class="stat-card-header"><span class="stat-label">PENAWARAN PROYEK</span></div>
        <div class="stat-number"><?php echo (int)$dashStats['offer_count']; ?></div>
        <div class="stat-caption">Tawaran dari pemberi kerja</div>
      </a>
      <a class="stat-card" href="worker-ulasan.php">
        <div class="stat-card-header"><span class="stat-label">RATING</span></div>
        <div class="stat-number"><?php echo htmlspecialchars(number_format($dashStats['rating'], 1, '.', ''), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="stat-caption">Dari ulasan pemberi kerja</div>
      </a>
    </section>

    <div class="middle-grid">
      <section class="white-card">
        <div class="card-header-flex">
          <div class="card-title-group">
            <h2>Countdown Proyek Aktif</h2>
            <p>Proyek dengan tenggat pengerjaan terdekat</p>
          </div>
          <a class="btn-action-sm" href="worker-tugas.php">Lihat Semua Proyek</a>
        </div>

        <?php if ($soonestProject): ?>
        <div style="margin-top:14px;padding:14px;background:linear-gradient(135deg, #eff6ff, #f0fdf4);border:1px solid #bfdbfe;border-radius:12px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
            <span style="font-size:0.7rem;font-weight:800;background:#ef4444;color:#fff;padding:3px 8px;border-radius:9999px;letter-spacing:0.5px;">🔥 TENGGAT TERDEKAT</span>
            <span style="font-size:0.75rem;color:#1e40af;font-weight:700;">Tenggat: <?php echo htmlspecialchars($soonestProject['deadline'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <h3 style="font-size:0.95rem;font-weight:800;color:#1e293b;margin:0 0 4px 0;"><?php echo htmlspecialchars($soonestProject['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:12px;">Pemberi Kerja: <strong><?php echo htmlspecialchars($soonestProject['employer'], ENT_QUOTES, 'UTF-8'); ?></strong> · Mulai <?php echo htmlspecialchars($soonestProject['hired_label'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($soonestProject['duration'], ENT_QUOTES, 'UTF-8'); ?>)</div>
          <div style="display:flex;gap:8px;text-align:center;" class="js-project-countdown" data-deadline="<?php echo htmlspecialchars($soonestProject['deadline_iso'], ENT_QUOTES, 'UTF-8'); ?>" id="dash-worker-shortest-countdown">
            <div style="background:#fff;border:1px solid #93c5fd;padding:6px 10px;border-radius:8px;flex:1;">
              <span class="c-days" style="font-size:1.2rem;font-weight:800;color:#1e40af;display:block;"><?php echo (int)$soonestProject['days_left']; ?></span>
              <span style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Hari</span>
            </div>
            <div style="background:#fff;border:1px solid #93c5fd;padding:6px 10px;border-radius:8px;flex:1;">
              <span class="c-hours" style="font-size:1.2rem;font-weight:800;color:#1e40af;display:block;"><?php echo sprintf('%02d', (int)$soonestProject['hours_left']); ?></span>
              <span style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Jam</span>
            </div>
            <div style="background:#fff;border:1px solid #93c5fd;padding:6px 10px;border-radius:8px;flex:1;">
              <span class="c-mins" style="font-size:1.2rem;font-weight:800;color:#1e40af;display:block;"><?php echo sprintf('%02d', (int)$soonestProject['mins_left']); ?></span>
              <span style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Menit</span>
            </div>
            <div style="background:#fff;border:1px solid #93c5fd;padding:6px 10px;border-radius:8px;flex:1;">
              <span class="c-secs" style="font-size:1.2rem;font-weight:800;color:#ef4444;display:block;"><?php echo sprintf('%02d', (int)$soonestProject['secs_left']); ?></span>
              <span style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Detik</span>
            </div>
          </div>
        </div>
        <?php else: ?>
        <p style="margin-top:14px;font-size:0.88rem;color:var(--text-muted);">Belum ada proyek aktif dengan tenggat. Cari proyek di Bursa Gig atau buka Penawaran Proyek.</p>
        <?php endif; ?>
      </section>

      <section class="white-card">
        <div class="card-header-flex">
          <div class="card-title-group">
            <h2>Akses Cepat</h2>
            <p>Fitur yang paling sering digunakan</p>
          </div>
        </div>
        <div class="quick-access-grid">
          <a href="worker-penawaran.php" class="quick-access-tile">
            <div class="tile-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
            <div>
              <div style="font-size:0.85rem;font-weight:700;">Penawaran Proyek</div>
              <div style="font-size:0.72rem;color:var(--text-muted);">Tawaran dari pemberi kerja</div>
            </div>
          </a>
          <a href="worker-bursa.php" class="quick-access-tile">
            <div class="tile-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/></svg></div>
            <div>
              <div style="font-size:0.85rem;font-weight:700;">Cari Proyek</div>
              <div style="font-size:0.72rem;color:var(--text-muted);">Cari lowongan proyek</div>
            </div>
          </a>
          <a href="worker-tugas.php" class="quick-access-tile">
            <div class="tile-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div>
            <div>
              <div style="font-size:0.85rem;font-weight:700;">Proyek Aktif</div>
              <div style="font-size:0.72rem;color:var(--text-muted);">Pantau pengerjaan</div>
            </div>
          </a>
          <a href="<?php echo htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8'); ?>" class="quick-access-tile">
            <div class="tile-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div>
              <div style="font-size:0.85rem;font-weight:700;">Profil</div>
              <div style="font-size:0.72rem;color:var(--text-muted);"><?php echo $isRegistered ? 'Lihat profil publik' : 'Lengkapi akun Anda'; ?></div>
            </div>
          </a>
          <a href="worker-riwayat.php" class="quick-access-tile">
            <div class="tile-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div>
              <div style="font-size:0.85rem;font-weight:700;">Riwayat Proyek</div>
              <div style="font-size:0.72rem;color:var(--text-muted);">Proyek yang sudah selesai</div>
            </div>
          </a>
        </div>
      </section>
    </div>

    <section class="white-card" style="margin-top:20px;">
      <div class="card-header-flex">
        <div class="card-title-group">
          <h2>Aktivitas Terbaru</h2>
          <p>Riwayat aktivitas akun</p>
        </div>
        <a class="btn-action-sm" href="worker-tugas.php">Lihat Semua</a>
      </div>
      <div class="activity-list">
        <div class="activity-row">
          <div class="activity-left">
            <div class="activity-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div>
            <div>
              <div class="activity-title">Proyek aktif — Redesign UI/UX Dashboard KarirHub</div>
              <div class="activity-date">9 September 2026, 11:20 WIB</div>
            </div>
          </div>
          <div class="activity-right">
            <span class="activity-code">Update terbaru</span>
            <span class="status-badge blue">Berjalan</span>
          </div>
        </div>
        <div class="activity-row">
          <div class="activity-left">
            <div class="activity-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div>
            <div>
              <div class="activity-title">Selesai — Portal Rekrutmen BUMN</div>
              <div class="activity-date">24 Juni 2026, 14:10 WIB</div>
            </div>
          </div>
          <div class="activity-right">
            <span class="activity-code">Riwayat proyek</span>
            <span class="status-badge green">Selesai</span>
          </div>
        </div>
      </div>
    </section>

    <script>
      (function startWorkerDashboardCountdown() {
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

<?php require __DIR__ . '/includes/worker-layout-end.php'; ?>
