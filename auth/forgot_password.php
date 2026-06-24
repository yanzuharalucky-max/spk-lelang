<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Email wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, email FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            $userId = (int) $user['id'];
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmtDelete = mysqli_prepare($conn, "DELETE FROM password_resets WHERE user_id = ?");
            mysqli_stmt_bind_param($stmtDelete, 'i', $userId);
            mysqli_stmt_execute($stmtDelete);

            $stmtInsert = mysqli_prepare(
                $conn,
                "INSERT INTO password_resets (user_id, email, token, expires_at)
                 VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmtInsert, 'isss', $userId, $email, $token, $expiresAt);

            if (mysqli_stmt_execute($stmtInsert)) {
                $success = 'Permintaan reset akun telah berhasil dibuat.';
                $resetLink = BASE_URL . '/auth/reset_password.php?token=' . $token;
                $_POST['email'] = '';
            } else {
                $error = 'Gagal membuat permintaan reset password.';
            }
        } else {
            $error = 'Email tidak ditemukan.';
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
        <div class="auth-card auth-card-premium forgot-card-premium reveal-up <?= $error ? 'login-has-error login-shake' : ''; ?>">
            <div class="auth-card-shine"></div>

            <div class="login-card-top">
                <span class="login-badge">Pemulihan Akun</span>
                <h1>Lupa Password</h1>
                <p>
                    Masukkan email akun Anda untuk membuat permintaan reset password
                    secara aman, cepat, dan profesional.
                </p>
            </div>

            <div class="forgot-intro-panel">
                <div class="forgot-intro-item">
                    <span class="forgot-intro-icon">🔒</span>
                    <div>
                        <strong>Reset aman</strong>
                        <p>Permintaan reset dibuat dengan token khusus yang memiliki batas waktu.</p>
                    </div>
                </div>
                <div class="forgot-intro-item">
                    <span class="forgot-intro-icon">⚡</span>
                    <div>
                        <strong>Proses cepat</strong>
                        <p>Anda bisa langsung lanjut ke halaman reset password untuk pengujian localhost.</p>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="login-notice login-notice-error">
                    <div class="login-notice-icon">!</div>
                    <div class="login-notice-content">
                        <strong>Permintaan gagal</strong>
                        <span><?= e($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="login-notice login-notice-success">
                    <div class="login-notice-icon success-icon">✓</div>
                    <div class="login-notice-content">
                        <strong>Permintaan berhasil dibuat</strong>
                        <span><?= e($success); ?></span>
                    </div>
                </div>

                <div class="reset-success-card reset-success-card-premium">
                    <div class="reset-success-head">
                        <div class="reset-success-icon">✓</div>
                        <div>
                            <h2>Reset Siap Dilanjutkan</h2>
                            <p>
                                Untuk versi localhost, Anda bisa langsung lanjut ke halaman
                                reset password melalui tombol di bawah ini.
                            </p>
                        </div>
                    </div>

                    <div class="reset-success-meta">
                        <div class="reset-meta-item">
                            <strong>Status</strong>
                            <span>Link reset berhasil dibuat</span>
                        </div>
                        <div class="reset-meta-item">
                            <strong>Masa berlaku</strong>
                            <span>1 jam sejak dibuat</span>
                        </div>
                    </div>

                    <div class="reset-success-actions">
                        <a href="<?= e($resetLink); ?>" class="btn btn-primary btn-block">Lanjut ke Reset Password</a>
                        <a href="<?= BASE_URL; ?>/auth/login.php" class="btn btn-outline btn-block btn-outline-light-auth">Kembali ke Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" action="" class="login-form-premium" id="forgotForm">
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
                            placeholder="Masukkan email akun Anda"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <p class="auth-helper-text">
                        Gunakan email yang terdaftar pada akun Seleno Anda.
                    </p>

                    <button type="submit" class="btn btn-primary btn-block login-submit-btn" id="forgotSubmitBtn">
                        <span class="btn-text">Buat Link Reset</span>
                        <span class="btn-loader"></span>
                    </button>
                </form>

                <div class="login-links-row forgot-links-row">
                    <p class="auth-link">
                        Sudah ingat password?
                        <a href="<?= BASE_URL; ?>/auth/login.php">Kembali ke Login</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const forgotForm = document.getElementById('forgotForm');
    const forgotSubmitBtn = document.getElementById('forgotSubmitBtn');

    if (forgotForm && forgotSubmitBtn) {
        forgotForm.addEventListener('submit', function () {
            forgotSubmitBtn.classList.add('is-loading');
            forgotSubmitBtn.disabled = true;
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>