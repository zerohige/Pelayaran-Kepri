<?php
// admin_dashboard.php - Dashboard admin (Fixed)
session_start();
require_once 'db_connection.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

// Function to safely get count with error handling
function getSafeCount($conn, $query, $default = 0) {
    try {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['total'] ?? $default;
        }
        return $default;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return $default;
    }
}

// Ambil statistik dengan error handling
$stats = [];

// Total reservasi
$stats['total_reservasi'] = getSafeCount($conn, "SELECT COUNT(*) as total FROM reservations");

// Total reservasi hari ini (coba dengan created_at, jika gagal gunakan alternatif)
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE DATE(created_at) = CURDATE()");
    $stats['reservasi_hari_ini'] = $result->fetch_assoc()['total'];
} catch (Exception $e) {
    // Jika kolom created_at tidak ada, gunakan query alternatif
    $stats['reservasi_hari_ini'] = getSafeCount($conn, "SELECT COUNT(*) as total FROM reservations WHERE DATE(departure_date) = CURDATE()");
}

// Total jadwal aktif
$stats['jadwal_aktif'] = getSafeCount($conn, "SELECT COUNT(*) as total FROM ship_schedules WHERE status = 'active' AND departure_date >= CURDATE()");

// Total kapal
$stats['total_kapal'] = getSafeCount($conn, "SELECT COUNT(*) as total FROM ships");

// Cek apakah tabel ship_schedules ada
$table_exists = false;
try {
    $result = $conn->query("SHOW TABLES LIKE 'ship_schedules'");
    $table_exists = $result->num_rows > 0;
    if (!$table_exists) {
        $stats['jadwal_aktif'] = 'N/A';
    }
} catch (Exception $e) {
    $stats['jadwal_aktif'] = 'N/A';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pelayaran Kepri</title>
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

        /* Stats Cards */
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

        /* Welcome Section */
        .welcome-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .welcome-title {
            color: #0a2259;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .welcome-text {
            color: #666;
            line-height: 1.6;
        }

        /* Action Buttons */
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .action-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .action-card:hover {
            transform: translateY(-5px);
        }

        .action-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .action-title {
            font-size: 18px;
            font-weight: bold;
            color: #0a2259;
            margin-bottom: 10px;
        }

        .action-description {
            color: #666;
            margin-bottom: 20px;
        }

        .action-btn {
            background-color: #0a2259;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }

        .action-btn:hover {
            background-color: #083048;
        }

        /* Alert untuk database incomplete */
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/header.css">

<body>
    <!-- Header -->
    <div class="header">
        <div class="logo-container">
            <img src="gambar/logo.png" alt="Logo">
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
            <li><a href="admin_dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="admin_reservasi.php">📋 Data Reservasi</a></li>
            <li><a href="admin_jadwal.php">🚢 Jadwal Kapal</a></li>
            <li><a href="admin_laporan.php">📈 Laporan</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (!$table_exists): ?>
            <div class="alert alert-danger">
                <strong>⚠️ Perhatian:</strong> Tabel jadwal kapal belum dibuat. Silakan jalankan script update database atau gunakan file instalasi untuk membuat tabel yang diperlukan.
                <a href="update_database.php" style="color: #721c24; text-decoration: underline;">Klik di sini untuk update database</a>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_reservasi']; ?></div>
                <div class="stat-label">Total Reservasi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['reservasi_hari_ini']; ?></div>
                <div class="stat-label">Reservasi Hari Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['jadwal_aktif']; ?></div>
                <div class="stat-label">Jadwal Aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_kapal']; ?></div>
                <div class="stat-label">Total Kapal</div>
            </div>
        </div>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2 class="welcome-title">Selamat Datang di Admin Panel</h2>
            <p class="welcome-text">
                Kelola sistem reservasi pelayaran Kepri dengan mudah. Anda dapat melihat data reservasi, 
                mengatur jadwal kapal, dan memantau statistik operasional dari dashboard ini.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <div class="action-card">
                <div class="action-icon">📋</div>
                <div class="action-title">Kelola Reservasi</div>
                <div class="action-description">Lihat dan kelola semua reservasi pelanggan</div>
                <a href="admin_reservasi.php" class="action-btn">Lihat Reservasi</a>
            </div>
            <div class="action-card">
                <div class="action-icon">🚢</div>
                <div class="action-title">Atur Jadwal Kapal</div>
                <div class="action-description">Tambah, edit, dan hapus jadwal keberangkatan kapal</div>
                <a href="admin_jadwal.php" class="action-btn">Kelola Jadwal</a>
            </div>
        </div>
    </div>
</body>
</html>