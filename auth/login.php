<?php
// =====================================================
// auth/login.php - Procesamiento del formulario de login
// =====================================================

require_once '../config/db.php';
require_once '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$email    = sanitizar($_POST['email']    ?? '');
$password = $_POST['password'] ?? '';

// Validaciones básicas
$errores = [];

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'Ingrese un correo electrónico válido.';
}
if (empty($password) || strlen($password) < 6) {
    $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
}

if (!empty($errores)) {
    $_SESSION['login_error'] = implode(' ', $errores);
    header('Location: ../index.php');
    exit;
}

try {
    $pdo  = conectarBD();
    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // password_verify() compara contra el hash almacenado
    // Para prueba rápida el hash guardado corresponde a 'password'
    if ($usuario && password_verify($password, $usuario['password'])) {
        session_regenerate_id(true); // Prevenir session fixation
        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email']  = $usuario['email'];
        $_SESSION['usuario_rol']    = $usuario['rol'];
        header('Location: ../dashboard.php');
        exit;
    } else {
        $_SESSION['login_error'] = 'Correo o contraseña incorrectos.';
        header('Location: ../index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['login_error'] = 'Error del servidor. Intente más tarde.';
    header('Location: ../index.php');
    exit;
}
