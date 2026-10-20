DROP DATABASE IF EXISTS expofuturo;

CREATE DATABASE expofuturo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE expofuturo;

CREATE TABLE admin (
  id_admin INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE area (
  id_area INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE universidad (
  id_universidad INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(200) NOT NULL,
  ciudad VARCHAR(100) NOT NULL,
  tipo ENUM('publica', 'privada') NOT NULL,
  sitio_web VARCHAR(255) NULL,
  descripcion TEXT NULL,
  logo_url VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE carrera (
  id_carrera INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(200) NOT NULL,
  id_area INT UNSIGNED NOT NULL,
  duracion_semestres TINYINT UNSIGNED NOT NULL,
  modalidad VARCHAR(80) NOT NULL,
  descripcion TEXT NULL,
  id_universidad INT UNSIGNED NOT NULL,
  FOREIGN KEY (id_area) REFERENCES area(id_area),
  FOREIGN KEY (id_universidad) REFERENCES universidad(id_universidad)
) ENGINE=InnoDB;

-- Administrador: admin@demo.com / Admin123!
INSERT INTO admin (nombre, correo, password_hash) VALUES
('Administrador ExpoFuturo', 'admin@demo.com', '$2y$10$eiuYxuFkvYIz7z2BhZBxq.LgVGz9kckdPQUlpQviC51ZzztLyo8Vi');

INSERT INTO area (nombre) VALUES
('Ingeniería'),
('Ciencias de la Salud'),
('Ciencias Sociales y Humanidades'),
('Artes, Diseño y Comunicación'),
('Administración y Negocios'),
('Ciencias Exactas y Naturales'),
('Derecho y Ciencias Políticas');

INSERT INTO universidad (nombre, ciudad, tipo, sitio_web, descripcion, logo_url) VALUES
('Universidad Nacional de Colombia — Sede Bogotá', 'Bogotá', 'publica', 'https://unal.edu.co',
 'La universidad pública más grande del país. Campus Ciudad Universitaria con tradición en investigación, ingeniería, artes y ciencias de la salud.', NULL),
('Universidad de los Andes', 'Bogotá', 'privada', 'https://uniandes.edu.co',
 'Universidad privada de alta exigencia académica, reconocida por ingeniería, economía, administración y proyección internacional.', NULL),
('Pontificia Universidad Javeriana', 'Bogotá', 'privada', 'https://www.javeriana.edu.co',
 'Institución jesuita con fuerte tradición en medicina, psicología, comunicación e ingeniería en el centro de Bogotá.', NULL),
('Universidad del Rosario', 'Bogotá', 'privada', 'https://www.urosario.edu.co',
 'Fundada en 1653. Referente en derecho, economía, administración y ciencias políticas en la capital.', NULL),
('Universidad de Antioquia', 'Medellín', 'publica', 'https://www.udea.edu.co',
 'Principal universidad pública de Antioquia. Campus de Ciudad Universitaria con amplia oferta en salud, ingeniería y humanidades.', NULL),
('Universidad EAFIT', 'Medellín', 'privada', 'https://www.eafit.edu.co',
 'Universidad privada de Medellín con énfasis en administración, ingeniería, economía y cultura emprendedora.', NULL),
('Universidad Pontificia Bolivariana', 'Medellín', 'privada', 'https://www.upb.edu.co',
 'Institución católica antioqueña con programas en ingeniería, diseño, comunicación y ciencias de la salud.', NULL),
('Universidad del Valle', 'Cali', 'publica', 'https://www.univalle.edu.co',
 'Universidad pública líder del Pacífico colombiano. Campus Meléndez con tradición científica y de salud pública.', NULL),
('Universidad Icesi', 'Cali', 'privada', 'https://www.icesi.edu.co',
 'Universidad privada del Valle del Cauca, destacada en diseño, ingeniería, administración y emprendimiento.', NULL),
('Universidad del Norte', 'Barranquilla', 'privada', 'https://www.uninorte.edu.co',
 'Referente del Caribe colombiano en ingeniería, medicina, derecho y negocios internacionales.', NULL),
('Universidad Industrial de Santander', 'Bucaramanga', 'publica', 'https://www.uis.edu.co',
 'Universidad pública santandereana con fortaleza en ingeniería de petróleos, sistemas y ciencias exactas.', NULL),
('Universidad Tecnológica de Pereira', 'Pereira', 'publica', 'https://www.utp.edu.co',
 'Universidad pública del Eje Cafetero, reconocida por ingeniería, tecnología y proyección regional.', NULL);

INSERT INTO carrera (nombre, id_area, duracion_semestres, modalidad, descripcion, id_universidad) VALUES
('Ingeniería de Sistemas y Computación', 1, 10, 'Presencial',
 'Formación en desarrollo de software, redes, bases de datos, ciberseguridad e inteligencia artificial para el sector productivo colombiano.', 1),
('Medicina', 2, 12, 'Presencial',
 'Programa de alto rigor clínico e investigativo, orientado al diagnóstico, tratamiento y prevención con práctica hospitalaria en Bogotá.', 1),
('Ingeniería Civil', 1, 10, 'Presencial',
 'Diseño y construcción de infraestructuras: vías, estructuras, hidráulica, geotécnica y gestión de proyectos de obra civil.', 1),
('Administración de Empresas', 5, 8, 'Presencial',
 'Prepara líderes para gestionar organizaciones, finanzas, mercadeo, operaciones y estrategia en entornos competitivos.', 2),
('Ingeniería Industrial', 1, 9, 'Presencial',
 'Optimización de procesos, logística, calidad, analítica de datos y sistemas de producción en la industria.', 2),
('Diseño', 4, 8, 'Presencial',
 'Integra tipografía, producto, identidad visual y experiencia de usuario para proyectos digitales y editoriales.', 2),
('Economía', 3, 8, 'Presencial',
 'Análisis de mercados, políticas públicas, desarrollo económico y herramientas cuantitativas aplicadas a Colombia y la región.', 2),
('Psicología', 3, 10, 'Presencial',
 'Estudio del comportamiento humano con énfasis clínico, educativo, organizacional y comunitario.', 3),
('Comunicación Social', 4, 8, 'Presencial',
 'Periodismo, producción audiovisual, comunicación estratégica y narrativas digitales para medios y organizaciones.', 3),
('Ingeniería Electrónica', 1, 10, 'Presencial',
 'Circuitos, control, telecomunicaciones, IoT y sistemas embebidos para industria e investigación.', 3),
('Derecho', 7, 10, 'Presencial',
 'Formación jurídica sólida en derecho público y privado, con énfasis en litigio, consultoría y políticas públicas.', 4),
('Ciencia Política y Gobierno', 7, 8, 'Presencial',
 'Análisis de instituciones, políticas públicas, relaciones internacionales y gobernanza en el contexto colombiano.', 4),
('Enfermería', 2, 8, 'Presencial',
 'Cuidado integral de la salud, promoción y prevención en escenarios hospitalarios, comunitarios y de atención primaria.', 5),
('Biología', 6, 10, 'Presencial',
 'Estudio de la biodiversidad tropical, ecología, biotecnología y práctica de laboratorio en el contexto andino y tropical.', 5),
('Ingeniería Química', 1, 10, 'Presencial',
 'Procesos químicos industriales, materiales, energía y sostenibilidad ambiental para la industria antioqueña.', 5),
('Negocios Internacionales', 5, 8, 'Presencial',
 'Comercio exterior, logística global, finanzas internacionales y estrategias de internacionalización empresarial.', 6),
('Ingeniería de Producción', 1, 10, 'Presencial',
 'Sistemas de manufactura, calidad, cadena de suministro y mejora continua en entornos productivos.', 6),
('Mercadeo', 5, 8, 'Presencial',
 'Investigación de mercados, branding, marketing digital y estrategia comercial orientada al consumidor.', 6),
('Diseño Industrial', 4, 9, 'Presencial',
 'Creación de productos útiles y estéticos, desde el concepto y el prototipo hasta la manufactura.', 7),
('Arquitectura', 4, 10, 'Presencial',
 'Diseño arquitectónico, urbanismo y sostenibilidad con mirada al hábitat latinoamericano.', 7),
('Medicina', 2, 12, 'Presencial',
 'Formación médica con énfasis en salud pública del Pacífico colombiano y práctica clínica hospitalaria.', 8),
('Ingeniería de Sistemas', 1, 10, 'Presencial',
 'Software, bases de datos, redes e innovación tecnológica aplicada a la industria del Valle del Cauca.', 8),
('Administración de Empresas', 5, 8, 'Híbrida',
 'Gestión estratégica, emprendimiento y liderazgo organizacional con modalidad flexible presencial-virtual.', 9),
('Diseño de Medios Interactivos', 4, 9, 'Presencial',
 'UX/UI, desarrollo creativo, animación y productos digitales para la industria creativa.', 9),
('Ingeniería Mecánica', 1, 10, 'Presencial',
 'Diseño mecánico, termofluidos, manufactura y mantenimiento industrial para el sector energético y manufacturero.', 10),
('Medicina', 2, 12, 'Presencial',
 'Programa médico del Caribe con énfasis en atención primaria, investigación clínica y salud regional.', 10),
('Ingeniería de Petróleos', 1, 10, 'Presencial',
 'Exploración, producción y gestión de hidrocarburos con enfoque técnico y ambiental.', 11),
('Ingeniería de Sistemas', 1, 10, 'Presencial',
 'Desarrollo de software, infraestructura TI y soluciones digitales para el sector productivo santandereano.', 11),
('Ingeniería de Sistemas y Computación', 1, 10, 'Presencial',
 'Computación, redes y desarrollo de soluciones tecnológicas para el Eje Cafetero y el país.', 12),
('Tecnología en Desarrollo de Software', 1, 6, 'Virtual',
 'Programa tecnológico 100 % virtual para formar desarrolladores con fundamentos de programación y bases de datos.', 12);
