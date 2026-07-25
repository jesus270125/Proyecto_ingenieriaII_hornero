
CREATE DATABASE IF NOT EXISTS el_hornero;
USE el_hornero;


CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    clave VARCHAR(100) NOT NULL,
    tipo ENUM('admin','cocina','pedido','caja') NOT NULL
);

CREATE TABLE menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(8,2) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255),
    categoria ENUM('bebidas','comida') NOT NULL DEFAULT 'comida'
);

CREATE TABLE caja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_apertura DATETIME NOT NULL,
    fecha_cierre DATETIME,
    monto_inicial DECIMAL(8,2) NOT NULL,
    monto_final DECIMAL(8,2),
    estado ENUM('ABIERTA','CERRADA') DEFAULT 'ABIERTA'
);

CREATE TABLE venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    monto DECIMAL(8,2) NOT NULL,
    metodo_pago ENUM('Efectivo', 'Tarjeta', 'Yape') DEFAULT 'Efectivo'
);

CREATE TABLE pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mesa INT NOT NULL,
    usuario_id INT,
    detalle TEXT,
    tipo_servicio ENUM('local','llevar') DEFAULT 'local',
    estado ENUM('pedido','cocinando','preparado','entregado','pagado') DEFAULT 'pedido',
    venta_id INT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (venta_id) REFERENCES venta(id)
);

CREATE TABLE venta_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    menu_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(8,2) NOT NULL,
    subtotal DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES venta(id),
    FOREIGN KEY (menu_id) REFERENCES menu(id)
);

CREATE TABLE recibo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    numero VARCHAR(20) NOT NULL,
    subtotal DECIMAL(8,2) NOT NULL,
    igv DECIMAL(8,2) NOT NULL,
    total DECIMAL(8,2) NOT NULL,
    tipo ENUM('BOLETA','FACTURA') DEFAULT 'BOLETA',
    estado_sunat ENUM('PENDIENTE','ENVIADO','RECHAZADO') DEFAULT 'PENDIENTE',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venta_id) REFERENCES venta(id)
);

CREATE TABLE sunat_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recibo_id INT NOT NULL,
    respuesta VARCHAR(50),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recibo_id) REFERENCES recibo(id)
);


INSERT INTO usuarios (usuario, nombres, apellidos, clave, tipo) VALUES
('admin', 'Administrador', 'General', 'admin123', 'admin'),
('cocinero', 'Juan', 'Cocinero', 'cocina123', 'cocina'),
('mesero', 'Luis', 'Mesero', 'pedido123', 'pedido'),
('cajero', 'Ana', 'Cajera', 'caja123', 'caja');


INSERT INTO menu (nombre, precio, descripcion, imagen, categoria) VALUES
('Pollo entero', 65.00, '1 pollo entero con papas y ensalada', NULL, 'comida'),
('Medio pollo', 35.00, '1/2 pollo con papas y ensalada', NULL, 'comida'),
('Cuarto de pollo', 20.00, '1/4 de pollo con papas', NULL, 'comida'),
('Octavo de pollo', 12.00, '1/8 de pollo ideal para un monstrito', NULL, 'comida');



INSERT INTO menu (nombre, precio, descripcion, imagen, categoria) VALUES
('Gaseosa 1L', 8.00, 'Gaseosa de 1 litro', NULL, 'bebidas'),
('Gaseosa 2L', 12.00, 'Gaseosa de 2 litros', NULL, 'bebidas'),
('Chicha morada 1L', 10.00, 'Chicha morada natural 1 litro', NULL, 'bebidas'),
('Agua mineral 600ml', 4.00, 'Agua mineral sin gas', NULL, 'bebidas');

INSERT INTO venta (fecha, monto) VALUES
(CURDATE(), 65.00),
(CURDATE(), 35.00),
(CURDATE(), 20.00);

-- ====================================================================
-- MÓDULO DE GESTIÓN DE INVENTARIOS (RF31 - RF34)
-- ====================================================================

-- RF31: Catálogo y Registro de Insumos Base
CREATE TABLE insumo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    unidad_medida VARCHAR(20) NOT NULL,
    stock_actual DECIMAL(10,3) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10,3) NOT NULL DEFAULT 0,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- RF32: Registro de Entradas de Mercadería (Historial de Compras)
CREATE TABLE entrada_mercaderia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    insumo_id INT NOT NULL,
    cantidad DECIMAL(10,3) NOT NULL,
    costo DECIMAL(8,2) DEFAULT NULL,
    proveedor VARCHAR(100) DEFAULT NULL,
    fecha DATE NOT NULL,
    observacion TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (insumo_id) REFERENCES insumo(id)
);

