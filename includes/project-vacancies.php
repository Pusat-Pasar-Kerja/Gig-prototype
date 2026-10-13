<?php
declare(strict_types=1);

/**
 * Shared Project Vacancies dataset.
 * Vacancy statuses:
 * - 'draft' -> Draft (Belum diajukan)
 * - 'review' -> Menunggu Verifikasi (Verifikasi Admin)
 * - 'revision' -> Perlu Revisi (Catatan perbaikan dari Admin)
 * - 'rejected' -> Ditolak (Tidak disetujui Admin)
 * - 'active' -> Tayang Aktif (Sudah disetujui & dipublikasikan)
 */
if (!function_exists('gig_is_remote_work')) {
    function gig_is_remote_work(array $jobOrProject): bool
    {
        if (isset($jobOrProject['is_remote'])) {
            return (bool)$jobOrProject['is_remote'];
        }
        $mode = strtolower(trim((string)($jobOrProject['work_type'] ?? ($jobOrProject['workType'] ?? ''))));
        if (str_contains($mode, 'remote')) {
            return true;
        }
        $loc = strtolower(trim((string)($jobOrProject['location'] ?? '')));
        if (str_contains($loc, 'remote')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('gig_work_type_label')) {
    function gig_work_type_label(array $jobOrProject): string
    {
        return gig_is_remote_work($jobOrProject) ? 'Remote' : 'On-site/Hybrid';
    }
}

function gig_project_vacancies_base(): array
{
    return [
        [
            'id' => 'GIG-2026-09-001',
            'title' => 'Redesign UI/UX Dashboard Prototype KarirHub',
            'category' => 'Desain & Kreatif',
            'status' => 'active',
            'statusLabel' => 'Tayang Aktif',
            'budget' => 'Rp 8.500.000',
            'duration' => '3 Minggu',
            'applicantsCount' => 2,
            'acceptedCount' => 1,
            'quota' => 1,
            'location' => 'Jakarta Selatan',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'posted' => '01 Sep 2026',
            'skills' => ['Figma', 'UI/UX', 'Design System', 'Prototyping'],
            'desc' => 'Dibutuhkan UI/UX designer berpengalaman untuk merancang prototype interaktif dashboard KarirHub dengan tampilan modern.',
            'deliverables' => 'Prototype interaktif 12+ layar, design system component kit di Figma, dan panduan handoff developer.',
            'deadline' => '20 Sep 2026',
            'adminNote' => 'Lowongan telah diverifikasi dan disetujui oleh Admin KarirHub pada 01 Sep 2026.',
            'employer' => 'PT ABC',
            'vacancy_type' => 'project',
            'entity_type' => 'perusahaan',
        ],
        [
            'id' => 'GIG-2026-09-002',
            'title' => 'Integrasi REST API Modul Notifikasi SMS & WhatsApp',
            'category' => 'IT & Pemrograman',
            'status' => 'active',
            'statusLabel' => 'Tayang Aktif',
            'budget' => 'Rp 6.000.000',
            'duration' => '2 Minggu',
            'applicantsCount' => 2,
            'acceptedCount' => 1,
            'quota' => 1,
            'location' => 'Bandung',
            'work_type' => 'On-site/Hybrid',
            'workType' => 'On-site/Hybrid',
            'is_remote' => false,
            'posted' => '03 Sep 2026',
            'skills' => ['PHP', 'REST API', 'Webhook', 'MySQL'],
            'desc' => 'Mengembangkan endpoint webhook dan mengintegrasikan provider SMS/WA ke core sistem.',
            'deliverables' => 'Endpoint REST API webhook, integrasi modul notifikasi SMS & WhatsApp, serta dokumentasi integrasi API.',
            'deadline' => '20 Sep 2026',
            'adminNote' => 'Lowongan disetujui Admin pada 03 Sep 2026.',
            'employer' => 'PT ABC',
            'vacancy_type' => 'project',
            'entity_type' => 'perusahaan',
        ],
        [
            'id' => 'GIG-2026-09-003',
            'title' => 'Kampanye Media Sosial & Copywriting Peluncuran Fitur',
            'category' => 'Pemasaran & Konten',
            'status' => 'review',
            'statusLabel' => 'Menunggu Verifikasi',
            'budget' => 'Rp 4.500.000',
            'duration' => '1 Bulan',
            'applicantsCount' => 0,
            'acceptedCount' => 0,
            'quota' => 1,
            'location' => 'Bandung',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'posted' => '07 Sep 2026',
            'skills' => ['Copywriting', 'Social Media', 'Content Plan'],
            'desc' => 'Menyusun strategi konten peluncuran fitur Gig Worker untuk meningkatkan awareness.',
            'deliverables' => 'Content plan 30 hari, copy editorial postingan media sosial, dan laporan performa engagement kampanye.',
            'deadline' => '28 Sep 2026',
            'adminNote' => 'Permohonan lowongan dalam antrean verifikasi Admin. Estimasi waktu 1x24 jam.',
            'employer' => 'PT ABC',
            'vacancy_type' => 'project',
            'entity_type' => 'perusahaan',
        ],
        [
            'id' => 'GIG-2026-09-005',
            'title' => 'Desain UI/UX Mobile App E-Commerce UMKM',
            'category' => 'Desain & Kreatif',
            'status' => 'active',
            'statusLabel' => 'Tayang Aktif',
            'budget' => 'Rp 6.500.000',
            'duration' => '2 Minggu',
            'applicantsCount' => 2,
            'acceptedCount' => 1,
            'quota' => 1,
            'location' => 'Yogyakarta',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'posted' => '04 Sep 2026',
            'skills' => ['Figma', 'Mobile UI', 'Design System', 'User Flow'],
            'desc' => 'Merancang alur checkout intuitif dan komponen antarmuka katalog produk aplikasi mobile e-commerce UMKM.',
            'deliverables' => 'Wireframe & High Fidelity Screens, alur checkout, dan halaman katalog produk di Figma.',
            'deadline' => '22 Sep 2026',
            'adminNote' => 'Disetujui Admin pada 04 Sep 2026.',
            'employer' => 'CV Visual Studio Creative',
            'vacancy_type' => 'project',
            'entity_type' => 'perusahaan',
        ],
        [
            'id' => 'JOB-2026-09-002',
            'title' => 'Digital Marketing & Growth Lead',
            'category' => 'Pemasaran & Konten',
            'status' => 'review',
            'statusLabel' => 'Menunggu Verifikasi',
            'budget' => 'Rp 10.000.000',
            'duration' => 'Tetap / Full Time',
            'applicantsCount' => 0,
            'acceptedCount' => 0,
            'quota' => 1,
            'location' => 'Jakarta Pusat',
            'work_type' => 'On-site/Hybrid',
            'workType' => 'On-site/Hybrid',
            'is_remote' => false,
            'posted' => '06 Sep 2026',
            'skills' => ['Google Ads', 'Meta Ads', 'SEO', 'Analytics'],
            'desc' => 'Memimpin tim pemasaran digital dan performa kampanye akuisisi pengguna.',
            'deliverables' => 'Laporan strategi akuisisi pengguna bulanan dan optimasi conversion funnel digital.',
            'deadline' => '27 Sep 2026',
            'adminNote' => 'Dalam antrean verifikasi Admin.',
            'employer' => 'PT Mega Media Indonesia',
            'vacancy_type' => 'job',
            'entity_type' => 'perusahaan',
        ],
        [
            'id' => 'JOB-2026-09-003',
            'title' => 'Accountant & Staf Keuangan',
            'category' => 'Data Analytics & Entry',
            'status' => 'revision',
            'statusLabel' => 'Perlu Revisi',
            'budget' => 'Rp 6.000.000',
            'duration' => 'Full Time',
            'applicantsCount' => 0,
            'acceptedCount' => 0,
            'quota' => 1,
            'location' => 'Surabaya',
            'work_type' => 'On-site/Hybrid',
            'workType' => 'On-site/Hybrid',
            'is_remote' => false,
            'posted' => '08 Sep 2026',
            'skills' => ['Akuntansi', 'Taxation', 'Excel', 'Accurate'],
            'desc' => 'Mengelola pembukuan rutin, laporan pajak bulanan, dan klaim pengeluaran operasional.',
            'deliverables' => 'Laporan keuangan bulanan, rekonsiliasi kas, dan berkas pelaporan pajak PPh.',
            'deadline' => '30 Sep 2026',
            'adminNote' => 'Revisi dari Admin: Harap sertakan persyaratan sertifikasi perpajakan (Brevet A/B).',
            'employer' => 'PT Mutualplus Global',
            'vacancy_type' => 'job',
            'entity_type' => 'perusahaan',
        ],
        [
            'id' => 'JOB-2026-09-004',
            'title' => 'Asisten Pribadi & Manager Konten Harian',
            'category' => 'Pemasaran & Konten',
            'status' => 'review',
            'statusLabel' => 'Menunggu Verifikasi',
            'budget' => 'Rp 4.000.000',
            'duration' => 'Paruh Waktu (Part Time)',
            'applicantsCount' => 0,
            'acceptedCount' => 0,
            'quota' => 1,
            'location' => 'Jakarta Selatan',
            'work_type' => 'On-site/Hybrid',
            'workType' => 'On-site/Hybrid',
            'is_remote' => false,
            'posted' => '08 Sep 2026',
            'skills' => ['Scheduling', 'Social Media', 'Canva', 'Administrative'],
            'desc' => 'Membantu pengelolaan jadwal harian, riset materi seminar, dan drafting postingan media sosial personal.',
            'deliverables' => 'Jadwal agenda mingguan tertata dan 5 materi postingan media sosial mingguan.',
            'deadline' => '30 Sep 2026',
            'adminNote' => 'Permohonan lowongan dalam antrean verifikasi Admin.',
            'employer' => 'Wendy Danendra',
            'vacancy_type' => 'job',
            'entity_type' => 'individual',
        ],
        [
            'id' => 'JOB-2026-09-005',
            'title' => 'Scraping Data Produk & Market Research',
            'category' => 'IT & Pemrograman',
            'status' => 'rejected',
            'statusLabel' => 'Ditolak',
            'budget' => 'Rp 2.000.000',
            'duration' => '1 Minggu',
            'applicantsCount' => 0,
            'acceptedCount' => 0,
            'quota' => 1,
            'location' => 'Surabaya',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'posted' => '05 Sep 2026',
            'skills' => ['Python', 'Web Scraping'],
            'desc' => 'Melakukan pengumpulan data massal dari platform eksternal.',
            'deliverables' => 'Dataset terstruktur hasil scraping dan script Python ekstraksi data.',
            'deadline' => '15 Sep 2026',
            'adminNote' => 'Penolakan dari Admin: Lowongan tidak memenuhi Syarat & Ketentuan KarirHub terkait perlindungan data pribadi dan hak cipta.',
            'employer' => 'Vino',
            'vacancy_type' => 'job',
            'entity_type' => 'individual',
        ],
        [
            'id' => 'JOB-2026-09-006',
            'title' => 'Tutor Privat Pemrograman Python & Data Science',
            'category' => 'IT & Pemrograman',
            'status' => 'active',
            'statusLabel' => 'Tayang Aktif',
            'budget' => 'Rp 5.000.000',
            'duration' => '2 Bulan',
            'applicantsCount' => 3,
            'acceptedCount' => 1,
            'quota' => 1,
            'location' => 'Riau',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'posted' => '16 Sep 2026',
            'skills' => ['Python', 'Pandas', 'Data Science', 'Teaching'],
            'desc' => 'Membimbing privat 2x seminggu untuk penguasaan Python dasar hingga pengolahan data.',
            'deliverables' => 'Silabus pembelajaran privat, modul latihan kode mingguan, dan evaluasi capaian belajar.',
            'deadline' => '07 Okt 2026',
            'adminNote' => 'Disetujui Admin pada 16 Sep 2026.',
            'employer' => 'Hendra Saputra',
            'vacancy_type' => 'job',
            'entity_type' => 'individual',
        ],
        [
            'id' => 'GIG-2026-09-007',
            'title' => 'Pengembangan Aplikasi Mobile E-Commerce (React Native)',
            'category' => 'IT & Pemrograman',
            'status' => 'active',
            'statusLabel' => 'Tayang Aktif',
            'budget' => 'Rp 12.000.000',
            'duration' => '1 Bulan',
            'applicantsCount' => 3,
            'acceptedCount' => 0,
            'quota' => 1,
            'location' => 'DKI Jakarta',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'posted' => '10 Sep 2026',
            'skills' => ['React Native', 'Mobile App', 'REST API', 'Redux'],
            'desc' => 'Membangun antarmuka mobile e-commerce responsif untuk iOS dan Android lengkap dengan integrasi payment gateway dan katalog produk.',
            'deadline' => '10 Okt 2026',
            'adminNote' => 'Disetujui Admin KarirHub pada 10 Sep 2026.',
            'employer' => 'PT Talenta Digital Indonesia',
            'vacancy_type' => 'project',
            'entity_type' => 'perusahaan',
            'deliverables' => 'Source code React Native, dokumentasi build APK/IPA, dan panduan integrasi API.',
        ],
        [
            'id' => 'GIG-2026-09-008',
            'title' => 'Desain Visual Asset & Branding Kit UMKM Go Digital',
            'category' => 'Desain & Kreatif',
            'status' => 'active',
            'statusLabel' => 'Tayang Aktif',
            'budget' => 'Rp 5.500.000',
            'duration' => '2 Minggu',
            'applicantsCount' => 4,
            'acceptedCount' => 1,
            'quota' => 1,
            'location' => 'Yogyakarta',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'posted' => '12 Sep 2026',
            'skills' => ['Figma', 'Illustrator', 'Branding', 'Logo Design'],
            'desc' => 'Pembuatan logo vector, pilihan tipografi, palette warna, serta 15 template postingan Instagram untuk UMKM kuliner.',
            'deadline' => '26 Sep 2026',
            'adminNote' => 'Disetujui Admin pada 12 Sep 2026.',
            'employer' => 'CV Kreasi Visual Nusantara',
            'vacancy_type' => 'project',
            'entity_type' => 'perusahaan',
            'deliverables' => 'File vector logo (.AI/.SVG), brand guidelines PDF, dan template Canva/Figma.',
        ],
        [
            'id' => 'GIG-2026-09-010',
            'title' => 'Pembersihan & Pengolahan Data Transaksi Penjualan (Data Cleaning)',
            'category' => 'Data Analytics & Entry',
            'status' => 'active',
            'statusLabel' => 'Tayang Aktif',
            'budget' => 'Rp 3.500.000',
            'duration' => '1 Minggu',
            'applicantsCount' => 2,
            'acceptedCount' => 0,
            'quota' => 1,
            'location' => 'Semarang',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'posted' => '15 Sep 2026',
            'skills' => ['Excel / Spreadsheet', 'Python Data Processing', 'Data Cleaning'],
            'desc' => 'Pembersihan dan standarisasi 50.000+ baris data transaksi penjualan toko online untuk analisis laporan triwulan.',
            'deadline' => '22 Sep 2026',
            'adminNote' => 'Disetujui Admin pada 15 Sep 2026.',
            'employer' => 'PT Analytics Indonesia Jaya',
            'entity_type' => 'perusahaan',
            'deliverables' => 'Dataset tersaring (.CSV/.XLSX) dan ringkasan metode validasi data.',
        ],
        [
            'id' => 'GIG-2026-09-012',
            'title' => 'Audit Design System & Aksesibilitas Web Portal',
            'category' => 'Desain & Kreatif',
            'status' => 'active',
            'statusLabel' => 'Tayang Aktif',
            'budget' => 'Rp 5.000.000',
            'duration' => '10 Hari',
            'applicantsCount' => 3,
            'acceptedCount' => 1,
            'quota' => 1,
            'location' => 'Jakarta Pusat',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'posted' => '16 Sep 2026',
            'skills' => ['Design System', 'Accessibility (WCAG)', 'Figma', 'UI Audit'],
            'desc' => 'Audit aksesibilitas kontras rasio WCAG 2.1 AA dan penataan token warna design system web portal.',
            'deliverables' => 'Dokumen laporan evaluasi WCAG 2.1 AA, token warna Figma, dan panduan perbaikan komponen.',
            'deadline' => '30 Sep 2026',
            'adminNote' => 'Disetujui Admin pada 16 Sep 2026.',
            'employer' => 'PT Nusantara Media Technologi',
            'vacancy_type' => 'project',
            'entity_type' => 'perusahaan',
        ],
        [
            'id' => 'GIG-2026-09-014',
            'title' => 'Pengembangan Backend Microservices & API Gateway (Node.js)',
            'category' => 'IT & Pemrograman',
            'status' => 'active',
            'statusLabel' => 'Tayang Aktif',
            'budget' => 'Rp 15.000.000',
            'duration' => '1.5 Bulan',
            'applicantsCount' => 5,
            'acceptedCount' => 0,
            'quota' => 1,
            'location' => 'Tangerang Selatan',
            'work_type' => 'Remote',
            'workType' => 'Remote',
            'is_remote' => true,
            'posted' => '18 Sep 2026',
            'skills' => ['Node.js', 'Express', 'Docker', 'Redis', 'PostgreSQL'],
            'desc' => 'Refactoring arsitektur backend monolith menjadi microservices modular berbasis Docker dan Redis cache.',
            'deadline' => '30 Okt 2026',
            'adminNote' => 'Disetujui Admin pada 18 Sep 2026.',
            'employer' => 'PT Solusi Infrastruktur Digital',
            'vacancy_type' => 'project',
            'entity_type' => 'perusahaan',
            'deliverables' => 'Repository backend, Docker compose file, dan spesifikasi OpenAPI 3.0.',
        ],
    ];
}

if (!function_exists('gig_random_location')) {
    function gig_random_location(string $seed = ''): string
    {
        $locations = [
            'Jakarta Selatan',
            'Bandung',
            'Surabaya',
            'Yogyakarta',
            'Semarang',
            'Tangerang Selatan',
            'Medan',
            'Denpasar',
            'Depok',
            'Bekasi',
            'Malang',
            'Bogor',
        ];
        if ($seed !== '') {
            $index = abs(crc32($seed)) % count($locations);
        } else {
            $index = array_rand($locations);
        }
        return $locations[$index];
    }
}

function gig_project_vacancies(): array
{
    require_once __DIR__ . '/vacancy-store.php';
    $fromDb = gig_vacancy_load_all();
    if ($fromDb !== []) {
        return $fromDb;
    }
    $fallback = gig_project_vacancies_base();
    return array_map(static function (array $vacancy): array {
        $vacancy['budget'] = gig_vacancy_budget_range((string)($vacancy['budget'] ?? ''));
        return $vacancy;
    }, $fallback);
}

function gig_find_vacancy(string $id): ?array
{
    $vacancies = gig_project_vacancies();
    $cleanId = strtolower(trim($id));
    foreach ($vacancies as $v) {
        if (strtolower($v['id']) === $cleanId || strtolower(str_replace('gig-2026-09-', '', strtolower($v['id']))) === $cleanId) {
            return $v;
        }
    }
    return null;
}

if (!function_exists('getCategoryBannerClass')) {
    function getCategoryBannerClass(string $cat): string
    {
        $c = strtolower($cat);
        if (str_contains($c, 'desain') || str_contains($c, 'ui/ux')) {
            return 'banner-blue';
        }
        if (str_contains($c, 'it') || str_contains($c, 'backend') || str_contains($c, 'pemrograman')) {
            return 'banner-cyan';
        }
        if (str_contains($c, 'pemasaran') || str_contains($c, 'konten') || str_contains($c, 'marketing')) {
            return 'banner-purple';
        }
        if (str_contains($c, 'data')) {
            return 'banner-green';
        }
        return 'banner-indigo';
    }
}

if (!function_exists('gig_offer_banner_class')) {
    function gig_offer_banner_class(string $cat): string
    {
        return getCategoryBannerClass($cat);
    }
}

if (!function_exists('getCategoryShortLabel')) {
    function getCategoryShortLabel(string $cat): string
    {
        $c = strtolower($cat);
        if (str_contains($c, 'desain') || str_contains($c, 'ui/ux')) {
            return 'UI/UX & Desain';
        }
        if (str_contains($c, 'it') || str_contains($c, 'backend') || str_contains($c, 'pemrograman')) {
            return 'Backend & API';
        }
        if (str_contains($c, 'pemasaran') || str_contains($c, 'konten') || str_contains($c, 'marketing')) {
            return 'Digital Marketing';
        }
        if (str_contains($c, 'data')) {
            return 'Data & Analitik';
        }
        return $cat !== '' ? $cat : 'Proyek';
    }
}

if (!function_exists('gig_offer_category_label')) {
    function gig_offer_category_label(string $cat): string
    {
        return getCategoryShortLabel($cat);
    }
}

if (!function_exists('getProjectAvatarSvg')) {
    function getProjectAvatarSvg(int $idx): string
    {
        $avatars = [
            '<svg viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="50" fill="#e0f2fe"/><circle cx="50" cy="38" r="18" fill="#f87171"/><path d="M50 20c-10 0-18 6-18 15 0 2 1 4 3 5 2-8 7-12 15-12s13 4 15 12c2-1 3-3 3-5 0-9-8-15-18-15z" fill="#1e293b"/><circle cx="43" cy="38" r="2.5" fill="#1e293b"/><circle cx="57" cy="38" r="2.5" fill="#1e293b"/><path d="M46 45q4 3 8 0" stroke="#1e293b" stroke-width="2" stroke-linecap="round"/><path d="M22 82c3-14 15-22 28-22s25 8 28 22" fill="#3b82f6"/></svg>',
            '<svg viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="50" fill="#ccfbf1"/><circle cx="50" cy="40" r="18" fill="#fcd34d"/><path d="M30 36c0-12 9-20 20-20s20 8 20 20v4H30v-4z" fill="#0f766e"/><circle cx="42" cy="40" r="2.5" fill="#1e293b"/><circle cx="58" cy="40" r="2.5" fill="#1e293b"/><path d="M46 47q4 2 8 0" stroke="#1e293b" stroke-width="2" stroke-linecap="round"/><path d="M20 85c4-16 16-23 30-23s26 7 30 23" fill="#0d9488"/></svg>',
            '<svg viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="50" fill="#f3e8ff"/><circle cx="50" cy="38" r="18" fill="#fed7aa"/><path d="M30 30c0-8 8-16 20-16s20 8 20 16v18c0 0-6 4-20 4s-20-4-20-4V30z" fill="#6b21a8"/><circle cx="43" cy="38" r="2.5" fill="#1e293b"/><circle cx="57" cy="38" r="2.5" fill="#1e293b"/><path d="M45 45q5 4 10 0" stroke="#1e293b" stroke-width="2" stroke-linecap="round"/><path d="M22 84c3-15 15-22 28-22s25 7 28 22" fill="#9333ea"/></svg>',
            '<svg viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="50" fill="#dcfce7"/><circle cx="50" cy="38" r="18" fill="#fca5a5"/><path d="M32 24c4-6 11-8 18-8s14 2 18 8v10H32V24z" fill="#14532d"/><circle cx="43" cy="36" r="2.5" fill="#1e293b"/><circle cx="57" cy="36" r="2.5" fill="#1e293b"/><path d="M46 44q4 3 8 0" stroke="#1e293b" stroke-width="2" stroke-linecap="round"/><path d="M20 84c4-15 16-22 30-22s26 7 30 22" fill="#15803d"/></svg>',
        ];
        return $avatars[$idx % count($avatars)];
    }
}

if (!function_exists('gig_offer_avatar_svg')) {
    function gig_offer_avatar_svg(int $idx): string
    {
        return getProjectAvatarSvg($idx);
    }
}

