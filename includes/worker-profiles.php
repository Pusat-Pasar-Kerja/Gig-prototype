<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Calculates a numeric timeline score for a project period string (newest period = higher score).
 */
function gig_parse_period_score(string $period): int
{
    $p = strtolower(trim($period));
    if ($p === '') {
        return 0;
    }

    $monthMap = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
        'mei' => 5, 'may' => 5, 'jun' => 6, 'jul' => 7,
        'agu' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10,
        'oct' => 10, 'nov' => 11, 'des' => 12, 'dec' => 12
    ];

    // Check for ongoing / present / sekarang / berjalan
    if (preg_match('/(sekarang|present|berjalan|ongoing|current)/i', $p)) {
        preg_match_all('/\b(19\d\d|20\d\d)\b/', $p, $matches);
        $startYear = !empty($matches[1]) ? (int)end($matches[1]) : (int)date('Y');
        
        $startMonth = 0;
        foreach ($monthMap as $mStr => $mNum) {
            if (str_contains($p, $mStr)) {
                $startMonth = max($startMonth, $mNum);
            }
        }
        return 90000000 + ($startYear * 100) + $startMonth;
    }

    preg_match_all('/\b(19\d\d|20\d\d)\b/', $p, $matches);
    if (empty($matches[1])) {
        return 100000;
    }

    $years = array_map('intval', $matches[1]);
    $endYear = max($years);
    $startYear = min($years);

    $endMonth = 0;
    foreach ($monthMap as $mStr => $mNum) {
        if (str_contains($p, $mStr)) {
            $endMonth = max($endMonth, $mNum);
        }
    }

    return ($endYear * 10000) + ($endMonth * 100) + ($startYear % 100);
}

/**
 * Sorts an array of experience/project items chronologically by period (newest project first).
 */
function gig_sort_experience_timeline(array &$experience): void
{
    usort($experience, function ($a, $b) {
        $periodA = (string)($a['period'] ?? '');
        $periodB = (string)($b['period'] ?? '');
        $scoreA = gig_parse_period_score($periodA);
        $scoreB = gig_parse_period_score($periodB);
        return $scoreB <=> $scoreA;
    });
}

/**
 * Standard expertise levels for Gig Worker skills.
 *
 * @return list<string>
 */
function gig_skill_levels(): array
{
    return ['Pemula', 'Menengah', 'Mahir', 'Ahli'];
}

/**
 * Normalizes skill entries into a uniform list of ['name' => string, 'level' => string].
 * Supports JSON string, comma-separated string, array of strings, or array of associative arrays.
 *
 * @param mixed $skills
 * @return list<array{name: string, level: string}>
 */
function gig_normalize_skills(mixed $skills): array
{
    if ($skills === null || $skills === false || $skills === '') {
        return [];
    }

    if (is_string($skills)) {
        $trimmed = trim($skills);
        if ($trimmed === '') {
            return [];
        }
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            $skills = $decoded;
        } else {
            $skills = array_filter(array_map('trim', explode(',', $trimmed)));
        }
    }

    if (!is_array($skills)) {
        return [];
    }

    $validLevels = ['Pemula', 'Menengah', 'Mahir', 'Ahli'];
    $normalized = [];

    foreach ($skills as $item) {
        $name = '';
        $level = 'Menengah';

        if (is_array($item)) {
            $name = trim((string)($item['name'] ?? $item['skill'] ?? ''));
            $lvlInput = trim((string)($item['level'] ?? $item['expertise'] ?? ''));
            if ($lvlInput !== '') {
                foreach ($validLevels as $vl) {
                    if (strcasecmp($vl, $lvlInput) === 0) {
                        $level = $vl;
                        break;
                    }
                }
            }
        } elseif (is_string($item)) {
            $str = trim($item);
            if (preg_match('/^(.*?)\s*[\(\[]\s*(Pemula|Menengah|Mahir|Ahli|Beginner|Intermediate|Advanced|Expert)\s*[\)\]]$/i', $str, $matches)) {
                $name = trim($matches[1]);
                $matchedLvl = strtolower($matches[2]);
                if (in_array($matchedLvl, ['pemula', 'beginner'], true)) {
                    $level = 'Pemula';
                } elseif (in_array($matchedLvl, ['menengah', 'intermediate'], true)) {
                    $level = 'Menengah';
                } elseif (in_array($matchedLvl, ['mahir', 'advanced'], true)) {
                    $level = 'Mahir';
                } elseif (in_array($matchedLvl, ['ahli', 'expert'], true)) {
                    $level = 'Ahli';
                }
            } else {
                $name = $str;
                $level = 'Menengah';
            }
        }

        if ($name !== '') {
            $normalized[] = [
                'name'  => $name,
                'level' => $level,
            ];
        }
    }

    return $normalized;
}

/**
 * Returns the name of a skill item.
 *
 * @param mixed $skill
 * @return string
 */
function gig_skill_name(mixed $skill): string
{
    if (is_array($skill)) {
        return trim((string)($skill['name'] ?? $skill['skill'] ?? ''));
    }
    if (is_string($skill)) {
        if (preg_match('/^(.*?)\s*[\(\[]\s*(Pemula|Menengah|Mahir|Ahli)\s*[\)\]]$/i', trim($skill), $m)) {
            return trim($m[1]);
        }
        return trim($skill);
    }
    return '';
}

/**
 * Returns the expertise level of a skill item.
 *
 * @param mixed $skill
 * @return string
 */
function gig_skill_level(mixed $skill): string
{
    if (is_array($skill)) {
        $lvl = trim((string)($skill['level'] ?? $skill['expertise'] ?? 'Menengah'));
        return $lvl !== '' ? $lvl : 'Menengah';
    }
    if (is_string($skill)) {
        if (preg_match('/[\(\[]\s*(Pemula|Menengah|Mahir|Ahli)\s*[\)\]]$/i', trim($skill), $m)) {
            return ucfirst(strtolower($m[1]));
        }
    }
    return 'Menengah';
}

/**
 * Returns color tokens for an expertise level badge.
 *
 * @param string $level
 * @return array{bg: string, color: string, border: string}
 */
function gig_skill_level_badge_style(string $level): array
{
    return match (strtolower(trim($level))) {
        'pemula', 'beginner' => [
            'bg'     => '#f1f5f9',
            'color'  => '#475569',
            'border' => '#cbd5e1',
        ],
        'mahir', 'advanced' => [
            'bg'     => '#ecfdf5',
            'color'  => '#047857',
            'border' => '#a7f3d0',
        ],
        'ahli', 'expert' => [
            'bg'     => '#faf5ff',
            'color'  => '#7e22ce',
            'border' => '#e9d5ff',
        ],
        default => [
            'bg'     => '#eff6ff',
            'color'  => '#1d4ed8',
            'border' => '#bfdbfe',
        ],
    };
}

/**
 * Formats a list of skills into a readable summary string (e.g. "Figma (Mahir), React (Menengah)").
 *
 * @param mixed $skills
 * @return string
 */
function gig_format_skills_summary(mixed $skills): string
{
    $normalized = gig_normalize_skills($skills);
    if ($normalized === []) {
        return '-';
    }
    $parts = [];
    foreach ($normalized as $item) {
        $parts[] = $item['name'] . ' (' . $item['level'] . ')';
    }
    return implode(', ', $parts);
}

/**
 * Shared Gig Worker account profiles.
 * Contact details stay hidden until both sides have agreed to work together.
 * Individual review ratings are whole integers (1–5).
 * The overall 'rating' field is a computed average and may be a decimal.
 * 'completed_projects' = completed rows in project_history.
 * 'total_projects' = completed + cancelled in project_history (same basis as Riwayat Proyek).
 */
