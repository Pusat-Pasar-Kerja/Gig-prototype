<?php
/**
 * Shared database connection and schema bootstrap.
 * Included once per request. Returns a PDO instance or null if DB is offline.
 */
declare(strict_types=1);

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'Gig');

function gig_db(): ?PDO
{
    static $pdo = null;
    static $tried = false;

    if ($tried) return $pdo;
    $tried = true;

    try {
        $serverDsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
        $server = new PDO($serverDsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE  => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT  => 2,
        ]);
        $server->exec(
            "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT          => 2,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        // --- project_reviews table ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `project_reviews` (
                `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `contract_id`          VARCHAR(50)  NOT NULL,
                `worker_id`            VARCHAR(50)  NOT NULL,
                `employer_username`    VARCHAR(100) NOT NULL,
                `project_title`        VARCHAR(255) NOT NULL,
                `overall_rating`       TINYINT UNSIGNED NOT NULL DEFAULT 5,
                `rating_quality`       TINYINT UNSIGNED NOT NULL DEFAULT 5,
                `rating_communication` TINYINT UNSIGNED NOT NULL DEFAULT 5,
                `rating_timeliness`    TINYINT UNSIGNED NOT NULL DEFAULT 5,
                `comment`              TEXT NOT NULL,
                `badges`               TEXT NOT NULL DEFAULT '',
                `recommend_worker`     TINYINT(1) NOT NULL DEFAULT 1,
                `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_contract` (`contract_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // --- project_completions table ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `project_completions` (
                `contract_id`       VARCHAR(50)  NOT NULL PRIMARY KEY,
                `worker_id`         VARCHAR(50)  NOT NULL,
                `employer_username` VARCHAR(100) NOT NULL,
                `rating_given`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `review_given`      TEXT NOT NULL,
                `completed_date`    DATE NOT NULL,
                `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `project_completion_confirmations` (
                `contract_id`            VARCHAR(50)  NOT NULL PRIMARY KEY,
                `employer_confirmed_at`  DATETIME NULL,
                `employer_confirmed_by`  VARCHAR(100) NOT NULL DEFAULT '',
                `worker_confirmed_at`    DATETIME NULL,
                `worker_confirmed_by`    VARCHAR(100) NOT NULL DEFAULT '',
                `both_confirmed_at`      DATETIME NULL,
                `created_at`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // --- gig_worker_registrations table ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `gig_worker_registrations` (
                `username`          VARCHAR(100) NOT NULL PRIMARY KEY,
                `bidang_keahlian`   VARCHAR(150) NOT NULL,
                `skills`            TEXT NOT NULL,
                `contact_choice`    VARCHAR(20) NOT NULL DEFAULT 'siapkerja',
                `contact_email`     VARCHAR(150) NOT NULL,
                `contact_wa`        VARCHAR(50) NOT NULL,
                `previous_projects` LONGTEXT NOT NULL,
                `portfolio`         LONGTEXT NOT NULL,
                `video_url`         VARCHAR(500) NOT NULL,
                `social_media`      LONGTEXT NOT NULL,
                `status`            VARCHAR(20)  NOT NULL DEFAULT 'pending',
                `admin_note`        TEXT NOT NULL,
                `reviewed_at`       DATETIME NULL,
                `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `project_offers` (
                `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employer_username` VARCHAR(100) NOT NULL,
                `worker_id`         VARCHAR(50)  NOT NULL,
                `vacancy_id`        VARCHAR(50)  NOT NULL,
                `message`           VARCHAR(500) NOT NULL DEFAULT '',
                `status`            VARCHAR(20)  NOT NULL DEFAULT 'pending',
                `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_offer` (`employer_username`, `worker_id`, `vacancy_id`),
                KEY `idx_worker`  (`worker_id`),
                KEY `idx_vacancy` (`vacancy_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // --- worker_profiles table ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `worker_profiles` (
                `id`                 VARCHAR(50)  NOT NULL PRIMARY KEY,
                `name`               VARCHAR(150) NOT NULL,
                `initials`           VARCHAR(10)  NOT NULL DEFAULT '',
                `color`              VARCHAR(30)  NOT NULL DEFAULT '#2563eb',
                `photo`              VARCHAR(500) NOT NULL DEFAULT '',
                `location`           VARCHAR(100) NOT NULL DEFAULT '',
                `rating`             DECIMAL(3,1) NOT NULL DEFAULT 5.0,
                `reviews_count`      INT UNSIGNED NOT NULL DEFAULT 0,
                `completed_projects` INT UNSIGNED NOT NULL DEFAULT 0,
                `total_projects`     INT UNSIGNED NOT NULL DEFAULT 0,
                `verified`           TINYINT(1)   NOT NULL DEFAULT 1,
                `agreed`             TINYINT(1)   NOT NULL DEFAULT 0,
                `category`           VARCHAR(50)  NOT NULL DEFAULT '',
                `applied_project`    VARCHAR(255) NOT NULL DEFAULT '',
                `bid`                VARCHAR(50)  NOT NULL DEFAULT '',
                `eta`                VARCHAR(50)  NOT NULL DEFAULT '',
                `proposal`           TEXT,
                `skills`             LONGTEXT,
                `social_media`       LONGTEXT,
                `video_url`          VARCHAR(500) NOT NULL DEFAULT '',
                `contact`            LONGTEXT,
                `experience`         LONGTEXT,
                `portfolio`          LONGTEXT,
                `reviews`            LONGTEXT,
                `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        require_once __DIR__ . '/project-history.php';
        gig_history_ensure_table($pdo);
        gig_seed_project_history($pdo);

        // Migrate legacy worker identifiers to canonical 'theressaz@pasker.id'
        $legacyIds = "'tess.kirana@pasker.id','theressaz','theressa zaratrusha','tessa kirana','tessa'";
        foreach (['project_history','project_reviews','project_completions','project_offers','project_applications','worker_notifications'] as $tbl) {
            try {
                $pdo->exec("UPDATE `$tbl` SET `worker_id` = 'theressaz@pasker.id'
                            WHERE LOWER(TRIM(`worker_id`)) IN ($legacyIds)
                               OR LOWER(TRIM(`worker_id`)) = 'tessa'");
            } catch (Throwable $migErr) { /* table may not exist yet */ }
        }

        require_once __DIR__ . '/admin-store.php';
        gig_admin_ensure_schema($pdo);

        require_once __DIR__ . '/user-avatars.php';
        gig_avatar_ensure_schema($pdo);

    } catch (Throwable $e) {
        $pdo = null;
    }

    return $pdo;
}
