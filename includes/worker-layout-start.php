<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/worker-profiles.php';
require_once __DIR__ . '/user-avatars.php';

$pageTitle = $pageTitle ?? 'Cari Proyek';
$pageKey = $pageKey ?? 'bursa';
$breadcrumbCurrent = $breadcrumbCurrent ?? 'Cari Proyek';
$workerObj = gig_find_worker($username ?? '') ?? gig_find_worker('tessa');
$displayName = $workerObj['name'] ?? ($_SESSION['siapkerja_name'] ?? 'Theressa Zaratrusha');
$userInitials = $workerObj['initials'] ?? 'TH';
$workerLabel = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
$workerAvatarUrl = gig_worker_display_photo((string)($workerObj['id'] ?? 'tessa'));

$isRegistered = true;
$profileUrl = 'worker-profile.php?id=tessa';

$navItems = [
    'bursa' => ['href' => 'worker-bursa.php', 'title' => 'Cari Proyek', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>'],
    'lamaran' => ['href' => 'worker-lamaran.php', 'title' => 'Lamaran Saya', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg>'],
    'penawaran' => ['href' => 'worker-penawaran.php', 'title' => 'Penawaran Proyek', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>'],
    'tugas' => ['href' => 'worker-tugas.php', 'title' => 'Proyek Aktif', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'],
    'riwayat' => ['href' => 'worker-riwayat.php', 'title' => 'Riwayat Proyek', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'],
    'ulasan' => ['href' => 'worker-ulasan.php', 'title' => 'Ulasan Mitra', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'],
    'profil' => ['href' => $profileUrl, 'title' => 'Profil Saya', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>'],
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
  <link rel="stylesheet" href="assets/worker.css" />
</head>
<body>
<div class="app-layout">
  <aside class="app-sidebar">
    <a href="<?php echo htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-logo" title="KarirHub">K</a>
    <div class="sidebar-menu">
      <?php foreach ($navItems as $key => $item): ?>
        <a href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-icon<?php echo $pageKey === $key ? ' active' : ''; ?>" title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo $item['icon']; ?>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="sidebar-bottom">
      <a href="worker-pengaturan.php" class="sidebar-icon<?php echo $pageKey === 'pengaturan' ? ' active' : ''; ?>" title="Pengaturan">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      </a>
      <div class="sidebar-user-initials" title="<?php echo $workerLabel; ?>"><?php echo htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
  </aside>

  <div class="app-main">
    <header class="app-navbar">
      <div class="navbar-left">
        <div class="breadcrumbs">
          <svg class="nav-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" onclick="history.back()"><polyline points="15 18 9 12 15 6"/></svg>
          <svg class="nav-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" onclick="history.forward()"><polyline points="9 18 15 12 9 6"/></svg>
          <span style="margin: 0 4px; color: var(--border-light);">|</span>
          <a href="<?php echo htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8'); ?>" style="color: inherit; text-decoration: none;">Beranda</a>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          <span class="current"><?php echo htmlspecialchars($breadcrumbCurrent, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </div>

      <div class="navbar-center">
        <form class="search-bar" action="worker-bursa.php" method="get">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="q" placeholder="Cari lowongan proyek dan keahlian..." value="<?php echo htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
        </form>
      </div>

<?php
$username = $username ?? (string)($_SESSION['username'] ?? $_SESSION['siapkerja_name'] ?? 'Theressa Zaratrusha');
$workerNotifs = gig_get_worker_notifications($username);
$unreadWorkerNotifs = count(array_filter($workerNotifs, fn($n) => empty($n['is_read'])));
?>
      <div class="navbar-right" style="display:flex;align-items:center;gap:12px;">
        <!-- WORKER NOTIFICATION BELL DROPDOWN -->
        <div class="profile-menu" style="position:relative;">
          <button type="button" onclick="document.getElementById('workerNotifDropdown').classList.toggle('open')" style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <?php if ($unreadWorkerNotifs > 0): ?>
              <span style="position:absolute;top:-2px;right:-2px;background:#ef4444;color:#fff;font-size:0.65rem;font-weight:800;border-radius:9999px;width:18px;height:18px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;"><?php echo $unreadWorkerNotifs; ?></span>
            <?php endif; ?>
          </button>
          
          <div class="profile-dropdown" id="workerNotifDropdown" style="width:340px;right:0;padding:12px 14px;">
            <div style="font-size:0.85rem;font-weight:800;color:#0f172a;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
              <span>Notifikasi Saya</span>
              <a href="worker-penawaran.php" style="font-size:0.72rem;color:#2563eb;font-weight:700;text-decoration:none;">Buka Penawaran &rarr;</a>
            </div>
            <div style="max-height:280px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;">
              <?php if (empty($workerNotifs)): ?>
                <div style="font-size:0.8rem;color:#64748b;text-align:center;padding:12px;">Belum ada notifikasi baru.</div>
              <?php else: ?>
                <?php foreach (array_slice($workerNotifs, 0, 5) as $wnf): ?>
                  <?php $wnfHref = gig_notification_href($wnf, 'worker'); ?>
                  <a class="notif-item-link" href="<?php echo htmlspecialchars($wnfHref, ENT_QUOTES, 'UTF-8'); ?>">
                    <div style="font-weight:800;color:#0f172a;margin-bottom:2px;"><?php echo htmlspecialchars($wnf['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="color:#475569;line-height:1.3;"><?php echo htmlspecialchars($wnf['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="font-size:0.68rem;color:#94a3b8;margin-top:4px;"><?php echo date('d M Y, H:i', strtotime($wnf['created_at'])); ?></div>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="profile-menu">
          <div class="profile-widget" onclick="document.getElementById('profileDropdown').classList.toggle('open')">
            <div style="width:36px;height:36px;border-radius:50%;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.95rem;flex-shrink:0;"><?php echo htmlspecialchars(strtoupper(substr($workerLabel !== '' ? $workerLabel : 'T', 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="profile-info">
              <span class="profile-name"><?php echo $workerLabel; ?></span>
              <span class="profile-role">Gig Workers</span>
            </div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
          <div class="profile-dropdown" id="profileDropdown">
            <a href="<?php echo htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8'); ?>">Profil saya</a>
            <a href="worker-pengaturan.php">Pengaturan akun</a>
            <a href="karirhub-logout.php">Keluar</a>
          </div>
        </div>
      </div>
    </header>

    <div class="app-content">
      <main class="page-main">
