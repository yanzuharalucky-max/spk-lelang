<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$currentUser = currentUser();

if (isAdmin()) {
    redirect('/admin/admin.php');
}

$error = '';
$success = '';

$userId = (int) $currentUser['id'];

$stmtUser = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmtUser, 'i', $userId);
mysqli_stmt_execute($stmtUser);
$resultUser = mysqli_stmt_get_result($stmtUser);
$userData = mysqli_fetch_assoc($resultUser);

if (($userData['subscription'] ?? '') === 'premium' && (int)($userData['subscribed'] ?? 0) === 1) {
    $success = 'Akun Anda sudah aktif sebagai premium.';
}

$stmtLast = mysqli_prepare(
    $conn,
    "SELECT * FROM subscriptions
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 1"
);
mysqli_stmt_bind_param($stmtLast, 'i', $userId);
mysqli_stmt_execute($stmtLast);
$resultLast = mysqli_stmt_get_result($stmtLast);
$lastSubscription = mysqli_fetch_assoc($resultLast);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $success === '') {
    $paymentMethod = trim($_POST['payment_method'] ?? '');
    $referenceCode = trim($_POST['reference_code'] ?? '');
    $packageName = 'premium';
    $amount = 150000.00;
    $paymentStatus = 'pending';
    $paymentProof = null;

    $allowedMethods = [
        'Dana',
        'OVO',
        'GoPay',
        'ShopeePay',
        'Transfer BCA',
        'Transfer BRI',
        'QRIS'
    ];

    if (!in_array($paymentMethod, $allowedMethods, true)) {
        $error = 'Metode pembayaran tidak valid.';
    } elseif ($referenceCode === '') {
        $error = 'Nomor referensi / catatan pembayaran wajib diisi.';
    } elseif (empty($_FILES['payment_proof']['name'])) {
        $error = 'Bukti pembayaran wajib diupload dalam bentuk foto / screenshot.';
    } else {
        $uploadDir = __DIR__ . '/../assets/uploads/payments/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tmpName = $_FILES['payment_proof']['tmp_name'];
        $originalName = $_FILES['payment_proof']['name'];
        $fileSize = $_FILES['payment_proof']['size'];
        $fileError = $_FILES['payment_proof']['error'];

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

        if ($fileError !== 0) {
            $error = 'Terjadi kesalahan saat upload bukti pembayaran.';
        } elseif (!in_array($ext, $allowedExt, true)) {
            $error = 'Format bukti pembayaran harus berupa jpg, jpeg, png, atau webp.';
        } elseif ($fileSize > 3 * 1024 * 1024) {
            $error = 'Ukuran file maksimal 3MB.';
        } else {
            $paymentProof = 'payment_' . time() . '_' . uniqid() . '.' . $ext;
            $destination = $uploadDir . $paymentProof;

            if (!move_uploaded_file($tmpName, $destination)) {
                $error = 'Gagal menyimpan bukti pembayaran.';
            }
        }
    }

    if ($error === '') {
        $stmtInsert = mysqli_prepare(
            $conn,
            "INSERT INTO subscriptions (
                user_id,
                package_name,
                amount,
                payment_method,
                reference_code,
                payment_proof,
                payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmtInsert,
            'isdssss',
            $userId,
            $packageName,
            $amount,
            $paymentMethod,
            $referenceCode,
            $paymentProof,
            $paymentStatus
        );

        if (mysqli_stmt_execute($stmtInsert)) {
            redirect('/buyer/subscribe.php?msg=pending');
        } else {
            $error = 'Gagal mengirim permintaan subscription.';
        }
    }
}

$msg = trim($_GET['msg'] ?? '');

include __DIR__ . '/../includes/header.php';
?>

