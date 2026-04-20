-- ═══════════════════════════════════════════════════════════════
--  BASE DE DATOS: CafeteriaISXB  v2
--  Sistema POS Jardín — Cafetería Itzel & Ximena
-- ═══════════════════════════════════════════════════════════════

DROP DATABASE IF EXISTS CafeteriaISXB;

CREATE DATABASE CafeteriaISXB
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE CafeteriaISXB;

-- ───────────────────────────────────────────────────────────────
-- 1. TABLAS
-- ───────────────────────────────────────────────────────────────

CREATE TABLE usuario (
    id_usuario   INT           AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100)  NOT NULL,
    pin          VARCHAR(255)  NOT NULL,
    pin_visible  VARCHAR(20)   NULL,
    correo       VARCHAR(120)  NULL,
    rol          ENUM('admin','mesero','cocina','barra','cajero') NOT NULL DEFAULT 'mesero',
    activo       TINYINT       NOT NULL DEFAULT 1,
    creado_por   INT           NULL,
    fecha_alta   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_correo (correo)
);

ALTER TABLE usuario
    ADD CONSTRAINT fk_usuario_creado_por
        FOREIGN KEY (creado_por)
        REFERENCES usuario(id_usuario)
        ON DELETE SET NULL;

CREATE TABLE mesa (
    id_mesa        INT      AUTO_INCREMENT PRIMARY KEY,
    numero_mesa    INT      NULL,
    estado         ENUM('libre','ocupada','cobro','entregado') NOT NULL DEFAULT 'libre',
    hora_ocupacion DATETIME NULL,
    id_usuario_fk  INT      NULL,

    UNIQUE KEY uq_numero_mesa (numero_mesa),
    CONSTRAINT fk_mesa_usuario
        FOREIGN KEY (id_usuario_fk)
        REFERENCES usuario(id_usuario)
        ON DELETE SET NULL
);

CREATE TABLE producto (
    id_producto  INT           AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100)  NOT NULL,
    precio       DECIMAL(10,2) NOT NULL,
    stock        INT           NOT NULL DEFAULT 0,
    tipo         ENUM('bebida','comida','coctel') NOT NULL,
    categoria    VARCHAR(50)   NULL,
    subcategoria VARCHAR(50)   NULL,
    ruta_defecto ENUM('barra','cocina') NOT NULL DEFAULT 'barra',
    activo       TINYINT       NOT NULL DEFAULT 1
);

CREATE TABLE modificador_producto (
    id_modificador INT           AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(80)   NOT NULL,
    precio_extra   DECIMAL(10,2) NOT NULL DEFAULT 0,
    aplica_a       ENUM('bebida','comida','armable','coctel','todos') NOT NULL DEFAULT 'todos',
    activo         TINYINT       NOT NULL DEFAULT 1
);

CREATE TABLE sesion_trabajo (
    id_sesion     INT      AUTO_INCREMENT PRIMARY KEY,
    id_usuario_fk INT      NOT NULL,
    fecha_turno   DATE     NOT NULL DEFAULT (CURDATE()),
    hora_entrada  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sesion_usuario
        FOREIGN KEY (id_usuario_fk)
        REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
);

CREATE TABLE pedido (
    id_pedido        INT         AUTO_INCREMENT PRIMARY KEY,
    id_mesa_fk       INT         NOT NULL,
    id_usuario_fk    INT         NOT NULL,
    timestamp_envio  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado_general   ENUM('pendiente','preparando','listo','entregado') NOT NULL DEFAULT 'pendiente',
    etiqueta_destino VARCHAR(20) NOT NULL DEFAULT 'AQUI',
    enviado_cobro    TINYINT     NOT NULL DEFAULT 0,

    CONSTRAINT fk_pedido_mesa
        FOREIGN KEY (id_mesa_fk)
        REFERENCES mesa(id_mesa)
        ON DELETE CASCADE,
    CONSTRAINT fk_pedido_usuario
        FOREIGN KEY (id_usuario_fk)
        REFERENCES usuario(id_usuario)
);

