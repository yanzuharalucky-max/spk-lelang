<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!isVendor()) {
    redirect('/index.php');
}

$vendorId = (int)(currentUser()['id'] ?? 0);
$listingId = (int)($_GET['id'] ?? 0);

if ($listingId <= 0) {
    redirect('/vendor/dashboard.php');
}

$stmtListing = mysqli_prepare(
    $conn,
    "SELECT l.*, u.name AS vendor_name
     FROM listings l
     JOIN users u ON l.user_id = u.id
     WHERE l.id = ? AND l.user_id = ?
     LIMIT 1"
);

if (!$stmtListing) {
    redirect('/vendor/dashboard.php');
}

mysqli_stmt_bind_param($stmtListing, 'ii', $listingId, $vendorId);
mysqli_stmt_execute($stmtListing);
$resultListing = mysqli_stmt_get_result($stmtListing);
$listing = mysqli_fetch_assoc($resultListing);

if (!$listing) {
    redirect('/vendor/dashboard.php');
}

$stmtBidCount = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total_bids
     FROM bids
     WHERE listing_id = ?"
);

$totalBids = 0;
if ($stmtBidCount) {
    mysqli_stmt_bind_param($stmtBidCount, 'i', $listingId);
    mysqli_stmt_execute($stmtBidCount);
    $resultBidCount = mysqli_stmt_get_result($stmtBidCount);
    $bidCountRow = mysqli_fetch_assoc($resultBidCount);
    $totalBids = (int)($bidCountRow['total_bids'] ?? 0);
}

$spkCalculated = $totalBids > 0 ? calculateSawForListing($conn, $listingId) : false;

$stmtRanking = mysqli_prepare(
    $conn,
    "SELECT
        sr.*,
        b.bid_amount,
        b.status AS bid_status,
        u.name AS buyer_name,
        u.email AS buyer_email,
        COALESCE(u.rating_buyer, 4) AS rating_buyer,
        COALESCE(u.response_score, 4) AS response_score,
        COALESCE(u.transaction_history, 1) AS transaction_history
     FROM spk_results sr
     JOIN bids b ON sr.bid_id = b.id
     JOIN users u ON sr.buyer_id = u.id
     WHERE sr.listing_id = ?
     ORDER BY sr.rank_position ASC, sr.final_score DESC, b.bid_amount ASC"
);

