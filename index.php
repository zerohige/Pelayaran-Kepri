<?php
// index.php - Halaman pemilihan lokasi
require_once 'db_connection.php';

// Fetch locations from database
$query = "SELECT * FROM locations ORDER BY name";
$result = $conn->query($query);
$locations = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelayaran Kepri</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #0a2259;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-image: url('gambar/kepri.jpg');
            background-size: cover;
            background-position: center;
        }

        /* Header Styling */
        .header {
            width: 100%;
            display: flex;
            justify-content: space-between;
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

        /* Header buttons */
        .header-buttons {
            display: flex;
            gap: 15px;
        }

        .check-reservation {
            font-size: 20px;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            font-weight: bold;
        }

        .admin {
            font-size: 20px;
            background-color: white;
            color: #0a2259;
            border-radius: 20px;
            padding: 8px 15px;
            text-decoration: none;
            font-weight: bold;
        }

        /* Content Area */
        .content-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Location Selection */
        /* Location Selection */
        .location-selection {
            background-color: rgba(10, 34, 89, 0.8);
            border-radius: 10px;
            padding: 50px 30px; /* Menambah padding di sekitar konten */
            width: 80%;
            max-width: 800px;
            text-align: center;
            margin-top: 200px; /* Menambahkan margin atas untuk menurunkan posisi */
        }

        .selection-title {
            color: white;
            font-size: 24px; /* Menambah ukuran font */
            font-weight: bold;
            margin-bottom: 30px; /* Menambah jarak bawah */
        }

        .location-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px; /* Menambah jarak antar tombol */
        }

        .location-button {
            background-color: white;
            color: #0a2259;
            border: none;
            border-radius: 20px;
            padding: 15px; /* Menambah padding untuk tombol lebih besar */
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            font-size: 18px; /* Menambah ukuran font tombol */
            transition: transform 0.2s, background-color 0.2s;
        }

        .location-button:hover {
            transform: scale(1.1); /* Membuat tombol lebih besar saat dihover */
            background-color: #f0f0f0;
        }


        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .location-buttons {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .location-selection {
                width: 90%;
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .location-buttons {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-buttons {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header with Logo and Buttons -->
    <div class="header">
        <div class="logo-container">
            <img src="gambar/logo.png" alt="Logo">
            <div class="title">Pelayaran Kepri</div>
        </div>
        <div class="header-buttons">
            <a href="cek_reservasi.php" class="check-reservation">Cek Reservasi</a>
            <a href="admin.php" class="admin">ADMIN</a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="location-selection">
            <div class="selection-title">PILIH LOKASI ANDA :</div>
            <div class="location-buttons">
                <?php foreach ($locations as $location): ?>
                    <a href="form.php?lokasi_id=<?php echo $location['id']; ?>&lokasi=<?php echo urlencode($location['name']); ?>" class="location-button"><?php echo $location['name']; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html><?php
// Close connection
$conn->close();
?>