<?php
// patient/index.php
require_once '../includes/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn('patient');

$database = new Database();
$db = $database->getConnection();

// Get patient info
$query = "SELECT * FROM patient WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['patient_id']);
$stmt->execute();
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

// If patient not found, try to find by username as fallback
if (!$patient && isset($_SESSION['patient_username'])) {
    $query = "SELECT * FROM patient WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $_SESSION['patient_username']);
    $stmt->execute();
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get upcoming appointments
$username = $_SESSION['patient_username'] ?? '';
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

// Get all doctors (except deleted)
$doctors = $db->query("SELECT * FROM doctor WHERE status != 'deleted' ORDER BY name")->fetchAll();

// Get all unique specializations for filter
$specializations = $db->query("SELECT DISTINCT specialisation FROM doctor WHERE status != 'deleted' ORDER BY specialisation")->fetchAll(PDO::FETCH_COLUMN);

// Set default values if patient not found
if (!$patient) {
    $patient = [
        'name' => 'Guest User',
        'id' => 0,
        'email' => 'N/A',
        'phone' => 'N/A'
    ];
}

$page_title = "Patient Portal - City Hospital";
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Welcome back, <?php echo htmlspecialchars($patient['name'] ?? 'Guest'); ?>!</h5>
                <span class="badge bg-primary">Patient ID: <?php echo str_pad($patient['id'] ?? 0, 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card stat-card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo count($upcoming); ?></h3>
                                <p class="text-muted">Upcoming Appointments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h6>Quick Actions</h6>
                        <a href="book-appointment.php" class="btn btn-primary me-2">
                            <i class="fas fa-calendar-plus me-1"></i> Book Appointment
                        </a>
                        <a href="my-appointments.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-list me-1"></i> My Appointments
                        </a>
                        <a href="update-profile.php" class="btn btn-outline-secondary">
                            <i class="fas fa-user-edit me-1"></i> Update Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Doctors Section -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-user-md text-primary me-2"></i>Available Doctors</h5>
            <a href="book-appointment.php" class="btn btn-primary btn-sm">
                <i class="fas fa-calendar-plus me-1"></i> Book Now
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Search and Filter -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="searchDoctor" placeholder="Search by doctor name...">
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="filterSpecialization">
                    <option value="">All Specializations</option>
                    <?php foreach ($specializations as $spec): ?>
                        <option value="<?php echo htmlspecialchars($spec); ?>"><?php echo htmlspecialchars($spec); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100" onclick="resetFilters()">
                    <i class="fas fa-redo me-1"></i>Reset
                </button>
            </div>
        </div>
        
        <!-- Doctors Grid -->
        <div class="row" id="doctorGrid">
            <?php foreach ($doctors as $doctor): ?>
            <div class="col-md-6 col-lg-4 mb-4 doctor-card" 
                 data-name="<?php echo strtolower($doctor['name']); ?>" 
                 data-specialization="<?php echo strtolower($doctor['specialisation']); ?>">
                <div class="card h-100 border-0 shadow-sm doctor-card-item">
                    <div class="card-body text-center">
                        <div class="doctor-avatar mb-3">
                            <div class="avatar-circle mx-auto">
                                <span class="avatar-initials"><?php echo strtoupper(substr($doctor['name'], 0, 1)); ?></span>
                            </div>
                        </div>
                        <h6 class="card-title mb-1">Dr. <?php echo htmlspecialchars($doctor['name']); ?></h6>
                        <p class="text-primary small mb-2">
                            <i class="fas fa-stethoscope me-1"></i>
                            <?php echo htmlspecialchars($doctor['specialisation']); ?>
                        </p>
                        <div class="doctor-info small">
                            <p class="mb-1 text-muted">
                                <i class="fas fa-briefcase me-2"></i>
                                <?php echo $doctor['experience']; ?> years experience
                            </p>
                            <p class="mb-0 text-muted">
                                <i class="fas fa-phone me-2"></i>
                                <?php echo $doctor['contact']; ?>
                            </p>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0">
                        <a href="book-appointment.php?doctor=<?php echo $doctor['DID']; ?>" 
                           class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-calendar-check me-1"></i> Book Appointment
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($doctors)): ?>
            <div class="col-12 text-center py-4">
                <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                <p class="text-muted">No doctors available at the moment.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- No Results Message -->
        <div class="text-center py-4 d-none" id="noResults">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h6 class="text-muted">No doctors match your search</h6>
            <button class="btn btn-secondary btn-sm" onclick="resetFilters()">
                <i class="fas fa-redo me-1"></i>Reset Filters
            </button>
        </div>
    </div>
</div>

<style>
.doctor-card-item {
    transition: transform 0.2s, box-shadow 0.2s;
}
.doctor-card-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important;
}
.avatar-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
.avatar-initials {
    font-size: 24px;
    font-weight: bold;
}
</style>

<script>
// Search and Filter Functionality
document.getElementById('searchDoctor').addEventListener('keyup', filterDoctors);
document.getElementById('filterSpecialization').addEventListener('change', filterDoctors);

function filterDoctors() {
    const searchTerm = document.getElementById('searchDoctor').value.toLowerCase();
    const specFilter = document.getElementById('filterSpecialization').value.toLowerCase();
    const cards = document.querySelectorAll('.doctor-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const name = card.dataset.name;
        const specialization = card.dataset.specialization;
        
        const matchesSearch = name.includes(searchTerm);
        const matchesSpec = specFilter === '' || specialization === specFilter;
        
        if (matchesSearch && matchesSpec) {
            card.classList.remove('d-none');
            visibleCount++;
        } else {
            card.classList.add('d-none');
        }
    });
    
    // Show/hide no results message
    const doctorGrid = document.getElementById('doctorGrid');
    const noResults = document.getElementById('noResults');
    
    if (visibleCount === 0 && cards.length > 0) {
        doctorGrid.classList.add('d-none');
        noResults.classList.remove('d-none');
    } else {
        doctorGrid.classList.remove('d-none');
        noResults.classList.add('d-none');
    }
}

function resetFilters() {
    document.getElementById('searchDoctor').value = '';
    document.getElementById('filterSpecialization').value = '';
    filterDoctors();
}
</script>

<!-- Upcoming Appointments -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Appointments</h5>
            </div>
            <div class="card-body">
                <?php if (count($upcoming) > 0): ?>
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
                                <?php foreach ($upcoming as $apt): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($apt['DOV'])); ?></td>
                                        <td><?php echo htmlspecialchars($apt['time_slot']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['specialisation']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $apt['Status'] == 'Confirmed' ? 'success' : 
                                                     ($apt['Status'] == 'Pending' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo htmlspecialchars($apt['Status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="cancel-appointment.php?id=<?php echo $apt['id']; ?>" 
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
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No upcoming appointments</p>
                        <a href="book-appointment.php" class="btn btn-primary">Book Your First Appointment</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Patient Information -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Your Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th>Name:</th>
                        <td><?php echo htmlspecialchars($patient['name'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo htmlspecialchars($patient['email'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><?php echo htmlspecialchars($patient['phone'] ?? ''); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-hospital me-2"></i>Hospital Information</h5>
            </div>
            <div class="card-body">
                <h5>City Hospital & Medical Center</h5>
                <p class="text-muted mb-1">Kathmandu, Nepal</p>
                <p class="mb-1"><i class="fas fa-phone me-2"></i>+977-1-4567890</p>
                <p class="mb-0"><i class="fas fa-envelope me-2"></i>info@cityhospital.com</p>
                <hr>
                <p class="small text-muted mb-0">Emergency: 24/7 Available</p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
