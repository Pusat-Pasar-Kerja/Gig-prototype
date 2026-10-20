<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/admin-store.php';

// Require user to be logged in with SIAPkerja account first
if (!isset($_SESSION['siapkerja_email']) && !isset($_SESSION['username'])) {
    header("Location: siapkerja-login.php?redirect=worker-register");
    exit;
}

$username = $_SESSION['username'] ?? $_SESSION['siapkerja_name'] ?? 'Theressa Zaratrusha';

$siapkerja = gig_get_siapkerja_profile($username);
$isRegistered = gig_is_worker_registered($username);
$isEditMode = !empty($_GET['edit']) || $isRegistered;

$existingReg = gig_get_worker_registration($username);
$workerProfile = gig_find_worker($username);

function gig_parse_period_to_dates(string $period): array {
    $monthsMap = [
        'jan' => 'Januari', 'januari' => 'Januari',
        'feb' => 'Februari', 'februari' => 'Februari',
        'mar' => 'Maret', 'maret' => 'Maret',
        'apr' => 'April', 'april' => 'April',
        'mei' => 'Mei',
        'jun' => 'Juni', 'juni' => 'Juni',
        'jul' => 'Juli', 'juli' => 'Juli',
        'agu' => 'Agustus', 'agt' => 'Agustus', 'agustus' => 'Agustus',
        'sep' => 'September', 'september' => 'September',
        'okt' => 'Oktober', 'oktober' => 'Oktober',
        'nov' => 'November', 'november' => 'November',
        'des' => 'Desember', 'desember' => 'Desember'
    ];

    $startMonth = '';
    $startYear = '';
    $endMonth = '';
    $endYear = '';

    $parts = preg_split('/\s*(?:—|–|-)\s*/u', trim($period));
    if (isset($parts[0])) {
        if (preg_match('/([a-z]+)\s*(\d{4})/i', trim($parts[0]), $m)) {
            $mKey = strtolower($m[1]);
            $startMonth = $monthsMap[$mKey] ?? ucfirst($m[1]);
            $startYear = $m[2];
        } elseif (preg_match('/(\d{4})/', trim($parts[0]), $m)) {
            $startYear = $m[1];
        }
    }
    if (isset($parts[1])) {
        if (str_contains(strtolower($parts[1]), 'masih') || str_contains(strtolower($parts[1]), 'sekarang')) {
            $endMonth = 'Masih Berjalan';
            $endYear = '';
        } elseif (preg_match('/([a-z]+)\s*(\d{4})/i', trim($parts[1]), $m)) {
            $mKey = strtolower($m[1]);
            $endMonth = $monthsMap[$mKey] ?? ucfirst($m[1]);
            $endYear = $m[2];
        } elseif (preg_match('/(\d{4})/', trim($parts[1]), $m)) {
            $endYear = $m[1];
        }
    }

    return [
        'start_month' => $startMonth,
        'start_year'  => $startYear,
        'end_month'   => $endMonth,
        'end_year'    => $endYear,
    ];
}

$currentBidang = $_POST['bidang_keahlian'] ?? ($existingReg['bidang_keahlian'] ?? ($workerProfile['title'] ?? ''));

$currentSkillsList = [];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (isset($_POST['skill_name']) && is_array($_POST['skill_name'])) {
        $postNames = $_POST['skill_name'];
        $postLevels = $_POST['skill_level'] ?? [];
        foreach ($postNames as $k => $nm) {
            $nm = trim((string)$nm);
            $lvl = trim((string)($postLevels[$k] ?? 'Menengah'));
            if ($nm !== '') {
                $currentSkillsList[] = ['name' => $nm, 'level' => $lvl];
            }
        }
    } elseif (isset($_POST['skills'])) {
        $currentSkillsList = gig_normalize_skills($_POST['skills']);
    }
} else {
    if (!empty($existingReg['skills'])) {
        $currentSkillsList = gig_normalize_skills($existingReg['skills']);
    } elseif (!empty($workerProfile['skills'])) {
        $currentSkillsList = gig_normalize_skills($workerProfile['skills']);
    }
}
if ($currentSkillsList === []) {
    $currentSkillsList = [['name' => '', 'level' => 'Menengah']];
}

$currentContactChoice = $_POST['contact_choice'] ?? ($existingReg['contact_choice'] ?? 'siapkerja');
$currentContactEmail = $_POST['contact_email_new'] ?? ($existingReg['contact_email'] ?? '');
$currentContactWa = $_POST['contact_wa_new'] ?? ($existingReg['contact_wa'] ?? '');

$rawVideoStr = $_POST['video_url'] ?? ($existingReg['video_url'] ?? ($workerProfile['video_url'] ?? ''));
if (isset($_POST['video_urls']) && is_array($_POST['video_urls'])) {
    $currentVideoUrls = array_values(array_filter(array_map('trim', $_POST['video_urls']), static fn($u) => $u !== ''));
} else {
    $currentVideoUrls = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', (string)$rawVideoStr)), static fn($u) => $u !== ''));
}
if ($currentVideoUrls === []) {
    $currentVideoUrls = [''];
}
$currentVideoUrl = implode("\n", $currentVideoUrls);

$currentSocialMedia = [];
if (!empty($_POST['social_media_platform']) && is_array($_POST['social_media_platform'])) {
    foreach ($_POST['social_media_platform'] as $sIdx => $plat) {
        $p = trim((string)$plat);
        $u = trim((string)($_POST['social_media_url'][$sIdx] ?? ''));
        if ($p !== '' || $u !== '') {
            $currentSocialMedia[] = ['platform' => $p, 'url' => $u];
        }
    }
} elseif (!empty($existingReg['social_media']) && is_array($existingReg['social_media'])) {
    $currentSocialMedia = $existingReg['social_media'];
} elseif (!empty($workerProfile['social_media']) && is_array($workerProfile['social_media'])) {
    $currentSocialMedia = $workerProfile['social_media'];
}

if ($currentSocialMedia === []) {
    $currentSocialMedia = [
        ['platform' => 'LinkedIn', 'url' => ''],
    ];
}

$currentPortfolio = !empty($existingReg['portfolio']) && is_array($existingReg['portfolio']) ? $existingReg['portfolio'] : ($workerProfile['portfolio'] ?? []);

$siapkerjaExperienceDefaults = [];
$skTitlesMap = [];
if (!empty($siapkerja['pengalaman_siapkerja']) && is_array($siapkerja['pengalaman_siapkerja'])) {
    foreach ($siapkerja['pengalaman_siapkerja'] as $skExp) {
        $parsedDates = gig_parse_period_to_dates((string)($skExp['period'] ?? ''));
        $roleStr = (string)($skExp['role'] ?? '');
        $instStr = (string)($skExp['institution'] ?? '');
        if ($roleStr !== '') $skTitlesMap[strtolower(trim($roleStr))] = true;
        if ($instStr !== '') $skTitlesMap[strtolower(trim($instStr))] = true;

        $siapkerjaExperienceDefaults[] = [
            'role'        => $roleStr,
            'project'     => $instStr,
            'company'     => $instStr,
            'period'      => (string)($skExp['period'] ?? ''),
            'start_month' => $parsedDates['start_month'],
            'start_year'  => $parsedDates['start_year'],
            'end_month'   => $parsedDates['end_month'],
            'end_year'    => $parsedDates['end_year'],
            'summary'     => (string)($skExp['summary'] ?? ''),
            'is_siapkerja'=> true,
        ];
    }
}

$rawProjects = !empty($existingReg['previous_projects']) && is_array($existingReg['previous_projects'])
    ? $existingReg['previous_projects']
    : (!empty($workerProfile['experience']) && is_array($workerProfile['experience']) ? $workerProfile['experience'] : $siapkerjaExperienceDefaults);

