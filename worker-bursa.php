<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/worker-auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/project-vacancies.php';

// Only open projects with "active" (Tayang Aktif) status are visible to Gig Workers
$allActiveVacancies = array_values(array_filter(gig_project_vacancies(), static function ($job) {
    return $job['status'] === 'active';
}));

$q = trim((string)($_GET['q'] ?? ''));
$selectedCat = trim((string)($_GET['category'] ?? 'all'));
$selectedBudget = trim((string)($_GET['budget'] ?? 'all'));
$selectedLocation = trim((string)($_GET['location'] ?? 'all'));
$selectedMode = trim((string)($_GET['mode'] ?? 'all'));

$vacancies = $allActiveVacancies;

// Filter by Keyword / Skill / Title / Client
if ($q !== '') {
    $vacancies = array_values(array_filter($vacancies, static function ($job) use ($q) {
        $hay = strtolower($job['title'] . ' ' . $job['category'] . ' ' . implode(' ', $job['skills']) . ' ' . $job['desc'] . ' ' . $job['client']);
        return str_contains($hay, strtolower($q));
    }));
}

// Filter by Work Mode (Remote / On-site)
if ($selectedMode !== '' && $selectedMode !== 'all') {
    $vacancies = array_values(array_filter($vacancies, static function ($job) use ($selectedMode) {
        $isRemote = gig_is_remote_work($job);
        if ($selectedMode === 'remote') {
            return $isRemote;
        }
        if ($selectedMode === 'onsite') {
            return !$isRemote;
        }
        return true;
    }));
}

// Filter by Category
if ($selectedCat !== '' && $selectedCat !== 'all') {
    $vacancies = array_values(array_filter($vacancies, static function ($job) use ($selectedCat) {
        $jobCat = strtolower($job['category']);
        $selCat = strtolower($selectedCat);
        if ($selCat === 'ui/ux & desain' || $selCat === 'desain & kreatif') {
            return str_contains($jobCat, 'desain') || str_contains($jobCat, 'ui/ux');
        }
        if ($selCat === 'backend & api' || $selCat === 'it & pemrograman') {
            return str_contains($jobCat, 'it') || str_contains($jobCat, 'backend') || str_contains($jobCat, 'pemrograman');
        }
        if ($selCat === 'pemasaran' || $selCat === 'pemasaran & konten') {
            return str_contains($jobCat, 'pemasaran') || str_contains($jobCat, 'konten') || str_contains($jobCat, 'marketing');
        }
        if ($selCat === 'data & analitik' || $selCat === 'data analytics & entry') {
            return str_contains($jobCat, 'data');
        }
        return $jobCat === $selCat;
    }));
}

// Filter by Budget Range
if ($selectedBudget !== '' && $selectedBudget !== 'all') {
    $vacancies = array_values(array_filter($vacancies, static function ($job) use ($selectedBudget) {
        $nums = [];
        if (preg_match_all('/\d[\d\.]*/', (string)($job['budget'] ?? ''), $m) === 1) {
            $nums = array_values(array_filter(array_map(static fn($n) => (int)preg_replace('/[^\d]/', '', (string)$n), $m[0]), static fn($n) => $n > 0));
        }
        $bVal = (int)($nums[1] ?? $nums[0] ?? 0);
        if ($selectedBudget === 'under_5m') return $bVal < 5000000;
        if ($selectedBudget === '5m_10m') return $bVal >= 5000000 && $bVal <= 10000000;
        if ($selectedBudget === 'over_10m') return $bVal > 10000000;
        return true;
    }));
}

if ($selectedLocation !== '' && $selectedLocation !== 'all') {
    $selectedLocationNeedle = strtolower($selectedLocation);
    $vacancies = array_values(array_filter($vacancies, static function ($job) use ($selectedLocationNeedle) {
        $locationRaw = trim(strtolower((string)($job['location'] ?? '')));
        if ($locationRaw === '' || $locationRaw === 'lokasi belum diisi') {
            return false;
        }
        return str_contains($locationRaw, $selectedLocationNeedle);
    }));
}

function gig_bursa_relative_posted(string $posted): string
{
    $ts = strtotime($posted);
    if (!$ts) {
        return 'Baru diposting';
    }
    $days = max(0, (int)floor((time() - $ts) / 86400));
    if ($days < 7) {
        return $days <= 1 ? '1 hari yang lalu' : $days . ' hari yang lalu';
    }
    $months = max(1, (int)floor($days / 30));
    return $months . ' bulan yang lalu';
}

