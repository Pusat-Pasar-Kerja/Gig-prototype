<?php
/**
 * seed-database.php  —  One-shot database seeder for the Gig Worker prototype.
 * Safe to run multiple times (INSERT IGNORE). No framework dependencies.
 * Visit this page once in a browser to populate all demo data into MySQL.
 */
declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'Gig');

$results = [];
function log_r(string $label, bool $ok, string $detail = ''): void {
    global $results;
    $results[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
}

/* ── Connect ────────────────────────────────────────────────────────────────── */
try {
    $srv = new PDO('mysql:host='.DB_HOST.';charset=utf8mb4', DB_USER, DB_PASS,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
    $srv->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    log_r('Database connection', true, 'Connected to `'.DB_NAME.'`');
} catch (Throwable $e) {
    die('<h2 style="color:red;font-family:sans-serif">DB connection failed: '.htmlspecialchars($e->getMessage()).'</h2>');
}

/* ── DDL helper ─────────────────────────────────────────────────────────────── */
function ddl(PDO $pdo, string $label, string $sql): void {
    try { $pdo->exec($sql); log_r($label, true); }
    catch (Throwable $e) { log_r($label, false, $e->getMessage()); }
}

/* ── INSERT helper ──────────────────────────────────────────────────────────── */
function ins(PDO $pdo, string $table, string $sql, array $rows): void {
    $ins = $skip = 0;
    try {
        $st = $pdo->prepare($sql);
        foreach ($rows as $r) {
            try { $st->execute($r); $ins += $st->rowCount(); }
            catch (Throwable $ignored) { $skip++; }
        }
        log_r("INSERT $table", true, "$ins inserted, $skip already existed");
    } catch (Throwable $e) { log_r("INSERT $table", false, $e->getMessage()); }
}

/* ══════════════════════════════════════════════════════════════════════════════
   1. CREATE TABLES
   ══════════════════════════════════════════════════════════════════════════════ */

ddl($pdo, 'CREATE project_history', "CREATE TABLE IF NOT EXISTS `project_history` (
    `contract_id` VARCHAR(50) NOT NULL PRIMARY KEY, `worker_id` VARCHAR(50) NOT NULL,
    `worker_name` VARCHAR(100) NOT NULL DEFAULT '', `worker_role` VARCHAR(150) NOT NULL DEFAULT '',
    `worker_avatar` VARCHAR(500) NOT NULL DEFAULT '', `employer_username` VARCHAR(100) NOT NULL,
    `project_title` VARCHAR(255) NOT NULL, `status` VARCHAR(20) NOT NULL DEFAULT 'completed',
    `budget` VARCHAR(50) NOT NULL DEFAULT '', `duration` VARCHAR(50) NOT NULL DEFAULT '',
    `start_date` VARCHAR(40) NOT NULL DEFAULT '', `end_date` VARCHAR(40) NOT NULL DEFAULT '',
    `summary` TEXT NOT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_w` (`worker_id`), KEY `idx_e` (`employer_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

ddl($pdo, 'CREATE project_reviews', "CREATE TABLE IF NOT EXISTS `project_reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `contract_id` VARCHAR(50) NOT NULL,
    `worker_id` VARCHAR(50) NOT NULL, `employer_username` VARCHAR(100) NOT NULL,
    `project_title` VARCHAR(255) NOT NULL, `overall_rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `rating_quality` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `rating_communication` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `rating_timeliness` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `comment` TEXT NOT NULL, `badges` TEXT NOT NULL DEFAULT '',
    `recommend_worker` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_contract` (`contract_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

ddl($pdo, 'CREATE project_completions', "CREATE TABLE IF NOT EXISTS `project_completions` (
    `contract_id` VARCHAR(50) NOT NULL PRIMARY KEY, `worker_id` VARCHAR(50) NOT NULL,
    `employer_username` VARCHAR(100) NOT NULL, `rating_given` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `review_given` TEXT NOT NULL, `completed_date` DATE NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

ddl($pdo, 'CREATE project_applications', "CREATE TABLE IF NOT EXISTS `project_applications` (
    `id` VARCHAR(100) NOT NULL PRIMARY KEY, `vacancy_id` VARCHAR(50) NOT NULL,
    `worker_id` VARCHAR(50) NOT NULL, `worker_name` VARCHAR(100) NOT NULL,
    `employer_username` VARCHAR(100) NOT NULL, `bid_amount` VARCHAR(50) NOT NULL DEFAULT '',
    `status` VARCHAR(40) NOT NULL DEFAULT 'applied', `note` TEXT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_vac` (`vacancy_id`), KEY `idx_wkr` (`worker_id`), KEY `idx_emp` (`employer_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

ddl($pdo, 'CREATE project_offers', "CREATE TABLE IF NOT EXISTS `project_offers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `employer_username` VARCHAR(100) NOT NULL,
    `worker_id` VARCHAR(50) NOT NULL, `vacancy_id` VARCHAR(50) NOT NULL,
    `message` VARCHAR(500) NOT NULL DEFAULT '', `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_offer` (`employer_username`,`worker_id`,`vacancy_id`),
    KEY `idx_wkr` (`worker_id`), KEY `idx_vac` (`vacancy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

ddl($pdo, 'CREATE employer_notifications', "CREATE TABLE IF NOT EXISTS `employer_notifications` (
    `id` VARCHAR(100) NOT NULL PRIMARY KEY, `employer_username` VARCHAR(100) NOT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'info', `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL, `vacancy_id` VARCHAR(50) NOT NULL DEFAULT '',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_emp` (`employer_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
try { $pdo->exec("ALTER TABLE `employer_notifications` ADD COLUMN `vacancy_id` VARCHAR(50) NOT NULL DEFAULT '' AFTER `message`"); } catch(Throwable $ignored) {}

ddl($pdo, 'CREATE worker_notifications', "CREATE TABLE IF NOT EXISTS `worker_notifications` (
    `id` VARCHAR(100) NOT NULL PRIMARY KEY, `worker_id` VARCHAR(50) NOT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'info', `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL, `vacancy_id` VARCHAR(50) NOT NULL DEFAULT '',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_wkr` (`worker_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
try { $pdo->exec("ALTER TABLE `worker_notifications` ADD COLUMN `vacancy_id` VARCHAR(50) NOT NULL DEFAULT '' AFTER `message`"); } catch(Throwable $ignored) {}

ddl($pdo, 'CREATE gig_worker_registrations', "CREATE TABLE IF NOT EXISTS `gig_worker_registrations` (
    `username` VARCHAR(100) NOT NULL PRIMARY KEY, `bidang_keahlian` VARCHAR(150) NOT NULL,
    `skills` TEXT NOT NULL, `contact_choice` VARCHAR(20) NOT NULL DEFAULT 'siapkerja',
    `contact_email` VARCHAR(150) NOT NULL, `contact_wa` VARCHAR(50) NOT NULL,
    `previous_projects` LONGTEXT NOT NULL, `portfolio` LONGTEXT NOT NULL,
    `video_url` VARCHAR(500) NOT NULL, `status` VARCHAR(20) NOT NULL DEFAULT 'approved',
    `admin_note` TEXT NOT NULL, `reviewed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
try { $pdo->exec("ALTER TABLE `gig_worker_registrations` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'approved' AFTER `video_url`"); } catch(Throwable $ignored) {}
try { $pdo->exec("ALTER TABLE `gig_worker_registrations` ADD COLUMN `admin_note` TEXT NOT NULL AFTER `status`"); } catch(Throwable $ignored) {}
try { $pdo->exec("ALTER TABLE `gig_worker_registrations` ADD COLUMN `reviewed_at` DATETIME NULL AFTER `admin_note`"); } catch(Throwable $ignored) {}

require_once __DIR__ . '/includes/admin-store.php';
gig_admin_ensure_schema($pdo);
gig_admin_seed_workers($pdo);

/* ══════════════════════════════════════════════════════════════════════════════
   2. project_history
   ══════════════════════════════════════════════════════════════════════════════ */
$TESSA_AVT = 'https://api.dicebear.com/9.x/notionists/svg?seed=Theressa&backgroundColor=dbeafe';
ins($pdo, 'project_history',
"INSERT IGNORE INTO `project_history`
  (`contract_id`,`worker_id`,`worker_name`,`worker_role`,`worker_avatar`,
   `employer_username`,`project_title`,`status`,`budget`,`duration`,
   `start_date`,`end_date`,`summary`,`created_at`)
VALUES (:cid,:wid,:wn,:wr,:av,:emp,:ttl,:st,:bgt,:dur,:sd,:ed,:sum,:cat)",
[
  [':cid'=>'CTR-GIG-2026-0815',':wid'=>'tessa',':wn'=>'Theressa Zaratrusha',':wr'=>'Lead UI/UX Designer',':av'=>$TESSA_AVT,
   ':emp'=>'PT ABC',':ttl'=>'Prototype Dashboard Internal',':st'=>'completed',':bgt'=>'Rp 8.000.000',':dur'=>'3 Minggu',
   ':sd'=>'01 Agu 2026',':ed'=>'22 Agu 2026',':sum'=>'Prototype dashboard internal dan pengujian alur kerja tim pemberi kerja.',':cat'=>'2026-08-22 10:00:00'],
  [':cid'=>'CTR-GIG-2026-0624',':wid'=>'tessa',':wn'=>'Theressa Zaratrusha',':wr'=>'Lead UI/UX Designer',':av'=>$TESSA_AVT,
   ':emp'=>'PT Talenta Nusantara',':ttl'=>'Portal Rekrutmen BUMN',':st'=>'completed',':bgt'=>'Rp 12.000.000',':dur'=>'6 Bulan',
   ':sd'=>'01 Jan 2026',':ed'=>'24 Jun 2026',':sum'=>'High-fidelity mockup desktop/mobile dan panduan interaksi portal rekrutmen.',':cat'=>'2026-06-24 14:10:00'],
  [':cid'=>'CTR-GIG-2026-0531',':wid'=>'tessa',':wn'=>'Theressa Zaratrusha',':wr'=>'Lead UI/UX Designer',':av'=>$TESSA_AVT,
   ':emp'=>'CV Kreasi Digital',':ttl'=>'Redesign Aplikasi Lowongan',':st'=>'completed',':bgt'=>'Rp 7.500.000',':dur'=>'1 Bulan',
   ':sd'=>'01 Mei 2026',':ed'=>'31 Mei 2026',':sum'=>'Perbaikan alur pencarian lowongan dan uji keterbacaan untuk pengguna baru.',':cat'=>'2026-05-31 16:00:00'],
  [':cid'=>'CTR-GIG-2026-0128',':wid'=>'tessa',':wn'=>'Theressa Zaratrusha',':wr'=>'Lead UI/UX Designer',':av'=>$TESSA_AVT,
   ':emp'=>'Yayasan Kerja Adil',':ttl'=>'Landing Page Program Pelatihan',':st'=>'completed',':bgt'=>'Rp 4.500.000',':dur'=>'3 Minggu',
   ':sd'=>'06 Jan 2026',':ed'=>'28 Jan 2026',':sum'=>'Landing page kampanye pelatihan dan aset visual pendukung.',':cat'=>'2026-01-28 11:30:00'],
  [':cid'=>'CTR-GIG-2025-1120',':wid'=>'tessa',':wn'=>'Theressa Zaratrusha',':wr'=>'Lead UI/UX Designer',':av'=>$TESSA_AVT,
   ':emp'=>'Startup Ketenagakerjaan',':ttl'=>'Aplikasi Pelaporan Pekerja Lepas',':st'=>'cancelled',':bgt'=>'Rp 9.000.000',':dur'=>'2 Bulan',
   ':sd'=>'01 Nov 2025',':ed'=>'20 Nov 2025',':sum'=>'Kontrak dihentikan bersama karena perubahan ruang lingkup produk.',':cat'=>'2025-11-20 09:00:00'],
  // Active contracts
  [':cid'=>'CTR-GIG-2026-0811',':wid'=>'theressaz@pasker.id',':wn'=>'Theressa Zaratrusha',':wr'=>'Lead UI/UX Designer',':av'=>$TESSA_AVT,
   ':emp'=>'PT Talenta Digital Indonesia',':ttl'=>'Redesign UI/UX Dashboard Prototype KarirHub',':st'=>'active',
   ':bgt'=>'Rp 8.500.000',':dur'=>'3 Minggu',':sd'=>'20 Sep 2026',':ed'=>'11 Okt 2026',
   ':sum'=>'Redesign dan prototyping UI/UX dashboard KarirHub dengan tampilan modern.',':cat'=>'2026-09-20 11:13:00'],
  [':cid'=>'CTR-GIG-2026-0905',':wid'=>'theressaz@pasker.id',':wn'=>'Theressa Zaratrusha',':wr'=>'Lead UI/UX Designer',':av'=>$TESSA_AVT,
   ':emp'=>'CV Visual Studio Creative',':ttl'=>'Desain UI/UX Mobile App E-Commerce UMKM',':st'=>'active',
   ':bgt'=>'Rp 6.500.000',':dur'=>'2 Minggu',':sd'=>'22 Sep 2026',':ed'=>'06 Okt 2026',
   ':sum'=>'Penyusunan wireframe dan high fidelity mockup aplikasi mobile e-commerce UMKM.',':cat'=>'2026-09-22 10:00:00'],
  [':cid'=>'CTR-GIG-2026-0912',':wid'=>'theressaz@pasker.id',':wn'=>'Theressa Zaratrusha',':wr'=>'Lead UI/UX Designer',':av'=>$TESSA_AVT,
   ':emp'=>'PT Nusantara Media Technologi',':ttl'=>'Audit Design System & Aksesibilitas Web Portal',':st'=>'active',
   ':bgt'=>'Rp 5.000.000',':dur'=>'10 Hari',':sd'=>'24 Sep 2026',':ed'=>'04 Okt 2026',
   ':sum'=>'Evaluasi kontras warna, WCAG 2.1, dan audit token komponen UI design system.',':cat'=>'2026-09-24 14:00:00'],
  [':cid'=>'CTR-GIG-2026-0819',':wid'=>'rian',':wn'=>'Rian Ardiansyah',':wr'=>'Backend API Developer',
   ':av'=>'https://api.dicebear.com/9.x/notionists/svg?seed=Rian&backgroundColor=ffedd5',
   ':emp'=>'PT Solusi Awan Indonesia',':ttl'=>'Integrasi REST API Modul Notifikasi SMS & WhatsApp',':st'=>'active',
   ':bgt'=>'Rp 6.000.000',':dur'=>'2 Minggu',':sd'=>'19 Sep 2026',':ed'=>'03 Okt 2026',
   ':sum'=>'Integrasi REST API modul notifikasi SMS dan WhatsApp ke core sistem.',':cat'=>'2026-09-19 09:00:00'],
  // Other workers
  [':cid'=>'CTR-GIG-2026-0640',':wid'=>'siti',':wn'=>'Siti Nurhaliza',':wr'=>'Social Media Specialist & Copywriter',
   ':av'=>'https://api.dicebear.com/9.x/notionists/svg?seed=Siti&backgroundColor=ede9fe',
   ':emp'=>'PT ABC',':ttl'=>'Pembuatan Landing Page Kampanye Edukasi Karir',':st'=>'completed',
   ':bgt'=>'Rp 4.500.000',':dur'=>'1 Bulan',':sd'=>'01 Jul 2026',':ed'=>'01 Agu 2026',
   ':sum'=>'Penulisan naskah landing page dan pembuatan 20 aset visual media sosial.',':cat'=>'2026-08-01 10:00:00'],
  [':cid'=>'CTR-GIG-2026-0512',':wid'=>'dimas',':wn'=>'Dimas Prasetyo',':wr'=>'Cloud API Engineer',
   ':av'=>'https://api.dicebear.com/9.x/notionists/svg?seed=Dimas&backgroundColor=ffedd5',
   ':emp'=>'PT ABC',':ttl'=>'Audit Keamanan & PenTesting Microservice Gateway',':st'=>'completed',
   ':bgt'=>'Rp 7.000.000',':dur'=>'2 Minggu',':sd'=>'10 Mei 2026',':ed'=>'24 Mei 2026',
   ':sum'=>'Laporan uji keamanan vulnerability assessment dan patching celah API.',':cat'=>'2026-05-24 15:00:00'],
  [':cid'=>'CTR-GIG-2026-0305',':wid'=>'budi',':wn'=>'Budi Wicaksono',':wr'=>'UI Designer / Technical Analyst',
   ':av'=>'https://api.dicebear.com/9.x/notionists/svg?seed=Budi&backgroundColor=d1fae5',
   ':emp'=>'PT ABC',':ttl'=>'Migrasi Database Legacy ke PostgreSQL',':st'=>'cancelled',
   ':bgt'=>'Rp 5.000.000',':dur'=>'2 Minggu',':sd'=>'01 Mar 2026',':ed'=>'08 Mar 2026',
   ':sum'=>'Proyek dibatalkan bersama karena perubahan spesifikasi arsitektur internal.',':cat'=>'2026-03-08 12:00:00'],
]);

/* ══════════════════════════════════════════════════════════════════════════════
   3. project_reviews
   ══════════════════════════════════════════════════════════════════════════════ */
ins($pdo, 'project_reviews',
"INSERT IGNORE INTO `project_reviews`
  (`contract_id`,`worker_id`,`employer_username`,`project_title`,
   `overall_rating`,`rating_quality`,`rating_communication`,`rating_timeliness`,
   `comment`,`badges`,`recommend_worker`,`created_at`)
VALUES (:cid,:wid,:emp,:ttl,:rat,:rat,:rat,:rat,:cmt,:bdg,1,:cat)",
[
  [':cid'=>'CTR-GIG-2026-0815',':wid'=>'tessa',':emp'=>'PT ABC',':ttl'=>'Prototype Dashboard Internal',
   ':rat'=>5,':cmt'=>'Hasil desain rapi, komunikatif, dan tepat waktu. Prototype mudah diuji tim internal.',
   ':bdg'=>'Tepat Waktu||Komunikatif',':cat'=>'2026-08-22 10:00:00'],
  [':cid'=>'CTR-GIG-2026-0624',':wid'=>'tessa',':emp'=>'PT Talenta Nusantara',':ttl'=>'Portal Rekrutmen BUMN',
   ':rat'=>5,':cmt'=>'Hasil desain rapi, komunikatif, dan tepat waktu. Prototype mudah diuji tim internal.',
   ':bdg'=>'Berkualitas Tinggi||Tepat Waktu',':cat'=>'2026-06-24 14:10:00'],
  [':cid'=>'CTR-GIG-2026-0531',':wid'=>'tessa',':emp'=>'CV Kreasi Digital',':ttl'=>'Redesign Aplikasi Lowongan',
   ':rat'=>5,':cmt'=>'Sangat memahami kebutuhan pengguna awam. Iterasi cepat setelah umpan balik.',
   ':bdg'=>'Responsif||Inovatif',':cat'=>'2026-05-31 16:00:00'],
  [':cid'=>'CTR-GIG-2026-0128',':wid'=>'tessa',':emp'=>'Yayasan Kerja Adil',':ttl'=>'Landing Page Program Pelatihan',
   ':rat'=>5,':cmt'=>'Visual konsisten dan aksesibel. Direkomendasikan untuk proyek pemerintahan.',
   ':bdg'=>'Direkomendasikan',':cat'=>'2026-01-28 11:30:00'],
  [':cid'=>'CTR-GIG-2026-0640',':wid'=>'siti',':emp'=>'PT ABC',':ttl'=>'Pembuatan Landing Page Kampanye Edukasi Karir',
   ':rat'=>5,':cmt'=>'Hasil pekerjaan luar biasa! Copywriting komunikatif dan desain landing page meningkatkan konversi.',
   ':bdg'=>'Berkualitas Tinggi||Kreatif',':cat'=>'2026-08-01 10:00:00'],
  [':cid'=>'CTR-GIG-2026-0512',':wid'=>'dimas',':emp'=>'PT ABC',':ttl'=>'Audit Keamanan & PenTesting Microservice Gateway',
   ':rat'=>5,':cmt'=>'Penetrasi testing komprehensif, laporan celah keamanan sangat lengkap dan solutif.',
   ':bdg'=>'Ahli Teknis||Detail',':cat'=>'2026-05-24 15:00:00'],
]);

/* ══════════════════════════════════════════════════════════════════════════════
   4. project_completions
   ══════════════════════════════════════════════════════════════════════════════ */
ins($pdo, 'project_completions',
"INSERT IGNORE INTO `project_completions`
  (`contract_id`,`worker_id`,`employer_username`,`rating_given`,`review_given`,`completed_date`,`created_at`)
VALUES (:cid,:wid,:emp,:rat,:rev,:cdt,:cat)",
[
  [':cid'=>'CTR-GIG-2026-0815',':wid'=>'tessa',':emp'=>'PT ABC',':rat'=>5,
   ':rev'=>'Hasil desain rapi, komunikatif, dan tepat waktu.',':cdt'=>'2026-08-22',':cat'=>'2026-08-22 10:00:00'],
  [':cid'=>'CTR-GIG-2026-0624',':wid'=>'tessa',':emp'=>'PT Talenta Nusantara',':rat'=>5,
   ':rev'=>'Hasil desain rapi, komunikatif, dan tepat waktu.',':cdt'=>'2026-06-24',':cat'=>'2026-06-24 14:10:00'],
  [':cid'=>'CTR-GIG-2026-0531',':wid'=>'tessa',':emp'=>'CV Kreasi Digital',':rat'=>5,
   ':rev'=>'Sangat memahami kebutuhan pengguna awam. Iterasi cepat setelah umpan balik.',':cdt'=>'2026-05-31',':cat'=>'2026-05-31 16:00:00'],
  [':cid'=>'CTR-GIG-2026-0128',':wid'=>'tessa',':emp'=>'Yayasan Kerja Adil',':rat'=>5,
   ':rev'=>'Visual konsisten dan aksesibel. Direkomendasikan untuk proyek pemerintahan.',':cdt'=>'2026-01-28',':cat'=>'2026-01-28 11:30:00'],
  [':cid'=>'CTR-GIG-2026-0640',':wid'=>'siti',':emp'=>'PT ABC',':rat'=>5,
   ':rev'=>'Hasil pekerjaan luar biasa! Copywriting komunikatif dan desain landing page meningkatkan konversi.',':cdt'=>'2026-08-01',':cat'=>'2026-08-01 10:00:00'],
  [':cid'=>'CTR-GIG-2026-0512',':wid'=>'dimas',':emp'=>'PT ABC',':rat'=>5,
   ':rev'=>'Penetrasi testing komprehensif, laporan celah keamanan sangat lengkap dan solutif.',':cdt'=>'2026-05-24',':cat'=>'2026-05-24 15:00:00'],
]);

/* ══════════════════════════════════════════════════════════════════════════════
   5. project_applications
   ══════════════════════════════════════════════════════════════════════════════ */
ins($pdo, 'project_applications',
"INSERT IGNORE INTO `project_applications`
  (`id`,`vacancy_id`,`worker_id`,`worker_name`,`employer_username`,
   `bid_amount`,`status`,`note`,`created_at`,`updated_at`)
VALUES (:id,:vac,:wid,:wn,:emp,:bid,:st,:nt,:cat,:uat)",
[
  [':id'=>'APP-2026-001',':vac'=>'GIG-2026-09-001',':wid'=>'tessa',':wn'=>'Theressa Zaratrusha',
   ':emp'=>'PT Talenta Digital Indonesia',':bid'=>'Rp 8.500.000',':st'=>'confirmed_by_worker',
   ':nt'=>'Saya memiliki pengalaman 4+ tahun dalam merancang UI/UX dashboard SaaS.',
   ':cat'=>'2026-09-18 09:20:00',':uat'=>'2026-09-20 11:13:00'],
  [':id'=>'APP-2026-002',':vac'=>'GIG-2026-09-002',':wid'=>'rian',':wn'=>'Rian Ardiansyah',
   ':emp'=>'PT Solusi Awan Indonesia',':bid'=>'Rp 6.000.000',':st'=>'confirmed_by_worker',
   ':nt'=>'Siap mengintegrasikan REST API SMS & WA dengan sertifikasi AWS Backend.',
   ':cat'=>'2026-09-16 10:00:00',':uat'=>'2026-09-19 09:00:00'],
  [':id'=>'APP-2026-003',':vac'=>'GIG-2026-09-003',':wid'=>'fajar',':wn'=>'Fajar Pratama',
   ':emp'=>'PT Media Digital Nusantara',':bid'=>'Rp 4.500.000',':st'=>'applied',
   ':nt'=>'Portofolio kampanye copywriting sosial media dengan engagement rate > 8%.',
   ':cat'=>'2026-09-21 08:30:00',':uat'=>'2026-09-21 08:30:00'],
  [':id'=>'APP-2026-004',':vac'=>'GIG-2026-09-007',':wid'=>'tessa',':wn'=>'Theressa Zaratrusha',
   ':emp'=>'PT Talenta Digital Indonesia',':bid'=>'Rp 12.000.000',':st'=>'applied',
   ':nt'=>'Berpengalaman dalam React Native untuk e-commerce dengan integrasi payment gateway.',
   ':cat'=>'2026-09-25 10:00:00',':uat'=>'2026-09-25 10:00:00'],
]);

/* ══════════════════════════════════════════════════════════════════════════════
   6. project_offers
   ══════════════════════════════════════════════════════════════════════════════ */
ins($pdo, 'project_offers',
"INSERT IGNORE INTO `project_offers`
  (`employer_username`,`worker_id`,`vacancy_id`,`message`,`status`,`created_at`)
VALUES (:emp,:wid,:vac,:msg,:st,:cat)",
[
  [':emp'=>'PT ABC',':wid'=>'tessa',':vac'=>'GIG-2026-09-001',
   ':msg'=>'Kami tertarik dengan profil Anda dan mengundang Anda untuk bergabung dalam proyek ini.',
   ':st'=>'pending',':cat'=>'2026-09-15 09:00:00'],
  [':emp'=>'PT ABC',':wid'=>'tessa',':vac'=>'GIG-2026-09-002',
   ':msg'=>'Proyek ini cocok dengan keahlian Anda. Silakan tinjau dan hubungi kami.',
   ':st'=>'pending',':cat'=>'2026-09-16 10:00:00'],
  [':emp'=>'CV Kreasi Visual Nusantara',':wid'=>'tessa',':vac'=>'GIG-2026-09-008',
   ':msg'=>'Kami mengundang Anda untuk proyek desain branding UMKM kami.',
   ':st'=>'pending',':cat'=>'2026-09-18 14:00:00'],
]);

/* ══════════════════════�[];═══════════════════════════════════════════════════════════════
   8. worker_notifications
   ══════════════════════════════════════════════════════════════════════════════ */
ins($pdo, 'worker_notifications',
"INSERT IGNORE INTO `worker_notifications`
  (`id`,`worker_id`,`type`,`title`,`message`,`vacancy_id`,`is_read`,`created_at`)
VALUES (:id,:wid,:tp,:ttl,:msg,:vid,:rd,:cat)",
[
  [':id'=>'WNOTIF-2026-002',':wid'=>'theressaz@pasker.id',':tp'=>'direct_offer',
   ':ttl'=>'Penawaran Proyek Baru!',
   ':msg'=>'employer@pasker.id menawarkan proyek "Redesign UI/UX Dashboard Prototype KarirHub" secara langsung kepada Anda. Buka menu Penawaran Proyek untuk meninjau rincian proyek.',
   ':vid'=>'GIG-2026-09-001',':rd'=>0,':cat'=>'2026-09-15 09:00:00'],
  [':id'=>'WNOTIF-2026-008',':wid'=>'theressaz@pasker.id',':tp'=>'direct_offer',
   ':ttl'=>'Penawaran Proyek Baru!',
   ':msg'=>'employer@pasker.id menawarkan proyek "Integrasi REST API Modul Notifikasi SMS & WhatsApp" secara langsung kepada Anda. Buka menu Penawaran Proyek untuk meninjau rincian proyek.',
   ':vid'=>'GIG-2026-09-002',':rd'=>0,':cat'=>'2026-09-16 10:00:00'],
  [':id'=>'WNOTIF-2026-005',':wid'=>'theressaz@pasker.id',':tp'=>'direct_offer',
   ':ttl'=>'Penawaran Proyek dari CV Kreasi Visual Nusantara!',
   ':msg'=>'CV Kreasi Visual Nusantara menawarkan proyek "Desain Visual Asset & Branding Kit UMKM Go Digital" secara langsung. Buka menu Penawaran Proyek untuk meninjau rincian.',
   ':vid'=>'GIG-2026-09-008',':rd'=>0,':cat'=>'2026-09-18 14:00:00'],
]);

/* ══════════════════════════════════════════════════════════════════════════════
   9. Migrate legacy worker_id values
   ══════════════════════════════════════════════════════════════════════════════ */
$legacy = "'tess.kirana@pasker.id','theressaz','theressa zaratrusha','tessa kirana','tessa'";
foreach (['project_history','project_reviews','project_completions','project_offers','project_applications','worker_notifications'] as $t) {
    try {
        $n = $pdo->exec("UPDATE `$t` SET `worker_id`='theressaz@pasker.id' WHERE (LOWER(TRIM(`worker_id`)) IN ($legacy) OR `worker_id`='tessa') AND `worker_id`!='theressaz@pasker.id'");
        if ($n > 0) log_r("Migrate legacy IDs in $t", true, "$n row(s) updated");
    } catch (Throwable $e2) { log_r("Migrate legacy IDs in $t", false, $e2->getMessage()); }
}

/* ══════════════════════════════════════════════════════════════════════════════
   10. Final row counts
   ══════════════════════════════════════════════════════════════════════════════ */
$tables = ['project_history','project_reviews','project_completions',
           'project_applications','project_offers','employer_notifications','worker_notifications',
           'gig_worker_registrations','worker_profile_edit_requests'];
$counts = [];
foreach ($tables as $t) {
    try { $counts[$t] = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); }
    catch (Throwable $e3) { $counts[$t] = 'error'; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Gig DB Seeder</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',system-ui,sans-serif;background:#f0f4f8;padding:32px}
  h1{color:#1e293b;font-size:1.6rem;margin-bottom:4px}
  .sub{color:#64748b;margin:0 0 28px;font-size:.95rem}
  .card{background:#fff;border-radius:14px;padding:24px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,.06)}
  h2{margin:0 0 14px;font-size:.82rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;font-weight:700}
  table{width:100%;border-collapse:collapse;font-size:.88rem}
  th{background:#f8fafc;padding:9px 14px;text-align:left;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0}
  td{padding:8px 14px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:top}
  .ok{color:#16a34a}.fail{color:#dc2626}
  .badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:.75rem;font-weight:700}
  .badge-ok{background:#dcfce7;color:#166534}.badge-fail{background:#fee2e2;color:#991b1b}
  .count-num{font-weight:800;color:#2563eb;font-size:1.05rem}
  .footer{margin-top:24px;padding:18px 20px;background:#eff6ff;border-radius:12px;color:#1e40af;font-size:.88rem;line-height:1.8}
  .footer a{color:#1d4ed8;font-weight:700;text-decoration:none;margin:0 6px;padding:4px 10px;background:#dbeafe;border-radius:6px}
  .footer a:hover{background:#bfdbfe}
</style>
</head>
<body>
<h1>🌱 Gig Prototype — Database Seeder</h1>
<p class="sub">Populates all demo data into MySQL. Uses INSERT IGNORE — safe to run multiple times.</p>

<div class="card">
  <h2>Operation Log</h2>
  <table>
    <tr><th>Operation</th><th>Status</th><th>Detail</th></tr>
    <?php foreach ($results as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['label']) ?></td>
      <td><span class="badge <?= $r['ok'] ? 'badge-ok' : 'badge-fail' ?>"><?= $r['ok'] ? '✓ OK' : '✗ FAIL' ?></span></td>
      <td style="color:#64748b;font-size:.83rem"><?= htmlspecialchars($r['detail']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="card">
  <h2>Row Counts After Seeding</h2>
  <table>
    <tr><th>Table</th><th>Rows in DB</th></tr>
    <?php foreach ($counts as $t => $n): ?>
    <tr>
      <td style="font-weight:600;color:#1e293b"><?= htmlspecialchars($t) ?></td>
      <td class="count-num"><?= htmlspecialchars((string)$n) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="footer">
  ✅ Seeding complete! All tables are now populated.
  <br>Navigate to:
  <a href="dashboard-worker.php">Worker Dashboard</a>
  <a href="worker-penawaran.php">Penawaran Proyek</a>
  <a href="worker-riwayat.php">Riwayat Proyek</a>
  <a href="worker-tugas.php">Proyek Aktif</a>
</div>
</body>
</html>
