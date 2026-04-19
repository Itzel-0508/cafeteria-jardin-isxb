<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin — Jardín POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── Variables — mismo tema que el POS ─────────────── */
        :root {
            --bg-app:       #F0F4EE;
            --bg-card:      #FFFFFF;
            --bg-sidebar:   #1C2B18;
            --accent:       #4A7A3D;
            --accent-light: #7DB56A;
            --accent-dark:  #2E5226;
            --danger:       #A0320A;
            --danger-bg:    rgba(160,50,10,.08);
            --warn:         #B07D12;
            --warn-bg:      rgba(176,125,18,.10);
            --text-main:    #1C2B18;
            --text-muted:   #6B7F65;
            --text-light:   #FFFFFF;
            --border:       #D4E0CF;
            --shadow:       0 2px 12px rgba(28,43,24,.10);
            --radius:       10px;
        }
        [data-dark="true"] {
            --bg-app:    #111A0F;
            --bg-card:   #1C2B18;
            --text-main: #E8F0E5;
            --text-muted:#8FA688;
            --border:    #2E4429;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg-app);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
        }

        /* ── Sidebar ────────────────────────────────────────── */
        .sidebar {
            width: 240px; min-height: 100vh; flex-shrink: 0;
            background: var(--bg-sidebar);
            display: flex; flex-direction: column;
            padding: 28px 0 20px;
            position: sticky; top: 0; height: 100vh;
        }
        .sidebar-logo {
            padding: 0 24px 28px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            margin-bottom: 16px;
        }
        .sidebar-logo h1 {
            color: #fff; font-size: 18px; font-weight: 700; letter-spacing: .5px;
        }
        .sidebar-logo p { color: rgba(255,255,255,.45); font-size: 11px; margin-top: 3px; }
        .sidebar-logo .admin-badge {
            display: inline-block; background: var(--accent);
            color: #fff; font-size: 10px; font-weight: 700;
            padding: 2px 8px; border-radius: 20px; margin-top: 8px; letter-spacing: .8px;
        }

        .nav-section { padding: 0 12px; margin-bottom: 4px; }
        .nav-label {
            font-size: 10px; font-weight: 700; letter-spacing: 1px;
            color: rgba(255,255,255,.3); text-transform: uppercase;
            padding: 8px 12px 4px;
        }
        .nav-btn {
            display: flex; align-items: center; gap: 12px;
            width: 100%; padding: 11px 14px; border-radius: 8px;
            border: none; background: transparent; cursor: pointer;
            color: rgba(255,255,255,.65); font-size: 13.5px; font-weight: 500;
            transition: background .15s, color .15s;
            text-align: left;
        }
        .nav-btn:hover { background: rgba(255,255,255,.08); color: #fff; }
        .nav-btn.activo { background: var(--accent); color: #fff; }
        .nav-btn i { width: 18px; text-align: center; font-size: 14px; }

        .sidebar-footer {
            margin-top: auto; padding: 16px 12px 0;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-usuario {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; border-radius: 8px;
        }
        .sidebar-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--accent); display: grid; place-items: center;
            font-weight: 700; font-size: 14px; color: #fff; flex-shrink: 0;
        }
        .sidebar-nombre { color: #fff; font-size: 13px; font-weight: 600; }
        .sidebar-rol    { color: rgba(255,255,255,.4); font-size: 11px; }
        .btn-salir {
            width: 100%; margin-top: 8px; padding: 9px;
            background: rgba(160,50,10,.25); border: none; border-radius: 8px;
            color: #ff9980; font-size: 13px; cursor: pointer; font-weight: 600;
            transition: background .15s;
        }
        .btn-salir:hover { background: rgba(160,50,10,.45); }

        /* ── Contenido principal ─────────────────────────────── */
        .main { flex: 1; padding: 32px 36px; overflow-y: auto; }
        .page { display: none; }
        .page.activa { display: block; }

        .page-title {
            font-size: 22px; font-weight: 700; margin-bottom: 6px;
        }
        .page-sub {
            font-size: 13px; color: var(--text-muted); margin-bottom: 28px;
        }

        /* ── Tarjetas resumen ────────────────────────────────── */
        .stats-grid {
            display: grid; grid-template-columns: repeat(5, 1fr);
            gap: 10px; margin-bottom: 20px;
        }
        .stat-card {
            background: var(--bg-card); border-radius: var(--radius);
            padding: 12px 16px; box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px;
        }
        .stat-num  { font-size: 26px; font-weight: 800; color: var(--accent); line-height: 1; }
        .stat-lbl  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
        .stat-icon { font-size: 18px; color: var(--accent-light); flex-shrink: 0; }

        /* ── Card ────────────────────────────────────────────── */
        .card {
            background: var(--bg-card); border-radius: var(--radius);
            box-shadow: var(--shadow); border: 1px solid var(--border);
            overflow: hidden; margin-bottom: 24px;
        }
        .card-header {
            padding: 16px 20px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
        }
        .card-header h3 { font-size: 15px; font-weight: 700; }
        .card-body { padding: 20px; }

        /* ── Tabla usuarios ──────────────────────────────────── */
        .tabla-usuarios {
            width: 100%; border-collapse: collapse;
        }
        .tabla-usuarios th {
            text-align: left; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .6px;
            color: var(--text-muted); padding: 10px 14px;
            border-bottom: 2px solid var(--border); background: var(--bg-app);
            position: sticky; top: 0; z-index: 1;
        }
        .tabla-usuarios td {
            padding: 13px 14px; border-bottom: 1px solid var(--border);
            font-size: 13.5px; vertical-align: middle;
        }
        .tabla-usuarios tr:last-child td { border-bottom: none; }
        .tabla-usuarios tr:hover td { background: rgba(74,122,61,.04); }

        /* ── Badges de rol ───────────────────────────────────── */
        .rol-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700; letter-spacing: .4px;
        }
        .rol-admin   { background: rgba(74,122,61,.15);  color: var(--accent-dark); }
        .rol-mesero  { background: rgba(74,100,200,.12); color: #2a4acc; }
        .rol-cocina  { background: rgba(176,125,18,.15); color: var(--warn); }
        .rol-barra   { background: rgba(100,60,180,.12); color: #5a30b0; }
        .rol-cajero  { background: rgba(160,50,10,.12);  color: var(--danger); }

        .badge-activo   { display:inline-block; width:8px; height:8px; border-radius:50%; background:#3CB371; }
        .badge-inactivo { display:inline-block; width:8px; height:8px; border-radius:50%; background:#ccc; }

        /* ── Botones de tabla ────────────────────────────────── */
        .btn-tbl {
            padding: 5px 10px; border-radius: 6px; border: 1px solid var(--border);
            background: var(--bg-app); cursor: pointer; font-size: 12px;
            color: var(--text-main); transition: all .15s; margin-right: 4px;
        }
        .btn-tbl:hover { background: var(--border); }
        .btn-tbl.peligro { border-color: var(--danger); color: var(--danger); }
        .btn-tbl.peligro:hover { background: var(--danger-bg); }
        .btn-tbl.ok { border-color: var(--accent); color: var(--accent); }
        .btn-tbl.ok:hover { background: rgba(74,122,61,.08); }

        /* ── Botones principales ─────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px; border-radius: 8px; border: none;
            font-size: 13px; font-weight: 600; cursor: pointer;
            transition: all .15s;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-dark); }
        .btn-secondary {
            background: transparent; color: var(--text-main);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { background: var(--border); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { opacity: .85; }
        .btn-warn { background: var(--warn); color: #fff; }
        .btn-warn:hover { opacity: .85; }

        /* ── Modal ───────────────────────────────────────────── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.45);
            display: none; place-items: center; z-index: 1000;
        }
        .modal-overlay.abierto { display: grid; }
        .modal {
            background: var(--bg-card); border-radius: 14px;
            padding: 28px; width: 420px; max-width: 95vw;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            animation: modalIn .2s ease;
        }
        @keyframes modalIn {
            from { transform: translateY(-16px) scale(.97); opacity: 0; }
            to   { transform: none; opacity: 1; }
        }
        .modal h3 { font-size: 17px; font-weight: 700; margin-bottom: 20px; }

        .form-field { margin-bottom: 16px; }
        .form-label {
            display: block; font-size: 12px; font-weight: 700;
            color: var(--text-muted); margin-bottom: 6px; letter-spacing: .4px;
            text-transform: uppercase;
        }
        .form-input {
            width: 100%; padding: 10px 13px; border-radius: 8px;
            border: 1.5px solid var(--border); background: var(--bg-app);
            color: var(--text-main); font-size: 14px; font-family: inherit;
            transition: border-color .15s; outline: none;
        }
        .form-input:focus { border-color: var(--accent); }
        select.form-input { cursor: pointer; }

        /* PIN input estilo numérico */
        .pin-field {
            display: flex; gap: 8px; align-items: center;
        }
        .pin-field .form-input { letter-spacing: 4px; font-size: 18px; font-weight: 700; }
        .pin-toggle {
            padding: 9px 12px; border-radius: 8px; border: 1.5px solid var(--border);
            background: var(--bg-app); cursor: pointer; color: var(--text-muted);
            font-size: 14px;
        }

        .modal-footer {
            display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px;
        }

        /* ── Mensaje de aviso en modal ───────────────────────── */
        .aviso-modal {
            padding: 10px 13px; border-radius: 8px; font-size: 12px;
            margin-bottom: 14px; display: none;
        }
        .aviso-info    { background: rgba(74,122,61,.1);  color: var(--accent-dark); border: 1px solid rgba(74,122,61,.2); }
        .aviso-error   { background: var(--danger-bg);    color: var(--danger);       border: 1px solid rgba(160,50,10,.2); }
        .aviso-success { background: rgba(60,179,113,.1); color: #1a6e3d;              border: 1px solid rgba(60,179,113,.25); }

        /* ── Historial ───────────────────────────────────────── */
        .hist-lista { max-height: 460px; overflow-y: auto; }
        .hist-item {
            display: flex; align-items: center; gap: 14px;
            padding: 11px 0; border-bottom: 1px solid var(--border);
        }
        .hist-item:last-child { border-bottom: none; }
        .hist-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            display: grid; place-items: center;
            font-weight: 700; font-size: 13px; color: #fff; flex-shrink: 0;
        }
        .hist-nombre { font-size: 13.5px; font-weight: 600; }
        .hist-meta   { font-size: 11.5px; color: var(--text-muted); }
        .hist-ts     { margin-left: auto; font-size: 11.5px; color: var(--text-muted); white-space: nowrap; }

        /* ── PIN compartido meseros ──────────────────────────── */
        .pin-compartido-box {
            background: var(--warn-bg); border: 1px solid rgba(176,125,18,.2);
            border-radius: var(--radius); padding: 16px 20px;
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
        }
        .pin-compartido-box p { font-size: 13px; color: var(--warn); font-weight: 500; }
        .pin-compartido-box strong { font-size: 15px; }

        /* ── Buscador ────────────────────────────────────────── */
        .buscador {
            position: relative; flex: 1;
        }
        .buscador input {
            width: 100%; padding: 9px 13px 9px 36px;
            border-radius: 8px; border: 1.5px solid var(--border);
            background: var(--bg-app); color: var(--text-main);
            font-size: 13px; outline: none; font-family: inherit;
        }
        .buscador input:focus { border-color: var(--accent); }
        .buscador i {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); font-size: 13px;
        }

        /* ── Toast ───────────────────────────────────────────── */
        #toast {
            position: fixed; bottom: 28px; right: 28px;
            background: var(--bg-sidebar); color: #fff;
            padding: 13px 20px; border-radius: 10px; font-size: 13.5px;
            box-shadow: 0 8px 24px rgba(0,0,0,.2); z-index: 9999;
            display: none; align-items: center; gap: 10px;
            animation: toastIn .2s ease;
        }
        @keyframes toastIn { from { transform: translateY(10px); opacity:0; } to { transform:none; opacity:1; } }
        #toast.visible { display: flex; }
        #toast.tipo-ok  { border-left: 4px solid var(--accent-light); }
        #toast.tipo-err { border-left: 4px solid var(--danger); }

        /* ── Filtros de rol ──────────────────────────────────── */
        .filtros-rol {
            display: flex; gap: 6px; flex-wrap: wrap;
        }
        .filtro-btn {
            padding: 5px 12px; border-radius: 20px; border: 1.5px solid var(--border);
            background: transparent; cursor: pointer; font-size: 12px; font-weight: 600;
            color: var(--text-muted); transition: all .15s;
        }
        .filtro-btn:hover  { border-color: var(--accent); color: var(--accent); }
        .filtro-btn.activo { background: var(--accent); border-color: var(--accent); color: #fff; }

        /* ── Empty state ─────────────────────────────────────── */
        .empty-state {
            text-align: center; padding: 48px 0; color: var(--text-muted);
        }
        .empty-state i { font-size: 36px; margin-bottom: 12px; }
        .empty-state p { font-size: 14px; }

        /* ── Toggle oscuro ───────────────────────────────────── */
        .btn-tema {
            background: rgba(255,255,255,.08); border: none; border-radius: 8px;
            padding: 8px 12px; color: rgba(255,255,255,.7); cursor: pointer;
            font-size: 14px; transition: background .15s;
        }
        .btn-tema:hover { background: rgba(255,255,255,.15); }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar { width: 64px; }
            .sidebar-logo h1, .sidebar-logo p, .sidebar-logo .admin-badge,
            .nav-label, .nav-btn span, .sidebar-nombre, .sidebar-rol,
            .btn-salir { display: none; }
            .nav-btn { justify-content: center; padding: 12px; }
            .nav-btn i { width: auto; }
            .main { padding: 20px 16px; }
        }
    
        /* ── Botón Volver a Panel Admin ───────────────────────── */
        .btn-volver-panel {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 16px; border-radius: 8px; border: none;
            background: linear-gradient(135deg, #2E5226, #4A7A3D);
            color: #fff; font-size: 13px; font-weight: 700;
            cursor: pointer; letter-spacing: .3px;
            transition: all .18s;
            box-shadow: 0 2px 8px rgba(46,82,38,.3);
        }
        .btn-volver-panel:hover {
            background: linear-gradient(135deg, #1C2B18, #2E5226);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(46,82,38,.4);
        }

        </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- SIDEBAR                                                      -->
<!-- ═══════════════════════════════════════════════════════════ -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <h1>🌿 Jardín POS</h1>
        <p>Panel de Administración</p>
        <span class="admin-badge">ADMIN</span>
    </div>

    <div class="nav-section">
        <div class="nav-label">Principal</div>
        <button class="nav-btn activo" onclick="irPagina('usuarios', this)">
            <i class="fas fa-users"></i>
            <span>Usuarios</span>
        </button>
        <button class="nav-btn" onclick="irPagina('historial', this)">
            <i class="fas fa-clock-rotate-left"></i>
            <span>Historial Accesos</span>
        </button>
        <button class="nav-btn" onclick="irPagina('turnos', this)">
            <i class="fas fa-calendar-day"></i>
            <span>Turnos del Día</span>
        </button>
    </div>

    <div class="nav-section" style="margin-top:8px;">
        <div class="nav-label">Sistema</div>
        <button class="nav-btn" onclick="irPagina('correo', this)">
            <i class="fas fa-envelope-circle-check"></i>
            <span>Config. Correo</span>
        </button>
        <button class="nav-btn" onclick="irAlPOSComoAdmin()">
            <i class="fas fa-cash-register"></i>
            <span>Ir al POS</span>
        </button>
    </div>

    <div class="sidebar-footer">
        <button class="btn-tema" onclick="toggleTema()" title="Cambiar tema"><i class="fas fa-moon"></i></button>
        <div class="sidebar-usuario" style="margin-top:8px;">
            <div class="sidebar-avatar" id="sb-avatar">A</div>
            <div>
                <div class="sidebar-nombre" id="sb-nombre">Admin</div>
                <div class="sidebar-rol">Administrador</div>
            </div>
        </div>
        <button class="btn-salir" onclick="cerrarSesion()">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </button>
    </div>
</aside>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- CONTENIDO PRINCIPAL                                          -->
<!-- ═══════════════════════════════════════════════════════════ -->
<main class="main">

    <!-- ── PÁGINA: USUARIOS ─────────────────────────────────── -->
    <div id="pg-usuarios" class="page activa">
        <div class="page-title"><i class="fas fa-users" style="color:var(--accent);margin-right:10px;"></i>Gestión de Usuarios</div>
        <p class="page-sub">Crea, edita y controla el acceso de cada integrante del equipo.</p>

        <!-- Stats -->
        <div class="stats-grid" id="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-num" id="stat-total">—</div>
                <div class="stat-lbl">Total usuarios</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-circle-check" style="color:#3CB371;"></i></div>
                <div class="stat-num" id="stat-activos">—</div>
                <div class="stat-lbl">Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-person-walking-arrow-right" style="color:var(--warn);"></i></div>
                <div class="stat-num" id="stat-meseros">—</div>
                <div class="stat-lbl">Meseros</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-fire-burner" style="color:var(--danger);"></i></div>
                <div class="stat-num" id="stat-cocina">—</div>
                <div class="stat-lbl">Cocina / Barra</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-cash-register" style="color:var(--danger);"></i></div>
                <div class="stat-num" id="stat-cajero">—</div>
                <div class="stat-lbl">Cajeros</div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card">
            <div class="card-header">
                <h3>Todos los usuarios</h3>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <div class="buscador">
                        <i class="fas fa-search"></i>
                        <input type="text" id="buscar-usuario" placeholder="Buscar nombre..."
                               oninput="filtrarTabla()">
                    </div>
                    <div class="filtros-rol" id="filtros-rol">
                        <button class="filtro-btn activo" data-rol="" onclick="selFiltro(this,'')">Todos</button>
                        <button class="filtro-btn" data-rol="admin"   onclick="selFiltro(this,'admin')">Admin</button>
                        <button class="filtro-btn" data-rol="mesero"  onclick="selFiltro(this,'mesero')">Meseros</button>
                        <button class="filtro-btn" data-rol="cocina"  onclick="selFiltro(this,'cocina')">Cocina</button>
                        <button class="filtro-btn" data-rol="barra"   onclick="selFiltro(this,'barra')">Barra</button>
                        <button class="filtro-btn" data-rol="cajero"  onclick="selFiltro(this,'cajero')">Cajero</button>
                    </div>
                    <button class="btn btn-primary" onclick="abrirModalCrear()">
                        <i class="fas fa-user-plus"></i> Nuevo usuario
                    </button>
                </div>
            </div>
            <div style="overflow-x:auto; overflow-y:auto; max-height:420px;">
                <table class="tabla-usuarios" id="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>NOMBRE</th>
                            <th>ROL</th>
                            <th>ESTADO</th>
                            <th>DESDE</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-usuarios">
                        <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted);">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── PÁGINA: HISTORIAL ─────────────────────────────────── -->
    <div id="pg-historial" class="page">
        <div class="page-title"><i class="fas fa-clock-rotate-left" style="color:var(--accent);margin-right:10px;"></i>Historial de Accesos</div>
        <p class="page-sub">Últimos 100 accesos registrados al sistema.</p>

        <div class="card">
            <div class="card-header">
                <h3>Accesos recientes</h3>
                <button class="btn btn-secondary" onclick="cargarHistorial()">
                    <i class="fas fa-rotate-right"></i> Actualizar
                </button>
            </div>
            <div class="card-body">
                <div class="hist-lista" id="hist-lista">
                    <div style="text-align:center;padding:32px;color:var(--text-muted);">
                        <i class="fas fa-spinner fa-spin"></i> Cargando historial...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── PÁGINA: PIN MESEROS ───────────────────────────────── -->
    <div id="pg-meseros_pin" class="page">
        <div class="page-title"><i class="fas fa-key" style="color:var(--accent);margin-right:10px;"></i>PIN Compartido de Meseros</div>
        <p class="page-sub">Todos los meseros usan el mismo PIN para acceder al sistema.</p>

        <div class="card">
            <div class="card-header"><h3>PIN actual de meseros</h3></div>
            <div class="card-body">
                <div class="pin-compartido-box" style="margin-bottom:24px;">
                    <div>
                        <p>Los meseros comparten un único PIN de acceso.</p>
                        <p style="font-size:12px;margin-top:4px;color:var(--text-muted);">
                            Al cambiar el PIN aquí, se actualiza para <strong>todos</strong> los meseros al mismo tiempo.
                        </p>
                    </div>
                    <i class="fas fa-users-between-lines" style="font-size:28px;color:var(--warn);flex-shrink:0;"></i>
                </div>

                <div class="form-field" style="max-width:320px;">
                    <label class="form-label">Nuevo PIN (mínimo 4 dígitos)</label>
                    <div class="pin-field">
                        <input type="password" id="pin-meseros-input" class="form-input"
                               maxlength="8" inputmode="numeric" pattern="[0-9]*"
                               placeholder="••••">
                        <button class="pin-toggle" onclick="togglePin('pin-meseros-input',this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div id="aviso-pin-meseros" class="aviso-modal aviso-info" style="max-width:320px;display:none;"></div>

                <button class="btn btn-warn" onclick="cambiarPinMeseros()" style="margin-top:16px;">
                    <i class="fas fa-key"></i> Cambiar PIN a todos los meseros
                </button>
            </div>
        </div>

        <!-- Lista de meseros activos -->
        <div class="card">
            <div class="card-header"><h3>Meseros registrados</h3></div>
            <div style="overflow-x:auto;">
                <table class="tabla-usuarios">
                    <thead>
                        <tr><th>NOMBRE</th><th>ESTADO</th><th>ACCIONES</th></tr>
                    </thead>
                    <tbody id="tbody-meseros"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════
         PÁGINA: TURNOS DEL DÍA
    ════════════════════════════════════════════════════════ -->
    <div id="pg-turnos" class="page">
        <div class="page-title">
            <i class="fas fa-calendar-day" style="color:var(--accent);margin-right:10px;"></i>
            Turnos del Día
        </div>
        <p class="page-sub">Registra quién trabajó cada día y distribuye las propinas del turno.</p>

        <!-- Selector de fecha -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 20px;">
                <label style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Fecha del turno:</label>
                <input type="date" id="turno-fecha-input" class="form-input" style="max-width:200px;"
                       oninput="cargarTurnoFecha(this.value)">
                <button class="btn btn-secondary" style="padding:8px 14px;font-size:12px;" onclick="turnoFechaHoy()">
                    <i class="fas fa-calendar-check"></i> Hoy
                </button>
                <span id="turno-fecha-label" style="font-size:13px;font-weight:700;color:var(--accent);margin-left:4px;"></span>
            </div>
        </div>

        <!-- Grid: Quién trabajó + Agregar -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">

            <!-- Turno del día (quiénes están) -->
            <div class="card">
                <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
                    <h3><i class="fas fa-user-check" style="color:var(--accent);margin-right:8px;"></i>Personal en turno</h3>
                    <span id="turno-count-badge" style="background:var(--accent);color:#fff;font-size:11px;font-weight:700;padding:2px 9px;border-radius:10px;">0</span>
                </div>
                <div class="card-body" style="padding:0;">
                    <div id="turno-lista-actual" style="min-height:80px;">
                        <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">
                            <i class="fas fa-user-xmark" style="font-size:28px;display:block;margin-bottom:8px;opacity:.3;"></i>
                            Sin personal registrado para este día
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agregar al turno -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-plus" style="color:var(--accent);margin-right:8px;"></i>Agregar al turno</h3>
                </div>
                <div class="card-body">
                    <div class="form-field">
                        <label class="form-label">Empleado</label>
                        <select id="turno-sel-usuario" class="form-input">
                            <option value="">— Selecciona empleado —</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Rol para este turno</label>
                        <select id="turno-sel-rol" class="form-input">
                            <option value="mesero">Mesero</option>
                            <option value="cajero">Cajero</option>
                            <option value="cocina">Cocina</option>
                            <option value="barra">Barra</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" onclick="agregarAlTurno()" style="width:100%;">
                        <i class="fas fa-plus"></i> Agregar al turno
                    </button>
                </div>
            </div>
        </div>

        <!-- Distribución de propinas -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-hand-holding-heart" style="color:var(--warn,#B07D12);margin-right:8px;"></i>Distribución de Propinas</h3>
            </div>
            <div class="card-body">
                <!-- Total propinas del día -->
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
                    <div style="background:rgba(176,125,18,.08);border:1px solid rgba(176,125,18,.25);border-radius:10px;padding:12px 18px;flex:1;min-width:200px;">
                        <div style="font-size:10px;font-weight:700;color:var(--warn,#B07D12);letter-spacing:.6px;text-transform:uppercase;margin-bottom:4px;">Propinas registradas hoy</div>
                        <div style="font-size:24px;font-weight:800;color:var(--warn,#B07D12);font-family:'Courier New',monospace;" id="turno-total-propinas">$0.00</div>
                    </div>
                    <button class="btn btn-secondary" onclick="cargarPropinasDia()" style="padding:10px 16px;">
                        <i class="fas fa-rotate-right"></i> Actualizar
                    </button>
                </div>

                <!-- Modo de distribución -->
                <div style="margin-bottom:16px;">
                    <label style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:8px;">¿Cómo repartir?</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;" id="modo-reparto-btns">
                        <button class="btn-modo-rep activo" data-modo="todos" onclick="selModoPropina('todos',this)">
                            <i class="fas fa-users"></i> Todo el personal
                        </button>
                        <button class="btn-modo-rep" data-modo="meseros" onclick="selModoPropina('meseros',this)">
                            <i class="fas fa-person-walking"></i> Solo meseros
                        </button>
                        <button class="btn-modo-rep" data-modo="manual" onclick="selModoPropina('manual',this)">
                            <i class="fas fa-hand-pointer"></i> Selección manual
                        </button>
                    </div>
                </div>

                <!-- Selección manual -->
                <div id="turno-seleccion-manual" style="display:none;margin-bottom:16px;">
                    <label style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:8px;">
                        Selecciona quiénes participan en el reparto:
                    </label>
                    <div id="turno-checkboxes-manual" style="display:flex;flex-direction:column;gap:6px;"></div>
                </div>

                <!-- Vista previa del reparto -->
                <div id="turno-preview-reparto" style="display:none;margin-bottom:16px;background:rgba(74,122,61,.06);border:1px solid rgba(74,122,61,.2);border-radius:10px;padding:14px 16px;">
                    <div style="font-size:11px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Vista previa del reparto:</div>
                    <div id="turno-preview-lista"></div>
                </div>

                <div id="turno-aviso-propina" class="aviso-modal" style="display:none;margin-bottom:12px;"></div>

                <button class="btn btn-primary" onclick="distribuirPropinas()" id="btn-distribuir-propinas" style="min-width:200px;" disabled>
                    <i class="fas fa-coins"></i> Confirmar distribución
                </button>
            </div>
        </div>

        <!-- Historial de propinas -->
        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
                <h3><i class="fas fa-clock-rotate-left" style="color:var(--accent);margin-right:8px;"></i>Historial de Propinas</h3>
                <button class="btn btn-secondary" style="padding:6px 12px;font-size:11px;" onclick="cargarHistorialPropinas()">
                    <i class="fas fa-eye"></i> Ver historial
                </button>
            </div>
            <div id="turno-historial-propinas" style="padding:12px 20px;color:var(--text-muted);font-size:13px;">
                Haz clic en "Ver historial" para cargar.
            </div>
        </div>
    </div>

</main>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- MODAL: CREAR / EDITAR USUARIO                               -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-usuario" onclick="cerrarModalSiOverlay(event)">
    <div class="modal">
        <h3 id="modal-titulo">Nuevo Usuario</h3>
        <input type="hidden" id="modal-id">

        <div id="aviso-modal" class="aviso-modal"></div>

        <div class="form-field">
            <label class="form-label">Nombre completo</label>
            <input type="text" id="modal-nombre" class="form-input"
                   placeholder="Ej: Karen, Itzel..." maxlength="50">
        </div>

        <div class="form-field">
            <label class="form-label">Rol</label>
            <select id="modal-rol" class="form-input" onchange="onRolChange()">
                <option value="mesero">Mesero</option>
                <option value="cocina">Cocina</option>
                <option value="barra">Barra</option>
                <option value="cajero">Cajero</option>
            </select>
        </div>

        <div class="form-field">
            <label class="form-label">Correo electrónico (para recuperación)</label>
            <input type="email" id="modal-correo" class="form-input"
                   placeholder="ejemplo@correo.com" maxlength="120">
            <p style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                <i class="fas fa-envelope" style="color:var(--accent);margin-right:4px;"></i>
                Se usa para recuperar el PIN si se olvida la contraseña.
            </p>
        </div>

        <!-- PIN actual visible (solo en edición) -->
        <div class="form-field" id="campo-pin-actual" style="display:none;">
            <label class="form-label">PIN actual</label>
            <div class="pin-field">
                <input type="password" id="modal-pin-actual" class="form-input"
                       readonly style="background:var(--bg-app);color:var(--text-muted);cursor:default;"
                       placeholder="••••">
                <button class="pin-toggle" onclick="togglePin('modal-pin-actual',this)" title="Ver PIN actual">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <p style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                <i class="fas fa-lock" style="color:var(--accent);margin-right:4px;"></i>
                PIN actual guardado en el sistema.
            </p>
        </div>

        <div class="form-field" id="campo-pin">
            <label class="form-label" id="pin-label">Nuevo PIN</label>
            <div class="pin-field">
                <input type="password" id="modal-pin" class="form-input"
                       maxlength="8" inputmode="numeric" pattern="[0-9]*"
                       placeholder="Ingresa nuevo PIN (vacío = sin cambio)">
                <button class="pin-toggle" onclick="togglePin('modal-pin',this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <p id="pin-nota" style="font-size:11px;color:var(--text-muted);margin-top:5px;"></p>
        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
            <button class="btn btn-primary" id="btn-guardar-usuario" onclick="guardarUsuario()">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast"><i class="fas fa-check-circle"></i><span id="toast-msg"></span></div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- JS                                                           -->
<!-- ═══════════════════════════════════════════════════════════ -->
<script>
const API = 'usuarios.php';
let todosUsuarios = [];
let filtroRolActual = '';

// ── Verificar que sea admin ────────────────────────────────────
(function() {
    const ses = JSON.parse(sessionStorage.getItem('pos_usuario') || '{}');
    if (!ses || ses.rol !== 'admin') {
        window.location.replace('index.php');
        return;
    }
    document.getElementById('sb-nombre').textContent = ses.nombre || 'Admin';
    document.getElementById('sb-avatar').textContent = (ses.nombre || 'A')[0].toUpperCase();
})();

// ── Tema oscuro ────────────────────────────────────────────────
function toggleTema() {
    const dark = document.body.getAttribute('data-dark') !== 'true';
    document.body.setAttribute('data-dark', dark);
    localStorage.setItem('pos_tema', dark ? 'oscuro' : 'claro');
}
if (localStorage.getItem('pos_tema') === 'oscuro')
    document.body.setAttribute('data-dark', 'true');

// ── Navegación ─────────────────────────────────────────────────
function irPagina(id, btn) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('activa'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('activo'));
    document.getElementById('pg-' + id).classList.add('activa');
    if (btn) btn.classList.add('activo');
    if (id === 'historial') cargarHistorial();
    if (id === 'meseros_pin') cargarMeseros();
}

// ── Cerrar sesión ──────────────────────────────────────────────
function cerrarSesion() {
    sessionStorage.removeItem('pos_usuario');
    window.location.href = 'index.php';
}

// ── Ir al POS: vuelve al panel de bienvenida admin en index.php ─
function irAlPOSComoAdmin() {
    // Setea flag para que index.php muestre el panel admin (no el login)
    try { localStorage.setItem('admin_volver_panel', '1'); } catch(e) {}
    window.location.href = 'index.php';
}

// ── Volver al panel principal (desde Gestionar Usuarios, etc.) ─
function volverAPanelAdmin() {
    window.location.href = 'index.php';
}

// ── Toast ──────────────────────────────────────────────────────
function toast(msg, tipo = 'ok') {
    const el = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = msg;
    el.className = 'visible tipo-' + tipo;
    el.querySelector('i').className = tipo === 'ok' ? 'fas fa-check-circle' : 'fas fa-circle-exclamation';
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('visible'), 3200);
}

// ── Rol → color ────────────────────────────────────────────────
const ROL_COLORS = {
    admin:  '#2E5226', mesero: '#2a4acc',
    cocina: '#B07D12', barra:  '#5a30b0', cajero: '#A0320A'
};
function rolBadge(rol) {
    return `<span class="rol-badge rol-${rol}">${rol.toUpperCase()}</span>`;
}

// ── Formatear fecha ────────────────────────────────────────────
function fmtFecha(dt) {
    if (!dt) return '—';
    const d = new Date(dt.replace(' ', 'T'));
    return d.toLocaleDateString('es-MX', { day:'2-digit', month:'short', year:'numeric' });
}
function fmtHora(dt) {
    if (!dt) return '—';
    const d = new Date(dt.replace(' ', 'T'));
    return d.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });
}

// ── CARGAR USUARIOS ────────────────────────────────────────────
async function cargarUsuarios() {
    try {
        const res  = await fetch(API + '?accion=listar');
        const text = await res.text(); // read as text first to debug
        let data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            console.error('usuarios.php no devolvió JSON válido:', text.substring(0, 300));
            document.getElementById('tbody-usuarios').innerHTML =
                '<tr><td colspan="5" style="text-align:center;color:red;padding:16px;">' +
                '⚠️ Error de servidor. Revisa usuarios.php en XAMPP.</td></tr>';
            return;
        }
        if (!data.ok) {
            console.error('Error en usuarios.php:', data.error);
            document.getElementById('tbody-usuarios').innerHTML =
                `<tr><td colspan="5" style="text-align:center;color:red;padding:16px;">
                Error: ${data.error || 'Respuesta inválida'}</td></tr>`;
            return;
        }
        todosUsuarios = data.usuarios;
        actualizarStats();
        renderTabla();
    } catch(e) {
        console.error('cargarUsuarios fetch error:', e);
        document.getElementById('tbody-usuarios').innerHTML =
            '<tr><td colspan="5" style="text-align:center;color:red;padding:16px;">' +
            '⚠️ Sin conexión con el servidor.</td></tr>';
    }
}

