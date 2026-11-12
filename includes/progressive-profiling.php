<?php
/**
 * Modul Progressive Profiling (Profile Completion Nudge)
 * Menggunakan PHP Native (PDO) & MySQL/MariaDB.
 *
 * Mengintegrasikan tabel `users`, `profiling_categories`, dan `profiling_questions`
 * secara langsung dengan tampilan dan data pada `profil.php`.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Daftar kolom pada tabel `users` yang diizinkan (Whitelist) untuk dijadikan `target_column`.
 * Setiap kolom terhubung langsung dengan bagian/section di `profil.php`.
 *
 * @return array<string, string>
 */
function gig_profiling_allowed_columns(): array
{
    return [
        'nik'                => 'Biodata: Nomor Induk Kependudukan (nik)',
        'birth_place_date'   => 'Biodata: Tempat, Tanggal Lahir (birth_place_date)',
        'gender'             => 'Biodata: Jenis Kelamin (gender)',
        'marital_status'     => 'Biodata: Status Perkawinan (marital_status)',
        'phone'              => 'Biodata: Nomor Telepon / WhatsApp (phone)',
        'address_ktp'        => 'Biodata: Alamat Sesuai KTP (address_ktp)',
        'domicile'           => 'Biodata: Alamat Domisili (domicile)',
        'bio'                => 'Biodata: Ringkasan Profil / Bio (bio)',
        'linkedin_url'       => 'Biodata: Tautan Profil LinkedIn (linkedin_url)',
        'expected_salary'    => 'Preferensi Karir: Ekspektasi Gaji Bulanan (expected_salary)',
        'work_preference'    => 'Preferensi Karir: Sistem Kerja WFO/Hybrid/Remote (work_preference)',
        'work_experience'    => 'Pengalaman Kerja: Posisi & Perusahaan (work_experience)',
        'training_name'      => 'Pelatihan: Nama Pelatihan & Penyelenggara (training_name)',
        'highest_education'  => 'Pendidikan: Kampus & Jurusan Terakhir (highest_education)',
        'certification_name' => 'Sertifikasi: Nama Sertifikasi Kompetensi (certification_name)',
        'achievement_name'   => 'Pencapaian: Penghargaan / Prestasi (achievement_name)',
        'skills_text'        => 'Keahlian: Daftar Skill Utama (skills_text)',
        'english_level'      => 'Bahasa: Tingkat Kemahiran Bahasa Inggris (english_level)',
        'mandarin_level'     => 'Bahasa: Tingkat Kemahiran Bahasa Mandarin (mandarin_level)',
    ];
}

/**
 * Memastikan tabel `users`, `profiling_categories`, dan `profiling_questions`
 * memiliki seluruh kolom yang dibutuhkan serta mengisi seed data awal.
 */
