<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/user-avatars.php';
require_once __DIR__ . '/includes/admin-session.php';
gig_admin_session_normalize();

$isLoggedIn = !empty($_SESSION['siapkerja_email']) || !empty($_SESSION['username']);
$displayName = (string)($_SESSION['siapkerja_name'] ?? $_SESSION['username'] ?? 'Pengguna');
$userInitials = strtoupper(substr(preg_replace('/\s+/', '', $displayName), 0, 2));
if (strlen($userInitials) < 2) {
    $userInitials = 'US';
}

$siapkerjaEmail = strtolower((string)($_SESSION['siapkerja_email'] ?? ''));
$isAdminAccount = $isLoggedIn && (($_SESSION['role'] ?? '') === 'admin');
$isEmployerAccount = $isLoggedIn && !$isAdminAccount && (
    ($_SESSION['role'] ?? '') === 'employer'
    || in_array($siapkerjaEmail, ['employer@pasker.id', 'calon.employer@pasker.id'], true)
);
$isWorkerAccount = $isLoggedIn && !$isAdminAccount && !$isEmployerAccount && (
    ($_SESSION['role'] ?? '') === 'worker'
    || in_array($siapkerjaEmail, ['theressaz@pasker.id', 'tessa'], true)
);
$showDasborPengelola = $isEmployerAccount || $isAdminAccount;
$profileEmail = (string)($_SESSION['siapkerja_email'] ?? '');
if ($profileEmail === '' && $isLoggedIn) {
    $profileEmail = strtolower(str_replace(' ', '', $displayName)) . '@pasker.id';
}

$searchQuery = trim((string)($_GET['q'] ?? ''));
$locationQuery = trim((string)($_GET['loc'] ?? ''));

