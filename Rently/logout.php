<?php
// logout.php
require_once 'includes/functions.php';

// Destroy session data
session_unset();
session_destroy();

// Redirect back to home
redirect('index.php');
?>
