<?php
class Reserva {
    private mysqli $conexion;
    private Recurso $recursos;

    public function __construct(mysqli $conexion, Recurso $recursos) {
        $this->conexion = $conexion;
        $this->recursos = $recursos;
    }

    public function calcularPresupuesto(int $idRecurso, int $plazas): ?float {
        $recurso = $this->recursos->obtenerPorId($idRecurso);

        if ($recurso === null || $plazas <= 0) {
            return null;
        }

        return (float) $recurso["precio"] * $plazas;
    }

    public function crear(int $idUsuario, int $idRecurso, int $plazas): bool {
        $recurso = $this->recursos->obtenerPorId($idRecurso);

        if ($recurso === null || $plazas <= 0) {
            return false;
        }

        if ($plazas > (int) $recurso["plazas_disponibles"]) {
            return false;
        }

        $presupuesto = $this->calcularPresupuesto($idRecurso, $plazas);

        if ($presupuesto === null) {
            return false;
        }

        $estadoConfirmada = 2;

        $sql = "INSERT INTO reservas (id_usuario, id_recurso, id_estado, numero_plazas, presupuesto)
                VALUES (?, ?, ?, ?, ?)";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("iiiid", $idUsuario, $idRecurso, $estadoConfirmada, $plazas, $presupuesto);

        return $sentencia->execute();
    }

    public function listarPorUsuario(int $idUsuario): array {
        $sql = "SELECT
                    res.id_reserva,
                    rec.nombre AS recurso,
                    rec.fecha_hora_inicio,
                    rec.fecha_hora_fin,
                    res.numero_plazas,
                    res.presupuesto,
                    res.fecha_reserva,
                    est.nombre AS estado
                FROM reservas res
                INNER JOIN recursos rec ON res.id_recurso = rec.id_recurso
                INNER JOIN estados_reserva est ON res.id_estado = est.id_estado
                WHERE res.id_usuario = ?
                ORDER BY res.fecha_reserva DESC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("i", $idUsuario);
        $sentencia->execute();

        $resultado = $sentencia->get_result();
        $reservas = [];

        while ($fila = $resultado->fetch_assoc()) {
            $reservas[] = $fila;
        }

        return $reservas;
    }

    public function anular(int $idReserva, int $idUsuario): bool {
        $estadoAnulada = 3;

        $sql = "UPDATE reservas
                SET id_estado = ?
                WHERE id_reserva = ?
                AND id_usuario = ?
                AND id_estado <> ?";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("iiii", $estadoAnulada, $idReserva, $idUsuario, $estadoAnulada);
        $sentencia->execute();

        return $sentencia->affected_rows > 0;
    }
}
?>