<?php
declare(strict_types=1);
session_start();

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'Gig');

$message = "";
$messageType = "";

$redirectParam = trim((string)($_GET['redirect'] ?? $_POST['redirect'] ?? ''));

$hasSiapkerjaSession = !empty($_SESSION['siapkerja_email']) || !empty($_SESSION['username']);

if ($redirectParam === 'worker-register' && $hasSiapkerjaSession) {
    header("Location: worker-register.php");
    exit;
}
if ($redirectParam === 'employer-register' && $hasSiapkerjaSession) {
    header("Location: employer-register.php");
    exit;
}
if (($redirectParam === 'karirhub-home' || $redirectParam === 'gig-workers' || $redirectParam === 'admin-dashboard') && $hasSiapkerjaSession) {
    if ($redirectParam === 'gig-workers') {
        header("Location: gig-workers-go.php");
        exit;
    }
    if ($redirectParam === 'admin-dashboard') {
        header('Location: admin-dashboard-go.php');
        exit;
    }
    header("Location: karirhub-home.php");
    exit;
}

if ($hasSiapkerjaSession && !isset($_GET['preview'])) {
    if ($redirectParam === 'worker-register') {
        header("Location: worker-register.php");
        exit;
    }
    if ($redirectParam === 'employer-register') {
        header("Location: employer-register.php");
        exit;
    }
    if ($redirectParam === 'gig-workers') {
        header("Location: gig-workers-go.php");
        exit;
    }
    if ($redirectParam === 'admin-dashboard') {
        header('Location: admin-dashboard-go.php');
        exit;
    }
    if ($redirectParam === '' || $redirectParam === 'karirhub-home') {
        header("Location: karirhub-home.php");
        exit;
    }
}

$pdo = null;

try {
    $serverDsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $serverPdo = new PDO($serverDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2
    ]);
    $serverPdo->exec(
        "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` " .
        "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );

    $databaseDsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($databaseDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2
    ]);

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `Login` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('worker', 'employer') NOT NULL DEFAULT 'worker'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    try {
        $pdo->exec("ALTER TABLE `Login` ADD COLUMN `role` ENUM('worker', 'employer') NOT NULL DEFAULT 'worker'");
    } catch (Throwable $e) {
        // Ignore if column exists
    }

    $workerPasswordHash = password_hash("12345", PASSWORD_DEFAULT);
    $employerPasswordHash = password_hash("00000", PASSWORD_DEFAULT);
    
     // Seed canonical accounts only and clean up duplicate legacy accounts
    $seedStmt = $pdo->prepare(
        "INSERT INTO `Login` (`username`, `password`, `role`) VALUES 
         ('theressaz@pasker.id', :pass1, 'worker'),
         ('employer@pasker.id', :pass2, 'employer'),
         ('pencaker@pasker.id', :pass1, 'worker'),
         ('calon.employer@pasker.id', :pass2, 'unregistered')
         ON DUPLICATE KEY UPDATE `role` = VALUES(`role`), `password` = VALUES(`password`)"
    );
    $seedStmt->execute([
        ":pass1" => $workerPasswordHash,
        ":pass2" => $employerPasswordHash
    ]);

    // Delete obsolete duplicate legacy logins
    $pdo->exec("DELETE FROM `Login` WHERE `username` IN ('Tessa', 'PT ABC', 'Perusahaan', 'tessa')");

} catch (Throwable $e) {
    $pdo = null;
}

