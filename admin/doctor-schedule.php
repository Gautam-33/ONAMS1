<?php
// admin/doctor-schedule.php
$page_title = "Doctor Schedules";
require_once '../config/database.php';
require_once '../includes/admin-header.php';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'add_schedule') {
        $doctorId = $_POST['doctor_id'];
        $day = $_POST['day'];
        $startTime = $_POST['start_time'];
        $endTime = $_POST['end_time'];
        $maxPatients = $_POST['max_patients'] ?? 20;
        
        // Check for overlap
        $query = "SELECT COUNT(*) FROM doctor_available 
                  WHERE DID = :did AND day = :day AND (
                    (:start BETWEEN starttime AND endtime) OR
                    (:end BETWEEN starttime AND endtime) OR
                    (starttime BETWEEN :start2 AND :end2)
                  )";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':did' => $doctorId,
            ':day' => $day,
            ':start' => $startTime,
            ':end' => $endTime,
            ':start2' => $startTime,
            ':end2' => $endTime
        ]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'Schedule conflicts with existing schedule for this doctor!';
        } else {
            $query = "INSERT INTO doctor_available (DID, day, starttime, endtime, max_patients) 
                      VALUES (:did, :day, :start, :end, :max)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':did' => $doctorId,
                ':day' => $day,
                ':start' => $startTime,
                ':end' => $endTime,
                ':max' => $maxPatients
            ]);
            $success = 'Schedule added successfully!';
        }
        
    } elseif ($action == 'delete_schedule') {
        $scheduleId = $_POST['schedule_id'];
        $stmt = $db->prepare("DELETE FROM doctor_available WHERE id = ?");
        $stmt->execute([$scheduleId]);
        $success = 'Schedule deleted!';
    }
}

// Get all doctors
$doctors = $db->query("SELECT * FROM doctor WHERE status = 'active' ORDER BY name")->fetchAll();

// Get all schedules
$schedules = $db->query("SELECT da.*, d.name as doctor_name, d.specialisation 
                          FROM doctor_available da
                          JOIN doctor d ON da.DID = d.DID
                          ORDER BY d.name, FIELD(da.day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), da.starttime")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-calendar-alt me-2"></i>Doctor Schedules</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Add Schedule Form -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Schedule</h6>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add_schedule">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Doctor</label>
                    <select class="form-select" name="doctor_id" required>
                        <option value="">Select Doctor</option>
                        <?php foreach ($doctors as $doc): ?>
                            <option value="<?php echo $doc['DID']; ?>">
                                Dr. <?php echo $doc['name']; ?> - <?php echo $doc['specialisation']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Day</label>
                    <select class="form-select" name="day" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Start Time</label>
                    <input type="time" class="form-control" name="start_time" value="09:00" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">End Time</label>
                    <input type="time" class="form-control" name="end_time" value="17:00" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Max Patients</label>
                    <input type="number" class="form-control" name="max_patients" value="20" min="1" max="50">
                </div>
                <div class="col-md-1 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Existing Schedules -->
<div class="card">
    <div class="card-header bg-secondary text-white">
        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Existing Schedules</h6>
    </div>
    <div class="card-body">
        <?php if (empty($schedules)): ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                <p>No schedules added yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Doctor</th>
                            <th>Specialisation</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Max Patients</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $sch): ?>
                            <tr>
                                <td><strong>Dr. <?php echo $sch['doctor_name']; ?></strong></td>
                                <td><?php echo $sch['specialisation']; ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $sch['day']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('h:i A', strtotime($sch['starttime'])); ?> - 
                                    <?php echo date('h:i A', strtotime($sch['endtime'])); ?>
                                </td>
                                <td><?php echo $sch['max_patients']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this schedule?');">
                                        <input type="hidden" name="action" value="delete_schedule">
                                        <input type="hidden" name="schedule_id" value="<?php echo $sch['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
