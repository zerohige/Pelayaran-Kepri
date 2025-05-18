<?php
// konfirmasi.php - Halaman konfirmasi pesanan (Updated)
require_once 'db_connection.php';

// Start session
session_start();

// Check if temp_booking exists in session
if (!isset($_SESSION['temp_booking']) || empty($_SESSION['temp_booking']['passengers'])) {
    header('Location: index.php');
    exit;
}

// Get booking data from session
$booking = $_SESSION['temp_booking'];
$lokasi_id = $booking['lokasi_id'];
$lokasi = $booking['lokasi'];
$tujuan_id = $booking['tujuan_id'];
$tujuan = $booking['tujuan'];
$penumpang = $booking['penumpang'];
$tanggal = $booking['tanggal'];
$kapal_id = $booking['kapal_id'];
$kapal_nama = $booking['kapal_nama'];
$passengers = $booking['passengers'];

// Get ship price
$ship = getShipDetails($conn, $kapal_id);
$harga_per_kapal = $ship['price'];
$total_harga = $harga_per_kapal * count($passengers);

// Hitung batas waktu pembayaran
$payment_deadline = date('d F Y', strtotime($tanggal . ' -1 day'));

// If form submitted (KONFIRMASI button is clicked)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate reservation code
    $kode = generateReservationCode();
    
    // Prepare data for insert
    $data = [
        'origin_id' => $lokasi_id,
        'destination_id' => $tujuan_id,
        'departure_date' => $tanggal,
        'ship_id' => $kapal_id,
        'passenger_count' => $penumpang,
        'total_price' => $total_harga,
        'schedule_id' => isset($booking['schedule_id']) ? $booking['schedule_id'] : null,
        'passengers' => []
    ];
    
    // Format passenger data
    foreach ($passengers as $passenger) {
        $data['passengers'][] = [
            'name' => $passenger['nama'],
            'ktp_number' => $passenger['no_ktp'],
            'phone_number' => $passenger['telepon']
        ];
    }
    
    try {
        // Insert reservation record into database
        $reservation_id = createReservation($conn, $data);
        
        // Store the reservation ID in session for the ticket page
        $_SESSION['reservation_id'] = $reservation_id;
        
        // Clear the temporary booking data
        unset($_SESSION['temp_booking']);
        
        // Redirect to ticket page
        header('Location: tiket.php');
        exit;
    } catch (Exception $e) {
        $error = "Terjadi kesalahan saat menyimpan data. Silakan coba lagi.";
    }
}

// Format tanggal
$tanggal_format = date('d F Y', strtotime($tanggal));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan - Pelayaran Kepri</title>
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

        /* Progress Indicator */
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

        /* Content Container */
        .content-container {
            width: 90%;
            max-width: 500px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            padding: 20px;
        }

        .section-title {
            color: #0a2259;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        /* Passenger cards */
        .passenger-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .passenger-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
        }

        .passenger-info {
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        .info-label {
            color: #666;
            font-size: 13px;
        }

        .info-value {
            font-weight: bold;
            text-align: right;
            font-size: 13px;
        }

        .disclaimer {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin: 20px 0;
        }

        .payment-info {
            background-color: #fff3cd; 
            border: 1px solid #ffeeba; 
            color: #856404; 
            padding: 10px; 
            margin: 15px 0; 
            border-radius: 5px;
        }

        .error-message {
            color: #d9534f;
            background-color: #f9f2f2;
            border-left: 3px solid #d9534f;
            padding: 10px;
            margin-bottom: 15px;
        }

        .submit-btn {
            background-color: #0a2259;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            text-transform: uppercase;
        }
    </style>
    <script>
    // Prevent multiple form submissions
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        form.addEventListener('submit', function() {
            // Disable the submit button
            const submitBtn = document.querySelector('.submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Memproses...';
        });
    });
    </script>
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
            <div class="step-circle step-active">3</div>
            <div class="step-text">Konfirmasi</div>
        </div>
    </div>

    <!-- Content Container -->
    <div class="content-container">
        <div class="section-title">Periksa Kembali Data Anda!</div>
        
        <!-- Passenger Cards -->
        <div class="passenger-cards">
            <?php foreach ($passengers as $index => $passenger) : ?>
                <div class="passenger-card">
                    <div class="passenger-info">
                        <div class="info-label">Nama Penumpang <?= $index+1 ?>:</div>
                        <div class="info-value"><?= htmlspecialchars($passenger['nama']) ?></div>
                    </div>
                    <div class="passenger-info">
                        <div class="info-label">No KTP:</div>
                        <div class="info-value"><?= htmlspecialchars($passenger['no_ktp']) ?></div>
                    </div>
                    <div class="passenger-info">
                        <div class="info-label">No HP:</div>
                        <div class="info-value"><?= htmlspecialchars($passenger['telepon']) ?></div>
                    </div>
                    <div class="passenger-info">
                        <div class="info-label">Tujuan:</div>
                        <div class="info-value"><?= htmlspecialchars($tujuan) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="payment-info">
            <strong>⚠️ PERHATIAN:</strong>
            <p>Silakan lakukan pembayaran di outlet pelabuhan paling lambat tanggal <strong><?php echo $payment_deadline; ?></strong> (1 hari sebelum keberangkatan).</p>
            <p>Reservasi akan HANGUS secara otomatis jika tidak dibayar sebelum batas waktu tersebut.</p>
        </div>
        
        <div class="disclaimer">
            *Silakan cek data yang telah diisi, jika sudah benar tekan konfirmasi untuk melanjutkan.
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <button type="submit" class="submit-btn">Konfirmasi</button>
        </form>
    </div>
</body>
</html><?php
// Close connection
$conn->close();
?>