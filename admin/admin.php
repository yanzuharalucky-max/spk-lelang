<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$currentUser = currentUser();

if (!isAdmin()) {
    redirect('/index.php');
}

$userFilter = trim($_GET['user_role'] ?? '');
$packageFilter = trim($_GET['package'] ?? '');
$listingKeyword = trim($_GET['listing_keyword'] ?? '');
$msg = trim($_GET['msg'] ?? '');

/* =========================
   STATISTIK UTAMA
========================= */
$totalUsersQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$totalUsers = mysqli_fetch_assoc($totalUsersQuery)['total'] ?? 0;

$totalAdminsQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
$totalAdmins = mysqli_fetch_assoc($totalAdminsQuery)['total'] ?? 0;

$totalVendorsQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'vendor'");
$totalVendors = mysqli_fetch_assoc($totalVendorsQuery)['total'] ?? 0;

$totalBuyersQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'buyer'");
$totalBuyers = mysqli_fetch_assoc($totalBuyersQuery)['total'] ?? 0;

$totalListingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM listings");
$totalListings = mysqli_fetch_assoc($totalListingsQuery)['total'] ?? 0;

$totalPendingQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM listings WHERE status = 'pending'");
$totalPending = mysqli_fetch_assoc($totalPendingQuery)['total'] ?? 0;

$totalApprovedQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM listings WHERE status = 'approved'");
$totalApproved = mysqli_fetch_assoc($totalApprovedQuery)['total'] ?? 0;

$totalRejectedQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM listings WHERE status = 'rejected'");
$totalRejected = mysqli_fetch_assoc($totalRejectedQuery)['total'] ?? 0;

$totalPremiumQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE subscription = 'premium'");
$totalPremium = mysqli_fetch_assoc($totalPremiumQuery)['total'] ?? 0;

$totalFreeQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE subscription = 'free'");
$totalFree = mysqli_fetch_assoc($totalFreeQuery)['total'] ?? 0;

/* =========================
   TAMBAHAN SUMMARY
========================= */
$newUsers7DaysQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
);
$newUsers7Days = mysqli_fetch_assoc($newUsers7DaysQuery)['total'] ?? 0;

$newListings7DaysQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM listings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
);
$newListings7Days = mysqli_fetch_assoc($newListings7DaysQuery)['total'] ?? 0;

$oldestPendingQuery = mysqli_query(
    $conn,
    "SELECT id, title, created_at FROM listings WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1"
);
$oldestPending = mysqli_fetch_assoc($oldestPendingQuery);

$latestUsersQuery = mysqli_query(
    $conn,
    "SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5"
);

$latestListingsQuery = mysqli_query(
    $conn,
    "SELECT listings.title, listings.status, listings.created_at, users.name AS vendor_name
     FROM listings
     JOIN users ON listings.user_id = users.id
     ORDER BY listings.created_at DESC
     LIMIT 5"
);

/* =========================
   USER TABLE
========================= */
$userSql = "SELECT id, name, email, role, subscribed, subscription, created_at FROM users";
$userConditions = [];
$userParams = [];
$userTypes = '';

if (in_array($userFilter, ['admin', 'vendor', 'buyer'], true)) {
    $userConditions[] = "role = ?";
    $userParams[] = $userFilter;
    $userTypes .= 's';
}

if (in_array($packageFilter, ['free', 'premium'], true)) {
    $userConditions[] = "subscription = ?";
    $userParams[] = $packageFilter;
    $userTypes .= 's';
}

if (!empty($userConditions)) {
    $userSql .= " WHERE " . implode(' AND ', $userConditions);
}

$userSql .= " ORDER BY created_at DESC";

$stmtUsers = mysqli_prepare($conn, $userSql);

if (!empty($userParams)) {
    mysqli_stmt_bind_param($stmtUsers, $userTypes, ...$userParams);
}

mysqli_stmt_execute($stmtUsers);
$allUsers = mysqli_stmt_get_result($stmtUsers);