function actualizarStats() {
    document.getElementById('stat-total').textContent   = todosUsuarios.length;
    document.getElementById('stat-activos').textContent = todosUsuarios.filter(u => u.activo == 1).length;
    document.getElementById('stat-meseros').textContent = todosUsuarios.filter(u => u.rol === 'mesero').length;
    const cb = todosUsuarios.filter(u => u.rol === 'cocina' || u.rol === 'barra').length;
    document.getElementById('stat-cocina').textContent  = cb;
    const caj = todosUsuarios.filter(u => u.rol === 'cajero').length;
    document.getElementById('stat-cajero').textContent  = caj;
}

function filtrarTabla() {
    renderTabla();
}

function selFiltro(btn, rol) {
    document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('activo'));
    btn.classList.add('activo');
    filtroRolActual = rol;
    renderTabla();
}

function renderTabla() {
    const busq = (document.getElementById('buscar-usuario').value || '').toLowerCase();
    let lista = todosUsuarios;

    if (filtroRolActual) lista = lista.filter(u => u.rol === filtroRolActual);
    if (busq) lista = lista.filter(u => u.nombre.toLowerCase().includes(busq));

    const tbody = document.getElementById('tbody-usuarios');
    if (!lista.length) {
        tbody.innerHTML = `<tr><td colspan="5">
            <div class="empty-state"><i class="fas fa-user-slash"></i><p>No hay usuarios con ese filtro</p></div>
        </td></tr>`;
        return;
    }

    tbody.innerHTML = lista.map(u => {
        const activo = u.activo == 1;
        const esAdmin = u.rol === 'admin';
        return `
        <tr data-id="${u.id_usuario}">
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:50%;
                         background:${ROL_COLORS[u.rol]||'#555'};
                         display:grid;place-items:center;
                         font-weight:700;font-size:13px;color:#fff;flex-shrink:0;">
                        ${u.nombre[0].toUpperCase()}
                    </div>
                    <div>
                        <span style="font-weight:600;">${escHtml(u.nombre)}</span>
                        ${u.correo ? `<div style="font-size:10px;color:var(--text-muted);margin-top:1px;"><i class="fas fa-envelope" style="margin-right:3px;"></i>${escHtml(u.correo)}</div>` : ''}
                    </div>
                </div>
            </td>
            <td>${rolBadge(u.rol)}</td>
            <td>
                <span style="display:inline-flex;align-items:center;gap:6px;">
                    <span class="${activo ? 'badge-activo' : 'badge-inactivo'}"></span>
                    ${activo ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td style="color:var(--text-muted)">${fmtFecha(u.fecha_alta)}</td>
            <td>
                <button class="btn-tbl ok" onclick="abrirModalEditar(${u.id_usuario})">
                    <i class="fas fa-pen"></i> Editar
                </button>
                <button class="btn-tbl ${activo ? 'peligro' : ''}"
                        onclick="toggleActivo(${u.id_usuario})"
                        ${esAdmin && activo ? 'title="Último admin activo"' : ''}>
                    <i class="fas fa-${activo ? 'ban' : 'circle-check'}"></i>
                    ${activo ? 'Desactivar' : 'Activar'}
                </button>
                ${!esAdmin ? `<button class="btn-tbl btn-eliminar" onclick="eliminarUsuario(${u.id_usuario},'${escHtml(u.nombre)}')">
                    <i class="fas fa-trash-can"></i> Borrar
                </button>` : ''}
            </td>
        </tr>`;
    }).join('');
}


