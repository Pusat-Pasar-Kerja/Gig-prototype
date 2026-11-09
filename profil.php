<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/user-avatars.php';
require_once __DIR__ . '/includes/admin-session.php';
gig_admin_session_normalize();

$isLoggedIn = !empty($_SESSION['siapkerja_email']) || !empty($_SESSION['username']);
$sessionName = trim((string)($_SESSION['siapkerja_name'] ?? $_SESSION['username'] ?? ''));
$sessionEmail = trim((string)($_SESSION['siapkerja_email'] ?? ''));
$sessionRole = trim((string)($_SESSION['role'] ?? ''));

$requestedUser = trim((string)($_GET['id'] ?? $_GET['user'] ?? ''));
if ($requestedUser === '' && ($sessionRole === 'admin' || strtolower($sessionEmail) === 'admin@kemnaker.go.id')) {
    $lookupKey = 'theressaz@pasker.id';
    $sessionName = 'Theressa Zaratrusha';
    $sessionEmail = 'theressaz@pasker.id';
} else {
    $lookupKey = $requestedUser !== '' ? $requestedUser : ($sessionEmail !== '' ? $sessionEmail : $sessionName);
}

if ($lookupKey === '' && !$isLoggedIn) {
    $lookupKey = 'theressaz@pasker.id';
}

$workerData = $lookupKey !== '' ? gig_find_worker($lookupKey) : null;
if ($workerData === null && $sessionName !== '' && $sessionName !== $lookupKey) {
    $workerData = gig_find_worker($sessionName);
}

$workerKey = (string)($workerData['id'] ?? $lookupKey);
$regData = $workerKey !== '' ? gig_get_worker_registration($workerKey) : null;
if ($regData === null && $sessionName !== '') {
    $regData = gig_get_worker_registration($sessionName);
}
if ($regData === null && $workerData !== null && !empty($workerData['name'])) {
    $regData = gig_get_worker_registration((string)$workerData['name']);
}

$siapkerjaInfo = gig_get_siapkerja_profile($lookupKey !== '' ? $lookupKey : 'theressaz@pasker.id');

$resolvedName = (string)(
    $workerData['name']
    ?? $regData['display_name']
    ?? ($sessionName !== '' ? $sessionName : ($siapkerjaInfo['nama'] ?? 'Theressa Zaratrusha'))
);

$resolvedEmail = (string)(
    $workerData['contact']['email']
    ?? $regData['contact_email']
    ?? ($sessionEmail !== '' ? $sessionEmail : ($siapkerjaInfo['email'] ?? 'theressaz@pasker.id'))
);

$resolvedLocation = (string)(
    $regData['domicile']
    ?? $workerData['location']
    ?? $siapkerjaInfo['lokasi']
    ?? 'Jakarta Selatan, DKI Jakarta'
);

$resolvedPhone = (string)(
    $workerData['contact']['wa']
    ?? $regData['contact_wa']
    ?? ($_SESSION['siapkerja_phone'] ?? $siapkerjaInfo['wa'] ?? '0811-7671-208')
);

$resolvedNik = (string)($_SESSION['siapkerja_nik'] ?? $siapkerjaInfo['nik'] ?? '1471 0252 0803 0001');
$resolvedSiapkerjaId = (string)($siapkerjaInfo['siapkerja_id'] ?? 'SK-2026-8F4A21');

$resolvedRole = $sessionRole;
if ($resolvedRole === '') {
    $lowerEmail = strtolower($resolvedEmail);
    if (in_array($lowerEmail, ['employer@pasker.id', 'calon.employer@pasker.id'], true)) {
        $resolvedRole = 'employer';
    } elseif ($lowerEmail === 'admin@kemnaker.go.id') {
        $resolvedRole = 'admin';
    } else {
        $resolvedRole = 'worker';
    }
}

$resolvedAvatar = '';
if ($workerData !== null && !empty($workerData['photo'])) {
    $resolvedAvatar = (string)$workerData['photo'];
} elseif ($isLoggedIn) {
    $avatarRole = ($resolvedRole === 'employer' || $resolvedRole === 'admin') ? 'employer' : 'worker';
    $resolvedAvatar = $avatarRole === 'worker'
        ? gig_worker_display_photo($workerKey !== '' ? $workerKey : null)
        : gig_resolve_user_avatar($resolvedEmail, 'employer');
} else {
    $resolvedAvatar = gig_avatar_preset_photo($workerKey !== '' ? $workerKey : 'tessa');
}

$resolvedColor = (string)($workerData['color'] ?? '#2563eb');
$resolvedInitials = (string)($workerData['initials'] ?? '');
if ($resolvedInitials === '') {
    $words = preg_split('/\s+/', trim($resolvedName)) ?: [];
    if (count($words) >= 2) {
        $resolvedInitials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        $resolvedInitials = strtoupper(substr(preg_replace('/\s+/', '', $resolvedName), 0, 2));
    }
    if ($resolvedInitials === '') {
        $resolvedInitials = 'SK';
    }
}

$bioFull = trim((string)(
    $regData['profile_summary']
    ?? $workerData['proposal']
    ?? ''
));

$lowerKey = strtolower($workerKey !== '' ? $workerKey : $resolvedEmail);
$isTessa = in_array($lowerKey, ['tessa', 'theressaz@pasker.id', 'theressa zaratrusha'], true)
    || str_contains(strtolower($resolvedName), 'theressa')
    || str_contains(strtolower($resolvedEmail), 'theressaz');

