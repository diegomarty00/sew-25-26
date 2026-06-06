<?php
require_once "clases/Conexion.php";
require_once "clases/Plantilla.php";
require_once "clases/Recurso.php";
require_once "clases/Reserva.php";
require_once "clases/Seguridad.php";

class PaginaReservar
{
    private Plantilla $plantilla;
    private Recurso $recursos;
    private Reserva $reservas;
    private Seguridad $seguridad;
    private string $mensaje = "";

    public function __construct()
    {
        $conexion = new Conexion();
        $this->recursos = new Recurso($conexion->getConexion());
        $this->reservas = new Reserva($conexion->getConexion(), $this->recursos);
        $this->plantilla = new Plantilla();
        $this->seguridad = new Seguridad();
        $this->seguridad->iniciarSesion();
        $this->seguridad->exigirAutenticacion();
    }

    public function ejecutar(): void
    {
        $idRecurso = $this->obtenerIdRecurso();
        $recurso = $this->recursos->obtenerPorId($idRecurso);

        if ($recurso === null) {
            $this->mostrarError("El recurso turístico seleccionado no existe.");
            return;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->procesarReserva($recurso);
            $recurso = $this->recursos->obtenerPorId($idRecurso);
        }

        $this->mostrar($recurso);
    }

    private function procesarReserva(array $recurso): void
    {
        $plazas = isset($_POST["plazas"]) ? (int) $_POST["plazas"] : 0;
        $accion = isset($_POST["accion"]) ? (string) $_POST["accion"] : "";

        if ($plazas <= 0) {
            $this->mensaje = "Debe indicar un número de plazas válido.";
            return;
        }

        if ($plazas > (int) $recurso["plazas_disponibles"]) {
            $this->mensaje = "No hay plazas suficientes para este recurso.";
            return;
        }

        $presupuesto = $this->reservas->calcularPresupuesto((int) $recurso["id_recurso"], $plazas);

        if ($accion === "presupuesto") {
            $this->mensaje = "Presupuesto: " . number_format((float) $presupuesto, 2, ",", ".") . " euros.";
            return;
        }

        if ($accion === "confirmar") {
            $creada = $this->reservas->crear(
                $this->seguridad->getIdUsuario(),
                (int) $recurso["id_recurso"],
                $plazas
            );

            $this->mensaje = $creada ? "Reserva confirmada correctamente." : "No se pudo confirmar la reserva.";
        }
    }

    private function mostrar(array $recurso): void
    {
        $this->plantilla->mostrarInicioDocumento("Baleares - Reservar recurso", "Reserva de recurso turístico");
        $this->plantilla->mostrarCabecera("reservas");
        $this->plantilla->mostrarMigas("Reservar recurso");

        echo '<main>';
        $this->plantilla->mostrarMenuReservas();

        echo '<section>';
        echo '<h2>Reservar recurso turístico</h2>';

        if ($this->mensaje !== "") {
            echo '<p>' . htmlspecialchars($this->mensaje, ENT_QUOTES, "UTF-8") . '</p>';
        }

        echo '<article>';
        echo '<h3>' . htmlspecialchars($recurso["nombre"], ENT_QUOTES, "UTF-8") . '</h3>';
        echo '<p>' . htmlspecialchars($recurso["descripcion"], ENT_QUOTES, "UTF-8") . '</p>';
        echo '<p>Precio por plaza: ' . number_format((float) $recurso["precio"], 2, ",", ".") . ' euros.</p>';
        echo '<p>Plazas disponibles: ' . (int) $recurso["plazas_disponibles"] . '.</p>';
        echo '</article>';

        echo '<form action="reservar.php?id_recurso=' . (int) $recurso["id_recurso"] . '" method="post">';
        echo '<fieldset>';
        echo '<legend>Datos de la reserva</legend>';
        echo '<p><label for="plazas">Número de plazas</label>';
        echo '<input type="number" id="plazas" name="plazas" min="1" required></p>';
        echo '</fieldset>';

        echo '<p><button type="submit" name="accion" value="presupuesto">Generar presupuesto</button></p>';
        echo '<p><button type="submit" name="accion" value="confirmar">Confirmar reserva</button></p>';
        echo '</form>';
        echo '</section>';
        echo '</main>';

        $this->plantilla->mostrarPie();
    }

    private function mostrarError(string $mensaje): void
    {
        $this->plantilla->mostrarInicioDocumento("Baleares - Error", "Error en reserva");
        $this->plantilla->mostrarCabecera("reservas");
        $this->plantilla->mostrarMigas("Error");

        echo '<main>';
        echo '<section>';
        echo '<h2>Error</h2>';
        echo '<p>' . htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") . '</p>';
        echo '</section>';
        echo '</main>';

        $this->plantilla->mostrarPie();
    }

    private function obtenerIdRecurso(): int
    {
        return isset($_GET["id_recurso"]) ? (int) $_GET["id_recurso"] : 0;
    }
}

$pagina = new PaginaReservar();
$pagina->ejecutar();
