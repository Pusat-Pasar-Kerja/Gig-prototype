<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/worker-profiles.php';

/**
 * Returns default set of realistic Gig Worker accounts with diverse statuses:
 * - Pending (Belum Terverifikasi): Dimas Prasetyo, Siti Nurhaliza, Christian D. Lawangga
 * - Revision (Revisi): Budi Wicaksono
 * - Approved (Terverifikasi): Theressa Zaratrusha, Rian Ardiansyah, Mega Lestari
 * - Rejected (Ditolak): Farhan Ramadhan, Aldo Pratama
 *
 * @return list<array<string, mixed>>
 */
function gig_admin_default_workers(): array
{
    return [
        [
            'username' => 'Dimas Prasetyo',
            'display_name' => 'Dimas Prasetyo',
            'bidang_keahlian' => 'Cloud API & Backend Engineer',
            'skills' => [
                ['name' => 'Node.js', 'level' => 'Ahli'],
                ['name' => 'REST API', 'level' => 'Mahir'],
                ['name' => 'Microservices', 'level' => 'Menengah'],
                ['name' => 'AWS', 'level' => 'Menengah'],
                ['name' => 'Redis', 'level' => 'Menengah'],
            ],
            'contact_choice' => 'siapkerja',
            'contact_email' => 'dimas.code@email.com',
            'contact_wa' => '0821-9988-7766',
            'domicile' => 'Depok, Jawa Barat',
            'profile_summary' => 'Cloud API Engineer dengan spesialisasi arsitektur microservices, message queue Redis, dan integrasi multi-gateway notification.',
            'previous_projects' => [
                [
                    'role' => 'Cloud API Engineer',
                    'project' => 'Integrasi Gateway Multi-Provider',
                    'period' => '2024 — 2026',
                    'summary' => 'Membangun layer abstraksi API untuk SMS, email, dan push notification.',
                ],
                [
                    'role' => 'Backend Developer',
                    'project' => 'Platform Antrian Cloud',
                    'period' => '2022 — 2024',
                    'summary' => 'Merancang antrean Redis dan worker Node.js untuk 2 juta event/hari.',
                ],
            ],
            'portfolio' => [
                [
                    'id' => 'dimas-port-1',
                    'title' => 'Multi-Gateway Adapter',
                    'type' => 'Microservice',
                    'client' => 'PT Cloud Nusantara',
                    'year' => '2026',
                    'deliverable' => 'Service Node.js, tes integrasi, dan runbook operasi.',
                    'files' => [
                        ['name' => 'Microservice_Adapter_Node.zip', 'type' => 'Source Code', 'size' => '8.9 MB', 'url' => '#'],
                        ['name' => 'Ops_Runbook_Deployment.pdf', 'type' => 'Dokumen PDF', 'size' => '1.4 MB', 'url' => '#'],
                    ],
                ],
            ],
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'social_media' => [
                ['platform' => 'GitHub', 'url' => 'https://github.com/dimascode'],
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/dimasprasetyo'],
            ],
            'status' => 'pending',
            'admin_note' => '',
            'reviewed_at' => null,
            'created_at' => '2026-10-27 10:15:00',
        ],
        [
            'username' => 'Siti Nurhaliza',
            'display_name' => 'Siti Nurhaliza',
            'bidang_keahlian' => 'Social Media Specialist & Copywriter',
            'skills' => [
                ['name' => 'Copywriting', 'level' => 'Ahli'],
                ['name' => 'Social Media', 'level' => 'Mahir'],
                ['name' => 'Content Plan', 'level' => 'Mahir'],
                ['name' => 'Canva', 'level' => 'Menengah'],
                ['name' => 'Instagram Ads', 'level' => 'Pemula'],
            ],
            'contact_choice' => 'siapkerja',
            'contact_email' => 'siti.marketing@email.com',
            'contact_wa' => '0857-1122-3344',
            'domicile' => 'Surabaya, Jawa Timur',
            'profile_summary' => 'Kreator konten dan copywriter berpengalaman 4+ tahun dalam kampanye digital edukatif, copywriting konversi tinggi, dan pengelolaan multi-channel medsos.',
            'previous_projects' => [
                [
                    'role' => 'Social Media Lead',
                    'project' => 'Kampanye Peluncuran Fitur Fintech',
                    'period' => '2025 — 2026',
                    'summary' => 'Mengelola 3 kanal dan menaikkan engagement organik 42% dalam 8 minggu.',
                ],
            ],
            'portfolio' => [
                [
                    'id' => 'siti-port-1',
                    'title' => 'Kalender Konten 30 Hari',
                    'type' => 'Content Plan',
                    'client' => 'Brand F&B Nasional',
                    'year' => '2026',
                    'deliverable' => 'Kalender editorial, 20 carousel, dan 8 naskah reels.',
                    'files' => [
                        ['name' => 'Kalender_Editorial_September.pdf', 'type' => 'Dokumen PDF', 'size' => '2.8 MB', 'url' => '#'],
                    ],
                ],
            ],
            'video_url' => '',
            'social_media' => [
                ['platform' => 'Instagram', 'url' => 'https://instagram.com/sitinurhaliza.work'],
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/sitinurhaliza'],
            ],
            'status' => 'pending',
            'admin_note' => '',
            'reviewed_at' => null,
            'created_at' => '2026-10-27 08:30:00',
        ],
        [
            'username' => 'Christian D. Lawangga',
            'display_name' => 'Christian D. Lawangga',
            'bidang_keahlian' => 'Operations & Quality Control Specialist',
            'skills' => [
                ['name' => 'Operations Management', 'level' => 'Mahir'],
                ['name' => 'Quality Control', 'level' => 'Mahir'],
                ['name' => 'Field Crew Coordination', 'level' => 'Ahli'],
                ['name' => 'SOP Drafting', 'level' => 'Mahir'],
            ],
            'contact_choice' => 'siapkerja',
            'contact_email' => 'ekocristianlawangga@gmail.com',
            'contact_wa' => '0812-4455-6677',
            'domicile' => 'Mondowe, Morowali, Sulawesi Tengah',
            'profile_summary' => 'Spesialis kendali mutu dan operasional lapangan eks PT. GNI. Disiplin tinggi, tanggap kepatuhan prosedur K3, dan koordinasi tim terstruktur.',
            'previous_projects' => [
                [
                    'role' => 'Crew & Operations Specialist',
                    'project' => 'PT Gunbuster Nickel Industry (PT GNI)',
                    'period' => '2022 — 2026',
                    'summary' => 'Pernah bekerja di PT. GNI divisi crew dengan tanggung jawab kendali mutu operasional dan kepatuhan prosedur harian.',
                ],
            ],
            'portfolio' => [
                [
                    'id' => 'christian-port-1',
                    'title' => 'SOP & Quality Checklist Operasional',
                    'type' => 'Dokumen Operasional',
                    'client' => 'PT GNI',
                    'year' => '2025',
                    'deliverable' => 'Panduan checklist inspeksi dan log alur kerja harian.',
                    'files' => [
                        ['name' => 'SOP_Inspeksi_Operasional.pdf', 'type' => 'Dokumen PDF', 'size' => '2.4 MB', 'url' => '#'],
                    ],
                ],
            ],
            'video_url' => '',
            'social_media' => [
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/christian-lawangga'],
            ],
            'status' => 'pending',
            'admin_note' => 'Catatan Admin: Harap sertakan dokumen sertifikasi pendukung K3 lapangan dan verifikasi kontak darurat.',
            'reviewed_at' => '2026-10-26 16:30:00',
            'created_at' => '2026-10-26 14:00:00',
        ],
        [
            'username' => 'Budi Wicaksono',
            'display_name' => 'Budi Wicaksono',
            'bidang_keahlian' => 'UI Designer',
            'skills' => [
                ['name' => 'UI Design', 'level' => 'Mahir'],
                ['name' => 'Wireframing', 'level' => 'Mahir'],
                ['name' => 'Figma', 'level' => 'Mahir'],
                ['name' => 'Style Guide', 'level' => 'Menengah'],
            ],
            'contact_choice' => 'siapkerja',
            'contact_email' => 'budi.design@email.com',
            'contact_wa' => '0819-2233-4455',
            'domicile' => 'Sleman, D.I. Yogyakarta',
            'profile_summary' => 'UI Designer berfokus pada desain mobile dan web yang bersih, rapi, dan mudah diimplementasikan tim developer.',
            'previous_projects' => [
                [
                    'role' => 'UI Designer',
                    'project' => 'Redesign Aplikasi Absensi',
                    'period' => '2025 — 2026',
                    'summary' => 'Merapikan 18 layar mobile dan menyusun style guide komponen dasar.',
                ],
            ],
            'portfolio' => [
                [
                    'id' => 'budi-port-1',
                    'title' => 'Style Guide Absensi Mobile',
                    'type' => 'UI Kit',
                    'client' => 'PT Karya Waktu',
                    'year' => '2026',
                    'deliverable' => 'Komponen Figma, ikon set, dan panduan spasi/tipografi.',
                    'files' => [
                        ['name' => 'StyleGuide_Absensi_Component.fig', 'type' => 'Figma File', 'size' => '18.2 MB', 'url' => '#'],
                    ],
                ],
            ],
            'video_url' => '',
            'social_media' => [
                ['platform' => 'Dribbble', 'url' => 'https://dribbble.com/budiwicaksono'],
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/budi-wicaksono'],
            ],
            'status' => 'revision',
            'admin_note' => 'Catatan Admin: Tautan berkas portofolio Figma tidak dapat diakses (akses dibatasi). Harap ubah hak akses file menjadi publik atau upload file langsung.',
            'reviewed_at' => '2026-10-26 09:20:00',
            'created_at' => '2026-10-25 11:00:00',
        ],
        [
            'username' => 'Rian Ardiansyah',
            'display_name' => 'Rian Ardiansyah',
            'bidang_keahlian' => 'Fullstack / Backend API Developer',
            'skills' => [
                ['name' => 'PHP / Laravel', 'level' => 'Ahli'],
                ['name' => 'REST API', 'level' => 'Mahir'],
                ['name' => 'MySQL', 'level' => 'Mahir'],
                ['name' => 'Webhook', 'level' => 'Menengah'],
                ['name' => 'Node.js', 'level' => 'Menengah'],
            ],
            'contact_choice' => 'siapkerja',
            'contact_email' => 'rian.dev@email.com',
            'contact_wa' => '0813-8899-7711',
            'domicile' => 'Bandung, Jawa Barat',
            'profile_summary' => 'Backend Developer dengan jam terbang tinggi di integrasi payment gateway, sistem antrean, dan RESTful API arsitektur aman.',
            'previous_projects' => [
                [
                    'role' => 'Backend API Developer',
                    'project' => 'Integrasi Notifikasi SMS & WhatsApp',
                    'period' => 'Sep 2026 — sekarang',
                    'summary' => 'Membangun endpoint webhook dan uji beban ke production.',
                ],
            ],
            'portfolio' => [
                [
                    'id' => 'rian-port-1',
                    'title' => 'Webhook Notification Hub',
                    'type' => 'REST API',
                    'client' => 'PT ABC',
                    'year' => '2026',
                    'deliverable' => 'Endpoint production, dokumentasi Postman, dan laporan stress test.',
                    'files' => [
                        ['name' => 'Postman_Collection_Notification.json', 'type' => 'API Specs', 'size' => '850 KB', 'url' => '#'],
                    ],
                ],
            ],
            'video_url' => '',
            'social_media' => [
                ['platform' => 'GitHub', 'url' => 'https://github.com/rian-dev'],
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/rian-ardiansyah'],
            ],
            'status' => 'approved',
            'admin_note' => 'Berkas KTP, NPWP, dan sertifikasi backend developer terverifikasi valid.',
            'reviewed_at' => '2026-10-22 14:15:00',
            'created_at' => '2026-10-22 09:00:00',
        ],
        [
            'username' => 'Theressa Zaratrusha',
            'display_name' => 'Theressa Zaratrusha',
            'bidang_keahlian' => 'Lead UI/UX Designer',
            'skills' => [
                ['name' => 'UI/UX Design', 'level' => 'Ahli'],
                ['name' => 'Figma Design', 'level' => 'Ahli'],
                ['name' => 'Design System', 'level' => 'Ahli'],
                ['name' => 'Prototyping', 'level' => 'Mahir'],
                ['name' => 'Usability Testing', 'level' => 'Menengah'],
            ],
            'contact_choice' => 'siapkerja',
            'contact_email' => 'theressaz@pasker.id',
            'contact_wa' => '0811-7671-208',
            'domicile' => 'Jakarta Selatan, DKI Jakarta',
            'profile_summary' => 'Lead UI/UX Designer berpengalaman merancang design system berskala besar dan prototype aplikasi layanan ketenagakerjaan.',
            'previous_projects' => [
                [
                    'role' => 'Lead UI/UX Designer',
                    'project' => 'Redesign Dashboard KarirHub',
                    'period' => 'Sep 2026 — sekarang',
                    'summary' => 'Merancang alur, wireframe, dan prototype interaktif dashboard pemberi kerja.',
                ],
            ],
            'portfolio' => [
                [
                    'id' => 'tessa-port-1',
                    'title' => 'Design System KarirHub',
                    'type' => 'UI Kit & Prototype',
                    'client' => 'Kemnaker RI',
                    'year' => '2026',
                    'deliverable' => 'File Figma, token warna, dan prototype klik-melalui 12 layar.',
                    'files' => [
                        ['name' => 'Design_System_KarirHub_v2.fig', 'type' => 'Figma File', 'size' => '24.5 MB', 'url' => '#'],
                    ],
                ],
            ],
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'social_media' => [
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/theressaz'],
                ['platform' => 'GitHub', 'url' => 'https://github.com/theressaz'],
            ],
            'status' => 'approved',
            'admin_note' => 'Portofolio, kartu identitas, dan sertifikasi terverifikasi lengkap.',
            'reviewed_at' => '2026-10-20 15:30:00',
            'created_at' => '2026-10-20 10:00:00',
        ],
        [
            'username' => 'Mega Lestari',
            'display_name' => 'Mega Lestari',
            'bidang_keahlian' => 'Digital Campaign Strategist',
            'skills' => [
                ['name' => 'Digital Campaign', 'level' => 'Mahir'],
                ['name' => 'SEO Writing', 'level' => 'Menengah'],
                ['name' => 'Brand Story', 'level' => 'Mahir'],
                ['name' => 'Analytics', 'level' => 'Menengah'],
            ],
            'contact_choice' => 'siapkerja',
            'contact_email' => 'mega.content@email.com',
            'contact_wa' => '0878-3344-5566',
            'domicile' => 'Semarang, Jawa Tengah',
            'profile_summary' => 'Spesialis kampanye pemasaran digital lintas platform, copywriting naratif BUMN dan program ketenagakerjaan.',
            'previous_projects' => [
                [
                    'role' => 'Campaign Strategist',
                    'project' => 'Peluncuran Program Magang Nasional',
                    'period' => '2025 — 2026',
                    'summary' => 'Menyusun pesan kampanye lintas kanal dan laporan insight mingguan.',
                ],
            ],
            'portfolio' => [
                [
                    'id' => 'mega-port-1',
                    'title' => 'Kampanye Magang Nasional',
                    'type' => 'Campaign Kit',
                    'client' => 'Lembaga Pemerintah',
                    'year' => '2026',
                    'deliverable' => 'Key message, 15 aset visual, dan laporan performa 30 hari.',
                    'files' => [
                        ['name' => 'Campaign_Strategy_Deck.pdf', 'type' => 'Dokumen Presentation', 'size' => '12.6 MB', 'url' => '#'],
                    ],
                ],
            ],
            'video_url' => '',
            'social_media' => [
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/megalestari'],
            ],
            'status' => 'approved',
            'admin_note' => 'Akun dan portofolio kampanye terverifikasi memenuhi standar platform.',
            'reviewed_at' => '2026-10-22 10:00:00',
            'created_at' => '2026-10-21 13:20:00',
        ],
        [
            'username' => 'Farhan Ramadhan',
            'display_name' => 'Farhan Ramadhan',
            'bidang_keahlian' => 'Junior Web Scraper & Data Assistant',
            'skills' => [
                ['name' => 'Web Scraping', 'level' => 'Pemula'],
                ['name' => 'Data Entry', 'level' => 'Pemula'],
                ['name' => 'Microsoft Excel', 'level' => 'Menengah'],
                ['name' => 'Python Scripting', 'level' => 'Pemula'],
            ],
            'contact_choice' => 'siapkerja',
            'contact_email' => 'farhan.ramadhan@pasker.id',
            'contact_wa' => '0852-9911-3322',
            'domicile' => 'Surakarta, Jawa Tengah',
            'profile_summary' => 'Junior web scraper dan asisten entri data menggunakan Python BeautifulSoup dan otomasi Excel.',
            'previous_projects' => [
                [
                    'role' => 'Data Scraping Assistant',
                    'project' => 'Pengumpulan Data Produk UMKM',
                    'period' => '2026',
                    'summary' => 'Mengekstrak data harga dan nama produk dari marketplace lokal.',
                    'output_title' => 'Data Sheet CSV 10K Rows',
                    'files' => [
                        ['name' => 'Sample_Dataset_UMKM.csv', 'url' => '#', 'type' => 'Dataset CSV'],
                    ],
                ],
            ],
            'portfolio' => [
                [
                    'id' => 'farhan-port-1',
                    'title' => 'Otomasi Scraping Produk',
                    'type' => 'Python Script',
                    'client' => 'UMKM Solo',
                    'year' => '2026',
                    'deliverable' => 'Script python dan file excel hasil ekspor.',
                    'files' => [
                        ['name' => 'Scraper_Script.py', 'url' => '#', 'type' => 'Source Code'],
                    ],
                ],
            ],
            'video_url' => '',
            'social_media' => [
                ['platform' => 'GitHub', 'url' => 'https://github.com/farhan-scraper'],
            ],
            'status' => 'rejected',
            'admin_note' => 'Ketidakpatuhan: Data tidak lengkap. Foto identitas diri buram tidak terbaca dan portofolio tidak menyertakan bukti pengerjaan proyek asli. Silakan mendaftar ulang dengan berkas resmi.',
            'reviewed_at' => '2026-10-25 11:30:00',
            'created_at' => '2026-10-24 16:45:00',
        ],
        [
            'username' => 'Aldo Pratama',
            'display_name' => 'Aldo Pratama',
            'bidang_keahlian' => 'IT Support & Hardware Technician',
            'skills' => [
                ['name' => 'Hardware Maintenance', 'level' => 'Mahir'],
                ['name' => 'Networking LAN', 'level' => 'Menengah'],
                ['name' => 'Windows Server', 'level' => 'Pemula'],
            ],
            'contact_choice' => 'siapkerja',
            'contact_email' => 'aldo.pratama@email.com',
            'contact_wa' => '0818-5544-3322',
            'domicile' => 'Medan, Sumatera Utara',
            'profile_summary' => 'Teknisi perakitan dan maintenance perangkat keras komputer, jaringan kantor LAN, dan troubleshooting sistem operasi.',
            'previous_projects' => [
                [
                    'role' => 'Teknisi Lab Komputer',
                    'project' => 'Peremajaan Jaringan Lab Sekolah',
                    'period' => '2025',
                    'summary' => 'Pemasangan kabel UTP CAT6 dan instalasi 30 PC lab.',
                    'output_title' => 'Dokumentasi Instalasi Lab',
                    'files' => [
                        ['name' => 'Laporan_Maintenance_Lab.pdf', 'url' => '#', 'type' => 'Dokumen PDF'],
                    ],
                ],
            ],
            'portfolio' => [
                [
                    'id' => 'aldo-port-1',
                    'title' => 'Maintenance Jaringan Lab',
                    'type' => 'Dokumentasi Teknis',
                    'client' => 'SMK TI Medan',
                    'year' => '2025',
                    'deliverable' => 'Topologi jaringan dan SOP pemeliharaan PC.',
                    'files' => [
                        ['name' => 'Topologi_Jaringan_Lab.pdf', 'url' => '#', 'type' => 'Dokumen PDF'],
                    ],
                ],
            ],
            'video_url' => '',
            'social_media' => [
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/aldopratama'],
            ],
            'status' => 'rejected',
            'admin_note' => 'Ketidakpatuhan: Tidak sesuai substansi. Profil dan pengalaman teknisi fisik on-site belum sesuai dengan cakupan proyek gig/freelance pada platform KarirHub Gig.',
            'reviewed_at' => '2026-10-24 10:45:00',
            'created_at' => '2026-10-23 14:10:00',
        ],
    ];
}