function gig_worker_profiles(): array
{
    if (!function_exists('gig_worker_project_stats')) {
        require_once __DIR__ . '/project-history.php';
    }

    $profiles = [
        'tessa' => [
            'id' => 'tessa',
            'name' => 'Theressa Zaratrusha',
            'initials' => 'TH',
            'color' => '#2563eb',
            'photo' => 'https://api.dicebear.com/9.x/notionists/svg?seed=Theressa&backgroundColor=dbeafe',
            'title' => 'Lead UI/UX Designer',
            'location' => 'Jakarta, Indonesia',
            'rating' => 5.0, // average of reviews: (5+5+5)/3 = 5.0
            'reviews_count' => 3,       // matches reviews array below
            'completed_projects' => 3,  // matches portfolio entries below
            'total_projects' => 3,      // matches portfolio entries below
            'verified' => true,
            'agreed' => false,
            'category' => 'ui-ux',
            'applied_project' => 'Redesign UI/UX Dashboard Prototype KarirHub',
            'bid' => 'Rp 8.000.000',
            'eta' => '14 Hari Kerja',
            'proposal' => '',
            'skills' => [
                ['name' => 'Figma Design', 'level' => 'Mahir'],
                ['name' => 'UI/UX Prototyping', 'level' => 'Mahir'],
                ['name' => 'Design System', 'level' => 'Ahli'],
                ['name' => 'Usability Testing', 'level' => 'Menengah'],
                ['name' => 'Wireframing', 'level' => 'Mahir'],
            ],
            'social_media' => [
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/theressaz'],
                ['platform' => 'GitHub', 'url' => 'https://github.com/theressaz'],
                ['platform' => 'Instagram', 'url' => 'https://instagram.com/theressaz']
            ],
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'contact' => [
                'wa' => '0812-3456-7890',
                'email' => 'theressaz@pasker.id',
            ],
            'experience' => [
                [
                    'role' => 'Lead UI/UX Designer',
                    'project' => 'Redesign Dashboard KarirHub (berjalan)',
                    'period' => 'Sep 2026 — sekarang',
                    'summary' => 'Merancang alur, wireframe, dan prototype interaktif dashboard pemberi kerja.',
                    'output_title' => 'Figma Interactive Prototype & Dashboard Wireframe',
                    'files' => [
                        ['name' => 'Figma Prototype (Dashboard KarirHub)', 'url' => 'https://figma.com/@theressaz/karirhub-dashboard-prototype', 'type' => 'Figma Prototype'],
                        ['name' => 'Design System UI Kit v2', 'url' => 'https://figma.com/@theressaz/karirhub-design-system', 'type' => 'UI Kit']
                    ],
                ],
                [
                    'role' => 'Product Designer',
                    'project' => 'Portal Lowongan Digital BUMN',
                    'period' => 'Jan 2025 — Jun 2026',
                    'summary' => 'Menyusun design system 80+ komponen dan uji keterbacaan untuk 3 modul layanan.',
                    'output_title' => 'Design System 80+ Components & Accessibility Report',
                    'files' => [
                        ['name' => 'Figma UI Kit & Design Tokens BUMN', 'url' => 'https://figma.com/@theressaz/bumn-digital-design-system', 'type' => 'Design System']
                    ],
                ],
                [
                    'role' => 'UI Designer',
                    'project' => 'Aplikasi Pelaporan Pekerja Lepas',
                    'period' => 'Mar 2024 — Des 2024',
                    'summary' => 'Mendesain alur onboarding dan dashboard pelaporan harian untuk mitra lapangan.',
                    'output_title' => 'Mobile Onboarding & Daily Reporting Case Study',
                    'files' => [
                        ['name' => 'Behance Case Study (Aplikasi Pekerja Lepas)', 'url' => 'https://behance.net/gallery/gigworker-reporting-app', 'type' => 'Case Study']
                    ],
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
                        ['name' => 'Panduan_Interaksi_UI.pdf', 'type' => 'Dokumen PDF', 'size' => '4.8 MB', 'url' => '#'],
                        ['name' => 'Prototype_Interactive_Flow.url', 'type' => 'Tautan Prototype Figma', 'size' => 'Akses Web', 'url' => '#']
                    ]
                ],
                [
                    'id' => 'tessa-port-2',
                    'title' => 'Portal Rekrutmen BUMN',
                    'type' => 'Web App UI',
                    'client' => 'PT Talenta Nusantara',
                    'year' => '2025',
                    'deliverable' => 'High-fidelity mockup desktop/mobile dan panduan interaksi.',
                    'files' => [
                        ['name' => 'HighFidelity_Mockups_BUMN.zip', 'type' => 'Arsip Desain', 'size' => '48.1 MB', 'url' => '#'],
                        ['name' => 'Dokumentasi_Desain_Portal.pdf', 'type' => 'Dokumen PDF', 'size' => '3.2 MB', 'url' => '#']
                    ]
                ],
                [
                    'id' => 'tessa-port-3',
                    'title' => 'Onboarding Gig Worker',
                    'type' => 'UX Flow',
                    'client' => 'Startup Ketenagakerjaan',
                    'year' => '2024',
                    'deliverable' => 'User flow, wireframe, dan hasil usability test 8 partisipan.',
                    'files' => [
                        ['name' => 'UserFlow_Onboarding.pdf', 'type' => 'Dokumen PDF', 'size' => '6.5 MB', 'url' => '#'],
                        ['name' => 'Usability_Testing_Report.xlsx', 'type' => 'Laporan Pengujian', 'size' => '1.2 MB', 'url' => '#']
                    ]
                ],
            ],
            'reviews' => [
                [
                    'employer' => 'PT ABC',
                    'project' => 'Prototype Dashboard Internal',
                    'rating' => 5, // integer
                    'date' => 'Agu 2026',
                    'comment' => 'Hasil desain rapi, komunikatif, dan tepat waktu. Prototype mudah diuji tim internal.',
                ],
                [
                    'employer' => 'CV Kreasi Digital',
                    'project' => 'Redesign Aplikasi Lowongan',
                    'rating' => 5, // integer
                    'date' => 'Mei 2026',
                    'comment' => 'Sangat memahami kebutuhan pengguna awam. Iterasi cepat setelah umpan balik.',
                ],
                [
                    'employer' => 'Yayasan Kerja Adil',
                    'project' => 'Landing Page Program Pelatihan',
                    'rating' => 5,
                    'date' => 'Jan 2026',
                    'comment' => 'Visual konsisten dan aksesibel. Direkomendasikan untuk proyek pemerintahan.',
                ],
            ],
        ],
        'rian' => [
            'id' => 'rian',
            'name' => 'Rian Ardiansyah',
            'initials' => 'RA',
            'color' => '#0891b2',
            'photo' => 'https://api.dicebear.com/9.x/notionists/svg?seed=Rian&backgroundColor=cffafe',
            'title' => 'Fullstack / Backend API Developer',
            'location' => 'Bandung, Indonesia',
            'rating' => 5.0, // average: (5+5+5)/3 = 5.0
            'reviews_count' => 3,       // matches reviews array below
            'completed_projects' => 3,  // matches portfolio entries below
            'total_projects' => 3,      // matches portfolio entries below
            'verified' => true,
            'agreed' => false,
            'category' => 'backend',
            'applied_project' => 'Integrasi REST API Modul Notifikasi',
            'bid' => 'Rp 6.000.000',
            'eta' => '10 Hari Kerja',
            'proposal' => 'Siap mengintegrasikan webhook gateway dan memastikan load testing API mampu menangani 5000+ request per menit.',
            'skills' => [
                ['name' => 'PHP / Laravel', 'level' => 'Ahli'],
                ['name' => 'REST API', 'level' => 'Mahir'],
                ['name' => 'MySQL', 'level' => 'Mahir'],
                ['name' => 'Webhook', 'level' => 'Menengah'],
                ['name' => 'Node.js', 'level' => 'Menengah'],
            ],
            'contact' => [
                'wa' => '0813-8899-7711',
                'email' => 'rian.dev@email.com',
            ],
            'experience' => [
                [
                    'role' => 'Backend API Developer',
                    'project' => 'Integrasi Notifikasi SMS & WhatsApp (berjalan)',
                    'period' => 'Sep 2026 — sekarang',
                    'summary' => 'Membangun endpoint webhook dan uji beban ke production.',
                ],
                [
                    'role' => 'Backend Engineer',
                    'project' => 'Gateway Pembayaran UMKM',
                    'period' => 'Feb 2025 — Jul 2026',
                    'summary' => 'Integrasi 4 payment provider dan dashboard rekonsiliasi harian.',
                ],
                [
                    'role' => 'PHP Developer',
                    'project' => 'Sistem Antrian Tiket Layanan',
                    'period' => '2023 — 2025',
                    'summary' => 'API antrian real-time dan dokumentasi OpenAPI untuk mitra eksternal.',
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
                        ['name' => 'Load_Testing_k6_Report.pdf', 'type' => 'Laporan Stress Test', 'size' => '2.1 MB', 'url' => '#'],
                        ['name' => 'Source_Code_Webhook_Handler.zip', 'type' => 'Source Code', 'size' => '12.4 MB', 'url' => '#']
                    ]
                ],
                [
                    'id' => 'rian-port-2',
                    'title' => 'Payment Reconcile Service',
                    'type' => 'Backend Service',
                    'client' => 'Koperasi Digital Mandiri',
                    'year' => '2025',
                    'deliverable' => 'Service Laravel, skema MySQL, dan cron rekonsiliasi.',
                    'files' => [
                        ['name' => 'Schema_Database_Reconciliation.sql', 'type' => 'SQL Script', 'size' => '340 KB', 'url' => '#'],
                        ['name' => 'Dokumentasi_Cronjob.pdf', 'type' => 'Dokumen PDF', 'size' => '1.5 MB', 'url' => '#']
                    ]
                ],
                [
                    'id' => 'rian-port-3',
                    'title' => 'OpenAPI Ticket Queue',
                    'type' => 'API + Docs',
                    'client' => 'Dinas Layanan Publik',
                    'year' => '2024',
                    'deliverable' => 'Spesifikasi OpenAPI 3.0 dan contoh klien PHP.',
                    'files' => [
                        ['name' => 'openapi_v3_spec.yaml', 'type' => 'OpenAPI Spec', 'size' => '120 KB', 'url' => '#'],
                        ['name' => 'PHP_Client_SDK_Sample.php', 'type' => 'Code Sample', 'size' => '45 KB', 'url' => '#']
                    ]
                ],
            ],
            'reviews' => [
                [
                    'employer' => 'PT ABC',
                    'project' => 'Integrasi Gateway SMS',
                    'rating' => 5,
                    'date' => 'Jul 2026',
                    'comment' => 'Kode bersih, dokumentasi lengkap, dan responsif saat UAT.',
                ],
                [
                    'employer' => 'PT Data Prima',
                    'project' => 'API Pelaporan Kinerja',
                    'rating' => 5,
                    'date' => 'Mar 2026',
                    'comment' => 'Performa stabil di traffic tinggi. Komunikasi teknis jelas.',
                ],
                [
                    'employer' => 'CV Kode Nusantara',
                    'project' => 'Migrasi Monolith ke API',
                    'rating' => 5,
                    'date' => 'Nov 2025',
                    'comment' => 'Migrasi berjalan tanpa downtime. Timeline terpenuhi.',
                ],
            ],
        ],
        'siti' => [
            'id' => 'siti',
            'name' => 'Siti Nurhaliza',
            'initials' => 'SN',
            'color' => '#7c3aed',
            'photo' => 'https://api.dicebear.com/9.x/notionists/svg?seed=Siti&backgroundColor=ede9fe',
            'title' => 'Social Media Specialist',
            'location' => 'Surabaya, Indonesia',
            'rating' => 5.0, // average: (5+5)/2 = 5.0
            'reviews_count' => 2,       // matches reviews array below
            'completed_projects' => 2,  // matches portfolio entries below
            'total_projects' => 2,      // matches portfolio entries below
            'verified' => true,
            'agreed' => false,
            'category' => 'marketing',
            'applied_project' => 'Kampanye Media Sosial & Copywriting Peluncuran Fitur',
            'bid' => 'Rp 4.500.000',
            'eta' => '20 Hari Kerja',
            'proposal' => 'Menyediakan paket 20 konten carousel edukatif, naskah reels/TikTok, dan kalender konten terstruktur.',
            'skills' => [
                ['name' => 'Copywriting', 'level' => 'Ahli'],
                ['name' => 'Social Media', 'level' => 'Mahir'],
                ['name' => 'Content Plan', 'level' => 'Mahir'],
                ['name' => 'Canva', 'level' => 'Menengah'],
                ['name' => 'Instagram Ads', 'level' => 'Pemula'],
            ],
            'contact' => [
                'wa' => '0857-1122-3344',
                'email' => 'siti.marketing@email.com',
            ],
            'experience' => [
                [
                    'role' => 'Social Media Lead',
                    'project' => 'Kampanye Peluncuran Fitur Fintech',
                    'period' => '2025 — 2026',
                    'summary' => 'Mengelola 3 kanal dan menaikkan engagement organik 42% dalam 8 minggu.',
                ],
                [
                    'role' => 'Content Specialist',
                    'project' => 'Edukasi Jaminan Sosial Pekerja',
                    'period' => '2024 — 2025',
                    'summary' => 'Menulis serial konten edukatif dan skrip video pendek untuk audiens informal.',
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
                        ['name' => 'Asset_Carousel_Pack_20Pcs.zip', 'type' => 'Aset Gambar', 'size' => '35.0 MB', 'url' => '#']
                    ]
                ],
                [
                    'id' => 'siti-port-2',
                    'title' => 'Kampanye Awareness BPJS',
                    'type' => 'Copy & Visual',
                    'client' => 'Lembaga Sosial',
                    'year' => '2025',
                    'deliverable' => 'Paket copy + visual edukasi dan laporan jangkauan.',
                    'files' => [
                        ['name' => 'Copywriting_Content_Sheet.xlsx', 'type' => 'Lembar Kerja', 'size' => '940 KB', 'url' => '#'],
                        ['name' => 'Laporan_Analitik_Jangkauan.pdf', 'type' => 'Laporan', 'size' => '1.8 MB', 'url' => '#']
                    ]
                ],
            ],
            'reviews' => [
                [
                    'employer' => 'PT Media Cerah',
                    'project' => 'Peluncuran Fitur Mobile',
                    'rating' => 5,
                    'date' => 'Jun 2026',
                    'comment' => 'Copy tajam dan visual konsisten. Deadline selalu tertahankan.',
                ],
                [
                    'employer' => 'Kopi Lokal Co.',
                    'project' => 'Konten Instagram 3 Bulan',
                    'rating' => 5,
                    'date' => 'Feb 2026',
                    'comment' => 'Naik follower organik tanpa iklan berlebih. Sangat direkomendasikan.',
                ],
            ],
        ],
        'budi' => [
            'id' => 'budi',
            'name' => 'Budi Wicaksono',
            'initials' => 'BW',
            'color' => '#059669',
            'photo' => 'https://api.dicebear.com/9.x/notionists/svg?seed=Budi&backgroundColor=d1fae5',
            'title' => 'UI Designer',
            'location' => 'Yogyakarta, Indonesia',
            'rating' => 4.0, // average: (4+4)/2 = 4.0
            'reviews_count' => 2,       // matches reviews array below
            'completed_projects' => 2,  // matches portfolio entries below
            'total_projects' => 2,      // matches portfolio entries below
            'verified' => true,
            'agreed' => false,
            'category' => 'ui-ux',
            'applied_project' => 'Redesign UI/UX Dashboard Prototype KarirHub',
            'bid' => 'Rp 7.500.000',
            'eta' => '10 Hari Kerja',
            'proposal' => 'Siap deliver cepat dalam 10 hari lengkap dengan usability testing dan panduan style guide.',
            'skills' => [
                ['name' => 'UI Design', 'level' => 'Mahir'],
                ['name' => 'Wireframing', 'level' => 'Mahir'],
                ['name' => 'Figma', 'level' => 'Mahir'],
                ['name' => 'Style Guide', 'level' => 'Menengah'],
            ],
            'contact' => [
                'wa' => '0819-2233-4455',
                'email' => 'budi.design@email.com',
            ],
            'experience' => [
                [
                    'role' => 'UI Designer',
                    'project' => 'Redesign Aplikasi Absensi',
                    'period' => '2025 — 2026',
                    'summary' => 'Merapikan 18 layar mobile dan menyusun style guide komponen dasar.',
                ],
                [
                    'role' => 'Visual Designer',
                    'project' => 'Website Lembaga Pelatihan',
                    'period' => '2024',
                    'summary' => 'Desain landing dan dashboard peserta pelatihan daring.',
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
                        ['name' => 'IconSet_Custom_SVG.zip', 'type' => 'Aset Vektor', 'size' => '4.1 MB', 'url' => '#']
                    ]
                ],
                [
                    'id' => 'budi-port-2',
                    'title' => 'Website Lembaga Pelatihan',
                    'type' => 'Web UI',
                    'client' => 'LPK Mandiri',
                    'year' => '2024',
                    'deliverable' => 'Mockup 9 halaman dan aset ekspor produksi.',
                    'files' => [
                        ['name' => 'Mockup_Web_Landing_Dashboard.zip', 'type' => 'Aset Gambar', 'size' => '22.0 MB', 'url' => '#']
                    ]
                ],
            ],
            'reviews' => [
                [
                    'employer' => 'PT Karya Waktu',
                    'project' => 'Redesign Absensi',
                    'rating' => 4,
                    'date' => 'Apr 2026',
                    'comment' => 'Cepat dan rapi. Perlu sedikit arahan di awal, hasil akhir memuaskan.',
                ],
                [
                    'employer' => 'LPK Mandiri',
                    'project' => 'Website Lembaga',
                    'rating' => 4,
                    'date' => 'Okt 2024',
                    'comment' => 'Visual bersih. Revisi ditangani dengan baik.',
                ],
            ],
        ],
        'dimas' => [
            'id' => 'dimas',
            'name' => 'Dimas Prasetyo',
            'initials' => 'DP',
            'color' => '#ea580c',
            'photo' => 'https://api.dicebear.com/9.x/notionists/svg?seed=Dimas&backgroundColor=ffedd5',
            'title' => 'Cloud API Engineer',
            'location' => 'Depok, Indonesia',
            'rating' => 5.0, // average: (5+5)/2 = 5.0
            'reviews_count' => 2,       // matches reviews array below
            'completed_projects' => 2,  // matches portfolio entries below
            'total_projects' => 2,      // matches portfolio entries below
            'verified' => true,
            'agreed' => false,
            'category' => 'backend',
            'applied_project' => 'Integrasi REST API Modul Notifikasi',
            'bid' => 'Rp 6.500.000',
            'eta' => '7 Hari Kerja',
            'proposal' => 'Spesialis integrasi cloud API dan microservices. Telah menyelesaikan puluhan integrasi gateway serupa.',
            'skills' => [
                ['name' => 'Node.js', 'level' => 'Ahli'],
                ['name' => 'REST API', 'level' => 'Mahir'],
                ['name' => 'Microservices', 'level' => 'Menengah'],
                ['name' => 'AWS', 'level' => 'Menengah'],
                ['name' => 'Redis', 'level' => 'Menengah'],
            ],
            'contact' => [
                'wa' => '0821-9988-7766',
                'email' => 'dimas.code@email.com',
            ],
            'experience' => [
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
                        ['name' => 'Ops_Runbook_Deployment.pdf', 'type' => 'Dokumen PDF', 'size' => '1.4 MB', 'url' => '#']
                    ]
                ],
                [
                    'id' => 'dimas-port-2',
                    'title' => 'Event Queue 2M/day',
                    'type' => 'Infrastructure',
                    'client' => 'Startup Logistik',
                    'year' => '2024',
                    'deliverable' => 'Arsitektur Redis + worker dan dashboard metrik.',
                    'files' => [
                        ['name' => 'Redis_Worker_Architecture.pdf', 'type' => 'Dokumen PDF', 'size' => '3.1 MB', 'url' => '#']
                    ]
                ],
            ],
            'reviews' => [
                [
                    'employer' => 'PT Cloud Nusantara',
                    'project' => 'Adapter Notifikasi',
                    'rating' => 5,
                    'date' => 'Agu 2026',
                    'comment' => 'Sangat andal. Dokumentasi operasi memudahkan tim internal.',
                ],
                [
                    'employer' => 'PT Logis Cepat',
                    'project' => 'Event Pipeline',
                    'rating' => 5,
                    'date' => 'Des 2025',
                    'comment' => 'Performa sesuai janji. Komunikasi teknis ringkas dan jelas.',
                ],
            ],
        ],
        'mega' => [
            'id' => 'mega',
            'name' => 'Mega Lestari',
            'initials' => 'ML',
            'color' => '#db2777',
            'photo' => 'https://api.dicebear.com/9.x/notionists/svg?seed=Mega&backgroundColor=fce7f3',
            'title' => 'Digital Campaign Strategist',
            'location' => 'Semarang, Indonesia',
            'rating' => 5.0, // average: (5+5)/2 = 5.0
            'reviews_count' => 2,       // matches reviews array below
            'completed_projects' => 2,  // matches portfolio entries below
            'total_projects' => 2,      // matches portfolio entries below
            'verified' => true,
            'agreed' => false,
            'category' => 'marketing',
            'applied_project' => 'Kampanye Media Sosial & Copywriting Peluncuran Fitur',
            'bid' => 'Rp 5.000.000',
            'eta' => '14 Hari Kerja',
            'proposal' => 'Portofolio mencakup campaign viral BUMN dan startup teknologi. Siap mulai riset audience segera.',
            'skills' => [
                ['name' => 'Digital Campaign', 'level' => 'Mahir'],
                ['name' => 'SEO Writing', 'level' => 'Menengah'],
                ['name' => 'Brand Story', 'level' => 'Mahir'],
                ['name' => 'Analytics', 'level' => 'Menengah'],
            ],
            'contact' => [
                'wa' => '0878-3344-5566',
                'email' => 'mega.content@email.com',
            ],
            'experience' => [
                [
                    'role' => 'Campaign Strategist',
                    'project' => 'Peluncuran Program Magang Nasional',
                    'period' => '2025 — 2026',
                    'summary' => 'Menyusun pesan kampanye lintas kanal dan laporan insight mingguan.',
                ],
                [
                    'role' => 'SEO Content Lead',
                    'project' => 'Portal Karir Daerah',
                    'period' => '2023 — 2025',
                    'summary' => 'Menaikkan trafik organik 3x melalui seri artikel edukasi kerja lepas.',
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
                        ['name' => 'Visual_Assets_Kit_15Pcs.zip', 'type' => 'Aset Gambar', 'size' => '40.2 MB', 'url' => '#']
                    ]
                ],
                [
                    'id' => 'mega-port-2',
                    'title' => 'Seri Artikel SEO Karir',
                    'type' => 'Content SEO',
                    'client' => 'Portal Karir Daerah',
                    'year' => '2025',
                    'deliverable' => '24 artikel long-form dan peta kata kunci.',
                    'files' => [
                        ['name' => '24_Artikel_SEO_Karir.zip', 'type' => 'Dokumen Teks', 'size' => '5.4 MB', 'url' => '#'],
                        ['name' => 'Keyword_Mapping_Matrix.xlsx', 'type' => 'Lembar Kerja', 'size' => '1.1 MB', 'url' => '#']
                    ]
                ],
            ],
            'reviews' => [
                [
                    'employer' => 'BUMN Karya Digital',
                    'project' => 'Kampanye Program Magang',
                    'rating' => 5,
                    'date' => 'Jul 2026',
                    'comment' => 'Narasi kuat dan terukur. Tim internal mudah mengeksekusi asetnya.',
                ],
                [
                    'employer' => 'Portal Karir Jateng',
                    'project' => 'Konten SEO 6 Bulan',
                    'rating' => 5,
                    'date' => 'Mar 2025',
                    'comment' => 'Trafik naik signifikan. Penulisan tetap ramah pembaca awam.',
                ],
            ],
        ],
    ];

    // ── Merge reviews from DB (primary source) ───────────────────────────────
    // db.php is conditionally included here so worker-profiles.php can work
    // standalone without requiring a DB connection in every consumer.
    if (function_exists('gig_db')) {
        $pdo = gig_db();
        if ($pdo !== null) {
            try {
                // Fetch all reviews stored by any employer for any worker
                $stmt = $pdo->query(
                    "SELECT `worker_id`, `employer_username`, `project_title`,
                            `overall_rating`, `comment`, `badges`, `created_at`
                     FROM `project_reviews`
                     ORDER BY `created_at` DESC"
                );
                $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Group by worker_id and track contracts already merged to avoid
                // double-inserting if both DB and session have the same review.
                $mergedContracts = [];

                foreach ($dbRows as $row) {
                    $wId = function_exists('gig_profile_key_for_worker_id')
                        ? gig_profile_key_for_worker_id((string)$row['worker_id'])
                        : (string)$row['worker_id'];
                    if (!isset($profiles[$wId])) continue;

                    $projectTitle = (string)$row['project_title'];
                    $alreadyIn = false;
                    foreach ($profiles[$wId]['reviews'] as $existing) {
                        if (strcasecmp((string)($existing['project'] ?? ''), $projectTitle) === 0
                            && strcasecmp((string)($existing['employer'] ?? ''), (string)$row['employer_username']) === 0
                        ) {
                            $alreadyIn = true;
                            break;
                        }
                    }
                    if ($alreadyIn) {
                        continue;
                    }

                    $contractKey = $row['employer_username'] . '||' . $projectTitle;
                    if (isset($mergedContracts[$wId][$contractKey])) continue;
                    $mergedContracts[$wId][$contractKey] = true;

                    $badgesArr = ($row['badges'] !== '')
                        ? explode('||', $row['badges'])
                        : [];

                    $reviewDate = date('M Y', strtotime($row['created_at']));

                    array_unshift($profiles[$wId]['reviews'], [
                        'employer' => $row['employer_username'],
                        'project'  => $projectTitle,
                        'rating'   => (int)$row['overall_rating'],
                        'date'     => $reviewDate,
                        'comment'  => $row['comment'],
                        'badges'   => $badgesArr,
                    ]);
                }

                foreach (array_keys($profiles) as $wId) {
                    $allRatings = array_column($profiles[$wId]['reviews'], 'rating');
                    $cnt = count($allRatings);
                    $profiles[$wId]['reviews_count'] = $cnt;
                    $profiles[$wId]['rating'] = $cnt > 0
                        ? round(array_sum($allRatings) / $cnt, 1)
                        : (float)($profiles[$wId]['rating'] ?? 5.0);
                }
            } catch (Throwable $e) {
                // DB error – fall through to session fallback
            }
        }
    }

    // ── Session fallback (used when DB is offline) ───────────────────────────
    if (session_status() === PHP_SESSION_ACTIVE
        && isset($_SESSION['custom_reviews'])
        && is_array($_SESSION['custom_reviews'])
    ) {
        foreach ($_SESSION['custom_reviews'] as $wId => $revList) {
            if (!isset($profiles[$wId]) || !is_array($revList)) continue;
            foreach ($revList as $r) {
                // Avoid duplicating if already merged from DB
                $contractKey = ($r['employer'] ?? '') . '||' . ($r['project'] ?? '');
                // Simple check: see if any existing review matches
                $alreadyIn = false;
                foreach ($profiles[$wId]['reviews'] as $existing) {
                    if (($existing['project'] ?? '') === ($r['project'] ?? '')
                        && ($existing['employer'] ?? '') === ($r['employer'] ?? '')
                    ) {
                        $alreadyIn = true;
                        break;
                    }
                }
                if ($alreadyIn) continue;

                array_unshift($profiles[$wId]['reviews'], $r);
            }
            $allRatings = array_column($profiles[$wId]['reviews'], 'rating');
            $cnt = count($allRatings);
            $profiles[$wId]['reviews_count'] = $cnt;
            $profiles[$wId]['rating'] = $cnt > 0
                ? round(array_sum($allRatings) / $cnt, 1)
                : 5.0;
        }
    }

    // ── Final normalisation ───────────────────────────────────────────────────
    foreach (array_keys($profiles) as $wId) {
        if (!empty($profiles[$wId]['experience']) && is_array($profiles[$wId]['experience'])) {
            gig_sort_experience_timeline($profiles[$wId]['experience']);
        }

        $profiles[$wId]['reviews_count'] = count($profiles[$wId]['reviews'] ?? []);

        $stats = gig_worker_project_stats($wId);
        $profiles[$wId]['completed_projects'] = $stats['completed_projects'];
        $profiles[$wId]['total_projects'] = $stats['total_projects'];
    }

    return $profiles;
}