// ── ELIMINAR USUARIO ──────────────────────────────────────────
async function eliminarUsuario(id, nombre) {
    if (!confirm(`¿Borrar permanentemente a "${nombre}"?\n\nEsta acción no se puede deshacer.\nEl usuario ya no podrá iniciar sesión.`)) return;

    try {
        const res  = await fetch(API + '?accion=eliminar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_usuario: id })
        });
        const data = await res.json();
        if (data.ok) {
            toast(`"${nombre}" eliminado correctamente`);
            cargarUsuarios();
        } else {
            toast(data.error || 'Error al eliminar', 'err');
        }
    } catch(e) {
        toast('Sin conexión al servidor', 'err');
    }
}

// ── TOGGLE ACTIVO ──────────────────────────────────────────────
async function toggleActivo(id) {
    const u = todosUsuarios.find(x => x.id_usuario == id);
    const accion = u.activo == 1 ? 'desactivar' : 'activar';
    if (!confirm(`¿${accion.charAt(0).toUpperCase()+accion.slice(1)} a ${u.nombre}?`)) return;

    const res = await fetch(API + '?accion=toggle_activo', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_usuario: id })
    });
    const data = await res.json();
    if (data.ok) {
        toast(`${u.nombre} ${data.activo ? 'activado' : 'desactivado'} correctamente`);
        cargarUsuarios();
    } else {
        toast(data.error || 'Error', 'err');
    }
}