if ($isTessa && mb_strlen($bioFull) < 180) {
    $bioFull = 'Saya merupakan seorang profesional Lead UI/UX Designer dengan pengalaman kerja lebih dari 4 tahun dalam merancang design system berskala besar, menyusun wireframe & prototype interaktif di Figma, serta memimpin pengujian ketergunaan (usability testing) untuk berbagai platform layanan publik dan ketenagakerjaan digital di ekosistem SIAPkerja Kemnaker RI.';
} elseif ($bioFull === '') {
    if ($resolvedRole === 'employer') {
        $bioFull = $resolvedName . ' merupakan mitra pemberi kerja resmi yang terhubung dengan ekosistem SIAPkerja Kemnaker RI untuk rekrutmen tenaga kerja dan pengelolaan proyek profesional di berbagai sektor industri.';
    } elseif ($resolvedRole === 'admin') {
        $bioFull = 'Pengelola dan verifikator resmi platform Karirhub & Gig Workers di bawah ekosistem SIAPkerja Kementerian Ketenagakerjaan Republik Indonesia.';
    } else {
        $bidangLabel = trim((string)($regData['bidang_keahlian'] ?? 'Teknologi & Layanan Digital'));
        $bioFull = 'Saya merupakan seorang profesional di bidang ' . $bidangLabel . ' yang terdaftar pada ekosistem SIAPkerja Kemnaker RI. Berfokus pada penyelesaian proyek secara terstruktur, komunikatif, dan berorientasi pada kualitas hasil kerja terbaik.';
    }
}

if (mb_strlen($bioFull) > 175) {
    $bioShort = rtrim(mb_substr($bioFull, 0, 172)) . '...';
} else {
    $bioShort = $bioFull;
}

$profile = [
    'id'           => $workerKey !== '' ? $workerKey : 'tessa',
    'name'         => $resolvedName,
    'email'        => $resolvedEmail,
    'phone'        => $resolvedPhone,
    'nik'          => $resolvedNik,
    'siapkerja_id' => $resolvedSiapkerjaId,
    'location'     => $resolvedLocation,
    'bidang'       => (string)($regData['bidang_keahlian'] ?? ($isTessa ? 'Lead UI/UX Designer' : 'Profesional Terverifikasi SIAPKerja')),
    'status_akun'  => (string)($siapkerjaInfo['status_akun'] ?? 'Terverifikasi (KYC Kemnaker RI)'),
    'bio_short'    => $bioShort,
    'bio_full'     => $bioFull,
    'avatar'       => $resolvedAvatar,
    'initials'     => $resolvedInitials,
    'color'        => $resolvedColor,
    'banner_art'   => 'assets/profile-banner-right.png',
    'role'         => $resolvedRole,
];

// --- Pengalaman Kerja (synchronized from worker_profiles, gig_worker_registrations, and SIAPkerja) ---
$experiences = [];
$seenExpKeys = [];
$addExperienceItem = static function (string $title, string $company, string $period, string $location = '', string $summary = '', bool $verified = true) use (&$experiences, &$seenExpKeys, $resolvedLocation): void {
    $title = trim($title);
    $company = trim($company);
    if ($title === '' && $company === '') {
        return;
    }
    $cleanCompany = strtolower((string)preg_replace('/\s*\(berjalan\)\s*/i', '', $company));
    $dedupKey = strtolower($title) . '||' . trim($cleanCompany);
    if (isset($seenExpKeys[$dedupKey])) {
        return;
    }
    $seenExpKeys[$dedupKey] = true;
    $experiences[] = [
        'title'    => $title !== '' ? $title : 'Profesional',
        'verified' => $verified,
        'company'  => $company !== '' ? $company : 'Mitra SIAPKerja Kemnaker',
        'period'   => trim($period) !== '' ? trim($period) : '2025 - 2026',
        'location' => trim($location) !== '' ? trim($location) : $resolvedLocation,
        'summary'  => trim($summary),
    ];
};

if (!empty($workerData['experience']) && is_array($workerData['experience'])) {
    foreach ($workerData['experience'] as $exp) {
        $addExperienceItem(
            (string)($exp['role'] ?? $exp['title'] ?? ''),
            (string)($exp['company'] ?? $exp['project'] ?? $exp['institution'] ?? ''),
            (string)($exp['period'] ?? ''),
            (string)($exp['location'] ?? $resolvedLocation),
            (string)($exp['summary'] ?? ''),
            true
        );
    }
}

if (!empty($regData['previous_projects']) && is_array($regData['previous_projects'])) {
    foreach ($regData['previous_projects'] as $proj) {
        $addExperienceItem(
            (string)($proj['role'] ?? $proj['title'] ?? ''),
            (string)($proj['company'] ?? $proj['project'] ?? ''),
            (string)($proj['period'] ?? ''),
            (string)($proj['location'] ?? $resolvedLocation),
            (string)($proj['summary'] ?? ''),
            true
        );
    }
}

if (!empty($siapkerjaInfo['pengalaman_siapkerja']) && is_array($siapkerjaInfo['pengalaman_siapkerja'])) {
    foreach ($siapkerjaInfo['pengalaman_siapkerja'] as $skExp) {
        if ($isTessa || empty($experiences)) {
            $addExperienceItem(
                (string)($skExp['role'] ?? ''),
                (string)($skExp['institution'] ?? ''),
                (string)($skExp['period'] ?? ''),
                (string)($skExp['location'] ?? 'Setiabudi, KOTA ADM. JAKARTA SELATAN, DKI JAKARTA'),
                (string)($skExp['summary'] ?? ''),
                true
            );
        }
    }
}

// --- Keahlian (synchronized from worker_profiles & gig_worker_registrations) ---
$rawSkills = !empty($workerData['skills']) ? $workerData['skills'] : ($regData['skills'] ?? []);
$normalizedSkills = gig_normalize_skills($rawSkills);
$skills = [];
foreach ($normalizedSkills as $skItem) {
    $sName = gig_skill_name($skItem);
    if ($sName !== '' && !in_array($sName, $skills, true)) {
        $skills[] = $sName;
    }
}
if ($isTessa) {
    foreach (['UI/UX Design', 'Figma Design', 'Design System', 'UI/UX Prototyping', 'Wireframing', 'Usability Testing', 'HTML/CSS', 'Design Tokens', 'Interaction Design', 'User Research'] as $extraSkill) {
        if (!in_array($extraSkill, $skills, true)) {
            $skills[] = $extraSkill;
        }
    }
} elseif (empty($skills)) {
    $skills = ['Komunikasi Profesional', 'Manajemen Proyek', 'Microsoft Office', 'Kolaborasi Tim', 'Problem Solving'];
}

