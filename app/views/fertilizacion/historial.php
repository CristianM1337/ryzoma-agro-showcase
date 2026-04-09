<div class="container-fluid mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 text-primary-dark-green fw-bold"><?php echo $data['titulo']; ?></h3>
        <div>
            <a href="<?php echo URL_ROOT; ?>/fertilizacion/index" class="btn btn-accent-calendula shadow-sm">
                <i class="bi bi-plus-circle me-2"></i> Registrar Nuevo
            </a>
        </div>
    </div>

    <?php SessionHelper::displayFlash(); ?>

    <div class="accordion mb-4 shadow-sm" id="accordionResumen">
  <div class="accordion-item border-0">
    <h2 class="accordion-header" id="headingOne">
      <button class="accordion-button collapsed bg-light text-primary-dark-green" type="button" data-bs-toggle="collapse" data-bs-target="#collapseResumen">
        <i class="bi bi-calculator me-2"></i> Resumen de Insumos del Mes
      </button>
    </h2>
    <div id="collapseResumen" class="accordion-collapse collapse" data-bs-parent="#accordionResumen">
      <div class="accordion-body bg-white">
        <div class="row">
            <?php if(empty($data['resumen'])): ?>
                <div class="col-12 text-muted text-center">Sin datos para resumir.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cabezal de Inyección</th>
                                <th>Producto</th>
                                <th class="text-end">Total Aplicado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['resumen'] as $res): ?>
                            <tr>
                                <td class="fw-bold text-secondary"><?php echo $res->nombre_cabezal; ?></td>
                                <td><?php echo $res->nombre_comercial; ?></td>
                                <td class="text-end font-monospace text-primary">
                                    <?php echo number_format($res->total_cantidad, 2, ',', '.') . ' ' . $res->tipo_unidad; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-end">
        <a href="<?php echo URL_ROOT; ?>/fertilizacion/reporteNutricional" class="btn btn-outline-success btn-sm">
            <i class="bi bi-bar-chart-fill me-1"></i> Ver Reporte Nutricional (NPK/Ha)
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary">
                    <tr>
                        <?php 
                            // Helper simple para generar URL de sort
                            $baseLink = "?mes={$data['mes_actual']}&year={$data['year_actual']}";
                            $newDir = ($data['dir'] == 'ASC') ? 'DESC' : 'ASC';
                            $icon = ($data['dir'] == 'ASC') ? '<i class="bi bi-arrow-up-short"></i>' : '<i class="bi bi-arrow-down-short"></i>';
                        ?>
                        <th class="ps-4 py-3">
                            <a href="<?php echo $baseLink; ?>&sort=fecha&dir=<?php echo $newDir; ?>" class="text-decoration-none text-secondary">
                                Fecha <?php echo ($data['sort'] == 'fecha') ? $icon : ''; ?>
                            </a>
                        </th>
                        <th class="py-3">
                            <a href="<?php echo $baseLink; ?>&sort=cabezal&dir=<?php echo $newDir; ?>" class="text-decoration-none text-secondary">
                                Punto de Inyección <?php echo ($data['sort'] == 'cabezal') ? $icon : ''; ?>
                            </a>
                        </th>
                        <th class="py-3">
                            <a href="<?php echo $baseLink; ?>&sort=producto&dir=<?php echo $newDir; ?>" class="text-decoration-none text-secondary">
                                Producto <?php echo ($data['sort'] == 'producto') ? $icon : ''; ?>
                            </a>
                        </th>
                        <th class="text-end py-3">Cantidad Total</th>
                        <th class="text-center py-3">Registrado por</th>
                        <th class="text-end pe-4 py-3">Acciones</th>
                    </tr>
                </thead>

    <!-- Filtro de Mes (Navegación) -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body py-2 d-flex justify-content-between align-items-center">
            <?php 
                $mes = $data['mes_actual'];
                $year = $data['year_actual'];
                
                $prevMonth = date('m', mktime(0, 0, 0, $mes - 1, 1, $year));
                $prevYear = date('Y', mktime(0, 0, 0, $mes - 1, 1, $year));
                $nextMonth = date('m', mktime(0, 0, 0, $mes + 1, 1, $year));
                $nextYear = date('Y', mktime(0, 0, 0, $mes + 1, 1, $year));
                
                setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');
                $nombreMes = strftime('%B', mktime(0, 0, 0, $mes, 10));
            ?>
            <a href="?mes=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-outline-secondary btn-sm border-0">
                <i class="bi bi-chevron-left"></i> Anterior
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar-month text-muted"></i>
                <span class="fw-bold text-capitalize fs-5"><?php echo $nombreMes . ' ' . $year; ?></span>
            </div>

            <a href="?mes=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-outline-secondary btn-sm border-0">
                Siguiente <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- Tabla de Bitácora -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-4 py-3">Fecha</th>
                            <th class="py-3">Punto de Inyección (Cabezal)</th>
                            <th class="py-3">Producto</th>
                            <th class="text-end py-3">Cantidad Total</th>
                            <th class="text-center py-3">Registrado por</th>
                            <th class="text-end pe-4 py-3" style="min-width: 140px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['registros'])): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-bucket fs-1 d-block mb-2 opacity-25"></i>
                                    No hay aplicaciones registradas en este mes.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($data['registros'] as $reg): 
                                // Icono y estilo según tipo
                                $badgeClass = 'bg-secondary';
                                $icon = 'bi-box-seam';
                                if($reg->tipo_producto == 'fertilizante') { $badgeClass = 'bg-success'; $icon = 'bi-flower1'; }
                                if($reg->tipo_producto == 'biostimulante') { $badgeClass = 'bg-info text-dark'; $icon = 'bi-stars'; }
                                if($reg->tipo_producto == 'enmienda') { $badgeClass = 'bg-warning text-dark'; $icon = 'bi-layers'; }
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">
                                    <?php echo date('d/m/Y', strtotime($reg->fecha)); ?>
                                </td>
                                <td>
                                    <i class="bi bi-diagram-3 text-primary me-1"></i>
                                    <?php echo htmlspecialchars($reg->nombre_cabezal); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badgeClass; ?> me-1"><i class="bi <?php echo $icon; ?>"></i></span>
                                    <?php echo htmlspecialchars($reg->nombre_comercial); ?>
                                </td>
                                <td class="text-end fw-bold font-monospace">
                                    <?php echo number_format($reg->cantidad_aplicada, 2, ',', '.'); ?> 
                                    <small class="text-muted"><?php echo $reg->tipo_unidad; ?></small>
                                </td>
                                <td class="text-center text-muted small">
                                    <?php echo htmlspecialchars($reg->nombre_usuario ?? '-'); ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-secondary border-0" 
                                                title="Ver Distribución"
                                                onclick="verDistribucion(<?php echo $reg->id; ?>, '<?php echo htmlspecialchars($reg->nombre_comercial); ?>', '<?php echo htmlspecialchars($reg->nombre_cabezal); ?>')">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                        <a href="<?php echo URL_ROOT; ?>/fertilizacion/editar/<?php echo $reg->id; ?>" 
                                           class="btn btn-sm btn-outline-primary border-0"
                                           title="Editar Registro">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalle de Distribución -->
