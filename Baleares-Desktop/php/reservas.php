<?php
session_start();

class BaseDatosReservas
{
    private string $host = "localhost";
    private string $usuario = "DBUSER2026";
    private string $contrasena = "DBPWD2026";
    private string $nombreBD = "baleares_reservas";
    private ?mysqli $conexion = null;

    public function __construct(bool $seleccionarBD = true)
    {
        $this->conectar($seleccionarBD);
    }

    private function conectar(bool $seleccionarBD): void
    {
        if ($seleccionarBD) {
            $this->conexion = @new mysqli($this->host, $this->usuario, $this->contrasena, $this->nombreBD);
        } else {
            $this->conexion = @new mysqli($this->host, $this->usuario, $this->contrasena);
        }

        if ($this->conexion->connect_error) {
            throw new Exception("Error de conexión con la base de datos: " . $this->conexion->connect_error);
        }

        $this->conexion->set_charset("utf8mb4");
    }

    public function getConexion(): mysqli
    {
        return $this->conexion;
    }

    public function crearBaseDatosYTablas(): void
    {
        $this->conexion->query("CREATE DATABASE IF NOT EXISTS {$this->nombreBD} CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci");

        if (!$this->conexion->select_db($this->nombreBD)) {
            throw new Exception("No se pudo seleccionar la base de datos {$this->nombreBD}.");
        }

        $sentencias = [];

        $sentencias[] = "CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellidos VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            telefono VARCHAR(20) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB";

        $sentencias[] = "CREATE TABLE IF NOT EXISTS tipos_recurso (
            id_tipo INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL UNIQUE,
            descripcion VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB";

        $sentencias[] = "CREATE TABLE IF NOT EXISTS recursos (
            id_recurso INT AUTO_INCREMENT PRIMARY KEY,
            id_tipo INT NOT NULL,
            nombre VARCHAR(150) NOT NULL,
            plazas_maximas INT NOT NULL,
            fecha_hora_inicio DATETIME NOT NULL,
            fecha_hora_fin DATETIME NOT NULL,
            precio DECIMAL(8,2) NOT NULL,
            descripcion TEXT NOT NULL,
            CONSTRAINT fk_recursos_tipos
                FOREIGN KEY (id_tipo)
                REFERENCES tipos_recurso(id_tipo)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
        ) ENGINE=InnoDB";