// --- Pelatihan, Pendidikan, Sertifikasi, Pencapaian, Bahasa per profil user ---
$presetDetails = [
    'tessa' => [
        'trainings' => [
            [
                'title'             => 'Advanced Design System & UI/UX Accessibility',
                'verified'          => true,
                'category'          => 'DESAIN ANTARMUKA & PENGALAMAN PENGGUNA (UI/UX)',
                'provider'          => 'Balai Besar Pelatihan Vokasi dan Produktivitas (BBPVP) Kemnaker RI',
                'period'            => 'Mei 2024 - Juni 2024',
                'description'       => 'Pelatihan intensif perancangan sistem desain berskala enterprise, pembuatan komponen interaktif berbasis Figma variables, serta audit aksesibilitas standar WCAG 2.1 untuk platform layanan publik.',
                'certificate_label' => 'Sertifikat Pelatihan',
            ],
            [
                'title'             => 'UX Research & Usability Testing Bootcamp',
                'verified'          => true,
                'category'          => 'TEKNOLOGI INFORMASI DAN KOMUNIKASI',
                'provider'          => 'Digital Talent Scholarship (DTS) Kominfo & Kemnaker',
                'period'            => 'Agustus 2023 - September 2023',
                'description'       => 'Program pelatihan metodologi riset pengguna, penyusunan user persona, pemetaan customer journey, dan evaluasi ketergunaan prototipe bersama partisipan nyata.',
                'certificate_label' => 'Sertifikat Pelatihan',
            ],
        ],
        'educations' => [
            [
                'school'            => 'Universitas Indonesia',
                'verified'          => true,
                'degree'            => 'S1 - Ilmu Komputer / Sistem Informasi',
                'gpa'               => '3.78',
                'period'            => '2018 - 2022',
                'location'          => 'Pondok Cina, Beji, KOTA DEPOK, JAWA BARAT',
                'certificate_label' => 'Ijasah Pendidikan',
            ],
        ],
        'certifications' => [
            [
                'title'             => 'BNSP Certified UI/UX Designer',
                'verified'          => true,
                'issuer'            => 'Badan Nasional Sertifikasi Profesi (BNSP)',
                'period'            => 'Berlaku: Jan 2025 - Jan 2028',
                'credential_id'     => 'No. Reg: TIK.UIUX.02948.2025',
                'certificate_label' => 'Sertifikat Kompetensi BNSP',
            ],
            [
                'title'             => 'Google UX Design Professional Certificate',
                'verified'          => true,
                'issuer'            => 'Google Career Certificates',
                'period'            => 'Diterbitkan: Oktober 2024',
                'credential_id'     => 'ID Kredensial: GUX-2024-883920',
                'certificate_label' => 'Sertifikat Google UX',
            ],
        ],
        'achievements' => [
            [
                'title'       => 'Desainer Terbaik Proyek Transformasi Digital KarirHub',
                'verified'    => true,
                'issuer'      => 'Pusat Pasar Kerja — Kementerian Ketenagakerjaan RI',
                'year'        => '2026',
                'description' => 'Penghargaan atas kontribusi perancangan ulang antarmuka dan Design System v2 pada ekosistem layanan pencari kerja dan pemberi kerja KarirHub.',
            ],
        ],
        'languages' => [
            ['name' => 'Bahasa Indonesia', 'level' => 'Tingkat Fasih atau Bahasa Ahli'],
            ['name' => 'English (Bahasa Inggris)', 'level' => 'Tingkat Menengah - Mahir (Professional Working Proficiency)'],
        ],
    ],
    'rian' => [
        'trainings' => [
            [
                'title'             => 'Cloud Native Backend & RESTful API Architecture',
                'verified'          => true,
                'category'          => 'TEKNOLOGI INFORMASI DAN KOMUNIKASI',
                'provider'          => 'AWS Skill Builder & Kemnaker RI',
                'period'            => 'Maret 2024 - April 2024',
                'description'       => 'Pelatihan arsitektur backend berkinerja tinggi, pengamanan endpoint API, integrasi webhook pembayaran, serta pengujian beban (load testing).',
                'certificate_label' => 'Sertifikat Pelatihan',
            ],
        ],
        'educations' => [
            [
                'school'            => 'Institut Teknologi Bandung',
                'verified'          => true,
                'degree'            => 'S1 - Teknik Informatika',
                'gpa'               => '3.68',
                'period'            => '2017 - 2021',
                'location'          => 'Coblong, KOTA BANDUNG, JAWA BARAT',
                'certificate_label' => 'Ijasah Pendidikan',
            ],
        ],
        'certifications' => [
            [
                'title'             => 'AWS Certified Developer – Associate',
                'verified'          => true,
                'issuer'            => 'Amazon Web Services (AWS)',
                'period'            => 'Berlaku: 2025 - 2028',
                'credential_id'     => 'ID Kredensial: AWS-DVA-99281',
                'certificate_label' => 'Sertifikat AWS Developer',
            ],
        ],
        'achievements' => [],
        'languages' => [
            ['name' => 'Bahasa Indonesia', 'level' => 'Tingkat Fasih atau Bahasa Ahli'],
            ['name' => 'English (Bahasa Inggris)', 'level' => 'Tingkat Menengah'],
        ],
    ],
    'siti' => [
        'trainings' => [
            [
                'title'             => 'Digital Marketing & Copywriting Konversi Tinggi',
                'verified'          => true,
                'category'          => 'PEMASARAN DIGITAL DAN KOMUNIKASI',
                'provider'          => 'BBPVP Bekasi — Kemnaker RI',
                'period'            => 'Februari 2024 - Maret 2024',
                'description'       => 'Pelatihan penyusunan strategi konten media sosial, penulisan copywriting persuasif, serta optimasi kampanye digital lintas kanal.',
                'certificate_label' => 'Sertifikat Pelatihan',
            ],
        ],
        'educations' => [
            [
                'school'            => 'Universitas Airlangga',
                'verified'          => true,
                'degree'            => 'S1 - Ilmu Komunikasi',
                'gpa'               => '3.72',
                'period'            => '2018 - 2022',
                'location'          => 'Gubeng, KOTA SURABAYA, JAWA TIMUR',
                'certificate_label' => 'Ijasah Pendidikan',
            ],
        ],
        'certifications' => [
            [
                'title'             => 'BNSP Digital Marketing Specialist',
                'verified'          => true,
                'issuer'            => 'Badan Nasional Sertifikasi Profesi (BNSP)',
                'period'            => 'Berlaku: 2025 - 2028',
                'credential_id'     => 'No. Reg: MKT.DIG.01184.2025',
                'certificate_label' => 'Sertifikat Kompetensi BNSP',
            ],
        ],
        'achievements' => [],
        'languages' => [
            ['name' => 'Bahasa Indonesia', 'level' => 'Tingkat Fasih atau Bahasa Ahli'],
            ['name' => 'English (Bahasa Inggris)', 'level' => 'Tingkat Menengah'],
        ],
    ],
];

