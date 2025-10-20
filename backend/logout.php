<?php
require_once __DIR__ . '/session.php';

session_destroy();
header("Location: ../frontend/index.php");
exit();
?>