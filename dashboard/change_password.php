<?php
/**
 * Halaman untuk mengubah password admin
 */

require_once 'auth_config.php';
requireLogin();

$user = getLoggedInUser();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Password must be at least 6 characters.';
    } else {
        $conn = getDBConnection();
        
        $sql = "SELECT password FROM admin_users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        
        if (verifyPassword($current_password, $admin['password'])) {
            $new_hash = hashPassword($new_password);
            $sql_update = "UPDATE admin_users SET password = ? WHERE username = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ss", $new_hash, $user['username']);
            
            if ($stmt_update->execute()) {
                $success_message = 'Password changed successfully!';
            } else {
                $error_message = 'Failed to update password.';
            }
            $stmt_update->close();
        } else {
            $error_message = 'Current password is incorrect.';
        }
        
        $stmt->close();
        closeDBConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
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
        
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
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
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
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
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>🔐 Ubah Password</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo escape($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                ✓ <?php echo escape($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Password saat ini</label>
                <input type="password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">
                💾 Ubah Password
            </button>
            
            <a href="index_n.php" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none; padding: 14px;">
                ⬅️ Kembali ke Dashboard
            </a>
        </form>
        
        <div class="back-link">
            <a href="logout.php">Logout</a>
        </div>
    </div>
</body>
</html>