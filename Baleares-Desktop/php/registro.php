<?php
require_once "clases/Conexion.php";
require_once "clases/Plantilla.php";
require_once "clases/Usuario.php";
require_once "clases/Seguridad.php";

class PaginaRegistro
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
            $this->procesarRegistro();
        }

        $this->mostrar();
    }

    private function procesarRegistro(): void
    {
        $nombre = $this->post("nombre");
        $apellidos = $this->post("apellidos");
        $email = $this->post("email");
        $telefono = $this->post("telefono");
        $password = $this->post("password");
        $idRecurso = $this->obtenerIdRecursoPendiente();

        if ($nombre === "" || $apellidos === "" || $email === "" || $telefono === "" || $password === "") {
            $this->mensaje = "Debe completar todos los campos.";
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mensaje = "El correo electrónico no es válido.";
            return;
        }

        if ($this->usuarios->registrar($nombre, $apellidos, $email, $telefono, $password)) {
            $usuario = $this->usuarios->autenticar($email, $password);

            if ($usuario !== null) {
                $this->seguridad->iniciarSesionUsuario($usuario);

                if ($idRecurso !== "") {
                    header("Location: reservar.php?id_recurso=" . urlencode($idRecurso));
                    exit;
                }

                header("Location: mis-reservas.php");
                exit;
            }

            $this->mensaje = "Usuario registrado correctamente. Ya puede iniciar sesión.";
            return;
        }

        $this->mensaje = "No se pudo registrar el usuario. Es posible que el correo ya exista.";
    }

    private function mostrar(): void
    {
        $idRecurso = $this->obtenerIdRecursoPendiente();

        $this->plantilla->mostrarInicioDocumento(
            "Baleares - Registro",
            "Registro de usuarios para reservas turísticas"
        );
        $this->plantilla->mostrarCabecera("reservas");
        $this->plantilla->mostrarMigas("Registro");

        echo '<main>';
        $this->plantilla->mostrarMenuReservas();

        echo '<section>';
        echo '<h2>Registro de usuarios</h2>';

        if ($this->mensaje !== "") {
            echo '<p>' . htmlspecialchars($this->mensaje, ENT_QUOTES, "UTF-8") . '</p>';
        }

        echo '<form action="registro.php' . $this->crearParametroRecurso($idRecurso) . '" method="post">';

        if ($idRecurso !== "") {
            echo '<input type="hidden" name="id_recurso" value="' . htmlspecialchars($idRecurso, ENT_QUOTES, "UTF-8") . '">';
        }

        echo '<fieldset>';
        echo '<legend>Datos del usuario</legend>';

        echo '<p>';
        echo '<label for="nombre">Nombre: </label>';
        echo '<input type="text" id="nombre" name="nombre" required>';
        echo '</p>';

        echo '<p>';
        echo '<label for="apellidos">Apellidos: </label>';
        echo '<input type="text" id="apellidos" name="apellidos" required>';
        echo '</p>';

        echo '<p>';
        echo '<label for="email">Correo electrónico: </label>';
        echo '<input type="email" id="email" name="email" required>';
        echo '</p>';

        echo '<p>';
        echo '<label for="telefono">Teléfono: </label>';
        echo '<input type="tel" id="telefono" name="telefono" required>';
        echo '</p>';

        echo '<p>';
        echo '<label for="password">Contraseña: </label>';
        echo '<input type="password" id="password" name="password" required>';
        echo '</p>';

        echo '</fieldset>';

        echo '<p><button type="submit">Registrarse</button></p>';
        echo '</form>';

        if ($idRecurso !== "") {
            $this->plantilla->mostrarEnlaceAccion("login.php?id_recurso=" . urlencode($idRecurso), "Tengo cuenta, iniciar sesión");
        } else {
            $this->plantilla->mostrarEnlaceAccion("login.php", "Tengo cuenta, iniciar sesión");
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

$pagina = new PaginaRegistro();
$pagina->ejecutar();