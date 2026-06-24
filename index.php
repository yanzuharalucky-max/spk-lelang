<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$keyword = trim($_GET['keyword'] ?? '');

if ($keyword !== '') {
    $search = '%' . $keyword . '%';
    $stmt = mysqli_prepare(
        $conn,
        "SELECT listings.*, users.name AS vendor_name
         FROM listings
         JOIN users ON listings.user_id = users.id
         WHERE listings.status = 'approved'
           AND (
                listings.title LIKE ?
                OR listings.category LIKE ?
                OR listings.description LIKE ?
                OR listings.location LIKE ?
           )
         ORDER BY listings.created_at DESC"
    );
    mysqli_stmt_bind_param($stmt, 'ssss', $search, $search, $search, $search);
} else {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT listings.*, users.name AS vendor_name
         FROM listings
         JOIN users ON listings.user_id = users.id
         WHERE listings.status = 'approved'
         ORDER BY listings.created_at DESC"
    );
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$totalListings = mysqli_query($conn, "SELECT COUNT(*) AS total FROM listings WHERE status = 'approved'");
$totalListings = mysqli_fetch_assoc($totalListings)['total'] ?? 0;

$totalVendors = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='vendor'");
$totalVendors = mysqli_fetch_assoc($totalVendors)['total'] ?? 0;

$totalBuyers = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='buyer'");
$totalBuyers = mysqli_fetch_assoc($totalBuyers)['total'] ?? 0;

include __DIR__ . '/includes/header.php';

