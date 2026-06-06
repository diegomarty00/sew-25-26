<?php
require_once "clases/Plantilla.php";
require_once "clases/Seguridad.php";

class PaginaLogout {
    private Plantilla $plantilla;
    private Seguridad $seguridad;

    public function __construct() {
        $this->plantilla = new Plantilla();
        $this->seguridad = new Seguridad();
        $this->seguridad->iniciarSesion();
    }

    public function ejecutar(): void {
        $this->seguridad->cerrarSesion();

        $this->plantilla->mostrarInicioDocumento("Baleares - Cerrar sesión", "Cierre de sesión de la central de reservas");
        $this->plantilla->mostrarCabecera("reservas");
        $this->plantilla->mostrarMigas("Cerrar sesión");

        echo '<main>';
        echo '<section>';
        echo '<h2>Sesión cerrada</h2>';
        echo '<p>La sesión se ha cerrado correctamente.</p>';
        $this->plantilla->mostrarEnlaceAccion("reservas.php", "Volver a la central de reservas");
        echo '</section>';
        echo '</main>';

        $this->plantilla->mostrarPie();
    }
}

$pagina = new PaginaLogout();
$pagina->ejecutar();
?>