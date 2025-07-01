<?php
require_once '../includes/auth.php';

// Solo administradores pueden acceder
$auth->requireAdmin();

$message = '';
$messageType = '';

// Procesar formulario de registro/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $firstName = trim($_POST['first_name']);
        $middleName = trim($_POST['middle_name']);
        $lastName = trim($_POST['last_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phoneNumber = trim($_POST['phone_number']);
        $role = $_POST['role'];
        $password = $_POST['password'];
        
        // Validar datos
        $errors = [];
        
        if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password)) {
            $errors[] = "Todos los campos obligatorios deben ser llenados";
        }
        
        // Validar contraseña según RF-001
        $passwordValidation = Auth::validatePassword($password);
        if (!$passwordValidation['valid']) {
            $errors = array_merge($errors, $passwordValidation['errors']);
        }
        
        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email inválido";
        }
        
        // Validar username único
        $stmt = $db->prepare("SELECT employee_id FROM employees WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "El nombre de usuario ya existe";
        }
        
        // Validar email único
        $stmt = $db->prepare("SELECT employee_id FROM employees WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "El email ya está registrado";
        }
        
        if (empty($errors)) {
            $hashedPassword = Auth::hashPassword($password);
            
            $stmt = $db->prepare("
                INSERT INTO employees (first_name, middle_name, last_name, username, email, phone_number, role, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssssss", $firstName, $middleName, $lastName, $username, $email, $phoneNumber, $role, $hashedPassword);
            
            if ($stmt->execute()) {
                $message = "Empleado registrado exitosamente";
                $messageType = "success";
            } else {
                $message = "Error al registrar empleado: " . $db->getConnection()->error;
                $messageType = "error";
            }
        } else {
            $message = implode(", ", $errors);
            $messageType = "error";
        }
    }
    
    if ($action === 'toggle_status') {
        $employeeId = intval($_POST['employee_id']);
        $newStatus = intval($_POST['new_status']);
        
        $stmt = $db->prepare("UPDATE employees SET active = ? WHERE employee_id = ?");
        $stmt->bind_param("ii", $newStatus, $employeeId);
        
        if ($stmt->execute()) {
            $message = $newStatus ? "Empleado activado" : "Empleado desactivado";
            $messageType = "success";
        } else {
            $message = "Error al cambiar estado del empleado";
            $messageType = "error";
        }
    }
}

// Obtener lista de empleados
$employees = $db->query("
    SELECT employee_id, first_name, middle_name, last_name, username, email, phone_number, role, active
    FROM employees 
    ORDER BY first_name, last_name
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados - Sistema POS</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Gestión de Empleados</h1>
            <div class="header-actions">
                <span>Bienvenido, <?= htmlspecialchars($auth->getCurrentUser()['full_name']) ?></span>
                <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Cerrar Sesión</a>
            </div>
        </header>

        <nav class="admin-nav">
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="employees.php" class="nav-item active">Empleados</a>
            <a href="products.php" class="nav-item">Productos</a>
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

            <!-- Formulario de Registro -->
            <div class="card">
                <div class="card-header">
                    <h2>Registrar Nuevo Empleado</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="employee-form">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">Nombre *</label>
                                <input type="text" id="first_name" name="first_name" required maxlength="50">
                            </div>
                            
                            <div class="form-group">
                                <label for="middle_name">Segundo Nombre</label>
                                <input type="text" id="middle_name" name="middle_name" maxlength="20">
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Apellido *</label>
                                <input type="text" id="last_name" name="last_name" required maxlength="20">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Usuario *</label>
                                <input type="text" id="username" name="username" required maxlength="20" pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guiones bajos">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required maxlength="50">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone_number">Teléfono</label>
                                <input type="tel" id="phone_number" name="phone_number" maxlength="10" pattern="[0-9]{10}" title="10 dígitos">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="role">Puesto *</label>
                                <select id="role" name="role" required>
                                    <option value="">Seleccionar puesto</option>
                                    <option value="admin">Administrador</option>
                                    <option value="seller">Cajero/Vendedor</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Contraseña *</label>
                                <input type="password" id="password" name="password" required>
                                <small class="form-help">
                                    Mínimo 8 caracteres, incluir mayúscula, minúscula, número y carácter especial
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Registrar Empleado</button>
                            <button type="reset" class="btn btn-secondary">Limpiar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Empleados -->
            <div class="card">
                <div class="card-header">
                    <h2>Lista de Empleados</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre Completo</th>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Puesto</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                <tr class="<?= $employee['active'] ? '' : 'inactive' ?>">
                                    <td><?= $employee['employee_id'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($employee['first_name']) ?>
                                        <?= htmlspecialchars($employee['middle_name']) ?>
                                        <?= htmlspecialchars($employee['last_name']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($employee['username']) ?></td>
                                    <td><?= htmlspecialchars($employee['email']) ?></td>
                                    <td><?= htmlspecialchars($employee['phone_number']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $employee['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                            <?= $employee['role'] === 'admin' ? 'Administrador' : 'Cajero' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $employee['active'] ? 'success' : 'danger' ?>">
                                            <?= $employee['active'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="inline-form" onsubmit="return confirm('¿Está seguro de cambiar el estado de este empleado?')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="employee_id" value="<?= $employee['employee_id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $employee['active'] ? '0' : '1' ?>">
                                            <button type="submit" class="btn btn-sm <?= $employee['active'] ? 'btn-warning' : 'btn-success' ?>">
                                                <?= $employee['active'] ? 'Desactivar' : 'Activar' ?>
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

    <script>
        // Validación en tiempo real de contraseña
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };
            
            // Crear o actualizar indicador visual
            let indicator = document.getElementById('password-strength');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'password-strength';
                indicator.className = 'password-strength';
                e.target.parentNode.appendChild(indicator);
            }
            
            const met = Object.values(requirements).filter(Boolean).length;
            const total = Object.keys(requirements).length;
            
            indicator.innerHTML = `
                <div class="strength-bar">
                    <div class="strength-fill" style="width: ${(met/total)*100}%"></div>
                </div>
                <small>Requisitos cumplidos: ${met}/${total}</small>
            `;
            
            indicator.className = `password-strength strength-${met < 3 ? 'weak' : met < 5 ? 'medium' : 'strong'}`;
        });

        // Auto-limpiar alertas
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>