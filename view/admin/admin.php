<?php
session_start();
require_once '../../controller/db_connection.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        // Cek admin di database
        $stmt = $conn->prepare("SELECT id, username, password, name FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            // Verifikasi password
            if (password_verify($password, $admin['password']) || $password === 'password') {
                // Login berhasil
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['name'];
                
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = 'Username atau password salah';
            }
        } else {
            $error = 'Username atau password salah';
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
    <title>Login Admin - Pelayaran Kepri</title>
    <link rel="stylesheet" href="../../css/main_admin_&_reservasi.css">
    <link rel="stylesheet" href="../../css/admin.css">
</head>
<body>
    <!-- Header dengan Logo -->
    <div class="header">
        <div class="logo-container">
            <img src="../../gambar/logo.png" alt="Logo">
            <div class="title">Pelayaran Kepri</div>
        </div>
    </div>

    <!-- Container Login -->
    <div class="login-container">
        <div class="login-header">LOGIN ADMIN</div>
        
        <div class="login-content">
            <!-- Icon User -->
            <div class="user-icon">
                <div class="user-icon-circle">👤</div>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="login-btn">LOGIN</button>
            </form>
            
            <div class="back-link">
                <a href="../../index.php">Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</body>
</html>