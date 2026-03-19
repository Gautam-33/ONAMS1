<?php
// admin/edit-doctor.php - Simplified for single hospital
require_once '../includes/session.php';
require_once '../config/database.php';

redirectIfNotLoggedIn('admin');

$database = new Database();
$db = $database->getConnection();

$doctorId = $_GET['id'] ?? 0;

if (empty($doctorId)) {
    header('Location: manage-doctors.php');
    exit();
}

$error = '';
$success = '';

// Get doctor details
$query = "SELECT * FROM doctor WHERE DID = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $doctorId);
$stmt->execute();
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    header('Location: manage-doctors.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $gender = sanitizeInput($_POST['gender']);
    $dob = sanitizeInput($_POST['dob']);
    $experience = sanitizeInput($_POST['experience']);
    $specialisation = sanitizeInput($_POST['specialisation']);
    $contact = sanitizeInput($_POST['contact']);
    $address = sanitizeInput($_POST['address']);
    $email = sanitizeInput($_POST['email'] ?? '');
    $qualification = sanitizeInput($_POST['qualification'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $photo = $_POST['photo'] ?? '';

    // Handle file upload for doctor photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoTmpPath = $_FILES['photo']['tmp_name'];
        $photoName = $_FILES['photo']['name'];
        $photoSize = $_FILES['photo']['size'];

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $photoExtension = strtolower(pathinfo($photoName, PATHINFO_EXTENSION));

        if (!in_array($photoExtension, $allowedExtensions)) {
            $errors[] = 'Invalid photo format. Only JPG, JPEG, PNG, and GIF are allowed.';
        } elseif ($photoSize > 2 * 1024 * 1024) {
            $errors[] = 'Photo size must not exceed 2MB.';
        } else {
            $photoNewName = 'doctor_' . time() . '_' . uniqid() . '.' . $photoExtension;
            $photoUploadDir = __DIR__ . '/../assets/images/';
            
            if (!is_dir($photoUploadDir)) {
                $errors[] = 'Upload directory does not exist';
            } elseif (!is_writable($photoUploadDir)) {
                $errors[] = 'Upload directory is not writable';
            } else {
                $photoUploadPath = $photoUploadDir . $photoNewName;
                
                if (copy($photoTmpPath, $photoUploadPath) || move_uploaded_file($photoTmpPath, $photoUploadPath)) {
                    $photoPath = 'assets/images/' . $photoNewName;
                    // Update photo path in database
                    $stmt = $db->prepare("UPDATE doctor SET photo_path = :photo_path WHERE DID = :id");
                    $stmt->bindParam(':photo_path', $photoPath);
                    $stmt->bindParam(':id', $doctorId);
                    $stmt->execute();
                } else {
                    $errors[] = 'Failed to upload photo. Please try again.';
                }
            }
        }
    }

    // Validation
    $errors = [];
    
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($gender)) $errors[] = 'Gender is required';
    if (empty($dob)) $errors[] = 'Date of birth is required';
    if (empty($experience)) $errors[] = 'Experience is required';
    if (empty($specialisation)) $errors[] = 'Specialization is required';
    if (empty($contact)) $errors[] = 'Contact is required';
    if (empty($address)) $errors[] = 'Address is required';
    
    if (!empty($contact) && !validateNepaliPhone($contact)) {
        $errors[] = 'Invalid Nepali phone number (must start with 98 or 97)';
    }
    
    // Calculate age from DOB
    $birthDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    
    if ($age < 25) {
        $errors[] = 'Doctor must be at least 25 years old';
    }
    
    if (empty($errors)) {
        // Update doctor
        $query = "UPDATE doctor SET name = :name, gender = :gender, dob = :dob, 
                 experience = :experience, specialisation = :specialisation, contact = :contact, 
                 address = :address, email = :email, 
                 qualification = :qualification, status = :status 
                 WHERE DID = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':experience', $experience);
        $stmt->bindParam(':specialisation', $specialisation);
        $stmt->bindParam(':contact', $contact);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':qualification', $qualification);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $doctorId);
        
        if ($stmt->execute()) {
            $success = 'Doctor updated successfully!';
            // Refresh doctor data
            $query = "SELECT * FROM doctor WHERE DID = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $doctorId);
            $stmt->execute();
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to update doctor. Please try again.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Get specializations for suggestions
$query = "SELECT DISTINCT specialisation FROM doctor ORDER BY specialisation";
$specializations = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);

