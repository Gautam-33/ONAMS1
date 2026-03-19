<?php
// includes/footer.php

// Admin footer configuration
$admin_footer_text = "Your Hospital Name";
$admin_footer_year = date('Y');

// Patient footer configuration
$patient_footer_text = "City Hospital Kathmandu";
$patient_footer_subtitle = "Online Appointment Management System";
?>

<?php
// Check if we're in admin section
$is_admin_section = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
?>

<?php if ($is_admin_section): ?>
        </main> <!-- Close main content from admin-header -->
    </div> <!-- Close row -->
</div> <!-- Close container-fluid -->

<footer class="footer mt-auto py-3 bg-light border-top">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <span class="text-muted"><?php echo $admin_footer_text; ?> &copy; <?php echo $admin_footer_year; ?></span>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="text-muted">
                    <i class="fas fa-user me-1"></i>
                    Logged in as: <strong><?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></strong>
                </span>
            </div>
        </div>
    </div>
</footer>
<?php else: ?>
    </div> <!-- Close container from header -->
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-calendar-check me-2"></i><?php echo $patient_footer_text; ?></h5>
                    <p><?php echo $patient_footer_subtitle; ?></p>
                    <p>&copy; <?php echo date('Y'); ?> <?php echo $patient_footer_text; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p></p>
                    <p></p>
                </div>
            </div>
        </div>
    </footer>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
<?php if (isset($custom_js)): ?>
    <script><?php echo $custom_js; ?></script>
<?php endif; ?>

<!-- Toast Container -->
<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>

</body>
</html>