        $sentencias[] = "CREATE TABLE IF NOT EXISTS estados_reserva (
            id_estado INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(50) NOT NULL UNIQUE
        ) ENGINE=InnoDB";

        $sentencias[] = "CREATE TABLE IF NOT EXISTS reservas (
            id_reserva INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_recurso INT NOT NULL,
            id_estado INT NOT NULL,
            numero_plazas INT NOT NULL,
            presupuesto DECIMAL(8,2) NOT NULL,
            fecha_reserva DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_reservas_usuarios
                FOREIGN KEY (id_usuario)
                REFERENCES usuarios(id_usuario)
                ON UPDATE CASCADE
                ON DELETE RESTRICT,
            CONSTRAINT fk_reservas_recursos
                FOREIGN KEY (id_recurso)
                REFERENCES recursos(id_recurso)
                ON UPDATE CASCADE
                ON DELETE RESTRICT,
            CONSTRAINT fk_reservas_estados
                FOREIGN KEY (id_estado)
                REFERENCES estados_reserva(id_estado)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
        ) ENGINE=InnoDB";

        foreach ($sentencias as $sql) {
            if (!$this->conexion->query($sql)) {
                throw new Exception("Error creando tablas: " . $this->conexion->error);
            }
        }

        $this->insertarDatosIniciales();
    }

    private function insertarDatosIniciales(): void
    {
        $this->conexion->query("INSERT IGNORE INTO tipos_recurso (id_tipo, nombre, descripcion) VALUES
            (1, 'Ruta turística', 'Recorridos turísticos por espacios culturales o naturales de Baleares'),
            (2, 'Museo', 'Visitas a espacios museísticos y patrimoniales'),
            (3, 'Restaurante', 'Experiencias gastronómicas con productos típicos'),
            (4, 'Actividad natural', 'Actividades relacionadas con el paisaje y la naturaleza'),
            (5, 'Experiencia cultural', 'Actividades culturales y visitas guiadas')
        ");

        $this->conexion->query("INSERT IGNORE INTO estados_reserva (id_estado, nombre) VALUES
            (1, 'Pendiente'),
            (2, 'Confirmada'),
            (3, 'Anulada')
        ");

        $this->conexion->query("INSERT IGNORE INTO recursos (
            id_recurso,
            id_tipo,
            nombre,
            plazas_maximas,
            fecha_hora_inicio,
            fecha_hora_fin,
            precio,
            descripcion
        ) VALUES
            (1, 1, 'Ruta monumental por Palma', 20, '2026-06-15 10:00:00', '2026-06-15 13:00:00', 18.00, 'Ruta urbana por el centro histórico de Palma para conocer sus principales monumentos.'),
            (2, 1, 'Ruta natural por el Camí de Cavalls', 15, '2026-06-16 09:00:00', '2026-06-16 12:00:00', 22.00, 'Ruta a pie por paisajes naturales y litorales de Menorca.'),
            (3, 1, 'Ruta histórica por Dalt Vila', 18, '2026-06-17 18:00:00', '2026-06-17 20:00:00', 16.00, 'Ruta por el centro histórico amurallado de Eivissa.'),
            (4, 3, 'Menú gastronómico balear', 30, '2026-06-18 14:00:00', '2026-06-18 16:00:00', 35.00, 'Experiencia gastronómica con productos típicos como sobrasada, ensaimada y queso de Mahón.'),
            (5, 5, 'Visita cultural a Palma', 25, '2026-06-19 11:00:00', '2026-06-19 13:00:00', 20.00, 'Visita guiada por espacios culturales representativos de Palma.')
        ");
    }
}

class Usuarios
{
    private mysqli $conexion;

    public function __construct(mysqli $conexion)
    {
        $this->conexion = $conexion;
    }

    public function registrar(string $nombre, string $apellidos, string $email, string $telefono, string $contrasena): bool
    {
        if ($this->existeEmail($email)) {
            return false;
        }

        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nombre, apellidos, email, telefono, password_hash)
                VALUES (?, ?, ?, ?, ?)";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("sssss", $nombre, $apellidos, $email, $telefono, $hash);

        return $sentencia->execute();
    }

    public function existeEmail(string $email): bool
    {
        $sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("s", $email);
        $sentencia->execute();

        $resultado = $sentencia->get_result();

        return $resultado->num_rows > 0;
    }

    public function obtenerPorEmail(string $email): ?array
    {
        $sql = "SELECT id_usuario, nombre, apellidos, email, telefono
                FROM usuarios
                WHERE email = ?";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("s", $email);
        $sentencia->execute();

        $resultado = $sentencia->get_result();

        if ($resultado->num_rows === 0) {
            return null;
        }

        return $resultado->fetch_assoc();
    }
}

class Recursos
{
    private mysqli $conexion;

    public function __construct(mysqli $conexion)
    {
        $this->conexion = $conexion;
    }

    public function listar(): array
    {
        $sql = "SELECT
                    r.id_recurso,
                    r.nombre,
                    r.plazas_maximas,
                    r.fecha_hora_inicio,
                    r.fecha_hora_fin,
                    r.precio,
                    r.descripcion,
                    t.nombre AS tipo
                FROM recursos r
                INNER JOIN tipos_recurso t ON r.id_tipo = t.id_tipo
                ORDER BY r.fecha_hora_inicio ASC";

        $resultado = $this->conexion->query($sql);
        $recursos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $fila["plazas_disponibles"] = $this->calcularPlazasDisponibles((int)$fila["id_recurso"]);
            $recursos[] = $fila;
        }

