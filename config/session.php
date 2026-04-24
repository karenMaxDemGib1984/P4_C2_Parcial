<?php
// =====================================================
// config/session.php - Manejo de sesiones y autenticación
// =====================================================

session_start();

function estaAutenticado(): bool {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

function requiereLogin(): void {
    if (!estaAutenticado()) {
        header('Location: ../index.php?msg=acceso_denegado');
        exit;
    }
}

function obtenerUsuario(): array {
    return [
        'id'     => $_SESSION['usuario_id']     ?? null,
        'nombre' => $_SESSION['usuario_nombre'] ?? '',
        'email'  => $_SESSION['usuario_email']  ?? '',
        'rol'    => $_SESSION['usuario_rol']    ?? '',
    ];
}

// Sanitizar entrada básica
function sanitizar(string $valor): string {
    return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
}
