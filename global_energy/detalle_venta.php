<?php
// detalle_venta.php
require 'includes/conexion.php';
require 'includes/auth.php';
verificarLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ventas.php'); exit; }

$stmt = $pdo->prepare("
    SELECT v.*, c.nombre AS cliente, c.telefono,
           p.marca, p.modelo, p.tipo, p.num_serie, p.garantia_meses, p.precio AS precio_producto,
           cr.num_pagos, cr.monto_total, cr.monto_pago_quincenal, cr.fecha_inicio, cr.estatus AS estatus_credito,
           u.nombre AS vendedor
    FROM ventas v
    JOIN clientes c  ON c.id = v.cliente_id
    JOIN productos p ON p.id = v.producto_id
    LEFT JOIN creditos cr ON cr.venta_id = v.id
    LEFT JOIN usuarios u ON u.id = v.usuario_id
    WHERE v.id = ?
");
$stmt->execute([$id]);
$v = $stmt->fetch();
if (!$v) { header('Location: ventas.php'); exit; }

// Documentos
$docsStmt = $pdo->prepare("SELECT tipo, nombre_original, ruta_archivo, fecha_subida FROM documentos WHERE venta_id=?");
$docsStmt->execute([$id]);
$docs = [];
foreach ($docsStmt->fetchAll() as $d) $docs[$d['tipo']] = $d;

$paginaActiva = 'ventas';
$tituloPagina = 'Venta ' . $v['num_venta'];

$labelEstatus = [
    'activa'    => ['text'=>'Activa',    'class'=>'badge-purple'],
    'entregada' => ['text'=>'Entregada', 'class'=>'badge-green'],
    'cancelada' => ['text'=>'Cancelada', 'class'=>'badge-red'],
];

$labelProducto = [
    'refrigerador'       => 'Refrigerador',
    'lavadora'           => 'Lavadora',
    'aire_acondicionado' => 'Aire acondicionado',
];

$docsCfg = [
    'poliza'      => ['label'=>'Póliza',      'icon'=>'ti-shield-check'],
    'factura'     => ['label'=>'Factura',      'icon'=>'ti-receipt'],
    'contrato'    => ['label'=>'Contrato',     'icon'=>'ti-file-text'],
    'presupuesto' => ['label'=>'Presupuesto',  'icon'=>'ti-calculator'],
    'requisitos'  => ['label'=>'Requisitos',   'icon'=>'ti-checklist'],
];

require 'includes/header.php';
?>

<!-- Topbar -->
<div class="topbar">
    <div>
        <a href="ventas.php" style="display:inline-flex;align-items:center;gap:5px;font-size:13px;color:var(--gray-muted);text-decoration:none;margin-bottom:6px">
            <i class="ti ti-arrow-left"></i> Volver a ventas
        </a>
        <div style="display:flex;align-items:center;gap:10px">
            <div class="page-title"><?= htmlspecialchars($v['num_venta']) ?></div>
            <?php $e = $labelEstatus[$v['estatus']] ?? ['text'=>$v['estatus'],'class'=>'badge-gray']; ?>
            <span class="badge <?= $e['class'] ?>"><?= $e['text'] ?></span>
        </div>
        <div class="page-subtitle">Registrada el <?= date('d/m/Y', strtotime($v['fecha_venta'])) ?></div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="agregar_venta.php?editar=<?= $id ?>" class="btn btn-outline">
            <i class="ti ti-edit"></i> Editar
        </a>
        <a href="ventas.php" class="btn btn-ghost">
            <i class="ti ti-list"></i> Ver todas
        </a>
    </div>
</div>

<?php if (isset($_GET['guardado'])): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> Venta guardada correctamente.</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <!-- Cliente -->
    <div class="card detail-section">
        <h3><i class="ti ti-user"></i> Cliente</h3>
        <div class="detail-row">
            <span class="dk">Nombre</span>
            <span class="dv"><?= htmlspecialchars($v['cliente']) ?></span>
        </div>
        <div class="detail-row">
            <span class="dk">Teléfono</span>
            <span class="dv"><?= htmlspecialchars($v['telefono'] ?: '—') ?></span>
        </div>
        <div class="detail-row">
            <span class="dk">Vendedor</span>
            <span class="dv"><?= htmlspecialchars($v['vendedor'] ?: '—') ?></span>
        </div>
    </div>

    <!-- Producto -->
    <div class="card detail-section">
        <h3><i class="ti ti-package"></i> Producto</h3>
        <div class="detail-row">
            <span class="dk">Tipo</span>
            <span class="dv">
                <span class="badge badge-purple"><?= $labelProducto[$v['tipo']] ?? $v['tipo'] ?></span>
            </span>
        </div>
        <div class="detail-row">
            <span class="dk">Marca / Modelo</span>
            <span class="dv"><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></span>
        </div>
        <div class="detail-row">
            <span class="dk">Núm. de serie</span>
            <span class="dv" style="font-family:monospace"><?= htmlspecialchars($v['num_serie'] ?: '—') ?></span>
        </div>
        <div class="detail-row">
            <span class="dk">Garantía</span>
            <span class="dv"><?= $v['garantia_meses'] ?> meses</span>
        </div>
    </div>

    <!-- Crédito -->
    <div class="card detail-section">
        <h3><i class="ti ti-credit-card"></i> Crédito</h3>
        <?php if ($v['monto_total']): ?>
            <div class="detail-row">
                <span class="dk">Monto total</span>
                <span class="dv" style="font-size:17px;color:var(--purple-main);font-weight:600">
                    $<?= number_format($v['monto_total'], 2) ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="dk">Pago quincenal</span>
                <span class="dv">$<?= number_format($v['monto_pago_quincenal'], 2) ?></span>
            </div>
            <div class="detail-row">
                <span class="dk">Núm. de pagos</span>
                <span class="dv"><?= $v['num_pagos'] ?> quincenas</span>
            </div>
            <div class="detail-row">
                <span class="dk">Fecha inicio</span>
                <span class="dv"><?= $v['fecha_inicio'] ? date('d/m/Y', strtotime($v['fecha_inicio'])) : '—' ?></span>
            </div>
            <div class="detail-row">
                <span class="dk">Estatus crédito</span>
                <span class="dv">
                    <?php $ec = ['activo'=>'badge-purple','pagado'=>'badge-green','vencido'=>'badge-red'][$v['estatus_credito']] ?? 'badge-gray'; ?>
                    <span class="badge <?= $ec ?>"><?= ucfirst($v['estatus_credito'] ?? '—') ?></span>
                </span>
            </div>
        <?php else: ?>
            <p style="color:var(--gray-muted);font-size:13px">Sin información de crédito registrada.</p>
        <?php endif; ?>
    </div>

    <!-- Documentos -->
    <div class="card detail-section">
        <h3><i class="ti ti-files"></i> Documentos</h3>
        <div class="doc-grid">
            <?php foreach ($docsCfg as $tipo => $cfg): ?>
                <?php if (isset($docs[$tipo])): ?>
                    <a href="<?= htmlspecialchars($docs[$tipo]['ruta_archivo']) ?>" target="_blank"
                       class="doc-item has-doc" title="Ver <?= $cfg['label'] ?>">
                        <i class="ti <?= $cfg['icon'] ?> doc-icon"></i>
                        <?= $cfg['label'] ?>
                    </a>
                <?php else: ?>
                    <div class="doc-item" title="Sin <?= $cfg['label'] ?>">
                        <i class="ti <?= $cfg['icon'] ?> doc-icon"></i>
                        <?= $cfg['label'] ?>
                        <span style="margin-left:auto;font-size:11px">—</span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <a href="agregar_venta.php?editar=<?= $id ?>" class="btn btn-ghost btn-sm" style="margin-top:12px">
            <i class="ti ti-upload"></i> Agregar documentos
        </a>
    </div>

</div>

<?php if ($v['notas']): ?>
    <div class="card detail-section" style="margin-top:20px">
        <h3><i class="ti ti-note"></i> Notas</h3>
        <p style="font-size:13.5px;line-height:1.6"><?= nl2br(htmlspecialchars($v['notas'])) ?></p>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
