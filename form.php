<?php
// form.php - Halaman form reservasi
require_once 'db_connection.php';

// Pastikan parameter lokasi ada
if (!isset($_GET['lokasi_id']) || !isset($_GET['lokasi'])) {
    // Jika tidak ada, redirect ke halaman utama
    header('Location: index.php');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Reservasi - <?php echo $lokasi; ?></title>
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

        /* Content Container */
        /* Content Container */
.content-container {
    margin-top: 20px; /* Menambah jarak atas untuk memberi ruang */
    width: 100%; /* Memperbesar lebar form untuk tampilan yang lebih luas */
    max-width: 600px; /* Memperbesar ukuran maksimal form */
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); /* Memberikan bayangan yang lebih soft */
    padding: 30px; /* Menambah padding untuk ruang sekitar form */
}

/* Location Header */
.location-header {
    background-color: #0a2259;
    color: white;
    padding: 20px; /* Menambah padding agar lebih besar */
    text-align: center;
    font-size: 22px; /* Menambah ukuran font */
    font-weight: bold;
}

/* Form Content */
    .form-content {
        padding: 30px; /* Menambah padding dalam form */
    }

    /* Form Instruction */
    .form-instruction {
        text-align: center;
        color: #666;
        margin-bottom: 10px; /* Menambah jarak bawah */
        font-size: 16px; /* Memperbesar ukuran font */
    }

    /* Form Group */
    .form-group {
        margin-bottom: 25px; /* Menambah jarak bawah antara input */
    }

    /* Label styling */
    .form-group label {
        display: block;
        margin-bottom: 10px; /* Menambah jarak bawah label */
        font-weight: bold;
        color: #333;
        font-size: 18px; /* Memperbesar ukuran font label */
    }

    /* Input and Select Styling */
    .form-group select, 
    .form-group input {
        width: 100%;
        padding: 8px; /* Mengurangi padding input dan select */
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px; /* Menurunkan ukuran font */
        box-sizing: border-box;
    }

    /* Submit Button */
    .submit-btn {
        background-color: #0a2259;
        color: white;
        border: none;
        padding: 10px 20px; /* Mengurangi padding tombol */
        border-radius: 20px;
        font-size: 20px; /* Menurunkan ukuran font tombol */
        font-weight: bold;
        cursor: pointer;
        width: 60%;
        margin: 15px auto; /* Mengurangi jarak atas tombol */
        display: block;
    }

    /* Submit Button Hover Effect */
    .submit-btn:hover {
        background-color: #073b8c; /* Menambah efek hover dengan warna lebih gelap */
    }

    /* Error Message */
    .error-message {
        color: #d9534f;
        background-color: #f9f2f2;
        border-left: 3px solid #d9534f;
        padding: 8px;
        margin-bottom: 10px; /* Mengurangi jarak bawah untuk error */
        font-size: 14px; /* Menurunkan ukuran font pada error message */
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