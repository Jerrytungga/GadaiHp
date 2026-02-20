<?php
/**
 * Helper untuk generate password hash
 * Gunakan file ini untuk membuat password hash yang aman
 * 
 * Cara pakai:
 * 1. Jalankan file ini di browser: http://localhost/GadaiHp/generate_password.php
 * 2. Masukkan password yang ingin di-hash
 * 3. Copy hasil hash dan gunakan untuk INSERT/UPDATE ke tabel admin
 */

$generated_hash = '';
$input_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $input_password = $_POST['password'] ?? '';
    
    if (!empty($input_password)) {
        // Generate password hash menggunakan bcrypt (algoritma default PHP)
        $generated_hash = password_hash($input_password, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Password Hash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .hash-output {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            word-break: break-all;
            margin-top: 15px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 30px;
            border-radius: 25px;
        }
        .btn-copy {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4">🔐 Generate Password Hash</h3>
                        
                        <div class="alert alert-info">
                            <strong>Petunjuk:</strong>
                            <ol class="mb-0 mt-2">
                                <li>Masukkan password yang ingin di-hash</li>
                                <li>Klik "Generate Hash"</li>
                                <li>Copy hasil hash</li>
                                <li>Gunakan hash untuk INSERT atau UPDATE ke tabel admin</li>
                            </ol>
                        </div>
                        
                        <form method="post">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password (Plain Text)</label>
                                <input type="text" class="form-control" id="password" name="password" 
                                       value="<?php echo htmlspecialchars($input_password); ?>" 
                                       required placeholder="Contoh: admin123">
                                <small class="text-muted">Password akan di-hash dengan algoritma bcrypt yang aman</small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="generate" class="btn btn-primary">Generate Hash</button>
                            </div>
                        </form>
                        
                        <?php if (!empty($generated_hash)): ?>
                        <div class="mt-4">
                            <h5>Hasil Hash:</h5>
                            <div class="hash-output" id="hashOutput">
                                <?php echo htmlspecialchars($generated_hash); ?>
                            </div>
                            
                            <button class="btn btn-secondary btn-copy w-100" onclick="copyHash()">
                                📋 Copy Hash
                            </button>
                            
                            <div class="alert alert-success mt-3">
                                <strong>Contoh Query SQL:</strong>
                                <pre class="mb-0 mt-2" style="background: white; padding: 10px; border-radius: 5px; font-size: 12px;">-- Insert admin baru
INSERT INTO admin (nik, nama, password, role) VALUES 
('<?php echo htmlspecialchars($input_password === 'admin123' ? 'admin123' : 'NIK_ANDA'); ?>', 'Nama Admin', '<?php echo htmlspecialchars($generated_hash); ?>', 'admin');

-- Update password admin yang sudah ada
UPDATE admin SET password = '<?php echo htmlspecialchars($generated_hash); ?>' WHERE nik = 'NIK_ADMIN';</pre>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-4 text-center">
                            <a href="login.php" class="btn btn-outline-primary">← Kembali ke Login</a>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title">📚 Default Admin</h5>
                        <p class="mb-2">Untuk keperluan testing, gunakan akun admin default:</p>
                        <ul>
                            <li><strong>NIK:</strong> admin123</li>
                            <li><strong>Password:</strong> admin123</li>
                        </ul>
                        <div class="alert alert-warning mb-0">
                            ⚠️ <strong>Penting:</strong> Ganti password default setelah login pertama kali untuk keamanan!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyHash() {
            const hashText = document.getElementById('hashOutput').textContent;
            navigator.clipboard.writeText(hashText).then(() => {
                alert('Hash berhasil di-copy!');
            }).catch(err => {
                // Fallback untuk browser lama
                const textarea = document.createElement('textarea');
                textarea.value = hashText;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Hash berhasil di-copy!');
            });
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
