-- =====================================================
-- Sistema de Tienda en Línea - La Despensa de Don Juan
-- Base de Datos: despensa_donjuan
-- =====================================================

CREATE DATABASE IF NOT EXISTS despensa_donjuan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE despensa_donjuan;

-- Tabla de usuarios (login)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empleado') NOT NULL DEFAULT 'empleado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    categoria ENUM('lacteos', 'carnes', 'bebidas', 'panaderia', 'limpieza', 'frutas_verduras', 'otros') NOT NULL,
    precio DECIMAL(8,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    descripcion TEXT NULL,              -- campo que acepta valores nulos
    disponible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_nombre VARCHAR(150) NOT NULL,
    cliente_email VARCHAR(150) NOT NULL,
    cliente_telefono VARCHAR(20) NOT NULL,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia') NOT NULL,
    requiere_factura TINYINT(1) NOT NULL DEFAULT 0,
    nit VARCHAR(20) NULL,               -- campo que acepta valores nulos
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'procesando', 'entregado', 'cancelado') NOT NULL DEFAULT 'pendiente',
    notas TEXT NULL,                    -- campo que acepta valores nulos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Usuario administrador por defecto (password: Admin123!)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@despensa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- NOTA: El hash anterior es de 'password'. Para producción usar:
-- password_hash('Admin123!', PASSWORD_BCRYPT)

-- Datos de productos (al menos 5 registros)
INSERT INTO productos (nombre, categoria, precio, stock, descripcion, disponible) VALUES
('Leche Entera Dos Pinos 1L', 'lacteos', 1.45, 120, 'Leche entera pasteurizada, ideal para toda la familia.', 1),
('Pollo Entero Fresco kg', 'carnes', 3.25, 45, 'Pollo fresco del día, criado en granjas locales.', 1),
('Coca-Cola 2.5L', 'bebidas', 1.85, 80, NULL, 1),
('Pan Francés (unidad)', 'panaderia', 0.15, 200, 'Pan artesanal horneado cada mañana.', 1),
('Detergente Ariel 1kg', 'limpieza', 3.75, 60, 'Detergente en polvo de alta eficiencia.', 1),
('Tomates Frescos kg', 'frutas_verduras', 0.85, 35, NULL, 1),
('Queso Duro kg', 'lacteos', 5.50, 25, 'Queso artesanal producido en la zona oriental.', 1),
('Agua Cristal 600ml', 'bebidas', 0.50, 150, NULL, 1);

-- Datos de pedidos (al menos 5 registros)
INSERT INTO pedidos (cliente_nombre, cliente_email, cliente_telefono, metodo_pago, requiere_factura, nit, total, estado, notas) VALUES
('María López', 'maria.lopez@gmail.com', '7654-3210', 'efectivo', 0, NULL, 12.50, 'entregado', 'Entregar en la mañana'),
('Carlos Hernández', 'carlos.h@hotmail.com', '7823-1122', 'tarjeta', 1, '0614-010185-101-2', 45.75, 'procesando', NULL),
('Ana Martínez', 'ana.martinez@yahoo.com', '7112-9988', 'transferencia', 0, NULL, 8.30, 'pendiente', NULL),
('José Ramírez', 'jose.r@gmail.com', '7456-7890', 'efectivo', 1, '0614-150390-103-5', 22.00, 'entregado', 'Cliente frecuente'),
('Lucía Flores', 'lucia.flores@gmail.com', '7334-5566', 'tarjeta', 0, NULL, 15.90, 'pendiente', 'Llamar antes de entregar');