        return $recursos;
    }

    public function obtenerPorId(int $idRecurso): ?array
    {
        $sql = "SELECT
                    r.id_recurso,
                    r.nombre,
                    r.plazas_maximas,
                    r.fecha_hora_inicio,
                    r.fecha_hora_fin,
                    r.precio,
                    r.descripcion,
                    t.nombre AS tipo
                FROM recursos r
                INNER JOIN tipos_recurso t ON r.id_tipo = t.id_tipo
                WHERE r.id_recurso = ?";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("i", $idRecurso);
        $sentencia->execute();

        $resultado = $sentencia->get_result();

        if ($resultado->num_rows === 0) {
            return null;
        }

        $recurso = $resultado->fetch_assoc();
        $recurso["plazas_disponibles"] = $this->calcularPlazasDisponibles($idRecurso);

        return $recurso;
    }

    public function calcularPlazasDisponibles(int $idRecurso): int
    {
        $recurso = $this->obtenerDatosBasicos($idRecurso);

        if ($recurso === null) {
            return 0;
        }

        $reservadas = $this->calcularPlazasReservadas($idRecurso);

        return (int)$recurso["plazas_maximas"] - $reservadas;
    }

    private function obtenerDatosBasicos(int $idRecurso): ?array
    {
        $sql = "SELECT id_recurso, plazas_maximas
                FROM recursos
                WHERE id_recurso = ?";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("i", $idRecurso);
        $sentencia->execute();

        $resultado = $sentencia->get_result();

        if ($resultado->num_rows === 0) {
            return null;
        }

        return $resultado->fetch_assoc();
    }

    private function calcularPlazasReservadas(int $idRecurso): int
    {
        $sql = "SELECT COALESCE(SUM(numero_plazas), 0) AS plazas_reservadas
                FROM reservas
                WHERE id_recurso = ?
                AND id_estado <> 3";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("i", $idRecurso);
        $sentencia->execute();

        $resultado = $sentencia->get_result();
        $fila = $resultado->fetch_assoc();

        return (int)$fila["plazas_reservadas"];
    }
}

class Reservas
{
    private mysqli $conexion;
    private Recursos $recursos;

    public function __construct(mysqli $conexion, Recursos $recursos)
    {
        $this->conexion = $conexion;
        $this->recursos = $recursos;
    }

    public function calcularPresupuesto(int $idRecurso, int $numeroPlazas): ?float
    {
        $recurso = $this->recursos->obtenerPorId($idRecurso);

        if ($recurso === null || $numeroPlazas <= 0) {
            return null;
        }

        return (float)$recurso["precio"] * $numeroPlazas;
    }

    public function confirmar(int $idUsuario, int $idRecurso, int $numeroPlazas): bool
    {
        $recurso = $this->recursos->obtenerPorId($idRecurso);

        if ($recurso === null || $numeroPlazas <= 0) {
            return false;
        }

        if ($numeroPlazas > (int)$recurso["plazas_disponibles"]) {
            return false;
        }

        $presupuesto = $this->calcularPresupuesto($idRecurso, $numeroPlazas);

        if ($presupuesto === null) {
            return false;
        }

        $estadoConfirmada = 2;

        $sql = "INSERT INTO reservas (id_usuario, id_recurso, id_estado, numero_plazas, presupuesto)
                VALUES (?, ?, ?, ?, ?)";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("iiiid", $idUsuario, $idRecurso, $estadoConfirmada, $numeroPlazas, $presupuesto);

        return $sentencia->execute();
    }

    public function listarPorUsuario(int $idUsuario): array
    {
        $sql = "SELECT
                    res.id_reserva,
                    rec.nombre AS recurso,
                    rec.fecha_hora_inicio,
                    rec.fecha_hora_fin,
                    res.numero_plazas,
                    res.presupuesto,
                    res.fecha_reserva,
                    est.nombre AS estado
                FROM reservas res
                INNER JOIN recursos rec ON res.id_recurso = rec.id_recurso
                INNER JOIN estados_reserva est ON res.id_estado = est.id_estado
                WHERE res.id_usuario = ?
                ORDER BY res.fecha_reserva DESC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("i", $idUsuario);
        $sentencia->execute();

        $resultado = $sentencia->get_result();
        $reservas = [];

        while ($fila = $resultado->fetch_assoc()) {
            $reservas[] = $fila;
        }

        return $reservas;
    }

