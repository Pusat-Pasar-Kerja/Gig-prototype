<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/worker-auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/worker-profiles.php';
require_once __DIR__ . '/includes/admin-store.php';

$role = $_SESSION['role'] ?? 'worker';
if ($role !== 'worker') {
    header("Location: dashboard-employer.php");
    exit;
}

$username = (string)($_SESSION['username'] ?? $_SESSION['siapkerja_name'] ?? 'Theressa Zaratrusha');
$worker = gig_find_worker($username) ?? gig_find_worker('tessa');
$workerKey = gig_worker_profile_edit_key((string)($worker['id'] ?? $username));
$siapkerja = gig_get_siapkerja_profile($username);
$existingReg = gig_get_worker_registration($workerKey) ?? gig_get_worker_registration($username) ?? [];

$successMessage = '';
$errorMessage = '';
$hasPendingEdit = gig_worker_has_pending_profile_edit($workerKey);

function gig_edit_parse_period_to_dates(string $period): array
{
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
        'des' => 'Desember', 'desember' => 'Desember',
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
        'start_year' => $startYear,
        'end_month' => $endMonth,
        'end_year' => $endYear,
    ];
}

$currentBidang = $_POST['bidang_keahlian'] ?? ($existingReg['bidang_keahlian'] ?? ($worker['title'] ?? ''));

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
    } elseif (!empty($worker['skills'])) {
        $currentSkillsList = gig_normalize_skills($worker['skills']);
    }
}
if ($currentSkillsList === []) {
    $currentSkillsList = [['name' => '', 'level' => 'Menengah']];
}

$currentSummary = $_POST['profile_summary'] ?? ($existingReg['profile_summary'] ?? ($worker['proposal'] ?? ''));
$currentContactChoice = $_POST['contact_choice'] ?? ($existingReg['contact_choice'] ?? 'siapkerja');
$currentContactEmail = $_POST['contact_email_new'] ?? ($existingReg['contact_email'] ?? '');
$currentContactWa = $_POST['contact_wa_new'] ?? ($existingReg['contact_wa'] ?? '');

$videoSource = $_POST['video_url'] ?? ($existingReg['video_url'] ?? ($worker['video_url'] ?? ''));
if (isset($_POST['video_urls']) && is_array($_POST['video_urls'])) {
    $currentVideoUrls = array_values(array_filter(array_map('trim', $_POST['video_urls']), static fn($u) => $u !== ''));
} else {
    $currentVideoUrls = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', (string)$videoSource)), static fn($u) => $u !== ''));
}
if ($currentVideoUrls === []) {
    $currentVideoUrls = [''];
}

$currentSocialMedia = [];
if (!empty($_POST['social_media_platform']) && is_array($_POST['social_media_platform'])) {
    foreach ($_POST['social_media_platform'] as $idx => $platform) {
        $p = trim((string)$platform);
        $u = trim((string)($_POST['social_media_url'][$idx] ?? ''));
        if ($p !== '' || $u !== '') {
            $currentSocialMedia[] = ['platform' => $p, 'url' => $u];
        }
    }
} elseif (!empty($existingReg['social_media']) && is_array($existingReg['social_media'])) {
    $currentSocialMedia = $existingReg['social_media'];
} elseif (!empty($worker['social_media']) && is_array($worker['social_media'])) {
    $currentSocialMedia = $worker['social_media'];
}
if ($currentSocialMedia === []) {
    $currentSocialMedia = [['platform' => 'LinkedIn', 'url' => '']];
}

$siapReferenceMap = [];
if (!empty($siapkerja['pengalaman_siapkerja']) && is_array($siapkerja['pengalaman_siapkerja'])) {
    foreach ($siapkerja['pengalaman_siapkerja'] as $skExp) {
        $refRole = strtolower(trim((string)($skExp['role'] ?? '')));
        $refInstitution = strtolower(trim((string)($skExp['institution'] ?? '')));
        if ($refRole !== '') {
            $siapReferenceMap[$refRole] = true;
        }
        if ($refInstitution !== '') {
            $siapReferenceMap[$refInstitution] = true;
        }
    }
}

$rawProjects = [];
if (!empty($_POST['project_title']) && is_array($_POST['project_title'])) {
    foreach ($_POST['project_title'] as $idx => $title) {
        $sMonth = trim((string)($_POST['start_month'][$idx] ?? ''));
        $sYear = trim((string)($_POST['start_year'][$idx] ?? ''));
        $eMonth = trim((string)($_POST['end_month'][$idx] ?? ''));
        $eYear = trim((string)($_POST['end_year'][$idx] ?? ''));
        $startStr = trim($sMonth . ' ' . $sYear);
        $endStr = $eMonth === 'Masih Berjalan' ? 'Masih Berjalan' : trim($eMonth . ' ' . $eYear);
        $period = ($startStr !== '' || $endStr !== '') ? trim($startStr . ' - ' . $endStr, ' -') : '';

        $files = [];
        $urlRows = $_POST['portfolio_file_url'][$idx] ?? [];
        if (is_array($urlRows)) {
            foreach ($urlRows as $fIdx => $rawUrl) {
                $fu = trim((string)$rawUrl);
                if ($fu === '' || $fu === '#') {
                    continue;
                }
                $files[] = [
                    'name' => 'Link Deliverable ' . ($fIdx + 1),
                    'type' => 'Dokumen/Link Output Proyek',
                    'size' => 'Akses Web / Link',
                    'url' => $fu,
                ];
            }
        }

        $rawProjects[] = [
            'role' => trim((string)$title),
            'project' => trim((string)($_POST['project_company'][$idx] ?? '')),
            'company' => trim((string)($_POST['project_company'][$idx] ?? '')),
            'period' => $period,
            'start_month' => $sMonth,
            'start_year' => $sYear,
            'end_month' => $eMonth,
            'end_year' => $eYear,
            'summary' => trim((string)($_POST['project_summary'][$idx] ?? '')),
            'output_title' => trim((string)($_POST['portfolio_title'][$idx] ?? '')),
            'files' => $files,
            'is_siapkerja' => !empty($_POST['is_siapkerja'][$idx]),
        ];
    }
} elseif (!empty($existingReg['previous_projects']) && is_array($existingReg['previous_projects'])) {
    $rawProjects = $existingReg['previous_projects'];
} elseif (!empty($worker['experience']) && is_array($worker['experience'])) {
    $rawProjects = $worker['experience'];
}