function gig_find_worker(string $id): ?array
{
    $profiles = gig_worker_profiles();
    $cleanId = strtolower(trim($id));

    if ($cleanId === 'theressaz@pasker.id' || $cleanId === 'theressaz' || str_contains($cleanId, 'theressa') || $cleanId === 'tessa') {
        $cleanId = 'tessa';
    }

    if (isset($profiles[$cleanId])) {
        // If custom session profile exists, merge it
        if (!empty($_SESSION['worker_custom_profile']) && is_array($_SESSION['worker_custom_profile'])) {
            $custom = $_SESSION['worker_custom_profile'];
            if (!empty($custom['name'])) $profiles[$cleanId]['name'] = $custom['name'];
            if (!empty($custom['title'])) $profiles[$cleanId]['title'] = $custom['title'];
            if (!empty($custom['location'])) $profiles[$cleanId]['location'] = $custom['location'];
            if (!empty($custom['skills'])) $profiles[$cleanId]['skills'] = gig_normalize_skills($custom['skills']);
            if (!empty($custom['proposal'])) $profiles[$cleanId]['proposal'] = $custom['proposal'];
            if (!empty($custom['video_url'])) $profiles[$cleanId]['video_url'] = $custom['video_url'];
            if (!empty($custom['contact'])) {
                if (!empty($custom['contact']['email'])) $profiles[$cleanId]['contact']['email'] = $custom['contact']['email'];
                if (!empty($custom['contact']['wa'])) $profiles[$cleanId]['contact']['wa'] = $custom['contact']['wa'];
            }
        }

        // If registration data exists for this user, merge registered data
        $reg = gig_get_worker_registration($cleanId);
        if ($reg !== null && $cleanId !== 'tessa') {
            if (!empty($reg['display_name'])) {
                $profiles[$cleanId]['name'] = $reg['display_name'];
            }
            if (!empty($reg['bidang_keahlian'])) {
                $profiles[$cleanId]['title'] = $reg['bidang_keahlian'];
            }
            if (!empty($reg['domicile'])) {
                $profiles[$cleanId]['location'] = $reg['domicile'];
            }
            if (!empty($reg['skills'])) {
                $profiles[$cleanId]['skills'] = gig_normalize_skills($reg['skills']);
            }
            if (!empty($reg['profile_summary'])) {
                $profiles[$cleanId]['proposal'] = $reg['profile_summary'];
            }
            if (!empty($reg['contact_email']) || !empty($reg['contact_wa'])) {
                $profiles[$cleanId]['contact']['email'] = !empty($reg['contact_email']) ? $reg['contact_email'] : $profiles[$cleanId]['contact']['email'];
                $profiles[$cleanId]['contact']['wa'] = !empty($reg['contact_wa']) ? $reg['contact_wa'] : $profiles[$cleanId]['contact']['wa'];
            }
            if (!empty($reg['portfolio']) && is_array($reg['portfolio'])) {
                $profiles[$cleanId]['portfolio'] = array_merge($reg['portfolio'], $profiles[$cleanId]['portfolio'] ?? []);
            }
            if (!empty($reg['previous_projects']) && is_array($reg['previous_projects'])) {
                $profiles[$cleanId]['experience'] = array_merge($reg['previous_projects'], $profiles[$cleanId]['experience'] ?? []);
            }
            if (!empty($reg['video_url'])) {
                $profiles[$cleanId]['video_url'] = $reg['video_url'];
            }
            if (!empty($reg['social_media']) && is_array($reg['social_media'])) {
                $profiles[$cleanId]['social_media'] = $reg['social_media'];
            }
        }
        if (!empty($profiles[$cleanId]['experience']) && is_array($profiles[$cleanId]['experience'])) {
            gig_sort_experience_timeline($profiles[$cleanId]['experience']);
        }
        if ($cleanId !== 'tessa') {
            $stats = gig_worker_project_stats($cleanId);
            $profiles[$cleanId]['completed_projects'] = $stats['completed_projects'];
            $profiles[$cleanId]['total_projects'] = $stats['total_projects'];
        }
        if (!function_exists('gig_avatar_url_for_worker_id')) {
            require_once __DIR__ . '/user-avatars.php';
        }
        $savedAvatar = gig_avatar_url_for_worker_id($cleanId);
        if ($savedAvatar !== null) {
            $profiles[$cleanId]['photo'] = $savedAvatar;
        } elseif (
            session_status() === PHP_SESSION_ACTIVE
            && !empty($_SESSION['siapkerja_email'])
            && gig_avatar_worker_profile_id(gig_avatar_account_key()) === $cleanId
        ) {
            $profiles[$cleanId]['photo'] = gig_worker_display_photo($cleanId);
        }
        return $profiles[$cleanId];
    }

    // Check if there is a registration for a user not in preset array
    $reg = gig_get_worker_registration($id);
    if ($reg !== null || gig_is_worker_registered($id)) {
        $siapkerja = gig_get_siapkerja_profile($id);
        $reg = $reg ?? [];
        $exp = $reg['previous_projects'] ?? $siapkerja['pengalaman_siapkerja'];
        if (is_array($exp)) {
            gig_sort_experience_timeline($exp);
        }
        $stats = gig_worker_project_stats($cleanId);
        return [
            'id' => $cleanId,
            'name' => $reg['display_name'] ?? ($siapkerja['nama'] ?? ucwords($id)),
            'initials' => strtoupper(substr($id, 0, 2)),
            'color' => '#2563eb',
            'photo' => 'https://api.dicebear.com/9.x/notionists/svg?seed=' . urlencode($id) . '&backgroundColor=dbeafe',
            'title' => $reg['bidang_keahlian'] ?? 'Gig Worker Professional',
            'location' => $reg['domicile'] ?? ($siapkerja['lokasi'] ?? 'Jakarta, Indonesia'),
            'rating' => 5.0,
            'reviews_count' => 5,
            'completed_projects' => $stats['completed_projects'],
            'total_projects' => $stats['total_projects'],
            'verified' => true,
            'agreed' => true,
            'category' => 'general',
            'skills' => gig_normalize_skills($reg['skills'] ?? ['Figma', 'Web Development']),
            'contact' => [
                'wa' => $reg['contact_wa'] ?? $siapkerja['wa'],
                'email' => $reg['contact_email'] ?? $siapkerja['email'],
            ],
            'experience' => $exp,
            'portfolio' => $reg['portfolio'] ?? [],
            'reviews' => [],
            'video_url' => $reg['video_url'] ?? '',
            'social_media' => $reg['social_media'] ?? [],
            'proposal' => $reg['profile_summary'] ?? '',
        ];
    }

    return null;
}

