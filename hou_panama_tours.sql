DROP DATABASE IF EXISTS db_hou_panama;
CREATE DATABASE db_hou_panama CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_hou_panama;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE destinos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  nombre VARCHAR(150) NOT NULL,
  provincia VARCHAR(80) NOT NULL,
  descripcion_corta VARCHAR(255) NOT NULL,
  descripcion_larga TEXT NOT NULL,
  precio_base DECIMAL(10,2) NOT NULL,
  imagen VARCHAR(255) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE reservas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo_reserva VARCHAR(80) NOT NULL UNIQUE,
  id_usuario INT NOT NULL,
  id_destino INT NOT NULL,
  nombre_contacto VARCHAR(120) NOT NULL,
  telefono_contacto VARCHAR(30) NOT NULL,
  fecha_tour DATE NOT NULL,
  adultos INT NOT NULL,
  ninos INT NOT NULL,
  jubilados INT NOT NULL,
  precio_base DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  estado ENUM('Pendiente de Pago','Pagado','Confirmado','Cancelado') NOT NULL DEFAULT 'Pendiente de Pago',
  metodo_pago VARCHAR(30) DEFAULT NULL,
  comentarios TEXT,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
  FOREIGN KEY (id_destino) REFERENCES destinos(id)
);

CREATE TABLE pagos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_reserva INT NOT NULL,
  metodo VARCHAR(30) NOT NULL,
  referencia VARCHAR(80) NOT NULL,
  estado VARCHAR(30) NOT NULL,
  fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_reserva) REFERENCES reservas(id)
);

CREATE TABLE historial_estados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_reserva INT NOT NULL,
  estado_anterior VARCHAR(40) NOT NULL,
  nuevo_estado VARCHAR(40) NOT NULL,
  id_admin INT NOT NULL,
  motivo VARCHAR(255),
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_reserva) REFERENCES reservas(id),
  FOREIGN KEY (id_admin) REFERENCES usuarios(id)
);

INSERT INTO usuarios (nombre,email,password_hash,rol,activo)
VALUES
('Administrador','admin@houpanama.com',SHA2('Admin123',256),'admin',1),
('Admin2','admin2@houpanama.com',SHA2('ADMINADMIN',256),'admin',1);

INSERT INTO destinos (
  codigo,
  nombre,
  provincia,
  descripcion_corta,
  descripcion_larga,
  precio_base,
  imagen,
  activo
) VALUES
('PNCOIBA','Parque Nacional Coiba','Veraguas',
 'Excursión al Parque Nacional Coiba',
 'Tour guiado al Parque Nacional Coiba con actividades de snorkel y observación de fauna marina.',
 125.00,'Imagenes/Veraguas/Coiba.jpg',1),

('CANALPTY','Canal de Panamá','Panamá',
 'Visita al Canal de Panamá',
 'Recorrido por las esclusas del Canal de Panamá con guía especializado.',
 80.00,'Imagenes/Panama/Canal.jpg',1),

('BOC01','Isla Bastimentos','Bocas del Toro',
 'Excursión a isla tropical con playas y manglares.',
 'Tour en Bocas del Toro que incluye transporte en lancha, guía local y tiempo libre en las playas de Isla Bastimentos.',
 75.00,'Imagenes/Bocasdeltoro/PlayaEstrellaBocas.jpg',1),

('COC01','Valle de Antón','Coclé',
 'Visita al Valle de Antón y sus senderos.',
 'Excursión al Valle de Antón con caminatas por el cráter, parada en miradores y tiempo libre en el pueblo.',
 45.00,'Imagenes/Cocle/ZoologicoElNispero.jpg',1),

('COL01','Fuerte San Lorenzo','Colón',
 'Tour histórico al Fuerte San Lorenzo.',
 'Recorrido por el Fuerte San Lorenzo y alrededores con explicación histórica y vistas al Caribe.',
 30.00,'Imagenes/Colon/CastilloSanLorenzo.jpg',1),

('CHI01','Volcán Barú','Chiriquí',
 'Ascenso al Volcán Barú.',
 'Caminata guiada al punto más alto de Panamá con vistas al Pacífico y Atlántico en días despejados.',
 120.00,'Imagenes/Chiriqui/ParqueVolcanBaru.jpg',1),

('DAR01','Parque Nacional Darién','Darién',
 'Exploración del Parque Nacional Darién.',
 'Expedición al Parque Nacional Darién con senderismo, observación de fauna y contacto con comunidades locales.',
 150.00,'Imagenes/Darien/ParqueNacionalDarien.jpg',1),

('HER01','Villa de Los Santos','Herrera',
 'Ruta cultural por Herrera.',
 'Visita a pueblos típicos de Herrera con enfoque en tradiciones, iglesias coloniales y gastronomía local.',
 40.00,'Imagenes/Herrera/Parita.jpg',1),

('LOS01','Isla Iguana','Los Santos',
 'Excursión a Isla Iguana.',
 'Tour de día completo a Isla Iguana con playas de arena blanca, snorkel y avistamiento de fauna marina.',
 65.00,'Imagenes/LosSantos/IslaIguana.jpg',1),

('POE01','Playa Coronado','Panamá Oeste',
 'Día de playa en Coronado.',
 'Traslado a la zona de playa de Coronado con tiempo libre para disfrutar del mar y las instalaciones.',
 45.00,'Imagenes/PanamaOeste/PlayaMalibu.jpg',1),

('EMB01','Comunidad Emberá Drua','Emberá-Wounaan',
 'Visita a comunidad Emberá.',
 'Experiencia cultural en comunidad Emberá con navegación por río, presentaciones tradicionales y almuerzo típico.',
 75.00,'Imagenes/EmberaWounaan/EmberaWounaan.jpg',1),

('GUN01','Isla Perro (San Blas)','Guna Yala',
 'Excursión a isla en San Blas.',
 'Tour a Guna Yala con visita a Isla Perro, tiempo de playa y contacto con la cultura guna.',
 120.00,'Imagenes/GunaYala/SanBlas.jpg',1),

('NGA01','Cascada El Salto del Tigre','Ngäbe-Buglé',
 'Visita a cascada en Ngäbe-Buglé.',
 'Caminata hacia cascada en zona montañosa de la comarca Ngäbe-Buglé con paradas para fotos y baño opcional.',
 55.00,'Imagenes/NgabeBugle/NgabeBugle.jpg',1);
