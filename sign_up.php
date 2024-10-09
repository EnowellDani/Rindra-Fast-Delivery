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
                // Redirect to login.php after successful registration
                header('Location: login.php'); // Change to login page
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
    <link rel="stylesheet" href="style.css"> <!-- Include your CSS file -->
    <title>Sign Up - Rindra Delivery Service</title>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="index.php"><strong>Rindra Delivery Service</strong></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h2>Sign Up</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="sign_up.php">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Name:</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="phone" class="form-label">Phone Number:</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="form-group mb-3">
                            <label for="address" class="form-label">Address:</label>
                            <input type="text" class="form-control" id="address" name="address">
                        </div>
                        <div class="form-group mb-3">
                            <label for="password" class="form-label">Password:</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group mb-4">
                            <label for="role" class="form-label">Role:</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="client">Client</option>
                                <option value="driver">Driver</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                    </form>
                    <?php if (isset($message)): ?>
                        <div class="alert alert-info mt-3"><?= htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-center mt-3">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>