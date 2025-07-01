<?php
require_once '../includes/auth.php';

// Solo administradores pueden acceder
$auth->requireAdmin();

$message = '';
$messageType = '';

// Procesar formulario de proveedores
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $supplierName = trim($_POST['supplier_name']);
        $phoneNumber = trim($_POST['phone_number']);
        $address = trim($_POST['address']);
        $supplierId = intval($_POST['supplier_id'] ?? 0);

        // Validaciones
        $errors = [];

        if (empty($supplierName)) {
            $errors[] = "El nombre del proveedor es obligatorio";
        }

        if (!empty($phoneNumber) && !preg_match('/^\d{10}$/', $phoneNumber)) {
            $errors[] = "El teléfono debe tener exactamente 10 dígitos";
        }

        if (empty($address)) {
            $errors[] = "La dirección es obligatoria";
        }

        // Verificar nombre único
        $nameQuery = $action === 'create' 
            ? "SELECT supplier_id FROM suppliers WHERE supplier_name = ?"
            : "SELECT supplier_id FROM suppliers WHERE supplier_name = ? AND supplier_id != ?";
            
        $stmt = $db->prepare($nameQuery);
        if ($action === 'create') {
            $stmt->bind_param("s", $supplierName);
        } else {
            $stmt->bind_param("si", $supplierName, $supplierId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Ya existe un proveedor con ese nombre";
        }

        if (empty($errors)) {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO suppliers (supplier_name, phone_number, address) 
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("sss", $supplierName, $phoneNumber, $address);

                if ($stmt->execute()) {
                    $message = "Proveedor registrado exitosamente";
                    $messageType = "success";
                } else {
                    $message = "Error al registrar proveedor: " . $db->error;
                    $messageType = "error";
                }
            } else {
                $stmt = $db->prepare("
                    UPDATE suppliers 
                    SET supplier_name = ?, phone_number = ?, address = ?
                    WHERE supplier_id = ?
                ");
                $stmt->bind_param("sssi", $supplierName, $phoneNumber, $address, $supplierId);

                if ($stmt->execute()) {
                    $message = "Proveedor actualizado exitosamente";
                    $messageType = "success";
                } else {
                    $message = "Error al actualizar proveedor: " . $db->error;
                    $messageType = "error";
                }
            }
        } else {
            $message = implode(", ", $errors);
            $messageType = "error";
        }
    }

    if ($action === 'delete') {
        $supplierId = intval($_POST['supplier_id'] ?? 0);

        if ($supplierId > 0) {
            $stmt = $db->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
            $stmt->bind_param("i", $supplierId);
            
            if ($stmt->execute()) {
                $message = "Proveedor eliminado exitosamente";
                $messageType = "success";
            } else {
                $message = "Error al eliminar proveedor: " . $db->error;
                $messageType = "error";
            }
        } else {
            $message = "ID de proveedor inválido para eliminar";
            $messageType = "error";
        }
    }
}

// Obtener lista de proveedores para mostrar en la vista
$suppliers = $db->query("SELECT * FROM suppliers ORDER BY supplier_name ASC")->fetch_all(MYSQLI_ASSOC);

function escape_js_string($str) {
    return str_replace(["\\", "'","\r","\n"], ["\\\\","\\'","",""], $str);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Gestión de Proveedores</title>
    <link rel="stylesheet" href="../assets/css/suppliers.css" />
</head>
<body>
    <h1>Proveedores</h1>

    <?php if ($message): ?>
        <div class="<?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario Crear/Editar -->
    <form method="post" action="" class="supplier-form">
        <input type="hidden" name="supplier_id" id="supplier_id" value="" />
        <input type="hidden" name="action" id="form_action" value="create" />

        <label for="supplier_name">Nombre:</label>
        <input type="text" name="supplier_name" id="supplier_name" required />

        <label for="phone_number">Teléfono (10 dígitos):</label>
        <input type="text" name="phone_number" id="phone_number" pattern="\d{10}" />

        <label for="address">Dirección:</label>
        <textarea name="address" id="address" required></textarea>

        <button type="submit">Guardar</button>
        <button type="button" onclick="resetForm()" style="background:#6c757d; margin-left:10px;">Cancelar</button>
    </form>

    <!-- Lista de proveedores -->
    <table class="suppliers-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><?= htmlspecialchars($supplier['supplier_name']) ?></td>
                    <td><?= htmlspecialchars($supplier['phone_number']) ?></td>
                    <td><?= htmlspecialchars($supplier['address']) ?></td>
                    <td class="actions">
                        <button
                            onclick="editSupplier(
                                <?= $supplier['supplier_id'] ?>, 
                                '<?= escape_js_string($supplier['supplier_name']) ?>', 
                                '<?= escape_js_string($supplier['phone_number']) ?>', 
                                '<?= escape_js_string($supplier['address']) ?>'
                            )"
                        >Editar</button>

                        <form method="post" action="" style="display:inline;" onsubmit="return confirm('¿Seguro que quieres eliminar este proveedor?');">
                            <input type="hidden" name="supplier_id" value="<?= $supplier['supplier_id'] ?>" />
                            <input type="hidden" name="action" value="delete" />
                            <button type="submit" style="background:#dc3545;">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<script>
function editSupplier(id, name, phone, address) {
    document.getElementById('supplier_id').value = id;
    document.getElementById('supplier_name').value = name;
    document.getElementById('phone_number').value = phone;
    document.getElementById('address').value = address;
    document.getElementById('form_action').value = 'update';
}

function resetForm() {
    document.getElementById('supplier_id').value = '';
    document.getElementById('supplier_name').value = '';
    document.getElementById('phone_number').value = '';
    document.getElementById('address').value = '';
    document.getElementById('form_action').value = 'create';
}
</script>

</body>
</html>
