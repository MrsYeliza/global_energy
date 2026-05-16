<?php
// login.php
require 'includes/conexion.php';
require 'includes/auth.php';
iniciarSesionSegura();

if (!empty($_SESSION['usuario_id'])) {
    header('Location: ventas.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitizar($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario']    = $usuario;
            header('Location: ventas.php');
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    } else {
        $error = 'Por favor completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión – Global Energy B.C.</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <div class="logo-circle">⚡</div>
            <h1>Global Energy B.C.</h1>
            <p>Sistema de gestión de ventas</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" class="form-control"
                       placeholder="usuario@globalenergy.mx"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group" style="margin-bottom:24px">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px">
                <i class="ti ti-login"></i> Iniciar sesión
            </button>
        </form>

        <p style="text-align:center;margin-top:18px;font-size:12px;color:var(--gray-muted)">
            Demo: admin@globalenergy.mx / password
        </p>
    </div>
</div>
</body>
</html>
