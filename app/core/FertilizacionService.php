<?php
// app/core/FertilizacionService.php 
class FertilizacionService {
    
    private $db;
    private $empresa_id;

    public function __construct($empresa_id) {
        $this->db = Database::getInstance();
        $this->empresa_id = $empresa_id;
    }

    // --- CREACIÓN ---
    public function registrarAplicacion($datos) {
        try {
            // Iniciamos la transacción (Ajusta 'beginTransaction' según la API de tu clase Database)
            $this->db->beginTransaction(); 

            // 1. Guardar Cabezal
            $cabezalId = $this->crearRegistroCabezal($datos);
            if (!$cabezalId) {
                $this->db->rollBack();
                return false;
            }

            // 2. Procesar Distribución
            $this->procesarDistribucion($cabezalId, $datos);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            // Re-lanzar la excepción para que el Controlador la atrape y muestre el mensaje flash
            throw $e; 
        }
    }

    // --- ACTUALIZACIÓN ---
    public function actualizarAplicacion($id, $datos) {
        try {
            $this->db->beginTransaction();

            // 1. Actualizar Cabezal
            $sql = "UPDATE fertilizaciones_cabezal SET 
                        predio_cabezal_id = :predio,
                        fertilizante_id = :fert,
                        fecha = :fecha,
                        cantidad_aplicada = :cant,
                        usuario_id = :user
                    WHERE id = :id AND empresa_id = :empresa";
            
            $this->db->query($sql);
            $this->db->bind(':id', $id);
            $this->db->bind(':empresa', $this->empresa_id);
            $this->db->bind(':predio', $datos['predio_cabezal_id']);
            $this->db->bind(':fert', $datos['fertilizante_id']);
            $this->db->bind(':fecha', $datos['fecha']);
            $this->db->bind(':cant', $datos['cantidad_aplicada']);
            $this->db->bind(':user', $datos['usuario_id']); 
            
            if (!$this->db->execute()) {
                $this->db->rollBack();
                return false;
            }

            // 2. Recalcular Distribución (Borrar y Recrear)
            $this->db->query("DELETE FROM fertilizaciones_reales WHERE fertilizacion_cabezal_id = :id AND empresa_id = :empresa");
            $this->db->bind(':id', $id);
            $this->db->bind(':empresa', $this->empresa_id);
            $this->db->execute();

            // 3. Re-crear Distribución
            $this->procesarDistribucion($id, $datos);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // --- HELPER DE PROCESAMIENTO (Reutilizable) ---
    private function procesarDistribucion($cabezalId, $datos) {
        // Obtener info del producto
        $infoFert = $this->obtenerInfoFertilizante($datos['fertilizante_id']);

        // Obtener reglas hidráulicas
        $distribuciones = $this->obtenerDistribuciones($datos['predio_cabezal_id']);
        
        $cantidadAplicada = (float)$datos['cantidad_aplicada'];
        $porcentajeSaliente = 0;
        $detalleDistribucion = [];

        // Calcular reparto
        foreach ($distribuciones as $dist) {
            $cantidadParcial = $cantidadAplicada * ($dist->porcentaje_flujo / 100);
            $detalleDistribucion[] = [
                'predio_destino_id' => $dist->predio_destino_id,
                'cantidad_nominal' => $cantidadParcial
            ];
            $porcentajeSaliente += $dist->porcentaje_flujo;
        }

        // Calcular remanente (lo que queda en el origen)
        $porcentajeRemanente = 100 - $porcentajeSaliente;
        if ($porcentajeRemanente > 0.01) { // Usar pequeña tolerancia float
            $detalleDistribucion[] = [
                'predio_destino_id' => $datos['predio_cabezal_id'],
                'cantidad_nominal' => $cantidadAplicada * ($porcentajeRemanente / 100)
            ];
        }

        // Guardar registros reales (outputs)
        foreach ($detalleDistribucion as $item) {
            $this->crearRegistroReal($cabezalId, $item['predio_destino_id'], $item['cantidad_nominal'], $infoFert);
        }
    }

    // --- OBTENER POR ID (Para editar) ---
    public function obtenerCabezalPorId($id) {
        $sql = "SELECT * FROM fertilizaciones_cabezal WHERE id = :id AND empresa_id = :empresa";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $this->db->bind(':empresa', $this->empresa_id);
        return $this->db->single();
    }

    // --- VERIFICAR DUPLICADOS ---
    public function verificarDuplicado($fecha, $cabezalId, $fertilizanteId, $excludeId = null) {
        $sql = "SELECT id, cantidad_aplicada, usuario_id FROM fertilizaciones_cabezal 
                WHERE fecha = :fecha 
                  AND predio_cabezal_id = :cabezal 
                  AND fertilizante_id = :fert 
                  AND empresa_id = :empresa";
        
        if ($excludeId) {
            $sql .= " AND id != :excludeId";
        }

        $this->db->query($sql);
        $this->db->bind(':fecha', $fecha);
        $this->db->bind(':cabezal', $cabezalId);
        $this->db->bind(':fert', $fertilizanteId);
        $this->db->bind(':empresa', $this->empresa_id);
        if ($excludeId) {
            $this->db->bind(':excludeId', $excludeId);
        }

        return $this->db->single();
    }

    // --- MÉTODOS DE LECTURA (HISTORIAL) ---
// --- Obtener Historial con Ordenamiento Dinámico ---
public function obtenerHistorialCabezal($mes, $year, $orderBy = 'fecha', $orderDir = 'DESC') {
    // Mapeo de campos permitidos para evitar inyección SQL
    $allowedColumns = [
        'fecha' => 'fc.fecha',
        'cabezal' => 'p.nombre',
        'producto' => 'f.nombre_comercial'
    ];
    
    $sortCol = $allowedColumns[$orderBy] ?? 'fc.fecha';
    $sortDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

    $sql = "SELECT 
                fc.id, fc.fecha, fc.cantidad_aplicada, fc.fecha_registro,
                f.nombre_comercial, f.tipo_producto, f.tipo_unidad,
                p.nombre as nombre_cabezal,
                u.username as nombre_usuario
            FROM fertilizaciones_cabezal fc
            JOIN fertilizantes f ON fc.fertilizante_id = f.id
            JOIN predios p ON fc.predio_cabezal_id = p.id
            LEFT JOIN usuarios u ON fc.usuario_id = u.id
            WHERE fc.empresa_id = :empresa 
              AND MONTH(fc.fecha) = :mes AND YEAR(fc.fecha) = :year
            ORDER BY $sortCol $sortDir, fc.id DESC"; // Query dinámico seguro

    $this->db->query($sql);
    $this->db->bind(':empresa', $this->empresa_id);
    $this->db->bind(':mes', $mes);
    $this->db->bind(':year', $year);
    return $this->db->resultSet();
}
// --- Resumen Mensual (Agrupado por Cabezal y Producto) ---
public function obtenerResumenMensual($mes, $year) {
    $sql = "SELECT 
                p.nombre as nombre_cabezal,
                f.nombre_comercial,
                f.tipo_unidad,
                SUM(fc.cantidad_aplicada) as total_cantidad
            FROM fertilizaciones_cabezal fc
            JOIN predios p ON fc.predio_cabezal_id = p.id
            JOIN fertilizantes f ON fc.fertilizante_id = f.id
            WHERE fc.empresa_id = :empresa 
              AND MONTH(fc.fecha) = :mes AND YEAR(fc.fecha) = :year
            GROUP BY p.nombre, f.nombre_comercial, f.tipo_unidad
            ORDER BY p.nombre ASC, f.nombre_comercial ASC";
    
    $this->db->query($sql);
    $this->db->bind(':empresa', $this->empresa_id);
    $this->db->bind(':mes', $mes);
    $this->db->bind(':year', $year);
    return $this->db->resultSet();
}

    // --- LÓGICA DE TOKENS PÚBLICOS ---

    public function generarTokenReporte($usuarioId, $tipo = 'nutricional_temporada', $diasValidez = 30) {
        // Generar un hash aleatorio seguro
        $token = bin2hex(random_bytes(32)); 
        $fechaExpiracion = date('Y-m-d H:i:s', strtotime("+$diasValidez days"));

        // Desactivar tokens anteriores del mismo tipo para mantener limpieza (opcional, política de 1 link activo)
        $this->db->query("UPDATE reportes_tokens SET activo = 0 WHERE empresa_id = :empresa AND tipo_reporte = :tipo");
        $this->db->bind(':empresa', $this->empresa_id);
        $this->db->bind(':tipo', $tipo);
        $this->db->execute();

        // Crear nuevo token
        $sql = "INSERT INTO reportes_tokens (empresa_id, usuario_creador_id, token, tipo_reporte, fecha_expiracion, activo) 
                VALUES (:empresa, :user, :token, :tipo, :expira, 1)";
        
        $this->db->query($sql);
        $this->db->bind(':empresa', $this->empresa_id);
        $this->db->bind(':user', $usuarioId);
        $this->db->bind(':token', $token);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':expira', $fechaExpiracion);
        
        if ($this->db->execute()) {
            return $token;
        }
        return false;
    }

    public function validarToken($token) {
        $sql = "SELECT t.*, e.nombre as nombre_empresa 
                FROM reportes_tokens t
                JOIN empresas e ON t.empresa_id = e.id
                WHERE t.token = :token AND t.activo = 1";
        
        $this->db->query($sql);
        $this->db->bind(':token', $token);
        $registro = $this->db->single();

        if (!$registro) return false;

        // Verificar expiración
        if ($registro->fecha_expiracion && strtotime($registro->fecha_expiracion) < time()) {
            return false;
        }

        return $registro;
    }

// --- Reporte Nutricional de Temporada (Unidades / Ha) ---
public function obtenerReporteNutricionalTemporada($fechaInicio, $fechaFin) {
    /**
     * Lógica:
     * 1. Usamos la tabla 'fertilizaciones_reales' que ya tiene el cálculo distribuido.
     * 2. Sumamos las unidades NPK y Extra.
     * 3. Dividimos por la superficie del predio destino (si superficie > 0).
     */
    $sql = "SELECT 
                p.nombre as predio,
                p.superficie_total as hectareas,
                c.nombre as cultivo,
                SUM(fr.unidades_n) as total_n,
                SUM(fr.unidades_p) as total_p,
                SUM(fr.unidades_k) as total_k,
                SUM(fr.unidades_extra) as total_extra,
                -- Cálculo de Unidades por Hectárea
                (SUM(fr.unidades_n) / NULLIF(p.superficie_total, 0)) as n_ha,
                (SUM(fr.unidades_p) / NULLIF(p.superficie_total, 0)) as p_ha,
                (SUM(fr.unidades_k) / NULLIF(p.superficie_total, 0)) as k_ha
            FROM fertilizaciones_reales fr
            JOIN fertilizaciones_cabezal fc ON fr.fertilizacion_cabezal_id = fc.id
            JOIN predios p ON fr.predio_destino_id = p.id
            LEFT JOIN cultivos c ON p.cultivo_id = c.id
            WHERE fr.empresa_id = :empresa
              AND fc.fecha BETWEEN :inicio AND :fin
            GROUP BY p.id, p.nombre, p.superficie_total
            ORDER BY p.nombre ASC";

    $this->db->query($sql);
    $this->db->bind(':empresa', $this->empresa_id);
    $this->db->bind(':inicio', $fechaInicio);
    $this->db->bind(':fin', $fechaFin);
    return $this->db->resultSet();
}

    public function obtenerDetalleDistribucion($cabezalId) {
        $sql = "SELECT fr.*, p.nombre as nombre_sector_destino
                FROM fertilizaciones_reales fr
                JOIN predios p ON fr.predio_destino_id = p.id
                WHERE fr.fertilizacion_cabezal_id = :cabezalId AND fr.empresa_id = :empresa";
        $this->db->query($sql);
        $this->db->bind(':cabezalId', $cabezalId);
        $this->db->bind(':empresa', $this->empresa_id);
        return $this->db->resultSet();
    }

    // --- HELPERS PRIVADOS (DB) ---
    private function crearRegistroCabezal($datos) {
        $sql = "INSERT INTO fertilizaciones_cabezal (empresa_id, predio_cabezal_id, usuario_id, fertilizante_id, fecha, cantidad_aplicada) 
                VALUES (:empresa, :predio, :user, :fert, :fecha, :cant)";
        $this->db->query($sql);
        $this->db->bind(':empresa', $this->empresa_id);
        $this->db->bind(':predio', $datos['predio_cabezal_id']);
        $this->db->bind(':user', $datos['usuario_id']);
        $this->db->bind(':fert', $datos['fertilizante_id']);
        $this->db->bind(':fecha', $datos['fecha']);
        $this->db->bind(':cant', $datos['cantidad_aplicada']);
        if ($this->db->execute()) return $this->db->lastInsertId();
        return false;
    }

    private function obtenerDistribuciones($predioOrigenId) {
        $sql = "SELECT predio_destino_id, porcentaje_flujo FROM config_distribucion_riego WHERE predio_origen_id = :origen AND empresa_id = :empresa";
        $this->db->query($sql);
        $this->db->bind(':origen', $predioOrigenId);
        $this->db->bind(':empresa', $this->empresa_id);
        return $this->db->resultSet();
    }

    private function obtenerInfoFertilizante($id) {
        $sql = "SELECT porcentaje_n, porcentaje_p, porcentaje_k, componente_extra_porcentaje, densidad, tipo_unidad FROM fertilizantes WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    private function crearRegistroReal($cabezalId, $predioDestinoId, $cantidadNominal, $infoFert) {
        $masaEfectiva = $cantidadNominal;
        if ($infoFert->tipo_unidad === 'lt') {
            $densidad = ($infoFert->densidad > 0) ? $infoFert->densidad : 1.0;
            $masaEfectiva = $cantidadNominal * $densidad;
        }
        $n = ($masaEfectiva * ($infoFert->porcentaje_n ?? 0)) / 100;
        $p = ($masaEfectiva * ($infoFert->porcentaje_p ?? 0)) / 100;
        $k = ($masaEfectiva * ($infoFert->porcentaje_k ?? 0)) / 100;
        $extra = ($masaEfectiva * ($infoFert->componente_extra_porcentaje ?? 0)) / 100;

        $sql = "INSERT INTO fertilizaciones_reales (empresa_id, fertilizacion_cabezal_id, predio_destino_id, cantidad_recibida, unidades_n, unidades_p, unidades_k, unidades_extra)
                VALUES (:empresa, :cabezal, :destino, :cant, :n, :p, :k, :extra)";
        $this->db->query($sql);
        $this->db->bind(':empresa', $this->empresa_id);
        $this->db->bind(':cabezal', $cabezalId);
        $this->db->bind(':destino', $predioDestinoId);
        $this->db->bind(':cant', $cantidadNominal);
        $this->db->bind(':n', $n);
        $this->db->bind(':p', $p);
        $this->db->bind(':k', $k);
        $this->db->bind(':extra', $extra);
        $this->db->execute();
    }
}
?>