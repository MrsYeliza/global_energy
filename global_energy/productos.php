<?php
// productos.php
require 'includes/conexion.php';
require 'includes/auth.php';
verificarLogin();

$paginaActiva = 'productos';
$tituloPagina = 'Productos';

$productos = $pdo->query("
    SELECT p.*, COUNT(v.id) AS total_ventas
    FROM productos p
    LEFT JOIN ventas v ON v.producto_id = p.id
    GROUP BY p.id
    ORDER BY p.tipo, p.marca
")->fetchAll();

$labelTipo = [
    'refrigerador'       => ['text'=>'Refrigerador',       'class'=>'badge-blue'],
    'lavadora'           => ['text'=>'Lavadora',           'class'=>'badge-purple'],
    'aire_acondicionado' => ['text'=>'Aire acondicionado', 'class'=>'badge-amber'],
];

require 'includes/header.php';
?>
<div class="topbar">
    <div>
        <div class="page-title">Productos</div>
        <div class="page-subtitle">Inventario de electrodomésticos</div>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Marca / Modelo</th>
                <th>Núm. Serie</th>
                <th>Precio</th>
                <th>Garantía</th>
                <th>Stock</th>
                <th>Ventas</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($productos as $p):
            $t = $labelTipo[$p['tipo']] ?? ['text'=>$p['tipo'],'class'=>'badge-gray'];
        ?>
            <tr>
                <td><span class="badge <?= $t['class'] ?>"><?= $t['text'] ?></span></td>
                <td>
                    <div class="td-name"><?= htmlspecialchars($p['marca'] . ' ' . $p['modelo']) ?></div>
                    <div style="font-size:11.5px;color:var(--gray-muted)"><?= htmlspecialchars($p['capacidad'] ?: '') ?> <?= htmlspecialchars($p['color'] ?: '') ?></div>
                </td>
                <td style="font-family:monospace;font-size:12px;color:var(--gray-muted)"><?= htmlspecialchars($p['num_serie'] ?: '—') ?></td>
                <td style="font-weight:500;color:var(--text-dark)">$<?= number_format($p['precio'], 0) ?></td>
                <td><?= $p['garantia_meses'] ?> meses</td>
                <td>
                    <span class="badge <?= $p['stock'] > 0 ? 'badge-green' : 'badge-red' ?>">
                        <?= $p['stock'] ?> <?= $p['stock'] == 1 ? 'unidad' : 'unidades' ?>
                    </span>
                </td>
                <td><?= $p['total_ventas'] ?> venta<?= $p['total_ventas'] != 1 ? 's' : '' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require 'includes/footer.php'; ?>
