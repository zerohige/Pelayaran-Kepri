<?php
require_once '../../controller/db_connection.php';

// Pastikan parameter lokasi ada
if (!isset($_GET['lokasi_id']) || !isset($_GET['lokasi'])) {
    // Jika tidak ada, redirect ke halaman utama
    header('Location: ../../index.php');
    exit;
}

// Ambil parameter lokasi
$lokasi_id = (int)$_GET['lokasi_id'];
$lokasi = htmlspecialchars($_GET['lokasi']);

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data form
    $tujuan_id = isset($_POST['destination']) ? (int)$_POST['destination'] : 0;
    $penumpang = isset($_POST['passengers']) ? (int)$_POST['passengers'] : 0;
    $tanggal = $_POST['date'] ?? '';

    // Validasi data
    $errors = [];
    if ($tujuan_id <= 0) {
        $errors[] = "Tujuan harus dipilih";
    }
    if (empty($tanggal)) {
        $errors[] = "Tanggal keberangkatan harus diisi";
    }

    // Jika tidak ada error, proses data
    if (empty($errors)) {
        // Get tujuan name
        $stmt = $conn->prepare("SELECT name FROM locations WHERE id = ?");
        $stmt->bind_param("i", $tujuan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tujuan_name = "";
        
        if ($row = $result->fetch_assoc()) {
            $tujuan_name = $row['name'];
        }
        
        // Redirect ke halaman pilih kapal
        header('Location: pilih_kapal.php?lokasi_id='.$lokasi_id.'&lokasi='.urlencode($lokasi).'&tujuan_id='.$tujuan_id.'&tujuan='.urlencode($tujuan_name).'&penumpang='.$penumpang.'&tanggal='.urlencode($tanggal));
        exit;
    }
}

// Fetch destination options from database based on selected origin
$query = "SELECT DISTINCT d.id, d.name FROM locations d 
          JOIN routes r ON r.destination_id = d.id 
          WHERE r.origin_id = ? 
          ORDER BY d.name";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lokasi_id);
$stmt->execute();
$result = $stmt->get_result();

$destinations = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $destinations[] = $row;
    }
}

// Halaman website
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Reservasi - <?php echo $lokasi; ?></title>
    <link rel="stylesheet" href="../../css/form.css">
</head>
<body>
    <!-- Header with Logo -->
    <div class="header">
        <div class="logo-container">
            <a href="../../index.php">
                <img src="../../gambar/logo.png" alt="Logo">
            </a>
            <div class="title">Pelayaran Kepri</div>
        </div>
    </div>

    <!-- Content Container -->
    <div class="content-container">
        <div class="location-header"><?php echo $lokasi; ?></div>
        
        <div class="form-content">
            <div class="form-instruction">Masukkan informasi keberangkatan Anda!</div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="destination">Pilih Tujuan Keberangkatan :</label>
                    <div class="select-wrapper">
                        <select id="destination" name="destination">
                            <option value="" selected disabled>Pilih Tujuan</option>
                            <?php foreach ($destinations as $destination): ?>
                                <option value="<?php echo $destination['id']; ?>"><?php echo $destination['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="passengers">Jumlah Penumpang :</label>
                    <div class="select-wrapper">
                        <select id="passengers" name="passengers">
                            <?php
                            for ($i = 1; $i <= 3; $i++) {
                                echo "<option value=\"$i\"> $i orang</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="date">Tanggal Keberangkatan :</label>
                    <input type="date" id="date" name="date" value="<?php echo isset($_POST['date']) ? $_POST['date'] : ''; ?>">
                </div>
                
                <button type="submit" class="submit-btn">SELESAI</button>
            </form>
        </div>
    </div>
</body>
</html><?php
// Close connection
$conn->close();
?>