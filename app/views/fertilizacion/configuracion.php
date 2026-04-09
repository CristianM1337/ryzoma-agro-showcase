<!-- ... (Header y Grilla) ... -->

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 text-primary-dark-green fw-bold">Configuración Hidráulica</h3>
            <p class="text-muted mb-0">Define cómo se distribuye el fertirriego entre predios interconectados.</p>
        </div>
        <button class="btn btn-accent-calendula shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearCabezal">
            <i class="bi bi-plus-circle-fill me-2"></i> Nuevo Cabezal Virtual
        </button>
    </div>
    
    <?php SessionHelper::displayFlash(); ?>

    <!-- Grilla de Predios -->
    <div class="row g-4">
        <?php foreach($data['predios'] as $predio): 
            $totalSaliente = 0;
            if (!empty($predio->distribuciones)) { foreach($predio->distribuciones as $d) $totalSaliente += $d->porcentaje_flujo; }
            $remanente = 100 - $totalSaliente;
            $colorBorde = ($remanente < 0.1 && $totalSaliente > 0) ? 'border-success' : ($remanente < 0 ? 'border-danger' : 'border-secondary');
            $icono = ($predio->tipo_superficie == 'cabezal_virtual') ? 'bi-hdd-network-fill' : 'bi-box-arrow-right';
            $badgeTipo = ($predio->tipo_superficie == 'cabezal_virtual') ? '<span class="badge bg-dark ms-2">VIRTUAL</span>' : '<span class="badge bg-success ms-2">FISICO</span>';
        ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm border-4 <?php echo $colorBorde; ?>" id="card-predio-<?php echo $predio->id; ?>">
                <div class="card-body d-flex flex-column">
                    
                    <!-- TITULO CON BOTON DE BORRADO -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title fw-bold text-truncate mb-0 me-2">
                            <i class="bi <?php echo $icono; ?> text-primary me-2"></i>
                            <?php echo htmlspecialchars($predio->nombre); ?>
                            <?php echo $badgeTipo; ?>
                        </h5>
                        
                        <?php if($predio->tipo_superficie == 'cabezal_virtual'): ?>
                            <button class="btn btn-sm text-danger p-0" onclick="eliminarCabezalVirtual(<?php echo $predio->id; ?>, '<?php echo htmlspecialchars($predio->nombre); ?>')" title="Eliminar Cabezal">
                                <i class="bi bi-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="distribucion-list-container mb-3 flex-grow-1">
                        <?php if (empty($predio->distribuciones)): ?>
                            <p class="text-muted small fst-italic">Sin distribución (100% queda en este punto).</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush small">
                                <?php foreach($predio->distribuciones as $dist): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 border-0">
                                        <span><i class="bi bi-arrow-return-right text-muted me-2"></i>Hacia <strong><?php echo htmlspecialchars($dist->nombre_destino); ?></strong></span>
                                        <span class="badge bg-secondary rounded-pill"><?php echo floatval($dist->porcentaje_flujo); ?>%</span>
                                    </li>
                                <?php endforeach; ?>
                                <?php if ($remanente > 0.1): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-light rounded mt-2 p-2">
                                    <span class="text-dark"><i class="bi bi-arrow-down-circle-fill me-2"></i>Queda en <strong>Origen</strong></span>
                                    <span class="badge bg-light text-dark border rounded-pill fw-bold"><?php echo number_format($remanente, 1); ?>%</span>
                                </li>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="mt-auto text-end border-top pt-2">
                        <button class="btn btn-sm btn-outline-primary w-100" onclick="abrirConfigurador(<?php echo $predio->id; ?>, '<?php echo htmlspecialchars($predio->nombre); ?>')">
                            <i class="bi bi-gear-fill me-1"></i> Configurar Distribución
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ... (Los Modales se mantienen IGUALES) ... -->
<div class="modal fade" id="modalCrearCabezal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary-dark-green text-white">
                <h5 class="modal-title"><i class="bi bi-plus-square me-2"></i>Nuevo Cabezal Virtual</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCrearCabezal">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Cabezal</label>
                        <input type="text" class="form-control" name="nombre_cabezal" placeholder="Ej: Cabezal Lotes 4 y 5" required>
                    </div>
                    <div class="d-grid"><button type="submit" class="btn btn-accent-calendula fw-bold">Crear Cabezal</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfiguracion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Configurar: <span id="modalPredioNombre" class="fw-bold text-warning"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid mb-3">
                    <button class="btn btn-info text-white btn-sm shadow-sm" onclick="abrirAsistente()">
                        <i class="bi bi-magic me-2"></i> <strong>Asistente Automático</strong> (Calcula sobre Remanente)
                    </button>
                </div>
                <hr>
                <div class="bg-light p-3 rounded mb-3 border">
                    <h6 class="fw-bold text-muted small text-uppercase mb-2">Agregar Manualmente</h6>
                    <form id="formAgregarRelacion" class="row g-2 align-items-end">
                        <input type="hidden" id="inputOrigenId" name="origen_id">
                        <div class="col-7">
                            <select class="form-select form-select-sm" id="selectDestino" name="destino_id" required>
                                <option value="">Destino...</option>
                                <?php foreach($data['predios_fisicos'] as $p): ?> 
                                    <option value="<?php echo $p->id; ?>"><?php echo htmlspecialchars($p->nombre); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="number" class="form-control form-control-sm" id="inputPorcentaje" name="porcentaje" placeholder="%" min="0.1" max="100" step="0.1" required>
                        </div>
                        <div class="col-2"><button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-plus-lg"></i></button></div>
                    </form>
                </div>
                <h6 class="fw-bold text-muted small text-uppercase">Distribución Actual</h6>
                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <tbody id="tablaRelacionesBody"></tbody>
                    </table>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                     <span class="small text-muted">Total Asignado: <span id="modalTotalSaliente" class="fw-bold">0%</span></span>
                     <span class="small text-success">Remanente (Aquí): <span id="modalRemanente" class="fw-bold">100%</span></span>
                </div>
                <div id="modalAlert" class="alert alert-danger mt-2 py-1 px-2 small" style="display:none;"></div>
            </div>
            <div class="modal-footer py-1 bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="location.reload();">Cerrar y Recargar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAsistente" tabindex="-1" aria-hidden="true" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title"><i class="bi bi-calculator me-2"></i>Cálculo Híbrido (Respeta Manuales)</h6>
                <button type="button" class="btn-close" onclick="cerrarAsistente()"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Seleccione los predios <strong>adicionales</strong> a agregar. El sistema distribuirá el porcentaje disponible entre ellos según sus Hectáreas, <strong>sin tocar</strong> lo que ya configuró manualmente.</p>
                <form id="formAsistente">
                    <div class="list-group mb-3" style="max-height: 250px; overflow-y: auto;">
                        <?php if(empty($data['predios_fisicos'])): ?>
                            <div class="text-center p-3 text-muted">No hay predios de cultivo registrados.</div>
                        <?php else: ?>
                            <?php foreach($data['predios_fisicos'] as $p): ?>
                                <label class="list-group-item d-flex gap-2 align-items-center cursor-pointer action-card">
                                    <input class="form-check-input flex-shrink-0" type="checkbox" name="predios_calculo[]" value="<?php echo $p->id; ?>" style="transform: scale(1.2);">
                                    <span>
                                        <span class="fw-bold"><?php echo htmlspecialchars($p->nombre); ?></span>
                                        <small class="d-block text-muted"><?php echo floatval($p->superficie_total); ?> Ha</small>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid">
                        <button type="button" class="btn btn-info text-white fw-bold" onclick="calcularYAplicar()">Calcular y Agregar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const URL_BASE = '<?php echo URL_ROOT; ?>';