function gig_stars(int|float $rating): string
{
    $intRating = (int) round((float)$rating);
    if ($intRating < 1) $intRating = 1;
    if ($intRating > 5) $intRating = 5;

    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= ($i <= $intRating) ? '★' : '☆';
    }
    return $html;
}

/**
 * Returns pre-filled profile information from the user's SIAPKerja account.
 */
function gig_get_siapkerja_profile(string $username): array
{
    $lower = strtolower(trim($username));
    if ($lower === 'pencaker' || $lower === 'pencaker@pasker.id') {
        $fullName = 'Pencaker';
        $userEmail = 'pencaker@pasker.id';
    } elseif ($lower === 'pemberi kerja' || $lower === 'calon.employer@pasker.id' || str_contains($lower, 'calon.employer')) {
        $fullName = 'Pemberi Kerja';
        $userEmail = 'calon.employer@pasker.id';
    } elseif ($lower === 'tessa' || $lower === 'theressaz@pasker.id' || str_contains($lower, 'theressa')) {
        $fullName = 'Theressa Zaratrusha';
        $userEmail = 'theressaz@pasker.id';
    } else {
        $fullName = ucwords(trim($username));
        $userEmail = strtolower(str_replace(' ', '.', $fullName)) . '@siapkerja.kemnaker.go.id';
    }

    $skExp = [
        [
            'role'        => 'UI/UX Designer & Digital Strategist',
            'institution' => 'PT Teknologi Digital Indonesia (SIAPKerja Verified)',
            'period'      => 'Jan 2024 — Agu 2026',
            'summary'     => 'Merancang desain antarmuka dashboard B2B SaaS dan memimpin pengujian ketergunaan bagi 500+ pengguna aktif.'
        ],
        [
            'role'        => 'Frontend & Product Specialist',
            'institution' => 'Proyek Digital KarirHub Kemnaker RI',
            'period'      => 'Mei 2025 — Des 2025',
            'summary'     => 'Mengembangkan komponen antarmuka web responsif dan standar aksesibilitas bagi calon tenaga kerja Indonesia.'
        ]
    ];
    gig_sort_experience_timeline($skExp);

    return [
        'username'        => $username,
        'nama'            => $fullName,
        'siapkerja_id'    => 'SK-2026-' . strtoupper(substr(md5($username), 0, 6)),
        'nik'             => '317409' . sprintf('%010d', abs(crc32($username) % 10000000000)),
        'email'           => $userEmail,
        'wa'              => '0812-3456-7890',
        'lokasi'          => 'Jakarta Selatan, DKI Jakarta',
        'status_akun'     => 'Terverifikasi (KYC Kemnaker RI)',
        'pengalaman_siapkerja' => $skExp
    ];
}

