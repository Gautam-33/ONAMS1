<?php
// admin/view-appointments.php - View All Appointments
$page_title = "View Appointments";
require_once '../config/database.php';
require_once '../includes/admin-header.php';

$database = new Database();
$db = $database->getConnection();

$success = '';

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $bookingId = $_POST['booking_id'];
    $newStatus = $_POST['action'];
    
    $stmt = $db->prepare("UPDATE booking SET Status = ? WHERE id = ?");
    if ($stmt->execute([$newStatus, $bookingId])) {
        $success = 'Appointment status updated!';
    }
}

// Build query
$query = "SELECT b.*, d.name as doctor_name, d.specialisation, p.name as patient_name 
          FROM booking b 
          JOIN doctor d ON b.DID = d.DID 
          LEFT JOIN patient p ON b.username = p.username";

$where = [];
$params = [];

if ($filter != 'all') {
    $where[] = "b.Status = ?";
    $params[] = $filter;
}

if (!empty($search)) {
    $where[] = "(b.Fname LIKE ? OR d.name LIKE ? OR b.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY b.Timestamp DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-calendar-check me-2"></i>View Appointments</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="filter" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="Pending" <?php echo $filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Confirmed" <?php echo $filter == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="Completed" <?php echo $filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?php echo $filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Search by patient name, doctor..." 
                       value="<?php echo $search; ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Appointments Table -->
<div class="card">
    <div class="card-header bg-secondary text-white">
        <span><i class="fas fa-calendar-list me-2"></i>Appointments (<?php echo count($appointments); ?>)</span>
    </div>
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                <p>No appointments found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td>#<?php echo $apt['id']; ?></td>
                                <td>
                                    <strong><?php echo $apt['Fname']; ?></strong><br>
                                    <small><?php echo $apt['username']; ?></small>
                                </td>
                                <td>
                                    <strong>Dr. <?php echo $apt['doctor_name']; ?></strong><br>
                                    <small><?php echo $apt['specialisation']; ?></small>
                                </td>
                                <td>
                                    <?php echo date('d M Y', strtotime($apt['DOV'])); ?><br>
                                    <small><?php echo date('h:i A', strtotime($apt['time_slot'])); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = 'bg-secondary';
                                    if ($apt['Status'] == 'Pending') $statusClass = 'bg-warning text-dark';
                                    elseif ($apt['Status'] == 'Confirmed') $statusClass = 'bg-success';
                                    elseif ($apt['Status'] == 'Cancelled') $statusClass = 'bg-danger';
                                    elseif ($apt['Status'] == 'Completed') $statusClass = 'bg-info text-dark';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $apt['Status']; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-success">Free Appointment</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($apt['Status'] == 'Pending'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="Confirmed">
                                                <input type="hidden" name="booking_id" value="<?php echo $apt['id']; ?>">
                                                <button type="submit" class="btn btn-success" title="Confirm">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="Cancelled">
                                                <input type="hidden" name="booking_id" value="<?php echo $apt['id']; ?>">
                                                <button type="submit" class="btn btn-danger" title="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php elseif ($apt['Status'] == 'Confirmed'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="Completed">
                                                <input type="hidden" name="booking_id" value="<?php echo $apt['id']; ?>">
                                                <button type="submit" class="btn btn-info" title="Mark Complete">
                                                    <i class="fas fa-check-double"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
