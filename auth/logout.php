<?php
// auth/logout.php
require_once '../config/session.php';
session_unset();
session_destroy();
header('Location: ../index.php?msg=sesion_cerrada');
exit;
