<?php
// tiket.php - Halaman tiket
require_once '../../controller/db_connection.php';

// Start session
session_start();

// Check if reservation ID exists in session
if (!isset($_SESSION['reservation_id'])) {
    header('Location: ../../index.php');
    exit;
}

$reservation_id = $_SESSION['reservation_id'];

// Get reservation details
$query = "SELECT r.*, 
          r.kode AS kode_reservasi,
          o.name AS origin_name, 
          d.name AS destination_name, 
          s.name AS ship_name, 
          s.price AS ship_price,
          r.status
          FROM reservations r
          JOIN locations o ON r.origin_id = o.id
          JOIN locations d ON r.destination_id = d.id
          JOIN ships s ON r.ship_id = s.id
          WHERE r.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../../index.php');
    exit;
}

$reservation = $result->fetch_assoc();

// Get passenger details
$query = "SELECT * FROM passengers WHERE reservation_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$passengers_result = $stmt->get_result();

$passengers = [];
while ($row = $passengers_result->fetch_assoc()) {
    $passengers[] = $row;
}

// Format tanggal
$tanggal_format = date('d F Y', strtotime($reservation['departure_date']));

// Hitung batas waktu pembayaran (1 hari sebelum keberangkatan)
$payment_deadline = date('d F Y', strtotime($reservation['departure_date'] . ' -1 day'));

// Halaman website
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Pelayaran - Pelayaran Kepri</title>
    <link rel="stylesheet" href="../../css/tiket.css">
</head>
<body>
    <!-- Header with Logo -->
    <div class="header">
        <div class="logo-container">
            <img src="../../gambar/logo.png" alt="Logo">
            <div class="title">Pelayaran Kepri</div>
        </div>
    </div>

    <!-- Ticket Container -->
    <div class="ticket-container">
        <div class="ticket-header">Informasi Reservasi Tiket Pelayaran</div>
        
        <div class="ticket-content">
            <div class="reservation-details">
                <div class="detail-row">
                    <div class="detail-label">Reservasi Tiket Pelayaran Dari:</div>
                    <div class="detail-value">Pelayaran Kepri</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Kode Pemesanan:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($reservation['kode_reservasi']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Nama Kapal:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($reservation['ship_name']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Tanggal Keberangkatan:</div>
                    <div class="detail-value"><?php echo $tanggal_format; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Tujuan Keberangkatan:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($reservation['destination_name']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Harga:</div>
                    <div class="detail-value"><?php echo formatRupiah($reservation['ship_price']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <?php 
                        $status_classes = [
                            'pending' => 'status-pending',
                            'paid' => 'status-paid',
                            'expired' => 'status-expired'
                        ];
                        $status_labels = [
                            'pending' => 'Menunggu Pembayaran',
                            'paid' => 'Sudah Dibayar',
                            'expired' => 'Expired/Hangus'
                        ];
                        $class = $status_classes[$reservation['status']] ?? 'status-pending';
                        $label = $status_labels[$reservation['status']] ?? 'Menunggu Pembayaran';
                        echo '<span class="status-badge ' . $class . '">' . $label . '</span>';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="passenger-title">Nama Penumpang:</div>
            <ol class="passenger-list">
                <?php foreach ($passengers as $passenger): ?>
                    <li><?php echo htmlspecialchars($passenger['name']); ?></li>
                <?php endforeach; ?>
            </ol>
            
            <div class="payment-info">
                <strong>⚠️ PERHATIAN:</strong>
                <p>Lakukan pembayaran di outlet pelabuhan paling lambat tanggal <strong><?php echo $payment_deadline; ?></strong> (1 hari sebelum keberangkatan).</p>
                <p>Reservasi akan HANGUS secara otomatis jika tidak dibayar sebelum batas waktu tersebut.</p>
            </div>
            
            <div class="disclaimer">
                E-tiket ini harus ditunjukkan kepada petugas untuk validasi sebelum menaiki kapal. Pastikan tiba di pelabuhan minimal 30 menit sebelum jadwal keberangkatan.
            </div>
            
           <a href="../../model/generate_ticket_pdf.php?id=<?php echo $reservation_id; ?>" class="download-btn">
                Unduh Bukti Reservasi Tiket
                <span class="download-icon">
                    <img src="../../gambar/unduh.png" alt="Download Icon" width="20" height="20">
                </span>
            </a>

        </div>
    </div>
    
    <a href="../../index.php" class="home-btn">KEMBALI KE BERANDA</a>
</body>
</html><?php
// Clear the reservation ID from session
unset($_SESSION['reservation_id']);

// Close connection
$conn->close();
?>