<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

// Solo administradores pueden acceder
$auth->requireAdmin();

try {
    $sales = $db->query("
        SELECT s.sale_id, s.sale_date, s.sale_total, s.payment_method, s.sale_status,
               CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
               c.client_name
        FROM sales s
        LEFT JOIN employees e ON s.employee_id = e.employee_id
        LEFT JOIN clients c ON s.client_id = c.client_id
        ORDER BY s.sale_date DESC
    ")->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Error al cargar ventas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Ventas - Sistema POS</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .sales-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sales-table th, .sales-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .sales-table th {
            background-color: #f4f4f4;
        }

        .status-complete {
            color: green;
            font-weight: bold;
        }

        .status-processing {
            color: orange;
            font-weight: bold;
        }

        .method-cash {
            background: #d4edda;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .method-card {
            background: #d1ecf1;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .method-credit {
            background: #fff3cd;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .admin-header, .admin-nav {
            background-color: #333;
            color: white;
            padding: 10px 20px;
        }

        .admin-nav a {
            color: white;
            margin-right: 15px;
            text-decoration: none;
        }

        .admin-nav a.active {
            font-weight: bold;
            border-bottom: 2px solid white;
        }

        .admin-content {
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .card h2 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Historial de Ventas</h1>
        <div class="header-actions">
            <span>Bienvenido, <?= htmlspecialchars($auth->getCurrentUser()['full_name']) ?></span>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            <a href="../logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </div>

    <div class="admin-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="employees.php">Empleados</a>
        <a href="products.php">Productos</a>
        <a href="sales.php" class="active">Ventas</a>
        <a href="reports.php">Reportes</a>
        <a href="clients.php">Clientes</a>
    </div>

    <main class="admin-content">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php else: ?>
            <div class="card">
                <h2>Ventas Registradas</h2>
                <?php if (empty($sales)): ?>
                    <p>No se encontraron ventas registradas.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="sales-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Cajero</th>
                                    <th>Total</th>
                                    <th>Pago</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td>#<?= $sale['sale_id'] ?></td>
                                        <td><?= date("d/m/Y H:i", strtotime($sale['sale_date'])) ?></td>
                                        <td><?= htmlspecialchars($sale['client_name'] ?? 'Público General') ?></td>
                                        <td><?= htmlspecialchars($sale['employee_name']) ?></td>
                                        <td>$<?= number_format($sale['sale_total'], 2) ?></td>
                                        <td>
                                            <span class="method-<?= $sale['payment_method'] ?>">
                                                <?= ucfirst($sale['payment_method']) ?>
                                            </span>
                                        </td>
                                        <td class="status-<?= $sale['sale_status'] ?>">
                                            <?= ucfirst($sale['sale_status']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