$translations['id'] = array_merge($translations['id'], [
    'hero_badge' => 'Platform Mitra Lelang Swasta',
    'hero_title_1' => 'Temukan Barang & Jasa',
    'hero_title_2' => 'Lelang Lebih Cepat,',
    'hero_title_3' => 'Lebih Rapi, dan',
    'hero_title_4' => 'Lebih Profesional',
    'hero_desc' => 'Platform modern untuk perusahaan, agensi, sekolah, dan lembaga berbadan hukum yang ingin memposting barang atau jasa secara profesional, terpercaya, dan mudah ditemukan oleh para pencari lelang.',
    'hero_search_placeholder' => 'Cari barang, jasa, kategori, atau lokasi...',
    'hero_search_button' => 'Cari Sekarang',
    'hero_join' => 'Gabung Sekarang',
    'hero_view_listing' => 'Lihat Listing',
    'hero_stat_listing' => 'Listing Aktif',
    'hero_stat_vendor' => 'Vendor Terverifikasi',
    'hero_stat_access' => 'Akses Platform',
    'hero_why_title' => 'Kenapa Saleno?',
    'hero_why_1' => 'Vendor dapat memposting listing setelah subscription aktif',
    'hero_why_2' => 'Pencari lelang dapat menemukan kebutuhan dengan keyword',
    'hero_why_3' => 'Detail penuh hanya tersedia untuk pengguna premium',
    'hero_why_4' => 'Tampilan profesional membangun kepercayaan customer',

    'stats_total_listing' => 'Total Listing',
    'stats_total_vendor' => 'Total Vendor',
    'stats_total_buyer' => 'Total Buyer',
    'stats_access' => 'Akses Platform',
    'stats_desc_listing' => 'Data listing publik yang tersedia di platform.',
    'stats_desc_vendor' => 'Vendor aktif yang siap memposting kebutuhan lelang.',
    'stats_desc_buyer' => 'Pengguna yang mencari barang dan jasa melalui platform.',
    'stats_desc_access' => 'Layanan dapat diakses kapan saja secara online.',

    'trust_badge' => 'Kepercayaan Platform',
    'trust_title' => 'Didesain untuk Tampilan Bisnis yang Lebih Kredibel',
    'trust_desc' => 'Saleno membantu vendor tampil lebih profesional dan membantu buyer menemukan kebutuhan lelang secara lebih cepat, rapi, dan terpercaya.',
    'trust_1_title' => 'Vendor Terkurasi',
    'trust_1_desc' => 'Listing berasal dari akun vendor yang terdaftar dan dapat dikelola secara profesional.',
    'trust_2_title' => 'Pencarian Cepat',
    'trust_2_desc' => 'Buyer dapat mencari kebutuhan berdasarkan keyword, kategori, dan lokasi secara efisien.',
    'trust_3_title' => 'Akses Premium',
    'trust_3_desc' => 'Detail penuh dapat dibuka untuk pengguna yang sudah berlangganan sesuai kebutuhan bisnis.',
    'trust_4_title' => 'UI Profesional',
    'trust_4_desc' => 'Tampilan modern meningkatkan kepercayaan terhadap vendor maupun platform secara keseluruhan.',

    'features_badge' => 'Fitur Utama',
    'features_title' => 'Dirancang untuk Vendor dan Pencari Lelang',
    'features_desc' => 'Platform mitra lelang swasta untuk barang dan jasa dari perusahaan, agensi, sekolah, dan lembaga berbadan hukum.',
    'features_1_title' => 'Posting Listing Profesional',
    'features_1_desc' => 'Vendor dapat menampilkan barang dan jasa dengan informasi yang rapi, menarik, dan terpercaya.',
    'features_2_title' => 'Pencarian Berbasis Keyword',
    'features_2_desc' => 'Pencari tinggal memasukkan kata kunci untuk menemukan barang atau jasa yang dibutuhkan.',
    'features_3_title' => 'Akses Detail Premium',
    'features_3_desc' => 'Informasi sensitif dan data penuh hanya terbuka untuk pengguna yang sudah subscribe.',

    'workflow_badge' => 'Cara Kerja',
    'workflow_title' => 'Alur Saleno yang Sederhana dan Efektif',
    'workflow_desc' => 'Proses yang jelas memudahkan vendor mempublikasikan kebutuhan dan buyer menemukan peluang terbaik.',
    'workflow_1_title' => 'Daftar & Aktivasi',
    'workflow_1_desc' => 'Vendor membuat akun dan mengaktifkan subscription untuk mulai menggunakan platform.',
    'workflow_2_title' => 'Posting Kebutuhan',
    'workflow_2_desc' => 'Barang atau jasa dipublikasikan dengan detail yang rapi agar mudah dicari buyer.',
    'workflow_3_title' => 'Ditemukan Buyer',
    'workflow_3_desc' => 'Pembeli dapat menelusuri listing dan menemukan kebutuhan lewat pencarian cepat.',
    'workflow_4_title' => 'Akses Premium',
    'workflow_4_desc' => 'Informasi penuh dibuka sesuai hak akses premium untuk kebutuhan bisnis yang lebih serius.',

    'listing_badge' => 'Data Listing',
    'listing_search_result' => 'Hasil Pencarian',
    'listing_latest' => 'Listing Terbaru',
    'listing_search_text' => 'Menampilkan hasil untuk keyword: ',
    'listing_default_text' => 'Berikut beberapa data barang dan jasa yang tersedia di platform.',
    'listing_be_vendor' => 'Jadi Vendor',
    'listing_vendor_label' => 'Vendor: ',
    'listing_detail' => 'Lihat Detail',
    'listing_empty_title' => 'Belum ada listing yang disetujui',
    'listing_empty_desc' => 'Saat ini belum ada data publik yang tersedia.',
    'listing_label_featured' => 'Pilihan Platform',
    'listing_featured_title' => 'Tampilan Listing Lebih Profesional',
    'listing_featured_desc' => 'Setiap listing ditampilkan dengan desain yang lebih rapi, informatif, dan meyakinkan agar vendor terlihat lebih kredibel di mata calon buyer.',
    'listing_stat_1' => 'Data aktif',
    'listing_stat_2' => 'Vendor siap tayang',
    'listing_stat_3' => 'Akses cepat',
    'listing_browse' => 'Lihat Semua',
    'listing_search_hint' => 'Cari cepat berdasarkan judul, kategori, dan lokasi.',

    'cta_badge' => 'Mulai Lebih Profesional',
    'cta_title' => 'Bangun Listing Bisnis yang Lebih Meyakinkan di Saleno',
    'cta_desc' => 'Tampilkan barang dan jasa dengan tampilan yang lebih rapi, kredibel, dan mudah ditemukan oleh calon pencari lelang.',
    'cta_register' => 'Daftar Sekarang',
    'cta_view' => 'Lihat Listing',

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
]);

