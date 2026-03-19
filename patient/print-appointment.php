<?php
// patient/print-appointment.php - Simplified for single hospital
require_once '../includes/session.php';
require_once '../config/database.php';

redirectIfNotLoggedIn('patient');

$appointmentId = $_GET['id'] ?? 0;

if (empty($appointmentId)) {
    die('Appointment ID is required');
}

$database = new Database();
$db = $database->getConnection();

// Get appointment details (simplified for single hospital)
$query = "SELECT b.*, d.name as doctor_name, d.specialisation, d.contact as doctor_contact
          FROM booking b
          JOIN doctor d ON b.DID = d.DID
          WHERE b.id = :id AND b.username = :username";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $appointmentId);
$stmt->bindParam(':username', $_SESSION['patient_username']);
$stmt->execute();

$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    die('Appointment not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Slip - <?php echo $appointmentId; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 20px; }
            .card { border: none !important; box-shadow: none !important; }
        }
        .appointment-slip {
            max-width: 600px;
            margin: 0 auto;
            border: 2px solid #4e73df;
            border-radius: 10px;
            padding: 30px;
        }
        .slip-header {
            text-align: center;
            border-bottom: 2px solid #4e73df;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .slip-footer {
            border-top: 2px solid #4e73df;
            padding-top: 20px;
            margin-top: 20px;
            text-align: center;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container mt-4 no-print">
        <div class="row">
            <div class="col-md-12 text-center">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Slip
                </button>
                <a href="my-appointments.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="appointment-slip">
            <div class="slip-header">
                <h3><i class="fas fa-calendar-check me-2"></i>City Hospital</h3>
                <p class="text-muted mb-0">Online Appointment Management System</p>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-primary">Appointment ID</h6>
                    <p class="h4">APT<?php echo str_pad($appointment['id'], 6, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="text-primary">Status</h6>
                    <span class="badge badge-<?php echo strtolower($appointment['Status']); ?> fs-6">
                        <?php echo $appointment['Status']; ?>
                    </span>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-primary">Patient Name</h6>
                    <p><?php echo $appointment['Fname']; ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="text-primary">Booking Date</h6>
                    <p><?php echo date('M d, Y', strtotime($appointment['Timestamp'])); ?></p>
                </div>
            </div>
            
            <hr>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-primary">Doctor</h6>
                    <p class="h5">Dr. <?php echo $appointment['doctor_name']; ?></p>
                    <p class="text-muted mb-0"><?php echo $appointment['specialisation']; ?></p>
                    <p class="text-muted">Contact: <?php echo $appointment['doctor_contact'] ?? 'N/A'; ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="text-primary">Hospital</h6>
                    <p class="h5">City Hospital</p>
                    <p class="text-muted mb-0">Kathmandu, Nepal</p>
                    <p class="text-muted">Contact: 01-4567890</p>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-primary">Appointment Date</h6>
                    <p class="h4"><?php echo date('l, F d, Y', strtotime($appointment['DOV'])); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="text-primary">Time Slot</h6>
                    <p class="h4"><?php echo $appointment['time_slot'] ?? 'Not specified'; ?></p>
                </div>
            </div>
            
            <?php if ($appointment['notes']): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <h6 class="text-primary">Notes</h6>
                    <p class="text-muted"><?php echo nl2br($appointment['notes']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="slip-footer">
                <p class="mb-1">Please arrive 15 minutes before your scheduled appointment time.</p>
                <p class="mb-0">For any queries, contact: 01-4567890</p>
            </div>
        </div>
    </div>
</body>
</html>
