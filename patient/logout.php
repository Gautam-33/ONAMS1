<?php
// patient/logout.php
require_once '../includes/session.php';

// Destroy session using the proper function
destroySession();

// Redirect to home page
header('Location: ../index.php');
exit();
?>