$translations['en'] = array_merge($translations['en'], [
    'hero_badge' => 'Private Auction Partner Platform',
    'hero_title_1' => 'Find Goods & Services',
    'hero_title_2' => 'Auction Faster,',
    'hero_title_3' => 'More Organized, and',
    'hero_title_4' => 'More Professional',
    'hero_desc' => 'A modern platform for companies, agencies, schools, and legal institutions that want to post goods or services professionally, reliably, and be easily discovered by auction seekers.',
    'hero_search_placeholder' => 'Search goods, services, category, or location...',
    'hero_search_button' => 'Search Now',
    'hero_join' => 'Join Now',
    'hero_view_listing' => 'View Listings',
    'hero_stat_listing' => 'Active Listings',
    'hero_stat_vendor' => 'Verified Vendors',
    'hero_stat_access' => 'Platform Access',
    'hero_why_title' => 'Why Saleno?',
    'hero_why_1' => 'Vendors can post listings after subscription is active',
    'hero_why_2' => 'Auction seekers can find needs through keywords',
    'hero_why_3' => 'Full details are only available for premium users',
    'hero_why_4' => 'Professional design builds customer trust',

    'stats_total_listing' => 'Total Listings',
    'stats_total_vendor' => 'Total Vendors',
    'stats_total_buyer' => 'Total Buyers',
    'stats_access' => 'Platform Access',
    'stats_desc_listing' => 'Public listing data available on the platform.',
    'stats_desc_vendor' => 'Active vendors ready to post auction needs.',
    'stats_desc_buyer' => 'Users searching for goods and services through the platform.',
    'stats_desc_access' => 'The service can be accessed online anytime.',

    'trust_badge' => 'Platform Trust',
    'trust_title' => 'Designed for a More Credible Business Appearance',
    'trust_desc' => 'Saleno helps vendors appear more professional and helps buyers find auction needs faster, more neatly, and more reliably.',
    'trust_1_title' => 'Curated Vendors',
    'trust_1_desc' => 'Listings come from registered vendor accounts and can be managed professionally.',
    'trust_2_title' => 'Fast Search',
    'trust_2_desc' => 'Buyers can search by keyword, category, and location efficiently.',
    'trust_3_title' => 'Premium Access',
    'trust_3_desc' => 'Full details can be opened for subscribed users according to business needs.',
    'trust_4_title' => 'Professional UI',
    'trust_4_desc' => 'A modern interface improves trust in both vendors and the platform.',

    'features_badge' => 'Main Features',
    'features_title' => 'Designed for Vendors and Auction Seekers',
    'features_desc' => 'A private auction partner platform for goods and services from companies, agencies, schools, and legal institutions.',
    'features_1_title' => 'Professional Listing Posting',
    'features_1_desc' => 'Vendors can display goods and services with neat, attractive, and trustworthy information.',
    'features_2_title' => 'Keyword-Based Search',
    'features_2_desc' => 'Seekers only need to enter keywords to find the goods or services they need.',
    'features_3_title' => 'Premium Detail Access',
    'features_3_desc' => 'Sensitive information and full data are only open to subscribed users.',

    'workflow_badge' => 'How It Works',
    'workflow_title' => 'A Simple and Effective Saleno Flow',
    'workflow_desc' => 'A clear process makes it easier for vendors to publish needs and buyers to find the best opportunities.',
    'workflow_1_title' => 'Register & Activate',
    'workflow_1_desc' => 'Vendors create an account and activate a subscription to start using the platform.',
    'workflow_2_title' => 'Post Requirements',
    'workflow_2_desc' => 'Goods or services are published with neat details so buyers can find them easily.',
    'workflow_3_title' => 'Found by Buyers',
    'workflow_3_desc' => 'Buyers can browse listings and find what they need through fast search.',
    'workflow_4_title' => 'Premium Access',
    'workflow_4_desc' => 'Full information is opened according to premium access rights for more serious business needs.',

    'listing_badge' => 'Listing Data',
    'listing_search_result' => 'Search Results',
    'listing_latest' => 'Latest Listings',
    'listing_search_text' => 'Showing results for keyword: ',
    'listing_default_text' => 'Here are some goods and services available on the platform.',
    'listing_be_vendor' => 'Become a Vendor',
    'listing_vendor_label' => 'Vendor: ',
    'listing_detail' => 'View Details',
    'listing_empty_title' => 'No approved listings yet',
    'listing_empty_desc' => 'There is currently no public data available.',
    'listing_label_featured' => 'Platform Choice',
    'listing_featured_title' => 'More Professional Listing Display',
    'listing_featured_desc' => 'Each listing is presented with a cleaner, more informative, and convincing design so vendors look more credible to potential buyers.',
    'listing_stat_1' => 'Active data',
    'listing_stat_2' => 'Vendors ready',
    'listing_stat_3' => 'Fast access',
    'listing_browse' => 'View All',
    'listing_search_hint' => 'Quick search by title, category, and location.',

    'cta_badge' => 'Start More Professionally',
    'cta_title' => 'Build a More Convincing Business Listing on Saleno',
    'cta_desc' => 'Showcase your goods and services with a neater, more credible appearance, and make them easier to find for potential auction seekers.',
    'cta_register' => 'Register Now',
    'cta_view' => 'View Listings',

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
]);
?>