// Banner & category label helpers are loaded from includes/project-vacancies.php


$pageTitle = 'Cari Proyek';
$pageKey = 'bursa';
$breadcrumbCurrent = 'Cari Proyek';
require __DIR__ . '/includes/worker-layout-start.php';
?>

<style>
.cari-proyek-container {
  max-width: 1400px;
  margin: 0 auto;
}

.page-title-block {
  margin-bottom: 18px;
}

.page-title {
  margin: 0;
  font-size: 1.85rem;
  line-height: 1.2;
  color: #0f172a;
  font-weight: 800;
  letter-spacing: -0.3px;
}

.page-title-sub {
  margin: 4px 0 0 0;
  color: #64748b;
  font-size: 0.88rem;
  font-weight: 500;
}

.bursa-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 20px;
  align-items: start;
}

.filter-panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
  padding: 18px 16px;
  position: sticky;
  top: 86px;
}

.filter-panel h2 {
  margin: 0 0 12px 0;
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.2px;
}

.filter-search-box {
  display: flex;
  align-items: center;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #f8fafc;
  padding: 8px 12px;
  transition: all 0.2s ease;
}

.filter-search-box:focus-within {
  border-color: #0284c7;
  box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
  background: #fff;
}

.filter-search-box input {
  border: none;
  background: transparent;
  outline: none;
  width: 100%;
  font-size: 0.84rem;
  color: #0f172a;
  margin-left: 8px;
  font-family: inherit;
}

.filter-group {
  border-top: 1px solid #f1f5f9;
  padding-top: 12px;
  margin-top: 12px;
}

.filter-group-title {
  font-size: 0.78rem;
  font-weight: 800;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.filter-link-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 3px;
}

.filter-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 10px;
  border-radius: 8px;
  border: 1px solid transparent;
  color: #475569;
  font-size: 0.84rem;
  font-weight: 600;
  text-decoration: none;
  gap: 8px;
  transition: all 0.15s ease;
}

.filter-link:hover {
  background: #f8fafc;
  color: #0f172a;
}

.filter-link.active {
  background: #f0f9ff;
  border-color: #bae6fd;
  color: #0284c7;
  font-weight: 700;
}

.filter-radio-dot {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  border: 1.5px solid #cbd5e1;
  background: #ffffff;
  flex-shrink: 0;
  position: relative;
}

.filter-link.active .filter-radio-dot {
  border-color: #0284c7;
}

.filter-link.active .filter-radio-dot::after {
  content: "";
  width: 6px;
  height: 6px;
  border-radius: 999px;
  background: #0284c7;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.filter-pill-count {
  font-size: 0.7rem;
  background: #e2e8f0;
  color: #475569;
  border-radius: 999px;
  padding: 2px 7px;
  font-weight: 700;
}

.filter-reset {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 14px;
  font-size: 0.8rem;
  font-weight: 700;
  color: #0284c7;
  text-decoration: none;
}
.filter-reset:hover {
  text-decoration: underline;
}

.results-pane {
  min-width: 0;
}

.proyek-cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

@media (max-width: 1100px) {
  .proyek-cards-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 840px) {
  .bursa-layout {
    grid-template-columns: 1fr;
  }
  .filter-panel {
    position: static;
  }
}

@media (max-width: 580px) {
  .proyek-cards-grid {
    grid-template-columns: 1fr;
  }
}

.proyek-card-item {
  background: #ffffff;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 18px;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  height: 100%;
}

a.proyek-card-item:hover {
  transform: translateY(-3px);
  border-color: #93c5fd;
  box-shadow: 0 10px 22px -4px rgba(37, 99, 235, 0.1);
  color: inherit;
}

.card-employer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  gap: 8px;
}

.card-employer-left {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  flex: 1;
}

.card-employer-info {
  min-width: 0;
  flex: 1;
}

.employer-avatar-circle-sm {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #1e40af, #2563eb);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  flex-shrink: 0;
}