/**
 * Checks whether the logged in user has completed Gig Worker registration.
 */
function gig_is_worker_registered(string $username): bool
{
    $clean = strtolower(trim($username));
    if (in_array($clean, ['tessa', 'theressaz@pasker.id', 'theressaz', 'theressa zaratrusha'], true) || str_contains($clean, 'theressa')) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['gig_worker_registered_' . $username] = true;
        }
        return true;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $email = strtolower(trim((string)($_SESSION['siapkerja_email'] ?? '')));
        if (in_array($email, ['tessa', 'theressaz@pasker.id', 'theressaz', 'theressa zaratrusha'], true) || str_contains($email, 'theressa')) {
            $_SESSION['gig_worker_registered_' . $username] = true;
            return true;
        }
        if (($_SESSION['role'] ?? '') === 'worker') {
            $_SESSION['gig_worker_registered_' . $username] = true;
            return true;
        }
        if (!empty($_SESSION['gig_worker_registered_' . $username])) {
            return true;
        }
    }

    $db = gig_db();
    if ($db !== null) {
        try {
            $stmt = $db->prepare("SELECT `status` FROM `gig_worker_registrations` WHERE `username` = :u OR `contact_email` = :e LIMIT 1");
            $stmt->execute([':u' => $username, ':e' => $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && (string)($row['status'] ?? 'approved') === 'approved') {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION['gig_worker_registered_' . $username] = true;
                }
                return true;
            }
        } catch (Throwable $e) {
            // fallback check session
        }
    }

    return false;
}

