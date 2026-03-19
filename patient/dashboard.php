<?php
// patient/dashboard.php
require_once '../includes/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn('patient');

$database = new Database();
$db = $database->getConnection();

// Get patient info
$query = "SELECT * FROM patient WHERE id = :id";
$stmt = $db->prepare($query);
$patientId = $_SESSION['patient_id'];
$stmt->bindParam(':id', $patientId);
$stmt->execute();
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

// If patient not found, try by username
if (!$patient && isset($_SESSION['patient_username'])) {
    $query = "SELECT * FROM patient WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $_SESSION['patient_username']);
    $stmt->execute();
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get appointment statistics
$username = $_SESSION['patient_username'] ?? '';
$query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN Status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN Status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as completed
          FROM booking 
          WHERE username = :username";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get upcoming appointments
$query = "SELECT b.*, d.name as doctor_name, d.specialisation
          FROM booking b
          JOIN doctor d ON b.DID = d.DID
          WHERE b.username = :username 
            AND b.DOV >= CURDATE()
            AND b.Status IN ('Pending', 'Confirmed')
          ORDER BY b.DOV ASC, b.time_slot ASC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent appointments
$query = "SELECT b.*, d.name as doctor_name, d.specialisation
          FROM booking b
          JOIN doctor d ON b.DID = d.DID
          WHERE b.username = :username 
          ORDER BY b.Timestamp DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Patient Dashboard - City Hospital";
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">Welcome, <?php echo htmlspecialchars($patient['name'] ?? $_SESSION['patient_name'] ?? 'Patient'); ?>!</h2>
                            <p class="mb-0 opacity-75">Manage your appointments and health records</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-primary">Patient ID: <?php echo str_pad($patient['id'] ?? 0, 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card text-center h-100">
                <div class="card-body">
                    <h3 class="text-primary"><?php echo $stats['total'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Total Appointments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card text-center h-100">
                <div class="card-body">
                    <h3 class="text-warning"><?php echo $stats['pending'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card text-center h-100">
                <div class="card-body">
                    <h3 class="text-success"><?php echo $stats['confirmed'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Confirmed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card text-center h-100">
                <div class="card-body">
                    <h3 class="text-secondary"><?php echo $stats['completed'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Quick Actions</h5>
                    <a href="simple-book.php" class="btn btn-primary me-2 mb-2">
                        <i class="fas fa-calendar-plus me-1"></i> Book New Appointment
                    </a>
                    <a href="my-appointments.php" class="btn btn-outline-primary me-2 mb-2">
                        <i class="fas fa-list me-1"></i> My Appointments
                    </a>
                    <a href="update-profile.php" class="btn btn-outline-secondary mb-2">
                        <i class="fas fa-user-edit me-1"></i> Update Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Upcoming Appointments -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Appointments</h5>
                    <a href="my-appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcoming)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No upcoming appointments</p>
                            <a href="simple-book.php" class="btn btn-primary">Book Appointment</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Doctor</th>
                                        <th>Specialization</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming as $appointment): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($appointment['DOV'])); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['time_slot']); ?></td>
                                            <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['specialisation']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $appointment['Status'] == 'Confirmed' ? 'success' : 
                                                         ($appointment['Status'] == 'Pending' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($appointment['Status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="cancel-appointment.php?id=<?php echo $appointment['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Activity -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent)): ?>
                        <p class="text-muted text-center">No recent activity</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recent as $appointment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Appointment with Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h6>
                                        <small><?php echo date('M d', strtotime($appointment['DOV'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($appointment['specialisation']); ?></p>
                                    <small>
                                        Status: 
                                        <span class="badge bg-<?php 
                                            echo $appointment['Status'] == 'Confirmed' ? 'success' : 
                                                 ($appointment['Status'] == 'Pending' ? 'warning' : 
                                                      ($appointment['Status'] == 'Cancelled' ? 'danger' : 'secondary')); 
                                        ?>">
                                            <?php echo htmlspecialchars($appointment['Status']); ?>
                                        </span>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Hospital Info -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-hospital me-2"></i>Hospital Information</h5>
                </div>
                <div class="card-body">
                    <h5>City Hospital & Medical Center</h5>
                    <p class="text-muted mb-2">Kathmandu, Nepal</p>
                    <hr>
                    <p class="mb-1"><i class="fas fa-phone me-2"></i>+977-1-4567890</p>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i>info@cityhospital.com</p>
                    <p class="mb-0"><i class="fas fa-clock me-2"></i>Emergency: 24/7 Available</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
