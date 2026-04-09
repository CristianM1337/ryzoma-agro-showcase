<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12 mx-auto">
            
            <?php SessionHelper::displayFlash(); ?>

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 py-3">
                    <h4 class="mb-0 text-primary-dark-green fw-bold"><?php echo $data['titulo']; ?></h4>
                    <a href="<?php echo URL_ROOT; ?>/fertilizante/form" class="btn btn-accent-calendula shadow-sm"> 
                        <i class="bi bi-plus-circle me-2"></i> Nuevo Producto
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 text-uppercase text-muted small">Nombre Comercial</th>
                                    <th class="text-uppercase text-muted small">Tipo</th>
                                    <th class="text-center text-uppercase text-muted small">N - P - K (%)</th>
                                    <th class="text-uppercase text-muted small">Componente Extra</th>
                                    <th class="text-end pe-4 text-uppercase text-muted small">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data['fertilizantes'])): ?>
                                    <tr>
                                       <td colspan="5" class="text-center py-5 text-muted">
                                           <i class="bi bi-bucket fs-1 d-block mb-2 opacity-50"></i>
                                           No hay productos registrados en el catálogo.
                                       </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($data['fertilizantes'] as $prod): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">
                                            <?php echo htmlspecialchars($prod->nombre_comercial); ?>
                                            <span class="badge bg-light text-muted border ms-2"><?php echo $prod->tipo_unidad; ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                $badgeClass = 'bg-secondary';
                                                if($prod->tipo_producto == 'fertilizante') $badgeClass = 'bg-success';
                                                if($prod->tipo_producto == 'biostimulante') $badgeClass = 'bg-info text-dark';
                                                if($prod->tipo_producto == 'enmienda') $badgeClass = 'bg-warning text-dark';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?> text-uppercase" style="font-size: 0.7rem;">
                                                <?php echo $prod->tipo_producto; ?>
                                            </span>
                                        </td>
                                        <td class="text-center font-monospace">
                                            <?php 
                                                // Formato visual N-P-K
                                                echo ($prod->porcentaje_n > 0 ? floatval($prod->porcentaje_n) : '-') . ' - ';
                                                echo ($prod->porcentaje_p > 0 ? floatval($prod->porcentaje_p) : '-') . ' - ';
                                                echo ($prod->porcentaje_k > 0 ? floatval($prod->porcentaje_k) : '-');
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($prod->componente_extra_nombre)): ?>
                                                <span class="text-primary fw-bold small">
                                                    <?php echo htmlspecialchars($prod->componente_extra_nombre); ?>:
                                                </span> 
                                                <?php echo floatval($prod->componente_extra_porcentaje); ?>%
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <a href="<?php echo URL_ROOT; ?>/fertilizante/form/<?php echo $prod->id; ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar">
                                                    <i class="bi bi-pencil-square fs-5"></i>
                                                </a>
                                                <form action="<?php echo URL_ROOT; ?>/fertilizante/eliminar/<?php echo $prod->id; ?>" method="POST" onsubmit="return confirm('¿Desactivar este producto?');">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Eliminar">
                                                        <i class="bi bi-trash fs-5"></i>
                                                    </button>
                                                </form>
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
    </div>
</div>