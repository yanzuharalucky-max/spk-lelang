<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!isVendor()) {
    redirect('/index.php');
}

$user = currentUser();
$userId = (int) $user['id'];

$stmtUser = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmtUser, 'i', $userId);
mysqli_stmt_execute($stmtUser);
$resultUser = mysqli_stmt_get_result($stmtUser);
$userData = mysqli_fetch_assoc($resultUser);

$stmtListings = mysqli_prepare(
    $conn,
    "SELECT l.*,
            (SELECT COUNT(*) FROM bids b WHERE b.listing_id = l.id) AS total_bids,
            (SELECT MAX(bid_amount) FROM bids b WHERE b.listing_id = l.id) AS highest_bid
     FROM listings l
     WHERE l.user_id = ?
     ORDER BY l.created_at DESC"
);
mysqli_stmt_bind_param($stmtListings, 'i', $userId);
mysqli_stmt_execute($stmtListings);
$listings = mysqli_stmt_get_result($stmtListings);

$totalListings = 0;
$totalApproved = 0;
$totalPending = 0;
$totalRejected = 0;
$totalOpenAuction = 0;
$totalClosedAuction = 0;
$totalIncomingBids = 0;

$listingRows = [];
if ($listings && mysqli_num_rows($listings) > 0) {
    while ($row = mysqli_fetch_assoc($listings)) {
        $listingRows[] = $row;
        $totalListings++;
        $totalIncomingBids += (int)($row['total_bids'] ?? 0);

        if (($row['status'] ?? '') === 'approved') {
            $totalApproved++;
        } elseif (($row['status'] ?? '') === 'rejected') {
            $totalRejected++;
        } else {
            $totalPending++;
        }

        if (($row['auction_status'] ?? 'open') === 'closed') {
            $totalClosedAuction++;
        } else {
            $totalOpenAuction++;
        }
    }
}

$msg = trim($_GET['msg'] ?? '');

