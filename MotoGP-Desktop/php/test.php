
<?php
declare(strict_types=1);

require_once 'cronometro.php';
session_start();


/**
 * Conexión y operaciones ajustadas al esquema de configuracion.php
 * Tablas: Usuarios, Resultados, Obsevaciones
 */
class Formulario {
    // Credenciales idénticas a configuracion.php
    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $dbname = 'UO270457_db';
    private $connection = null;

    public function __construct() {
        $this->conect();
    }

    private function conect(): void {
        $this->connection = @new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        if ($this->connection->connect_error) {
            exit('Error de conexión: ' . $this->connection->connect_error);
        }
        $this->connection->set_charset('utf8mb4');
    }

    /** Arranca prueba: inserta Usuario + inicia cronómetro */
    public function start(array $post): void {
        // Validación mínima de los datos del usuario
        $profesion = trim((string)($post['profesion'] ?? ''));
        $edad      = (int)($post['edad'] ?? 0);
        $genero    = trim((string)($post['genero'] ?? ''));
        $pericia   = (int)($post['pericia'] ?? 0);

        if ($profesion === '' || $edad < 1 || $edad > 120 ||
            !in_array($genero, ['Masculino','Femenino','Otro'], true) ||
            $pericia < 0 || $pericia > 10) {
            $_SESSION['errores_usuario'] = ['Completa los datos correctamente (género: Masculino/Femenino/Otro; pericia 0–10; edad 1–120).'];
            $_SESSION['fase'] = 'usuario';
            return;
        }

        // ID_Usuario = MAX+1 (coherente con tu patrón anterior)
        $sqlId = "SELECT IFNULL(MAX(ID_Usuario), 0) + 1 AS nuevoId FROM Usuarios";
        $resId = $this->connection->query($sqlId);
        $fila  = $resId ? $resId->fetch_assoc() : ['nuevoId' => 1];
        $nuevoId = (int)$fila['nuevoId'];

        $stmt = $this->connection->prepare("
            INSERT INTO Usuarios (ID_Usuario, Profesion, Edad, Genero, Pericia_Informatica)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isisi", $nuevoId, $profesion, $edad, $genero, $pericia);
        $stmt->execute();
        $stmt->close();

        $_SESSION['id_usuario'] = $nuevoId;

        // Cronómetro en sesión (usando tu cronometro.php)
        if (!isset($_SESSION['cronometroPrueba'])) {
            // Supone que cronometro.php define la clase Cronometro
            $_SESSION['cronometroPrueba'] = new Cronometro();
        }
        /** @var Cronometro $cronometro */
        $cronometro = $_SESSION['cronometroPrueba'];
        // Iniciar (tu clase usa 'inicio' en lugar de 'arrancar')
        $cronometro->inicio();

        $_SESSION['fase'] = 'preguntas';
    }

    /** Terminar fase usuario: detener cronómetro, validar preguntas, guardar Resultados */
    public function endUser(array $post): void {
        // Detener cronómetro sin mostrar tiempo
        /** @var Cronometro $cronometro */
        $cronometro = $_SESSION['cronometroPrueba'] ?? null;
        if ($cronometro instanceof Cronometro) {
            $cronometro->parar();
        }

        // Validar 10 preguntas
        $errores = $this->validarPreguntas($post);
        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            $_SESSION['fase'] = 'preguntas';
            return;
        }

        // Recoger respuestas
        $respuestas = [];
        for ($i = 1; $i <= 10; $i++) {
            $key = "pregunta{$i}";
            $respuestas["p{$i}"] = trim((string)$post[$key]);
        }

        // Tiempo -> convertir a HH:MM:SS para campo TIME
        // Tu cronómetro tiene mostrar() que devuelve mm:ss.s; generamos HH:MM:SS.
        $tiempoTime = $this->cronometroA_TIME($cronometro);

        // Comentarios/Propuesta/Valoración (feedback del usuario)
        $comentario = trim((string)($post['comentarioUsuario'] ?? ''));
        $propuesta  = trim((string)($post['propuestasUsuario'] ?? ''));
        $valoracion = (int)($post['valoracionUsuario'] ?? 0);
        if ($valoracion < 0 || $valoracion > 10) {
            $valoracion = 0;
        }

        // Dispositivo y completado
        $id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
        $dispositivo = 'Ordenador'; // según práctica
        $completado  = 1;

        // Guardamos las respuestas dentro de Comentarios (para NO cambiar el esquema)
        // Formato compacto legible (JSON inline)
        $comentariosFinal = $this->combinarComentariosConRespuestas($comentario, $respuestas);

        $stmt = $this->connection->prepare("
            INSERT INTO Resultados
            (ID_Usuario, Dispositivo, Tiempo, Completado, Comentarios, Propuesta, Valoracion)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ississs",
            $id_usuario,        // INT
            $dispositivo,       // ENUM
            $tiempoTime,        // TIME 'HH:MM:SS'
            $completado,        // BOOLEAN
            $comentariosFinal,  // TEXT (respuestas + comentario)
            $propuesta,         // TEXT
            $valoracion         // INT (0–10)
        );
        $stmt->execute();
        $stmt->close();

        // Siguiente fase
        $_SESSION['fase'] = 'observador';
        unset($_SESSION['errores']);
    }

