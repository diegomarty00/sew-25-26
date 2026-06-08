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
        echo '<a href="../index.html" >Inicio</a>';
        echo '<a href="../gastronomia.html" >Gastronomía</a>';
        echo '<a href="../rutas.html" >Rutas</a>';
        echo '<a href="../meteorologia.html"  >Meteorología</a>';
        echo '<a href="../juego.html" >Juego</a>';
        echo '<a href="reservas.php" class="active" >Reservas</a>';
        echo '<a href="../ayuda.html" >Ayuda</a>';
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

        $this->mostrarEnlaceAccion("recursos.php", "Recursos turísticos");

        if ($autenticado) {
            $this->mostrarEnlaceAccion("mis-reservas.php", "Mis reservas");
            $this->mostrarEnlaceAccion("logout.php", "Cerrar sesión");
        } else {
            $this->mostrarEnlaceAccion("registro.php", "Registrarse");
            $this->mostrarEnlaceAccion("login.php", "Iniciar sesión");
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


    public function crearEnlaceAccion(string $href, string $texto): string
    {
        return '<a href="' . htmlspecialchars($href, ENT_QUOTES, "UTF-8") . '" data-accion="reservas">'
            . htmlspecialchars($texto, ENT_QUOTES, "UTF-8")
            . '</a>';
    }

    public function mostrarEnlaceAccion(string $href, string $texto): void
    {
        echo $this->crearEnlaceAccion($href, $texto);
    }


    private function usuarioAutenticado(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION["id_usuario"]);
    }
}
