<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!isBuyer()) {
    redirect('/index.php');
}

$userId = (int) currentUser()['id'];

$stmtUser = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmtUser, 'i', $userId);
mysqli_stmt_execute($stmtUser);
$resUser = mysqli_stmt_get_result($stmtUser);
$userData = mysqli_fetch_assoc($resUser);

$stmtListings = mysqli_prepare(
    $conn,
    "SELECT l.*, u.name AS vendor_name,
            (SELECT COUNT(*) FROM bids b WHERE b.listing_id = l.id) AS total_bids
     FROM listings l
     JOIN users u ON l.user_id = u.id
     WHERE l.status = 'approved'
     ORDER BY l.created_at DESC"
);
mysqli_stmt_execute($stmtListings);
$listings = mysqli_stmt_get_result($stmtListings);

$stmtMyBids = mysqli_prepare(
    $conn,
    "SELECT b.*, l.title, l.auction_status
     FROM bids b
     JOIN listings l ON b.listing_id = l.id
     WHERE b.buyer_id = ?
     ORDER BY b.created_at DESC"
);
mysqli_stmt_bind_param($stmtMyBids, 'i', $userId);
mysqli_stmt_execute($stmtMyBids);
$myBids = mysqli_stmt_get_result($stmtMyBids);

$totalListings = mysqli_num_rows($listings);
$totalMyBids = mysqli_num_rows($myBids);