<section class="hero-section hero-premium">
    <div class="hero-overlay"></div>
    <div class="hero-stars"></div>
    <div class="hero-grid-lines"></div>
    <div class="hero-glow hero-glow-1"></div>
    <div class="hero-glow hero-glow-2"></div>
    <div class="hero-glow hero-glow-3"></div>

    <div class="container hero-grid">
        <div class="hero-left reveal-up">
            <span class="hero-badge"><?= e(t('hero_badge')); ?></span>

            <h1 class="hero-title">
                <?= e(t('hero_title_1')); ?>
                <span class="hero-highlight"><?= e(t('hero_title_2')); ?></span>
                <?= e(t('hero_title_3')); ?>
                <?= e(t('hero_title_4')); ?>
            </h1>

            <p class="hero-description reveal-up delay-1">
                <?= e(t('hero_desc')); ?>
            </p>

            <form method="GET" action="" class="search-box reveal-up delay-2">
                <input type="hidden" name="lang" value="<?= e($currentLang); ?>">
                <input
                    type="text"
                    name="keyword"
                    placeholder="<?= e(t('hero_search_placeholder')); ?>"
                    value="<?= e($keyword); ?>"
                >
                <button type="submit"><?= e(t('hero_search_button')); ?></button>
            </form>

            <div class="hero-actions reveal-up delay-3">
                <a class="btn btn-primary btn-large" href="<?= langUrl('/auth/register.php'); ?>"><?= e(t('hero_join')); ?></a>
                <a class="btn btn-outline-light btn-large" href="<?= langUrl('/index.php', [], 'listing-section'); ?>"><?= e(t('hero_view_listing')); ?></a>
            </div>

            <div class="hero-mini-stats reveal-up delay-3">
                <div class="hero-mini-stat">
                    <strong><?= e((string)$totalListings); ?>+</strong>
                    <span><?= e(t('hero_stat_listing')); ?></span>
                </div>

                <div class="hero-mini-stat">
                    <strong><?= e((string)$totalVendors); ?>+</strong>
                    <span><?= e(t('hero_stat_vendor')); ?></span>
                </div>

                <div class="hero-mini-stat">
                    <strong>24/7</strong>
                    <span><?= e(t('hero_stat_access')); ?></span>
                </div>
            </div>
        </div>

        <div class="hero-right reveal-right">
            <div class="hero-logo-card floating-card premium-entry">
                <div class="hero-logo-inner">
                    <img src="<?= BASE_URL; ?>/assets/img/logo-saleno.png" alt="<?= e(t('brand_title')); ?>" class="hero-logo">
                </div>
            </div>

            <div class="hero-info-card premium-entry delay-card">
                <div class="card-shine"></div>
                <h3><?= e(t('hero_why_title')); ?></h3>
                <ul class="hero-benefit-list">
                    <li><?= e(t('hero_why_1')); ?></li>
                    <li><?= e(t('hero_why_2')); ?></li>
                    <li><?= e(t('hero_why_3')); ?></li>
                    <li><?= e(t('hero_why_4')); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="hero-bottom-curve"></div>
</section>

<section class="stats-section">
    <div class="container stats-grid">
        <div class="stat-card reveal-up">
            <span class="stat-line"></span>
            <div class="stat-top">
                <span class="stat-label"><?= e(t('stats_total_listing')); ?></span>
                <span class="stat-dot"></span>
            </div>
            <h3><?= e((string)$totalListings); ?></h3>
            <p><?= e(t('stats_desc_listing')); ?></p>
        </div>

        <div class="stat-card reveal-up delay-1">
            <span class="stat-line"></span>
            <div class="stat-top">
                <span class="stat-label"><?= e(t('stats_total_vendor')); ?></span>
                <span class="stat-dot"></span>
            </div>
            <h3><?= e((string)$totalVendors); ?></h3>
            <p><?= e(t('stats_desc_vendor')); ?></p>
        </div>

        <div class="stat-card reveal-up delay-2">
            <span class="stat-line"></span>
            <div class="stat-top">
                <span class="stat-label"><?= e(t('stats_total_buyer')); ?></span>
                <span class="stat-dot"></span>
            </div>
            <h3><?= e((string)$totalBuyers); ?></h3>
            <p><?= e(t('stats_desc_buyer')); ?></p>
        </div>

        <div class="stat-card reveal-up delay-3">
            <span class="stat-line"></span>
            <div class="stat-top">
                <span class="stat-label"><?= e(t('stats_access')); ?></span>
                <span class="stat-dot"></span>
            </div>
            <h3>24/7</h3>
            <p><?= e(t('stats_desc_access')); ?></p>
        </div>
    </div>
