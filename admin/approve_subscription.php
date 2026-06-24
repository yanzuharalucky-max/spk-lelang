<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

if (!isAdmin()) {
    redirect('/index.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('/admin/subscriptions.php?msg=invalid_subscription');
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT s.id, s.user_id, s.payment_status, s.package_name, s.amount, u.name AS user_name, u.email AS user_email
     FROM subscriptions s
     JOIN users u ON s.user_id = u.id
     WHERE s.id = ?
     LIMIT 1"
);

if (!$stmt) {
    redirect('/admin/subscriptions.php?msg=approve_failed');
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$subscription = mysqli_fetch_assoc($result);

if (!$subscription) {
    redirect('/admin/subscriptions.php?msg=subscription_not_found');
}

$currentStatus = $subscription['payment_status'] ?? '';

if ($currentStatus === 'paid') {
    redirect('/admin/subscriptions.php?msg=already_paid');
}

if ($currentStatus === 'rejected') {
    redirect('/admin/subscriptions.php?msg=already_rejected');
}

mysqli_begin_transaction($conn);

try {
    $paid = 'paid';
    $emptyNote = '';
    $userId = (int) $subscription['user_id'];

    $stmtSub = mysqli_prepare(
        $conn,
        "UPDATE subscriptions
         SET payment_status = ?, admin_note = ?
         WHERE id = ?"
    );

    if (!$stmtSub) {
        throw new Exception('Prepare update subscription gagal.');
    }

    mysqli_stmt_bind_param($stmtSub, 'ssi', $paid, $emptyNote, $id);

    if (!mysqli_stmt_execute($stmtSub)) {
        throw new Exception('Gagal update status subscription.');
    }

    $userSubscription = 'premium';
    $subscribed = 1;

    $stmtUser = mysqli_prepare(
        $conn,
        "UPDATE users
         SET subscription = ?, subscribed = ?
         WHERE id = ?"
    );

    if (!$stmtUser) {
        throw new Exception('Prepare update user gagal.');
    }

    mysqli_stmt_bind_param($stmtUser, 'sii', $userSubscription, $subscribed, $userId);

    if (!mysqli_stmt_execute($stmtUser)) {
        throw new Exception('Gagal update user premium.');
    }

    mysqli_commit($conn);

    sendSubscriptionStatusEmail(
        $subscription['user_email'] ?? '',
        $subscription['user_name'] ?? '',
        'paid',
        ''
    );

    redirect('/admin/subscriptions.php?msg=approved');
} catch (Throwable $e) {
    mysqli_rollback($conn);
    redirect('/admin/subscriptions.php?msg=approve_failed');
}