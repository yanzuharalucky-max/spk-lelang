<?php

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function getSessionUserFromStorage(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function syncCurrentUserFromDb(): ?array
{
    $sessionUser = getSessionUserFromStorage();

    if (!$sessionUser) {
        unset($GLOBALS['__current_user_cache']);
        return null;
    }

    $userId = (int)($sessionUser['id'] ?? 0);
    if ($userId <= 0) {
        return $sessionUser;
    }

    $cachedUser = $GLOBALS['__current_user_cache'] ?? null;
    if (is_array($cachedUser) && (int)($cachedUser['id'] ?? 0) === $userId) {
        return $cachedUser;
    }

    global $conn;

    if (isset($conn) && $conn instanceof mysqli) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $freshUser = mysqli_fetch_assoc($result);

            if ($freshUser) {
                $_SESSION['user'] = $freshUser;
                $GLOBALS['__current_user_cache'] = $freshUser;
                return $freshUser;
            }
        }

        unset($_SESSION['user'], $GLOBALS['__current_user_cache']);
        return null;
    }

    $GLOBALS['__current_user_cache'] = $sessionUser;
    return $sessionUser;
}

function isLoggedIn(): bool
{
    return syncCurrentUserFromDb() !== null;
}

function currentUser(): ?array
{
    return syncCurrentUserFromDb();
}

function isAdmin(): bool
{
    $user = currentUser();
    return $user !== null && ($user['role'] ?? '') === 'admin';
}

function isVendor(): bool
{
    $user = currentUser();
    return $user !== null && ($user['role'] ?? '') === 'vendor';
}

function isBuyer(): bool
{
    $user = currentUser();
    return $user !== null && ($user['role'] ?? '') === 'buyer';
}

function isSubscribed(): bool
{
    $user = currentUser();
    return $user !== null && (int)($user['subscribed'] ?? 0) === 1;
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function updateSessionUserFromDb(mysqli $conn, int $userId): void
{
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        $_SESSION['user'] = $user;
        $GLOBALS['__current_user_cache'] = $user;
    }
}