</section>

<section class="trust-section">
    <div class="container">
        <div class="section-head reveal-up">
            <span><?= e(t('trust_badge')); ?></span>
            <h2><?= e(t('trust_title')); ?></h2>
            <p><?= e(t('trust_desc')); ?></p>
        </div>

        <div class="trust-grid">
            <div class="trust-card reveal-up">
                <div class="trust-icon">01</div>
                <h3><?= e(t('trust_1_title')); ?></h3>
                <p><?= e(t('trust_1_desc')); ?></p>
            </div>

            <div class="trust-card reveal-up delay-1">
                <div class="trust-icon">02</div>
                <h3><?= e(t('trust_2_title')); ?></h3>
                <p><?= e(t('trust_2_desc')); ?></p>
            </div>

            <div class="trust-card reveal-up delay-2">
                <div class="trust-icon">03</div>
                <h3><?= e(t('trust_3_title')); ?></h3>
                <p><?= e(t('trust_3_desc')); ?></p>
            </div>

            <div class="trust-card reveal-up delay-3">
                <div class="trust-icon">04</div>
                <h3><?= e(t('trust_4_title')); ?></h3>
                <p><?= e(t('trust_4_desc')); ?></p>
            </div>
        </div>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <div class="section-head reveal-up">
            <span><?= e(t('features_badge')); ?></span>
            <h2><?= e(t('features_title')); ?></h2>
            <p><?= e(t('features_desc')); ?></p>
        </div>

        <div class="feature-grid">
            <div class="feature-card glass-card reveal-left">
                <div class="feature-number">01</div>
                <h3><?= e(t('features_1_title')); ?></h3>
                <p><?= e(t('features_1_desc')); ?></p>
            </div>

            <div class="feature-card glass-card reveal-up delay-1">
                <div class="feature-number">02</div>
                <h3><?= e(t('features_2_title')); ?></h3>
                <p><?= e(t('features_2_desc')); ?></p>
            </div>

            <div class="feature-card glass-card reveal-right delay-2">
                <div class="feature-number">03</div>
                <h3><?= e(t('features_3_title')); ?></h3>
                <p><?= e(t('features_3_desc')); ?></p>
            </div>
        </div>
    </div>
</section>

<section class="workflow-section">
    <div class="container">
        <div class="section-head reveal-up">
            <span><?= e(t('workflow_badge')); ?></span>
            <h2><?= e(t('workflow_title')); ?></h2>
            <p><?= e(t('workflow_desc')); ?></p>
        </div>

        <div class="workflow-grid">
            <div class="workflow-card reveal-up">
                <div class="workflow-step">01</div>
                <h3><?= e(t('workflow_1_title')); ?></h3>
                <p><?= e(t('workflow_1_desc')); ?></p>
            </div>

            <div class="workflow-card reveal-up delay-1">
                <div class="workflow-step">02</div>
                <h3><?= e(t('workflow_2_title')); ?></h3>
                <p><?= e(t('workflow_2_desc')); ?></p>
            </div>

            <div class="workflow-card reveal-up delay-2">
                <div class="workflow-step">03</div>
                <h3><?= e(t('workflow_3_title')); ?></h3>
                <p><?= e(t('workflow_3_desc')); ?></p>
            </div>

            <div class="workflow-card reveal-up delay-3">
                <div class="workflow-step">04</div>
                <h3><?= e(t('workflow_4_title')); ?></h3>
                <p><?= e(t('workflow_4_desc')); ?></p>
            </div>
        </div>
    </div>
</section>

