<?php
class Seguridad {
    public function iniciarSesion(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function usuarioAutenticado(): bool {
        return isset($_SESSION["id_usuario"]);
    }

    public function exigirAutenticacion(): void {
        if (!$this->usuarioAutenticado()) {
            header("Location: login.php");
            exit;
        }
    }

    public function iniciarSesionUsuario(array $usuario): void {
        $_SESSION["id_usuario"] = $usuario["id_usuario"];
        $_SESSION["nombre_usuario"] = $usuario["nombre"];
        $_SESSION["email_usuario"] = $usuario["email"];
    }

    public function cerrarSesion(): void {
        $_SESSION = [];
        session_destroy();
    }

    public function getIdUsuario(): int {
        return (int) $_SESSION["id_usuario"];
    }

    public function getNombreUsuario(): string {
        return isset($_SESSION["nombre_usuario"]) ? $_SESSION["nombre_usuario"] : "";
    }
}
?>