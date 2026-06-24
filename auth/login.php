<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$errorType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
        $errorType = 'empty';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            $isPasswordValid = false;

            if (password_verify($password, $user['password'])) {
                $isPasswordValid = true;
            } elseif ($password === $user['password']) {
                $isPasswordValid = true;
            }

            if ($isPasswordValid) {
                $_SESSION['user'] = $user;

                if ($user['role'] === 'admin') {
                    redirect('/admin/admin.php');
                } elseif ($user['role'] === 'vendor') {
                    redirect('/vendor/dashboard.php');
                } else {
                    redirect('/buyer/dashboard.php');
                }
            } else {
                $error = 'Password yang Anda masukkan salah. Silakan cek kembali.';
                $errorType = 'password';
            }
        } else {
            $error = 'Akun tidak terdaftar. Silakan daftar terlebih dahulu.';
            $errorType = 'account';
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
        <div class="auth-card auth-card-premium reveal-up <?= $error ? 'login-has-error' : ''; ?>">
            <div class="auth-card-shine"></div>

            <div class="login-card-top">
                <span class="login-badge">Akses Akun</span>
                <h1>Selamat Datang Kembali</h1>
                <p>
                    Masuk ke akun Anda untuk mengelola atau mencari listing
                    secara aman, cepat, dan profesional.
                </p>
            </div>



            <?php if ($error): ?>
                <div class="login-notice login-notice-error login-notice-<?= e($errorType); ?>">
                    <div class="login-notice-icon">!</div>
                    <div class="login-notice-content">
                        <strong>Login gagal</strong>
                        <span><?= e($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error && $errorType === 'account'): ?>
                <div class="login-inline-action">
                    <a href="<?= BASE_URL; ?>/auth/register.php" class="btn btn-outline-dark btn-sm">
                        Daftar Sekarang
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        alert("<?= e($error); ?>");
                    });
                </script>
            <?php endif; ?>

            <form method="POST" action="" class="login-form-premium" id="loginForm">
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
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle" id="togglePassword" aria-label="Tampilkan password">
                        <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 5c5.23 0 9.27 4.11 10.69 6.02a1.63 1.63 0 0 1 0 1.96C21.27 14.89 17.23 19 12 19S2.73 14.89 1.31 12.98a1.63 1.63 0 0 1 0-1.96C2.73 9.11 6.77 5 12 5Zm0 1.5c-4.56 0-8.13 3.57-9.47 5.37-.05.07-.05.16 0 .23C3.87 13.93 7.44 17.5 12 17.5s8.13-3.57 9.47-5.4c.05-.07.05-.16 0-.23C20.13 10.07 16.56 6.5 12 6.5Zm0 2.25A3.25 3.25 0 1 1 8.75 12 3.25 3.25 0 0 1 12 8.75Zm0 1.5A1.75 1.75 0 1 0 13.75 12 1.75 1.75 0 0 0 12 10.25Z"></path>
                        </svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3.53 2.47 2.47 3.53l3 3A13.5 13.5 0 0 0 1.3 11a1.64 1.64 0 0 0 0 2c1.42 1.9 5.47 6 10.7 6a10.9 10.9 0 0 0 4.44-.92l4.03 4.03 1.06-1.06L3.53 2.47ZM12 17.5c-4.57 0-8.14-3.58-9.47-5.39a.2.2 0 0 1 0-.22 16.5 16.5 0 0 1 4-3.98l1.85 1.85A3.23 3.23 0 0 0 8.75 12 3.25 3.25 0 0 0 12 15.25c.41 0 .8-.08 1.15-.21l2.22 2.22A9.54 9.54 0 0 1 12 17.5Zm10.7-5.5a1.64 1.64 0 0 1 0 2 16.44 16.44 0 0 1-3.55 3.58l-1.08-1.08A14.3 14.3 0 0 0 21.47 12a.2.2 0 0 0 0-.22C20.13 9.97 16.56 6.4 12 6.4c-.94 0-1.83.15-2.67.4L8.1 5.57A10.72 10.72 0 0 1 12 5c5.23 0 9.27 4.11 10.7 7Z"></path>
                        </svg>
                    </button>
                </div>

                <button type="submit" class="btn btn-primary btn-block login-submit-btn" id="loginSubmitBtn">
                    <span class="btn-text">Login</span>
                    <span class="btn-loader"></span>
                </button>
            </form>

            <div class="login-links-row">
                <p class="auth-link">
                    Belum punya akun?
                    <a href="<?= BASE_URL; ?>/auth/register.php">Daftar sekarang</a>
                </p>

                <p class="auth-link">
                    <a href="<?= BASE_URL; ?>/auth/forgot_password.php">Lupa password?</a>
                </p>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const loginForm = document.getElementById('loginForm');
    const loginSubmitBtn = document.getElementById('loginSubmitBtn');
    const loginNotice = document.querySelector('.login-notice');

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

    if (loginForm && loginSubmitBtn) {
        loginForm.addEventListener('submit', function () {
            loginSubmitBtn.classList.add('is-loading');
            loginSubmitBtn.disabled = true;
        });
    }

    if (loginNotice) {
        loginNotice.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        loginNotice.classList.add('is-visible');

        setTimeout(() => {
            loginNotice.classList.remove('is-visible');
        }, 4200);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>