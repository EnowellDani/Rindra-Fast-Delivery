<?php
// Hash the password for the admin user
$hashedPassword = password_hash('danieyladmin', PASSWORD_DEFAULT);
echo $hashedPassword;
?>