CREATE TABLE detalle_pedido (
    id_detalle      INT           AUTO_INCREMENT PRIMARY KEY,
    id_pedido_fk    INT           NOT NULL,
    id_producto_fk  INT           NOT NULL,
    cantidad        INT           NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    extra_precio    DECIMAL(10,2) NOT NULL DEFAULT 0,
    nota            TEXT          NULL,
    destino_item    ENUM('aqui','llevar')  NOT NULL DEFAULT 'aqui',
    ruta_area       ENUM('barra','cocina') NOT NULL,
    estado_item     ENUM('pendiente','preparando','listo') NOT NULL DEFAULT 'pendiente',
    ts_envio_item   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_detalle_pedido
        FOREIGN KEY (id_pedido_fk)
        REFERENCES pedido(id_pedido)
        ON DELETE CASCADE,
    CONSTRAINT fk_detalle_producto
        FOREIGN KEY (id_producto_fk)
        REFERENCES producto(id_producto)
);

CREATE TABLE notificacion (
    id_notif   INT         AUTO_INCREMENT PRIMARY KEY,
    id_mesa_fk INT         NOT NULL,
    origen     ENUM('barra','cocina','pos')            NOT NULL,
    destino    ENUM('barra','cocina','mesero','todos') NOT NULL,
    tipo       VARCHAR(40) NOT NULL,
    mensaje    TEXT        NOT NULL,
    ts         DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    leida      TINYINT     NOT NULL DEFAULT 0,

    CONSTRAINT fk_notif_mesa
        FOREIGN KEY (id_mesa_fk)
        REFERENCES mesa(id_mesa)
        ON DELETE CASCADE
);

CREATE TABLE pago (
    id_pago         INT           AUTO_INCREMENT PRIMARY KEY,
    id_mesa_fk      INT           NOT NULL,
    id_usuario_fk   INT           NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    propina         DECIMAL(10,2) NOT NULL DEFAULT 0,
    total           DECIMAL(10,2) NOT NULL,
    metodo_pago     ENUM('efectivo') NOT NULL DEFAULT 'efectivo',
    monto_recibido  DECIMAL(10,2) NOT NULL DEFAULT 0,
    cambio          DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_hora      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ticket_generado TINYINT       NOT NULL DEFAULT 0,

    CONSTRAINT fk_pago_mesa
        FOREIGN KEY (id_mesa_fk)
        REFERENCES mesa(id_mesa),
    CONSTRAINT fk_pago_usuario
        FOREIGN KEY (id_usuario_fk)
        REFERENCES usuario(id_usuario)
);

CREATE TABLE division_cuenta (
    id_division   INT AUTO_INCREMENT PRIMARY KEY,
    id_pago_fk    INT NOT NULL UNIQUE,
    tipo_division ENUM('iguales','por_productos') NOT NULL,
    num_personas  INT NOT NULL,

    CONSTRAINT fk_division_pago
        FOREIGN KEY (id_pago_fk)
        REFERENCES pago(id_pago)
        ON DELETE CASCADE
);

CREATE TABLE detalle_division (
    id_detalle_div INT           AUTO_INCREMENT PRIMARY KEY,
    id_division_fk INT           NOT NULL,
    numero_persona INT           NOT NULL,
    monto_persona  DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_detalle_div
        FOREIGN KEY (id_division_fk)
        REFERENCES division_cuenta(id_division)
        ON DELETE CASCADE
);