/**
 * Returns default set of worker profile edit requests for the "Revisi" tab.
 *
 * @return list<array<string, mixed>>
 */
function gig_admin_default_worker_profile_edits(): array
{
    return [
        [
            'id' => 1,
            'worker_username' => 'rian',
            'worker_email' => 'rian.dev@email.com',
            'edited_field' => 'Keahlian & Pengalaman Proyek',
            'reason_code' => 'Penambahan Sertifikasi',
            'reason_detail' => 'Telah lulus sertifikasi AWS Cloud Developer dan menambahkan proyek webhook payment gateway terbaru ke riwayat.',
            'change_summary' => 'Menambahkan skill AWS & Docker serta 1 entri proyek baru di riwayat.',
            'proposed_payload' => [
                'skills' => [
                    ['name' => 'PHP / Laravel', 'level' => 'Ahli'],
                    ['name' => 'REST API', 'level' => 'Mahir'],
                    ['name' => 'MySQL', 'level' => 'Mahir'],
                    ['name' => 'AWS Cloud', 'level' => 'Menengah'],
                    ['name' => 'Docker', 'level' => 'Menengah'],
                ],
                'previous_projects' => [
                    [
                        'role' => 'Backend Lead',
                        'project' => 'Gateway Pembayaran QRIS',
                        'period' => '2026',
                        'summary' => 'Integrasi API QRIS statis & dinamis dengan webhook notifikasi real-time.',
                    ],
                ],
            ],
            'status' => 'pending',
            'admin_note' => '',
            'created_at' => '2026-10-27 09:15:00',
            'reviewed_at' => null,
        ],
        [
            'id' => 2,
            'worker_username' => 'budi',
            'worker_email' => 'budi.design@email.com',
            'edited_field' => 'No. Telepon & Tautan Portofolio',
            'reason_code' => 'Perbaikan Tautan & Kontak',
            'reason_detail' => 'Tautan Figma telah diperbarui ke izin publik dan nomor WhatsApp diganti ke nomor bisnis.',
            'change_summary' => 'Update tautan Figma publik dan no WA menjadi 0819-2233-9999',
            'proposed_payload' => [
                'contact_wa' => '0819-2233-9999',
                'portfolio' => [
                    [
                        'id' => 'budi-port-1',
                        'title' => 'Style Guide Absensi Mobile (Publik)',
                        'type' => 'UI Kit',
                        'client' => 'PT Karya Waktu',
                        'year' => '2026',
                        'deliverable' => 'Komponen Figma publik: https://figma.com/@budi/ui-kit-public',
                    ],
                ],
            ],
            'status' => 'pending',
            'admin_note' => '',
            'created_at' => '2026-10-26 15:40:00',
            'reviewed_at' => null,
        ],
    ];
}