$currentProjects = [];
foreach ($rawProjects as $rp) {
    $period = (string)($rp['period'] ?? '');
    $parsed = gig_edit_parse_period_to_dates($period);
    $roleLower = strtolower(trim((string)($rp['role'] ?? '')));
    $companyLower = strtolower(trim((string)($rp['company'] ?? ($rp['project'] ?? ''))));
    $isSiap = !empty($rp['is_siapkerja'])
        || isset($siapReferenceMap[$roleLower])
        || isset($siapReferenceMap[$companyLower]);
    $currentProjects[] = [
        'role' => (string)($rp['role'] ?? ''),
        'project' => (string)($rp['project'] ?? ($rp['company'] ?? '')),
        'company' => (string)($rp['company'] ?? ($rp['project'] ?? '')),
        'period' => $period,
        'start_month' => (string)($rp['start_month'] ?? $parsed['start_month']),
        'start_year' => (string)($rp['start_year'] ?? $parsed['start_year']),
        'end_month' => (string)($rp['end_month'] ?? $parsed['end_month']),
        'end_year' => (string)($rp['end_year'] ?? $parsed['end_year']),
        'summary' => (string)($rp['summary'] ?? ''),
        'output_title' => (string)($rp['output_title'] ?? ''),
        'files' => is_array($rp['files'] ?? null) ? $rp['files'] : [],
        'is_siapkerja' => $isSiap,
    ];
}
if ($currentProjects === []) {
    $currentProjects[] = [
        'role' => '',
        'project' => '',
        'company' => '',
        'period' => '',
        'start_month' => '',
        'start_year' => '',
        'end_month' => '',
        'end_year' => '',
        'summary' => '',
        'output_title' => '',
        'files' => [],
        'is_siapkerja' => false,
    ];
}
gig_sort_experience_timeline($currentProjects);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $changeSummary = trim((string)($_POST['change_summary'] ?? ''));
    $editedField = trim((string)($_POST['edited_field'] ?? ''));
    $reasonCode = trim((string)($_POST['reason_code'] ?? ''));
    $reasonDetail = trim((string)($_POST['reason_detail'] ?? ''));
    $bidangKeahlian = trim((string)($_POST['bidang_keahlian'] ?? ''));

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

    $profileSummary = trim((string)($_POST['profile_summary'] ?? ''));
    $contactChoice = trim((string)($_POST['contact_choice'] ?? 'siapkerja'));
    $contactEmail = $contactChoice === 'new' ? trim((string)($_POST['contact_email_new'] ?? '')) : (string)($siapkerja['email'] ?? '');
    $contactWa = $contactChoice === 'new' ? trim((string)($_POST['contact_wa_new'] ?? '')) : (string)($siapkerja['wa'] ?? '');
    $videoUrl = implode("\n", array_values(array_filter(array_map('trim', $_POST['video_urls'] ?? []), static fn($u) => $u !== '')));

    $socialMedia = [];
    if (!empty($_POST['social_media_platform']) && is_array($_POST['social_media_platform'])) {
        foreach ($_POST['social_media_platform'] as $sIdx => $plat) {
            $p = trim((string)$plat);
            $u = trim((string)($_POST['social_media_url'][$sIdx] ?? ''));
            if ($p !== '' || $u !== '') {
                $socialMedia[] = ['platform' => $p !== '' ? $p : 'LinkedIn', 'url' => $u];
            }
        }
    }

    $projects = [];
    $portfolios = [];
    if (!empty($_POST['project_title']) && is_array($_POST['project_title'])) {
        foreach ($_POST['project_title'] as $idx => $title) {
            $t = trim((string)$title);
            $comp = trim((string)($_POST['project_company'][$idx] ?? ''));
            $isSk = !empty($_POST['is_siapkerja'][$idx]);
            if ($t === '' && $comp === '') {
                continue;
            }
            $sMonth = trim((string)($_POST['start_month'][$idx] ?? ''));
            $sYear = trim((string)($_POST['start_year'][$idx] ?? ''));
            $eMonth = trim((string)($_POST['end_month'][$idx] ?? ''));
            $eYear = trim((string)($_POST['end_year'][$idx] ?? ''));
            $startStr = trim($sMonth . ' ' . $sYear);
            $endStr = $eMonth === 'Masih Berjalan' ? 'Masih Berjalan' : trim($eMonth . ' ' . $eYear);
            $period = ($startStr !== '' || $endStr !== '') ? trim($startStr . ' - ' . $endStr, ' -') : date('Y');
            $summary = trim((string)($_POST['project_summary'][$idx] ?? ''));
            $portTitle = trim((string)($_POST['portfolio_title'][$idx] ?? ''));
            if ($portTitle === '' && $t !== '') {
                $portTitle = 'Output Proyek: ' . $t;
            }

            $itemFiles = [];
            $urlRows = $_POST['portfolio_file_url'][$idx] ?? [];
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
                        'url' => $fu,
                    ];
                }
            }

            $projects[] = [
                'role' => $t,
                'project' => $comp !== '' ? $comp : $t,
                'company' => $comp,
                'period' => $period,
                'start_month' => $sMonth,
                'start_year' => $sYear,
                'end_month' => $eMonth,
                'end_year' => $eYear,
                'summary' => $summary,
                'output_title' => $portTitle,
                'files' => $itemFiles,
                'is_siapkerja' => $isSk,
            ];

            if ($itemFiles !== []) {
                $portfolios[] = [
                    'id' => 'port-' . uniqid(),
                    'title' => $portTitle !== '' ? $portTitle : ($t . ' Output'),
                    'type' => 'Proyek Deliverable',
                    'deliverable' => $summary,
                    'url' => $itemFiles[0]['url'] ?? '',
                    'client' => $comp !== '' ? $comp : 'Klien Terverifikasi',
                    'year' => $sYear !== '' ? $sYear : date('Y'),
                    'files' => $itemFiles,
                ];
            }
        }
    }

    if ($bidangKeahlian === '') {
        $errorMessage = 'Pilih Bidang Keahlian utama sebelum mengirim pengajuan.';
    } elseif ($skillsList === []) {
        $errorMessage = 'Isi minimal satu skill dan tingkat keahlian pada kolom Keahlian Spesifik.';
    } elseif ($contactChoice === 'new' && ($contactEmail === '' || $contactWa === '')) {
        $errorMessage = 'Jika memilih kontak baru, email dan nomor WhatsApp wajib diisi.';
    } elseif ($changeSummary === '') {
        $errorMessage = 'Ringkasan perubahan wajib diisi sebelum pengajuan verifikasi.';
    } elseif ($editedField === '' || $reasonCode === '' || $reasonDetail === '') {
        $errorMessage = 'Pilih bidang yang diubah, alasan perubahan, dan jelaskan alasannya pada pop-up verifikasi.';
    } elseif ($hasPendingEdit) {
        $errorMessage = 'Masih ada pengajuan edit profil yang menunggu verifikasi admin. Selesaikan dulu pengajuan sebelumnya.';
    } else {
        $displayName = (string)($existingReg['display_name'] ?? ($siapkerja['nama'] ?? ($worker['name'] ?? $username)));
        $domicile = (string)($existingReg['domicile'] ?? ($siapkerja['lokasi'] ?? ($worker['location'] ?? '')));

        $payload = [
            'bidang_keahlian' => $bidangKeahlian,
            'skills' => $skillsList,
            'contact_choice' => $contactChoice,
            'contact_email' => $contactEmail,
            'contact_wa' => $contactWa,
            'previous_projects' => $projects,
            'portfolio' => $portfolios,
            'video_url' => $videoUrl,
            'social_media' => $socialMedia,
            'display_name' => $displayName,
            'domicile' => $domicile,
            'profile_summary' => $profileSummary,
            'name' => $displayName,
            'title' => $bidangKeahlian,
            'location' => $domicile,
            'email' => $contactEmail,
            'wa' => $contactWa,
            'experience' => $projects,
            'proposal' => $profileSummary,
        ];

        $ok = gig_create_worker_profile_edit_request($workerKey, [
            'worker_email' => (string)($_SESSION['siapkerja_email'] ?? $contactEmail),
            'edited_field' => $editedField,
            'reason_code' => $reasonCode,
            'reason_detail' => $reasonDetail,
            'change_summary' => $changeSummary,
            'proposed_payload' => $payload,
        ]);

        if ($ok) {
            $successMessage = 'Pengajuan edit profil berhasil dikirim. Perubahan akan tampil setelah diverifikasi admin.';
            $hasPendingEdit = true;
            $worker = gig_find_worker($username) ?? gig_find_worker('tessa');
        } else {
            $errorMessage = 'Gagal mengirim pengajuan edit profil ke sistem verifikasi admin.';
        }
    }
}

