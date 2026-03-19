<?php
// patient/signup.php
require_once '../includes/session.php';
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $gender = sanitizeInput($_POST['gender']);
    $dob = sanitizeInput($_POST['dob']);
    $phone = sanitizeInput($_POST['phone']);
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = sanitizeInput($_POST['email']);
    
    // Validation
    $errors = [];
    
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($gender)) $errors[] = 'Gender is required';
    if (empty($dob)) $errors[] = 'Date of birth is required';
    if (empty($phone)) $errors[] = 'Phone is required';
    if (empty($username)) $errors[] = 'Username is required';
    if (empty($password)) $errors[] = 'Password is required';
    if (empty($email)) $errors[] = 'Email is required';
    
    if (!empty($phone) && !validateNepaliPhone($phone)) {
        $errors[] = 'Invalid Nepali phone number (must start with 98 or 97)';
    }
    
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Invalid email address';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if username or email exists
        $query = "SELECT id FROM patient WHERE username = :username OR email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = 'Username or email already exists';
        } else {
            // Store password as plain text
            
            // Insert patient
            $query = "INSERT INTO patient (name, gender, dob, phone, username, password, email) 
                     VALUES (:name, :gender, :dob, :phone, :username, :password, :email)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':dob', $dob);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':email', $email);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
                // Clear form
                $_POST = [];
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - City Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-4">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Patient Registration</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" needs-validation>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" name="name" 
                                               required value="<?php echo $_POST['name'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                        <select class="form-select" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo ($_POST['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($_POST['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($_POST['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="date" class="form-control" name="dob" 
                                               required max="<?php echo date('Y-m-d'); ?>"
                                               value="<?php echo $_POST['dob'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number (Nepal) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="tel" class="form-control" name="phone" 
                                               pattern="^(98|97)[0-9]{8}$" 
                                               title="Nepali phone number starting with 98 or 97" 
                                               required value="<?php echo $_POST['phone'] ?? ''; ?>">
                                    </div>
                                    <small class="text-muted">Format: 98XXXXXXXX or 97XXXXXXXX</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" 
                                           required value="<?php echo $_POST['email'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    <input type="text" class="form-control" name="username" 
                                           required minlength="4" value="<?php echo $_POST['username'] ?? ''; ?>">
                                </div>
                                <small class="text-muted">Minimum 4 characters</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="password" class="form-control" name="password" required minlength="6">
                                    </div>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                                </label>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Register
                                </button>
                                <a href="login.php" class="btn btn-link">Already have an account? Login here</a>
                                <a href="../index.php" class="btn btn-link">Back to Home</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>By registering, you agree to:</p>
                    <ul>
                        <li>Provide accurate and complete information</li>
                        <li>Keep your login credentials secure</li>
                        <li>Cancel appointments at least 24 hours in advance</li>
                        <li>Follow clinic rules and regulations</li>
                        <li>Respect doctor's time and schedule</li>
                    </ul>
                    <p>We respect your privacy and will protect your personal information.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>