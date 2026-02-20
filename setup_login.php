<?php
/**
 * Setup Login - Script untuk setup tabel admin dan insert user default
 * Jalankan file ini SEKALI untuk setup database login
 * 
 * Akses: http://localhost/GadaiHp/setup_login.php
 */

include 'database.php';

$messages = [];
$errors = [];
$success = true;

// Step 1: Cek koneksi database
try {
    if (!$conn) {
        throw new Exception('Koneksi database gagal!');
    }
    $messages[] = '✅ Koneksi database berhasil';
} catch (Exception $e) {
    $errors[] = '❌ Koneksi database gagal: ' . $e->getMessage();
    $success = false;
}

// Step 2: Cek atau buat tabel admin
if ($success) {
    try {
        // Cek apakah tabel admin sudah ada
        $check_table = $conn->query("SHOW TABLES LIKE 'admin'");
        
        if ($check_table->num_rows == 0) {
            // Tabel belum ada, buat baru
            $create_table = "CREATE TABLE admin (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nik VARCHAR(20) NOT NULL UNIQUE,
                nama VARCHAR(100) NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                telepon VARCHAR(20),
                role ENUM('super_admin', 'admin') DEFAULT 'admin',
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                INDEX idx_nik (nik),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if ($conn->query($create_table) === TRUE) {
                $messages[] = '✅ Tabel admin berhasil dibuat';
            } else {
                throw new Exception('Gagal membuat tabel: ' . $conn->error);
            }
        } else {
            $messages[] = '✅ Tabel admin sudah ada';
            
            // Cek apakah kolom yang diperlukan ada
            $check_columns = $conn->query("DESCRIBE admin");
            $columns = [];
            while ($row = $check_columns->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            
            // Tambahkan kolom password jika belum ada
            if (!in_array('password', $columns)) {
                $conn->query("ALTER TABLE admin ADD COLUMN password VARCHAR(255) NOT NULL AFTER nik");
                $messages[] = '✅ Kolom password ditambahkan';
            }
            
            // Tambahkan kolom nama jika belum ada
            if (!in_array('nama', $columns)) {
                $conn->query("ALTER TABLE admin ADD COLUMN nama VARCHAR(100) NOT NULL AFTER nik");
                $messages[] = '✅ Kolom nama ditambahkan';
            }
        }
    } catch (Exception $e) {
        $errors[] = '❌ Error tabel admin: ' . $e->getMessage();
        $success = false;
    }
}

// Step 3: Insert atau update admin default
if ($success) {
    try {
        // Cek apakah admin123 sudah ada
        $check_admin = $conn->prepare("SELECT id FROM admin WHERE nik = ?");
        $default_nik = 'admin123';
        $check_admin->bind_param("s", $default_nik);
        $check_admin->execute();
        $result = $check_admin->get_result();
        
        // Password default: admin123
        $default_password = 'admin123';
        $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
        
        if ($result->num_rows == 0) {
            // Insert admin baru
            $insert = $conn->prepare("INSERT INTO admin (nik, nama, password, role) VALUES (?, ?, ?, ?)");
            $nama = 'Administrator';
            $role = 'super_admin';
            $insert->bind_param("ssss", $default_nik, $nama, $password_hash, $role);
            
            if ($insert->execute()) {
                $messages[] = '✅ Admin default berhasil dibuat';
                $messages[] = '📝 NIK: admin123';
                $messages[] = '📝 Password: admin123';
            } else {
                throw new Exception('Gagal insert admin: ' . $conn->error);
            }
            $insert->close();
        } else {
            // Update password admin yang sudah ada
            $update = $conn->prepare("UPDATE admin SET password = ?, nama = ? WHERE nik = ?");
            $nama = 'Administrator';
            $update->bind_param("sss", $password_hash, $nama, $default_nik);
            
            if ($update->execute()) {
                $messages[] = '✅ Password admin123 berhasil direset';
                $messages[] = '📝 NIK: admin123';
                $messages[] = '📝 Password: admin123';
            } else {
                throw new Exception('Gagal update admin: ' . $conn->error);
            }
            $update->close();
        }
        $check_admin->close();
    } catch (Exception $e) {
        $errors[] = '❌ Error setup admin: ' . $e->getMessage();
        $success = false;
    }
}

// Step 4: Test login (verify password hash)
if ($success) {
    try {
        $test_stmt = $conn->prepare("SELECT id, nik, password, nama FROM admin WHERE nik = ?");
        $test_nik = 'admin123';
        $test_stmt->bind_param("s", $test_nik);
        $test_stmt->execute();
        $test_result = $test_stmt->get_result();
        
        if ($test_result->num_rows > 0) {
            $admin_data = $test_result->fetch_assoc();
            
            // Verify password
            if (password_verify('admin123', $admin_data['password'])) {
                $messages[] = '✅ Verifikasi password berhasil';
                $messages[] = '🎉 Setup selesai! Silakan login dengan kredensial di atas';
            } else {
                $errors[] = '⚠️ Password hash tidak cocok, coba jalankan setup lagi';
            }
        }
        $test_stmt->close();
    } catch (Exception $e) {
        $errors[] = '⚠️ Test verification error: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Login - Gadai Cepat Timika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            padding: 50px 20px;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            margin: 0 auto;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 25px;
        }
        .message-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }
        .error-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #dc3545;
        }
        .btn-action {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    Setup Login Admin
                </h3>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($messages)): ?>
                    <div class="mb-4">
                        <h5 class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Status Setup:</h5>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-box">
                                <?php echo $msg; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="mb-4">
                        <h5 class="mb-3"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Error:</h5>
                        <?php foreach ($errors as $err): ?>
                            <div class="error-box">
                                <?php echo $err; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle me-2"></i>Setup Berhasil!</h5>
                        <p class="mb-2">Database dan admin default telah siap digunakan.</p>
                        <hr>
                        <p class="mb-1"><strong>Kredensial Login:</strong></p>
                        <ul class="mb-0">
                            <li>NIK: <code>admin123</code></li>
                            <li>Password: <code>admin123</code></li>
                        </ul>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <a href="login.php" class="btn btn-action btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login Sekarang
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-times-circle me-2"></i>Setup Gagal</h5>
                        <p class="mb-2">Terjadi kesalahan saat setup. Silakan cek error di atas.</p>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <a href="setup_login.php" class="btn btn-action btn-lg">
                            <i class="fas fa-redo me-2"></i>
                            Coba Lagi
                        </a>
                    </div>
                <?php endif; ?>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Setup ini hanya perlu dijalankan SEKALI. Setelah berhasil, Anda bisa login dengan kredensial di atas.
                    </small>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="login.php" class="text-white text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i>
                Kembali ke Login
            </a>
        </div>
    </div>
</body>
</html>
