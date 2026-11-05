<?php
declare(strict_types=1);

require_once __DIR__ . '/user-avatars.php';

$pageTitle = $pageTitle ?? 'Dashboard Pemberi Kerja';
$pageKey = $pageKey ?? 'overview';
$breadcrumbCurrent = $breadcrumbCurrent ?? 'Beranda';
$username = $username ?? (string)($_SESSION['username'] ?? $_SESSION['siapkerja_name'] ?? 'PT ABC');
$userInitials = strtoupper(substr($username !== '' ? $username : 'PT', 0, 2));
$companyLabel = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
$employerAvatarUrl = gig_resolve_user_avatar(null, 'employer');

$navItems = [
    'overview' => ['href' => 'dashboard-employer.php', 'title' => 'Ringkasan', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'],
    'lowongan' => ['href' => 'employer-lowongan.php', 'title' => 'Lowongan Proyek', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>'],
    'aktif' => ['href' => 'employer-proyek-aktif.php', 'title' => 'Proyek Aktif', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'],
    'riwayat' => ['href' => 'employer-riwayat-proyek.php', 'title' => 'Riwayat Proyek', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'],
    'cari-mitra' => ['href' => 'employer-cari-mitra.php', 'title' => 'Cari Mitra Gig', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'],
    'profil' => ['href' => 'employer-profile.php', 'title' => 'Profil Perusahaan', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> | KarirHub Gig Workers</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/employer.css" />
</head>
<body>
<div class="app-layout">
  <aside class="app-sidebar">
    <a href="dashboard-employer.php" class="sidebar-logo" title="KarirHub">K</a>
    <div class="sidebar-menu">
      <?php foreach ($navItems as $key => $item): ?>
        <a href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-icon<?php echo $pageKey === $key ? ' active' : ''; ?>" title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo $item['icon']; ?>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="sidebar-bottom">
      <a href="employer-pengaturan.php" class="sidebar-icon<?php echo $pageKey === 'pengaturan' ? ' active' : ''; ?>" title="Pengaturan">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      </a>
      <a href="employer-profile.php" class="sidebar-user-initials" title="<?php echo $companyLabel; ?>" style="text-decoration:none;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;"><?php echo htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
  </aside>

  <div class="app-main">
    <header class="app-navbar">
      <div class="navbar-left">
        <div class="breadcrumbs">
          <svg class="nav-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" onclick="history.back()"><polyline points="15 18 9 12 15 6"/></svg>
          <svg class="nav-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" onclick="history.forward()"><polyline points="9 18 15 12 9 6"/></svg>
          <span style="margin: 0 4px; color: var(--border-light);">|</span>
          <a href="dashboard-employer.php" style="color: inherit; text-decoration: none;">Beranda</a>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          <span class="current"><?php echo htmlspecialchars($breadcrumbCurrent, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </div>

      <div class="navbar-center">
        <form class="search-bar" action="employer-cari-mitra.php" method="get">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="q" placeholder="Cari lowongan proyek dan pelamar..." value="<?php echo htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
        </form>
      </div>

<?php
require_once __DIR__ . '/project-applications.php';
$empNotifs = gig_get_employer_notifications();
$unreadNotifCount = count(array_filter($empNotifs, fn($n) => empty($n['is_read'])));
?>
      <div class="navbar-right" style="display:flex;align-items:center;gap:12px;">
        <!-- NOTIFICATION BELL DROPDOWN -->
        <div class="profile-menu" style="position:relative;">
          <button type="button" onclick="document.getElementById('notifDropdown').classList.toggle('open')" style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <?php if ($unreadNotifCount > 0): ?>
              <span style="position:absolute;top:-2px;right:-2px;background:#ef4444;color:#fff;font-size:0.65rem;font-weight:800;border-radius:9999px;width:18px;height:18px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;"><?php echo $unreadNotifCount; ?></span>
            <?php endif; ?>
          </button>
          
          <div class="profile-dropdown" id="notifDropdown" style="width:340px;right:0;padding:12px 14px;">
            <div style="font-size:0.85rem;font-weight:800;color:#0f172a;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
              <span>Notifikasi Perusahaan</span>
              <span style="font-size:0.72rem;color:#2563eb;font-weight:600;"><?php echo count($empNotifs); ?> Total</span>
            </div>
            <div style="max-height:280px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;">
              <?php if (empty($empNotifs)): ?>
                <div style="font-size:0.8rem;color:#64748b;text-align:center;padding:12px;">Belum ada notifikasi baru.</div>
              <?php else: ?>
                <?php foreach (array_slice($empNotifs, 0, 5) as $nf): ?>
                  <?php $nfHref = gig_notification_href($nf, 'employer'); ?>
                  <a class="notif-item-link" href="<?php echo htmlspecialchars($nfHref, ENT_QUOTES, 'UTF-8'); ?>">
                    <div style="font-weight:800;color:#0f172a;margin-bottom:2px;"><?php echo htmlspecialchars($nf['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="color:#475569;line-height:1.3;"><?php echo htmlspecialchars($nf['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="font-size:0.68rem;color:#94a3b8;margin-top:4px;"><?php echo date('d M Y, H:i', strtotime($nf['created_at'])); ?></div>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="profile-menu">
          <div class="profile-widget" onclick="document.getElementById('profileDropdown').classList.toggle('open')" style="cursor:pointer;">
            <div style="width:36px;height:36px;border-radius:50%;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.95rem;flex-shrink:0;"><?php echo htmlspecialchars(strtoupper(substr($companyLabel !== '' ? $companyLabel : 'P', 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="profile-info">
              <span class="profile-name"><?php echo $companyLabel; ?></span>
              <span class="profile-role">Perusahaan</span>
            </div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
          <div class="profile-dropdown" id="profileDropdown">
            <a href="employer-profile.php">Profil Perusahaan</a>
            <a href="employer-pengaturan.php">Pengaturan akun</a>
            <a href="karirhub-logout.php">Keluar</a>
          </div>
        </div>
        <button class="btn-primary-add" type="button" onclick="openPostProjectModal()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah
        </button>
      </div>
    </header>

    <div class="app-content">
      <main class="page-main">
