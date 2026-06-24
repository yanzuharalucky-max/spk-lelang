<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

if (!isAdmin()) {
    redirect('/index.php');
}

$statusFilter = trim($_GET['status'] ?? '');
$msg = trim($_GET['msg'] ?? '');

/* =========================
   SUMMARY STATS
========================= */
$summaryQuery = mysqli_query(
    $conn,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) AS total_pending,
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS total_paid,
        SUM(CASE WHEN payment_status = 'rejected' THEN 1 ELSE 0 END) AS total_rejected,
        SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) AS pending_amount
     FROM subscriptions"
);

$summary = mysqli_fetch_assoc($summaryQuery) ?: [];
$totalSubscriptions = (int)($summary['total'] ?? 0);
$totalPending = (int)($summary['total_pending'] ?? 0);
$totalPaid = (int)($summary['total_paid'] ?? 0);
$totalRejected = (int)($summary['total_rejected'] ?? 0);
$pendingAmount = (float)($summary['pending_amount'] ?? 0);

/* =========================
   TABLE DATA
========================= */
$sql = "SELECT subscriptions.*, users.name, users.email
        FROM subscriptions
        JOIN users ON subscriptions.user_id = users.id";

$params = [];
$types = '';
$conditions = [];

if (in_array($statusFilter, ['pending', 'paid', 'rejected'], true)) {
    $conditions[] = "subscriptions.payment_status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY subscriptions.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$subscriptions = mysqli_stmt_get_result($stmt);

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/admin.css?v=7">

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

        <?php if ($msg === 'approved'): ?>
            <div class="admin-alert success reveal-up">
                Pembayaran berhasil disetujui dan user sudah menjadi premium.
            </div>
        <?php elseif ($msg === 'rejected'): ?>
            <div class="admin-alert danger reveal-up">
                Pembayaran berhasil ditolak.
            </div>
        <?php elseif ($msg === 'approve_failed'): ?>
            <div class="admin-alert danger reveal-up">
                Gagal menyetujui pembayaran subscription.
            </div>
        <?php elseif ($msg === 'reject_failed'): ?>
            <div class="admin-alert danger reveal-up">
                Gagal menolak pembayaran subscription.
            </div>
        <?php elseif ($msg === 'invalid_subscription'): ?>
            <div class="admin-alert danger reveal-up">
                ID subscription tidak valid.
            </div>
        <?php elseif ($msg === 'subscription_not_found'): ?>
            <div class="admin-alert danger reveal-up">
                Data subscription tidak ditemukan.
            </div>
        <?php elseif ($msg === 'already_paid'): ?>
            <div class="admin-alert success reveal-up">
                Pembayaran ini sudah berstatus paid.
            </div>
        <?php elseif ($msg === 'already_rejected'): ?>
            <div class="admin-alert success reveal-up">
                Pembayaran ini sudah berstatus rejected.
            </div>
        <?php elseif ($msg === 'reject_note_required'): ?>
            <div class="admin-alert danger reveal-up">
                Alasan penolakan wajib diisi.
            </div>
        <?php endif; ?>

        <div class="admin-hero reveal-up">
            <div class="admin-hero-shine"></div>

            <div class="admin-hero-main">
                <span class="admin-badge">Verifikasi Payment</span>
                <h1>
                    Kelola Pembayaran
                    <span class="admin-hero-highlight">Premium Lebih Cepat,</span>
                    Lebih Rapi, dan Aman
                </h1>
                <p>
                    Review bukti transfer, cek referensi pembayaran, lalu approve atau reject
                    subscription dari satu halaman yang lebih profesional dan selaras dengan
                    tampilan utama Seleno.
                </p>

                <div class="admin-hero-meta">
                    <div class="admin-hero-meta-card">
                        <span>Total Pengajuan</span>
                        <strong><?= e((string)$totalSubscriptions); ?></strong>
                        <small>Semua data subscription</small>
                    </div>

                    <div class="admin-hero-meta-card">
                        <span>Menunggu Review</span>
                        <strong><?= e((string)$totalPending); ?></strong>
                        <small>Butuh tindakan admin</small>
                    </div>

                    <div class="admin-hero-meta-card">
                        <span>Nominal Pending</span>
                        <strong><?= e(formatRupiah($pendingAmount)); ?></strong>
                        <small>Total pembayaran pending</small>
                    </div>
                </div>
            </div>

            <div class="admin-hero-side">
                <div class="admin-mini-card reveal-right">
                    <span>Ringkasan Status</span>
                    <strong>Monitoring Subscription</strong>
                    <small>
                        Halaman ini membantu admin memverifikasi pembayaran premium dengan
                        alur yang lebih jelas dan cepat dibaca.
                    </small>

                    <div class="admin-mini-pills">
                        <span class="count-pill">Paid <?= e((string)$totalPaid); ?></span>
                        <span class="count-pill">Pending <?= e((string)$totalPending); ?></span>
                        <span class="count-pill">Rejected <?= e((string)$totalRejected); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-stats-grid reveal-up delay-1">
            <div class="admin-stat-card">
                <span>Total Pembayaran</span>
                <h3><?= e((string)$totalSubscriptions); ?></h3>
                <p>Seluruh pengajuan payment premium</p>
            </div>

            <div class="admin-stat-card">
                <span>Pembayaran Pending</span>
                <h3><?= e((string)$totalPending); ?></h3>
                <p>Perlu review admin secepatnya</p>
            </div>

            <div class="admin-stat-card">
                <span>Pembayaran Paid</span>
                <h3><?= e((string)$totalPaid); ?></h3>
                <p>Sudah disetujui dan aktif</p>
            </div>

            <div class="admin-stat-card">
                <span>Pembayaran Rejected</span>
                <h3><?= e((string)$totalRejected); ?></h3>
                <p>Butuh follow up user bila perlu</p>
            </div>

            <div class="admin-stat-card">
                <span>Queue Saat Ini</span>
                <h3><?= e((string)$totalPending); ?></h3>
                <p>Prioritas kerja admin hari ini</p>
            </div>
        </div>

        <div class="admin-panel reveal-up delay-2">
            <div class="admin-panel-shine"></div>

            <div class="admin-panel-head">
                <div>
                    <span class="panel-kicker">Filter Pembayaran</span>
                    <h2>Verifikasi Pembayaran Premium</h2>
                    <p>
                        Gunakan filter untuk fokus ke status tertentu, lalu review bukti pembayaran
                        dengan lebih cepat dan konsisten.
                    </p>
                </div>
            </div>

            <form method="GET" class="admin-filter-form">
                <div class="admin-filter-grid">
                    <div>
                        <label for="status">Filter Status Pembayaran</label>
                        <select name="status" id="status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>

                    <div class="admin-filter-actions">
                        <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                        <a href="<?= BASE_URL; ?>/admin/subscriptions.php" class="btn btn-outline-dark">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-card-premium reveal-up delay-3">
            <div class="table-card-shine"></div>

            <div class="premium-table-head">
                <div>
                    <span class="table-badge">Tabel Verifikasi</span>
                    <h2>Daftar Pembayaran Subscription</h2>
                    <p>
                        Approve atau reject pembayaran berdasarkan bukti transfer, referensi, dan catatan admin.
                    </p>
                </div>
            </div>

            <div class="table-wrap premium-table-wrap">
                <table class="admin-table premium-dashboard-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Paket</th>
                            <th>Jumlah</th>
                            <th>Metode</th>
                            <th>Referensi</th>
                            <th>Bukti</th>
                            <th>Status</th>
                            <th>Catatan</th>
                            <th>Tanggal</th>
                            <th width="260">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($subscriptions && mysqli_num_rows($subscriptions) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($subscriptions)): ?>
                                <?php
                                    $proofFile = trim($row['payment_proof'] ?? '');
                                    $proofUrl = $proofFile !== ''
                                        ? BASE_URL . '/assets/uploads/payments/' . rawurlencode($proofFile)
                                        : '';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= e($row['name']); ?></strong><br>
                                        <small><?= e($row['email']); ?></small>
                                    </td>

                                    <td><?= e($row['package_name']); ?></td>
                                    <td><?= e(formatRupiah($row['amount'])); ?></td>
                                    <td><?= e($row['payment_method']); ?></td>
                                    <td><?= e($row['reference_code'] ?? '-'); ?></td>

                                    <td>
                                        <?php if ($proofFile !== ''): ?>
                                            <div class="subscription-proof-actions">
                                                <button
                                                    type="button"
                                                    class="btn-action btn-view js-open-proof"
                                                    data-proof-url="<?= e($proofUrl); ?>"
                                                    data-proof-user="<?= e($row['name']); ?>"
                                                    data-proof-package="<?= e($row['package_name']); ?>"
                                                    data-proof-amount="<?= e(formatRupiah($row['amount'])); ?>"
                                                >
                                                    Preview
                                                </button>

                                                <a
                                                    class="btn-action btn-outline-proof"
                                                    target="_blank"
                                                    href="<?= e($proofUrl); ?>"
                                                >
                                                    Tab Baru
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($row['payment_status'] === 'paid'): ?>
                                            <span class="status-badge badge-approved">Paid</span>
                                        <?php elseif ($row['payment_status'] === 'rejected'): ?>
                                            <span class="status-badge badge-rejected">Rejected</span>
                                        <?php else: ?>
                                            <span class="status-badge badge-pending">Pending</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= !empty($row['admin_note']) ? e($row['admin_note']) : '-'; ?></td>
                                    <td><?= date('d M Y H:i', strtotime($row['created_at'])); ?></td>

                                    <td>
                                        <div class="action-group action-group-subscription">
                                            <?php if ($row['payment_status'] === 'pending'): ?>
                                                <a
                                                    href="<?= BASE_URL; ?>/admin/approve_subscription.php?id=<?= (int)$row['id']; ?>"
                                                    class="btn-action btn-approve"
                                                    onclick="return confirm('Setujui pembayaran ini?');"
                                                >
                                                    Approve
                                                </a>

                                                <form
                                                    method="POST"
                                                    action="<?= BASE_URL; ?>/admin/reject_subscription.php"
                                                    class="reject-form"
                                                >
                                                    <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                                                    <input type="text" name="admin_note" placeholder="Alasan ditolak..." required>
                                                    <button type="submit" class="btn-action btn-reject">Reject</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="btn-action btn-disabled">Selesai</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10">
                                    Belum ada data pembayaran subscription.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-summary-grid reveal-up delay-4" style="margin-top: 24px;">
            <div class="admin-note-card">
                <span class="panel-kicker">Upgrade Halaman</span>
                <h3>Preview bukti bayar sudah ada di halaman ini</h3>
                <p>
                    Sekarang admin bisa melihat bukti transfer langsung lewat modal preview,
                    jadi proses verifikasi lebih cepat tanpa perlu bolak-balik halaman.
                </p>
            </div>

            <div class="admin-note-card">
                <span class="panel-kicker">Prioritas Admin</span>
                <h3>Fokus utama hari ini</h3>
                <p>
                    Mulai dari transaksi pending paling lama dulu, lalu cek nominal, metode transfer,
                    referensi, dan bukti bayar supaya approval lebih aman dan rapi.
                </p>
            </div>
        </div>

    </div>
</section>

<div class="proof-modal" id="proofModal" aria-hidden="true">
    <div class="proof-modal-backdrop js-close-proof"></div>

    <div class="proof-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="proofModalTitle">
        <button type="button" class="proof-modal-close js-close-proof" aria-label="Tutup preview">&times;</button>

        <div class="proof-modal-head">
            <div>
                <span class="table-badge">Preview Bukti Bayar</span>
                <h2 id="proofModalTitle">Bukti Pembayaran</h2>
                <p id="proofModalMeta">Preview file pembayaran subscription.</p>
            </div>
        </div>

        <div class="proof-modal-body">
            <img id="proofModalImage" src="" alt="Bukti Pembayaran" class="proof-modal-image">
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('proofModal');
    const modalImage = document.getElementById('proofModalImage');
    const modalMeta = document.getElementById('proofModalMeta');
    const openButtons = document.querySelectorAll('.js-open-proof');
    const closeButtons = document.querySelectorAll('.js-close-proof');

    function openModal(url, user, pkg, amount) {
        if (!modal || !modalImage || !modalMeta) return;
        modalImage.src = url;
        modalMeta.textContent = `${user} • ${pkg} • ${amount}`;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        if (!modal || !modalImage) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        modalImage.src = '';
        document.body.classList.remove('modal-open');
    }

    openButtons.forEach((button) => {
        button.addEventListener('click', function () {
            openModal(
                this.dataset.proofUrl || '',
                this.dataset.proofUser || 'User',
                this.dataset.proofPackage || 'Paket',
                this.dataset.proofAmount || 'Nominal'
            );
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>