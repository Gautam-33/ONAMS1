<?php
// api/check-availability.php - Simplified for single hospital
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$doctorId = $_GET['doctor_id'] ?? '';
$date = $_GET['date'] ?? '';
$time = $_GET['time'] ?? '';

if (empty($doctorId) || empty($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

// ALGORITHM: Availability Checking for Single Hospital
try {
    // Get day of week
    $dateObj = new DateTime($date);
    $dayOfWeek = $dateObj->format('l'); // Monday, Tuesday, etc.
    
    // Check if doctor is available on this day (simplified for single hospital)
    $query = "SELECT da.* FROM doctor_available da 
              WHERE da.DID = :doctorId 
              AND da.day = :dayOfWeek";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doctorId', $doctorId);
    $stmt->bindParam(':dayOfWeek', $dayOfWeek);
    $stmt->execute();
    
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode([
            'available' => false,
            'message' => 'Doctor not available on ' . $dayOfWeek,
            'available_slots' => 0
        ]);
        exit();
    }
    
    // Calculate available time slots
    $startTime = strtotime($schedule['starttime']);
    $endTime = strtotime($schedule['endtime']);
    $slotDuration = 20 * 60; // 20 minutes in seconds
    $maxPatients = $schedule['max_patients'] ?? 1;
    
    // Get existing appointments for this date
    $query = "SELECT time_slot FROM booking 
              WHERE DID = :doctorId 
              AND DOV = :date 
              AND Status IN ('Pending', 'Confirmed')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doctorId', $doctorId);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Count appointments per time slot
    $slotCounts = [];
    foreach ($bookedSlots as $slot) {
        $slotCounts[$slot] = ($slotCounts[$slot] ?? 0) + 1;
    }
    
    // If specific time is requested, check that slot
    if (!empty($time)) {
        $slotCount = $slotCounts[$time] ?? 0;
        $available = ($slotCount < $maxPatients);
        
        echo json_encode([
            'available' => $available,
            'message' => $available ? 'Time slot available' : 'Time slot fully booked',
            'available_slots' => $maxPatients - $slotCount,
            'booked_count' => $slotCount,
            'max_patients' => $maxPatients
        ]);
        exit();
    }
    
    // Generate all available time slots
    $availableSlots = [];
    $current = $startTime;
    
    while ($current < $endTime) {
        $slotTime = date('H:i:s', $current);
        $slotEnd = $current + $slotDuration;
        
        if ($slotEnd <= $endTime) {
            $slotCount = $slotCounts[$slotTime] ?? 0;
            $availableSlots[] = [
                'time' => $slotTime,
                'display' => date('h:i A', $current) . ' - ' . date('h:i A', $slotEnd),
                'available' => ($slotCount < $maxPatients),
                'available_count' => $maxPatients - $slotCount,
                'booked_count' => $slotCount
            ];
        }
        
        $current += $slotDuration;
    }
    
    // Calculate overall availability
    $totalSlots = count($availableSlots);
    $availableCount = count(array_filter($availableSlots, fn($slot) => $slot['available']));
    
    echo json_encode([
        'available' => $availableCount > 0,
        'message' => $availableCount > 0 ? 
                    "$availableCount of $totalSlots time slots available" : 
                    'Fully booked for this day',
        'available_slots' => $availableCount,
        'total_slots' => $totalSlots,
        'slots' => $availableSlots,
        'schedule' => [
            'day' => $dayOfWeek,
            'start_time' => $schedule['starttime'],
            'end_time' => $schedule['endtime'],
            'max_patients_per_slot' => $maxPatients
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