$currentProjects = [];
if (is_array($rawProjects)) {
    foreach ($rawProjects as $rp) {
        $role = (string)($rp['role'] ?? '');
        $comp = (string)($rp['company'] ?? ($rp['project'] ?? ''));
        $period = (string)($rp['period'] ?? '');
        $parsed = gig_parse_period_to_dates($period);

        $isSk = !empty($rp['is_siapkerja'])
            || isset($skTitlesMap[strtolower(trim($role))])
            || isset($skTitlesMap[strtolower(trim($comp))]);

        $currentProjects[] = [
            'role'        => $role,
            'project'     => $comp,
            'company'     => $comp,
            'period'      => $period,
            'start_month' => !empty($rp['start_month']) ? $rp['start_month'] : $parsed['start_month'],
            'start_year'  => !empty($rp['start_year']) ? $rp['start_year'] : $parsed['start_year'],
            'end_month'   => !empty($rp['end_month']) ? $rp['end_month'] : $parsed['end_month'],
            'end_year'    => !empty($rp['end_year']) ? $rp['end_year'] : $parsed['end_year'],
            'summary'     => $rp['summary'] ?? '',
            'output_title'=> $rp['output_title'] ?? '',
            'files'       => $rp['files'] ?? [],
            'is_siapkerja'=> $isSk,
        ];
    }
}
if (is_array($currentProjects)) {
    gig_sort_experience_timeline($currentProjects);
}

$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "register_gig_worker") {
    $bidangKeahlian = trim((string)($_POST["bidang_keahlian"] ?? ""));

    // Process structured skills
    $skillsList = [];
    if (isset($_POST['skill_name']) && is_array($_POST['skill_name'])) {
        $postNames = $_POST['skill_name'];
        $postLevels = $_POST['skill_level'] ?? [];
        foreach ($postNames as $k => $nm) {
            $nm = trim((string)$nm);
            $lvl = trim((string)($postLevels[$k] ?? 'Menengah'));
            if (!in_array($lvl, ['Pemula', 'Menengah', 'Mahir', 'Ahli'], true)) {
                $lvl = 'Menengah';
            }
            if ($nm !== '') {
                $skillsList[] = ['name' => $nm, 'level' => $lvl];
            }
        }
    } elseif (!empty($_POST['skills'])) {
        $skillsList = gig_normalize_skills($_POST['skills']);
    }

    $contactChoice = trim((string)($_POST["contact_choice"] ?? "siapkerja"));
    $contactEmail = $contactChoice === 'new' ? trim((string)($_POST["contact_email_new"] ?? "")) : $siapkerja['email'];
    $contactWa = $contactChoice === 'new' ? trim((string)($_POST["contact_wa_new"] ?? "")) : $siapkerja['wa'];
    if (isset($_POST['video_urls']) && is_array($_POST['video_urls'])) {
        $videoUrlsArr = array_values(array_filter(array_map('trim', $_POST['video_urls']), static fn($u) => $u !== ''));
        $videoUrl = implode("\n", $videoUrlsArr);
    } else {
        $videoUrl = trim((string)($_POST["video_url"] ?? ""));
    }

    $socialMedia = [];
    if (!empty($_POST['social_media_platform']) && is_array($_POST['social_media_platform'])) {
        foreach ($_POST['social_media_platform'] as $sIdx => $plat) {
            $p = trim((string)$plat);
            $u = trim((string)($_POST['social_media_url'][$sIdx] ?? ''));
            if ($p !== '' || $u !== '') {
                $socialMedia[] = [
                    'platform' => $p !== '' ? $p : 'LinkedIn',
                    'url' => $u,
                ];
            }
        }
    }

    // Process previous projects & integrated portfolios
    $projects = [];
    $portfolios = [];
    if (!empty($_POST["project_title"]) && is_array($_POST["project_title"])) {
        foreach ($_POST["project_title"] as $idx => $title) {
            $t = trim((string)$title);
            $comp = trim((string)($_POST["project_company"][$idx] ?? ''));
            $isSk = !empty($_POST["is_siapkerja"][$idx]);
            if ($t !== "" || $comp !== "") {
                $sMonth = trim((string)($_POST["start_month"][$idx] ?? ''));
                $sYear = trim((string)($_POST["start_year"][$idx] ?? ''));
                $eMonth = trim((string)($_POST["end_month"][$idx] ?? ''));
                $eYear = trim((string)($_POST["end_year"][$idx] ?? ''));

                $startStr = trim($sMonth . ' ' . $sYear);
                if ($eMonth === 'Masih Berjalan') {
                    $endStr = 'Masih Berjalan';
                } else {
                    $endStr = trim($eMonth . ' ' . $eYear);
                }

                $period = '';
                if ($startStr !== '' && $endStr !== '') {
                    $period = $startStr . ' - ' . $endStr;
                } elseif ($startStr !== '') {
                    $period = $startStr;
                } elseif ($endStr !== '') {
                    $period = $endStr;
                } else {
                    $period = date('Y');
                }

                $summary = trim((string)($_POST["project_summary"][$idx] ?? ""));

                $itemFiles = [];
                $urlRows = $_POST["portfolio_file_url"][$idx] ?? [];
                if (is_array($urlRows)) {
                    foreach ($urlRows as $fIdx => $rawUrl) {
                        $fu = trim((string)$rawUrl);
                        if ($fu === '' || $fu === '#') {
                            continue;
                        }
                        $itemFiles[] = [
                            'name' => 'Link Deliverable ' . ($fIdx + 1),
                            'type' => 'Dokumen/Link Output Proyek',
                            'size' => 'Akses Web / Link',
                            'url'  => $fu
                        ];
                    }
                }

                $portTitle = trim((string)($_POST["portfolio_title"][$idx] ?? ""));
                if ($portTitle === '' && $t !== '') {
                    $portTitle = 'Output Proyek: ' . $t;
                }

                $projects[] = [
                    'role'        => $t,
                    'project'     => $comp !== '' ? $comp : $t,
                    'company'     => $comp,
                    'period'      => $period,
                    'start_month' => $sMonth,
                    'start_year'  => $sYear,
                    'end_month'   => $eMonth,
                    'end_year'    => $eYear,
                    'summary'     => $summary,
                    'output_title'=> $portTitle,
                    'files'       => $itemFiles,
                    'is_siapkerja'=> $isSk,
                ];

                if (!empty($itemFiles)) {
                    $portfolios[] = [
                        'id'          => 'port-' . uniqid(),
                        'title'       => $portTitle !== '' ? $portTitle : ($t . ' Output'),
                        'type'        => 'Proyek Deliverable',
                        'deliverable' => $summary,
                        'url'         => $itemFiles[0]['url'] ?? '',
                        'client'      => $comp !== '' ? $comp : 'Klien Terverifikasi',
                        'year'        => $sYear !== '' ? $sYear : date('Y'),
                        'files'       => $itemFiles
                    ];
                }
            }
        }
    }

    if (!$isEditMode && empty($_POST['consent_truth'])) {
        $errorMessage = "Harap centang persetujuan kebenaran data sebelum mengirim pendaftaran.";
    } elseif ($bidangKeahlian === "") {
        $errorMessage = "Harap pilih Bidang Keahlian Anda.";
    } elseif ($skillsList === []) {
        $errorMessage = "Harap cantumkan minimal 1 Skill / Keahlian spesifik beserta tingkat keahliannya.";
    } else {
        $existingStatus = gig_worker_registration_status($username);
        $registrationData = [
            'username'          => $username,
            'bidang_keahlian'   => $bidangKeahlian,
            'skills'            => $skillsList,
            'contact_choice'    => $contactChoice,
            'contact_email'     => $contactEmail,
            'contact_wa'        => $contactWa,
            'previous_projects' => $projects,
            'portfolio'         => $portfolios,
            'video_url'         => $videoUrl,
            'social_media'      => $socialMedia,
            'registered_at'     => date('Y-m-d H:i:s'),
            'status'            => ($isEditMode && $existingStatus === 'approved') ? 'approved' : 'pending',
        ];

        if (!gig_save_worker_registration($username, $registrationData)) {
            $errorMessage = 'Gagal menyimpan pendaftaran ke database. Pastikan MySQL/XAMPP berjalan.';
        } else {
        $_SESSION['role'] = 'worker';
        $_SESSION['worker_registered'] = true;
        $profileId = strtolower(preg_replace('/[^a-z0-9]+/i', '', explode(' ', $username)[0] ?? $username));
        header("Location: worker-verification-pending.php");
        exit;
        }
    }
}

