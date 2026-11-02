<?php
declare(strict_types=1);
session_start();

$profile = [
    'name' => 'Achmad Rizky Mauludi',
    'email' => 'achmadky555@gmail.com',
    'bio_short' => 'Saya merupakan seorang profesional di bidang Teknologi Informasi dengan pengalaman kerja selama kurang lebih 3 tahun dalam pengembangan, pengujian, dan pemeliharaan sistem aplikasi. Saya memiliki...',
    'bio_full' => 'Saya merupakan seorang profesional di bidang Teknologi Informasi dengan pengalaman kerja selama kurang lebih 3 tahun dalam pengembangan, pengujian, dan pemeliharaan sistem aplikasi. Saya memiliki keahlian dalam membangun solusi perangkat lunak yang andal, melakukan otomatisasi pengujian (QA Automation), serta mengelola integrasi sistem berbasis middleware.',
    'avatar' => 'assets/profile-avatar-crop.png',
    'banner_art' => 'assets/profile-banner-right.png',
];

$experiences = [
    [
        'title' => 'Application Developer',
        'verified' => true,
        'company' => 'PT infracom Technology',
        'period' => 'Januari 2024 - November 2024',
        'location' => 'Petojo Selatan, Gambir, KOTA ADM. JAKARTA PUSAT, DKI JAKARTA',
    ],
    [
        'title' => 'QA Automation Engineer',
        'verified' => true,
        'company' => 'PT. KPSG',
        'period' => 'November 2024 - Mei 2025',
        'location' => 'Karet Semanggi, Setiabudi, KOTA ADM. JAKARTA SELATAN, DKI JAKARTA',
    ],
    [
        'title' => 'Middleware Engineer',
        'verified' => true,
        'company' => 'PT infracom Technology',
        'period' => 'Maret 2023 - Desember 2023',
        'location' => 'Petojo Selatan, Gambir, KOTA ADM. JAKARTA PUSAT, DKI JAKARTA',
    ],
];

$trainings = [
    [
        'title' => 'Quality Assurance',
        'verified' => true,
        'category' => 'TEKNOLOGI INFORMASI DAN KOMUNIKASI',
        'provider' => 'PT Santai Berkualitas Syberindo',
        'period' => 'Mei 2024 - Mei 2024',
        'description' => 'Sebuah pelatihan untuk mengetahui apa itu quality assurance, bagaimana cara kerjanya dan mengetahui bagaimana cara para tester mengetahui sebuah bug yang ada di dalam sistem',
        'certificate_label' => 'Sertifikat Pelatihan',
    ],
];

$educations = [
    [
        'school' => 'Politeknik Negeri Jakarta',
        'verified' => true,
        'degree' => 'D4',
        'gpa' => '3.62',
        'period' => '2017 - 2021',
        'location' => 'Kukusan, Beji, KOTA DEPOK, JAWA BARAT',
        'certificate_label' => 'Ijasah Pendidikan',
    ],
];

$certifications = [];

$achievements = [];

$skills = [
    'Kotlin',
    'cypress',
    'Flutter',
    'Outsystems',
    'Laravel',
    'CSS',
    'SQL',
    'PHP',
    'Javascript Frameworks',
    'HTML',
    'JMeter',
    'Android',
    'Vue.js',
];

