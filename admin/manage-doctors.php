<?php
// admin/manage-doctors.php - Manage Doctors
// PROCESS FIRST (before any HTML output)
$page_title = "Manage Doctors";

// Initialize database connection
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $doctorId = $_GET['id'];
    
    switch ($_GET['action']) {
        case 'activate':
            $stmt = $db->prepare("UPDATE doctor SET status = 'active' WHERE DID = ?");
            if ($stmt->execute([$doctorId])) {
                $success = 'Doctor activated successfully';
            }
            break;
            
        case 'deactivate':
            $stmt = $db->prepare("UPDATE doctor SET status = 'inactive' WHERE DID = ?");
            if ($stmt->execute([$doctorId])) {
                $success = 'Doctor deactivated successfully';
            }
            break;
            
        case 'delete':
            // Soft delete - change status to 'deleted' instead of actual delete
            // This prevents foreign key constraint violations
            $stmt = $db->prepare("UPDATE doctor SET status = 'deleted' WHERE DID = ?");
            if ($stmt->execute([$doctorId])) {
                $success = 'Doctor deleted successfully (archived)';
            } else {
                $error = 'Failed to delete doctor';
            }
            break;
    }
}

// Now include admin-header.php
require_once '../includes/admin-header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-md me-2"></i>Manage Doctors</h1>
    <a href="add-doctor.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add New Doctor
    </a>
</div>

