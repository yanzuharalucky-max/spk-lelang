<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

if (!isAdmin()) {
    redirect('/index.php');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('/admin/admin.php?msg=invalid_listing');
}

/* =========================
   CEK LISTING DULU
========================= */
$stmtCheck = mysqli_prepare(
    $conn,
    "SELECT id, title, image FROM listings WHERE id = ? LIMIT 1"
);

if (!$stmtCheck) {
    redirect('/admin/admin.php?msg=delete_failed');
}

mysqli_stmt_bind_param($stmtCheck, 'i', $id);
mysqli_stmt_execute($stmtCheck);
$resultCheck = mysqli_stmt_get_result($stmtCheck);
$listing = mysqli_fetch_assoc($resultCheck);

if (!$listing) {
    redirect('/admin/admin.php?msg=listing_not_found');
}

$imageName = trim($listing['image'] ?? '');

/* =========================
   HAPUS DATA LISTING
========================= */
$stmtDelete = mysqli_prepare(
    $conn,
    "DELETE FROM listings WHERE id = ?"
);

if (!$stmtDelete) {
    redirect('/admin/admin.php?msg=delete_failed');
}

mysqli_stmt_bind_param($stmtDelete, 'i', $id);

if (!mysqli_stmt_execute($stmtDelete)) {
    redirect('/admin/admin.php?msg=delete_failed');
}

/* =========================
   HAPUS FILE GAMBAR JIKA ADA
========================= */
if ($imageName !== '') {
    $imagePath = __DIR__ . '/../assets/uploads/listings/' . $imageName;

    if (is_file($imagePath)) {
        @unlink($imagePath);
    }
}

redirect('/admin/admin.php?msg=listing_deleted');