CREATE TABLE corte_diario (
    id_corte               INT           AUTO_INCREMENT PRIMARY KEY,
    id_usuario_fk          INT           NOT NULL,
    fecha                  DATE          NOT NULL UNIQUE,
    total_ventas_brutas    DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_ventas_netas     DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_propinas         DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_efectivo_sistema DECIMAL(10,2) NOT NULL DEFAULT 0,
    efectivo_real_caja     DECIMAL(10,2) NULL,
    diferencia             DECIMAL(10,2) NULL,
    num_transacciones      INT           NOT NULL DEFAULT 0,
    ticket_corte_generado  TINYINT       NOT NULL DEFAULT 0,

    CONSTRAINT fk_corte_usuario
        FOREIGN KEY (id_usuario_fk)
        REFERENCES usuario(id_usuario)
);

-- ───────────────────────────────────────────────────────────────
-- 2. ÍNDICES
-- ───────────────────────────────────────────────────────────────

CREATE INDEX idx_pedido_mesa    ON pedido(id_mesa_fk);
CREATE INDEX idx_detalle_pedido ON detalle_pedido(id_pedido_fk);
CREATE INDEX idx_producto_tipo  ON producto(tipo);
CREATE INDEX idx_sesion_fecha   ON sesion_trabajo(fecha_turno);
CREATE INDEX idx_pago_fecha     ON pago(fecha_hora);
CREATE INDEX idx_notif_leida    ON notificacion(leida);

-- ───────────────────────────────────────────────────────────────
-- 3. TRIGGERS
-- ───────────────────────────────────────────────────────────────

DELIMITER $$

CREATE TRIGGER trg_unico_admin_insert
BEFORE INSERT ON usuario
FOR EACH ROW
BEGIN
    IF NEW.rol = 'admin' THEN
        IF (SELECT COUNT(*) FROM usuario WHERE rol = 'admin') > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ya existe un admin';
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_unico_admin_update
BEFORE UPDATE ON usuario
FOR EACH ROW
BEGIN
    IF NEW.rol = 'admin' AND OLD.rol <> 'admin' THEN
        IF (SELECT COUNT(*) FROM usuario WHERE rol = 'admin') > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ya existe un admin';
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_numero_mesa
BEFORE INSERT ON mesa
FOR EACH ROW
BEGIN
    IF NEW.numero_mesa IS NULL THEN
        SELECT IFNULL(MAX(numero_mesa), 0) + 1 INTO @num FROM mesa;
        SET NEW.numero_mesa = @num;
    END IF;
END$$

CREATE TRIGGER trg_ocupar_mesa
AFTER INSERT ON pedido
FOR EACH ROW
BEGIN
    UPDATE mesa
    SET estado         = 'ocupada',
        hora_ocupacion = IFNULL(hora_ocupacion, NOW()),
        id_usuario_fk  = NEW.id_usuario_fk
    WHERE id_mesa = NEW.id_mesa_fk;
END$$

CREATE TRIGGER trg_total_pago
BEFORE INSERT ON pago
FOR EACH ROW
BEGIN
    SET NEW.total  = NEW.subtotal + NEW.propina;
    SET NEW.cambio = NEW.monto_recibido - NEW.total;
END$$

CREATE TRIGGER trg_liberar_mesa
AFTER INSERT ON pago
FOR EACH ROW
BEGIN
    UPDATE mesa
    SET estado         = 'libre',
        hora_ocupacion = NULL,
        id_usuario_fk  = NULL
    WHERE id_mesa = NEW.id_mesa_fk;
END$$

CREATE TRIGGER trg_corte_diario
AFTER INSERT ON pago
FOR EACH ROW
BEGIN
    INSERT INTO corte_diario (
        id_usuario_fk, fecha,
        total_ventas_brutas, total_ventas_netas,
        total_propinas, total_efectivo_sistema,
        num_transacciones
    )
    VALUES (
        NEW.id_usuario_fk, DATE(NEW.fecha_hora),
        NEW.total, NEW.subtotal,
        NEW.propina, NEW.total,
        1
    )
    ON DUPLICATE KEY UPDATE
        total_ventas_brutas    = total_ventas_brutas    + NEW.total,
        total_ventas_netas     = total_ventas_netas     + NEW.subtotal,
        total_propinas         = total_propinas         + NEW.propina,
        total_efectivo_sistema = total_efectivo_sistema + NEW.total,
        num_transacciones      = num_transacciones      + 1;
