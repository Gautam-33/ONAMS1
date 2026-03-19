<?php
// patient/my-appointments.php - Simplified for single hospital
require_once '../includes/session.php';
require_once '../config/database.php';

redirectIfNotLoggedIn('patient');

$database = new Database();
$db = $database->getConnection();

// Handle filter
$filter = $_GET['filter'] ?? 'all';
$page = max(1, $_GET['page'] ?? 1);
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Get total count
$query = "SELECT COUNT(*) as total FROM booking WHERE username = :username";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $_SESSION['patient_username']);
$stmt->execute();
$totalAll = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get appointments based on filter (simplified for single hospital)
$today = date('Y-m-d');
$query = "SELECT b.*, d.name as doctor_name, d.specialisation, d.contact as doctor_contact
          FROM booking b
          JOIN doctor d ON b.DID = d.DID
          WHERE b.username = :username";

$params = [':username' => $_SESSION['patient_username']];

// Apply filter
switch ($filter) {
    case 'upcoming':
        $query .= " AND b.DOV >= :today AND b.Status IN ('Pending', 'Confirmed')";
        $params[':today'] = $today;
        break;
    case 'pending':
        $query .= " AND b.Status = 'Pending'";
        break;
    case 'confirmed':
        $query .= " AND b.Status = 'Confirmed'";
        break;
    case 'cancelled':
        $query .= " AND b.Status = 'Cancelled'";
        break;
    case 'past':
        $query .= " AND b.DOV < :today";
        $params[':today'] = $today;
        break;
    case 'completed':
        $query .= " AND b.Status = 'Completed'";
        break;
}

// Get filtered count
$countQuery = "SELECT COUNT(*) as total FROM booking b JOIN doctor d ON b.DID = d.DID WHERE b.username = :username";
if (isset($params[':today'])) {
    if ($filter == 'upcoming') {
        $countQuery .= " AND b.DOV >= :today AND b.Status IN ('Pending', 'Confirmed')";
    } elseif ($filter == 'past') {
        $countQuery .= " AND b.DOV < :today";
    }
}
$stmt = $db->prepare($countQuery);
$stmt->bindParam(':username', $_SESSION['patient_username']);
if (isset($params[':today'])) {
    $stmt->bindParam(':today', $params[':today']);
}
$stmt->execute();
$filteredTotal = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$totalPages = ceil($filteredTotal / $itemsPerPage);

$query .= " ORDER BY b.DOV DESC, b.time_slot DESC LIMIT :offset, :limit";

$stmt = $db->prepare($query);
$stmt->bindParam(':username', $_SESSION['patient_username']);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    if ($key != ':username' && $key != ':offset' && $key != ':limit') {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build result array
$result = [
    'appointments' => $appointments,
    'total' => $filteredTotal,
    'filter' => $filter,
    'total_all' => $totalAll,
    'total_pages' => $totalPages,
    'page' => $page
];

// Handle cancellation request
if (isset($_POST['cancel_appointment'])) {
    $appointmentId = $_POST['appointment_id'];
    $reason = $_POST['reason'] ?? '';
    
    // Check if appointment exists
    $query = "SELECT b.* FROM booking b 
              WHERE b.id = :id 
              AND b.username = :username 
              AND b.Status IN ('Pending', 'Confirmed')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $appointmentId);
    $stmt->bindParam(':username', $_SESSION['patient_username']);
    $stmt->execute();
    
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($appointment) {
        // Check if appointment is within 24 hours
        $appointmentDate = new DateTime($appointment['DOV']);
        $today = new DateTime();
        $interval = $today->diff($appointmentDate);
        
        if ($interval->days < 1 && $interval->invert == 0) {
            $_SESSION['error'] = 'Cannot cancel appointment within 24 hours. Please contact hospital directly.';
        } else {
            // Update status to cancelled
            $query = "UPDATE booking SET Status = 'Cancelled', notes = CONCAT(COALESCE(notes, ''), ' Cancelled by patient: ', :reason) 
                     WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $appointmentId);
            $stmt->bindParam(':reason', $reason);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Appointment cancelled successfully';
            } else {
                $_SESSION['error'] = 'Failed to cancel appointment';
            }
        }
    } else {
        $_SESSION['error'] = 'Appointment not found or cannot be cancelled';
    }
    
    header('Location: my-appointments.php?filter=' . $filter . '&page=' . $page);
    exit();
}