$languages = [
    [
        'name' => 'English (Bahasa Inggris)',
        'level' => 'Tingkat Menengah',
    ],
    [
        'name' => 'Bahasa Indonesia',
        'level' => 'Tingkat Fasih atau Bahasa Ahli',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profil - <?php echo htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      background-color: #f9fafb;
      color: #111827;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    /* Outer wrapper matching the 700px SIAPkerja / Karirhub profile container */
    .profile-shell {
      max-width: 702px;
      margin: 0 auto;
      background-color: #f9fafb;
      min-height: 100vh;
      border-left: 1px solid #f3f4f6;
      border-right: 1px solid #f3f4f6;
    }

    /* Top Sticky Navigation Header (height: 46px) */
    .top-header {
      position: sticky;
      top: 0;
      z-index: 40;
      height: 46px;
      background-color: #ffffff;
      border-bottom: 1px solid #e5e7eb;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 16px;
    }

    .back-btn {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      width: 28px;
      height: 28px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #111827;
      text-decoration: none;
      border-radius: 6px;
      cursor: pointer;
      background: transparent;
      border: none;
    }

    .back-btn:hover {
      background-color: #f3f4f6;
    }

    .top-header-title {
      font-size: 16px;
      font-weight: 700;
      color: #111827;
      letter-spacing: -0.01em;
      line-height: 1;
    }

    /* Main Profile Card */
    .profile-card {
      background-color: #ffffff;
    }

    /* Teal Banner (height: 89px, color: #09B8A7) */
    .profile-banner {
      position: relative;
      height: 89px;
      background-color: #09b8a7;
      overflow: hidden;
    }

    .profile-banner-art {
      position: absolute;
      top: 0;
      right: 0;
      height: 89px;
      width: auto;
      display: block;
      pointer-events: none;
      user-select: none;
    }

    /* Profile Identity Section */
    .profile-identity {
      position: relative;
      padding: 0 23px 19px 23px;
    }

    /* Avatar + Edit Profil Row */
    .profile-top-row {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      min-height: 52px;
      margin-bottom: 17px;
    }

    .avatar-wrap {
      position: relative;
      margin-top: -53px;
      margin-left: -1px;
      width: 99px;
      height: 99px;
      flex-shrink: 0;
      user-select: none;
    }

    .avatar-img {
      width: 99px;
      height: 99px;
      display: block;
    }

    .camera-btn {
      position: absolute;
      right: -1px;
      bottom: 0;
      width: 31px;
      height: 31px;
      border-radius: 50%;
      background: transparent;
      border: none;
      cursor: pointer;
      padding: 0;
    }

    /* Edit Profil Dropdown Button */
    .edit-profil-wrapper {
      position: relative;
      margin-top: 18px;
    }

    .btn-edit-profil {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 11px;
      height: 34px;
      padding: 0 15px 0 16px;
      background-color: #ffffff;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-family: inherit;
      font-size: 13.5px;
      font-weight: 700;
      color: #111827;
      cursor: pointer;
      transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .btn-edit-profil:hover {
      background-color: #f9fafb;
      border-color: #9ca3af;
    }

    .btn-edit-profil svg {
      width: 14px;
      height: 14px;
      color: #111827;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .edit-dropdown {
      display: none;
      position: absolute;
      right: 0;
      top: calc(100% + 6px);
      min-width: 180px;
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      box-shadow: 0 10px 25px rgba(17, 24, 39, 0.1);
      z-index: 30;
      padding: 4px 0;
    }

    .edit-dropdown.open {
      display: block;
    }

    .edit-dropdown a {
      display: block;
      padding: 9px 14px;
      font-size: 13px;
      font-weight: 500;
      color: #374151;
      text-decoration: none;
    }

    .edit-dropdown a:hover {
      background-color: #f3f4f6;
      color: #111827;
    }

    /* User Name & Email */
    .profile-name {
      font-size: 18px;
      font-weight: 700;
      color: #111827;
      line-height: 22px;
      letter-spacing: -0.01em;
      margin-bottom: 6px;
    }

    .profile-email {
      font-size: 14px;
      font-weight: 400;
      color: #111827;
      line-height: 18px;
      margin-bottom: 16px;
    }

    /* Bio Summary */
    .profile-bio {
      font-size: 13px;
      font-weight: 400;
      color: #6b7280;
      line-height: 20px;
      margin-bottom: 5px;
      letter-spacing: -0.008em;
    }

    .btn-read-more {
      display: inline-block;
      background: none;
      border: none;
      padding: 0;
      font-family: inherit;
      font-size: 13.5px;
      font-weight: 600;
      color: #374151;
      cursor: pointer;
      line-height: 18px;
    }

    .btn-read-more:hover {
      color: #111827;
      text-decoration: underline;
    }

    /* Lihat Informasi Biodata Row */
    .biodata-bar {
      border-top: 1px solid #e5e7eb;
      padding: 19px 23px 18px 23px;
      background-color: #ffffff;
    }

    .biodata-link {
      display: inline-block;
      font-size: 14px;
      font-weight: 700;
      color: #09b8a7;
      text-decoration: none;
      letter-spacing: -0.005em;
      line-height: 18px;
    }

    .biodata-link:hover {
      color: #079b8c;
      text-decoration: underline;
    }

    /* 7px Spacer between cards */
    .section-spacer {
      height: 7px;
      background-color: #f9fafb;
    }

    /* Pengalaman Kerja Section Card */
    .experience-card {
      background-color: #ffffff;
      padding: 22px 23px 4px 23px;
    }

    .experience-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .experience-title {
      font-size: 16px;
      font-weight: 700;
      color: #111826;
      letter-spacing: -0.01em;
      line-height: 20px;
    }

    .btn-add-exp {
      width: 24px;
      height: 24px;
      margin-right: -3px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: transparent;
      border: none;
      color: #111826;
      border-radius: 6px;
      cursor: pointer;
    }

    .btn-add-exp:hover {
      background-color: #f3f4f6;
    }

    /* Experience List & Items */
    .experience-list {
      list-style: none;
    }

    .experience-item {
      display: flex;
      align-items: flex-start;
      gap: 22px;
      padding-bottom: 14px;
      margin-bottom: 15px;
      border-bottom: 1px solid #f3f4f6;
    }

    .experience-item:last-child {
      margin-bottom: 0;
    }

    .exp-icon-box {
      width: 60px;
      height: 60px;
      border-radius: 10px;
      background-color: #9da4ae;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      overflow: hidden;
    }

    .exp-icon-box img {
      width: 60px;
      height: 60px;
      display: block;
    }

    .exp-content {
      flex: 1;
      min-width: 0;
      padding-top: 0;
    }

    .exp-role-row {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 4px;
    }

    .exp-role {
      font-size: 15.5px;
      font-weight: 700;
      color: #111826;
      line-height: 20px;
      letter-spacing: -0.01em;
    }

    .verified-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      background-color: #049769;
      flex-shrink: 0;
    }

    .verified-badge svg {
      width: 10px;
      height: 10px;
      color: #ffffff;
    }

    .exp-company {
      font-size: 13.8px;
      font-weight: 400;
      color: #111826;
      line-height: 18px;
      margin-bottom: 5px;
    }

    .exp-period {
      font-size: 13.8px;
      font-weight: 400;
      color: #6c727f;
      line-height: 18px;
      margin-bottom: 12px;
    }

    .exp-location {
      font-size: 13.8px;
      font-weight: 400;
      color: #6c727f;
      line-height: 18px;
    }

    /* Pelatihan & Pendidikan Specific Styles */
    .section-card {
      background-color: #ffffff;
      padding: 22px 23px 15px 23px;
    }

    .section-item {
      display: flex;
      align-items: flex-start;
      gap: 22px;
    }

    .section-item + .section-item {
      padding-top: 15px;
      margin-top: 15px;
      border-top: 1px solid #f3f4f6;
    }

    .training-category {
      font-size: 13.5px;
      font-weight: 400;
      color: #111826;
      line-height: 18px;
      margin-bottom: 6px;
    }

    .training-provider {
      font-size: 13.8px;
      font-weight: 400;
      color: #6c727f;
      line-height: 18px;
      margin-bottom: 10px;
    }

    .training-period {
      font-size: 13.8px;
      font-weight: 400;
      color: #6c727f;
      line-height: 18px;
      margin-bottom: 10px;
    }

    .training-desc {
      font-size: 13.5px;
      font-weight: 400;
      color: #111827;
      line-height: 19px;
      max-width: 548px;
      margin-bottom: 12px;
      letter-spacing: -0.005em;
    }

    .edu-degree {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13.8px;
      font-weight: 400;
      color: #111826;
      line-height: 18px;
      margin-bottom: 6px;
    }

    .edu-bullet {
      font-size: 10px;
      color: #111826;
      line-height: 1;
    }

    .edu-period {
      font-size: 13.8px;
      font-weight: 400;
      color: #6c727f;
      line-height: 18px;
      margin-bottom: 11px;
    }

    .edu-location {
      font-size: 13.8px;
      font-weight: 400;
      color: #6c727f;
      line-height: 18px;
      margin-bottom: 12px;
    }

    .attachment-box {
      display: flex;
      align-items: center;
      gap: 11px;
      width: 100%;
      max-width: 549px;
      height: 36px;
      padding: 0 12px;
      border: 1px solid #e4e7ea;
      border-radius: 8px;
      background-color: #ffffff;
      color: #111826;
      text-decoration: none;
      font-size: 12.2px;
      font-weight: 500;
      transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .attachment-box:hover {
      background-color: #f9fafb;
      border-color: #d1d5db;
    }

    .attachment-box img {
      width: 16px;
      height: 18px;
      display: block;
      flex-shrink: 0;
    }

    /* Sertifikasi Empty State */
    .certification-card {
      background-color: #ffffff;
      padding: 22px 23px 40px 23px;
    }

    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 28px 16px 8px 16px;
    }

    .empty-state-img {
      width: 170px;
      height: 82px;
      display: block;
      margin-bottom: 38px;
      user-select: none;
    }

    .empty-state-title {
      font-size: 16px;
      font-weight: 700;
      color: #111827;
      line-height: 20px;
      letter-spacing: -0.01em;
      margin-bottom: 11px;
    }

    .empty-state-sub {
      font-size: 13.6px;
      font-weight: 400;
      color: #6b7280;
      line-height: 19px;
      letter-spacing: -0.005em;
    }

    /* Keahlian Section */
    .skills-card {
      background-color: #ffffff;
      padding: 22px 23px 24px 23px;
    }

    .skills-list {
      display: flex;
      flex-wrap: wrap;
      column-gap: 8px;
      row-gap: 10px;
      padding-top: 4px;
    }

    .skill-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 22px;
      padding: 0 6px;
      border: 1px solid #09b8a7;
      border-radius: 999px;
      background-color: #ffffff;
      color: #09b8a7;
      font-size: 11.2px;
      font-weight: 500;
      line-height: 1;
      letter-spacing: -0.01em;
      white-space: nowrap;
    }

    /* Bahasa Section */
    .language-card {
      background-color: #ffffff;
      padding: 22px 23px 6px 23px;
    }

    .language-list {
      list-style: none;
      padding-top: 2px;
    }

    .language-item {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      padding-bottom: 16px;
      margin-bottom: 18px;
      border-bottom: 1px solid #f3f4f6;
    }

    .language-item:last-child {
      margin-bottom: 0;
    }

    .language-info {
      min-width: 0;
    }

    .language-name {
      font-size: 15.5px;
      font-weight: 700;
      color: #111826;
      line-height: 20px;
      letter-spacing: -0.01em;
      margin-bottom: 5px;
    }

    .language-level {
      font-size: 13.8px;
      font-weight: 400;
      color: #111826;
      line-height: 18px;
    }

    .btn-edit-item {
      width: 24px;
      height: 24px;
      margin-top: -1px;
      margin-right: -3px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: transparent;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      padding: 0;
      flex-shrink: 0;
    }

    .btn-edit-item:hover {
      background-color: #f3f4f6;
    }

    .btn-edit-item img {
      width: 20px;
      height: 20px;
      display: block;
    }

    @media (max-width: 640px) {
      .profile-identity {
        padding: 0 16px 18px 16px;
      }
      .biodata-bar {
        padding: 16px;
      }
      .experience-card,
      .section-card,
      .certification-card,
      .skills-card,
      .language-card {
        padding-left: 16px;
        padding-right: 16px;
      }
      .experience-item,
      .section-item {
        gap: 16px;
      }
    }
  </style>
</head>
<body>

  <div class="profile-shell">
    <!-- Top Header -->
    <header class="top-header">
      <a href="karirhub-home.php" class="back-btn" aria-label="Kembali">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
      </a>
      <h1 class="top-header-title">Profil</h1>
    </header>

    <!-- Main Profile Card -->
    <section class="profile-card">
      <!-- Teal Banner -->
      <div class="profile-banner">
        <img
          src="<?php echo htmlspecialchars($profile['banner_art'], ENT_QUOTES, 'UTF-8'); ?>"
          alt=""
          class="profile-banner-art"
        />
      </div>

      <!-- Identity Block -->
      <div class="profile-identity">
        <div class="profile-top-row">
          <div class="avatar-wrap">
            <img
              src="<?php echo htmlspecialchars($profile['avatar'], ENT_QUOTES, 'UTF-8'); ?>"
              alt="<?php echo htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8'); ?>"
              class="avatar-img"
            />
            <button type="button" class="camera-btn" aria-label="Ubah foto profil"></button>
          </div>

          <div class="edit-profil-wrapper" id="editProfilWrap">
            <button type="button" class="btn-edit-profil" id="editProfilBtn" aria-expanded="false">
              <span>Edit Profil</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <div class="edit-dropdown" id="editDropdown">
              <a href="#ubah-profil">Ubah Data Profil</a>
              <a href="#unduh-cv">Unduh CV</a>
              <a href="#bagikan-profil">Bagikan Profil</a>
            </div>
          </div>
        </div>

        <h2 class="profile-name"><?php echo htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="profile-email"><?php echo htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8'); ?></div>

        <p class="profile-bio" id="profileBioText"
           data-short="<?php echo htmlspecialchars($profile['bio_short'], ENT_QUOTES, 'UTF-8'); ?>"
           data-full="<?php echo htmlspecialchars($profile['bio_full'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo htmlspecialchars($profile['bio_short'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <button type="button" class="btn-read-more" id="toggleBioBtn">Tampilkan Lebih Banyak</button>
      </div>

      <!-- Lihat Informasi Biodata -->
      <div class="biodata-bar">
        <a href="#biodata" class="biodata-link">Lihat Informasi Biodata</a>
      </div>
    </section>

    <div class="section-spacer"></div>

    <!-- Pengalaman Kerja Card -->
    <section class="experience-card">
      <div class="experience-header">
        <h3 class="experience-title">Pengalaman Kerja</h3>
        <button type="button" class="btn-add-exp" aria-label="Tambah Pengalaman Kerja">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
      </div>

      <ul class="experience-list">
        <?php foreach ($experiences as $exp): ?>
          <li class="experience-item">
            <div class="exp-icon-box" aria-hidden="true">
              <img src="assets/profile-briefcase-icon.png" alt="" />
            </div>
            <div class="exp-content">
              <div class="exp-role-row">
                <span class="exp-role"><?php echo htmlspecialchars($exp['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if (!empty($exp['verified'])): ?>
                  <span class="verified-badge" title="Terverifikasi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                  </span>
                <?php endif; ?>
              </div>
              <div class="exp-company"><?php echo htmlspecialchars($exp['company'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="exp-period"><?php echo htmlspecialchars($exp['period'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="exp-location"><?php echo htmlspecialchars($exp['location'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <div class="section-spacer"></div>

    <!-- Pelatihan Card -->
    <section class="section-card">
      <div class="experience-header">
        <h3 class="experience-title">Pelatihan</h3>
        <button type="button" class="btn-add-exp" aria-label="Tambah Pelatihan">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
      </div>

      <ul class="experience-list">
        <?php foreach ($trainings as $tr): ?>
          <li class="section-item">
            <div class="exp-icon-box" aria-hidden="true">
              <img src="assets/profile-pelatihan-icon.png" alt="" />
            </div>
            <div class="exp-content">
              <div class="exp-role-row">
                <span class="exp-role"><?php echo htmlspecialchars($tr['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if (!empty($tr['verified'])): ?>
                  <span class="verified-badge" title="Terverifikasi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                  </span>
                <?php endif; ?>
              </div>
              <div class="training-category"><?php echo htmlspecialchars($tr['category'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="training-provider"><?php echo htmlspecialchars($tr['provider'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="training-period"><?php echo htmlspecialchars($tr['period'], ENT_QUOTES, 'UTF-8'); ?></div>
              <p class="training-desc"><?php echo htmlspecialchars($tr['description'], ENT_QUOTES, 'UTF-8'); ?></p>
              <a href="#sertifikat-pelatihan" class="attachment-box">
                <img src="assets/profile-doc-icon.png" alt="" />
                <span><?php echo htmlspecialchars($tr['certificate_label'], ENT_QUOTES, 'UTF-8'); ?></span>
              </a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <div class="section-spacer"></div>

    <!-- Pendidikan Card -->
    <section class="section-card">
      <div class="experience-header">
        <h3 class="experience-title">Pendidikan</h3>
        <button type="button" class="btn-add-exp" aria-label="Tambah Pendidikan">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
      </div>

      <ul class="experience-list">
        <?php foreach ($educations as $edu): ?>
          <li class="section-item">
            <div class="exp-icon-box" aria-hidden="true">
              <img src="assets/profile-pendidikan-icon.png" alt="" />
            </div>
            <div class="exp-content">
              <div class="exp-role-row">
                <span class="exp-role"><?php echo htmlspecialchars($edu['school'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if (!empty($edu['verified'])): ?>
                  <span class="verified-badge" title="Terverifikasi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                  </span>
                <?php endif; ?>
              </div>
              <div class="edu-degree">
                <span><?php echo htmlspecialchars($edu['degree'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="edu-bullet">•</span>
                <span><?php echo htmlspecialchars($edu['gpa'], ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="edu-period"><?php echo htmlspecialchars($edu['period'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="edu-location"><?php echo htmlspecialchars($edu['location'], ENT_QUOTES, 'UTF-8'); ?></div>
              <a href="#ijasah-pendidikan" class="attachment-box">
                <img src="assets/profile-doc-icon.png" alt="" />
                <span><?php echo htmlspecialchars($edu['certificate_label'], ENT_QUOTES, 'UTF-8'); ?></span>
              </a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <div class="section-spacer"></div>

    <!-- Sertifikasi Card -->
    <section class="certification-card">
      <div class="experience-header">
        <h3 class="experience-title">Sertifikasi</h3>
        <button type="button" class="btn-add-exp" aria-label="Tambah Sertifikasi">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
      </div>

      <?php if (empty($certifications)): ?>
        <div class="empty-state">
          <img src="assets/profile-empty-sertifikasi.png" alt="Tidak ada sertifikasi" class="empty-state-img" />
          <h4 class="empty-state-title">Tidak ada sertifikasi</h4>
          <p class="empty-state-sub">Silahkan lengkapi data sertifikasi kamu, data sertifikasi ini akan menjadi kelengkapan profilmu.</p>
        </div>
      <?php endif; ?>
    </section>

    <div class="section-spacer"></div>

    <!-- Pencapaian Card -->
    <section class="certification-card">
      <div class="experience-header">
        <h3 class="experience-title">Pencapaian</h3>
        <button type="button" class="btn-add-exp" aria-label="Tambah Pencapaian">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
      </div>

      <?php if (empty($achievements)): ?>
        <div class="empty-state">
          <img src="assets/profile-empty-sertifikasi.png" alt="Tidak ada data pencapaian" class="empty-state-img" />
          <h4 class="empty-state-title">Tidak ada data pencapaian</h4>
          <p class="empty-state-sub">Silahkan lengkapi data pencapaian kamu, data pencapaian ini akan menjadi kelengkapan profilmu.</p>
        </div>
      <?php endif; ?>
    </section>

    <div class="section-spacer"></div>

    <!-- Keahlian Card -->
    <section class="skills-card">
      <div class="experience-header">
        <h3 class="experience-title">Keahlian</h3>
        <button type="button" class="btn-add-exp" aria-label="Tambah Keahlian">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
      </div>

      <div class="skills-list">
        <?php foreach ($skills as $skill): ?>
          <span class="skill-pill"><?php echo htmlspecialchars($skill, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
      </div>
    </section>

    <div class="section-spacer"></div>

    <!-- Bahasa Card -->
    <section class="language-card">
      <div class="experience-header">
        <h3 class="experience-title">Bahasa</h3>
        <button type="button" class="btn-add-exp" aria-label="Tambah Bahasa">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
      </div>

      <ul class="language-list">
        <?php foreach ($languages as $lang): ?>
          <li class="language-item">
            <div class="language-info">
              <div class="language-name"><?php echo htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="language-level"><?php echo htmlspecialchars($lang['level'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <button type="button" class="btn-edit-item" aria-label="Ubah <?php echo htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8'); ?>">
              <img src="assets/profile-edit-icon.png" alt="" />
            </button>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </div>

  <script>
    (function () {
      var bioEl = document.getElementById('profileBioText');
      var toggleBtn = document.getElementById('toggleBioBtn');
      var expanded = false;

      if (bioEl && toggleBtn) {
        toggleBtn.addEventListener('click', function () {
          expanded = !expanded;
          bioEl.textContent = expanded
            ? bioEl.getAttribute('data-full')
            : bioEl.getAttribute('data-short');
          toggleBtn.textContent = expanded
            ? 'Tampilkan Lebih Sedikit'
            : 'Tampilkan Lebih Banyak';
        });
      }

      var editWrap = document.getElementById('editProfilWrap');
      var editBtn = document.getElementById('editProfilBtn');
      var editDropdown = document.getElementById('editDropdown');

      if (editWrap && editBtn && editDropdown) {
        editBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          var isOpen = editDropdown.classList.toggle('open');
          editBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
          if (!editWrap.contains(e.target)) {
            editDropdown.classList.remove('open');
            editBtn.setAttribute('aria-expanded', 'false');
          }
        });
      }
    })();
  </script>
</body>
</html>
