<?php
/**
 * Controlador Admin para Fertilizantes (CRUD)
 * Arquitectura v3.2 - Soporte para Densidad
 */
class FertilizanteController extends Controller {
    
    private $fertilizanteModel;

    public function __construct() {
        parent::__construct();
        $this->protect(['Admin']); // Solo Admin configura productos
        $this->fertilizanteModel = $this->model('FertilizanteModel');
    }

    public function index() {
        $data = [
            'titulo' => 'Catálogo de Fertilizantes e Insumos',
            'fertilizantes' => $this->fertilizanteModel->obtenerTodos(),
            'breadcrumbs' => [
                ['label' => 'Administración', 'url' => URL_ROOT . '/admin'],
                ['label' => 'Fertilizantes']
            ]
        ];
        $this->view('fertilizantes/index', $data);
    }

    public function form($id = null) {
        $data = [
            'titulo' => 'Nuevo Producto',
            'producto' => (object)[
                'id' => null,
                'nombre_comercial' => '',
                'tipo_producto' => 'fertilizante',
                'tipo_unidad' => 'kg',
                'densidad' => '1.000', // Valor por defecto
                'porcentaje_n' => '', 'porcentaje_p' => '', 'porcentaje_k' => '',
                'componente_extra_nombre' => '', 'componente_extra_porcentaje' => ''
            ],
            'breadcrumbs' => [
                ['label' => 'Catálogo', 'url' => URL_ROOT . '/fertilizante'],
                ['label' => $id ? 'Editar' : 'Crear']
            ]
        ];

        if ($id) {
            $prod = $this->fertilizanteModel->obtenerPorId($id);
            if ($prod) {
                $data['titulo'] = 'Editar: ' . $prod->nombre_comercial;
                $data['producto'] = $prod;
            } else {
                SessionHelper::setFlash('Producto no encontrado', 'danger');
                $this->redirect('fertilizante/index');
            }
        }

        $this->view('fertilizantes/form', $data);
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') $this->redirect('fertilizante/index');
        
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $id = $_POST['id'] ?? null;
        
        $datos = [
            'nombre_comercial' => trim($_POST['nombre_comercial']),
            'tipo_producto' => $_POST['tipo_producto'],
            'tipo_unidad' => $_POST['tipo_unidad'],
            // NUEVO: Capturamos densidad. Si es Kg o viene vacía, forzamos 1.0
            'densidad' => ($_POST['tipo_unidad'] === 'lt' && !empty($_POST['densidad'])) ? $_POST['densidad'] : 1.000,
            
            'porcentaje_n' => $_POST['porcentaje_n'],
            'porcentaje_p' => $_POST['porcentaje_p'],
            'porcentaje_k' => $_POST['porcentaje_k'],
            'componente_extra_nombre' => trim($_POST['componente_extra_nombre']),
            'componente_extra_porcentaje' => $_POST['componente_extra_porcentaje']
        ];

        if (empty($datos['nombre_comercial'])) {
            SessionHelper::setFlash('El nombre es obligatorio', 'danger');
            $this->redirect('fertilizante/form/' . $id);
            return;
        }

        try {
            if ($id) {
                $this->fertilizanteModel->actualizar($id, $datos);
                SessionHelper::setFlash('Producto actualizado.', 'success');
            } else {
                $this->fertilizanteModel->crear($datos);
                SessionHelper::setFlash('Producto creado.', 'success');
            }
        } catch (Exception $e) {
            SessionHelper::setFlash('Error: ' . $e->getMessage(), 'danger');
        }
        
        $this->redirect('fertilizante/index');
    }
    
    public function eliminar($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->fertilizanteModel->desactivar($id);
            SessionHelper::setFlash('Producto desactivado.', 'success');
        }
        $this->redirect('fertilizante/index');
    }
}
?>