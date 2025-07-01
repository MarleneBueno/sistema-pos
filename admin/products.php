<?php
require_once '../includes/auth.php';

// Solo administradores pueden acceder
$auth->requireAdmin();

$message = '';
$messageType = '';

// Procesar formulario de productos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $productName = trim($_POST['product_name']);
        $barcode = trim($_POST['barcode']);
        $purchasePrice = floatval($_POST['purchase_price']);
        $salePrice = floatval($_POST['sale_price']);
        $existence = intval($_POST['existence']);
        $supplierId = intval($_POST['supplier_id']);
        $productId = intval($_POST['product_id'] ?? 0);
        
        // Validaciones
        $errors = [];
        
        if (empty($productName)) {
            $errors[] = "El nombre del producto es obligatorio";
        }
        
        if (empty($barcode) || !preg_match('/^\d{8,13}$/', $barcode)) {
            $errors[] = "Código de barras debe tener entre 8 y 13 dígitos";
        }
        
        if ($purchasePrice <= 0) {
            $errors[] = "El precio de compra debe ser mayor a 0";
        }
        
        if ($salePrice <= 0) {
            $errors[] = "El precio de venta debe ser mayor a 0";
        }
        
        if ($salePrice <= $purchasePrice) {
            $errors[] = "El precio de venta debe ser mayor al precio de compra";
        }
        
        if ($existence < 0) {
            $errors[] = "La existencia no puede ser negativa";
        }
        
        if ($supplierId <= 0) {
            $errors[] = "Debe seleccionar un proveedor válido";
        }
        
        // Verificar código de barras único
        $barcodeQuery = $action === 'create' 
            ? "SELECT product_id FROM products WHERE barcode = ?"
            : "SELECT product_id FROM products WHERE barcode = ? AND product_id != ?";
            
        $stmt = $db->prepare($barcodeQuery);
        if ($action === 'create') {
            $stmt->bind_param("s", $barcode);
        } else {
            $stmt->bind_param("si", $barcode, $productId);
        }
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "El código de barras ya existe";
        }
        
        if (empty($errors)) {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO products (product_name, barcode, purchase_price, sale_price, existence, supplier_id) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssddii", $productName, $barcode, $purchasePrice, $salePrice, $existence, $supplierId);
                
                if ($stmt->execute()) {
                    $message = "Producto registrado exitosamente";
                    $messageType = "success";
                } else {
                    $message = "Error al registrar producto: " . $db->getConnection()->error;
                    $messageType = "error";
                }
            } else {
                $stmt = $db->prepare("
                    UPDATE products 
                    SET product_name = ?, barcode = ?, purchase_price = ?, sale_price = ?, existence = ?, supplier_id = ?
                    WHERE product_id = ?
                ");
                $stmt->bind_param("ssddiii", $productName, $barcode, $purchasePrice, $salePrice, $existence, $supplierId, $productId);
                
                if ($stmt->execute()) {
                    $message = "Producto actualizado exitosamente";
                    $messageType = "success";
                } else {
                    $message = "Error al actualizar producto: " . $db->getConnection()->error;
                    $messageType = "error";
                }
            }
        } else {
            $message = implode(", ", $errors);
            $messageType = "error";
        }
    }
    
    if ($action === 'delete') {
        $productId = intval($_POST['product_id']);
        
        // Verificar si el producto tiene ventas asociadas
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM sale_details WHERE product_id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $hasSales = $stmt->get_result()->fetch_assoc()['count'] > 0;
        
        if ($hasSales) {
            $message = "No se puede eliminar el producto porque tiene ventas asociadas";
            $messageType = "error";
        } else {
            $stmt = $db->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $productId);
            
            if ($stmt->execute()) {
                $message = "Producto eliminado exitosamente";
                $messageType = "success";
            } else {
                $message = "Error al eliminar producto";
                $messageType = "error";
            }
        }
    }
    
    if ($action === 'adjust_stock') {
        $productId = intval($_POST['product_id']);
        $adjustment = intval($_POST['adjustment']);
        $reason = trim($_POST['reason']);
        
        if (empty($reason)) {
            $message = "Debe especificar la razón del ajuste";
            $messageType = "error";
        } else {
            $stmt = $db->prepare("UPDATE products SET existence = existence + ? WHERE product_id = ?");
            $stmt->bind_param("ii", $adjustment, $productId);
            
            if ($stmt->execute()) {
                $message = "Stock ajustado exitosamente. Razón: " . htmlspecialchars($reason);
                $messageType = "success";
            } else {
                $message = "Error al ajustar stock";
                $messageType = "error";
            }
        }
    }
}