/**
 * Gets registration details for a Gig Worker.
 */
function gig_get_worker_registration(string $username): ?array
{
    $db = gig_db();
    if ($db !== null) {
        try {
            $stmt = $db->prepare("SELECT * FROM `gig_worker_registrations` WHERE `username` = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['previous_projects'] = json_decode((string)$row['previous_projects'], true) ?: [];
                $row['portfolio'] = json_decode((string)$row['portfolio'], true) ?: [];
                $row['social_media'] = json_decode((string)($row['social_media'] ?? ''), true) ?: [];
                $rawSkills = $row['skills'] ?? '';
                $decodedSkills = json_decode((string)$rawSkills, true);
                $row['skills'] = is_array($decodedSkills) ? gig_normalize_skills($decodedSkills) : gig_normalize_skills($rawSkills);
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION['gig_worker_registration_data_' . $username] = $row;
                    if ((string)($row['status'] ?? 'approved') === 'approved') {
                        $_SESSION['gig_worker_registered_' . $username] = true;
                    }
                }
                return $row;
            }
        } catch (Throwable $e) {
            // fallback
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['gig_worker_registration_data_' . $username])) {
        $row = $_SESSION['gig_worker_registration_data_' . $username];
        if (!is_array($row)) {
            return null;
        }
        if (!is_array($row['social_media'] ?? null)) {
            $row['social_media'] = json_decode((string)($row['social_media'] ?? ''), true) ?: [];
        }
        if (!empty($row['skills'])) {
            $row['skills'] = gig_normalize_skills($row['skills']);
        }
        return $row;
    }

    return null;
}

