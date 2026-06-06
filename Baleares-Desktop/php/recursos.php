<?php
require_once "clases/Conexion.php";
require_once "clases/Plantilla.php";
require_once "clases/Recurso.php";
require_once "clases/Seguridad.php";

class PaginaRecursos {
    private Plantilla $plantilla;
    private Recurso $recursos;
    private Seguridad $seguridad;

    public function __construct() {
        $conexion = new Conexion();
        $this->recursos = new Recurso($conexion->getConexion());
        $this->plantilla = new Plantilla();
        $this->seguridad = new Seguridad();
        $this->seguridad->iniciarSesion();
    }

    public function ejecutar(): void {
        $lista = $this->recursos->listar();

        $this->plantilla->mostrarInicioDocumento("Baleares - Recursos turísticos", "Listado de recursos turísticos reservables");
        $this->plantilla->mostrarCabecera("reservas");
        $this->plantilla->mostrarMigas("Recursos turísticos");

        echo '<main>';
        $this->plantilla->mostrarMenuReservas();

        echo '<section>';
        echo '<h2>Recursos turísticos disponibles</h2>';

        foreach ($lista as $recurso) {
            echo '<article>';
            echo '<h3>' . htmlspecialchars($recurso["nombre"], ENT_QUOTES, "UTF-8") . '</h3>';
            echo '<dl>';
            echo '<dt>Tipo</dt><dd>' . htmlspecialchars($recurso["tipo"], ENT_QUOTES, "UTF-8") . '</dd>';
            echo '<dt>Plazas máximas</dt><dd>' . (int) $recurso["plazas_maximas"] . '</dd>';
            echo '<dt>Plazas disponibles</dt><dd>' . (int) $recurso["plazas_disponibles"] . '</dd>';
            echo '<dt>Inicio</dt><dd>' . htmlspecialchars($recurso["fecha_hora_inicio"], ENT_QUOTES, "UTF-8") . '</dd>';
            echo '<dt>Fin</dt><dd>' . htmlspecialchars($recurso["fecha_hora_fin"], ENT_QUOTES, "UTF-8") . '</dd>';
            echo '<dt>Precio</dt><dd>' . number_format((float) $recurso["precio"], 2, ",", ".") . ' euros</dd>';
            echo '<dt>Descripción</dt><dd>' . htmlspecialchars($recurso["descripcion"], ENT_QUOTES, "UTF-8") . '</dd>';
            echo '</dl>';

            if ($this->seguridad->usuarioAutenticado()) {
                $this->plantilla->mostrarEnlaceAccion("reservar.php?id_recurso=" . (int) $recurso["id_recurso"], "Reservar este recurso");
            } else {
                $this->plantilla->mostrarEnlaceAccion("registro.php?id_recurso=" . (int) $recurso["id_recurso"], "Iniciar sesión para reservar este recurso");
            }

            echo '</article>';
        }

        echo '</section>';
        echo '</main>';

        $this->plantilla->mostrarPie();
    }
}

$pagina = new PaginaRecursos();
$pagina->ejecutar();
?>