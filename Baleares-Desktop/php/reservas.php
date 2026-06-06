<?php
require_once "clases/Plantilla.php";
require_once "clases/Seguridad.php";

class PaginaReservas
{
    private Plantilla $plantilla;
    private Seguridad $seguridad;

    public function __construct()
    {
        $this->plantilla = new Plantilla();
        $this->seguridad = new Seguridad();
        $this->seguridad->iniciarSesion();
    }

    public function ejecutar(): void
    {
        $this->plantilla->mostrarInicioDocumento("Baleares - Reservas", "Central de reservas de recursos turísticos de Baleares");
        $this->plantilla->mostrarCabecera("reservas");
        $this->plantilla->mostrarMigas("Reservas");

        echo '<main>';

        $this->plantilla->mostrarMenuReservas();

        echo '<section>';
        echo '<h2>Central de reservas de recursos turísticos</h2>';
        echo '<p>Desde esta sección puede registrarse, iniciar sesión, consultar recursos turísticos, realizar reservas, consultar sus reservas y anularlas.</p>';

        if ($this->seguridad->usuarioAutenticado()) {
            echo '<p>Sesión iniciada como ' . htmlspecialchars($this->seguridad->getNombreUsuario(), ENT_QUOTES, "UTF-8") . '.</p>';
        } else {
            echo '<p>Para reservar recursos turísticos debe registrarse o iniciar sesión.</p>';
        }

        echo '</section>';

        echo '</main>';

        $this->plantilla->mostrarPie();
    }
}

$pagina = new PaginaReservas();
$pagina->ejecutar();
