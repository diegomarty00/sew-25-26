
<?php
session_start();

class Configuracion {
  private $host = 'localhost';
  // En XAMPP por defecto suele ser: user 'root' y pass '' (vacío).
  // Cambia estas credenciales si tienes otras configuradas.
  private $user = 'root';
  private $pass = '';
  private $dbname = 'UO270457_db';
  private $connection = null;

  public function __construct() {
    $this->conect();
  }

  /** Conecta SIN seleccionar BD para poder crearla si no existe */
  private function conect() {
    // Conexión genérica (sin dbname)
    $this->connection = @new mysqli($this->host, $this->user, $this->pass);
    if ($this->connection->connect_error) {
      exit('Error de conexión: ' . $this->connection->connect_error);
    }
    $this->connection->set_charset('utf8mb4');
  }

  /** Crea la BD y las tablas EXACTAS que has solicitado */
  public function create() {
    // 1) Crear BD si no existe
    $this->connection->query("CREATE DATABASE IF NOT EXISTS {$this->dbname} CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci");

    // 2) Seleccionar BD
    if (!$this->connection->select_db($this->dbname)) {
      exit('No se pudo seleccionar la BD: ' . $this->dbname);
    }

    // 3) Crear tablas
    $ddl = [];

    $ddl[] = "CREATE TABLE IF NOT EXISTS Usuarios (
                ID_Usuario INT AUTO_INCREMENT PRIMARY KEY,
                Profesion TEXT NOT NULL,
                Edad INT NOT NULL,
                Genero ENUM('Masculino','Femenino','Otro') NOT NULL,
                Pericia_Informatica INT NOT NULL,
                CONSTRAINT chk_edad CHECK (Edad BETWEEN 1 AND 120),
                CONSTRAINT chk_pericia CHECK (Pericia_Informatica BETWEEN 0 AND 10)
              ) ENGINE=InnoDB";

