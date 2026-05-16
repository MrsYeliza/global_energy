<?php
// ============================================
// includes/auth.php
// Funciones de sesión y autenticación
// ============================================

function iniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 3600,
            'path'     => '/',
            'secure'   => false, // Cambiar a true en producción con HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

function verificarLogin() {
    iniciarSesionSegura();
    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

function usuarioActual() {
    return $_SESSION['usuario'] ?? null;
}

function cerrarSesion() {
    iniciarSesionSegura();
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

function sanitizar($valor) {
    return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
}

function generarNumVenta($pdo) {
    $anio = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM ventas WHERE YEAR(fecha_venta) = $anio");
    $total = $stmt->fetchColumn();
    return 'GE-' . $anio . '-' . str_pad($total + 1, 3, '0', STR_PAD_LEFT);
}
?>
