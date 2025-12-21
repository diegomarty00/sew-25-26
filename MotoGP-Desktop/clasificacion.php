<?php
class Clasificacion
{
    private $documento;
    private $datos;

    public function __construct()
    {
        // Tarea 2: ruta al XML en /xml
        $this->documento = __DIR__ . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'circuitoEsquema.xml';
    }

    /** Tarea 3: leer XML */
    public function consultar(): void
    {
        if (!is_readable($this->documento)) {
            echo "<p class='error'>Error: No se puede leer el XML en <code>" . $this->h($this->documento) . "</code>.</p>";
            return;
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($this->documento);
        if ($xml === false) {
            $errs = array_map(static fn($e) => trim($e->message), libxml_get_errors());
            libxml_clear_errors();
            echo "<p class='error'>Error al parsear el XML: " . $this->h(implode(' | ', $errs)) . "</p>";
            return;
        }
        $this->datos = $xml;
    }

    /** Tarea 4: imprimir ganador */
    public function mostrarGanador(): void
    {
        if (!$this->datos->resultados) { return; }

        if (!isset($this->datos->resultados->ganador)) {
            echo "<section><h3>Ganador de la Carrera - Automotodrom-Brno</h3><p>No se encontró la etiqueta ganador en el XML.</p></section>";
            return;
        }

        $nombre = (string)$this->datos->resultados->ganador;
        $tiempoISO = (string)$this->datos->resultados->tiempoGP;
        $tiempo = $this->formatearDuracionISO($tiempoISO);

        echo "<section>";
        echo "<h3>Ganador de la Carrera - Automotodrom-Brno</h3>";
        echo "<p><strong>Piloto:</strong> " . $this->h($nombre) . "</p>";
        echo "<p><strong>Tiempo empleado:</strong> " . $this->h($tiempo) . "</p>";
        echo "</section>";
    }

    /** Tarea 5: imprimir clasificación */
    public function mostrarClasificacion(): void
    {
        if (!$this->datos) { return; }

        if (!isset($this->datos->resultados->clasificacion->piloto)) {
            echo "<section><h3>Clasificación del Mundial</h3><p>No se encontró clasificacion con elementos piloto.</p></section>";
            return;
        }

        echo "<section>";
        echo "<h3>Clasificación del Mundial</h3>";
        echo "<ul>";
        foreach ($this->datos->resultados->clasificacion->piloto as $piloto) {
            $pos = isset($piloto['posicion']) ? (string)$piloto['posicion'] : '';
            $nom = (string)$piloto;
            echo "<li>Posición " . $this->h($pos) . ": " . $this->h($nom) . "</li>";
        }
        echo "</ul>";
        echo "</section>";
    }

    /* ==== Utilidades ==== */
    private function h(?string $s): string
    {
        return htmlspecialchars((string)$s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Formatea PT#H#M#S (con fracciones en S) a "h min s" */
    private function formatearDuracionISO(string $iso): string
    {
        if ($iso === '') return 'No disponible';

        // Si NO hay fracción → DateInterval
        if (!preg_match('/\.\d+S$/', $iso)) {
            try {
                $di = new DateInterval($iso);
                $partes = [];
                if ($di->h > 0) { $partes[] = $di->format('%h h'); }
                if ($di->i > 0 || $di->h > 0) { $partes[] = $di->format('%i min'); }
                $partes[] = $di->format('%s s');
                return implode(' ', $partes);
            } catch (Throwable $e) { /* seguimos con parser manual */ }
        }

        // Parser manual PT#H#M#S (S admite decimal)
        if (preg_match('/^P?T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+(?:[.,]\d+)?)S)?$/', $iso, $m)) {
            $h = $m[1] ?? ''; $i = $m[2] ?? ''; $s = str_replace(',', '.', $m[3] ?? '');
            $out = [];
            if ($h !== '' && $h !== '0') { $out[] = $h . ' h'; }
            if (($i !== '' && $i !== '0') || $h !== '') { $out[] = ($i === '' ? '0' : $i) . ' min'; }
            $out[] = ($s === '' ? '0' : rtrim(rtrim($s, '0'), '.')) . ' s';
            return implode(' ', $out);
        }
        return $iso; // Fallback
    }
}

/* ===== Ejecución ===== */
$clasificacion = new Clasificacion();
$clasificacion->consultar();
?>

<!DOCTYPE HTML>

<html lang="es">
<head>
    <!-- Datos que describen el documento -->
    <meta charset="UTF-8" />
    <meta name="author" content="Diego Marty">
    <meta name="description" content="Documento para utilizar en otros módulos de la asignatura">
    <meta name ="keywords" content="clasificacion, motogp, moto"/>
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    <title>MotoGP-Clasificación</title>
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
            <a href="clasificacion.php" class="active" accesskey="s">Clasificación</a>
            <a href="juegos.html" accesskey="j">Juegos</a>
            <a href="ayuda.html" accesskey="a">Ayuda</a>
        </nav>
    </header>
    <p>Estás en: <a href="index.html">Inicio</a> >> Clasificación</p>
    <main>
        <h2>Clasificación de MotoGP-Desktop</h2>
        <?php
            // Aquí imprimimos el resultado dinámico (¡clave!)
            $clasificacion->mostrarGanador();
            $clasificacion->mostrarClasificacion();
        ?>

        
    </main>

    <footer>
        <p class="footer-company-name">All Rights Reserved. &copy; 2025 <a href="index.html"
                target="_blank">MotoGP</a>
            - Design By : <a href="https://github.com/diegomarty00" target="_blank">Diego Martinez Menéndez</a>
        </p>
    </footer>
</body>
</html>
