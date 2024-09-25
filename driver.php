<?php
class Driver {
    private $pdo;
    private $driverId;

    public function __construct($pdo, $driverId) {
        $this->pdo = $pdo;
        $this->driverId = $driverId;
    }

    // Method to get assigned orders for the driver
    public function getAssignedOrders() {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE driver_id = ?");
        $stmt->execute([$this->driverId]);
        return $stmt->fetchAll();
    }
}
?>
