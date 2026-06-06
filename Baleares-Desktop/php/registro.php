<?php
require_once "clases/Conexion.php";
require_once "clases/Plantilla.php";
require_once "clases/Usuario.php";
require_once "clases/Seguridad.php";

class PaginaRegistro {
    private Plantilla $plantilla;
    private Usuario $usuarios;
    private string $mensaje = "";

    public function __construct() {
        $conexion = new Conexion();
        $this->usuarios = new Usuario($conexion->getConexion());
        $this->plantilla = new Plantilla();
    }

    public function ejecutar(): void {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->procesarRegistro();
        }

        $this->mostrar();
    }

    private function procesarRegistro(): void {
        $nombre = $this->post("nombre");
        $apellidos = $this->post("apellidos");
        $email = $this->post("email");
        $telefono = $this->post("telefono");
        $password = $this->post("password");

        if ($nombre === "" || $apellidos === "" || $email === "" || $telefono === "" || $password === "") {
            $this->mensaje = "Debe completar todos los campos.";
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mensaje = "El correo electrónico no es válido.";
            return;
        }

        if ($this->usuarios->registrar($nombre, $apellidos, $email, $telefono, $password)) {
            $this->mensaje = "Usuario registrado correctamente. Ya puede iniciar sesión.";
        } else {
            $this->mensaje = "No se pudo registrar el usuario. Es posible que el correo ya exista.";
        }
    }

    private function mostrar(): void {
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

        echo '<form action="registro.php" method="post">';

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
        echo '</section>';
        echo '</main>';

        $this->plantilla->mostrarPie();
    }

    private function post(string $clave): string {
        return isset($_POST[$clave]) ? trim((string) $_POST[$clave]) : "";
    }
}

$pagina = new PaginaRegistro();
$pagina->ejecutar();
?>