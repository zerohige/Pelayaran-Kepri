<?php
require_once '../../controller/db_connection.php';

// Start session
session_start();

// Check if temp_booking exists in session
if (!isset($_SESSION['temp_booking'])) {
    header('Location: ../../index.php');
    exit;
}

$booking = $_SESSION['temp_booking'];
$lokasi = $booking['lokasi'];
$tujuan = $booking['tujuan'];
$penumpang = $booking['penumpang'];
$tanggal = $booking['tanggal'];
$kapal_nama = $booking['kapal_nama'];

// Inisialisasi current_passenger untuk melacak penumpang ke berapa yang sedang diisi
$current_passenger = isset($_GET['passenger']) ? (int)$_GET['passenger'] : 1;

// Jika current_passenger lebih besar dari jumlah penumpang, redirect ke konfirmasi
if ($current_passenger > $penumpang) {
    header('Location: konfirmasi.php');
    exit;
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data form
    $nama = $_POST['nama'] ?? '';
    $no_ktp = $_POST['no_ktp'] ?? '';
    $no_telepon = $_POST['no_telepon'] ?? '';

    // Validasi data
    $errors = [];
    if (empty($nama)) {
        $errors[] = "Nama lengkap harus diisi";
    }
    // Validasi No. KTP
    if (empty($no_ktp)) {
        $errors[] = "Nomor KTP harus diisi";
    } elseif (!preg_match('/^\d{16}$/', $no_ktp)) {
        $errors[] = "Nomor KTP harus terdiri dari 16 digit angka";
    }
    // Validasi No. Telepon
    if (empty($no_telepon)) {
        $errors[] = "Nomor telepon harus diisi";
    } elseif (!preg_match('/^\d{10,13}$/', $no_telepon)) {
        $errors[] = "Nomor telepon harus valid dan terdiri dari 10 hingga 13 digit angka";
    }

    // Jika tidak ada error, simpan data penumpang
    if (empty($errors)) {
        // Simpan data penumpang saat ini
        $_SESSION['temp_booking']['passengers'][$current_passenger] = [
            'nama' => $nama,
            'no_ktp' => $no_ktp,
            'telepon' => $no_telepon
        ];

        // Jika masih ada penumpang yang perlu diisi
        if ($current_passenger < $penumpang) {
            // Redirect ke penumpang berikutnya
            header('Location: isi_data.php?passenger=' . ($current_passenger + 1));
            exit;
        } else {
            // Semua penumpang sudah diisi, redirect ke konfirmasi
            header('Location: konfirmasi.php');
            exit;
        }
    }
}

// Ambil data penumpang yang mungkin sudah diisi sebelumnya
$data_penumpang = $_SESSION['temp_booking']['passengers'] ?? [];

// Halaman website
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isi Data Penumpang - Pelayaran Kepri</title>
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/isi_data.css">
</head>

<body>
    <!-- Header with Logo -->
    <div class="header">
        <div class="logo-container">
            <a href="../../index.php">
                <img src="../../gambar/logo.png" alt="Logo">
            </a>
            <div class="title">Pelayaran Kepri</div>
        </div>
    </div>

    <!-- Progress Indicator -->
    <div class="progress-container">
        <div class="progress-step">
            <div class="step-circle step-active">1</div>
            <div class="step-text">Pilih Kapal</div>
        </div>
        <div class="progress-line" id="line-1"></div>
        <div class="progress-step">
            <div class="step-circle step-active">2</div>
            <div class="step-text">Isi Data</div>
        </div>
        <div class="progress-line"></div>
        <div class="progress-step">
            <div class="step-circle step-inactive">3</div>
            <div class="step-text">Konfirmasi</div>
        </div>
    </div>

    <!-- Page Title -->
    <div class="page-title">Masukkan Data Diri Anda!</div>

    <!-- Content Container -->
    <div class="content-container">
        <div class="passenger-header">Data Penumpang <?php echo $current_passenger; ?></div>

        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nama">Nama Lengkap Sesuai KTP :</label>
                    <input type="text" id="nama" name="nama" value="<?php echo isset($data_penumpang[$current_passenger]['nama']) ? htmlspecialchars($data_penumpang[$current_passenger]['nama']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="no_ktp">No. KTP :</label>
                    <input type="text" id="no_ktp" name="no_ktp" value="<?php echo isset($data_penumpang[$current_passenger]['no_ktp']) ? htmlspecialchars($data_penumpang[$current_passenger]['no_ktp']) : ''; ?>"
                        pattern="\d{16}"
                        title="Nomor KTP harus terdiri dari 16 digit angka"
                        required
                        maxlength="16"
                        inputmode="numeric"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                </div>

                <!-- Form for HP -->
                <div class="form-group">
                    <label for="no_telepon">No. Telepon :</label>
                    <input type="text" id="no_telepon" name="no_telepon" value="<?php echo isset($data_penumpang[$current_passenger]['telepon']) ? htmlspecialchars($data_penumpang[$current_passenger]['telepon']) : ''; ?>"
                        pattern="\d{10,13}"
                        title="Nomor telepon harus terdiri dari 10 hingga 13 digit angka"
                        required
                        maxlength="13"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                </div>

                <button type="submit" class="submit-btn">SELESAI</button>
            </form>
        </div>
    </div>

</body>

</html>