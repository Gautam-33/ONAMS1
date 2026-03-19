<?php
// includes/admin-header.php
// Start session first
session_start();

// Check if database is already included
if (!class_exists('Database')) {
    require_once '../config/database.php';
}

// Check if admin is logged in
if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn() {
        return isset($_SESSION['admin_username']) && $_SESSION['admin_role'] == 'admin';
    }
}

// Redirect if not logged in
if (!function_exists('redirectIfNotLoggedIn')) {
    function redirectIfNotLoggedIn() {
        if (!isAdminLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }
}

// Check admin login
if (!isAdminLoggedIn()) {
    redirectIfNotLoggedIn();
}

// Admin header configuration
$admin_hospital_name = "City Hospital Kathmandu";

// Get page title
$page_title = isset($page_title) ? $page_title : 'Admin Panel';

// Initialize database connection only if needed
$db = null;
function getAdminDbConnection() {
    global $db;
    if ($db === null && class_exists('Database')) {
        $database = new Database();
        $db = $database->getConnection();
    }
    return $db;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo $admin_hospital_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-sidebar {
            min-height: calc(100vh - 56px);
            background-color: #343a40;
            color: white;
        }
        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-left: 3px solid transparent;
        }
        .admin-sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .admin-sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #4e73df;
        }
        .admin-sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        .admin-navbar {
            background-color: #4e73df;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1rem;
        }
        .breadcrumb-item a {
            color: #4e73df;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-hospital me-2"></i><?php echo $admin_hospital_name; ?> Admin
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-dark admin-sidebar collapse" id="sidebarMenu">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                               href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item mt-3">
                            <small class="text-uppercase text-muted px-3">Doctors</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add-doctor.php' ? 'active' : ''; ?>" 
                               href="add-doctor.php">
                                <i class="fas fa-user-plus me-2"></i>
                                Add Doctor
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-doctors.php' ? 'active' : ''; ?>" 
                               href="manage-doctors.php">
                                <i class="fas fa-user-md me-2"></i>
                                Manage Doctors
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'doctor-schedule.php' ? 'active' : ''; ?>" 
                               href="doctor-schedule.php">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Doctor Schedules
                            </a>
                        </li>
                        
                        <li class="nav-item mt-3">
                            <small class="text-uppercase text-muted px-3">Appointments</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view-appointments.php' ? 'active' : ''; ?>" 
                               href="view-appointments.php">
                                <i class="fas fa-calendar-check me-2"></i>
                                View Appointments
                            </a>
                        </li>
                        
                        <li class="nav-item mt-3">
                            <small class="text-uppercase text-muted px-3">System</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../patient/index.php" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>
                                View Site
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Statistics Sidebar -->
                    <div class="mt-5 px-3">
                        <h6 class="text-uppercase text-muted mb-3">Quick Stats</h6>
                        <?php
                        if (isAdminLoggedIn()) {
                            $db = getAdminDbConnection();
                            
                            if ($db) {
                                // Get quick stats (simplified for single hospital)
                                $statsQuery = "SELECT 
                                    (SELECT COUNT(*) FROM doctor WHERE status = 'active') as doctors,
                                    (SELECT COUNT(*) FROM booking WHERE DATE(DOV) = CURDATE()) as today_appointments,
                                    (SELECT COUNT(*) FROM booking WHERE Status = 'Pending') as pending_appointments,
                                    (SELECT COUNT(*) FROM patient) as patients";
                                
                                $statsStmt = $db->query($statsQuery);
                                $quickStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="bg-primary text-white rounded p-2 text-center">
                                            <small class="d-block">Doctors</small>
                                            <strong><?php echo $quickStats['doctors']; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-success text-white rounded p-2 text-center">
                                            <small class="d-block">Patients</small>
                                            <strong><?php echo $quickStats['patients']; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-info text-white rounded p-2 text-center">
                                            <small class="d-block">Today</small>
                                            <strong><?php echo $quickStats['today_appointments']; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-warning text-white rounded p-2 text-center">
                                            <small class="d-block">Pending</small>
                                            <strong><?php echo $quickStats['pending_appointments']; ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $page_title; ?></li>
                    </ol>
                </nav>
                
                <!-- Toggle Sidebar Button (for mobile) -->
                <button class="btn btn-primary d-md-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                    <i class="fas fa-bars"></i> Toggle Menu
                </button>
