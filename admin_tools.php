<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Tools - Gadai Cepat Timika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 20px;
            font-family: 'Poppins', sans-serif;
        }
        .tool-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        .tool-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .header-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-card">
            <h1>🛠️ Admin Tools & Troubleshooting</h1>
            <p class="text-muted mb-0">Pilih tool yang Anda butuhkan untuk setup atau troubleshoot sistem login</p>
        </div>
        
        <div class="row">
            <!-- Auto Setup Database (NEW!) -->
            <div class="col-md-4">
                <div class="tool-card text-center" style="border: 3px solid #ffc107;">
                    <div class="tool-icon text-warning">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h4>Auto Setup Database</h4>
                    <p class="text-muted">Setup SEMUA database otomatis sekali klik!</p>
                    <a href="setup_database.php" class="btn btn-warning w-100">
                        <i class="fas fa-rocket me-2"></i>
                        Auto Setup All
                    </a>
                    <small class="text-success d-block mt-2 fw-bold">✨ RECOMMENDED - Setup Lengkap!</small>
                </div>
            </div>
            
            <!-- Setup Login (Old) -->
            <div class="col-md-4">
                <div class="tool-card text-center">
                    <div class="tool-icon text-secondary">
                        <i class="fas fa-database"></i>
                    </div>
                    <h4>Setup Login Manual</h4>
                    <p class="text-muted">Buat tabel admin dan insert user default</p>
                    <a href="setup_login.php" class="btn btn-secondary w-100">
                        <i class="fas fa-cog me-2"></i>
                        Manual Setup
                    </a>
                    <small class="text-muted d-block mt-2">Setup admin saja (legacy)</small>
                </div>
            </div>
            
            <!-- Login Page -->
            <div class="col-md-4">
                <div class="tool-card text-center">
                    <div class="tool-icon text-primary">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h4>Login Admin</h4>
                    <p class="text-muted">Halaman login untuk masuk ke dashboard</p>
                    <a href="login.php" class="btn btn-primary w-100">
                        <i class="fas fa-user me-2"></i>
                        Go to Login
                    </a>
                    <small class="text-muted d-block mt-2">NIK: admin123 | Pass: admin123</small>
                </div>
            </div>
            
            <!-- Test Login -->
            <div class="col-md-4">
                <div class="tool-card text-center">
                    <div class="tool-icon text-success">
                        <i class="fas fa-flask"></i>
                    </div>
                    <h4>Test Login</h4>
                    <p class="text-muted">Test apakah kredensial login bekerja</p>
                    <a href="test_login.php" class="btn btn-success w-100">
                        <i class="fas fa-check-circle me-2"></i>
                        Test Credentials
                    </a>
                    <small class="text-muted d-block mt-2">Verifikasi database & password hash</small>
                </div>
            </div>
            
            <!-- Debug Session -->
            <div class="col-md-4">
                <div class="tool-card text-center">
                    <div class="tool-icon text-info">
                        <i class="fas fa-bug"></i>
                    </div>
                    <h4>Debug Session</h4>
                    <p class="text-muted">Lihat status session dan variable</p>
                    <a href="debug_session.php" class="btn btn-info w-100">
                        <i class="fas fa-search me-2"></i>
                        Debug Session
                    </a>
                    <small class="text-muted d-block mt-2">Cek apakah session tersimpan</small>
                </div>
            </div>
            
            <!-- Admin Dashboard -->
            <div class="col-md-4">
                <div class="tool-card text-center">
                    <div class="tool-icon text-dark">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h4>Admin Dashboard</h4>
                    <p class="text-muted">Halaman admin utama (butuh login)</p>
                    <a href="admin_verifikasi.php" class="btn btn-dark w-100">
                        <i class="fas fa-home me-2"></i>
                        Dashboard
                    </a>
                    <small class="text-muted d-block mt-2">Halaman setelah login berhasil</small>
                </div>
            </div>
            
            <!-- Generate Password -->
            <div class="col-md-4">
                <div class="tool-card text-center">
                    <div class="tool-icon text-danger">
                        <i class="fas fa-key"></i>
                    </div>
                    <h4>Generate Password</h4>
                    <p class="text-muted">Buat password hash untuk admin baru</p>
                    <a href="generate_password.php" class="btn btn-danger w-100">
                        <i class="fas fa-lock me-2"></i>
                        Generate Hash
                    </a>
                    <small class="text-muted d-block mt-2">Tool untuk membuat password hash</small>
                </div>
            </div>
        </div>
        
        <div class="card mt-4" style="background: white; border-radius: 15px; padding: 20px;">
            <h5><i class="fas fa-info-circle me-2"></i>Troubleshooting Steps:</h5>
            <ol>
                <li><strong>Setup Database</strong> - Jalankan jika belum pernah setup</li>
                <li><strong>Test Login</strong> - Pastikan kredensial bekerja</li>
                <li><strong>Login</strong> - Coba login dengan NIK: admin123 / Password: admin123</li>
                <li><strong>Debug Session</strong> - Jika redirect loop, cek session di sini</li>
            </ol>
            
            <div class="alert alert-info mt-3 mb-0">
                <strong><i class="fas fa-lightbulb me-2"></i>Tips:</strong>
                <ul class="mb-0">
                    <li>Clear browser cache jika ada masalah redirect</li>
                    <li>Pastikan Laragon sudah running (Apache & MySQL)</li>
                    <li>Gunakan incognito window untuk test bersih</li>
                </ul>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="index.php" class="text-white text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i>
                Kembali ke Landing Page
            </a>
        </div>
    </div>
</body>
</html>
