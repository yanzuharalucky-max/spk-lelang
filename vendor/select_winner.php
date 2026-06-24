<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!isVendor()) {
    redirect('/index.php');
}

$vendorId = (int) currentUser()['id'];
$listingId = (int) ($_POST['listing_id'] ?? 0);
$bidId = (int) ($_POST['bid_id'] ?? 0);

if ($listingId <= 0 || $bidId <= 0) {
    redirect('/vendor/dashboard.php');
}

$stmtListing = mysqli_prepare(
    $conn,
    "SELECT * FROM listings
     WHERE id = ? AND user_id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmtListing, 'ii', $listingId, $vendorId);
mysqli_stmt_execute($stmtListing);
$resultListing = mysqli_stmt_get_result($stmtListing);
$listing = mysqli_fetch_assoc($resultListing);

if (!$listing) {
    redirect('/vendor/dashboard.php');
}

if (($listing['auction_status'] ?? 'open') === 'closed') {
    redirect('/vendor/dashboard.php?msg=already_closed');
}

$stmtBid = mysqli_prepare(
    $conn,
    "SELECT b.*, u.name AS buyer_name, u.email AS buyer_email
     FROM bids b
     JOIN users u ON b.buyer_id = u.id
     WHERE b.id = ? AND b.listing_id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmtBid, 'ii', $bidId, $listingId);
mysqli_stmt_execute($stmtBid);
$resultBid = mysqli_stmt_get_result($stmtBid);
$winnerBid = mysqli_fetch_assoc($resultBid);

if (!$winnerBid) {
    redirect('/vendor/dashboard.php?msg=bid_not_found');
}

mysqli_begin_transaction($conn);

try {
    $statusWinner = 'winner';
    $updatedAt = date('Y-m-d H:i:s');

    $stmtWinner = mysqli_prepare(
        $conn,
        "UPDATE bids
         SET status = ?, updated_at = ?
         WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmtWinner, 'ssi', $statusWinner, $updatedAt, $bidId);
    if (!mysqli_stmt_execute($stmtWinner)) {
        throw new Exception('Gagal set winner');
    }

    $statusLost = 'lost';
    $stmtOthers = mysqli_prepare(
        $conn,
        "UPDATE bids
         SET status = ?, updated_at = ?
         WHERE listing_id = ? AND id != ? AND status = 'active'"
    );
    mysqli_stmt_bind_param($stmtOthers, 'ssii', $statusLost, $updatedAt, $listingId, $bidId);
    if (!mysqli_stmt_execute($stmtOthers)) {
        throw new Exception('Gagal set lost');
    }

    $auctionClosed = 'closed';
    $stmtListingUpdate = mysqli_prepare(
        $conn,
        "UPDATE listings
         SET auction_status = ?, winner_bid_id = ?
         WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmtListingUpdate, 'sii', $auctionClosed, $bidId, $listingId);
    if (!mysqli_stmt_execute($stmtListingUpdate)) {
        throw new Exception('Gagal tutup listing');
    }

    mysqli_commit($conn);

    sendAuctionWinnerEmail(
        $winnerBid['buyer_email'] ?? '',
        $winnerBid['buyer_name'] ?? '',
        $listing['title'] ?? '',
        currentUser()['name'] ?? 'Vendor',
        (float)($winnerBid['bid_amount'] ?? 0)
    );

    $stmtLosers = mysqli_prepare(
        $conn,
        "SELECT u.email, u.name
         FROM bids b
         JOIN users u ON b.buyer_id = u.id
         WHERE b.listing_id = ? AND b.id != ?"
    );
    mysqli_stmt_bind_param($stmtLosers, 'ii', $listingId, $bidId);
    mysqli_stmt_execute($stmtLosers);
    $resultLosers = mysqli_stmt_get_result($stmtLosers);

    while ($loser = mysqli_fetch_assoc($resultLosers)) {
        sendAuctionLoseEmail(
            $loser['email'] ?? '',
            $loser['name'] ?? '',
            $listing['title'] ?? ''
        );
    }

    redirect('/vendor/dashboard.php?msg=winner_selected');
} catch (Throwable $e) {
    mysqli_rollback($conn);
    redirect('/vendor/dashboard.php?msg=winner_failed');
}