include __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-section dashboard-section-premium">
    <div class="auth-grid-lines"></div>
    <div class="auth-background-glow auth-glow-1"></div>
    <div class="auth-background-glow auth-glow-2"></div>
    <div class="auth-background-glow auth-glow-3"></div>

    <div class="container">
        <div class="dashboard-hero premium-entry">
            <div class="dashboard-hero-content">
                <span class="dashboard-badge">Dashboard Buyer</span>
                <h1>Buyer Workspace Penawaran</h1>
                <p>
                    Pantau listing, kirim penawaran, dan lihat status kemenangan Anda
                    dalam sistem lelang barang, jasa, pekerjaan, dan pengadaan.
                </p>
            </div>

            <div class="dashboard-hero-stats">
                <div class="dashboard-mini-stat">
                    <strong><?= e((string) $totalListings); ?></strong>
                    <span>Total Listing</span>
                </div>
                <div class="dashboard-mini-stat">
                    <strong><?= e((string) $totalMyBids); ?></strong>
                    <span>Penawaran Saya</span>
                </div>
                <div class="dashboard-mini-stat">
                    <strong><?= (int)($userData['subscribed'] ?? 0) === 1 ? 'Premium' : 'Free'; ?></strong>
                    <span>Status Akun</span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid dashboard-grid-premium">
            <div class="dashboard-card dashboard-card-premium reveal-up">
                <div class="dashboard-card-top">
                    <span class="dashboard-card-icon">👤</span>
                    <div>
                        <h3>Informasi Akun</h3>
                        <p>Ringkasan profil buyer Anda.</p>
                    </div>
                </div>

                <ul class="summary-list summary-list-premium">
                    <li><strong>Nama</strong><span><?= e($userData['name']); ?></span></li>
                    <li><strong>Email</strong><span><?= e($userData['email']); ?></span></li>
                    <li><strong>Status</strong><span><?= (int)$userData['subscribed'] === 1 ? 'Premium Aktif' : 'Belum Aktif'; ?></span></li>
                    <li><strong>Paket</strong><span><?= e($userData['subscription']); ?></span></li>
                </ul>
            </div>

            <div class="dashboard-card dashboard-card-premium reveal-up delay-1">
                <div class="dashboard-card-top">
                    <span class="dashboard-card-icon">🔨</span>
                    <div>
                        <h3>Akses Penawaran</h3>
                        <p>Status hak ikut lelang Anda.</p>
                    </div>
                </div>

                <?php if ((int)$userData['subscribed'] === 1): ?>
                    <div class="dashboard-status-box status-success-box">
                        <strong>Buyer Premium Aktif</strong>
                        <p>Anda dapat mengirim penawaran dan ikut dalam proses lelang.</p>
                    </div>
                <?php else: ?>
                    <div class="dashboard-status-box status-warning-box">
                        <strong>Upgrade untuk Menawar</strong>
                        <p>Buyer harus premium agar bisa ikut sistem penawaran.</p>
                    </div>
                    <a class="btn btn-primary btn-block" href="<?= BASE_URL; ?>/buyer/subscribe.php">Aktifkan Subscription</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="listing-head-row dashboard-listing-head">
            <div class="section-head section-head-left reveal-up">
                <span>Penawaran Saya</span>
                <h2>Riwayat Bid Buyer</h2>
                <p>Lihat semua penawaran yang pernah Anda ajukan.</p>
            </div>
        </div>

        <div class="buyer-bid-history-shell reveal-up">
            <div class="buyer-bid-history-shine"></div>

            <div class="buyer-bid-history-head">
                <div>
                    <span class="buyer-bid-history-kicker">Riwayat Penawaran</span>
                    <h3>Daftar Bid yang Pernah Anda Kirim</h3>
                    <p>Semua aktivitas penawaran buyer Anda tampil di bawah ini dengan status bid dan status lelang terbaru.</p>
                </div>
            </div>

            <div class="buyer-bid-history-table-wrap">
                <table class="buyer-bid-history-table">
                    <thead>
                        <tr>
                            <th>Judul Listing</th>
                            <th>Nominal</th>
                            <th>Status Bid</th>
                            <th>Status Lelang</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($totalMyBids > 0): ?>
                            <?php mysqli_data_seek($myBids, 0); ?>
                            <?php while ($bid = mysqli_fetch_assoc($myBids)): ?>
                                <tr>
                                    <td class="buyer-bid-title"><?= e($bid['title']); ?></td>
                                    <td class="buyer-bid-amount"><?= e(formatRupiah($bid['bid_amount'])); ?></td>
                                    <td>
                                        <span class="status-badge <?= e(getBidStatusClass($bid['status'] ?? 'active')); ?>">
                                            <?= e(getBidStatusLabel($bid['status'] ?? 'active')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= ($bid['auction_status'] ?? 'open') === 'closed' ? 'status-inactive' : 'status-pending'; ?>">
                                            <?= e(getAuctionStatusLabel($bid['auction_status'] ?? 'open')); ?>
                                        </span>
                                    </td>
                                    <td class="buyer-bid-date"><?= e(date('d M Y H:i', strtotime($bid['created_at']))); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="buyer-bid-empty">Belum ada penawaran yang Anda kirim.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="listing-head-row dashboard-listing-head">
            <div class="section-head section-head-left reveal-up">
                <span>Listing Tersedia</span>
                <h2>Semua Listing Lelang</h2>
                <p>Cari barang, jasa, pekerjaan, atau pengadaan yang ingin Anda ikuti.</p>
            </div>
        </div>

        <div class="listing-grid">
            <?php if ($totalListings > 0): ?>
                <?php $delay = 0; ?>
                <?php mysqli_data_seek($listings, 0); ?>
                <?php while ($row = mysqli_fetch_assoc($listings)): ?>
                    <div class="listing-card listing-card-enhanced reveal-up" style="animation-delay: <?= number_format($delay, 2, '.', ''); ?>s;">
                        <div class="listing-shine"></div>

                        <div class="listing-top">
                            <span class="listing-category"><?= e($row['category']); ?></span>
                            <span class="status-badge <?= ($row['auction_status'] ?? 'open') === 'closed' ? 'status-inactive' : 'status-pending'; ?>">
                                <?= e(getAuctionStatusLabel($row['auction_status'] ?? 'open')); ?>
                            </span>
                        </div>

                        <h3><?= e($row['title']); ?></h3>
                        <p class="listing-desc"><?= e(mb_strimwidth($row['description'], 0, 110, '...')); ?></p>

                        <div class="listing-meta">
                            <span class="listing-price"><?= formatRupiah($row['price']); ?></span>
                            <span class="listing-location"><?= e($row['location']); ?></span>
                        </div>

                        <div class="listing-auction-mini">
                            <span>Vendor: <?= e($row['vendor_name']); ?></span>
                            <span>Total Bid: <?= (int)($row['total_bids'] ?? 0); ?></span>
                        </div>

                        <div class="listing-bottom">
                            <a class="btn btn-primary btn-sm" href="<?= BASE_URL; ?>/detail.php?id=<?= (int)$row['id']; ?>">Lihat Detail</a>
                        </div>
                    </div>
                    <?php $delay += 0.08; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state reveal-up">
                    <div class="empty-state-icon">!</div>
                    <h3>Belum ada listing</h3>
                    <p>Data listing akan muncul di sini setelah vendor menambahkannya.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>