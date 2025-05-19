<?php
// admin_update_status.php - Update status pembayaran reservasi
session_start();
require_once '../controller/db_connection.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../view/admin/admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reservation_id']) || !isset($_POST['status'])) {
    header('Location: ../view/admin/admin_reservasi.php');
    exit;
}

$reservation_id = (int)$_POST['reservation_id'];
$status = $_POST['status'];

// Validasi status
if (!in_array($status, ['pending', 'paid', 'expired'])) {
    header('Location: ../view/admin/admin_detail_reservasi.php?id=' . $reservation_id . '&error=invalid_status');
    exit;
}

// Update status
$stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $reservation_id);

if ($stmt->execute()) {
    header('Location: ../view/admin/admin_detail_reservasi.php?id=' . $reservation_id . '&message=status_updated');
} else {
    header('Location: ../view/admin/admin_detail_reservasi.php?id=' . $reservation_id . '&error=update_failed');
}
exit;
?>