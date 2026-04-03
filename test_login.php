<?php
require_once 'api/config.php';

$username = 'admin';
$password = 'admin123';

echo "<h3>Login Test</h3>";
echo "Testing username: $username<br>";
echo "Testing password: $password<br><br>";

$conn = getDBConnection();

$sql = "SELECT id, username, password, full_name, email, is_active 
        FROM admin_users 
        WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "❌ ERROR: User 'admin' not found in database!<br>";
} else {
    echo "✅ User found in database!<br><br>";
    
    $user = $result->fetch_assoc();
    
    echo "Database info:<br>";
    echo "- Username: " . $user['username'] . "<br>";
    echo "- Full name: " . $user['full_name'] . "<br>";
    echo "- Email: " . $user['email'] . "<br>";
    echo "- Is active: " . $user['is_active'] . "<br>";
    echo "- Password hash: " . substr($user['password'], 0, 20) . "...<br><br>";
    
    if ($user['is_active'] != 1) {
        echo "❌ ERROR: Account is disabled!<br>";
    } else {
        echo "✅ Account is active<br><br>";
        
        if (password_verify($password, $user['password'])) {
            echo "✅✅✅ PASSWORD MATCHES! Login should work!<br>";
        } else {
            echo "❌ ERROR: Password does NOT match!<br>";
            echo "This means the password hash is wrong.<br>";
        }
    }
}

$stmt->close();
closeDBConnection($conn);
?>