function gig_profiling_ensure_schema(?PDO $pdo = null): void
{
    $pdo = $pdo ?? gig_db();
    if ($pdo === null) {
        return;
    }

    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    try {
        // 1. Buat tabel `users` jika belum ada
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `phone` VARCHAR(30) DEFAULT NULL,
                `domicile` VARCHAR(100) DEFAULT NULL,
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
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Pastikan kolom tambahan tersedia jika tabel `users` sudah dibuat sebelumnya
        try {
            $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `domicile` VARCHAR(255) DEFAULT NULL");
        } catch (Throwable $ignore) {}

        $alterColumns = [
            'nik'                     => 'VARCHAR(30) DEFAULT NULL',
            'birth_place_date'        => 'VARCHAR(120) DEFAULT NULL',
            'gender'                  => 'VARCHAR(30) DEFAULT NULL',
            'marital_status'          => 'VARCHAR(40) DEFAULT NULL',
            'address_ktp'             => 'TEXT DEFAULT NULL',
            'bio'                     => 'TEXT DEFAULT NULL',
            'linkedin_url'            => 'VARCHAR(255) DEFAULT NULL',
            'expected_salary'         => 'VARCHAR(60) DEFAULT NULL',
            'work_preference'         => 'VARCHAR(60) DEFAULT NULL',
            'work_experience'         => 'VARCHAR(255) DEFAULT NULL',
            'training_name'           => 'VARCHAR(255) DEFAULT NULL',
            'highest_education'       => 'VARCHAR(180) DEFAULT NULL',
            'certification_name'      => 'VARCHAR(180) DEFAULT NULL',
            'achievement_name'        => 'VARCHAR(180) DEFAULT NULL',
            'skills_text'             => 'TEXT DEFAULT NULL',
            'english_level'           => 'VARCHAR(80) DEFAULT NULL',
            'mandarin_level'          => 'VARCHAR(80) DEFAULT NULL',
            'last_nudge_shown_at'        => 'DATETIME DEFAULT NULL',
            'last_nudge_answered_at'     => 'DATETIME DEFAULT NULL',
            'nudge_shown_count_today'    => 'INT UNSIGNED NOT NULL DEFAULT 0',
            'nudge_answered_count_today' => 'INT UNSIGNED NOT NULL DEFAULT 0',
        ];
        foreach ($alterColumns as $col => $def) {
            try {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `{$col}` {$def}");
            } catch (Throwable $ignore) {
                // Kolom sudah ada
            }
        }

        // 1b. Buat tabel `profiling_settings` (Pengaturan Admin untuk frekuensi kemunculan pop-up)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `profiling_settings` (
                `id` TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
                `is_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Pop-up Aktif, 0 = Nonaktif',
                `frequency_mode` VARCHAR(30) NOT NULL DEFAULT 'once_per_day' COMMENT 'once_per_day, custom_interval, once_per_session, always',
                `interval_hours` INT UNSIGNED NOT NULL DEFAULT 24 COMMENT 'Jeda jam jika frequency_mode = custom_interval',
                `max_questions_per_show` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Jumlah pertanyaan per kemunculan (default 1)',
                `show_fab_button` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Tampilkan tombol floating Lengkapi Profil',
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            INSERT IGNORE INTO `profiling_settings`
                (`id`, `is_enabled`, `frequency_mode`, `interval_hours`, `max_questions_per_show`, `show_fab_button`)
            VALUES
                (1, 1, 'once_per_day', 24, 1, 1)
        ");

        // 2. Buat tabel `profiling_categories`
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `profiling_categories` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `description` VARCHAR(255) DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 3. Buat tabel `profiling_questions` (berelasi dengan `profiling_categories`)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `profiling_questions` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `category_id` INT UNSIGNED NOT NULL,
                `target_column` VARCHAR(64) NOT NULL,
                `question_new` TEXT NOT NULL,
                `question_verify` TEXT NOT NULL,
                `input_type` ENUM('text', 'select', 'tel', 'url') NOT NULL DEFAULT 'text',
                `options_json` TEXT DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT `fk_profiling_questions_category`
                    FOREIGN KEY (`category_id`) REFERENCES `profiling_categories` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                INDEX `idx_target_column` (`target_column`),
                INDEX `idx_is_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 4. Seed Kategori Standar
        $pdo->exec("
            INSERT IGNORE INTO `profiling_categories` (`id`, `name`, `description`) VALUES
            (1, 'Biodata', 'Informasi kontak pribadi, bio, dan alamat domisili'),
            (2, 'Bahasa', 'Kemampuan penguasaan bahasa asing dan daerah'),
            (3, 'Pendidikan & Pelatihan', 'Riwayat pendidikan, pelatihan, sertifikasi, dan pencapaian'),
            (4, 'Keahlian & Karir', 'Keahlian teknis, ekspektasi gaji, dan sistem kerja')
        ");

        // 5. Pastikan pertanyaan untuk seluruh field profil tersedia
        $defaultQuestions = [
            [
                'target_column'   => 'certification_name',
                'category_id'     => 3,
                'question_new'    => 'Bagian Sertifikasi di profil Anda masih kosong. Sertifikasi kompetensi apa yang pernah Anda peroleh? (Contoh: BNSP UI/UX Designer / Google UX Certificate)',
                'question_verify' => 'Apakah data sertifikasi kompetensi Anda masih sesuai: {value}?',
                'input_type'      => 'text',
                'options_json'    => null,
            ],
            [
                'target_column'   => 'achievement_name',
                'category_id'     => 3,
                'question_new'    => 'Bagian Pencapaian di profil Anda masih kosong. Penghargaan atau pencapaian profesional apa yang pernah Anda raih?',
                'question_verify' => 'Apakah pencapaian utama di profil Anda masih sesuai: {value}?',
                'input_type'      => 'text',
                'options_json'    => null,
            ],
            [
                'target_column'   => 'mandarin_level',
                'category_id'     => 2,
                'question_new'    => 'Apakah Anda memiliki kemahiran Bahasa Mandarin untuk ditambahkan ke profil Bahasa Anda?',
                'question_verify' => 'Apakah kemampuan Bahasa Mandarin Anda masih pada tingkat {value}?',
                'input_type'      => 'select',
                'options_json'    => json_encode(['Tingkat Pemula (HSK 1-2)', 'Tingkat Menengah (HSK 3-4)', 'Tingkat Fasih (HSK 5-6)', 'Belum Menguasai'], JSON_UNESCAPED_UNICODE),
            ],
            [
                'target_column'   => 'expected_salary',
                'category_id'     => 4,
                'question_new'    => 'Berapa kisaran gaji bulanan yang Anda harapkan untuk ditampilkan pada biodata karir Anda?',
                'question_verify' => 'Apakah ekspektasi gaji bulanan Anda masih di kisaran {value}?',
                'input_type'      => 'select',
                'options_json'    => json_encode(['Rp 4.000.000 - Rp 6.000.000', 'Rp 6.000.000 - Rp 9.000.000', 'Rp 9.000.000 - Rp 14.000.000', 'Di atas Rp 14.000.000'], JSON_UNESCAPED_UNICODE),
            ],
            [
                'target_column'   => 'work_preference',
                'category_id'     => 4,
                'question_new'    => 'Apa sistem kerja yang paling Anda prioritaskan saat ini (WFO / Hybrid / Remote)?',
                'question_verify' => 'Apakah preferensi sistem kerja Anda masih {value}?',
                'input_type'      => 'select',
                'options_json'    => json_encode(['Work From Office (WFO)', 'Hybrid (Fleksibel)', 'Full Remote / Work From Anywhere'], JSON_UNESCAPED_UNICODE),
            ],
            [
                'target_column'   => 'phone',
                'category_id'     => 1,
                'question_new'    => 'Nomor WhatsApp Anda belum tercantum di profil. Berapa nomor WhatsApp aktif Anda?',
                'question_verify' => 'Apakah nomor WhatsApp di profil Anda masih aktif di {value}?',
                'input_type'      => 'tel',
                'options_json'    => null,
            ],
            [
                'target_column'   => 'domicile',
                'category_id'     => 1,
                'question_new'    => 'Di kota/kabupaten mana Anda berdomisili saat ini?',
                'question_verify' => 'Apakah saat ini Anda masih berdomisili di {value}?',
                'input_type'      => 'text',
                'options_json'    => null,
            ],
            [
                'target_column'   => 'linkedin_url',
                'category_id'     => 1,
                'question_new'    => 'Tautan LinkedIn Anda masih kosong. Yuk cantumkan URL profil LinkedIn Anda!',
                'question_verify' => 'Apakah alamat profil LinkedIn Anda masih benar di {value}?',
                'input_type'      => 'url',
                'options_json'    => null,
            ],
            [
                'target_column'   => 'english_level',
                'category_id'     => 2,
                'question_new'    => 'Apa tingkat kemahiran Bahasa Inggris Anda saat ini?',
                'question_verify' => 'Apakah tingkat kemahiran Bahasa Inggris di profil Anda masih {value}?',
                'input_type'      => 'select',
                'options_json'    => json_encode(['Tingkat Pemula', 'Tingkat Menengah', 'Tingkat Menengah - Mahir (Professional Working Proficiency)', 'Tingkat Fasih atau Bahasa Ahli'], JSON_UNESCAPED_UNICODE),
            ],
            [
                'target_column'   => 'highest_education',
                'category_id'     => 3,
                'question_new'    => 'Data Pendidikan Anda masih kosong. Apa jenjang pendidikan terakhir dan universitas Anda? (Contoh: S1 Ilmu Komputer - Universitas Indonesia)',
                'question_verify' => 'Apakah riwayat pendidikan terakhir di profil Anda sudah sesuai: {value}?',
                'input_type'      => 'text',
                'options_json'    => null,
            ],
            [
                'target_column'   => 'training_name',
                'category_id'     => 3,
                'question_new'    => 'Bagian Pelatihan Anda masih kosong. Pelatihan atau bootcamp apa yang pernah Anda ikuti?',
                'question_verify' => 'Apakah data pelatihan di profil Anda masih sesuai: {value}?',
                'input_type'      => 'text',
                'options_json'    => null,
            ],
            [
                'target_column'   => 'work_experience',
                'category_id'     => 4,
                'question_new'    => 'Bagian Pengalaman Kerja Anda masih kosong. Apa posisi dan nama perusahaan tempat Anda bekerja terakhir? (Contoh: Lead UI/UX Designer - PT KarirHub)',
                'question_verify' => 'Apakah posisi pengalaman kerja utama Anda masih di {value}?',
                'input_type'      => 'text',
                'options_json'    => null,
            ],
            [
                'target_column'   => 'skills_text',
                'category_id'     => 4,
                'question_new'    => 'Daftar Keahlian Anda masih kosong. Sebutkan keahlian utama Anda (pisahkan dengan koma, contoh: Figma, UI/UX, HTML/CSS):',
                'question_verify' => 'Apakah daftar keahlian utama di profil Anda masih sesuai ({value})?',
                'input_type'      => 'text',
                'options_json'    => null,
            ],
            [
                'target_column'   => 'bio',
                'category_id'     => 1,
                'question_new'    => 'Ringkasan bio profil Anda masih kosong. Tuliskan deskripsi singkat mengenai pengalaman dan keahlian profesional Anda:',
                'question_verify' => 'Apakah ringkasan bio profil Anda masih relevan?',
                'input_type'      => 'text',
                'options_json'    => null,
            ],
            [
                'target_column'   => 'birth_place_date',
                'category_id'     => 1,
                'question_new'    => 'Tempat dan tanggal lahir Anda belum lengkap. Tuliskan tempat dan tanggal lahir Anda (Contoh: Jakarta, 14 Mei 1998):',
                'question_verify' => 'Apakah data tempat, tanggal lahir Anda sudah benar: {value}?',
                'input_type'      => 'text',
                'options_json'    => null,
            ],
            [
                'target_column'   => 'marital_status',
                'category_id'     => 1,
                'question_new'    => 'Apa status perkawinan Anda saat ini?',
                'question_verify' => 'Apakah status perkawinan Anda masih {value}?',
                'input_type'      => 'select',
                'options_json'    => json_encode(['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'], JSON_UNESCAPED_UNICODE),
            ],
            [
                'target_column'   => 'address_ktp',
                'category_id'     => 1,
                'question_new'    => 'Alamat sesuai KTP Anda belum tercantum. Tuliskan alamat lengkap sesuai KTP Anda:',
                'question_verify' => 'Apakah alamat sesuai KTP Anda masih di {value}?',
                'input_type'      => 'text',
                'options_json'    => null,
            ],
        ];

        $stmtCheckQ = $pdo->prepare('SELECT COUNT(*) FROM `profiling_questions` WHERE `target_column` = :col');
        $stmtInsertQ = $pdo->prepare("
            INSERT INTO `profiling_questions`
                (`category_id`, `target_column`, `question_new`, `question_verify`, `input_type`, `options_json`, `is_active`)
            VALUES
                (:category_id, :target_column, :question_new, :question_verify, :input_type, :options_json, 1)
        ");

        foreach ($defaultQuestions as $dq) {
            $stmtCheckQ->execute([':col' => $dq['target_column']]);
            if ((int)$stmtCheckQ->fetchColumn() === 0) {
                $stmtInsertQ->execute([
                    ':category_id'     => $dq['category_id'],
                    ':target_column'   => $dq['target_column'],
                    ':question_new'    => $dq['question_new'],
                    ':question_verify' => $dq['question_verify'],
                    ':input_type'      => $dq['input_type'],
                    ':options_json'    => $dq['options_json'],
                ]);
            }
        }

        // 6. Seed / Lengkapi data awal User #1 (Theressa Zaratrusha) agar sinkron dengan profil.php
        // Sebagian field diisi sesuai profilnya, dan sebagian dibiarkan NULL (Sertifikasi, Pencapaian, Mandarin, Gaji, Preferensi)
        // sehingga sistem mengecek field yang kosong terlebih dahulu di profil.php!
        $uCount = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        if ($uCount === 0) {
            $pdo->exec("
                INSERT IGNORE INTO `users`
                (`id`, `name`, `email`, `phone`, `domicile`, `bio`, `linkedin_url`, `expected_salary`, `work_preference`, `work_experience`, `training_name`, `highest_education`, `certification_name`, `achievement_name`, `skills_text`, `english_level`, `mandarin_level`)
                VALUES
                (1, 'Theressa Zaratrusha', 'theressaz@pasker.id', '0811-7671-208', 'Jakarta Selatan, DKI Jakarta',
                 'Saya merupakan seorang profesional Lead UI/UX Designer dengan pengalaman kerja lebih dari 4 tahun dalam merancang design system berskala besar, menyusun wireframe & prototype interaktif di Figma, serta memimpin pengujian ketergunaan (usability testing) untuk berbagai platform layanan publik dan ketenagakerjaan digital di ekosistem SIAPkerja Kemnaker RI.',
                 'https://linkedin.com/in/theressaz', NULL, NULL,
                 'Lead UI/UX Designer - Redesign Dashboard KarirHub',
                 'Advanced Design System & UI/UX Accessibility - BBPVP Kemnaker RI',
                 'S1 - Ilmu Komputer / Sistem Informasi - Universitas Indonesia',
                 NULL, NULL,
                 'Figma Design, UI/UX Prototyping, Design System, Usability Testing, Wireframing, UI/UX Design, HTML/CSS',
                 'Tingkat Menengah - Mahir (Professional Working Proficiency)', NULL)
            ");
        } else {
            // Pastikan user #1 memiliki nilai default untuk field yang memang sudah tampil di profil awalnya
            // jika kolom-kolom baru baru saja ditambahkan via ALTER TABLE
            $stmtU1 = $pdo->prepare('SELECT * FROM `users` WHERE `id` = 1 LIMIT 1');
            $stmtU1->execute();
            $u1 = $stmtU1->fetch(PDO::FETCH_ASSOC);
            if ($u1 && ($u1['nik'] === null || $u1['birth_place_date'] === null || $u1['address_ktp'] === null || $u1['work_experience'] === null)) {
                $stmtInitU1 = $pdo->prepare("
                    UPDATE `users`
                    SET `nik` = COALESCE(`nik`, :nik),
                        `birth_place_date` = COALESCE(`birth_place_date`, :bpd),
                        `gender` = COALESCE(`gender`, :gen),
                        `marital_status` = COALESCE(`marital_status`, :mar),
                        `address_ktp` = COALESCE(`address_ktp`, :ktp),
                        `domicile` = CASE
                            WHEN `domicile` IS NULL OR `domicile` = 'Jakarta Selatan, DKI Jakarta'
                            THEN :dom
                            ELSE `domicile`
                        END,
                        `bio` = COALESCE(`bio`, :bio),
                        `work_experience` = COALESCE(`work_experience`, :exp),
                        `training_name` = COALESCE(`training_name`, :trn),
                        `highest_education` = COALESCE(`highest_education`, :edu),
                        `skills_text` = COALESCE(`skills_text`, :skl),
                        `english_level` = COALESCE(`english_level`, :eng)
                    WHERE `id` = 1
                ");
                $stmtInitU1->execute([
                    ':nik' => '3174095405980002',
                    ':bpd' => 'Jakarta, 14 Mei 1998',
                    ':gen' => 'Perempuan',
                    ':mar' => 'Belum Kawin',
                    ':ktp' => 'Senayan, Kebayoran Baru, KOTA ADM. JAKARTA SELATAN, DKI JAKARTA - 12190',
                    ':dom' => 'Senayan, Kebayoran Baru, Jakarta Selatan, DKI Jakarta, Indonesia, Jl. Tulodong Atas No. 18, RT. 004, RW. 002, 12190',
                    ':bio' => 'Saya merupakan seorang profesional Lead UI/UX Designer dengan pengalaman kerja lebih dari 4 tahun dalam merancang design system berskala besar, menyusun wireframe & prototype interaktif di Figma, serta memimpin pengujian ketergunaan (usability testing) untuk berbagai platform layanan publik dan ketenagakerjaan digital di ekosistem SIAPkerja Kemnaker RI.',
                    ':exp' => 'Lead UI/UX Designer - Redesign Dashboard KarirHub',
                    ':trn' => 'Advanced Design System & UI/UX Accessibility - BBPVP Kemnaker RI',
                    ':edu' => 'S1 - Ilmu Komputer / Sistem Informasi - Universitas Indonesia',
                    ':skl' => 'Figma Design, UI/UX Prototyping, Design System, Usability Testing, Wireframing, UI/UX Design, HTML/CSS',
                    ':eng' => 'Tingkat Menengah - Mahir (Professional Working Proficiency)',
                ]);
            }
        }
    } catch (Throwable $e) {
        // Abaikan jika terjadi kendala skema minor
    }
}

/**
 * Menyinkronkan session login SIAPkerja dengan `$_SESSION['user_id']` di tabel `users`.
 * Ketika pengguna baru dibuat di tabel `users`, fungsi ini menarik data awal yang sudah
 * ada di profil mereka dan membiarkan field yang belum ada tetap NULL (kosong).
 */
function gig_profiling_resolve_user_id(?PDO $pdo = null): int
{
    $pdo = $pdo ?? gig_db();
    if ($pdo === null) {
        return (int)($_SESSION['user_id'] ?? 1);
    }

    gig_profiling_ensure_schema($pdo);

    $sessionEmail = strtolower(trim((string)($_SESSION['siapkerja_email'] ?? '')));
    $sessionName  = trim((string)($_SESSION['siapkerja_name'] ?? $_SESSION['username'] ?? ''));
    $sessionRole  = strtolower(trim((string)($_SESSION['role'] ?? '')));

    // Jika sedang login sebagai Admin, arahkan target profiling ke akun utama SIAPKerja (Theressa Zaratrusha / User #1)
    if ($sessionRole === 'admin' || $sessionEmail === 'admin@kemnaker.go.id' || strtolower($sessionName) === 'admin karirhub') {
        $_SESSION['user_id'] = 1;
        return 1;
    }

    if ($sessionEmail !== '') {
        $stmt = $pdo->prepare('SELECT `id` FROM `users` WHERE LOWER(`email`) = :email LIMIT 1');
        $stmt->execute([':email' => $sessionEmail]);
        $foundId = $stmt->fetchColumn();

        if ($foundId) {
            $_SESSION['user_id'] = (int)$foundId;
            return (int)$foundId;
        }

        // Tarik data awal dari worker profile jika ada agar sinkron dengan profil.php
        if (!function_exists('gig_find_worker')) {
            require_once __DIR__ . '/worker-profiles.php';
        }
        $workerData = gig_find_worker($sessionEmail) ?? ($sessionName !== '' ? gig_find_worker($sessionName) : null);

        $initPhone    = $workerData['contact']['wa'] ?? ($_SESSION['siapkerja_phone'] ?? null);
        $initDomicile = $workerData['location'] ?? null;
        $initBio      = !empty($workerData['proposal']) ? (string)$workerData['proposal'] : null;
        $initSkills   = null;
        if (!empty($workerData['skills']) && is_array($workerData['skills'])) {
            $sNames = [];
            foreach ($workerData['skills'] as $sk) {
                $nm = gig_skill_name($sk);
                if ($nm !== '') {
                    $sNames[] = $nm;
                }
            }
            if (!empty($sNames)) {
                $initSkills = implode(', ', $sNames);
            }
        }
        $initExp = null;
        if (!empty($workerData['experience'][0])) {
            $e0 = $workerData['experience'][0];
            $initExp = trim(($e0['role'] ?? '') . ' - ' . ($e0['project'] ?? ($e0['company'] ?? '')), ' -');
        }

        $ins = $pdo->prepare("
            INSERT INTO `users`
                (`name`, `email`, `phone`, `domicile`, `bio`, `work_experience`, `skills_text`, `english_level`)
            VALUES
                (:name, :email, :phone, :domicile, :bio, :exp, :skills, 'Tingkat Menengah')
        ");
        $ins->execute([
            ':name'     => $workerData['name'] ?? ($sessionName !== '' ? $sessionName : 'Pengguna SIAPKerja'),
            ':email'    => $sessionEmail,
            ':phone'    => $initPhone,
            ':domicile' => $initDomicile,
            ':bio'      => $initBio,
            ':exp'      => $initExp,
            ':skills'   => $initSkills,
        ]);
        $newId = (int)$pdo->lastInsertId();
        $_SESSION['user_id'] = $newId;
        return $newId;
    }

    if (empty($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 1;
    }

    return (int)$_SESSION['user_id'];
}

/**
 * Daftar opsi mode frekuensi kemunculan pop-up yang dapat diatur oleh Admin.
 *
 * @return array<string, string>
 */
function gig_profiling_frequency_modes(): array
{
    return [
        'once_per_day'     => 'Cukup 1x Sehari (Default)',
        'custom_interval'  => 'Setiap Interval Jam Tertentu (Custom Jam)',
        'once_per_session' => '1x per Sesi Login',
        'always'           => 'Selalu Muncul di Setiap Buka Halaman (Mode Testing)',
    ];
}

/**
 * Mengambil pengaturan kemunculan pop-up Progressive Profiling dari tabel `profiling_settings`.
 *
 * @return array{is_enabled: int, frequency_mode: string, interval_hours: int, max_questions_per_show: int, show_fab_button: int, updated_at: string}
 */
function gig_profiling_get_settings(?PDO $pdo = null): array
{
    $defaults = [
        'is_enabled'             => 1,
        'frequency_mode'         => 'once_per_day',
        'interval_hours'         => 24,
        'max_questions_per_show' => 1,
        'show_fab_button'        => 1,
        'updated_at'             => date('Y-m-d H:i:s'),
    ];

    $pdo = $pdo ?? gig_db();
    if ($pdo === null) {
        return $defaults;
    }

    gig_profiling_ensure_schema($pdo);

    try {
        $row = $pdo->query('SELECT * FROM `profiling_settings` WHERE `id` = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [
                'is_enabled'             => (int)($row['is_enabled'] ?? 1),
                'frequency_mode'         => (string)($row['frequency_mode'] ?? 'once_per_day'),
                'interval_hours'         => max(1, (int)($row['interval_hours'] ?? 24)),
                'max_questions_per_show' => max(1, (int)($row['max_questions_per_show'] ?? 1)),
                'show_fab_button'        => (int)($row['show_fab_button'] ?? 1),
                'updated_at'             => (string)($row['updated_at'] ?? date('Y-m-d H:i:s')),
            ];
        }
    } catch (Throwable $e) {
    }

    return $defaults;
}

/**
 * Menyimpan pengaturan kemunculan pop-up yang diatur dari Dashboard Admin.
 */
function gig_profiling_save_settings(array $input, ?PDO $pdo = null): bool
{
    $pdo = $pdo ?? gig_db();
    if ($pdo === null) {
        return false;
    }

    gig_profiling_ensure_schema($pdo);

    $allowedModes = array_keys(gig_profiling_frequency_modes());
    $mode         = trim((string)($input['frequency_mode'] ?? 'once_per_day'));
    if (!in_array($mode, $allowedModes, true)) {
        $mode = 'once_per_day';
    }

    $isEnabled    = !empty($input['is_enabled']) ? 1 : 0;
    $intervalHrs  = max(1, min(720, (int)($input['interval_hours'] ?? 24)));
    $maxQuestions = max(1, min(14, (int)($input['max_questions_per_show'] ?? 1)));
    $showFab      = !empty($input['show_fab_button']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO `profiling_settings`
                (`id`, `is_enabled`, `frequency_mode`, `interval_hours`, `max_questions_per_show`, `show_fab_button`)
            VALUES
                (1, :enabled, :mode, :hours, :max_q, :fab)
            ON DUPLICATE KEY UPDATE
                `is_enabled`             = VALUES(`is_enabled`),
                `frequency_mode`         = VALUES(`frequency_mode`),
                `interval_hours`         = VALUES(`interval_hours`),
                `max_questions_per_show` = VALUES(`max_questions_per_show`),
                `show_fab_button`        = VALUES(`show_fab_button`),
                `updated_at`             = NOW()
        ");
        return $stmt->execute([
            ':enabled' => $isEnabled,
            ':mode'    => $mode,
            ':hours'   => $intervalHrs,
            ':max_q'   => $maxQuestions,
            ':fab'     => $showFab,
        ]);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Mereset riwayat kemunculan harian (cooldown) pada user tertentu atau semua user
 * agar pop-up dapat langsung muncul kembali saat diuji oleh Admin.
 */
function gig_profiling_reset_user_cooldown(?int $userId = null, ?PDO $pdo = null): bool
{
    $pdo = $pdo ?? gig_db();
    if ($pdo === null) {
        return false;
    }

    gig_profiling_ensure_schema($pdo);

    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['pp_nudge_shown_session'], $_SESSION['pp_nudge_answered_session_count']);
    }

    try {
        if ($userId !== null && $userId > 0) {
            $stmt = $pdo->prepare("
                UPDATE `users`
                SET `last_nudge_shown_at` = NULL,
                    `last_nudge_answered_at` = NULL,
                    `nudge_shown_count_today` = 0,
                    `nudge_answered_count_today` = 0
                WHERE `id` = :id
            ");
            return $stmt->execute([':id' => $userId]);
        }

        $pdo->exec("
            UPDATE `users`
            SET `last_nudge_shown_at` = NULL,
                `last_nudge_answered_at` = NULL,
                `nudge_shown_count_today` = 0,
                `nudge_answered_count_today` = 0
        ");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Mengevaluasi apakah pertanyaan pop-up masih boleh muncul hari ini berdasarkan pengaturan Admin
 * dan apakah user sudah mengisi/menjawab pertanyaan di hari tersebut (`last_nudge_answered_at` & `nudge_answered_count_today`).
 *
 * @return array{should_auto_show: bool, quota_reached: bool, reason: string, next_available_label: string, shown_today: int, answered_today: int, max_per_show: int}
 */
function gig_profiling_evaluate_schedule(array $userData, array $settings): array
{
    $isEnabled   = (int)($settings['is_enabled'] ?? 1) === 1;
    $mode        = (string)($settings['frequency_mode'] ?? 'once_per_day');
    $intervalHrs = max(1, (int)($settings['interval_hours'] ?? 24));
    $maxPerShow  = max(1, (int)($settings['max_questions_per_show'] ?? 1));

    $todayDate    = date('Y-m-d');
    $lastShownStr = trim((string)($userData['last_nudge_shown_at'] ?? ''));
    $lastShownTs  = $lastShownStr !== '' ? strtotime($lastShownStr) : false;
    $shownToday   = ($lastShownTs && date('Y-m-d', $lastShownTs) === $todayDate)
        ? (int)($userData['nudge_shown_count_today'] ?? 0)
        : 0;

    $lastAnsStr    = trim((string)($userData['last_nudge_answered_at'] ?? ''));
    $lastAnsTs     = $lastAnsStr !== '' ? strtotime($lastAnsStr) : false;
    $answeredToday = ($lastAnsTs && date('Y-m-d', $lastAnsTs) === $todayDate)
        ? max(1, (int)($userData['nudge_answered_count_today'] ?? 1))
        : 0;

    if (!$isEnabled) {
        return [
            'should_auto_show'     => false,
            'quota_reached'        => true,
            'reason'               => 'Pop-up dinonaktifkan oleh Admin.',
            'next_available_label' => 'Saat diaktifkan kembali oleh Admin',
            'shown_today'          => $shownToday,
            'answered_today'       => $answeredToday,
            'max_per_show'         => $maxPerShow,
        ];
    }

    if ($mode === 'always') {
        return [
            'should_auto_show'     => true,
            'quota_reached'        => false,
            'reason'               => 'Mode Testing (Selalu Muncul).',
            'next_available_label' => 'Setiap muat halaman',
            'shown_today'          => $shownToday,
            'answered_today'       => $answeredToday,
            'max_per_show'         => $maxPerShow,
        ];
    }

    if ($mode === 'once_per_session') {
        $sessAnswered = (int)($_SESSION['pp_nudge_answered_session_count'] ?? 0);
        $reached      = $sessAnswered >= $maxPerShow;
        return [
            'should_auto_show'     => !$reached,
            'quota_reached'        => $reached,
            'reason'               => $reached ? 'Sudah mengisi pertanyaan pada sesi login ini.' : 'Belum mengisi pertanyaan di sesi ini.',
            'next_available_label' => $reached ? 'Sesi login berikutnya' : 'Sekarang',
            'shown_today'          => $shownToday,
            'answered_today'       => $answeredToday,
            'max_per_show'         => $maxPerShow,
        ];
    }

    if ($mode === 'custom_interval') {
        if ($lastAnsTs !== false && $answeredToday >= $maxPerShow) {
            $nextTs = $lastAnsTs + ($intervalHrs * 3600);
            if (time() < $nextTs) {
                return [
                    'should_auto_show'     => false,
                    'quota_reached'        => true,
                    'reason'               => "Sudah mengisi pertanyaan pada " . date('d M Y H:i', $lastAnsTs) . " (Interval {$intervalHrs} jam).",
                    'next_available_label' => date('d M Y, H:i', $nextTs),
                    'shown_today'          => $shownToday,
                    'answered_today'       => $answeredToday,
                    'max_per_show'         => $maxPerShow,
                ];
            }
        }
        return [
            'should_auto_show'     => true,
            'quota_reached'        => false,
            'reason'               => "Interval {$intervalHrs} jam terpenuhi.",
            'next_available_label' => 'Sekarang',
            'shown_today'          => $shownToday,
            'answered_today'       => $answeredToday,
            'max_per_show'         => $maxPerShow,
        ];
    }

    // Default: 'once_per_day' (Ketika user sudah mengisi 1x hari ini, tidak ada pertanyaan lagi sampai besok)
    if ($answeredToday >= $maxPerShow && $lastAnsTs !== false) {
        $tomorrowDate = date('d M Y, 00:00', strtotime('tomorrow'));
        return [
            'should_auto_show'     => false,
            'quota_reached'        => true,
            'reason'               => 'Sudah mengisi ' . $answeredToday . 'x hari ini pada pukul ' . date('H:i', $lastAnsTs) . ' (Pertanyaan baru muncul esok hari).',
            'next_available_label' => 'Besok (' . $tomorrowDate . ')',
            'shown_today'          => max(1, $shownToday),
            'answered_today'       => $answeredToday,
            'max_per_show'         => $maxPerShow,
        ];
    }

    return [
        'should_auto_show'     => true,
        'quota_reached'        => false,
        'reason'               => 'Belum mengisi pertanyaan hari ini (Tersedia ' . ($maxPerShow - $answeredToday) . ' pertanyaan hari ini).',
        'next_available_label' => 'Sekarang',
        'shown_today'          => $shownToday,
        'answered_today'       => $answeredToday,
        'max_per_show'         => $maxPerShow,
    ];
}

