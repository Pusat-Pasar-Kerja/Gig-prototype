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
    $fileFormats = function_exists('gig_special_req_file_formats') ? gig_special_req_file_formats() : [];
    $reqTypeKey = trim((string)($rawSpecial['type'] ?? ''));
    $reqLabel = trim((string)($rawSpecial['label'] ?? ''));
    if ($reqTypeKey !== '' && isset($reqTypes[$reqTypeKey]) && $reqLabel !== '') {
        $meta = $reqTypes[$reqTypeKey];
        $chosenInputType = trim((string)($rawSpecial['input_type'] ?? ''));

        $chosenKey = strtolower($chosenInputType);
        if ($chosenKey === 'pdf' || in_array($chosenKey, ['file_pdf', 'pdf_only'], true)) {
            $inputMode = 'file';
            $fileType = 'pdf';
            $fileTypeLabel = 'PDF';
            $accept = '.pdf';
            $formatHint = 'PDF (.pdf)';
            $workerHint = 'Unggah berkas dalam format PDF (.pdf, maks. 5 MB).';
            $allowedExts = ['pdf'];
        } elseif (in_array($chosenKey, ['image', 'jpg', 'jpeg', 'png', 'file_image', 'image_only'], true)) {
            $inputMode = 'file';
            $fileType = 'image';
            $fileTypeLabel = 'Gambar (JPG, JPEG, PNG)';
            $accept = '.jpg,.jpeg,.png';
            $formatHint = 'Gambar (JPG, JPEG, PNG)';
            $workerHint = 'Unggah gambar dalam format JPG, JPEG, atau PNG (maks. 5 MB).';
            $allowedExts = ['jpg', 'jpeg', 'png'];
        } elseif ($chosenKey === 'doc' || in_array($chosenKey, ['docx', 'file_any', 'any_doc'], true)) {
            $inputMode = 'file';
            $fileType = 'doc';
            $fileTypeLabel = 'DOC / DOCX';
            $accept = '.doc,.docx';
            $formatHint = 'DOC / DOCX (.doc, .docx)';
            $workerHint = 'Unggah dokumen dalam format Word (.doc, .docx, maks. 5 MB).';
            $allowedExts = ['doc', 'docx'];
        } elseif ($chosenKey === 'url') {
            $inputMode = 'url';
            $fileType = null;
            $fileTypeLabel = null;
            $accept = '';
            $formatHint = 'Link / URL';
            $workerHint = 'Masukkan tautan / URL yang valid.';
            $allowedExts = null;
        } elseif ($chosenKey === 'text' || $chosenKey === 'textarea') {
            $inputMode = 'text';
            $fileType = null;
            $fileTypeLabel = null;
            $accept = '';
            $formatHint = 'Text';
            $workerHint = 'Masukkan teks atau informasi singkat yang diminta.';
            $allowedExts = null;
        } elseif (in_array($chosenKey, ['file', 'file_pdf_image', 'pdf_image'], true)) {
            $inputMode = 'file';
            $fileType = 'pdf';
            $fileTypeLabel = 'PDF';
            $accept = '.pdf';
            $formatHint = 'PDF (.pdf)';
            $workerHint = 'Unggah berkas dalam format PDF (.pdf, maks. 5 MB).';
            $allowedExts = ['pdf'];
        } else {
            $inputMode = (string)($meta['input_mode'] ?? 'file');
            if ($inputMode === 'url') {
                $fileType = null;
                $fileTypeLabel = null;
                $accept = '';
                $formatHint = 'Link / URL';
                $workerHint = 'Masukkan tautan / URL yang valid.';
                $allowedExts = null;
            } elseif ($inputMode === 'textarea' || $inputMode === 'text') {
                $fileType = null;
                $fileTypeLabel = null;
                $accept = '';
                $formatHint = 'Text';
                $workerHint = 'Masukkan teks atau informasi singkat yang diminta.';
                $allowedExts = null;
            } else {
                $inputMode = 'file';
                $fileType = 'pdf';
                $fileTypeLabel = 'PDF';
                $accept = '.pdf';
                $formatHint = 'PDF (.pdf)';
                $workerHint = 'Unggah berkas dalam format PDF (.pdf, maks. 5 MB).';
                $allowedExts = ['pdf'];
            }
        }

        $customType = trim((string)($rawSpecial['custom_type'] ?? ''));
        $typeLabel = ($reqTypeKey === 'other' && $customType !== '') ? $customType : (string)$meta['label'];

        $specialReq = [
            'type'            => $reqTypeKey,
            'custom_type'     => $customType,
            'type_label'      => $typeLabel,
            'label'           => $reqLabel,
            'input_type'      => $chosenInputType !== '' ? $chosenInputType : ($inputMode === 'file' ? 'pdf' : $inputMode),
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