.card-employer-name {
  font-size: 0.82rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-employer-sub {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 500;
}

.card-cat-badge {
  color: #64748b;
  font-size: 0.74rem;
  font-weight: 500;
  white-space: nowrap;
  flex-shrink: 0;
}

.card-project-title {
  font-size: 1rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 12px 0;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.7em;
}

.card-meta-detail {
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: 10px;
  padding: 10px 12px;
  margin-bottom: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-salary-label {
  font-size: 0.68rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 2px;
}

.card-salary-value {
  font-size: 1.05rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.meta-pill-duration {
  font-size: 0.74rem;
  font-weight: 700;
  color: #0284c7;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  padding: 3px 9px;
  border-radius: 6px;
  white-space: nowrap;
}

.card-skills-label {
  font-size: 0.68rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 6px;
}

.card-skills-row {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 14px;
  min-height: 28px;
}

.skill-pill-sm {
  background: #f1f5f9;
  color: #334155;
  font-size: 0.74rem;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.skill-pill-more {
  background: #e2e8f0;
  color: #334155;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
}

.card-action-footer {
  padding-top: 12px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.76rem;
}

.deadline-text {
  color: #64748b;
  font-weight: 500;
}

.apply-cta {
  font-weight: 700;
  color: #0284c7;
  transition: transform 0.15s ease;
}

a.proyek-card-item:hover .apply-cta {
  transform: translateX(3px);
}
</style>

<div class="cari-proyek-container">
  <?php
    $locations = [];
    foreach ($allActiveVacancies as $rowVacancy) {
        $loc = trim((string)($rowVacancy['location'] ?? ''));
        if ($loc === '' || strcasecmp($loc, 'Lokasi belum diisi') === 0) {
            $loc = gig_random_location((string)($rowVacancy['id'] ?? ''));
        }
        $locKey = strtolower($loc);
        if (!isset($locations[$locKey])) {
            $locations[$locKey] = ['label' => $loc, 'count' => 0];
        }
        $locations[$locKey]['count']++;
    }
    uasort($locations, static fn($a, $b) => strcmp((string)$a['label'], (string)$b['label']));
    $locations = array_slice($locations, 0, 6, true);

    $catCounts = [
      'all' => count($allActiveVacancies),
      'UI/UX & Desain' => count(array_filter($allActiveVacancies, static fn($j) => str_contains(strtolower((string)$j['category']), 'desain') || str_contains(strtolower((string)$j['category']), 'ui/ux'))),
      'Backend & API' => count(array_filter($allActiveVacancies, static fn($j) => str_contains(strtolower((string)$j['category']), 'it') || str_contains(strtolower((string)$j['category']), 'backend') || str_contains(strtolower((string)$j['category']), 'pemrograman'))),
      'Pemasaran' => count(array_filter($allActiveVacancies, static fn($j) => str_contains(strtolower((string)$j['category']), 'pemasaran') || str_contains(strtolower((string)$j['category']), 'konten') || str_contains(strtolower((string)$j['category']), 'marketing'))),
      'Data & Analitik' => count(array_filter($allActiveVacancies, static fn($j) => str_contains(strtolower((string)$j['category']), 'data'))),
    ];
    $catMap = [
      'all' => 'Semua Bidang',
      'UI/UX & Desain' => 'UI/UX & Desain',
      'Backend & API' => 'Backend & API',
      'Pemasaran' => 'Pemasaran',
      'Data & Analitik' => 'Data & Analitik',
    ];
    $budgetMap = [
      'all' => 'Semua Rentang Gaji',
      'under_5m' => 'Di bawah 5 Juta',
      '5m_10m' => '5 Juta - 10 Juta',
      'over_10m' => 'Di atas 10 Juta',
    ];

    $remoteCount = count(array_filter($allActiveVacancies, static fn($j) => gig_is_remote_work($j)));
    $onsiteCount = count($allActiveVacancies) - $remoteCount;
    $modeMap = [
      'all' => ['label' => 'Semua Mode', 'count' => count($allActiveVacancies)],
      'remote' => ['label' => '🌐 Remote', 'count' => $remoteCount],
      'onsite' => ['label' => '🏢 On-site / Hybrid', 'count' => $onsiteCount],
    ];
  ?>

  <section class="page-title-block">
    <div>
      <h1 class="page-title">Cari Proyek</h1>
      <p class="page-title-sub">Temukan lowongan proyek yang sesuai dengan keahlian Anda.</p>
    </div>
  </section>

  <div class="bursa-layout">
    <aside class="filter-panel">
      <h2>Filter</h2>

      <form method="get" action="" class="filter-search-form">
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCat, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="budget" value="<?php echo htmlspecialchars($selectedBudget, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="location" value="<?php echo htmlspecialchars($selectedLocation, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="mode" value="<?php echo htmlspecialchars($selectedMode, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="filter-search-box">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="q" placeholder="Cari kata kunci lowongan..." value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" />
        </div>
      </form>

      <div class="filter-group">
        <div class="filter-group-title">Mode Kerja</div>
        <ul class="filter-link-list">
          <?php foreach ($modeMap as $modeKey => $modeInfo): ?>
            <?php $isModeActive = ($selectedMode === $modeKey || ($modeKey === 'all' && ($selectedMode === '' || $selectedMode === 'all'))); ?>
            <li>
              <a class="filter-link <?php echo $isModeActive ? 'active' : ''; ?>" href="?<?php echo htmlspecialchars(http_build_query(['category' => $selectedCat, 'budget' => $selectedBudget, 'q' => $q, 'location' => $selectedLocation, 'mode' => $modeKey]), ENT_QUOTES, 'UTF-8'); ?>">
                <span class="filter-radio-dot"></span>
                <span style="flex:1;"><?php echo htmlspecialchars($modeInfo['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="filter-pill-count"><?php echo (int)$modeInfo['count']; ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="filter-group">
        <div class="filter-group-title">Lokasi</div>
        <ul class="filter-link-list">
          <li>
            <a class="filter-link <?php echo ($selectedLocation === 'all' || $selectedLocation === '') ? 'active' : ''; ?>" href="?<?php echo htmlspecialchars(http_build_query(['category' => $selectedCat, 'budget' => $selectedBudget, 'q' => $q, 'location' => 'all', 'mode' => $selectedMode]), ENT_QUOTES, 'UTF-8'); ?>">
              <span class="filter-radio-dot"></span>
              <span style="flex:1;">Semua Lokasi</span>
              <span class="filter-pill-count"><?php echo count($allActiveVacancies); ?></span>
            </a>
          </li>
          <?php foreach ($locations as $loc): ?>
            <?php
              $locLabel = (string)$loc['label'];
              $isLocActive = strtolower($selectedLocation) === strtolower($locLabel);
            ?>
            <li>
              <a class="filter-link <?php echo $isLocActive ? 'active' : ''; ?>" href="?<?php echo htmlspecialchars(http_build_query(['category' => $selectedCat, 'budget' => $selectedBudget, 'q' => $q, 'location' => $locLabel, 'mode' => $selectedMode]), ENT_QUOTES, 'UTF-8'); ?>">
                <span class="filter-radio-dot"></span>
                <span style="flex:1;"><?php echo htmlspecialchars($locLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="filter-pill-count"><?php echo (int)$loc['count']; ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="filter-group">
        <div class="filter-group-title">Kategori</div>
        <ul class="filter-link-list">
          <?php foreach ($catMap as $catKey => $catLabel): ?>
            <?php $isActive = (strtolower($selectedCat) === strtolower($catKey) || ($catKey === 'all' && $selectedCat === 'all')); ?>
            <li>
              <a class="filter-link <?php echo $isActive ? 'active' : ''; ?>" href="?<?php echo htmlspecialchars(http_build_query(['category' => $catKey, 'budget' => $selectedBudget, 'q' => $q, 'location' => $selectedLocation, 'mode' => $selectedMode]), ENT_QUOTES, 'UTF-8'); ?>">
                <span class="filter-radio-dot"></span>
                <span style="flex:1;"><?php echo htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="filter-pill-count"><?php echo (int)($catCounts[$catKey] ?? 0); ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="filter-group">
        <div class="filter-group-title">Rentang Gaji</div>
        <ul class="filter-link-list">
          <?php foreach ($budgetMap as $budgetKey => $budgetLabel): ?>
            <?php $isBudgetActive = $selectedBudget === $budgetKey || ($selectedBudget === '' && $budgetKey === 'all'); ?>
            <li>
              <a class="filter-link <?php echo $isBudgetActive ? 'active' : ''; ?>" href="?<?php echo htmlspecialchars(http_build_query(['category' => $selectedCat, 'budget' => $budgetKey, 'q' => $q, 'location' => $selectedLocation, 'mode' => $selectedMode]), ENT_QUOTES, 'UTF-8'); ?>">
                <span class="filter-radio-dot"></span>
                <span><?php echo htmlspecialchars($budgetLabel, ENT_QUOTES, 'UTF-8'); ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <a class="filter-reset" href="worker-bursa.php">↺ Reset semua filter</a>
    </aside>

    <section class="results-pane">
      <?php if (empty($vacancies)): ?>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 48px 20px; text-align: center; color: #64748b;">
          <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px; color: #94a3b8;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 6px;">Tidak ada proyek yang cocok</h3>
          <p style="font-size: 0.9rem;">Coba sesuaikan kata kunci pencarian atau pilih kategori lain.</p>
          <a href="worker-bursa.php" class="filter-reset" style="display: inline-flex; margin-top: 16px;">Tampilkan Semua Proyek</a>
        </div>
      <?php else: ?>
        <div class="proyek-cards-grid">
          <?php foreach ($vacancies as $idx => $job): ?>
            <?php
              $badgeLabel = getCategoryShortLabel($job['category']);
              $employerDisplayName = (string)($job['employer'] ?? ($job['client'] ?? 'PT SIAPKerja Partner'));
              $skillsToShow = array_slice($job['skills'], 0, 3);
              $remainingCount = count($job['skills']) - count($skillsToShow);
              $cleanBudget = trim(preg_replace('/\s*\/\s*bulan/i', '', gig_vacancy_budget_range((string)($job['budget'] ?? ''))));
            ?>
            <a class="proyek-card-item" href="worker-project-detail.php?id=<?php echo urlencode($job['id']); ?>">
              <div>
                <div class="card-employer-header">
                  <div class="card-employer-left">
                    <div class="employer-avatar-circle-sm">🏢</div>
                    <div class="card-employer-info">
                      <div class="card-employer-name"><?php echo htmlspecialchars($employerDisplayName, ENT_QUOTES, 'UTF-8'); ?></div>
                      <?php
                        $locDisplay = trim((string)($job['location'] ?? ''));
                        if ($locDisplay === '' || strcasecmp($locDisplay, 'Lokasi belum diisi') === 0) {
                            $locDisplay = gig_random_location((string)($job['id'] ?? ''));
                        }
                      ?>
                      <div class="card-employer-sub" style="display:flex;align-items:center;gap:6px;">
                        <span>📍 <?php echo htmlspecialchars($locDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span>•</span>
                        <?php if (gig_is_remote_work($job)): ?>
                          <span style="color:#1d4ed8;font-weight:700;">🌐 Remote</span>
                        <?php else: ?>
                          <span style="color:#475569;font-weight:600;">🏢 On-site</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <span class="card-cat-badge"><?php echo htmlspecialchars(gig_bursa_relative_posted((string)($job['posted'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <h3 class="card-project-title"><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></h3>

                <div class="card-meta-detail">
                  <div>
                    <div class="card-salary-label">Gaji</div>
                    <div class="card-salary-value"><?php echo htmlspecialchars($cleanBudget, ENT_QUOTES, 'UTF-8'); ?></div>
                  </div>
                  <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                    <div class="meta-pill-duration"><?php echo htmlspecialchars((string)($job['duration'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if (gig_is_remote_work($job)): ?>
                      <span style="font-size:0.7rem;font-weight:700;color:#1d4ed8;background:#eff6ff;border:1px solid #bfdbfe;padding:2px 7px;border-radius:6px;display:inline-flex;align-items:center;gap:3px;">🌐 Remote</span>
                    <?php else: ?>
                      <span style="font-size:0.7rem;font-weight:700;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;padding:2px 7px;border-radius:6px;display:inline-flex;align-items:center;gap:3px;">🏢 On-site</span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="card-skills-label">Keahlian</div>
                <div class="card-skills-row">
                  <?php foreach ($skillsToShow as $sk): ?>
                    <span class="skill-pill-sm"><?php echo htmlspecialchars((string)$sk, ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php endforeach; ?>
                  <?php if ($remainingCount > 0): ?>
                    <span class="skill-pill-more">+<?php echo $remainingCount; ?></span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="card-action-footer">
                <span class="deadline-text">Lamar Sebelum: <?php echo htmlspecialchars((string)($job['deadline'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>

<?php require __DIR__ . '/includes/worker-layout-end.php'; ?>

