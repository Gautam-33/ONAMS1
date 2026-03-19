<?php
// admin/add-doctor.php - Add New Doctor
$page_title = "Add New Doctor";

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$error = '';
$photoPath = '';

// Handle file upload FIRST (before form validation)
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photoTmpPath = $_FILES['photo']['tmp_name'];
    $photoName = $_FILES['photo']['name'];
    $photoSize = $_FILES['photo']['size'];

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $photoExtension = strtolower(pathinfo($photoName, PATHINFO_EXTENSION));

    if (!in_array($photoExtension, $allowedExtensions)) {
        $error = 'Invalid photo format. Only JPG, JPEG, PNG, and GIF are allowed.';
    } elseif ($photoSize > 2 * 1024 * 1024) {
        $error = 'Photo size must not exceed 2MB.';
    } else {
        $photoNewName = 'doctor_' . time() . '_' . uniqid() . '.' . $photoExtension;
        $photoUploadDir = __DIR__ . '/../assets/images/';
        
        // Debug: Check if directory exists
        if (!is_dir($photoUploadDir)) {
            $error = 'Upload directory does not exist: ' . $photoUploadDir;
        } elseif (!is_writable($photoUploadDir)) {
            $error = 'Upload directory is not writable: ' . $photoUploadDir;
        } else {
            $photoUploadPath = $photoUploadDir . $photoNewName;
            
            // Use copy() instead of move_uploaded_file() for more compatibility
            if (copy($photoTmpPath, $photoUploadPath) || move_uploaded_file($photoTmpPath, $photoUploadPath)) {
                $photoPath = 'assets/images/' . $photoNewName;
            } else {
                $error = 'Failed to upload photo. Error: ' . error_get_last()['message'];
            }
        }
    }
} elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    // Show upload error if there was a problem
    $error = 'Photo upload error. Code: ' . $_FILES['photo']['error'];
}

// Nepali phone number validation function
if (!function_exists('validateNepaliPhone')) {
    function validateNepaliPhone($phone) {
        return preg_match('/^(97|98)\d{8}$/', $phone);
    }
}

// Email validation function
if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

