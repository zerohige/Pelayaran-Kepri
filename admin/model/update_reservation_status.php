<?php
// update_reservation_status.php - Update status expired reservations
// Dijalankan melalui cron job setiap hari: php /path/to/update_reservation_status.php
require_once '../../controller/db_connection.php';

// Cari reservasi yang belum dibayar dan sudah melewati batas waktu (1 hari sebelum keberangkatan)
$query = "UPDATE reservations 
          SET status = 'expired' 
          WHERE status = 'pending' 
          AND departure_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY)";

$stmt = $conn->prepare($query);
$stmt->execute();

echo "Updated " . $stmt->affected_rows . " expired reservations.";
// Log update jika perlu
file_put_contents('logs/status_update.log', date('Y-m-d H:i:s') . " - Updated " . $stmt->affected_rows . " expired reservations.\n", FILE_APPEND);
?>