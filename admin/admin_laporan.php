<?php
// admin_laporan.php - Halaman laporan
session_start();
require_once '../controller/db_connection.php';

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Admin Pelayaran Kepri</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            margin-top: 80px;
            padding: 20px;
            min-height: calc(100vh - 80px);
        }

        .page-title {
            color: #0a2259;
            font-size: 24px;
            margin-bottom: 20px;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .filter-btn {
            background-color: #0a2259;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #0a2259;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .stat-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        /* Report Cards */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .report-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .report-header {
            background-color: #0a2259;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }

        .report-content {
            padding: 20px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th,
        .report-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .report-table th {
            font-weight: bold;
            color: #333;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .chart-title {
            color: #0a2259;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* Print Button */
        .print-section {
            text-align: center;
            margin: 30px 0;
        }

        .print-btn {
            background-color: #0a2259;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        /* CSS untuk print */
        @media print {
            /* Sembunyikan elemen header dan sidebar */
            .header, .sidebar, .print-section {
                display: none;
            }

            /* Atur konten agar pas di halaman cetak */
            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            /* Sesuaikan ukuran font dan layout jika perlu */
            .report-table th, .report-table td {
                font-size: 12px;
                padding: 8px;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .report-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/header.css">
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo-container">
            <img src="../gambar/logo.png" alt="Logo">
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