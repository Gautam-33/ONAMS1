<?php
// config/database.php

class Database {
    private $host = "localhost";
    private $db_name = "hospital_appointment_system";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            die("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Helper functions
function validateNepaliPhone($phone) {
    return preg_match('/^(98|97)[0-9]{8}$/', $phone);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

function generateUniqueId($prefix = 'APT') {
    return $prefix . date('YmdHis') . rand(100, 999);
}

// Function to calculate cosine similarity
if (!function_exists('cosineSimilarity')) {
    function cosineSimilarity($vectorA, $vectorB) {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        foreach ($vectorA as $key => $value) {
            $dotProduct += $value * $vectorB[$key];
            $magnitudeA += pow($value, 2);
            $magnitudeB += pow($vectorB[$key], 2);
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA * $magnitudeB == 0) {
            return 0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
}
?>