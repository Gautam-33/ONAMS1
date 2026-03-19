<?php
// includes/admin-navbar.php
// Just the navbar component - no session_start() or full HTML
?>

<style>
    .admin-navbar {
        background-color: #4e73df;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
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
</style>

<!-- Admin Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-hospital me-2"></i>City Hospital Admin
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
                        <li><a class="dropdown-item" href="manage-doctors.php">
                            <i class="fas fa-user-md me-2"></i>Manage Doctors
                        </a></li>
                        <li><a class="dropdown-item" href="view-appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>View Appointments
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
