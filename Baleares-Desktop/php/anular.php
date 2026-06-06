<?php
require_once "clases/Conexion.php";
require_once "clases/Plantilla.php";
require_once "clases/Recurso.php";
require_once "clases/Reserva.php";
require_once "clases/Seguridad.php";

class PaginaAnular {
    private Plantilla $plantilla;
    private Reserva $reservas;
    private Seguridad $seguridad;
    private string $mensaje = "";

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
        $idReserva = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $idReserva = isset($_POST["id_reserva"]) ? (int) $_POST["id_reserva"] : 0;

            if ($this->reservas->anular($idReserva, $this->seguridad->getIdUsuario())) {
                $this->mensaje = "Reserva anulada correctamente.";
            } else {
                $this->mensaje = "No se pudo anular la reserva.";
            }
        }

        $this->mostrar($idReserva);
    }

    private function mostrar(int $idReserva): void {
        $this->plantilla->mostrarInicioDocumento("Baleares - Anular reserva", "Anulación de reservas del usuario");
        $this->plantilla->mostrarCabecera("reservas");
        $this->plantilla->mostrarMigas("Anular reserva");

        echo '<main>';
        $this->plantilla->mostrarMenuReservas();

        echo '<section>';
        echo '<h2>Anular reserva</h2>';

        if ($this->mensaje !== "") {
            echo '<p>' . htmlspecialchars($this->mensaje, ENT_QUOTES, "UTF-8") . '</p>';
            echo '<p><a href="reservas.php">Volver a mis reservas</a></p>';
        } else {
            echo '<p>Confirme la anulación de la reserva seleccionada.</p>';
            echo '<form action="anular.php?id_reserva=' . (int) $idReserva . '" method="post">';
            echo '<input type="hidden" name="id_reserva" value="' . (int) $idReserva . '">';
            echo '<p><button type="submit">Anular reserva</button></p>';
            echo '</form>';
        }

        echo '</section>';
        echo '</main>';

        $this->plantilla->mostrarPie();
    }
}

$pagina = new PaginaAnular();
$pagina->ejecutar();
?>