END$$

CREATE TRIGGER trg_diferencia_caja
BEFORE UPDATE ON corte_diario
FOR EACH ROW
BEGIN
    IF NEW.efectivo_real_caja IS NOT NULL THEN
        SET NEW.diferencia = NEW.efectivo_real_caja - NEW.total_efectivo_sistema;
    END IF;
END$$

CREATE TRIGGER trg_pedido_listo
AFTER UPDATE ON detalle_pedido
FOR EACH ROW
BEGIN
    DECLARE v_total  INT;
    DECLARE v_listos INT;
    DECLARE v_mesa   INT;

    IF NEW.estado_item = 'listo' AND OLD.estado_item <> 'listo' THEN
        SELECT COUNT(*) INTO v_total
        FROM detalle_pedido
        WHERE id_pedido_fk = NEW.id_pedido_fk;

        SELECT COUNT(*) INTO v_listos
        FROM detalle_pedido
        WHERE id_pedido_fk = NEW.id_pedido_fk
          AND estado_item = 'listo';

        IF v_total = v_listos THEN
            UPDATE pedido
            SET estado_general = 'listo'
            WHERE id_pedido = NEW.id_pedido_fk;

            SELECT id_mesa_fk INTO v_mesa
            FROM pedido
            WHERE id_pedido = NEW.id_pedido_fk;

            UPDATE mesa
            SET estado = 'entregado'
            WHERE id_mesa = v_mesa;
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_notif_pedido_listo
AFTER UPDATE ON pedido
FOR EACH ROW
BEGIN
    DECLARE v_mesa INT;

    IF NEW.estado_general = 'listo' AND OLD.estado_general <> 'listo' THEN
        SELECT numero_mesa INTO v_mesa
        FROM mesa
        WHERE id_mesa = NEW.id_mesa_fk;

        INSERT INTO notificacion (id_mesa_fk, origen, destino, tipo, mensaje)
        VALUES (
            NEW.id_mesa_fk,
            'cocina', 'mesero',
            'pedido_listo',
            CONCAT('Pedido listo — Mesa ', v_mesa)
        );
    END IF;
END$$

DELIMITER ;

-- ───────────────────────────────────────────────────────────────
-- 4. VISTAS
-- ───────────────────────────────────────────────────────────────

CREATE VIEW vista_consumo_mesa AS
SELECT
    m.id_mesa,
    m.numero_mesa,
    m.estado,
    m.hora_ocupacion,
    IFNULL(SUM((dp.precio_unitario + dp.extra_precio) * dp.cantidad), 0) AS total_consumo,
    COUNT(dp.id_detalle)                                                   AS total_items,
    TIMESTAMPDIFF(MINUTE, m.hora_ocupacion, NOW())                         AS minutos_ocupada
FROM mesa m
LEFT JOIN pedido p
       ON p.id_mesa_fk = m.id_mesa
      AND p.estado_general <> 'entregado'
LEFT JOIN detalle_pedido dp
       ON dp.id_pedido_fk = p.id_pedido
GROUP BY m.id_mesa, m.numero_mesa, m.estado, m.hora_ocupacion;

CREATE VIEW vista_ventas_hoy AS
SELECT
    IFNULL(SUM(total),    0) AS ventas_brutas,
    IFNULL(SUM(subtotal), 0) AS ventas_netas,
    IFNULL(SUM(propina),  0) AS total_propinas,
    COUNT(*)                  AS num_transacciones,
    IFNULL(SUM(total),    0) AS efectivo_en_sistema
FROM pago
WHERE DATE(fecha_hora) = CURDATE();

