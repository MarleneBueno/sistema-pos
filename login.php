<?php
require_once 'includes/auth.php';

// Si ya está logueado, redirigir al dashboard
if ($auth->isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: " . ($role === 'admin' ? 'admin/dashboard.php' : 'seller/dashboard.php'));
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña';
    } else {
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            $success = $result['message'];
            // Redirigir según el rol
            $role = $result['role'];
            header("refresh:1;url=" . ($role === 'admin' ? 'admin/dashboard.php' : 'seller/dashboard.php'));
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema POS Abarrotes</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="assets/images/logo.png" alt="Logo" class="logo" onerror="this.style.display='none'">
                <h1>Sistema POS</h1>
                <p>Abarrotes El Buen Precio</p>
            </div>
            
            <form method="POST" class="login-form" autocomplete="off">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?= htmlspecialchars($username ?? '') ?>"
                        required 
                        autocomplete="username"
                        placeholder="Ingrese su usuario"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="Ingrese su contraseña"
                    >
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">⚠️</span>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon">✅</span>
                        <?= htmlspecialchars($success) ?> Redirigiendo...
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="btn-login">
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="login-footer">
                <p>&copy; <?= date('Y') ?> Sistema POS Abarrotes</p>
                <small>Versión 1.0</small>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-focus en el campo de usuario
        document.getElementById('username').focus();
        
        // Limpiar mensajes después de 5 segundos
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