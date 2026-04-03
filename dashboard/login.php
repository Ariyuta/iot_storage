<?php
/**
 * Admin Login Page
 */

require_once 'auth_config.php';

if (isLoggedIn()) {
    header('Location: index_n.php');
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        // DEBUG: Menunjukkan apa yang di input
       // echo "DEBUG: Attempting login...<br>";
        //echo "Username entered: '" . htmlspecialchars($username) . "'<br>";
        //echo "Password length: " . strlen($password) . "<br><br>";

        $conn = getDBConnection();
        
        $sql = "SELECT id, username, password, full_name, email, is_active 
                FROM admin_users 
                WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['is_active'] != 1) {
                $error_message = 'Your account has been disabled.';
                logLoginAttempt($username, 0, $conn);
            }
            elseif (verifyPassword($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_full_name'] = $user['full_name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['last_activity'] = time();
                
                $sql_update = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $user['id']);
                $stmt_update->execute();
                $stmt_update->close();
                
                logLoginAttempt($username, 1, $conn);
                
                if ($remember) {
                    setcookie('remember_user', $username, time() + (86400 * 30), "/");
                }
                
                header('Location: index_n.php');
                exit;
            } else {
                $error_message = 'Invalid username or password.';
                logLoginAttempt($username, 0, $conn);
            }
        } else {
            $error_message = 'Invalid username or password.';
            logLoginAttempt($username, 0, $conn);
        }
        
        $stmt->close();
        closeDBConnection($conn);
    }
}

$remembered_username = isset($_COOKIE['remember_user']) ? $_COOKIE['remember_user'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - IoT Storage System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .logo {
            font-size: 60px;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            color: #666;
            font-size: 14px;
            cursor: pointer;
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #999;
            font-size: 12px;
        }
        
        .default-credentials {
            background: #fff9e6;
            border: 1px solid #ffe066;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .default-credentials strong {
            color: #d68910;
        }
        
        .credential-item {
            margin: 5px 0;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🔐</div>
            <h1>Login Admin</h1>
            <p>Sistem Indikator Ketersediaan Barang</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo escape($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?php echo escape($remembered_username); ?>"
                    placeholder="Masukkan Username"
                    required
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Masukkan Password"
                    required
                >
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Ingat Saya untuk 30 hari</label>
            </div>
            
            <button type="submit" class="login-btn">
                🔓 Login to Dashboard
            </button>
        </form>
        
        <div class="default-credentials">
            <strong>⚠️ Default Credentials:</strong><br>
            <div class="credential-item">Username: <strong>admin</strong></div>
            <div class="credential-item">Password: <strong>admin123</strong></div>
            <small style="color: #d68910;">Ubah password setelah login!</small>
        </div>
        
        <div class="login-footer">
            © 2026 Abang Ariza Yutia Pratama.
        </div>
    </div>
</body>
</html>