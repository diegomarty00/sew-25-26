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
    private int $plazasSeleccionadas = 1;
    private string $presupuestoHtml = "";
    private bool $reservaRealizada = false;

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

            $recursoActualizado = $this->recursos->obtenerPorId($idRecurso);

            if ($recursoActualizado !== null) {
                $recurso = $recursoActualizado;
            }
        }

        $this->mostrar($recurso);
    }

    private function procesarReserva(array $recurso): void
    {
        $plazas = isset($_POST["plazas"]) ? (int) $_POST["plazas"] : 0;
        $accion = isset($_POST["accion"]) ? (string) $_POST["accion"] : "";

        $this->plazasSeleccionadas = max(1, $plazas);

        if ($plazas <= 0) {
            $this->mensaje = "Debe indicar un número de plazas válido.";
            return;
        }

        if ($plazas > (int) $recurso["plazas_disponibles"]) {
            $this->mensaje = "No hay suficientes plazas disponibles para la cantidad solicitada.";
            return;
        }

        $presupuesto = $this->reservas->calcularPresupuesto((int) $recurso["id_recurso"], $plazas);

        if ($accion === "presupuesto") {
            $this->presupuestoHtml = $this->crearResumenPresupuesto($recurso, $plazas, (float) $presupuesto);
            return;
        }

        if ($accion === "confirmar") {
            $creada = $this->reservas->crear(
                $this->seguridad->getIdUsuario(),
                (int) $recurso["id_recurso"],
                $plazas
            );

            if ($creada) {
                $this->reservaRealizada = true;
                return;
            }

            $this->mensaje = "No se pudo confirmar la reserva.";
        }
    }

    private function crearResumenPresupuesto(array $recurso, int $plazas, float $presupuesto): string
    {
        $html = '<section aria-live="polite">';
        $html .= '<h3>Presupuesto actualizado</h3>';
        $html .= '<p>Recurso: ' . htmlspecialchars($recurso["nombre"], ENT_QUOTES, "UTF-8") . '</p>';
        $html .= '<p>Número de plazas: ' . $plazas . '</p>';
        $html .= '<p>Precio por plaza: ' . number_format((float) $recurso["precio"], 2, ",", ".") . ' euros.</p>';
        $html .= '<p>Importe total: ' . number_format($presupuesto, 2, ",", ".") . ' euros.</p>';
        $html .= '</section>';

        return $html;
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
            echo '<p data-estado="error" role="alert">' . htmlspecialchars($this->mensaje, ENT_QUOTES, "UTF-8") . '</p>';
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

        echo '<p>';
        echo '<label for="plazas">Número de plazas</label>';
        echo '<input type="number" id="plazas" name="plazas" min="1" value="' . (int) $this->plazasSeleccionadas . '" required>';
        echo '</p>';

        echo '</fieldset>';

        echo '<p><button type="submit" name="accion" value="presupuesto">Generar presupuesto</button></p>';
        echo '<p><button type="submit" name="accion" value="confirmar">Confirmar reserva</button></p>';
        echo '</form>';

        if ($this->presupuestoHtml !== "") {
            echo $this->presupuestoHtml;
        }


        if ($this->reservaRealizada) {
            echo '<dialog data-dialogo="reserva-realizada" aria-labelledby="titulo-dialogo-reserva">';
            echo '<h3 id="titulo-dialogo-reserva">Reserva realizada</h3>';
            echo '<p>La reserva se ha realizado correctamente.</p>';
            echo '<form action="mis-reservas.php" method="get">';
            echo '<button type="submit" autofocus>Aceptar</button>';
            echo '</form>';
            echo '</dialog>';

            echo '<script>';
            echo 'const dialogoReserva = document.querySelector("dialog[data-dialogo=\'reserva-realizada\']");';
            echo 'if (dialogoReserva && typeof dialogoReserva.showModal === "function") {';
            echo 'dialogoReserva.showModal();';
            echo '}';
            echo '</script>';
        }


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
        echo '<p data-estado="error" role="alert">' . htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") . '</p>';
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
