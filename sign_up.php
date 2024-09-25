<?php
session_start();
require 'database.php';  // Include your database connection

$message = ''; // Variable to store success or error message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);    // Get name from the form
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);  // Get phone number
    $address = trim($_POST['address']);  // Get address
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Initialize validation flags
    $valid = true;

    // Validate Name
    if (empty($name)) {
        $message .= '<div class="alert alert-danger">Name is required.</div>';
        $valid = false;
    }

    // Validate Email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message .= '<div class="alert alert-danger">A valid email is required.</div>';
        $valid = false;
    }

    // Validate Phone (optional: can add more checks for format)
    if (!empty($phone) && !preg_match('/^[0-9]+$/', $phone)) {
        $message .= '<div class="alert alert-danger">Phone number must contain only digits.</div>';
        $valid = false;
    }

    // Validate Password
    if (empty($password)) {
        $message .= '<div class="alert alert-danger">Password is required.</div>';
        $valid = false;
    } elseif (strlen($password) < 6) {
        $message .= '<div class="alert alert-danger">Password must be at least 6 characters long.</div>';
        $valid = false;
    }

    // Proceed only if validation passed
    if ($valid) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Separate queries for checking if the email already exists in each table
        $tables = ['pending_users', 'clients', 'drivers', 'admins'];
        $emailExists = false;

        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SELECT email FROM $table WHERE email = :email");
            $stmt->execute([':email' => $email]);

            if ($stmt->fetchColumn()) {
                $emailExists = true;
                break;
            }
        }

        if ($emailExists) {
            $message .= '<div class="alert alert-danger">Error: This email is already registered. Please use a different email.</div>';
        } else {
            // Prepare the INSERT statement into the pending_users table
            $stmt = $pdo->prepare("
                INSERT INTO pending_users (username, email, phone, role, password, address) 
                VALUES (:username, :email, :phone, :role, :password, :address)
            ");
            
            $params = [
                ':username' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':role' => $role,
                ':password' => $hashedPassword,
                ':address' => $address
            ];

            if ($stmt->execute($params)) {
                $_SESSION['signup_message'] = 'Thank you for signing up! Please wait for approval and check your email for updates regarding your registration.';
                // Redirect to index.php after successful registration
                header('Location: index.php');
                exit(); // Stop further execution
            } else {
                $message .= '<div class="alert alert-danger">Error during registration. Please try again.</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <title>Sign Up</title>
</head>
<body>
<div class="container mt-5">
    <h2>Sign Up</h2>

    <!-- Display message for errors or success -->
    <?php if ($message): ?>
        <?= $message; ?>
    <?php endif; ?>

    <form method="POST" action="sign_up.php">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="phone" name="phone" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select class="form-select" id="role" name="role" required>
                <option value="client">Client</option>
                <option value="driver">Driver</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address (optional for clients)</label>
            <input type="text" class="form-control" id="address" name="address">
        </div>
        <button type="submit" class="btn btn-primary">Sign Up</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>