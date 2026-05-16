<?php
// includes/header.php
// Requiere: $paginaActiva (string) y $tituloPagina (string)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Global Energy') ?> – Global Energy B.C.</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
</head>
<body>
<div class="app-shell">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">⚡</div>
            <h2>Global Energy</h2>
            <span>B.C. Sistema de ventas</span>
        </div>

        <span class="nav-section">Principal</span>
        <a href="ventas.php" class="nav-item <?= ($paginaActiva === 'ventas') ? 'active' : '' ?>">
            <i class="ti ti-shopping-cart nav-icon"></i> Ventas
        </a>
        <a href="agregar_venta.php" class="nav-item <?= ($paginaActiva === 'agregar') ? 'active' : '' ?>">
            <i class="ti ti-plus nav-icon"></i> Nueva venta
        </a>
        <a href="clientes.php" class="nav-item <?= ($paginaActiva === 'clientes') ? 'active' : '' ?>">
            <i class="ti ti-users nav-icon"></i> Clientes
        </a>

        <span class="nav-section">Gestión</span>
        <a href="productos.php" class="nav-item <?= ($paginaActiva === 'productos') ? 'active' : '' ?>">
            <i class="ti ti-package nav-icon"></i> Productos
        </a>
        <a href="creditos.php" class="nav-item <?= ($paginaActiva === 'creditos') ? 'active' : '' ?>">
            <i class="ti ti-credit-card nav-icon"></i> Créditos
        </a>
        <a href="reportes.php" class="nav-item <?= ($paginaActiva === 'reportes') ? 'active' : '' ?>">
            <i class="ti ti-chart-bar nav-icon"></i> Reportes
        </a>

        <div class="sidebar-footer">
            <div class="nav-item" style="cursor:default;font-size:12.5px;color:var(--gray-muted);gap:8px">
                <i class="ti ti-user-circle" style="font-size:18px"></i>
                <div>
                    <div style="color:var(--text-dark);font-weight:500;font-size:13px">
                        <?= htmlspecialchars(usuarioActual()['nombre'] ?? 'Usuario') ?>
                    </div>
                    <div style="font-size:11px"><?= htmlspecialchars(usuarioActual()['rol'] ?? '') ?></div>
                </div>
            </div>
            <a href="logout.php" class="nav-item" style="color:var(--danger)">
                <i class="ti ti-logout nav-icon"></i> Cerrar sesión
            </a>
        </div>
    </aside>

    <!-- Main -->
    <main class="main-content">
