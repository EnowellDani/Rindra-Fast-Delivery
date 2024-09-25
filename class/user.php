<?php
class User {
    protected $pdo;
    protected $userId;
    protected $name;
    protected $email;
    protected $phone;
    protected $password;

    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->loadUserDetails();
    }

    // Load user details from the database
    private function loadUserDetails() {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $this->userId]);
        $user = $stmt->fetch();

        if ($user) {
            $this->name = $user['name'];
            $this->email = $user['email'];
            $this->phone = $user['phone'];
            $this->password = $user['password']; // Store hashed password securely
        } else {
            throw new Exception("User not found");
        }
    }

    // Getters for user properties
    public function getName() {
        return $this->name;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getPhone() {
        return $this->phone;
    }

    // Method for user authentication (you can expand this)
    public function authenticate($password) {
        return password_verify($password, $this->password);
    }
}
?>