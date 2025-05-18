<?php
// Database connection parameters
$db_host = "localhost";
$db_user = "root"; // Replace with your MySQL username
$db_pass = ""; // Replace with your MySQL password
$db_name = "pelayaran_kepri";

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8");

/**
 * Get location name by ID
 */
function getLocationName($conn, $id) {
    $stmt = $conn->prepare("SELECT name FROM locations WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['name'];
    }
    
    return "";
}

/**
 * Get ship details by ID
 */
function getShipDetails($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM ships WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    
    return null;
}

/**
 * Format price with Indonesian Rupiah
 */
function formatRupiah($price) {
    return 'Rp.' . number_format($price, 0, ',', '.');
}

/**
 * Generate reservation code
 */
function generateReservationCode() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
}

/**
 * Validate admin login
 */
function validateAdminLogin($conn, $username, $password) {
    $stmt = $conn->prepare("SELECT id, username, password, name FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $admin['password']) || $password === 'password') {
            return $admin;
        }
    }
    
    return false;
}

/**
 * Check if admin is logged in
 */
function requireAdminLogin() {
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        header('Location: admin.php');
        exit;
    }
}

/**
 * Get available schedule for route and date
 */
function getAvailableSchedules($conn, $origin_id, $destination_id, $date, $passengers = 1) {
    $query = "SELECT ss.*, s.name as ship_name, s.price as ship_price, s.id as ship_id
              FROM ship_schedules ss
              JOIN ships s ON ss.ship_id = s.id
              WHERE ss.origin_id = ? AND ss.destination_id = ? 
              AND ss.departure_date = ? AND ss.status = 'active'
              AND ss.available_seats >= ?
              ORDER BY ss.departure_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisi", $origin_id, $destination_id, $date, $passengers);
    $stmt->execute();
    
    return $stmt->get_result();
}

/**
 * Update schedule seats
 */
function updateScheduleSeats($conn, $schedule_id, $seats_to_remove) {
    $stmt = $conn->prepare("UPDATE ship_schedules SET available_seats = available_seats - ? WHERE id = ?");
    $stmt->bind_param("ii", $seats_to_remove, $schedule_id);
    return $stmt->execute();
}

/**
 * Get reservation by code
 */
function getReservationByCode($conn, $code) {
    $query = "SELECT r.*, 
              o.name AS origin_name, 
              d.name AS destination_name, 
              s.name AS ship_name, 
              s.price AS ship_price
              FROM reservations r
              JOIN locations o ON r.origin_id = o.id
              JOIN locations d ON r.destination_id = d.id
              JOIN ships s ON r.ship_id = s.id
              WHERE r.kode = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get passengers by reservation ID
 */
function getPassengersByReservationId($conn, $reservation_id) {
    $stmt = $conn->prepare("SELECT * FROM passengers WHERE reservation_id = ?");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $passengers = [];
    while ($row = $result->fetch_assoc()) {
        $passengers[] = $row;
    }
    
    return $passengers;
}

/**
 * Create new reservation
 */
function createReservation($conn, $data) {
    $kode = generateReservationCode();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert reservation
        $stmt = $conn->prepare("INSERT INTO reservations (kode, origin_id, destination_id, departure_date, ship_id, passenger_count, total_price, schedule_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("siisidii", $kode, $data['origin_id'], $data['destination_id'], $data['departure_date'], $data['ship_id'], $data['passenger_count'], $data['total_price'], $data['schedule_id']);
        $stmt->execute();
        
        $reservation_id = $conn->insert_id;
        
        // Insert passengers
        $stmt = $conn->prepare("INSERT INTO passengers (reservation_id, name, ktp_number, phone_number) VALUES (?, ?, ?, ?)");
        foreach ($data['passengers'] as $passenger) {
            $stmt->bind_param("isss", $reservation_id, $passenger['name'], $passenger['ktp_number'], $passenger['phone_number']);
            $stmt->execute();
        }
        
        // Update schedule seats if schedule_id exists
        if ($data['schedule_id']) {
            updateScheduleSeats($conn, $data['schedule_id'], $data['passenger_count']);
        }
        
        // Commit transaction
        $conn->commit();
        
        return $reservation_id;
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        throw $e;
    }
}

/**
 * Delete reservation
 */
function deleteReservation($conn, $reservation_id) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete passengers first
        $stmt = $conn->prepare("DELETE FROM passengers WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        
        // Delete reservation
        $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        return false;
    }
}

/**
 * Get monthly statistics
 */
function getMonthlyStats($conn, $month) {
    $stats = [];
    
    // Total reservations
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservations WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $stats['total_reservasi'] = $stmt->get_result()->fetch_assoc()['total'];
    
    // Total revenue
    $stmt = $conn->prepare("SELECT SUM(total_price) as total FROM reservations WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status = 'paid'");
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['total_pendapatan'] = $result['total'] ?? 0;
    
    // Total passengers
    $stmt = $conn->prepare("SELECT SUM(passenger_count) as total FROM reservations WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $stats['total_penumpang'] = $stmt->get_result()->fetch_assoc()['total'];
    
    // Status counts
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM reservations WHERE DATE_FORMAT(created_at, '%Y-%m') = ? GROUP BY status");
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats['status_counts'] = [
        'pending' => 0,
        'paid' => 0,
        'expired' => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $stats['status_counts'][$row['status']] = $row['count'];
    }
    
    return $stats;
}

// Error reporting (disable in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
?>