<section id="listing-section" class="listing-section listing-section-enhanced">
    <div class="container">
        <div class="listing-showcase reveal-up">
            <div class="listing-showcase-content">
                <span class="listing-showcase-badge"><?= e(t('listing_label_featured')); ?></span>
                <h3><?= e(t('listing_featured_title')); ?></h3>
                <p><?= e(t('listing_featured_desc')); ?></p>

                <div class="listing-showcase-stats">
                    <div class="listing-showcase-stat">
                        <strong><?= e((string)$totalListings); ?>+</strong>
                        <span><?= e(t('listing_stat_1')); ?></span>
                    </div>
                    <div class="listing-showcase-stat">
                        <strong><?= e((string)$totalVendors); ?>+</strong>
                        <span><?= e(t('listing_stat_2')); ?></span>
                    </div>
                    <div class="listing-showcase-stat">
                        <strong>24/7</strong>
                        <span><?= e(t('listing_stat_3')); ?></span>
                    </div>
                </div>
            </div>

            <div class="listing-showcase-action">
                <a class="btn btn-primary btn-large" href="<?= langUrl('/auth/register.php'); ?>"><?= e(t('listing_be_vendor')); ?></a>
                <p><?= e(t('listing_search_hint')); ?></p>
            </div>
        </div>

        <div class="listing-head-row">
            <div class="section-head section-head-left reveal-up">
                <span><?= e(t('listing_badge')); ?></span>
                <h2><?= $keyword !== '' ? e(t('listing_search_result')) : e(t('listing_latest')); ?></h2>
                <p>
                    <?= $keyword !== '' ? e(t('listing_search_text') . $keyword) : e(t('listing_default_text')); ?>
                </p>
            </div>

            <div class="listing-head-action reveal-up delay-1">
                <a class="btn btn-outline-dark" href="<?= langUrl('/auth/register.php'); ?>"><?= e(t('listing_be_vendor')); ?></a>
            </div>
        </div>

        <div class="listing-grid listing-grid-enhanced">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php $delay = 0; ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
<div class="listing-card listing-card-enhanced reveal-up" style="animation-delay: <?= number_format($delay, 2, '.', ''); ?>s;">
    <div class="listing-shine"></div>
    <div class="listing-card-glow"></div>

    <div class="listing-top">
        <span class="listing-category"><?= e($row['category']); ?></span>
        <span class="listing-date"><?= date('d M Y', strtotime($row['created_at'])); ?></span>
    </div>

    <h3><?= e($row['title']); ?></h3>

    <p class="listing-desc">
        <?= e(mb_strimwidth($row['description'], 0, 135, '...')); ?>
    </p>

    <div class="listing-meta listing-meta-stack">
        <div class="listing-meta-item meta-box">
            <span class="listing-meta-label">Harga</span>
            <span class="listing-price value-box-text"><?= formatRupiah($row['price']); ?></span>
        </div>

        <div class="listing-meta-item meta-box">
            <span class="listing-meta-label">Lokasi</span>
            <span class="listing-location value-box-text"><?= e($row['location']); ?></span>
        </div>
    </div>

    <div class="listing-bottom">
        <small class="vendor-name"><?= e(t('listing_vendor_label')); ?><?= e($row['vendor_name']); ?></small>
        <a class="btn btn-primary btn-sm" href="<?= langUrl('/detail.php', ['id' => (int)$row['id']]); ?>">
            <?= e(t('listing_detail')); ?>
        </a>
    </div>
</div>
                    <?php $delay += 0.08; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state reveal-up">
                    <div class="empty-state-icon">!</div>
                    <h3><?= e(t('listing_empty_title')); ?></h3>
                    <p><?= e(t('listing_empty_desc')); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="listing-footer-action reveal-up">
                <a class="btn btn-outline-dark btn-large" href="<?= langUrl('/index.php', [], 'listing-section'); ?>">
                    <?= e(t('listing_browse')); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <div class="cta-card reveal-up">
            <div class="cta-content">
                <span class="cta-badge"><?= e(t('cta_badge')); ?></span>
                <h2><?= e(t('cta_title')); ?></h2>
                <p><?= e(t('cta_desc')); ?></p>
            </div>

            <div class="cta-actions">
                <a href="<?= langUrl('/auth/register.php'); ?>" class="btn btn-primary btn-large"><?= e(t('cta_register')); ?></a>
                <a href="<?= langUrl('/index.php', [], 'listing-section'); ?>" class="btn btn-outline-light btn-large"><?= e(t('cta_view')); ?></a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>