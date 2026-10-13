<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/project-vacancies.php';

$role = (string)($_SESSION['role'] ?? 'employer');
$username = (string)($_SESSION['username'] ?? 'PT Talenta Digital Indonesia');

if (!isset($_SESSION['username']) && !isset($_SESSION['role'])) {
    header("Location: welcome-screen.php");
    exit;
}

$employerName = trim((string)($_GET['name'] ?? $_GET['employer'] ?? ''));
if ($employerName === '') {
    $employerName = ($role === 'employer' && $username !== '') ? $username : 'PT Talenta Digital Indonesia';
}

$allVacancies = gig_project_vacancies();
$employerVacancies = array_values(array_filter($allVacancies, static function ($job) use ($employerName) {
    $emp = (string)($job['employer'] ?? 'PT Talenta Digital Indonesia');
    return strtolower($emp) === strtolower($employerName) || str_contains(strtolower($emp), strtolower($employerName));
}));

// Fallback to active vacancies if no direct name match
if (empty($employerVacancies)) {
    $employerVacancies = array_values(array_filter($allVacancies, static function ($job) {
        return $job['status'] === 'active';
    }));
    $employerVacancies = array_slice($employerVacancies, 0, 3);
}

$pageTitle = 'Profil Perusahaan · ' . $employerName;
$pageKey = 'profil';
$breadcrumbCurrent = 'Profil Perusahaan';

if ($role === 'employer') {
    require __DIR__ . '/includes/employer-layout-start.php';
} else {
    require __DIR__ . '/includes/worker-layout-start.php';
}
?>

<style>
  .emp-profile-container {
    max-width: 1000px;
    margin: 0 auto;
  }

  .emp-card {
    background: #ffffff;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 28px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
  }

  .emp-header-row {
    display: flex;
    gap: 20px;
    align-items: flex-start;
  }

  .emp-avatar-box {
    width: 76px;
    height: 76px;
    border-radius: 16px;
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
    color: #ffffff;
    font-size: 1.8rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
  }

  .emp-title {
    font-size: 1.4rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 6px 0;
  }

  .emp-badge-verified {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 9999px;
  }

  .emp-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px dashed #e2e8f0;
  }

  .emp-stat-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    text-align: center;
  }

  .emp-stat-val {
    font-size: 1.3rem;
    font-weight: 800;
    color: #1d4ed8;
  }

  .emp-stat-lbl {
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 600;
    margin-top: 2px;
  }
</style>

<div class="emp-profile-container">
  <a href="javascript:history.back()" style="display: inline-flex; align-items: center; gap: 6px; color: var(--primary-blue); font-size: 0.85rem; font-weight: 700; text-decoration: none; margin-bottom: 16px;">
    &larr; Kembali
  </a>

  <!-- EMPLOYER PROFILE HEADER -->
  <section class="emp-card">
    <div class="emp-header-row">
      <div class="emp-avatar-box">
        <?php echo htmlspecialchars(strtoupper(substr($employerName !== '' ? $employerName : 'P', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
      </div>

      <div style="flex-grow: 1;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
          <div>
            <h1 class="emp-title"><?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?></h1>
            <div style="font-size: 0.88rem; color: #64748b; margin-bottom: 8px;">
              Mitra Pemberi Kerja Terverifikasi SIAPKerja &bull; Kemnaker RI
            </div>
          </div>

          <span class="emp-badge-verified">
            ✓ Terverifikasi KYC Legalitas Perusahaan
          </span>
        </div>

        <p style="font-size: 0.9rem; line-height: 1.6; color: #334155; margin-top: 8px;">
          <?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?> adalah perusahaan mitra resmi ekosistem KarirHub yang secara aktif memublikasikan lowongan proyek gig berkualitas tinggi untuk para freelancer profesional di seluruh Indonesia.
        </p>

        <div class="emp-stats-grid">
          <div class="emp-stat-item">
            <div class="emp-stat-val"><?php echo count($employerVacancies); ?></div>
            <div class="emp-stat-lbl">Proyek Aktif</div>
          </div>
          <div class="emp-stat-item">
            <div class="emp-stat-val">12</div>
            <div class="emp-stat-lbl">Proyek Selesai</div>
          </div>
          <div class="emp-stat-item">
            <div class="emp-stat-val">★ 4.9</div>
            <div class="emp-stat-lbl">Rating Pemberi Kerja</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- PROYEK DIPASANG OLEH EMPLOYER INI -->
  <section class="emp-card">
    <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 16px;">
      Proyek yang Sedang Dibuka oleh <?php echo htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8'); ?>
    </h3>

    <div style="display: flex; flex-direction: column; gap: 14px;">
      <?php foreach ($employerVacancies as $job): 
        $jobLink = $role === 'employer' ? ('employer-detail-lowongan.php?id=' . urlencode($job['id'])) : ('worker-project-detail.php?id=' . urlencode($job['id']));
      ?>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div>
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;flex-wrap:wrap;">
              <span style="font-size: 0.75rem; background: #eff6ff; color: #1d4ed8; font-weight: 700; padding: 3px 10px; border-radius: 9999px; display: inline-block;">
                <?php echo htmlspecialchars($job['category'], ENT_QUOTES, 'UTF-8'); ?>
              </span>
              <?php if (gig_is_remote_work($job)): ?>
                <span style="font-size: 0.72rem; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 700; padding: 2px 8px; border-radius: 9999px;">
                  🌐 Remote
                </span>
              <?php else: ?>
                <span style="font-size: 0.72rem; background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; font-weight: 700; padding: 2px 8px; border-radius: 9999px;">
                  🏢 On-site
                </span>
              <?php endif; ?>
            </div>
            <h4 style="font-size: 0.98rem; font-weight: 800; color: #0f172a; margin: 4px 0;">
              <a href="<?php echo htmlspecialchars($jobLink, ENT_QUOTES, 'UTF-8'); ?>" style="color: inherit; text-decoration: none;">
                <?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
            </h4>
            <div style="font-size: 0.82rem; color: #64748b;">
              Gaji: <strong style="color: #1d4ed8;"><?php echo htmlspecialchars(gig_vacancy_budget_range((string)($job['budget'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></strong> &bull; Durasi: <?php echo htmlspecialchars($job['duration'], ENT_QUOTES, 'UTF-8'); ?> &bull; Lokasi: <?php echo htmlspecialchars((string)($job['location'] ?? 'Indonesia'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
          </div>

          <a href="<?php echo htmlspecialchars($jobLink, ENT_QUOTES, 'UTF-8'); ?>" class="btn-primary-add" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none; flex-shrink: 0;">
            Lihat Proyek &rarr;
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

</div>

<?php 
if ($role === 'employer') {
    require __DIR__ . '/includes/employer-layout-end.php';
} else {
    require __DIR__ . '/includes/worker-layout-end.php';
}
?>
