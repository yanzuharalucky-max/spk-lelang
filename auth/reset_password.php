<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($token === '') {
    redirect('/auth/login.php');
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM password_resets
     WHERE token = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$resetData = mysqli_fetch_assoc($result);

if (!$resetData) {
    $error = 'Token reset password tidak valid.';
} elseif (strtotime($resetData['expires_at']) < time()) {
    $error = 'Token reset password sudah kadaluarsa.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($password === '' || $confirmPassword === '') {
        $error = 'Password baru wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userId = (int) $resetData['user_id'];

        $stmtUpdate = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmtUpdate, 'si', $hashedPassword, $userId);

        if (mysqli_stmt_execute($stmtUpdate)) {
            $stmtDelete = mysqli_prepare($conn, "DELETE FROM password_resets WHERE user_id = ?");
            mysqli_stmt_bind_param($stmtDelete, 'i', $userId);
            mysqli_stmt_execute($stmtDelete);

            $success = 'Password berhasil diperbarui. Silakan login.';
        } else {
            $error = 'Gagal memperbarui password.';
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
        <div class="auth-card auth-card-premium reset-card-premium reveal-up <?= $error ? 'login-has-error login-shake' : ''; ?>">
            <div class="auth-card-shine"></div>

            <div class="login-card-top">
                <span class="login-badge">Reset Akses</span>
                <h1>Reset Password</h1>
                <p>
                    Masukkan password baru untuk akun Anda agar akses dapat digunakan kembali
                    dengan aman, cepat, dan profesional.
                </p>
            </div>

            <?php if (!$success && (!$error || $resetData)): ?>
                <div class="reset-intro-panel">
                    <div class="reset-intro-item">
                        <span class="reset-intro-icon">🔐</span>
                        <div>
                            <strong>Password baru aman</strong>
                            <p>Gunakan kombinasi huruf dan angka agar akun lebih terlindungi.</p>
                        </div>
                    </div>
                    <div class="reset-intro-item">
                        <span class="reset-intro-icon">⚡</span>
                        <div>
                            <strong>Reset cepat</strong>
                            <p>Setelah disimpan, Anda bisa langsung login dengan password terbaru.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="login-notice login-notice-error">
                    <div class="login-notice-icon">!</div>
                    <div class="login-notice-content">
                        <strong>Reset gagal</strong>
                        <span><?= e($error); ?></span>
                    </div>
                </div>

                <?php if (!$resetData || (isset($resetData['expires_at']) && strtotime($resetData['expires_at']) < time())): ?>
                    <div class="reset-invalid-card">
                        <div class="reset-invalid-icon">!</div>
                        <div class="reset-invalid-content">
                            <h2>Link Reset Tidak Dapat Digunakan</h2>
                            <p>
                                Token reset sudah tidak valid atau sudah kadaluarsa.
                                Silakan buat permintaan reset password baru untuk melanjutkan.
                            </p>
                        </div>

                        <div class="reset-success-actions">
                            <a href="<?= BASE_URL; ?>/auth/forgot_password.php" class="btn btn-primary btn-block">Buat Permintaan Baru</a>
                            <a href="<?= BASE_URL; ?>/auth/login.php" class="btn btn-outline btn-block btn-outline-light-auth">Kembali ke Login</a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="login-notice login-notice-success">
                    <div class="login-notice-icon success-icon">✓</div>
                    <div class="login-notice-content">
                        <strong>Password berhasil diperbarui</strong>
                        <span><?= e($success); ?></span>
                    </div>
                </div>

                <div class="reset-success-card reset-success-card-premium">
                    <div class="reset-success-head">
                        <div class="reset-success-icon">✓</div>
                        <div>
                            <h2>Password Baru Sudah Aktif</h2>
                            <p>
                                Akun Anda sudah siap digunakan kembali. Silakan lanjut login
                                menggunakan password terbaru.
                            </p>
                        </div>
                    </div>

                    <div class="reset-success-meta">
                        <div class="reset-meta-item">
                            <strong>Status</strong>
                            <span>Password berhasil diperbarui</span>
                        </div>
                        <div class="reset-meta-item">
                            <strong>Langkah berikutnya</strong>
                            <span>Login ulang dengan password baru</span>
                        </div>
                    </div>

                    <div class="reset-success-actions">
                        <a href="<?= BASE_URL; ?>/auth/login.php" class="btn btn-primary btn-block">Login Sekarang</a>
                    </div>
                </div>
            <?php elseif (!$error || $resetData): ?>
                <form method="POST" action="" class="login-form-premium" id="resetPasswordForm">
                    <input type="hidden" name="token" value="<?= e($token); ?>">

                    <label for="password">Password Baru</label>
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
                            placeholder="Masukkan password baru"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" id="toggleNewPassword" aria-label="Tampilkan password baru">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 5c5.23 0 9.27 4.11 10.69 6.02a1.63 1.63 0 0 1 0 1.96C21.27 14.89 17.23 19 12 19S2.73 14.89 1.31 12.98a1.63 1.63 0 0 1 0-1.96C2.73 9.11 6.77 5 12 5Zm0 1.5c-4.56 0-8.13 3.57-9.47 5.37-.05.07-.05.16 0 .23C3.87 13.93 7.44 17.5 12 17.5s8.13-3.57 9.47-5.4c.05-.07.05-.16 0-.23C20.13 10.07 16.56 6.5 12 6.5Zm0 2.25A3.25 3.25 0 1 1 8.75 12 3.25 3.25 0 0 1 12 8.75Zm0 1.5A1.75 1.75 0 1 0 13.75 12 1.75 1.75 0 0 0 12 10.25Z"></path>
                            </svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3.53 2.47 2.47 3.53l3 3A13.5 13.5 0 0 0 1.3 11a1.64 1.64 0 0 0 0 2c1.42 1.9 5.47 6 10.7 6a10.9 10.9 0 0 0 4.44-.92l4.03 4.03 1.06-1.06L3.53 2.47ZM12 17.5c-4.57 0-8.14-3.58-9.47-5.39a.2.2 0 0 1 0-.22 16.5 16.5 0 0 1 4-3.98l1.85 1.85A3.23 3.23 0 0 0 8.75 12 3.25 3.25 0 0 0 12 15.25c.41 0 .8-.08 1.15-.21l2.22 2.22A9.54 9.54 0 0 1 12 17.5Zm10.7-5.5a1.64 1.64 0 0 1 0 2 16.44 16.44 0 0 1-3.55 3.58l-1.08-1.08A14.3 14.3 0 0 0 21.47 12a.2.2 0 0 0 0-.22C20.13 9.97 16.56 6.4 12 6.4c-.94 0-1.83.15-2.67.4L8.1 5.57A10.72 10.72 0 0 1 12 5c5.23 0 9.27 4.11 10.7 7Z"></path>
                            </svg>
                        </button>
                    </div>

                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <div class="input-shell input-shell-password">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2.75a4.75 4.75 0 0 0-4.75 4.75v2H6A2.25 2.25 0 0 0 3.75 11.75v7.5A2.25 2.25 0 0 0 6 21.5h12a2.25 2.25 0 0 0 2.25-2.25v-7.5A2.25 2.25 0 0 0 18 9.5h-1.25v-2A4.75 4.75 0 0 0 12 2.75Zm-3.25 6.75v-2a3.25 3.25 0 1 1 6.5 0v2h-6.5Zm3.25 3.25a1.75 1.75 0 0 1 1 3.19V17.5a.75.75 0 0 1-1.5 0v-1.56a1.75 1.75 0 0 1 .5-3.19Z"></path>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Ulangi password baru"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" id="toggleConfirmPassword" aria-label="Tampilkan konfirmasi password">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 5c5.23 0 9.27 4.11 10.69 6.02a1.63 1.63 0 0 1 0 1.96C21.27 14.89 17.23 19 12 19S2.73 14.89 1.31 12.98a1.63 1.63 0 0 1 0-1.96C2.73 9.11 6.77 5 12 5Zm0 1.5c-4.56 0-8.13 3.57-9.47 5.37-.05.07-.05.16 0 .23C3.87 13.93 7.44 17.5 12 17.5s8.13-3.57 9.47-5.4c.05-.07.05-.16 0-.23C20.13 10.07 16.56 6.5 12 6.5Zm0 2.25A3.25 3.25 0 1 1 8.75 12 3.25 3.25 0 0 1 12 8.75Zm0 1.5A1.75 1.75 0 1 0 13.75 12 1.75 1.75 0 0 0 12 10.25Z"></path>
                            </svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3.53 2.47 2.47 3.53l3 3A13.5 13.5 0 0 0 1.3 11a1.64 1.64 0 0 0 0 2c1.42 1.9 5.47 6 10.7 6a10.9 10.9 0 0 0 4.44-.92l4.03 4.03 1.06-1.06L3.53 2.47ZM12 17.5c-4.57 0-8.14-3.58-9.47-5.39a.2.2 0 0 1 0-.22 16.5 16.5 0 0 1 4-3.98l1.85 1.85A3.23 3.23 0 0 0 8.75 12 3.25 3.25 0 0 0 12 15.25c.41 0 .8-.08 1.15-.21l2.22 2.22A9.54 9.54 0 0 1 12 17.5Zm10.7-5.5a1.64 1.64 0 0 1 0 2 16.44 16.44 0 0 1-3.55 3.58l-1.08-1.08A14.3 14.3 0 0 0 21.47 12a.2.2 0 0 0 0-.22C20.13 9.97 16.56 6.4 12 6.4c-.94 0-1.83.15-2.67.4L8.1 5.57A10.72 10.72 0 0 1 12 5c5.23 0 9.27 4.11 10.7 7Z"></path>
                            </svg>
                        </button>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block login-submit-btn" id="resetSubmitBtn">
                        <span class="btn-text">Simpan Password Baru</span>
                        <span class="btn-loader"></span>
                    </button>
                </form>

                <div class="login-links-row">
                    <p class="auth-link">
                        <a href="<?= BASE_URL; ?>/auth/login.php">Kembali ke Login</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const resetForm = document.getElementById('resetPasswordForm');
    const resetSubmitBtn = document.getElementById('resetSubmitBtn');

    const togglePasswordField = (toggleId, inputId, labelShow, labelHide) => {
        const toggleBtn = document.getElementById(toggleId);
        const input = document.getElementById(inputId);

        if (toggleBtn && input) {
            toggleBtn.addEventListener('click', function () {
                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                toggleBtn.classList.toggle('is-active', isPassword);
                toggleBtn.setAttribute('aria-label', isPassword ? labelHide : labelShow);
            });
        }
    };

    togglePasswordField(
        'toggleNewPassword',
        'password',
        'Tampilkan password baru',
        'Sembunyikan password baru'
    );

    togglePasswordField(
        'toggleConfirmPassword',
        'confirm_password',
        'Tampilkan konfirmasi password',
        'Sembunyikan konfirmasi password'
    );

    if (resetForm && resetSubmitBtn) {
        resetForm.addEventListener('submit', function () {
            resetSubmitBtn.classList.add('is-loading');
            resetSubmitBtn.disabled = true;
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>