/**
 * Saves a new Gig Worker registration.
 */
function gig_save_worker_registration(string $username, array $data): bool
{
    require_once __DIR__ . '/admin-store.php';
    gig_admin_ensure_schema();

    $db = gig_db();
    if ($db === null) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $data['skills'] = gig_normalize_skills($data['skills'] ?? []);
            $_SESSION['gig_worker_registration_data_' . $username] = $data;
            if ((string)($data['status'] ?? 'pending') === 'approved') {
                $_SESSION['gig_worker_registered_' . $username] = true;
            }
            return true;
        }
        return false;
    }

    try {
        $status = (string)($data['status'] ?? 'pending');
        $preserveNote = '';
        if ($status === 'approved') {
            $existing = gig_get_worker_registration($username);
            $preserveNote = (string)($existing['admin_note'] ?? '');
        }

        $stmt = $db->prepare("
            INSERT INTO `gig_worker_registrations`
            (`username`, `bidang_keahlian`, `skills`, `contact_choice`, `contact_email`, `contact_wa`,
             `previous_projects`, `portfolio`, `video_url`, `social_media`, `display_name`, `domicile`, `profile_summary`, `status`, `admin_note`)
            VALUES (:u, :bidang, :skills, :choice, :email, :wa, :projects, :portfolio, :video, :social_media, :display_name, :domicile, :profile_summary, :status, :note)
            ON DUPLICATE KEY UPDATE
              `bidang_keahlian` = VALUES(`bidang_keahlian`),
              `skills` = VALUES(`skills`),
              `contact_choice` = VALUES(`contact_choice`),
              `contact_email` = VALUES(`contact_email`),
              `contact_wa` = VALUES(`contact_wa`),
              `previous_projects` = VALUES(`previous_projects`),
              `portfolio` = VALUES(`portfolio`),
              `video_url` = VALUES(`video_url`),
              `social_media` = VALUES(`social_media`),
              `display_name` = VALUES(`display_name`),
              `domicile` = VALUES(`domicile`),
              `profile_summary` = VALUES(`profile_summary`),
              `status` = VALUES(`status`),
              `admin_note` = IF(VALUES(`status`) = 'pending', '', `admin_note`)
        ");
        $stmt->execute([
            ':u'        => $username,
            ':bidang'   => $data['bidang_keahlian'] ?? '',
            ':skills'   => json_encode(gig_normalize_skills($data['skills'] ?? [])),
            ':choice'   => $data['contact_choice'] ?? 'siapkerja',
            ':email'    => $data['contact_email'] ?? '',
            ':wa'       => $data['contact_wa'] ?? '',
            ':projects' => json_encode($data['previous_projects'] ?? []),
            ':portfolio'=> json_encode($data['portfolio'] ?? []),
            ':video'    => $data['video_url'] ?? '',
            ':social_media' => json_encode($data['social_media'] ?? []),
            ':display_name' => $data['display_name'] ?? '',
            ':domicile' => $data['domicile'] ?? '',
            ':profile_summary' => $data['profile_summary'] ?? '',
            ':status'   => $status,
            ':note'     => $status === 'approved' ? $preserveNote : '',
        ]);
    } catch (Throwable $e) {
        return false;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['gig_worker_registration_data_' . $username], $_SESSION['gig_worker_registered_' . $username]);
    }
    return true;
}

/**
 * Compiles an exhaustive search payload for a worker profile covering EVERYTHING:
 * Name, title, category, location, bio/proposal, all skills & levels, all experiences,
 * output deliverables, all portfolios, clients, deliverable files, all employer reviews,
 * comments, badges, social media links, video profile demos, contact details, and SIAPKerja background.
 *
 * @param array $worker
 * @return array{full_text: string, facets: array<string, mixed>}
 */