/* =========================
   LISTING TABLE
========================= */
if ($listingKeyword !== '') {
    $search = '%' . $listingKeyword . '%';
    $stmtListings = mysqli_prepare(
        $conn,
        "SELECT listings.*, users.name AS vendor_name, users.email AS vendor_email
         FROM listings
         JOIN users ON listings.user_id = users.id
         WHERE listings.title LIKE ?
            OR listings.category LIKE ?
            OR listings.location LIKE ?
            OR listings.company_name LIKE ?
            OR users.name LIKE ?
         ORDER BY listings.created_at DESC"
    );
    mysqli_stmt_bind_param($stmtListings, 'sssss', $search, $search, $search, $search, $search);
} else {
    $stmtListings = mysqli_prepare(
        $conn,
        "SELECT listings.*, users.name AS vendor_name, users.email AS vendor_email
         FROM listings
         JOIN users ON listings.user_id = users.id
         ORDER BY listings.created_at DESC"
    );
}

mysqli_stmt_execute($stmtListings);
$allListings = mysqli_stmt_get_result($stmtListings);

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/admin.css?v=8">

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('admin-page');
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.classList.add('admin-main-content');
    }
});
</script>

<section class="admin-section admin-section-premium admin-page-shell">
    <div class="admin-bg-grid"></div>
    <div class="admin-bg-orb admin-orb-1"></div>
    <div class="admin-bg-orb admin-orb-2"></div>
    <div class="admin-bg-orb admin-orb-3"></div>

    <div class="container admin-container-premium">

        <?php if ($msg === 'subscription_updated'): ?>
            <div class="admin-alert success reveal-up">Paket user berhasil diperbarui.</div>
        <?php elseif ($msg === 'subscription_failed'): ?>
            <div class="admin-alert danger reveal-up">Gagal memperbarui paket user.</div>
        <?php elseif ($msg === 'subscription_invalid'): ?>
            <div class="admin-alert danger reveal-up">Data paket tidak valid.</div>
        <?php elseif ($msg === 'subscription_same'): ?>
            <div class="admin-alert success reveal-up">Paket user sudah sesuai, tidak ada perubahan.</div>

        <?php elseif ($msg === 'listing_approved'): ?>
            <div class="admin-alert success reveal-up">Listing berhasil di-approve.</div>
        <?php elseif ($msg === 'approve_failed'): ?>
            <div class="admin-alert danger reveal-up">Gagal melakukan approve listing.</div>
        <?php elseif ($msg === 'listing_not_found'): ?>
            <div class="admin-alert danger reveal-up">Listing tidak ditemukan.</div>
        <?php elseif ($msg === 'already_approved'): ?>
            <div class="admin-alert success reveal-up">Listing ini sudah berstatus approved.</div>
        <?php elseif ($msg === 'invalid_listing'): ?>
            <div class="admin-alert danger reveal-up">ID listing tidak valid.</div>

        <?php elseif ($msg === 'listing_rejected'): ?>
            <div class="admin-alert success reveal-up">Listing berhasil di-reject.</div>
        <?php elseif ($msg === 'reject_failed'): ?>
            <div class="admin-alert danger reveal-up">Gagal melakukan reject listing.</div>
        <?php elseif ($msg === 'already_rejected'): ?>
            <div class="admin-alert success reveal-up">Listing ini sudah berstatus rejected.</div>
        <?php elseif ($msg === 'reject_remark_required'): ?>
            <div class="admin-alert danger reveal-up">Alasan reject wajib diisi.</div>

        <?php elseif ($msg === 'listing_deleted'): ?>
            <div class="admin-alert success reveal-up">Listing berhasil dihapus.</div>
        <?php elseif ($msg === 'delete_failed'): ?>
            <div class="admin-alert danger reveal-up">Gagal menghapus listing.</div>

        <?php elseif ($msg === 'user_deleted'): ?>
            <div class="admin-alert success reveal-up">User berhasil dihapus.</div>
        <?php elseif ($msg === 'user_delete_failed'): ?>
            <div class="admin-alert danger reveal-up">Gagal menghapus user.</div>
        <?php elseif ($msg === 'user_not_found'): ?>
            <div class="admin-alert danger reveal-up">User tidak ditemukan.</div>
        <?php elseif ($msg === 'invalid_user'): ?>
            <div class="admin-alert danger reveal-up">ID user tidak valid.</div>
        <?php elseif ($msg === 'cannot_delete_self'): ?>
            <div class="admin-alert danger reveal-up">Akun Anda sendiri tidak bisa dihapus.</div>
        <?php elseif ($msg === 'cannot_delete_admin'): ?>
            <div class="admin-alert danger reveal-up">Akun admin lain tidak boleh dihapus.</div>
        <?php endif; ?>

        <div class="admin-hero reveal-up">
            <div class="admin-hero-shine"></div>

            <div class="admin-hero-main">
                <span class="admin-badge">Dashboard Monitoring</span>
                <h1>
                    Admin Panel
                    <span class="admin-hero-highlight">Saleno Premium</span>
                </h1>
                <p>
                    Pantau user, listing, approval, dan performa platform
                    langsung dari dashboard admin dengan tampilan premium,
                    jelas, profesional, dan selaras dengan seluruh halaman Seleno.
                </p>

                <div class="admin-hero-meta">
                    <div class="admin-hero-meta-card">
                        <span>Total User</span>
                        <strong><?= e((string)$totalUsers); ?></strong>
                        <small>Semua akun terdaftar</small>
                    </div>

                    <div class="admin-hero-meta-card">
                        <span>Total Listing</span>
                        <strong><?= e((string)$totalListings); ?></strong>
                        <small>Semua listing sistem</small>
                    </div>

                    <div class="admin-hero-meta-card">
                        <span>Pending Review</span>
                        <strong><?= e((string)$totalPending); ?></strong>
                        <small>Perlu tindakan admin</small>
                    </div>
                </div>
            </div>

            <div class="admin-hero-side">
                <div class="admin-mini-card">
                    <span>Login sebagai</span>
                    <strong><?= e($currentUser['name']); ?></strong>
                    <small><?= e($currentUser['email']); ?></small>

                    <div class="admin-mini-pills">
                        <span class="count-pill">Admin</span>
                        <span class="count-pill"><?= e(date('d M Y')); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-stats-grid reveal-up delay-1">
            <div class="admin-stat-card">
                <span>Total Admin</span>
                <h3><?= e((string)$totalAdmins); ?></h3>
                <p>Akun administrator</p>
            </div>

            <div class="admin-stat-card">
                <span>Total Vendor</span>
                <h3><?= e((string)$totalVendors); ?></h3>
                <p>Mitra yang bisa posting</p>
            </div>

            <div class="admin-stat-card">
                <span>Total Buyer</span>
                <h3><?= e((string)$totalBuyers); ?></h3>
                <p>Pencari barang dan jasa</p>
            </div>

            <div class="admin-stat-card">
                <span>User Premium</span>
                <h3><?= e((string)$totalPremium); ?></h3>
                <p>Paket premium aktif</p>
            </div>

            <div class="admin-stat-card highlight">
                <span>User Free</span>
                <h3><?= e((string)$totalFree); ?></h3>
                <p>Paket free</p>
            </div>
        </div>

        <div class="admin-executive-grid reveal-up delay-2">
            <div class="admin-executive-card">
                <div class="admin-card-shine"></div>
                <span class="admin-kicker">Executive Summary</span>
                <h3>Ringkasan Kinerja</h3>
                <div class="admin-executive-pills">
                    <div class="admin-executive-pill">
                        <strong><?= e((string)$newUsers7Days); ?></strong>
                        <span>User baru 7 hari</span>
                    </div>
                    <div class="admin-executive-pill">
                        <strong><?= e((string)$newListings7Days); ?></strong>
                        <span>Listing baru 7 hari</span>
                    </div>
                    <div class="admin-executive-pill">
                        <strong><?= e((string)$totalApproved); ?></strong>
                        <span>Listing approved</span>
                    </div>
                    <div class="admin-executive-pill">
                        <strong><?= e((string)$totalRejected); ?></strong>
                        <span>Listing rejected</span>
                    </div>
                </div>
            </div>

            <div class="admin-health-card">
                <div class="admin-card-shine"></div>
                <span class="admin-kicker">System Health</span>
                <h3>Status Sistem</h3>
                <ul class="admin-health-list">
                    <li><span>Database User</span><strong>Stabil</strong></li>
                    <li><span>Approval Queue</span><strong><?= e((string)$totalPending); ?> Pending</strong></li>
                    <li><span>Premium Adoption</span><strong><?= e((string)$totalPremium); ?> User</strong></li>
                    <li><span>Aktivitas Mingguan</span><strong><?= e((string)$newListings7Days); ?> Listing Baru</strong></li>
                </ul>
            </div>
        </div>

        <div class="admin-dashboard-top-grid reveal-up delay-2">
            <div class="admin-panel admin-chart-panel">
                <div class="admin-panel-shine"></div>
                <div class="premium-table-head">
                    <div>
                        <span class="table-badge">Performance Snapshot</span>
                        <h2>Chart Placeholder</h2>
                        <p>Tempat ideal untuk grafik pertumbuhan user, listing, dan approval rate.</p>
                    </div>
                </div>

                <div class="chart-placeholder-grid">
                    <div class="chart-bar-card">
                        <span>Jan</span>
                        <div class="chart-bar"><i style="height: 36%;"></i></div>
                    </div>
                    <div class="chart-bar-card">
                        <span>Feb</span>
                        <div class="chart-bar"><i style="height: 52%;"></i></div>
                    </div>
                    <div class="chart-bar-card">
                        <span>Mar</span>
                        <div class="chart-bar"><i style="height: 64%;"></i></div>
                    </div>
                    <div class="chart-bar-card">
                        <span>Apr</span>
                        <div class="chart-bar"><i style="height: 76%;"></i></div>
                    </div>
                    <div class="chart-bar-card">
                        <span>May</span>
                        <div class="chart-bar"><i style="height: 58%;"></i></div>
                    </div>
                    <div class="chart-bar-card">
                        <span>Jun</span>
                        <div class="chart-bar"><i style="height: 86%;"></i></div>
                    </div>
                </div>
            </div>

            <div class="admin-side-stack">
                <div class="admin-quick-card">
                    <div class="admin-card-shine"></div>
                    <span class="admin-kicker">Quick Actions</span>
                    <h3>Aksi Cepat</h3>
                    <div class="admin-quick-actions">
                        <a href="#user-management" class="btn btn-primary btn-sm">Kelola User</a>
                        <a href="#listing-approval" class="btn btn-outline-dark btn-sm">Approval Listing</a>
                        <a href="<?= BASE_URL; ?>/admin/subscriptions.php" class="btn btn-outline-dark btn-sm">Pembayaran Premium</a>
                        <a href="<?= BASE_URL; ?>/admin/admin.php?user_role=vendor" class="btn btn-outline-dark btn-sm">Vendor Aktif</a>
                    </div>
                </div>

                <div class="admin-priority-card">
                    <div class="admin-card-shine"></div>
                    <span class="admin-kicker">Priority Queue</span>
                    <h3>Perlu Tindakan Cepat</h3>
                    <ul class="admin-priority-list">
                        <li>
                            <span>Listing Pending</span>
                            <strong><?= e((string)$totalPending); ?> item</strong>
                        </li>
                        <li>
                            <span>User Baru 7 Hari</span>
                            <strong><?= e((string)$newUsers7Days); ?> akun</strong>
                        </li>
                        <li>
                            <span>Pending Tertua</span>
                            <strong><?= !empty($oldestPending['title']) ? e($oldestPending['title']) : 'Tidak ada'; ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="admin-summary-grid reveal-up delay-2">
            <div class="admin-note-card">
                <div class="admin-card-shine"></div>
                <h3>Ringkasan Monitoring</h3>
                <p>
                    Dashboard ini membantu admin memantau akun, status paket, kelengkapan data,
                    dan proses approval listing dengan tampilan premium yang lebih cepat dibaca.
                </p>
            </div>

            <div class="admin-note-card">
                <div class="admin-card-shine"></div>
                <h3>Prioritas Hari Ini</h3>
                <p>
                    Fokus utama ada pada listing pending, validasi data vendor, pembaruan paket user,
                    dan menjaga kualitas data agar tetap rapi dan siap tayang.
                </p>
            </div>
        </div>

        <div class="admin-activity-grid reveal-up delay-3">
            <div class="admin-panel">
                <div class="admin-panel-shine"></div>
                <div class="admin-panel-head">
                    <div>
                        <span class="panel-kicker">Recent Accounts</span>
                        <h2>Aktivitas User Terbaru</h2>
                    </div>
                    <span>5 akun terakhir</span>
                </div>

                <div class="admin-list">
                    <?php if ($latestUsersQuery && mysqli_num_rows($latestUsersQuery) > 0): ?>
                        <?php while ($recentUser = mysqli_fetch_assoc($latestUsersQuery)): ?>
                            <div class="admin-list-item">
                                <div>
                                    <strong><?= e($recentUser['name']); ?></strong>
                                    <p><?= e($recentUser['email']); ?></p>
                                </div>
                                <div class="admin-list-side">
                                    <span class="role-badge role-<?= e($recentUser['role']); ?>">
                                        <?= strtoupper(e($recentUser['role'])); ?>
                                    </span>
                                    <small><?= date('d M Y H:i', strtotime($recentUser['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="admin-list-item">
                            <div>
                                <strong>Belum ada aktivitas user.</strong>
                                <p>Data akun terbaru akan muncul di sini.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-panel">
                <div class="admin-panel-shine"></div>
                <div class="admin-panel-head">
                    <div>
                        <span class="panel-kicker">Recent Listings</span>
                        <h2>Feed Listing Terbaru</h2>
                    </div>
                    <span>5 listing terakhir</span>
                </div>

                <div class="admin-list">
                    <?php if ($latestListingsQuery && mysqli_num_rows($latestListingsQuery) > 0): ?>
                        <?php while ($recentListing = mysqli_fetch_assoc($latestListingsQuery)): ?>
                            <div class="admin-list-item">
                                <div>
                                    <strong><?= e($recentListing['title']); ?></strong>
                                    <p>Vendor: <?= e($recentListing['vendor_name']); ?></p>
                                </div>
                                <div class="admin-list-side">
                                    <?php if (($recentListing['status'] ?? '') === 'approved'): ?>
                                        <span class="status-badge status-active">Approved</span>
                                    <?php elseif (($recentListing['status'] ?? '') === 'rejected'): ?>
                                        <span class="status-badge status-inactive">Rejected</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Pending</span>
                                    <?php endif; ?>
                                    <small><?= date('d M Y H:i', strtotime($recentListing['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="admin-list-item">
                            <div>
                                <strong>Belum ada aktivitas listing.</strong>
                                <p>Data listing terbaru akan muncul di sini.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="admin-panel reveal-up delay-2" id="user-management">
            <div class="admin-panel-shine"></div>

            <div class="admin-panel-head">
                <div>
                    <span class="panel-kicker">User Management</span>
                    <h2>Manajemen User & Paket</h2>
                </div>
                <span>Filter, ubah paket, dan hapus user</span>
            </div>

            <form method="GET" class="admin-filter-form">
                <div class="admin-filter-grid admin-filter-grid-3">
                    <div>
                        <label for="user_role">Filter Role</label>
                        <select name="user_role" id="user_role">
                            <option value="">Semua Role</option>
                            <option value="admin" <?= $userFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="vendor" <?= $userFilter === 'vendor' ? 'selected' : ''; ?>>Vendor</option>
                            <option value="buyer" <?= $userFilter === 'buyer' ? 'selected' : ''; ?>>Buyer</option>
                        </select>
                    </div>

                    <div>
                        <label for="package">Filter Paket</label>
                        <select name="package" id="package">
                            <option value="">Semua Paket</option>
                            <option value="free" <?= $packageFilter === 'free' ? 'selected' : ''; ?>>Free</option>
                            <option value="premium" <?= $packageFilter === 'premium' ? 'selected' : ''; ?>>Premium</option>
                        </select>
                    </div>

                    <div class="admin-filter-actions">
                        <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                        <a href="<?= BASE_URL; ?>/admin/admin.php" class="btn btn-outline-dark">Reset</a>
                    </div>
                </div>
            </form>

            <div class="table-card-premium">
                <div class="table-card-shine"></div>
                <div class="premium-table-head">
                    <div>
                        <span class="table-badge">User Table</span>
                        <h2>Daftar User Sistem</h2>
                        <p>Kelola role, status paket, dan akun pengguna dari satu panel yang rapi.</p>
                    </div>
                </div>

                <div class="table-wrap premium-table-wrap">
                    <table class="admin-table premium-dashboard-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Paket</th>
                                <th>Ubah Paket</th>
                                <th>Tanggal</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($allUsers && mysqli_num_rows($allUsers) > 0): ?>
                                <?php while ($user = mysqli_fetch_assoc($allUsers)): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($user['name']); ?></strong><br>
                                            <small><?= e($user['email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="role-badge role-<?= e($user['role']); ?>">
                                                <?= strtoupper(e($user['role'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ((int)$user['subscribed'] === 1): ?>
                                                <span class="status-badge status-active">Aktif</span>
                                            <?php else: ?>
                                                <span class="status-badge status-inactive">Free</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($user['subscription'] ?? '') === 'premium'): ?>
                                                <span class="badge-complete">Premium</span>
                                            <?php else: ?>
                                                <span class="badge-warning">Free</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($user['role'] ?? '') !== 'admin'): ?>
                                                <form method="POST" action="<?= BASE_URL; ?>/admin/update_subscription.php" class="inline-package-form">
                                                    <input type="hidden" name="id" value="<?= (int)$user['id']; ?>">
                                                    <select name="subscription">
                                                        <option value="free" <?= ($user['subscription'] ?? '') === 'free' ? 'selected' : ''; ?>>Free</option>
                                                        <option value="premium" <?= ($user['subscription'] ?? '') === 'premium' ? 'selected' : ''; ?>>Premium</option>
                                                    </select>
                                                    <button type="submit" class="btn-action btn-view">Simpan</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="btn-action btn-disabled">Admin Tetap</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if ((int)$user['id'] !== (int)$currentUser['id']): ?>
                                                <a href="<?= BASE_URL; ?>/admin/delete_user.php?id=<?= (int)$user['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus user ini?');">Hapus</a>
                                            <?php else: ?>
                                                <span class="btn-action btn-disabled">Akun Anda</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">Belum ada data user.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="admin-panel reveal-up delay-3" id="listing-approval">
            <div class="admin-panel-shine"></div>

            <div class="admin-panel-head">
                <div>
                    <span class="panel-kicker">Listing Approval</span>
                    <h2>Approval & Manajemen Listing</h2>
                </div>
                <span>Approve, reject, atau hapus listing</span>
            </div>

            <form method="GET" class="admin-filter-form">
                <div class="admin-filter-grid listing-filter-grid">
                    <div>
                        <label for="listing_keyword">Cari Listing</label>
                        <input
                            type="text"
                            name="listing_keyword"
                            id="listing_keyword"
                            value="<?= e($listingKeyword); ?>"
                            placeholder="Cari judul, kategori, lokasi, vendor, atau nama PT..."
                        >
                    </div>

                    <div class="admin-filter-actions">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <a href="<?= BASE_URL; ?>/admin/admin.php" class="btn btn-outline-dark">Reset</a>
                    </div>
                </div>
            </form>

            <div class="table-card-premium">
                <div class="table-card-shine"></div>
                <div class="premium-table-head">
                    <div>
                        <span class="table-badge">Listing Table</span>
                        <h2>Daftar Listing Sistem</h2>
                        <p>Review kualitas data listing dan lakukan approval dengan lebih cepat.</p>
                    </div>
                </div>

                <div class="table-wrap premium-table-wrap">
                    <table class="admin-table premium-dashboard-table">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Judul</th>
                                <th>PT / Mitra</th>
                                <th>Kontak</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                                <th>Kelengkapan</th>
                                <th>Remark</th>
                                <th width="380">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($allListings && mysqli_num_rows($allListings) > 0): ?>
                                <?php while ($listing = mysqli_fetch_assoc($allListings)): ?>
                                    <?php $check = checkListingCompleteness($listing); ?>
                                    <?php
                                    $rowClass = '';
                                    if ($check['status'] === 'complete') {
                                        $rowClass = 'row-complete';
                                    } elseif ($check['status'] === 'almost') {
                                        $rowClass = 'row-warning';
                                    } else {
                                        $rowClass = 'row-danger';
                                    }
                                    ?>
                                    <tr class="<?= $rowClass; ?>">
                                        <td>
                                            <?php if (!empty($listing['image'])): ?>
                                                <img
                                                    src="<?= BASE_URL; ?>/assets/uploads/listings/<?= e($listing['image']); ?>"
                                                    alt="Listing"
                                                    style="width:70px;height:70px;object-fit:cover;border-radius:10px;"
                                                >
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($listing['title']); ?></td>
                                        <td><?= e($listing['company_name'] ?? '-'); ?></td>
                                        <td>
                                            <?= e($listing['contact_person'] ?? '-'); ?><br>
                                            <small><?= e($listing['contact_phone'] ?? '-'); ?></small>
                                        </td>
                                        <td>
                                            <?= e($listing['district'] ?? '-'); ?>,
                                            <?= e($listing['city'] ?? '-'); ?>,
                                            <?= e($listing['province'] ?? '-'); ?>
                                        </td>
                                        <td>
                                            <?php if (($listing['status'] ?? '') === 'approved'): ?>
                                                <span class="status-badge status-active">Approved</span>
                                            <?php elseif (($listing['status'] ?? '') === 'rejected'): ?>
                                                <span class="status-badge status-inactive">Rejected</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($check['status'] === 'complete'): ?>
                                                <span class="badge-complete">Lengkap</span>
                                            <?php elseif ($check['status'] === 'almost'): ?>
                                                <span class="badge-warning">Hampir</span><br>
                                                <small><?= e(implode(', ', $check['missing'])); ?></small>
                                            <?php else: ?>
                                                <span class="badge-danger">Kurang</span><br>
                                                <small><?= e(implode(', ', $check['missing'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= !empty($listing['remark']) ? e($listing['remark']) : '-'; ?>
                                        </td>
                                        <td>
                                            <div class="action-group">
                                                <a
                                                    href="<?= BASE_URL; ?>/admin/detail_listing.php?id=<?= (int)$listing['id']; ?>"
                                                    class="btn-action btn-view"
                                                >
                                                    Detail
                                                </a>

                                                <?php if (($listing['status'] ?? '') !== 'approved'): ?>
                                                    <?php if ($check['status'] === 'complete'): ?>
                                                        <a
                                                            href="<?= BASE_URL; ?>/admin/approve_listing.php?id=<?= (int)$listing['id']; ?>"
                                                            class="btn-action btn-approve"
                                                            onclick="return confirm('Approve listing ini?');"
                                                        >
                                                            Approve
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="btn-action btn-disabled">Belum Siap</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if (($listing['status'] ?? '') !== 'rejected'): ?>
                                                    <form method="POST" action="<?= BASE_URL; ?>/admin/reject_listing.php" class="reject-form">
                                                        <input type="hidden" name="id" value="<?= (int)$listing['id']; ?>">
                                                        <input type="text" name="remark" placeholder="Alasan reject." required>
                                                        <button type="submit" class="btn-action btn-reject">Reject</button>
                                                    </form>
                                                <?php endif; ?>

                                                <a
                                                    href="<?= BASE_URL; ?>/admin/delete_listing.php?id=<?= (int)$listing['id']; ?>"
                                                    class="btn-action btn-delete"
                                                    onclick="return confirm('Yakin ingin menghapus listing ini?');"
                                                >
                                                    Hapus
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9">Belum ada data listing.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const reveals = document.querySelectorAll('.reveal-up');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, { threshold: 0.12 });

    reveals.forEach((item) => observer.observe(item));
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>