$predefinedSpecializations = [
    'Cardiology', 'Dermatology', 'Neurology', 'Orthopedics', 'Pediatrics',
    'Psychiatry', 'General Practice', 'Internal Medicine', 'Surgery',
    'Obstetrics & Gynecology', 'ENT', 'Ophthalmology', 'Urology',
    'Gastroenterology', 'Pulmonology', 'Oncology', 'Rheumatology',
    'Nephrology', 'Endocrinology', 'Radiology', 'Anesthesiology',
    'Pathology', 'Dentistry', 'Homeopathy', 'Ayurveda', 'Physical Therapy'
];

$allSpecializations = array_unique(array_merge($specializations, $predefinedSpecializations));
sort($allSpecializations);

$page_title = "Edit Doctor";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - City Hospital Kathmandu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin-navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Doctor</h5>
                        <span class="badge bg-light text-dark">ID: D<?php echo str_pad($doctor['DID'], 4, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="name" 
                                           required value="<?php echo htmlspecialchars($doctor['name']); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender *</label>
                                    <select class="form-select" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo $doctor['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $doctor['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo $doctor['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth *</label>
                                    <input type="date" class="form-control" name="dob" 
                                           required max="<?php echo date('Y-m-d', strtotime('-25 years')); ?>"
                                           value="<?php echo $doctor['dob']; ?>">
                                    <small class="text-muted">Must be at least 25 years old</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Years of Experience *</label>
                                    <select class="form-select" name="experience" required>
                                        <option value="">Select Experience</option>
                                        <?php for ($i = 1; $i <= 50; $i++): ?>
                                            <option value="<?php echo $i; ?>" 
                                                <?php echo $doctor['experience'] == $i ? 'selected' : ''; ?>>
                                                <?php echo $i; ?> year<?php echo $i > 1 ? 's' : ''; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Specialization *</label>
                                    <input type="text" class="form-control" name="specialisation" 
                                           list="specializations" required
                                           value="<?php echo htmlspecialchars($doctor['specialisation']); ?>">
                                    <datalist id="specializations">
                                        <?php foreach ($allSpecializations as $spec): ?>
                                            <option value="<?php echo $spec; ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Qualification</label>
                                    <input type="text" class="form-control" name="qualification" 
                                           value="<?php echo htmlspecialchars($doctor['qualification'] ?? ''); ?>"
                                           placeholder="e.g., MD, MS, MBBS">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number (Nepal) *</label>
                                    <input type="tel" class="form-control" name="contact" 
                                           pattern="^(98|97)[0-9]{8}$" 
                                           title="Nepali phone number starting with 98 or 97" 
                                           required value="<?php echo htmlspecialchars($doctor['contact']); ?>">
                                    <small class="text-muted">Format: 98XXXXXXXX or 97XXXXXXXX</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($doctor['email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Address *</label>
                                <textarea class="form-control" name="address" rows="2" required><?php echo htmlspecialchars($doctor['address']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="photo" class="form-label">Doctor Photo</label>
                                <?php 
                                $photoPath = isset($doctor['photo_path']) ? trim($doctor['photo_path']) : '';
                                $defaultPhoto = 'assets/images/doctor.jpg';
                                
                                if (!empty($photoPath) && file_exists($photoPath)): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Doctor Photo" class="rounded" width="100">
                                    </div>
                                <?php elseif (file_exists($defaultPhoto)): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo $defaultPhoto; ?>" alt="Doctor Photo" class="rounded" width="100">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                <small class="text-muted">Supported formats: JPG, JPEG, PNG, GIF. Max size: 2MB.</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" <?php echo $doctor['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $doctor['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                                <a href="manage-doctors.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Doctors
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
