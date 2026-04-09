<div class="container-fluid mt-3">
    
    <?php SessionHelper::displayFlash(); ?>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7"> <div class="card shadow border-0">
                <div class="card-header <?php echo isset($data['registro']) ? 'bg-warning text-dark' : 'bg-primary-dark-green text-white'; ?> py-3">
                    <h4 class="mb-0 fw-bold">
                        <i class="bi <?php echo isset($data['registro']) ? 'bi-pencil-square' : 'bi-bucket-fill'; ?> me-2"></i>
                        <?php echo isset($data['registro']) ? 'Editar Registro Individual' : 'Registro Aplicación de Fertilizantes'; ?>
                    </h4>
                    <p class="mb-0 small opacity-75">
                        <?php echo isset($data['registro']) ? 'Modificando un registro específico.' : 'Ingresa uno o varios productos aplicados.'; ?>
                    </p>
                </div>
                
                <div class="card-body p-4">
                    <form action="<?php echo URL_ROOT; ?>/fertilizacion/guardarRegistro" method="POST" id="formFertilizacion">
                        
                        <?php if (isset($data['registro'])): ?>
                            <input type="hidden" name="id" value="<?php echo $data['registro']->id; ?>">
                        <?php endif; ?>

                        <div class="row g-3 mb-4 p-3 bg-light rounded border">
                            <div class="col-md-6">
                                <label for="fecha" class="form-label fw-bold text-muted">Fecha de Aplicación</label>
                                <input type="date" class="form-control" id="fecha" name="fecha" 
                                       value="<?php echo $data['fecha_hoy']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="predio_cabezal_id" class="form-label fw-bold text-muted">Lugar (Cabezal/Inyección)</label>
                                <select class="form-select" id="predio_cabezal_id" name="predio_cabezal_id" required>
                                    <option value="" disabled <?php echo !isset($data['registro']) ? 'selected' : ''; ?>>Seleccione Caseta...</option>
                                    <?php foreach($data['predios'] as $p): ?>
                                        <option value="<?php echo $p->id; ?>" 
                                            <?php echo (isset($data['registro']) && $data['registro']->predio_cabezal_id == $p->id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p->nombre); ?> 
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <label class="form-label fw-bold text-primary-dark-green mb-2">Productos a aplicar:</label>
                        
                        <div id="lista-productos">
                            </div>

                        <?php if (!isset($data['registro'])): ?>
                        <div class="mb-4">
                            <button type="button" class="btn btn-outline-secondary btn-sm dashed-border w-100" id="btn-agregar-producto">
                                <i class="bi bi-plus-circle me-2"></i> Añadir producto
                            </button>
                        </div>
                        <?php endif; ?>

                        <hr>

                        <div class="d-flex gap-2">
                            <a href="<?php echo URL_ROOT; ?>/fertilizacion/historial" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-accent-calendula fw-bold flex-grow-1 shadow-sm">
                                <i class="bi bi-check-circle-fill me-2"></i> 
                                <?php echo isset($data['registro']) ? 'Guardar Cambios' : 'Registrar Aplicación Completa'; ?>
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="row-template">
    <div class="row g-2 mb-3 mb-sm-2 align-items-end product-row animation-fade-in border-bottom pb-3 border-bottom-sm-0 pb-sm-0">
        
        <div class="col-12 col-sm-6">
            <label class="form-label small text-muted mb-1">Producto</label>
            <select class="form-select select-producto" name="fertilizante_id[]" required>
                <option value="" selected disabled>Seleccione...</option>
                <?php foreach($data['fertilizantes'] as $f): ?>
                    <option value="<?php echo $f->id; ?>" data-unidad="<?php echo $f->tipo_unidad; ?>">
                        <?php echo htmlspecialchars($f->nombre_comercial); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-9 col-sm-4">
            <label class="form-label small text-muted mb-1">Cantidad</label>
            <div class="input-group">
                <input type="number" step="0.01" class="form-control fw-bold" name="cantidad_aplicada[]" placeholder="0.00" required>
                <span class="input-group-text bg-white text-muted small label-unidad fw-bold">-</span>
            </div>
        </div>
        
        <div class="col-3 col-sm-2 text-end">
            <button type="button" class="btn btn-outline-danger border-0 btn-eliminar-fila w-100" title="Quitar">
                <i class="bi bi-x-lg"></i> <span class="d-sm-none small ms-1"></span>
            </button>
        </div>
    </div>
</template>

<style>
    .dashed-border { border: 2px dashed #ccc; }
    .animation-fade-in { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('lista-productos');
    const template = document.getElementById('row-template');
    const btnAdd = document.getElementById('btn-agregar-producto');
    
    // Datos para edición (inyectados desde PHP si existen)
    const isEdit = <?php echo isset($data['registro']) ? 'true' : 'false'; ?>;
    const editData = <?php echo isset($data['registro']) ? json_encode($data['registro']) : 'null'; ?>;

    // Función para crear una fila
    function addRow(data = null) {
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.product-row');
        
        const select = row.querySelector('.select-producto');
        const input = row.querySelector('input[name="cantidad_aplicada[]"]');
        const spanUnidad = row.querySelector('.label-unidad');
        const btnDel = row.querySelector('.btn-eliminar-fila');

        // Lógica de unidad dinámica al cambiar el select
        select.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const unidad = option.getAttribute('data-unidad');
            spanUnidad.textContent = (unidad === 'lt') ? 'Lt' : 'Kg';
        });

        // Lógica de eliminación
        btnDel.addEventListener('click', function() {
            if (container.querySelectorAll('.product-row').length > 1) {
                row.remove();
            } else {
                // Si es la última, solo limpiamos valores
                select.value = "";
                input.value = "";
                spanUnidad.textContent = "-";
            }
        });

        // Si hay datos (Edición), pre-llenar
        if (data) {
            select.value = data.fertilizante_id;
            input.value = parseFloat(data.cantidad_aplicada);
            // Disparar evento manual para actualizar la unidad visual
            select.dispatchEvent(new Event('change'));
            // En edición ocultamos el botón eliminar para no romper la lógica de ID único
            btnDel.style.display = 'none';
        }

        container.appendChild(row);
    }

    // Inicialización
    if (isEdit && editData) {
        addRow(editData);
    } else {
        addRow(); // Agregar primera fila vacía por defecto
    }

    if (btnAdd) {
        btnAdd.addEventListener('click', () => addRow());
    }
});
</script>