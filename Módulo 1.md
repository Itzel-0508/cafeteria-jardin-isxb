# 🌿 Sistema POS — Cafetería Jardín

**Sistema de Gestión de Pedidos y Caja** desarrollado para la Cafetería Jardín.
Permite gestionar mesas, tomar pedidos digitalmente, transmitirlos a cocina y barra
en tiempo real, registrar cobros y administrar al personal mediante roles y acceso por PIN cifrado.


## 👥 Equipo 17

| Integrante | Rol |
|---|---|
| Ramírez Osorio Marlen Ximena | Desarrolladora |
| Solis Ortiz Itzel Guadalupe | Desarrolladora |

**Materia:** Ingeniería en Software XB
**Institución:** Instituto Tecnológico de Zacatepec
**Docente:** Bustillos Gaytan Claudia Gabriela
**Periodo:** Enero – Junio 2026



## 🧩 Módulo trabajado: Gestión del Personal

El módulo de Gestión del Personal controla quién puede acceder al sistema POS y con qué permisos.
Cada usuario tiene un rol asignado que determina a qué pantalla puede acceder exclusivamente.
El acceso se valida mediante PIN cifrado con SHA2-256 y se registra automáticamente en bitácora.

### Requerimientos que cubre

| ID | Requerimiento | Descripción |
|---|---|---|
| RF-24 | Registrar personal | El admin crea usuarios con nombre, PIN cifrado y rol desde el panel de administración |
| RF-25 | Mostrar personal registrado | Lista completa del personal con rol, estado activo/inactivo y fecha de alta |
| RF-26 | Actualizar información | El admin edita nombre, rol y PIN de cualquier usuario desde el modal de edición |
| RF-27 | Validar PIN de acceso | `login_check.php` verifica nombre, cuenta activa y compara PIN con SHA2(pin,256) |
| RF-28 | Autorizar acceso por rol | El sistema redirige a cada usuario a su pantalla exclusiva según su rol |



## 🔐 Roles y acceso

| Rol | Pantalla exclusiva | Puede hacer |
|---|---|---|
| **Admin** | Panel de administración | Gestionar usuarios, ver mesas, configurar correo, distribuir propinas |
| **Mesero** | Pantalla de mesas | Tomar pedidos, enviar a cocina/barra, solicitar cobro |
| **Cajero** | `cobro.php` | Ver mesas en espera, registrar pagos, hacer corte diario |
| **Cocina** | `cocina.php` | Ver y avanzar comandas del área de cocina |
| **Barra** | `barra.php` | Ver y avanzar comandas de bebidas y cocteles |



## 📁 Estructura del repositorio

**`backend/`** — Lógica del servidor (PHP)
- `conexion.php` — Conexión a MySQL y helper respJson()
- `login_check.php` — Validación de PIN SHA2-256 y redirección por rol
- `usuarios.php` — API CRUD de usuarios (listar, crear, editar, activar)
- `admin.php` — Panel de administración completo

**`frontend/`** — Interfaces de usuario
- `index.php` — Login con teclado PIN, pantalla de mesas y panel admin
- `css/estilos.css` — Estilos globales del sistema

**`database/`** — Base de datos
- `CafeteriaISXB.sql` — Script completo: tablas, triggers, vistas e índices

## 🗃️ Base de datos — Tablas del módulo

| Tabla | Descripción |
|---|---|
| `usuario` | Núcleo del módulo. Almacena nombre, PIN cifrado SHA2-256, rol, correo y estado activo/inactivo |
| `log_acceso` | Bitácora automática de cada inicio de sesión con timestamp e IP del dispositivo |
| `sesion_trabajo` | Registro de turnos: hora de entrada y salida de cada empleado por día |
| `turno_dia` | Información operativa del turno: rol desempeñado y propina distribuida por empleado |



## 🛠️ Tecnologías utilizadas

| Elemento | Detalle |
|---|---|
| Backend | PHP 8 |
| Base de datos | MySQL — `CafeteriaISXB` |
| Frontend | HTML5, CSS3, JavaScript (Fetch API) |
| Cifrado | SHA2-256 para PINes de acceso |
| Correo | SMTP con Gmail para recuperación de PIN |
| Servidor | XAMPP / Apache — puerto 3306 |



