# 🌿 Sistema POS — Cafetería Jardín
Sistema de Gestión de Pedidos y Caja desarrollado para la Cafetería Jardín.

---

## 👥 Equipo 17

| Integrante | Rol |
|---|---|
| Ramírez Osorio Marlen Ximena | Desarrolladora |
| Solis Ortiz Itzel Guadalupe | Desarrolladora |

**Materia:** Ingeniería en Software XB
**Institución:** Instituto Tecnológico de Zacatepec
**Docente:** Bustillos Gaytan Claudia Gabriela
**Periodo:** Enero – Junio 2026

---

## 🧩 Módulos trabajados: Toma de Pedidos en Mesa y Transmisión de Pedidos

### Módulo 2 — Toma de Pedidos en Mesa

El módulo de Toma de Pedidos permite al mesero registrar digitalmente las órdenes de los clientes desde la pantalla de mesas. El menú se organiza por categorías y subcategorías, cada producto puede llevar notas personalizadas y modificadores (extras), y el pedido se muestra en un panel de comanda antes de enviarse. Esto elimina por completo el uso de comandas en papel.

### Módulo 3 — Transmisión de Pedidos

Una vez confirmado el pedido, el sistema lo enruta automáticamente: los alimentos van a cocina y las bebidas a barra según el campo `ruta_area` de cada ítem. El mesero visualiza la separación en un modal con columnas barra/cocina antes de enviar, y el sistema garantiza que cada área reciba únicamente lo que le corresponde preparar.

---

## ✅ Requerimientos que cubre

### Módulo 2 — Toma de Pedidos en Mesa

| ID | Requerimiento | Descripción |
|---|---|---|
| RF-01 | Registrar nuevo pedido | El mesero selecciona una mesa activa e inicia una comanda nueva desde `index.php` |
| RF-02 | Mostrar menú disponible | El menú se carga desde `producto` en BD, organizado por categorías y subcategorías |
| RF-03 | Capturar productos solicitados | Cada producto se agrega con cantidad, nota personalizada y extras opcionales |
| RF-04 | Guardar pedido registrado | `pedidos.php` inserta la cabecera en `pedido` y cada ítem en `detalle_pedido` con su `ruta_area` |
| RF-05 | Mostrar pedido para confirmación | Modal de confirmación con columnas barra/cocina antes de enviar definitivamente |

### Módulo 3 — Transmisión de Pedidos

| ID | Requerimiento | Descripción |
|---|---|---|
| RF-06 | Registrar recepción del pedido | `pedidos.php` acción `guardar` inserta el pedido en BD con estado `pendiente` |
| RF-07 | Mantener integridad durante transmisión | El campo `estado_item` en `detalle_pedido` asegura que cada ítem conserva su información durante todo el proceso |
| RF-08 | Registrar entrega a cocina | Ítems con `ruta_area = 'cocina'` quedan visibles en `cocina.php` vía `vista_cocina_activa` |
| RF-09 | Registrar entrega a barra | Ítems con `ruta_area = 'barra'` quedan visibles en `barra.php` vía `vista_barra_activa` |
| RF-10 | Identificar pedidos pendientes | `pedidos.php` acción `activos` filtra por `estado_general != 'entregado'` para seguimiento |

---

## 🔄 Flujo del proceso

```
Mesero selecciona mesa
        ↓
Agrega productos al panel de comanda
(cantidad · nota · extras · destino aquí/llevar)
        ↓
Modal de confirmación
(columna BARRA | columna COCINA)
        ↓
pedidos.php → INSERT pedido + detalle_pedido
(ruta_area asignada por ruta_defecto del producto)
        ↓
        ├──► vista_cocina_activa → cocina.php
        └──► vista_barra_activa → barra.php
```

---

## 📁 Estructura del repositorio

**`backend/`** — Lógica del servidor (PHP)

- `conexion.php` — Conexión a MySQL y helper `respJson()`
- `pedidos.php` — API de pedidos: guardar, listar activos, cambiar estado de ítem
- `mesas.php` — Estado de mesas con consumo acumulado en tiempo real
- `menu_api.php` — Endpoint que devuelve el catálogo de productos y modificadores
- `comandas.php` — Transmisión de estados entre cocina, barra y mesero
- `reservas.php` — API de reservas: crear, listar y gestionar reservaciones
- `gestion_reservas.php` — Panel de administración de reservas

**`frontend/`** — Interfaces de usuario

- `index.php` — Pantalla de mesas, panel de comanda y modal de confirmación barra/cocina
- `js/app.js` — Lógica del menú, comanda, polling de estados y notificaciones
- `css/estilos.css` — Estilos globales del sistema

**`database/`** — Base de datos

- `CafeteriaISXB.sql` — Script completo: tablas, triggers, vistas e índices

---

## ➕ Funcionalidad adicional — Reservas de Mesa

El sistema incluye un módulo de reservas que opera de forma independiente al flujo de pedidos. Permite registrar reservaciones anticipadas por mesa, consultarlas desde el panel de administración y visualizarlas en la pantalla de mesas para que el mesero sepa qué mesas están comprometidas antes de asignarlas.

| Archivo | Función |
|---|---|
| `reservas.php` | API para crear, listar y gestionar reservaciones en BD |
| `gestion_reservas.php` | Panel visual para administrar las reservas activas |

---

| Tabla | Descripción |
|---|---|
| `mesa` | Estado de cada mesa: `libre`, `ocupada`, `cobro`. Se ocupa automáticamente al crear un pedido vía trigger |
| `pedido` | Cabecera de cada orden: mesa, usuario, timestamp, estado general y etiqueta aquí/llevar |
| `detalle_pedido` | Cada ítem del pedido con cantidad, precio, nota, `ruta_area` (`cocina`/`barra`) y `estado_item` |
| `producto` | Catálogo completo del menú con tipo, categoría, subcategoría y `ruta_defecto` |
| `modificador_producto` | Extras y personalizaciones disponibles por tipo de producto (bebida, comida, armable, coctel) |
| `reserva` | Reservaciones anticipadas por mesa con fecha, hora y datos del cliente |

### Vistas utilizadas

| Vista | Propósito |
|---|---|
| `vista_cocina_activa` | Comandas activas filtradas por `ruta_area = 'cocina'` con tiempo de espera en minutos |
| `vista_barra_activa` | Comandas activas filtradas por `ruta_area = 'barra'` con tiempo de espera en minutos |
| `vista_consumo_mesa` | Consumo acumulado por mesa en tiempo real para el panel del mesero |

### Triggers relevantes

| Trigger | Acción |
|---|---|
| `trg_ocupar_mesa` | Al insertar un pedido, cambia el estado de la mesa a `ocupada` automáticamente |
| `trg_pedido_listo` | Cuando todos los `estado_item` de un pedido cambian a `listo`, actualiza `estado_general` del pedido |
| `trg_notif_pedido_listo` | Al quedar listo el pedido, inserta una notificación automática para el mesero en `notificacion` |

---

## 🛠️ Tecnologías utilizadas

| Elemento | Detalle |
|---|---|
| Backend | PHP 8 |
| Base de datos | MySQL — `CafeteriaISXB` |
| Frontend | HTML5, CSS3, JavaScript (Fetch API) |
| Comunicación | Polling cada 2–3 segundos via `fetch()` para actualización en tiempo real |
| Enrutamiento | Campo `ruta_area` en `detalle_pedido` + `ruta_defecto` en `producto` |
| Servidor | XAMPP / Apache — puerto 3306 |