    /* Mantengo tu nombre literal 'Obsevaciones' */
    $ddl[] = "CREATE TABLE IF NOT EXISTS Obsevaciones (
                ID_Usuario INT NOT NULL,
                Comentario TEXT NOT NULL,
                CONSTRAINT fk_obsevaciones_usuario
                  FOREIGN KEY (ID_Usuario) REFERENCES Usuarios(ID_Usuario)
                  ON UPDATE CASCADE ON DELETE CASCADE
              ) ENGINE=InnoDB";

    $ddl[] = "CREATE TABLE IF NOT EXISTS Resultados (
                ID_Usuario INT NOT NULL,
                Dispositivo ENUM('Tableta','Ordenador','Movil') NOT NULL,
                Tiempo TIME NOT NULL,
                Completado BOOLEAN NOT NULL,
                Comentarios TEXT NULL,
                Propuesta TEXT NULL,
                Valoracion INT NOT NULL,
                CONSTRAINT fk_resultados_usuario
                  FOREIGN KEY (ID_Usuario) REFERENCES Usuarios(ID_Usuario)
                  ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT chk_valoracion CHECK (Valoracion BETWEEN 0 AND 10)
              ) ENGINE=InnoDB";

    foreach ($ddl as $sql) {
      $this->connection->query($sql);
    }
  }

  /** Vacía las tablas (sin borrar la estructura) */
  public function restart() {
    if (!$this->connection->select_db($this->dbname)) {
      exit('La BD no existe. Pulsa "Crear BD y tablas" primero.');
    }
    $this->connection->query('SET FOREIGN_KEY_CHECKS = 0');
    $tablas = ['Resultados','Obsevaciones','Usuarios']; // orden por dependencias
    foreach ($tablas as $tabla) {
      $this->connection->query("TRUNCATE TABLE $tabla");
    }
    $this->connection->query('SET FOREIGN_KEY_CHECKS = 1');
  }

  /** Elimina la base de datos completa */
  public function delete() {
    // Asegúrate de estar conectado genérico (ya lo estamos)
    $this->connection->query("DROP DATABASE IF EXISTS {$this->dbname}");
  }

  /** Exporta las tablas a CSV (secciones por tabla) */
  
  
  public function export(){
    // 1) Seleccionar BD
    if (!$this->connection->select_db($this->dbname)) {
      exit('La BD no existe. Pulsa "Crear BD y tablas" primero.');
    }
    $this->connection->set_charset('utf8mb4');
    $this->connection->query("SET NAMES utf8mb4");

    // 2) Evitar cualquier salida previa a headers
    if (ob_get_length()) { ob_end_clean(); }

    // 3) Opciones
    $sep = ';';               // Separador regional para Excel en ES
    $tiempoComo = 'decimal';  // 'decimal' => 7,46 | 'mmss' => 07:28 (mm:ss)

    // 4) Headers HTTP
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=tablas_UO270457.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    // 5) BOM para Excel
    echo "\xEF\xBB\xBF";

    // 6) Abre salida
    $out = fopen('php://output', 'w');

    // === Helper para escribir secciones ===
    $writeSection = function($title, $headers, $rows) use ($out, $sep) {
      fputcsv($out, [''], $sep);                 // línea en blanco
      fputcsv($out, ["Tabla: $title"], $sep);    // título de la tabla
      fputcsv($out, $headers, $sep);             // cabeceras
      foreach ($rows as $r) { fputcsv($out, $r, $sep); } // filas
    };

    // 7) USUARIOS
    $sqlUsuarios = "SELECT ID_Usuario, Profesion, Edad, Genero, Pericia_Informatica
                    FROM Usuarios
                    ORDER BY ID_Usuario ASC";
    $res = $this->connection->query($sqlUsuarios);
    $rowsUsuarios = [];
    if ($res) {
      while ($r = $res->fetch_assoc()) {
        $rowsUsuarios[] = [
          $r['ID_Usuario'],
          $r['Profesion'],
          $r['Edad'],
          $r['Genero'],
          $r['Pericia_Informatica'],
        ];
      }
    }
    $writeSection('Usuarios',
      ['ID Usuario','Profesión','Edad','Género','Pericia informática'],
      $rowsUsuarios
    );

    // 8) OBSEVACIONES (nombre literal que usas)
    $sqlObsev = "SELECT ID_Usuario, Comentario
                FROM Obsevaciones
                ORDER BY ID_Usuario ASC";
    $res = $this->connection->query($sqlObsev);
    $rowsObsev = [];
    if ($res) {
      while ($r = $res->fetch_assoc()) {
        $rowsObsev[] = [
          $r['ID_Usuario'],
          $r['Comentario'],
        ];
      }
    }
    $writeSection('Obsevaciones', ['ID Usuario','Comentario'], $rowsObsev);

    // 9) RESULTADOS (ordenado por tiempo ascendente)
    $sqlResultados = "SELECT ID_Usuario, Dispositivo, Tiempo, Completado, Comentarios, Propuesta, `Valoracion`
                      FROM Resultados
                      ORDER BY ID_Usuario ASC";
    $res = $this->connection->query($sqlResultados);
    $rowsResultados = [];
    if ($res) {
      while ($r = $res->fetch_assoc()) {

        // Convertir Tiempo según preferencia
        $tiempoStr = $r['Tiempo'];
        $colTiempo = $tiempoStr; // por defecto

        if ($tiempoComo === 'decimal') {
          // HH:MM:SS -> minutos decimales con coma
          if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $tiempoStr, $m)) {
            $h = (int)$m[1]; $mi = (int)$m[2]; $s = (int)$m[3];
            $minDec = $h * 60 + $mi + ($s / 60);
            $colTiempo = number_format($minDec, 2, ',', '');
          } elseif (preg_match('/^(\d{1,2}):(\d{2})$/', $tiempoStr, $m2)) {
            $h = 0; $mi = (int)$m2[1]; $s = (int)$m2[2];
            $minDec = $h * 60 + $mi + ($s / 60);
            $colTiempo = number_format($minDec, 2, ',', '');
          }
        } else { // 'mmss'
          if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $tiempoStr, $m)) {
            $total = $m[1]*3600 + $m[2]*60 + $m[3];
            $mm = floor($total / 60);
            $ss = $total % 60;
            $colTiempo = sprintf('%02d:%02d', $mm, $ss);
          } elseif (preg_match('/^(\d{1,2}):(\d{2})$/', $tiempoStr, $m2)) {
            $mm = (int)$m2[1]; $ss = (int)$m2[2];
            $colTiempo = sprintf('%02d:%02d', $mm, $ss);
          }
        }

        // Completado a texto
        $colCompletado = ((string)$r['Completado'] === '1') ? 'Completado' : 'No completado';

        $rowsResultados[] = [
          $r['ID_Usuario'],
          $r['Dispositivo'],
          $colTiempo,
          $colCompletado,
          $r['Comentarios'],
          $r['Propuesta'],
          $r['Valoracion'],
        ];
      }
    }
    $writeSection(
      'Resultados',
      [
        'ID Usuario','Dispositivo','Tiempo',
        'Finalización de prueba','Comentarios del usuario',
        'Propuesta de mejora','Valoracion del usuario'
      ],
      $rowsResultados
    );

    fclose($out);
    exit;
  }

}


/* Control de botones */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $config = new Configuracion();
  if (isset($_POST['botonCrear']))     $config->create();
  if (isset($_POST['botonReiniciar'])) $config->restart();
  if (isset($_POST['botonEliminar']))  $config->delete();
  if (isset($_POST['botonExportar']))  $config->export();
}
?>

<!DOCTYPE HTML>

<html lang="es">
<head>
    <!-- Datos que describen el documento -->
    <meta charset="UTF-8" />
    <meta name="author" content="Iker Jiménez Herrero"/>
    <meta name="description" content="Juegos de MotoGP Desktop"/>
    <meta name="keywords" content="Juegos, MotoGP"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>MotoGP</title>
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css" />
</head>
<body>
    <!-- Datos con el contenidos que aparece en el navegador -->
     
    <header><h1>Configuración de la base de datos</h1>
     </header>
    <main>
    <?php
        echo "  
                
                <form action='#' method='post' name='botones'>
                    <input type='submit' name='botonCrear' value='Crear BD y tablas'/>
                    <input type='submit' name='botonReiniciar' value='Reiniciar (vaciar)'/>
                    <input type='submit' name='botonEliminar'  value='Eliminar BD'/>
                    <input type='submit' name='botonExportar'  value='Exportar .CSV'/>
                </form>
            ";        
    ?>
    </main>
</body>