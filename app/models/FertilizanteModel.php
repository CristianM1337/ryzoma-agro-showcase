<?php
/**
 * Modelo para el Catálogo de Fertilizantes e Insumos
 * Arquitectura v3.1 (Flexible NPK + Extra)
 */
class FertilizanteModel {
    private $db;
    private $empresa_id;

    public function __construct($db, $empresa_id) {
        $this->db = $db;
        $this->empresa_id = $empresa_id;
    }

    // --- CRUD Básico ---

    public function obtenerTodos() {
        $sql = "SELECT * FROM fertilizantes 
                WHERE empresa_id = :empresa_id 
                ORDER BY nombre_comercial ASC";
        $this->db->query($sql);
        $this->db->bind(':empresa_id', $this->empresa_id);
        return $this->db->resultSet();
    }

    public function obtenerActivos() {
        $sql = "SELECT * FROM fertilizantes WHERE empresa_id = :empresa_id AND activo = 1 ORDER BY nombre_comercial ASC";
        $this->db->query($sql);
        $this->db->bind(':empresa_id', $this->empresa_id);
        return $this->db->resultSet();
    }
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM fertilizantes WHERE id = :id AND empresa_id = :empresa_id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $this->db->bind(':empresa_id', $this->empresa_id);
        return $this->db->single();
    }

    public function crear($datos) {
        $sql = "INSERT INTO fertilizantes (
                    empresa_id, nombre_comercial, tipo_producto, tipo_unidad, densidad,
                    porcentaje_n, porcentaje_p, porcentaje_k,
                    componente_extra_nombre, componente_extra_porcentaje, activo
                ) VALUES (
                    :empresa, :nombre, :tipo, :unidad, :densidad,
                    :n, :p, :k,
                    :extra_nom, :extra_porc, 1
                )";
        
        $this->db->query($sql);
        $this->bindParams($datos);
        return $this->db->execute();
    }

    public function actualizar($id, $datos) {
        $sql = "UPDATE fertilizantes SET
                    nombre_comercial = :nombre,
                    tipo_producto = :tipo,
                    tipo_unidad = :unidad,
                    densidad = :densidad,
                    porcentaje_n = :n,
                    porcentaje_p = :p,
                    porcentaje_k = :k,
                    componente_extra_nombre = :extra_nom,
                    componente_extra_porcentaje = :extra_porc
                WHERE id = :id AND empresa_id = :empresa";
        
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $this->bindParams($datos);
        return $this->db->execute();
    }

    public function desactivar($id) {
        $sql = "UPDATE fertilizantes SET activo = 0 WHERE id = :id AND empresa_id = :empresa";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $this->db->bind(':empresa', $this->empresa_id);
        return $this->db->execute();
    }

    // Helper para bindear parámetros repetitivos
    private function bindParams($datos) {
        $this->db->bind(':empresa', $this->empresa_id);
        $this->db->bind(':nombre', $datos['nombre_comercial']);
        $this->db->bind(':tipo', $datos['tipo_producto']);
        $this->db->bind(':unidad', $datos['tipo_unidad']);
        $this->db->bind(':densidad', !empty($datos['densidad']) ? $datos['densidad'] : 1.00);
        
        // Manejo de nulos/ceros para NPK
        $this->db->bind(':n', !empty($datos['porcentaje_n']) ? $datos['porcentaje_n'] : 0.00);
        $this->db->bind(':p', !empty($datos['porcentaje_p']) ? $datos['porcentaje_p'] : 0.00);
        $this->db->bind(':k', !empty($datos['porcentaje_k']) ? $datos['porcentaje_k'] : 0.00);
        
        // Manejo de Extra
        $this->db->bind(':extra_nom', !empty($datos['componente_extra_nombre']) ? $datos['componente_extra_nombre'] : null);
        $this->db->bind(':extra_porc', !empty($datos['componente_extra_porcentaje']) ? $datos['componente_extra_porcentaje'] : 0.00);
    }
}
?>