CREATE VIEW vista_cocina_activa AS
SELECT
    p.id_pedido,
    m.numero_mesa,
    p.timestamp_envio,
    p.estado_general,
    p.etiqueta_destino,
    dp.id_detalle,
    pr.nombre AS producto,
    dp.cantidad,
    dp.nota,
    dp.estado_item,
    dp.destino_item,
    TIMESTAMPDIFF(MINUTE, p.timestamp_envio, NOW()) AS minutos_espera
FROM pedido p
JOIN mesa           m  ON m.id_mesa       = p.id_mesa_fk
JOIN detalle_pedido dp ON dp.id_pedido_fk = p.id_pedido
JOIN producto       pr ON pr.id_producto  = dp.id_producto_fk
WHERE dp.ruta_area     = 'cocina'
  AND p.estado_general <> 'entregado'
ORDER BY p.timestamp_envio ASC;

CREATE VIEW vista_barra_activa AS
SELECT
    p.id_pedido,
    m.numero_mesa,
    p.timestamp_envio,
    p.estado_general,
    p.etiqueta_destino,
    dp.id_detalle,
    pr.nombre AS producto,
    dp.cantidad,
    dp.nota,
    dp.estado_item,
    dp.destino_item,
    TIMESTAMPDIFF(MINUTE, p.timestamp_envio, NOW()) AS minutos_espera
FROM pedido p
JOIN mesa           m  ON m.id_mesa       = p.id_mesa_fk
JOIN detalle_pedido dp ON dp.id_pedido_fk = p.id_pedido
JOIN producto       pr ON pr.id_producto  = dp.id_producto_fk
WHERE dp.ruta_area     = 'barra'
  AND p.estado_general <> 'entregado'
ORDER BY p.timestamp_envio ASC;

CREATE VIEW vista_historial_hoy AS
SELECT
    pg.id_pago,
    m.numero_mesa,
    pg.subtotal,
    pg.propina,
    pg.total,
    pg.metodo_pago,
    pg.monto_recibido,
    pg.cambio,
    pg.fecha_hora,
    pg.ticket_generado
FROM pago pg
JOIN mesa m ON m.id_mesa = pg.id_mesa_fk
WHERE DATE(pg.fecha_hora) = CURDATE()
ORDER BY pg.fecha_hora DESC;

CREATE VIEW vista_asistencia_hoy AS
SELECT
    u.id_usuario,
    u.nombre,
    u.rol,
    s.fecha_turno,
    s.hora_entrada,
    s.hora_salida
FROM sesion_trabajo s
JOIN usuario u ON u.id_usuario = s.id_usuario_fk
WHERE s.fecha_turno = CURDATE()
ORDER BY s.hora_entrada ASC;

-- ───────────────────────────────────────────────────────────────
-- 5. DATOS INICIALES
-- ───────────────────────────────────────────────────────────────

INSERT INTO usuario (nombre, pin, pin_visible, rol, creado_por)
SELECT 'Admin', SHA2('9999', 256), '9999', 'admin', NULL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE rol = 'admin');

INSERT INTO mesa (numero_mesa) VALUES (1),(2),(3),(4),(5);

