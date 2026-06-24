<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

if (!isAdmin()) {
    redirect('/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$adminNote = trim($_POST['admin_note'] ?? '');

if ($id <= 0) {
    redirect('/admin/subscriptions.php?msg=invalid_subscription');
}

if ($adminNote === '') {
    redirect('/admin/subscriptions.php?msg=reject_note_required');
}

$stmtCheck = mysqli_prepare(
    $conn,
    "SELECT s.id, s.user_id, s.payment_status, u.name AS user_name, u.email AS user_email
     FROM subscriptions s
     JOIN users u ON s.user_id = u.id
     WHERE s.id = ?
     LIMIT 1"
);

if (!$stmtCheck) {
    redirect('/admin/subscriptions.php?msg=reject_failed');
}

mysqli_stmt_bind_param($stmtCheck, 'i', $id);
mysqli_stmt_execute($stmtCheck);
$resultCheck = mysqli_stmt_get_result($stmtCheck);
$subscription = mysqli_fetch_assoc($resultCheck);

if (!$subscription) {
    redirect('/admin/subscriptions.php?msg=subscription_not_found');
}

$currentStatus = $subscription['payment_status'] ?? '';

if ($currentStatus === 'rejected') {
    redirect('/admin/subscriptions.php?msg=already_rejected');
}

if ($currentStatus === 'paid') {
    redirect('/admin/subscriptions.php?msg=already_paid');
}

$rejected = 'rejected';

$stmtUpdate = mysqli_prepare(
    $conn,
    "UPDATE subscriptions
     SET payment_status = ?, admin_note = ?
     WHERE id = ?"
);

if (!$stmtUpdate) {
    redirect('/admin/subscriptions.php?msg=reject_failed');
}

mysqli_stmt_bind_param($stmtUpdate, 'ssi', $rejected, $adminNote, $id);

if (!mysqli_stmt_execute($stmtUpdate)) {
    redirect('/admin/subscriptions.php?msg=reject_failed');
}

sendSubscriptionStatusEmail(
    $subscription['user_email'] ?? '',
    $subscription['user_name'] ?? '',
    'rejected',
    $adminNote
);

redirect('/admin/subscriptions.php?msg=rejected');