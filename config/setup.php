<?php
/**
 * Auto Setup - Creates default admin and patient accounts
 * Include this file at the top of login pages
 */

require_once 'database.php';

$database = new Database();
$db = $database->getConnection();

// Create default admin account if not exists
$adminUsername = 'admin';
$adminPassword = 'admin123';

$query = "INSERT INTO admin (username, password) VALUES (:username, :password)
          ON DUPLICATE KEY UPDATE password = :password2";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $adminUsername);
$stmt->bindParam(':password', $adminPassword);
$stmt->bindParam(':password2', $adminPassword);
$stmt->execute();

// Create default patient account if not exists
$patientName = 'Test User';
$patientGender = 'Male';
$patientDob = '1990-01-15';
$patientPhone = '9841000001';
$patientUsername = 'testuser';
$patientPassword = 'test123';
$patientEmail = 'test@example.com';

$query = "INSERT INTO patient (name, gender, dob, phone, username, password, email) 
          VALUES (:name, :gender, :dob, :phone, :username, :password, :email)
          ON DUPLICATE KEY UPDATE password = :password2";
$stmt = $db->prepare($query);
$stmt->bindParam(':name', $patientName);
$stmt->bindParam(':gender', $patientGender);
$stmt->bindParam(':dob', $patientDob);
$stmt->bindParam(':phone', $patientPhone);
$stmt->bindParam(':username', $patientUsername);
$stmt->bindParam(':password', $patientPassword);
$stmt->bindParam(':password2', $patientPassword);
$stmt->bindParam(':email', $patientEmail);
$stmt->execute();

// Create sample doctor if not exists
$doctorName = 'Rajesh Sharma';
$doctorGender = 'Male';
$doctorDob = '1980-05-15';
$doctorExperience = '15';
$doctorSpecialisation = 'Cardiology';
$doctorContact = '9841001001';
$doctorAddress = 'Kathmandu';
$doctorEmail = 'dr.rajesh@cityhospital.com';
$doctorQualification = 'MD Cardiology';

$query = "INSERT INTO doctor (name, gender, dob, experience, specialisation, contact, address, email, qualification) 
          VALUES (:name, :gender, :dob, :experience, :specialisation, :contact, :address, :email, :qualification)
          ON DUPLICATE KEY UPDATE contact = :contact2";
$stmt = $db->prepare($query);
$stmt->bindParam(':name', $doctorName);
$stmt->bindParam(':gender', $doctorGender);
$stmt->bindParam(':dob', $doctorDob);
$stmt->bindParam(':experience', $doctorExperience);
$stmt->bindParam(':specialisation', $doctorSpecialisation);
$stmt->bindParam(':contact', $doctorContact);
$stmt->bindParam(':address', $doctorAddress);
$stmt->bindParam(':email', $doctorEmail);
$stmt->bindParam(':qualification', $doctorQualification);
$stmt->bindParam(':contact2', $doctorContact);
$stmt->execute();

// Create doctor availability for the sample doctor
$query = "SELECT DID FROM doctor WHERE name = :name";
$stmt = $db->prepare($query);
$stmt->bindParam(':name', $doctorName);
$stmt->execute();
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if ($doctor) {
    $query = "INSERT INTO doctor_available (DID, day, starttime, endtime, max_patients) 
              VALUES (:did, :day, :start, :end, :max)
              ON DUPLICATE KEY UPDATE starttime = :start2";
    $days = ['Monday', 'Wednesday', 'Friday'];
    foreach ($days as $day) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':did', $doctor['DID']);
        $stmt->bindParam(':day', $day);
        $startTime = '09:00:00';
        $endTime = '17:00:00';
        $maxPatients = 10;
        $stmt->bindParam(':start', $startTime);
        $stmt->bindParam(':end', $endTime);
        $stmt->bindParam(':max', $maxPatients);
        $stmt->bindParam(':start2', $startTime);
        $stmt->execute();
    }
}

// Hospital settings table is no longer used
// Hospital name is configured in includes/footer.php and includes/header.php
