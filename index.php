<?php
session_start();
require_once 'config/database.php';

// Get all doctors (except deleted)
$database = new Database();
$db = $database->getConnection();
$doctors = $db->query("SELECT * FROM doctor WHERE status != 'deleted' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$specializations = $db->query("SELECT DISTINCT specialisation FROM doctor WHERE status != 'deleted' ORDER BY specialisation")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City Hospital Kathmandu - Online Appointment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .doctor-card { transition: transform 0.3s, box-shadow 0.3s; }
        .doctor-card:hover { transform: translateY(-8px); box-shadow: 0 12px 30px rgba(0,0,0,0.15) !important; }
        .avatar-circle { width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; margin: 0 auto; }
        .avatar-initials { font-size: 28px; font-weight: bold; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-lg">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-hospital me-2"></i>City Hospital
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fas fa-home me-1"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#doctors"><i class="fas fa-user-md me-1"></i> Our Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features"><i class="fas fa-star me-1"></i> Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="patient/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-light text-primary px-3 ms-2" href="patient/signup.php"><i class="fas fa-user-plus me-1"></i> Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <span class="badge bg-white text-primary mb-3 px-3 py-2"><i class="fas fa-calendar-check me-1"></i> Online Appointment System</span>
                    <h1 class="display-3 fw-bold mb-4">Book Your <span class="text-warning">Healthcare</span> Today</h1>
                    <p class="lead mb-4">Easy, fast, and reliable appointment booking system. Connect with the best doctors in Nepal from the comfort of your home.</p>
                    <div class="d-flex gap-3">
                        <a href="patient/signup.php" class="btn btn-light btn-lg px-4 shadow-sm">
                            <i class="fas fa-user-plus me-2"></i>Get Started
                        </a>
                        <a href="#doctors" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-search me-2"></i>Find Doctors
                        </a>
                    </div>
                    <div class="mt-4 d-flex gap-4">
                        <div><h4 class="fw-bold text-white mb-0">50+</h4><small class="text-white-50">Expert Doctors</small></div>
                        <div><h4 class="fw-bold text-white mb-0">10K+</h4><small class="text-white-50">Happy Patients</small></div>
                        <div><h4 class="fw-bold text-white mb-0">24/7</h4><small class="text-white-50">Support</small></div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="position-relative">
                        <img src="assets/images/doctor.jpg" alt="Healthcare" class="img-fluid rounded-4 shadow-lg" style="max-width: 450px;">
                        <div class="position-absolute bottom-0 start-0 bg-white p-3 rounded shadow m-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-success rounded-circle p-2 me-2"><i class="fas fa-check text-white"></i></div>
                                <div><strong>Easy Booking</strong><br><small class="text-muted">In just 3 steps</small></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Doctors Section -->
    <section id="doctors" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 mb-2">Medical Experts</span>
                <h2 class="fw-bold display-6">Our Doctors</h2>
                <p class="text-muted">Meet our team of experienced healthcare professionals</p>
            </div>
            
            <!-- Search and Filter -->
            <div class="row g-3 mb-4 justify-content-center">
                <div class="col-md-5">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-primary"></i></span>
                        <input type="text" class="form-control border-start-0" id="searchDoctor" placeholder="Search by doctor name...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select shadow-sm" id="filterSpecialization">
                        <option value="">All Specializations</option>
                        <?php foreach ($specializations as $spec): ?>
                            <option value="<?php echo htmlspecialchars($spec); ?>"><?php echo htmlspecialchars($spec); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-secondary w-100 shadow-sm" onclick="resetFilters()"><i class="fas fa-redo me-1"></i>Reset</button>
                </div>
            </div>
            
            <!-- Doctors Grid -->
            <div class="row" id="doctorGrid">
                <?php foreach ($doctors as $doctor): ?>
                <div class="col-md-6 col-lg-4 mb-4 doctor-card" data-name="<?php echo strtolower($doctor['name']); ?>" data-specialization="<?php echo strtolower($doctor['specialisation']); ?>">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <!-- Doctor Photo -->
                            <div class="avatar-circle mb-3">
                                <?php 
                                $photoPath = isset($doctor['photo_path']) ? trim($doctor['photo_path']) : '';
                                $defaultPhoto = 'assets/images/doctor.jpg';
                                
                                if (!empty($photoPath) && file_exists($photoPath)): ?>
                                    <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Dr. <?php echo htmlspecialchars($doctor['name']); ?>" class="rounded-circle" style="width: 70px; height: 70px; object-fit: cover;">
                                <?php elseif (file_exists($defaultPhoto)): ?>
                                    <img src="<?php echo $defaultPhoto; ?>" alt="Dr. <?php echo htmlspecialchars($doctor['name']); ?>" class="rounded-circle" style="width: 70px; height: 70px; object-fit: cover;">
                                <?php else: ?>
                                    <span class="avatar-initials"><?php echo strtoupper(substr($doctor['name'], 0, 1)); ?></span>
                                <?php endif; ?>
                            </div>
                            <h5 class="card-title fw-bold">Dr. <?php echo htmlspecialchars($doctor['name']); ?></h5>
                            <p class="text-primary small fw-bold mb-2"><i class="fas fa-stethoscope me-1"></i><?php echo htmlspecialchars($doctor['specialisation']); ?></p>
                            <div class="text-muted small">
                                <p class="mb-1"><i class="fas fa-briefcase me-2"></i><?php echo $doctor['experience']; ?> years experience</p>
                                <p class="mb-0"><i class="fas fa-phone me-2"></i><?php echo $doctor['contact']; ?></p>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0">
                            <a href="patient/signup.php" class="btn btn-outline-primary w-100"><i class="fas fa-calendar-check me-1"></i> Book Appointment</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($doctors)): ?>
                <div class="col-12 text-center py-4">
                    <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No doctors available at the moment.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- No Results -->
            <div class="text-center py-4 d-none" id="noResults">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No doctors match your search</h6>
                <button class="btn btn-secondary btn-sm" onclick="resetFilters()"><i class="fas fa-redo me-1"></i>Reset Filters</button>
            </div>
            
            <div class="text-center mt-4">
                <a href="patient/signup.php" class="btn btn-primary btn-lg shadow"><i class="fas fa-user-plus me-2"></i>Register to Book Appointment</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 mb-2">Why Choose Us</span>
                <h2 class="fw-bold display-6">Our Features</h2>
                <p class="text-muted">Everything you need for easy healthcare access</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center">
                        <div class="feature-icon mb-3"><i class="fas fa-calendar-check fa-3x text-primary"></i></div>
                        <h5>Easy Booking</h5>
                        <p class="text-muted mb-0">Book appointments with doctors in just a few clicks. No waiting in queues.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center">
                        <div class="feature-icon mb-3"><i class="fas fa-clock fa-3x text-primary"></i></div>
                        <h5>24/7 Availability</h5>
                        <p class="text-muted mb-0">Book appointments anytime, anywhere. Accessible from any device.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center">
                        <div class="feature-icon mb-3"><i class="fas fa-user-md fa-3x text-primary"></i></div>
                        <h5>Expert Doctors</h5>
                        <p class="text-muted mb-0">Access to qualified and experienced healthcare professionals.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 mb-2">Process</span>
                <h2 class="fw-bold display-6">How It Works</h2>
            </div>
            <div class="row text-center">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="p-4">
                        <div class="step-number mx-auto mb-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;font-size:24px;">1</div>
                        <h5>Register</h5>
                        <p class="text-muted mb-0">Create your account as a patient</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="p-4">
                        <div class="step-number mx-auto mb-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;font-size:24px;">2</div>
                        <h5>Find Doctor</h5>
                        <p class="text-muted mb-0">Search by specialization</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="p-4">
                        <div class="step-number mx-auto mb-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;font-size:24px;">3</div>
                        <h5>Book Slot</h5>
                        <p class="text-muted mb-0">Choose date and time</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="p-4">
                        <div class="step-number mx-auto mb-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;font-size:24px;">4</div>
                        <h5>Get Confirmation</h5>
                        <p class="text-muted mb-0">Receive instant confirmation</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="fw-bold mb-3">Ready to Get Started?</h2>
            <p class="mb-4">Join thousands of patients who trust City Hospital for their healthcare needs.</p>
            <a href="patient/signup.php" class="btn btn-light btn-lg px-5 shadow"><i class="fas fa-user-plus me-2"></i>Register Now</a>
            <a href="patient/login.php" class="btn btn-outline-light btn-lg px-5 ms-2">Login</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-hospital me-2"></i>City Hospital</h5>
                    <p class="text-muted mb-0">Your trusted healthcare partner in Nepal providing quality medical services.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3">Contact</h5>
                    <p class="mb-1"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Kathmandu, Nepal</p>
                    <p class="mb-1"><i class="fas fa-phone me-2 text-primary"></i>+977 1-4567890</p>
                    <p class="mb-0"><i class="fas fa-envelope me-2 text-primary"></i>info@cityhospital.com</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3">Quick Links</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="patient/login.php" class="text-muted text-decoration-none">Patient Login</a></li>
                        <li><a href="patient/signup.php" class="text-muted text-decoration-none">Register</a></li>
                        <li><a href="admin/login.php" class="text-muted text-decoration-none">Admin Login</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 border-secondary">
            <div class="text-center">
                <p class="mb-0">&copy; 2026 City Hospital Kathmandu. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        document.getElementById('searchDoctor').addEventListener('keyup', filterDoctors);
        document.getElementById('filterSpecialization').addEventListener('change', filterDoctors);
        function filterDoctors() {
            const search = document.getElementById('searchDoctor').value.toLowerCase();
            const spec = document.getElementById('filterSpecialization').value.toLowerCase();
            const cards = document.querySelectorAll('.doctor-card');
            let visible = 0;
            cards.forEach(card => {
                const name = card.dataset.name, specialization = card.dataset.specialization;
                if ((name.includes(search)) && (spec === '' || specialization === spec)) {
                    card.classList.remove('d-none'); visible++;
                } else { card.classList.add('d-none'); }
            });
            document.getElementById('doctorGrid').classList.toggle('d-none', visible === 0 && cards.length > 0);
            document.getElementById('noResults').classList.toggle('d-none', visible > 0 || cards.length === 0);
        }
        function resetFilters() { document.getElementById('searchDoctor').value = ''; document.getElementById('filterSpecialization').value = ''; filterDoctors(); }
    </script>
</body>
</html>
