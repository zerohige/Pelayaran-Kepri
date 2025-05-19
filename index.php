<?php
// index.php - Halaman pemilihan lokasi
require_once 'controller/db_connection.php';

// Fetch locations from database
$query = "SELECT * FROM locations ORDER BY name";
$result = $conn->query($query);
$locations = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}

// Halaman website
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelayaran Kepri</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <!-- Header with Logo and Buttons -->
    <div class="header">
        <div class="logo-container">
            <img src="gambar/logo.png" alt="Logo">
            <div class="title">Pelayaran Kepri</div>
        </div>
        <div class="header-buttons">
            <a href="view/user/cek_reservasi.php" class="check-reservation">Cek Reservasi</a>
            <a href="view/admin/admin.php" class="admin">ADMIN</a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="location-selection">
            <div class="selection-title">PILIH LOKASI ANDA :</div>
            <div class="location-buttons">
                <?php foreach ($locations as $location): ?>
                    <a href="view/user/form.php?lokasi_id=<?php echo $location['id']; ?>&lokasi=<?php echo urlencode($location['name']); ?>" class="location-button"><?php echo $location['name']; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html><?php

// Close connection
$conn->close();
?>