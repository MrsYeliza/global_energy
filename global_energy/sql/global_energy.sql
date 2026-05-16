-- ============================================
-- Global Energy B.C. - Base de Datos
-- ============================================
CREATE DATABASE IF NOT EXISTS global_energy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE global_energy;

-- Tabla de usuarios del sistema
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin','vendedor','gerente') DEFAULT 'vendedor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(50),
    modelo VARCHAR(80),
    tipo ENUM('refrigerador','lavadora','aire_acondicionado') NOT NULL,
    capacidad VARCHAR(40),
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    color VARCHAR(40),
    num_serie VARCHAR(80),
    garantia_meses INT DEFAULT 12,
    proveedor VARCHAR(100),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de ventas
CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    num_venta VARCHAR(30) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    producto_id INT NOT NULL,
    usuario_id INT,
    fecha_venta DATE NOT NULL,
    estatus ENUM('activa','entregada','cancelada') DEFAULT 'activa',
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de créditos
CREATE TABLE IF NOT EXISTS creditos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL UNIQUE,
    num_pagos INT NOT NULL DEFAULT 1,
    monto_total DECIMAL(10,2) NOT NULL,
    monto_pago_quincenal DECIMAL(10,2),
    fecha_inicio DATE,
    estatus ENUM('activo','pagado','vencido') DEFAULT 'activo',
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE
);

-- Tabla de documentos
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    tipo ENUM('poliza','factura','contrato','presupuesto','requisitos') NOT NULL,
    nombre_original VARCHAR(255),
    ruta_archivo VARCHAR(255) NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE
);

-- ============================================
-- Datos de prueba
-- ============================================
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@globalenergy.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Vendedor 1', 'vendedor1@globalenergy.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendedor');

INSERT INTO clientes (nombre, telefono, email) VALUES
('María García López', '664-123-4567', 'maria@email.com'),
('Carlos Rodríguez', '664-987-6543', 'carlos@email.com'),
('Ana Martínez Cruz', '664-555-0001', 'ana@email.com'),
('Roberto Sánchez', '664-555-0002', 'roberto@email.com'),
('Lucia Flores', '664-555-0003', 'lucia@email.com');

INSERT INTO productos (marca, modelo, tipo, capacidad, precio, color, num_serie, garantia_meses, proveedor, stock) VALUES
('Samsung', 'RT29K500JS8', 'refrigerador', '11 pies', 12500.00, 'Plata', 'SN-001-2024', 12, 'Samsung MX', 5),
('LG', 'WT22BSPD', 'lavadora', '22 kg', 9800.00, 'Blanco', 'LG-002-2024', 12, 'LG México', 3),
('Mabe', 'RME360FXMRX0', 'refrigerador', '14 pies', 11200.00, 'Gris', 'MB-003-2024', 12, 'Mabe SA', 4),
('Whirlpool', 'ACO18F', 'aire_acondicionado', '18000 BTU', 14500.00, 'Blanco', 'WP-004-2024', 12, 'Whirlpool MX', 2),
('Acros', 'AWH1530CS', 'lavadora', '15 kg', 7600.00, 'Blanco', 'AC-005-2024', 12, 'Acros Vitro', 6);

INSERT INTO ventas (num_venta, cliente_id, producto_id, usuario_id, fecha_venta, estatus) VALUES
('GE-2024-001', 1, 1, 1, '2024-01-15', 'entregada'),
('GE-2024-002', 2, 2, 2, '2024-02-03', 'activa'),
('GE-2024-003', 3, 4, 1, '2024-02-20', 'activa'),
('GE-2024-004', 4, 3, 2, '2024-03-10', 'entregada'),
('GE-2024-005', 5, 5, 1, '2024-03-25', 'activa');

INSERT INTO creditos (venta_id, num_pagos, monto_total, monto_pago_quincenal, fecha_inicio, estatus) VALUES
(1, 24, 12500.00, 520.83, '2024-01-15', 'activo'),
(2, 18, 9800.00, 544.44, '2024-02-03', 'activo'),
(3, 24, 14500.00, 604.17, '2024-02-20', 'activo'),
(4, 12, 11200.00, 933.33, '2024-03-10', 'pagado'),
(5, 18, 7600.00, 422.22, '2024-03-25', 'activo');
