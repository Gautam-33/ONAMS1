<?php
// patient/update-profile.php
require_once '../includes/session.php';
require_once '../config/database.php';

redirectIfNotLoggedIn('patient');

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Get current patient info
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

// Set default values if patient not found
if (!$patient) {
    $patient = [
        'name' => 'Guest User',
        'username' => 'guest',
        'gender' => 'N/A',
        'dob' => 'N/A',
        'phone' => '',
        'email' => ''
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    } elseif (!validateNepaliPhone($phone)) {
        $errors[] = 'Invalid Nepali phone number (must start with 98 or 97)';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Invalid email address';
    }
    
    // Check if email already exists (excluding current patient)
    if (!empty($email)) {
        $query = "SELECT id FROM patient WHERE email = :email AND id != :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $_SESSION['patient_id']);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Email already exists';
        }
    }
    
    if (empty($errors)) {
        $query = "UPDATE patient SET phone = :phone, email = :email WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $_SESSION['patient_id']);
        
        if ($stmt->execute()) {
            $success = 'Profile updated successfully!';
            // Refresh patient data
            $query = "SELECT * FROM patient WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_SESSION['patient_id']);
            $stmt->execute();
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$page_title = "Update Profile";
?>
<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Update Profile</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($patient['name'] ?? ''); ?>" readonly>
                            <small class="text-muted">Name cannot be changed</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($patient['username'] ?? ''); ?>" readonly>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($patient['gender'] ?? ''); ?>" readonly>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($patient['dob'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone Number (Nepal) *</label>
                        <input type="tel" class="form-control" name="phone" 
                               pattern="^(98|97)[0-9]{8}$" 
                               title="Nepali phone number starting with 98 or 97" 
                               required value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>">
                        <small class="text-muted">Format: 98XXXXXXXX or 97XXXXXXXX</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
