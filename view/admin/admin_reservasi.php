<?php
session_start();
require_once '../../controller/db_connection.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

// Filter
$filter_date = $_GET['date'] ?? '';
$filter_status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Pesan sistem
$message = '';
$message_type = '';

if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'deleted':
            $message = 'Reservasi berhasil dihapus';
            $message_type = 'success';
            break;
        case 'error':
            $message = 'Terjadi kesalahan saat menghapus reservasi';
            $message_type = 'error';
            break;
    }
}

// Query untuk mengambil data reservasi
$query = "SELECT r.*, 
          o.name AS origin_name, 
          d.name AS destination_name, 
          s.name AS ship_name,
          r.status
          FROM reservations r
          JOIN locations o ON r.origin_id = o.id
          JOIN locations d ON r.destination_id = d.id
          JOIN ships s ON r.ship_id = s.id";

$conditions = [];
$params = [];
$types = '';

if (!empty($filter_date)) {
    $conditions[] = "DATE(r.departure_date) = ?";
    $params[] = $filter_date;
    $types .= 's';
}

if (!empty($filter_status)) {
    $conditions[] = "r.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($search)) {
    $conditions[] = "(r.kode LIKE ? OR o.name LIKE ? OR d.name LIKE ? OR s.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Halaman website
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Reservasi - Admin Pelayaran Kepri</title>
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/header.css">
    <link rel="stylesheet" href="../../css/admin_reservasi.css">
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
       <h1 class="page-title">Data Reservasi Pelayaran Kepri</h1>

       <?php if ($message): ?>
           <div class="message <?php echo $message_type; ?>">
               <?php echo $message; ?>
           </div>
       <?php endif; ?>

       <!-- Filter Section -->
       <div class="filter-section">
           <form method="GET" class="filter-form">
               <div class="filter-group">
                   <label for="date">Tanggal Keberangkatan</label>
                   <input type="date" id="date" name="date" value="<?php echo $filter_date; ?>">
               </div>
               <div class="filter-group">
                   <label for="status">Status</label>
                   <select id="status" name="status">
                       <option value="">Semua Status</option>
                       <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                       <option value="paid" <?php echo ($filter_status == 'paid') ? 'selected' : ''; ?>>Sudah Dibayar</option>
                       <option value="expired" <?php echo ($filter_status == 'expired') ? 'selected' : ''; ?>>Expired/Hangus</option>
                   </select>
               </div>
               <div class="filter-group">
                   <label for="search">Pencarian</label>
                   <input type="text" id="search" name="search" placeholder="Kode reservasi, asal, tujuan, kapal..." 
                          value="<?php echo htmlspecialchars($search); ?>">
               </div>
               <div class="filter-group">
                   <button type="submit" class="filter-btn">Filter</button>
                   <a href="admin_reservasi.php" class="reset-btn">Reset Pencarian</a>
               </div>
           </form>
       </div>

       <!-- Table -->
       <div class="table-container">
           <table class="table">
               <thead>
                   <tr>
                       <th>Kode Reservasi</th>
                       <th>Tanggal Pemesanan</th>
                       <th>Asal - Tujuan</th>
                       <th>Tanggal Keberangkatan</th>
                       <th>Kapal</th>
                       <th>Jumlah Penumpang</th>
                       <th>Total Harga</th>
                       <th>Status</th>
                       <th>Aksi</th>
                   </tr>
               </thead>
               <tbody>
                   <?php if ($result->num_rows > 0): ?>
                       <?php while ($row = $result->fetch_assoc()): ?>
                           <tr>
                               <td><strong><?php echo htmlspecialchars($row['kode']); ?></strong></td>
                               <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                               <td><?php echo htmlspecialchars($row['origin_name'] . ' - ' . $row['destination_name']); ?></td>
                               <td><?php echo date('d/m/Y', strtotime($row['departure_date'])); ?></td>
                               <td><?php echo htmlspecialchars($row['ship_name']); ?></td>
                               <td><?php echo $row['passenger_count']; ?> orang</td>
                               <td><?php echo formatRupiah($row['total_price']); ?></td>
                               <td>
                                   <?php 
                                   $status_badges = [
                                       'pending' => '<span class="status-badge status-pending">Menunggu Pembayaran</span>',
                                       'paid' => '<span class="status-badge status-paid">Sudah Dibayar</span>',
                                       'expired' => '<span class="status-badge status-expired">Expired</span>'
                                   ];
                                   echo $status_badges[$row['status']] ?? '<span class="status-badge status-pending">Menunggu Pembayaran</span>';
                                   ?>
                               </td>
                               <td>
                                   <a href="admin_detail_reservasi.php?id=<?php echo $row['id']; ?>" class="action-btn btn-detail">Detail</a>
                                   <a href="../../model/admin_delete_reservasi.php?id=<?php echo $row['id']; ?>" 
                                      class="action-btn btn-delete" 
                                      onclick="return confirm('Yakin ingin menghapus reservasi ini?')">Hapus</a>
                               </td>
                           </tr>
                       <?php endwhile; ?>
                   <?php else: ?>
                       <tr>
                           <td colspan="9" class="no-data">Tidak ada data reservasi</td>
                       </tr>
                   <?php endif; ?>
               </tbody>
           </table>
       </div>
   </div>
</body>
</html>