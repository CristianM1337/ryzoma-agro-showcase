<?php
/**
 * Controlador Operativo de Fertirrigación
 * Maneja: Registro, Edición y Configuración
 */
class FertilizacionController extends Controller {
    
    private $fertilizacionService;
    private $fertilizanteModel;
    private $predioModel;
    private $configRiegoModel;

    public function __construct() {
        parent::__construct();
        
        require_once APP_ROOT . '/core/FertilizacionService.php';
        $this->fertilizacionService = new FertilizacionService($this->empresa_id);
        
        $this->fertilizanteModel = $this->model('FertilizanteModel');
        $this->predioModel = $this->model('PredioModel');
        
        require_once APP_ROOT . '/models/ConfiguracionRiegoModel.php';
        $this->configRiegoModel = new ConfiguracionRiegoModel($this->db, $this->empresa_id);
        
        $this->protect(['Admin', 'Usuario_riego', 'Usuario_general']);
    }

    // --- VISTA OPERARIO (Registro/Edición) ---

    public function index() {
        // Vista limpia para registro nuevo
        $this->cargarFormulario();
    }

    public function editar($id) {
        // Vista precargada para edición
        if (!$id) $this->redirect('fertilizacion/historial');
        
        $registro = $this->fertilizacionService->obtenerCabezalPorId($id);
        
        if (!$registro) {
            SessionHelper::setFlash('Registro no encontrado o no pertenece a su empresa.', 'danger');
            $this->redirect('fertilizacion/historial');
        }

        $this->cargarFormulario($registro);
    }

    // Helper privado para no repetir código en index() y editar()
    private function cargarFormulario($registro = null) {
        $data = [
            'titulo' => $registro ? 'Editar Aplicación #' . $registro->id : 'Registro de Fertirrigación',
            'predios' => $this->predioModel->obtenerPuntosInyeccion(), 
            'fertilizantes' => $this->fertilizanteModel->obtenerActivos(),
            'fecha_hoy' => $registro ? $registro->fecha : date('Y-m-d'),
            'registro' => $registro, // Objeto con datos si es edición, null si es nuevo
            'breadcrumbs' => [
                ['label' => 'Historial', 'url' => URL_ROOT . '/fertilizacion/historial'],
                ['label' => $registro ? 'Editar' : 'Nuevo Registro']
            ]
        ];
        $this->view('fertilizacion/registro', $data);
    }

    public function guardarRegistro() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') $this->redirect('fertilizacion/index');
        
        $id = $_POST['id'] ?? null; // ID para edición (solo edición simple por ahora)
        
        // Capturamos los datos comunes
        $fecha = $_POST['fecha'];
        $cabezal_id = $_POST['predio_cabezal_id'];
        
        // Capturamos los arrays
        $fertilizantes = $_POST['fertilizante_id'] ?? []; // Ahora es un array
        $cantidades = $_POST['cantidad_aplicada'] ?? []; // Ahora es un array

        // Validaciones básicas de cabecera
        if (empty($fecha) || empty($cabezal_id)) {
            SessionHelper::setFlash('La fecha y el lugar son obligatorios.', 'danger');
            $this->redirect('fertilizacion/index');
            return;
        }

        $registros_exitosos = 0;
        $errores = 0;