let currentOrigenId = null;

// --- NUEVA FUNCIÓN DE BORRADO ---
function eliminarCabezalVirtual(id, nombre) {
    if (!confirm(`¿Está seguro de eliminar el cabezal "${nombre}"?\n\nSi fue usado en registros antiguos, solo se ocultará.`)) {
        return;
    }
    
    const fd = new FormData();
    fd.append('id', id);

    fetch(`${URL_BASE}/fertilizacion/eliminarCabezalVirtual`, { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo procesar la solicitud.'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión con el servidor.');
    });
}

// --- RESTO DE FUNCIONES (Crear, Configurar, Asistente) IGUALES QUE ANTES ---
// Lógica Creación Cabezal
document.getElementById('formCrearCabezal').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch(`${URL_BASE}/fertilizacion/crearCabezalRapido`, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.message || 'Error');
    });
});

function abrirConfigurador(predioId, predioNombre) {
    currentOrigenId = predioId;
    document.getElementById('modalPredioNombre').textContent = predioNombre;
    document.getElementById('inputOrigenId').value = predioId;
    document.getElementById('formAgregarRelacion').reset();
    document.getElementById('modalAlert').style.display = 'none';
    cargarRelaciones();
    new bootstrap.Modal(document.getElementById('modalConfiguracion')).show();
}