// Handle Login Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usernameInput = trim((string)($_POST["username"] ?? ""));
    $passwordInput = (string)($_POST["password"] ?? "");

    if ($usernameInput === "") {
        $message = "Silakan isi email atau nomor handphone.";
        $messageType = "error";
    } else {
        $userFound = false;
        $userRole = null;
        $lowerInput = strtolower($usernameInput);
        $resolvedUsername = "Theressa Zaratrusha";
        $userEmail = $usernameInput;
        
        // 1. Account Mapping requested by USER:
        // Unregistered SIAPkerja Employer: calon.employer@pasker.id / Pemberi Kerja
        if ($lowerInput === 'calon.employer@pasker.id' || $lowerInput === 'calon.employer' || $lowerInput === 'pemberi kerja' || str_contains($lowerInput, 'calon')) {
            $userFound = true;
            $userRole = 'unregistered';
            $resolvedUsername = 'Pemberi Kerja';
            $userEmail = 'calon.employer@pasker.id';
        }
        // Gig Worker Account: theressaz@pasker.id / tessa
        elseif ($lowerInput === 'theressaz@pasker.id' || $lowerInput === 'tessa' || str_contains($lowerInput, 'theressaz')) {
            $userFound = true;
            $userRole = 'worker';
            $resolvedUsername = 'Theressa Zaratrusha';
            $userEmail = 'theressaz@pasker.id';
        }
        // Employer Account: employer@pasker.id / pt abc
        elseif ($lowerInput === 'employer@pasker.id' || $lowerInput === 'employer' || $lowerInput === 'pt abc' || $lowerInput === 'ptabc') {
            $userFound = true;
            $userRole = 'employer';
            $resolvedUsername = 'PT ABC';
            $userEmail = 'employer@pasker.id';
        }
        // Admin Kemnaker: admin@kemnaker.go.id / admin123
        elseif ($lowerInput === 'admin@kemnaker.go.id' || $lowerInput === 'admin') {
            if ($passwordInput !== 'admin123') {
                $message = 'Kata sandi admin tidak valid.';
                $messageType = 'error';
                $userFound = false;
            } else {
                $userFound = true;
                $userRole = 'admin';
                $resolvedUsername = 'Admin KarirHub';
                $userEmail = 'admin@kemnaker.go.id';
            }
        }
        // Unregistered SIAPkerja User: pencaker@pasker.id
        elseif ($lowerInput === 'pencaker@pasker.id' || str_contains($lowerInput, 'pencaker')) {
            $userFound = true;
            $userRole = 'unregistered';
            $resolvedUsername = 'Pencaker';
            $userEmail = 'pencaker@pasker.id';
        }
        else {
            $userFound = true;
            $userRole = 'unregistered';
            $userEmail = filter_var($usernameInput, FILTER_VALIDATE_EMAIL) ? $usernameInput : (strtolower(str_replace(' ', '', $usernameInput)) . "@pasker.id");
            $resolvedUsername = ucwords(explode('@', $usernameInput)[0]);
        }

        if ($userFound) {
            // 2. Populate SIAPkerja Session Data
            $_SESSION['siapkerja_email'] = $userEmail;
            $_SESSION['siapkerja_name'] = $resolvedUsername;
            $_SESSION['siapkerja_nik'] = '1471 0252 0803 0001';
            $_SESSION['siapkerja_phone'] = '08117671208';
            $_SESSION['username'] = $resolvedUsername;

            if ($userRole === 'employer') {
                $_SESSION['role'] = 'employer';
                $_SESSION['company_registered'] = true;
                unset($_SESSION['admin_name']);
            } elseif ($userRole === 'worker') {
                $_SESSION['role'] = 'worker';
                unset($_SESSION['admin_name']);
            } elseif ($userRole === 'admin') {
                $_SESSION['role'] = 'admin';
                $_SESSION['admin_name'] = 'Admin KarirHub';
                $_SESSION['username'] = 'admin@kemnaker.go.id';
                unset($_SESSION['company_registered']);
            } elseif ($lowerInput === 'calon.employer@pasker.id' || str_contains($lowerInput, 'calon') || str_contains($lowerInput, 'pemberi kerja')) {
                unset($_SESSION['role'], $_SESSION['admin_name']);
            } else {
                unset($_SESSION['role'], $_SESSION['admin_name']);
            }

            require_once __DIR__ . '/includes/user-avatars.php';
            if ($userRole === 'employer' || $userRole === 'admin') {
                gig_bootstrap_user_avatar($userEmail, 'employer');
            } elseif ($userRole === 'worker') {
                gig_bootstrap_user_avatar($userEmail, 'worker');
            }

            // 3. IF coming from Registration Flow, route to requested registration page!
            if ($redirectParam === 'employer-register') {
                header('Location: employer-register.php');
                exit;
            }
            if ($redirectParam === 'worker-register') {
                header('Location: worker-register.php');
                exit;
            }
            if ($redirectParam === 'admin-dashboard') {
                header('Location: admin-dashboard-go.php');
                exit;
            }

            // 4. Default: land on Karirhub home after SIAPkerja login
            header('Location: karirhub-home.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke SIAPkerja ID</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #ffffff;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            overflow-x: hidden;
        }
        button:disabled,
        input[type="button"]:disabled,
        input[type="submit"]:disabled,
        [role="button"][aria-disabled="true"] {
            background: #cbd5e1 !important;
            color: #64748b !important;
            border-color: #cbd5e1 !important;
            box-shadow: none !important;
            cursor: not-allowed !important;
            opacity: 1 !important;
            pointer-events: none;
        }

        .siapkerja-header-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 40px;
        }

        .main-wrapper {
            flex: 1;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            border: 1px solid #f1f5f9;
            width: 100%;
            max-width: 440px;
            padding: 36px 32px;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .card-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 24px;
        }

        .btn-teal {
            background-color: #09bda4;
            color: #ffffff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            padding: 13px 20px;
            border-radius: 8px;
            border: none;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-teal:hover {
            background-color: #07a690;
        }

        .card-link-wrapper {
            margin-top: 20px;
            font-size: 14px;
            color: #64748b;
        }
        .card-link-wrapper a {
            color: #09bda4;
            text-decoration: none;
            font-weight: 600;
        }
        .card-link-wrapper a:hover {
            text-decoration: underline;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .form-label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .form-label-row .form-label {
            margin-bottom: 0;
        }
        .link-teal {
            color: #09bda4;
            font-size: 13px;
            text-decoration: none;
            font-weight: 600;
        }
        .link-teal:hover {
            text-decoration: underline;
        }

        .input-control {
            width: 100%;
            background-color: #eef5fc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 14px;
            color: #0f172a;
            outline: none;
            transition: all 0.2s ease;
        }
        .input-control:focus {
            background-color: #ffffff;
            border-color: #09bda4;
            box-shadow: 0 0 0 3px rgba(9, 189, 164, 0.15);
        }

        .password-wrapper {
            position: relative;
        }
        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Step 2 Vector Graphic Background */
        .siapkerja-bg-wrapper {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            overflow: hidden;
            padding: 0 5%;
        }

        .bg-vector-side {
            opacity: 0.85;
            max-width: 320px;
        }

        .page-footer {
            padding: 24px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
            width: 100%;
        }

        .error-message-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
            text-align: left;
        }

        @media (max-width: 640px) {
            .auth-card {
                padding: 28px 20px;
            }
            .bg-vector-side {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- SCREEN 2: MASUK KE SIAPKERJA ID (Form Login) -->
    <header class="siapkerja-header-brand">
        <svg width="28" height="28" viewBox="0 0 36 36" fill="none">
            <circle cx="12" cy="12" r="6" fill="#09bda4"/>
            <circle cx="24" cy="12" r="6" fill="#09bda4"/>
            <circle cx="18" cy="24" r="6" fill="#09bda4"/>
        </svg>
        <span style="font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px;">
            SIAP<span style="color: #09bda4;">kerja</span> <span style="font-weight: 700; color: #09bda4;">ID</span>
        </span>
    </header>

    <main class="main-wrapper">
        <div class="siapkerja-bg-wrapper">
            <!-- Left Vector Illustration -->
            <svg class="bg-vector-side" viewBox="0 0 350 400" fill="none">
                <path d="M40 320 Q 150 200 280 340" stroke="#cbd5e1" stroke-width="20" stroke-linecap="round" fill="none" opacity="0.4"/>
                <rect x="50" y="240" width="80" height="50" rx="6" fill="#0284c7" opacity="0.8"/>
                <rect x="56" y="246" width="68" height="38" rx="3" fill="#e0f2fe"/>
                <path d="M40 292 H 140" stroke="#0369a1" stroke-width="6" stroke-linecap="round"/>
                <path d="M160 160 L200 220 L240 180 L220 280" stroke="#0284c7" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="160" cy="160" r="14" fill="#0369a1"/>
                <circle cx="200" cy="220" r="10" fill="#0284c7"/>
                <rect x="70" y="300" width="70" height="45" rx="4" fill="#ffffff" stroke="#09bda4" stroke-width="2"/>
                <path d="M80 315 H120 M80 325 H110" stroke="#cbd5e1" stroke-width="3" stroke-linecap="round"/>
                <circle cx="125" cy="330" r="8" fill="#10b981"/>
            </svg>

            <!-- Right Vector Illustration -->
            <svg class="bg-vector-side" viewBox="0 0 350 400" fill="none">
                <rect x="180" y="170" width="100" height="80" rx="10" fill="#0369a1"/>
                <path d="M210 170 V155 Q 210 145 230 145 Q 250 145 250 155 V170" stroke="#0369a1" stroke-width="6" fill="none"/>
                <rect x="220" y="195" width="20" height="15" rx="3" fill="#38bdf8"/>
                <rect x="240" y="280" width="80" height="50" rx="6" fill="#0284c7"/>
                <path d="M250 260 Q 265 240 280 260" stroke="#22c55e" stroke-width="14" stroke-linecap="round"/>
                <rect x="260" y="295" width="40" height="20" rx="3" fill="#ffffff"/>
                <text x="272" y="310" fill="#0284c7" font-size="11" font-weight="bold">Rp</text>
                <path d="M280 130 C 310 130 330 150 330 185 L 300 200 L 280 185 Z" fill="#1e293b"/>
                <rect x="295" y="150" width="25" height="12" rx="2" fill="#38bdf8"/>
            </svg>
        </div>

        <div class="auth-card">
            <h1 class="card-title">Masuk</h1>
            
            <?php if ($message !== ""): ?>
                <div class="error-message-box"><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></div>
            <?php endif; ?>

            <form method="POST" action="siapkerja-login.php">
                <?php if ($redirectParam !== ""): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectParam, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="username">Email atau nomor handphone</label>
                    <input type="text" id="username" name="username" class="input-control" placeholder="theressaz@pasker.id" required>
                </div>

                <div class="form-group">
                    <div class="form-label-row">
                        <label class="form-label" for="password">Password</label>
                        <a href="#" class="link-teal">Kendala Masuk ke Akun?</a>
                    </div>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="input-control" placeholder="••••••••••••" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility()" aria-label="Toggle Password">
                            <svg id="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-teal">Masuk</button>

                <div class="card-link-wrapper" style="margin-top: 20px;">
                    Belum memiliki akun? <a href="#" style="color: #09bda4;">Daftar Sekarang</a>
                </div>

                <div style="margin-top: 28px;">
                    <a href="#" style="color: #475569; font-size: 13.5px; text-decoration: none; font-weight: 500;">Kunjungi Pusat Bantuan</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="page-footer">
        ©2026 Kemnaker RI
    </footer>

    <script>
        function togglePasswordVisibility() {
            const pwdInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (!pwdInput) return;
            
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            } else {
                pwdInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                `;
            }
        }
    </script>
</body>
</html>
