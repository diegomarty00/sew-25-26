<?php
class Usuario {
    private mysqli $conexion;

    public function __construct(mysqli $conexion) {
        $this->conexion = $conexion;
    }

    public function registrar(string $nombre, string $apellidos, string $email, string $telefono, string $password): bool {
        if ($this->existeEmail($email)) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nombre, apellidos, email, telefono, password_hash)
                VALUES (?, ?, ?, ?, ?)";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("sssss", $nombre, $apellidos, $email, $telefono, $hash);

        return $sentencia->execute();
    }

    public function existeEmail(string $email): bool {
        $sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("s", $email);
        $sentencia->execute();

        $resultado = $sentencia->get_result();

        return $resultado->num_rows > 0;
    }

    public function autenticar(string $email, string $password): ?array {
        $sql = "SELECT id_usuario, nombre, apellidos, email, password_hash
                FROM usuarios
                WHERE email = ?";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("s", $email);
        $sentencia->execute();

        $resultado = $sentencia->get_result();

        if ($resultado->num_rows === 0) {
            return null;
        }

        $usuario = $resultado->fetch_assoc();

        if (!password_verify($password, $usuario["password_hash"])) {
            return null;
        }

        return $usuario;
    }
}
?>