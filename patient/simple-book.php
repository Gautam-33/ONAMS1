<?php
// patient/simple-book.php - Book Appointment (Free)
session_start();
require_once '../config/database.php';
require_once '../algorithm.php';

if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$message = '';

// Process booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'book') {
    $doctorId = $_POST['doctor_id'];
    $date = $_POST['date'];
    $time = $_POST['time_slot'];
    $problem = $_POST['patient_problem'];
    
    // Get patient info
    $stmt = $db->prepare("SELECT * FROM patient WHERE id = ?");
    $stmt->execute([$_SESSION['patient_id']]);
    $patient = $stmt->fetch();
    
    // Get doctor info
    $stmt = $db->prepare("SELECT * FROM doctor WHERE DID = ?");
    $stmt->execute([$doctorId]);
    $doctor = $stmt->fetch();
    
    // Enhanced conflict detection for appointment booking
    $stmt = $db->prepare("SELECT COUNT(*) FROM booking WHERE DID = ? AND DOV = ? AND time_slot = ? AND Status IN ('Pending', 'Confirmed')");
    $stmt->execute([$doctorId, $date, $time]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $message = '<div class="alert alert-danger">This time slot is already booked!</div>';
    } else {
        // Proceed with booking
        $stmt = $db->prepare("INSERT INTO booking (username, Fname, gender, DID, DOV, time_slot, Status) VALUES (?, ?, ?, ?, ?, ?, 'Confirmed')");
        $stmt->execute([$_SESSION['patient_username'], $patient['name'], $patient['gender'], $doctorId, $date, $time]);

        $bookingId = $db->lastInsertId();

        header('Location: my-appointments.php?success=Appointment booked successfully!');
        exit;
    }
}

// Get patient problem if submitted
$patientProblem = isset($_POST['patient_problem']) ? $_POST['patient_problem'] : '';

// Use the cosine similarity algorithm for recommendations
$recommender = new CosineSimilarityRecommendation();
$doctorRecommendations = !empty($patientProblem) ? $recommender->getRecommendations($patientProblem) : [];

$showRecommendations = !empty($doctorRecommendations);
$primarySpec = !empty($doctorRecommendations) ? $doctorRecommendations[0]['matched_specialization'] : '';