$detailKey = $isTessa ? 'tessa' : strtolower($workerKey);
$activeDetails = $presetDetails[$detailKey] ?? [
    'trainings' => [
        [
            'title'             => 'Pelatihan Berbasis Kompetensi: ' . $profile['bidang'],
            'verified'          => true,
            'category'          => 'PENGEMBANGAN KOMPETENSI KERJA (SIAPKERJA)',
            'provider'          => 'Balai Pelatihan Vokasi dan Produktivitas (BPVP) Kemnaker RI',
            'period'            => 'Mei 2024 - Juni 2024',
            'description'       => 'Program peningkatan kompetensi teknis dan kesiapan kerja profesional terintegrasi pada ekosistem SIAPkerja Kementerian Ketenagakerjaan RI.',
            'certificate_label' => 'Sertifikat Pelatihan',
        ],
    ],
    'educations' => [
        [
            'school'            => 'Politeknik Negeri Jakarta',
            'verified'          => true,
            'degree'            => 'D4 / S1 Terapan',
            'gpa'               => '3.65',
            'period'            => '2018 - 2022',
            'location'          => $resolvedLocation,
            'certificate_label' => 'Ijasah Pendidikan',
        ],
    ],
    'certifications' => [],
    'achievements'   => [],
    'languages'      => [
        ['name' => 'Bahasa Indonesia', 'level' => 'Tingkat Fasih atau Bahasa Ahli'],
        ['name' => 'English (Bahasa Inggris)', 'level' => 'Tingkat Menengah'],
    ],
];

$trainings      = $activeDetails['trainings'];
$educations     = $activeDetails['educations'];
$certifications = $activeDetails['certifications'];
$achievements   = $activeDetails['achievements'];
$languages      = $activeDetails['languages'];

$profile['birth_place_date'] = 'Jakarta, 14 Mei 1998';
$profile['gender']           = $isTessa ? 'Perempuan' : 'Laki-laki';
$profile['marital_status']   = 'Belum Kawin';
$profile['address_ktp']      = 'Senayan, Kebayoran Baru, KOTA ADM. JAKARTA SELATAN, DKI JAKARTA - 12190';
$profile['linkedin_url']     = '';
$profile['expected_salary']  = '';
$profile['work_preference']  = '';

