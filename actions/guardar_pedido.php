<?php
// =====================================================
// actions/guardar_pedido.php
// =====================================================

require_once '../config/db.php';
require_once '../config/session.php';

requiereLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit;
}

$errores = [];

$cliente_nombre  = sanitizar($_POST['cliente_nombre']  ?? '');
$cliente_email   = sanitizar($_POST['cliente_email']   ?? '');
$cliente_tel     = sanitizar($_POST['cliente_telefono'] ?? '');
$metodo_pago     = sanitizar($_POST['metodo_pago']     ?? '');
$requiere_fac    = isset($_POST['requiere_factura']) ? 1 : 0;
$nit             = sanitizar($_POST['nit']             ?? '');
$total           = $_POST['total'] ?? '';
$estado          = sanitizar($_POST['estado']          ?? 'pendiente');
$notas           = sanitizar($_POST['notas']           ?? '');

// Validaciones
if (empty($cliente_nombre) || strlen($cliente_nombre) < 3) {
    $errores[] = 'Ingrese el nombre completo del cliente.';
}
if (!filter_var($cliente_email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'Correo del cliente no es válido.';
}
if (!preg_match('/^\d{4}-\d{4}$/', $cliente_tel)) {
    $errores[] = 'Teléfono inválido. Use formato: 7654-3210';
}

$pagos_validos = ['efectivo','tarjeta','transferencia'];
if (!in_array($metodo_pago, $pagos_validos)) {
    $errores[] = 'Seleccione un método de pago válido.';
}
if (!is_numeric($total) || $total <= 0) {
    $errores[] = 'El total debe ser mayor a $0.00';
}
if ($requiere_fac && empty($nit)) {
    $errores[] = 'Si requiere factura, debe ingresar el NIT.';
}

$estados_validos = ['pendiente','procesando','entregado','cancelado'];
if (!in_array($estado, $estados_validos)) {
    $errores[] = 'Estado inválido.';
}

$nitFinal   = ($nit   === '') ? null : $nit;
$notasFinal = ($notas === '') ? null : $notas;

if (!empty($errores)) {
    $_SESSION['pedido_error'] = implode('<br>', $errores);
    $_SESSION['pedido_datos'] = $_POST;
    header('Location: ../dashboard.php#pedidos');
    exit;
}

try {
    $pdo  = conectarBD();
    $stmt = $pdo->prepare(
        "INSERT INTO pedidos (cliente_nombre, cliente_email, cliente_telefono, metodo_pago,
                              requiere_factura, nit, total, estado, notas)
         VALUES (?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
        $cliente_nombre, $cliente_email, $cliente_tel, $metodo_pago,
        $requiere_fac, $nitFinal, (float)$total, $estado, $notasFinal
    ]);

    $_SESSION['pedido_exito'] = "Pedido de \"$cliente_nombre\" registrado correctamente.";
    header('Location: ../dashboard.php#pedidos');
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['pedido_error'] = 'Error al guardar el pedido.';
    header('Location: ../dashboard.php#pedidos');
    exit;
}