$page_title = "My Appointments";
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>My Appointments</h5>
                <div class="btn-group">
                    <a href="simple-book.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>New Appointment
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php 
                $message = getMessage();
                if ($message): 
                ?>
                    <div class="alert alert-<?php echo $message['type']; ?>">
                        <?php echo $message['text']; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filter Tabs -->
                <ul class="nav nav-tabs mb-4" id="appointmentTabs">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter == 'all' ? 'active' : ''; ?>" 
                           href="?filter=all">All (<?php echo $result['total_all']; ?>)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter == 'upcoming' ? 'active' : ''; ?>" 
                           href="?filter=upcoming">Upcoming</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter == 'pending' ? 'active' : ''; ?>" 
                           href="?filter=pending">Pending</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter == 'confirmed' ? 'active' : ''; ?>" 
                           href="?filter=confirmed">Confirmed</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter == 'cancelled' ? 'active' : ''; ?>" 
                           href="?filter=cancelled">Cancelled</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter == 'past' ? 'active' : ''; ?>" 
                           href="?filter=past">Past</a>
                    </li>
                </ul>
                
                <?php if (empty($result['appointments'])): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5>No appointments found</h5>
                        <p class="text-muted">You don't have any appointments with this filter.</p>
                        <a href="simple-book.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-2"></i>Book Your First Appointment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Date & Time</th>
                                    <th>Doctor</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['appointments'] as $appointment): ?>
                                    <tr>
                                        <td>APT<?php echo str_pad($appointment['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <strong><?php echo date('M d, Y', strtotime($appointment['DOV'])); ?></strong><br>
                                            <small><?php echo $appointment['time_slot'] ?? 'Time not set'; ?></small>
                                        </td>
                                        <td>
                                            <strong>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($appointment['specialisation']); ?></small><br>
                                            <small><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($appointment['doctor_contact'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                        <td>
                                            <span class="badge bg-success">Free</span><br>
                                            <small class="text-muted">No Payment</small>
                                        </td>
                                        <td>
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
                                            <?php if ($appointment['Status'] == 'Pending'): ?>
                                                <br><small class="text-muted">Awaiting confirmation</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#detailsModal<?php echo $appointment['id']; ?>"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if (in_array($appointment['Status'], ['Pending', 'Confirmed'])): ?>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#cancelModal<?php echo $appointment['id']; ?>"
                                                            title="Cancel">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Details Modal -->
                                    <div class="modal fade" id="detailsModal<?php echo $appointment['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Appointment Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Appointment Information</h6>
                                                            <table class="table table-borderless">
                                                                <tr>
                                                                    <th width="40%">Appointment ID:</th>
                                                                    <td>APT<?php echo str_pad($appointment['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Date:</th>
                                                                    <td><?php echo date('F d, Y', strtotime($appointment['DOV'])); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Time:</th>
                                                                    <td><?php echo $appointment['time_slot'] ?? 'Not specified'; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Status:</th>
                                                                    <td>
                                                                        <span class="badge <?php echo $statusClass; ?>">
                                                                            <?php echo $appointment['Status']; ?>
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Booked On:</th>
                                                                    <td><?php echo date('M d, Y H:i', strtotime($appointment['Timestamp'])); ?></td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Doctor Information</h6>
                                                            <table class="table table-borderless">
                                                                <tr>
                                                                    <th width="40%">Name:</th>
                                                                    <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Specialization:</th>
                                                                    <td><?php echo htmlspecialchars($appointment['specialisation']); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Contact:</th>
                                                                    <td><?php echo htmlspecialchars($appointment['doctor_contact'] ?? 'N/A'); ?></td>
                                                                </tr>
                                                            </table>
                                                            
                                                            <h6 class="mt-3">Hospital Information</h6>
                                                            <table class="table table-borderless">
                                                                <tr>
                                                                    <th width="40%">Name:</th>
                                                                    <td>City Hospital</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Contact:</th>
                                                                    <td>01-4567890</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <?php if ($appointment['notes']): ?>
                                                        <div class="mt-3">
                                                            <h6>Notes</h6>
                                                            <div class="alert alert-info">
                                                                <?php echo nl2br(htmlspecialchars($appointment['notes'])); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <?php if ($appointment['Status'] == 'Confirmed'): ?>
                                                        <button type="button" class="btn btn-primary" onclick="printAppointment(<?php echo $appointment['id']; ?>)">
                                                            <i class="fas fa-print me-2"></i>Print
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Cancel Modal -->
                                    <?php if (in_array($appointment['Status'], ['Pending', 'Confirmed'])): ?>
                                    <div class="modal fade" id="cancelModal<?php echo $appointment['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Cancel Appointment</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                        
                                                        <p>Are you sure you want to cancel this appointment?</p>
                                                        <div class="mb-3">
                                                            <label class="form-label">Reason for cancellation (optional)</label>
                                                            <textarea class="form-control" name="reason" rows="3" 
                                                                      placeholder="Please provide a reason for cancellation"></textarea>
                                                        </div>
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            <strong>Note:</strong> Cancellations within 24 hours of appointment may not be possible.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep It</button>
                                                        <button type="submit" name="cancel_appointment" class="btn btn-danger">Yes, Cancel Appointment</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($result['total_pages'] > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $result['total_pages']): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <p class="text-muted">
                            Showing <?php echo count($result['appointments']); ?> of <?php echo $result['total_all']; ?> appointments
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function printAppointment(appointmentId) {
    window.open(`print-appointment.php?id=${appointmentId}`, '_blank');
}
</script>

<?php include '../includes/footer.php'; ?>
