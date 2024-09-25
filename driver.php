<?php
class Driver {
    private $pdo;
    private $driverId;

    public function __construct($pdo, $driverId) {
        $this->pdo = $pdo;
        $this->driverId = $driverId;
    }

    public function getAssignedOrders() {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE driver_id = :driver_id");
        $stmt->execute([':driver_id' => $this->driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
