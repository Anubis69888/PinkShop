<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Set timezone if needed
date_default_timezone_set('Asia/Bangkok');
?>