// ALGORITHM: Doctor Registration with Validation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    $name = $_POST['name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $specialisation = $_POST['specialisation'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $qualification = $_POST['qualification'] ?? '';
    $address = $_POST['address'] ?? '';
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    } elseif (strlen($name) < 3) {
        $errors[] = 'Name must be at least 3 characters';
    }
    
    if (empty($gender)) {
        $errors[] = 'Gender is required';
    }
    
    if (empty($dob)) {
        $errors[] = 'Date of birth is required';
    }
    
    if (empty($experience)) {
        $errors[] = 'Experience is required';
    } elseif (!is_numeric($experience) || $experience < 0 || $experience > 50) {
        $errors[] = 'Experience must be a valid number (0-50 years)';
    }
    
    if (empty($specialisation)) {
        $errors[] = 'Specialisation is required';
    }
    
    if (empty($address)) {
        $errors[] = 'Address is required';
    }
    
    if (empty($contact)) {
        $errors[] = 'Contact number is required';
    } elseif (!validateNepaliPhone($contact)) {
        $errors[] = 'Invalid Nepali phone number. Must start with 97 or 98 and be 10 digits (e.g., 9841234567)';
    }
    
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Invalid email address format';
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM doctor WHERE name = ? AND specialisation = ? AND status != 'deleted'");
        $stmt->execute([$name, $specialisation]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Doctor with same name and specialisation already exists!';
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM doctor WHERE contact = ? AND status != 'deleted'");
            $stmt->execute([$contact]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'This contact number is already registered for another doctor!';
            } else {
                if (!empty($photoPath)) {
                    $stmt = $db->prepare("INSERT INTO doctor (name, gender, dob, experience, specialisation, contact, email, qualification, address, photo_path, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->execute([$name, $gender, $dob, $experience, $specialisation, $contact, $email, $qualification, $address, $photoPath]);
                } else {
                    $stmt = $db->prepare("INSERT INTO doctor (name, gender, dob, experience, specialisation, contact, email, qualification, address, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->execute([$name, $gender, $dob, $experience, $specialisation, $contact, $email, $qualification, $address]);
                }
                
                header("Location: manage-doctors.php?success=Doctor added successfully!");
                exit;
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

require_once '../includes/admin-header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-plus me-2"></i>Add New Doctor</h1>
    <a href="manage-doctors.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Manage Doctors
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="fas fa-user-md me-2"></i>Doctor Information</h6>
    </div>
    <div class="card-body">
        <form method="POST" id="doctorForm" onsubmit="return validateForm()" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-control" name="name" id="name" placeholder="Dr. Full Name" required 
                           value="<?php echo $_POST['name'] ?? ''; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Gender *</label>
                    <select class="form-select" name="gender" id="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo (($_POST['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (($_POST['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (($_POST['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Date of Birth *</label>
                    <input type="date" class="form-control" name="dob" id="dob" required 
                           max="<?php echo date('Y-m-d', strtotime('-25 years')); ?>">
                    <small class="text-muted">Must be at least 25 years old</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Experience (Years) *</label>
                    <input type="number" class="form-control" name="experience" id="experience" min="0" max="50" placeholder="Years" required
                           value="<?php echo $_POST['experience'] ?? ''; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Specialisation *</label>
                    <input type="text" class="form-control" name="specialisation" id="specialisation" placeholder="e.g., Cardiology" required
                           value="<?php echo $_POST['specialisation'] ?? ''; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Contact Number * (Nepal)</label>
                    <input type="tel" class="form-control" name="contact" id="contact" placeholder="9841234567" maxlength="10"
                           value="<?php echo $_POST['contact'] ?? ''; ?>">
                    <div class="form-text">Must start with 97 or 98 (10 digits)</div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Email (Optional)</label>
                    <input type="email" class="form-control" name="email" id="email" placeholder="doctor@email.com"
                           value="<?php echo $_POST['email'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Qualification</label>
                <input type="text" class="form-control" name="qualification" placeholder="e.g., MBBS, MD"
                       value="<?php echo $_POST['qualification'] ?? ''; ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Address *</label>
                <textarea class="form-control" name="address" rows="2" placeholder="Enter doctor address" required><?php echo $_POST['address'] ?? ''; ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="photo" class="form-label">Doctor Photo</label>
                <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                <small class="text-muted">Supported formats: JPG, JPEG, PNG, GIF. Max size: 2MB.</small>
            </div>
            
            <div class="d-grid gap-2 d-md-flex">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Add Doctor
                </button>
                <a href="manage-doctors.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function validateForm() {
    const name = document.getElementById('name').value.trim();
    const gender = document.getElementById('gender').value;
    const dob = document.getElementById('dob').value;
    const experience = document.getElementById('experience').value;
    const specialisation = document.getElementById('specialisation').value.trim();
    const contact = document.getElementById('contact').value.trim();
    const address = document.querySelector('textarea[name="address"]').value.trim();
    
    let errors = [];
    
    if (name.length < 3) {
        errors.push('Name must be at least 3 characters');
    }
    
    if (!gender) {
        errors.push('Gender is required');
    }
    
    if (!dob) {
        errors.push('Date of birth is required');
    }
    
    if (!experience || experience < 0 || experience > 50) {
        errors.push('Experience must be between 0-50 years');
    }
    
    if (!specialisation) {
        errors.push('Specialisation is required');
    }
    
    if (!contact || !/^(97|98)\d{8}$/.test(contact)) {
        errors.push('Invalid phone number. Must start with 97 or 98 and be 10 digits');
    }
    
    if (!address) {
        errors.push('Address is required');
    }
    
    if (errors.length > 0) {
        alert(errors.join('\n'));
        return false;
    }
    
    return true;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>