<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="<?php echo URL_ROOT; ?>/fertilizante" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
                <h5 class="mb-0 text-muted"><?php echo $data['titulo']; ?></h5>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="<?php echo URL_ROOT; ?>/fertilizante/guardar" method="POST">
                        
                        <input type="hidden" name="id" value="<?php echo $data['producto']->id; ?>">

                        <!-- Datos Básicos -->
                        <h6 class="text-primary-dark-green fw-bold mb-3"><i class="bi bi-tag-fill me-2"></i>Identificación</h6>
                        <div class="mb-3">
                            <label for="nombre_comercial" class="form-label">Nombre Comercial <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="nombre_comercial" name="nombre_comercial" 
                                   value="<?php echo htmlspecialchars($data['producto']->nombre_comercial); ?>" placeholder="Ej: Ultrasol K Plus" required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="tipo_producto" class="form-label">Tipo de Producto</label>
                                <select class="form-select" id="tipo_producto" name="tipo_producto">
                                <?php $tipos = ['fertilizante' => 'Fertilizante NPK', 'biostimulante' => 'Biostimulante', 'enmienda' => 'Enmienda', 'otro' => 'Otro']; ?>
                                <?php foreach($tipos as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" <?php echo ($data['producto']->tipo_producto == $val) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="tipo_unidad" class="form-label">Unidad de Medida</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="tipo_unidad" id="unidad_kg" value="kg" 
                                        onclick="toggleDensidad(false)"
                                        <?php echo ($data['producto']->tipo_unidad == 'kg') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="unidad_kg">Kilos (kg)</label>

                                    <input type="radio" class="btn-check" name="tipo_unidad" id="unidad_lt" value="lt" 
                                        onclick="toggleDensidad(true)"
                                        <?php echo ($data['producto']->tipo_unidad == 'lt') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="unidad_lt">Litros (Lt)</label>
                                </div>
                            </div>
                        </div>

                        <!-- CAMPO DE DENSIDAD -->
                        <div id="bloque_densidad" class="mb-4 bg-info bg-opacity-10 p-3 rounded border border-info" style="display: none;">
                            <label for="densidad" class="form-label fw-bold text-primary-dark-green">
                                <i class="bi bi-droplet-half me-2"></i>Densidad (Gravedad Específica)
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.001" class="form-control" id="densidad" name="densidad" 
                                    value="<?php echo ($data['producto']->densidad > 0) ? $data['producto']->densidad : '1.000'; ?>" 
                                    placeholder="Ej: 1.25">
                                <span class="input-group-text">kg/lt</span>
                            </div>
                            <div class="form-text text-muted">1 Lt × Densidad = Kg reales de producto.</div>
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <!-- Composición NPK -->
                        <h6 class="text-primary-dark-green fw-bold mb-3"><i class="bi bi-eyedropper me-2"></i>Composición NPK (0 si no aplica)</h6>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-4">
                                <label class="form-label fw-bold text-muted">Nitrógeno (N)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control border-success" name="porcentaje_n" value="<?php echo $data['producto']->porcentaje_n; ?>" placeholder="0">
                                    <span class="input-group-text bg-success text-white">%</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-bold text-muted">Fósforo (P)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control border-warning" name="porcentaje_p" value="<?php echo $data['producto']->porcentaje_p; ?>" placeholder="0">
                                    <span class="input-group-text bg-warning text-dark">%</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-bold text-muted">Potasio (K)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control border-danger" name="porcentaje_k" value="<?php echo $data['producto']->porcentaje_k; ?>" placeholder="0">
                                    <span class="input-group-text bg-danger text-white">%</span>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <!-- Micronutrientes dinámicos -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-primary-dark-green fw-bold mb-0">
                                    <i class="bi bi-plus-square-dotted me-2"></i>Micronutrientes / Componentes Extra
                                </h6>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="agregarMicro()">
                                    <i class="bi bi-plus-lg me-1"></i> Añadir
                                </button>
                            </div>
                            <div class="form-text text-muted mb-2">Ej: Zinc, Boro, Azufre, Ácidos Húmicos. Dejar vacío si no aplica.</div>

                            <div id="contenedor_micros">
                                <?php if (!empty($data['producto']->micronutrientes_array)): ?>
                                    <?php foreach ($data['producto']->micronutrientes_array as $nombre => $porcentaje): ?>
                                    <div class="row g-2 mb-2 fila-micro">
                                        <div class="col-7">
                                            <input type="text" class="form-control form-control-sm" name="micro_nombre[]" 
                                                   value="<?php echo htmlspecialchars($nombre); ?>" placeholder="Nombre (Ej: Zinc)">
                                        </div>
                                        <div class="col-4">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" min="0.01" class="form-control" name="micro_porcentaje[]" 
                                                       value="<?php echo $porcentaje; ?>" placeholder="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <div class="col-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 w-100" onclick="eliminarMicro(this)" title="Eliminar">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-accent-calendula btn-lg shadow-sm">
                                <i class="bi bi-save-fill me-2"></i> Guardar Producto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDensidad(show) {
    document.getElementById('bloque_densidad').style.display = show ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    toggleDensidad(document.getElementById('unidad_lt').checked);
});

function agregarMicro() {
    const contenedor = document.getElementById('contenedor_micros');
    const fila = document.createElement('div');
    fila.className = 'row g-2 mb-2 fila-micro';
    fila.innerHTML = `
        <div class="col-7">
            <input type="text" class="form-control form-control-sm" name="micro_nombre[]" placeholder="Nombre (Ej: Zinc)">
        </div>
        <div class="col-4">
            <div class="input-group input-group-sm">
                <input type="number" step="0.01" min="0.01" class="form-control" name="micro_porcentaje[]" placeholder="0">
                <span class="input-group-text">%</span>
            </div>
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-outline-danger border-0 w-100" onclick="eliminarMicro(this)" title="Eliminar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>`;
    contenedor.appendChild(fila);
    fila.querySelector('input[name="micro_nombre[]"]').focus();
}

function eliminarMicro(btn) {
    btn.closest('.fila-micro').remove();
}
</script>