<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/worker-profiles.php';

function gig_katalog_data_path(): string
{
    return dirname(__DIR__) . '/data/katalog-services.json';
}

function gig_katalog_ensure_schema(): void
{
    $pdo = gig_db();
    if ($pdo === null) {
        return;
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `gig_service_catalogues` (
                `id` VARCHAR(60) PRIMARY KEY,
                `worker_id` VARCHAR(100) NOT NULL,
                `worker_name` VARCHAR(150) NOT NULL,
                `worker_avatar` VARCHAR(255) NOT NULL DEFAULT '',
                `worker_badge` VARCHAR(50) NOT NULL DEFAULT 'Super Seller',
                `worker_response_time` VARCHAR(50) NOT NULL DEFAULT '< 1 Jam',
                `category` VARCHAR(100) NOT NULL,
                `subcategory` VARCHAR(100) NOT NULL,
                `tag` VARCHAR(100) NOT NULL DEFAULT '',
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL DEFAULT '',
                `thumbnail` TEXT NOT NULL,
                `gallery` LONGTEXT NOT NULL,
                `rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
                `reviews_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `orders_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `price_starting` INT UNSIGNED NOT NULL DEFAULT 150000,
                `delivery_days` TINYINT UNSIGNED NOT NULL DEFAULT 2,
                `revisions` VARCHAR(50) NOT NULL DEFAULT '3x Revisi',
                `description` LONGTEXT NOT NULL,
                `packages` LONGTEXT NOT NULL,
                `client_requirements` TEXT NOT NULL,
                `faqs` LONGTEXT NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_worker (`worker_id`),
                INDEX idx_category (`category`),
                INDEX idx_status (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (Throwable $e) {
        // Safe silence if MySQL offline or insufficient privilege
    }
}

/**
 * Seed items representing realistic services matching Fastwork-like marketplace
 */
function gig_katalog_default_seeds(): array
{
    return [
        [
            'id' => 'jasa-logo-regla-01',
            'worker_id' => 'theressaz@pasker.id',
            'worker_name' => 'Theressa Zaratrusha',
            'worker_avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
            'worker_badge' => 'Super Seller',
            'worker_response_time' => '15 Menit',
            'category' => 'Desain Grafis',
            'subcategory' => 'Desain Logo',
            'tag' => 'Minimalis',
            'title' => 'Jasa Desain Logo Bisnis & Brand Identity Korporat Eksklusif',
            'slug' => 'jasa-desain-logo-bisnis-brand-identity-eksklusif',
            'thumbnail' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80',
            'gallery' => [
                'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1600132806370-bf17e65e942f?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1572044162444-ad60f128bdea?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=800&auto=format&fit=crop&q=80'
            ],
            'rating' => 4.95,
            'reviews_count' => 148,
            'orders_count' => 312,
            'price_starting' => 150000,
            'delivery_days' => 2,
            'revisions' => 'Bebas Revisi',
            'description' => "Halo! Selamat datang di profil deliverables layanan desain identitas visual profesional kami.\n\nLayanan ini dirancang khusus untuk perusahaan, brand, dan unit bisnis yang membutuhkan identitas merek otentik, memikat, dan terstandarisasi untuk berbagai kebutuhan proyek.\n\n✨ Keunggulan Deliverables Kami:\n• Desain 100% original, dirancang terstruktur dari konsep dasar\n• Berorientasi pada karakter industri dan target audiens perusahaan\n• Pengerjaan terstruktur, profesional, dan sesuai timeline perusahaan\n• File siap cetak (300 DPI) dan siap aplikasi digital korporat\n• Hak cipta penuh langsung menjadi aset perusahaan",
            'packages' => [
                'basic' => [
                    'name' => 'Paket Basic (Startup)',
                    'price' => 150000,
                    'delivery_days' => 2,
                    'revisions' => '2x Revisi',
                    'desc' => 'Cocok untuk usaha rintisan atau online shop pemula yang butuh logo cepat dan rapi.',
                    'features' => [
                        '2 Konsep Desain Awal',
                        'File Resolusi Tinggi JPG & PNG Transparan',
                        'Desain 300 DPI Siap Cetak',
                        'Pratinjau Mockup 3D',
                        'Hak Cipta Penggunaan Komersial'
                    ]
                ],
                'standard' => [
                    'name' => 'Paket Standar (Bisnis UMKM)',
                    'price' => 350000,
                    'delivery_days' => 3,
                    'revisions' => '5x Revisi',
                    'desc' => 'Pilihan terpopuler! Paket lengkap untuk bisnis yang membutuhkan aset identitas merek profesional.',
                    'features' => [
                        '3 Konsep Desain Awal Eksklusif',
                        'File Master AI, EPS, SVG, PDF, PNG & JPG',
                        'Revisi Desain hingga 5 kali',
                        'Palet Warna Resmi & Panduan Tipografi Font',
                        'Mockup Stationery (Kartu Nama & Kop Surat)',
                        'Hak Cipta Komersial Penuh 100%'
                    ]
                ],
                'premium' => [
                    'name' => 'Paket Premium (Korporat / Brand Guidelines)',
                    'price' => 750000,
                    'delivery_days' => 5,
                    'revisions' => 'Bebas Revisi (Unlimited)',
                    'desc' => 'Solusi all-in-one lengkap untuk brand premium dengan buku panduan grafis (Brand Guidelines Book).',
                    'features' => [
                        '5 Konsep Desain Awal Berbeda Karakter',
                        'Revisi Tanpa Batas sampai Puas',
                        'Buku Brand Guidelines PDF (Aturan Logo, Spacing, Do & Don\'t)',
                        'Social Media Kit (Profile Icon + Banner Header)',
                        'Stationery Kit Lengkap (Kartu Nama, Map, Amplop, Stempel)',
                        'Semua File Master Vektor Vector AI/EPS/PDF/SVG',
                        'Prioritas Antrian & Support Pasca Serah Terima 30 Hari'
                    ]
                ]
            ],
            'client_requirements' => "1. Nama brand/bisnis dan tagline (jika ada)\n2. Bidang usaha dan target pasar/konsumen utama\n3. Preferensi gaya (Minimalis, Elegan, Modern, Playful, Vintage)\n4. Warna yang disukai atau dihindari\n5. Contoh referensi logo yang disukai (jika ada)",
            'faqs' => [
                ['q' => 'Apakah logo ini 100% original?', 'a' => 'Ya, seluruh konsep dirancang dari sketsa orisinal tanpa menggunakan clipart atau template gratisan.'],
                ['q' => 'Apakah saya mendapatkan file master vektor?', 'a' => 'Ya, untuk Paket Standar dan Premium Anda akan menerima file master AI, EPS, dan SVG yang dapat diperbesar tanpa pecah.'],
                ['q' => 'Bagaimana jika saya tidak cocok dengan konsep awal?', 'a' => 'Kami menyediakan revisi sesuai paket yang dipilih dan siap mendiskusikan penyesuaian gaya.'],
                ['q' => 'Berapa lama pengerjaan draft awal?', 'a' => 'Konsep awal biasanya kami kirimkan dalam 24-48 jam setelah brief disetujui.']
            ],
            'status' => 'active',
            'created_at' => '2026-10-15 09:30:00'
        ],
        [
            'id' => 'jasa-brand-axen-02',
            'worker_id' => 'ahmad.fauzi@pasker.id',
            'worker_name' => 'Ahmad Fauzi',
            'worker_avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
            'worker_badge' => 'Top Rated',
            'worker_response_time' => '30 Menit',
            'category' => 'Desain Grafis',
            'subcategory' => 'Desain Logo',
            'tag' => 'Modern',
            'title' => 'Desain Logo Brand Guidelines & Corporate Visual Identity Lengkap',
            'slug' => 'desain-logo-brand-guidelines-corporate-visual-identity',
            'thumbnail' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop&q=80',
            'gallery' => [
                'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1586717791821-3f44a563fa4c?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1542744094-3a31f272c490?w=800&auto=format&fit=crop&q=80'
            ],
            'rating' => 4.98,
            'reviews_count' => 98,
            'orders_count' => 210,
            'price_starting' => 350000,
            'delivery_days' => 3,
            'revisions' => '5x Revisi',
            'description' => "Spesialis identitas korporat modern & teknologi. Berpengalaman menangani re-branding 80+ perusahaan teknologi, agensi digital, dan startup finansial.\n\nKami mengutamakan filosofi bentuk geometris presisi, sistem warna terstandarisasi, dan penerapan lintas media dari aplikasi mobile hingga papan reklame besar.",
            'packages' => [
                'basic' => [
                    'name' => 'Logo Essential',
                    'price' => 350000,
                    'delivery_days' => 3,
                    'revisions' => '3x Revisi',
                    'desc' => 'Identitas logo modern berbasis vektor dengan file master lengkap.',
                    'features' => ['2 Konsep Desain', 'Master File AI/EPS/PNG', 'Palet Warna Hex/RGB/CMYK', '3D Mockup']
                ],
                'standard' => [
                    'name' => 'Corporate Identity',
                    'price' => 650000,
                    'delivery_days' => 4,
                    'revisions' => '6x Revisi',
                    'desc' => 'Logo + Brand Guidelines Ringkas + Stationery Bisnis.',
                    'features' => ['3 Konsep Desain', 'Master Files Lengkap', 'Stationery Kit', 'Brand Book 10 Halaman', 'Social Media Profile']
                ],
                'premium' => [
                    'name' => 'Full Corporate Suite',
                    'price' => 1200000,
                    'delivery_days' => 7,
                    'revisions' => 'Unlimited',
                    'desc' => 'Solusi menyeluruh untuk instansi/perusahaan siap ekspansi.',
                    'features' => ['4 Konsep Eksklusif', 'Brand Guidelines Komprehensif', 'Stationery + Seragam Mockup', 'Aset Medsos & Iklan', 'Lisensi Penuh']
                ]
            ],
            'client_requirements' => "Profil perusahaan, nilai inti (core values), target pasar, dan warna dominan.",
            'faqs' => [
                ['q' => 'Apakah dapat faktur/invoice resmi?', 'a' => 'Tentu, tersedia tanda terima dan detail transaksi resmi di KarirHub.']
            ],
            'status' => 'active',
            'created_at' => '2026-10-10 11:20:00'
        ],
        [
            'id' => 'jasa-mascot-03',
            'worker_id' => 'budi.santoso@pasker.id',
            'worker_name' => 'Budi Santoso',
            'worker_avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
            'worker_badge' => 'Super Seller',
            'worker_response_time' => '20 Menit',
            'category' => 'Desain Grafis',
            'subcategory' => 'Desain Logo',
            'tag' => 'Mascot / Karakter',
            'title' => 'Jasa Desain Logo Karakter, Mascot & Ilustrasi Custom F&B / Esport',
            'slug' => 'jasa-desain-logo-karakter-mascot-ilustrasi-custom',
            'thumbnail' => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=80',
            'gallery' => [
                'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop&q=80'
            ],
            'rating' => 4.88,
            'reviews_count' => 64,
            'orders_count' => 140,
            'price_starting' => 250000,
            'delivery_days' => 3,
            'revisions' => '3x Revisi',
            'description' => "Ingin brand kuliner, kedai kopi, merchandise, atau tim gaming Anda memiliki maskot lucu, keren, dan berciri khas tinggi? Saya ilustrator profesional dengan gaya vector character yang ekspresif dan ramah anak muda.",
            'packages' => [
                'basic' => [
                    'name' => 'Mascot Head Only',
                    'price' => 250000,
                    'delivery_days' => 3,
                    'revisions' => '3x Revisi',
                    'desc' => 'Desain kepala maskot / avatar karakter resolusi tinggi.',
                    'features' => ['1 Konsep Sketsa Halus', 'Full Color Shading', 'File PNG Transparan & JPG HD', 'Mockup Kaos/Sticker']
                ],
                'standard' => [
                    'name' => 'Full Body Mascot & Typography',
                    'price' => 450000,
                    'delivery_days' => 4,
                    'revisions' => '5x Revisi',
                    'desc' => 'Karakter utuh seluruh badan + nama brand/logo terintegrasi.',
                    'features' => ['Karakter Full Body Dinamis', 'Master File AI/EPS/PSD', 'Font Typography Khusus', 'Revisi hingga 5x']
                ],
                'premium' => [
                    'name' => 'Mascot Brand Kit (3 Poses)',
                    'price' => 850000,
                    'delivery_days' => 6,
                    'revisions' => 'Unlimited',
                    'desc' => 'Maskot dalam 3 pose berbeda (Menyapa, Memegang Produk, Promo) + Hak Cipta Penuh.',
                    'features' => ['3 Pose Maskot Kustom', 'Master AI Vektor 100%', 'Sticker Pack Siap Cetak', 'Commercial License Hak Cipta Eksklusif']
                ]
            ],
            'client_requirements' => "Hewan/tokoh karakter yang diinginkan, tema kuliner/gaming, dan ekspresi karakter.",
            'faqs' => [
                ['q' => 'Apakah gambar diarsir manual atau vektor?', 'a' => 'Pertama digambar manual di tablet digital lalu di-tracing menjadi vektor AI/EPS yang tajam sempurna.']
            ],
            'status' => 'active',
            'created_at' => '2026-10-18 14:10:00'
        ],
        [
            'id' => 'jasa-web-landing-04',
            'worker_id' => 'theressaz@pasker.id',
            'worker_name' => 'Theressa Zaratrusha',
            'worker_avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
            'worker_badge' => 'Super Seller',
            'worker_response_time' => '15 Menit',
            'category' => 'Web & Pemrograman',
            'subcategory' => 'Landing Page',
            'tag' => 'Landing Page',
            'title' => 'Jasa Pembuatan Landing Page Modern, Responsif & Cepat Konversi Penjualan',
            'slug' => 'jasa-pembuatan-landing-page-modern-responsif-cepat-konversi',
            'thumbnail' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop&q=80',
            'gallery' => [
                'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1522542550221-31fd19575a2d?w=800&auto=format&fit=crop&q=80'
            ],
            'rating' => 4.97,
            'reviews_count' => 86,
            'orders_count' => 175,
            'price_starting' => 450000,
            'delivery_days' => 3,
            'revisions' => '3x Revisi',
            'description' => "Landing page adalah ujung tombak penjualan produk dan jasa Anda. Saya membuat halaman arahan (landing page) berkecepatan tinggi, ramah tampilan ponsel (mobile-first), dan dirancang khusus untuk memaksimalkan rasio konversi (klik WhatsApp / formulir pendaftaran / checkout).\n\nFitur bawaan:\n• Desain UI/UX eksklusif sesuai identitas bisnis Anda\n• Kecepatan load page optimal (< 2 detik)\n• Tombol CTA WhatsApp langsung dengan pesan otomatis\n• Terintegrasi formulir pendaftaran atau pesanan\n• Setup SEO meta dasar Google",
            'packages' => [
                'basic' => [
                    'name' => 'Single Page Promo',
                    'price' => 450000,
                    'delivery_days' => 3,
                    'revisions' => '3x Revisi',
                    'desc' => 'Landing page satu halaman (3-4 section) untuk promo produk tunggal atau event.',
                    'features' => ['3-4 Section Halaman', 'Desain Responsif Mobile & Desktop', 'Tombol WhatsApp Otomatis', 'Free Setup Domain/Hosting Klien']
                ],
                'standard' => [
                    'name' => 'High-Converting Business',
                    'price' => 850000,
                    'delivery_days' => 5,
                    'revisions' => '5x Revisi',
                    'desc' => 'Landing page lengkap (6-8 section) dengan copywriting persuasif dan formulir pesanan.',
                    'features' => ['6-8 Section Lengkap', 'Formulir Order / Kontak', 'Optimasi Kecepatan Google PageSpeed', 'Integrasi Pixel Meta / Google Analytics', 'Copywriting & Penulisan Headline']
                ],
                'premium' => [
                    'name' => 'Full Funnel & Lead Gen',
                    'price' => 1500000,
                    'delivery_days' => 7,
                    'revisions' => 'Bebas Revisi',
                    'desc' => 'Solusi landing page korporat / funnel iklan lengkap dengan A/B test layout dan support teknis.',
                    'features' => ['Multi-section + Halaman Thank You Page', 'Integrasi CRM / Spreadsheet Otomatis', 'Animasi Interaktif Halus', 'Bebas Revisi Desain', 'Garansi Bug Fixing 60 Hari']
                ]
            ],
            'client_requirements' => "1. Detail produk/jasa yang dijual\n2. Teks atau materi promo (jika sudah ada)\n3. Nomor WhatsApp admin tujuan\n4. Akses domain/hosting (atau kami bantu siapkan)",
            'faqs' => [
                ['q' => 'Apakah sudah termasuk domain dan hosting?', 'a' => 'Biaya belum termasuk domain/hosting, namun kami memberikan rekomendasi hosting terbaik dan siap menginstalkannya secara cuma-cuma.'],
                ['q' => 'Apakah mudah diedit sendiri nantinya?', 'a' => 'Ya, kami buatkan dengan CMS atau kode rapi disertai panduan video cara mengganti teks dan foto secara mandiri.']
            ],
            'status' => 'active',
            'created_at' => '2026-10-22 16:45:00'
        ],
        [
            'id' => 'jasa-video-reels-05',
            'worker_id' => 'rina.lestari@pasker.id',
            'worker_name' => 'Rina Lestari',
            'worker_avatar' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
            'worker_badge' => 'Fast Response',
            'worker_response_time' => '10 Menit',
            'category' => 'Video & Animasi',
            'subcategory' => 'Video Editing',
            'tag' => 'Shorts & TikTok',
            'title' => 'Video Editing TikTok, Reels, YouTube Shorts Kreatif dengan Subtitle Dinamis',
            'slug' => 'video-editing-tiktok-reels-youtube-shorts-kreatif-subtitle-dinamis',
            'thumbnail' => 'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?w=800&auto=format&fit=crop&q=80',
            'gallery' => [
                'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?w=800&auto=format&fit=crop&q=80'
            ],
            'rating' => 4.92,
            'reviews_count' => 115,
            'orders_count' => 280,
            'price_starting' => 120000,
            'delivery_days' => 1,
            'revisions' => '3x Revisi',
            'description' => "Ubah rekaman mentah video Anda menjadi konten vertikal yang viral, adiktif, dan mengundang interaksi! Dilengkapi teks subtitle bergerak warna-warni (Alex Hormozi style), sound effect sinematik, zoom in/out transisi, dan pemilihan b-roll yang pas.",
            'packages' => [
                'basic' => [
                    'name' => '1 Video Singkat (Up to 60s)',
                    'price' => 120000,
                    'delivery_days' => 1,
                    'revisions' => '2x Revisi',
                    'desc' => '1 video pendek durasi maksimal 60 detik dengan subtitle menarik.',
                    'features' => ['Pemotongan jeda kosong/filler word', 'Animated Subtitle / Caption Pop Up', 'Sound FX & Background Music Bebas Hak Cipta', 'Export Resolusi Full HD 1080x1920']
                ],
                'standard' => [
                    'name' => 'Paket Hemat 3 Video',
                    'price' => 320000,
                    'delivery_days' => 2,
                    'revisions' => '4x Revisi',
                    'desc' => '3 video vertikal untuk stok postingan akun media sosial Anda.',
                    'features' => ['3 Video siap tayang', 'Color Grading Menarik', 'B-Roll & Visual Efek Hook Awal', 'Thumbnail Cover Menarik']
                ],
                'premium' => [
                    'name' => 'Paket Bulanan 10 Video',
                    'price' => 950000,
                    'delivery_days' => 5,
                    'revisions' => 'Bebas Revisi',
                    'desc' => 'Solusi ideal kreator/brand untuk konsistensi posting konten harian.',
                    'features' => ['10 Video Shorts/Reels/TikTok', 'Riset Trend Audio & Gaya Hook', 'Revisi Cepat Prioritas', 'Pemberian Aset Mentahan Project']
                ]
            ],
            'client_requirements' => "Video mentahan, naskah/poin yang ingin ditekankan, dan referensi akun yang disukai.",
            'faqs' => [
                ['q' => 'Berapa lama pengerjaan 1 video?', 'a' => 'Dalam waktu 24 jam video sudah siap Anda review!']
            ],
            'status' => 'active',
            'created_at' => '2026-10-25 10:00:00'
        ],
        [
            'id' => 'jasa-packaging-pack-06',
            'worker_id' => 'dimas.prasetyo@pasker.id',
            'worker_name' => 'Dimas Prasetyo',
            'worker_avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
            'worker_badge' => 'Top Rated',
            'worker_response_time' => '40 Menit',
            'category' => 'Desain Grafis',
            'subcategory' => 'Desain Kemasan',
            'tag' => 'Kemasan & Label',
            'title' => 'Desain Kemasan Produk, Box & Label Makanan/Minuman UMKM Siap Cetak (Dieline)',
            'slug' => 'desain-kemasan-produk-box-label-makanan-minuman-umkm-siap-cetak',
            'thumbnail' => 'https://images.unsplash.com/photo-1544816155-12df9643f363?w=800&auto=format&fit=crop&q=80',
            'gallery' => [
                'https://images.unsplash.com/photo-1544816155-12df9643f363?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1589939705384-5185137a7f0f?w=800&auto=format&fit=crop&q=80'
            ],
            'rating' => 4.96,
            'reviews_count' => 79,
            'orders_count' => 160,
            'price_starting' => 275000,
            'delivery_days' => 3,
            'revisions' => '4x Revisi',
            'description' => "Kemasan yang menarik adalah sales pertama produk Anda di rak toko maupun e-commerce. Saya mendesain packaging box, pouch standing, label botol, kaleng, hingga sleeve packaging dengan ukuran akurat (dieline cetak) siap serah ke percetakan.",
            'packages' => [
                'basic' => [
                    'name' => 'Label Stiker Sederhana',
                    'price' => 275000,
                    'delivery_days' => 3,
                    'revisions' => '3x Revisi',
                    'desc' => 'Desain stiker toples / botol / pouch satu sisi.',
                    'features' => ['1 Konsep Desain Matang', 'File Cetak PDF CMYK 300 DPI', 'Mockup Produk Realistis', 'File Vector Illustrator']
                ],
                'standard' => [
                    'name' => 'Full Packaging Box / Pouch',
                    'price' => 550000,
                    'delivery_days' => 4,
                    'revisions' => '5x Revisi',
                    'desc' => 'Desain dus kotak kemasan luar / standing pouch depan-belakang dengan pola potong (dieline).',
                    'features' => ['Pola Garis Potong (Dieline) Akurat', 'Tabel Nilai Gizi & Barcode Ready', '3D Mockup Sudut Beragam', 'Revisi hingga 5 kali']
                ],
                'premium' => [
                    'name' => 'Lini Produk Seri (3 Varian Rasa)',
                    'price' => 1100000,
                    'delivery_days' => 6,
                    'revisions' => 'Unlimited',
                    'desc' => 'Satu seri kemasan lengkap dengan 3 varian rasa/warna berbeda.',
                    'features' => ['3 Desain Varian Rasa', 'Panduan Cetak untuk Percetakan', 'Master Aset AI/PDF/Font', 'Komunikasi Langsung dengan Percetakan Jika Dibutuhkan']
                ]
            ],
            'client_requirements' => "Ukuran kemasan (P x L x T), logo brand, teks deskripsi produk, komposisi, nomor izin/PIRT jika ada.",
            'faqs' => [
                ['q' => 'Apakah file langsung bisa dikirim ke percetakan?', 'a' => 'Ya, seluruh file kami ekspor dengan profil warna CMYK dan outline font sehingga aman tanpa error font hilang.']
            ],
            'status' => 'active',
            'created_at' => '2026-10-12 08:30:00'
        ],
        [
            'id' => 'jasa-vintage-retro-07',
            'worker_id' => 'bayu.samudra@pasker.id',
            'worker_name' => 'Bayu Samudra',
            'worker_avatar' => 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80',
            'worker_badge' => 'Terverifikasi',
            'worker_response_time' => '1 Jam',
            'category' => 'Desain Grafis',
            'subcategory' => 'Desain Logo',
            'tag' => 'Vintage / Retro',
            'title' => 'Desain Typography, Vintage & Retro Badge Logo Unik untuk Kafe & Distro',
            'slug' => 'desain-typography-vintage-retro-badge-logo-unik',
            'thumbnail' => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=800&auto=format&fit=crop&q=80',
            'gallery' => [
                'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1524758631624-e2822e304c36?w=800&auto=format&fit=crop&q=80'
            ],
            'rating' => 4.91,
            'reviews_count' => 52,
            'orders_count' => 110,
            'price_starting' => 180000,
            'delivery_days' => 2,
            'revisions' => '3x Revisi',
            'description' => "Gaya klasik, rustik, dan vintage selalu memiliki jiwa tersendiri. Cocok untuk coffeeshop, barbershop, apparel distro, komunitas motor, dan produk kerajinan tangan.",
            'packages' => [
                'basic' => [
                    'name' => 'Badge Emblem Sederhana',
                    'price' => 180000,
                    'delivery_days' => 2,
                    'revisions' => '2x Revisi',
                    'desc' => 'Desain emblem retro monokrom / dua warna.',
                    'features' => ['1 Konsep Desain', 'File JPG & PNG Transparan', 'Mockup Kaos/Sticker']
                ],
                'standard' => [
                    'name' => 'Handcrafted Vintage Classic',
                    'price' => 380000,
                    'delivery_days' => 3,
                    'revisions' => '4x Revisi',
                    'desc' => 'Desain detail ilustrasi vintage bertekstur texture grit.',
                    'features' => ['2 Konsep Desain', 'Master File AI/EPS/PDF', 'Tekstur Distressed/Grunge', 'Hak Penggunaan Komersil']
                ],
                'premium' => [
                    'name' => 'Complete Apparel & Merch Kit',
                    'price' => 700000,
                    'delivery_days' => 4,
                    'revisions' => 'Unlimited',
                    'desc' => 'Logo + Desain Sablon Kaos Belakang + Hangtag & Label Baju.',
                    'features' => ['3 Variasi Desain', 'Separasi Warna Sablon Siap Cetak', 'Aset Vektor Bebas Skala', 'Mockup Distro Realistis']
                ]
            ],
            'client_requirements' => "Nama brand, tahun didirikan (Est. XXXX), tagline, dan elemen simbol yang diinginkan.",
            'faqs' => [
                ['q' => 'Apakah file bisa disablon langsung?', 'a' => 'Ya, kami sediakan file separasi warna jika dibutuhkan oleh konveksi Anda.']
            ],
            'status' => 'active',
            'created_at' => '2026-10-14 13:00:00'
        ],
        [
            'id' => 'jasa-copy-seo-08',
            'worker_id' => 'dian.kartika@pasker.id',
            'worker_name' => 'Dian Kartika',
            'worker_avatar' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
            'worker_badge' => 'Super Seller',
            'worker_response_time' => '15 Menit',
            'category' => 'Penulisan & Terjemahan',
            'subcategory' => 'Artikel SEO',
            'tag' => 'Artikel SEO',
            'title' => 'Jasa Penulisan Artikel SEO Friendly & Copywriting Website Berkualitas Human-Written',
            'slug' => 'jasa-penulisan-artikel-seo-friendly-copywriting-website',
            'thumbnail' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=800&auto=format&fit=crop&q=80',
            'gallery' => [
                'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=800&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1488190211105-8b0e65b80b4e?w=800&auto=format&fit=crop&q=80'
            ],
            'rating' => 4.94,
            'reviews_count' => 93,
            'orders_count' => 310,
            'price_starting' => 95000,
            'delivery_days' => 1,
            'revisions' => '3x Revisi',
            'description' => "Artikel organik yang ramah mesin pencari Google sekaligus nyaman dibaca manusia (bukan hasil translate mentah AI tanpa jiwa). Dioptimalkan dengan riset kata kunci (LSI keywords), kaidah SEO on-page, heading H1-H3 rapi, dan bebas plagiarisme 100%.",
            'packages' => [
                'basic' => [
                    'name' => '1 Artikel 800 - 1000 Kata',
                    'price' => 95000,
                    'delivery_days' => 1,
                    'revisions' => '2x Revisi',
                    'desc' => '1 artikel SEO mendalam dengan riset kata kunci dan meta deskripsi.',
                    'features' => ['800-1000 Kata Berkualitas', 'Lolos Copyscape 100%', 'Meta Title & Meta Description', 'Struktur Heading H1-H3 Rapi']
                ],
                'standard' => [
                    'name' => 'Paket Blog Booster (5 Artikel)',
                    'price' => 425000,
                    'delivery_days' => 3,
                    'revisions' => '3x Revisi',
                    'desc' => '5 artikel pilar untuk meningkatkan peringkat website bisnis Anda di Google.',
                    'features' => ['5 Artikel @1000 Kata', 'Riset Kata Kunci Potensial', 'Gambar Ilustrasi Bebas Lisensi', 'Internal Linking Strategy']
                ],
                'premium' => [
                    'name' => 'Paket Konten Bulanan (12 Artikel)',
                    'price' => 990000,
                    'delivery_days' => 7,
                    'revisions' => 'Unlimited',
                    'desc' => 'Pasokan konten rutin sebulan siap publikasi ke WordPress Anda.',
                    'features' => ['12 Artikel Mendalam (@1200 Kata)', 'Direct Upload & Format di WordPress', 'Kalender Konten Terencana', 'Laporan Peringkat SEO Ringkas']
                ]
            ],
            'client_requirements' => "Topik bahasan, target kata kunci utama (jika ada), dan website tujuan.",
            'faqs' => [
                ['q' => 'Apakah artikel ditulis manual?', 'a' => '100% ditulis dan diteliti oleh copywriter berpengalaman dengan pemeriksaan fakta akurat.']
            ],
            'status' => 'active',
            'created_at' => '2026-10-19 15:20:00'
        ]
    ];
}

/**
 * Retrieve all catalogue services
 * Priority: DB -> local JSON file -> default seeds + session
 */
function gig_katalog_load_all(): array
{
    gig_katalog_ensure_schema();
    $pdo = gig_db();

    if ($pdo !== null) {
        try {
            $stmt = $pdo->query("SELECT * FROM `gig_service_catalogues` ORDER BY `created_at` DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $results = [];
                foreach ($rows as $row) {
                    $results[] = [
                        'id' => (string)$row['id'],
                        'worker_id' => (string)$row['worker_id'],
                        'worker_name' => (string)$row['worker_name'],
                        'worker_avatar' => (string)$row['worker_avatar'],
                        'worker_badge' => (string)$row['worker_badge'],
                        'worker_response_time' => (string)$row['worker_response_time'],
                        'category' => (string)$row['category'],
                        'subcategory' => (string)$row['subcategory'],
                        'tag' => (string)$row['tag'],
                        'title' => (string)$row['title'],
                        'slug' => (string)$row['slug'],
                        'thumbnail' => (string)$row['thumbnail'],
                        'gallery' => json_decode((string)$row['gallery'], true) ?: [],
                        'rating' => (float)$row['rating'],
                        'reviews_count' => (int)$row['reviews_count'],
                        'orders_count' => (int)$row['orders_count'],
                        'price_starting' => (int)$row['price_starting'],
                        'delivery_days' => (int)$row['delivery_days'],
                        'revisions' => (string)$row['revisions'],
                        'description' => (string)$row['description'],
                        'packages' => json_decode((string)$row['packages'], true) ?: [],
                        'client_requirements' => (string)$row['client_requirements'],
                        'faqs' => json_decode((string)$row['faqs'], true) ?: [],
                        'status' => (string)$row['status'],
                        'created_at' => (string)$row['created_at'],
                    ];
                }
                return $results;
            }
        } catch (Throwable $e) {
            // DB read error, fall through to json
        }
    }

    // Local JSON file storage
    $jsonPath = gig_katalog_data_path();
    if (file_exists($jsonPath)) {
        $content = file_get_contents($jsonPath);
        if ($content) {
            $decoded = json_decode($content, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }
    }

    // If file doesn't exist, seed with default catalog
    $seeds = gig_katalog_default_seeds();
    @file_put_contents($jsonPath, json_encode($seeds, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $seeds;
}

/**
 * Find single catalogue by ID or Slug
 */
function gig_katalog_find(string $idOrSlug): ?array
{
    $all = gig_katalog_load_all();
    $target = strtolower(trim($idOrSlug));

    foreach ($all as $item) {
        if (strtolower((string)($item['id'] ?? '')) === $target || strtolower((string)($item['slug'] ?? '')) === $target) {
            return $item;
        }
    }

    return null;
}

/**
 * Filter catalogue items belonging to a specific worker
 */
function gig_katalog_by_worker(string $workerId): array
{
    $all = gig_katalog_load_all();
    $needle = strtolower(trim($workerId));

    $matches = [];
    foreach ($all as $item) {
        $wId = strtolower(trim((string)($item['worker_id'] ?? '')));
        // Match canonical or alias
        if ($wId === $needle ||
           (in_array($needle, ['tessa', 'theressa', 'theressaz@pasker.id', 'theressa zaratrusha']) &&
            in_array($wId, ['tessa', 'theressa', 'theressaz@pasker.id', 'theressa zaratrusha']))) {
            $matches[] = $item;
        }
    }

    return $matches;
}

/**
 * Save (create or update) a catalogue item
 */
function gig_katalog_save(array $service): bool
{
    gig_katalog_ensure_schema();

    if (empty($service['id'])) {
        $service['id'] = 'jasa-' . bin2hex(random_bytes(6));
    }
    if (empty($service['slug'])) {
        $service['slug'] = preg_replace('/[^a-z0-9\-]+/', '-', strtolower(trim((string)($service['title'] ?? 'jasa'))));
        $service['slug'] = trim($service['slug'], '-');
    }
    if (empty($service['created_at'])) {
        $service['created_at'] = date('Y-m-d H:i:s');
    }

    $savedDb = false;
    $pdo = gig_db();
    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO `gig_service_catalogues`
                (`id`, `worker_id`, `worker_name`, `worker_avatar`, `worker_badge`, `worker_response_time`, `category`, `subcategory`, `tag`, `title`, `slug`, `thumbnail`, `gallery`, `rating`, `reviews_count`, `orders_count`, `price_starting`, `delivery_days`, `revisions`, `description`, `packages`, `client_requirements`, `faqs`, `status`, `created_at`)
                VALUES (:id, :worker_id, :worker_name, :worker_avatar, :worker_badge, :worker_response_time, :category, :subcategory, :tag, :title, :slug, :thumbnail, :gallery, :rating, :reviews_count, :orders_count, :price_starting, :delivery_days, :revisions, :description, :packages, :client_requirements, :faqs, :status, :created_at)
                ON DUPLICATE KEY UPDATE
                `worker_name` = VALUES(`worker_name`),
                `worker_avatar` = VALUES(`worker_avatar`),
                `worker_badge` = VALUES(`worker_badge`),
                `worker_response_time` = VALUES(`worker_response_time`),
                `category` = VALUES(`category`),
                `subcategory` = VALUES(`subcategory`),
                `tag` = VALUES(`tag`),
                `title` = VALUES(`title`),
                `slug` = VALUES(`slug`),
                `thumbnail` = VALUES(`thumbnail`),
                `gallery` = VALUES(`gallery`),
                `rating` = VALUES(`rating`),
                `reviews_count` = VALUES(`reviews_count`),
                `orders_count` = VALUES(`orders_count`),
                `price_starting` = VALUES(`price_starting`),
                `delivery_days` = VALUES(`delivery_days`),
                `revisions` = VALUES(`revisions`),
                `description` = VALUES(`description`),
                `packages` = VALUES(`packages`),
                `client_requirements` = VALUES(`client_requirements`),
                `faqs` = VALUES(`faqs`),
                `status` = VALUES(`status`)
            ");
            $stmt->execute([
                ':id' => $service['id'],
                ':worker_id' => $service['worker_id'],
                ':worker_name' => $service['worker_name'],
                ':worker_avatar' => $service['worker_avatar'] ?? '',
                ':worker_badge' => $service['worker_badge'] ?? 'Super Seller',
                ':worker_response_time' => $service['worker_response_time'] ?? '< 1 Jam',
                ':category' => $service['category'] ?? 'Desain Grafis',
                ':subcategory' => $service['subcategory'] ?? 'Desain Logo',
                ':tag' => $service['tag'] ?? '',
                ':title' => $service['title'],
                ':slug' => $service['slug'],
                ':thumbnail' => $service['thumbnail'],
                ':gallery' => json_encode($service['gallery'] ?? [], JSON_UNESCAPED_UNICODE),
                ':rating' => (float)($service['rating'] ?? 5.0),
                ':reviews_count' => (int)($service['reviews_count'] ?? 0),
                ':orders_count' => (int)($service['orders_count'] ?? 0),
                ':price_starting' => (int)($service['price_starting'] ?? 150000),
                ':delivery_days' => (int)($service['delivery_days'] ?? 2),
                ':revisions' => $service['revisions'] ?? '3x Revisi',
                ':description' => $service['description'] ?? '',
                ':packages' => json_encode($service['packages'] ?? [], JSON_UNESCAPED_UNICODE),
                ':client_requirements' => $service['client_requirements'] ?? '',
                ':faqs' => json_encode($service['faqs'] ?? [], JSON_UNESCAPED_UNICODE),
                ':status' => $service['status'] ?? 'active',
                ':created_at' => $service['created_at'],
            ]);
            $savedDb = true;
        } catch (Throwable $e) {
            $savedDb = false;
        }
    }

    // Always keep JSON file updated as well
    $jsonPath = gig_katalog_data_path();
    $all = gig_katalog_load_all();
    $found = false;
    foreach ($all as $idx => $existing) {
        if ($existing['id'] === $service['id']) {
            $all[$idx] = array_merge($existing, $service);
            $found = true;
            break;
        }
    }
    if (!$found) {
        array_unshift($all, $service);
    }

    @file_put_contents($jsonPath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return true;
}

/**
 * Delete a catalogue item
 */
function gig_katalog_delete(string $id): bool
{
    $pdo = gig_db();
    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `gig_service_catalogues` WHERE `id` = :id");
            $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {}
    }

    $jsonPath = gig_katalog_data_path();
    $all = gig_katalog_load_all();
    $all = array_values(array_filter($all, fn($item) => $item['id'] !== $id));
    @file_put_contents($jsonPath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

/**
 * Format IDR Rupiah
 */
function gig_format_rupiah(int|float $amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Extract 1-2 letter initials from a person or brand name
 */
function gig_katalog_initials(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return 'W';
    }
    $parts = preg_split('/\s+/', $name);
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    return strtoupper(substr($parts[0], 0, min(2, strlen($parts[0]))));
}

/**
 * Render a circular letter avatar matching other pages in KarirHub prototype
 */
function gig_katalog_render_avatar(string $name, int $size = 28, string $fontSize = '0.74rem', string $extraStyles = ''): string
{
    $initials = gig_katalog_initials($name);
    return sprintf(
        '<div style="width:%dpx;height:%dpx;border-radius:50%%;background:#2563eb;color:#ffffff;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:%s;flex-shrink:0;letter-spacing:-0.2px;line-height:1;user-select:none;%s">%s</div>',
        $size,
        $size,
        $fontSize,
        $extraStyles,
        htmlspecialchars($initials, ENT_QUOTES, 'UTF-8')
    );
}