    /** Guardar comentario del observador en Obsevaciones */
    public function endObserver(array $post): void {
        $id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
        $comentarioF = trim((string)($post['comentarioFacilitador'] ?? ''));

        $stmt = $this->connection->prepare("
            INSERT INTO Obsevaciones (ID_Usuario, Comentario)
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $id_usuario, $comentarioF);
        $stmt->execute();
        $stmt->close();

        // Reiniciar flujo para una nueva prueba
        $_SESSION['fase'] = 'usuario';
        unset($_SESSION['cronometroPrueba']);
    }

    /** Validación de las 10 preguntas (p2 numérica) */
    private function validarPreguntas(array $post): array {
        $errores = [];
        for ($i = 1; $i <= 10; $i++) {
            $key = "pregunta{$i}";
            if (!isset($post[$key]) || trim((string)$post[$key]) === '') {
                $errores[] = "La pregunta {$i} es obligatoria.";
            }
        }
        // p2 debe ser número (cantidad de enlaces)
        if (isset($post['pregunta2']) && !preg_match('/^\d+$/', trim((string)$post['pregunta2']))) {
            $errores[] = "La pregunta 2 debe ser un número (cantidad de enlaces a redes sociales).";
        }
        return $errores;
    }

    /** Convierte el tiempo del cronómetro a cadena TIME 'HH:MM:SS' */
    private function cronometroA_TIME(?Cronometro $cronometro): string {
        if (!$cronometro) {
            return '00:00:00';
        }
        // Tu mostrar() devuelve "mm:ss.s". Lo convertimos a HH:MM:SS.
        $str = $cronometro->mostrar(); // ej. "02:15.4"
        // Parse mm:ss.s -> total segundos -> HH:MM:SS
        if (preg_match('/^(\d{1,2}):(\d{2})(?:\.(\d))?$/', $str, $m)) {
            $mm = (int)$m[1];
            $ss = (int)$m[2];
            $frac = isset($m[3]) ? (int)$m[3] : 0; // décimas
            $total = ($mm * 60) + $ss; // ignoramos décimas para TIME
            $hh = floor($total / 3600);
            $rem = $total % 3600;
            $mm2 = floor($rem / 60);
            $ss2 = $rem % 60;
            return sprintf('%02d:%02d:%02d', $hh, $mm2, $ss2);
        }
        // Fallback
        return '00:00:00';
    }

    /** Combina comentario libre del usuario con las 10 respuestas (sin cambiar esquema) */
    private function combinarComentariosConRespuestas(string $comentarioUsuario, array $respuestas): string {
        // Ejemplo en texto legible + JSON compacto al final
        $bloque = "Respuestas del test:\n";
        $map = [
            'p1'  => 'Piloto patrocinador',
            'p2'  => 'Número de enlaces a redes',
            'p3'  => 'Desarrollador de la web',
            'p4'  => 'Tiempo del juego de memoria',
            'p5'  => 'Acciones en cronometraje',
            'p6'  => 'Puesta del sol (día carrera)',
            'p7'  => 'Segunda escudería',
            'p8'  => 'Última noticia',
            'p9'  => 'Humedad a las 12:00 (día carrera)',
            'p10' => 'Coordenadas del circuito',
        ];
        foreach ($map as $k => $label) {
            $val = $respuestas[$k] ?? '';
            $bloque .= "- {$label}: {$val}\n";
        }
        if ($comentarioUsuario !== '') {
            $bloque .= "\nComentario del usuario:\n{$comentarioUsuario}\n";
        }
        // Añadimos JSON compacto por si quieres exportar con claridad
        $bloque .= "\nJSON: " . json_encode($respuestas, JSON_UNESCAPED_UNICODE);
        return $bloque;
    }
}

// ---------- Fase inicial ----------
if (empty($_SESSION['fase'])) {
    $_SESSION['fase'] = 'usuario';
}

