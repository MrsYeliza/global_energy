<?php
// ventas.php - Lista de ventas (imagen 1 del mockup)
require 'includes/conexion.php';
require 'includes/auth.php';
verificarLogin();

$paginaActiva  = 'ventas';
$tituloPagina  = 'Ventas';

// Filtros
$busqueda = sanitizar($_GET['q'] ?? '');
$filtroEstatus = sanitizar($_GET['estatus'] ?? '');
$filtroMes     = sanitizar($_GET['mes'] ?? '');

// Query principal
$where = ['1=1'];
$params = [];

if ($busqueda) {
    $where[] = "(c.nombre LIKE :q OR v.num_venta LIKE :q OR p.tipo LIKE :q)";
    $params[':q'] = "%$busqueda%";
}
if ($filtroEstatus) {
    $where[] = "v.estatus = :estatus";
    $params[':estatus'] = $filtroEstatus;
}
if ($filtroMes) {
    $where[] = "DATE_FORMAT(v.fecha_venta, '%Y-%m') = :mes";
    $params[':mes'] = $filtroMes;
}

$sql = "SELECT v.id, v.num_venta, v.fecha_venta, v.estatus,
               c.nombre AS cliente,
               p.tipo AS producto, p.marca,
               cr.monto_total, cr.estatus AS estatus_credito
        FROM ventas v
        JOIN clientes c  ON c.id = v.cliente_id
        JOIN productos p ON p.id = v.producto_id
        LEFT JOIN creditos cr ON cr.venta_id = v.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY v.fecha_venta DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll();

// Estadísticas rápidas
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total_ventas,
        SUM(cr.monto_total) AS monto_total,
        COUNT(CASE WHEN v.estatus = 'activa' THEN 1 END) AS activas,
        COUNT(CASE WHEN v.estatus = 'entregada' THEN 1 END) AS entregadas
    FROM ventas v
    LEFT JOIN creditos cr ON cr.venta_id = v.id
")->fetch();

$labelEstatus = [
    'activa'    => ['text' => 'Activa',    'class' => 'badge-purple'],
    'entregada' => ['text' => 'Entregada', 'class' => 'badge-green'],
    'cancelada' => ['text' => 'Cancelada', 'class' => 'badge-red'],
];

$labelProducto = [
    'refrigerador'       => 'Refrigerador',
    'lavadora'           => 'Lavadora',
    'aire_acondicionado' => 'Aire acondicionado',
];

function iniciales($nombre) {
    $partes = explode(' ', trim($nombre));
    return strtoupper(substr($partes[0], 0, 1) . (isset($partes[1]) ? substr($partes[1], 0, 1) : ''));
}

require 'includes/header.php';
?>

<!-- Topbar -->
<div class="topbar">
    <div>
        <div class="page-title">Ventas</div>
        <div class="page-subtitle">Registro de ventas de Global Energy B.C.</div>
    </div>
    <a href="agregar_venta.php" class="btn btn-primary">
        <i class="ti ti-plus"></i> Agregar venta
    </a>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Total ventas</div>
        <div class="stat-value purple"><?= $stats['total_ventas'] ?></div>
        <div class="stat-sub">Registradas en el sistema</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Monto total</div>
        <div class="stat-value">$<?= number_format($stats['monto_total'] ?? 0, 0) ?></div>
        <div class="stat-sub">En créditos</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Activas</div>
        <div class="stat-value"><?= $stats['activas'] ?></div>
        <div class="stat-sub">En proceso</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Entregadas</div>
        <div class="stat-value"><?= $stats['entregadas'] ?></div>
        <div class="stat-sub">Completadas</div>
    </div>
</div>

