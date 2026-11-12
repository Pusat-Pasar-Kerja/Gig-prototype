<?php
declare(strict_types=1);

require_once __DIR__ . '/user-avatars.php';

$adminTab = $adminTab ?? 'verification';
$pageTitle = $pageTitle ?? 'Dashboard Admin';
$breadcrumbCurrent = $breadcrumbCurrent ?? 'Dashboard';
$adminDisplayName = htmlspecialchars((string)($adminName ?? 'Admin KarirHub'), ENT_QUOTES, 'UTF-8');
$adminAvatarUrl = gig_resolve_user_avatar('admin@kemnaker.go.id', 'employer');
$adminSearchQ = trim((string)($_GET['q'] ?? ''));

$sidebarTabs = [
    'verification' => ['title' => 'Verifikasi', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>'],
    'workers' => ['title' => 'Gig Worker', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>'],
    'projects' => ['title' => 'Lowongan', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'],
    'profiling' => ['title' => 'Progressive Profiling', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> | KarirHub Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/employer.css" />
  <link rel="stylesheet" href="assets/admin.css" />
  <style>
    a, a:hover, a:focus, a:active, a:visited, a * { text-decoration: none !important; }
    .flash { padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 0.88rem; font-weight: 600; }
    .flash.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .flash.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .review-card { background: #fff; border: 1px solid var(--border-subtle); border-radius: 14px; padding: 18px; margin-bottom: 14px; box-shadow: var(--shadow-xs); }
    .review-card h3 { font-size: 1rem; margin-bottom: 6px; }
    .review-meta { color: var(--text-muted); font-size: 0.82rem; margin-bottom: 10px; }
    .review-detail { font-size: 0.86rem; color: var(--text-soft); margin-bottom: 12px; line-height: 1.55; }
    .review-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-start; }
    .review-actions textarea { width: 100%; min-height: 70px; border: 1px solid var(--border-light); border-radius: 10px; padding: 10px; font: inherit; margin-bottom: 8px; }
    .btn-approve { background: #059669; color: #fff; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 700; cursor: pointer; }
    .btn-reject { background: #dc2626; color: #fff; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 700; cursor: pointer; }
    .btn-revision { background: #d97706; color: #fff; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 700; cursor: pointer; }
    .empty-state { background: #fff; border: 1px dashed var(--border-light); border-radius: 12px; padding: 28px; text-align: center; color: var(--text-muted); }
  </style>
</head>
<body>
<div class="app-layout">
  <aside class="app-sidebar">
    <a href="dashboard-admin.php" class="sidebar-logo" title="KarirHub Admin">K</a>
    <div class="sidebar-menu">
      <?php foreach ($sidebarTabs as $key => $item): ?>
        <a href="dashboard-admin.php?tab=<?php echo urlencode($key); ?>" class="sidebar-icon<?php echo $adminTab === $key ? ' active' : ''; ?>" title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo $item['icon']; ?>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="sidebar-bottom">
      <a href="karirhub-home.php" class="sidebar-icon" title="Karirhub Home">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </a>
      <a href="karirhub-logout.php" class="sidebar-icon" title="Keluar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
      <div class="sidebar-user-initials" title="<?php echo $adminDisplayName; ?>">AD</div>
    </div>
  </aside>

  <div class="app-main">
    <header class="app-navbar">
      <div class="navbar-left">
        <div class="breadcrumbs">
          <a href="karirhub-home.php" style="color:inherit;text-decoration:none;">Beranda</a>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          <span class="current"><?php echo htmlspecialchars($breadcrumbCurrent, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </div>
      <div class="navbar-center">
        <form class="search-bar admin-search-mini" method="get" action="dashboard-admin.php">
          <input type="hidden" name="tab" value="<?php echo htmlspecialchars($adminTab, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="text" name="q" placeholder="Cari pendaftaran, pemberi kerja, atau lowongan..." value="<?php echo htmlspecialchars($adminSearchQ, ENT_QUOTES, 'UTF-8'); ?>" />
        </form>
      </div>
      <div class="navbar-right">
        <div class="admin-profile-chip">
          <div style="width:36px;height:36px;border-radius:50%;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.95rem;flex-shrink:0;"><?php echo htmlspecialchars(strtoupper(substr($adminDisplayName !== '' ? $adminDisplayName : 'A', 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
          <div>
            <div class="name"><?php echo $adminDisplayName; ?></div>
            <div class="role">Admin pusat</div>
          </div>
        </div>
      </div>
    </header>

    <div class="app-content">
      <main class="page-main">
