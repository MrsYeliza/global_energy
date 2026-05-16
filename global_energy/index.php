<?php
// index.php - Redirige al login o a ventas si ya hay sesión
require 'includes/auth.php';
iniciarSesionSegura();
if (!empty($_SESSION['usuario_id'])) {
    header('Location: ventas.php');
} else {
    header('Location: login.php');
}
exit;
?>