function gig_worker_search_metadata(array $worker): array
{
    $wId = strtolower(trim((string)($worker['id'] ?? '')));
    $reg = function_exists('gig_get_worker_registration') ? (gig_get_worker_registration($wId) ?? []) : [];
    $siap = function_exists('gig_get_siapkerja_profile') ? gig_get_siapkerja_profile($wId) : [];

    // Category mapping
    $cat = strtolower((string)($worker['category'] ?? ''));
    $catLabels = [
        'ui-ux' => 'UI/UX & Desain Grafis Frontend Prototype Wireframe',
        'backend' => 'Backend & API Cloud Database Developer Microservices REST Server',
        'marketing' => 'Digital Marketing Pemasaran Konten Copywriting Media Sosial Campaign',
    ];
    $catLabel = $catLabels[$cat] ?? 'Freelancer';

    // Skills
    $rawSkills = !empty($worker['skills']) ? $worker['skills'] : ($reg['skills'] ?? []);
    $skillsList = function_exists('gig_normalize_skills') ? gig_normalize_skills($rawSkills) : [];
    $skillFacets = [];
    foreach ($skillsList as $s) {
        $nm = gig_skill_name($s);
        $lvl = gig_skill_level($s);
        $skillFacets[] = [
            'name' => $nm,
            'level' => $lvl,
            'label' => $nm . ' (' . $lvl . ')'
        ];
    }

    // Experiences
    $experiences = !empty($worker['experience']) ? $worker['experience'] : ($reg['previous_projects'] ?? ($siap['pengalaman_siapkerja'] ?? []));
    $expFacets = [];
    if (is_array($experiences)) {
        foreach ($experiences as $e) {
            $eFiles = [];
            if (!empty($e['files']) && is_array($e['files'])) {
                foreach ($e['files'] as $f) {
                    $eFiles[] = trim(($f['name'] ?? '') . ' ' . ($f['type'] ?? ''));
                }
            }
            $expFacets[] = [
                'role' => (string)($e['role'] ?? ''),
                'project' => (string)($e['project'] ?? ($e['institution'] ?? '')),
                'period' => (string)($e['period'] ?? ''),
                'summary' => (string)($e['summary'] ?? ''),
                'output_title' => (string)($e['output_title'] ?? ''),
                'files' => implode(', ', $eFiles)
            ];
        }
    }

    // Portfolios
    $portfolios = !empty($worker['portfolio']) ? $worker['portfolio'] : ($reg['portfolio'] ?? []);
    $portFacets = [];
    if (is_array($portfolios)) {
        foreach ($portfolios as $p) {
            $pFiles = [];
            if (!empty($p['files']) && is_array($p['files'])) {
                foreach ($p['files'] as $f) {
                    $pFiles[] = trim(($f['name'] ?? '') . ' ' . ($f['type'] ?? '') . ' ' . ($f['size'] ?? ''));
                }
            }
            $portFacets[] = [
                'title' => (string)($p['title'] ?? ''),
                'type' => (string)($p['type'] ?? ''),
                'client' => (string)($p['client'] ?? ''),
                'year' => (string)($p['year'] ?? ''),
                'deliverable' => (string)($p['deliverable'] ?? ''),
                'files' => implode(', ', $pFiles)
            ];
        }
    }

    // Reviews
    $reviews = !empty($worker['reviews']) ? $worker['reviews'] : [];
    $revFacets = [];
    if (is_array($reviews)) {
        foreach ($reviews as $r) {
            $revFacets[] = [
                'employer' => (string)($r['employer'] ?? ''),
                'project' => (string)($r['project'] ?? ''),
                'comment' => (string)($r['comment'] ?? ''),
                'date' => (string)($r['date'] ?? ''),
                'badges' => is_array($r['badges'] ?? null) ? implode(', ', $r['badges']) : ''
            ];
        }
    }

    // Social Media
    $social = !empty($worker['social_media']) ? $worker['social_media'] : ($reg['social_media'] ?? []);
    $socList = [];
    if (is_array($social)) {
        foreach ($social as $sm) {
            $p = (string)($sm['platform'] ?? '');
            $u = (string)($sm['url'] ?? '');
            if ($p !== '' || $u !== '') {
                $socList[] = trim($p . ' ' . $u);
            }
        }
    }

    // Contact
    $contactWa = (string)($worker['contact']['wa'] ?? $reg['contact_wa'] ?? '');
    $contactEmail = (string)($worker['contact']['email'] ?? $reg['contact_email'] ?? '');

    // Video
    $videoUrl = (string)($worker['video_url'] ?? $reg['video_url'] ?? '');

    // Bio & Proposal
    $bio = (string)($worker['proposal'] ?? $reg['profile_summary'] ?? '');
    $appliedProject = (string)($worker['applied_project'] ?? '');
    $bid = (string)($worker['bid'] ?? '');
    $eta = (string)($worker['eta'] ?? '');

    // SIAPKerja background (only if worker doesn't have custom experience)
    $siapStrings = [];
    if (empty($worker['experience']) && !empty($siap['pengalaman_siapkerja']) && is_array($siap['pengalaman_siapkerja'])) {
        foreach ($siap['pengalaman_siapkerja'] as $skExp) {
            $siapStrings[] = ($skExp['role'] ?? '') . ' ' . ($skExp['institution'] ?? '') . ' ' . ($skExp['summary'] ?? '');
        }
    }

    // Construct flat text corpus for fast matching
    $textParts = [
        $wId,
        (string)($worker['name'] ?? $reg['display_name'] ?? ($siap['nama'] ?? '')),
        (string)($worker['title'] ?? $reg['bidang_keahlian'] ?? ''),
        $cat,
        $catLabel,
        (string)($worker['location'] ?? $reg['domicile'] ?? ($siap['lokasi'] ?? '')),
        $bio,
        $appliedProject,
        $bid,
        $eta,
        $contactWa,
        $contactEmail,
        $videoUrl,
        implode(' ', $socList),
        implode(' ', $siapStrings),
    ];

    foreach ($skillFacets as $sf) {
        $textParts[] = $sf['name'] . ' ' . $sf['level'] . ' ' . $sf['label'];
    }
    foreach ($expFacets as $ef) {
        $textParts[] = $ef['role'] . ' ' . $ef['project'] . ' ' . $ef['period'] . ' ' . $ef['summary'] . ' ' . $ef['output_title'] . ' ' . $ef['files'];
    }
    foreach ($portFacets as $pf) {
        $textParts[] = $pf['title'] . ' ' . $pf['type'] . ' ' . $pf['client'] . ' ' . $pf['year'] . ' ' . $pf['deliverable'] . ' ' . $pf['files'];
    }
    foreach ($revFacets as $rf) {
        $textParts[] = $rf['employer'] . ' ' . $rf['project'] . ' ' . $rf['comment'] . ' ' . $rf['date'] . ' ' . $rf['badges'];
    }

    $corpus = strtolower(preg_replace('/\s+/', ' ', implode(' ', array_filter($textParts))));

    return [
        'full_text' => $corpus,
        'facets' => [
            'name' => (string)($worker['name'] ?? ''),
            'title' => (string)($worker['title'] ?? ''),
            'location' => (string)($worker['location'] ?? ''),
            'bio' => $bio,
            'skills' => $skillFacets,
            'experiences' => $expFacets,
            'portfolios' => $portFacets,
            'reviews' => $revFacets,
            'social' => $socList,
            'contact' => trim($contactWa . ' ' . $contactEmail),
        ]
    ];
}



