// assets/js/script.js
// Simplified: Focus on appointment conflict detection algorithm

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-dismiss alerts
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Real-time availability/conflict check
    const availabilityCheckers = document.querySelectorAll('.check-availability');
    availabilityCheckers.forEach(checker => {
        checker.addEventListener('change', checkConflict);
    });
});

// ALGORITHM: Appointment Conflict Detection
function checkConflict(event) {
    const form = event.target.form;
    const doctorId = form.querySelector('[name="doctor_id"]').value;
    const clinicId = form.querySelector('[name="clinic_id"]').value;
    const date = form.querySelector('[name="date"]').value;
    const timeSlot = form.querySelector('[name="time_slot"]').value;
    
    if (!doctorId || !clinicId || !date) {
        return;
    }
    
    // Call backend to check for conflicts
    fetch(`../api/check-availability.php?doctor_id=${doctorId}&clinic_id=${clinicId}&date=${date}&time=${timeSlot}`)
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('availability-result');
            if (resultDiv) {
                if (data.available) {
                    const slotsRemaining = data.available_slots || (data.max_patients - (data.booked_count || 0));
                    resultDiv.innerHTML = '<div class="alert alert-success">✓ Time slot available (' + slotsRemaining + ' slot(s) remaining)</div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger">✗ ' + (data.message || 'Time slot fully booked') + '</div>';
                }
            }
        })
        .catch(error => console.error('Conflict check error:', error));
}


// Confirm before delete
function confirmDelete(message = 'Are you sure you want to delete this?') {
    return confirm(message);
}