        try {
            // Si es EDICIÓN (Modo simple, un solo registro por ID)
            if ($id) {
                // En edición, el array tendrá solo 1 elemento (índice 0)
                $datos = [
                    'empresa_id' => $this->empresa_id,
                    'usuario_id' => $this->usuario_id,
                    'fecha' => $fecha,
                    'predio_cabezal_id' => $cabezal_id,
                    'fertilizante_id' => $fertilizantes[0], // Primer elemento
                    'cantidad_aplicada' => $cantidades[0]   // Primer elemento
                ];
                
                if ($this->fertilizacionService->actualizarAplicacion($id, $datos)) {
                    SessionHelper::setFlash('Registro actualizado correctamente.', 'success');
                } else {
                    SessionHelper::setFlash('Error al actualizar el registro.', 'danger');
                }

            } else {
                // Si es CREACIÓN (Modo Mezcla / Bucle)
                // Iteramos sobre los arrays enviados
                for ($i = 0; $i < count($fertilizantes); $i++) {
                    
                    // Saltamos filas vacías si las hubiera
                    if (empty($fertilizantes[$i]) || empty($cantidades[$i])) continue;

                    $datos = [
                        'empresa_id' => $this->empresa_id,
                        'usuario_id' => $this->usuario_id,
                        'fecha' => $fecha,
                        'predio_cabezal_id' => $cabezal_id,
                        'fertilizante_id' => $fertilizantes[$i],
                        'cantidad_aplicada' => $cantidades[$i]
                    ];

                    // Validamos duplicados para cada ítem de la lista
                    // (Podríamos omitir esto si queremos permitir doble carga, pero es mejor prevenir)
                    $duplicado = $this->fertilizacionService->verificarDuplicado(
                        $datos['fecha'], $datos['predio_cabezal_id'], $datos['fertilizante_id']
                    );

                    if ($duplicado) {
                        // Opción: Omitir silenciosamente o contar como error.
                        // Para simplificar, lo contaremos como advertencia y no lo guardamos.
                        $errores++;
                        continue; 
                    }

                    if ($this->fertilizacionService->registrarAplicacion($datos)) {
                        $registros_exitosos++;
                    } else {
                        $errores++;
                    }
                }

                // Feedback al usuario
                if ($registros_exitosos > 0) {
                    $msg = "Se registraron {$registros_exitosos} productos en la mezcla correctamente.";
                    if ($errores > 0) $msg .= " (Hubo {$errores} omitidos por duplicidad o error).";
                    SessionHelper::setFlash($msg, 'success');
                } elseif ($errores > 0) {
                    SessionHelper::setFlash("No se pudo registrar la mezcla. Posibles duplicados.", 'warning');
                } else {
                    SessionHelper::setFlash("No se enviaron datos válidos.", 'info');
                }
            }

        } catch (Exception $e) {
            SessionHelper::setFlash('Excepción del sistema: ' . $e->getMessage(), 'danger');
        }
        
        $this->redirect('fertilizacion/historial');
    }

    // --- AJAX: VERIFICACIÓN DE EXISTENCIA ---
    public function verificarExistenciaAjax() {
        $fecha = $_POST['fecha'] ?? '';
        $cabezal = $_POST['cabezal'] ?? '';
        $producto = $_POST['producto'] ?? '';
        $excludeId = $_POST['exclude_id'] ?? null;

        if (!$fecha || !$cabezal || !$producto) {
            $this->respondJson(['existe' => false]);
            return;
        }

        $registro = $this->fertilizacionService->verificarDuplicado($fecha, $cabezal, $producto, $excludeId);

        if ($registro) {
            $this->respondJson([
                'existe' => true,
                'mensaje' => "Atención: Ya existe un registro de " . floatval($registro->cantidad_aplicada) . " unidades para este producto en esta fecha y lugar."
            ]);
        } else {
            $this->respondJson(['existe' => false]);
        }
    }