// 30 Lowongan (first 18 match screenshot exactly)
$allVacancies = [
    [
        'id' => 1,
        'title' => 'STORE CREW',
        'company' => 'PT PESTA PORA ABADI ( MIE GACOAN )',
        'company_short' => 'PT PESTA PORA ABADI ( MIE ...',
        'location' => 'Kab. Aceh Besar',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 12 Agu 2027',
        'quota' => 1,
        'time_ago' => '1 menit yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'gacoan',
        'description' => 'Bertanggung jawab melayani pesanan pelanggan, menjaga kebersihan area dining dan kitchen, serta memastikan standar operasional restoran Mie Gacoan terpenuhi.',
        'requirements' => 'Pria / Wanita, usia maks. 25 tahun, pendidikan min. SMA/SMK, berpenampilan rapi, komunikatif, dan siap bekerja sistem shift.'
    ],
    [
        'id' => 2,
        'title' => 'Crew Outlet',
        'company' => 'PANCONG POCONG',
        'company_short' => 'PANCONG POCONG',
        'location' => 'Kab. Timor Tengah Selatan',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 20 Agu 2027',
        'quota' => 5,
        'time_ago' => '1 menit yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'pancong',
        'description' => 'Menyiapkan bahan baku kue pancong, memanggang sesuai pesanan, melayani transaksi kasir, serta menjaga higienitas outlet.',
        'requirements' => 'Pendidikan min. SMP/SMA, jujur, teliti, disiplin, dan memiliki semangat kerja yang tinggi.'
    ],
    [
        'id' => 3,
        'title' => 'Store Crew',
        'company' => 'PT PESTA PORA ABADI ( MIE GACOAN )',
        'company_short' => 'PT PESTA PORA ABADI ( MIE ...',
        'location' => 'Kab. Aceh Barat',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 12 Agu 2027',
        'quota' => 5,
        'time_ago' => '2 menit yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'gacoan',
        'description' => 'Membantu persiapan kitchen, melayani kasir POS, dan menyajikan pesanan makanan dan minuman kepada pengunjung outlet Mie Gacoan.',
        'requirements' => 'Usia 18–25 tahun, pendidikan SMA/SMK sederajat, mampu bekerja dalam tim yang dinamis dan berkecepatan tinggi.'
    ],
    [
        'id' => 4,
        'title' => 'Mixologist',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '5 menit yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Meracik signature cocktail & mocktail bertaraf internasional, mengelola inventaris bar, dan memberikan pelayanan minuman premium kepada member country club.',
        'requirements' => 'Pengalaman min. 2 tahun sebagai Bartender/Mixologist di hotel bintang 4/5 atau beach club, kemampuan Bahasa Inggris aktif.'
    ],
    [
        'id' => 5,
        'title' => 'Operator Outlet',
        'company' => 'PT Babarafi Internasional',
        'company_short' => 'PT Babarafi Internasional',
        'location' => 'Kab. Aceh Barat Daya',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 17 Agu 2027',
        'quota' => 5,
        'time_ago' => '5 menit yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'babarafi',
        'description' => 'Mengoperasikan booth kebab, meracik isian tortila dan daging kebab, serta mengelola pembukuan kas harian outlet Kebab Turki Baba Rafi.',
        'requirements' => 'Pria / Wanita, min. SMA sederajat, ramah, jujur, bersedia ditempatkan di outlet wilayah penugasan.'
    ],
    [
        'id' => 6,
        'title' => 'Staff Administrasi',
        'company' => 'PT. Deva Medica',
        'company_short' => 'PT. Deva Medica',
        'location' => 'Kab. Aceh Jaya',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 16 Agu 2027',
        'quota' => 5,
        'time_ago' => '14 menit yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'devagroup',
        'description' => 'Mengelola arsip surat masuk/keluar, input data operasional klinik/distribusi farmasi ke sistem, serta menyusun rekap laporan berkala.',
        'requirements' => 'Pendidikan min. D3/S1 semua jurusan, mahir mengoperasikan Microsoft Excel, teliti dan bertanggung jawab.'
    ],
    [
        'id' => 7,
        'title' => 'Host Live (Sekaligus Karyawan)',
        'company' => 'ADINEE SHOP',
        'company_short' => 'ADINEE SHOP',
        'location' => 'Kab. Aceh Besar',
        'type' => 'Part time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 06 Agu 2027',
        'quota' => 5,
        'time_ago' => '14 menit yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'adinee',
        'description' => 'Membawakan siaran langsung (live streaming) jualan di TikTok & Shopee Live, berinteraksi aktif dengan penonton, serta membantu packing produk.',
        'requirements' => 'Percaya diri di depan kamera, komunikatif, persuasif, memiliki ketertarikan di bidang fashion & skincare.'
    ],
    [
        'id' => 8,
        'title' => 'Operator Outlet',
        'company' => 'PT Babarafi Internasional',
        'company_short' => 'PT Babarafi Internasional',
        'location' => 'Kab. Aceh Barat',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 17 Agu 2027',
        'quota' => 5,
        'time_ago' => '15 menit yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'babarafi',
        'description' => 'Memanggang dan menyajikan kebab, burger, dan roti canai Baba Rafi, mencatat stok harian bahan, serta melayani pembeli dengan ramah.',
        'requirements' => 'Pria/Wanita maks. 28 tahun, jujur, rajin, dapat berkomunikasi dengan baik, bersedia bekerja shift.'
    ],
    [
        'id' => 9,
        'title' => 'Sales WiFi (Marpoyan - Pekan Baru)',
        'company' => 'PT Tiga Karya Makmur',
        'company_short' => 'PT Tiga Karya Makmur',
        'location' => 'Pekanbaru, Riau',
        'type' => 'Contract',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '17 menit yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'tkm',
        'description' => 'Melakukan penawaran langsung dan canvassing langganan internet fiber optik rumah tangga di area Marpoyan Pekanbaru.',
        'requirements' => 'Pria / Wanita, memiliki kendaraan bermotor & SIM C, menyukai target penjualan dan negosiasi lapangan.'
    ],
    [
        'id' => 10,
        'title' => 'Food & Safety Officer',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '17 menit yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Mengawasi kepatuhan standar HACCP dan sanitasi makanan di seluruh outlet F&B dan dapur utama club.',
        'requirements' => 'S1 Teknologi Pangan / Gizi / Perhotelan, bersertifikat HACCP / Food Hygiene, pengalaman min. 1 tahun di resort/hotel bintang 5.'
    ],
    [
        'id' => 11,
        'title' => 'Banquet & Event',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '18 menit yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Mengatur operasional jamuan banquet, setup dekorasi event meeting, wedding, dan gala dinner di ballroom.',
        'requirements' => 'Pengalaman perhotelan min. 1 tahun di divisi Banquet/F&B Service, mampu koordinasi tim dan sigap dalam menangani tamu VIP.'
    ],
    [
        'id' => 12,
        'title' => 'Server (Waiter/ss)',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '18 menit yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Menyambut tamu, mencatat pesanan menu fine dining, mengantarkan makanan, dan memberikan pelayanan berkelas bintang lima.',
        'requirements' => 'Pria / Wanita, tinggi proporsional, berpenampilan rapi, ramah, dan fasih berbahasa Inggris percakapan.'
    ],
    [
        'id' => 13,
        'title' => 'Bartendress',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '21 menit yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Menyiapkan minuman di area lounge & pool bar, menjaga kebersihan bar station, dan memberikan rekomendasi beverages kepada tamu.',
        'requirements' => 'Wanita, usia maks. 28 tahun, berpenampilan menarik, pengalaman bartender min. 1 tahun di beach club / resto.'
    ],
    [
        'id' => 14,
        'title' => 'Bartender',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '25 menit yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Melakukan mixing drink, mengelola stok botol minuman, melayani pesanan tamu di main bar dengan standar SOP hospitality kelas atas.',
        'requirements' => 'Pria, min. SMK Pariwisata / D1 Perhotelan, menguasai teknik flaring dan cocktail klasik, beretika profesional.'
    ],
    [
        'id' => 15,
        'title' => 'Bar Supervisor',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '31 menit yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Memimpin operasional seluruh bar outlet, mengawasi cost of beverage, mengatur jadwal shift tim bartender, dan menjaga kualitas layanan.',
        'requirements' => 'Pengalaman min. 2 tahun sebagai Bar Supervisor di industri hospitality luxury resort, leadership yang kuat.'
    ],
    [
        'id' => 16,
        'title' => 'Hostess',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '36 menit yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Menyambut tamu di pintu masuk restaurant, mengelola reservasi meja, mengantarkan tamu ke tempat duduk, dan memastikan kenyamanan kunjungan.',
        'requirements' => 'Wanita, penampilan menarik dan elegan, tinggi min. 165 cm, fasih berbahasa Inggris, supel dan berkepribadian hangat.'
    ],
    [
        'id' => 17,
        'title' => 'Restaurant Supervisor',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '46 menit yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Mengawasi jalannya layanan restoran, menangani masukan tamu, briefing harian waiter/waitress, dan memastikan kepatuhan standar higienitas.',
        'requirements' => 'Min. D3/S1 Perhotelan, pengalaman min. 2 tahun sebagai Kapten/Supervisor Resto bintang 5, teliti dan tegas.'
    ],
    [
        'id' => 18,
        'title' => 'Restaurant Assistant Manager',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '48 menit yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Membantu Restaurant Manager dalam perencanaan target penjualan F&B, evaluasi kinerja staf, serta efisiensi biaya operasional restoran.',
        'requirements' => 'Pendidikan S1 Perhotelan/Manajemen, pengalaman min. 3 tahun di posisi manajerial F&B service, kemampuan problem-solving prima.'
    ],
    [
        'id' => 19,
        'title' => 'Accountant',
        'company' => 'PT. Surya Indah',
        'company_short' => 'PT. Surya Indah',
        'location' => 'Jakarta Pusat, DKI Jakarta',
        'type' => 'Full time',
        'salary_type' => 'Rp 8–12 jt',
        'deadline' => 'Lamar sebelum 15 Sep 2027',
        'quota' => 2,
        'time_ago' => '52 menit yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'suryaindah',
        'description' => 'Menyusun laporan laba rugi, neraca keuangan bulanan, rekonsiliasi bank, dan perhitungan kepatuhan pajak PPh & PPN perusahaan.',
        'requirements' => 'S1 Akuntansi, pengalaman min. 2 tahun, menguasai software Accurate/SAP dan regulasi e-Faktur Pajak.'
    ],
    [
        'id' => 20,
        'title' => 'Graphic Designer',
        'company' => 'CV. Kreatif Digital',
        'company_short' => 'CV. Kreatif Digital',
        'location' => 'Kota Bandung, Jawa Barat',
        'type' => 'Full time',
        'salary_type' => 'Rp 6–9 jt',
        'deadline' => 'Lamar sebelum 20 Sep 2027',
        'quota' => 3,
        'time_ago' => '1 jam yang lalu',
        'source' => 'Karirhub',
        'source_type' => 'karirhub',
        'logo_type' => 'kreatifdigital',
        'description' => 'Merancang materi promosi visual kreatif untuk media sosial, banner display, packaging produk, dan materi branding digital.',
        'requirements' => 'Mahir Adobe Illustrator, Photoshop, dan Figma. Portofolio desain wajib dilampirkan.'
    ],
    [
        'id' => 21,
        'title' => 'Software Engineer',
        'company' => 'PT. Nusantara Tech',
        'company_short' => 'PT. Nusantara Tech',
        'location' => 'Kota Surabaya, Jawa Timur',
        'type' => 'Full time',
        'salary_type' => 'Rp 12–18 jt',
        'deadline' => 'Lamar sebelum 30 Sep 2027',
        'quota' => 4,
        'time_ago' => '1 jam yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'nusantaratech',
        'description' => 'Mengembangkan backend web services dengan Node.js / PHP Laravel, membangun arsitektur microservices yang handal, dan optimasi database.',
        'requirements' => 'S1 Teknik Informatika / Ilmu Komputer, pengalaman min. 3 tahun dalam software engineering, terbiasa dengan Git dan Docker.'
    ],
    [
        'id' => 22,
        'title' => 'HR Specialist',
        'company' => 'PT. Talenta Prima',
        'company_short' => 'PT. Talenta Prima',
        'location' => 'Jakarta Selatan, DKI Jakarta',
        'type' => 'Full time',
        'salary_type' => 'Rp 7–10 jt',
        'deadline' => 'Lamar sebelum 10 Okt 2027',
        'quota' => 1,
        'time_ago' => '2 jam yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'talentaprima',
        'description' => 'Mengelola proses end-to-end rekrutmen karyawan baru, administrasi BPJS Ketenagakerjaan, serta program pelatihan kompetensi internal.',
        'requirements' => 'S1 Psikologi / Manajemen SDM, memahami UU Ketenagakerjaan, pengalaman min. 2 tahun di bidang talent acquisition.'
    ],
    [
        'id' => 23,
        'title' => 'Digital Marketing Strategist',
        'company' => 'PT Media Kreasi Mandiri',
        'company_short' => 'PT Media Kreasi Mandiri',
        'location' => 'Jakarta Selatan, DKI Jakarta',
        'type' => 'Full time',
        'salary_type' => 'Rp 7–11 jt',
        'deadline' => 'Lamar sebelum 15 Okt 2027',
        'quota' => 2,
        'time_ago' => '2 jam yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'mediakreasi',
        'description' => 'Merancang kampanye periklanan Google Ads & Meta Ads terarah, optimasi funnel konversi, serta menganalisis ROI performa pemasaran digital.',
        'requirements' => 'Pengalaman min. 2 tahun dalam performance marketing, analytical mindset, dan menguasai Google Analytics 4.'
    ],
    [
        'id' => 24,
        'title' => 'Cook / Demi Chef',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 2,
        'time_ago' => '2 jam yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Memasak hidangan internasional dan nusantara sesuai resep standar, menjaga kualitas dan rasa makanan, serta menjaga higienitas dapur.',
        'requirements' => 'Min. SMK Tata Boga / D3 Perhotelan, pengalaman min. 2 tahun di hot kitchen / banquet hotel berbintang.'
    ],
    [
        'id' => 25,
        'title' => 'Steward Supervisor',
        'company' => 'Bali Beach Country Club Nusantara',
        'company_short' => 'Bali Beach Country Club Nut...',
        'location' => 'Denpasar, Bali',
        'type' => 'Full time',
        'salary_type' => 'Dirahasiakan',
        'deadline' => 'Lamar sebelum 01 Jan 2027',
        'quota' => 1,
        'time_ago' => '3 jam yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'balibeach',
        'description' => 'Memastikan ketersediaan dan kebersihan seluruh peralatan makan, alat masak dapur, serta pengelolaan limbah dapur yang sesuai standar ramah lingkungan.',
        'requirements' => 'Pengalaman min. 2 tahun di bidang stewarding hotel bintang 4/5, memahami penggunaan chemical cleaning yang aman.'
    ],
    [
        'id' => 26,
        'title' => 'Barista & Kasir',
        'company' => 'Kopi Kenangan Mantan',
        'company_short' => 'Kopi Kenangan Mantan',
        'location' => 'Jakarta Barat, DKI Jakarta',
        'type' => 'Full time',
        'salary_type' => 'Rp 4.5–5.5 jt',
        'deadline' => 'Lamar sebelum 05 Okt 2027',
        'quota' => 8,
        'time_ago' => '3 jam yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'kenangan',
        'description' => 'Membuat racikan kopi espresso base, minuman signature kekinian, melayani kasir POS, dan menjaga kebersihan gerai coffee shop.',
        'requirements' => 'Pria / Wanita maks. 26 tahun, min. SMA/SMK, berpenampilan bersih, ramah, dan berminat tinggi di industri kopi.'
    ],
    [
        'id' => 27,
        'title' => 'Admin Gudang & Logistik',
        'company' => 'PT Sinar Logistik Abadi',
        'company_short' => 'PT Sinar Logistik Abadi',
        'location' => 'Kab. Aceh Besar',
        'type' => 'Full time',
        'salary_type' => 'Rp 3.8–5 jt',
        'deadline' => 'Lamar sebelum 25 Sep 2027',
        'quota' => 3,
        'time_ago' => '4 jam yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'sinarlogistik',
        'description' => 'Mencatat arus keluar-masuk barang pergudangan, stock opname rutin, dan pembuatan surat jalan pengiriman barang antar cabang.',
        'requirements' => 'Pria / Wanita, teliti, menguasai Ms. Excel, min. SMA/SMK, bersedia bekerja di area warehouse.'
    ],
    [
        'id' => 28,
        'title' => 'Customer Relations Officer',
        'company' => 'PT Bank Central Nusantara',
        'company_short' => 'PT Bank Central Nusantara',
        'location' => 'Kota Medan, Sumatera Utara',
        'type' => 'Full time',
        'salary_type' => 'Rp 5–7.5 jt',
        'deadline' => 'Lamar sebelum 01 Okt 2027',
        'quota' => 2,
        'time_ago' => '4 jam yang lalu',
        'source' => 'HiredToday',
        'source_type' => 'hiredtoday',
        'logo_type' => 'bcn',
        'description' => 'Melayani kebutuhan informasi nasabah prioritas, menangani komplain perbankan dengan empati, dan menawarkan produk finansial perbankan.',
        'requirements' => 'Pendidikan min. S1 semua jurusan, IPK min. 3.00, berpenampilan menarik, artikulatif, dan berorientasi pada kepuasan pelanggan.'
    ],
    [
        'id' => 29,
        'title' => 'Quality Control Staff',
        'company' => 'PT Indofood Makmur Mandiri',
        'company_short' => 'PT Indofood Makmur Mandiri',
        'location' => 'Kota Semarang, Jawa Tengah',
        'type' => 'Full time',
        'salary_type' => 'Rp 4.5–6 jt',
        'deadline' => 'Lamar sebelum 18 Okt 2027',
        'quota' => 4,
        'time_ago' => '5 jam yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'indofood',
        'description' => 'Melakukan inspeksi sampling bahan baku pangan dan produk jadi di lini produksi sesuai dengan standar mutu ISO 9001 & BPOM.',
        'requirements' => 'D3 / S1 Teknologi Pangan / Kimia / Farmasi, teliti, disiplin, bersedia bekerja dalam sistem shift pabrik.'
    ],
    [
        'id' => 30,
        'title' => 'Purchasing & Procurement Staff',
        'company' => 'PT Mega Baja Nusantara',
        'company_short' => 'PT Mega Baja Nusantara',
        'location' => 'Kota Surabaya, Jawa Timur',
        'type' => 'Full time',
        'salary_type' => 'Rp 5–8 jt',
        'deadline' => 'Lamar sebelum 12 Okt 2027',
        'quota' => 1,
        'time_ago' => '5 jam yang lalu',
        'source' => 'TopLoker.com',
        'source_type' => 'toploker',
        'logo_type' => 'megabaja',
        'description' => 'Mencari vendor material industri terbaik, negosiasi harga dan termin pembayaran PO, serta memastikan ketepatan pengiriman barang.',
        'requirements' => 'S1 Manajemen / Teknik Industri, pengalaman min. 1 tahun di bidang procurement/procurement, kemampuan negosiasi yang baik.'
    ],
];

