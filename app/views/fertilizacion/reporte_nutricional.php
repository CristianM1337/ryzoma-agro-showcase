<!-- Estilos Específicos para Impresión Limpia -->
<style>
    @media print {
        body * { visibility: hidden; }
        #areaImprimible, #areaImprimible * { visibility: visible; }
        #areaImprimible {
            position: absolute; left: 0; top: 0; width: 100%;
            margin: 0; padding: 20px; background-color: white;
            border: none !important; box-shadow: none !important;
        }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        table { font-size: 12px; width: 100% !important; }
        h2 { font-size: 18px; margin-bottom: 5px; }
        p { font-size: 11px; }
        .badge { border: 1px solid #ddd; color: black !important; }
        tr { page-break-inside: avoid; }
    }
</style>

<?php
// Recolectar todas las keys de micronutrientes presentes en el dataset
// para construir columnas dinámicas
$microKeys = [];
if (!empty($data['datos'])) {
    foreach ($data['datos'] as $row) {
        if (!empty($row->micronutrientes)) {
            foreach (array_keys($row->micronutrientes) as $k) {
                if (!in_array($k, $microKeys)) $microKeys[] = $k;
            }
        }
    }
}
sort($microKeys);
?>

<div class="container-fluid mt-4">
    <!-- BARRA DE ACCIONES -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3 no-print-section">
        <div class="w-100">
            <h3 class="text-primary-dark-green fw-bold mb-0">Reporte Nutricional</h3>
            <p class="text-muted mb-0 small">
                Acumulado desde: <strong><?php echo date('d/m/Y', strtotime($data['inicio_temporada'])); ?></strong> hasta hoy.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 w-100 justify-content-md-end">
            <a href="<?php echo URL_ROOT; ?>/fertilizacion/historial" class="btn btn-outline-secondary flex-fill flex-md-grow-0 text-center">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <div class="btn-group flex-fill flex-md-grow-0">
                <button type="button" class="btn btn-primary dropdown-toggle w-100" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i> Exportar
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <button class="dropdown-item" onclick="window.print()">
                            <i class="bi bi-file-pdf text-danger me-2"></i> Guardar como PDF
                        </button>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?php echo URL_ROOT; ?>/fertilizacion/exportarExcelNutricional">
                            <i class="bi bi-file-excel text-success me-2"></i> Descargar Excel
                        </a>
                    </li>
                </ul>
            </div>
            <button class="btn btn-accent-calendula text-white shadow-sm flex-fill flex-md-grow-0" onclick="generarLinkCompartible()">
                <i class="bi bi-share-fill me-1"></i> Compartir
            </button>
        </div>
    </div>

    <!-- ÁREA IMPRIMIBLE -->
    <div id="areaImprimible">
        <div class="card shadow border-0">
            <div class="card-body p-4">
                
                <div class="border-bottom pb-3 mb-3">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h2 class="fw-bold text-dark mb-1">Informe de Estado Nutricional</h2>
                            <p class="text-muted mb-0">
                                Temporada Agrícola <?php echo date('Y'); ?> | 
                                <?php echo date('d/m/Y', strtotime($data['inicio_temporada'])); ?> — <?php echo date('d/m/Y'); ?>
                            </p>
                        </div>
                        <div class="col-4 text-end">
                            <img src="<?php echo URL_ROOT; ?>/img/logo-plataforma.png" alt="Logo" style="height: 40px; opacity: 0.8;">
                            <div class="small text-muted mt-1">Generado: <?php echo date('d/m/Y H:i'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle table-sm">
                        <thead style="background-color: #1a4d2e !important; color: white !important;" class="text-center">
                            <tr>
                                <th class="align-middle" rowspan="2">Sector / Predio</th>
                                <th class="align-middle" rowspan="2">Cultivo</th>
                                <th class="align-middle" rowspan="2">Sup. (Ha)</th>
                                <th colspan="3" class="border-bottom-0">Unidades / Ha</th>
                                <?php if (!empty($microKeys)): ?>
                                <th colspan="<?php echo count($microKeys); ?>" class="border-bottom-0">Micronutrientes (U. totales)</th>
                                <?php endif; ?>
                            </tr>
                            <tr>
                                <th style="background-color:#198754!important" class="text-white">N</th>
                                <th style="background-color:#ffc107!important" class="text-dark">P</th>
                                <th style="background-color:#dc3545!important" class="text-white">K</th>
                                <?php foreach ($microKeys as $mk): ?>
                                <th class="bg-secondary text-white"><?php echo htmlspecialchars($mk); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data['datos'])): ?>
                                <tr><td colspan="<?php echo 6 + count($microKeys); ?>" class="text-center py-4 text-muted">No hay datos de fertilización en esta temporada.</td></tr>
                            <?php else: ?>
                                <?php foreach ($data['datos'] as $row): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($row->predio); ?></td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($row->cultivo ?? '-'); ?></td>
                                    <td class="text-center font-monospace"><?php echo number_format($row->hectareas, 2, ',', '.'); ?></td>
                                    <td class="text-center fw-bold text-success border-start bg-light">
                                        <?php echo number_format($row->n_ha, 1, ',', '.'); ?>
                                    </td>
                                    <td class="text-center fw-bold border-start bg-light" style="color:#d39e00!important">
                                        <?php echo number_format($row->p_ha, 1, ',', '.'); ?>
                                    </td>
                                    <td class="text-center fw-bold text-danger border-start bg-light">
                                        <?php echo number_format($row->k_ha, 1, ',', '.'); ?>
                                    </td>
                                    <?php foreach ($microKeys as $mk): ?>
                                    <td class="text-end small text-muted border-start">
                                        <?php
                                        $val = $row->micronutrientes[$mk] ?? 0;
                                        echo $val > 0 ? number_format($val, 2, ',', '.') : '<span class="text-muted opacity-25">—</span>';
                                        ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pt-2 border-top">
                    <div class="d-flex align-items-start small text-muted">
                        <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                        <div>
                            <strong>Nota Técnica:</strong> 
                            N, P y K expresados en <em>Unidades por Hectárea (U/Ha)</em>. 
                            Micronutrientes en Unidades totales acumuladas para el período.
                            Cálculos basados en la distribución hidráulica registrada.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal Compartir -->
<div class="modal fade" id="modalCompartir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Compartir Reporte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Enlace válido por 30 días.</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="inputLink" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copiarLink()">Copiar</button>
                </div>
                <div id="msgCopia" class="text-success small fw-bold" style="display:none;">¡Copiado!</div>
            </div>
        </div>
    </div>
</div>

<script>
function generarLinkCompartible() {
    const btn = document.querySelector('button[onclick="generarLinkCompartible()"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    fetch('<?php echo URL_ROOT; ?>/fertilizacion/generarLinkPublico', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('inputLink').value = data.link;
            new bootstrap.Modal(document.getElementById('modalCompartir')).show();
        } else alert('Error: ' + (data.message || 'Error al generar token.'));
    })
    .catch(() => alert('Error de conexión'))
    .finally(() => { btn.disabled = false; btn.innerHTML = originalText; });
}
function copiarLink() {
    const el = document.getElementById('inputLink');
    el.select();
    document.execCommand('copy');
    document.getElementById('msgCopia').style.display = 'block';
    setTimeout(() => document.getElementById('msgCopia').style.display = 'none', 3000);
}
</script>