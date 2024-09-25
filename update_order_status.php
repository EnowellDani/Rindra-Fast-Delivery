<?php
session_start();
require 'database.php';  // Include database connection

// Ensure the driver is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header('Location: login.php');  // Redirect to login if not logged in as a driver
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve order_id and new_status from the POST request
    $order_id = $_POST['order_id'] ?? null;
    $new_status = $_POST['status'] ?? null;  // Expecting "completed" or "cancelled"

    if ($order_id === null || $new_status === null) {
        $_SESSION['error_message'] = "Invalid order ID or status.";
        header('Location: driver_dashboard.php');
        exit;
    }

    try {
        // Begin a transaction to ensure data integrity
        $pdo->beginTransaction();

        // Fetch the order details before updating
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :order_id");
        $stmt->execute([':order_id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Update order status in the 'orders' table
            $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
            $stmt->execute([':status' => $new_status, ':order_id' => $order_id]);

            // If the order is completed or cancelled, move it to the 'order_history' table
            if ($new_status === 'completed' || $new_status === 'cancelled') {
                // Insert into 'order_history' table
                $stmt = $pdo->prepare("INSERT INTO order_history (order_id, client_id, driver_id, address, status, created_at)
                                       VALUES (:order_id, :client_id, :driver_id, :address, :status, NOW())");
                $stmt->execute([
                    ':order_id' => $order['id'],
                    ':client_id' => $order['client_id'],
                    ':driver_id' => $order['driver_id'],
                    ':address' => $order['address'],
                    ':status' => $new_status
                ]);

                // Delete from 'orders' table
                $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :order_id");
                $stmt->execute([':order_id' => $order_id]);
            }

            // Commit transaction
            $pdo->commit();
            $_SESSION['success_message'] = "Order status updated successfully.";
        } else {
            $_SESSION['error_message'] = "Order not found.";
        }
    } catch (Exception $e) {
        // Roll back the transaction if something failed
        $pdo->rollBack();
        $_SESSION['error_message'] = "Failed to update order status: " . $e->getMessage(); // Provide error details for debugging
    }

    // Redirect back to driver dashboard
    header('Location: driver_dashboard.php');
    exit;
}
?>