// Helper to render crisp branded logo badge
function renderBrandLogo(string $type, string $title, string $company): string {
    switch ($type) {
        case 'gacoan':
            return '<div style="width:44px;height:44px;border-radius:10px;background:#fdf2f8;border:1.5px solid #fbcfe8;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2px;box-shadow:0 1px 2px rgba(0,0,0,0.04);"><span style="font-size:7px;font-weight:900;color:#db2777;letter-spacing:-0.2px;line-height:1;">MIE</span><span style="font-size:8px;font-weight:900;color:#db2777;letter-spacing:-0.3px;line-height:1;">GACOAN</span><div style="width:14px;height:2px;background:#f43f5e;border-radius:2px;margin-top:2px;"></div></div>';
        case 'pancong':
            return '<div style="width:44px;height:44px;border-radius:10px;background:#fefce8;border:1.5px solid #fef08a;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3px;box-shadow:0 1px 2px rgba(0,0,0,0.04);"><span style="font-size:6.5px;font-weight:800;color:#854d0e;line-height:1.1;">PANCONG</span><span style="font-size:6.5px;font-weight:800;color:#b45309;line-height:1.1;">POCONG</span></div>';
        case 'balibeach':
            return '<div style="width:44px;height:44px;border-radius:10px;background:#064e3b;border:1.5px solid #047857;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3px;box-shadow:0 1px 2px rgba(0,0,0,0.06);"><span style="font-size:6.5px;font-weight:900;color:#fbbf24;letter-spacing:0.3px;line-height:1;">BALI</span><span style="font-size:6.5px;font-weight:900;color:#fbbf24;letter-spacing:0.3px;line-height:1;">BEACH</span></div>';
        case 'babarafi':
            return '<div style="width:44px;height:44px;border-radius:10px;background:#fffbeb;border:1.5px solid #fde68a;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2px;box-shadow:0 1px 2px rgba(0,0,0,0.04);"><span style="font-size:7px;font-weight:900;color:#b45309;line-height:1;">BABA</span><span style="font-size:7px;font-weight:900;color:#dc2626;line-height:1;">RAFI</span></div>';
        case 'devagroup':
            return '<div style="width:44px;height:44px;border-radius:10px;background:#fef2f2;border:1.5px solid #fecaca;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2px;box-shadow:0 1px 2px rgba(0,0,0,0.04);"><span style="font-size:7px;font-weight:900;color:#dc2626;line-height:1;">deva</span><span style="font-size:6.5px;font-weight:700;color:#991b1b;line-height:1;">group</span></div>';
        case 'adinee':
            return '<div style="width:44px;height:44px;border-radius:50%;background:#fdf2f8;border:1.5px solid #fbcfe8;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2px;box-shadow:0 1px 2px rgba(0,0,0,0.04);"><span style="font-size:6.5px;font-weight:800;color:#be185d;line-height:1;">ADINEE</span><span style="font-size:5.5px;font-weight:700;color:#db2777;line-height:1;">shop</span></div>';
        case 'tkm':
            return '<div style="width:44px;height:44px;border-radius:10px;background:#f0fdf4;border:1.5px solid #bbf7d0;display:flex;align-items:center;justify-content:center;padding:2px;box-shadow:0 1px 2px rgba(0,0,0,0.04);"><span style="font-size:11px;font-weight:900;background:linear-gradient(135deg, #ef4444, #eab308, #2563eb);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">TKM</span></div>';
        default:
            $init = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $company), 0, 2)) ?: 'PT';
            return '<div style="width:44px;height:44px;border-radius:10px;background:#f0f9ff;border:1.5px solid #bae6fd;display:flex;align-items:center;justify-content:center;color:#0284c7;font-weight:800;font-size:12px;box-shadow:0 1px 2px rgba(0,0,0,0.04);">' . htmlspecialchars($init) . '</div>';
    }
}

