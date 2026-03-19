<?php
// patient/cancel-appointment.php - Simplified for single hospital
require_once '../includes/session.php';
require_once '../config/database.php';

redirectIfNotLoggedIn('patient');

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Get cancellable appointments (simplified for single hospital)
$query = "SELECT b.*, d.name as doctor_name, d.specialisation
          FROM booking b
          JOIN doctor d ON b.DID = d.DID
          WHERE b.username = :username 
            AND b.DOV >= CURDATE()
            AND b.Status IN ('Pending', 'Confirmed')
          ORDER BY b.DOV ASC, b.time_slot ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $_SESSION['patient_username']);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_appointment'])) {
    $appointmentId = $_POST['appointment_id'];
    $reason = $_POST['reason'] ?? '';
    
    // ALGORITHM: Validate cancellation
    $query = "SELECT b.*, DATEDIFF(b.DOV, CURDATE()) as days_until 
              FROM booking b 
              WHERE b.id = :id 
              AND b.username = :username 
              AND b.Status IN ('Pending', 'Confirmed')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $appointmentId);
    $stmt->bindParam(':username', $_SESSION['patient_username']);
    $stmt->execute();
    
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        $error = 'Appointment not found or cannot be cancelled';
    } elseif ($appointment['days_until'] < 1) {
        $error = 'Cannot cancel appointment within 24 hours. Please contact the hospital directly.';
    } else {
        // Update appointment status
        $query = "UPDATE booking 
                 SET Status = 'Cancelled', 
                     notes = CONCAT(COALESCE(notes, ''), ' | Cancelled on ', NOW(), ': ', :reason)
                 WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $appointmentId);
        $stmt->bindParam(':reason', $reason);
        
        if ($stmt->execute()) {
            $success = 'Appointment cancelled successfully';
            // Refresh appointments list (simplified for single hospital)
            $query = "SELECT b.*, d.name as doctor_name, d.specialisation
                      FROM booking b
                      JOIN doctor d ON b.DID = d.DID
                      WHERE b.username = :username 
                        AND b.DOV >= CURDATE()
                        AND b.Status IN ('Pending', 'Confirmed')
                      ORDER BY b.DOV ASC, b.time_slot ASC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $_SESSION['patient_username']);
            $stmt->execute();
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to cancel appointment';
        }
    }
}

$page_title = "Cancel Appointment";
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calendar-times me-2"></i>Cancel Appointment</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (empty($appointments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5>No appointments to cancel</h5>
                        <p class="text-muted">You don't have any upcoming appointments that can be cancelled.</p>
                        <a href="book-appointment.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        You can cancel appointments up to 24 hours before the scheduled time. 
                        Cancellations within 24 hours may require you to contact the hospital directly.
                    </div>
                    
                    <div class="row">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Dr. <?php echo $appointment['doctor_name']; ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted"><?php echo $appointment['specialisation']; ?></h6>
                                        
                                        <p class="card-text">
                                            <strong>Date:</strong> <?php echo date('M d, Y', strtotime($appointment['DOV'])); ?><br>
                                            <strong>Time:</strong> <?php echo $appointment['time_slot'] ?? 'Not specified'; ?><br>
                                            <strong>Hospital:</strong> City Hospital<br>
                                            <strong>Status:</strong>
                                        <?php
                                        $statusClass = 'bg-secondary';
                                        if ($appointment['Status'] == 'Pending') $statusClass = 'bg-warning text-dark';
                                        elseif ($appointment['Status'] == 'Confirmed') $statusClass = 'bg-success';
                                        elseif ($appointment['Status'] == 'Cancelled') $statusClass = 'bg-danger';
                                        elseif ($appointment['Status'] == 'Completed') $statusClass = 'bg-info text-dark';
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo $appointment['Status']; ?>
                                        </span>
                                        </p>
                                        
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#cancelModal<?php echo $appointment['id']; ?>">
                                            <i class="fas fa-times me-1"></i>Cancel Appointment
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Cancel Modal -->
                            <div class="modal fade" id="cancelModal<?php echo $appointment['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Confirm Cancellation</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="">
                                            <div class="modal-body">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                
                                                <p>Are you sure you want to cancel your appointment with <strong>Dr. <?php echo $appointment['doctor_name']; ?></strong>?</p>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Appointment Details:</label>
                                                    <div class="alert alert-light">
                                                        <strong>Date:</strong> <?php echo date('F d, Y', strtotime($appointment['DOV'])); ?><br>
                                                        <strong>Time:</strong> <?php echo $appointment['time_slot'] ?? 'Not specified'; ?><br>
                                                        <strong>Hospital:</strong> City Hospital
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Reason for cancellation (optional):</label>
                                                    <textarea class="form-control" name="reason" rows="3" 
                                                              placeholder="Please let us know why you're cancelling..."></textarea>
                                                </div>
                                                
                                                <?php 
                                                $appointmentDate = new DateTime($appointment['DOV']);
                                                $today = new DateTime();
                                                $interval = $today->diff($appointmentDate);
                                                ?>
                                                
                                                <?php if ($interval->days < 1): ?>
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        This appointment is within 24 hours. You may need to contact the hospital directly to cancel.
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Appointment</button>
                                                <button type="submit" name="cancel_appointment" class="btn btn-danger">
                                                    <i class="fas fa-times me-1"></i>Cancel Appointment
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
