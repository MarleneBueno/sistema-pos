-- ====== DATOS INICIALES PARA SISTEMA POS ======
-- Este archivo contiene los datos básicos para inicializar el sistema

USE grocerystore;

-- Insertar usuario administrador por defecto
-- Contraseña: Admin123!
INSERT INTO employees (first_name, middle_name, last_name, username, email, phone_number, role, password, active) VALUES
('Administrador', '', 'Sistema', 'admin', 'admin@abarrotes.com', '5551234567', 'admin', '$2y$12$LQv3c1ydiCSmqmdHPDc9UON5.7x0w/7/Kn1XGPZ2aDCH.mY4kw9Ay', 1),
('Juan Carlos', 'Eduardo', 'Pérez', 'cajero1', 'juan.perez@abarrotes.com', '5557654321', 'seller', '$2y$12$LQv3c1ydiCSmqmdHPDc9UON5.7x0w/7/Kn1XGPZ2aDCH.mY4kw9Ay', 1),
('María', 'Isabel', 'González', 'cajero2', 'maria.gonzalez@abarrotes.com', '5559876543', 'seller', '$2y$12$LQv3c1ydiCSmqmdHPDc9UON5.7x0w/7/Kn1XGPZ2aDCH.mY4kw9Ay', 1);

-- Insertar proveedores de ejemplo
INSERT INTO suppliers (supplier_name, phone_number, address) VALUES
('Distribuidora La Central', '5551111111', 'Av. Central 123, Col. Centro, CDMX'),
('Abarrotes Mayoreo SA', '5552222222', 'Calle Comercio 456, Col. Industrial, CDMX'),
('Productos del Campo', '5553333333', 'Carretera México-Toluca Km 15, Estado de México'),
('Lacteos y Derivados', '5554444444', 'Blvd. Lácteo 789, Col. Ganadería, CDMX'),
('Bebidas Refrescantes', '5555555555', 'Zona Industrial 321, Col. Bebidas, CDMX');

-- Insertar productos de ejemplo organizados por categorías
INSERT INTO products (product_name, barcode, purchase_price, sale_price, existence, supplier_id) VALUES
-- ABARROTES BÁSICOS
('Arroz Blanco 1kg', '7501234567001', 12.50, 18.00, 50, 1),
('Frijol Negro 1kg', '7501234567002', 15.00, 22.00, 45, 1),
('Azúcar Refinada 1kg', '7501234567003', 14.00, 20.00, 60, 1),
('Sal de Mesa 1kg', '7501234567004', 8.00, 12.00, 80, 1),
('Aceite Vegetal 1L', '7501234567005', 22.00, 32.00, 35, 1),
('Harina de Trigo 1kg', '7501234567006', 16.00, 24.00, 40, 1),
('Avena en Hojuelas 500g', '7501234567007', 18.00, 26.00, 30, 1),
('Lentejas 500g', '7501234567008', 20.00, 28.00, 25, 1),
('Pasta Espagueti 500g', '7501234567009', 12.00, 18.00, 55, 1),
('Atún en Agua 140g', '7501234567010', 15.00, 22.00, 75, 1),

-- LÁCTEOS Y DERIVADOS
('Leche Entera 1L', '7501234567011', 18.00, 25.00, 60, 4),
('Yogurt Natural 1kg', '7501234567012', 25.00, 35.00, 40, 4),
('Queso Panela 500g', '7501234567013', 35.00, 50.00, 30, 4),
('Mantequilla 250g', '7501234567014', 28.00, 40.00, 35, 4),
('Crema Ácida 200ml', '7501234567015', 12.00, 18.00, 45, 4),
('Queso Oaxaca 250g', '7501234567016', 25.00, 36.00, 25, 4),
('Leche Condensada 387g', '7501234567017', 22.00, 32.00, 40, 4),
('Queso Amarillo 200g', '7501234567018', 20.00, 30.00, 35, 4),

-- CARNES Y EMBUTIDOS (productos refrigerados)
('Jamón de Pavo 250g', '7501234567019', 35.00, 50.00, 20, 2),
('Salchicha Viena 500g', '7501234567020', 28.00, 40.00, 25, 2),
('Chorizo Rojo 250g', '7501234567021', 30.00, 45.00, 18, 2),
('Tocino Ahumado 200g', '7501234567022', 32.00, 48.00, 15, 2),

