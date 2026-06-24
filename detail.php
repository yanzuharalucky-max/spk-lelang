<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('/index.php');
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT l.*, u.name AS vendor_name, u.email AS vendor_email
     FROM listings l
     JOIN users u ON l.user_id = u.id
     WHERE l.id = ? AND l.status = 'approved'
     LIMIT 1"
);

if (!$stmt) {
    redirect('/index.php');
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    redirect('/index.php');
}

$canViewFull = isSubscribed();
$currentUserData = currentUser();
$currentUserId = (int)($currentUserData['id'] ?? 0);
$isVendorOwner = isVendor() && $currentUserId === (int)$data['user_id'];
$isBuyerUser = isBuyer();

$stmtBidSummary = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total_bids, MAX(bid_amount) AS highest_bid, MIN(bid_amount) AS lowest_bid
     FROM bids
     WHERE listing_id = ?"
);
mysqli_stmt_bind_param($stmtBidSummary, 'i', $id);
mysqli_stmt_execute($stmtBidSummary);
$bidSummaryResult = mysqli_stmt_get_result($stmtBidSummary);
$bidSummary = mysqli_fetch_assoc($bidSummaryResult);

$stmtWinner = mysqli_prepare(
    $conn,
    "SELECT b.bid_amount, u.name AS buyer_name
     FROM bids b
     JOIN users u ON b.buyer_id = u.id
     WHERE b.id = ?
     LIMIT 1"
);
$winnerBidId = (int)($data['winner_bid_id'] ?? 0);
$winnerData = null;
if ($winnerBidId > 0) {
    mysqli_stmt_bind_param($stmtWinner, 'i', $winnerBidId);
    mysqli_stmt_execute($stmtWinner);
    $winnerResult = mysqli_stmt_get_result($stmtWinner);
    $winnerData = mysqli_fetch_assoc($winnerResult);
}

$myBid = null;
if ($isBuyerUser && $currentUserId > 0) {
    $stmtMyBid = mysqli_prepare(
        $conn,
        "SELECT *
         FROM bids
         WHERE listing_id = ? AND buyer_id = ?
         ORDER BY created_at DESC
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmtMyBid, 'ii', $id, $currentUserId);
    mysqli_stmt_execute($stmtMyBid);
    $myBidResult = mysqli_stmt_get_result($stmtMyBid);
    $myBid = mysqli_fetch_assoc($myBidResult);
}

$msg = trim($_GET['msg'] ?? '');

include __DIR__ . '/includes/header.php';
?>

