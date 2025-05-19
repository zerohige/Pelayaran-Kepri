<?php
// admin_delete_reservasi.php - Hapus reservasi
session_start();
require_once '../../controller/db_connection.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin.php');
    exit;
}

$reservation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reservation_id <= 0) {
    header('Location: ../admin_reservasi.php');
    exit;
}

// Hapus passengers terlebih dahulu (karena foreign key constraint)
$stmt = $conn->prepare("DELETE FROM passengers WHERE reservation_id = ?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();

// Kemudian hapus reservasi
$stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
$stmt->bind_param("i", $reservation_id);

if ($stmt->execute()) {
    // Redirect dengan pesan sukses
    header('Location: ../admin_reservasi.php?message=deleted');
} else {
    // Redirect dengan pesan error
    header('Location: ../admin_reservasi.php?message=error');
}

exit;
?>