-- FRUTAS Y VERDURAS (productos frescos)
('Plátano Tabasco (kg)', '2001234567001', 8.00, 15.00, 25, 3),
('Manzana Red (kg)', '2001234567002', 25.00, 38.00, 20, 3),
('Naranja Valencia (kg)', '2001234567003', 12.00, 20.00, 30, 3),
('Tomate Saladette (kg)', '2001234567004', 15.00, 25.00, 22, 3),
('Cebolla Blanca (kg)', '2001234567005', 18.00, 28.00, 18, 3),
('Papa Blanca (kg)', '2001234567006', 20.00, 30.00, 35, 3),
('Zanahoria (kg)', '2001234567007', 12.00, 20.00, 15, 3),
('Limón (kg)', '2001234567008', 10.00, 18.00, 25, 3),

-- BEBIDAS
('Coca Cola 600ml', '7501234567023', 12.00, 18.00, 80, 5),
('Agua Natural 1.5L', '7501234567024', 8.00, 15.00, 100, 5),
('Jugo de Naranja 1L', '7501234567025', 18.00, 26.00, 45, 5),
('Cerveza Corona 355ml', '7501234567026', 15.00, 25.00, 60, 5),
('Refresco de Cola 2L', '7501234567027', 20.00, 30.00, 35, 5),
('Té Helado 1L', '7501234567028', 16.00, 24.00, 40, 5),
('Agua Mineral 500ml', '7501234567029', 10.00, 16.00, 70, 5),

-- PRODUCTOS DE LIMPIEZA
('Detergente en Polvo 1kg', '7501234567030', 35.00, 50.00, 25, 2),
('Jabón de Trastes 500ml', '7501234567031', 18.00, 28.00, 40, 2),
('Cloro 1L', '7501234567032', 15.00, 22.00, 35, 2),
('Papel Higiénico 4 rollos', '7501234567033', 25.00, 38.00, 50, 2),
('Servilletas 100 pzas', '7501234567034', 12.00, 18.00, 60, 2),
('Jabón de Baño 150g', '7501234567035', 8.00, 15.00, 45, 2),
('Shampoo 400ml', '7501234567036', 28.00, 42.00, 30, 2),

-- DULCES Y BOTANAS
('Chocolate en Barra 50g', '7501234567037', 8.00, 15.00, 80, 1),
('Paletas de Dulce 10 pzas', '7501234567038', 12.00, 20.00, 65, 1),
('Chicles Surtidos 5 pzas', '7501234567039', 5.00, 10.00, 90, 1),
('Papas Fritas 150g', '7501234567040', 15.00, 25.00, 55, 1),
('Cacahuates Salados 100g', '7501234567041', 10.00, 18.00, 70, 1),
('Galletas Saladas 200g', '7501234567042', 18.00, 28.00, 40, 1),

-- PRODUCTOS CON STOCK BAJO (para probar alertas)
('Café Soluble 100g', '7501234567043', 35.00, 52.00, 8, 1),
('Mayonesa 380g', '7501234567044', 25.00, 38.00, 5, 2),
('Vinagre Blanco 500ml', '7501234567045', 12.00, 20.00, 3, 1),
('Pimienta Molida 50g', '7501234567046', 15.00, 25.00, 2, 1);

-- Insertar algunos clientes de ejemplo
INSERT INTO clients (client_name, client_email, debt) VALUES
('Público General', 'publico@general.com', 0.00),
('María Elena Rodríguez', 'maria.rodriguez@email.com', 0.00),
('Carlos Alberto Méndez', 'carlos.mendez@email.com', 0.00),
('Ana Patricia López', 'ana.lopez@email.com', 150.00),
('Roberto González Paz', 'roberto.gonzalez@email.com', 75.50),
('Lucia Fernández Torres', 'lucia.fernandez@email.com', 220.00),
('José Manuel Jiménez', 'jose.jimenez@email.com', 0.00),
('Carmen Leticia Cruz', 'carmen.cruz@email.com', 89.75);

