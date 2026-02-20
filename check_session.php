<?php
/**
 * Quick Session Check - Lihat session setelah login
 */
session_start();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Session Check</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
        }
        .box {
            background: #2d2d2d;
            border: 2px solid #00ff00;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .info { color: #00ffff; }
        h2 { 
            color: #ffff00;
            border-bottom: 2px solid #ffff00;
            padding-bottom: 10px;
        }
        pre {
            background: #000;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .status {
            font-size: 24px;
            font-weight: bold;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-radius: 10px;
        }
        .logged-in {
            background: #004d00;
            border: 3px solid #00ff00;
        }
        .not-logged-in {
            background: #4d0000;
            border: 3px solid #ff0000;
        }
    </style>
</head>
<body>

<h1>🔍 Session Status Check</h1>

<?php
// Check login status
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
?>

<div class="status <?php echo $is_logged_in ? 'logged-in' : 'not-logged-in'; ?>">
    <?php if ($is_logged_in): ?>
        ✅ STATUS: LOGGED IN
    <?php else: ?>
        ❌ STATUS: NOT LOGGED IN
    <?php endif; ?>
</div>

<div class="box">
    <h2>📋 Session Info</h2>
    <p><strong>Session ID:</strong> <span class="info"><?php echo session_id(); ?></span></p>
    <p><strong>Session Status:</strong> 
        <span class="<?php echo session_status() === PHP_SESSION_ACTIVE ? 'success' : 'error'; ?>">
            <?php 
            switch(session_status()) {
                case PHP_SESSION_DISABLED: echo 'DISABLED'; break;
                case PHP_SESSION_NONE: echo 'NONE'; break;
                case PHP_SESSION_ACTIVE: echo 'ACTIVE ✓'; break;
            }
            ?>
        </span>
    </p>
    <p><strong>Session Name:</strong> <span class="info"><?php echo session_name(); ?></span></p>
</div>

<div class="box">
    <h2>📦 Session Variables</h2>
    <?php if (empty($_SESSION)): ?>
        <p class="error">❌ No session variables found</p>
    <?php else: ?>
        <pre><?php print_r($_SESSION); ?></pre>
    <?php endif; ?>
</div>

<div class="box">
    <h2>🔑 Login Session Variables</h2>
    <table style="width: 100%; color: #fff;">
        <tr>
            <td><strong>admin_logged_in:</strong></td>
            <td class="<?php echo isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] ? 'success' : 'error'; ?>">
                <?php echo isset($_SESSION['admin_logged_in']) ? ($_SESSION['admin_logged_in'] ? 'TRUE ✓' : 'FALSE ✗') : 'NOT SET ✗'; ?>
            </td>
        </tr>
        <tr>
            <td><strong>admin_user:</strong></td>
            <td class="<?php echo isset($_SESSION['admin_user']) ? 'success' : 'error'; ?>">
                <?php echo isset($_SESSION['admin_user']) ? htmlspecialchars($_SESSION['admin_user']) : 'NOT SET'; ?>
            </td>
        </tr>
        <tr>
            <td><strong>admin_id:</strong></td>
            <td class="<?php echo isset($_SESSION['admin_id']) ? 'success' : 'error'; ?>">
                <?php echo isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'NOT SET'; ?>
            </td>
        </tr>
        <tr>
            <td><strong>admin_nik:</strong></td>
            <td class="<?php echo isset($_SESSION['admin_nik']) ? 'success' : 'error'; ?>">
                <?php echo isset($_SESSION['admin_nik']) ? htmlspecialchars($_SESSION['admin_nik']) : 'NOT SET'; ?>
            </td>
        </tr>
        <tr>
            <td><strong>login_time:</strong></td>
            <td class="<?php echo isset($_SESSION['login_time']) ? 'success' : 'error'; ?>">
                <?php 
                if (isset($_SESSION['login_time'])) {
                    echo date('Y-m-d H:i:s', $_SESSION['login_time']);
                    echo ' (' . (time() - $_SESSION['login_time']) . ' seconds ago)';
                } else {
                    echo 'NOT SET';
                }
                ?>
            </td>
        </tr>
    </table>
</div>

<div class="box">
    <h2>🍪 Cookie Info</h2>
    <p><strong>Session Cookie Name:</strong> <span class="info"><?php echo session_name(); ?></span></p>
    <p><strong>Session Cookie Value:</strong> 
        <span class="info">
            <?php echo isset($_COOKIE[session_name()]) ? substr($_COOKIE[session_name()], 0, 20) . '...' : 'NOT SET'; ?>
        </span>
    </p>
    <p><strong>Cookie Parameters:</strong></p>
    <pre><?php print_r(session_get_cookie_params()); ?></pre>
</div>

<div class="box">
    <h2>⚙️ PHP Session Configuration</h2>
    <table style="width: 100%; color: #fff;">
        <tr>
            <td><strong>session.save_path:</strong></td>
            <td class="info"><?php echo ini_get('session.save_path') ?: 'default'; ?></td>
        </tr>
        <tr>
            <td><strong>session.save_handler:</strong></td>
            <td class="info"><?php echo ini_get('session.save_handler'); ?></td>
        </tr>
        <tr>
            <td><strong>session.use_cookies:</strong></td>
            <td class="<?php echo ini_get('session.use_cookies') ? 'success' : 'error'; ?>">
                <?php echo ini_get('session.use_cookies') ? 'ON' : 'OFF'; ?>
            </td>
        </tr>
        <tr>
            <td><strong>session.cookie_lifetime:</strong></td>
            <td class="info"><?php echo ini_get('session.cookie_lifetime'); ?> seconds</td>
        </tr>
        <tr>
            <td><strong>session.gc_maxlifetime:</strong></td>
            <td class="info"><?php echo ini_get('session.gc_maxlifetime'); ?> seconds</td>
        </tr>
    </table>
</div>

<div class="box">
    <h2>🔧 Quick Actions</h2>
    <p>
        <a href="login.php" style="color: #00ffff; text-decoration: none;">← Back to Login</a> |
        <a href="admin_verifikasi.php" style="color: #00ffff; text-decoration: none;">Go to Admin Dashboard →</a> |
        <a href="debug_session.php" style="color: #00ffff; text-decoration: none;">Debug Session</a> |
        <a href="?clear=1" style="color: #ff0000; text-decoration: none;">Clear Session</a>
    </p>
</div>

<?php
// Clear session if requested
if (isset($_GET['clear'])) {
    session_destroy();
    echo '<meta http-equiv="refresh" content="1">';
    echo '<p class="success">✓ Session cleared! Refreshing...</p>';
}
?>

</body>
</html>