    public function anular(int $idReserva, int $idUsuario): bool
    {
        $estadoAnulada = 3;

        $sql = "UPDATE reservas
                SET id_estado = ?
                WHERE id_reserva = ?
                AND id_usuario = ?
                AND id_estado <> ?";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bind_param("iiii", $estadoAnulada, $idReserva, $idUsuario, $estadoAnulada);
        $sentencia->execute();

        return $sentencia->affected_rows > 0;
    }
}

class AplicacionReservas
{
    private ?mysqli $conexion = null;
    private ?Usuarios $usuarios = null;
    private ?Recursos $recursos = null;
    private ?Reservas $reservas = null;

    private string $mensaje = "";
    private array $reservasConsultadas = [];

    public function ejecutar(): void
    {
        try {
            if ($this->debeCrearBaseDatos()) {
                $bd = new BaseDatosReservas(false);
                $bd->crearBaseDatosYTablas();
                $this->mensaje = "Base de datos, tablas y datos iniciales creados correctamente.";
            }

            $this->inicializarRepositorios();

            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $accion = $this->obtenerPost("accion");

                if ($accion === "registrar") {
                    $this->procesarRegistro();
                } elseif ($accion === "presupuesto") {
                    $this->procesarPresupuesto();
                } elseif ($accion === "confirmar") {
                    $this->procesarConfirmacion();
                } elseif ($accion === "consultar") {
                    $this->procesarConsulta();
                } elseif ($accion === "anular") {
                    $this->procesarAnulacion();
                }
            }

            $this->mostrarPagina();
        } catch (Exception $excepcion) {
            $this->mostrarPaginaError($excepcion->getMessage());
        }
    }

    private function debeCrearBaseDatos(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === "POST" && $this->obtenerPost("accion") === "crear_bd";
    }

    private function inicializarRepositorios(): void
    {
        $bd = new BaseDatosReservas(true);
        $this->conexion = $bd->getConexion();

        $this->usuarios = new Usuarios($this->conexion);
        $this->recursos = new Recursos($this->conexion);
        $this->reservas = new Reservas($this->conexion, $this->recursos);
    }

    private function procesarRegistro(): void
    {
        $nombre = $this->obtenerPost("nombre");
        $apellidos = $this->obtenerPost("apellidos");
        $email = $this->obtenerPost("email");
        $telefono = $this->obtenerPost("telefono");
        $contrasena = $this->obtenerPost("contrasena");

        if ($nombre === "" || $apellidos === "" || $email === "" || $telefono === "" || $contrasena === "") {
            $this->mensaje = "Debe completar todos los campos del registro.";
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mensaje = "El correo electrónico no tiene un formato válido.";
            return;
        }

        if ($this->usuarios->registrar($nombre, $apellidos, $email, $telefono, $contrasena)) {
            $this->mensaje = "Usuario registrado correctamente. Ya puede realizar reservas.";
        } else {
            $this->mensaje = "No se pudo registrar el usuario. Es posible que el correo electrónico ya exista.";
        }
    }

    private function procesarPresupuesto(): void
    {
        $email = $this->obtenerPost("email_presupuesto");
        $idRecurso = (int)$this->obtenerPost("id_recurso_presupuesto");
        $plazas = (int)$this->obtenerPost("plazas_presupuesto");

        $usuario = $this->usuarios->obtenerPorEmail($email);

        if ($usuario === null) {
            $this->mensaje = "Debe estar registrado para generar un presupuesto.";
            return;
        }

        $recurso = $this->recursos->obtenerPorId($idRecurso);

        if ($recurso === null) {
            $this->mensaje = "El recurso turístico seleccionado no existe.";
            return;
        }

        if ($plazas <= 0) {
            $this->mensaje = "El número de plazas debe ser mayor que cero.";
            return;
        }

        if ($plazas > (int)$recurso["plazas_disponibles"]) {
            $this->mensaje = "No hay plazas suficientes para ese recurso turístico.";
            return;
        }

        $presupuesto = $this->reservas->calcularPresupuesto($idRecurso, $plazas);

        if ($presupuesto === null) {
            $this->mensaje = "No se pudo calcular el presupuesto.";
            return;
        }

        $this->mensaje = "Presupuesto generado para " . $this->escapar($recurso["nombre"]) . ": " .
            number_format($presupuesto, 2, ",", ".") . " euros. Si está conforme, confirme la reserva.";
    }

    private function procesarConfirmacion(): void
    {
        $email = $this->obtenerPost("email_confirmacion");
        $idRecurso = (int)$this->obtenerPost("id_recurso_confirmacion");
        $plazas = (int)$this->obtenerPost("plazas_confirmacion");

        $usuario = $this->usuarios->obtenerPorEmail($email);

        if ($usuario === null) {
            $this->mensaje = "Debe estar registrado para confirmar una reserva.";
            return;
        }

        if ($this->reservas->confirmar((int)$usuario["id_usuario"], $idRecurso, $plazas)) {
            $this->mensaje = "Reserva confirmada correctamente.";
        } else {
            $this->mensaje = "No se pudo confirmar la reserva. Revise el recurso y las plazas disponibles.";
        }
    }

    private function procesarConsulta(): void
    {
        $email = $this->obtenerPost("email_consulta");
        $usuario = $this->usuarios->obtenerPorEmail($email);

        if ($usuario === null) {
            $this->mensaje = "No existe ningún usuario registrado con ese correo electrónico.";
            return;
        }

        $this->reservasConsultadas = $this->reservas->listarPorUsuario((int)$usuario["id_usuario"]);
        $this->mensaje = "Reservas asociadas a " . $this->escapar($usuario["nombre"]) . " " . $this->escapar($usuario["apellidos"]) . ".";
    }

    private function procesarAnulacion(): void
    {
        $email = $this->obtenerPost("email_anulacion");
        $idReserva = (int)$this->obtenerPost("id_reserva_anulacion");

        $usuario = $this->usuarios->obtenerPorEmail($email);

        if ($usuario === null) {
            $this->mensaje = "No existe ningún usuario registrado con ese correo electrónico.";
            return;
        }

        if ($this->reservas->anular($idReserva, (int)$usuario["id_usuario"])) {
            $this->mensaje = "Reserva anulada correctamente.";
        } else {
            $this->mensaje = "No se pudo anular la reserva. Compruebe el número de reserva.";
        }
    }

    private function mostrarPagina(): void
    {
        $listaRecursos = [];

        if ($this->recursos !== null) {
            $listaRecursos = $this->recursos->listar();
        }

        echo '<!DOCTYPE HTML>';
        echo '<html lang="es">';
        $this->mostrarHead();
        echo '<body>';
        $this->mostrarHeader();

        echo '<p>Estás en: <a href="../index.html">Inicio</a> &gt; Reservas</p>';

        echo '<main>';
        echo '<section>';
        echo '<h2>Central de reservas de recursos turísticos</h2>';
        echo '<p>Esta página permite registrar usuarios, consultar recursos turísticos, generar presupuestos, confirmar reservas, consultar reservas realizadas y anular reservas.</p>';
        echo '</section>';

        $this->mostrarFormularioCrearBD();

        if ($this->mensaje !== "") {
            echo '<section>';
            echo '<h2>Resultado de la operación</h2>';
            echo '<p>' . $this->mensaje . '</p>';
            echo '</section>';
        }

        $this->mostrarFormularioRegistro();
        $this->mostrarRecursos($listaRecursos);
        $this->mostrarFormularioPresupuesto($listaRecursos);
        $this->mostrarFormularioConfirmacion($listaRecursos);
        $this->mostrarFormularioConsulta();
        $this->mostrarReservasConsultadas();
        $this->mostrarFormularioAnulacion();

        echo '</main>';

        $this->mostrarFooter();
        echo '</body>';
        echo '</html>';
    }

    private function mostrarHead(): void
    {
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="author" content="Diego Martínez Menéndez">';
        echo '<meta name="description" content="Central de reservas de recursos turísticos de Baleares">';
        echo '<meta name="keywords" content="Baleares, reservas, recursos turísticos, turismo">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>Baleares - Reservas</title>';
        echo '<link rel="icon" href="../multimedia/imagenes/Baleares.ico" type="image/x-icon">';
        echo '<link rel="stylesheet" href="../estilo/estilo.css">';
        echo '<link rel="stylesheet" href="../estilo/layout.css">';
        echo '</head>';
    }

    private function mostrarHeader(): void
    {
        echo '<header>';
        echo '<h1><a href="../index.html">Baleares</a></h1>';

        echo '<nav>';
        echo '<a href="../index.html" accesskey="i">Inicio</a>';
        echo '<a href="../gastronomia.html" accesskey="g">Gastronomía</a>';
        echo '<a href="../rutas.html" accesskey="r">Rutas</a>';
        echo '<a href="../meteorologia.html" accesskey="m">Meteorología</a>';
        echo '<a href="../juego.html" accesskey="j">Juego</a>';
        echo '<a href="reservas.php" class="active" aria-current="page" accesskey="v">Reservas</a>';
        echo '<a href="../ayuda.html" accesskey="a">Ayuda</a>';
        echo '</nav>';

        echo '</header>';
    }

    private function mostrarFormularioCrearBD(): void
    {
        echo '<section>';
        echo '<h2>Inicialización de la base de datos</h2>';
        echo '<p>Use esta opción si todavía no se han creado las tablas y los datos iniciales de reservas.</p>';
        echo '<form action="reservas.php" method="post">';
        echo '<input type="hidden" name="accion" value="crear_bd">';
        echo '<p><button type="submit">Crear base de datos, tablas y datos iniciales</button></p>';
        echo '</form>';
        echo '</section>';
    }

    private function mostrarFormularioRegistro(): void
    {
        echo '<section>';
        echo '<h2>Registro de usuarios</h2>';
        echo '<form action="reservas.php" method="post">';
        echo '<input type="hidden" name="accion" value="registrar">';

        echo '<fieldset>';
        echo '<legend>Datos del usuario</legend>';

        echo '<p><label for="nombre">Nombre</label>';
        echo '<input type="text" id="nombre" name="nombre" required></p>';

        echo '<p><label for="apellidos">Apellidos</label>';
        echo '<input type="text" id="apellidos" name="apellidos" required></p>';

        echo '<p><label for="email">Correo electrónico</label>';
        echo '<input type="email" id="email" name="email" required></p>';

        echo '<p><label for="telefono">Teléfono</label>';
        echo '<input type="tel" id="telefono" name="telefono" required></p>';

        echo '<p><label for="contrasena">Contraseña</label>';
        echo '<input type="password" id="contrasena" name="contrasena" required></p>';

        echo '</fieldset>';
        echo '<p><button type="submit">Registrar usuario</button></p>';
        echo '</form>';
        echo '</section>';
    }

    private function mostrarRecursos(array $recursos): void
    {
        echo '<section>';
        echo '<h2>Recursos turísticos disponibles</h2>';

        if (count($recursos) === 0) {
            echo '<p>No hay recursos turísticos disponibles. Cree la base de datos y los datos iniciales si aún no lo ha hecho.</p>';
            echo '</section>';
            return;
        }

        foreach ($recursos as $recurso) {
            echo '<article>';
            echo '<h3>' . $this->escapar($recurso["nombre"]) . '</h3>';
            echo '<dl>';
            echo '<dt>Tipo</dt><dd>' . $this->escapar($recurso["tipo"]) . '</dd>';
            echo '<dt>Plazas máximas</dt><dd>' . (int)$recurso["plazas_maximas"] . '</dd>';
            echo '<dt>Plazas disponibles</dt><dd>' . (int)$recurso["plazas_disponibles"] . '</dd>';
            echo '<dt>Fecha y hora de inicio</dt><dd>' . $this->formatearFechaHora($recurso["fecha_hora_inicio"]) . '</dd>';
            echo '<dt>Fecha y hora de finalización</dt><dd>' . $this->formatearFechaHora($recurso["fecha_hora_fin"]) . '</dd>';
            echo '<dt>Precio por plaza</dt><dd>' . number_format((float)$recurso["precio"], 2, ",", ".") . ' euros</dd>';
            echo '<dt>Descripción</dt><dd>' . $this->escapar($recurso["descripcion"]) . '</dd>';
            echo '</dl>';
            echo '</article>';
        }

        echo '</section>';
    }

    private function mostrarFormularioPresupuesto(array $recursos): void
    {
        echo '<section>';
        echo '<h2>Generar presupuesto</h2>';
        echo '<form action="reservas.php" method="post">';
        echo '<input type="hidden" name="accion" value="presupuesto">';

        echo '<fieldset>';
        echo '<legend>Datos para el presupuesto</legend>';

        echo '<p><label for="email_presupuesto">Correo electrónico registrado</label>';
        echo '<input type="email" id="email_presupuesto" name="email_presupuesto" required></p>';

        echo '<p><label for="id_recurso_presupuesto">Recurso turístico</label>';
        echo '<select id="id_recurso_presupuesto" name="id_recurso_presupuesto" required>';
        $this->mostrarOpcionesRecursos($recursos);
        echo '</select></p>';

        echo '<p><label for="plazas_presupuesto">Número de plazas</label>';
        echo '<input type="number" id="plazas_presupuesto" name="plazas_presupuesto" min="1" required></p>';

        echo '</fieldset>';
        echo '<p><button type="submit">Calcular presupuesto</button></p>';
        echo '</form>';
        echo '</section>';
    }

    private function mostrarFormularioConfirmacion(array $recursos): void
    {
        echo '<section>';
        echo '<h2>Confirmar reserva</h2>';
        echo '<p>Después de generar el presupuesto, confirme la reserva indicando los mismos datos.</p>';

        echo '<form action="reservas.php" method="post">';
        echo '<input type="hidden" name="accion" value="confirmar">';

        echo '<fieldset>';
        echo '<legend>Datos de confirmación</legend>';

        echo '<p><label for="email_confirmacion">Correo electrónico registrado</label>';
        echo '<input type="email" id="email_confirmacion" name="email_confirmacion" required></p>';

        echo '<p><label for="id_recurso_confirmacion">Recurso turístico</label>';
        echo '<select id="id_recurso_confirmacion" name="id_recurso_confirmacion" required>';
        $this->mostrarOpcionesRecursos($recursos);
        echo '</select></p>';

        echo '<p><label for="plazas_confirmacion">Número de plazas</label>';
        echo '<input type="number" id="plazas_confirmacion" name="plazas_confirmacion" min="1" required></p>';

        echo '</fieldset>';
        echo '<p><button type="submit">Confirmar reserva</button></p>';
        echo '</form>';
        echo '</section>';
    }

    private function mostrarFormularioConsulta(): void
    {
        echo '<section>';
        echo '<h2>Consultar reservas del usuario</h2>';
        echo '<form action="reservas.php" method="post">';
        echo '<input type="hidden" name="accion" value="consultar">';

        echo '<fieldset>';
        echo '<legend>Datos de consulta</legend>';

        echo '<p><label for="email_consulta">Correo electrónico registrado</label>';
        echo '<input type="email" id="email_consulta" name="email_consulta" required></p>';

        echo '</fieldset>';
        echo '<p><button type="submit">Consultar reservas</button></p>';
        echo '</form>';
        echo '</section>';
    }

    private function mostrarReservasConsultadas(): void
    {
        if (count($this->reservasConsultadas) === 0) {
            return;
        }

        echo '<section>';
        echo '<h2>Listado de reservas del usuario</h2>';

        foreach ($this->reservasConsultadas as $reserva) {
            echo '<article>';
            echo '<h3>Reserva ' . (int)$reserva["id_reserva"] . '</h3>';
            echo '<dl>';
            echo '<dt>Recurso turístico</dt><dd>' . $this->escapar($reserva["recurso"]) . '</dd>';
            echo '<dt>Inicio</dt><dd>' . $this->formatearFechaHora($reserva["fecha_hora_inicio"]) . '</dd>';
            echo '<dt>Fin</dt><dd>' . $this->formatearFechaHora($reserva["fecha_hora_fin"]) . '</dd>';
            echo '<dt>Número de plazas</dt><dd>' . (int)$reserva["numero_plazas"] . '</dd>';
            echo '<dt>Presupuesto</dt><dd>' . number_format((float)$reserva["presupuesto"], 2, ",", ".") . ' euros</dd>';
            echo '<dt>Fecha de reserva</dt><dd>' . $this->formatearFechaHora($reserva["fecha_reserva"]) . '</dd>';
            echo '<dt>Estado</dt><dd>' . $this->escapar($reserva["estado"]) . '</dd>';
            echo '</dl>';
            echo '</article>';
        }

        echo '</section>';
    }

    private function mostrarFormularioAnulacion(): void
    {
        echo '<section>';
        echo '<h2>Anular reserva</h2>';
        echo '<p>Para anular una reserva, introduzca el correo electrónico registrado y el número de reserva que aparece en el listado.</p>';

        echo '<form action="reservas.php" method="post">';
        echo '<input type="hidden" name="accion" value="anular">';

        echo '<fieldset>';
        echo '<legend>Datos de anulación</legend>';

        echo '<p><label for="email_anulacion">Correo electrónico registrado</label>';
        echo '<input type="email" id="email_anulacion" name="email_anulacion" required></p>';

        echo '<p><label for="id_reserva_anulacion">Número de reserva</label>';
        echo '<input type="number" id="id_reserva_anulacion" name="id_reserva_anulacion" min="1" required></p>';

        echo '</fieldset>';
        echo '<p><button type="submit">Anular reserva</button></p>';
        echo '</form>';
        echo '</section>';
    }

    private function mostrarOpcionesRecursos(array $recursos): void
    {
        foreach ($recursos as $recurso) {
            echo '<option value="' . (int)$recurso["id_recurso"] . '">';
            echo $this->escapar($recurso["nombre"]) . ' - ' . number_format((float)$recurso["precio"], 2, ",", ".") . ' euros por plaza';
            echo '</option>';
        }
    }

    private function mostrarFooter(): void
    {
        echo '<footer>';
        echo '<p>';
        echo 'All Rights Reserved. &copy; 2026 <a href="../index.html">Baleares</a>';
        echo ' - Design By : <a href="https://github.com/diegomarty00">Diego Martinez Menéndez</a>';
        echo '</p>';
        echo '</footer>';
    }

    private function mostrarPaginaError(string $error): void
    {
        echo '<!DOCTYPE HTML>';
        echo '<html lang="es">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="author" content="Diego Martínez Menéndez">';
        echo '<meta name="description" content="Error en la central de reservas de Baleares">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>Baleares - Error de reservas</title>';
        echo '<link rel="stylesheet" href="../estilo/estilo.css">';
        echo '<link rel="stylesheet" href="../estilo/layout.css">';
        echo '</head>';
        echo '<body>';
        echo '<main>';
        echo '<section>';
        echo '<h1>Error en la central de reservas</h1>';
        echo '<p>' . $this->escapar($error) . '</p>';
        echo '<p>Compruebe que existe la base de datos, que MySQL o MariaDB está activo y que el usuario DBUSER2026 tiene permisos.</p>';
        echo '</section>';
        echo '</main>';
        echo '</body>';
        echo '</html>';
    }

    private function obtenerPost(string $clave): string
    {
        return isset($_POST[$clave]) ? trim((string)$_POST[$clave]) : "";
    }

    private function escapar(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, "UTF-8");
    }

    private function formatearFechaHora(string $fechaHora): string
    {
        $fecha = new DateTime($fechaHora);
        return $fecha->format("d/m/Y H:i");
    }
}

$aplicacion = new AplicacionReservas();
$aplicacion->ejecutar();