-- Insertar algunas ventas de ejemplo para estadísticas
INSERT INTO sales (employee_id, client_id, sale_date, payment_method, sale_status, discount, sale_total) VALUES
(2, 1, '2025-01-01 09:15:00', 'cash', 'complete', 0.00, 125.50),
(2, 1, '2025-01-01 10:30:00', 'card', 'complete', 0.00, 89.75),
(3, 2, '2025-01-01 11:45:00', 'cash', 'complete', 5.00, 67.20),
(2, 1, '2025-01-01 14:20:00', 'credit', 'complete', 0.00, 150.00),
(3, 3, '2025-01-01 16:10:00', 'cash', 'complete', 0.00, 234.80),
(2, 1, '2025-01-01 17:35:00', 'card', 'complete', 10.00, 178.50),
(3, 1, '2025-01-01 18:45:00', 'cash', 'complete', 0.00, 95.25);

-- Insertar detalles de algunas ventas
INSERT INTO sale_details (sale_id, product_id, unit_price, quantity, subtotal) VALUES
-- Venta 1
(1, 1, 18.00, 2, 36.00),
(1, 11, 25.00, 3, 75.00),
(1, 23, 18.00, 1, 18.00),
-- Venta 2  
(2, 5, 32.00, 2, 64.00),
(2, 13, 50.00, 1, 50.00),
-- Venta 3
(3, 24, 15.00, 4, 60.00),
(3, 37, 15.00, 2, 30.00),
-- Venta 4
(4, 19, 50.00, 2, 100.00),
(4, 12, 35.00, 1, 35.00),
(4, 26, 25.00, 1, 25.00),
-- Venta 5
(5, 30, 50.00, 3, 150.00),
(5, 33, 38.00, 2, 76.00),
(5, 40, 25.00, 1, 25.00);

-- Actualizar existencias después de las ventas simuladas
UPDATE products SET existence = existence - 2 WHERE product_id = 1;
UPDATE products SET existence = existence - 3 WHERE product_id = 11;
UPDATE products SET existence = existence - 1 WHERE product_id = 23;
UPDATE products SET existence = existence - 2 WHERE product_id = 5;
UPDATE products SET existence = existence - 1 WHERE product_id = 13;
UPDATE products SET existence = existence - 4 WHERE product_id = 24;
UPDATE products SET existence = existence - 2 WHERE product_id = 37;
UPDATE products SET existence = existence - 2 WHERE product_id = 19;
UPDATE products SET existence = existence - 1 WHERE product_id = 12;
UPDATE products SET existence = existence - 1 WHERE product_id = 26;
UPDATE products SET existence = existence - 3 WHERE product_id = 30;
UPDATE products SET existence = existence - 2 WHERE product_id = 33;
UPDATE products SET existence = existence - 1 WHERE product_id = 40;

-- Insertar algunos pagos de crédito
INSERT INTO credit_payments (sale_id, client_id, payment_date, amount_paid, payment_method) VALUES
(4, 4, '2025-01-01 19:00:00', 50.00, 'cash'),
(4, 5, '2025-01-01 19:15:00', 25.50, 'cash');

-- Actualizar deudas de clientes
UPDATE clients SET debt = 150.00 WHERE client_id = 4;
UPDATE clients SET debt = 75.50 WHERE client_id = 5;
UPDATE clients SET debt = 220.00 WHERE client_id = 6;
UPDATE clients SET debt = 89.75 WHERE client_id = 8;

-- Crear algunos cierres de caja de ejemplo
INSERT INTO cash_closures (employee_id, start_datetime, end_datetime, total_cash, total_card, total_credit, notes) VALUES
(2, '2025-01-01 08:00:00', '2025-01-01 16:00:00', 580.95, 268.25, 150.00, 'Turno matutino - Sin novedades'),
(3, '2025-01-01 16:00:00', '2025-01-01 20:00:00', 329.05, 0.00, 0.00, 'Turno vespertino - Cierre normal');

-- ====== INSTRUCCIONES DE USO ======
-- 1. Ejecutar este archivo después de crear la estructura de la base de datos
-- 2. Usuarios por defecto:
--    - admin / Admin123! (Administrador)
--    - cajero1 / Admin123! (Cajero)
--    - cajero2 / Admin123! (Cajero)
-- 3. Los códigos de barras siguen el formato estándar mexicano EAN-13
-- 4. Los precios están en pesos mexicanos
-- 5. Algunos productos tienen stock bajo intencionalmente para probar alertas