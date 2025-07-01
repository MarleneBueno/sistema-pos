<?php
require_once '../includes/auth.php';

// Solo administradores pueden acceder
$auth->requireAdmin();

// Obtener estadísticas del sistema
try {
    // Total de empleados activos
    $totalEmployees = $db->query("SELECT COUNT(*) as count FROM employees WHERE active = 1")->fetch_assoc()['count'];
    
    // Total de productos
    $totalProducts = $db->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
    
    // Total de clientes
    $totalClients = $db->query("SELECT COUNT(*) as count FROM clients")->fetch_assoc()['count'];
    
    // Ventas del día
    $todaySales = $db->query("
        SELECT COUNT(*) as count, COALESCE(SUM(sale_total), 0) as total 
        FROM sales 
        WHERE DATE(sale_date) = CURDATE()
    ")->fetch_assoc();
    
    // Ventas del mes
    $monthSales = $db->query("
        SELECT COUNT(*) as count, COALESCE(SUM(sale_total), 0) as total 
        FROM sales 
        WHERE YEAR(sale_date) = YEAR(CURDATE()) AND MONTH(sale_date) = MONTH(CURDATE())
    ")->fetch_assoc();
    
    // Productos con stock bajo (menos de 10)
    $lowStockProducts = $db->query("
        SELECT product_name, existence 
        FROM products 
        WHERE existence < 10 
        ORDER BY existence ASC 
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Últimas ventas
    $recentSales = $db->query("
        SELECT s.sale_id, s.sale_date, s.sale_total, s.payment_method,
               CONCAT(e.first_name, ' ', e.last_name) as employee_name,
               c.client_name
        FROM sales s
        LEFT JOIN employees e ON s.employee_id = e.employee_id
        LEFT JOIN clients c ON s.client_id = c.client_id
        ORDER BY s.sale_date DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Deudas pendientes
    $pendingDebts = $db->query("
        SELECT client_name, debt 
        FROM clients 
        WHERE debt > 0 
        ORDER BY debt DESC 
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $error = "Error al cargar estadísticas: " . $e->getMessage();
}

$currentUser = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - Sistema POS</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="header-left">
                <h1>Dashboard Administrativo</h1>
                <span class="current-date"><?= date('l, d F Y') ?></span>
            </div>
            <div class="header-actions">
                <span>Bienvenido, <?= htmlspecialchars($currentUser['full_name']) ?></span>
                <a href="../logout.php" class="btn btn-danger">Cerrar Sesión</a>
            </div>
        </header>

        <nav class="admin-nav">
            <a href="dashboard.php" class="nav-item active">Dashboard</a>
            <a href="employees.php" class="nav-item">Empleados</a>
            <a href="products.php" class="nav-item">Productos</a>
            <a href="sales.php" class="nav-item">Ventas</a>
            <a href="reports.php" class="nav-item">Reportes</a>
            <a href="clients.php" class="nav-item">Clientes</a>
        </nav>

        <main class="admin-content">
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas Principales -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?= $totalEmployees ?></h3>
                        <p>Empleados Activos</p>
                    </div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-icon">📦</div>
                    <div class="stat-content">
                        <h3><?= $totalProducts ?></h3>
                        <p>Productos</p>
                    </div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-content">
                        <h3><?= $totalClients ?></h3>
                        <p>Clientes</p>
                    </div>
                </div>

                <div class="stat-card stat-warning">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3>$<?= number_format($todaySales['total'], 2) ?></h3>
                        <p>Ventas Hoy (<?= $todaySales['count'] ?>)</p>
                    </div>
                </div>
            </div>

            <!-- Contenido Principal -->
            <div class="dashboard-grid">
                <!-- Panel Izquierdo -->
                <div class="dashboard-left">
                    <!-- Ventas del Mes -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Resumen del Mes</h2>
                        </div>
                        <div class="card-body">
                            <div class="month-summary">
                                <div class="summary-item">
                                    <span class="summary-label">Total Ventas:</span>
                                    <span class="summary-value">$<?= number_format($monthSales['total'], 2) ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Número de Ventas:</span>
                                    <span class="summary-value"><?= $monthSales['count'] ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Promedio por Venta:</span>
                                    <span class="summary-value">
                                        $<?= $monthSales['count'] > 0 ? number_format($monthSales['total'] / $monthSales['count'], 2) : '0.00' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Últimas Ventas -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Últimas Ventas</h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentSales)): ?>
                                <p class="no-data">No hay ventas registradas</p>
                            <?php else: ?>
                                <div class="sales-list">
                                    <?php foreach ($recentSales as $sale): ?>
                                        <div class="sale-item">
                                            <div class="sale-info">
                                                <strong>#<?= $sale['sale_id'] ?></strong>
                                                <span class="sale-date"><?= date('H:i', strtotime($sale['sale_date'])) ?></span>
                                            </div>
                                            <div class="sale-details">
                                                <div>Cliente: <?= htmlspecialchars($sale['client_name'] ?? 'Público General') ?></div>
                                                <div>Cajero: <?= htmlspecialchars($sale['employee_name']) ?></div>
                                            </div>
                                            <div class="sale-amount">
                                                $<?= number_format($sale['sale_total'], 2) ?>
                                                <span class="payment-method <?= $sale['payment_method'] ?>">
                                                    <?= ucfirst($sale['payment_method']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Panel Derecho -->
                <div class="dashboard-right">
                    <!-- Productos con Stock Bajo -->
                    <div class="card alert-card">
                        <div class="card-header">
                            <h2>⚠️ Stock Bajo</h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($lowStockProducts)): ?>
                                <p class="no-data success">✅ Todos los productos tienen stock suficiente</p>
                            <?php else: ?>
                                <div class="stock-alerts">
                                    <?php foreach ($lowStockProducts as $product): ?>
                                        <div class="stock-item">
                                            <span class="product-name"><?= htmlspecialchars($product['product_name']) ?></span>
                                            <span class="stock-count danger"><?= $product['existence'] ?> unidades</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <a href="products.php" class="btn btn-warning btn-sm">Ver Todos los Productos</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Deudas Pendientes -->
                    <div class="card">
                        <div class="card-header">
                            <h2>💳 Deudas Pendientes</h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pendingDebts)): ?>
                                <p class="no-data success">✅ No hay deudas pendientes</p>
                            <?php else: ?>
                                <div class="debt-list">
                                    <?php foreach ($pendingDebts as $debt): ?>
                                        <div class="debt-item">
                                            <span class="client-name"><?= htmlspecialchars($debt['client_name']) ?></span>
                                            <span class="debt-amount">$<?= number_format($debt['debt'], 2) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <a href="clients.php" class="btn btn-info btn-sm">Gestionar Clientes</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Accesos Rápidos -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Accesos Rápidos</h2>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="products.php" class="quick-action">
                                    <span class="action-icon">📦</span>
                                    <span>Gestionar Productos</span>
                                </a>
                                <a href="employees.php" class="quick-action">
                                    <span class="action-icon">👥</span>
                                    <span>Empleados</span>
                                </a>
                                <a href="reports.php" class="quick-action">
                                    <span class="action-icon">📊</span>
                                    <span>Generar Reportes</span>
                                </a>
                                <a href="../seller/pos.php" class="quick-action primary">
                                    <span class="action-icon">🛒</span>
                                    <span>Punto de Venta</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Función para actualizar fecha y hora
        function updateDate() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };

            const fechaFormateada = now.toLocaleDateString('es-ES', options);
            document.querySelector('.current-date').textContent = fechaFormateada;
        }

        // Mostrar fecha al cargar
        updateDate();

        // Actualizar fecha y hora cada minuto
        setInterval(updateDate, 60000);

        // Auto-refresh de estadísticas cada 5 minutos
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>

</body>
</html>