// ── MODAL CREAR ────────────────────────────────────────────────
function abrirModalCrear() {
    document.getElementById('modal-titulo').textContent = 'Nuevo Usuario';
    document.getElementById('modal-id').value   = '';
    document.getElementById('modal-nombre').value = '';
    document.getElementById('modal-pin').value  = '';
    document.getElementById('modal-rol').value  = 'mesero';
    document.getElementById('btn-guardar-usuario').innerHTML = '<i class="fas fa-user-plus"></i> Crear';
    document.getElementById('modal-correo').value = '';
    // Ocultar campo PIN actual (solo para edición)
    document.getElementById('campo-pin-actual').style.display = 'none';
    document.getElementById('pin-label').textContent = 'PIN de acceso';
    document.getElementById('pin-nota').textContent  = '';
    ocultarAviso();
    onRolChange();
    document.getElementById('modal-usuario').classList.add('abierto');
    setTimeout(() => document.getElementById('modal-nombre').focus(), 100);
}

// ── MODAL EDITAR ───────────────────────────────────────────────
function abrirModalEditar(id) {
    const u = todosUsuarios.find(x => x.id_usuario == id);
    if (!u) return;
    const ses = JSON.parse(sessionStorage.getItem('pos_usuario') || '{}');
    const esYoMismo = ses.id && parseInt(ses.id) === parseInt(id);

    document.getElementById('modal-titulo').textContent =
        esYoMismo ? '✏️ Editar mi perfil' : `Editar: ${u.nombre}`;
    document.getElementById('modal-id').value      = u.id_usuario;
    document.getElementById('modal-nombre').value  = u.nombre;
    document.getElementById('modal-pin').value     = '';
    document.getElementById('modal-rol').value     = u.rol;
    document.getElementById('modal-correo').value  = u.correo || '';
    document.getElementById('btn-guardar-usuario').innerHTML = '<i class="fas fa-save"></i> Guardar cambios';

    // Mostrar PIN actual si está disponible
    const campoPinActual = document.getElementById('campo-pin-actual');
    const inputPinActual = document.getElementById('modal-pin-actual');
    if (u.pin_visible && u.pin_visible.length > 0) {
        inputPinActual.value = u.pin_visible;
        inputPinActual.type  = 'password'; // empieza oculto
        campoPinActual.style.display = 'block';
        // Reset ojo
        const ojoActual = campoPinActual.querySelector('.pin-toggle i');
        if (ojoActual) ojoActual.className = 'fas fa-eye';
    } else {
        campoPinActual.style.display = 'none';
        inputPinActual.value = '';
    }

    document.getElementById('pin-label').textContent =
        esYoMismo ? 'Cambiar mi PIN' : 'Nuevo PIN (vacío = sin cambio)';
    document.getElementById('pin-nota').textContent =
        esYoMismo
            ? '🔑 Déjalo vacío para conservar tu PIN actual.'
            : 'Déjalo vacío para no cambiar el PIN actual.';

    ocultarAviso();
    document.getElementById('modal-usuario').classList.add('abierto');
    setTimeout(() => document.getElementById('modal-nombre').focus(), 100);
}

