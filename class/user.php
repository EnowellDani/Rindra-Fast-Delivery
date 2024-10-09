<?php
class User {
    protected $pdo;
    protected $userId;
    protected $name;
    protected $email;
    protected $phone;
    protected $password;
    protected $role; // New property for user role

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
            $this->role = $user['role']; // Load role from the database
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

    public function getRole() {
        return $this->role; // New method to get the user role
    }

    // Method for user authentication
    public function authenticate($password) {
        return password_verify($password, $this->password);
    }

    // Role checks
    public function isAdmin() {
        return $this->role === 'admin';
    }

    public function isDriver() {
        return $this->role === 'driver';
    }

    public function isClient() {
        return $this->role === 'client';
    }
}
?>