// --- VISTA HISTORIAL ---
public function historial() {
    $mes = $_GET['mes'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    // Capturamos parámetros de ordenamiento
    $orderBy = $_GET['sort'] ?? 'fecha';
    $orderDir = $_GET['dir'] ?? 'DESC';

    // Obtenemos los registros y el resumen
    $registros = $this->fertilizacionService->obtenerHistorialCabezal($mes, $year, $orderBy, $orderDir);
    $resumen = $this->fertilizacionService->obtenerResumenMensual($mes, $year);

    $data = [
        'titulo' => 'Bitácora de Fertilización',
        'mes_actual' => $mes,
        'year_actual' => $year,
        'sort' => $orderBy,
        'dir' => $orderDir,
        'registros' => $registros,
        'resumen' => $resumen, // Pasamos el resumen a la vista
        'breadcrumbs' => [
            ['label' => 'Fertirrigación', 'url' => URL_ROOT . '/fertilizacion'],
            ['label' => 'Historial']
        ]
    ];
    $this->view('fertilizacion/historial', $data);
}

// --- REPORTE GERENCIAL (NPK / Ha) ---
public function reporteNutricional() {
    // Definir Temporada: Si estamos en Nov 2025, la temporada empezó en Sep 2025.
    // Si estamos en Ene 2026, la temporada empezó en Sep 2025.
    $mesActual = date('n');
    $yearActual = date('Y');
    
    // Lógica de inicio de temporada (Septiembre = 9)
    if ($mesActual >= 9) {
        $inicioTemporada = $yearActual . '-09-01';
        $finTemporada = ($yearActual + 1) . '-08-31';
    } else {
        $inicioTemporada = ($yearActual - 1) . '-09-01';
        $finTemporada = $yearActual . '-08-31';
    }

    // Obtener datos
    $datosNutricionales = $this->fertilizacionService->obtenerReporteNutricionalTemporada($inicioTemporada, date('Y-m-d')); // Hasta hoy

    $data = [
        'titulo' => 'Reporte Nutricional Acumulado',
        'inicio_temporada' => $inicioTemporada,
        'datos' => $datosNutricionales,
        'breadcrumbs' => [
            ['label' => 'Fertirrigación', 'url' => URL_ROOT . '/fertilizacion'],
            ['label' => 'Reporte Gerencial']
        ]
    ];

    $this->view('fertilizacion/reporte_nutricional', $data);
}

    // --- EXPORTACIÓN A EXCEL (CSV) ---
    public function exportarExcelNutricional() {
        // Reutilizamos lógica de fechas
        $mesActual = date('n');
        $yearActual = date('Y');
        $inicioTemporada = ($mesActual >= 9) ? $yearActual . '-09-01' : ($yearActual - 1) . '-09-01';
        
        $datos = $this->fertilizacionService->obtenerReporteNutricionalTemporada($inicioTemporada, date('Y-m-d'));
        
        $filename = "Reporte_Nutricional_" . date('Y-m-d') . ".csv";
        
        // Headers para forzar descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // BOM para que Excel reconozca UTF-8 correctamente
        fputs($output, "\xEF\xBB\xBF");
        
        // Encabezados de columnas
        fputcsv($output, ['Sector/Predio', 'Cultivo', 'Superficie (Ha)', 'N (Unidades/Ha)', 'P (Unidades/Ha)', 'K (Unidades/Ha)', 'Total Extra (Unidades)'], ';');
        
        foreach ($datos as $row) {
            fputcsv($output, [
                $row->predio,
                $row->cultivo ?? 'N/A',
                number_format($row->hectareas, 2, ',', ''),
                number_format($row->n_ha, 2, ',', ''),
                number_format($row->p_ha, 2, ',', ''),
                number_format($row->k_ha, 2, ',', ''),
                number_format($row->total_extra, 2, ',', '')
            ], ';');
        }
        
        fclose($output);
        exit(); // Detener ejecución para no imprimir HTML
    }

    // --- GENERAR LINK COMPARTIBLE ---
    public function generarLinkPublico() {
        // Solo Admins pueden compartir
        $this->protect(['Admin']);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $token = $this->fertilizacionService->generarTokenReporte($this->usuario_id);
            
            if ($token) {
                $link = URL_ROOT . '/publico/reporte/' . $token;
                $this->respondJson(['success' => true, 'link' => $link]);
            } else {
                $this->respondJson(['success' => false, 'message' => 'Error al generar token.']);
            }
        }
    }
    public function verDetalleDistribucion($cabezalId) {
        $detalle = $this->fertilizacionService->obtenerDetalleDistribucion($cabezalId);
        $this->respondJson(['success' => true, 'detalle' => $detalle]);
    }

    // --- CONFIGURACIÓN Y OTROS ---
    public function configuracion() {
        $this->protect(['Admin']);
        $predios = $this->predioModel->obtenerPuntosInyeccion();
        foreach ($predios as $p) {
            $p->distribuciones = $this->configRiegoModel->obtenerPorOrigen($p->id);
        }
        $data = ['titulo' => 'Configuración de Distribución Hidráulica', 'predios' => $predios];
        $this->view('fertilizacion/configuracion', $data);
    }
    public function getDistribuciones($origen) { $this->protect(['Admin']); $this->respondJson($this->configRiegoModel->obtenerPorOrigen($origen)); }
    public function guardarDistribucion() { /* ... lógica existente ... */ 
        $this->protect(['Admin']);
        $origen = $_POST['origen_id']; $destino = $_POST['destino_id']; $porcentaje = $_POST['porcentaje'];
        if ($origen == $destino) { $this->respondJson(['success' => false, 'message' => 'Origen y destino iguales.']); return; }
        if ($this->configRiegoModel->guardarRelacion($origen, $destino, $porcentaje)) $this->respondJson(['success' => true]);
        else $this->respondJson(['success' => false, 'message' => 'Error al guardar.']);
    }
    public function eliminarDistribucion() { /* ... lógica existente ... */ 
        $this->protect(['Admin']);
        $id = $_POST['id'];
        if ($this->configRiegoModel->eliminarRelacion($id)) $this->respondJson(['success' => true]);
        else $this->respondJson(['success' => false]);
    }
}
?>