function cerrarModal() {
    document.getElementById('modal-usuario').classList.remove('abierto');
}
function cerrarModalSiOverlay(e) {
    if (e.target.classList.contains('modal-overlay')) cerrarModal();
}

// ── Reacción a cambio de rol en modal ─────────────────────────
function onRolChange(esEdicion = false) {
    const rol = document.getElementById('modal-rol').value;
    const nota = document.getElementById('pin-nota');
    const label = document.getElementById('pin-label');
    const id = document.getElementById('modal-id').value;
    const esEdit = !!id || esEdicion;

    if (rol === 'mesero') {
        nota.textContent = 'Los meseros comparten un PIN. Si dejas vacío, se usará el PIN actual del grupo.';
        label.textContent = 'PIN compartido de meseros (opcional en edición)';
    } else {
        nota.textContent = esEdit ? 'Deja vacío para no cambiar el PIN actual.' : '';
        label.textContent = esEdit ? 'Nuevo PIN (dejar vacío para no cambiar)' : 'PIN de acceso';
    }
}

// ── GUARDAR USUARIO ────────────────────────────────────────────
async function guardarUsuario() {
    const id     = document.getElementById('modal-id').value;
    const nombre = document.getElementById('modal-nombre').value.trim();
    const pin    = document.getElementById('modal-pin').value.trim();
    const rol    = document.getElementById('modal-rol').value;
    const correo = document.getElementById('modal-correo').value.trim();

    if (!nombre) { mostrarAviso('El nombre es obligatorio', 'aviso-error'); return; }

    // Validar correo si se proporcionó
    if (correo) {
        // Formato básico
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        if (!emailRegex.test(correo)) {
            mostrarAviso('❌ El correo electrónico no tiene un formato válido. Ejemplo: nombre@gmail.com', 'aviso-error');
            document.getElementById('modal-correo').focus();
            return;
        }
        // Verificar que el dominio tenga al menos un punto y extensión real
        const partes = correo.split('@');
        const dominio = partes[1] || '';
        if (!dominio.includes('.') || dominio.endsWith('.') || dominio.startsWith('.')) {
            mostrarAviso('❌ El dominio del correo no es válido. Ejemplo: nombre@gmail.com', 'aviso-error');
            document.getElementById('modal-correo').focus();
            return;
        }
    }

    const accion = id ? 'editar' : 'crear';
    const body = { nombre, pin, rol, correo };
    if (id) body.id_usuario = parseInt(id);

    const btn = document.getElementById('btn-guardar-usuario');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    try {
        const res  = await fetch(API + '?accion=' + accion, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();

        if (data.ok) {
            cerrarModal();
            // Si el admin se editó a sí mismo, actualizar nombre en sidebar y sessionStorage
            if (id) {
                try {
                    const ses = JSON.parse(sessionStorage.getItem('pos_usuario') || '{}');
                    if (ses && parseInt(ses.id) === parseInt(id)) {
                        ses.nombre = nombre;
                        sessionStorage.setItem('pos_usuario', JSON.stringify(ses));
                        const sbN = document.getElementById('sb-nombre');
                        const sbA = document.getElementById('sb-avatar');
                        if (sbN) sbN.textContent = nombre;
                        if (sbA) sbA.textContent = nombre[0].toUpperCase();
                    }
                } catch(e2){}
            }
            toast(id ? 'Cambios guardados ✓' : 'Usuario creado ✓');
            if (data.aviso) setTimeout(() => toast(data.aviso), 600);
            cargarUsuarios();
        } else {
            mostrarAviso(data.error || 'Error desconocido', 'aviso-error');
        }
    } catch(e) {
        mostrarAviso('Error de red: ' + e.message, 'aviso-error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = id
            ? '<i class="fas fa-save"></i> Guardar'
            : '<i class="fas fa-user-plus"></i> Crear';
    }
}

// ── HISTORIAL ──────────────────────────────────────────────────
async function cargarHistorial() {
    document.getElementById('hist-lista').innerHTML =
        '<div style="text-align:center;padding:32px;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
    const res = await fetch(API + '?accion=historial');
    const data = await res.json();

    if (!data.ok || !data.historial.length) {
        document.getElementById('hist-lista').innerHTML =
            '<div class="empty-state"><i class="fas fa-inbox"></i><p>No hay accesos registrados aún.<br>Se registrarán automáticamente cuando los usuarios inicien sesión.</p></div>';
        return;
    }

    document.getElementById('hist-lista').innerHTML = data.historial.map(log => `
        <div class="hist-item">
            <div class="hist-avatar" style="background:${ROL_COLORS[log.rol]||'#555'};">
                ${log.nombre[0].toUpperCase()}
            </div>
            <div>
                <div class="hist-nombre">${escHtml(log.nombre)}</div>
                <div class="hist-meta">${rolBadge(log.rol)} &nbsp; IP: ${log.ip || '—'}</div>
            </div>
            <div class="hist-ts">
                ${fmtFecha(log.ts)}<br>
                <span style="font-size:13px;font-weight:600;">${fmtHora(log.ts)}</span>
            </div>
        </div>
    `).join('');
}

// ── MESEROS PIN ────────────────────────────────────────────────
function cargarMeseros() {
    const meseros = todosUsuarios.filter(u => u.rol === 'mesero');
    document.getElementById('tbody-meseros').innerHTML = meseros.length
        ? meseros.map(u => `
            <tr>
                <td><div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:30px;height:30px;border-radius:50%;
                         background:${ROL_COLORS.mesero};display:grid;place-items:center;
                         font-weight:700;font-size:12px;color:#fff;">
                        ${u.nombre[0].toUpperCase()}
                    </div>
                    <span style="font-weight:600;">${escHtml(u.nombre)}</span>
                </div></td>
                <td><span style="display:inline-flex;align-items:center;gap:6px;">
                    <span class="${u.activo==1?'badge-activo':'badge-inactivo'}"></span>
                    ${u.activo==1?'Activo':'Inactivo'}
                </span></td>
                <td>
                    <button class="btn-tbl ok" onclick="abrirModalEditar(${u.id_usuario})">
                        <i class="fas fa-pen"></i> Editar
                    </button>
                    <button class="btn-tbl ${u.activo==1?'peligro':''}" onclick="toggleActivo(${u.id_usuario})">
                        <i class="fas fa-${u.activo==1?'ban':'circle-check'}"></i>
                        ${u.activo==1?'Desactivar':'Activar'}
                    </button>
                </td>
            </tr>`).join('')
        : '<tr><td colspan="3"><div class="empty-state"><i class="fas fa-user-slash"></i><p>No hay meseros registrados</p></div></td></tr>';
}

async function cambiarPinMeseros() {
    const pin = document.getElementById('pin-meseros-input').value.trim();
    const aviso = document.getElementById('aviso-pin-meseros');

    if (!pin || pin.length < 4 || !/^\d+$/.test(pin)) {
        aviso.className = 'aviso-modal aviso-error';
        aviso.textContent = 'El PIN debe tener al menos 4 dígitos numéricos.';
        aviso.style.display = 'block';
        return;
    }
    if (!confirm(`¿Cambiar el PIN de TODOS los meseros a ${pin}?`)) return;

    const res  = await fetch(API + '?accion=cambiar_pin_meseros', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pin_nuevo: pin })
    });
    const data = await res.json();

    aviso.style.display = 'block';
    if (data.ok) {
        aviso.className = 'aviso-modal aviso-success';
        aviso.textContent = `✓ PIN actualizado para ${data.meseros_actualizados} mesero(s).`;
        document.getElementById('pin-meseros-input').value = '';
        toast('PIN de meseros actualizado');
    } else {
        aviso.className = 'aviso-modal aviso-error';
        aviso.textContent = data.error || 'Error al cambiar el PIN.';
    }
}

