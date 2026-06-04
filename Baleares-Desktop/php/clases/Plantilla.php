<?php
class Plantilla
{
    public function mostrarInicioDocumento(string $titulo, string $descripcion): void
    {
        echo '<!DOCTYPE HTML>';
        echo '<html lang="es">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="author" content="Diego Martínez Menéndez">';
        echo '<meta name="description" content="' . htmlspecialchars($descripcion, ENT_QUOTES, "UTF-8") . '">';
        echo '<meta name="keywords" content="Baleares, reservas, turismo, recursos turísticos">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . htmlspecialchars($titulo, ENT_QUOTES, "UTF-8") . '</title>';
        echo '<link rel="icon" href="../multimedia/imagenes/Baleares.ico" type="image/x-icon">';
        echo '<link rel="stylesheet" href="../estilo/estilo.css">';
        echo '<link rel="stylesheet" href="../estilo/layout.css">';
        echo '</head>';
        echo '<body>';
    }

    public function mostrarCabecera(string $paginaActiva): void
    {
        echo '<header>';
        echo '<h1><a href="../index.html">Baleares</a></h1>';

        echo '<nav>';
        echo '<a href="../index.html" accesskey="i">Inicio</a>';
        echo '<a href="../gastronomia.html" accesskey="g">Gastronomía</a>';
        echo '<a href="../rutas.html" accesskey="r">Rutas</a>';
        echo '<a href="../meteorologia.html" accesskey="m">Meteorología</a>';
        echo '<a href="../juego.html" accesskey="j">Juego</a>';
        echo '<a href="reservas.php" class="active" accesskey="v">Reservas</a>';
        echo '<a href="../ayuda.html" accesskey="a">Ayuda</a>';
        echo '</nav>';

        echo '</header>';
    }

    public function mostrarMigas(string $actual): void
    {
        if ($actual === "Reservas") {
            echo '<p>Estás en: <a href="../index.html">Inicio</a> &gt;&gt; Reservas</p>';
        } else {
            echo '<p>Estás en: <a href="../index.html">Inicio</a> &gt;&gt; <a href="reservas.php">Reservas</a> &gt;&gt; ' . htmlspecialchars($actual, ENT_QUOTES, "UTF-8") . '</p>';
        }
    }

    public function mostrarMenuReservas(): void
    {
        $autenticado = $this->usuarioAutenticado();

        echo '<section aria-label="Opciones de gestión de reservas">';
        echo '<h2>Gestión de reservas</h2>';

        echo '<nav aria-label="Menú de gestión de reservas">';

        echo '<a href="recursos.php">Recursos turísticos</a>';

        if ($autenticado) {
            echo '<a href="mis-reservas.php">Mis reservas</a>';
            echo '<a href="logout.php">Cerrar sesión</a>';
        } else {
            echo '<a href="registro.php">Registrarse</a>';
            echo '<a href="login.php">Iniciar sesión</a>';
        }

        echo '</nav>';

        echo '</section>';
    }

    public function mostrarPie(): void
    {
        echo '<footer>';
        echo '<p>';
        echo 'All Rights Reserved. &copy; 2026 <a href="../index.html">Baleares</a>';
        echo ' - Design By : <a href="https://github.com/diegomarty00">Diego Martinez Menéndez</a>';
        echo '</p>';
        echo '</footer>';
        echo '</body>';
        echo '</html>';
    }

    private function usuarioAutenticado(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION["id_usuario"]);
    }
}
