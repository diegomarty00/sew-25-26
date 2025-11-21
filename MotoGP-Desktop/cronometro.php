<?php
class Cronometro {
    protected $inicio;
    protected $tiempo;

    public function __construct() {
        $this->inicio = 0;
        $this->tiempo = 0;
    }

    public function inicio() {
        $this->inicio = microtime(true);
    }

    public function parar() {
        if ($this->inicio != 0) {
            $this->tiempo = microtime(true) - $this->inicio;
            $this->inicio = 0;
        }
    }

    public function mostrar() {
        // Calculamos el tiempo total
        $total = $this->tiempo;
        // Convertimos a minutos y segundos
        $minutos = floor($total / 60);
        $segundos = $total - ($minutos * 60);

        // Formato mm:ss.s
        return sprintf("%02d:%04.1f", $minutos, $segundos);
    }
}

session_start();

if (!isset($_SESSION['cronometro'])) {
    $_SESSION['cronometro'] = new Cronometro();
}

$cronometro = $_SESSION['cronometro'];
$mensaje = "";

if (isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'inicio':
            $cronometro->inicio();
            break;
        case 'parar':
            $cronometro->parar();
            break;
        case 'mostrar':
            $mensaje = "Tiempo transcurrido: " . $cronometro->mostrar();
            break;
    }
}
?>



<!DOCTYPE HTML>

<html lang="es">

<head>
    <!-- Datos que describen el documento -->
    <meta charset="UTF-8" />
    <meta name="author" content="Diego Marty">
    <meta name="description" content="Documento para utilizar en otros módulos de la asignatura">
    <meta name="keywords" content="juegos, motogp, moto" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MotoGP-Juegos</title>
    <link rel="icon" href="multimedia/imagenes/MotoGP.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="estilo/layout.css" />
</head>

<body>
    <!-- Datos con el contenidos que aparece en el navegador -->
    <header>
        <h1><a href="index.html">MotoGP Desktop</a></h1>
        <nav>
            <a href="index.html" accesskey="i">Inicio</a>
            <a href="piloto.html" accesskey="p">Piloto</a>
            <a href="circuito.html" accesskey="c">Circuito</a>
            <a href="meteorologia.html" accesskey="m">Meteorología</a>
            <a href="clasificacion.html" accesskey="s">Clasificación</a>
            <a href="juegos.html" class="active" accesskey="j">Juegos</a>
            <a href="ayuda.html" accesskey="a">Ayuda</a>
        </nav>
    </header>
    <p>Estás en: <a href="index.html">Inicio</a> >> <a href="juegos.html">Juegos</a> >> Cronómetro</p>
    <main>
        <h2>Cronómetro</h2>
        <section>
            <form method="post">
                <button type="submit" name="accion" value="inicio">Inicio</button>
                <button type="submit" name="accion" value="parar">Parar</button>
                <button type="submit" name="accion" value="mostrar">Mostrar</button>
            </form>
            <p><?php echo $mensaje; ?></p>
        </section>
    </main>
    <footer>
        <nav>
            <a href="index.html" accesskey="i">Inicio</a>
            <a href="piloto.html" accesskey="p">Piloto</a>
            <a href="circuito.html" accesskey="c">Circuito</a>
            <a href="meteorologia.html" accesskey="m">Meteorología</a>
            <a href="clasificacion.html" accesskey="s">Clasificación</a>
            <a href="juegos.html" class="active" accesskey="j">Juegos</a>
            <a href="ayuda.html" accesskey="a">Ayuda</a>
        </nav>
        <p class="footer-company-name">All Rights Reserved. &copy; 2025 <a href="index.html" target="_blank">MotoGP</a>
            - Design By : <a href="https://github.com/diegomarty00" target="_blank">Diego Martinez Menéndez</a>
        </p>
    </footer>
</body>

</html>

