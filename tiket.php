<?php
// tiket.php - Halaman tiket
require_once 'db_connection.php';

// Start session
session_start();

// Check if reservation ID exists in session
if (!isset($_SESSION['reservation_id'])) {
    header('Location: index.php');
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
    header('Location: index.php');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Pelayaran - Pelayaran Kepri</title>
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

        /* Ticket Container */
        /* Ticket Container */
        .ticket-container {
            width: 90%;
            max-width: 600px; /* Memperbesar lebar maksimal 20% */
            background-color: white;
            border:2px solid white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: 24px 0; /* Memperbesar jarak atas dan bawah */
            overflow: hidden;
        }

        /* Ticket Header */
        .ticket-header {
            background-color: #0a2259;
            border: white;
            color: white;
            padding: 18px; /* Memperbesar padding */
            font-size: 21px; /* Memperbesar ukuran font */
            font-weight: bold;
            text-align: center;
        }

        /* Ticket Content */
        .ticket-content {
            padding: 24px; /* Memperbesar padding */
        }

        /* Reservation Details */
        .reservation-details {
            margin-bottom: 24px; /* Memperbesar jarak bawah */
        }

        /* Detail Row */
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px; /* Memperbesar jarak antar baris detail */
            font-size: 16px; /* Memperbesar ukuran font */
        }

        /* Detail Label */
        .detail-label {
            font-weight: bold;
            color: #666;
        }

        /* Detail Value */
        .detail-value {
            text-align: right;
        }

        /* Passenger Title */
        .passenger-title {
            font-size: 19px; /* Memperbesar ukuran font */
            font-weight: bold;
            margin: 18px 0 12px; /* Memperbesar margin */
            color: #333;
        }

        /* Passenger List */
        .passenger-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        /* Passenger List Item */
        .passenger-list li {
            padding: 7px 0;
            font-size: 16px; /* Memperbesar ukuran font */
            border-bottom: 1px solid #eee;
        }

        /* Disclaimer */
        .disclaimer {
            font-size: 14px; /* Memperbesar ukuran font */
            color: #666;
            margin-top: 24px; /* Memperbesar jarak atas */
            font-style: italic;
        }

        /* Download Button */
        .download-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #0a2259;
            color: white;
            text-decoration: none;
            padding: 12px 18px; /* Memperbesar padding */
            border-radius: 20px;
            margin-top: 24px; /* Memperbesar margin atas */
            font-weight: bold;
            font-size: 16px; /* Memperbesar ukuran font */
        }

        /* Download Icon */
        .download-icon {
            margin-left: 10px;
            font-size: 20px; /* Memperbesar ukuran ikon */
        }

        /* Home Button */
        .home-btn {
            background-color: #0a2259;
            color: white;
            border: 2px solid white;
            padding: 14px 24px; /* Memperbesar padding tombol */
            border-radius: 20px;
            font-size: 16px; /* Memperbesar ukuran font tombol */
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px; /* Memperbesar margin atas tombol */
            text-decoration: none;
            display: inline-block;
        }

        /* Payment Info */
        .payment-info {
            background-color: #fff3cd; 
            border: 1px solid #ffeeba; 
            color: #856404; 
            padding: 12px; /* Memperbesar padding */
            margin: 18px 0; /* Memperbesar margin */
            border-radius: 5px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 14px; /* Memperbesar ukuran font */
            font-weight: bold;
        }

        /* Status Pending */
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        /* Status Paid */
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

        /* Status Expired */
        .status-expired {
            background-color: #f8d7da;
            color: #721c24;
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
            
           <a href="generate_ticket_pdf.php?id=<?php echo $reservation_id; ?>" class="download-btn">
                Unduh Bukti Reservasi Tiket
                <span class="download-icon">
                    <img src="gambar/unduh.png" alt="Download Icon" width="20" height="20">
                </span>
            </a>

        </div>
    </div>
    
    <a href="index.php" class="home-btn">KEMBALI KE BERANDA</a>
</body>
</html><?php
// Clear the reservation ID from session
unset($_SESSION['reservation_id']);

// Close connection
$conn->close();
?>