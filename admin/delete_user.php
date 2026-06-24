<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

if (!isAdmin()) {
    redirect('/index.php');
}

$currentUser = currentUser();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('/admin/admin.php?msg=invalid_user');
}

/* =========================
   CEGAH HAPUS AKUN SENDIRI
========================= */
if ($id === (int)$currentUser['id']) {
    redirect('/admin/admin.php?msg=cannot_delete_self');
}

/* =========================
   CEK USER DULU
========================= */
$stmtCheck = mysqli_prepare(
    $conn,
    "SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1"
);

if (!$stmtCheck) {
    redirect('/admin/admin.php?msg=user_delete_failed');
}

mysqli_stmt_bind_param($stmtCheck, 'i', $id);
mysqli_stmt_execute($stmtCheck);
$resultCheck = mysqli_stmt_get_result($stmtCheck);
$user = mysqli_fetch_assoc($resultCheck);

if (!$user) {
    redirect('/admin/admin.php?msg=user_not_found');
}

/* =========================
   CEGAH HAPUS ADMIN LAIN
========================= */
if (($user['role'] ?? '') === 'admin') {
    redirect('/admin/admin.php?msg=cannot_delete_admin');
}

/* =========================
   HAPUS USER
========================= */
$stmtDelete = mysqli_prepare(
    $conn,
    "DELETE FROM users WHERE id = ?"
);

if (!$stmtDelete) {
    redirect('/admin/admin.php?msg=user_delete_failed');
}

mysqli_stmt_bind_param($stmtDelete, 'i', $id);

if (mysqli_stmt_execute($stmtDelete)) {
    redirect('/admin/admin.php?msg=user_deleted');
}

redirect('/admin/admin.php?msg=user_delete_failed');