<!-- Toolbar: búsqueda + filtros -->
<form method="GET" action="ventas.php">
    <div class="toolbar">
        <div class="search-wrap">
            <i class="ti ti-search search-icon"></i>
            <input type="text" name="q" placeholder="Buscar por cliente, número de venta o producto..."
                   value="<?= htmlspecialchars($busqueda) ?>">
        </div>
        <select name="estatus" class="form-control" style="width:140px;border-radius:30px">
            <option value="">Todos</option>
            <option value="activa"    <?= $filtroEstatus === 'activa'    ? 'selected' : '' ?>>Activa</option>
            <option value="entregada" <?= $filtroEstatus === 'entregada' ? 'selected' : '' ?>>Entregada</option>
            <option value="cancelada" <?= $filtroEstatus === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
        </select>
        <input type="month" name="mes" class="form-control" style="width:150px;border-radius:30px"
               value="<?= htmlspecialchars($filtroMes) ?>">
        <button type="submit" class="btn btn-outline">
            <i class="ti ti-filter"></i> Filtros
        </button>
        <?php if ($busqueda || $filtroEstatus || $filtroMes): ?>
            <a href="ventas.php" class="btn btn-ghost">
                <i class="ti ti-x"></i> Limpiar
            </a>
        <?php endif; ?>
    </div>
</form>

<!-- Tabla de ventas -->
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th style="width:36px"></th>
                <th>Nombre cliente</th>
                <th>Núm. Venta</th>
                <th>Producto</th>
                <th>Fecha de venta</th>
                <th>Monto</th>
                <th>Estatus</th>
                <th style="width:80px"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ventas)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:var(--gray-muted)">
                        <i class="ti ti-inbox" style="font-size:32px;display:block;margin-bottom:8px;opacity:.4"></i>
                        No se encontraron ventas
                        <?= $busqueda ? "para \"$busqueda\"" : '' ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($ventas as $v): ?>
                    <tr onclick="window.location='detalle_venta.php?id=<?= $v['id'] ?>'">
                        <td>
                            <div class="avatar"><?= iniciales($v['cliente']) ?></div>
                        </td>
                        <td>
                            <div class="td-name"><?= htmlspecialchars($v['cliente']) ?></div>
                        </td>
                        <td style="font-family:monospace;font-size:12.5px;color:var(--purple-main)">
                            <?= htmlspecialchars($v['num_venta']) ?>
                        </td>
                        <td>
                            <span class="badge badge-purple">
                                <?= $labelProducto[$v['producto']] ?? $v['producto'] ?>
                            </span>
                            <div style="font-size:11.5px;color:var(--gray-muted);margin-top:2px">
                                <?= htmlspecialchars($v['marca']) ?>
                            </div>
                        </td>
                        <td style="color:var(--gray-muted)">
                            <?= date('d/m/Y', strtotime($v['fecha_venta'])) ?>
                        </td>
                        <td style="font-weight:500;color:var(--text-dark)">
                            <?= $v['monto_total'] ? '$' . number_format($v['monto_total'], 0) : '—' ?>
                        </td>
                        <td>
                            <?php $e = $labelEstatus[$v['estatus']] ?? ['text'=>$v['estatus'],'class'=>'badge-gray']; ?>
                            <span class="badge <?= $e['class'] ?>"><?= $e['text'] ?></span>
                        </td>
                        <td onclick="event.stopPropagation()" style="display:flex;gap:4px;padding:8px 12px">
                            <a href="detalle_venta.php?id=<?= $v['id'] ?>"
                               class="btn btn-ghost btn-sm btn-icon" title="Ver detalle">
                                <i class="ti ti-eye"></i>
                            </a>
                            <a href="agregar_venta.php?editar=<?= $v['id'] ?>"
                               class="btn btn-ghost btn-sm btn-icon" title="Editar">
                                <i class="ti ti-edit"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<p style="font-size:12px;color:var(--gray-muted);margin-top:10px;text-align:right">
    <?= count($ventas) ?> resultado<?= count($ventas) !== 1 ? 's' : '' ?>
</p>

<?php require 'includes/footer.php'; ?>
