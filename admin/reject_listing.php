<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

if (!isAdmin()) {
    redirect('/index.php');
}

$id = (int)($_POST['id'] ?? 0);
$remark = trim($_POST['remark'] ?? '');

if ($id <= 0) {
    redirect('/admin/admin.php?msg=invalid_listing');
}

if ($remark === '') {
    redirect('/admin/admin.php?msg=reject_remark_required');
}

/* =========================
   CEK LISTING DULU
========================= */
$stmtCheck = mysqli_prepare(
    $conn,
    "SELECT id, status, title FROM listings WHERE id = ? LIMIT 1"
);

if (!$stmtCheck) {
    redirect('/admin/admin.php?msg=reject_failed');
}

mysqli_stmt_bind_param($stmtCheck, 'i', $id);
mysqli_stmt_execute($stmtCheck);
$resultCheck = mysqli_stmt_get_result($stmtCheck);
$listing = mysqli_fetch_assoc($resultCheck);

if (!$listing) {
    redirect('/admin/admin.php?msg=listing_not_found');
}

/* =========================
   JIKA SUDAH REJECTED
========================= */
if (($listing['status'] ?? '') === 'rejected') {
    redirect('/admin/admin.php?msg=already_rejected');
}

/* =========================
   UPDATE STATUS LISTING
========================= */
$status = 'rejected';

$stmtUpdate = mysqli_prepare(
    $conn,
    "UPDATE listings SET status = ?, remark = ? WHERE id = ?"
);

if (!$stmtUpdate) {
    redirect('/admin/admin.php?msg=reject_failed');
}

mysqli_stmt_bind_param($stmtUpdate, 'ssi', $status, $remark, $id);

if (mysqli_stmt_execute($stmtUpdate)) {
    redirect('/admin/admin.php?msg=listing_rejected');
}

redirect('/admin/admin.php?msg=reject_failed');