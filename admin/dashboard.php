<?php
// admin/dashboard.php
$page_title = "Admin Dashboard";
require_once '../config/database.php';
require_once '../includes/admin-header.php';

$database = new Database();
$db = $database->getConnection();

// Get counts
$totalAppointments = $db->query("SELECT COUNT(*) as cnt FROM booking")->fetch(PDO::FETCH_ASSOC)['cnt'];
$totalPatients = $db->query("SELECT COUNT(*) as cnt FROM patient")->fetch(PDO::FETCH_ASSOC)['cnt'];
$activeDoctors = $db->query("SELECT COUNT(*) as cnt FROM doctor WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)['cnt'];
$todayAppointments = $db->query("SELECT COUNT(*) as cnt FROM booking WHERE DATE(DOV) = CURDATE()")->fetch(PDO::FETCH_ASSOC)['cnt'];
$pendingAppointments = $db->query("SELECT COUNT(*) as cnt FROM booking WHERE Status = 'Pending'")->fetch(PDO::FETCH_ASSOC)['cnt'];

// Get registered patients
$patients = $db->query("SELECT * FROM patient ORDER BY id DESC LIMIT 10")->fetchAll();

// Get recent appointments
$appointments = $db->query("SELECT b.*, d.name as doctor_name 
                            FROM booking b 
                            JOIN doctor d ON b.DID = d.DID 
                            ORDER BY b.Timestamp DESC LIMIT 10")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Doctors</h5>
                        <p class="card-text h3 mb-0"><?php echo $activeDoctors; ?></p>
                    </div>
                    <i class="fas fa-user-md fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Patients</h5>
                        <p class="card-text h3 mb-0"><?php echo $totalPatients; ?></p>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Today</h5>
                        <p class="card-text h3 mb-0"><?php echo $todayAppointments; ?></p>
                    </div>
                    <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Pending</h5>
                        <p class="card-text h3 mb-0"><?php echo $pendingAppointments; ?></p>
                    </div>
                    <i class="fas fa-clock fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Appointments -->
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <span><i class="fas fa-calendar-alt me-2"></i>Recent Appointments</span>
    </div>
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <p class="text-center text-muted">No appointments yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td><?php echo $apt['Fname']; ?></td>
                                <td>Dr. <?php echo $apt['doctor_name']; ?></td>
                                <td><?php echo date('d M Y', strtotime($apt['DOV'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($apt['time_slot'])); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo $apt['Status'] == 'Confirmed' ? 'bg-success' : 
                                            ($apt['Status'] == 'Pending' ? 'bg-warning' : 
                                            ($apt['Status'] == 'Cancelled' ? 'bg-danger' : 'bg-info')); 
                                    ?>">
                                        <?php echo $apt['Status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Patients -->
<div class="card">
    <div class="card-header bg-secondary text-white">
        <span><i class="fas fa-user-plus me-2"></i>Recent Patients</span>
    </div>
    <div class="card-body">
        <?php if (empty($patients)): ?>
            <p class="text-center text-muted">No patients registered yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Contact</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $pat): ?>
                            <tr>
                                <td><?php echo $pat['id']; ?></td>
                                <td><?php echo $pat['name']; ?></td>
                                <td><?php echo $pat['gender']; ?></td>
                                <td><?php echo $pat['phone'] ?? 'N/A'; ?></td>
                                <td><?php echo date('d M Y', strtotime($pat['created_at'] ?? 'now')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
