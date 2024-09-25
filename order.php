<?php
class Order {
    private $pdo;
    private $orderId;
    private $clientName;
    private $deliveryAddress;
    private $status;

    public function __construct($pdo, $orderId) {
        $this->pdo = $pdo;
        $this->orderId = $orderId;
        $this->loadOrderDetails();
    }

    // Load order details from the database
    private function loadOrderDetails() {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute([':id' => $this->orderId]);
        $order = $stmt->fetch();

        if ($order) {
            $this->clientName = $order['client_name'];
            $this->deliveryAddress = $order['delivery_address'];
            $this->status = $order['status'];
        } else {
            throw new Exception("Order not found");
        }
    }

    // Update the order status
    public function updateStatus($newStatus) {
        $stmt = $this->pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $newStatus, ':id' => $this->orderId]);
    }

    // Getters for the order properties
    public function getClientName() {
        return $this->clientName;
    }

    public function getDeliveryAddress() {
        return $this->deliveryAddress;
    }

    public function getStatus() {
        return $this->status;
    }
}