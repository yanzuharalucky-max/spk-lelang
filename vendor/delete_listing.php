<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!isVendor()) {
    redirect('/index.php');
}

$id = (int)($_GET['id'] ?? 0);
$userId = (int)currentUser()['id'];

if ($id > 0) {
    $stmtImage = mysqli_prepare($conn, "SELECT image FROM listings WHERE id = ? AND user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtImage, 'ii', $id, $userId);
    mysqli_stmt_execute($stmtImage);
    $resultImage = mysqli_stmt_get_result($stmtImage);
    $listingData = mysqli_fetch_assoc($resultImage);

    if ($listingData) {
        if (!empty($listingData['image'])) {
            $imagePath = __DIR__ . '/../assets/uploads/listings/' . $listingData['image'];
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM listings WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
        mysqli_stmt_execute($stmt);
    }
}

redirect('/vendor/dashboard.php');