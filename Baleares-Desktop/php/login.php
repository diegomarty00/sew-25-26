<?php
require_once "clases/Conexion.php";
require_once "clases/Plantilla.php";
require_once "clases/Usuario.php";
require_once "clases/Seguridad.php";

class PaginaLogin
{
    private Plantilla $plantilla;
    private Usuario $usuarios;
    private Seguridad $seguridad;
    private string $mensaje = "";

    public function __construct()
    {
        $conexion = new Conexion();
        $this->usuarios = new Usuario($conexion->getConexion());
        $this->plantilla = new Plantilla();
        $this->seguridad = new Seguridad();
        $this->seguridad->iniciarSesion();
    }

    public function ejecutar(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->procesarLogin();
        }

        $this->mostrar();
    }

    private function procesarLogin(): void
    {
        $email = $this->post("email");
        $password = $this->post("password");
        $idRecurso = $this->post("id_recurso");

        $usuario = $this->usuarios->autenticar($email, $password);

        if ($usuario === null) {
            $this->mensaje = "Correo electrónico o contraseña incorrectos.";
            return;
        }

        $this->seguridad->iniciarSesionUsuario($usuario);

        if ($idRecurso !== "") {
            header("Location: reservar.php?id_recurso=" . urlencode($idRecurso));
            exit;
        }

        header("Location: mis-reservas.php");
        exit;
    }

    private function mostrar(): void
    {
        $idRecurso = $this->obtenerIdRecursoPendiente();

        $this->plantilla->mostrarInicioDocumento(
            "Baleares - Iniciar sesión",
            "Inicio de sesión para reservas turísticas"
        );
        $this->plantilla->mostrarCabecera("reservas");
        $this->plantilla->mostrarMigas("Iniciar sesión");

        echo '<main>';
        $this->plantilla->mostrarMenuReservas();

        echo '<section>';
        echo '<h2>Iniciar sesión</h2>';

        if ($this->mensaje !== "") {
            echo '<p data-estado="error" role="alert">' . htmlspecialchars($this->mensaje, ENT_QUOTES, "UTF-8") . '</p>';
        }

        echo '<form method="post" action="login.php' . $this->crearParametroRecurso($idRecurso) . '">';

        if ($idRecurso !== "") {
            echo '<input type="hidden" name="id_recurso" value="' . htmlspecialchars($idRecurso, ENT_QUOTES, "UTF-8") . '">';
        }

        echo '<fieldset>';
        echo '<legend>Datos de acceso</legend>';

        echo '<p>';
        echo '<label for="email">Correo electrónico: </label>';
        echo '<input type="email" id="email" name="email" required>';
        echo '</p>';

        echo '<p>';
        echo '<label for="password">Contraseña: </label>';
        echo '<input type="password" id="password" name="password" required>';
        echo '</p>';

        echo '</fieldset>';

        echo '<p><button type="submit">Iniciar sesión</button></p>';
        echo '</form>';

        if ($idRecurso !== "") {
            $this->plantilla->mostrarEnlaceAccion(
                "registro.php?id_recurso=" . urlencode($idRecurso),
                "No tengo cuenta, registrarme"
            );
        } else {
            $this->plantilla->mostrarEnlaceAccion(
                "registro.php",
                "No tengo cuenta, registrarme"
            );
        }

        echo '</section>';
        echo '</main>';
        $this->plantilla->mostrarPie();
    }

    private function obtenerIdRecursoPendiente(): string
    {
        $idPost = $this->post("id_recurso");
        if ($idPost !== "") {
            return $idPost;
        }
        return $this->get("id_recurso");
    }

    private function crearParametroRecurso(string $idRecurso): string
    {
        if ($idRecurso === "") {
            return "";
        }
        return "?id_recurso=" . urlencode($idRecurso);
    }

    private function get(string $clave): string
    {
        return isset($_GET[$clave]) ? trim((string) $_GET[$clave]) : "";
    }

    private function post(string $clave): string
    {
        return isset($_POST[$clave]) ? trim((string) $_POST[$clave]) : "";
    }
}

$pagina = new PaginaLogin();
$pagina->ejecutar();