<div class="modal fade" id="modalDistribucion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary-dark-green text-white">
                <h5 class="modal-title"><i class="bi bi-share-fill me-2"></i>Distribución Real</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 pb-2 border-bottom">
                    <h6 class="fw-bold mb-1" id="modalProducto"></h6>
                    <small class="text-muted">Inyectado en: <span id="modalCabezal"></span></small>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="small text-muted">
                            <tr>
                                <th>Destino (Sector/Lote)</th>
                                <th class="text-end">Cant. Recibida</th>
                                <th class="text-end">N-P-K (Unidades)</th>
                            </tr>
                        </thead>
                        <tbody id="modalTablaBody">
                            <tr><td colspan="3" class="text-center"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3 mb-0 py-2 small">
                    <i class="bi bi-info-circle me-1"></i> 
                    El sistema calculó esto basándose en la configuración hidráulica y la densidad del producto.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function verDistribucion(cabezalId, producto, cabezal) {
    // Setear textos
    document.getElementById('modalProducto').textContent = producto;
    document.getElementById('modalCabezal').textContent = cabezal;
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalDistribucion'));
    modal.show();
    
    // Cargar datos
    const tbody = document.getElementById('modalTablaBody');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><div class="spinner-border text-primary"></div></td></tr>';

    fetch('<?php echo URL_ROOT; ?>/fertilizacion/verDetalleDistribucion/' + cabezalId)
        .then(response => response.json())
        .then(data => {
            tbody.innerHTML = '';
            if (data.success && data.detalle.length > 0) {
                data.detalle.forEach(d => {
                    // Formatear NPK si existen
                    let npk = '-';
                    if (parseFloat(d.unidades_n) > 0 || parseFloat(d.unidades_p) > 0 || parseFloat(d.unidades_k) > 0) {
                        npk = `<span class="text-success">${parseFloat(d.unidades_n)}</span> - 
                               <span class="text-warning text-dark">${parseFloat(d.unidades_p)}</span> - 
                               <span class="text-danger">${parseFloat(d.unidades_k)}</span>`;
                    }

                    const row = `<tr>
                        <td>${d.nombre_sector_destino}</td>
                        <td class="text-end fw-bold">${parseFloat(d.cantidad_recibida).toFixed(2)}</td>
                        <td class="text-end small font-monospace">${npk}</td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Sin distribución registrada.</td></tr>';
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error al cargar detalles.</td></tr>';
        });
}
</script>