// AJAX: Check available slots
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_slots'])) {
    $doctorId = $_POST['doctor_id'];
    $date = $_POST['date'];

    $stmt = $db->prepare("SELECT time_slot FROM booking WHERE DID = ? AND DOV = ? AND Status IN ('Pending', 'Confirmed')");
    $stmt->execute([$doctorId, $date]);
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($bookedSlots);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - City Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    body {
        background-color: #f4f6f9;
        font-family: 'Arial', sans-serif;
    }
    .navbar {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .card {
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .doctor-item {
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
    }
    .doctor-item:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .doctor-item.selected {
        border-color: #198754 !important;
        background-color: #d1e7dd !important;
    }
    .btn {
        border-radius: 20px;
    }
    .slot-btn { margin: 2px; }
    .match-high { color: #198754; font-weight: bold; }
    .match-medium { color: #ffc107; font-weight: bold; }
    .match-low { color: #6c757d; }
    .similarity-badge {
        font-size: 0.75rem;
        padding: 3px 8px;
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-hospital me-2"></i>City Hospital</a>
            <div class="d-flex">
                <span class="text-white me-3"><?php echo $_SESSION['patient_name']; ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <h2><i class="fas fa-calendar-plus me-2"></i>Book Appointment</h2>
        <?php echo $message; ?>
        
        <!-- Step 1: Enter Problem -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Step 1: Describe Your Problem</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="problemForm">
                    <div class="mb-3">
                        <label class="form-label">What's your health issue? *</label>
                        <textarea name="patient_problem" id="patientProblem" class="form-control" rows="3" 
                            placeholder="Describe your symptoms or condition (e.g., I have chest pain and shortness of breath, or I have a skin rash on my face...)" required><?php echo isset($_POST['patient_problem']) ? htmlspecialchars($_POST['patient_problem']) : ''; ?></textarea>
                        <div class="form-text">
                            Keywords: heart, chest, skin, rash, brain, headache, bone, joint, fracture, child, baby, mental, depression, eye, vision, ear, nose, throat, kidney, urinary, diabetes, thyroid, fever, cough, stomach, digestion
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Find Recommended Doctors
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Step 2: Show Recommended Doctors (only if problem submitted) -->
        <?php if ($showRecommendations): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>Step 2: Recommended Doctors</h5>
                <?php if (!empty($matchedSpecialization)): ?>
                    <small class="text-white">Based on your problem: <strong><?php echo htmlspecialchars($matchedSpecialization); ?></strong></small>
                <?php endif; ?>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($doctorRecommendations)): ?>
                    <div class="alert alert-warning">No doctors found.</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($doctorRecommendations as $index => $rec): 
                            $doc = $rec['doctor'];
                            $similarity = $rec['similarity'];
                            $matchClass = $similarity >= 0.5 ? 'match-high' : ($similarity > 0 ? 'match-medium' : 'match-low');
                        ?>
                            <div class="col-md-6 mb-2">
                                <div class="doctor-item p-3 border rounded" 
                                     onclick="selectDoctor(<?php echo $doc['DID']; ?>, '<?php echo addslashes($doc['name']); ?>', '<?php echo addslashes($doc['specialisation']); ?>')" 
                                     id="doctor-<?php echo $doc['DID']; ?>">
                                    <div class="d-flex">
                                        <?php 
                                        $photoPath = isset($doc['photo_path']) ? trim($doc['photo_path']) : '';
                                        $defaultPhoto = 'assets/images/doctor.jpg';
                                        
                                        if (!empty($photoPath) && file_exists($photoPath)): ?>
                                            <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Dr. <?php echo htmlspecialchars($doc['name']); ?>" class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;">
                                        <?php elseif (file_exists($defaultPhoto)): ?>
                                            <img src="<?php echo $defaultPhoto; ?>" alt="Dr. <?php echo htmlspecialchars($doc['name']); ?>" class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                <i class="fas fa-user-md text-primary fs-4"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <?php if ($index === 0 && $similarity > 0): ?>
                                                <span class="badge bg-success similarity-badge mb-1"><i class="fas fa-star"></i> Best Match</span><br>
                                            <?php endif; ?>
                                            <strong>Dr. <?php echo $doc['name']; ?></strong>
                                            <br><small class="text-primary"><?php echo $doc['specialisation']; ?></small>
                                            <br><small class="text-muted">Experience: <?php echo $doc['experience']; ?> years</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="<?php echo $matchClass; ?>">
                                                <?php if ($similarity > 0): ?>
                                                    <i class="fas fa-percentage"></i> <?php echo round($similarity * 100); ?>% Match
                                                <?php else: ?>
                                                    <span class="text-muted">General</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Step 3: Booking Form (appears after doctor selection) -->
        <div class="card" id="bookingCard" style="display: <?php echo isset($_POST['doctor_selected']) ? 'block' : 'none'; ?>;">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Step 3: Select Date & Time</h5>
            </div>
            <div class="card-body">
                <h5 id="doctorName">Dr. ---</h5>
                <p id="doctorSpec" class="text-primary">---</p>
                <hr>
                
                <form method="POST" id="bookingForm">
                    <input type="hidden" name="action" value="book">
                    <input type="hidden" name="doctor_id" id="doctorId">
                    <input type="hidden" name="patient_problem" value="<?php echo htmlspecialchars($patientProblem); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Your Problem:</label>
                        <textarea class="form-control" rows="2" readonly><?php echo htmlspecialchars($patientProblem); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Date (Next 14 Days)</label>
                        <input type="date" name="date" id="datePicker" class="form-control" required
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               max="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
                    </div>
                    
                    <div class="mb-3" id="slotsDiv" style="display:none;">
                        <label class="form-label">Select Time Slot</label>
                        <div id="slotsContainer"></div>
                    </div>
                    
                    <div class="mb-3" id="summaryDiv" style="display:none;">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>Booking Summary:</h6>
                                <p><strong>Doctor:</strong> <span id="summaryDoctor"></span></p>
                                <p><strong>Date:</strong> <span id="summaryDate"></span></p>
                                <p><strong>Time:</strong> <span id="summaryTime"></span></p>
                                <hr>
                                <p class="h5"><strong>Total: Rs. 200 (Pay on Visit)</strong></p>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100" id="payBtn" style="display:none;">
                        <i class="fas fa-check me-2"></i>Book Appointment (Rs. 200 on Visit)
                    </button>
                </form>
            </div>
        </div>
        
        <!-- No doctor selected message -->
        <?php if ($showRecommendations): ?>
        <div class="card" id="noSelection">
            <div class="card-body text-center py-4">
                <i class="fas fa-hand-pointer-up fa-3x text-muted mb-3"></i>
                <p class="text-muted">Click on a recommended doctor above to proceed with booking</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    let selectedDoctor = 0;
    let selectedTime = null;
    
    function selectDoctor(id, name, spec) {
        selectedDoctor = id;
        
        // Update UI - highlight selected doctor
        document.querySelectorAll('.doctor-item').forEach(d => d.classList.remove('selected'));
        document.getElementById('doctor-' + id).classList.add('selected');
        
        // Show booking card
        document.getElementById('doctorName').textContent = 'Dr. ' + name;
        document.getElementById('doctorSpec').textContent = spec;
        document.getElementById('doctorId').value = id;
        document.getElementById('noSelection').style.display = 'none';
        document.getElementById('bookingCard').style.display = 'block';
        
        // Reset form
        document.getElementById('datePicker').value = '';
        document.getElementById('slotsDiv').style.display = 'none';
        document.getElementById('summaryDiv').style.display = 'none';
        document.getElementById('payBtn').style.display = 'none';
        document.getElementById('summaryDoctor').textContent = 'Dr. ' + name;
        
        // Scroll to booking form
        document.getElementById('bookingCard').scrollIntoView({ behavior: 'smooth' });
    }
    
    document.getElementById('datePicker').addEventListener('change', function() {
        const date = this.value;
        if (!date || !selectedDoctor) return;
        
        document.getElementById('summaryDate').textContent = date;
        document.getElementById('summaryTime').textContent = 'Select a time slot';
        
        const container = document.getElementById('slotsContainer');
        const slotsDiv = document.getElementById('slotsDiv');
        const summaryDiv = document.getElementById('summaryDiv');
        const payBtn = document.getElementById('payBtn');
        
        container.innerHTML = '<div class="text-info"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        slotsDiv.style.display = 'block';
        summaryDiv.style.display = 'none';
        payBtn.style.display = 'none';
        
        // Fetch booked slots via AJAX
        fetch('simple-book.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                check_slots: true,
                doctor_id: selectedDoctor,
                date: date
            })
        })
        .then(response => response.json())
        .then(bookedSlots => {
            const times = [];
            for (let h = 9; h < 17; h++) {
                for (let m = 0; m < 60; m += 20) {
                    const hour = h < 10 ? '0' + h : h;
                    const min = m < 10 ? '0' + m : m;
                    const time24 = hour + ':' + min + ':00';
                    const hour12 = h > 12 ? h - 12 : h;
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    const display = hour12 + ':' + (min < 10 ? '0' + min : min) + ' ' + ampm;
                    times.push({ time: time24, display: display });
                }
            }

            let html = '';
            times.forEach(t => {
                const isBooked = bookedSlots.includes(t.time);
                html += `<button type="button" class="btn ${isBooked ? 'btn-secondary' : 'btn-outline-success'} slot-btn" ${isBooked ? 'disabled' : ''} onclick="selectSlot('${t.time}', '${t.display}', this)">${t.display}</button>`;
            });
            container.innerHTML = html;
        });
    });
    
    function selectSlot(time, display, btn) {
        selectedTime = time;
        document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('bg-success', 'text-white'));
        btn.classList.add('bg-success', 'text-white');
        
        document.getElementById('summaryTime').textContent = display;
        document.getElementById('summaryDiv').style.display = 'block';
        document.getElementById('payBtn').style.display = 'block';
        
        let input = document.querySelector('input[name="time_slot"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'time_slot';
            document.getElementById('bookingForm').appendChild(input);
        }
        input.value = time;
    }
    </script>
</body>
</html>
