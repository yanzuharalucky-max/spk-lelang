<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('getCurrentLang')) {
    function getCurrentLang(): string
    {
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'id';
        $lang = in_array($lang, ['id', 'en'], true) ? $lang : 'id';
        $_SESSION['lang'] = $lang;
        return $lang;
    }
}

if (!function_exists('langUrl')) {
    function langUrl(string $path, array $params = [], string $anchor = ''): string
    {
        $lang = getCurrentLang();
        $params = array_merge(['lang' => $lang], $params);
        $query = http_build_query($params);
        $url = BASE_URL . $path;

        if ($query !== '') {
            $url .= '?' . $query;
        }

        if ($anchor !== '') {
            $url .= '#' . ltrim($anchor, '#');
        }

        return $url;
    }
}

if (!function_exists('switchLangUrl')) {
    function switchLangUrl(string $targetLang): string
    {
        $targetLang = in_array($targetLang, ['id', 'en'], true) ? $targetLang : 'id';
        $params = $_GET;
        $params['lang'] = $targetLang;

        $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
        $query = http_build_query($params);

        return $path . ($query ? '?' . $query : '');
    }
}

$currentLang = getCurrentLang();

$translations = [
    'id' => [
        'app_name' => APP_NAME,
        'brand_title' => 'Saleno Lelang',
        'brand_subtitle' => 'Mitra Lelang Swasta',
        'nav_home' => 'Beranda',
        'nav_listing' => 'Listing',
        'nav_login' => 'Login',
        'nav_register' => 'Daftar',
        'nav_logout' => 'Logout',
        'nav_admin' => 'Admin Panel',
        'nav_verify_payment' => 'Verifikasi Payment',
        'nav_vendor_dashboard' => 'Dashboard Vendor',
        'nav_buyer_dashboard' => 'Dashboard Buyer',
        'nav_hello' => 'Halo, ',
        'footer_brand_title' => 'Saleno Lelang',
        'footer_brand_desc' => 'Platform mitra lelang swasta untuk barang dan jasa dari perusahaan, agensi, sekolah, dan lembaga berbadan hukum.',
        'footer_nav_title' => 'Navigasi',
        'footer_nav_home' => 'Beranda',
        'footer_nav_listing' => 'Listing',
        'footer_nav_login' => 'Login',
        'footer_nav_register' => 'Register',
        'footer_contact_title' => 'Kontak',
        'footer_address' => '18 Office Park, 21st Floor, Unit 21H, Kebagusan, Pasar Minggu, Jakarta Selatan 12520',
        'footer_copyright' => 'Saleno Lelang. All rights reserved.',
    ],
    'en' => [
        'app_name' => 'Saleno Auction',
        'brand_title' => 'Saleno Auction',
        'brand_subtitle' => 'Private Auction Partner',
        'nav_home' => 'Home',
        'nav_listing' => 'Listings',
        'nav_login' => 'Login',
        'nav_register' => 'Register',
        'nav_logout' => 'Logout',
        'nav_admin' => 'Admin Panel',
        'nav_verify_payment' => 'Verify Payment',
        'nav_vendor_dashboard' => 'Vendor Dashboard',
        'nav_buyer_dashboard' => 'Buyer Dashboard',
        'nav_hello' => 'Hello, ',
        'footer_brand_title' => 'Saleno Auction',
        'footer_brand_desc' => 'A private auction partner platform for goods and services from companies, agencies, schools, and legal institutions.',
        'footer_nav_title' => 'Navigation',
        'footer_nav_home' => 'Home',
        'footer_nav_listing' => 'Listings',
        'footer_nav_login' => 'Login',
        'footer_nav_register' => 'Register',
        'footer_contact_title' => 'Contact',
        'footer_address' => '18 Office Park, 21st Floor, Unit 21H, Kebagusan, Pasar Minggu, South Jakarta 12520',
        'footer_copyright' => 'Saleno Auction. All rights reserved.',
    ],
];

if (!function_exists('t')) {
    function t(string $key): string
    {
        global $translations, $currentLang;
        return $translations[$currentLang][$key] ?? $translations['id'][$key] ?? $key;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($currentLang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('app_name')); ?></title>
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css?v=<?= time(); ?>">
</head>
<body>

<header class="site-header">
    <div class="nav-container">
        <a class="brand" href="<?= langUrl('/index.php'); ?>">
            <span class="brand-mark-wrap">
                <img src="<?= BASE_URL; ?>/assets/img/logo-saleno.png" alt="<?= e(t('brand_title')); ?>" class="brand-logo">
            </span>

            <div class="brand-group">
                <span class="brand-title"><?= e(t('brand_title')); ?></span>
                <span class="brand-subtitle"><?= e(t('brand_subtitle')); ?></span>
            </div>
        </a>

        <nav class="nav-menu">
            <a href="<?= langUrl('/index.php'); ?>"><?= e(t('nav_home')); ?></a>
            <a href="<?= langUrl('/index.php', [], 'listing-section'); ?>"><?= e(t('nav_listing')); ?></a>

            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="<?= langUrl('/admin/admin.php'); ?>"><?= e(t('nav_admin')); ?></a>
                    <a href="<?= langUrl('/admin/subscriptions.php'); ?>"><?= e(t('nav_verify_payment')); ?></a>
                <?php endif; ?>

                <?php if (isVendor()): ?>
                    <a href="<?= langUrl('/vendor/dashboard.php'); ?>"><?= e(t('nav_vendor_dashboard')); ?></a>
                <?php endif; ?>

                <?php if (isBuyer()): ?>
                    <a href="<?= langUrl('/buyer/dashboard.php'); ?>"><?= e(t('nav_buyer_dashboard')); ?></a>
                <?php endif; ?>

                <span class="nav-user"><?= e(t('nav_hello') . currentUser()['name']); ?></span>
                <a class="btn btn-outline" href="<?= langUrl('/auth/logout.php'); ?>"><?= e(t('nav_logout')); ?></a>
            <?php else: ?>
                <a href="<?= langUrl('/auth/login.php'); ?>"><?= e(t('nav_login')); ?></a>
                <a class="btn btn-primary" href="<?= langUrl('/auth/register.php'); ?>"><?= e(t('nav_register')); ?></a>
            <?php endif; ?>

            <div class="nav-language-switch">
                <a href="<?= e(switchLangUrl('id')); ?>" class="lang-link <?= $currentLang === 'id' ? 'is-active' : ''; ?>">ID</a>
                <span class="lang-separator">|</span>
                <a href="<?= e(switchLangUrl('en')); ?>" class="lang-link <?= $currentLang === 'en' ? 'is-active' : ''; ?>">EN</a>
            </div>
        </nav>
    </div>
</header>

<main class="main-content">