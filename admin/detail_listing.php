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
    redirect('/admin/admin.php?msg=invalid_listing');
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT listings.*, users.name AS vendor_name, users.email AS vendor_email
     FROM listings
     JOIN users ON listings.user_id = users.id
     WHERE listings.id = ?
     LIMIT 1"
);

if (!$stmt) {
    redirect('/admin/admin.php?msg=listing_not_found');
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$listing = mysqli_fetch_assoc($result);

if (!$listing) {
    redirect('/admin/admin.php?msg=listing_not_found');
}

$check = checkListingCompleteness($listing);
$currentStatus = $listing['status'] ?? 'pending';

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/admin.css?v=6">

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

        <div class="admin-hero reveal-up">
            <div class="admin-hero-shine"></div>

            <div class="admin-hero-main">
                <span class="admin-badge">Detail Listing</span>
                <h1>
                    Review Listing
                    <span class="admin-hero-highlight">Lebih Detail,</span>
                    Lebih Aman
                </h1>
                <p>
                    Tinjau informasi listing vendor secara lengkap sebelum approve, reject,
                    atau hapus data. Halaman ini dibuat supaya proses review admin lebih rapi,
                    cepat dibaca, dan konsisten dengan tampilan premium Seleno.
                </p>

                <div class="admin-hero-meta">
                    <div class="admin-hero-meta-card">
                        <span>Status Listing</span>
                        <strong><?= e(ucfirst($currentStatus)); ?></strong>
                        <small>Posisi data saat ini</small>
                    </div>

                    <div class="admin-hero-meta-card">
                        <span>Kelengkapan</span>
                        <strong>
                            <?php if ($check['status'] === 'complete'): ?>
                                Lengkap
                            <?php elseif ($check['status'] === 'almost'): ?>
                                Hampir
                            <?php else: ?>
                                Kurang
                            <?php endif; ?>
                        </strong>
                        <small>Evaluasi kelengkapan listing</small>
                    </div>

                    <div class="admin-hero-meta-card">
                        <span>Tanggal Dibuat</span>
                        <strong><?= e(date('d M Y', strtotime($listing['created_at']))); ?></strong>
                        <small><?= e(date('H:i', strtotime($listing['created_at']))); ?> WIB</small>
                    </div>
                </div>
            </div>

            <div class="admin-hero-side">
                <div class="admin-mini-card">
                    <span>Kategori</span>
                    <strong><?= e($listing['category'] ?? '-'); ?></strong>
                    <small><?= e($listing['company_name'] ?? 'Mitra / perusahaan belum diisi'); ?></small>

                    <div class="admin-mini-pills">
                        <?php if ($currentStatus === 'approved'): ?>
                            <span class="status-badge badge-approved">Approved</span>
                        <?php elseif ($currentStatus === 'rejected'): ?>
                            <span class="status-badge badge-rejected">Rejected</span>
                        <?php else: ?>
                            <span class="status-badge badge-pending">Pending</span>
                        <?php endif; ?>

                        <?php if ($check['status'] === 'complete'): ?>
                            <span class="badge-complete">Lengkap</span>
                        <?php elseif ($check['status'] === 'almost'): ?>
                            <span class="badge-warning">Hampir</span>
                        <?php else: ?>
                            <span class="badge-danger">Kurang</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-detail-premium-layout reveal-up delay-1">

            <div class="admin-detail-premium-main">

                <?php if (!empty($listing['image'])): ?>
                    <div class="table-card-premium admin-detail-media-card">
                        <div class="table-card-shine"></div>
                        <div class="premium-table-head">
                            <div>
                                <span class="table-badge">Preview Listing</span>
                                <h2>Gambar Utama</h2>
                                <p>Preview visual utama dari listing vendor.</p>
                            </div>
                        </div>

                        <div class="admin-detail-image-wrap">
                            <img
                                src="<?= BASE_URL; ?>/assets/uploads/listings/<?= e($listing['image']); ?>"
                                alt="<?= e($listing['title']); ?>"
                                class="admin-detail-image-premium"
                            >
                        </div>
                    </div>
                <?php endif; ?>

                <div class="table-card-premium">
                    <div class="table-card-shine"></div>
                    <div class="premium-table-head">
                        <div>
                            <span class="table-badge">Informasi Utama</span>
                            <h2><?= e($listing['title']); ?></h2>
                            <p>Ringkasan inti listing yang akan dilihat admin sebelum mengambil keputusan.</p>
                        </div>
                    </div>

                    <div class="admin-detail-info-grid">
                        <div class="admin-detail-info-item">
                            <span>Harga</span>
                            <strong><?= e(formatRupiah($listing['price'] ?? 0)); ?></strong>
                        </div>
                        <div class="admin-detail-info-item">
                            <span>Kategori</span>
                            <strong><?= e($listing['category'] ?? '-'); ?></strong>
                        </div>
                        <div class="admin-detail-info-item">
                            <span>Lokasi Singkat</span>
                            <strong><?= e($listing['location'] ?? '-'); ?></strong>
                        </div>
                        <div class="admin-detail-info-item">
                            <span>Vendor</span>
                            <strong><?= e($listing['vendor_name'] ?? '-'); ?></strong>
                        </div>
                    </div>

                    <div class="admin-detail-content-block">
                        <h3>Deskripsi Lengkap</h3>
                        <div class="admin-detail-richtext">
                            <?= nl2br(e($listing['description'] ?? '-')); ?>
                        </div>
                    </div>
                </div>

                <div class="admin-detail-grid-2">
                    <div class="table-card-premium">
                        <div class="table-card-shine"></div>
                        <div class="premium-table-head">
                            <div>
                                <span class="table-badge">Data Vendor</span>
                                <h2>Informasi Mitra / Perusahaan</h2>
                                <p>Data identitas vendor dan PIC yang terkait dengan listing ini.</p>
                            </div>
                        </div>

                        <div class="admin-detail-data-grid">
                            <div class="admin-detail-data-card">
                                <span>Nama PT / Mitra</span>
                                <strong><?= e($listing['company_name'] ?? '-'); ?></strong>
                            </div>
                            <div class="admin-detail-data-card">
                                <span>Nama PIC</span>
                                <strong><?= e($listing['contact_person'] ?? '-'); ?></strong>
                            </div>
                            <div class="admin-detail-data-card">
                                <span>No. Telepon / WA</span>
                                <strong><?= e($listing['contact_phone'] ?? '-'); ?></strong>
                            </div>
                            <div class="admin-detail-data-card">
                                <span>Email Vendor</span>
                                <strong><?= e($listing['vendor_email'] ?? '-'); ?></strong>
                            </div>
                            <div class="admin-detail-data-card">
                                <span>Nama Akun Vendor</span>
                                <strong><?= e($listing['vendor_name'] ?? '-'); ?></strong>
                            </div>
                            <div class="admin-detail-data-card">
                                <span>Dibuat Pada</span>
                                <strong><?= e(date('d M Y H:i', strtotime($listing['created_at']))); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="table-card-premium">
                        <div class="table-card-shine"></div>
                        <div class="premium-table-head">
                            <div>
                                <span class="table-badge">Validasi Admin</span>
                                <h2>Status & Kelengkapan</h2>
                                <p>Checklist cepat untuk membantu admin mengambil keputusan.</p>
                            </div>
                        </div>

                        <div class="admin-health-list admin-detail-check-list">
                            <li>
                                <span>Status Listing</span>
                                <strong>
                                    <?php if ($currentStatus === 'approved'): ?>
                                        Approved
                                    <?php elseif ($currentStatus === 'rejected'): ?>
                                        Rejected
                                    <?php else: ?>
                                        Pending
                                    <?php endif; ?>
                                </strong>
                            </li>
                            <li>
                                <span>Kelengkapan Data</span>
                                <strong>
                                    <?php if ($check['status'] === 'complete'): ?>
                                        Lengkap
                                    <?php elseif ($check['status'] === 'almost'): ?>
                                        Hampir Lengkap
                                    <?php else: ?>
                                        Masih Kurang
                                    <?php endif; ?>
                                </strong>
                            </li>
                            <li>
                                <span>Gambar Listing</span>
                                <strong><?= !empty($listing['image']) ? 'Tersedia' : 'Belum ada'; ?></strong>
                            </li>
                            <li>
                                <span>Google Maps</span>
                                <strong><?= !empty($listing['google_maps_link']) ? 'Tersedia' : 'Belum ada'; ?></strong>
                            </li>
                        </div>

                        <div class="admin-detail-missing-box">
                            <h3>Catatan Kelengkapan</h3>
                            <?php if ($check['status'] === 'complete'): ?>
                                <p>Semua komponen utama listing sudah terisi dengan baik dan siap direview lebih lanjut.</p>
                            <?php else: ?>
                                <ul class="admin-detail-missing-list">
                                    <?php foreach (($check['missing'] ?? []) as $missingItem): ?>
                                        <li><?= e($missingItem); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="table-card-premium">
                    <div class="table-card-shine"></div>
                    <div class="premium-table-head">
                        <div>
                            <span class="table-badge">Lokasi</span>
                            <h2>Informasi Lokasi Lengkap</h2>
                            <p>Lokasi operasional atau titik terkait listing vendor.</p>
                        </div>
                    </div>

                    <div class="admin-detail-data-grid">
                        <div class="admin-detail-data-card">
                            <span>Lokasi Singkat</span>
                            <strong><?= e($listing['location'] ?? '-'); ?></strong>
                        </div>
                        <div class="admin-detail-data-card">
                            <span>Provinsi</span>
                            <strong><?= e($listing['province'] ?? '-'); ?></strong>
                        </div>
                        <div class="admin-detail-data-card">
                            <span>Kota / Kabupaten</span>
                            <strong><?= e($listing['city'] ?? '-'); ?></strong>
                        </div>
                        <div class="admin-detail-data-card">
                            <span>Kecamatan</span>
                            <strong><?= e($listing['district'] ?? '-'); ?></strong>
                        </div>
                    </div>

                    <div class="admin-detail-content-block">
                        <h3>Alamat Detail</h3>
                        <div class="admin-detail-richtext">
                            <?= nl2br(e($listing['address_detail'] ?? '-')); ?>
                        </div>
                    </div>

                    <?php if (!empty($listing['google_maps_link'])): ?>
                        <div class="admin-detail-map-link">
                            <a href="<?= e($listing['google_maps_link']); ?>" target="_blank" class="btn btn-primary">
                                Buka Google Maps
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="table-card-premium">
                    <div class="table-card-shine"></div>
                    <div class="premium-table-head">
                        <div>
                            <span class="table-badge">Catatan Admin</span>
                            <h2>Remark / Riwayat Review</h2>
                            <p>Catatan hasil review sebelumnya atau alasan keputusan admin.</p>
                        </div>
                    </div>

                    <div class="admin-detail-richtext">
                        <?= !empty($listing['remark']) ? nl2br(e($listing['remark'])) : '-'; ?>
                    </div>
                </div>
            </div>

            <aside class="admin-detail-premium-sidebar">
                <div class="table-card-premium admin-detail-sticky-card">
                    <div class="table-card-shine"></div>
                    <div class="premium-table-head">
                        <div>
                            <span class="table-badge">Aksi Admin</span>
                            <h2>Keputusan Listing</h2>
                            <p>Lakukan tindakan utama terhadap listing dari panel ini.</p>
                        </div>
                    </div>

                    <div class="action-group vertical-action-group">
                        <a href="<?= BASE_URL; ?>/admin/admin.php#listing-approval" class="btn btn-outline-dark">
                            Kembali ke Dashboard
                        </a>

                        <?php if (($listing['status'] ?? '') !== 'approved'): ?>
                            <?php if ($check['status'] === 'complete'): ?>
                                <a
                                    href="<?= BASE_URL; ?>/admin/approve_listing.php?id=<?= (int)$listing['id']; ?>"
                                    class="btn-action btn-approve"
                                    onclick="return confirm('Approve listing ini?');"
                                >
                                    Approve Listing
                                </a>
                            <?php else: ?>
                                <span class="btn-action btn-disabled">Belum Siap Approve</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (($listing['status'] ?? '') !== 'rejected'): ?>
                            <form method="POST" action="<?= BASE_URL; ?>/admin/reject_listing.php" class="reject-form detail-reject-form">
                                <input type="hidden" name="id" value="<?= (int)$listing['id']; ?>">
                                <input type="text" name="remark" placeholder="Tulis alasan reject..." required>
                                <button type="submit" class="btn-action btn-reject">Reject Listing</button>
                            </form>
                        <?php endif; ?>

                        <a
                            href="<?= BASE_URL; ?>/admin/delete_listing.php?id=<?= (int)$listing['id']; ?>"
                            class="btn-action btn-delete"
                            onclick="return confirm('Yakin ingin menghapus listing ini?');"
                        >
                            Hapus Listing
                        </a>
                    </div>
                </div>
            </aside>
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