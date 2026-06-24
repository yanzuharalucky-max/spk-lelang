<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';
$messageType = '';
$suggestedPassword = generateStrongPasswordSuggestion(12);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? '');

    if ($name === '' || $email === '' || $password === '' || $role === '') {
        $error = 'Semua field wajib diisi.';
        $messageType = 'empty';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
        $messageType = 'password';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Password harus mengandung huruf besar, huruf kecil, dan angka.';
        $messageType = 'password';
    } elseif (!in_array($role, ['vendor', 'buyer'], true)) {
        $error = 'Role tidak valid.';
        $messageType = 'role';
    } else {
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($checkStmt, 's', $email);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_fetch_assoc($checkResult)) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
            $messageType = 'email';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $subscription = 'free';
            $subscribed = 0;

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users (name, email, password, role, subscribed, subscription)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, 'ssssis', $name, $email, $hashedPassword, $role, $subscribed, $subscription);

            if (mysqli_stmt_execute($stmt)) {
                $success = 'Registrasi berhasil. Akun Anda sudah dibuat dan siap digunakan.';
                $messageType = 'success';

                $_POST['name'] = '';
                $_POST['email'] = '';
                $_POST['role'] = '';
            } else {
                $error = 'Registrasi gagal. Silakan coba lagi.';
                $messageType = 'failed';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="auth-section auth-section-premium">
    <div class="auth-grid-lines"></div>
    <div class="auth-background-glow auth-glow-1"></div>
    <div class="auth-background-glow auth-glow-2"></div>
    <div class="auth-background-glow auth-glow-3"></div>

    <div class="container auth-layout auth-layout-single">
        <div class="auth-card auth-card-premium register-card-wide reveal-up <?= $error ? 'login-has-error' : ''; ?>">
            <div class="auth-card-shine"></div>

            <div class="login-card-top">
                <span class="login-badge">Buat Akun</span>
                <h1>Bergabung dengan Seleno</h1>
                <p>
                    Daftar sebagai vendor atau buyer dengan tampilan registrasi yang lebih rapi,
                    profesional, dan aman.
                </p>
            </div>

            <div class="register-intro-panel">
                <div class="register-intro-item">
                    <span class="register-intro-icon">✓</span>
                    <div>
                        <strong>Registrasi lebih aman</strong>
                        <p>Password disarankan otomatis agar user tidak memakai password lemah.</p>
                    </div>
                </div>

                <div class="register-intro-item">
                    <span class="register-intro-icon">★</span>
                    <div>
                        <strong>Tampilan lebih premium</strong>
                        <p>Popup sukses registrasi dibuat lebih enak dilihat dan lebih profesional.</p>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="login-notice login-notice-error login-notice-<?= e($messageType); ?>">
                    <div class="login-notice-icon">!</div>
                    <div class="login-notice-content">
                        <strong>Registrasi gagal</strong>
                        <span><?= e($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="register-popup-overlay is-active" id="registerPopupOverlay">
                    <div class="register-popup-box register-popup-box-premium">
                        <div class="register-popup-icon">✓</div>
                        <span class="register-popup-badge">Akun Berhasil Dibuat</span>
                        <h3>Registrasi Berhasil</h3>
                        <p><?= e($success); ?></p>

                        <div class="register-popup-actions">
                            <a href="<?= BASE_URL; ?>/auth/login.php" class="btn btn-primary btn-sm">
                                Login Sekarang
                            </a>
                            <button type="button" class="btn btn-outline-dark btn-sm" id="closeRegisterPopup">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form-premium" id="registerForm">
                <label for="name">Nama Lengkap</label>
                <div class="input-shell">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 12.75a4.5 4.5 0 1 0-4.5-4.5 4.5 4.5 0 0 0 4.5 4.5Zm0 1.5c-4.16 0-7.5 2.09-7.5 4.67 0 .32.26.58.58.58h13.84c.32 0 .58-.26.58-.58 0-2.58-3.34-4.67-7.5-4.67Z"></path>
                        </svg>
                    </span>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($_POST['name'] ?? ''); ?>"
                        placeholder="Masukkan nama lengkap Anda"
                        required
                        autocomplete="name"
                    >
                </div>

                <label for="email">Email</label>
                <div class="input-shell">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 6.75h16A1.25 1.25 0 0 1 21.25 8v8A1.25 1.25 0 0 1 20 17.25H4A1.25 1.25 0 0 1 2.75 16V8A1.25 1.25 0 0 1 4 6.75Zm0 1.5a.2.2 0 0 0-.2.2v.16l8.2 5.32 8.2-5.32v-.16a.2.2 0 0 0-.2-.2H4Zm16.2 2.14-7.79 5.06a.75.75 0 0 1-.82 0L3.8 10.39V16c0 .11.09.2.2.2h16c.11 0 .2-.09.2-.2v-5.61Z"></path>
                        </svg>
                    </span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($_POST['email'] ?? ''); ?>"
                        placeholder="Masukkan email Anda"
                        required
                        autocomplete="email"
                    >
                </div>

                <label for="password">Password</label>
                <div class="input-shell input-shell-password">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2.75a4.75 4.75 0 0 0-4.75 4.75v2H6A2.25 2.25 0 0 0 3.75 11.75v7.5A2.25 2.25 0 0 0 6 21.5h12a2.25 2.25 0 0 0 2.25-2.25v-7.5A2.25 2.25 0 0 0 18 9.5h-1.25v-2A4.75 4.75 0 0 0 12 2.75Zm-3.25 6.75v-2a3.25 3.25 0 1 1 6.5 0v2h-6.5Zm3.25 3.25a1.75 1.75 0 0 1 1 3.19V17.5a.75.75 0 0 1-1.5 0v-1.56a1.75 1.75 0 0 1 .5-3.19Z"></path>
                        </svg>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password Anda"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" id="toggleRegisterPassword" aria-label="Tampilkan password">
                        <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 5c5.23 0 9.27 4.11 10.69 6.02a1.63 1.63 0 0 1 0 1.96C21.27 14.89 17.23 19 12 19S2.73 14.89 1.31 12.98a1.63 1.63 0 0 1 0-1.96C2.73 9.11 6.77 5 12 5Zm0 1.5c-4.56 0-8.13 3.57-9.47 5.37-.05.07-.05.16 0 .23C3.87 13.93 7.44 17.5 12 17.5s8.13-3.57 9.47-5.4c.05-.07.05-.16 0-.23C20.13 10.07 16.56 6.5 12 6.5Zm0 2.25A3.25 3.25 0 1 1 8.75 12 3.25 3.25 0 0 1 12 8.75Zm0 1.5A1.75 1.75 0 1 0 13.75 12 1.75 1.75 0 0 0 12 10.25Z"></path>
                        </svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3.53 2.47 2.47 3.53l3 3A13.5 13.5 0 0 0 1.3 11a1.64 1.64 0 0 0 0 2c1.42 1.9 5.47 6 10.7 6a10.9 10.9 0 0 0 4.44-.92l4.03 4.03 1.06-1.06L3.53 2.47ZM12 17.5c-4.57 0-8.14-3.58-9.47-5.39a.2.2 0 0 1 0-.22 16.5 16.5 0 0 1 4-3.98l1.85 1.85A3.23 3.23 0 0 0 8.75 12 3.25 3.25 0 0 0 12 15.25c.41 0 .8-.08 1.15-.21l2.22 2.22A9.54 9.54 0 0 1 12 17.5Zm10.7-5.5a1.64 1.64 0 0 1 0 2 16.44 16.44 0 0 1-3.55 3.58l-1.08-1.08A14.3 14.3 0 0 0 21.47 12a.2.2 0 0 0 0-.22C20.13 9.97 16.56 6.4 12 6.4c-.94 0-1.83.15-2.67.4L8.1 5.57A10.72 10.72 0 0 1 12 5c5.23 0 9.27 4.11 10.7 7Z"></path>
                        </svg>
                    </button>
                </div>

                <div class="register-password-suggestion">
                    <div class="register-password-suggestion-text">
                        <strong>Password yang disarankan:</strong>
                        <span id="suggestedPasswordText"><?= e($suggestedPassword); ?></span>
                    </div>
                    <div class="register-password-suggestion-actions">
                        <button type="button" class="btn btn-outline-dark btn-sm" id="useSuggestedPasswordBtn">
                            Pakai Password Ini
                        </button>
                        <button type="button" class="btn btn-outline-dark btn-sm" id="copySuggestedPasswordBtn">
                            Copy
                        </button>
                    </div>
                    <small>Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol.</small>
                </div>

                <label for="role">Daftar Sebagai</label>
                <div class="input-shell input-shell-select">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7.25 5.75A2.25 2.25 0 0 1 9.5 3.5h5A2.25 2.25 0 0 1 16.75 5.75v1H18A2.25 2.25 0 0 1 20.25 9v8A2.25 2.25 0 0 1 18 19.25H6A2.25 2.25 0 0 1 3.75 17V9A2.25 2.25 0 0 1 6 6.75h1.25v-1Zm1.5 1h6.5v-1a.75.75 0 0 0-.75-.75h-5a.75.75 0 0 0-.75.75v1ZM6 8.25a.75.75 0 0 0-.75.75v1.5h14.5V9a.75.75 0 0 0-.75-.75H6Zm13.75 3.75H5.25V17c0 .41.34.75.75.75h12c.41 0 .75-.34.75-.75v-5Z"></path>
                        </svg>
                    </span>
                    <select id="role" name="role" required>
                        <option value="">-- Pilih Peran --</option>
                        <option value="vendor" <?= (($_POST['role'] ?? '') === 'vendor') ? 'selected' : ''; ?>>Vendor / Mitra</option>
                        <option value="buyer" <?= (($_POST['role'] ?? '') === 'buyer') ? 'selected' : ''; ?>>Buyer / Pencari</option>
                    </select>
                    <span class="select-arrow">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M6.7 9.3a1 1 0 0 1 1.4 0L12 13.2l3.9-3.9a1 1 0 1 1 1.4 1.4l-4.6 4.6a1 1 0 0 1-1.4 0L6.7 10.7a1 1 0 0 1 0-1.4Z"></path>
                        </svg>
                    </span>
                </div>

                <div class="register-role-hint-grid">
                    <div class="register-role-hint">
                        <strong>Vendor / Mitra</strong>
                        <span>Untuk pihak yang menawarkan barang, jasa, pekerjaan, atau pengadaan.</span>
                    </div>
                    <div class="register-role-hint">
                        <strong>Buyer / Pencari</strong>
                        <span>Untuk pihak yang mencari kebutuhan dan memantau listing yang tersedia.</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block login-submit-btn" id="registerSubmitBtn">
                    <span class="btn-text">Daftar Sekarang</span>
                    <span class="btn-loader"></span>
                </button>
            </form>

            <div class="login-links-row">
                <p class="auth-link">
                    Sudah punya akun?
                    <a href="<?= BASE_URL; ?>/auth/login.php">Login di sini</a>
                </p>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('toggleRegisterPassword');
    const registerForm = document.getElementById('registerForm');
    const registerSubmitBtn = document.getElementById('registerSubmitBtn');
    const closeRegisterPopup = document.getElementById('closeRegisterPopup');
    const registerPopupOverlay = document.getElementById('registerPopupOverlay');
    const useSuggestedPasswordBtn = document.getElementById('useSuggestedPasswordBtn');
    const copySuggestedPasswordBtn = document.getElementById('copySuggestedPasswordBtn');
    const suggestedPasswordText = document.getElementById('suggestedPasswordText');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            togglePassword.classList.toggle('is-active', isPassword);
            togglePassword.setAttribute(
                'aria-label',
                isPassword ? 'Sembunyikan password' : 'Tampilkan password'
            );
        });
    }

    if (useSuggestedPasswordBtn && passwordInput && suggestedPasswordText) {
        useSuggestedPasswordBtn.addEventListener('click', function () {
            passwordInput.value = suggestedPasswordText.textContent.trim();
            passwordInput.setAttribute('type', 'text');
            if (togglePassword) {
                togglePassword.classList.add('is-active');
                togglePassword.setAttribute('aria-label', 'Sembunyikan password');
            }
        });
    }

    if (copySuggestedPasswordBtn && suggestedPasswordText) {
        copySuggestedPasswordBtn.addEventListener('click', async function () {
            try {
                await navigator.clipboard.writeText(suggestedPasswordText.textContent.trim());
                copySuggestedPasswordBtn.textContent = 'Tersalin';
                setTimeout(() => {
                    copySuggestedPasswordBtn.textContent = 'Copy';
                }, 1500);
            } catch (error) {
                alert('Gagal menyalin password.');
            }
        });
    }

    if (registerForm && registerSubmitBtn) {
        registerForm.addEventListener('submit', function () {
            registerSubmitBtn.classList.add('is-loading');
            registerSubmitBtn.disabled = true;
        });
    }

    if (closeRegisterPopup && registerPopupOverlay) {
        closeRegisterPopup.addEventListener('click', function () {
            registerPopupOverlay.classList.remove('is-active');
        });

        registerPopupOverlay.addEventListener('click', function (e) {
            if (e.target === registerPopupOverlay) {
                registerPopupOverlay.classList.remove('is-active');
            }
        });
    }

    <?php if ($error): ?>
    if (registerSubmitBtn) {
        registerSubmitBtn.classList.remove('is-loading');
        registerSubmitBtn.disabled = false;
    }
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>