function renderSourceBadge(string $type, string $name): string {
    if ($type === 'toploker') {
        return '<div style="display:inline-flex;align-items:center;gap:3px;"><span style="color:#f97316;font-weight:900;font-size:9.5px;line-height:1;">Top</span><span style="color:#0284c7;font-weight:800;font-size:9.5px;line-height:1;">Loker</span></div>';
    } elseif ($type === 'hiredtoday') {
        return '<div style="width:14px;height:14px;border-radius:3px;background:#0d9488;display:flex;align-items:center;justify-content:center;"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>';
    } else {
        return '<div style="width:14px;height:14px;border-radius:3px;background:#0284c7;display:flex;align-items:center;justify-content:center;"><span style="color:#fff;font-size:8px;font-weight:900;">K</span></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Karirhub · Lowongan Pekerjaan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/karirhub-lowongan.css" />
  <style>
    :root {
      --blue: #1657c1;
      --blue-dark: #0f3d8c;
      --sky: #18b5ea;
      --sky-btn: #0ea5e9;
      --text: #0f172a;
      --muted: #64748b;
      --border: #e2e8f0;
      --bg: #f8fafc;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      color: var(--text);
      background: #ffffff;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
    }
    a { color: inherit; text-decoration: none; }

    /* Topbar matching Karirhub */
    .topbar {
      position: sticky; top: 0; z-index: 50;
      background: #fff;
      border-bottom: 1px solid var(--border);
    }
    .nav-inner {
      height: 72px; display: flex; align-items: center; justify-content: space-between; gap: 20px;
      max-width: 1320px; margin: 0 auto; padding: 0 28px;
    }
    .nav-left { display: flex; align-items: center; gap: 20px; flex: 1; min-width: 0; }
    .nav-hamburger {
      width: 40px; height: 40px; border: none; background: transparent; cursor: pointer;
      display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; padding: 0;
    }
    .nav-hamburger span {
      display: block; width: 22px; height: 2px; background: #1e293b; border-radius: 2px;
    }
    .brand { display: flex; align-items: center; gap: 10px; }
    .brand-icon { width: 40px; height: 40px; flex-shrink: 0; }
    .brand-text { line-height: 1.15; }
    .brand-text .name {
      font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -0.03em;
    }
    .brand-text .sub { font-size: 0.68rem; font-weight: 600; color: #64748b; margin-top: 1px; }

    .nav-links {
      display: flex; align-items: center; gap: 24px; flex-shrink: 0;
    }
    .nav-links a {
      font-size: 0.95rem; font-weight: 600; color: #334155; white-space: nowrap;
      display: inline-flex; align-items: center; gap: 4px;
    }
    .nav-links a.nav-jobs { color: #1657c1; font-weight: 700; }
    .nav-links a:hover { color: var(--blue); }
    .nav-caret { color: #94a3b8; flex-shrink: 0; }

    .nav-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .btn-nav-outline {
      padding: 10px 22px; border-radius: 999px; border: 1px solid #cbd5e1;
      background: #fff; color: #0f172a; font-size: 0.9rem; font-weight: 700; font-family: inherit;
      cursor: pointer; line-height: 1;
    }
    .btn-nav-outline:hover { background: #f8fafc; }
    .btn-nav-solid {
      display: inline-flex; align-items: center; justify-content: center;
      padding: 10px 26px; border-radius: 999px; border: none;
      background: #0ea5e9; color: #fff; font-size: 0.9rem; font-weight: 700; font-family: inherit;
      cursor: pointer; line-height: 1; min-width: 88px; text-decoration: none;
    }
    .btn-nav-solid:hover { background: #0284c7; color: #fff; }

    .nav-user-zone { position: relative; display: flex; align-items: center; gap: 14px; }
    .dasbor-trigger {
      border: none; background: transparent; font: inherit; font-size: 0.92rem;
      font-weight: 600; color: #334155; cursor: pointer; padding: 6px 0;
    }
    .dasbor-trigger:hover { color: var(--blue); }
    .nav-avatar-btn {
      border: none; padding: 0; background: transparent; cursor: pointer; border-radius: 50%; line-height: 0;
    }
    .nav-avatar {
      width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0;
      background: #f1f5f9; display: block;
    }

    /* Main Container layout matching Screenshot 2 */
    .vacancies-shell {
      max-width: 1280px;
      margin: 0 auto;
      padding: 24px 24px 48px 24px;
    }

    .vacancies-layout {
      display: grid;
      grid-template-columns: 240px 1fr;
      gap: 32px;
      align-items: flex-start;
    }

    /* Left Sidebar Filter */
    .filter-sidebar {
      background: #ffffff;
      position: sticky;
      top: 90px;
    }

    .filter-title {
      font-size: 1.25rem;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: -0.02em;
      margin-bottom: 8px;
    }

    .filter-group {
      border-bottom: 1px solid #f1f5f9;
      padding: 14px 0;
    }

    .filter-group-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      background: transparent;
      border: none;
      font-family: inherit;
      font-size: 0.88rem;
      font-weight: 700;
      color: #1e293b;
      cursor: pointer;
      padding: 4px 0;
      text-align: left;
    }

    .filter-chevron {
      width: 16px;
      height: 16px;
      color: #64748b;
      transition: transform 0.2s ease;
      flex-shrink: 0;
    }
    .filter-chevron.is-open {
      transform: rotate(180deg);
    }

    .filter-content {
      margin-top: 10px;
      display: none;
    }
    .filter-content.is-open {
      display: block;
    }

    .filter-search-box {
      position: relative;
      margin-bottom: 12px;
    }
    .filter-search-input {
      width: 100%;
      height: 36px;
      padding: 0 32px 0 12px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.82rem;
      font-family: inherit;
      color: #0f172a;
      outline: none;
      background: #ffffff;
    }
    .filter-search-input:focus {
      border-color: #0ea5e9;
    }
    .filter-search-icon {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      pointer-events: none;
    }

    .filter-option {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 5px 0;
      cursor: pointer;
      user-select: none;
      font-size: 0.82rem;
      color: #334155;
    }
    .filter-option:hover {
      color: #0284c7;
    }

    /* Circle radio outline matching screenshot */
    .circle-radio {
      width: 15px;
      height: 15px;
      border-radius: 50%;
      border: 2px solid #38bdf8;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      background: #ffffff;
      transition: all 0.15s ease;
    }
    .circle-radio.checked {
      border-color: #0284c7;
      background: #0284c7;
    }
    .circle-radio.checked::after {
      content: '';
      width: 5px;
      height: 5px;
      border-radius: 50%;
      background: #ffffff;
    }

    .filter-link-more {
      display: inline-block;
      margin-top: 8px;
      font-size: 0.78rem;
      font-weight: 700;
      color: #0284c7;
      cursor: pointer;
    }
    .filter-link-more:hover {
      text-decoration: underline;
    }

    /* Right Section: 3-column Grid */
    .cards-grid-container {
      display: flex;
      flex-direction: column;
      gap: 32px;
    }

    .vacancies-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
    }

    .vacancy-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 16px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 290px;
      box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
      transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
      cursor: pointer;
      position: relative;
    }
    .vacancy-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
      border-color: #cbd5e1;
    }

    .card-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 12px;
    }

    .post-time {
      font-size: 0.68rem;
      color: #94a3b8;
      font-weight: 500;
      white-space: nowrap;
    }

    .card-title {
      font-size: 0.95rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.3;
      margin-bottom: 3px;
      letter-spacing: -0.01em;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .card-company {
      font-size: 0.78rem;
      color: #64748b;
      font-weight: 500;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 12px;
    }

    .salary-block {
      margin-bottom: 8px;
    }
    .salary-label {
      display: block;
      font-size: 0.68rem;
      color: #94a3b8;
      font-weight: 600;
      margin-bottom: 1px;
    }
    .salary-val {
      font-size: 0.85rem;
      font-weight: 700;
      color: #0f172a;
    }

    .deadline-text {
      font-size: 0.72rem;
      color: #64748b;
      margin-bottom: 14px;
    }

    .quota-pill-wrap {
      margin-bottom: 14px;
    }
    .quota-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 4px 16px;
      border: 1.5px solid #0284c7;
      border-radius: 999px;
      background: #ffffff;
      color: #0284c7;
      font-size: 0.75rem;
      font-weight: 700;
      line-height: 1.2;
      transition: background 0.15s ease, color 0.15s ease;
    }
    .quota-pill:hover {
      background: #f0f9ff;
    }

    .card-footer {
      border-top: 1px solid #f1f5f9;
      padding-top: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .source-label {
      font-size: 0.68rem;
      color: #94a3b8;
      display: block;
      line-height: 1.2;
    }
    .source-name {
      font-size: 0.75rem;
      font-weight: 700;
      color: #475569;
    }

    /* Pagination */
    .pagination-bar {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 24px;
      user-select: none;
    }

    .page-btn {
      min-width: 32px;
      height: 32px;
      padding: 0 6px;
      border-radius: 8px;
      border: 1px solid transparent;
      background: transparent;
      color: #475569;
      font-size: 0.82rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s ease;
    }
    .page-btn:hover:not(:disabled) {
      background: #f1f5f9;
      color: #0f172a;
    }
    .page-btn.active {
      border-color: #0ea5e9;
      color: #0284c7;
      font-weight: 800;
      background: #ffffff;
      box-shadow: 0 1px 2px rgba(2, 132, 199, 0.1);
    }
    .page-btn:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }
    .page-ellipsis {
      font-size: 0.82rem;
      color: #94a3b8;
      padding: 0 4px;
    }

    /* Modal Detail Lowongan */
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.5);
      z-index: 100;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-backdrop.is-open {
      display: flex;
    }
    .modal-card {
      background: #ffffff;
      width: 100%;
      max-width: 600px;
      max-height: 90vh;
      border-radius: 16px;
      overflow-y: auto;
      padding: 28px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
      position: relative;
    }
    .modal-close-btn {
      position: absolute;
      top: 18px; right: 18px;
      width: 32px; height: 32px;
      border-radius: 50%;
      border: 1px solid #e2e8f0;
      background: #f8fafc;
      color: #64748b;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; font-weight: 700;
    }
    .modal-close-btn:hover { background: #e2e8f0; color: #0f172a; }

    @media (max-width: 1120px) {
      .vacancies-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }
    @media (max-width: 860px) {
      .vacancies-layout {
        grid-template-columns: 1fr;
      }
      .filter-sidebar {
        position: static;
      }
      .vacancies-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <!-- Topbar Header matching Karirhub -->
  <header class="topbar">
    <div class="nav-inner">
      <div class="nav-left">
        <button type="button" class="nav-hamburger" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
        <a href="karirhub-home.php" class="brand">
          <svg class="brand-icon" viewBox="0 0 40 40" fill="none" aria-hidden="true">
            <path d="M8 8 H22 Q28 8 28 14 V20 L16 32 H8 Z" fill="#18b5ea"/>
            <path d="M22 8 L32 8 L32 18 L24 26 H16 L22 18 Z" fill="#0ea5e9" opacity="0.85"/>
            <circle cx="30" cy="10" r="4" fill="#38bdf8"/>
          </svg>
          <div class="brand-text">
            <div class="name">Karirhub</div>
            <div class="sub">oleh Kemnaker</div>
          </div>
        </a>
        <nav class="nav-links" aria-label="Navigasi utama">
          <a href="info_lowongan.php" class="nav-jobs">Lowongan Pekerjaan <svg class="nav-caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></a>
          <a href="gig-workers-go.php">Gig Workers</a>
          <a href="pilih-jenis-pemberi-kerja.php">Pemberi Kerja</a>
          <a href="karirhub-home.php#lowongan">Jobfair</a>
        </nav>
      </div>
      <div class="nav-actions">
        <?php if (!$isLoggedIn): ?>
          <a href="pilih-pendaftaran.php" class="btn-nav-outline">Daftar</a>
          <a href="siapkerja-login.php?redirect=info_lowongan" class="btn-nav-solid">Masuk</a>
        <?php else: ?>
          <div class="nav-user-zone">
            <?php if ($showDasborPengelola): ?>
              <button type="button" class="dasbor-trigger" onclick="window.location.href='<?php echo $isAdminAccount ? 'admin-dashboard-go.php' : 'dashboard-pemberi-kerja.php'; ?>'">
                Dasbor Pengelola
              </button>
            <?php endif; ?>
            <a href="profil.php" class="nav-avatar-btn" aria-label="Menu profil">
              <div class="nav-avatar" style="width:40px;height:40px;border-radius:50%;background:#2563eb;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;flex-shrink:0;">
                <?php echo htmlspecialchars(strtoupper(substr($displayName !== '' ? $displayName : 'T', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
              </div>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="vacancies-shell">
    <div class="vacancies-layout">

      <!-- LEFT SIDEBAR: FILTER (Exact replica of Screenshot 2) -->
      <aside class="filter-sidebar">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <h2 class="filter-title">Filter</h2>
          <button type="button" id="btnResetFilter" style="display:none;background:none;border:none;color:#0284c7;font-size:0.75rem;font-weight:700;cursor:pointer;">Reset</button>
        </div>

        <!-- 1. Lokasi -->
        <div class="filter-group">
          <button type="button" class="filter-group-header" onclick="toggleFilterGroup('lokasi')">
            <span>Lokasi</span>
            <svg class="filter-chevron is-open" id="chev-lokasi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="filter-content is-open" id="content-lokasi">
            <div class="filter-search-box">
              <input type="text" id="locSearchInput" placeholder="Cari lokasi" class="filter-search-input" value="<?php echo htmlspecialchars($locationQuery); ?>" />
              <svg class="filter-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
            <div id="locOptionsList">
              <div class="filter-option" data-filter="loc" data-value="Kab. Timor Tengah Selatan" onclick="selectFilter('loc', 'Kab. Timor Tengah Selatan')">
                <span class="circle-radio" id="radio-loc-Kab. Timor Tengah Selatan"></span>
                <span>Kab. Timor Tengah Selatan</span>
              </div>
              <div class="filter-option" data-filter="loc" data-value="Kab. Aceh Barat" onclick="selectFilter('loc', 'Kab. Aceh Barat')">
                <span class="circle-radio" id="radio-loc-Kab. Aceh Barat"></span>
                <span>Kab. Aceh Barat</span>
              </div>
              <div class="filter-option" data-filter="loc" data-value="Kab. Aceh Barat Daya" onclick="selectFilter('loc', 'Kab. Aceh Barat Daya')">
                <span class="circle-radio" id="radio-loc-Kab. Aceh Barat Daya"></span>
                <span>Kab. Aceh Barat Daya</span>
              </div>
              <div class="filter-option" data-filter="loc" data-value="Kab. Aceh Besar" onclick="selectFilter('loc', 'Kab. Aceh Besar')">
                <span class="circle-radio" id="radio-loc-Kab. Aceh Besar"></span>
                <span>Kab. Aceh Besar</span>
              </div>
              <div class="filter-option" data-filter="loc" data-value="Kab. Aceh Jaya" onclick="selectFilter('loc', 'Kab. Aceh Jaya')">
                <span class="circle-radio" id="radio-loc-Kab. Aceh Jaya"></span>
                <span>Kab. Aceh Jaya</span>
              </div>
            </div>
            <span class="filter-link-more" onclick="toggleMoreLocations(this)">Lihat Lebih Banyak</span>
            <div id="moreLocations" style="display:none;margin-top:4px;">
              <div class="filter-option" data-filter="loc" data-value="Denpasar, Bali" onclick="selectFilter('loc', 'Denpasar, Bali')">
                <span class="circle-radio" id="radio-loc-Denpasar, Bali"></span>
                <span>Denpasar, Bali</span>
              </div>
              <div class="filter-option" data-filter="loc" data-value="Jakarta" onclick="selectFilter('loc', 'Jakarta')">
                <span class="circle-radio" id="radio-loc-Jakarta"></span>
                <span>DKI Jakarta</span>
              </div>
              <div class="filter-option" data-filter="loc" data-value="Surabaya" onclick="selectFilter('loc', 'Surabaya')">
                <span class="circle-radio" id="radio-loc-Surabaya"></span>
                <span>Kota Surabaya</span>
              </div>
              <div class="filter-option" data-filter="loc" data-value="Bandung" onclick="selectFilter('loc', 'Bandung')">
                <span class="circle-radio" id="radio-loc-Bandung"></span>
                <span>Kota Bandung</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Tipe Pekerjaan -->
        <div class="filter-group">
          <button type="button" class="filter-group-header" onclick="toggleFilterGroup('tipe')">
            <span>Tipe Pekerjaan</span>
            <svg class="filter-chevron is-open" id="chev-tipe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="filter-content is-open" id="content-tipe">
            <div class="filter-option" data-filter="type" data-value="Full time" onclick="selectFilter('type', 'Full time')">
              <span class="circle-radio" id="radio-type-Full time"></span>
              <span>Full time</span>
            </div>
            <div class="filter-option" data-filter="type" data-value="Part time" onclick="selectFilter('type', 'Part time')">
              <span class="circle-radio" id="radio-type-Part time"></span>
              <span>Part time</span>
            </div>
            <div class="filter-option" data-filter="type" data-value="Contract" onclick="selectFilter('type', 'Contract')">
              <span class="circle-radio" id="radio-type-Contract"></span>
              <span>Contract</span>
            </div>
            <div class="filter-option" data-filter="type" data-value="Internship" onclick="selectFilter('type', 'Internship')">
              <span class="circle-radio" id="radio-type-Internship"></span>
              <span>Internship</span>
            </div>
          </div>
        </div>

        <!-- 3. Pendidikan -->
        <div class="filter-group">
          <button type="button" class="filter-group-header" onclick="toggleFilterGroup('pendidikan')">
            <span>Pendidikan</span>
            <svg class="filter-chevron" id="chev-pendidikan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="filter-content" id="content-pendidikan">
            <div class="filter-option" onclick="selectFilter('edu', 'SMA')">
              <span class="circle-radio" id="radio-edu-SMA"></span>
              <span>SMA / SMK</span>
            </div>
            <div class="filter-option" onclick="selectFilter('edu', 'D3')">
              <span class="circle-radio" id="radio-edu-D3"></span>
              <span>Diploma (D3 / D4)</span>
            </div>
            <div class="filter-option" onclick="selectFilter('edu', 'S1')">
              <span class="circle-radio" id="radio-edu-S1"></span>
              <span>Sarjana (S1)</span>
            </div>
          </div>
        </div>

        <!-- 4. Kondisi -->
        <div class="filter-group">
          <button type="button" class="filter-group-header" onclick="toggleFilterGroup('kondisi')">
            <span>Kondisi</span>
            <svg class="filter-chevron" id="chev-kondisi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="filter-content" id="content-kondisi">
            <div class="filter-option" onclick="selectFilter('kondisi', 'non')">
              <span class="circle-radio" id="radio-kondisi-non"></span>
              <span>Umum / Non-Disabilitas</span>
            </div>
            <div class="filter-option" onclick="selectFilter('kondisi', 'disabilitas')">
              <span class="circle-radio" id="radio-kondisi-disabilitas"></span>
              <span>Disabilitas Terbuka</span>
            </div>
          </div>
        </div>

        <!-- 5. Lowongan Kerja IKP -->
        <div class="filter-group">
          <button type="button" class="filter-group-header" onclick="toggleFilterGroup('ikp')">
            <span>Lowongan Kerja IKP</span>
            <svg class="filter-chevron" id="chev-ikp" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="filter-content" id="content-ikp">
            <div class="filter-option" onclick="selectFilter('ikp', 'ya')">
              <span class="circle-radio" id="radio-ikp-ya"></span>
              <span>Ya</span>
            </div>
            <div class="filter-option" onclick="selectFilter('ikp', 'tidak')">
              <span class="circle-radio" id="radio-ikp-tidak"></span>
              <span>Tidak</span>
            </div>
          </div>
        </div>

        <!-- 6. Sumber Lowongan -->
        <div class="filter-group">
          <button type="button" class="filter-group-header" onclick="toggleFilterGroup('sumber')">
            <span>Sumber Lowongan</span>
            <svg class="filter-chevron" id="chev-sumber" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="filter-content" id="content-sumber">
            <div class="filter-option" onclick="selectFilter('source', 'TopLoker.com')">
              <span class="circle-radio" id="radio-source-TopLoker.com"></span>
              <span>TopLoker.com</span>
            </div>
            <div class="filter-option" onclick="selectFilter('source', 'HiredToday')">
              <span class="circle-radio" id="radio-source-HiredToday"></span>
              <span>HiredToday</span>
            </div>
            <div class="filter-option" onclick="selectFilter('source', 'Karirhub')">
              <span class="circle-radio" id="radio-source-Karirhub"></span>
              <span>Karirhub / Kemnaker</span>
            </div>
          </div>
        </div>

        <!-- 7. Bidang Pekerjaan -->
        <div class="filter-group">
          <button type="button" class="filter-group-header" onclick="toggleFilterGroup('bidang')">
            <span>Bidang Pekerjaan</span>
            <svg class="filter-chevron" id="chev-bidang" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="filter-content" id="content-bidang">
            <div class="filter-option" onclick="selectFilter('cat', 'Kuliner')">
              <span class="circle-radio" id="radio-cat-Kuliner"></span>
              <span>Kuliner & Restoran</span>
            </div>
            <div class="filter-option" onclick="selectFilter('cat', 'Perhotelan')">
              <span class="circle-radio" id="radio-cat-Perhotelan"></span>
              <span>Perhotelan & Pariwisata</span>
            </div>
            <div class="filter-option" onclick="selectFilter('cat', 'TI')">
              <span class="circle-radio" id="radio-cat-TI"></span>
              <span>TI & Software</span>
            </div>
          </div>
        </div>

        <!-- 8. Sektor -->
        <div class="filter-group">
          <button type="button" class="filter-group-header" onclick="toggleFilterGroup('sektor')">
            <span>Sektor</span>
            <svg class="filter-chevron" id="chev-sektor" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="filter-content" id="content-sektor">
            <div class="filter-option" onclick="selectFilter('sektor', 'swasta')">
              <span class="circle-radio" id="radio-sektor-swasta"></span>
              <span>Swasta</span>
            </div>
            <div class="filter-option" onclick="selectFilter('sektor', 'bumn')">
              <span class="circle-radio" id="radio-sektor-bumn"></span>
              <span>BUMN / BUMD</span>
            </div>
          </div>
        </div>

        <!-- 9. Keahlian -->
        <div class="filter-group" style="border-bottom:none;">
          <button type="button" class="filter-group-header" onclick="toggleFilterGroup('keahlian')">
            <span>Keahlian</span>
            <svg class="filter-chevron" id="chev-keahlian" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="filter-content" id="content-keahlian">
            <div class="filter-option" onclick="selectFilter('skill', 'Service')">
              <span class="circle-radio" id="radio-skill-Service"></span>
              <span>Hospitality & Service</span>
            </div>
            <div class="filter-option" onclick="selectFilter('skill', 'Barista')">
              <span class="circle-radio" id="radio-skill-Barista"></span>
              <span>Bartending & Barista</span>
            </div>
            <div class="filter-option" onclick="selectFilter('skill', 'Admin')">
              <span class="circle-radio" id="radio-skill-Admin"></span>
              <span>Administrasi & Kasir</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- RIGHT COLUMN: JOB CARDS GRID (3-Column layout matching Screenshot 2) -->
      <section class="cards-grid-container">

        <div class="vacancies-grid" id="vacanciesGrid">
          <?php foreach ($allVacancies as $vIndex => $job): ?>
            <article class="vacancy-card" 
                     data-index="<?php echo $vIndex; ?>"
                     data-id="<?php echo $job['id']; ?>"
                     data-title="<?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?>"
                     data-company="<?php echo htmlspecialchars($job['company'], ENT_QUOTES, 'UTF-8'); ?>"
                     data-location="<?php echo htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8'); ?>"
                     data-type="<?php echo htmlspecialchars($job['type'], ENT_QUOTES, 'UTF-8'); ?>"
                     data-source="<?php echo htmlspecialchars($job['source'], ENT_QUOTES, 'UTF-8'); ?>"
                     onclick="openJobDetail(<?php echo $job['id']; ?>)">
              
              <div>
                <!-- Top row: Company Logo & Relative Post Time -->
                <div class="card-top">
                  <?php echo renderBrandLogo($job['logo_type'], $job['title'], $job['company']); ?>
                  <span class="post-time"><?php echo htmlspecialchars($job['time_ago'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <!-- Job Title & Company -->
                <h3 class="card-title"><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p class="card-company"><?php echo htmlspecialchars($job['company_short'], ENT_QUOTES, 'UTF-8'); ?></p>

                <!-- Salary Range -->
                <div class="salary-block">
                  <span class="salary-label">Kisaran Gaji</span>
                  <span class="salary-val"><?php echo htmlspecialchars($job['salary_type'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <!-- Deadline info -->
                <p class="deadline-text"><?php echo htmlspecialchars($job['deadline'], ENT_QUOTES, 'UTF-8'); ?></p>
              </div>

              <div>
                <!-- Quota Pill -->
                <div class="quota-pill-wrap">
                  <span class="quota-pill">Tersisa <?php echo (int)$job['quota']; ?> lowongan</span>
                </div>

                <!-- Card Footer (Source) -->
                <div class="card-footer">
                  <div>
                    <span class="source-label">Lowongan dari</span>
                    <span class="source-name"><?php echo htmlspecialchars($job['source'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                  <?php echo renderSourceBadge($job['source_type'], $job['source']); ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <!-- Empty state when filtered out -->
        <div id="emptyResultsState" style="display:none;text-align:center;padding:60px 20px;background:#fff;border:1px dashed #cbd5e1;border-radius:16px;">
          <div style="font-size:2.5rem;margin-bottom:12px;">🔍</div>
          <h3 style="font-size:1.15rem;font-weight:800;color:#0f172a;margin-bottom:6px;">Tidak ada lowongan ditemukan</h3>
          <p style="font-size:0.88rem;color:#64748b;margin-bottom:16px;">Coba ubah kata kunci pencarian atau bersihkan filter lokasi dan tipe pekerjaan.</p>
          <button type="button" onclick="clearAllFilters()" style="background:#0284c7;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:700;font-size:0.85rem;cursor:pointer;">Reset Semua Filter</button>
        </div>

        <!-- Pagination Bar matching Screenshot 2: < 1 2 3 4 5 ... 1203 > -->
        <nav class="pagination-bar" aria-label="Navigasi halaman lowongan">
          <button type="button" class="page-btn" id="btnPrevPage" onclick="changePage(currentPage - 1)" aria-label="Sebelumnya">&lt;</button>
          <button type="button" class="page-btn active" id="pageBtn1" onclick="changePage(1)">1</button>
          <button type="button" class="page-btn" id="pageBtn2" onclick="changePage(2)">2</button>
          <button type="button" class="page-btn" onclick="changePage(1)">3</button>
          <button type="button" class="page-btn" onclick="changePage(1)">4</button>
          <button type="button" class="page-btn" onclick="changePage(1)">5</button>
          <span class="page-ellipsis">...</span>
          <button type="button" class="page-btn" onclick="changePage(2)">1203</button>
          <button type="button" class="page-btn" id="btnNextPage" onclick="changePage(currentPage + 1)" aria-label="Selanjutnya">&gt;</button>
        </nav>

      </section>
    </div>
  </main>

  <!-- Detail Modal Dialog -->
  <div class="modal-backdrop" id="jobDetailModal" role="dialog" aria-modal="true">
    <div class="modal-card">
      <button type="button" class="modal-close-btn" onclick="closeJobDetail()" aria-label="Tutup">&times;</button>
      
      <div id="modalContent">
        <!-- Injected via JavaScript -->
      </div>
    </div>
  </div>

  <script>
    const allJobs = <?php echo json_encode($allVacancies, JSON_UNESCAPED_UNICODE); ?>;
    let currentFilters = {
      loc: '<?php echo addslashes($locationQuery); ?>',
      type: '',
      source: '',
      q: '<?php echo addslashes($searchQuery); ?>'
    };
    let currentPage = 1;
    const cardsPerPage = 18; // 6 rows x 3 columns matches Gambar 2 exactly!

    function toggleFilterGroup(id) {
      const content = document.getElementById('content-' + id);
      const chev = document.getElementById('chev-' + id);
      if (!content) return;
      const willOpen = !content.classList.contains('is-open');
      content.classList.toggle('is-open', willOpen);
      if (chev) {
        chev.classList.toggle('is-open', willOpen);
      }
    }

    function toggleMoreLocations(linkEl) {
      const more = document.getElementById('moreLocations');
      if (!more) return;
      const isHidden = more.style.display === 'none';
      more.style.display = isHidden ? 'block' : 'none';
      linkEl.textContent = isHidden ? 'Sembunyikan' : 'Lihat Lebih Banyak';
    }

    function selectFilter(filterKey, value) {
      // Toggle if clicked again
      if (currentFilters[filterKey] === value) {
        currentFilters[filterKey] = '';
      } else {
        currentFilters[filterKey] = value;
      }
      currentPage = 1;
      updateFilterRadioStyles();
      applyFilters();
    }

    function updateFilterRadioStyles() {
      // Clear all radios
      document.querySelectorAll('.circle-radio').forEach(el => el.classList.remove('checked'));
      
      // Update location radio
      if (currentFilters.loc) {
        const rad = document.getElementById('radio-loc-' + currentFilters.loc);
        if (rad) rad.classList.add('checked');
      }
      // Update type radio
      if (currentFilters.type) {
        const rad = document.getElementById('radio-type-' + currentFilters.type);
        if (rad) rad.classList.add('checked');
      }
      // Update source radio
      if (currentFilters.source) {
        const rad = document.getElementById('radio-source-' + currentFilters.source);
        if (rad) rad.classList.add('checked');
      }

      // Show/hide reset button
      const resetBtn = document.getElementById('btnResetFilter');
      const hasActive = currentFilters.loc || currentFilters.type || currentFilters.source || currentFilters.q;
      if (resetBtn) resetBtn.style.display = hasActive ? 'inline-block' : 'none';
    }

    function clearAllFilters() {
      currentFilters = { loc: '', type: '', source: '', q: '' };
      const locInput = document.getElementById('locSearchInput');
      if (locInput) locInput.value = '';
      currentPage = 1;
      updateFilterRadioStyles();
      applyFilters();
    }

    document.getElementById('btnResetFilter')?.addEventListener('click', clearAllFilters);

    document.getElementById('locSearchInput')?.addEventListener('input', function(e) {
      const val = e.target.value.toLowerCase().trim();
      currentFilters.loc = val;
      currentPage = 1;
      applyFilters();
    });

    function applyFilters() {
      const cards = document.querySelectorAll('.vacancy-card');
      let visibleCount = 0;
      let matchingIndexes = [];

      allJobs.forEach((job, idx) => {
        let match = true;

        if (currentFilters.loc) {
          const locFilter = currentFilters.loc.toLowerCase();
          if (!job.location.toLowerCase().includes(locFilter)) {
            match = false;
          }
        }

        if (currentFilters.type) {
          if (job.type.toLowerCase() !== currentFilters.type.toLowerCase()) {
            match = false;
          }
        }

        if (currentFilters.source) {
          if (job.source.toLowerCase() !== currentFilters.source.toLowerCase()) {
            match = false;
          }
        }

        if (currentFilters.q) {
          const q = currentFilters.q.toLowerCase();
          const inTitle = job.title.toLowerCase().includes(q);
          const inComp = job.company.toLowerCase().includes(q);
          const inLoc = job.location.toLowerCase().includes(q);
          if (!inTitle && !inComp && !inLoc) match = false;
        }

        if (match) {
          matchingIndexes.push(idx);
        }
      });

      // Pagination slice
      const totalMatches = matchingIndexes.length;
      const startIndex = (currentPage - 1) * cardsPerPage;
      const endIndex = startIndex + cardsPerPage;
      const pagedIndexes = new Set(matchingIndexes.slice(startIndex, endIndex));

      cards.forEach((card, idx) => {
        if (pagedIndexes.has(idx)) {
          card.style.display = 'flex';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      // Empty state
      const emptyState = document.getElementById('emptyResultsState');
      const grid = document.getElementById('vacanciesGrid');
      if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
      }

      // Update pagination buttons
      const btn1 = document.getElementById('pageBtn1');
      const btn2 = document.getElementById('pageBtn2');
      const prevBtn = document.getElementById('btnPrevPage');
      const nextBtn = document.getElementById('btnNextPage');
      
      if (btn1) btn1.classList.toggle('active', currentPage === 1);
      if (btn2) btn2.classList.toggle('active', currentPage === 2);
      if (prevBtn) prevBtn.disabled = currentPage <= 1;
      if (nextBtn) nextBtn.disabled = currentPage >= 2;
    }

    function changePage(page) {
      if (page < 1 || page > 2) return;
      currentPage = page;
      applyFilters();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Modal job detail
    function openJobDetail(jobId) {
      const job = allJobs.find(j => j.id === jobId);
      if (!job) return;

      const modal = document.getElementById('jobDetailModal');
      const content = document.getElementById('modalContent');
      if (!modal || !content) return;

      content.innerHTML = `
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
          <div style="flex-shrink:0;">
            <div style="width:52px;height:52px;border-radius:12px;background:#f0f9ff;border:1px solid #bae6fd;display:flex;align-items:center;justify-content:center;color:#0284c7;font-weight:800;font-size:1.1rem;">
              ${job.title.substring(0, 2).toUpperCase()}
            </div>
          </div>
          <div>
            <h2 style="font-size:1.25rem;font-weight:800;color:#0f172a;line-height:1.25;">${job.title}</h2>
            <div style="font-size:0.85rem;color:#475569;font-weight:600;margin-top:2px;">${job.company}</div>
          </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px;">
          <span style="background:#e0f2fe;color:#0369a1;padding:4px 12px;border-radius:999px;font-size:0.75rem;font-weight:700;">📍 ${job.location}</span>
          <span style="background:#f1f5f9;color:#334155;padding:4px 12px;border-radius:999px;font-size:0.75rem;font-weight:700;">💼 ${job.type}</span>
          <span style="background:#ecfdf5;color:#047857;padding:4px 12px;border-radius:999px;font-size:0.75rem;font-weight:700;">💰 ${job.salary_type}</span>
          <span style="background:#fff7ed;color:#c2410c;padding:4px 12px;border-radius:999px;font-size:0.75rem;font-weight:700;">⏱ ${job.deadline}</span>
        </div>

        <div style="border-top:1px solid #f1f5f9;padding-top:16px;margin-bottom:16px;">
          <h4 style="font-size:0.92rem;font-weight:800;color:#0f172a;margin-bottom:6px;">Deskripsi Pekerjaan</h4>
          <p style="font-size:0.85rem;color:#475569;line-height:1.6;">${job.description}</p>
        </div>

        <div style="border-top:1px solid #f1f5f9;padding-top:16px;margin-bottom:20px;">
          <h4 style="font-size:0.92rem;font-weight:800;color:#0f172a;margin-bottom:6px;">Kualifikasi & Persyaratan</h4>
          <p style="font-size:0.85rem;color:#475569;line-height:1.6;">${job.requirements}</p>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e2e8f0;padding-top:18px;">
          <div>
            <span style="font-size:0.72rem;color:#94a3b8;display:block;">Sumber Lowongan Resmi</span>
            <strong style="font-size:0.85rem;color:#0f172a;">${job.source} &bull; Kemnaker RI</strong>
          </div>
          <button type="button" onclick="alert('Lamaran untuk ' + ${JSON.stringify(job.title)} + ' berhasil dikirimkan via SIAPKerja!'); closeJobDetail();" style="background:#0ea5e9;color:#fff;border:none;padding:10px 24px;border-radius:999px;font-weight:700;font-size:0.88rem;cursor:pointer;">
            Lamar Sekarang
          </button>
        </div>
      `;
      modal.classList.add('is-open');
    }

    function closeJobDetail() {
      const modal = document.getElementById('jobDetailModal');
      if (modal) modal.classList.remove('is-open');
    }

    document.getElementById('jobDetailModal')?.addEventListener('click', function(e) {
      if (e.target === this) closeJobDetail();
    });

    // Initial render
    updateFilterRadioStyles();
    applyFilters();
  </script>
</body>
</html>
