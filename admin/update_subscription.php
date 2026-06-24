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
$subscription = trim($_POST['subscription'] ?? '');

if ($id <= 0) {
    redirect('/admin/admin.php?msg=subscription_invalid');
}

if (!in_array($subscription, ['free', 'premium'], true)) {
    redirect('/admin/admin.php?msg=subscription_invalid');
}

/* =========================
   CEK USER DULU
========================= */
$stmtCheck = mysqli_prepare(
    $conn,
    "SELECT id, role, subscription, subscribed
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if (!$stmtCheck) {
    redirect('/admin/admin.php?msg=subscription_failed');
}

mysqli_stmt_bind_param($stmtCheck, 'i', $id);
mysqli_stmt_execute($stmtCheck);
$resultCheck = mysqli_stmt_get_result($stmtCheck);
$user = mysqli_fetch_assoc($resultCheck);

if (!$user) {
    redirect('/admin/admin.php?msg=user_not_found');
}

/* =========================
   ADMIN TIDAK BOLEH DIUBAH
========================= */
if (($user['role'] ?? '') === 'admin') {
    redirect('/admin/admin.php?msg=subscription_invalid');
}

/* =========================
   JIKA PAKET SAMA
========================= */
if (($user['subscription'] ?? '') === $subscription) {
    redirect('/admin/admin.php?msg=subscription_same');
}

/* =========================
   TENTUKAN STATUS SUBSCRIBED
========================= */
$subscribed = $subscription === 'premium' ? 1 : 0;

/* =========================
   UPDATE PAKET USER
========================= */
$stmtUpdate = mysqli_prepare(
    $conn,
    "UPDATE users
     SET subscription = ?, subscribed = ?
     WHERE id = ?"
);

if (!$stmtUpdate) {
    redirect('/admin/admin.php?msg=subscription_failed');
}

mysqli_stmt_bind_param($stmtUpdate, 'sii', $subscription, $subscribed, $id);

if (mysqli_stmt_execute($stmtUpdate)) {
    redirect('/admin/admin.php?msg=subscription_updated');
}

redirect('/admin/admin.php?msg=subscription_failed');