<?php
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Berhasil - <?= APP_NAME; ?></title>
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css?v=<?= time(); ?>">
    <meta http-equiv="refresh" content="2;url=<?= BASE_URL; ?>/index.php">
</head>
<body>

<section class="auth-section auth-section-premium logout-section-premium">
    <div class="auth-grid-lines"></div>
    <div class="auth-background-glow auth-glow-1"></div>
    <div class="auth-background-glow auth-glow-2"></div>
    <div class="auth-background-glow auth-glow-3"></div>

    <div class="container auth-layout auth-layout-single">
        <div class="auth-card auth-card-premium logout-card-premium reveal-up">
            <div class="auth-card-shine"></div>

            <div class="logout-success-icon-wrap">
                <div class="logout-success-ring"></div>
                <div class="logout-success-icon">✓</div>
            </div>

            <div class="login-card-top logout-card-top">
                <span class="login-badge">Logout Berhasil</span>
                <h1>Anda Berhasil Logout</h1>
                <p>
                    Sesi Anda telah diakhiri dengan aman.
                    Anda akan diarahkan kembali ke halaman beranda dalam beberapa saat.
                </p>
            </div>

            <div class="logout-status-panel">
                <div class="logout-status-item">
                    <strong>Status</strong>
                    <span>Sesi berhasil dihapus</span>
                </div>
                <div class="logout-status-item">
                    <strong>Redirect</strong>
                    <span>Beranda dalam 2 detik</span>
                </div>
            </div>

            <div class="logout-loader-wrap">
                <div class="logout-loader"></div>
                <span>Mengalihkan ke beranda...</span>
            </div>

            <div class="logout-actions">
                <a href="<?= BASE_URL; ?>/index.php" class="btn btn-primary btn-block">Ke Beranda Sekarang</a>
                <a href="<?= BASE_URL; ?>/auth/login.php" class="btn btn-outline btn-block btn-outline-light-auth">Login Kembali</a>
            </div>
        </div>
    </div>
</section>

<script>
setTimeout(function () {
    window.location.href = '<?= BASE_URL; ?>/index.php';
}, 2000);
</script>

</body>
</html>