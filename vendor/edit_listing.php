<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!isVendor()) {
    redirect('/index.php');
}

$userId = (int) currentUser()['id'];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('/vendor/dashboard.php');
}

$error = '';

$stmt = mysqli_prepare($conn, "SELECT * FROM listings WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$listing = mysqli_fetch_assoc($result);

if (!$listing) {
    redirect('/vendor/dashboard.php');
}

$minPrice = defined('PRICE_MIN') ? PRICE_MIN : 10000;
$maxPrice = defined('PRICE_MAX') ? PRICE_MAX : 999999999999;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = normalizePriceInput($_POST['price'] ?? '0');
    $location = trim($_POST['location'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $addressDetail = trim($_POST['address_detail'] ?? '');
    $googleMapsLink = trim($_POST['google_maps_link'] ?? '');
    $status = 'pending';
    $remark = null;
    $imageName = $listing['image'];

    if (
        $title === '' ||
        $category === '' ||
        $description === '' ||
        $location === '' ||
        $companyName === '' ||
        $contactPerson === '' ||
        $contactPhone === '' ||
        $province === '' ||
        $city === '' ||
        $district === '' ||
        $addressDetail === ''
    ) {
        $error = 'Semua field wajib diisi.';
    } elseif ($price < $minPrice) {
        $error = 'Harga minimum adalah ' . formatRupiah($minPrice) . '.';
    } elseif ($price > $maxPrice) {
        $error = 'Harga maksimum adalah ' . formatRupiah($maxPrice) . '.';
    } else {
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . '/../assets/uploads/listings/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $tmpName = $_FILES['image']['tmp_name'];
            $originalName = $_FILES['image']['name'];
            $fileSize = $_FILES['image']['size'];
            $fileError = $_FILES['image']['error'];

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

            if ($fileError !== 0) {
                $error = 'Terjadi kesalahan saat upload gambar.';
            } elseif (!in_array($ext, $allowedExt, true)) {
                $error = 'Format gambar harus jpg, jpeg, png, atau webp.';
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 2MB.';
            } else {
                $newImageName = 'listing_' . time() . '_' . uniqid() . '.' . $ext;
                $destination = $uploadDir . $newImageName;

                if (move_uploaded_file($tmpName, $destination)) {
                    if (!empty($listing['image'])) {
                        $oldImagePath = $uploadDir . $listing['image'];
                        if (file_exists($oldImagePath)) {
                            @unlink($oldImagePath);
                        }
                    }
                    $imageName = $newImageName;
                } else {
                    $error = 'Gagal menyimpan gambar baru.';
                }
            }
        }

        if ($error === '') {
            $stmtUpdate = mysqli_prepare(
                $conn,
                "UPDATE listings SET
                    company_name = ?,
                    contact_person = ?,
                    contact_phone = ?,
                    title = ?,
                    category = ?,
                    description = ?,
                    price = ?,
                    location = ?,
                    province = ?,
                    city = ?,
                    district = ?,
                    address_detail = ?,
                    google_maps_link = ?,
                    image = ?,
                    status = ?,
                    remark = ?
                 WHERE id = ? AND user_id = ?"
            );

            mysqli_stmt_bind_param(
                $stmtUpdate,
                'ssssssdsssssssssii',
                $companyName,
                $contactPerson,
                $contactPhone,
                $title,
                $category,
                $description,
                $price,
                $location,
                $province,
                $city,
                $district,
                $addressDetail,
                $googleMapsLink,
                $imageName,
                $status,
                $remark,
                $id,
                $userId
            );

            if (mysqli_stmt_execute($stmtUpdate)) {
                redirect('/vendor/dashboard.php');
            } else {
                $error = 'Gagal memperbarui listing.';
            }
        }
    }

    if ($error === '') {
        $stmtReload = mysqli_prepare($conn, "SELECT * FROM listings WHERE id = ? AND user_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtReload, 'ii', $id, $userId);
        mysqli_stmt_execute($stmtReload);
        $reloadResult = mysqli_stmt_get_result($stmtReload);
        $listing = mysqli_fetch_assoc($reloadResult);
    } else {
        $listing['company_name'] = $companyName;
        $listing['contact_person'] = $contactPerson;
        $listing['contact_phone'] = $contactPhone;
        $listing['title'] = $title;
        $listing['category'] = $category;
        $listing['description'] = $description;
        $listing['price'] = $price;
        $listing['location'] = $location;
        $listing['province'] = $province;
        $listing['city'] = $city;
        $listing['district'] = $district;
        $listing['address_detail'] = $addressDetail;
        $listing['google_maps_link'] = $googleMapsLink;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="form-section form-section-premium">
    <div class="dashboard-grid-lines"></div>
    <div class="dashboard-background-glow dashboard-glow-1"></div>
    <div class="dashboard-background-glow dashboard-glow-2"></div>

    <div class="container">
        <div class="form-card form-card-premium reveal-up">
            <div class="form-card-shine"></div>

            <div class="form-head-premium">
                <span class="dashboard-badge">Vendor Listing</span>
                <h1>Edit Listing</h1>
                <p>Perbarui data listing Anda. Setelah disimpan ulang, listing akan masuk kembali ke proses review admin.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($listing['remark'])): ?>
                <div class="alert alert-danger">
                    <strong>Catatan admin:</strong> <?= e($listing['remark']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" class="premium-form-grid">
                <div class="form-grid-2">
                    <div>
                        <label>Nama PT / Instansi / Mitra</label>
                        <input type="text" name="company_name" value="<?= e($listing['company_name'] ?? ''); ?>" required>
                    </div>

                    <div>
                        <label>Nama PIC</label>
                        <input type="text" name="contact_person" value="<?= e($listing['contact_person'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div>
                        <label>Nomor WhatsApp / Telepon Aktif</label>
                        <input type="text" name="contact_phone" value="<?= e($listing['contact_phone'] ?? ''); ?>" required>
                    </div>

                    <div>
                        <label>Judul Listing</label>
                        <input type="text" name="title" value="<?= e($listing['title']); ?>" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div>
                        <label>Kategori</label>
                        <select name="category" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="barang" <?= ($listing['category'] ?? '') === 'barang' ? 'selected' : ''; ?>>Barang</option>
                            <option value="jasa" <?= ($listing['category'] ?? '') === 'jasa' ? 'selected' : ''; ?>>Jasa</option>
                            <option value="konstruksi" <?= ($listing['category'] ?? '') === 'konstruksi' ? 'selected' : ''; ?>>Konstruksi</option>
                            <option value="konsultansi" <?= ($listing['category'] ?? '') === 'konsultansi' ? 'selected' : ''; ?>>Konsultansi</option>
                        </select>
                    </div>

                    <div>
                        <label for="price_display">Harga / Nilai Penawaran</label>
                        <input
                            id="price_display"
                            type="text"
                            placeholder="Contoh: 150.000.000"
                            value="<?= e((string)$listing['price']); ?>"
                            required
                        >
                        <input
                            id="price"
                            type="hidden"
                            name="price"
                            value="<?= e((string)$listing['price']); ?>"
                        >
                        <small class="form-help-text">Minimal <?= e(formatRupiah($minPrice)); ?> dan maksimal <?= e(formatRupiah($maxPrice)); ?></small>
                    </div>
                </div>

                <div>
                    <label>Deskripsi</label>
                    <textarea name="description" rows="6" required><?= e($listing['description']); ?></textarea>
                </div>

                <div class="form-grid-2">
                    <div>
                        <label for="province_select">Provinsi</label>
                        <select id="province_select" required>
                            <option value="">Memuat provinsi...</option>
                        </select>
                        <input type="hidden" id="province" name="province" value="<?= e($listing['province'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="city_select">Kota / Kabupaten</label>
                        <select id="city_select" required disabled>
                            <option value="">Pilih provinsi dulu</option>
                        </select>
                        <input type="hidden" id="city" name="city" value="<?= e($listing['city'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div>
                        <label for="district_select">Kecamatan</label>
                        <select id="district_select" required disabled>
                            <option value="">Pilih kota / kabupaten dulu</option>
                        </select>
                        <input type="hidden" id="district" name="district" value="<?= e($listing['district'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="location">Lokasi Singkat</label>
                        <input
                            id="location"
                            type="text"
                            name="location"
                            value="<?= e($listing['location'] ?? ''); ?>"
                            readonly
                            required
                        >
                    </div>
                </div>

                <div>
                    <label>Alamat Detail</label>
                    <textarea name="address_detail" rows="4" required><?= e($listing['address_detail'] ?? ''); ?></textarea>
                </div>

                <div class="form-grid-2">
                    <div>
                        <label>Link Google Maps</label>
                        <input type="url" name="google_maps_link" value="<?= e($listing['google_maps_link'] ?? ''); ?>">
                    </div>

                    <div>
                        <label>Foto Barang / Jasa</label>
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>

                <?php if (!empty($listing['image'])): ?>
                    <div class="current-image-preview">
                        <span>Foto saat ini</span>
                        <img src="<?= BASE_URL; ?>/assets/uploads/listings/<?= e($listing['image']); ?>" alt="Foto Listing">
                    </div>
                <?php endif; ?>

                <div class="form-action-row">
                    <a href="<?= BASE_URL; ?>/vendor/dashboard.php" class="btn btn-outline-dark">Kembali ke Dashboard</a>
                    <button type="submit" class="btn btn-primary">Update Listing</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const priceDisplay = document.getElementById('price_display');
    const priceHidden = document.getElementById('price');

    const provinceSelect = document.getElementById('province_select');
    const citySelect = document.getElementById('city_select');
    const districtSelect = document.getElementById('district_select');

    const provinceInput = document.getElementById('province');
    const cityInput = document.getElementById('city');
    const districtInput = document.getElementById('district');
    const locationInput = document.getElementById('location');

    const oldProvince = <?= json_encode($listing['province'] ?? '') ?>;
    const oldCity = <?= json_encode($listing['city'] ?? '') ?>;
    const oldDistrict = <?= json_encode($listing['district'] ?? '') ?>;

    function formatNumberWithDots(value) {
        const numbers = String(value).replace(/[^\d]/g, '');
        return numbers.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    if (priceDisplay && priceHidden) {
        priceDisplay.value = formatNumberWithDots(priceHidden.value || priceDisplay.value || '');

        priceDisplay.addEventListener('input', function () {
            const raw = this.value.replace(/[^\d]/g, '');
            this.value = formatNumberWithDots(raw);
            priceHidden.value = raw;
        });
    }

    function fillSelect(select, items, placeholder, selectedName = '') {
        select.innerHTML = '<option value="">' + placeholder + '</option>';

        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.name;
            option.textContent = item.name;
            option.dataset.id = item.id;
            if (selectedName && item.name.toLowerCase() === selectedName.toLowerCase()) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        select.disabled = false;
    }

    function setLocation() {
        const city = cityInput.value.trim();
        const province = provinceInput.value.trim();
        locationInput.value = city && province ? city + ', ' + province : city || province || '';
    }

    async function loadProvinces() {
        try {
            const response = await fetch('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
            const data = await response.json();
            fillSelect(provinceSelect, data, '-- Pilih Provinsi --', oldProvince);
            provinceInput.value = provinceSelect.value || '';

            if (provinceSelect.selectedOptions[0] && provinceSelect.selectedOptions[0].dataset.id) {
                await loadCities(provinceSelect.selectedOptions[0].dataset.id, oldCity);
            }
        } catch (error) {
            provinceSelect.innerHTML = '<option value="">Gagal memuat provinsi</option>';
        }
    }

    async function loadCities(provinceId, selectedCity = '') {
        citySelect.innerHTML = '<option value="">Memuat kota / kabupaten...</option>';
        citySelect.disabled = true;
        districtSelect.innerHTML = '<option value="">Pilih kota / kabupaten dulu</option>';
        districtSelect.disabled = true;
        districtInput.value = '';

        try {
            const response = await fetch('https://www.emsifa.com/api-wilayah-indonesia/api/regencies/' + provinceId + '.json');
            const data = await response.json();
            fillSelect(citySelect, data, '-- Pilih Kota / Kabupaten --', selectedCity);
            cityInput.value = citySelect.value || '';
            setLocation();

            if (citySelect.selectedOptions[0] && citySelect.selectedOptions[0].dataset.id) {
                await loadDistricts(citySelect.selectedOptions[0].dataset.id, oldDistrict);
            }
        } catch (error) {
            citySelect.innerHTML = '<option value="">Gagal memuat kota / kabupaten</option>';
        }
    }

    async function loadDistricts(cityId, selectedDistrict = '') {
        districtSelect.innerHTML = '<option value="">Memuat kecamatan...</option>';
        districtSelect.disabled = true;

        try {
            const response = await fetch('https://www.emsifa.com/api-wilayah-indonesia/api/districts/' + cityId + '.json');
            const data = await response.json();
            fillSelect(districtSelect, data, '-- Pilih Kecamatan --', selectedDistrict);
            districtInput.value = districtSelect.value || '';
        } catch (error) {
            districtSelect.innerHTML = '<option value="">Gagal memuat kecamatan</option>';
        }
    }

    provinceSelect.addEventListener('change', async function () {
        provinceInput.value = this.value;
        cityInput.value = '';
        districtInput.value = '';
        setLocation();

        const selected = this.selectedOptions[0];
        if (selected && selected.dataset.id) {
            await loadCities(selected.dataset.id);
        } else {
            citySelect.innerHTML = '<option value="">Pilih provinsi dulu</option>';
            citySelect.disabled = true;
            districtSelect.innerHTML = '<option value="">Pilih kota / kabupaten dulu</option>';
            districtSelect.disabled = true;
        }
    });

    citySelect.addEventListener('change', async function () {
        cityInput.value = this.value;
        districtInput.value = '';
        setLocation();

        const selected = this.selectedOptions[0];
        if (selected && selected.dataset.id) {
            await loadDistricts(selected.dataset.id);
        } else {
            districtSelect.innerHTML = '<option value="">Pilih kota / kabupaten dulu</option>';
            districtSelect.disabled = true;
        }
    });

    districtSelect.addEventListener('change', function () {
        districtInput.value = this.value;
    });

    loadProvinces();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>