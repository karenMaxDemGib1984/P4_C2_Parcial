<?php
// =====================================================
// actions/guardar_producto.php
// =====================================================

require_once '../config/db.php';
require_once '../config/session.php';

requiereLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit;
}

$errores = [];

// --- Recuperar y sanitizar campos ---
$nombre      = sanitizar($_POST['nombre']      ?? '');
$categoria   = sanitizar($_POST['categoria']   ?? '');
$precio      = $_POST['precio']    ?? '';
$stock       = $_POST['stock']     ?? '';
$descripcion = sanitizar($_POST['descripcion'] ?? '');
$disponible  = isset($_POST['disponible']) ? 1 : 0;

// --- Validaciones ---
if (empty($nombre) || strlen($nombre) < 3) {
    $errores[] = 'El nombre del producto debe tener al menos 3 caracteres.';
}

$categorias_validas = ['lacteos','carnes','bebidas','panaderia','limpieza','frutas_verduras','otros'];
if (!in_array($categoria, $categorias_validas)) {
    $errores[] = 'Seleccione una categoría válida.';
}

if (!is_numeric($precio) || $precio <= 0) {
    $errores[] = 'El precio debe ser un número mayor a 0.';
}

if (!is_numeric($stock) || $stock < 0 || !ctype_digit(strval($stock))) {
    $errores[] = 'El stock debe ser un número entero no negativo.';
}

// descripcion puede ser nula
$descripcionFinal = (trim($descripcion) === '') ? null : $descripcion;

if (!empty($errores)) {
    $_SESSION['form_error']  = implode('<br>', $errores);
    $_SESSION['form_datos']  = $_POST; // Repoblar formulario
    header('Location: ../dashboard.php#productos');
    exit;
}

try {
    $pdo  = conectarBD();
    $stmt = $pdo->prepare(
        "INSERT INTO productos (nombre, categoria, precio, stock, descripcion, disponible)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$nombre, $categoria, (float)$precio, (int)$stock, $descripcionFinal, $disponible]);

    $_SESSION['form_exito'] = "Producto \"$nombre\" registrado correctamente.";
    header('Location: ../dashboard.php#productos');
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['form_error'] = 'Error al guardar el producto. Intente de nuevo.';
    header('Location: ../dashboard.php#productos');
    exit;
}
