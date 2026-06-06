<?php
require_once "clases/Conexion.php";
require_once "clases/Plantilla.php";
require_once "clases/Recurso.php";
require_once "clases/Reserva.php";
require_once "clases/Seguridad.php";

class PaginaMisReservas {
    private Plantilla $plantilla;
    private Reserva $reservas;
    private Seguridad $seguridad;

    public function __construct() {
        $conexion = new Conexion();
        $recursos = new Recurso($conexion->getConexion());
        $this->reservas = new Reserva($conexion->getConexion(), $recursos);
        $this->plantilla = new Plantilla();
        $this->seguridad = new Seguridad();
        $this->seguridad->iniciarSesion();
        $this->seguridad->exigirAutenticacion();
    }

    public function ejecutar(): void {
        $lista = $this->reservas->listarPorUsuario($this->seguridad->getIdUsuario());

        $this->plantilla->mostrarInicioDocumento("Baleares - Mis reservas", "Consulta de reservas del usuario");
        $this->plantilla->mostrarCabecera("reservas");
        $this->plantilla->mostrarMigas("Mis reservas");

        echo '<main>';
        $this->plantilla->mostrarMenuReservas();

        echo '<section>';
        echo '<h2>Mis reservas</h2>';

        if (count($lista) === 0) {
            echo '<p>No tiene reservas registradas.</p>';
        }

        foreach ($lista as $reserva) {
            echo '<article>';
            echo '<h3>Reserva ' . (int) $reserva["id_reserva"] . '</h3>';
            echo '<dl>';
            echo '<dt>Recurso</dt><dd>' . htmlspecialchars($reserva["recurso"], ENT_QUOTES, "UTF-8") . '</dd>';
            echo '<dt>Inicio</dt><dd>' . htmlspecialchars($reserva["fecha_hora_inicio"], ENT_QUOTES, "UTF-8") . '</dd>';
            echo '<dt>Fin</dt><dd>' . htmlspecialchars($reserva["fecha_hora_fin"], ENT_QUOTES, "UTF-8") . '</dd>';
            echo '<dt>Plazas</dt><dd>' . (int) $reserva["numero_plazas"] . '</dd>';
            echo '<dt>Presupuesto</dt><dd>' . number_format((float) $reserva["presupuesto"], 2, ",", ".") . ' euros</dd>';
            echo '<dt>Estado</dt><dd>' . htmlspecialchars($reserva["estado"], ENT_QUOTES, "UTF-8") . '</dd>';
            echo '</dl>';

            if ($reserva["estado"] !== "Anulada") {
                $this->plantilla->mostrarEnlaceAccion("anular.php?id_reserva=" . (int) $reserva["id_reserva"], "Anular esta reserva");
            }

            echo '</article>';
        }

        echo '</section>';
        echo '</main>';

        $this->plantilla->mostrarPie();
    }
}

$pagina = new PaginaMisReservas();
$pagina->ejecutar();
?>