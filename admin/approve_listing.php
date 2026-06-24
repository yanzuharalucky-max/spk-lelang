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
    "SELECT id, status, title FROM listings WHERE id = ? LIMIT 1"
);

if (!$stmtCheck) {
    redirect('/admin/admin.php?msg=approve_failed');
}

mysqli_stmt_bind_param($stmtCheck, 'i', $id);
mysqli_stmt_execute($stmtCheck);
$resultCheck = mysqli_stmt_get_result($stmtCheck);
$listing = mysqli_fetch_assoc($resultCheck);

if (!$listing) {
    redirect('/admin/admin.php?msg=listing_not_found');
}

/* =========================
   JIKA SUDAH APPROVED
========================= */
if ($listing['status'] === 'approved') {
    redirect('/admin/admin.php?msg=already_approved');
}

/* =========================
   UPDATE STATUS LISTING
========================= */
$status = 'approved';
$remark = null;

$stmtUpdate = mysqli_prepare(
    $conn,
    "UPDATE listings SET status = ?, remark = ? WHERE id = ?"
);

if (!$stmtUpdate) {
    redirect('/admin/admin.php?msg=approve_failed');
}

mysqli_stmt_bind_param($stmtUpdate, 'ssi', $status, $remark, $id);

if (mysqli_stmt_execute($stmtUpdate)) {
    redirect('/admin/admin.php?msg=listing_approved');
}

redirect('/admin/admin.php?msg=approve_failed');