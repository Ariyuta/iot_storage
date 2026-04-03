<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "<h3>New Password Hash Generator</h3>";
echo "Password: $password<br>";
echo "New hash: <br>";
echo "<textarea rows='3' cols='80'>$hash</textarea><br><br>";
echo "Copy the hash above and paste it into phpMyAdmin!";
?>