// ── Helpers ────────────────────────────────────────────────────
function mostrarAviso(msg, cls) {
    const el = document.getElementById('aviso-modal');
    el.className = 'aviso-modal ' + cls;
    el.textContent = msg;
    el.style.display = 'block';
}
function ocultarAviso() {
    const el = document.getElementById('aviso-modal');
    el.style.display = 'none';
}
function togglePin(inputId, btn) {
    const inp = document.getElementById(inputId);
    const visible = inp.type === 'text';
    inp.type = visible ? 'password' : 'text';
    btn.querySelector('i').className = 'fas fa-' + (visible ? 'eye' : 'eye-slash');
}
function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Enter en modal = guardar
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') cerrarModal();
    if (e.key === 'Enter' && document.getElementById('modal-usuario').classList.contains('abierto'))
        guardarUsuario();
});

// ── Init ───────────────────────────────────────────────────────
// Asegurar que se ejecuta después de que el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cargarUsuarios);
} else {
    cargarUsuarios();
}

// ══════════════════════════════════════════════════════════════════
//  TURNOS DEL DÍA
// ══════════════════════════════════════════════════════════════════
const TURNO_API = 'turnos.php';
let _turnoFecha = '';
let _turnoUsuarios = [];
let _turnoActual  = [];
let _modoReparto  = 'todos';
let _selManual    = new Set();
let _totalPropinas = 0;

const ROL_COLORS_T = { admin:'#2E5226',mesero:'#2a4acc',cocina:'#B07D12',barra:'#5a30b0',cajero:'#A0320A' };

function irPaginaTurnos() {
    irPagina('turnos', document.querySelector('[onclick*="turnos"]'));
}

// Al entrar en la página de turnos: inicializar con fecha de hoy
document.addEventListener('DOMContentLoaded', function(){
    turnoFechaHoy();
});

function turnoFechaHoy() {
    const hoy = new Date();
    const iso = hoy.toISOString().split('T')[0];
    const inp = document.getElementById('turno-fecha-input');
    if (inp) inp.value = iso;
    cargarTurnoFecha(iso);
}

async function cargarTurnoFecha(fecha) {
    if (!fecha) return;
    _turnoFecha = fecha;
    const d = new Date(fecha + 'T12:00:00');
    const lbl = d.toLocaleDateString('es-MX',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
    const labelEl = document.getElementById('turno-fecha-label');
    if (labelEl) labelEl.textContent = lbl;

    try {
        const res  = await fetch(`${TURNO_API}?accion=listar_fecha&fecha=${fecha}`);
        const data = await res.json();
        if (!data.ok) return;
        _turnoActual  = data.turnos  || [];
        _turnoUsuarios = data.usuarios || [];
        _renderTurnoActual();
        _renderSelectorUsuarios();
        _actualizarPreviewReparto();
    } catch(e) {
        console.warn('turnos.php no disponible');
    }
    cargarPropinasDia();
}

function _renderTurnoActual() {
    const el = document.getElementById('turno-lista-actual');
    const badge = document.getElementById('turno-count-badge');
    if (badge) badge.textContent = _turnoActual.length;

    if (!_turnoActual.length) {
        el.innerHTML = `<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">
            <i class="fas fa-user-xmark" style="font-size:28px;display:block;margin-bottom:8px;opacity:.3;"></i>
            Sin personal registrado para este día
        </div>`;
        return;
    }

    el.innerHTML = _turnoActual.map(t => {
        const inicial = t.nombre[0].toUpperCase();
        const color   = ROL_COLORS_T[t.rol_turno] || '#4A7A3D';
        const propTag = t.propina > 0 ? `<span class="turno-propina-tag">$${parseFloat(t.propina).toFixed(2)}</span>` : '';
        const opciones = ['mesero','cajero','cocina','barra','admin'].map(r =>
            `<option value="${r}" ${r===t.rol_turno?'selected':''}>${r.charAt(0).toUpperCase()+r.slice(1)}</option>`
        ).join('');
        return `<div class="turno-item" id="ti-${t.id_turno}">
            <div class="turno-avatar" style="background:${color}">${inicial}</div>
            <span class="turno-nombre">${escAdmin(t.nombre)}</span>
            ${propTag}
            <select class="turno-rol-sel" onchange="cambiarRolTurno(${t.id_turno},${t.id_usuario},this.value)">
                ${opciones}
            </select>
            <button class="btn-quitar-turno" onclick="quitarDelTurno(${t.id_turno})" title="Quitar del turno">
                <i class="fas fa-xmark"></i>
            </button>
        </div>`;
    }).join('');

    _actualizarCheckboxesManual();
}

function _renderSelectorUsuarios() {
    const sel = document.getElementById('turno-sel-usuario');
    if (!sel) return;
    const yaEnTurno = new Set(_turnoActual.map(t => t.id_usuario));
    sel.innerHTML = '<option value="">— Selecciona empleado —</option>' +
        _turnoUsuarios
            .filter(u => !yaEnTurno.has(parseInt(u.id_usuario)))
            .map(u => `<option value="${u.id_usuario}" data-rol="${u.rol}">${escAdmin(u.nombre)} (${u.rol})</option>`)
            .join('');

    // Autoselect rol base
    sel.onchange = function() {
        const opt = sel.options[sel.selectedIndex];
        const rolBase = opt.dataset.rol || 'mesero';
        const rolSel  = document.getElementById('turno-sel-rol');
        if (rolSel) rolSel.value = rolBase;
    };
}

async function agregarAlTurno() {
    const uid = parseInt(document.getElementById('turno-sel-usuario').value);
    const rol = document.getElementById('turno-sel-rol').value;
    if (!uid) { toast('Selecciona un empleado', 'error'); return; }

    try {
        const res  = await fetch(`${TURNO_API}?accion=registrar`, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ id_usuario: uid, rol_turno: rol, fecha: _turnoFecha })
        });
        const data = await res.json();
        if (data.ok) { await cargarTurnoFecha(_turnoFecha); toast('Empleado agregado al turno ✓'); }
        else toast(data.error || 'Error', 'error');
    } catch(e) { toast('Sin conexión', 'error'); }
}