<section class="page-section subscribe-page-premium">
    <div class="auth-grid-lines"></div>
    <div class="auth-background-glow auth-glow-1"></div>
    <div class="auth-background-glow auth-glow-2"></div>
    <div class="auth-background-glow auth-glow-3"></div>

    <div class="container">
        <div class="dashboard-hero premium-entry subscribe-hero">
            <div class="dashboard-hero-content">
                <span class="dashboard-badge">Checkout Premium</span>
                <h1>Aktifkan Subscription Premium</h1>
                <p>
                    Upload bukti pembayaran sekarang wajib berbentuk foto atau screenshot
                    agar verifikasi admin lebih cepat dan lebih jelas.
                </p>
            </div>
        </div>

        <div class="subscribe-checkout-grid subscribe-checkout-grid-premium">
            <div class="subscribe-summary-card subscribe-summary-card-premium reveal-left">
                <span class="checkout-badge">Paket Premium</span>
                <h2>Paket Premium</h2>
                <div class="checkout-price">Rp 150.000<span>/bulan</span></div>

                <ul class="checkout-benefits">
                    <li>Akses detail listing penuh</li>
                    <li>Lihat data vendor lebih lengkap</li>
                    <li>Lihat alamat dan Google Maps</li>
                    <li>Proses validasi lebih profesional</li>
                    <li>Notifikasi status dari admin melalui email</li>
                </ul>

                <div class="payment-info-box">
                    <h3>Metode Pembayaran Simulasi</h3>
                    <p><strong>Dana / OVO / GoPay / ShopeePay:</strong> 0812-0000-0000</p>
                    <p><strong>Transfer BCA:</strong> 1234567890 a.n. PT Seleno</p>
                    <p><strong>Transfer BRI:</strong> 9876543210 a.n. PT Seleno</p>
                    <p><strong>QRIS:</strong> scan ke admin lalu upload screenshot bukti bayar</p>
                </div>

                <?php if ($lastSubscription): ?>
                    <div class="last-payment-box">
                        <h3>Status Pengajuan Terakhir</h3>
                        <p><strong>Metode:</strong> <?= e($lastSubscription['payment_method']); ?></p>
                        <p>
                            <strong>Status:</strong>
                            <?php if ($lastSubscription['payment_status'] === 'paid'): ?>
                                <span class="status-badge status-active">Paid</span>
                            <?php elseif ($lastSubscription['payment_status'] === 'rejected'): ?>
                                <span class="status-badge status-inactive">Rejected</span>
                            <?php else: ?>
                                <span class="status-badge status-pending">Pending</span>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($lastSubscription['admin_note'])): ?>
                            <p><strong>Catatan Admin:</strong> <?= e($lastSubscription['admin_note']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="subscribe-form-card subscribe-form-card-premium reveal-right">
                <h2>Konfirmasi Pembayaran</h2>
                <p>Isi data pembayaran dengan benar. Bukti pembayaran wajib berupa foto atau screenshot.</p>

                <?php if ($msg === 'pending'): ?>
                    <div class="alert alert-warning">
                        Permintaan subscription berhasil dikirim dan sedang menunggu verifikasi admin.
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= e($success); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error); ?></div>
                <?php endif; ?>

                <?php if (!(($userData['subscription'] ?? '') === 'premium' && (int)($userData['subscribed'] ?? 0) === 1)): ?>
                    <form method="POST" action="" enctype="multipart/form-data" class="premium-form-grid">
                        <label>Nama User</label>
                        <div class="input-shell">
                            <input type="text" value="<?= e($userData['name']); ?>" readonly>
                        </div>

                        <label>Email</label>
                        <div class="input-shell">
                            <input type="text" value="<?= e($userData['email']); ?>" readonly>
                        </div>

                        <label for="payment_method">Metode Pembayaran</label>
                        <div class="input-shell">
                            <select name="payment_method" id="payment_method" required>
                                <option value="">-- Pilih Metode Pembayaran --</option>
                                <option value="Dana">Dana</option>
                                <option value="OVO">OVO</option>
                                <option value="GoPay">GoPay</option>
                                <option value="ShopeePay">ShopeePay</option>
                                <option value="Transfer BCA">Transfer BCA</option>
                                <option value="Transfer BRI">Transfer BRI</option>
                                <option value="QRIS">QRIS</option>
                            </select>
                        </div>

                        <label for="reference_code">Referensi / Catatan Pembayaran</label>
                        <div class="input-shell">
                            <input
                                type="text"
                                id="reference_code"
                                name="reference_code"
                                placeholder="Contoh: transfer 18.30 / kode referensi / catatan"
                                required
                            >
                        </div>

                        <label for="payment_proof">Upload Bukti Pembayaran</label>
                        <div class="input-shell">
                            <input
                                type="file"
                                id="payment_proof"
                                name="payment_proof"
                                accept=".jpg,.jpeg,.png,.webp,image/*"
                                required
                            >
                        </div>

                        <small class="form-help-text">
                            File wajib berupa screenshot / foto bukti pembayaran. PDF tidak diperbolehkan.
                        </small>

                        <div class="payment-proof-preview" id="paymentProofPreviewBox" style="display:none;">
                            <span>Preview Bukti Pembayaran</span>
                            <img id="paymentProofPreviewImage" src="" alt="Preview Bukti Pembayaran">
                        </div>

                        <div class="form-action-row">
                            <a href="<?= BASE_URL; ?>/buyer/dashboard.php" class="btn btn-outline-dark">Kembali</a>
                            <button type="submit" class="btn btn-primary">Kirim Pengajuan Premium</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">
                        Akun Anda sudah premium dan aktif.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const paymentProofInput = document.getElementById('payment_proof');
    const previewBox = document.getElementById('paymentProofPreviewBox');
    const previewImage = document.getElementById('paymentProofPreviewImage');

    if (paymentProofInput && previewBox && previewImage) {
        paymentProofInput.addEventListener('change', function () {
            const file = this.files && this.files[0] ? this.files[0] : null;

            if (!file) {
                previewBox.style.display = 'none';
                previewImage.src = '';
                return;
            }

            if (!file.type.startsWith('image/')) {
                alert('Bukti pembayaran wajib berupa gambar.');
                this.value = '';
                previewBox.style.display = 'none';
                previewImage.src = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                previewImage.src = e.target.result;
                previewBox.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>