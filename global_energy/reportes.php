<?php
// reportes.php
require 'includes/conexion.php';
require 'includes/auth.php';
verificarLogin();

$paginaActiva = 'reportes';
$tituloPagina = 'Reportes';

// Ventas por mes (últimos 6 meses)
$ventasPorMes = $pdo->query("
    SELECT DATE_FORMAT(fecha_venta, '%Y-%m') AS mes,
           DATE_FORMAT(fecha_venta, '%b %Y') AS mes_label,
           COUNT(*) AS total,
           SUM(cr.monto_total) AS monto
    FROM ventas v
    LEFT JOIN creditos cr ON cr.venta_id = v.id
    WHERE fecha_venta >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha_venta, '%Y-%m')
    ORDER BY mes
")->fetchAll();

// Ventas por tipo de producto
$ventasPorTipo = $pdo->query("
    SELECT p.tipo, COUNT(v.id) AS total, SUM(cr.monto_total) AS monto
    FROM ventas v
    JOIN productos p ON p.id = v.producto_id
    LEFT JOIN creditos cr ON cr.venta_id = v.id
    GROUP BY p.tipo
")->fetchAll();

// Resumen general
$resumen = $pdo->query("
    SELECT
        COUNT(*) AS total_ventas,
        SUM(cr.monto_total) AS monto_total,
        COUNT(DISTINCT v.cliente_id) AS total_clientes,
        AVG(cr.monto_total) AS promedio_venta
    FROM ventas v
    LEFT JOIN creditos cr ON cr.venta_id = v.id
")->fetch();

$labelTipo = [
    'refrigerador'       => 'Refrigerador',
    'lavadora'           => 'Lavadora',
    'aire_acondicionado' => 'Aire acondicionado',
];

require 'includes/header.php';
?>
<div class="topbar">
    <div>
        <div class="page-title">Reportes</div>
        <div class="page-subtitle">Resumen general del negocio</div>
    </div>
</div>

<div class="stats-row" style="margin-bottom:28px">
    <div class="stat-card">
        <div class="stat-label">Total ventas</div>
        <div class="stat-value purple"><?= $resumen['total_ventas'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Monto total</div>
        <div class="stat-value">$<?= number_format($resumen['monto_total'] ?? 0, 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Clientes únicos</div>
        <div class="stat-value"><?= $resumen['total_clientes'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Promedio por venta</div>
        <div class="stat-value">$<?= number_format($resumen['promedio_venta'] ?? 0, 0) ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <div class="card">
        <h3 style="font-size:14px;font-weight:600;color:var(--text-dark);margin-bottom:16px">
            <i class="ti ti-chart-bar" style="color:var(--purple-main)"></i> Ventas por mes
        </h3>
        <?php if (empty($ventasPorMes)): ?>
            <p style="color:var(--gray-muted);font-size:13px">Sin datos</p>
        <?php else: ?>
            <?php
            $maxMonto = max(array_column($ventasPorMes, 'monto') ?: [1]);
            foreach ($ventasPorMes as $m):
                $pct = $maxMonto > 0 ? ($m['monto'] / $maxMonto * 100) : 0;
            ?>
                <div style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                        <span style="color:var(--text-body)"><?= htmlspecialchars($m['mes_label']) ?></span>
                        <span style="font-weight:500;color:var(--text-dark)">
                            <?= $m['total'] ?> venta<?= $m['total']!=1?'s':'' ?>
                            — $<?= number_format($m['monto'] ?? 0, 0) ?>
                        </span>
                    </div>
                    <div style="height:8px;background:var(--gray-input);border-radius:8px;overflow:hidden">
                        <div style="height:100%;width:<?= round($pct) ?>%;background:var(--purple-main);border-radius:8px;transition:width .3s"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3 style="font-size:14px;font-weight:600;color:var(--text-dark);margin-bottom:16px">
            <i class="ti ti-package" style="color:var(--purple-main)"></i> Ventas por producto
        </h3>
        <?php if (empty($ventasPorTipo)): ?>
            <p style="color:var(--gray-muted);font-size:13px">Sin datos</p>
        <?php else: ?>
            <?php foreach ($ventasPorTipo as $t):
                $pct = $resumen['total_ventas'] > 0 ? ($t['total'] / $resumen['total_ventas'] * 100) : 0;
            ?>
                <div style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                        <span style="color:var(--text-body)"><?= $labelTipo[$t['tipo']] ?? $t['tipo'] ?></span>
                        <span style="font-weight:500;color:var(--text-dark)">
                            <?= $t['total'] ?> — <?= round($pct) ?>%
                        </span>
                    </div>
                    <div style="height:8px;background:var(--gray-input);border-radius:8px;overflow:hidden">
                        <div style="height:100%;width:<?= round($pct) ?>%;background:var(--purple-mid);border-radius:8px"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
<?php require 'includes/footer.php'; ?>
