
<?php
session_start();

class Configuracion {

    private $host   = 'localhost';
    private $user   = 'DBUSER2025';
    private $pass   = 'DBPSWD2025';
    private $dbname = 'UO270457_db';

    /** @var mysqli|null */
    private $connection = null;

    public function __construct() {
        $this->conect();
    }

    private function conect(){
        // Intenta conectar directamente a la BD
        $this->connection = @new mysqli($this->host, $this->user, $this->pass, $this->dbname);

        if ($this->connection->connect_error) {
            // Si la BD no existe o hay error, muestra mensaje y evita warnings rotos
            // Puedes opcionalmente reconectar solo al servidor: new mysqli($host, $user, $pass)
            echo 'Error de conexión: ' . htmlspecialchars($this->connection->connect_error);
        } else {
            // Charset recomendado para evitar problemas de acentos y emojis
            $this->connection->set_charset('utf8mb4');
            // Para MySQL 8+, asegúrate de que el modo SQL no bloquee TRUNCATE con FKs
            // $this->connection->query("SET sql_mode=''");
        }
    }

    /** Reiniciar (vaciar) datos de las tablas */
    public function restart(){
        if (!$this->connection || $this->connection->connect_error) {
            echo 'No hay conexión válida.';
            return;
        }

        // Desactiva la comprobación de claves foráneas para evitar errores al TRUNCATE
        $this->connection->query('SET FOREIGN_KEY_CHECKS = 0');

        // Ajusta esta lista a las tablas reales de tu BD
        $tablas = ['observaciones', 'resultado', 'usuario'];

        foreach ($tablas as $tabla) {
            // Usa backticks para evitar problemas si el nombre es palabra reservada
            $sql = "TRUNCATE TABLE `{$tabla}`";
            if (!$this->connection->query($sql)) {
                echo 'Algo salió mal al vaciar ' . htmlspecialchars($tabla) . ': ' . htmlspecialchars($this->connection->error) . '<br>';
            }
        }

        $this->connection->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    /** Eliminar la base de datos completa */
    public function delete(){
        // Cierra la conexión si estaba abierta a la BD
        if ($this->connection && !$this->connection->connect_error) {
            $this->connection->close();
        }

        // Conexión genérica al servidor (sin seleccionar BD)
        $genConn = new mysqli($this->host, $this->user, $this->pass);
        if ($genConn->connect_error) {
            echo 'Algo salió mal: ' . htmlspecialchars($genConn->connect_error);
            return;
        }

        // IMPORTANTE: IF EXISTS evita error si ya no existe
        $sql = "DROP DATABASE IF EXISTS `{$this->dbname}`";
        if (!$genConn->query($sql)) {
            echo 'Error eliminando BD: ' . htmlspecialchars($genConn->error);
        } else {
            echo 'BD eliminada correctamente.';
        }

        $genConn->close();
    }

    /**
     * Exportar todas las tablas a un único CSV (con separador ';'), incluyendo cabeceras y separadores entre tablas.
     * Opcionalmente añadimos BOM para que Excel reconozca UTF-8.
     */
    public function export(){
        if (!$this->connection || $this->connection->connect_error) {
            echo 'No hay conexión válida.';
            return;
        }

        $tablas = ['observaciones', 'resultado', 'usuario'];

        // Cabeceras de descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=tablas_UO276417.csv');

        $archivoSalida = fopen('php://output', 'w');

        if (!$archivoSalida) {
            echo 'No se pudo abrir la salida CSV.';
            exit;
        }

        // BOM UTF-8 (para que Excel muestre bien acentos)
        echo "\xEF\xBB\xBF";

        foreach ($tablas as $tabla) {
            // Línea vacía para separar tablas
            fputcsv($archivoSalida, [''], ';');
            // Título de la tabla
            fputcsv($archivoSalida, ["Tabla: $tabla"], ';');

            $sql = "SELECT * FROM `{$tabla}`";
            $result = $this->connection->query($sql);

            if ($result === false) {
                fputcsv($archivoSalida, ["Error consultando '$tabla': {$this->connection->error}"], ';');
                continue;
            }

            // Cabeceras
            $campos = $result->fetch_fields();
            $cabeceras = [];
            foreach ($campos as $campo) {
                $cabeceras[] = $campo->name;
            }
            fputcsv($archivoSalida, $cabeceras, ';');

            // Filas
            while ($fila = $result->fetch_assoc()) {
                // Aseguramos UTF-8 en cada valor
                $filaUtf8 = array_map(function($v) {
                    return is_null($v) ? '' : (string)$v;
                }, $fila);
                fputcsv($archivoSalida, $filaUtf8, ';');
            }

            $result->free();
        }

        fclose($archivoSalida);
        exit; // Muy importante: terminar la respuesta después de enviar el CSV
    }
}

// === Control de acciones por POST (manteniendo tus nombres de botones) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configuracion = new Configuracion();

    if (isset($_POST['botonReiniciar'])) { $configuracion->restart(); }
    if (isset($_POST['botonEliminar']))  { $configuracion->delete();  }
    if (isset($_POST['botonExportar']))  { $configuracion->export();  }
}
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
    <title>MotoGP-Configuración</title>
    <link rel="icon" href="multimedia/imagenes/MotoGP.ico" type="image/x-icon">
	<link rel="stylesheet" type="text/css" href="estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="estilo/layout.css" />
</head>
<body>
    <header></header>
    <main>
    <?php
        echo "  
                <h1>Opciones</h1>
                <form action='#' method='post' name='botones'>
                    <input type = 'submit' name = 'botonReiniciar' value = 'Reiniciar'/>
                    <input type = 'submit' name = 'botonEliminar' value = 'Eliminar'/>
                    <input type = 'submit' name = 'botonExportar' value = 'Exportar'/>      
                </form>
            ";
    ?>
    </main>
</body>