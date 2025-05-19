<?php
// konfirmasi.php - Halaman konfirmasi pesanan (Updated)
require_once 'controller/db_connection.php';

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

// Halaman website
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan - Pelayaran Kepri</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/konfirmasi.css">
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
            <a href="index.php">
                <img src="gambar/logo.png" alt="Logo">
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