INSERT INTO producto (nombre, precio, tipo, categoria, subcategoria, ruta_defecto) VALUES
('Frutas de la Pasión', 45.00, 'bebida', 'calientes', 'Tisanas', 'barra'),
('Fresa-Kiwi',          45.00, 'bebida', 'calientes', 'Tisanas', 'barra'),
('Sueño de Manzana',    45.00, 'bebida', 'calientes', 'Tisanas', 'barra'),
('Té Verde con Menta',  45.00, 'bebida', 'calientes', 'Tisanas', 'barra'),
('Guayaba',             45.00, 'bebida', 'calientes', 'Tisanas', 'barra'),
('Americano',           25.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Capuchino',           40.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Moka',                70.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Moka Blanco',         70.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Caramel Macchiato',   60.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Latte',               55.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Chocolate Caliente',  40.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Espresso',            15.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Affogato',            60.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Chai Latte',          60.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Chai Sucio',          65.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Matcha Latte',        70.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Taro Latte',          60.00, 'bebida', 'calientes', 'Cafés', 'barra'),
('Frappe Capuchino',         60.00, 'bebida', 'frias', 'Frappes Clásicos', 'barra'),
('Frappe Vainilla',          60.00, 'bebida', 'frias', 'Frappes Clásicos', 'barra'),
('Frappe Moka',              60.00, 'bebida', 'frias', 'Frappes Clásicos', 'barra'),
('Frappe Galleta Oreo',      60.00, 'bebida', 'frias', 'Frappes Clásicos', 'barra'),
('Frappe Caramelo',          60.00, 'bebida', 'frias', 'Frappes Clásicos', 'barra'),
('Frappe Chocolate Blanco',  60.00, 'bebida', 'frias', 'Frappes Clásicos', 'barra'),
('Frappe Choco-menta',       60.00, 'bebida', 'frias', 'Frappes Clásicos', 'barra'),
('Frappe Chocolate Obscuro', 60.00, 'bebida', 'frias', 'Frappes Clásicos', 'barra'),
('Frappe Chocolate Abuelita',60.00, 'bebida', 'frias', 'Frappes Clásicos', 'barra'),
('Soda Frutos Rojos',  55.00, 'bebida', 'frias', 'Sodas Italianas', 'barra'),
('Soda Fresa',         55.00, 'bebida', 'frias', 'Sodas Italianas', 'barra'),
('Soda Blueberry',     55.00, 'bebida', 'frias', 'Sodas Italianas', 'barra'),
('Soda Kiwi',          55.00, 'bebida', 'frias', 'Sodas Italianas', 'barra'),
('Soda Manzana Verde', 55.00, 'bebida', 'frias', 'Sodas Italianas', 'barra'),
('Soda Maracuyá',      55.00, 'bebida', 'frias', 'Sodas Italianas', 'barra'),
('Soda Durazno',       55.00, 'bebida', 'frias', 'Sodas Italianas', 'barra'),
('Limonada Clásica',   45.00, 'bebida', 'frias', 'Limonadas', 'barra'),
('Limonada de Coco',   50.00, 'bebida', 'frias', 'Limonadas', 'barra'),
('Limonada de Fresa',  50.00, 'bebida', 'frias', 'Limonadas', 'barra'),
('Agua Natural',       25.00, 'bebida', 'frias', 'Aguas', 'barra'),
('Agua de Horchata',   30.00, 'bebida', 'frias', 'Aguas', 'barra'),
('Agua de Jamaica',    30.00, 'bebida', 'frias', 'Aguas', 'barra'),
('Crepa Dulce',        65.00, 'comida', 'crepas', 'Crepas',  'cocina'),
('Crepa Salada',       70.00, 'comida', 'crepas', 'Crepas',  'cocina'),
('Waffle Clásico',     65.00, 'comida', 'crepas', 'Waffles', 'cocina'),
('Waffle Especial',    75.00, 'comida', 'crepas', 'Waffles', 'cocina'),
('Ensalada Verde',       70.00, 'comida', 'snacks', 'Ensaladas', 'cocina'),
('Ensalada César',       80.00, 'comida', 'snacks', 'Ensaladas', 'cocina'),
('Nachos con Guacamole', 75.00, 'comida', 'snacks', 'Snacks',    'cocina'),
('Papas Fritas',         50.00, 'comida', 'snacks', 'Snacks',    'cocina'),
('Fruta de Temporada',   55.00, 'comida', 'snacks', 'Snacks',    'cocina'),
('Chapata de Jamón',   80.00, 'comida', 'chapatas', 'Chapatas',   'cocina'),
('Chapata Vegetal',    75.00, 'comida', 'chapatas', 'Chapatas',   'cocina'),
('Croissant Simple',   45.00, 'comida', 'chapatas', 'Croissants', 'cocina'),
('Croissant Relleno',  65.00, 'comida', 'chapatas', 'Croissants', 'cocina'),
('Tostadas Francesas', 70.00, 'comida', 'chapatas', 'Croissants', 'cocina'),
('Burrito Campechano',  95.00, 'comida', 'comida', 'Burritos',     'cocina'),
('Burrito de Pollo',    90.00, 'comida', 'comida', 'Burritos',     'cocina'),
('Burrito Vegetariano', 85.00, 'comida', 'comida', 'Burritos',     'cocina'),
('Hamburguesa Clásica',100.00, 'comida', 'comida', 'Hamburguesas', 'cocina'),
('Hamburguesa BBQ',    110.00, 'comida', 'comida', 'Hamburguesas', 'cocina'),
('Hamburguesa Veggie',  95.00, 'comida', 'comida', 'Hamburguesas', 'cocina'),
('Mojito',            90.00, 'coctel', 'cocteles', 'Clásicos',   'barra'),
('Piña Colada',       95.00, 'coctel', 'cocteles', 'Clásicos',   'barra'),
('Margarita',         90.00, 'coctel', 'cocteles', 'Clásicos',   'barra'),
('Cosmopolitan',      95.00, 'coctel', 'cocteles', 'Clásicos',   'barra'),
('Tequila Sunrise',   90.00, 'coctel', 'cocteles', 'Clásicos',   'barra'),
('Gin Tonic',         95.00, 'coctel', 'cocteles', 'Clásicos',   'barra'),
('Clamato Preparado', 85.00, 'coctel', 'cocteles', 'Preparados', 'barra'),
('Michelada',         75.00, 'coctel', 'cocteles', 'Preparados', 'barra');

