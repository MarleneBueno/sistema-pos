<?php
/**
 * Sistema de Autenticación y Autorización
 * Maneja login, logout y control de roles (admin/seller)
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        
        // Iniciar sesión si no está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Autenticar usuario
     */
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare(
                "SELECT employee_id, first_name, last_name, username, role, password, active 
                 FROM employees 
                 WHERE username = ? AND active = 1"
            );
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verificar contraseña (usando password_verify para bcrypt)
                if (password_verify($password, $user['password'])) {
                    // Crear sesión
                    $_SESSION['user_id'] = $user['employee_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    return [
                        'success' => true,
                        'message' => 'Login exitoso',
                        'role' => $user['role']
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Usuario o contraseña incorrectos'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado o inactivo'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error del sistema: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        return true;
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Obtener información del usuario actual
     */
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    /**
     * Verificar si es administrador
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }
    
    /**
     * Verificar si es vendedor
     */
    public function isSeller() {
        return $this->hasRole('seller');
    }
    
    /**
     * Middleware para proteger páginas que requieren autenticación
     */
    public function requireAuth($redirectTo = 'login.php') {
        if (!$this->isLoggedIn()) {
            header("Location: $redirectTo");
            exit();
        }
    }
    
    /**
     * Middleware para proteger páginas que requieren rol de administrador
     */
    public function requireAdmin($redirectTo = 'dashboard.php') {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            header("Location: $redirectTo");
            exit();
        }
    }
    
    /**
     * Crear hash de contraseña segura
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Validar fuerza de contraseña según RF-001
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Mínimo 8 caracteres";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Al menos una letra mayúscula";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Al menos una letra minúscula";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Al menos un número";
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Al menos un carácter especial";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

// Instancia global de autenticación
$auth = new Auth();
?>