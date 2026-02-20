<?php
/**
 * Test Login - File untuk test login secara manual
 * Akses: http://localhost/GadaiHp/test_login.php
 * 
 * Masukkan NIK dan password, lalu lihat hasilnya
 */

require_once 'database.php';
session_start();

$result = [];
$test_nik = 'admin123';
$test_password = 'admin123';

// Override jika ada POST
if (isset($_POST['test_nik'])) {
    $test_nik = $_POST['test_nik'];
    $test_password = $_POST['test_password'];
}

$result['step1'] = "✓ Database connected";

// Test query admin
try {
    $stmt = $conn->prepare("SELECT id, nik, password, nama FROM admin WHERE nik = ?");
    if ($stmt === false) {
        $result['step2'] = "✗ Prepare statement failed: " . $conn->error;
    } else {
        $result['step2'] = "✓ Prepare statement OK";
        
        $stmt->bind_param("s", $test_nik);
        $stmt->execute();
        $data = $stmt->get_result();
        
        if ($data->num_rows === 0) {
            $result['step3'] = "✗ Admin not found with NIK: $test_nik";
        } else {
            $result['step3'] = "✓ Admin found: " . $data->num_rows . " row(s)";
            
            $admin = $data->fetch_assoc();
            $result['admin_data'] = [
                'id' => $admin['id'],
                'nik' => $admin['nik'],
                'nama' => $admin['nama'],
                'password_hash' => substr($admin['password'], 0, 20) . '...'
            ];
            
            // Test password verify
            if (password_verify($test_password, $admin['password'])) {
                $result['step4'] = "✓ Password MATCH!";
                
                // Test set session
                $_SESSION['test_admin_logged_in'] = true;
                $_SESSION['test_admin_id'] = $admin['id'];
                $_SESSION['test_admin_user'] = $admin['nama'];
                
                $result['step5'] = "✓ Session variables set";
                $result['session_data'] = [
                    'session_id' => session_id(),
                    'test_admin_logged_in' => $_SESSION['test_admin_logged_in'],
                    'test_admin_user' => $_SESSION['test_admin_user']
                ];
                
                $result['conclusion'] = "✅ LOGIN SHOULD WORK! All tests passed.";
            } else {
                $result['step4'] = "✗ Password MISMATCH!";
                $result['conclusion'] = "❌ Password verification failed";
            }
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $result['error'] = "Exception: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 20px;
            font-family: monospace;
        }
        .card {
            max-width: 700px;
            margin: 0 auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .result-box {
            background: #f8f9fa;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            font-size: 14px;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            font-size: 12px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">🧪 Test Login Functionality</h4>
            </div>
            <div class="card-body">
                <form method="post" class="mb-4">
                    <div class="row">
                        <div class="col-md-5">
                            <input type="text" name="test_nik" class="form-control" placeholder="NIK" value="<?php echo htmlspecialchars($test_nik); ?>">
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="test_password" class="form-control" placeholder="Password" value="<?php echo htmlspecialchars($test_password); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Test</button>
                        </div>
                    </div>
                </form>
                
                <h5 class="mb-3">Test Results:</h5>
                <?php foreach ($result as $key => $value): ?>
                    <?php if (is_array($value)): ?>
                        <div class="result-box">
                            <strong><?php echo $key; ?>:</strong>
                            <pre><?php echo json_encode($value, JSON_PRETTY_PRINT); ?></pre>
                        </div>
                    <?php else: ?>
                        <div class="result-box">
                            <?php echo $value; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <hr>
                <div class="d-grid gap-2 mt-3">
                    <a href="login.php" class="btn btn-success">Go to Login Page</a>
                    <a href="debug_session.php" class="btn btn-info">Debug Session</a>
                    <a href="setup_login.php" class="btn btn-warning">Setup Database</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
