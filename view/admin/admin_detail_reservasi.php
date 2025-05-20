<?php
session_start();
require_once '../../controller/db_connection.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

$reservation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reservation_id <= 0) {
    header('Location: admin_reservasi.php');
    exit;
}

// Get reservation details
$query = "SELECT r.*, 
          o.name AS origin_name, 
          d.name AS destination_name, 
          s.name AS ship_name, 
          s.price AS ship_price
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
    header('Location: admin_reservasi.php');
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

// Hitung batas waktu pembayaran
$payment_deadline = date('d F Y', strtotime($reservation['departure_date'] . ' -1 day'));

// Check for messages
$message = '';
$message_type = '';

if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'status_updated':
            $message = 'Status pembayaran berhasil diperbarui';
            $message_type = 'success';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_status':
            $message = 'Status pembayaran tidak valid';
            $message_type = 'error';
            break;
        case 'update_failed':
            $message = 'Gagal memperbarui status';
            $message_type = 'error';
            break;
    }
}

// Halaman website
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Reservasi - Admin Pelayaran Kepri</title>
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/header.css">
    <link rel="stylesheet" href="../../css/admin_detail_reservasi.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo-container">
            <img src="../../gambar/logo.png" alt="Logo">
            <div class="header-title">Admin Panel - Pelayaran Kepri</div>
        </div>
        <div class="admin-info">
            <span>Selamat datang, <?php echo $_SESSION['admin_name']; ?></span>
            <a href="admin_logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
            <li><a href="admin_reservasi.php" class="active">📋 Data Reservasi</a></li>
            <li><a href="admin_jadwal.php">🚢 Jadwal Kapal</a></li>
            <li><a href="admin_laporan.php">📈 Laporan</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <a href="admin_reservasi.php" class="back-btn">
            <img src="../../gambar/left.png" alt="Kembali" style="width: 16px; vertical-align: middle; margin-right: 4px;">
            Kembali ke Data Reservasi
        </a>
        
        <h1 class="page-title">Detail Reservasi</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="detail-container">
            <div class="detail-header">
                Informasi Reservasi - <?php echo htmlspecialchars($reservation['kode']); ?>
            </div>
            
            <div class="detail-content">
                <div class="detail-grid">
                    <!-- Informasi Reservasi -->
                    <div class="detail-section">
                        <div class="section-title">Informasi Reservasi</div>
                        <div class="detail-row">
                            <span class="detail-label">Kode Reservasi:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($reservation['kode']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tanggal Pemesanan:</span>
                            <span class="detail-value"><?php echo date('d F Y H:i', strtotime($reservation['created_at'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">
                                <?php 
                                $status_badges = [
                                    'pending' => '<span class="status-badge status-pending">Menunggu Pembayaran</span>',
                                    'paid' => '<span class="status-badge status-paid">Sudah Dibayar</span>',
                                    'expired' => '<span class="status-badge status-expired">Expired/Hangus</span>'
                                ];
                                echo $status_badges[$reservation['status']] ?? '<span class="status-badge status-pending">Menunggu Pembayaran</span>';
                                ?>
                            </span>
                        </div>
                    </div>

                    <!-- Informasi Perjalanan -->
                    <div class="detail-section">
                        <div class="section-title">Informasi Perjalanan</div>
                        <div class="detail-row">
                            <span class="detail-label">Asal:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($reservation['origin_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tujuan:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($reservation['destination_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tanggal Keberangkatan:</span>
                            <span class="detail-value"><?php echo date('d F Y', strtotime($reservation['departure_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Kapal:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($reservation['ship_name']); ?></span>
                        </div>
                    </div>

                    <!-- Informasi Pembayaran -->
                    <div class="detail-section">
                        <div class="section-title">Informasi Pembayaran</div>
                        <div class="detail-row">
                            <span class="detail-label">Jumlah Penumpang:</span>
                            <span class="detail-value"><?php echo $reservation['passenger_count']; ?> orang</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Harga per Tiket:</span>
                            <span class="detail-value"><?php echo formatRupiah($reservation['ship_price']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Harga:</span>
                            <span class="detail-value"><strong><?php echo formatRupiah($reservation['total_price']); ?></strong></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Batas Waktu Pembayaran:</span>
                            <span class="detail-value"><?php echo $payment_deadline; ?></span>
                        </div>
                    </div>

                    <!-- Update Status Pembayaran -->
                    <div class="detail-section">
                        <div class="section-title">Update Status Pembayaran</div>
                        <form method="POST" action="../../model/admin_update_status.php">
                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                            <div class="form-group">
                                <label for="status">Status Pembayaran:</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="pending" <?php echo ($reservation['status'] == 'pending') ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                                    <option value="paid" <?php echo ($reservation['status'] == 'paid') ? 'selected' : ''; ?>>Sudah Dibayar</option>
                                    <option value="expired" <?php echo ($reservation['status'] == 'expired') ? 'selected' : ''; ?>>Expired/Hangus</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-primary">Update Status</button>
                        </form>
                    </div>
                </div>

                <!-- Daftar Penumpang -->
                <div class="passengers-section">
                    <div class="section-title">Daftar Penumpang</div>
                    <?php foreach ($passengers as $index => $passenger): ?>
                        <div class="passenger-card">
                            <div class="passenger-name">
                                <?php echo ($index + 1) . '. ' . htmlspecialchars($passenger['name']); ?>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">No. KTP:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($passenger['ktp_number']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">No. Telepon:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($passenger['phone_number']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Action Buttons -->
                <div class="print-section">
                    <a href="../../model/generate_ticket_pdf.php?id=<?php echo $reservation['id']; ?>" class="print-btn">🖨️ Cetak Tiket</a>
                    <a href="../../model/admin_delete_reservasi.php?id=<?php echo $reservation['id']; ?>" 
                       class="delete-btn" 
                       onclick="return confirm('Yakin ingin menghapus reservasi ini? Tindakan ini tidak dapat dibatalkan.')">
                        🗑️ Hapus Reservasi
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
