<?php
/**
 * Script para generar y actualizar contraseñas correctas
 * Ejecutar este archivo UNA VEZ para arreglar las contraseñas
 */

require_once 'config/database.php';

echo "<h2>🔧 Actualizando Contraseñas del Sistema</h2>";

// Contraseñas que queremos establecer
$passwords = [
    'admin' => 'Admin123!',
    'cajero1' => 'Admin123!', 
    'cajero2' => 'Admin123!'
];

try {
    foreach ($passwords as $username => $password) {
        // Generar hash correcto
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Actualizar en la base de datos
        $stmt = $db->prepare("UPDATE employees SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hashedPassword, $username);
        
        if ($stmt->execute()) {
            echo "<p>✅ Contraseña actualizada para <strong>$username</strong></p>";
            echo "<p>   └─ Usuario: <code>$username</code> | Contraseña: <code>$password</code></p>";
        } else {
            echo "<p>❌ Error actualizando $username: " . $db->getConnection()->error . "</p>";
        }
        
        $stmt->close();
    }
    
    echo "<br><h3>🎉 ¡Actualización Completada!</h3>";
    echo "<p><strong>Usuarios disponibles:</strong></p>";
    echo "<ul>";
    echo "<li><strong>admin</strong> / Admin123! (Administrador)</li>";
    echo "<li><strong>cajero1</strong> / Admin123! (Cajero)</li>";
    echo "<li><strong>cajero2</strong> / Admin123! (Cajero)</li>";
    echo "</ul>";
    
    echo "<p>🔒 <strong>Formato de contraseña:</strong> Mínimo 8 caracteres, mayúscula, minúscula, número y carácter especial</p>";
    echo "<p>🚀 <strong>Ahora puedes hacer login en:</strong> <a href='login.php'>login.php</a></p>";
    
    // Verificar que los usuarios existen
    echo "<br><h4>📋 Verificación de Usuarios:</h4>";
    $result = $db->query("SELECT username, role, active FROM employees ORDER BY username");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Usuario</th><th>Rol</th><th>Estado</th></tr>";
        
        while ($user = $result->fetch_assoc()) {
            $status = $user['active'] ? '✅ Activo' : '❌ Inactivo';
            $roleColor = $user['role'] === 'admin' ? '#007bff' : '#28a745';
            
            echo "<tr>";
            echo "<td><strong>{$user['username']}</strong></td>";
            echo "<td style='color: $roleColor;'>{$user['role']}</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ No se encontraron usuarios. Ejecuta el archivo database_seed.sql primero.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>🔍 <strong>Verifica:</strong></p>";
    echo "<ul>";
    echo "<li>Que la base de datos 'grocerystore' existe</li>";
    echo "<li>Que las tablas están creadas correctamente</li>";
    echo "<li>Que el archivo config/database.php tiene la configuración correcta</li>";
    echo "</ul>";
}

echo "<br><p><small>⚠️ Elimina este archivo después de ejecutarlo por seguridad.</small></p>";
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 40px; 
    background: #f8f9fa; 
}
table { 
    background: white; 
    padding: 10px; 
    border-radius: 8px; 
}
th, td { 
    padding: 10px; 
    text-align: left; 
}
code { 
    background: #e9ecef; 
    padding: 2px 6px; 
    border-radius: 3px; 
    font-family: monospace; 
}
</style>