/**
 * Idempotently seeds default worker registrations and profile edits into DB if needed.
 */
function gig_admin_seed_workers(?PDO $pdo = null): void
{
    $pdo = $pdo ?? gig_db();
    if ($pdo === null) {
        return;
    }

    try {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM `gig_worker_registrations`")->fetchColumn();
        if ($count <= 3) {
            $stmt = $pdo->prepare("
                INSERT INTO `gig_worker_registrations`
                (`username`, `display_name`, `bidang_keahlian`, `skills`, `contact_choice`, `contact_email`, `contact_wa`, `domicile`, `profile_summary`, `previous_projects`, `portfolio`, `video_url`, `social_media`, `status`, `admin_note`, `reviewed_at`, `created_at`)
                VALUES
                (:un, :dn, :bk, :sk, :cc, :ce, :cw, :dm, :ps, :pp, :pf, :vu, :sm, :st, :an, :ra, :ca)
                ON DUPLICATE KEY UPDATE
                  `display_name` = VALUES(`display_name`),
                  `bidang_keahlian` = VALUES(`bidang_keahlian`),
                  `skills` = VALUES(`skills`),
                  `contact_choice` = VALUES(`contact_choice`),
                  `contact_email` = VALUES(`contact_email`),
                  `contact_wa` = VALUES(`contact_wa`),
                  `domicile` = VALUES(`domicile`),
                  `profile_summary` = VALUES(`profile_summary`),
                  `previous_projects` = VALUES(`previous_projects`),
                  `portfolio` = VALUES(`portfolio`),
                  `video_url` = VALUES(`video_url`),
                  `social_media` = VALUES(`social_media`),
                  `status` = VALUES(`status`),
                  `admin_note` = VALUES(`admin_note`),
                  `reviewed_at` = VALUES(`reviewed_at`),
                  `created_at` = VALUES(`created_at`)
            ");

            foreach (gig_admin_default_workers() as $w) {
                $stmt->execute([
                    ':un' => $w['username'],
                    ':dn' => $w['display_name'],
                    ':bk' => $w['bidang_keahlian'],
                    ':sk' => is_array($w['skills']) ? json_encode($w['skills'], JSON_UNESCAPED_UNICODE) : (string)$w['skills'],
                    ':cc' => $w['contact_choice'],
                    ':ce' => $w['contact_email'],
                    ':cw' => $w['contact_wa'],
                    ':dm' => $w['domicile'],
                    ':ps' => $w['profile_summary'],
                    ':pp' => is_array($w['previous_projects']) ? json_encode($w['previous_projects'], JSON_UNESCAPED_UNICODE) : (string)$w['previous_projects'],
                    ':pf' => is_array($w['portfolio']) ? json_encode($w['portfolio'], JSON_UNESCAPED_UNICODE) : (string)$w['portfolio'],
                    ':vu' => $w['video_url'],
                    ':sm' => is_array($w['social_media']) ? json_encode($w['social_media'], JSON_UNESCAPED_UNICODE) : (string)$w['social_media'],
                    ':st' => $w['status'],
                    ':an' => $w['admin_note'],
                    ':ra' => $w['reviewed_at'],
                    ':ca' => $w['created_at'],
                ]);
            }
        }

        $editCount = (int)$pdo->query("SELECT COUNT(*) FROM `worker_profile_edit_requests`")->fetchColumn();
        if ($editCount === 0) {
            $editStmt = $pdo->prepare("
                INSERT INTO `worker_profile_edit_requests`
                (`id`, `worker_username`, `worker_email`, `edited_field`, `reason_code`, `reason_detail`, `change_summary`, `proposed_payload`, `status`, `admin_note`, `created_at`)
                VALUES
                (:id, :un, :em, :ef, :rc, :rd, :cs, :pp, :st, :an, :ca)
                ON DUPLICATE KEY UPDATE
                  `worker_username` = VALUES(`worker_username`),
                  `worker_email` = VALUES(`worker_email`),
                  `edited_field` = VALUES(`edited_field`),
                  `reason_code` = VALUES(`reason_code`),
                  `reason_detail` = VALUES(`reason_detail`),
                  `change_summary` = VALUES(`change_summary`),
                  `proposed_payload` = VALUES(`proposed_payload`),
                  `status` = VALUES(`status`),
                  `admin_note` = VALUES(`admin_note`)
            ");

            foreach (gig_admin_default_worker_profile_edits() as $e) {
                $editStmt->execute([
                    ':id' => $e['id'],
                    ':un' => $e['worker_username'],
                    ':em' => $e['worker_email'],
                    ':ef' => $e['edited_field'],
                    ':rc' => $e['reason_code'],
                    ':rd' => $e['reason_detail'],
                    ':cs' => $e['change_summary'],
                    ':pp' => is_array($e['proposed_payload']) ? json_encode($e['proposed_payload'], JSON_UNESCAPED_UNICODE) : (string)$e['proposed_payload'],
                    ':st' => $e['status'],
                    ':an' => $e['admin_note'],
                    ':ca' => $e['created_at'],
                ]);
            }
        }
    } catch (Throwable $e) {
        // Silently skip if table schema issues
    }
}

function gig_admin_ensure_schema(?PDO $pdo = null): void
{
    $pdo = $pdo ?? gig_db();
    if ($pdo === null) {
        return;
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `employer_gig_registrations` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `siapkerja_email` VARCHAR(150) NOT NULL,
                `company_name`    VARCHAR(150) NOT NULL DEFAULT '',
                `industry`        VARCHAR(150) NOT NULL DEFAULT '',
                `nama_pic`        VARCHAR(150) NOT NULL,
                `nik_pic`         VARCHAR(50)  NOT NULL DEFAULT '',
                `email_pic`       VARCHAR(150) NOT NULL,
                `phone_pic`       VARCHAR(50)  NOT NULL DEFAULT '',
                `status`          VARCHAR(20)  NOT NULL DEFAULT 'pending',
                `admin_note`      TEXT NOT NULL,
                `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `reviewed_at`     DATETIME NULL,
                KEY `idx_status` (`status`),
                KEY `idx_email` (`siapkerja_email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER `video_url`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `admin_note` TEXT NOT NULL AFTER `status`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `reviewed_at` DATETIME NULL AFTER `admin_note`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `display_name` VARCHAR(150) NOT NULL DEFAULT '' AFTER `video_url`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `social_media` LONGTEXT NOT NULL AFTER `video_url`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `domicile` VARCHAR(180) NOT NULL DEFAULT '' AFTER `display_name`");
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE `gig_worker_registrations`
                ADD COLUMN `profile_summary` TEXT NOT NULL AFTER `domicile`");
        } catch (Throwable $e) {
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `worker_profile_edit_requests` (
                `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `worker_username`   VARCHAR(100) NOT NULL,
                `worker_email`      VARCHAR(150) NOT NULL DEFAULT '',
                `edited_field`      VARCHAR(60)  NOT NULL DEFAULT '',
                `reason_code`       VARCHAR(60)  NOT NULL DEFAULT '',
                `reason_detail`     TEXT NOT NULL,
                `change_summary`    TEXT NOT NULL,
                `proposed_payload`  LONGTEXT NOT NULL,
                `status`            VARCHAR(20)  NOT NULL DEFAULT 'pending',
                `admin_note`        TEXT NOT NULL,
                `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `reviewed_at`       DATETIME NULL,
                KEY `idx_worker_edit_status` (`status`),
                KEY `idx_worker_edit_username` (`worker_username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        gig_admin_seed_workers($pdo);

        require_once __DIR__ . '/vacancy-store.php';
        gig_vacancy_ensure_schema($pdo);
    } catch (Throwable $e) {
        // offline / partial schema
    }
}

/** @return list<array<string, mixed>> */
function gig_admin_list_worker_registrations(?string $statusFilter = null): array
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        if (!isset($_SESSION['gig_worker_registrations']) || !is_array($_SESSION['gig_worker_registrations']) || empty($_SESSION['gig_worker_registrations'])) {
            $_SESSION['gig_worker_registrations'] = [];
            foreach (gig_admin_default_workers() as $w) {
                $_SESSION['gig_worker_registrations'][$w['username']] = $w;
            }
        }
        $rows = array_values($_SESSION['gig_worker_registrations']);
        if ($statusFilter !== null && $statusFilter !== '') {
            $rows = array_values(array_filter($rows, static fn($r) => ($r['status'] ?? 'pending') === $statusFilter));
        }
        usort($rows, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return $rows;
    }
    gig_admin_seed_workers($db);
    $sql = "SELECT * FROM `gig_worker_registrations`";
    $params = [];
    if ($statusFilter !== null && $statusFilter !== '') {
        $sql .= " WHERE `status` = :st";
        $params[':st'] = $statusFilter;
    }
    $sql .= " ORDER BY `created_at` DESC";
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row['previous_projects'] = json_decode((string)($row['previous_projects'] ?? ''), true) ?: [];
            $row['portfolio'] = json_decode((string)($row['portfolio'] ?? ''), true) ?: [];
            $rawSkills = (string)($row['skills'] ?? '');
            $decodedSkills = json_decode($rawSkills, true);
            $row['skills'] = is_array($decodedSkills) ? gig_normalize_skills($decodedSkills) : gig_normalize_skills($rawSkills);
        }
        unset($row);
        return $rows;
    } catch (Throwable $e) {
        return [];
    }
}

/** @return list<array<string, mixed>> */
function gig_admin_list_employer_registrations(?string $statusFilter = null): array
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return [];
    }
    $sql = "SELECT * FROM `employer_gig_registrations`";
    $params = [];
    if ($statusFilter !== null && $statusFilter !== '') {
        $sql .= " WHERE `status` = :st";
        $params[':st'] = $statusFilter;
    }
    $sql .= " ORDER BY `created_at` DESC";
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function gig_admin_set_worker_status(string $username, string $status, string $adminNote = ''): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    $allowed = ['pending', 'revision', 'approved', 'rejected'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }
    if ($db === null) {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        if (isset($_SESSION['gig_worker_registrations'][$username])) {
            $_SESSION['gig_worker_registrations'][$username]['status'] = $status;
            $_SESSION['gig_worker_registrations'][$username]['admin_note'] = $adminNote;
            $_SESSION['gig_worker_registrations'][$username]['reviewed_at'] = date('Y-m-d H:i:s');
            return true;
        }
        return false;
    }
    try {
        $stmt = $db->prepare(
            "UPDATE `gig_worker_registrations`
             SET `status` = :st, `admin_note` = :note, `reviewed_at` = NOW()
             WHERE `username` = :u"
        );
        $stmt->execute([':st' => $status, ':note' => $adminNote, ':u' => $username]);
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['gig_worker_registered_' . $username], $_SESSION['gig_worker_registration_data_' . $username]);
        }
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function gig_admin_set_employer_status(int $id, string $status, string $adminNote = ''): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return false;
    }
    $allowed = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }
    try {
        $stmt = $db->prepare(
            "UPDATE `employer_gig_registrations`
             SET `status` = :st, `admin_note` = :note, `reviewed_at` = NOW()
             WHERE `id` = :id"
        );
        $stmt->execute([':st' => $status, ':note' => $adminNote, ':id' => $id]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function gig_save_employer_gig_registration(array $data): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return false;
    }
    try {
        $stmt = $db->prepare(
            "INSERT INTO `employer_gig_registrations`
             (`siapkerja_email`, `company_name`, `industry`, `nama_pic`, `nik_pic`, `email_pic`, `phone_pic`, `status`, `admin_note`)
             VALUES (:email, :company, :industry, :nama, :nik, :epic, :phone, 'approved', '')"
        );
        $stmt->execute([
            ':email'   => $data['siapkerja_email'] ?? '',
            ':company' => $data['company_name'] ?? '',
            ':industry'=> $data['industry'] ?? '',
            ':nama'    => $data['nama_pic'] ?? '',
            ':nik'     => $data['nik_pic'] ?? '',
            ':epic'    => $data['email_pic'] ?? '',
            ':phone'   => $data['phone_pic'] ?? '',
        ]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function gig_employer_gig_registration_status(string $siapkerjaEmail): ?string
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return null;
    }
    try {
        $stmt = $db->prepare(
            "SELECT `status` FROM `employer_gig_registrations`
             WHERE `siapkerja_email` = :e ORDER BY `id` DESC LIMIT 1"
        );
        $stmt->execute([':e' => $siapkerjaEmail]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string)$row['status'] : null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * @param list<array<string, mixed>> $rows
 * @return array<string, int>
 */
function gig_admin_count_by_status(array $rows, string $field = 'status'): array
{
    $counts = [];
    foreach ($rows as $row) {
        $st = (string)($row[$field] ?? 'pending');
        $counts[$st] = ($counts[$st] ?? 0) + 1;
    }
    return $counts;
}

/**
 * @param list<array<string, mixed>> $workers
 * @param list<array<string, mixed>> $employers
 * @param list<array<string, mixed>> $vacancies
 * @return array<string, mixed>
 */
function gig_admin_dashboard_metrics(array $workers, array $employers, array $vacancies): array
{
    $wc = gig_admin_count_by_status($workers);
    $ec = gig_admin_count_by_status($employers);
    $vc = gig_admin_count_by_status($vacancies);
    $profileEdits = gig_admin_list_worker_profile_edits('pending');

    return [
        'workers_total' => count($workers),
        'workers_pending' => (int)($wc['pending'] ?? 0),
        'workers_approved' => (int)($wc['approved'] ?? 0),
        'workers_rejected' => (int)($wc['rejected'] ?? 0),
        'employers_total' => count($employers),
        'employers_pending' => 0,
        'employers_approved' => count($employers),
        'employers_rejected' => (int)($ec['rejected'] ?? 0),
        'vacancies_total' => count($vacancies),
        'vacancies_review' => (int)($vc['review'] ?? 0),
        'vacancies_active' => (int)($vc['active'] ?? 0),
        'vacancies_revision' => (int)($vc['revision'] ?? 0),
        'vacancies_rejected' => (int)($vc['rejected'] ?? 0),
        'vacancies_draft' => (int)($vc['draft'] ?? 0),
        'worker_profile_edits_pending' => count($profileEdits),
        'pending_all' => (int)($wc['pending'] ?? 0) + (int)($vc['review'] ?? 0) + count($profileEdits),
    ];
}

/** @return list<array{label: string, employers: int, vacancies: int}> */
function gig_admin_cluster_by_industry(array $employers, array $vacancies): array
{
    $clusters = [];
    foreach ($employers as $row) {
        $label = trim((string)($row['industry'] ?? ''));
        if ($label === '') {
            $label = 'Lainnya';
        }
        if (!isset($clusters[$label])) {
            $clusters[$label] = ['label' => $label, 'employers' => 0, 'vacancies' => 0];
        }
        $clusters[$label]['employers']++;
    }
    foreach ($vacancies as $row) {
        $label = trim((string)($row['location'] ?? ''));
        if ($label === '') {
            $label = 'Remote / Nasional';
        }
        if (!isset($clusters[$label])) {
            $clusters[$label] = ['label' => $label, 'employers' => 0, 'vacancies' => 0];
        }
        $clusters[$label]['vacancies']++;
    }
    $out = array_values($clusters);
    usort($out, static fn($a, $b) => ($b['employers'] + $b['vacancies']) <=> ($a['employers'] + $a['vacancies']));
    return array_slice($out, 0, 8);
}

function gig_worker_registration_status(string $username): ?string
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return null;
    }
    try {
        $stmt = $db->prepare("SELECT `status` FROM `gig_worker_registrations` WHERE `username` = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string)$row['status'] : null;
    } catch (Throwable $e) {
        return null;
    }
}

function gig_worker_profile_edit_key(string $username): string
{
    $clean = strtolower(trim($username));
    if (in_array($clean, ['theressaz@pasker.id', 'theressaz', 'theressa zaratrusha', 'tessa'], true) || str_contains($clean, 'theressa')) {
        return 'tessa';
    }
    $first = explode(' ', $clean)[0] ?? $clean;
    return preg_replace('/[^a-z0-9]+/i', '', $first) ?: $clean;
}

/** @return list<array<string,mixed>> */
function gig_admin_list_worker_profile_edits(?string $statusFilter = null): array
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        if (!isset($_SESSION['worker_profile_edit_requests']) || !is_array($_SESSION['worker_profile_edit_requests']) || empty($_SESSION['worker_profile_edit_requests'])) {
            $_SESSION['worker_profile_edit_requests'] = gig_admin_default_worker_profile_edits();
        }
        $rows = array_values($_SESSION['worker_profile_edit_requests']);
        if ($statusFilter !== null && $statusFilter !== '') {
            $rows = array_values(array_filter($rows, static fn($r) => ($r['status'] ?? 'pending') === $statusFilter));
        }
        usort($rows, static fn($a, $b) => ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0)));
        return $rows;
    }
    gig_admin_seed_workers($db);
    $sql = "SELECT * FROM `worker_profile_edit_requests`";
    $params = [];
    if ($statusFilter !== null && $statusFilter !== '') {
        $sql .= " WHERE `status` = :st";
        $params[':st'] = $statusFilter;
    }
    $sql .= " ORDER BY `created_at` DESC";
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row['proposed_payload'] = json_decode((string)($row['proposed_payload'] ?? ''), true) ?: [];
        }
        unset($row);
        return $rows;
    } catch (Throwable $e) {
        return [];
    }
}

function gig_create_worker_profile_edit_request(string $username, array $request): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return false;
    }
    $key = gig_worker_profile_edit_key($username);
    try {
        $stmt = $db->prepare(
            "INSERT INTO `worker_profile_edit_requests`
             (`worker_username`,`worker_email`,`edited_field`,`reason_code`,`reason_detail`,`change_summary`,`proposed_payload`,`status`,`admin_note`)
             VALUES (:u,:email,:field,:reason,:detail,:summary,:payload,'pending','')"
        );
        $stmt->execute([
            ':u' => $key,
            ':email' => (string)($request['worker_email'] ?? ''),
            ':field' => (string)($request['edited_field'] ?? ''),
            ':reason' => (string)($request['reason_code'] ?? ''),
            ':detail' => (string)($request['reason_detail'] ?? ''),
            ':summary' => (string)($request['change_summary'] ?? ''),
            ':payload' => json_encode($request['proposed_payload'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function gig_worker_has_pending_profile_edit(string $username): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if ($db === null) {
        return false;
    }
    $key = gig_worker_profile_edit_key($username);
    try {
        $stmt = $db->prepare(
            "SELECT `id` FROM `worker_profile_edit_requests`
             WHERE `worker_username` = :u AND `status` = 'pending'
             ORDER BY `id` DESC LIMIT 1"
        );
        $stmt->execute([':u' => $key]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

function gig_admin_set_worker_profile_edit_status(int $id, string $status, string $adminNote = ''): bool
{
    gig_admin_ensure_schema();
    $db = gig_db();
    if (!in_array($status, ['approved', 'rejected'], true)) {
        return false;
    }
    if ($db === null) {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        if (isset($_SESSION['worker_profile_edit_requests'])) {
            foreach ($_SESSION['worker_profile_edit_requests'] as &$ed) {
                if ((int)($ed['id'] ?? 0) === $id) {
                    $ed['status'] = $status;
                    $ed['admin_note'] = $adminNote;
                    $ed['reviewed_at'] = date('Y-m-d H:i:s');
                    return true;
                }
            }
            unset($ed);
        }
        return false;
    }
    try {
        $stmt = $db->prepare("SELECT * FROM `worker_profile_edit_requests` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        if ((string)$row['status'] !== 'pending') {
            return false;
        }

        if ($status === 'approved') {
            $payload = json_decode((string)$row['proposed_payload'], true) ?: [];
            $workerKey = (string)$row['worker_username'];
            $existing = gig_get_worker_registration($workerKey) ?? [];
            $data = [
                'bidang_keahlian' => $payload['bidang_keahlian'] ?? ($payload['title'] ?? ($existing['bidang_keahlian'] ?? '')),
                'skills' => $payload['skills'] ?? ($existing['skills'] ?? []),
                'contact_choice' => $payload['contact_choice'] ?? ($existing['contact_choice'] ?? 'new'),
                'contact_email' => $payload['contact_email'] ?? ($payload['email'] ?? ($existing['contact_email'] ?? '')),
                'contact_wa' => $payload['contact_wa'] ?? ($payload['wa'] ?? ($existing['contact_wa'] ?? '')),
                'previous_projects' => $payload['previous_projects'] ?? ($payload['experience'] ?? ($existing['previous_projects'] ?? [])),
                'portfolio' => $payload['portfolio'] ?? ($existing['portfolio'] ?? []),
                'video_url' => $payload['video_url'] ?? ($existing['video_url'] ?? ''),
                'social_media' => $payload['social_media'] ?? ($existing['social_media'] ?? []),
                'status' => 'approved',
                'display_name' => $payload['display_name'] ?? ($payload['name'] ?? ($existing['display_name'] ?? '')),
                'domicile' => $payload['domicile'] ?? ($payload['location'] ?? ($existing['domicile'] ?? '')),
                'profile_summary' => $payload['profile_summary'] ?? ($payload['proposal'] ?? ($existing['profile_summary'] ?? '')),
            ];
            if (!gig_save_worker_registration($workerKey, $data)) {
                return false;
            }
        }

        $up = $db->prepare(
            "UPDATE `worker_profile_edit_requests`
             SET `status` = :st, `admin_note` = :note, `reviewed_at` = NOW()
             WHERE `id` = :id"
        );
        $up->execute([':st' => $status, ':note' => $adminNote, ':id' => $id]);
        return $up->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}
