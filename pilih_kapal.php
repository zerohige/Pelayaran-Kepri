<?php
// pilih_kapal.php - Halaman pemilihan kapal (Updated with schedule integration)
require_once 'controller/db_connection.php';

// Pastikan semua parameter ada
if (!isset($_GET['lokasi_id']) || !isset($_GET['lokasi']) || !isset($_GET['tujuan_id']) || 
    !isset($_GET['tujuan']) || !isset($_GET['penumpang']) || !isset($_GET['tanggal'])) {
    // Jika tidak ada, redirect ke halaman utama
    header('Location: index.php');
    exit;
}

// Ambil parameter
$lokasi_id = (int)$_GET['lokasi_id'];
$lokasi = htmlspecialchars($_GET['lokasi']);
$tujuan_id = (int)$_GET['tujuan_id'];
$tujuan = htmlspecialchars($_GET['tujuan']);
$penumpang = (int)$_GET['penumpang'];
$tanggal = htmlspecialchars($_GET['tanggal']);

$error = '';
$ships = [];

// Cek apakah ada jadwal untuk tanggal dan rute yang dipilih
$query = "SELECT ss.*, s.name as ship_name, s.price as ship_price, s.id as ship_id
          FROM ship_schedules ss
          JOIN ships s ON ss.ship_id = s.id
          WHERE ss.origin_id = ? AND ss.destination_id = ? 
          AND ss.departure_date = ? AND ss.status = 'active'
          AND ss.available_seats >= ?
          ORDER BY ss.departure_time";

$stmt = $conn->prepare($query);
$stmt->bind_param("iisi", $lokasi_id, $tujuan_id, $tanggal, $penumpang);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "Maaf, tidak ada jadwal kapal yang tersedia untuk rute " . $lokasi . " → " . $tujuan . " pada tanggal " . date('d F Y', strtotime($tanggal)) . " dengan kapasitas " . $penumpang . " penumpang.";
} else {
    while ($row = $result->fetch_assoc()) {
        $ships[] = $row;
    }
}

// Jika form disubmit (tombol PILIH ditekan)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = isset($_POST['schedule']) ? (int)$_POST['schedule'] : 0;

    if ($schedule_id > 0) {
        // Get schedule and ship info
        $stmt = $conn->prepare("SELECT ss.*, s.name as ship_name, s.price as ship_price 
                               FROM ship_schedules ss 
                               JOIN ships s ON ss.ship_id = s.id 
                               WHERE ss.id = ?");
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $schedule = $result->fetch_assoc();
            
            // Start the session to store temporary booking data
            session_start();
            
            // Create a temporary booking record
            $_SESSION['temp_booking'] = [
                'lokasi_id' => $lokasi_id,
                'lokasi' => $lokasi,
                'tujuan_id' => $tujuan_id,
                'tujuan' => $tujuan,
                'penumpang' => $penumpang,
                'tanggal' => $tanggal,
                'schedule_id' => $schedule_id,
                'kapal_id' => $schedule['ship_id'],
                'kapal_nama' => $schedule['ship_name'],
                'passengers' => []
            ];
            
            // Redirect ke halaman isi data penumpang
            header('Location: isi_data.php?passenger=1');
            exit;
        }
    }
}

// Halaman website
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Kapal - Pelayaran Kepri</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/pilih_kapal.css">
</head>
<body>
    <!-- Header dengan Logo -->
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
            <div class="step-circle step-inactive">2</div>
            <div class="step-text">Isi Data</div>
        </div>
        <div class="progress-line"></div>
        <div class="progress-step">
            <div class="step-circle step-inactive">3</div>
            <div class="step-text">Konfirmasi</div>
        </div>
    </div>

    <!-- Content Container -->
    <div class="content-container">
        <?php if ($error): ?>
            <!-- Error State -->
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <div class="content-title">Jadwal Tidak Tersedia</div>
                <div class="error-message"><?php echo $error; ?></div>
                <p style="color: #666; text-align: center;">
                    Silakan pilih tanggal lain atau hubungi admin untuk informasi jadwal lebih lanjut.
                </p>
                <div style="text-align: center;">
                    <div class="nav-links">
                        <a href="form.php?lokasi_id=<?php echo $lokasi_id; ?>&lokasi=<?php echo urlencode($lokasi); ?>" class="back-button">
                            <img src="gambar/left.png" alt="Kembali" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;">
                            Pilih Tanggal Lain
                        </a>
    
                        <a href="index.php" class="back-button">
                            🏠 Kembali ke Beranda
                        </a>
                    </div>

                </div>
            </div>
        <?php else: ?>
            <!-- Normal State dengan Jadwal Tersedia -->
            <div class="content-title">Pilih Transportasi Kapal yang Diinginkan!</div>
            
            <form method="POST" action="">
                <div class="ship-options">
                    <?php foreach ($ships as $ship): ?>
                    <label class="ship-option" id="option-<?php echo $ship['id']; ?>">
                        <div class="ship-details">
                            <div class="ship-name"><?php echo htmlspecialchars($ship['ship_name']); ?></div>
                            <div class="ship-time">Keberangkatan: <?php echo date('H:i', strtotime($ship['departure_time'])); ?></div>
                            <div class="ship-time">Kursi tersedia: <?php echo $ship['available_seats']; ?></div>
                        </div>
                        <div class="ship-price"><?php echo formatRupiah($ship['ship_price']); ?></div>
                        <input type="radio" name="schedule" value="<?php echo $ship['id']; ?>" style="display:none;" required>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" class="submit-btn">PILIH</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Fungsi untuk menangani pilihan kapal
        document.querySelectorAll('.ship-option').forEach(option => {
            option.addEventListener('click', function() {
                // Hapus kelas selected dari semua opsi
                document.querySelectorAll('.ship-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Tambahkan kelas selected ke opsi yang dipilih
                this.classList.add('selected');
                
                // Cek radio button
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
            });
        });
    </script>
</body>
</html><?php
// Close connection
$conn->close();
?>
