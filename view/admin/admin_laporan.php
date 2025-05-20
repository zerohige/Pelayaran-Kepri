<?php
session_start();
require_once '../../controller/db_connection.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

// Filter
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_year = $_GET['year'] ?? date('Y');

// Statistik Umum
$stats = [];

// Total reservasi bulan ini
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservations WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt->bind_param("s", $filter_month);
$stmt->execute();
$stats['reservasi_bulan'] = $stmt->get_result()->fetch_assoc()['total'];

// Total pendapatan bulan ini
$stmt = $conn->prepare("SELECT SUM(total_price) as total FROM reservations WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt->bind_param("s", $filter_month);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stats['pendapatan_bulan'] = $result['total'] ?? 0;

// Total penumpang bulan ini
$stmt = $conn->prepare("SELECT SUM(passenger_count) as total FROM reservations WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt->bind_param("s", $filter_month);
$stmt->execute();
$stats['penumpang_bulan'] = $stmt->get_result()->fetch_assoc()['total'];

// Reservasi per hari (bulan ini)
$daily_reservations = [];
$stmt = $conn->prepare("SELECT DATE(created_at) as date, COUNT(*) as count, SUM(total_price) as revenue 
                       FROM reservations 
                       WHERE DATE_FORMAT(created_at, '%Y-%m') = ? 
                       GROUP BY DATE(created_at) 
                       ORDER BY date");
$stmt->bind_param("s", $filter_month);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $daily_reservations[] = $row;
}

// Reservasi per kapal
$ship_reservations = [];
$stmt = $conn->prepare("SELECT s.name, COUNT(r.id) as count, SUM(r.total_price) as revenue 
                       FROM reservations r 
                       JOIN ships s ON r.ship_id = s.id 
                       WHERE DATE_FORMAT(r.created_at, '%Y-%m') = ? 
                       GROUP BY s.id, s.name 
                       ORDER BY count DESC");
$stmt->bind_param("s", $filter_month);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $ship_reservations[] = $row;
}

// Rute terpopuler
$popular_routes = [];
$stmt = $conn->prepare("SELECT o.name as origin, d.name as destination, COUNT(r.id) as count 
                       FROM reservations r 
                       JOIN locations o ON r.origin_id = o.id 
                       JOIN locations d ON r.destination_id = d.id 
                       WHERE DATE_FORMAT(r.created_at, '%Y-%m') = ? 
                       GROUP BY r.origin_id, r.destination_id 
                       ORDER BY count DESC 
                       LIMIT 10");
$stmt->bind_param("s", $filter_month);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $popular_routes[] = $row;
}

// Halaman website
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Admin Pelayaran Kepri</title>
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/header.css">
    <link rel="stylesheet" href="../../css/admin_laporan.css">
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
            <li><a href="admin_reservasi.php">📋 Data Reservasi</a></li>
            <li><a href="admin_jadwal.php">🚢 Jadwal Kapal</a></li>
            <li><a href="admin_laporan.php" class="active">📈 Laporan</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">Laporan Pelayaran Kepri</h1>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="month">Bulan</label>
                    <input type="month" id="month" name="month" value="<?php echo $filter_month; ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="filter-btn">Tampilkan Laporan</button>
                </div>
            </form>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-number"><?php echo $stats['reservasi_bulan']; ?></div>
                <div class="stat-label">Total Reservasi Bulan Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-number"><?php echo formatRupiah($stats['pendapatan_bulan']); ?></div>
                <div class="stat-label">Total Pendapatan Bulan Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $stats['penumpang_bulan']; ?></div>
                <div class="stat-label">Total Penumpang Bulan Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-number"><?php echo $stats['reservasi_bulan'] > 0 ? number_format($stats['pendapatan_bulan'] / $stats['reservasi_bulan'], 0) : 0; ?></div>
                <div class="stat-label">Rata-rata per Reservasi</div>
            </div>
        </div>

        <!-- Daily Chart Section -->
        <div class="chart-container">
            <div class="chart-title">Grafik Reservasi Harian - <?php echo date('F Y', strtotime($filter_month . '-01')); ?></div>
            <?php if (!empty($daily_reservations)): ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jumlah Reservasi</th>
                            <th>Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_reservations as $daily): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($daily['date'])); ?></td>
                                <td><?php echo $daily['count']; ?> reservasi</td>
                                <td><?php echo formatRupiah($daily['revenue']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">Tidak ada data untuk periode ini</div>
            <?php endif; ?>
        </div>

        <!-- Reports Grid -->
        <div class="report-grid">
            <!-- Ship Performance -->
            <div class="report-card">
                <div class="report-header">Performa Kapal</div>
                <div class="report-content">
                    <?php if (!empty($ship_reservations)): ?>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Nama Kapal</th>
                                    <th>Reservasi</th>
                                    <th>Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ship_reservations as $ship): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ship['name']); ?></td>
                                        <td><?php echo $ship['count']; ?></td>
                                        <td><?php echo formatRupiah($ship['revenue']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">Tidak ada data kapal</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Popular Routes -->
            <div class="report-card">
                <div class="report-header">Rute Terpopuler</div>
                <div class="report-content">
                    <?php if (!empty($popular_routes)): ?>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Rute</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_routes as $route): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($route['origin'] . ' → ' . $route['destination']); ?></td>
                                        <td><?php echo $route['count']; ?> reservasi</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">Tidak ada data rute</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Print Section -->
        <div class="print-section">
            <button onclick="window.print()" class="print-btn">🖨️ Cetak Laporan</button>
        </div>
    </div>
</body>
</html>