-- RF33: Tabla intermedia para Recetas (Producto-Insumo)
CREATE TABLE menu_insumo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT NOT NULL,
    insumo_id INT NOT NULL,
    cantidad_requerida DECIMAL(10,3) NOT NULL,
    FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE,
    FOREIGN KEY (insumo_id) REFERENCES insumo(id),
    UNIQUE KEY unique_menu_insumo (menu_id, insumo_id)
);

-- ====================================================================
-- DATOS DE EJEMPLO PARA INVENTARIOS
-- ====================================================================

INSERT INTO insumo (nombre, categoria, unidad_medida, stock_actual, stock_minimo) VALUES
('Pollo entero crudo', 'Carnes', 'kg', 50.000, 10.000),
('Papa', 'Verduras', 'kg', 80.000, 15.000),
('Aceite vegetal', 'Aceites', 'litros', 20.000, 5.000),
('Sal', 'Condimentos', 'kg', 10.000, 2.000),
('Ají panca molido', 'Condimentos', 'kg', 5.000, 1.000),
('Ajo molido', 'Condimentos', 'kg', 3.000, 0.500),
('Sillao', 'Condimentos', 'litros', 4.000, 1.000),
('Lechuga', 'Verduras', 'kg', 15.000, 3.000),
('Tomate', 'Verduras', 'kg', 12.000, 3.000),
('Gaseosa 1L', 'Bebidas', 'unidades', 30.000, 10.000),
('Gaseosa 2L', 'Bebidas', 'unidades', 20.000, 8.000),
('Chicha morada concentrada', 'Bebidas', 'litros', 10.000, 3.000),
('Agua mineral 600ml', 'Bebidas', 'unidades', 40.000, 15.000),
('Carbón', 'Combustible', 'kg', 25.000, 5.000),
('Vinagre', 'Condimentos', 'litros', 3.000, 1.000);

-- Recetas: Pollo entero (menu_id=1) = 1.2kg pollo + 1kg papa + 0.3L aceite + ensalada
INSERT INTO menu_insumo (menu_id, insumo_id, cantidad_requerida) VALUES
(1, 1, 1.200),  -- Pollo entero: 1.2 kg pollo
(1, 2, 1.000),  -- Pollo entero: 1 kg papa
(1, 3, 0.300),  -- Pollo entero: 0.3 L aceite
(1, 4, 0.020),  -- Pollo entero: 20g sal
(1, 8, 0.150),  -- Pollo entero: 150g lechuga
(1, 9, 0.100),  -- Pollo entero: 100g tomate
(1, 14, 0.500); -- Pollo entero: 0.5 kg carbón

-- Recetas: Medio pollo (menu_id=2)
INSERT INTO menu_insumo (menu_id, insumo_id, cantidad_requerida) VALUES
(2, 1, 0.600),
(2, 2, 0.500),
(2, 3, 0.150),
(2, 4, 0.010),
(2, 8, 0.100),
(2, 9, 0.080),
(2, 14, 0.250);

-- Recetas: Cuarto de pollo (menu_id=3)
INSERT INTO menu_insumo (menu_id, insumo_id, cantidad_requerida) VALUES
(3, 1, 0.300),
(3, 2, 0.300),
(3, 3, 0.100),
(3, 4, 0.005),
(3, 14, 0.150);

INSERT INTO menu_insumo (menu_id, insumo_id, cantidad_requerida) VALUES
(4, 1, 0.150),
(4, 2, 0.200),
(4, 3, 0.050),
(4, 4, 0.003),
(4, 14, 0.100);

-- ====================================================================
-- MÓDULO DE PROPINAS Y FIDELIZACIÓN (RF39 - RF40 - RF38)
-- ====================================================================

CREATE TABLE propinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NULL,
    monto DECIMAL(8,2) NOT NULL DEFAULT 0,
    metodo_pago VARCHAR(50) NULL,
    usuario_id INT NULL,
    referencia VARCHAR(255) NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (venta_id) REFERENCES venta(id)
);

CREATE TABLE puntos_fidelidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NULL,
    puntos INT DEFAULT 0,
    acumulado_total INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Datos de ejemplo
INSERT INTO propinas (venta_id, monto, metodo_pago, usuario_id, referencia) VALUES
(1, 5.00, 'Yape', 4, 'YP12345'),
(2, 3.00, 'Tarjeta', 4, 'TX98765');

INSERT INTO puntos_fidelidad (cliente_id, puntos, acumulado_total) VALUES
(NULL, 120, 120);