$rankingRows = [];
if ($stmtRanking) {
    mysqli_stmt_bind_param($stmtRanking, 'i', $listingId);
    mysqli_stmt_execute($stmtRanking);
    $resultRanking = mysqli_stmt_get_result($stmtRanking);

    if ($resultRanking) {
        while ($row = mysqli_fetch_assoc($resultRanking)) {
            $rankingRows[] = $row;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-section dashboard-section-premium vendor-dashboard-premium spk-ranking-page">
    <div class="auth-grid-lines"></div>
    <div class="auth-background-glow auth-glow-1"></div>
    <div class="auth-background-glow auth-glow-2"></div>
    <div class="auth-background-glow auth-glow-3"></div>

    <div class="container">
        <div class="dashboard-head dashboard-head-premium reveal-up">
            <span class="dashboard-badge">SPK Vendor</span>
            <h1>Rekomendasi Pemenang Lelang</h1>
            <p>
                Sistem SAW menghitung ranking buyer untuk listing
                <strong><?= e($listing['title'] ?? ''); ?></strong>
                agar vendor mendapat referensi pemenang sebelum menutup lelang.
            </p>
        </div>

        <div class="spk-method-card reveal-up delay-1">
            <span class="dashboard-badge">Metode SAW</span>
            <p style="margin: 14px 0 0;">
                Bobot yang dipakai: Harga 40% (cost), Rating 25% (benefit),
                Kecepatan Respon 20% (benefit), dan Riwayat Transaksi 15% (benefit).
                Skor akhir dihitung dari penjumlahan seluruh nilai normalisasi berbobot.
            </p>
        </div>

        <div class="table-card-premium spk-table-card reveal-up delay-2">
            <div class="table-card-shine"></div>

            <div class="premium-table-head">
                <div>
                    <span class="table-badge">Ranking Buyer</span>
                    <h2 style="margin-bottom: 10px;"><?= e($listing['title'] ?? ''); ?></h2>
                    <p>
                        Status lelang:
                        <span class="status-badge <?= ($listing['auction_status'] ?? 'open') === 'open' ? 'status-pending' : 'status-active'; ?>">
                            <?= e(getAuctionStatusLabel($listing['auction_status'] ?? 'open')); ?>
                        </span>
                    </p>
                </div>

                <div>
                    <a href="<?= BASE_URL; ?>/vendor/dashboard.php" class="btn btn-outline-dark btn-sm">Kembali ke Dashboard</a>
                </div>
            </div>

            <?php if ($totalBids <= 0): ?>
                <div class="alert alert-warning">Belum ada bid untuk dihitung SPK.</div>
            <?php elseif (!$spkCalculated && !$rankingRows): ?>
                <div class="alert alert-danger">
                    Hasil SPK belum bisa ditampilkan. Pastikan tabel dan kolom pada <strong>spk_update.sql</strong> sudah di-import ke database.
                </div>
            <?php else: ?>
                <p class="spk-table-help">Geser tabel ke kiri atau kanan bila seluruh kolom belum terlihat. Kolom Rank dan Buyer akan tetap terlihat.</p>
                <div class="premium-table-wrap premium-table-wrapper">
                    <table class="premium-dashboard-table premium-table">
                        <thead>
                            <tr>
                                <th class="spk-col-rank">Rank</th>
                                <th class="spk-col-buyer">Buyer</th>
                                <th class="spk-col-email">Email</th>
                                <th class="spk-col-money">Harga Bid</th>
                                <th class="spk-col-small">Rating</th>
                                <th class="spk-col-small">Respon</th>
                                <th class="spk-col-small">Riwayat</th>
                                <th class="spk-col-number">Nilai Harga Normalisasi</th>
                                <th class="spk-col-number">Nilai Rating Normalisasi</th>
                                <th class="spk-col-number">Nilai Respon Normalisasi</th>
                                <th class="spk-col-number">Nilai Riwayat Normalisasi</th>
                                <th class="spk-col-score">Skor Akhir</th>
                                <th class="spk-col-recommendation">Rekomendasi</th>
                                <th class="spk-col-action">Aksi Pilih Pemenang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rankingRows as $row): ?>
                                <?php
                                    $isBestRecommendation = (int)($row['rank_position'] ?? 0) === 1;
                                    $bidStatus = $row['bid_status'] ?? 'active';
                                ?>
                                <tr class="<?= $isBestRecommendation ? 'spk-best-row' : ''; ?>">
                                    <td class="spk-col-rank"><strong>#<?= (int)($row['rank_position'] ?? 0); ?></strong></td>
                                    <td class="spk-col-buyer"><?= e($row['buyer_name'] ?? '-'); ?></td>
                                    <td class="spk-col-email"><?= e($row['buyer_email'] ?? '-'); ?></td>
                                    <td class="spk-col-money"><?= e(formatRupiah($row['bid_amount'] ?? 0)); ?></td>
                                    <td class="spk-col-small"><?= e(number_format((float)($row['rating_buyer'] ?? 0), 2, '.', '')); ?></td>
                                    <td class="spk-col-small"><?= e(number_format((float)($row['response_score'] ?? 0), 2, '.', '')); ?></td>
                                    <td class="spk-col-small"><?= e((string)($row['transaction_history'] ?? 0)); ?></td>
                                    <td class="spk-col-number"><?= e(number_format((float)($row['normalized_price'] ?? 0), 6, '.', '')); ?></td>
                                    <td class="spk-col-number"><?= e(number_format((float)($row['normalized_rating'] ?? 0), 6, '.', '')); ?></td>
                                    <td class="spk-col-number"><?= e(number_format((float)($row['normalized_response'] ?? 0), 6, '.', '')); ?></td>
                                    <td class="spk-col-number"><?= e(number_format((float)($row['normalized_history'] ?? 0), 6, '.', '')); ?></td>
                                    <td class="spk-col-score spk-score"><?= e(number_format((float)($row['final_score'] ?? 0), 6, '.', '')); ?></td>
                                    <td class="spk-col-recommendation">
                                        <?php if ($isBestRecommendation): ?>
                                            <span class="status-badge status-active">Rekomendasi Terbaik</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Alternatif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="spk-col-action">
                                        <?php if (($listing['auction_status'] ?? 'open') === 'open' && $bidStatus === 'active'): ?>
                                            <form class="spk-action-form" method="POST" action="<?= BASE_URL; ?>/vendor/select_winner.php" onsubmit="return confirm('Pilih buyer ini sebagai pemenang? Listing akan ditutup.');">
                                                <input type="hidden" name="listing_id" value="<?= (int)$listingId; ?>">
                                                <input type="hidden" name="bid_id" value="<?= (int)($row['bid_id'] ?? 0); ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">Pilih Pemenang</button>
                                            </form>
                                        <?php elseif ($bidStatus === 'winner'): ?>
                                            <span class="status-badge status-active">Pemenang Terpilih</span>
                                        <?php else: ?>
                                            <span class="status-badge <?= e(getBidStatusClass($bidStatus)); ?>">
                                                <?= e(getBidStatusLabel($bidStatus)); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
