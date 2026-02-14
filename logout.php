<?php
require_once 'backend/config.php';

session_destroy();
header('Location: login.php');
exit;
?>
