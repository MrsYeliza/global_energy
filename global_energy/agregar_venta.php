<?php
// agregar_venta.php - Formulario nueva venta (imagen 2 y 3 del mockup)
require 'includes/conexion.php';
require 'includes/auth.php';
verificarLogin();

$paginaActiva = 'agregar';
$tituloPagina = 'Nueva venta';
$mensaje  = '';
$error    = '';
$isEditar = isset($_GET['editar']);

// Cargar clientes y productos para selects
$clientes  = $pdo->query("SELECT id, nombre, telefono FROM clientes ORDER BY nombre")->fetchAll();
$productos = $pdo->query("SELECT id, marca, modelo, tipo, precio FROM productos ORDER BY tipo, marca")->fetchAll();

// Si es edición, cargar datos existentes
$venta = null;
if ($isEditar) {
    $idEditar = (int)$_GET['editar'];
    $stmt = $pdo->prepare("
        SELECT v.*, c.nombre AS cliente_nombre, c.telefono AS cliente_telefono,
               cr.num_pagos, cr.monto_total, cr.monto_pago_quincenal
        FROM ventas v
        JOIN clientes c ON c.id = v.cliente_id
        LEFT JOIN creditos cr ON cr.venta_id = v.id
        WHERE v.id = ?
    ");
    $stmt->execute([$idEditar]);
    $venta = $stmt->fetch();
    $tituloPagina = 'Editar venta';
    $paginaActiva = 'ventas';
}

// GUARDAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        $nombre    = sanitizar($_POST['nombre'] ?? '');
        $telefono  = sanitizar($_POST['telefono'] ?? '');
        $numVenta  = sanitizar($_POST['num_venta'] ?? '');
        $productoId= (int)($_POST['producto_id'] ?? 0);
        $fecha     = sanitizar($_POST['fecha_venta'] ?? date('Y-m-d'));
        $estatus   = sanitizar($_POST['estatus'] ?? 'activa');
        $numPagos  = (int)($_POST['num_pagos'] ?? 1);
        $montoTotal= (float)($_POST['monto_total'] ?? 0);
        $notas     = sanitizar($_POST['notas'] ?? '');

        if (!$nombre || !$numVenta || !$productoId) {
            throw new Exception('Por favor completa los campos requeridos.');
        }

        // Cliente
        if ($isEditar && $venta) {
            $stmt = $pdo->prepare("UPDATE clientes SET nombre=?, telefono=? WHERE id=?");
            $stmt->execute([$nombre, $telefono, $venta['cliente_id']]);
            $clienteId = $venta['cliente_id'];
        } else {
            // Verificar si ya existe cliente con ese nombre
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE nombre = ? AND telefono = ? LIMIT 1");
            $stmt->execute([$nombre, $telefono]);
            $clienteExiste = $stmt->fetch();
            if ($clienteExiste) {
                $clienteId = $clienteExiste['id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO clientes (nombre, telefono) VALUES (?, ?)");
                $stmt->execute([$nombre, $telefono]);
                $clienteId = $pdo->lastInsertId();
            }
        }

        // Venta
        if ($isEditar && $venta) {
            $stmt = $pdo->prepare("UPDATE ventas SET num_venta=?, producto_id=?, fecha_venta=?, estatus=?, notas=? WHERE id=?");
            $stmt->execute([$numVenta, $productoId, $fecha, $estatus, $notas, $venta['id']]);
            $ventaId = $venta['id'];
        } else {
            if (!$numVenta) $numVenta = generarNumVenta($pdo);
            $stmt = $pdo->prepare("INSERT INTO ventas (num_venta, cliente_id, producto_id, usuario_id, fecha_venta, estatus, notas) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$numVenta, $clienteId, $productoId, $_SESSION['usuario_id'], $fecha, $estatus, $notas]);
            $ventaId = $pdo->lastInsertId();
        }

        // Crédito
        $pagQuin = $numPagos > 0 ? round($montoTotal / $numPagos, 2) : 0;
        $stmt = $pdo->prepare("SELECT id FROM creditos WHERE venta_id = ?");
        $stmt->execute([$ventaId]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE creditos SET num_pagos=?, monto_total=?, monto_pago_quincenal=? WHERE venta_id=?");
            $stmt->execute([$numPagos, $montoTotal, $pagQuin, $ventaId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO creditos (venta_id, num_pagos, monto_total, monto_pago_quincenal, fecha_inicio) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$ventaId, $numPagos, $montoTotal, $pagQuin, $fecha]);
        }

        // Documentos
        $tiposDoc = ['poliza', 'factura', 'contrato', 'presupuesto', 'requisitos'];
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        foreach ($tiposDoc as $tipo) {
            if (!empty($_FILES[$tipo]['name']) && $_FILES[$tipo]['error'] === UPLOAD_ERR_OK) {
                $ext      = strtolower(pathinfo($_FILES[$tipo]['name'], PATHINFO_EXTENSION));
                $allowed  = ['pdf','jpg','jpeg','png','doc','docx'];
                if (!in_array($ext, $allowed)) continue;

                $nombreArch = $ventaId . '_' . $tipo . '_' . time() . '.' . $ext;
                $destino    = $uploadDir . $nombreArch;

                if (move_uploaded_file($_FILES[$tipo]['tmp_name'], $destino)) {
                    // Eliminar documento anterior del mismo tipo si existe
                    $stmt = $pdo->prepare("SELECT ruta_archivo FROM documentos WHERE venta_id=? AND tipo=?");
                    $stmt->execute([$ventaId, $tipo]);
                    $docAnterior = $stmt->fetch();
                    if ($docAnterior && file_exists($docAnterior['ruta_archivo'])) {
                        unlink($docAnterior['ruta_archivo']);
                    }
                    $pdo->prepare("DELETE FROM documentos WHERE venta_id=? AND tipo=?")->execute([$ventaId, $tipo]);

                    $stmt = $pdo->prepare("INSERT INTO documentos (venta_id, tipo, nombre_original, ruta_archivo) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$ventaId, $tipo, $_FILES[$tipo]['name'], $destino]);
                }
            }
        }

        $pdo->commit();
        header('Location: detalle_venta.php?id=' . $ventaId . '&guardado=1');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Número de venta sugerido
$numVentaSugerido = $venta['num_venta'] ?? generarNumVenta($pdo);

// Documentos existentes (edición)
$docsExistentes = [];
if ($isEditar && $venta) {
    $stmt = $pdo->prepare("SELECT tipo, nombre_original, ruta_archivo FROM documentos WHERE venta_id=?");
    $stmt->execute([$venta['id']]);
    foreach ($stmt->fetchAll() as $d) {
        $docsExistentes[$d['tipo']] = $d;
    }
}

require 'includes/header.php';
?>

<!-- Topbar -->
<div class="topbar">
    <div>
        <a href="ventas.php" style="display:inline-flex;align-items:center;gap:5px;font-size:13px;color:var(--gray-muted);text-decoration:none;margin-bottom:6px">
            <i class="ti ti-arrow-left"></i> Volver a ventas
        </a>
        <div class="page-title"><?= $isEditar ? 'Editar venta' : 'Nueva venta' ?></div>
        <div class="page-subtitle">
            <?= $isEditar ? 'Modifica los datos de la venta ' . htmlspecialchars($venta['num_venta'] ?? '') : 'Registra una nueva venta de electrodoméstico' ?>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="ti ti-alert-circle"></i> <?= $error ?></div>
<?php endif; ?>

<form method="POST" action="agregar_venta.php<?= $isEditar ? '?editar=' . (int)$_GET['editar'] : '' ?>"
      enctype="multipart/form-data">

    <div class="form-two-col">

        <!-- Columna izquierda: datos del cliente + venta -->
        <div>
            <div class="card" style="margin-bottom:20px">
                <h3 style="font-size:13px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:16px">
                    <i class="ti ti-user"></i> Datos del cliente
                </h3>
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" name="nombre" class="form-control"
                               placeholder="Ej. María García López"
                               value="<?= htmlspecialchars($venta['cliente_nombre'] ?? $_POST['nombre'] ?? '') ?>" required>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control"
                               placeholder="Ej. 664-123-4567"
                               value="<?= htmlspecialchars($venta['cliente_telefono'] ?? $_POST['telefono'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 style="font-size:13px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:16px">
                    <i class="ti ti-shopping-cart"></i> Datos de la venta
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Núm. Venta *</label>
                        <input type="text" name="num_venta" class="form-control"
                               placeholder="GE-2024-001"
                               value="<?= htmlspecialchars($numVentaSugerido) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha de venta *</label>
                        <input type="date" name="fecha_venta" class="form-control"
                               value="<?= htmlspecialchars($venta['fecha_venta'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Producto *</label>
                        <select name="producto_id" class="form-control" required>
                            <option value="">— Seleccionar producto —</option>
                            <?php
                            $tipoActual = '';
                            foreach ($productos as $p):
                                if ($p['tipo'] !== $tipoActual):
                                    if ($tipoActual) echo '</optgroup>';
                                    $labels = ['refrigerador'=>'Refrigeradores','lavadora'=>'Lavadoras','aire_acondicionado'=>'Aires acondicionados'];
                                    echo '<optgroup label="' . ($labels[$p['tipo']] ?? $p['tipo']) . '">';
                                    $tipoActual = $p['tipo'];
                                endif;
                                $sel = (isset($venta['producto_id']) && $venta['producto_id'] == $p['id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio'] ?>" <?= $sel ?>>
                                    <?= htmlspecialchars($p['marca'] . ' ' . $p['modelo']) ?> — $<?= number_format($p['precio'], 0) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($tipoActual) echo '</optgroup>'; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Monto total ($)</label>
                        <input type="number" name="monto_total" id="monto_total" class="form-control"
                               placeholder="0.00" step="0.01" min="0"
                               value="<?= htmlspecialchars($venta['monto_total'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Núm. pagos quincenales</label>
                        <select name="num_pagos" class="form-control" id="num_pagos">
                            <?php foreach ([12,18,24,36] as $n): ?>
                                <option value="<?= $n ?>" <?= ($venta['num_pagos'] ?? 24) == $n ? 'selected' : '' ?>>
                                    <?= $n ?> quincenas
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full" style="background:var(--purple-light);border-radius:var(--radius-sm);padding:12px 14px">
                        <span style="font-size:12px;color:var(--gray-muted)">Pago quincenal estimado</span>
                        <span id="pago_quincenal" style="font-size:22px;font-weight:600;color:var(--purple-main)">$0.00</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estatus</label>
                        <select name="estatus" class="form-control">
                            <option value="activa"    <?= ($venta['estatus'] ?? 'activa') === 'activa'    ? 'selected' : '' ?>>Activa</option>
                            <option value="entregada" <?= ($venta['estatus'] ?? '') === 'entregada' ? 'selected' : '' ?>>Entregada</option>
                            <option value="cancelada" <?= ($venta['estatus'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Notas</label>
                        <textarea name="notas" class="form-control" placeholder="Observaciones adicionales..."><?= htmlspecialchars($venta['notas'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna derecha: documentos -->
        <div>
            <div class="card">
                <h3 style="font-size:13px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:16px">
                    <i class="ti ti-files"></i> Documentos
                </h3>
                <p style="font-size:12.5px;color:var(--gray-muted);margin-bottom:18px">
                    Sube los archivos en formato PDF, JPG o DOC. Máximo 10MB por archivo.
                </p>
                <div class="docs-section">

                    <?php
                    $docsCfg = [
                        'poliza'       => ['label' => 'Póliza',       'icon' => 'ti-shield-check'],
                        'factura'      => ['label' => 'Factura',      'icon' => 'ti-receipt'],
                        'contrato'     => ['label' => 'Contrato',     'icon' => 'ti-file-text'],
                        'presupuesto'  => ['label' => 'Presupuesto',  'icon' => 'ti-calculator'],
                        'requisitos'   => ['label' => 'Requisitos',   'icon' => 'ti-checklist'],
                    ];
                    foreach ($docsCfg as $tipo => $cfg):
                        $tieneDoc = isset($docsExistentes[$tipo]);
                    ?>
                        <div>
                            <div class="doc-label" style="margin-bottom:5px"><?= $cfg['label'] ?></div>
                            <label class="upload-area <?= $tieneDoc ? 'uploaded' : '' ?>" id="label_<?= $tipo ?>">
                                <div class="up-left">
                                    <i class="ti <?= $cfg['icon'] ?> up-icon"></i>
                                    <span id="txt_<?= $tipo ?>">
                                        <?= $tieneDoc
                                            ? htmlspecialchars($docsExistentes[$tipo]['nombre_original'])
                                            : 'Subir ' . $cfg['label'] ?>
                                    </span>
                                </div>
                                <input type="file" name="<?= $tipo ?>" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                       onchange="actualizarArchivo('<?= $tipo ?>', this)">
                                <button type="button" class="up-clear" title="Quitar"
                                        onclick="limpiarArchivo('<?= $tipo ?>', '<?= $cfg['label'] ?>', event)">⊗</button>
                            </label>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="ventas.php" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-check"></i>
            <?= $isEditar ? 'Guardar cambios' : 'Registrar venta' ?>
        </button>
    </div>
</form>

<script>
function actualizarArchivo(tipo, input) {
    const label = document.getElementById('label_' + tipo);
    const txt   = document.getElementById('txt_' + tipo);
    if (input.files && input.files[0]) {
        txt.textContent = input.files[0].name;
        label.classList.add('uploaded');
    }
}

function limpiarArchivo(tipo, labelNombre, e) {
    e.preventDefault();
    e.stopPropagation();
    const label = document.getElementById('label_' + tipo);
    const txt   = document.getElementById('txt_' + tipo);
    const input = label.querySelector('input[type="file"]');
    if (input) input.value = '';
    txt.textContent = 'Subir ' + labelNombre;
    label.classList.remove('uploaded');
}

// Calcular pago quincenal automáticamente
function calcularPago() {
    const monto  = parseFloat(document.getElementById('monto_total').value) || 0;
    const pagos  = parseInt(document.getElementById('num_pagos').value) || 1;
    const result = pagos > 0 ? (monto / pagos).toFixed(2) : '0.00';
    document.getElementById('pago_quincenal').textContent = '$' + parseFloat(result).toLocaleString('es-MX', {minimumFractionDigits:2});
}

// Pre-llenar monto según producto seleccionado
document.querySelector('[name="producto_id"]').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const precio = opt.dataset.precio;
    if (precio) {
        document.getElementById('monto_total').value = parseFloat(precio).toFixed(2);
        calcularPago();
    }
});

document.getElementById('monto_total').addEventListener('input', calcularPago);
document.getElementById('num_pagos').addEventListener('change', calcularPago);

calcularPago();
</script>

<?php require 'includes/footer.php'; ?>
