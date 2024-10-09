<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'database.php'; // Ensure this path is correct

// Function to check if the user has the required role
function check_role($required_role) {
    if (!is_logged_in() || $_SESSION['role'] !== $required_role) {
        // Redirect to the not authorized page if the user does not have the required role
        header('Location: not_authorized.php');
        exit();
    }
}

// Function to check if the user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Function to log in the user
function login($email, $password) {
    global $pdo;

    // Fetch the user from the database by email
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = $pdo->prepare("SELECT * FROM drivers WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        }
    }

    // Check if the user exists and if the password is correct
    if ($user && password_verify($password, $user['password'])) {
        // Store user data in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role']; // Assuming the role is stored in the database

        // Redirect to the appropriate dashboard
        if ($user['role'] === 'admin') {
            header('Location: view/admin_dashboard.php');
        } elseif ($user['role'] === 'driver') {
            header('Location: view/driver_dashboard.php');
        } elseif ($user['role'] === 'client') {
            header('Location: view/client_dashboard.php');
        }
        exit();
    } else {
        return "Invalid email or password.";
    }
}