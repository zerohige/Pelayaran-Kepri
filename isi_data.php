<?php
// isi_data.php - Halaman pengisian data penumpang
require_once 'db_connection.php';

// Start session
session_start();

// Check if temp_booking exists in session
if (!isset($_SESSION['temp_booking'])) {
    header('Location: index.php');
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
    if (empty($no_ktp)) {
        $errors[] = "Nomor KTP harus diisi";
    }
    if (empty($no_telepon)) {
        $errors[] = "Nomor telepon harus diisi";
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
            header('Location: isi_data.php?passenger='.($current_passenger + 1));
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isi Data Penumpang - Pelayaran Kepri</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #0a2259;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-image: url('gambar/background.jpg');
            background-size: cover;
            background-position: center;
        }

        /* Header Styling */
        .header {
            width: 100%;
            display: flex;
            padding: 20px;
            box-sizing: border-box;
            align-items: center;
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo-container img {
            height: 70px;
            width: auto;
            margin-right: 10px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: white;
        }

        /* Progress Container */
    /* Progress Container */
    .progress-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 90%;
        max-width: 600px; /* Memperbesar lebar maksimal */
        margin: 24px 0; /* Memperbesar margin atas dan bawah */
        background-color: white;
        border-radius: 25px;
        padding: 18px; /* Memperbesar padding */
    }

    /* Progress Step */
    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
    }

    /* Step Circle */
    .step-circle {
        width: 36px; /* Memperbesar ukuran lingkaran */
        height: 36px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 8px; /* Memperbesar jarak bawah */
        font-weight: bold;
        color: white;
        font-size: 18px; /* Memperbesar ukuran font */
    }

    /* Active Step */
    .step-active {
        background-color: #0a2259;
    }

    /* Inactive Step */
    .step-inactive {
        background-color: #ccc;
    }

    /* Step Text */
    .step-text {
        font-size: 16px; /* Memperbesar ukuran font teks langkah */
        color: #666;
    }

    /* Progress Line */
    .progress-line {
        height: 4px; /* Memperbesar ketebalan garis progress */
        background-color: #ccc;
        flex: 1;
        margin: 0 10px; /* Memperbesar jarak antara garis dan langkah */
    }

    /* Page Title */
    /* Page Title */
/* Page Title */
    .page-title {
        color: white;
        font-size: 18px; /* Mengurangi ukuran font 20% lebih kecil */
        text-align: center;
        font-weight: bold;
        margin: 12px 0 18px; /* Mengurangi margin lebih kecil */
    }

    /* Content Container */
    .content-container {
        width: 90%;
        max-width: 430px; /* Mengurangi lebar maksimal 20% lebih kecil */
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 18px; /* Mengurangi jarak bawah */
        overflow: hidden;
        padding: 18px; /* Mengurangi padding lebih kecil */
    }

    /* Passenger Header */
    .passenger-header {
        background-color: #0a2259;
        color: white;
        padding: 14px; /* Mengurangi padding lebih kecil */
        font-size: 16px; /* Mengurangi ukuran font */
        font-weight: bold;
        text-align: center;
    }

    /* Form Container */
    .form-container {
        padding: 18px; /* Mengurangi padding lebih kecil */
    }

    /* Form Group */
    .form-group {
        margin-bottom: 14px; /* Mengurangi jarak antar form */
    }

    /* Form Group Label */
    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 6px; /* Mengurangi jarak bawah label */
        color: #333;
        font-size: 14px; /* Mengurangi ukuran font label */
    }

    /* Form Group Input */
    .form-group input {
        width: 100%;
        padding: 9px; /* Mengurangi padding input lebih kecil */
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px; /* Mengurangi ukuran font input */
        box-sizing: border-box;
    }

    /* Submit Button */
    .submit-btn {
        background-color: #0a2259;
        color: white;
        border: none;
        padding: 10px 18px; /* Mengurangi padding tombol lebih kecil */
        border-radius: 20px; /* Memperbesar radius tombol */
        font-size: 16px; /* Mengurangi ukuran font tombol */
        font-weight: bold;
        cursor: pointer;
        float: right;
        margin-top: 18px; /* Mengurangi jarak atas tombol */
        margin-bottom: 18px; /* Mengurangi jarak bawah tombol */
    }

    /* Error Message */
    .error-message {
        color: #d9534f;
        background-color: #f9f2f2;
        border-left: 3px solid #d9534f;
        padding: 9px; /* Mengurangi padding pesan error */
        margin-bottom: 14px; /* Mengurangi jarak bawah pesan error */
        font-size: 14px; /* Mengurangi ukuran font pesan error */
    }

    </style>
</head>
<body>
    <!-- Header with Logo -->
    <div class="header">
        <div class="logo-container">
            <img src="gambar/logo.png" alt="Logo">
            <div class="title">Pelayaran Kepri</div>
        </div>
    </div>

    <!-- Progress Indicator -->
    <div class="progress-container">
        <div class="progress-step">
            <div class="step-circle step-active">1</div>
            <div class="step-text">Pilih Kapal</div>
        </div>
        <div class="progress-line"></div>
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
                    <input type="text" id="nama" name="nama" value="<?php echo isset($data_penumpang[$current_passenger]['nama']) ? htmlspecialchars($data_penumpang[$current_passenger]['nama']) : ''; ?>">
                </div>
                
                <!-- Form for KTP -->
                <div class="form-group">
                    <label for="no_ktp">No. KTP :</label>
                    <input type="text" id="no_ktp" name="no_ktp" value="<?php echo isset($data_penumpang[$current_passenger]['no_ktp']) ? htmlspecialchars($data_penumpang[$current_passenger]['no_ktp']) : ''; ?>" 
                        pattern="\d{16}" 
                        title="Nomor KTP harus terdiri dari 16 digit angka" 
                        required>
                </div>

                <!-- Form for HP -->
                <div class="form-group">
                    <label for="no_telepon">No. Telepon :</label>
                    <input type="tel" id="no_telepon" name="no_telepon" value="<?php echo isset($data_penumpang[$current_passenger]['telepon']) ? htmlspecialchars($data_penumpang[$current_passenger]['telepon']) : ''; ?>" 
                        pattern="\d{10,12}" 
                        title="Nomor telepon harus terdiri dari 10 hingga 12 digit angka" 
                        required>
                </div>

                <button type="submit" class="submit-btn">SELESAI</button>
            </form>
        </div>
    </div>
</body>
</html>