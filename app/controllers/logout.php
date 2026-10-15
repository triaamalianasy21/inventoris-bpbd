<?php
session_start();
session_unset();
session_destroy();

// Karena login.php dan logout.php sejajar di folder controller/
header("Location: login.php");
exit;