// Sinkronkan seluruh bagian profil dengan tabel `users` (Progressive Profiling)
require_once __DIR__ . '/includes/progressive-profiling.php';
$ppPdo = gig_db();
if ($ppPdo !== null) {
    $ppUserId = gig_profiling_resolve_user_id($ppPdo);
    $stmtPpUser = $ppPdo->prepare('SELECT * FROM `users` WHERE `id` = :id LIMIT 1');
    $stmtPpUser->execute([':id' => $ppUserId]);
    $ppUserRow = $stmtPpUser->fetch(PDO::FETCH_ASSOC);

    if ($ppUserRow) {
        // 1. Biodata: NIK, TTL, Jenis Kelamin, Status, Nomor Telepon, Alamat KTP, Domisili, LinkedIn, Gaji, Preferensi Kerja, Bio
        $profile['nik']              = trim((string)($ppUserRow['nik'] ?? ''));
        $profile['birth_place_date'] = trim((string)($ppUserRow['birth_place_date'] ?? ''));
        $profile['gender']           = trim((string)($ppUserRow['gender'] ?? ''));
        $profile['marital_status']   = trim((string)($ppUserRow['marital_status'] ?? ''));
        $profile['phone']            = trim((string)($ppUserRow['phone'] ?? ''));
        $profile['address_ktp']      = trim((string)($ppUserRow['address_ktp'] ?? ''));
        $profile['location']         = trim((string)($ppUserRow['domicile'] ?? ''));
        $profile['linkedin_url']     = trim((string)($ppUserRow['linkedin_url'] ?? ''));
        $profile['expected_salary']  = trim((string)($ppUserRow['expected_salary'] ?? ''));
        $profile['work_preference']  = trim((string)($ppUserRow['work_preference'] ?? ''));

        $dbBio = trim((string)($ppUserRow['bio'] ?? ''));
        if ($dbBio !== '') {
            $profile['bio_full']  = $dbBio;
            $profile['bio_short'] = mb_strlen($dbBio) > 175 ? (rtrim(mb_substr($dbBio, 0, 172)) . '...') : $dbBio;
        } else {
            $profile['bio_full']  = '';
            $profile['bio_short'] = 'Belum ada ringkasan profil. Silakan lengkapi melalui pop-up Progressive Profiling.';
        }

        // 2. Pengalaman Kerja (`work_experience`)
        $dbExp = trim((string)($ppUserRow['work_experience'] ?? ''));
        if ($dbExp === '') {
            $experiences = [];
        } else {
            $expParts = array_map('trim', explode('-', $dbExp, 2));
            $expRole  = $expParts[0] !== '' ? $expParts[0] : $dbExp;
            $expComp  = $expParts[1] ?? ($experiences[0]['company'] ?? 'Mitra SIAPKerja Kemnaker');
            if (!empty($experiences)) {
                $experiences[0]['title']    = $expRole;
                $experiences[0]['company']  = $expComp;
                $experiences[0]['location'] = $profile['location'] !== '' ? $profile['location'] : $experiences[0]['location'];
            } else {
                $experiences = [[
                    'title'    => $expRole,
                    'verified' => true,
                    'company'  => $expComp,
                    'period'   => '2025 - Sekarang',
                    'location' => $profile['location'] !== '' ? $profile['location'] : 'Indonesia',
                    'summary'  => 'Pengalaman kerja terverifikasi pada profil SIAPKerja.',
                ]];
            }
        }

        // 3. Pelatihan (`training_name`)
        $dbTraining = trim((string)($ppUserRow['training_name'] ?? ''));
        if ($dbTraining === '') {
            $trainings = [];
        } else {
            $trParts    = array_map('trim', explode('-', $dbTraining, 2));
            $trTitle    = $trParts[0] !== '' ? $trParts[0] : $dbTraining;
            $trProvider = $trParts[1] ?? ($trainings[0]['provider'] ?? 'Balai Pelatihan Vokasi dan Produktivitas (BPVP) Kemnaker RI');
            if (!empty($trainings)) {
                $trainings[0]['title']    = $trTitle;
                $trainings[0]['provider'] = $trProvider;
            } else {
                $trainings = [[
                    'title'             => $trTitle,
                    'verified'          => true,
                    'category'          => 'PENGEMBANGAN KOMPETENSI KERJA (SIAPKERJA)',
                    'provider'          => $trProvider,
                    'period'            => '2025',
                    'description'       => 'Program pelatihan peningkatan kompetensi teknis terverifikasi SIAPKerja.',
                    'certificate_label' => 'Sertifikat Pelatihan',
                ]];
            }
        }

        // 4. Pendidikan (`highest_education`)
        $dbEdu = trim((string)($ppUserRow['highest_education'] ?? ''));
        if ($dbEdu === '') {
            $educations = [];
        } else {
            $eduParts = array_map('trim', explode('-', $dbEdu));
            if (count($eduParts) >= 3) {
                $eduDegree = $eduParts[0] . ' - ' . $eduParts[1];
                $eduSchool = $eduParts[2];
            } elseif (count($eduParts) === 2) {
                if (preg_match('/^(SMA|SMK|D1|D2|D3|D4|S1|S2|S3)\b/i', $eduParts[0])) {
                    $eduDegree = $eduParts[0];
                    $eduSchool = $eduParts[1];
                } else {
                    $eduSchool = $eduParts[0];
                    $eduDegree = $eduParts[1];
                }
            } else {
                $eduSchool = $dbEdu;
                $eduDegree = $educations[0]['degree'] ?? 'Sarjana / Diploma';
            }

            if (!empty($educations)) {
                $educations[0]['school'] = $eduSchool;
                $educations[0]['degree'] = $eduDegree;
            } else {
                $educations = [[
                    'school'            => $eduSchool,
                    'verified'          => true,
                    'degree'            => $eduDegree,
                    'gpa'               => '3.75',
                    'period'            => '2018 - 2022',
                    'location'          => $profile['location'] !== '' ? $profile['location'] : 'Indonesia',
                    'certificate_label' => 'Ijasah Pendidikan',
                ]];
            }
        }

        // 5. Sertifikasi (`certification_name`)
        $dbCert = trim((string)($ppUserRow['certification_name'] ?? ''));
        if ($dbCert === '') {
            $certifications = [];
        } else {
            $certParts  = array_map('trim', explode('-', $dbCert, 2));
            $certTitle  = $certParts[0] !== '' ? $certParts[0] : $dbCert;
            $certIssuer = $certParts[1] ?? 'Badan Nasional Sertifikasi Profesi (BNSP) / Lembaga Resmi';
            $certifications = [[
                'title'             => $certTitle,
                'verified'          => true,
                'issuer'            => $certIssuer,
                'period'            => 'Berlaku: 2025 - 2028',
                'credential_id'     => 'Terverifikasi via Progressive Profiling SIAPKerja',
                'certificate_label' => 'Sertifikat Kompetensi',
            ]];
        }

        // 6. Pencapaian (`achievement_name`)
        $dbAch = trim((string)($ppUserRow['achievement_name'] ?? ''));
        if ($dbAch === '') {
            $achievements = [];
        } else {
            $achParts  = array_map('trim', explode('-', $dbAch, 2));
            $achTitle  = $achParts[0] !== '' ? $achParts[0] : $dbAch;
            $achIssuer = $achParts[1] ?? 'Kementerian Ketenagakerjaan RI / Mitra Industri';
            $achievements = [[
                'title'       => $achTitle,
                'verified'    => true,
                'issuer'      => $achIssuer,
                'year'        => date('Y'),
                'description' => 'Penghargaan dan pencapaian profesional yang tercatat pada profil SIAPKerja.',
            ]];
        }

        // 7. Keahlian (`skills_text`)
        $dbSkills = trim((string)($ppUserRow['skills_text'] ?? ''));
        if ($dbSkills === '') {
            $skills = [];
        } else {
            $parsedSkills = array_values(array_filter(array_map('trim', explode(',', $dbSkills)), static fn($s) => $s !== ''));
            $skills = $parsedSkills;
        }

        // 8. Bahasa (`english_level` & `mandarin_level`)
        $languages = [
            ['name' => 'Bahasa Indonesia', 'level' => 'Tingkat Fasih atau Bahasa Ahli'],
        ];
        $dbEng = trim((string)($ppUserRow['english_level'] ?? ''));
        if ($dbEng !== '') {
            $languages[] = [
                'name'  => 'English (Bahasa Inggris)',
                'level' => $dbEng,
            ];
        }
        $dbMan = trim((string)($ppUserRow['mandarin_level'] ?? ''));
        if ($dbMan !== '') {
            $languages[] = [
                'name'  => 'Mandarin (Bahasa Mandarin)',
                'level' => $dbMan,
            ];
        }
    }
}

