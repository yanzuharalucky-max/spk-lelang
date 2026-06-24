<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!isBuyer()) {
    redirect('/index.php');
}

if (!isSubscribed()) {
    redirect('/buyer/subscribe.php');
}

$buyerId = (int) currentUser()['id'];
$listingId = (int) ($_GET['id'] ?? $_POST['listing_id'] ?? 0);

if ($listingId <= 0) {
    redirect('/buyer/dashboard.php');
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT l.*, u.name AS vendor_name
     FROM listings l
     JOIN users u ON l.user_id = u.id
     WHERE l.id = ? AND l.status = 'approved'
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'i', $listingId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$listing = mysqli_fetch_assoc($result);

if (!$listing) {
    redirect('/buyer/dashboard.php');
}

if (($listing['auction_status'] ?? 'open') !== 'open') {
    redirect('/detail.php?id=' . $listingId . '&msg=auction_closed');
}

if ((int)$listing['user_id'] === $buyerId) {
    redirect('/detail.php?id=' . $listingId . '&msg=self_bid_not_allowed');
}

$error = '';
$success = '';

$stmtExisting = mysqli_prepare(
    $conn,
    "SELECT * FROM bids
     WHERE listing_id = ? AND buyer_id = ?
     ORDER BY created_at DESC
     LIMIT 1"
);
mysqli_stmt_bind_param($stmtExisting, 'ii', $listingId, $buyerId);
mysqli_stmt_execute($stmtExisting);
$resultExisting = mysqli_stmt_get_result($stmtExisting);
$existingBid = mysqli_fetch_assoc($resultExisting);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bidAmount = normalizePriceInput($_POST['bid_amount'] ?? '0');
    $bidNote = trim($_POST['bid_note'] ?? '');

    if ($bidAmount < 1000) {
        $error = 'Nominal penawaran minimal Rp 1.000.';
    } else {
        if ($existingBid) {
            if (($existingBid['status'] ?? 'active') !== 'active') {
                $error = 'Penawaran ini sudah tidak bisa diubah lagi.';
            } else {
                $updatedAt = date('Y-m-d H:i:s');
                $stmtUpdate = mysqli_prepare(
                    $conn,
                    "UPDATE bids
                     SET bid_amount = ?, bid_note = ?, updated_at = ?
                     WHERE id = ?"
                );
                mysqli_stmt_bind_param($stmtUpdate, 'dssi', $bidAmount, $bidNote, $updatedAt, $existingBid['id']);

                if (mysqli_stmt_execute($stmtUpdate)) {
                    calculateSawForListing($conn, $listingId);
                    redirect('/detail.php?id=' . $listingId . '&msg=bid_updated');
                } else {
                    $error = 'Gagal memperbarui penawaran.';
                }
            }
        } else {
            $status = 'active';
            $stmtInsert = mysqli_prepare(
                $conn,
                "INSERT INTO bids (listing_id, buyer_id, bid_amount, bid_note, status)
                 VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmtInsert, 'iidss', $listingId, $buyerId, $bidAmount, $bidNote, $status);

            if (mysqli_stmt_execute($stmtInsert)) {
                calculateSawForListing($conn, $listingId);
                redirect('/detail.php?id=' . $listingId . '&msg=bid_sent');
            } else {
                $error = 'Gagal mengirim penawaran.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="form-section form-section-premium">
    <div class="dashboard-grid-lines"></div>
    <div class="dashboard-background-glow dashboard-glow-1"></div>
    <div class="dashboard-background-glow dashboard-glow-2"></div>

    <div class="container">
        <div class="form-card form-card-premium reveal-up">
            <div class="form-card-shine"></div>

            <div class="form-head-premium">
                <span class="dashboard-badge">Penawaran Buyer</span>
                <h1>Ajukan Penawaran</h1>
                <p>Buyer dapat mengajukan nilai penawaran untuk listing barang, jasa, pekerjaan, atau pengadaan.</p>
            </div>

            <div class="auction-highlight-box">
                <div>
                    <span>Listing</span>
                    <strong><?= e($listing['title']); ?></strong>
                </div>
                <div>
                    <span>Vendor</span>
                    <strong><?= e($listing['vendor_name']); ?></strong>
                </div>
                <div>
                    <span>Harga Referensi</span>
                    <strong><?= e(formatRupiah($listing['price'])); ?></strong>
                </div>
                <div>
                    <span>Status</span>
                    <strong><?= e(getAuctionStatusLabel($listing['auction_status'] ?? 'open')); ?></strong>
                </div>
            </div>

            <?php if ($existingBid): ?>
                <div class="alert alert-warning">
                    Anda sudah pernah mengajukan penawaran untuk listing ini.
                    Anda masih bisa mengubahnya selama status penawaran masih aktif.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="premium-form-grid">
                <input type="hidden" name="listing_id" value="<?= (int)$listingId; ?>">

                <div class="field-group">
                    <label for="bid_amount_display">Nilai Penawaran</label>
                    <input
                        type="text"
                        id="bid_amount_display"
                        value="<?= e($existingBid['bid_amount'] ?? ''); ?>"
                        placeholder="Contoh: 15.000.000"
                        required
                    >
                    <input
                        type="hidden"
                        name="bid_amount"
                        id="bid_amount"
                        value="<?= e($existingBid['bid_amount'] ?? ''); ?>"
                    >
                </div>

                <div class="field-group">
                    <label for="bid_note">Catatan Penawaran</label>
                    <textarea id="bid_note" name="bid_note" rows="5" placeholder="Tulis catatan, keunggulan, atau alasan kenapa vendor harus memilih Anda"><?= e($existingBid['bid_note'] ?? ''); ?></textarea>
                </div>

                <div class="form-action-row">
                    <a href="<?= BASE_URL; ?>/detail.php?id=<?= (int)$listingId; ?>" class="btn btn-outline-dark">Kembali ke Detail</a>
                    <button type="submit" class="btn btn-primary">
                        <?= $existingBid ? 'Update Penawaran' : 'Kirim Penawaran'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const display = document.getElementById('bid_amount_display');
    const hidden = document.getElementById('bid_amount');

    function formatNumberWithDots(value) {
        const numbers = String(value).replace(/[^\d]/g, '');
        return numbers.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    if (display && hidden) {
        display.value = formatNumberWithDots(hidden.value || display.value || '');

        display.addEventListener('input', function () {
            const raw = this.value.replace(/[^\d]/g, '');
            this.value = formatNumberWithDots(raw);
            hidden.value = raw;
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