include __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-section dashboard-section-premium vendor-dashboard-premium">
    <div class="auth-grid-lines"></div>
    <div class="auth-background-glow auth-glow-1"></div>
    <div class="auth-background-glow auth-glow-2"></div>
    <div class="auth-background-glow auth-glow-3"></div>

    <div class="container">
        <?php if ($msg === 'winner_selected'): ?>
            <div class="alert alert-success reveal-up">Pemenang berhasil dipilih dan listing ditutup.</div>
        <?php elseif ($msg === 'winner_failed'): ?>
            <div class="alert alert-danger reveal-up">Gagal memilih pemenang.</div>
        <?php elseif ($msg === 'already_closed'): ?>
            <div class="alert alert-warning reveal-up">Listing ini sudah ditutup sebelumnya.</div>
        <?php endif; ?>

        <div class="dashboard-hero premium-entry vendor-dashboard-hero reveal-up">
            <div class="dashboard-hero-content">
                <span class="dashboard-badge">Vendor Workspace</span>
                <h1>Dashboard Vendor Lelang</h1>
                <p>
                    Kelola listing, lihat semua penawaran buyer, tampilkan foto listing,
                    dan pilih pemenang dengan tampilan yang lebih premium, rapi, dan selaras
                    dengan halaman utama Seleno.
                </p>
            </div>

            <div class="vendor-hero-usercard">
                <span class="vendor-hero-usercard-label">Login sebagai</span>
                <strong><?= e($userData['name'] ?? 'Vendor'); ?></strong>
                <small><?= e($userData['email'] ?? '-'); ?></small>

                <div class="vendor-hero-usercard-pills">
                    <span>Vendor</span>
                    <span><?= (int)($userData['subscribed'] ?? 0) === 1 ? 'Premium' : 'Free'; ?></span>
                </div>
            </div>
        </div>

        <div class="vendor-stats-grid reveal-up delay-1">
            <div class="vendor-stat-card">
                <span>Total Listing</span>
                <h3><?= e((string)$totalListings); ?></h3>
                <p>Jumlah keseluruhan listing yang pernah Anda buat.</p>
            </div>

            <div class="vendor-stat-card">
                <span>Total Penawaran Masuk</span>
                <h3><?= e((string)$totalIncomingBids); ?></h3>
                <p>Semua bid buyer yang masuk ke listing Anda.</p>
            </div>

            <div class="vendor-stat-card">
                <span>Lelang Aktif</span>
                <h3><?= e((string)$totalOpenAuction); ?></h3>
                <p>Listing yang masih terbuka untuk penawaran buyer.</p>
            </div>

            <div class="vendor-stat-card">
                <span>Lelang Ditutup</span>
                <h3><?= e((string)$totalClosedAuction); ?></h3>
                <p>Listing yang pemenangnya sudah dipilih.</p>
            </div>
        </div>

        <div class="vendor-dashboard-actions reveal-up delay-2">
            <a href="<?= BASE_URL; ?>/vendor/add_listing.php" class="btn btn-primary">+ Tambah Listing Baru</a>
        </div>

        <div class="vendor-table-shell reveal-up delay-2">
            <div class="vendor-table-shell-shine"></div>

            <div class="vendor-table-head">
                <div>
                    <span class="vendor-table-kicker">Listing Vendor</span>
                    <h2>Kelola Listing dan Penawaran</h2>
                    <p>
                        Lihat jumlah bid, nilai tertinggi, foto listing, dan pilih pemenang
                        jika listing sudah siap ditutup.
                    </p>
                </div>
            </div>

            <div class="vendor-listing-stack">
                <?php if ($totalListings > 0): ?>
                    <?php foreach ($listingRows as $index => $row): ?>
                        <?php
                            $imageUrl = !empty($row['image'])
                                ? BASE_URL . '/assets/uploads/listings/' . rawurlencode($row['image'])
                                : BASE_URL . '/assets/img/logo-saleno.png';

                            $stmtBids = mysqli_prepare(
                                $conn,
                                "SELECT b.*, u.name AS buyer_name, u.email AS buyer_email
                                 FROM bids b
                                 JOIN users u ON b.buyer_id = u.id
                                 WHERE b.listing_id = ?
                                 ORDER BY b.bid_amount DESC, b.created_at ASC"
                            );
                            mysqli_stmt_bind_param($stmtBids, 'i', $row['id']);
                            mysqli_stmt_execute($stmtBids);
                            $resultBids = mysqli_stmt_get_result($stmtBids);
                        ?>

                        <div class="vendor-listing-card reveal-up" style="animation-delay: <?= number_format($index * 0.08, 2, '.', ''); ?>s;">
                            <div class="vendor-listing-card-shine"></div>

                            <div class="vendor-listing-card-top">
                                <div class="vendor-listing-image-wrap">
                                    <img src="<?= e($imageUrl); ?>" alt="<?= e($row['title']); ?>" class="vendor-listing-image">
                                </div>

                                <div class="vendor-listing-main">
                                    <div class="vendor-listing-header-row">
                                        <div>
                                            <span class="vendor-listing-badge">Listing Anda</span>
                                            <h3><?= e($row['title']); ?></h3>
                                        </div>

                                        <div class="vendor-listing-date">
                                            <?= e(date('d M Y', strtotime($row['created_at']))); ?>
                                        </div>
                                    </div>

                                    <div class="vendor-listing-summary-grid">
                                        <div class="vendor-summary-box">
                                            <span>Status Admin</span>
                                            <strong class="<?= ($row['status'] ?? 'pending') === 'approved' ? 'text-success' : (($row['status'] ?? 'pending') === 'rejected' ? 'text-danger' : 'text-warning'); ?>">
                                                <?= e(ucfirst($row['status'] ?? 'pending')); ?>
                                            </strong>
                                        </div>

                                        <div class="vendor-summary-box">
                                            <span>Status Lelang</span>
                                            <strong class="<?= ($row['auction_status'] ?? 'open') === 'closed' ? 'text-danger' : 'text-warning'; ?>">
                                                <?= e(getAuctionStatusLabel($row['auction_status'] ?? 'open')); ?>
                                            </strong>
                                        </div>

                                        <div class="vendor-summary-box">
                                            <span>Total Bid</span>
                                            <strong><?= (int)($row['total_bids'] ?? 0); ?></strong>
                                        </div>

                                        <div class="vendor-summary-box">
                                            <span>Bid Tertinggi</span>
                                            <strong><?= e(formatRupiah($row['highest_bid'] ?? 0)); ?></strong>
                                        </div>
                                    </div>

                                    <div class="vendor-listing-action-row">
                                        <a class="btn btn-outline-dark btn-sm" href="<?= BASE_URL; ?>/detail.php?id=<?= (int)$row['id']; ?>">Lihat</a>
                                        <a class="btn btn-primary btn-sm" href="<?= BASE_URL; ?>/vendor/spk_ranking.php?id=<?= (int)$row['id']; ?>">
                                            Lihat SPK
                                        </a>
                                        <a class="btn btn-outline-dark btn-sm" href="<?= BASE_URL; ?>/vendor/edit_listing.php?id=<?= (int)$row['id']; ?>">Edit</a>
                                        <a class="btn btn-outline-dark btn-sm" href="<?= BASE_URL; ?>/vendor/delete_listing.php?id=<?= (int)$row['id']; ?>" onclick="return confirm('Yakin ingin menghapus listing ini?');">Hapus</a>
                                    </div>
                                </div>
                            </div>

                            <div class="vendor-bids-panel">
                                <div class="vendor-bids-panel-head">
                                    <div>
                                        <span class="vendor-panel-kicker">Penawaran Buyer</span>
                                        <h4>Penawaran Buyer untuk: <?= e($row['title']); ?></h4>
                                    </div>
                                </div>

                                <?php if ($resultBids && mysqli_num_rows($resultBids) > 0): ?>
                                    <div class="vendor-bid-card-grid">
                                        <?php while ($bid = mysqli_fetch_assoc($resultBids)): ?>
                                            <div class="vendor-bid-card">
                                                <div class="vendor-bid-card-top">
                                                    <div>
                                                        <span class="vendor-bid-label">Buyer</span>
                                                        <strong><?= e($bid['buyer_name']); ?></strong>
                                                    </div>
                                                    <span class="status-badge <?= e(getBidStatusClass($bid['status'] ?? 'active')); ?>">
                                                        <?= e(getBidStatusLabel($bid['status'] ?? 'active')); ?>
                                                    </span>
                                                </div>

                                                <div class="vendor-bid-metrics">
                                                    <div class="vendor-bid-metric">
                                                        <span>Nominal</span>
                                                        <strong><?= e(formatRupiah($bid['bid_amount'])); ?></strong>
                                                    </div>
                                                    <div class="vendor-bid-metric">
                                                        <span>Tanggal</span>
                                                        <strong><?= e(date('d M Y H:i', strtotime($bid['created_at']))); ?></strong>
                                                    </div>
                                                </div>

                                                <?php if (!empty($bid['bid_note'])): ?>
                                                    <div class="vendor-bid-note-box">
                                                        <span>Catatan Buyer</span>
                                                        <p><?= nl2br(e($bid['bid_note'])); ?></p>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="vendor-bid-actions">
                                                    <?php if (($row['auction_status'] ?? 'open') === 'open' && ($bid['status'] ?? 'active') === 'active'): ?>
                                                        <form method="POST" action="<?= BASE_URL; ?>/vendor/select_winner.php" onsubmit="return confirm('Pilih buyer ini sebagai pemenang? Listing akan ditutup.');">
                                                            <input type="hidden" name="listing_id" value="<?= (int)$row['id']; ?>">
                                                            <input type="hidden" name="bid_id" value="<?= (int)$bid['id']; ?>">
                                                            <button type="submit" class="btn btn-primary btn-sm">Pilih Pemenang</button>
                                                        </form>
                                                    <?php elseif (($bid['status'] ?? '') === 'winner'): ?>
                                                        <span class="winner-badge">Pemenang Terpilih</span>
                                                    <?php else: ?>
                                                        <span class="status-badge <?= e(getBidStatusClass($bid['status'] ?? 'active')); ?>">
                                                            <?= e(getBidStatusLabel($bid['status'] ?? 'active')); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="vendor-empty-bid-state">
                                        <div class="vendor-empty-bid-icon">⌁</div>
                                        <div>
                                            <strong>Belum ada buyer yang menawar listing ini.</strong>
                                            <p>Penawaran buyer akan muncul di panel ini secara otomatis saat ada bid masuk.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="vendor-empty-dashboard">
                        <div class="vendor-empty-dashboard-icon">+</div>
                        <h3>Belum ada listing</h3>
                        <p>Tambahkan listing pertama Anda agar dashboard vendor mulai terisi dan dapat menerima penawaran buyer.</p>
                        <a href="<?= BASE_URL; ?>/vendor/add_listing.php" class="btn btn-primary">Tambah Listing Sekarang</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
