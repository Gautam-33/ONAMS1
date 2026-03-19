<?php
/**
 * COSINE SIMILARITY ALGORITHM FOR DOCTOR RECOMMENDATION
 * 
 * This algorithm recommends doctors to patients based on:
 * 1. Specialization matching
 * 2. Experience level
 * 3. Location preference
 * 4. Doctor availability
 * 5. Patient booking history preferences
 * 
 * The algorithm uses Cosine Similarity to calculate the similarity
 * between patient preference vectors and doctor feature vectors.
 */

// ALGORITHM: Cosine Similarity Calculation
// Formula: cos(θ) = (A · B) / (||A|| × ||B||)
function calculateCosineSimilarity($vector1, $vector2) {
    // Calculate dot product of two vectors
    $dotProduct = 0;
    foreach ($vector1 as $key => $value) {
        if (isset($vector2[$key])) {
            $dotProduct += ($value * $vector2[$key]);
        }
    }
    
    // Calculate magnitude of vector1
    $magnitude1 = sqrt(array_sum(array_map(function($x) { return $x * $x; }, $vector1)));
    
    // Calculate magnitude of vector2
    $magnitude2 = sqrt(array_sum(array_map(function($x) { return $x * $x; }, $vector2)));
    
    // Calculate cosine similarity
    if ($magnitude1 == 0 || $magnitude2 == 0) {
        return 0;
    }
    
    return $dotProduct / ($magnitude1 * $magnitude2);
}

// ALGORITHM: Build patient preference vector from booking history
function buildPatientVector($patientData, $allSpecializations, $allRegions) {
    $vector = [];
    
    // Specialization vector (one-hot encoding)
    foreach ($allSpecializations as $spec) {
        $vector['spec_' . $spec] = ($patientData['preferred_specialization'] === $spec) ? 1 : 0;
    }
    
    // Region vector (one-hot encoding)
    foreach ($allRegions as $region) {
        $vector['region_' . $region] = ($patientData['preferred_region'] === $region) ? 1 : 0;
    }
    
    // Experience preference (normalized 0-1)
    $vector['experience'] = min(($patientData['preferred_experience'] ?? 5) / 20, 1);
    
    // Availability weight
    $vector['availability'] = $patientData['availability_priority'] ?? 0.5;
    
    return $vector;
}

// ALGORITHM: Build doctor feature vector
function buildDoctorVector($doctor, $allSpecializations, $allRegions) {
    $vector = [];
    
    // Specialization vector (one-hot encoding)
    foreach ($allSpecializations as $spec) {
        $vector['spec_' . $spec] = ($doctor['specialisation'] === $spec) ? 1 : 0;
    }
    
    // Region vector (one-hot encoding)
    foreach ($allRegions as $region) {
        $vector['region_' . $region] = ($doctor['region'] === $region) ? 1 : 0;
    }
    
    // Experience vector (normalized 0-1)
    $vector['experience'] = min((int)$doctor['experience'] / 20, 1);
    
    // Availability score (calculated separately)
    $vector['availability'] = 0;
    
    return $vector;
}

// ALGORITHM: Calculate doctor availability score (Simplified for single hospital)
function calculateAvailabilityScore($db, $doctorId, $requiredDay) {
    $query = "SELECT max_patients, COUNT(b.id) as booked 
              FROM doctor_available da
              LEFT JOIN booking b ON da.DID = b.DID 
              AND da.day = DAYNAME(b.DOV) AND b.Status IN ('Pending', 'Confirmed')
              WHERE da.DID = :doctorId AND da.day = :day
              GROUP BY da.id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doctorId', $doctorId);
    $stmt->bindParam(':day', $requiredDay);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        return 0; // Not available
    }
    
    $maxPatients = $result['max_patients'] ?? 20;
    $booked = $result['booked'] ?? 0;
    
    // Higher score = more available slots
    return 1 - ($booked / $maxPatients);
}

// ALGORITHM: Get recommended doctors using cosine similarity
function getRecommendedDoctors($db, $patientUsername, $limit = 10) {
    $recommendations = [];
    
    // Get patient data
    $query = "SELECT * FROM patient WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $patientUsername);
    $stmt->execute();
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        return [];
    }
    
    // Get all active doctors (with GROUP BY to prevent duplicates)
    $query = "SELECT * FROM doctor WHERE status = 'active' GROUP BY DID";
    $doctors = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all unique specializations and regions
    $allSpecializations = array_unique(array_column($doctors, 'specialisation'));
    $allRegions = array_unique(array_column($doctors, 'region'));
    
    // Build patient preference vector
    $patientVector = buildPatientVector([
        'preferred_specialization' => '',
        'preferred_region' => '',
        'preferred_experience' => 5,
        'availability_priority' => 0.5
    ], $allSpecializations, $allRegions);
    
    // Get patient's booking history to determine preferences
    $historyQuery = "SELECT d.specialisation, d.region, COUNT(*) as visit_count 
                    FROM booking b 
                    JOIN doctor d ON b.DID = d.DID 
                    WHERE b.username = :username AND b.Status = 'Completed'
                    GROUP BY d.DID";
    $stmt = $db->prepare($historyQuery);
    $stmt->bindParam(':username', $patientUsername);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adjust patient vector based on history (learning from past behavior)
    if (!empty($history)) {
        $mostVisitedSpec = $history[0]['specialisation'] ?? '';
        $mostVisitedRegion = $history[0]['region'] ?? '';
        
        if (isset($patientVector['spec_' . $mostVisitedSpec])) {
            $patientVector['spec_' . $mostVisitedSpec] += 2; // Weight: highly preferred
        }
        if (isset($patientVector['region_' . $mostVisitedRegion])) {
            $patientVector['region_' . $mostVisitedRegion] += 2; // Weight: highly preferred
        }
    }
    
    // Calculate similarity for each doctor
    foreach ($doctors as $doctor) {
        $doctorVector = buildDoctorVector($doctor, $allSpecializations, $allRegions);
        
        // Calculate availability score (simplified for single hospital)
        $avgAvailability = 0;
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $availabilitySum = 0;
        foreach ($days as $day) {
            $availabilitySum += calculateAvailabilityScore($db, $doctor['DID'], $day);
        }
        $avgAvailability = $availabilitySum / 7;
        
        $doctorVector['availability'] = $avgAvailability;
        
        // Calculate cosine similarity
        $similarity = calculateCosineSimilarity($patientVector, $doctorVector);
        
        if ($similarity > 0) {
            $recommendations[] = [
                'doctor' => $doctor,
                'similarity_score' => $similarity,
                'availability_score' => $avgAvailability
            ];
        }
    }
    
    // Sort by similarity score (descending)
    usort($recommendations, function($a, $b) {
        return $b['similarity_score'] - $a['similarity_score'];
    });
    
    // Return top recommendations
    return array_slice($recommendations, 0, $limit);
}

// ALGORITHM: Simple recommendation based on specialization and history
function getSimpleRecommendations($db, $patientUsername, $specialization = '', $limit = 5) {
    $params = [];
    $whereClause = "WHERE d.status = 'active'";
    
    if (!empty($specialization)) {
        $whereClause .= " AND d.specialisation = :specialization";
        $params[':specialization'] = $specialization;
    }
    
    // Get doctors with their booking counts
    $query = "SELECT d.*, 
              (SELECT COUNT(*) FROM doctor_available da WHERE da.DID = d.DID) as availability_count,
              (SELECT COUNT(*) FROM booking b WHERE b.DID = d.DID AND b.Status = 'Completed') as completed_bookings
              FROM doctor d
              $whereClause
              ORDER BY d.experience DESC, completed_bookings DESC
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
