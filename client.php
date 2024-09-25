<?php
class Client extends User {
    private $address;

    public function __construct($pdo, $userId) {
        parent::__construct($pdo, $userId);
        $this->loadClientDetails();
    }

    // Load additional client details
    private function loadClientDetails() {
        $stmt = $this->pdo->prepare("SELECT address FROM clients WHERE id = :id");
        $stmt->execute([':id' => $this->userId]);
        $client = $stmt->fetch();

        if ($client) {
            $this->address = $client['address'];
        } else {
            throw new Exception("Client details not found");
        }
    }

    // Getter for address
    public function getAddress() {
        return $this->address;
    }
}
?>