<?php
// api/get-doctors.php - Simplified for single hospital with deduplication
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Get filter parameters
    $specialization = $_GET['specialization'] ?? '';
    
    // Build dynamic WHERE clause based on filters
    $whereConditions = ["d.status = 'active'"];
    
    // Filter by specialization
    if (!empty($specialization)) {
        $whereConditions[] = "d.specialisation = :specialization";
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    // Get all active doctors with their availability information
    // Using GROUP BY d.DID to prevent duplicates
    $query = "SELECT DISTINCT
                d.DID, 
                d.name, 
                d.specialisation,
                d.experience,
                d.address,
                d.contact,
                d.qualification,
                d.consultation_fee,
                GROUP_CONCAT(DISTINCT CONCAT(da.day, ' ', TIME_FORMAT(da.starttime, '%H:%i'), '-', TIME_FORMAT(da.endtime, '%H:%i')) ORDER BY FIELD(da.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') SEPARATOR ', ') as availability
              FROM doctor d
              LEFT JOIN doctor_available da ON d.DID = da.DID
              $whereClause
              GROUP BY d.DID
              ORDER BY d.name ASC";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters only if they have values
    if (!empty($specialization)) {
        $stmt->bindParam(':specialization', $specialization);
    }
    
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Additional PHP-level deduplication to ensure unique doctors
    $uniqueDoctors = [];
    $seenIds = [];
    foreach ($doctors as $doctor) {
        if (!in_array($doctor['DID'], $seenIds)) {
            $seenIds[] = $doctor['DID'];
            $uniqueDoctors[] = $doctor;
        }
    }
    
    echo json_encode($uniqueDoctors);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