$months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$pageTitle = 'Edit Profil Saya';
$pageKey = 'profil';
$breadcrumbCurrent = 'Edit Profil Saya';

require __DIR__ . '/includes/worker-layout-start.php';
?>

<style>
.gig-verify-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  z-index: 1200;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}
.gig-verify-modal-card {
  background: #ffffff;
  border-radius: 18px;
  max-width: 680px;
  width: 100%;
  max-height: 88vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
  overflow: hidden;
  animation: gigModalSlideIn .22s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes gigModalSlideIn {
  from { opacity: 0; transform: translateY(12px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.gig-verify-modal-header {
  padding: 16px 22px 14px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: #ffffff;
  flex-shrink: 0;
}
.gig-verify-modal-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 2px 0;
}
.gig-verify-modal-sub {
  font-size: 0.82rem;
  color: #64748b;
  margin: 0;
}
.gig-verify-modal-close {
  width: 32px;
  height: 32px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #64748b;
  font-size: 1.2rem;
  line-height: 1;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all .16s ease;
  flex-shrink: 0;
}
.gig-verify-modal-close:hover {
  background: #f8fafc;
  color: #0f172a;
}
.gig-verify-modal-body {
  padding: 18px 22px;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 14px;
  background: #f8fafc;
}
.gig-modal-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.gig-modal-label {
  font-size: 0.82rem;
  font-weight: 700;
  color: #1e293b;
}
.gig-modal-label .req {
  color: #ef4444;
}
.gig-modal-input {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-family: inherit;
  font-size: 0.86rem;
  background: #ffffff;
  color: #0f172a;
  resize: vertical;
  transition: border-color .16s ease, box-shadow .16s ease;
}
.gig-modal-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}
.gig-modal-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
@media (max-width: 640px) {
  .gig-modal-grid-2 {
    grid-template-columns: 1fr;
  }
}
.gig-radio-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.gig-radio-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  border-radius: 8px;
  font-size: 0.82rem;
  color: #334155;
  font-weight: 600;
  cursor: pointer;
  transition: all .16s ease;
}
.gig-radio-option:hover {
  border-color: #bfdbfe;
  background: #eff6ff;
}
.gig-radio-option input[type="radio"] {
  accent-color: #2563eb;
  flex-shrink: 0;
}
.gig-verify-modal-footer {
  padding: 14px 22px;
  border-top: 1px solid #e2e8f0;
  background: #ffffff;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-shrink: 0;
}
.gig-verify-modal-footer .btn-cancel {
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #475569;
  padding: 9px 16px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.86rem;
  cursor: pointer;
  transition: all .16s ease;
}
.gig-verify-modal-footer .btn-cancel:hover {
  background: #f1f5f9;
  color: #0f172a;
}
.gig-verify-modal-footer .btn-submit {
  border: none;
  background: #2563eb;
  color: #ffffff;
  padding: 9px 20px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.86rem;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
  transition: all .16s ease;
}
.gig-verify-modal-footer .btn-submit:hover {
  background: #1d4ed8;
}
</style>

<div class="page-toolbar">
  <div>
    <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin-bottom:4px;">Edit Profil Gig Worker</h1>
    <p style="font-size:0.88rem;color:#64748b;">Perubahan profil akan diverifikasi admin sebelum ditayangkan.</p>
  </div>
  <a class="btn-action-sm" href="worker-profile.php">← Kembali ke Profil Saya</a>
</div>

<?php if ($successMessage !== ''): ?>
  <div style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:14px 18px;border-radius:10px;font-size:0.9rem;font-weight:700;margin-bottom:20px;">
    ✓ <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
  </div>
<?php endif; ?>

<?php if ($errorMessage !== ''): ?>
  <div style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:14px 18px;border-radius:10px;font-size:0.9rem;font-weight:700;margin-bottom:20px;">
    ✕ <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
  </div>
<?php endif; ?>

<?php if ($hasPendingEdit): ?>
  <div style="background:#eff6ff;color:#1e3a8a;border:1px solid #bfdbfe;padding:14px 18px;border-radius:10px;font-size:0.88rem;font-weight:600;margin-bottom:20px;">
    Pengajuan edit profil Anda sedang menunggu verifikasi admin. Anda tetap bisa mengisi form, namun pengajuan baru belum bisa dikirim sampai pengajuan sebelumnya diproses.
  </div>
<?php endif; ?>

<div class="filter-card" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;box-shadow:0 4px 20px rgba(0,0,0,0.03);max-width:980px;">
  <form method="POST" action="worker-edit-profile.php" id="workerEditForm">
    <div style="margin-bottom:28px;">
      <h2 style="font-size:1.1rem;font-weight:800;color:#0f172a;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;">1. Identitas Perorangan (SIAPKerja)</h2>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div>
          <label style="display:block;font-size:0.82rem;font-weight:700;color:#1e293b;margin-bottom:6px;">Nama Lengkap</label>
          <input type="text" value="<?php echo htmlspecialchars((string)($siapkerja['nama'] ?? $worker['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly style="width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;color:#334155;">
        </div>
        <div>
          <label style="display:block;font-size:0.82rem;font-weight:700;color:#1e293b;margin-bottom:6px;">NIK</label>
          <input type="text" value="<?php echo htmlspecialchars((string)($siapkerja['nik'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly style="width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;color:#334155;">
        </div>
        <div style="grid-column:1 / -1;">
          <label style="display:block;font-size:0.82rem;font-weight:700;color:#1e293b;margin-bottom:6px;">Alamat / Domisili</label>
          <input type="text" value="<?php echo htmlspecialchars((string)($siapkerja['lokasi'] ?? $worker['location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly style="width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;color:#334155;">
        </div>
      </div>
      <div style="margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <label style="display:flex;gap:10px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:10px;padding:12px;cursor:pointer;">
          <input type="radio" name="contact_choice" value="siapkerja" <?php echo $currentContactChoice === 'siapkerja' ? 'checked' : ''; ?> onclick="toggleContactChoice()">
          <span style="font-size:0.84rem;color:#1e3a8a;"><strong>Pakai Kontak SIAPKerja</strong><br>Email & WA akan mengikuti data SIAPKerja Anda.</span>
        </label>
        <label style="display:flex;gap:10px;border:1px solid #fde68a;background:#fffbeb;border-radius:10px;padding:12px;cursor:pointer;">
          <input type="radio" name="contact_choice" value="new" <?php echo $currentContactChoice === 'new' ? 'checked' : ''; ?> onclick="toggleContactChoice()">
          <span style="font-size:0.84rem;color:#92400e;"><strong>Gunakan Kontak Baru</strong><br>Masukkan email/WA khusus untuk keperluan proyek gig.</span>
        </label>
      </div>
      <div id="newContactFields" style="margin-top:12px;display:<?php echo $currentContactChoice === 'new' ? 'grid' : 'none'; ?>;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:0.82rem;font-weight:700;color:#1e293b;margin-bottom:6px;">Email Kontak Baru</label>
          <input type="email" name="contact_email_new" value="<?php echo htmlspecialchars((string)$currentContactEmail, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;">
        </div>
        <div>
          <label style="display:block;font-size:0.82rem;font-weight:700;color:#1e293b;margin-bottom:6px;">Nomor WhatsApp Baru</label>
          <input type="text" name="contact_wa_new" value="<?php echo htmlspecialchars((string)$currentContactWa, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;">
        </div>
      </div>
    </div>

    <div style="margin-bottom:28px;">
      <h2 style="font-size:1.1rem;font-weight:800;color:#0f172a;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;">2. Media Sosial</h2>
      <div id="socialMediaContainer" style="display:flex;flex-direction:column;gap:10px;">
        <?php foreach ($currentSocialMedia as $smIdx => $sm): ?>
          <div class="social-row" style="display:flex;gap:10px;align-items:center;">
            <select name="social_media_platform[]" style="width:220px;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
              <?php foreach (['LinkedIn','GitHub','Instagram','Twitter / X','YouTube','TikTok','Behance','Dribbble','Website Personal','Lainnya'] as $opt): ?>
                <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string)($sm['platform'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" name="social_media_url[]" value="<?php echo htmlspecialchars((string)($sm['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://... atau username" style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
            <button type="button" onclick="removeRow(this,'social-row')" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;padding:10px 12px;border-radius:8px;">Hapus</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" onclick="addSocialRow()" style="margin-top:10px;border:1px dashed #2563eb;background:#eff6ff;color:#1d4ed8;padding:8px 12px;border-radius:8px;font-weight:700;">+ Tambah Media Sosial</button>
    </div>

    <div style="margin-bottom:28px;">
      <h2 style="font-size:1.1rem;font-weight:800;color:#0f172a;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;">3. Bidang Keahlian &amp; Skill Spesifik</h2>
      <div style="margin-bottom:20px;">
        <label style="display:block;font-size:0.82rem;font-weight:700;color:#1e293b;margin-bottom:6px;">Bidang Keahlian Utama <span style="color:#ef4444;">*</span></label>
        <select name="bidang_keahlian" required style="width:100%;max-width:520px;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
          <option value="">-- Pilih Bidang Keahlian --</option>
          <?php foreach (['Desain UI/UX','Digital Marketing','Pengembangan Website','Pengembangan Aplikasi Mobile','Data Entry & Administrasi','Penulisan Konten','Video Editing & Motion Graphic','Fotografi & Videografi','Penerjemah','Tutor/Instruktur','Customer Service','Lainnya'] as $b): ?>
            <option value="<?php echo htmlspecialchars($b, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $currentBidang === $b ? 'selected' : ''; ?>><?php echo htmlspecialchars($b, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display:flex;justify-content:space-between;align-items:center;font-size:0.82rem;font-weight:700;color:#1e293b;margin-bottom:6px;">
          <span>Skill / Keahlian Spesifik &amp; Tingkat Keahlian <span style="color:#ef4444;">*</span></span>
          <span style="font-size:0.75rem;font-weight:600;color:#64748b;">(Minimal 1 keahlian)</span>
        </label>
        <p style="font-size:0.8rem;color:#64748b;margin:0 0 12px 0;">
          Tentukan keahlian spesifik yang Anda kuasai beserta tingkat penguasaannya (Pemula, Menengah, Mahir, Ahli). Anda dapat menambah lebih dari 1 keahlian.
        </p>

        <div id="skillsEditRepeater" style="display:flex;flex-direction:column;gap:10px;">
          <?php foreach ($currentSkillsList as $sIdx => $sItem): ?>
            <div class="skill-row-item" style="display:flex;gap:10px;align-items:center;">
              <input type="text" name="skill_name[]" placeholder="Nama keahlian (contoh: Figma, React, Copywriting, SQL)" value="<?php echo htmlspecialchars((string)($sItem['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
              <select name="skill_level[]" style="width:160px;min-width:140px;flex-shrink:0;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
                <?php foreach (gig_skill_levels() as $lvlOpt): ?>
                  <option value="<?php echo $lvlOpt; ?>" <?php echo (string)($sItem['level'] ?? '') === $lvlOpt ? 'selected' : ''; ?>>
                    <?php echo $lvlOpt; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="button" onclick="removeSkillEditRow(this)" style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-weight:bold;font-size:1.1rem;flex-shrink:0;" title="Hapus keahlian">✕</button>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:12px;">
          <button type="button" onclick="addSkillEditRow()" style="border:1px dashed #2563eb;background:#eff6ff;color:#1d4ed8;padding:8px 16px;border-radius:8px;font-size:0.84rem;font-weight:700;cursor:pointer;">
            + Tambah Keahlian
          </button>
        </div>
      </div>
    </div>

    <div style="margin-bottom:28px;">
      <h2 style="font-size:1.1rem;font-weight:800;color:#0f172a;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;">4. Pengalaman</h2>
      <div id="projectContainer" style="display:flex;flex-direction:column;gap:14px;">
        <?php foreach ($currentProjects as $idx => $proj): ?>
          <?php $isSk = !empty($proj['is_siapkerja']); ?>
          <div class="project-item" data-idx="<?php echo (int)$idx; ?>" style="border:1px solid <?php echo $isSk ? '#bfdbfe' : '#e2e8f0'; ?>;background:<?php echo $isSk ? '#f8fbff' : '#ffffff'; ?>;border-radius:12px;padding:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
              <strong style="font-size:0.93rem;color:#0f172a;">Pengalaman #<?php echo (int)$idx + 1; ?></strong>
              <?php if ($isSk): ?>
                <span style="font-size:0.74rem;background:#dbeafe;color:#1d4ed8;border:1px solid #93c5fd;padding:3px 8px;border-radius:999px;font-weight:700;">SIAPKerja (Terkunci)</span>
              <?php else: ?>
                <button type="button" onclick="removeProject(this)" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;padding:6px 10px;border-radius:8px;font-weight:700;">Hapus</button>
              <?php endif; ?>
            </div>
            <?php if ($isSk): ?>
              <input type="hidden" name="project_title[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['role'], ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="project_company[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['company'], ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="start_month[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['start_month'], ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="start_year[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['start_year'], ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="end_month[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['end_month'], ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="end_year[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['end_year'], ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="project_summary[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['summary'], ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="portfolio_title[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['output_title'], ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="is_siapkerja[<?php echo (int)$idx; ?>]" value="1">
              <?php foreach (($proj['files'] ?? []) as $f): ?>
                <input type="hidden" name="portfolio_file_url[<?php echo (int)$idx; ?>][]" value="<?php echo htmlspecialchars((string)($f['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              <?php endforeach; ?>
              <div style="font-size:0.84rem;color:#334155;line-height:1.6;">
                <div><strong>Peran/Proyek:</strong> <?php echo htmlspecialchars((string)$proj['role'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div><strong>Perusahaan:</strong> <?php echo htmlspecialchars((string)$proj['company'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div><strong>Periode:</strong> <?php echo htmlspecialchars((string)$proj['period'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div style="margin-top:6px;"><strong>Ringkasan:</strong> <?php echo htmlspecialchars((string)$proj['summary'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            <?php else: ?>
              <input type="hidden" name="is_siapkerja[<?php echo (int)$idx; ?>]" value="">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <input type="text" name="project_title[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['role'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nama Pekerjaan / Proyek" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
                <input type="text" name="project_company[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['company'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nama Perusahaan" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;">
                <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:8px;">
                  <select name="start_month[<?php echo (int)$idx; ?>]" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
                    <option value="">-- Pilih Bulan --</option>
                    <?php foreach ($months as $m): ?><option value="<?php echo htmlspecialchars($m, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string)$proj['start_month'] === $m) ? 'selected' : ''; ?>><?php echo htmlspecialchars($m, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                  </select>
                  <input type="text" name="start_year[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['start_year'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tahun" maxlength="4" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
                </div>
                <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:8px;">
                  <select name="end_month[<?php echo (int)$idx; ?>]" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
                    <option value="">-- Pilih Bulan --</option>
                    <option value="Masih Berjalan" <?php echo ((string)$proj['end_month'] === 'Masih Berjalan') ? 'selected' : ''; ?>>Masih Berjalan</option>
                    <?php foreach ($months as $m): ?><option value="<?php echo htmlspecialchars($m, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string)$proj['end_month'] === $m) ? 'selected' : ''; ?>><?php echo htmlspecialchars($m, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                  </select>
                  <input type="text" name="end_year[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['end_year'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tahun" maxlength="4" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
                </div>
              </div>
              <textarea name="project_summary[<?php echo (int)$idx; ?>]" rows="2" placeholder="Ringkasan tugas/proyek" style="margin-top:10px;width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;font-family:inherit;"><?php echo htmlspecialchars((string)$proj['summary'], ENT_QUOTES, 'UTF-8'); ?></textarea>
              <input type="text" name="portfolio_title[<?php echo (int)$idx; ?>]" value="<?php echo htmlspecialchars((string)$proj['output_title'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nama Output Proyek" style="margin-top:10px;width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
              <div id="file-list-<?php echo (int)$idx; ?>" style="margin-top:8px;display:flex;flex-direction:column;gap:8px;">
                <?php $projFiles = is_array($proj['files'] ?? null) ? $proj['files'] : []; ?>
                <?php if ($projFiles === []): ?>
                  <div class="file-item-row" style="display:flex;gap:8px;">
                    <input type="url" name="portfolio_file_url[<?php echo (int)$idx; ?>][]" placeholder="https://... tautan output proyek" style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
                    <button type="button" onclick="removeFileRow(this)" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;padding:0 10px;border-radius:8px;">&times;</button>
                  </div>
                <?php else: ?>
                  <?php foreach ($projFiles as $f): ?>
                    <div class="file-item-row" style="display:flex;gap:8px;">
                      <input type="url" name="portfolio_file_url[<?php echo (int)$idx; ?>][]" value="<?php echo htmlspecialchars((string)($f['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://... tautan output proyek" style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
                      <button type="button" onclick="removeFileRow(this)" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;padding:0 10px;border-radius:8px;">&times;</button>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <button type="button" onclick="addFileToProject(<?php echo (int)$idx; ?>)" style="margin-top:8px;border:1px dashed #2563eb;background:#eff6ff;color:#1d4ed8;padding:6px 10px;border-radius:8px;font-size:0.8rem;">+ Tambah Link Output Proyek</button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" onclick="addProjectItem()" style="margin-top:10px;border:1px dashed #2563eb;background:#eff6ff;color:#1d4ed8;padding:8px 12px;border-radius:8px;font-weight:700;">+ Tambah Pengalaman</button>
    </div>

    <div style="margin-bottom:28px;">
      <h2 style="font-size:1.1rem;font-weight:800;color:#0f172a;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;">5. Link Video Profil</h2>
      <div id="videoLinksContainer" style="display:flex;flex-direction:column;gap:10px;">
        <?php foreach ($currentVideoUrls as $v): ?>
          <div class="video-row" style="display:flex;gap:10px;align-items:center;">
            <input type="url" name="video_urls[]" value="<?php echo htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://www.youtube.com/watch?v=... / https://www.loom.com/share/..." style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
            <button type="button" onclick="removeRow(this,'video-row')" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;padding:10px 12px;border-radius:8px;">Hapus</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" onclick="addVideoRow()" style="margin-top:10px;border:1px dashed #2563eb;background:#eff6ff;color:#1d4ed8;padding:8px 12px;border-radius:8px;font-weight:700;">+ Tambah Link Video</button>
    </div>

    <div style="display:flex;gap:14px;align-items:center;padding-top:16px;border-top:1px solid #f1f5f9;">
      <button type="button" id="openVerifyModalBtn" style="background:#2563eb;color:#ffffff;border:none;padding:12px 28px;border-radius:8px;font-weight:700;font-size:0.95rem;cursor:pointer;" <?php echo $hasPendingEdit ? 'disabled' : ''; ?>>Kirim untuk Verifikasi Admin</button>
      <a href="worker-profile.php" style="color:#475569;font-weight:600;font-size:0.9rem;text-decoration:none;">Batal</a>
    </div>

    <div id="verifyModal" class="gig-verify-modal-backdrop" style="display:none;" onclick="if(event.target===this) closeVerifyModal();">
      <div class="gig-verify-modal-card">
        <div class="gig-verify-modal-header">
          <div>
            <h3 class="gig-verify-modal-title">Konfirmasi Pengajuan Edit Profil</h3>
            <p class="gig-verify-modal-sub">Admin akan memverifikasi perincian perubahan Anda sebelum ditayangkan.</p>
          </div>
          <button type="button" class="gig-verify-modal-close" onclick="closeVerifyModal()">&times;</button>
        </div>

        <div class="gig-verify-modal-body">
          <div class="gig-modal-field">
            <label class="gig-modal-label">Ringkasan perubahan yang Anda lakukan <span class="req">*</span></label>
            <textarea name="change_summary" id="change_summary" rows="2" placeholder="Contoh: Menambahkan 2 pengalaman kerja terbaru dan memperbarui 1 portofolio proyek." class="gig-modal-input"></textarea>
          </div>

          <div class="gig-modal-grid-2">
            <div class="gig-modal-field">
              <label class="gig-modal-label">Bidang utama yang diubah <span class="req">*</span></label>
              <div class="gig-radio-group">
                <label class="gig-radio-option"><input type="radio" name="edited_field" value="identity_contact"> <span>Identitas & kontak</span></label>
                <label class="gig-radio-option"><input type="radio" name="edited_field" value="skills_bio"> <span>Keahlian & bio</span></label>
                <label class="gig-radio-option"><input type="radio" name="edited_field" value="experience"> <span>Pengalaman kerja</span></label>
                <label class="gig-radio-option"><input type="radio" name="edited_field" value="portfolio"> <span>Portofolio / output proyek</span></label>
                <label class="gig-radio-option"><input type="radio" name="edited_field" value="social_video"> <span>Media sosial & video profil</span></label>
                <label class="gig-radio-option"><input type="radio" name="edited_field" value="multiple_fields"> <span>Lebih dari satu bidang</span></label>
              </div>
            </div>

            <div class="gig-modal-field">
              <label class="gig-modal-label">Alasan edit profil <span class="req">*</span></label>
              <div class="gig-radio-group">
                <label class="gig-radio-option"><input type="radio" name="reason_code" value="latest_work_update"> <span>Menambahkan hasil kerja terbaru</span></label>
                <label class="gig-radio-option"><input type="radio" name="reason_code" value="data_correction"> <span>Koreksi data kurang tepat</span></label>
                <label class="gig-radio-option"><input type="radio" name="reason_code" value="rebranding"> <span>Rebranding / reposisi profil</span></label>
                <label class="gig-radio-option"><input type="radio" name="reason_code" value="admin_request"> <span>Catatan admin/mitra</span></label>
                <label class="gig-radio-option"><input type="radio" name="reason_code" value="other"> <span>Alasan lainnya</span></label>
              </div>
            </div>
          </div>

          <div class="gig-modal-field">
            <label class="gig-modal-label">Penjelasan alasan edit <span class="req">*</span></label>
            <textarea name="reason_detail" rows="2" placeholder="Jelaskan secara singkat alasan kenapa profil perlu diubah." class="gig-modal-input"></textarea>
          </div>
        </div>

        <div class="gig-verify-modal-footer">
          <button type="button" class="btn-cancel" onclick="closeVerifyModal()">Batal</button>
          <button type="submit" class="btn-submit">Kirim Pengajuan</button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
let projectCounter = (() => {
  const cards = document.querySelectorAll('#projectContainer .project-item');
  let max = -1;
  cards.forEach((c) => {
    const idx = parseInt(c.getAttribute('data-idx') || '-1', 10);
    if (idx > max) max = idx;
  });
  return max + 1;
})();

function monthOptions(includeCurrent) {
  let html = '<option value="">-- Pilih Bulan --</option>';
  if (includeCurrent) html += '<option value="Masih Berjalan">Masih Berjalan</option>';
  months.forEach((m) => { html += `<option value="${m}">${m}</option>`; });
  return html;
}

function toggleContactChoice() {
  const checked = document.querySelector('input[name="contact_choice"]:checked');
  const container = document.getElementById('newContactFields');
  container.style.display = checked && checked.value === 'new' ? 'grid' : 'none';
}

function removeRow(btn, className) {
  const row = btn.closest('.' + className);
  const parent = row?.parentElement;
  if (!row || !parent) return;
  if (parent.querySelectorAll('.' + className).length > 1) {
    row.remove();
  } else {
    const input = row.querySelector('input[type="text"], input[type="url"], input[type="email"]');
    if (input) input.value = '';
  }
}

function addSocialRow() {
  const container = document.getElementById('socialMediaContainer');
  const row = document.createElement('div');
  row.className = 'social-row';
  row.style.cssText = 'display:flex;gap:10px;align-items:center;';
  row.innerHTML = `
    <select name="social_media_platform[]" style="width:220px;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
      <option>LinkedIn</option><option>GitHub</option><option>Instagram</option><option>Twitter / X</option>
      <option>YouTube</option><option>TikTok</option><option>Behance</option><option>Dribbble</option>
      <option>Website Personal</option><option>Lainnya</option>
    </select>
    <input type="text" name="social_media_url[]" placeholder="https://... atau username" style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
    <button type="button" onclick="removeRow(this,'social-row')" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;padding:10px 12px;border-radius:8px;">Hapus</button>
  `;
  container.appendChild(row);
}

function addVideoRow() {
  const container = document.getElementById('videoLinksContainer');
  const row = document.createElement('div');
  row.className = 'video-row';
  row.style.cssText = 'display:flex;gap:10px;align-items:center;';
  row.innerHTML = `
    <input type="url" name="video_urls[]" placeholder="https://www.youtube.com/watch?v=... / https://www.loom.com/share/..." style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
    <button type="button" onclick="removeRow(this,'video-row')" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;padding:10px 12px;border-radius:8px;">Hapus</button>
  `;
  container.appendChild(row);
}

function removeProject(btn) {
  const card = btn.closest('.project-item');
  if (card) card.remove();
}

function removeFileRow(btn) {
  const row = btn.closest('.file-item-row');
  const parent = row?.parentElement;
  if (!row || !parent) return;
  if (parent.querySelectorAll('.file-item-row').length > 1) {
    row.remove();
  } else {
    const input = row.querySelector('input');
    if (input) input.value = '';
  }
}

function addFileToProject(idx) {
  const fileList = document.getElementById(`file-list-${idx}`);
  if (!fileList) return;
  const row = document.createElement('div');
  row.className = 'file-item-row';
  row.style.cssText = 'display:flex;gap:8px;';
  row.innerHTML = `
    <input type="url" name="portfolio_file_url[${idx}][]" placeholder="https://... tautan output proyek" style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
    <button type="button" onclick="removeFileRow(this)" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;padding:0 10px;border-radius:8px;">&times;</button>
  `;
  fileList.appendChild(row);
}

function addProjectItem() {
  const idx = projectCounter++;
  const container = document.getElementById('projectContainer');
  const card = document.createElement('div');
  card.className = 'project-item';
  card.setAttribute('data-idx', String(idx));
  card.style.cssText = 'border:1px solid #e2e8f0;background:#ffffff;border-radius:12px;padding:14px;';
  card.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <strong style="font-size:0.93rem;color:#0f172a;">Pengalaman #${idx + 1}</strong>
      <button type="button" onclick="removeProject(this)" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;padding:6px 10px;border-radius:8px;font-weight:700;">Hapus</button>
    </div>
    <input type="hidden" name="is_siapkerja[${idx}]" value="">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
      <input type="text" name="project_title[${idx}]" placeholder="Nama Pekerjaan / Proyek" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
      <input type="text" name="project_company[${idx}]" placeholder="Nama Perusahaan" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;">
      <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:8px;">
        <select name="start_month[${idx}]" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">${monthOptions(false)}</select>
        <input type="text" name="start_year[${idx}]" placeholder="Tahun" maxlength="4" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
      </div>
      <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:8px;">
        <select name="end_month[${idx}]" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">${monthOptions(true)}</select>
        <input type="text" name="end_year[${idx}]" placeholder="Tahun" maxlength="4" style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
      </div>
    </div>
    <textarea name="project_summary[${idx}]" rows="2" placeholder="Ringkasan tugas/proyek" style="margin-top:10px;width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;font-family:inherit;"></textarea>
    <input type="text" name="portfolio_title[${idx}]" placeholder="Nama Output Proyek" style="margin-top:10px;width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
    <div id="file-list-${idx}" style="margin-top:8px;display:flex;flex-direction:column;gap:8px;">
      <div class="file-item-row" style="display:flex;gap:8px;">
        <input type="url" name="portfolio_file_url[${idx}][]" placeholder="https://... tautan output proyek" style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
        <button type="button" onclick="removeFileRow(this)" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;padding:0 10px;border-radius:8px;">&times;</button>
      </div>
    </div>
    <button type="button" onclick="addFileToProject(${idx})" style="margin-top:8px;border:1px dashed #2563eb;background:#eff6ff;color:#1d4ed8;padding:6px 10px;border-radius:8px;font-size:0.8rem;">+ Tambah Link Output Proyek</button>
  `;
  container.appendChild(card);
}

function addSkillEditRow(name = '', level = 'Menengah') {
  const container = document.getElementById('skillsEditRepeater');
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
    <input type="text" name="skill_name[]" placeholder="Nama keahlian (contoh: Figma, React, Copywriting, SQL)" value="${name.replace(/"/g, '&quot;')}" required style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
    <select name="skill_level[]" style="width:160px;min-width:140px;flex-shrink:0;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
      ${optionsHtml}
    </select>
    <button type="button" onclick="removeSkillEditRow(this)" style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-weight:bold;font-size:1.1rem;flex-shrink:0;" title="Hapus keahlian">✕</button>
  `;
  container.appendChild(row);
  const input = row.querySelector('input');
  if (input) input.focus();
}

function removeSkillEditRow(btn) {
  const container = document.getElementById('skillsEditRepeater');
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

function openVerifyModal() {
  const form = document.getElementById('workerEditForm');
  if (!form.reportValidity()) return;
  document.getElementById('verifyModal').style.display = 'flex';
}

function closeVerifyModal() {
  document.getElementById('verifyModal').style.display = 'none';
}

document.getElementById('openVerifyModalBtn').addEventListener('click', openVerifyModal);
document.getElementById('workerEditForm').addEventListener('submit', function (e) {
  const modal = document.getElementById('verifyModal');
  if (modal.style.display !== 'flex') {
    e.preventDefault();
    openVerifyModal();
    return;
  }
  const editedField = document.querySelector('input[name="edited_field"]:checked');
  const reasonCode = document.querySelector('input[name="reason_code"]:checked');
  const reasonDetail = document.querySelector('textarea[name="reason_detail"]');
  const summary = document.getElementById('change_summary');
  if (!summary.value.trim() || !editedField || !reasonCode || !reasonDetail.value.trim()) {
    e.preventDefault();
    alert('Lengkapi ringkasan perubahan, bidang yang diubah, alasan edit, dan penjelasan alasan sebelum mengirim.');
  }
});
toggleContactChoice();
</script>

<?php require __DIR__ . '/includes/worker-layout-end.php'; ?>
