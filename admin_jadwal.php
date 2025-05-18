<?php
// admin_jadwal.php - Halaman kelola jadwal kapal
session_start();
require_once 'db_connection.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

$message = '';
$message_type = '';

// Proses tambah jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $ship_id = $_POST['ship_id'];
        $origin_id = $_POST['origin_id'];
        $destination_id = $_POST['destination_id'];
        $departure_date = $_POST['departure_date'];
        $departure_time = $_POST['departure_time'];
        $available_seats = $_POST['available_seats'];
        
        $stmt = $conn->prepare("INSERT INTO ship_schedules (ship_id, origin_id, destination_id, departure_date, departure_time, available_seats) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissi", $ship_id, $origin_id, $destination_id, $departure_date, $departure_time, $available_seats);
        
        if ($stmt->execute()) {
            $message = 'Jadwal berhasil ditambahkan';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambahkan jadwal';
            $message_type = 'error';
        }
    }
    
    if ($_POST['action'] === 'delete') {
        $schedule_id = $_POST['schedule_id'];
        
        $stmt = $conn->prepare("DELETE FROM ship_schedules WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);
        
        if ($stmt->execute()) {
            $message = 'Jadwal berhasil dihapus';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus jadwal';
            $message_type = 'error';
        }
    }
}

// Ambil data untuk form
$ships = [];
$stmt = $conn->prepare("SELECT * FROM ships ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $ships[] = $row;
}

$locations = [];
$stmt = $conn->prepare("SELECT * FROM locations ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $locations[] = $row;
}

// Ambil jadwal yang ada
$schedules = [];
$query = "SELECT ss.*, s.name as ship_name, o.name as origin_name, d.name as destination_name 
          FROM ship_schedules ss
          JOIN ships s ON ss.ship_id = s.id
          JOIN locations o ON ss.origin_id = o.id
          JOIN locations d ON ss.destination_id = d.id
          WHERE ss.departure_date >= CURDATE()
          ORDER BY ss.departure_date, ss.departure_time";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal Kapal - Admin Pelayaran Kepri</title>
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

        /* Message */
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .message.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        /* Form Section */
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-title {
            color: #0a2259;
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .submit-btn {
            background-color: #0a2259;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 20px;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background-color: #0a2259;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
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
            <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
            <li><a href="admin_reservasi.php">📋 Data Reservasi</a></li>
            <li><a href="admin_jadwal.php" class="active">🚢 Jadwal Kapal</a></li>
            <li><a href="admin_laporan.php">📈 Laporan</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">Kelola Jadwal Kapal</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Form Tambah Jadwal -->
        <div class="form-section">
            <h2 class="form-title">Tambah Jadwal Baru</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="ship_id">Kapal</label>
                        <select id="ship_id" name="ship_id" required>
                            <option value="">Pilih Kapal</option>
                            <?php foreach ($ships as $ship): ?>
                                <option value="<?php echo $ship['id']; ?>"><?php echo htmlspecialchars($ship['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="origin_id">Asal</label>
                        <select id="origin_id" name="origin_id" required>
                            <option value="">Pilih Asal</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>"><?php echo htmlspecialchars($location['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="destination_id">Tujuan</label>
                        <select id="destination_id" name="destination_id" required>
                            <option value="">Pilih Tujuan</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>"><?php echo htmlspecialchars($location['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="departure_date">Tanggal Keberangkatan</label>
                        <input type="date" id="departure_date" name="departure_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="departure_time">Jam Keberangkatan</label>
                        <input type="time" id="departure_time" name="departure_time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="available_seats">Kursi Tersedia</label>
                        <input type="number" id="available_seats" name="available_seats" required min="1" value="100">
                    </div>
                </div>
                <button type="submit" class="submit-btn">Tambah Jadwal</button>
            </form>
        </div>

        <!-- Tabel Jadwal -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kapal</th>
                        <th>Rute</th>
                        <th>Tanggal & Jam</th>
                        <th>Kursi Tersedia</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($schedules)): ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($schedule['ship_name']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['origin_name'] . ' → ' . $schedule['destination_name']); ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($schedule['departure_date'])); ?><br>
                                    <small><?php echo date('H:i', strtotime($schedule['departure_time'])); ?></small>
                                </td>
                                <td><?php echo $schedule['available_seats']; ?> kursi</td>
                                <td>
                                    <span class="status-badge status-active"><?php echo ucfirst($schedule['status']); ?></span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus jadwal ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                        <button type="submit" class="action-btn btn-delete">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">Belum ada jadwal</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Pastikan asal dan tujuan tidak sama
        document.getElementById('origin_id').addEventListener('change', function() {
            var originValue = this.value;
            var destinationSelect = document.getElementById('destination_id');
            var destinationValue = destinationSelect.value;
            
            if (originValue === destinationValue && destinationValue !== '') {
                alert('Asal dan tujuan tidak boleh sama!');
                destinationSelect.value = '';
            }
        });
        
        document.getElementById('destination_id').addEventListener('change', function() {
            var destinationValue = this.value;
            var originSelect = document.getElementById('origin_id');
            var originValue = originSelect.value;
            
            if (originValue === destinationValue && originValue !== '') {
                alert('Asal dan tujuan tidak boleh sama!');
                this.value = '';
            }
        });
    </script>
</body>
</html>