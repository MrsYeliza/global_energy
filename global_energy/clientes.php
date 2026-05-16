<?php
// clientes.php
require 'includes/conexion.php';
require 'includes/auth.php';
verificarLogin();

$paginaActiva = 'clientes';
$tituloPagina = 'Clientes';

$busqueda = sanitizar($_GET['q'] ?? '');
$where  = $busqueda ? "WHERE nombre LIKE :q OR telefono LIKE :q" : '';
$params = $busqueda ? [':q' => "%$busqueda%"] : [];

$stmt = $pdo->prepare("
    SELECT c.*, COUNT(v.id) AS total_ventas
    FROM clientes c
    LEFT JOIN ventas v ON v.cliente_id = c.id
    $where
    GROUP BY c.id
    ORDER BY c.nombre
");
$stmt->execute($params);
$clientes = $stmt->fetchAll();

function iniciales($n) {
    $p = explode(' ', trim($n));
    return strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):''));
}

require 'includes/header.php';
?>
<div class="topbar">
    <div>
        <div class="page-title">Clientes</div>
        <div class="page-subtitle"><?= count($clientes) ?> clientes registrados</div>
    </div>
</div>

<form method="GET">
    <div class="toolbar">
        <div class="search-wrap">
            <i class="ti ti-search search-icon"></i>
            <input type="text" name="q" placeholder="Buscar cliente o teléfono..."
                   value="<?= htmlspecialchars($busqueda) ?>">
        </div>
        <button type="submit" class="btn btn-outline"><i class="ti ti-search"></i> Buscar</button>
        <?php if ($busqueda): ?>
            <a href="clientes.php" class="btn btn-ghost"><i class="ti ti-x"></i> Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th style="width:36px"></th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Ventas</th>
                <th>Registro</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($clientes)): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--gray-muted)">
                Sin clientes<?= $busqueda ? " para \"$busqueda\"" : '' ?>
            </td></tr>
        <?php else: ?>
            <?php foreach ($clientes as $c): ?>
            <tr>
                <td><div class="avatar"><?= iniciales($c['nombre']) ?></div></td>
                <td class="td-name"><?= htmlspecialchars($c['nombre']) ?></td>
                <td><?= htmlspecialchars($c['telefono'] ?: '—') ?></td>
                <td>
                    <?php if ($c['total_ventas'] > 0): ?>
                        <span class="badge badge-purple"><?= $c['total_ventas'] ?> venta<?= $c['total_ventas']!=1?'s':'' ?></span>
                    <?php else: ?>
                        <span style="color:var(--gray-muted);font-size:12px">Sin ventas</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--gray-muted);font-size:12.5px"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require 'includes/footer.php'; ?>
