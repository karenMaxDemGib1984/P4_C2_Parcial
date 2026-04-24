<?php
// =====================================================
// config/db.php - Conexión a la base de datos
// Sistema: La Despensa de Don Juan
// =====================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Cambiar según su configuración
define('DB_PASS', '');            // Cambiar según su configuración
define('DB_NAME', 'despensa_donjuan');

function conectarBD(): PDO {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        return $pdo;
    } catch (PDOException $e) {
        // En producción NO mostrar el mensaje real al usuario
        error_log("Error de conexión BD: " . $e->getMessage());
        die(json_encode([
            'exito'   => false,
            'mensaje' => 'Error al conectar con la base de datos. Intente más tarde.'
        ]));
    }
}