$editProfilHref = ($resolvedRole === 'employer')
    ? 'employer-profile.php'
    : ($isTessa || $workerData !== null ? 'worker-edit-profile.php' : 'worker-register.php');
$gigProfileHref = ($resolvedRole === 'employer')
    ? 'employer-profile.php'
    : 'worker-profile.php?id=' . urlencode($profile['id']);
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
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #ffffff;
      background-color: #dbeafe;
      box-shadow: 0 2px 10px rgba(17, 24, 39, 0.12);
      display: block;
    }

    .camera-btn {
      position: absolute;
      right: 0;
      bottom: 0;
      width: 31px;
      height: 31px;
      border-radius: 50%;
      background: #ffffff;
      border: 1.5px solid #e5e7eb;
      box-shadow: 0 2px 6px rgba(17, 24, 39, 0.12);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #111827;
      cursor: pointer;
      padding: 0;
    }

    .camera-btn:hover {
      background: #f9fafb;
    }

    .biodata-modal-backdrop {
      position: fixed;
      top: 0;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 100%;
      max-width: 702px;
      background-color: rgba(0, 0, 0, 0.45);
      z-index: 1005;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    .biodata-modal-backdrop.open {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
    }

    .biodata-sheet {
      position: relative;
      width: 100%;
      max-height: 88vh;
      overflow-y: auto;
      background-color: #ffffff;
      border-radius: 16px 16px 0 0;
      padding: 20px 24px 32px 24px;
      box-shadow: 0 -10px 35px rgba(0, 0, 0, 0.16);
      transform: translateY(100%);
      transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .biodata-modal-backdrop.open .biodata-sheet {
      transform: translateY(0);
    }

    .biodata-sheet-close {
      position: absolute;
      top: 16px;
      right: 18px;
      width: 32px;
      height: 32px;
      border: none;
      background: transparent;
      color: #111827;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      padding: 0;
    }

    .biodata-sheet-close:hover {
      background-color: #f3f4f6;
    }

    .biodata-sheet-title {
      font-size: 16px;
      font-weight: 700;
      color: #111827;
      line-height: 22px;
      margin-top: 22px;
      margin-bottom: 20px;
      letter-spacing: -0.01em;
    }

    .biodata-sheet-list {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .biodata-sheet-item {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .biodata-sheet-label {
      font-size: 13.8px;
      font-weight: 400;
      color: #6b7280;
      line-height: 19px;
    }

    .biodata-sheet-value {
      font-size: 14.5px;
      font-weight: 400;
      color: #111827;
      line-height: 21px;
      word-break: break-word;
    }

    .exp-summary {
      font-size: 13.3px;
      font-weight: 400;
      color: #374151;
      line-height: 19px;
      margin-top: 6px;
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
            <button type="button" class="camera-btn" aria-label="Ubah foto profil" onclick="window.location.href='<?php echo htmlspecialchars($editProfilHref, ENT_QUOTES, 'UTF-8'); ?>'">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                <circle cx="12" cy="13" r="4"></circle>
              </svg>
            </button>
          </div>

          <div class="edit-profil-wrapper" id="editProfilWrap">
            <button type="button" class="btn-edit-profil" id="editProfilBtn" aria-expanded="false">
              <span>Edit Profil</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <div class="edit-dropdown" id="editDropdown">
              <a href="<?php echo htmlspecialchars($editProfilHref, ENT_QUOTES, 'UTF-8'); ?>">Ubah Data Profil</a>
              <a href="<?php echo htmlspecialchars($gigProfileHref, ENT_QUOTES, 'UTF-8'); ?>">Lihat Profil Gig Worker</a>
              <a href="#unduh-cv" onclick="window.print(); return false;">Unduh CV</a>
            </div>
          </div>
        </div>

        <h2 class="profile-name"><?php echo htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="profile-email"><?php echo htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8'); ?></div>

        <div data-profile-field="bio">
          <p class="profile-bio" id="profileBioText"
             data-short="<?php echo htmlspecialchars($profile['bio_short'], ENT_QUOTES, 'UTF-8'); ?>"
             data-full="<?php echo htmlspecialchars($profile['bio_full'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($profile['bio_short'], ENT_QUOTES, 'UTF-8'); ?>
          </p>
          <?php if ($profile['bio_short'] !== $profile['bio_full'] && $profile['bio_full'] !== ''): ?>
            <button type="button" class="btn-read-more" id="toggleBioBtn">Tampilkan Lebih Banyak</button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Lihat Informasi Biodata -->
      <div class="biodata-bar">
        <a href="#biodata" class="biodata-link" id="toggleBiodataBtn">Lihat Informasi Biodata</a>
      </div>

      <!-- Pop-up Bottom Sheet: Biodata Pengguna -->
      <div class="biodata-modal-backdrop" id="biodataModalBackdrop" role="dialog" aria-modal="true" aria-labelledby="biodataModalTitle">
        <div class="biodata-sheet" id="biodataSheet">
          <button type="button" class="biodata-sheet-close" id="closeBiodataBtn" aria-label="Tutup">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>

          <h3 class="biodata-sheet-title" id="biodataModalTitle">Biodata pengguna</h3>

          <div class="biodata-sheet-list">
            <div class="biodata-sheet-item" data-profile-field="nik">
              <div class="biodata-sheet-label">Nomor Induk Kependudukan</div>
              <div class="biodata-sheet-value"><?php echo htmlspecialchars($profile['nik'] !== '' ? $profile['nik'] : 'Belum diisi', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="biodata-sheet-item" data-profile-field="birth_place_date">
              <div class="biodata-sheet-label">Tempat, tanggal lahir</div>
              <div class="biodata-sheet-value"><?php echo htmlspecialchars($profile['birth_place_date'] !== '' ? $profile['birth_place_date'] : 'Belum diisi', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="biodata-sheet-item" data-profile-field="gender">
              <div class="biodata-sheet-label">Jenis kelamin</div>
              <div class="biodata-sheet-value"><?php echo htmlspecialchars($profile['gender'] !== '' ? $profile['gender'] : 'Belum diisi', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="biodata-sheet-item" data-profile-field="marital_status">
              <div class="biodata-sheet-label">Status</div>
              <div class="biodata-sheet-value"><?php echo htmlspecialchars($profile['marital_status'] !== '' ? $profile['marital_status'] : 'Belum diisi', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="biodata-sheet-item" data-profile-field="phone">
              <div class="biodata-sheet-label">Nomor telepon</div>
              <div class="biodata-sheet-value"><?php echo htmlspecialchars(str_replace('-', '', $profile['phone']) !== '' ? str_replace('-', '', $profile['phone']) : 'Belum diisi', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="biodata-sheet-item" data-profile-field="address_ktp">
              <div class="biodata-sheet-label">Alamat sesuai KTP</div>
              <div class="biodata-sheet-value"><?php echo htmlspecialchars($profile['address_ktp'] !== '' ? $profile['address_ktp'] : 'Belum diisi', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="biodata-sheet-item" data-profile-field="domicile">
              <div class="biodata-sheet-label">Alamat domisili</div>
              <div class="biodata-sheet-value"><?php echo htmlspecialchars($profile['location'] !== '' ? $profile['location'] : 'Belum diisi', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <?php if ($profile['expected_salary'] !== ''): ?>
            <div class="biodata-sheet-item" data-profile-field="expected_salary">
              <div class="biodata-sheet-label">Ekspektasi gaji</div>
              <div class="biodata-sheet-value"><?php echo htmlspecialchars($profile['expected_salary'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php endif; ?>

            <?php if ($profile['work_preference'] !== ''): ?>
            <div class="biodata-sheet-item" data-profile-field="work_preference">
              <div class="biodata-sheet-label">Preferensi sistem kerja</div>
              <div class="biodata-sheet-value"><?php echo htmlspecialchars($profile['work_preference'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <div class="section-spacer"></div>

    <!-- Pengalaman Kerja Card -->
    <section class="<?php echo empty($experiences) ? 'certification-card' : 'experience-card'; ?>" data-profile-field="work_experience">
      <div class="experience-header">
        <h3 class="experience-title">Pengalaman Kerja</h3>
        <button type="button" class="btn-add-exp" aria-label="Tambah Pengalaman Kerja" onclick="window.location.href='<?php echo htmlspecialchars($editProfilHref, ENT_QUOTES, 'UTF-8'); ?>'">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
      </div>

      <?php if (empty($experiences)): ?>
        <div class="empty-state">
          <img src="assets/profile-empty-sertifikasi.png" alt="Tidak ada pengalaman kerja" class="empty-state-img" />
          <h4 class="empty-state-title">Tidak ada pengalaman kerja</h4>
          <p class="empty-state-sub">Silahkan lengkapi data pengalaman kerja kamu melalui pop-up Progressive Profiling.</p>
        </div>
      <?php else: ?>
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
                <?php if (!empty($exp['summary'])): ?>
                  <div class="exp-summary"><?php echo htmlspecialchars($exp['summary'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <div class="section-spacer"></div>

    <!-- Pelatihan Card -->
    <section class="<?php echo empty($trainings) ? 'certification-card' : 'section-card'; ?>" data-profile-field="training_name">
      <div class="experience-header">
        <h3 class="experience-title">Pelatihan</h3>
        <button type="button" class="btn-add-exp" aria-label="Tambah Pelatihan">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
      </div>

      <?php if (empty($trainings)): ?>
        <div class="empty-state">
          <img src="assets/profile-empty-sertifikasi.png" alt="Tidak ada pelatihan" class="empty-state-img" />
          <h4 class="empty-state-title">Tidak ada data pelatihan</h4>
          <p class="empty-state-sub">Silahkan lengkapi data pelatihan kamu, data pelatihan ini akan menjadi kelengkapan profilmu.</p>
        </div>
      <?php else: ?>
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
      <?php endif; ?>
    </section>

    <div class="section-spacer"></div>

    <!-- Pendidikan Card -->
    <section class="<?php echo empty($educations) ? 'certification-card' : 'section-card'; ?>" data-profile-field="highest_education">
      <div class="experience-header">
        <h3 class="experience-title">Pendidikan</h3>
        <button type="button" class="btn-add-exp" aria-label="Tambah Pendidikan">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
      </div>

      <?php if (empty($educations)): ?>
        <div class="empty-state">
          <img src="assets/profile-empty-sertifikasi.png" alt="Tidak ada pendidikan" class="empty-state-img" />
          <h4 class="empty-state-title">Tidak ada data pendidikan</h4>
          <p class="empty-state-sub">Silahkan lengkapi data pendidikan terakhir kamu untuk melengkapi profilmu.</p>
        </div>
      <?php else: ?>
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
      <?php endif; ?>
    </section>

    <div class="section-spacer"></div>

    <!-- Sertifikasi Card -->
    <section class="<?php echo empty($certifications) ? 'certification-card' : 'section-card'; ?>" data-profile-field="certification_name">
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
      <?php else: ?>
        <ul class="experience-list">
          <?php foreach ($certifications as $cert): ?>
            <li class="section-item">
              <div class="exp-icon-box" aria-hidden="true">
                <img src="assets/profile-pelatihan-icon.png" alt="" />
              </div>
              <div class="exp-content">
                <div class="exp-role-row">
                  <span class="exp-role"><?php echo htmlspecialchars($cert['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php if (!empty($cert['verified'])): ?>
                    <span class="verified-badge" title="Terverifikasi">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="training-category"><?php echo htmlspecialchars($cert['issuer'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="training-provider"><?php echo htmlspecialchars($cert['credential_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="training-period"><?php echo htmlspecialchars($cert['period'], ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="#sertifikat-kompetensi" class="attachment-box">
                  <img src="assets/profile-doc-icon.png" alt="" />
                  <span><?php echo htmlspecialchars($cert['certificate_label'] ?? 'Sertifikat Kompetensi', ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <div class="section-spacer"></div>

    <!-- Pencapaian Card -->
    <section class="<?php echo empty($achievements) ? 'certification-card' : 'section-card'; ?>" data-profile-field="achievement_name">
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
      <?php else: ?>
        <ul class="experience-list">
          <?php foreach ($achievements as $ach): ?>
            <li class="section-item">
              <div class="exp-icon-box" aria-hidden="true">
                <img src="assets/profile-briefcase-icon.png" alt="" />
              </div>
              <div class="exp-content">
                <div class="exp-role-row">
                  <span class="exp-role"><?php echo htmlspecialchars($ach['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php if (!empty($ach['verified'])): ?>
                    <span class="verified-badge" title="Terverifikasi">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="training-category"><?php echo htmlspecialchars($ach['issuer'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="training-period">Tahun <?php echo htmlspecialchars($ach['year'], ENT_QUOTES, 'UTF-8'); ?></div>
                <p class="training-desc" style="margin-bottom:0;"><?php echo htmlspecialchars($ach['description'], ENT_QUOTES, 'UTF-8'); ?></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <div class="section-spacer"></div>

    <!-- Keahlian Card -->
    <section class="<?php echo empty($skills) ? 'certification-card' : 'skills-card'; ?>" data-profile-field="skills_text">
      <div class="experience-header">
        <h3 class="experience-title">Keahlian</h3>
        <button type="button" class="btn-add-exp" aria-label="Tambah Keahlian">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
      </div>

      <?php if (empty($skills)): ?>
        <div class="empty-state">
          <img src="assets/profile-empty-sertifikasi.png" alt="Tidak ada keahlian" class="empty-state-img" />
          <h4 class="empty-state-title">Tidak ada data keahlian</h4>
          <p class="empty-state-sub">Silahkan lengkapi daftar keahlian kamu untuk menjadi kelengkapan profilmu.</p>
        </div>
      <?php else: ?>
        <div class="skills-list">
          <?php foreach ($skills as $skill): ?>
            <span class="skill-pill"><?php echo htmlspecialchars($skill, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <div class="section-spacer"></div>

    <!-- Bahasa Card -->
    <section class="language-card" data-profile-field="english_level mandarin_level">
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
    window.initProfileInteractions = function () {
      var bioEl = document.getElementById('profileBioText');
      var toggleBtn = document.getElementById('toggleBioBtn');
      var expanded = false;

      if (bioEl && toggleBtn) {
        toggleBtn.onclick = function () {
          expanded = !expanded;
          bioEl.textContent = expanded
            ? bioEl.getAttribute('data-full')
            : bioEl.getAttribute('data-short');
          toggleBtn.textContent = expanded
            ? 'Tampilkan Lebih Sedikit'
            : 'Tampilkan Lebih Banyak';
        };
      }

      var editWrap = document.getElementById('editProfilWrap');
      var editBtn = document.getElementById('editProfilBtn');
      var editDropdown = document.getElementById('editDropdown');

      if (editWrap && editBtn && editDropdown) {
        editBtn.onclick = function (e) {
          e.stopPropagation();
          var isOpen = editDropdown.classList.toggle('open');
          editBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };
      }

      var biodataBtn = document.getElementById('toggleBiodataBtn');
      var biodataBackdrop = document.getElementById('biodataModalBackdrop');
      var closeBiodataBtn = document.getElementById('closeBiodataBtn');

      if (biodataBtn && biodataBackdrop) {
        biodataBtn.onclick = function (e) {
          e.preventDefault();
          biodataBackdrop.classList.add('open');
        };
      }

      if (closeBiodataBtn && biodataBackdrop) {
        closeBiodataBtn.onclick = function () {
          biodataBackdrop.classList.remove('open');
        };
      }

      if (biodataBackdrop) {
        biodataBackdrop.onclick = function (e) {
          if (e.target === biodataBackdrop) {
            biodataBackdrop.classList.remove('open');
          }
        };
      }
    };

    document.addEventListener('click', function (e) {
      var editWrap = document.getElementById('editProfilWrap');
      var editBtn = document.getElementById('editProfilBtn');
      var editDropdown = document.getElementById('editDropdown');
      if (editWrap && editBtn && editDropdown && !editWrap.contains(e.target)) {
        editDropdown.classList.remove('open');
        editBtn.setAttribute('aria-expanded', 'false');
      }
    });

    window.initProfileInteractions();
  </script>
  <?php require __DIR__ . '/includes/nudge-widget.php'; ?>
</body>
</html>