<section class="hero-premium detail-premium-page">
    <div class="hero-overlay"></div>
    <div class="hero-grid-lines"></div>
    <div class="hero-stars"></div>
    <div class="hero-glow hero-glow-1"></div>
    <div class="hero-glow hero-glow-2"></div>
    <div class="hero-glow hero-glow-3"></div>

    <div class="container">
        <?php if ($msg === 'bid_sent'): ?>
            <div class="alert alert-success">Penawaran berhasil dikirim.</div>
        <?php elseif ($msg === 'bid_updated'): ?>
            <div class="alert alert-success">Penawaran berhasil diperbarui.</div>
        <?php elseif ($msg === 'auction_closed'): ?>
            <div class="alert alert-warning">Lelang untuk listing ini sudah ditutup.</div>
        <?php elseif ($msg === 'self_bid_not_allowed'): ?>
            <div class="alert alert-danger">Vendor tidak bisa menawar listing miliknya sendiri.</div>
        <?php endif; ?>

        <div class="detail-premium-header reveal-up">
            <div class="detail-premium-header-main">
                <span class="hero-badge">Detail Listing Lelang</span>

                <div class="detail-top-meta">
                    <span class="listing-category"><?= e($data['category']); ?></span>
                    <span class="listing-date"><?= date('d M Y H:i', strtotime($data['created_at'])); ?></span>
                </div>

                <h1 class="detail-premium-title"><?= e($data['title']); ?></h1>

                <div class="detail-premium-price"><?= formatRupiah($data['price']); ?></div>

                <p class="detail-premium-desc">
                    Listing ini menggunakan mekanisme lelang. Buyer dapat mengirim penawaran,
                    lalu vendor akan memilih penawaran terbaik sebagai pemenang.
                </p>

                <div class="detail-premium-pills">
                    <span class="detail-pill"><?= e($data['category']); ?></span>
                    <span class="detail-pill"><?= $canViewFull ? 'Full Access' : 'Preview Only'; ?></span>
                    <span class="detail-pill"><?= e(getAuctionStatusLabel($data['auction_status'] ?? 'open')); ?></span>
                </div>
            </div>

            <div class="detail-premium-header-side">
                <div class="detail-summary-hero-card">
                    <span>Status Lelang</span>
                    <strong><?= e(getAuctionStatusLabel($data['auction_status'] ?? 'open')); ?></strong>
                    <small>
                        <?= ($data['auction_status'] ?? 'open') === 'open'
                            ? 'Buyer masih dapat mengirim atau memperbarui penawaran.'
                            : 'Lelang sudah ditutup dan pemenang sudah dipilih.'; ?>
                    </small>

                    <div class="detail-summary-mini-grid">
                        <div class="detail-summary-mini-item">
                            <span>Total Penawaran</span>
                            <strong><?= (int)($bidSummary['total_bids'] ?? 0); ?></strong>
                        </div>
                        <div class="detail-summary-mini-item">
                            <span>Bid Tertinggi</span>
                            <strong><?= formatRupiah($bidSummary['highest_bid'] ?? 0); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="detail-premium-layout reveal-up delay-1">
            <div class="detail-premium-main">
                <?php if (!empty($data['image'])): ?>
                    <div class="detail-premium-card detail-image-card">
                        <div class="detail-card-shine"></div>
                        <div class="detail-card-head">
                            <div>
                                <span class="detail-card-badge">Preview Listing</span>
                                <h2>Gambar Utama</h2>
                                <p>Visual utama dari listing yang dipublikasikan vendor.</p>
                            </div>
                        </div>

                        <div class="detail-image-wrap">
                            <img
                                src="<?= BASE_URL; ?>/assets/uploads/listings/<?= e($data['image']); ?>"
                                alt="<?= e($data['title']); ?>"
                                class="detail-main-image"
                            >
                        </div>
                    </div>
                <?php endif; ?>

                <div class="detail-premium-card">
                    <div class="detail-card-shine"></div>
                    <div class="detail-card-head">
                        <div>
                            <span class="detail-card-badge">Informasi Utama</span>
                            <h2>Deskripsi Listing</h2>
                            <p>Ringkasan dan uraian kebutuhan yang dipublikasikan mitra.</p>
                        </div>
                    </div>

                    <?php if ($canViewFull): ?>
                        <div class="detail-richtext"><?= nl2br(e($data['description'])); ?></div>
                    <?php else: ?>
                        <div class="detail-richtext blurred-text"><?= nl2br(e($data['description'])); ?></div>
                        <p class="warning-text">Detail penuh hanya tersedia untuk pengguna yang sudah subscribe.</p>
                    <?php endif; ?>
                </div>

                <div class="detail-grid-2">
                    <div class="detail-premium-card">
                        <div class="detail-card-shine"></div>
                        <div class="detail-card-head">
                            <div>
                                <span class="detail-card-badge">Informasi Mitra</span>
                                <h2>Data Vendor</h2>
                                <p>Informasi perusahaan dan PIC terkait listing.</p>
                            </div>
                        </div>

                        <div class="detail-info-grid">
                            <div class="detail-info-item">
                                <span>PT / Mitra</span>
                                <strong><?= $canViewFull ? e($data['company_name'] ?? '-') : '<span class="blurred-inline">Data premium</span>'; ?></strong>
                            </div>
                            <div class="detail-info-item">
                                <span>PIC</span>
                                <strong><?= $canViewFull ? e($data['contact_person'] ?? '-') : '<span class="blurred-inline">Data premium</span>'; ?></strong>
                            </div>
                            <div class="detail-info-item">
                                <span>Telepon / WA</span>
                                <strong><?= $canViewFull ? e($data['contact_phone'] ?? '-') : '<span class="blurred-inline">Data premium</span>'; ?></strong>
                            </div>
                            <div class="detail-info-item">
                                <span>Vendor Akun</span>
                                <strong><?= $canViewFull ? e($data['vendor_name']) : '<span class="blurred-inline">Data premium</span>'; ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="detail-premium-card">
                        <div class="detail-card-shine"></div>
                        <div class="detail-card-head">
                            <div>
                                <span class="detail-card-badge">Status Lelang</span>
                                <h2>Ringkasan Penawaran</h2>
                                <p>Informasi pergerakan penawaran pada listing ini.</p>
                            </div>
                        </div>

                        <div class="detail-info-grid">
                            <div class="detail-info-item">
                                <span>Status Lelang</span>
                                <strong><?= e(getAuctionStatusLabel($data['auction_status'] ?? 'open')); ?></strong>
                            </div>
                            <div class="detail-info-item">
                                <span>Total Penawaran</span>
                                <strong><?= (int)($bidSummary['total_bids'] ?? 0); ?></strong>
                            </div>
                            <div class="detail-info-item">
                                <span>Penawaran Tertinggi</span>
                                <strong><?= e(formatRupiah($bidSummary['highest_bid'] ?? 0)); ?></strong>
                            </div>
                            <div class="detail-info-item">
                                <span>Penawaran Terendah</span>
                                <strong><?= e(formatRupiah($bidSummary['lowest_bid'] ?? 0)); ?></strong>
                            </div>
                        </div>

                        <?php if ($winnerData): ?>
                            <div class="auction-winner-box">
                                <span>Pemenang Terpilih</span>
                                <strong><?= e($winnerData['buyer_name'] ?? '-'); ?></strong>
                                <p>Nilai penawaran: <?= e(formatRupiah($winnerData['bid_amount'] ?? 0)); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <aside class="detail-premium-sidebar">
                <div class="detail-premium-card detail-side-card">
                    <div class="detail-card-shine"></div>
                    <div class="detail-card-head">
                        <div>
                            <span class="detail-card-badge">Aksi Pengguna</span>
                            <h2>Panel Lelang</h2>
                        </div>
                    </div>

                    <?php if ($isBuyerUser): ?>
                        <?php if (!isSubscribed()): ?>
                            <div class="dashboard-status-box status-warning-box">
                                <strong>Butuh Premium</strong>
                                <p>Buyer wajib premium untuk mengirim penawaran.</p>
                            </div>
                            <a href="<?= BASE_URL; ?>/buyer/subscribe.php" class="btn btn-primary btn-block">Aktifkan Premium</a>
                        <?php elseif (($data['auction_status'] ?? 'open') === 'closed'): ?>
                            <div class="dashboard-status-box status-warning-box">
                                <strong>Lelang Sudah Ditutup</strong>
                                <p>Penawaran baru tidak dapat diajukan lagi.</p>
                            </div>
                        <?php else: ?>
                            <?php if ($myBid): ?>
                                <div class="dashboard-status-box status-success-box">
                                    <strong>Anda Sudah Mengajukan Penawaran</strong>
                                    <p>Status: <?= e(getBidStatusLabel($myBid['status'] ?? 'active')); ?></p>
                                    <p>Nominal: <?= e(formatRupiah($myBid['bid_amount'] ?? 0)); ?></p>
                                    <p>Total penawaran pada listing ini: <?= (int)($bidSummary['total_bids'] ?? 0); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="dashboard-status-box status-warning-box">
                                    <strong>Anda Belum Mengajukan Penawaran</strong>
                                    <?php if ((int)($bidSummary['total_bids'] ?? 0) > 0): ?>
                                        <p>
                                            Saat ini sudah ada <?= (int)($bidSummary['total_bids'] ?? 0); ?>
                                            penawaran pada listing ini, tetapi akun Anda belum ikut menawar.
                                        </p>
                                    <?php else: ?>
                                        <p>Belum ada buyer yang menawar listing ini. Jadilah yang pertama mengajukan penawaran.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <a href="<?= BASE_URL; ?>/buyer/place_bid.php?id=<?= (int)$id; ?>" class="btn btn-primary btn-block">
                                <?= $myBid ? 'Ubah Penawaran' : 'Ajukan Penawaran'; ?>
                            </a>
                        <?php endif; ?>
                    <?php elseif ($isVendorOwner): ?>
                        <div class="dashboard-status-box status-success-box">
                            <strong>Anda Pemilik Listing</strong>
                            <p>Silakan cek dashboard vendor untuk melihat seluruh penawaran dan memilih pemenang.</p>
                        </div>
                        <a href="<?= BASE_URL; ?>/vendor/dashboard.php" class="btn btn-primary btn-block">Buka Dashboard Vendor</a>
                    <?php else: ?>
                        <div class="dashboard-status-box status-warning-box">
                            <strong>Login sebagai Buyer</strong>
                            <p>Masuk sebagai buyer premium untuk ikut menawar.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="detail-premium-card detail-side-card">
                    <div class="detail-card-shine"></div>
                    <div class="detail-card-head">
                        <div>
                            <span class="detail-card-badge">Lokasi</span>
                            <h2>Area Listing</h2>
                        </div>
                    </div>

                    <div class="detail-info-grid">
                        <div class="detail-info-item">
                            <span>Provinsi</span>
                            <strong><?= $canViewFull ? e($data['province'] ?? '-') : '<span class="blurred-inline">Data premium</span>'; ?></strong>
                        </div>
                        <div class="detail-info-item">
                            <span>Kota / Kabupaten</span>
                            <strong><?= $canViewFull ? e($data['city'] ?? '-') : '<span class="blurred-inline">Data premium</span>'; ?></strong>
                        </div>
                        <div class="detail-info-item">
                            <span>Kecamatan</span>
                            <strong><?= $canViewFull ? e($data['district'] ?? '-') : '<span class="blurred-inline">Data premium</span>'; ?></strong>
                        </div>
                        <div class="detail-info-item">
                            <span>Lokasi Singkat</span>
                            <strong><?= $canViewFull ? e($data['location']) : '<span class="blurred-inline">Data premium</span>'; ?></strong>
                        </div>
                    </div>

                    <div class="detail-address-box">
                        <span>Alamat Detail</span>
                        <div class="detail-address-content">
                            <?= $canViewFull ? nl2br(e($data['address_detail'] ?? '-')) : '<span class="blurred-inline">Alamat lengkap premium</span>'; ?>
                        </div>
                    </div>

                    <?php if ($canViewFull && !empty($data['google_maps_link'])): ?>
                        <a href="<?= e($data['google_maps_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-block">
                            Buka Google Maps
                        </a>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