function cargarRelaciones() {
    const tbody = document.getElementById('tablaRelacionesBody');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Cargando...</td></tr>';
    fetch(`${URL_BASE}/fertilizacion/getDistribuciones/${currentOrigenId}`)
        .then(response => response.json())
        .then(data => {
            tbody.innerHTML = '';
            let totalSaliente = 0;
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted fst-italic small py-3">Sin configuración (Todo queda aquí).</td></tr>';
            } else {
                data.forEach(rel => {
                    totalSaliente += parseFloat(rel.porcentaje_flujo);
                    tbody.innerHTML += `<tr>
                        <td><i class="bi bi-arrow-return-right text-secondary me-2"></i>${rel.nombre_destino}</td>
                        <td class="text-center fw-bold text-primary bg-light">${parseFloat(rel.porcentaje_flujo)}%</td>
                        <td class="text-end"><button class="btn btn-outline-danger btn-sm border-0 py-0" onclick="eliminarRelacion(${rel.id})"><i class="bi bi-trash"></i></button></td>
                    </tr>`;
                });
            }
            actualizarTotales(totalSaliente);
            actualizarSelectDestino(data);
        });
}

document.getElementById('formAgregarRelacion').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch(`${URL_BASE}/fertilizacion/guardarDistribucion`, { method: 'POST', body: formData })
    .then(res => res.json()).then(resp => {
        if(resp.success) { document.getElementById('formAgregarRelacion').reset(); cargarRelaciones(); }
        else { mostrarAlerta(resp.message); }
    });
});

function eliminarRelacion(id) {
    if(!confirm('¿Quitar salida?')) return;
    const fd = new FormData(); fd.append('id', id);
    fetch(`${URL_BASE}/fertilizacion/eliminarDistribucion`, { method: 'POST', body: fd })
    .then(res => res.json()).then(resp => { if(resp.success) cargarRelaciones(); });
}

function abrirAsistente() {
    new bootstrap.Modal(document.getElementById('modalAsistente')).show();
    const chkOrigen = document.querySelector(`#formAsistente input[value="${currentOrigenId}"]`);
    if(chkOrigen) chkOrigen.disabled = true;
    document.querySelectorAll('#formAsistente input[type="checkbox"]').forEach(chk => chk.checked = false);
}

function cerrarAsistente() {
    bootstrap.Modal.getInstance(document.getElementById('modalAsistente')).hide();
}

function calcularYAplicar() {
    const form = document.getElementById('formAsistente');
    const checkboxes = form.querySelectorAll('input[type="checkbox"]:checked');
    const destinos = Array.from(checkboxes).map(cb => cb.value);

    if (destinos.length === 0) { alert('Seleccione al menos un predio.'); return; }

    const payload = { origen_id: currentOrigenId, destinos: destinos };

    fetch(`${URL_BASE}/fertilizacion/calcularProporcionSuperficie`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(async data => {
        if (!data.success) { alert(data.message); return; }
        
        let msg = `Se detectaron asignaciones previas fijas (${(100 - data.porcentaje_disponible).toFixed(1)}%).\n`;
        msg += `Queda disponible para distribuir: ${data.porcentaje_disponible}%\n\n`;
        msg += `Se asignará entre ${data.calculos.length} predios seleccionados (${data.total_ha} Ha):\n`;
        data.calculos.forEach(c => { msg += `- ${c.nombre}: ${c.porcentaje_calculado}%\n`; });
        msg += `\n¿Agregar estas salidas?`;

        if(!confirm(msg)) return;

        cerrarAsistente();
        
        for (const calculo of data.calculos) {
            const fd = new FormData();
            fd.append('origen_id', currentOrigenId);
            fd.append('destino_id', calculo.id);
            fd.append('porcentaje', calculo.porcentaje_calculado);
            await fetch(`${URL_BASE}/fertilizacion/guardarDistribucion`, { method: 'POST', body: fd });
        }
        
        cargarRelaciones();
        alert('Configuración actualizada con éxito.');
    });
}

function actualizarTotales(total) {
    const elRemanente = document.getElementById('modalRemanente');
    document.getElementById('modalTotalSaliente').textContent = total.toFixed(1) + '%';
    const remanente = 100 - total;
    elRemanente.textContent = remanente.toFixed(1) + '%';
    if(remanente < -0.1) {
        elRemanente.className = 'fw-bold text-danger';
        mostrarAlerta('Error: La suma excede el 100%. Por favor ajuste los valores.');
    } else {
        elRemanente.className = 'fw-bold text-success';
        document.getElementById('modalAlert').style.display = 'none';
    }
}

function mostrarAlerta(msg) {
    const alert = document.getElementById('modalAlert');
    alert.textContent = msg; alert.style.display = 'block';
}

function actualizarSelectDestino(dataExistente) {
    const select = document.getElementById('selectDestino');
    for (let i = 0; i < select.options.length; i++) select.options[i].disabled = false;
    const self = select.querySelector(`option[value="${currentOrigenId}"]`);
    if(self) self.disabled = true;
    dataExistente.forEach(rel => {
        const opt = select.querySelector(`option[value="${rel.predio_destino_id}"]`);
        if(opt) opt.disabled = true;
    });
}
</script>