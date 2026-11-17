<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employer') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/includes/vacancy-store.php';

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$title = trim((string)($payload['title'] ?? ''));
if ($title === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Judul proyek wajib diisi.']);
    exit;
}

$employer = (string)($_SESSION['username'] ?? 'Employer');
$budgetRaw = trim((string)($payload['budget'] ?? ''));
$showSalary = !empty($payload['show_salary']);
$budgetMin = (int)($payload['budget_min'] ?? ($payload['salary'] ?? 0));
$budget = 'Nilai proyek dapat dinegosiasikan';
if ($showSalary) {
    if ($budgetMin > 0) {
        $budget = 'Rp ' . number_format($budgetMin, 0, ',', '.');
    } elseif ($budgetRaw !== '') {
        $budgetSingle = (float)str_replace(['.', ','], '', preg_replace('/[^\d]/', '', $budgetRaw));
        if ($budgetSingle > 0) {
            $budget = 'Rp ' . number_format($budgetSingle, 0, ',', '.');
        }
    }
}

$locType = (string)($payload['location_type'] ?? 'luring');
$locDetail = trim((string)($payload['location_detail'] ?? ''));
$locCity = trim((string)($payload['location_city'] ?? ''));
$locProvince = trim((string)($payload['location_province'] ?? ''));
$location = $locDetail !== '' ? $locDetail : trim($locCity . ', ' . $locProvince, ', ');
if ($location === '' || strcasecmp($location, 'Lokasi belum diisi') === 0) {
    $location = gig_random_location();
}
if ($locType === 'remote' && !str_contains(strtolower($location), 'remote')) {
    $location .= ' (Remote)';
}

$specialReq = null;
$rawSpecial = $payload['special_requirement'] ?? null;
if (is_array($rawSpecial)) {
    $reqTypes = gig_special_requirement_types();
    $reqTypeKey = trim((string)($rawSpecial['type'] ?? ''));
    $reqLabel = trim((string)($rawSpecial['label'] ?? ''));
    if ($reqTypeKey !== '' && isset($reqTypes[$reqTypeKey]) && $reqLabel !== '') {
        $meta = $reqTypes[$reqTypeKey];
        $inputMode = (string)($meta['input_mode'] ?? 'text');
        $accept = (string)($meta['accept'] ?? '');
        $formatHint = (string)($meta['format_hint'] ?? '');
        $workerHint = (string)($meta['worker_hint'] ?? '');
        $fileType = null;
        $fileTypeLabel = null;
        $allowedExts = null;

        if ($inputMode === 'file') {
            $fileFormats = gig_special_req_file_formats();
            $chosenFormat = trim((string)($rawSpecial['file_type'] ?? 'pdf_image'));
            if (!isset($fileFormats[$chosenFormat])) {
                $chosenFormat = 'pdf_image';
            }
            $fMeta = $fileFormats[$chosenFormat];
            $fileType = $chosenFormat;
            $fileTypeLabel = $fMeta['label'];
            $accept = $fMeta['accept'];
            $formatHint = $fMeta['format_hint'];
            $workerHint = $fMeta['worker_hint'];
            $allowedExts = $fMeta['allowed_exts'];
        }

        $specialReq = [
            'type'            => $reqTypeKey,
            'type_label'      => $meta['label'],
            'label'           => $reqLabel,
            'input_mode'      => $inputMode,
            'file_type'       => $fileType,
            'file_type_label' => $fileTypeLabel,
            'accept'          => $accept,
            'format_hint'     => $formatHint,
            'worker_hint'     => $workerHint,
            'allowed_exts'    => $allowedExts,
        ];
    }
}

$vacancy = [
    'title' => $title,
    'category' => (string)($payload['category'] ?? ''),
    'kbji' => (string)($payload['kbji'] ?? ''),
    'duration' => (string)($payload['duration'] ?? ''),
    'desc' => (string)($payload['desc'] ?? ''),
    'deliverables' => (string)($payload['target'] ?? ''),
    'qualifications' => (string)($payload['qualifications'] ?? ''),
    'work_type' => $locType === 'remote' ? 'Remote' : 'On-site/Hybrid',
    'workType' => $locType === 'remote' ? 'Remote' : 'On-site/Hybrid',
    'is_remote' => ($locType === 'remote'),
    'industry' => (string)($payload['industry'] ?? ''),
    'experience_level' => (string)($payload['experience_level'] ?? ''),
    'visibility' => (string)($payload['visibility'] ?? 'public'),
    'quota' => 1,
    'deadline' => (string)($payload['deadline'] ?? ''),
    'location' => $location,
    'location_city' => $locCity,
    'location_province' => $locProvince,
    'budget' => $budget,
    'skills' => array_filter(array_map('trim', explode(',', (string)($payload['skills'] ?? '')))),
    'special_requirement' => $specialReq,
];

$id = gig_vacancy_save_submission($employer, $vacancy);
if ($id === null) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Gagal menyimpan pengajuan.']);
    exit;
}

echo json_encode(['ok' => true, 'id' => $id, 'message' => 'Lowongan diajukan untuk verifikasi Admin.']);