$pageTitle = $isEditMode ? 'Edit Profil Gig Worker' : 'Pendaftaran Gig Worker';
$backHref = $isEditMode
    ? 'worker-profile.php?id=' . urlencode(strtolower(preg_replace('/[^a-z0-9]+/i', '', explode(' ', $username)[0] ?? $username)))
    : 'pilih-pendaftaran.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FORM PROFIL GIG WORKER · Karirhub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/employer.css" />
    <link rel="stylesheet" href="assets/worker.css" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header-nav {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-link-left {
            color: #475569;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .nav-link-left:hover {
            color: #0f172a;
        }

        .nav-center-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .nav-center-logo .logo-text {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .nav-center-logo .logo-subtext {
            font-size: 10px;
            color: #64748b;
            display: block;
            line-height: 1;
        }

        .main-container {
            flex: 1;
            max-width: 860px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
        }

        .reg-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            padding: 40px 44px;
        }

        .form-header-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.2px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .form-header-subtitle {
            font-size: 13.5px;
            color: #64748b;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-section-header {
            margin-top: 32px;
            margin-bottom: 18px;
        }

        .form-section-title {
            font-size: 14.5px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin-bottom: 4px;
        }

        .form-section-subtitle {
            font-size: 13px;
            color: #64748b;
        }

        .form-grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-label {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1e293b;
        }

        .form-hint {
            font-size: 0.78rem;
            color: #64748b;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #0f172a;
            background: #f8fafc;
            transition: all 0.2s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #0284c7;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        }

        .form-input:disabled, .form-select:disabled, .form-textarea:disabled {
            cursor: not-allowed;
            opacity: 1;
        }

        .prefill-disabled {
            background: #dbe4ee !important;
            border-color: #94a3b8 !important;
            color: #1e293b !important;
            font-weight: 600;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        .form-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 32px 0;
        }

        .siapkerja-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .siapkerja-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .contact-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 8px;
        }

        .contact-option-card {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #f8fafc;
            transition: all 0.2s;
        }

        .contact-option-card:hover { border-color: #93c5fd; background: #ffffff; }
        .contact-option-card.active { border-color: #0284c7; background: #eff6ff; }
        .contact-radio { margin-top: 3px; accent-color: #0284c7; }

        .new-contact-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 14px;
            padding: 16px;
            background: #f8fafc;
            border: 1px dashed #bfdbfe;
            border-radius: 10px;
        }

        .dynamic-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            position: relative;
            margin-bottom: 16px;
        }

        .btn-remove-item {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-add-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            color: #0284c7;
            border: 1px solid #bfdbfe;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-add-item:hover { background: #dbeafe; border-color: #0284c7; }

        .btn-submit {
            background: #0284c7;
            color: #ffffff;
            border: none;
            padding: 14px 36px;
            border-radius: 9999px;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s ease;
        }
        .btn-submit:hover { background: #0369a1; }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

        .page-footer {
            padding: 24px 20px;
            text-align: center;
            font-size: 13.5px;
            color: #64748b;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            margin-top: 40px;
        }
        .page-footer a { color: #0284c7; font-weight: 600; text-decoration: none; }
        .page-footer a:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .reg-card { padding: 24px 20px; }
            .form-grid-2col, .contact-options { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <header class="header-nav">
        <a href="welcome-screen.php" class="nav-link-left">Kembali ke Halaman Utama</a>

        <a href="welcome-screen.php" class="nav-center-logo">
            <svg width="32" height="32" viewBox="0 0 40 40" fill="none">
                <path d="M10 10 H28 Q32 10 32 14 V18 L20 30 H10 Z" fill="#18b5ea"/>
                <circle cx="28" cy="12" r="3" fill="#38bdf8"/>
            </svg>
            <div>
                <span class="logo-text">Karir<span style="color: #18b5ea;">hub</span></span>
                <span class="logo-subtext">oleh Kemnaker</span>
            </div>
        </a>

        <div style="min-width: 170px;"></div>
    </header>

    <main class="main-container">
        <div class="reg-card">
            <h1 class="form-header-title">FORM PROFIL GIG WORKER</h1>
            <p class="form-header-subtitle">Lengkapi biodata individu untuk pengajuan verifikasi Hak Akses Gig Worker.</p>

    <?php if ($successMessage !== ""): ?>
      <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ""): ?>
      <div class="alert alert-error">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    <?php endif; ?>

    <!-- FORM PENDAFTARAN GIG WORKER -->
    <form method="POST" action="" style="display:flex; flex-direction:column;">
      <input type="hidden" name="action" value="register_gig_worker" />

      <!-- BAGIAN 1: IDENTITAS DARI SIAPKERJA -->
      <div class="form-section-header">
        <h2 class="form-section-title">1. IDENTITAS PERORANGAN</h2>
        <p class="form-section-subtitle">
          Data pada segmen ini ditarik dari akun SIAPKerja Anda. Nama dan NIK bersifat read-only.
        </p>
      </div>

      <div class="form-grid-2col">
        <div class="form-group">
          <label class="form-label" for="siap_nama">Nama Gig Worker <span style="color:#ef4444;">*</span></label>
          <input type="text" id="siap_nama" class="form-input prefill-disabled" value="<?php echo htmlspecialchars((string)$siapkerja['nama'], ENT_QUOTES, 'UTF-8'); ?>" readonly disabled />
          <span class="form-hint">Data nama terisi otomatis (prefill) dari akun SIAPKerja.</span>
        </div>
        <div class="form-group">
          <label class="form-label" for="siap_nik">NIK <span style="color:#ef4444;">*</span></label>
          <input type="text" id="siap_nik" class="form-input prefill-disabled" value="<?php echo htmlspecialchars((string)$siapkerja['nik'], ENT_QUOTES, 'UTF-8'); ?>" readonly disabled />
          <span class="form-hint">Data NIK terisi otomatis (prefill) dari akun SIAPKerja.</span>
        </div>
      </div>

      <div class="form-grid-2col" style="margin-top:14px;">
        <div class="form-group">
          <label class="form-label" for="siap_alamat">Alamat <span style="color:#ef4444;">*</span></label>
          <input type="text" id="siap_alamat" class="form-input prefill-disabled" value="<?php echo htmlspecialchars((string)$siapkerja['lokasi'], ENT_QUOTES, 'UTF-8'); ?>" readonly disabled />
          <span class="form-hint">Alamat terhubung dari akun SIAPKerja.</span>
        </div>
        <div class="form-group">
          <label class="form-label" for="siap_email">Email <span style="color:#ef4444;">*</span></label>
          <input type="text" id="siap_email" class="form-input prefill-disabled" value="<?php echo htmlspecialchars((string)$siapkerja['email'], ENT_QUOTES, 'UTF-8'); ?>" readonly disabled />
          <span class="form-hint">Email SIAPKerja bersifat read-only.</span>
        </div>
      </div>

      <div class="form-grid-2col" style="margin-top:14px;">
        <div class="form-group">
          <label class="form-label" for="siap_phone">Nomor Telepon Aktif <span style="color:#ef4444;">*</span></label>
          <input type="text" id="siap_phone" class="form-input prefill-disabled" value="<?php echo htmlspecialchars((string)$siapkerja['wa'], ENT_QUOTES, 'UTF-8'); ?>" readonly disabled />
          <span class="form-hint">Nomor telepon SIAPKerja bersifat read-only.</span>
        </div>
      </div>

      <div style="margin-top:18px;padding:14px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
        <div style="font-size:0.82rem;font-weight:800;color:#0f172a;margin-bottom:8px;">Pilihan Kontak untuk Proyek Gig Worker</div>
        <p style="font-size:0.78rem;color:#64748b;margin-bottom:10px;">Anda dapat menggunakan kontak SIAPKerja atau input kontak baru khusus untuk layanan Gig Worker.</p>

        <div class="contact-options">
          <label class="contact-option-card <?php echo $currentContactChoice === 'siapkerja' ? 'active' : ''; ?>" id="opt-siapkerja" onclick="selectContactOption('siapkerja')">
            <input type="radio" name="contact_choice" value="siapkerja" class="contact-radio" <?php echo $currentContactChoice === 'siapkerja' ? 'checked' : ''; ?> />
            <div>
              <strong style="font-size:0.9rem; color:var(--text-main);">Gunakan Kontak Akun SIAPKerja</strong>
              <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">
                Email: <?php echo htmlspecialchars($siapkerja['email'], ENT_QUOTES, 'UTF-8'); ?><br />
                WhatsApp: <?php echo htmlspecialchars($siapkerja['wa'], ENT_QUOTES, 'UTF-8'); ?>
              </div>
            </div>
          </label>

          <label class="contact-option-card <?php echo $currentContactChoice === 'new' ? 'active' : ''; ?>" id="opt-new" onclick="selectContactOption('new')">
            <input type="radio" name="contact_choice" value="new" class="contact-radio" <?php echo $currentContactChoice === 'new' ? 'checked' : ''; ?> />
            <div>
              <strong style="font-size:0.9rem; color:var(--text-main);">Input Informasi Kontak Baru</strong>
              <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">
                Gunakan alamat email atau nomor telepon/WA alternatif khusus proyek Gig Worker.
              </div>
            </div>
          </label>
        </div>

        <div class="new-contact-fields" id="newContactContainer" style="display: <?php echo $currentContactChoice === 'new' ? 'grid' : 'none'; ?>;">
          <div class="form-group">
            <label class="form-label" for="contact_email_new">Email Kontak Baru</label>
            <input type="email" id="contact_email_new" name="contact_email_new" class="form-input" value="<?php echo htmlspecialchars($currentContactEmail, ENT_QUOTES, 'UTF-8'); ?>" placeholder="contoh: tessa.gig@email.com" />
          </div>

          <div class="form-group">
            <label class="form-label" for="contact_wa_new">Nomor WhatsApp / HP Baru</label>
            <input type="text" id="contact_wa_new" name="contact_wa_new" class="form-input" value="<?php echo htmlspecialchars($currentContactWa, ENT_QUOTES, 'UTF-8'); ?>" placeholder="contoh: 0812-9988-7766" />
          </div>
        </div>
      </div>

      <!-- BAGIAN 2: MEDIA SOSIAL GIG WORKER -->
      <div class="form-section-header">
        <h2 class="form-section-title">2. MEDIA SOSIAL GIG WORKER</h2>
        <p class="form-section-subtitle">
          Tautkan akun media sosial Anda (misal: LinkedIn, GitHub, Instagram, Twitter/X, YouTube, TikTok, Behance, Dribbble, atau Website Personal). Anda dapat menambahkan lebih dari satu akun media sosial.
        </p>
      </div>

      <div class="form-group">
        <label class="form-label">Tautan / Akun Media Sosial</label>
        <div id="social-media-container" style="display: flex; flex-direction: column; gap: 12px;">
          <?php foreach ($currentSocialMedia as $sIdx => $smItem): ?>
            <?php
              $pVal = (string)($smItem['platform'] ?? 'LinkedIn');
              $uVal = (string)($smItem['url'] ?? '');
            ?>
            <div class="social-media-row" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
              <div style="width: 200px; flex-shrink: 0;">
                <select name="social_media_platform[]" class="form-select">
                  <?php
                    $platforms = ['LinkedIn', 'GitHub', 'Instagram', 'Twitter / X', 'YouTube', 'TikTok', 'Behance', 'Dribbble', 'Website Personal', 'Lainnya'];
                    foreach ($platforms as $pOpt):
                  ?>
                    <option value="<?php echo htmlspecialchars($pOpt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo strcasecmp($pVal, $pOpt) === 0 ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($pOpt, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div style="flex: 1; min-width: 220px;">
                <input type="text" name="social_media_url[]" class="form-input" value="<?php echo htmlspecialchars($uVal, ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://... atau username" />
              </div>
              <button type="button" class="btn-remove-social" onclick="removeSocialRow(this)" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 11px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.84rem; flex-shrink: 0;" title="Hapus Media Sosial">
                Hapus
              </button>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top: 12px;">
          <button type="button" onclick="addSocialRow()" style="background: #f0f9ff; color: #0284c7; border: 1px solid #0284c7; padding: 10px 18px; border-radius: 8px; font-weight: 700; font-size: 0.86rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            + Tambah Media Sosial
          </button>
        </div>
      </div>

      <!-- BAGIAN 3: BIDANG KEAHLIAN & SKILL -->
      <div class="form-section-header">
        <h2 class="form-section-title">3. BIDANG KEAHLIAN &amp; SKILL SPESIFIK</h2>
        <p class="form-section-subtitle">Tentukan spesialisasi utama dan daftar keahlian teknis Anda.</p>
      </div>

      <div class="form-group" style="margin-bottom:24px;">
        <label class="form-label" for="bidang_keahlian">Bidang Keahlian Utama <span style="color:#ef4444;">*</span></label>
        <?php 
          $bidangOpts = [
            "UI/UX Design & Product Interface",
            "Web Development (Frontend / Backend / Fullstack)",
            "Mobile Application Development",
            "Digital Marketing & Social Media Strategy",
            "Data Analytics & Data Entry",
            "Copywriting, Content Writing & Translation",
            "Graphic Design, Video Editing & Multimedia",
            "Administrative & Virtual Assistant"
          ];
        ?>
        <select id="bidang_keahlian" name="bidang_keahlian" class="form-select" required style="max-width:540px;">
          <option value="">-- Pilih Bidang Keahlian --</option>
          <?php foreach ($bidangOpts as $bOpt): ?>
            <option value="<?php echo htmlspecialchars($bOpt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $currentBidang === $bOpt ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($bOpt, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" style="display:flex;justify-content:space-between;align-items:center;">
          <span>Skill / Keahlian Spesifik &amp; Tingkat Keahlian <span style="color:#ef4444;">*</span></span>
          <span style="font-size:0.75rem;font-weight:600;color:#64748b;">(Minimal 1 keahlian)</span>
        </label>
        <p style="font-size:0.82rem;color:#64748b;margin:0 0 12px 0;">
          Masukkan skill yang Anda kuasai dan tentukan tingkat keahlian (Pemula, Menengah, Mahir, Ahli). Anda dapat menambahkan lebih dari 1 keahlian.
        </p>

        <div id="skillsRepeater" style="display:flex;flex-direction:column;gap:10px;">
          <?php foreach ($currentSkillsList as $sIdx => $sItem): ?>
            <div class="skill-row-item" style="display:flex;gap:10px;align-items:center;">
              <input type="text" name="skill_name[]" class="form-input" style="flex:1;" placeholder="Nama keahlian (contoh: Figma, React, Copywriting, SQL)" value="<?php echo htmlspecialchars((string)($sItem['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required />
              <select name="skill_level[]" class="form-select" style="width:160px;min-width:140px;flex-shrink:0;">
                <?php foreach (gig_skill_levels() as $lvlOpt): ?>
                  <option value="<?php echo $lvlOpt; ?>" <?php echo (string)($sItem['level'] ?? '') === $lvlOpt ? 'selected' : ''; ?>>
                    <?php echo $lvlOpt; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="btn-remove-skill" style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-weight:bold;font-size:1.1rem;flex-shrink:0;transition:all 0.2s;" title="Hapus keahlian" onclick="removeSkillRow(this)">✕</button>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:12px;">
          <button type="button" id="btnAddSkill" class="btn-add-item" onclick="addSkillRow()">
            + Tambah Keahlian
          </button>
        </div>
      </div>

      <div class="form-divider"></div>

      <?php
        $monthsList = [
          'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
          'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
      ?>

      <!-- BAGIAN 4: PENGALAMAN -->
      <div class="form-section-header">
        <h2 class="form-section-title">4. PENGALAMAN</h2>
        <p class="form-section-subtitle">Data pengalaman ditarik dari SIAPKerja. Anda dapat menambah, memperbarui, serta melampirkan hasil karya/portofolio pada tiap pengalaman.</p>
      </div>

      <div id="projectContainer">
        <?php if (!empty($currentProjects) && is_array($currentProjects)): ?>
          <?php foreach ($currentProjects as $projIdx => $proj): ?>
            <?php
              $pTitle = $proj['role'] ?? ($proj['project'] ?? '');
              $pCompany = $proj['company'] ?? ($proj['project'] ?? '');
              if ($pCompany === $pTitle) {
                $pCompany = $proj['company'] ?? '';
              }
              $sMonth = $proj['start_month'] ?? '';
              $sYear = $proj['start_year'] ?? '';
              $eMonth = $proj['end_month'] ?? '';
              $eYear = $proj['end_year'] ?? '';
              $summary = $proj['summary'] ?? '';
              $outTitle = $proj['output_title'] ?? ($proj['portfolio_title'] ?? '');
              $isSk = !empty($proj['is_siapkerja']);
              $files = !empty($proj['files']) && is_array($proj['files']) ? $proj['files'] : [];
              if (empty($files) && !empty($proj['url'])) {
                $files = [['url' => $proj['url']]];
              }
              if (empty($files) && isset($currentPortfolio[$projIdx]['files'])) {
                $files = $currentPortfolio[$projIdx]['files'];
              }
              if (empty($files)) {
                $files = [['url' => '']];
              }
            ?>
            <div class="dynamic-item" id="proj-item-<?php echo $projIdx; ?>" style="<?php echo $isSk ? 'background:#f8fafc;border:1px solid #cbd5e1;' : ''; ?>">
              <input type="hidden" name="is_siapkerja[]" value="<?php echo $isSk ? '1' : '0'; ?>" />
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:8px;border-bottom:1px dashed #cbd5e1;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                  <strong style="font-size:0.95rem;color:#0f172a;">Pengalaman #<?php echo $projIdx + 1; ?></strong>
                  <?php if ($isSk): ?>
                    <span class="chip" style="background:#e0f2fe;color:#0369a1;font-size:0.72rem;font-weight:700;padding:2px 9px;border-radius:9999px;border:1px solid #bae6fd;">🔒 Data Terhubung SIAPKerja (Read-Only)</span>
                  <?php endif; ?>
                </div>
                <?php if ($projIdx > 0 && !$isSk): ?>
                  <button type="button" class="btn-remove-item" onclick="document.getElementById('proj-item-<?php echo $projIdx; ?>').remove()">Hapus Pengalaman</button>
                <?php endif; ?>
              </div>
              
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div class="form-group">
                  <label class="form-label">Nama Pekerjaan / Proyek <span style="color:#ef4444;">*</span></label>
                  <?php if ($isSk): ?>
                    <input type="text" class="form-input prefill-disabled" value="<?php echo htmlspecialchars((string)$pTitle, ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="background:#e2e8f0;color:#475569;cursor:not-allowed;font-weight:600;" />
                    <input type="hidden" name="project_title[]" value="<?php echo htmlspecialchars((string)$pTitle, ENT_QUOTES, 'UTF-8'); ?>" />
                  <?php else: ?>
                    <input type="text" name="project_title[]" class="form-input" value="<?php echo htmlspecialchars((string)$pTitle, ENT_QUOTES, 'UTF-8'); ?>" placeholder="contoh: Redesign UI/UX Mobile App" required />
                  <?php endif; ?>
                </div>
                <div class="form-group">
                  <label class="form-label">Nama Perusahaan <span style="color:#ef4444;">*</span></label>
                  <?php if ($isSk): ?>
                    <input type="text" class="form-input prefill-disabled" value="<?php echo htmlspecialchars((string)$pCompany, ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="background:#e2e8f0;color:#475569;cursor:not-allowed;font-weight:600;" />
                    <input type="hidden" name="project_company[]" value="<?php echo htmlspecialchars((string)$pCompany, ENT_QUOTES, 'UTF-8'); ?>" />
                  <?php else: ?>
                    <input type="text" name="project_company[]" class="form-input" value="<?php echo htmlspecialchars((string)$pCompany, ENT_QUOTES, 'UTF-8'); ?>" placeholder="contoh: PT Solusi Digital Nusantara" required />
                  <?php endif; ?>
                </div>
              </div>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div class="form-group">
                  <label class="form-label">Bulan Mulai <span style="color:#ef4444;">*</span></label>
                  <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:8px;">
                    <?php if ($isSk): ?>
                      <select class="form-select prefill-disabled" disabled style="background:#e2e8f0;color:#475569;cursor:not-allowed;font-weight:600;">
                        <option value="<?php echo htmlspecialchars((string)$sMonth, ENT_QUOTES, 'UTF-8'); ?>" selected><?php echo htmlspecialchars((string)($sMonth ?: 'Pilih Bulan'), ENT_QUOTES, 'UTF-8'); ?></option>
                      </select>
                      <input type="hidden" name="start_month[]" value="<?php echo htmlspecialchars((string)$sMonth, ENT_QUOTES, 'UTF-8'); ?>" />
                      <input type="text" class="form-input prefill-disabled" value="<?php echo htmlspecialchars((string)$sYear, ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="background:#e2e8f0;color:#475569;cursor:not-allowed;font-weight:600;" />
                      <input type="hidden" name="start_year[]" value="<?php echo htmlspecialchars((string)$sYear, ENT_QUOTES, 'UTF-8'); ?>" />
                    <?php else: ?>
                      <select name="start_month[]" class="form-select" required>
                        <option value="">-- Pilih Bulan --</option>
                        <?php foreach ($monthsList as $mOpt): ?>
                          <option value="<?php echo $mOpt; ?>" <?php echo $sMonth === $mOpt ? 'selected' : ''; ?>><?php echo $mOpt; ?></option>
                        <?php endforeach; ?>
                      </select>
                      <input type="text" name="start_year[]" class="form-input" value="<?php echo htmlspecialchars((string)$sYear, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tahun (2024)" maxlength="4" required />
                    <?php endif; ?>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Bulan Selesai <span style="color:#ef4444;">*</span></label>
                  <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:8px;">
                    <?php if ($isSk): ?>
                      <select class="form-select prefill-disabled" disabled style="background:#e2e8f0;color:#475569;cursor:not-allowed;font-weight:600;">
                        <option value="<?php echo htmlspecialchars((string)$eMonth, ENT_QUOTES, 'UTF-8'); ?>" selected><?php echo htmlspecialchars((string)($eMonth ?: 'Pilih Bulan'), ENT_QUOTES, 'UTF-8'); ?></option>
                      </select>
                      <input type="hidden" name="end_month[]" value="<?php echo htmlspecialchars((string)$eMonth, ENT_QUOTES, 'UTF-8'); ?>" />
                      <input type="text" class="form-input prefill-disabled" value="<?php echo htmlspecialchars((string)$eYear, ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="background:#e2e8f0;color:#475569;cursor:not-allowed;font-weight:600;" />
                      <input type="hidden" name="end_year[]" value="<?php echo htmlspecialchars((string)$eYear, ENT_QUOTES, 'UTF-8'); ?>" />
                    <?php else: ?>
                      <select name="end_month[]" class="form-select" required>
                        <option value="">-- Pilih Bulan --</option>
                        <option value="Masih Berjalan" <?php echo $eMonth === 'Masih Berjalan' ? 'selected' : ''; ?>>Masih Berjalan</option>
                        <?php foreach ($monthsList as $mOpt): ?>
                          <option value="<?php echo $mOpt; ?>" <?php echo $eMonth === $mOpt ? 'selected' : ''; ?>><?php echo $mOpt; ?></option>
                        <?php endforeach; ?>
                      </select>
                      <input type="text" name="end_year[]" class="form-input" value="<?php echo htmlspecialchars((string)$eYear, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tahun (2025)" maxlength="4" required />
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Ringkasan Tugas <span style="color:#ef4444;">*</span></label>
                <textarea name="project_summary[]" class="form-input" rows="3" style="resize:vertical;" placeholder="Deskripsikan peran, tugas, dan hasil pencapaian Anda dalam proyek ini..." required><?php echo htmlspecialchars((string)$summary, ENT_QUOTES, 'UTF-8'); ?></textarea>
              </div>

              <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-top:10px;">
                <div style="font-size:0.86rem;font-weight:700;color:#0f172a;margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;">
                  <span>📁 Output Proyek</span>
                  <span style="font-size:0.75rem;color:#64748b;font-weight:500;">Link Figma, GitHub, Google Drive, Dribbble, dll.</span>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                  <label class="form-label" style="font-size:0.78rem;">Nama Output Proyek</label>
                  <input type="text" name="portfolio_title[<?php echo $projIdx; ?>]" class="form-input" style="font-size:0.85rem;" value="<?php echo htmlspecialchars((string)$outTitle, ENT_QUOTES, 'UTF-8'); ?>" placeholder="contoh: Wireframe &amp; Design System Figma" />
                </div>
                <div id="file-list-<?php echo $projIdx; ?>">
                  <?php foreach ($files as $fIdx => $f): ?>
                    <div class="file-item-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                      <input type="url" name="portfolio_file_url[<?php echo $projIdx; ?>][]" class="form-input" style="font-size:0.85rem;flex:1;" value="<?php echo htmlspecialchars((string)($f['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://... (Masukkan Tautan Link Output Proyek)" />
                      <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:#ef4444;font-size:1.2rem;font-weight:bold;cursor:pointer;padding:0 4px;" title="Hapus link ini">&times;</button>
                    </div>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="btn-add-item" style="font-size:0.78rem;padding:5px 12px;margin-top:4px;" onclick="addFileToPortfolio(<?php echo $projIdx; ?>)">
                  + Tambah Link Tautan Output Proyek
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="dynamic-item" id="proj-item-0">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:8px;border-bottom:1px dashed #cbd5e1;">
              <strong style="font-size:0.95rem;color:#0f172a;">Pengalaman #1</strong>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
              <div class="form-group">
                <label class="form-label">Nama Pekerjaan / Proyek <span style="color:#ef4444;">*</span></label>
                <input type="text" name="project_title[]" class="form-input" placeholder="contoh: Redesign UI/UX Mobile App" required />
              </div>
              <div class="form-group">
                <label class="form-label">Nama Perusahaan <span style="color:#ef4444;">*</span></label>
                <input type="text" name="project_company[]" class="form-input" placeholder="contoh: PT Solusi Digital Nusantara" required />
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
              <div class="form-group">
                <label class="form-label">Bulan Mulai <span style="color:#ef4444;">*</span></label>
                <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:8px;">
                  <select name="start_month[]" class="form-select" required>
                    <option value="">-- Pilih Bulan --</option>
                    <?php foreach ($monthsList as $mOpt): ?>
                      <option value="<?php echo $mOpt; ?>"><?php echo $mOpt; ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="text" name="start_year[]" class="form-input" placeholder="Tahun (2024)" maxlength="4" required />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Bulan Selesai <span style="color:#ef4444;">*</span></label>
                <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:8px;">
                  <select name="end_month[]" class="form-select" required>
                    <option value="">-- Pilih Bulan --</option>
                    <option value="Masih Berjalan">Masih Berjalan</option>
                    <?php foreach ($monthsList as $mOpt): ?>
                      <option value="<?php echo $mOpt; ?>"><?php echo $mOpt; ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="text" name="end_year[]" class="form-input" placeholder="Tahun (2025)" maxlength="4" required />
                </div>
              </div>
            </div>

            <div class="form-group" style="margin-bottom:14px;">
              <label class="form-label">Ringkasan Tugas <span style="color:#ef4444;">*</span></label>
              <textarea name="project_summary[]" class="form-input" rows="3" style="resize:vertical;" placeholder="Deskripsikan peran, tugas, dan hasil pencapaian Anda dalam proyek ini..." required></textarea>
            </div>

            <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-top:10px;">
              <div style="font-size:0.86rem;font-weight:700;color:#0f172a;margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;">
                <span>📁 Output Proyek</span>
                <span style="font-size:0.75rem;color:#64748b;font-weight:500;">Link Figma, GitHub, Google Drive, Dribbble, dll.</span>
              </div>
              <div class="form-group" style="margin-bottom:10px;">
                <label class="form-label" style="font-size:0.78rem;">Nama Output Proyek</label>
                <input type="text" name="portfolio_title[0]" class="form-input" style="font-size:0.85rem;" placeholder="contoh: Prototype Figma / Repositori GitHub" />
              </div>
              <div id="file-list-0">
                <div class="file-item-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                  <input type="url" name="portfolio_file_url[0][]" class="form-input" style="font-size:0.85rem;flex:1;" placeholder="https://... (Masukkan Tautan Link Output Proyek)" />
                  <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:#ef4444;font-size:1.2rem;font-weight:bold;cursor:pointer;padding:0 4px;" title="Hapus link ini">&times;</button>
                </div>
              </div>
              <button type="button" class="btn-add-item" style="font-size:0.78rem;padding:5px 12px;margin-top:4px;" onclick="addFileToPortfolio(0)">
                + Tambah Link Tautan Output Proyek
              </button>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <button type="button" class="btn-add-item" onclick="addProjectItem()" style="margin-top:8px;align-self:flex-start;">
        + Tambah Pengalaman Gig Workers
      </button>

      <div class="form-divider"></div>

      <!-- BAGIAN 5: LINK VIDEO PROFIL -->
      <div class="form-section-header">
        <h2 class="form-section-title">5. LINK VIDEO PROFIL GIG WORKER</h2>
        <p class="form-section-subtitle">
          Sampaikan perkenalan singkat diri dan keahlian Anda melalui video (misal: YouTube, Loom, atau Google Drive Video). Anda dapat menambahkan lebih dari satu tautan video.
        </p>
      </div>

      <div class="form-group">
        <label class="form-label">Tautan / URL Video Profil</label>
        <div id="video-links-container" style="display: flex; flex-direction: column; gap: 12px;">
          <?php foreach ($currentVideoUrls as $vIdx => $vUrl): ?>
            <div class="video-url-row" style="display: flex; gap: 10px; align-items: center;">
              <div style="flex: 1;">
                <input type="url" name="video_urls[]" class="form-input video-url-input" value="<?php echo htmlspecialchars($vUrl, ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://www.youtube.com/watch?v=... atau https://www.loom.com/share/..." />
              </div>
              <button type="button" class="btn-remove-video" onclick="removeVideoRow(this)" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 11px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.84rem; flex-shrink: 0;" title="Hapus Link Video">
                Hapus
              </button>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top: 12px;">
          <button type="button" onclick="addVideoRow()" style="background: #f0f9ff; color: #0284c7; border: 1px solid #0284c7; padding: 10px 18px; border-radius: 8px; font-weight: 700; font-size: 0.86rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            + Tambah Link Video Profil
          </button>
        </div>
      </div>

      <!-- SUBMIT -->
      <?php if (!$isEditMode): ?>
      <div style="margin-top:24px;padding:14px 16px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
        <label for="consent_truth" style="display:flex;gap:10px;align-items:flex-start;cursor:pointer;">
          <input type="checkbox" id="consent_truth" name="consent_truth" value="1" style="margin-top:3px;accent-color:#0284c7;" />
          <span style="font-size:0.84rem;color:#334155;line-height:1.5;">
            Saya menyatakan bahwa seluruh data yang saya submit adalah benar. Saya bersedia memenuhi kewajiban saya sebagai Gig Worker, dan apabila ditemukan bahwa saya tidak melaksanakan kewajiban sebagaimana mestinya, saya bersedia dimintai pertanggungjawaban berdasarkan peraturan perundang-undangan.
          </span>
        </label>
      </div>
      <?php endif; ?>

      <div style="display: flex; justify-content: flex-end; gap: 14px; margin-top: 32px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
        <a href="pilih-pendaftaran.php" style="padding:14px 24px; text-decoration:none; color:#64748b; font-weight:700; font-size:0.9rem;">Batal</a>
        <button type="submit" class="btn-submit" id="worker-register-submit-btn" <?php echo !$isEditMode ? 'disabled style="background:#9ca3af;cursor:not-allowed;box-shadow:none;"' : ''; ?>>
          <?php echo $isEditMode ? 'Simpan Pembaruan Profil' : 'Kirim Pengajuan Verifikasi'; ?>
        </button>
      </div>
    </form>


  <script>
    function selectContactOption(choice) {
      const optSiapkerja = document.getElementById('opt-siapkerja');
      const optNew = document.getElementById('opt-new');
      const container = document.getElementById('newContactContainer');

      if (choice === 'new') {
        optSiapkerja.classList.remove('active');
        optNew.classList.add('active');
        container.style.display = 'grid';
      } else {
        optNew.classList.remove('active');
        optSiapkerja.classList.add('active');
        container.style.display = 'none';
      }
    }

    let projectCount = <?php echo max(1, is_array($currentProjects) ? count($currentProjects) : 1); ?>;
    const monthsOptionsHtml = `
      <option value="">-- Pilih Bulan --</option>
      <option value="Januari">Januari</option>
      <option value="Februari">Februari</option>
      <option value="Maret">Maret</option>
      <option value="April">April</option>
      <option value="Mei">Mei</option>
      <option value="Juni">Juni</option>
      <option value="Juli">Juli</option>
      <option value="Agustus">Agustus</option>
      <option value="September">September</option>
      <option value="Oktober">Oktober</option>
      <option value="November">November</option>
      <option value="Desember">Desember</option>
    `;

    function addProjectItem() {
      const pIdx = projectCount;
      projectCount++;
      const container = document.getElementById('projectContainer');
      const div = document.createElement('div');
      div.className = 'dynamic-item';
      div.id = 'proj-item-' + pIdx;
      div.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:8px;border-bottom:1px dashed #cbd5e1;">
          <strong style="font-size:0.95rem;color:#0f172a;">Pengalaman #${pIdx + 1}</strong>
          <button type="button" class="btn-remove-item" onclick="document.getElementById('proj-item-${pIdx}').remove()">Hapus Pengalaman</button>
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div class="form-group">
            <label class="form-label">Nama Pekerjaan / Proyek <span style="color:#ef4444;">*</span></label>
            <input type="text" name="project_title[]" class="form-input" placeholder="contoh: Redesign UI/UX Mobile App" required />
          </div>
          <div class="form-group">
            <label class="form-label">Nama Perusahaan <span style="color:#ef4444;">*</span></label>
            <input type="text" name="project_company[]" class="form-input" placeholder="contoh: PT Solusi Digital Nusantara" required />
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div class="form-group">
            <label class="form-label">Bulan Mulai <span style="color:#ef4444;">*</span></label>
            <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:8px;">
              <select name="start_month[]" class="form-select" required>
                ${monthsOptionsHtml}
              </select>
              <input type="text" name="start_year[]" class="form-input" placeholder="Tahun (2024)" maxlength="4" required />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Bulan Selesai <span style="color:#ef4444;">*</span></label>
            <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:8px;">
              <select name="end_month[]" class="form-select" required>
                <option value="">-- Pilih Bulan --</option>
                <option value="Masih Berjalan">Masih Berjalan</option>
                <option value="Januari">Januari</option>
                <option value="Februari">Februari</option>
                <option value="Maret">Maret</option>
                <option value="April">April</option>
                <option value="Mei">Mei</option>
                <option value="Juni">Juni</option>
                <option value="Juli">Juli</option>
                <option value="Agustus">Agustus</option>
                <option value="September">September</option>
                <option value="Oktober">Oktober</option>
                <option value="November">November</option>
                <option value="Desember">Desember</option>
              </select>
              <input type="text" name="end_year[]" class="form-input" placeholder="Tahun (2025)" maxlength="4" required />
            </div>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:14px;">
          <label class="form-label">Ringkasan Tugas <span style="color:#ef4444;">*</span></label>
          <textarea name="project_summary[]" class="form-input" rows="3" style="resize:vertical;" placeholder="Deskripsikan peran, tugas, dan hasil pencapaian Anda dalam proyek ini..." required></textarea>
        </div>

        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-top:10px;">
          <div style="font-size:0.86rem;font-weight:700;color:#0f172a;margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;">
            <span>📁 Output Proyek</span>
            <span style="font-size:0.75rem;color:#64748b;font-weight:500;">Link Figma, GitHub, Google Drive, Dribbble, dll.</span>
          </div>
          <div class="form-group" style="margin-bottom:10px;">
            <label class="form-label" style="font-size:0.78rem;">Nama Output Proyek</label>
            <input type="text" name="portfolio_title[${pIdx}]" class="form-input" style="font-size:0.85rem;" placeholder="contoh: Prototype Figma / Repositori GitHub" />
          </div>
          <div id="file-list-${pIdx}">
            <div class="file-item-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
              <input type="url" name="portfolio_file_url[${pIdx}][]" class="form-input" style="font-size:0.85rem;flex:1;" placeholder="https://... (Masukkan Tautan Link Output Proyek)" />
              <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:#ef4444;font-size:1.2rem;font-weight:bold;cursor:pointer;padding:0 4px;" title="Hapus link ini">&times;</button>
            </div>
          </div>
          <button type="button" class="btn-add-item" style="font-size:0.78rem;padding:5px 12px;margin-top:4px;" onclick="addFileToPortfolio(${pIdx})">
            + Tambah Link Tautan Output Proyek
          </button>
        </div>
      `;
      container.appendChild(div);
    }

    function addFileToPortfolio(pIdx) {
      const fileList = document.getElementById('file-list-' + pIdx);
      if (!fileList) return;
      const row = document.createElement('div');
      row.className = 'file-item-row';
      row.style.cssText = 'display: flex; gap: 8px; align-items: center; margin-bottom: 8px;';
      row.innerHTML = `
        <input type="url" name="portfolio_file_url[${pIdx}][]" class="form-input" style="font-size:0.85rem; flex:1;" placeholder="https://... (Masukkan Tautan Link Deliverable)" />
        <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:#ef4444; font-size:1.2rem; font-weight:bold; cursor:pointer; padding:0 4px;" title="Hapus link ini">&times;</button>
      `;
      fileList.appendChild(row);
    }

    function checkVideoPreview(url) {
      const statusDiv = document.getElementById('videoPreviewStatus');
      if (!url.trim()) {
        statusDiv.style.display = 'none';
        return;
      }
      statusDiv.style.display = 'block';
      if (url.includes('youtube.com') || url.includes('youtu.be') || url.includes('loom.com') || url.includes('drive.google.com')) {
        statusDiv.style.color = '#10b981';
        statusDiv.innerHTML = '✓ Format video terdeteksi. Video profil siap ditampilkan di profil Gig Worker Anda.';
      } else {
        statusDiv.style.color = '#d97706';
        statusDiv.innerHTML = 'ℹ Tautan video terdaftar. Pastikan akses video diset ke Publik/Unlisted.';
      }
    }

    function addVideoRow() {
      const container = document.getElementById('video-links-container');
      if (!container) return;
      const div = document.createElement('div');
      div.className = 'video-url-row';
      div.style.cssText = 'display: flex; gap: 10px; align-items: center;';
      div.innerHTML = `
        <div style="flex: 1;">
          <input type="url" name="video_urls[]" class="form-input video-url-input" placeholder="https://www.youtube.com/watch?v=... atau https://www.loom.com/share/..." />
        </div>
        <button type="button" class="btn-remove-video" onclick="removeVideoRow(this)" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 11px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.84rem; flex-shrink: 0;" title="Hapus Link Video">
          Hapus
        </button>
      `;
      container.appendChild(div);
    }

    function removeVideoRow(btn) {
      const container = document.getElementById('video-links-container');
      if (!container) return;
      const rows = container.querySelectorAll('.video-url-row');
      if (rows.length > 1) {
        btn.closest('.video-url-row').remove();
      } else {
        const input = rows[0].querySelector('input');
        if (input) input.value = '';
      }
    }

    function addSocialRow() {
      const container = document.getElementById('social-media-container');
      if (!container) return;
      const div = document.createElement('div');
      div.className = 'social-media-row';
      div.style.cssText = 'display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 12px;';
      div.innerHTML = `
        <div style="width: 200px; flex-shrink: 0;">
          <select name="social_media_platform[]" class="form-select">
            <option value="LinkedIn" selected>LinkedIn</option>
            <option value="GitHub">GitHub</option>
            <option value="Instagram">Instagram</option>
            <option value="Twitter / X">Twitter / X</option>
            <option value="YouTube">YouTube</option>
            <option value="TikTok">TikTok</option>
            <option value="Behance">Behance</option>
            <option value="Dribbble">Dribbble</option>
            <option value="Website Personal">Website Personal</option>
            <option value="Lainnya">Lainnya</option>
          </select>
        </div>
        <div style="flex: 1; min-width: 220px;">
          <input type="text" name="social_media_url[]" class="form-input" placeholder="https://... atau username" />
        </div>
        <button type="button" class="btn-remove-social" onclick="removeSocialRow(this)" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 11px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.84rem; flex-shrink: 0;" title="Hapus Media Sosial">
          Hapus
        </button>
      `;
      container.appendChild(div);
    }

    function removeSocialRow(btn) {
      const container = document.getElementById('social-media-container');
      if (!container) return;
      const rows = container.querySelectorAll('.social-media-row');
      if (rows.length > 1) {
        btn.closest('.social-media-row').remove();
      } else {
        const inputs = rows[0].querySelectorAll('input');
        inputs.forEach(i => i.value = '');
      }
    }

    function addSkillRow(name = '', level = 'Menengah') {
      const container = document.getElementById('skillsRepeater');
      if (!container) return;
      const row = document.createElement('div');
      row.className = 'skill-row-item';
      row.style.cssText = 'display:flex;gap:10px;align-items:center;';
      
      const levels = ['Pemula', 'Menengah', 'Mahir', 'Ahli'];
      let optionsHtml = '';
      levels.forEach(lvl => {
        optionsHtml += `<option value="${lvl}" ${lvl === level ? 'selected' : ''}>${lvl}</option>`;
      });
      
      row.innerHTML = `
        <input type="text" name="skill_name[]" class="form-input" style="flex:1;" placeholder="Nama keahlian (contoh: Figma, React, Copywriting, SQL)" value="${name.replace(/"/g, '&quot;')}" required />
        <select name="skill_level[]" class="form-select" style="width:160px;min-width:140px;flex-shrink:0;">
          ${optionsHtml}
        </select>
        <button type="button" class="btn-remove-skill" style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-weight:bold;font-size:1.1rem;flex-shrink:0;transition:all 0.2s;" title="Hapus keahlian" onclick="removeSkillRow(this)">✕</button>
      `;
      container.appendChild(row);
      const input = row.querySelector('input');
      if (input) input.focus();
    }

    function removeSkillRow(btn) {
      const container = document.getElementById('skillsRepeater');
      if (!container) return;
      const rows = container.querySelectorAll('.skill-row-item');
      if (rows.length <= 1) {
        const firstInput = rows[0].querySelector('input');
        if (firstInput) firstInput.value = '';
        const firstSelect = rows[0].querySelector('select');
        if (firstSelect) firstSelect.value = 'Menengah';
        return;
      }
      btn.closest('.skill-row-item').remove();
    }

    function syncRegisterConsentState() {
      const consentCb = document.getElementById('consent_truth');
      const submitBtn = document.getElementById('worker-register-submit-btn');
      if (!submitBtn) return;
      if (!consentCb) return;
      submitBtn.disabled = !consentCb.checked;
      if (consentCb.checked) {
        submitBtn.style.background = '#0284c7';
        submitBtn.style.cursor = 'pointer';
        submitBtn.style.boxShadow = '0 4px 12px rgba(2, 132, 199, 0.2)';
      } else {
        submitBtn.style.background = '#9ca3af';
        submitBtn.style.cursor = 'not-allowed';
        submitBtn.style.boxShadow = 'none';
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      const consentCb = document.getElementById('consent_truth');
      if (!consentCb) return;
      consentCb.addEventListener('change', syncRegisterConsentState);
      syncRegisterConsentState();
    });
  </script>
        </div>
    </main>

    <footer class="page-footer">
        Butuh bantuan? <a href="#">Kunjungi Pusat Bantuan</a> atau hubungi kami
    </footer>

</body>
</html>
