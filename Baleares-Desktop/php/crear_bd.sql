DROP DATABASE IF EXISTS baleares_reservas;
CREATE DATABASE baleares_reservas CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;

CREATE USER IF NOT EXISTS 'DBUSER2026'@'localhost' IDENTIFIED BY 'DBPWD2026';
GRANT ALL PRIVILEGES ON baleares_reservas.* TO 'DBUSER2026'@'localhost';
FLUSH PRIVILEGES;

USE baleares_reservas;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tipos_recurso (
    id_tipo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL
);

CREATE TABLE recursos (
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
);

CREATE TABLE estados_reserva (
    id_estado INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE reservas (
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
);

########################################
# Datos de ejemplo que se pueden poner #
########################################

INSERT INTO tipos_recurso (id_tipo, nombre, descripcion) VALUES
(1, 'Ruta turística', 'Recorridos turísticos por espacios culturales o naturales de Baleares'),
(2, 'Museo', 'Visitas a espacios museísticos y patrimoniales'),
(3, 'Restaurante', 'Experiencias gastronómicas con productos típicos'),
(4, 'Actividad natural', 'Actividades relacionadas con el paisaje y la naturaleza'),
(5, 'Experiencia cultural', 'Actividades culturales y visitas guiadas');

INSERT INTO estados_reserva (id_estado, nombre) VALUES
(1, 'Pendiente'),
(2, 'Confirmada'),
(3, 'Anulada');

INSERT INTO recursos (
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
(4, 3, 'Menú gastronómico balear', 30, '2026-06-18 14:00:00', '2026-06-18 16:00:00', 35.00, 'Experiencia gastronómica con productos típicos como sobrasada ensaimada y queso de Mahón.'),
(5, 5, 'Visita cultural a Palma', 25, '2026-06-19 11:00:00', '2026-06-19 13:00:00', 20.00, 'Visita guiada por espacios culturales representativos de Palma.');