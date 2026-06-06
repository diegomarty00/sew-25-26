
<?php
class Conexion {
    private string $host = "localhost";
    private string $usuario = "DBUSER2026";
    private string $contrasena = "DBPWD2026";
    private string $baseDatos = "baleares_reservas";
    private ?mysqli $conexion = null;

    public function __construct() {
        $this->conexion = new mysqli(
            $this->host,
            $this->usuario,
            $this->contrasena,
            $this->baseDatos
        );

        if ($this->conexion->connect_error) {
            die("Error de conexión: " . $this->conexion->connect_error);
        }

        $this->conexion->set_charset("utf8mb4");
    }

    public function getConexion(): mysqli {
        return $this->conexion;
    }
}
?>