async function quitarDelTurno(id_turno) {
    try {
        const res  = await fetch(`${TURNO_API}?accion=quitar`, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ id_turno })
        });
        const data = await res.json();
        if (data.ok) { await cargarTurnoFecha(_turnoFecha); toast('Empleado quitado del turno'); }
        else toast(data.error || 'Error', 'error');
    } catch(e) { toast('Sin conexión', 'error'); }
}

async function cambiarRolTurno(id_turno, id_usuario, nuevo_rol) {
    try {
        await fetch(`${TURNO_API}?accion=registrar`, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ id_usuario, rol_turno: nuevo_rol, fecha: _turnoFecha })
        });
        await cargarTurnoFecha(_turnoFecha);
        toast(`Rol actualizado a ${nuevo_rol} ✓`);
    } catch(e) {}
}

// ── PROPINAS ──────────────────────────────────────────────────
async function cargarPropinasDia() {
    try {
        const res  = await fetch(`${TURNO_API}?accion=propinas_hoy&fecha=${_turnoFecha}`);
        const data = await res.json();
        if (data.ok) {
            _totalPropinas = data.total_propinas;
            const el = document.getElementById('turno-total-propinas');
            if (el) el.textContent = '$' + _totalPropinas.toFixed(2);
            _actualizarPreviewReparto();
        }
    } catch(e) {}
}

function selModoPropina(modo, btn) {
    _modoReparto = modo;
    document.querySelectorAll('.btn-modo-rep').forEach(b => b.classList.remove('activo'));
    btn.classList.add('activo');
    const manualDiv = document.getElementById('turno-seleccion-manual');
    if (manualDiv) manualDiv.style.display = modo === 'manual' ? 'block' : 'none';
    _actualizarPreviewReparto();
}

function _actualizarCheckboxesManual() {
    const el = document.getElementById('turno-checkboxes-manual');
    if (!el) return;
    el.innerHTML = _turnoActual
        .filter(t => t.rol_turno !== 'admin')
        .map(t => {
            const ch = _selManual.has(t.id_usuario) ? 'checked' : '';
            return `<label style="display:flex;align-items:center;gap:9px;padding:6px 4px;cursor:pointer;font-size:13px;border-radius:6px;transition:background .1s;"
                onmouseover="this.style.background='rgba(74,122,61,.06)'" onmouseout="this.style.background=''">
                <input type="checkbox" ${ch} style="width:15px;height:15px;accent-color:var(--accent);"
                    onchange="toggleSelManual(${t.id_usuario},this.checked)">
                <span style="font-weight:600;">${escAdmin(t.nombre)}</span>
                <span style="font-size:11px;color:var(--text-muted);">(${t.rol_turno})</span>
            </label>`;
        }).join('');
}

function toggleSelManual(uid, checked) {
    if (checked) _selManual.add(uid); else _selManual.delete(uid);
    _actualizarPreviewReparto();
}

function _actualizarPreviewReparto() {
    const previewEl = document.getElementById('turno-preview-reparto');
    const listaEl   = document.getElementById('turno-preview-lista');
    const btnEl     = document.getElementById('btn-distribuir-propinas');
    if (!previewEl || !listaEl) return;

    let participantes = [];
    if (_modoReparto === 'meseros') {
        participantes = _turnoActual.filter(t => t.rol_turno === 'mesero');
    } else if (_modoReparto === 'manual') {
        participantes = _turnoActual.filter(t => _selManual.has(t.id_usuario));
    } else {
        participantes = _turnoActual.filter(t => t.rol_turno !== 'admin');
    }

    if (!participantes.length || _totalPropinas <= 0) {
        previewEl.style.display = 'none';
        if (btnEl) btnEl.disabled = true;
        return;
    }

    const porPersona = _totalPropinas / participantes.length;
    previewEl.style.display = 'block';
    listaEl.innerHTML = participantes.map(t =>
        `<div class="turno-preview-row">
            <span class="turno-prev-nombre">${escAdmin(t.nombre)} <span style="font-size:11px;color:var(--text-muted);">(${t.rol_turno})</span></span>
            <span class="turno-prev-monto">$${porPersona.toFixed(2)}</span>
        </div>`
    ).join('');

    if (btnEl) btnEl.disabled = false;
}

async function distribuirPropinas() {
    if (_totalPropinas <= 0) { toast('No hay propinas para repartir hoy', 'error'); return; }
    if (!_turnoActual.length) { toast('No hay personal en el turno', 'error'); return; }

    const ids = _modoReparto === 'manual' ? [..._selManual] : [];

    const aviso = document.getElementById('turno-aviso-propina');

    try {
        const res  = await fetch(`${TURNO_API}?accion=dividir_propinas`, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                fecha: _turnoFecha,
                total_propina: _totalPropinas,
                modo: _modoReparto,
                ids_usuarios: ids
            })
        });
        const data = await res.json();
        if (data.ok) {
            aviso.style.display = 'block';
            aviso.className = 'aviso-modal aviso-ok';
            aviso.innerHTML = `<i class="fas fa-circle-check"></i> Propinas distribuidas — <strong>${data.participantes} persona${data.participantes !== 1 ? 's' : ''}</strong> · $${parseFloat(data.por_persona).toFixed(2)} c/u`;
            await cargarTurnoFecha(_turnoFecha);
            toast('Propinas distribuidas ✓');
        } else {
            aviso.style.display = 'block';
            aviso.className = 'aviso-modal aviso-error';
            aviso.textContent = data.error || 'Error al distribuir';
        }
    } catch(e) {
        aviso.style.display = 'block';
        aviso.className = 'aviso-modal aviso-error';
        aviso.textContent = 'Sin conexión al servidor';
    }
}

async function cargarHistorialPropinas() {
    const el = document.getElementById('turno-historial-propinas');
    el.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
    try {
        const res  = await fetch(`${TURNO_API}?accion=historial_propinas`);
        const data = await res.json();
        if (!data.ok || !data.historial.length) {
            el.innerHTML = '<span style="color:var(--text-muted);">Sin propinas registradas aún.</span>'; return;
        }
        // Agrupar por fecha
        const porFecha = {};
        data.historial.forEach(h => {
            if (!porFecha[h.fecha]) porFecha[h.fecha] = [];
            porFecha[h.fecha].push(h);
        });
        el.innerHTML = Object.entries(porFecha).map(([fecha, rows]) => {
            const d = new Date(fecha + 'T12:00:00');
            const fStr = d.toLocaleDateString('es-MX',{weekday:'short',day:'numeric',month:'short',year:'numeric'});
            const rowsHtml = rows.map(r =>
                `<div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid rgba(0,0,0,.04);font-size:13px;">
                    <span>${escAdmin(r.nombre)} <span style="font-size:11px;color:var(--text-muted);">(${r.rol_turno})</span></span>
                    <span style="font-weight:700;color:var(--accent);">$${parseFloat(r.propina).toFixed(2)}</span>
                </div>`
            ).join('');
            return `<div style="margin-bottom:16px;">
                <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;padding:6px 18px;background:var(--bg-app);border-radius:6px;margin-bottom:4px;">${fStr}</div>
                <div style="padding:0 6px;">${rowsHtml}</div>
            </div>`;
        }).join('');
    } catch(e) { el.innerHTML = '<span style="color:var(--text-muted);">Sin conexión al servidor.</span>'; }
}

