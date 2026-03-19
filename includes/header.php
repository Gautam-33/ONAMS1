<?php
// includes/header.php
$patient_footer_text = isset($patient_footer_text) ? $patient_footer_text : 'City Hospital Kathmandu';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : $patient_footer_text . ' - Online Appointment System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-hospital me-2"></i><?php echo $patient_footer_text; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <?php if (isset($_SESSION['patient_username'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="book-appointment.php">Book Appointment</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my-appointments.php">My Appointments</a>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link">Welcome, <?php echo $_SESSION['patient_name']; ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Patient Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="signup.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4"></div>