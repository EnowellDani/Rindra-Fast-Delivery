<?php
session_start();
require 'database.php';  // Ensure this path is correct

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];  // Using email for login
    $password = $_POST['password'];

    // Fetch the user from the database by email
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?"); // Check admin first
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    // Fetch user for driver and client if admin not found
    if (!$admin) {
        $stmt = $pdo->prepare("SELECT * FROM drivers WHERE email = ?"); // Check drivers
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Fetch client if neither admin nor user found
        if (!$user) {
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?"); // Check clients
            $stmt->execute([$email]);
            $client = $stmt->fetch();
        }
    }

    // Check if the user exists and if the password is correct
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['role'] = 'admin';
        header('Location: view/admin_dashboard.php');
        exit;

    } elseif ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = 'driver';
        header('Location: view/driver_dashboard.php');
        exit;

    } elseif (isset($client) && password_verify($password, $client['password'])) {
        $_SESSION['user_id'] = $client['id'];
        $_SESSION['role'] = 'client';
        header('Location: view/client_dashboard.php');
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rindra Delivery Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css"> <!-- Link to your custom CSS -->
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="index.php">Rindra Delivery Service</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="sign_up.php">Sign Up</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title text-center">Login</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mt-3"><?= htmlspecialchars($error); ?></div>
                    <?php endif ; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-qTOifltYudHQsNWXtG4JgGF9zjuimTUFRQSW0PBpJQHnP" crossorigin="anonymous"></script>
</body>
</html>