<!-- Search and Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="searchDoctor" placeholder="Search doctors by name or specialization...">
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="filterStatus">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100" onclick="resetFilters()">
                    <i class="fas fa-redo me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <?php
    $totalDoctors = $db->query("SELECT COUNT(*) as cnt FROM doctor WHERE status != 'deleted'")->fetch(PDO::FETCH_ASSOC)['cnt'];
    $activeDoctors = $db->query("SELECT COUNT(*) as cnt FROM doctor WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)['cnt'];
    $inactiveDoctors = $db->query("SELECT COUNT(*) as cnt FROM doctor WHERE status = 'inactive'")->fetch(PDO::FETCH_ASSOC)['cnt'];
    ?>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Total Doctors</p>
                        <h3 class="mb-0"><?php echo $totalDoctors; ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="fas fa-user-md fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Active</p>
                        <h3 class="mb-0 text-success"><?php echo $activeDoctors; ?></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Inactive</p>
                        <h3 class="mb-0 text-secondary"><?php echo $inactiveDoctors; ?></h3>
                    </div>
                    <div class="bg-secondary bg-opacity-10 p-3 rounded">
                        <i class="fas fa-clock fa-2x text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Doctor Cards Grid -->
<div class="row" id="doctorGrid">
    <?php
    // Get all doctors except deleted
    $doctors = $db->query("SELECT * FROM doctor WHERE status != 'deleted' ORDER BY name")->fetchAll();
    
    foreach ($doctors as $doctor):
    ?>
    <div class="col-md-6 col-lg-4 mb-4 doctor-card" 
         data-name="<?php echo strtolower($doctor['name']); ?>" 
         data-specialization="<?php echo strtolower($doctor['specialisation']); ?>"
         data-status="<?php echo $doctor['status']; ?>">
        <div class="card h-100 border-0 shadow-sm doctor-card-item">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-primary">ID: <?php echo $doctor['DID']; ?></span>
                    <?php if ($doctor['status'] == 'active'): ?>
                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
<div class="card-body text-center">
                <div class="doctor-avatar mb-3">
                    <?php 
                    $photoPath = isset($doctor['photo_path']) ? trim($doctor['photo_path']) : '';
                    $defaultPhoto = '../assets/images/doctor.jpg';
                    
                    // Check if the stored photo path exists, otherwise use default
                    if (!empty($photoPath) && file_exists('../' . $photoPath)): ?>
                        <img src="../<?php echo htmlspecialchars($photoPath); ?>" alt="<?php echo htmlspecialchars($doctor['name']); ?>" class="rounded-circle" width="80" height="80">
                    <?php elseif (file_exists($defaultPhoto)): ?>
                        <img src="<?php echo $defaultPhoto; ?>" alt="<?php echo htmlspecialchars($doctor['name']); ?>" class="rounded-circle" width="80" height="80">
                    <?php else: ?>
                        <div class="avatar-circle mx-auto">
                            <i class="fas fa-user-md fa-3x text-primary"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h5 class="card-title mb-1">Dr. <?php echo htmlspecialchars($doctor['name']); ?></h5>
                <p class="text-muted small mb-2">
                    <i class="fas fa-stethoscope me-1"></i>
                    <?php echo htmlspecialchars($doctor['specialisation']); ?>
                </p>
                <div class="doctor-info">
                    <p class="mb-1 small">
                        <i class="fas fa-briefcase me-2 text-muted"></i>
                        <?php echo $doctor['experience']; ?> years experience
                    </p>
                    <p class="mb-1 small">
                        <i class="fas fa-venus-mars me-2 text-muted"></i>
                        <?php echo $doctor['gender']; ?>
                    </p>
                    <p class="mb-0 small">
                        <i class="fas fa-phone me-2 text-muted"></i>
                        <?php echo $doctor['contact']; ?>
                    </p>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                <div class="d-flex justify-content-between">
                    <?php if ($doctor['status'] == 'active'): ?>
                        <a href="?action=deactivate&id=<?php echo $doctor['DID']; ?>" 
                           class="btn btn-sm btn-outline-warning" 
                           title="Deactivate"
                           onclick="return confirm('Are you sure you want to deactivate this doctor?');">
                            <i class="fas fa-pause me-1"></i>Deactivate
                        </a>
                    <?php else: ?>
                        <a href="?action=activate&id=<?php echo $doctor['DID']; ?>" 
                           class="btn btn-sm btn-outline-success" 
                           title="Activate">
                            <i class="fas fa-play me-1"></i>Activate
                        </a>
                    <?php endif; ?>
                    <a href="edit-doctor.php?id=<?php echo $doctor['DID']; ?>" 
                       class="btn btn-sm btn-outline-primary" 
                       title="Edit">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <a href="?action=delete&id=<?php echo $doctor['DID']; ?>" 
                       class="btn btn-sm btn-outline-danger" 
                       title="Delete"
                       onclick="return confirm('Are you sure you want to delete this doctor?');">
                        <i class="fas fa-trash me-1"></i>Delete
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($doctors)): ?>
<div class="text-center py-5">
    <i class="fas fa-user-md fa-4x text-muted mb-3"></i>
    <h5 class="text-muted">No doctors found</h5>
    <p class="text-muted">Add a new doctor to get started.</p>
    <a href="add-doctor.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add New Doctor
    </a>
</div>
<?php endif; ?>

<!-- Empty State for Filter -->
<div class="text-center py-5 d-none" id="noResults">
    <i class="fas fa-search fa-4x text-muted mb-3"></i>
    <h5 class="text-muted">No doctors match your search</h5>
    <p class="text-muted">Try adjusting your search or filter criteria.</p>
    <button class="btn btn-secondary" onclick="resetFilters()">
        <i class="fas fa-redo me-1"></i>Reset Filters
    </button>
</div>

<style>
.doctor-card-item {
    transition: transform 0.2s, box-shadow 0.2s;
}
.doctor-card-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}
.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
</style>

<script>
// Search and Filter Functionality
document.getElementById('searchDoctor').addEventListener('keyup', filterDoctors);
document.getElementById('filterStatus').addEventListener('change', filterDoctors);

function filterDoctors() {
    const searchTerm = document.getElementById('searchDoctor').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;
    const cards = document.querySelectorAll('.doctor-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const name = card.dataset.name;
        const specialization = card.dataset.specialization;
        const status = card.dataset.status;
        
        const matchesSearch = name.includes(searchTerm) || specialization.includes(searchTerm);
        const matchesStatus = statusFilter === '' || status === statusFilter;
        
        if (matchesSearch && matchesStatus) {
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
    document.getElementById('filterStatus').value = '';
    filterDoctors();
}
</script>
