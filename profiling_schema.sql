-- ============================================================================
-- SKEMA DATABASE: PROGRESSIVE PROFILING (PROFILE COMPLETION NUDGE)
-- Database: Gig (MySQL / MariaDB)
-- Terintegrasi penuh dengan halaman profil.php & Pengaturan Admin (1x Sehari)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `Gig`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `Gig`;

-- 1. Tabel `users` (Menyimpan seluruh field profil & waktu kemunculan pop-up harian)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `nik` VARCHAR(30) DEFAULT NULL,
    `birth_place_date` VARCHAR(120) DEFAULT NULL,
    `gender` VARCHAR(30) DEFAULT NULL,
    `marital_status` VARCHAR(40) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `address_ktp` TEXT DEFAULT NULL,
    `domicile` VARCHAR(255) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `linkedin_url` VARCHAR(255) DEFAULT NULL,
    `expected_salary` VARCHAR(60) DEFAULT NULL,
    `work_preference` VARCHAR(60) DEFAULT NULL,
    `work_experience` VARCHAR(255) DEFAULT NULL,
    `training_name` VARCHAR(255) DEFAULT NULL,
    `highest_education` VARCHAR(180) DEFAULT NULL,
    `certification_name` VARCHAR(180) DEFAULT NULL,
    `achievement_name` VARCHAR(180) DEFAULT NULL,
    `skills_text` TEXT DEFAULT NULL,
    `english_level` VARCHAR(80) DEFAULT NULL,
    `mandarin_level` VARCHAR(80) DEFAULT NULL,
    `last_nudge_shown_at` DATETIME DEFAULT NULL COMMENT 'Waktu terakhir pop-up muncul ke user',
    `last_nudge_answered_at` DATETIME DEFAULT NULL COMMENT 'Waktu terakhir user menjawab pop-up',
    `nudge_shown_count_today` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Jumlah kemunculan pop-up hari ini',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel `profiling_settings` (Pengaturan Admin untuk frekuensi kemunculan pop-up)
CREATE TABLE IF NOT EXISTS `profiling_settings` (
    `id` TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Pop-up Aktif, 0 = Nonaktif',
    `frequency_mode` VARCHAR(30) NOT NULL DEFAULT 'once_per_day' COMMENT 'once_per_day, custom_interval, once_per_session, always',
    `interval_hours` INT UNSIGNED NOT NULL DEFAULT 24 COMMENT 'Jeda jam jika frequency_mode = custom_interval',
    `max_questions_per_show` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Jumlah pertanyaan per kemunculan (default 1)',
    `show_fab_button` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Tampilkan tombol floating Lengkapi Profil',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `profiling_settings`
    (`id`, `is_enabled`, `frequency_mode`, `interval_hours`, `max_questions_per_show`, `show_fab_button`)
VALUES
    (1, 1, 'once_per_day', 24, 1, 1);

-- 3. Tabel `profiling_categories` (Master kategori pertanyaan: Biodata, Bahasa, dll.)
CREATE TABLE IF NOT EXISTS `profiling_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nama kategori seperti Biodata, Bahasa, Pendidikan & Pelatihan, Keahlian & Karir',
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel `profiling_questions` (Berelasi ke profiling_categories)
CREATE TABLE IF NOT EXISTS `profiling_questions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `target_column` VARCHAR(64) NOT NULL COMMENT 'Nama kolom di tabel users yang ingin diisi/diverifikasi',
    `question_new` TEXT NOT NULL COMMENT 'Teks pertanyaan jika data di tabel users masih kosong',
    `question_verify` TEXT NOT NULL COMMENT 'Teks pertanyaan verifikasi jika data di tabel users sudah ada',
    `input_type` ENUM('text', 'select', 'tel', 'url') NOT NULL DEFAULT 'text',
    `options_json` TEXT DEFAULT NULL COMMENT 'Opsi pilihan JSON jika input_type = select',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_profiling_questions_category`
        FOREIGN KEY (`category_id`) REFERENCES `profiling_categories` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX `idx_target_column` (`target_column`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
