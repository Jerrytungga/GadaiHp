<?php
// Start output buffering
ob_start();

// Include database
require_once 'database.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize messages
$error_message = '';
$success_message = '';

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_verifikasi.php");
    exit();
}

// Check for timeout parameter
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error_message = 'Sesi Anda telah berakhir. Silakan login kembali.';
}

// Handle login form submission
if (isset($_POST['login'])) {
    $nik = isset($_POST['nik']) ? trim($_POST['nik']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validation
    if (empty($nik) || empty($password)) {
        $error_message = 'NIK dan password harus diisi.';
    } else {
        try {
            // Prepare statement untuk keamanan
            $stmt = $conn->prepare("SELECT id, nik, password, nama, role FROM admin WHERE nik = ?");
            
            if ($stmt === false) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            $stmt->bind_param("s", $nik);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                
                // Verify password dengan password_verify
                if (password_verify($password, $admin['password'])) {
                    // Regenerate session ID untuk keamanan
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_user'] = $admin['nama'] ?? $admin['nik'];
                    $_SESSION['admin_nik'] = $admin['nik'];
                    $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                    $_SESSION['login_time'] = time();
                    
                    // Paksa commit session ke disk (JANGAN session_write_close!)
                    session_commit();
                    
                    // Redirect ke dashboard
                    header("Location: admin_verifikasi.php");
                    exit();
                    
                } else {
                    $error_message = 'NIK atau password salah.';
                }
            } else {
                $error_message = 'NIK atau password salah.';
            }
            
            $stmt->close();
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $error_message = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - Gadai Cepat Timika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  </head>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Poppins', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }
    
    /* Animated background circles */
    body::before,
    body::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      animation: float 20s infinite;
    }
    
    body::before {
      width: 400px;
      height: 400px;
      top: -100px;
      left: -100px;
      animation-delay: 0s;
    }
    
    body::after {
      width: 300px;
      height: 300px;
      bottom: -80px;
      right: -80px;
      animation-delay: 5s;
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-30px) rotate(180deg); }
    }
    
    .container {
      position: relative;
      z-index: 1;
    }
    
    .login-wrapper {
      max-width: 450px;
      margin: 0 auto;
    }
    
    .logo-container {
      text-align: center;
      margin-bottom: 30px;
      animation: fadeInDown 0.8s ease;
    }
    
    .logo {
      width: 120px;
      height: 120px;
      background: white;
      border-radius: 50%;
      padding: 20px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      margin-bottom: 20px;
      transition: all 0.3s ease;
    }
    
    .logo:hover {
      transform: scale(1.05) rotate(5deg);
    }
    
    .logo-text {
      color: white;
      font-size: 28px;
      font-weight: 700;
      text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
      margin-bottom: 5px;
      letter-spacing: 1px;
    }
    
    .logo-subtitle {
      color: rgba(255, 255, 255, 0.9);
      font-size: 14px;
      font-weight: 300;
    }
    
    .card {
      border: none;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(10px);
      background: rgba(255, 255, 255, 0.95);
      animation: fadeInUp 0.8s ease;
      overflow: hidden;
    }
    
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }
    
    .card-body {
      padding: 40px 35px;
    }
    
    .card-title {
      color: #333;
      font-weight: 600;
      font-size: 24px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    
    .card-subtitle {
      color: #666;
      font-size: 14px;
      text-align: center;
      margin-bottom: 30px;
      font-weight: 300;
    }
    
    .form-label {
      color: #555;
      font-weight: 500;
      font-size: 14px;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .form-label i {
      color: #667eea;
    }
    
    .input-wrapper {
      position: relative;
      margin-bottom: 20px;
    }
    
    .form-control {
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      padding: 14px 18px;
      padding-right: 45px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: #f8f9fa;
      width: 100%;
    }
    
    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
      background: white;
      transform: translateY(-2px);
      outline: none;
    }
    
    .form-control::placeholder {
      color: #aaa;
      font-weight: 300;
    }
    
    .password-toggle {
      position: absolute;
      right: 18px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #999;
      transition: color 0.3s;
      z-index: 10;
      padding: 5px;
    }
    
    .password-toggle:hover {
      color: #667eea;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 12px;
      padding: 14px;
      font-weight: 600;
      font-size: 16px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      letter-spacing: 0.5px;
      width: 100%;
    }
    .btn-primary::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      transition: left 0.5s;
    }
    
    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:hover::before {
      left: 100%;
    }
    
    .btn-primary:active {
      transform: translateY(-1px);
    }
    
    .alert {
      border: none;
      border-radius: 12px;
      padding: 15px 20px;
      margin-bottom: 20px;
      animation: slideInDown 0.5s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .alert i {
      font-size: 20px;
    }
    
    .alert-danger {
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      color: white;
    }
    
    .alert-success {
      background: linear-gradient(135deg, #28a745 0%, #218838 100%);
      color: white;
    }
    
    .alert-warning {
      background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
      color: #333;
    }
    
    .footer-text {
      text-align: center;
      margin-top: 25px;
      animation: fadeIn 1s ease 0.5s both;
    }
    
    .footer-text small {
      color: rgba(255, 255, 255, 0.9);
      font-weight: 300;
      font-size: 13px;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    }
    
    .helper-links {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 15px;
      animation: fadeIn 1s ease 0.6s both;
      flex-wrap: wrap;
    }
    
    .helper-links a {
      color: white;
      text-decoration: none;
      font-size: 13px;
      font-weight: 400;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 8px 15px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      backdrop-filter: blur(10px);
    }
    
    .helper-links a:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
    }
    
    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @keyframes slideInDown {
      from {
        transform: translateY(-20px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }
    
    /* Loading spinner */
    .spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 0.6s linear infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    /* Responsive */
    @media (max-width: 576px) {
      .logo {
        width: 100px;
        height: 100px;
        padding: 15px;
      }
      
      .logo-text {
        font-size: 24px;
      }
      
      .card-body {
        padding: 30px 25px;
      }
      
      body::before,
      body::after {
        display: none;
      }
      
      .helper-links {
        flex-direction: column;
        gap: 10px;
      }
    }
  </style>
  <body>
    <div class="container">
        <div class="login-wrapper">
            <!-- Logo Section -->
            <div class="logo-container">
                <img src="image/GC.png" class="logo" alt="Gadai Cepat Timika">
                <h1 class="logo-text">Gadai Cepat Timika</h1>
                <p class="logo-subtitle">Sistem Administrasi & Verifikasi</p>
            </div>
            
            <!-- Alert Messages -->
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Login Card -->
            <div class="card shadow-lg">
                <div class="card-body">
                    <h4 class="card-title">
                        <i class="fas fa-user-shield"></i>
                        Login Admin
                    </h4>
                    <p class="card-subtitle">Masukkan kredensial Anda untuk melanjutkan</p>
                    
                    <form action="" method="post" autocomplete="off" id="loginForm">
                        <!-- NIK Field -->
                        <div class="mb-3">
                            <label for="nik" class="form-label">
                                <i class="fas fa-id-card"></i>
                                NIK Admin
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nik" 
                                   name="nik" 
                                   required 
                                   autocomplete="off"
                                   placeholder="Masukkan NIK Anda"
                                   value="<?php echo isset($_POST['nik']) ? htmlspecialchars($_POST['nik']) : ''; ?>">
                        </div>
                        
                        <!-- Password Field -->
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i>
                                Password
                            </label>
                            <div class="input-wrapper">
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required
                                       autocomplete="off"
                                       placeholder="Masukkan password Anda">
                                <span class="password-toggle" onclick="togglePassword()" title="Tampilkan/Sembunyikan Password">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Login Button -->
                        <div class="d-grid">
                            <button type="submit" name="login" class="btn btn-primary btn-lg" id="loginButton">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Masuk ke Dashboard
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Helper Links -->
            <div class="helper-links">
                <a href="admin_tools.php" title="Admin Tools & Troubleshooting">
                    <i class="fas fa-tools"></i>
                    Admin Tools
                </a>
                <a href="index.php" title="Kembali ke Beranda">
                    <i class="fas fa-home"></i>
                    Beranda
                </a>
            </div>
            
            <!-- Footer -->
            <div class="footer-text">
                <small>
                    <i class="fas fa-copyright"></i>
                    <?php echo date('Y'); ?> Gadai Cepat Timika. All rights reserved.
                </small>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Add keyboard shortcut for password toggle
        document.addEventListener('keydown', function(e) {
            // Alt + S untuk toggle password
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                togglePassword();
            }
        });
        
        // Add loading state on form submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginButton');
            button.innerHTML = '<span class="spinner me-2"></span>Memproses...';
            button.disabled = true;
        });
        
        // Focus on NIK field on page load
        window.addEventListener('load', function() {
            document.getElementById('nik').focus();
        });
        
        // Enter key handling - smooth transition between fields
        document.getElementById('nik').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('password').focus();
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>