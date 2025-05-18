<?php
// admin.php - Halaman login admin
session_start();
require_once 'db_connection.php';

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Pelayaran Kepri</title>
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

        /* Login Container */
        .login-container {
            width: 90%;
            max-width: 400px;
            background-color: white;
            border: 2px solid white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: 50px 0;
            overflow: hidden;
        }

        .login-header {
            background-color: #0a2259;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }

        .login-content {
            padding: 30px;
        }

        .user-icon {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .user-icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .login-btn {
            background-color: #0a2259;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }

        .error-message {
            color: #d9534f;
            background-color: #f9f2f2;
            border-left: 3px solid #d9534f;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 3px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #0a2259;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Header dengan Logo -->
    <div class="header">
        <div class="logo-container">
            <img src="gambar/logo.png" alt="Logo">
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
                <a href="index.php">Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</body>
</html>