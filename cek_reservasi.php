<?php
// cek_reservasi.php - Halaman cek reservasi
require_once 'db_connection.php';

$reservation = null;
$error = null;
$passengers = [];

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = $_POST['kode'] ?? '';
    
    if (empty($kode)) {
        $error = "Silakan masukkan kode reservasi";
    } else {
        // Fetch reservation details
        $query = "SELECT r.*, 
                 o.name AS origin_name, 
                 d.name AS destination_name, 
                 s.name AS ship_name, 
                 s.price AS ship_price,
                 r.status
                 FROM reservations r
                 JOIN locations o ON r.origin_id = o.id
                 JOIN locations d ON r.destination_id = d.id
                 JOIN ships s ON r.ship_id = s.id
                 WHERE r.kode = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $kode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Reservasi dengan kode tersebut tidak ditemukan";
        } else {
            $reservation = $result->fetch_assoc();
            
            // Get passenger details
            $query = "SELECT * FROM passengers WHERE reservation_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $reservation['id']);
            $stmt->execute();
            $passengers_result = $stmt->get_result();
            
            while ($row = $passengers_result->fetch_assoc()) {
                $passengers[] = $row;
            }
        }
    }
}

// Hitung batas waktu pembayaran jika reservasi ditemukan
$payment_deadline = '';
if ($reservation) {
    $payment_deadline = date('d F Y', strtotime($reservation['departure_date'] . ' -1 day'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Reservasi - Pelayaran Kepri</title>
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

        /* Form Container */
        .form-container {
            width: 90%;
            max-width: 500px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            padding: 20px;
        }

        .form-title {
            color: #0a2259;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .search-btn {
            background-color: #0a2259;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        .error-message {
            color: #d9534f;
            background-color: #f9f2f2;
            border-left: 3px solid #d9534f;
            padding: 10px;
            margin: 15px 0;
        }

        /* Ticket Container */
        .ticket-container {
            width: 90%;
            max-width: 500px;
            background-color: white;
            border:2px solid white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            overflow: hidden;
            display: <?php echo $reservation ? 'block' : 'none'; ?>;
        }

        .ticket-header {
            background-color: #0a2259;
            color: white;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .ticket-content {
            padding: 20px;
        }

        .reservation-details {
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .detail-label {
            font-weight: bold;
            color: #666;
        }

        .detail-value {
            text-align: right;
        }

        .passenger-title {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0 10px;
            color: #333;
        }

        .passenger-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .passenger-list li {
            padding: 5px 0;
            font-size: 14px;
            border-bottom: 1px solid #eee;
        }

        .download-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #0a2259;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 20px;
            margin-top: 20px;
            font-weight: bold;
            font-size: 14px;
        }

        .download-icon {
            margin-left: 8px;
            font-size: 18px;
        }

        /* Button to return to home */
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

        .payment-info {
            background-color: #fff3cd; 
            border: 1px solid #ffeeba; 
            color: #856404; 
            padding: 10px; 
            margin: 15px 0; 
            border-radius: 5px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

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

    <!-- Search Form Container -->
    <div class="form-container">
        <div class="form-title">Cek Reservasi Tiket</div>
        
        <form method="POST" action="" class="search-form">
            <input type="text" name="kode" placeholder="Masukkan kode reservasi" class="search-input" value="<?php echo isset($_POST['kode']) ? htmlspecialchars($_POST['kode']) : ''; ?>">
            <button type="submit" class="search-btn">Cari</button>
        </form>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>

    <!-- Ticket Container (shown only if reservation found) -->
    <?php if ($reservation): ?>
    <div class="ticket-container">
        <div class="ticket-header">Informasi Reservasi Tiket Pelayaran</div>
        
        <div class="ticket-content">
            <div class="reservation-details">
                <div class="detail-row">
                    <div class="detail-label">Reservasi Tiket Pelayaran Dari-Menuju:</div>
                    <div class="detail-value">Pelayaran Kepri</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Kode Pemesanan:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($reservation['kode']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Nama Kapal:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($reservation['ship_name']); ?></div>
                </div>

               
               <div class="detail-row">
                   <div class="detail-label">Tanggal Keberangkatan:</div>
                   <div class="detail-value"><?php echo date('d F Y', strtotime($reservation['departure_date'])); ?></div>
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
           
           <?php if ($reservation['status'] == 'pending'): ?>
           <div class="payment-info">
               <strong>⚠️ PERHATIAN:</strong>
               <p>Lakukan pembayaran di outlet pelabuhan paling lambat tanggal <strong><?php echo $payment_deadline; ?></strong> (1 hari sebelum keberangkatan).</p>
               <p>Reservasi akan HANGUS secara otomatis jika tidak dibayar sebelum batas waktu tersebut.</p>
           </div>
           <?php endif; ?>
           
           <a href="generate_ticket_pdf.php?id=<?php echo $reservation['id']; ?>" class="download-btn">
               Unduh Bukti Reservasi Tiket
               <span class="download-icon">
                    <img src="gambar/unduh.png" alt="Download Icon" width="20" height="20">
                </span>
           </a>
       </div>
   </div>
   <?php endif; ?>
   
   <a href="index.php" class="home-btn">KEMBALI KE BERANDA</a>
</body>
</html><?php
// Close connection
$conn->close();
?>