INSERT INTO modificador_producto (nombre, precio_extra, aplica_a) VALUES
('Leche Almendra',            12.00, 'bebida'),
('Leche Coco',                12.00, 'bebida'),
('Carga Extra',               15.00, 'bebida'),
('Jarabe Extra',              15.00, 'bebida'),
('Perlas Explosivas',         12.00, 'bebida'),
('Jellys',                    12.00, 'bebida'),
('Nutella',                    0.00, 'armable'),
('Queso Crema',                0.00, 'armable'),
('Lechera',                    0.00, 'armable'),
('Mermelada Zarzamora',        0.00, 'armable'),
('Mermelada Fresa',            0.00, 'armable'),
('Cajeta',                     0.00, 'armable'),
('Durazno',                    0.00, 'armable'),
('Fresa',                      0.00, 'armable'),
('Plátano',                    0.00, 'armable'),
('Kiwi',                       0.00, 'armable'),
('Manzana Verde',              0.00, 'armable'),
('Nuez',                      15.00, 'armable'),
('Helado',                    25.00, 'armable'),
('Almendra',                  15.00, 'armable'),
('Fresas/Duraznos con crema', 40.00, 'armable'),
('Extra Guacamole',           30.00, 'comida'),
('Extra Tocino',              15.00, 'comida'),
('Extra Carne',               25.00, 'comida'),
('Extra Ron Blanco',          20.00, 'coctel'),
('Extra Ginebra',             30.00, 'coctel');

-- ───────────────────────────────────────────────────────────────
-- 6. VERIFICACIÓN FINAL
-- ───────────────────────────────────────────────────────────────

SELECT 'CafeteriaISXB v2 lista ✓' AS estado;

SELECT CONCAT(COUNT(*), ' productos')          AS resumen FROM producto
UNION ALL
SELECT CONCAT(COUNT(*), ' modificadores')                 FROM modificador_producto
UNION ALL
SELECT CONCAT(COUNT(*), ' mesas')                         FROM mesa
UNION ALL
SELECT CONCAT(COUNT(*), ' usuarios (solo admin)')         FROM usuario;
