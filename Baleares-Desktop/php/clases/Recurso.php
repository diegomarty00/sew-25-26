<?php
class Recurso {
    private mysqli $conexion;

    public function __construct(mysqli $conexion) {
        $this->conexion = $conexion;
    }

    public function listar(): array {
        $sql = "SELECT
                    r.id_recurso,
                    r.nombre,
                    r.plazas_maximas,
                    r.fecha_hora_inicio,
                    r.fecha_hora_fin,
                    r.precio,
                    r.descripcion,
                    t.nombre AS tipo
                FROM recursos r
                INNER JOIN tipos_recurso t ON r.id_tipo = t.id_tipo
                ORDER BY r.fecha_hora_inicio ASC";

        $resultado = $this->conexion->query($sql);
        $recursos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $fila["plazas_disponibles"] = $this->obtenerPlazasDisponibles((int) $fila["id_recurso"]);
            $recursos[] = $fila;
        }

        return $recursos;
    }

    public function obtenerPorId(int $idRecurso): ?array {
        $sql = "SELECT
                    r.id_recurso,
                    r.nombre,
                    r.plazas_maximas,
                    r.fecha_hora_inicio,
                    r.fecha_hora_fin,
                    r.precio,
                    r.descripcion,
                    t.nombre AS tipo
                FROM recursos r
                INNER JOIN tipos_recurso t ON r.id_tipo = t.id_tipo
                WHERE r.id_recurso = ?";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("i", $idRecurso);
        $sentencia->execute();

        $resultado = $sentencia->get_result();

        if ($resultado->num_rows === 0) {
            return null;
        }

        $recurso = $resultado->fetch_assoc();
        $recurso["plazas_disponibles"] = $this->obtenerPlazasDisponibles($idRecurso);

        return $recurso;
    }

    public function obtenerPlazasDisponibles(int $idRecurso): int {
        $recurso = $this->obtenerBasico($idRecurso);

        if ($recurso === null) {
            return 0;
        }

        $reservadas = $this->obtenerPlazasReservadas($idRecurso);

        return (int) $recurso["plazas_maximas"] - $reservadas;
    }

    private function obtenerBasico(int $idRecurso): ?array {
        $sql = "SELECT id_recurso, plazas_maximas
                FROM recursos
                WHERE id_recurso = ?";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("i", $idRecurso);
        $sentencia->execute();

        $resultado = $sentencia->get_result();

        if ($resultado->num_rows === 0) {
            return null;
        }

        return $resultado->fetch_assoc();
    }

    private function obtenerPlazasReservadas(int $idRecurso): int {
        $sql = "SELECT COALESCE(SUM(numero_plazas), 0) AS plazas_reservadas
                FROM reservas
                WHERE id_recurso = ?
                AND id_estado <> 3";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("i", $idRecurso);
        $sentencia->execute();

        $resultado = $sentencia->get_result();
        $fila = $resultado->fetch_assoc();

        return (int) $fila["plazas_reservadas"];
    }
}
?>