// ---------- Enrutado POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = new Formulario();
    if (isset($_POST['botonIniciar']))            $form->start($_POST);
    if (isset($_POST['botonTerminarUsuario']))    $form->endUser($_POST);
    if (isset($_POST['botonTerminarObservador'])) $form->endObserver($_POST);
}
?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="author" content="Diego Martínez Menéndez"/>
    <meta name="description" content="Prueba de usabilidad — MotoGP Desktop"/>
    <meta name="keywords" content="Juegos, MotoGP, Usabilidad"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>MotoGP — Test de usabilidad</title>
    <!-- Reutiliza tu CSS -->
    ../estilo/estilo.css
    <style>
        main { max-width: 960px; margin: 2rem auto; padding: 1rem; }
        form { display: grid; gap: .75rem; }
        label { display: grid; gap: .35rem; }
        input[type=text], input[type=number], textarea {
            width: 100%; padding: .5rem; border: 1px solid #ccc; border-radius: 6px;
        }
        .acciones { margin-top: .75rem; display: flex; gap: .75rem; }
        .error { color: #b00020; font-weight: 600; }
        ul.errores { margin: .5rem 0; padding-left: 1.25rem; }
    </style>
</head>
<body>
<main>
<?php
// -------- Fase: usuario --------
if ($_SESSION['fase'] === 'usuario') {
    if (!empty($_SESSION['errores_usuario'])) {
        echo "<div class='error'>".htmlspecialchars($_SESSION['errores_usuario'][0])."</div>";
        unset($_SESSION['errores_usuario']);
    }
    echo "
    <h1>Datos</h1>
    #
        <label for='profesion'>Profesión
            <input type='text' id='profesion' name='profesion' required />
        </label>
        <label for='edad'>Edad
            <input type='number' id='edad' name='edad' min='1' max='120' required />
        </label>
        <label for='genero'>Género
            <select id='genero' name='genero' required>
                <option value=''>Selecciona…</option>
                <option value='Masculino'>Masculino</option>
                <option value='Femenino'>Femenino</option>
                <option value='Otro'>Otro</option>
            </select>
        </label>
        <label for='pericia'>Pericia informática (0–10)
            <input type='number' id='pericia' name='pericia' min='0' max='10' required />
        </label>
        <div class='acciones'>
            <input type='submit' name='botonIniciar' value='Iniciar prueba'/>
        </div>
    </form>
    ";
}

// -------- Fase: preguntas --------
if ($_SESSION['fase'] === 'preguntas') {
    if (!empty($_SESSION['errores'])) {
        echo "<div class='error'>Por favor, corrige los siguientes errores:</div>";
        echo "<ul class='errores'>";
        foreach ($_SESSION['errores'] as $e) {
            echo "<li class='error'>" . htmlspecialchars($e) . "</li>";
        }
        echo "</ul>";
        unset($_SESSION['errores']);
    }
    echo "
    <h1>Preguntas</h1>
    <form action='#' method='gunta1'>1) Identifícame el piloto patrocinador del sitio web
            <input type='text' id='pregunta1' name='pregunta1' required />
        </label>
        <label for='pregunta2'>2) ¿Cuántos enlaces a redes sociales tiene el piloto?
            <input type='number' id='pregunta2' name='pregunta2' min='0' step='1' required />
        </label>
        <label for='pregunta3'>3) ¿Quién es el desarrollador de la web?
            <input type='text' id='pregunta3' name='pregunta3' required />
        </label>
        <label for='pregunta4'>4) Completa el tiempo del juego de memoria e indica el tiempo que tardaste
            <input type='text' id='pregunta4' name='pregunta4' placeholder='Ej.: 00:45 (45 segundos)' required />
        </label>
        <label for='pregunta5'>5) Pon en marcha un juego de cronometraje, páralo y reinícialo, avisa para hacerlo
            <textarea id='pregunta5' name='pregunta5' rows='2' placeholder='Describe las acciones que realizaste.' required></textarea>
        </label>
        <label for='pregunta6'>6) Indica cuándo es la puesta del sol el día de la carrera
            <input type='text' id='pregunta6' name='pregunta6' placeholder='Ej.: 21:07' required />
        </label>
        <label for='pregunta7'>7) Segunda escudería del piloto
            <input type='text' id='pregunta7' name='pregunta7' required />
        </label>
        <label for='pregunta8'>8) Dime cuál es la última noticia
            <textarea id='pregunta8' name='pregunta8' rows='2' required></textarea>
        </label>
        <label for='pregunta9'>9) Dime la humedad que habrá el día de la carrera a las 12:00
            <input type='text' id='pregunta9' name='pregunta9' placeholder='Ej.: 55%' required />
        </label>
        <label for='pregunta10'>10) Dime las coordenadas del circuito
            <input type='text' id='pregunta10' name='pregunta10' placeholder='Ej.: 43.362, -5.847' required />
        </label>

        <!-- Feedback del usuario -->
        <label for='comentarioUsuario'>Comentario
            <textarea id='comentarioUsuario' name='comentarioUsuario' rows='5' cols='40'></textarea>
        </label>
        <label for='propuestasUsuario'>Propuestas
            <textarea id='propuestasUsuario' name='propuestasUsuario' rows='5' cols='40'></textarea>
        </label>
        <label for='valoracionUsuario'>Valoración (0–10)
            <input type='number' id='valoracionUsuario' name='valoracionUsuario' min='0' max='10' />
        </label>

        <p><input name='botonTerminarUsuario' type='submit' value='Terminar prueba'/></p>
    </form>
    ";
}

// -------- Fase: observador --------
if ($_SESSION['fase'] === 'observador') {
    echo "
    <h1>Observador</h1>
    #
        <label for='comentarioFacilitador'>Comentario
            <textarea name='comentarioFacilitador' id='comentarioFacilitador' rows='5' cols='40'></textarea>
        </label>
        <div class='acciones'>
            <input type='submit' name='botonTerminarObservador' value='Terminar prueba'/>
        </div>
    </form>
    ";
}
?>
</main>
</body>
</html>