function formatRupiah($number): string
{
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

function normalizePriceInput($value): float
{
    $clean = preg_replace('/[^\d]/', '', (string)$value);
    return $clean === '' ? 0 : (float)$clean;
}

function generateStrongPasswordSuggestion(int $length = 12): string
{
    $lower = 'abcdefghjkmnpqrstuvwxyz';
    $upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
    $numbers = '23456789';
    $symbols = '!@#$%&*?';

    $all = $lower . $upper . $numbers . $symbols;

    $password = '';
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $upper[random_int(0, strlen($upper) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $symbols[random_int(0, strlen($symbols) - 1)];

    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }

    return str_shuffle($password);
}

function checkListingCompleteness($listing)
{
    $missing = [];

    if (empty($listing['image'])) $missing[] = 'Foto';
    if (empty($listing['company_name'])) $missing[] = 'PT/Mitra';
    if (empty($listing['contact_person'])) $missing[] = 'PIC';
    if (empty($listing['contact_phone'])) $missing[] = 'Kontak';
    if (empty($listing['province'])) $missing[] = 'Provinsi';
    if (empty($listing['city'])) $missing[] = 'Kota';
    if (empty($listing['district'])) $missing[] = 'Kecamatan';
    if (empty($listing['address_detail'])) $missing[] = 'Alamat';
    if (empty($listing['description'])) $missing[] = 'Deskripsi';

    if (count($missing) === 0) {
        return [
            'status' => 'complete',
            'label' => 'Lengkap',
            'color' => 'green'
        ];
    }

    if (count($missing) <= 2) {
        return [
            'status' => 'almost',
            'label' => 'Hampir Lengkap',
            'color' => 'orange',
            'missing' => $missing
        ];
    }

    return [
        'status' => 'incomplete',
        'label' => 'Tidak Lengkap',
        'color' => 'red',
        'missing' => $missing
    ];
}

function sendAppEmail(string $to, string $subject, string $htmlMessage, string $plainMessage = ''): bool
{
    if ($to === '') {
        return false;
    }

    $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'no-reply@localhost';
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Seleno Lelang';

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    return @mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
}

function sendSubscriptionStatusEmail(string $toEmail, string $toName, string $status, string $adminNote = ''): bool
{
    $safeName = e($toName !== '' ? $toName : 'Pengguna');
    $safeNote = nl2br(e($adminNote));
    $appName = defined('APP_NAME') ? APP_NAME : 'Seleno Lelang';

    if ($status === 'paid') {
        $subject = '[' . $appName . '] Subscription Premium Disetujui';
        $html = '
            <div style="font-family: Arial, sans-serif; color:#1f2937; line-height:1.6;">
                <h2 style="margin-bottom:8px;">Subscription Premium Disetujui</h2>
                <p>Halo <strong>' . $safeName . '</strong>,</p>
                <p>Pembayaran subscription premium Anda telah <strong>disetujui</strong> oleh admin.</p>
                <p>Akun Anda sekarang dapat menggunakan akses premium pada platform <strong>' . e($appName) . '</strong>.</p>
                <p style="margin-top:20px;">Terima kasih.</p>
            </div>
        ';
        return sendAppEmail($toEmail, $subject, $html);
    }

    if ($status === 'rejected') {
        $subject = '[' . $appName . '] Subscription Premium Ditolak';
        $html = '
            <div style="font-family: Arial, sans-serif; color:#1f2937; line-height:1.6;">
                <h2 style="margin-bottom:8px;">Subscription Premium Ditolak</h2>
                <p>Halo <strong>' . $safeName . '</strong>,</p>
                <p>Pengajuan subscription premium Anda <strong>ditolak</strong> oleh admin.</p>
                <p><strong>Alasan / catatan admin:</strong></p>
                <div style="background:#f8fafc; border:1px solid #e5e7eb; padding:12px; border-radius:10px;">
                    ' . ($safeNote !== '' ? $safeNote : 'Tidak ada catatan tambahan.') . '
                </div>
                <p style="margin-top:16px;">Silakan perbaiki bukti pembayaran lalu ajukan kembali.</p>
            </div>
        ';
        return sendAppEmail($toEmail, $subject, $html);
    }

    return false;
}

function sendAuctionWinnerEmail(string $toEmail, string $toName, string $listingTitle, string $vendorName, float $bidAmount): bool
{
    $subject = '[Seleno Lelang] Anda Menang Penawaran';
    $html = '
        <div style="font-family: Arial, sans-serif; color:#1f2937; line-height:1.7;">
            <h2>Selamat, Anda Menang Penawaran</h2>
            <p>Halo <strong>' . e($toName) . '</strong>,</p>
            <p>Penawaran Anda dipilih sebagai pemenang oleh vendor.</p>
            <p><strong>Judul Listing:</strong> ' . e($listingTitle) . '</p>
            <p><strong>Vendor:</strong> ' . e($vendorName) . '</p>
            <p><strong>Nilai Penawaran:</strong> ' . e(formatRupiah($bidAmount)) . '</p>
            <p>Silakan login ke platform untuk melihat detail lebih lanjut.</p>
        </div>
    ';
    return sendAppEmail($toEmail, $subject, $html);
}

function sendAuctionLoseEmail(string $toEmail, string $toName, string $listingTitle): bool
{
    $subject = '[Seleno Lelang] Status Penawaran Anda';
    $html = '
        <div style="font-family: Arial, sans-serif; color:#1f2937; line-height:1.7;">
            <h2>Status Penawaran</h2>
            <p>Halo <strong>' . e($toName) . '</strong>,</p>
            <p>Untuk listing <strong>' . e($listingTitle) . '</strong>, penawaran Anda belum terpilih sebagai pemenang.</p>
            <p>Terima kasih telah berpartisipasi di platform Seleno Lelang.</p>
        </div>
    ';
    return sendAppEmail($toEmail, $subject, $html);
}

function getAuctionStatusLabel(string $status): string
{
    return $status === 'closed' ? 'Ditutup' : 'Dibuka';
}

function getBidStatusLabel(string $status): string
{
    switch ($status) {
        case 'winner':
            return 'Menang';
        case 'lost':
            return 'Kalah';
        case 'cancelled':
            return 'Dibatalkan';
        default:
            return 'Aktif';
    }
}

function getBidStatusClass(string $status): string
{
    switch ($status) {
        case 'winner':
            return 'status-active';
        case 'lost':
            return 'status-inactive';
        case 'cancelled':
            return 'status-inactive';
        default:
            return 'status-pending';
    }
}

function calculateSawForListing(mysqli $conn, int $listingId): bool
{
    if ($listingId <= 0) {
        return false;
    }

    $stmtBids = mysqli_prepare(
        $conn,
        "SELECT
            b.id AS bid_id,
            b.listing_id,
            b.buyer_id,
            b.bid_amount,
            COALESCE(u.rating_buyer, 4) AS rating_buyer,
            COALESCE(u.response_score, 4) AS response_score,
            COALESCE(u.transaction_history, 1) AS transaction_history
         FROM bids b
         JOIN users u ON b.buyer_id = u.id
         WHERE b.listing_id = ?
         ORDER BY b.created_at ASC, b.id ASC"
    );

    if (!$stmtBids) {
        return false;
    }

    mysqli_stmt_bind_param($stmtBids, 'i', $listingId);
    if (!mysqli_stmt_execute($stmtBids)) {
        return false;
    }

    $resultBids = mysqli_stmt_get_result($stmtBids);
    if (!$resultBids || mysqli_num_rows($resultBids) === 0) {
        return false;
    }

    $bidRows = [];
    $minPrice = null;
    $maxRating = 0.0;
    $maxResponse = 0.0;
    $maxHistory = 0;

    while ($row = mysqli_fetch_assoc($resultBids)) {
        $bidAmount = (float)($row['bid_amount'] ?? 0);
        $ratingBuyer = (float)($row['rating_buyer'] ?? 4);
        $responseScore = (float)($row['response_score'] ?? 4);
        $transactionHistory = (int)($row['transaction_history'] ?? 1);

        $row['bid_amount'] = $bidAmount;
        $row['rating_buyer'] = $ratingBuyer;
        $row['response_score'] = $responseScore;
        $row['transaction_history'] = $transactionHistory;
        $bidRows[] = $row;

        if ($minPrice === null || $bidAmount < $minPrice) {
            $minPrice = $bidAmount;
        }

        if ($ratingBuyer > $maxRating) {
            $maxRating = $ratingBuyer;
        }

        if ($responseScore > $maxResponse) {
            $maxResponse = $responseScore;
        }

        if ($transactionHistory > $maxHistory) {
            $maxHistory = $transactionHistory;
        }
    }

    if (!$bidRows) {
        return false;
    }

    $scoredRows = [];

    foreach ($bidRows as $row) {
        $normalizedPrice = ($minPrice !== null && $minPrice > 0 && $row['bid_amount'] > 0)
            ? $minPrice / $row['bid_amount']
            : 0.0;
        $normalizedRating = $maxRating > 0
            ? $row['rating_buyer'] / $maxRating
            : 0.0;
        $normalizedResponse = $maxResponse > 0
            ? $row['response_score'] / $maxResponse
            : 0.0;
        $normalizedHistory = $maxHistory > 0
            ? $row['transaction_history'] / $maxHistory
            : 0.0;

        $finalScore =
            ($normalizedPrice * 0.40) +
            ($normalizedRating * 0.25) +
            ($normalizedResponse * 0.20) +
            ($normalizedHistory * 0.15);

        $row['normalized_price'] = round($normalizedPrice, 6);
        $row['normalized_rating'] = round($normalizedRating, 6);
        $row['normalized_response'] = round($normalizedResponse, 6);
        $row['normalized_history'] = round($normalizedHistory, 6);
        $row['final_score'] = round($finalScore, 6);
        $scoredRows[] = $row;
    }

    usort($scoredRows, static function (array $a, array $b): int {
        if ($a['final_score'] === $b['final_score']) {
            if ($a['bid_amount'] === $b['bid_amount']) {
                return $a['bid_id'] <=> $b['bid_id'];
            }

            return $a['bid_amount'] <=> $b['bid_amount'];
        }

        return $b['final_score'] <=> $a['final_score'];
    });

    $stmtCleanup = mysqli_prepare(
        $conn,
        "DELETE sr
         FROM spk_results sr
         LEFT JOIN bids b ON sr.bid_id = b.id
         WHERE sr.listing_id = ?
           AND (b.id IS NULL OR b.listing_id != ?)"
    );

    if (!$stmtCleanup) {
        return false;
    }

    $stmtUpsert = mysqli_prepare(
        $conn,
        "INSERT INTO spk_results (
            listing_id,
            bid_id,
            buyer_id,
            normalized_price,
            normalized_rating,
            normalized_response,
            normalized_history,
            final_score,
            rank_position,
            updated_at
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            listing_id = VALUES(listing_id),
            buyer_id = VALUES(buyer_id),
            normalized_price = VALUES(normalized_price),
            normalized_rating = VALUES(normalized_rating),
            normalized_response = VALUES(normalized_response),
            normalized_history = VALUES(normalized_history),
            final_score = VALUES(final_score),
            rank_position = VALUES(rank_position),
            updated_at = VALUES(updated_at)"
    );

    if (!$stmtUpsert) {
        return false;
    }

    mysqli_begin_transaction($conn);

    try {
        mysqli_stmt_bind_param($stmtCleanup, 'ii', $listingId, $listingId);
        if (!mysqli_stmt_execute($stmtCleanup)) {
            throw new Exception('Gagal membersihkan hasil SPK lama.');
        }

        $rankPosition = 1;
        foreach ($scoredRows as $row) {
            $updatedAt = date('Y-m-d H:i:s');
            $normalizedPrice = (float)$row['normalized_price'];
            $normalizedRating = (float)$row['normalized_rating'];
            $normalizedResponse = (float)$row['normalized_response'];
            $normalizedHistory = (float)$row['normalized_history'];
            $finalScore = (float)$row['final_score'];

            mysqli_stmt_bind_param(
                $stmtUpsert,
                'iiidddddis',
                $listingId,
                $row['bid_id'],
                $row['buyer_id'],
                $normalizedPrice,
                $normalizedRating,
                $normalizedResponse,
                $normalizedHistory,
                $finalScore,
                $rankPosition,
                $updatedAt
            );

            if (!mysqli_stmt_execute($stmtUpsert)) {
                throw new Exception('Gagal menyimpan hasil SPK.');
            }

            $rankPosition++;
        }

        mysqli_commit($conn);
        return true;
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return false;
    }
}