function escAdmin(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── Configuración de Correo ─────────────────────────────────────
const CONF_API = 'configuracion.php';

async function cargarConfigCorreo() {
    try {
        const res  = await fetch(`${CONF_API}?accion=leer`);
        const data = await res.json();
        if (!data.ok) return;
        document.getElementById('cfg-smtp-host').value      = data.smtp_host      || 'smtp.gmail.com';
        document.getElementById('cfg-smtp-port').value      = data.smtp_port      || '587';
        document.getElementById('cfg-smtp-user').value      = data.smtp_user      || '';
        document.getElementById('cfg-smtp-from-name').value = data.smtp_from_name || 'Jardín POS';
        document.getElementById('cfg-negocio').value        = data.negocio_nombre || '';
        // Mostrar contraseña guardada en el campo (oculta con puntos, visible con ojito)
        const passInput = document.getElementById('cfg-smtp-pass');
        const passHint  = document.getElementById('cfg-pass-hint');
        if (data.smtp_pass_set && data.smtp_pass_value) {
            passInput.value       = data.smtp_pass_value;
            passInput.placeholder = '••••••••••••••••';
            passHint.innerHTML = '<i class="fas fa-lock" style="color:var(--accent);margin-right:4px;"></i>Contraseña guardada. Haz clic en el ojo para verla. Deja vacío para no cambiarla.';
        } else if (data.smtp_pass_set) {
            passInput.placeholder = '(contraseña guardada)';
            passHint.innerHTML = '<i class="fas fa-lock" style="color:var(--accent);margin-right:4px;"></i>Contraseña guardada. Deja vacío para no cambiarla.';
        } else {
            passHint.innerHTML = '<i class="fas fa-exclamation-circle" style="color:var(--warn);margin-right:4px;"></i>Sin contraseña guardada aún.';
        }
    } catch(e) { console.error('Error cargando config correo', e); }
}

async function guardarConfigCorreo() {
    const btn  = document.getElementById('cfg-btn-guardar');
    const av   = document.getElementById('cfg-aviso');
    av.style.display = 'none';

    const body = {
        smtp_host:      document.getElementById('cfg-smtp-host').value.trim(),
        smtp_port:      document.getElementById('cfg-smtp-port').value.trim(),
        smtp_user:      document.getElementById('cfg-smtp-user').value.trim(),
        smtp_pass:      document.getElementById('cfg-smtp-pass').value.trim(),
        smtp_from_name: document.getElementById('cfg-smtp-from-name').value.trim(),
        negocio_nombre: document.getElementById('cfg-negocio').value.trim(),
    };

    if (!body.smtp_user) { cfgAviso('El correo de envío es obligatorio', 'error'); return; }
    if (!body.smtp_user.includes('@') || !body.smtp_user.includes('.')) { cfgAviso('El correo de envío no tiene formato válido', 'error'); return; }

    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    try {
        const res  = await fetch(`${CONF_API}?accion=guardar_smtp`, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)});
        const data = await res.json();
        if (data.ok) {
            cfgAviso('✅ Configuración guardada correctamente', 'ok');
            document.getElementById('cfg-smtp-pass').value = '';
            cargarConfigCorreo();
            toast('Config. de correo guardada ✓');
        } else {
            cfgAviso('❌ ' + (data.error || 'Error desconocido'), 'error');
        }
    } catch(e) { cfgAviso('Sin conexión al servidor', 'error'); }
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Guardar configuración';
}

async function probarSMTP() {
    const correo = document.getElementById('cfg-correo-prueba').value.trim();
    const av     = document.getElementById('cfg-aviso-prueba');
    const btn    = document.getElementById('cfg-btn-probar');
    av.style.display = 'none';
    if (!correo) { av.className='aviso-modal aviso-error'; av.textContent='Escribe un correo para la prueba'; av.style.display='block'; return; }
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando prueba...';
    try {
        const res  = await fetch(`${CONF_API}?accion=probar_smtp`, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({correo_prueba: correo})});
        const data = await res.json();
        av.className = data.ok ? 'aviso-modal aviso-ok' : 'aviso-modal aviso-error';
        av.innerHTML = data.ok
            ? '✅ Correo de prueba enviado a <strong>' + escAdmin(correo) + '</strong>. Revisa tu bandeja.'
            : '❌ ' + escAdmin(data.error || 'Error desconocido');
        av.style.display = 'block';
        if (data.ok) toast('Correo de prueba enviado ✓');
    } catch(e) { av.className='aviso-modal aviso-error'; av.textContent='Sin conexión al servidor'; av.style.display='block'; }
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar correo de prueba';
}

function cfgAviso(msg, tipo) {
    const av = document.getElementById('cfg-aviso');
    av.className = 'aviso-modal ' + (tipo === 'ok' ? 'aviso-ok' : 'aviso-error');
    av.innerHTML = msg;
    av.style.display = 'block';
}

</script>

<!-- ══ PÁGINA: CONFIGURACIÓN DE CORREO ══════════════════════════ -->
<template id="tpl-pg-correo">
<div id="pg-correo" class="page">
    <div class="page-title"><i class="fas fa-envelope-circle-check" style="color:var(--accent);margin-right:10px;"></i>Configuración de Correo</div>
    <p class="page-sub">Define el correo desde el que el sistema envía PINs a los empleados. Puede ser cualquier Gmail.</p>

    <!-- Info rápida -->
    <div style="background:rgba(74,122,61,.07);border:1px solid rgba(74,122,61,.2);border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:var(--text-main);line-height:1.7;">
        <strong><i class="fas fa-circle-info" style="color:var(--accent);margin-right:6px;"></i>¿Cómo funciona?</strong><br>
        El sistema usa el correo que configures aquí para enviar el PIN a cualquier empleado que lo solicite en <em>recuperar.php</em>.<br>
        Puedes usar el Gmail de la cafetería, el del dueño, o uno creado especialmente para el negocio.
    </div>

    <!-- Formulario SMTP -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><h3><i class="fas fa-gear" style="margin-right:8px;color:var(--accent);"></i>Credenciales SMTP</h3></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

            <div class="form-field" style="grid-column:1/-1;">
                <label class="form-label">Nombre del negocio <span style="color:var(--text-muted);font-weight:400;">(aparece en los correos)</span></label>
                <input type="text" id="cfg-negocio" class="form-input" placeholder="Cafetería Jardín" maxlength="80">
            </div>

            <div class="form-field" style="grid-column:1/-1;">
                <label class="form-label">Correo que ENVÍA los mensajes</label>
                <input type="email" id="cfg-smtp-user" class="form-input" placeholder="cafeteria@gmail.com" maxlength="120">
                <p style="font-size:11px;color:var(--text-muted);margin-top:5px;">Este es el correo desde el que llegarán los mensajes a los empleados.</p>
            </div>

            <div class="form-field" style="grid-column:1/-1;">
                <label class="form-label">Contraseña de Aplicación (App Password) de Google</label>
                <div class="pin-field">
                    <input type="password" id="cfg-smtp-pass" class="form-input" placeholder="16 letras sin espacios" maxlength="40" autocomplete="new-password">
                    <button class="pin-toggle" onclick="togglePin('cfg-smtp-pass',this)"><i class="fas fa-eye"></i></button>
                </div>
                <p id="cfg-pass-hint" style="font-size:11px;color:var(--text-muted);margin-top:5px;"></p>
                <div style="background:rgba(176,125,18,.08);border:1px solid rgba(176,125,18,.25);border-radius:8px;padding:10px 14px;margin-top:10px;font-size:12px;color:var(--warn,#B07D12);line-height:1.7;">
                    <strong>⚠️ NO uses tu contraseña normal de Gmail.</strong> Debes crear una <strong>Contraseña de Aplicación</strong>:<br>
                    1. Ve a <strong>myaccount.google.com</strong> → Seguridad<br>
                    2. Activa <strong>Verificación en 2 pasos</strong><br>
                    3. Busca <strong>"Contraseñas de aplicación"</strong><br>
                    4. Crea una → copia las 16 letras <strong>sin espacios</strong>
                </div>
            </div>

            <div class="form-field">
                <label class="form-label">Nombre que aparece en el correo</label>
                <input type="text" id="cfg-smtp-from-name" class="form-input" placeholder="Jardín POS" maxlength="60">
            </div>

            <div class="form-field">
                <label class="form-label">Servidor SMTP</label>
                <input type="text" id="cfg-smtp-host" class="form-input" value="smtp.gmail.com" maxlength="80">
            </div>

            <div class="form-field">
                <label class="form-label">Puerto</label>
                <input type="number" id="cfg-smtp-port" class="form-input" value="587" min="1" max="65535">
                <p style="font-size:11px;color:var(--text-muted);margin-top:4px;">Gmail usa 587 (STARTTLS). No cambies esto a menos que uses otro proveedor.</p>
            </div>

            <div style="grid-column:1/-1;">
                <div id="cfg-aviso" class="aviso-modal" style="display:none;margin-bottom:12px;"></div>
                <button class="btn btn-primary" id="cfg-btn-guardar" onclick="guardarConfigCorreo()">
                    <i class="fas fa-save"></i> Guardar configuración
                </button>
            </div>
        </div>
    </div>

    <!-- Correo de prueba -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-paper-plane" style="margin-right:8px;color:var(--accent);"></i>Enviar correo de prueba</h3></div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
                Después de guardar la configuración, envía un correo de prueba para verificar que todo funciona.
            </p>
            <div class="form-field" style="max-width:380px;">
                <label class="form-label">Correo destino de la prueba</label>
                <input type="email" id="cfg-correo-prueba" class="form-input" placeholder="tuCorreo@gmail.com"
                       onkeydown="if(event.key==='Enter') probarSMTP()">
            </div>
            <div id="cfg-aviso-prueba" class="aviso-modal" style="display:none;margin-bottom:12px;max-width:480px;"></div>
            <button class="btn btn-secondary" id="cfg-btn-probar" onclick="probarSMTP()">
                <i class="fas fa-paper-plane"></i> Enviar correo de prueba
            </button>
        </div>
    </div>
</div>
</template>

<script>
// Inyectar la página de correo en el DOM e inicializar
(function() {
    const tpl = document.getElementById('tpl-pg-correo');
    if (tpl) {
        document.querySelector('main.main').insertAdjacentHTML('beforeend',
            tpl.content.querySelector('#pg-correo').outerHTML
        );
        tpl.remove();
    }

    // Extender irPagina para cargar config al entrar a 'correo'
    const _irPaginaOrig = window.irPagina;
    window.irPagina = function(id, btn) {
        _irPaginaOrig(id, btn);
        if (id === 'correo') cargarConfigCorreo();
    };
})();
</script>

</body>
</html>