// Obtener filtros de búsqueda
$search = $_GET['search'] ?? '';
$supplerFilter = $_GET['supplier'] ?? '';
$stockFilter = $_GET['stock'] ?? '';

// Construir consulta con filtros
$whereConditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $whereConditions[] = "(p.product_name LIKE ? OR p.barcode LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

if (!empty($supplerFilter)) {
    $whereConditions[] = "p.supplier_id = ?";
    $params[] = intval($supplerFilter);
    $types .= 'i';
}

if ($stockFilter === 'low') {
    $whereConditions[] = "p.existence < 10";
} elseif ($stockFilter === 'out') {
    $whereConditions[] = "p.existence = 0";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Obtener productos con información del proveedor
$productsQuery = "
    SELECT p.*, s.supplier_name,
           CASE 
               WHEN p.existence = 0 THEN 'Sin Stock'
               WHEN p.existence < 10 THEN 'Stock Bajo'
               ELSE 'Stock Normal'
           END as stock_status
    FROM products p
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    $whereClause
    ORDER BY p.product_name
";

$stmt = $db->prepare($productsQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener proveedores para el select
$suppliers = $db->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetch_all(MYSQLI_ASSOC);

// Obtener producto para editar si se solicita
$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
}

$currentUser = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Sistema POS</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/products.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Gestión de Productos</h1>
            <div class="header-actions">
                <span>Bienvenido, <?= htmlspecialchars($currentUser['full_name']) ?></span>
                <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Cerrar Sesión</a>
            </div>
        </header>

        <nav class="admin-nav">
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="employees.php" class="nav-item">Empleados</a>
            <a href="products.php" class="nav-item active">Productos</a>
            <a href="sales.php" class="nav-item">Ventas</a>
            <a href="reports.php" class="nav-item">Reportes</a>
            <a href="clients.php" class="nav-item">Clientes</a>
        </nav>

        <main class="admin-content">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Filtros de Búsqueda -->
            <div class="card">
                <div class="card-header">
                    <h2>🔍 Filtros de Búsqueda</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="filters-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="search">Buscar por nombre o código:</label>
                                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nombre del producto o código de barras">
                            </div>
                            
                            <div class="filter-group">
                                <label for="supplier">Proveedor:</label>
                                <select id="supplier" name="supplier">
                                    <option value="">Todos los proveedores</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['supplier_id'] ?>" <?= $supplerFilter == $supplier['supplier_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($supplier['supplier_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="stock">Estado de Stock:</label>
                                <select id="stock" name="stock">
                                    <option value="">Todos</option>
                                    <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Stock Bajo (&lt;10)</option>
                                    <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Sin Stock</option>
                                </select>
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary">Buscar</button>
                                <a href="products.php" class="btn btn-secondary">Limpiar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Formulario de Producto -->
            <div class="card">
                <div class="card-header">
                    <h2><?= $editProduct ? '✏️ Editar Producto' : '➕ Agregar Nuevo Producto' ?></h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="product-form" id="productForm">
                        <input type="hidden" name="action" value="<?= $editProduct ? 'update' : 'create' ?>">
                        <?php if ($editProduct): ?>
                            <input type="hidden" name="product_id" value="<?= $editProduct['product_id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="product_name">Nombre del Producto *</label>
                                <input type="text" id="product_name" name="product_name" required maxlength="100" 
                                       value="<?= htmlspecialchars($editProduct['product_name'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="barcode">Código de Barras *</label>
                                <input type="text" id="barcode" name="barcode" required pattern="\d{8,13}" 
                                       title="Entre 8 y 13 dígitos" maxlength="13"
                                       value="<?= htmlspecialchars($editProduct['barcode'] ?? '') ?>">
                                <small class="form-help">Entre 8 y 13 dígitos numéricos</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="supplier_id">Proveedor *</label>
                                <select id="supplier_id" name="supplier_id" required>
                                    <option value="">Seleccionar proveedor</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['supplier_id'] ?>" 
                                                <?= ($editProduct['supplier_id'] ?? '') == $supplier['supplier_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($supplier['supplier_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="purchase_price">Precio de Compra *</label>
                                <input type="number" id="purchase_price" name="purchase_price" step="0.01" min="0.01" required
                                       value="<?= htmlspecialchars($editProduct['purchase_price'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="sale_price">Precio de Venta *</label>
                                <input type="number" id="sale_price" name="sale_price" step="0.01" min="0.01" required
                                       value="<?= htmlspecialchars($editProduct['sale_price'] ?? '') ?>">
                                <small class="margin-info" id="marginInfo"></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="existence">Existencia Inicial *</label>
                                <input type="number" id="existence" name="existence" min="0" required
                                       value="<?= htmlspecialchars($editProduct['existence'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?= $editProduct ? 'Actualizar Producto' : 'Registrar Producto' ?>
                            </button>
                            <button type="reset" class="btn btn-secondary">Limpiar</button>
                            <?php if ($editProduct): ?>
                                <a href="products.php" class="btn btn-warning">Cancelar Edición</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Productos -->
            <div class="card">
                <div class="card-header">
                    <h2>📦 Lista de Productos (<?= count($products) ?> encontrados)</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table products-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Proveedor</th>
                                    <th>P. Compra</th>
                                    <th>P. Venta</th>
                                    <th>Margen</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): 
                                    $margin = $product['sale_price'] > 0 ? 
                                        (($product['sale_price'] - $product['purchase_price']) / $product['purchase_price']) * 100 : 0;
                                ?>
                                <tr class="<?= $product['existence'] == 0 ? 'out-of-stock' : ($product['existence'] < 10 ? 'low-stock' : '') ?>">
                                    <td class="barcode"><?= htmlspecialchars($product['barcode']) ?></td>
                                    <td class="product-name"><?= htmlspecialchars($product['product_name']) ?></td>
                                    <td><?= htmlspecialchars($product['supplier_name']) ?></td>
                                    <td class="price">$<?= number_format($product['purchase_price'], 2) ?></td>
                                    <td class="price">$<?= number_format($product['sale_price'], 2) ?></td>
                                    <td class="margin"><?= number_format($margin, 1) ?>%</td>
                                    <td class="stock">
                                        <span class="stock-number"><?= $product['existence'] ?></span>
                                        <button type="button" class="btn-stock-adjust" data-id="<?= $product['product_id'] ?>" 
                                                data-name="<?= htmlspecialchars($product['product_name']) ?>">
                                            ⚙️
                                        </button>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $product['existence'] == 0 ? 'danger' : ($product['existence'] < 10 ? 'warning' : 'success') ?>">
                                            <?= $product['stock_status'] ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="products.php?edit=<?= $product['product_id'] ?>" class="btn btn-sm btn-primary" title="Editar">
                                            ✏️
                                        </a>
                                        <form method="POST" class="inline-form" onsubmit="return confirm('¿Está seguro de eliminar este producto?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Ajuste de Stock -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⚙️ Ajustar Stock</h3>
                <span class="modal-close">&times;</span>
            </div>
            <form method="POST" id="stockForm">
                <input type="hidden" name="action" value="adjust_stock">
                <input type="hidden" name="product_id" id="stockProductId">
                
                <div class="modal-body">
                    <p>Producto: <strong id="stockProductName"></strong></p>
                    
                    <div class="form-group">
                        <label for="adjustment">Ajuste de Stock:</label>
                        <input type="number" id="adjustment" name="adjustment" required 
                               placeholder="Positivo para aumentar, negativo para reducir">
                        <small class="form-help">Ejemplo: +10 para aumentar 10 unidades, -5 para reducir 5 unidades</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">Razón del Ajuste:</label>
                        <select id="reason" name="reason" required>
                            <option value="">Seleccionar razón</option>
                            <option value="Entrada de mercancía">Entrada de mercancía</option>
                            <option value="Merma por daño">Merma por daño</option>
                            <option value="Merma por vencimiento">Merma por vencimiento</option>
                            <option value="Merma por robo">Merma por robo</option>
                            <option value="Corrección de inventario">Corrección de inventario</option>
                            <option value="Devolución de cliente">Devolución de cliente</option>
                            <option value="Otro">Otro (especificar)</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Ajustar Stock</button>
                    <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/products.js"></script>
</body>
</html>