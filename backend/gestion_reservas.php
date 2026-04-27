<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reservas — Jardín POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --verde1:#0F2014; --verde2:#1C3A20; --verde3:#2E5226; --accent:#4A7A3D;
        --light:#7DB56A; --bg:#F0F4EE; --card:#FFFFFF; --border:#D4E0CF;
        --text:#1C2B18; --muted:#6B7F65; --danger:#A0320A; --amber:#B07D12;
        --purple:#5A2090; --purple-bg:rgba(107,47,170,.08);
        --shadow:0 2px 12px rgba(28,43,24,.09); --radius:12px;
    }
    [data-dark="true"] {
        --bg:#111A0F; --card:#1C2B18; --text:#E8F0E5;
        --muted:#8FA688; --border:#2E4429;
    }
    * { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:'Segoe UI',system-ui,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }

    /* ═══ TOPBAR ══════════════════════════════════════════════ */
    .topbar {
        position:sticky; top:0; z-index:100;
        display:flex; align-items:center; justify-content:space-between;
        padding:0 24px; height:58px;
        background:var(--verde1); border-bottom:1px solid rgba(255,255,255,.06);
    }
    .tb-left { display:flex; align-items:center; gap:14px; }
    .tb-right { display:flex; align-items:center; gap:10px; }
    .tb-logo { font-size:18px; }
    .tb-title { font-size:13px; font-weight:700; color:#fff; letter-spacing:.5px; }
    .tb-sub { font-size:9px; color:rgba(255,255,255,.35); letter-spacing:1.5px; text-transform:uppercase; display:block; margin-top:1px; }
    .btn-tb { display:inline-flex; align-items:center; gap:7px; padding:7px 14px; border-radius:8px; border:none; font-size:12px; font-weight:700; cursor:pointer; transition:all .15s; }
    .btn-tb-back { background:rgba(255,255,255,.08); color:rgba(255,255,255,.8); border:1px solid rgba(255,255,255,.1); }
    .btn-tb-back:hover { background:rgba(255,255,255,.16); color:#fff; }
    .btn-tb-salir { background:rgba(160,50,10,.3); color:#ff9f80; border:1px solid rgba(160,50,10,.4); }
    .btn-tb-salir:hover { background:rgba(160,50,10,.55); }
    .btn-tb-tema { background:rgba(255,255,255,.06); color:rgba(255,255,255,.5); border:1px solid rgba(255,255,255,.08); padding:7px 10px; }

    /* ═══ LAYOUT ══════════════════════════════════════════════ */
    .page-body { max-width:1200px; margin:0 auto; padding:28px 24px; }

    /* ═══ CABECERA ════════════════════════════════════════════ */
    .page-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:28px; }
    .page-header-left h1 { font-size:22px; font-weight:800; color:var(--text); }
    .page-header-left p { font-size:13px; color:var(--muted); margin-top:4px; }

    .btn-prim { display:inline-flex; align-items:center; gap:7px; padding:10px 20px; background:var(--accent); color:#fff; border:none; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 3px 10px rgba(74,122,61,.3); transition:all .18s; }
    .btn-prim:hover { background:var(--verde3); transform:translateY(-1px); }
    .btn-sec { display:inline-flex; align-items:center; gap:7px; padding:9px 16px; background:transparent; color:var(--text); border:1.5px solid var(--border); border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; transition:all .15s; }
    .btn-sec:hover { background:var(--bg); }

    /* ═══ GRID: CALENDARIO + LISTA ═══════════════════════════ */
    .reservas-grid { display:grid; grid-template-columns:340px 1fr; gap:20px; align-items:start; }
    @media (max-width:800px) { .reservas-grid { grid-template-columns:1fr; } }

    /* ═══ PANEL CALENDARIO ════════════════════════════════════ */
    .cal-panel {
        background:var(--card); border-radius:var(--radius);
        border:1px solid var(--border); box-shadow:var(--shadow);
        overflow:hidden; position:sticky; top:78px;
    }
    .cal-panel-header { padding:18px 20px 14px; border-bottom:1px solid var(--border); }
    .cal-panel-header h3 { font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px; }
    .cal-panel-header p { font-size:12px; color:var(--muted); }

    .cal-nav { display:flex; align-items:center; justify-content:space-between; padding:14px 20px 8px; }
    .cal-nav-btn { background:var(--bg); border:1px solid var(--border); border-radius:8px; padding:6px 10px; cursor:pointer; color:var(--text); font-size:13px; transition:background .15s; }
    .cal-nav-btn:hover { background:var(--border); }
    .cal-mes-lbl { font-size:14px; font-weight:700; color:var(--text); }

    .cal-semana { display:grid; grid-template-columns:repeat(7,1fr); padding:4px 12px; }
    .cal-semana span { text-align:center; font-size:10px; font-weight:700; color:var(--muted); text-transform:uppercase; padding:4px 0; }

    .cal-grid { display:grid; grid-template-columns:repeat(7,1fr); padding:4px 12px 16px; gap:2px; }
    .cal-dia {
        aspect-ratio:1; display:flex; flex-direction:column; align-items:center; justify-content:center;
        border-radius:8px; font-size:12.5px; cursor:pointer; position:relative;
        transition:background .15s; user-select:none;
    }
    .cal-dia:hover:not(.cal-vacio):not(.cal-pasado) { background:rgba(74,122,61,.1); }
    .cal-vacio { cursor:default; }
    .cal-pasado { color:var(--muted); opacity:.4; cursor:default; }
    .cal-hoy { background:rgba(74,122,61,.12); font-weight:700; color:var(--accent); }
    .cal-selec { background:var(--accent) !important; color:#fff !important; font-weight:800; }
    .cal-reservado { font-weight:700; }
    .cal-dot {
        width:5px; height:5px; border-radius:50%; background:var(--danger);
        position:absolute; bottom:3px;
    }
    .cal-dia.cal-reservado-hoy { background:rgba(160,50,10,.08); color:var(--danger); }

    /* Leyenda calendario */
    .cal-leyenda { display:flex; gap:12px; padding:10px 16px 14px; flex-wrap:wrap; }
    .cal-ley-item { display:flex; align-items:center; gap:5px; font-size:11px; color:var(--muted); }
    .cal-ley-dot { width:8px; height:8px; border-radius:50%; }

    /* ═══ PANEL LISTA DE RESERVAS ═════════════════════════════ */
    .lista-panel { display:flex; flex-direction:column; gap:12px; }

    .lista-tabs { display:flex; gap:6px; margin-bottom:4px; flex-wrap:wrap; }
    .lista-tab {
        padding:7px 16px; border-radius:8px; border:1.5px solid var(--border);
        background:transparent; cursor:pointer; font-size:13px; font-weight:600;
        color:var(--muted); transition:all .15s;
    }
    .lista-tab:hover { border-color:var(--accent); color:var(--accent); }
    .lista-tab.activo { background:var(--accent); border-color:var(--accent); color:#fff; }

    .reserva-card {
        background:var(--card); border-radius:var(--radius);
        border:1.5px solid var(--border); box-shadow:var(--shadow);
        padding:16px 20px; transition:box-shadow .15s, border-color .15s;
        display:flex; flex-direction:column; gap:10px;
    }
    .reserva-card:hover { box-shadow:0 4px 18px rgba(28,43,24,.12); border-color:rgba(74,122,61,.3); }
    .reserva-card.card-hoy { border-left:4px solid var(--accent); }
    .reserva-card.card-proxima { border-left:4px solid var(--amber); }
    .reserva-card.card-pasada { border-left:4px solid var(--muted); opacity:.7; }
    .reserva-card.card-cancelada { border-left:4px solid var(--danger); opacity:.55; }

    .rc-top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
    .rc-fecha-grande {
        background:var(--accent); color:#fff; border-radius:10px;
        padding:8px 14px; text-align:center; flex-shrink:0; min-width:64px;
    }
    .rc-fecha-grande .rc-dia { font-size:22px; font-weight:800; line-height:1; }
    .rc-fecha-grande .rc-mes { font-size:10px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; margin-top:2px; opacity:.9; }

    .rc-info { flex:1; }
    .rc-cliente { font-size:15px; font-weight:700; color:var(--text); margin-bottom:4px; }
    .rc-meta { display:flex; flex-wrap:wrap; gap:8px; }
    .rc-chip { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; color:var(--muted); background:var(--bg); border:1px solid var(--border); padding:3px 9px; border-radius:20px; }
    .rc-chip i { font-size:11px; color:var(--accent); }

    .rc-estado-badge {
        font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:20px;
        white-space:nowrap; align-self:flex-start;
    }
    .badge-hoy      { background:rgba(74,122,61,.15);  color:var(--verde3); }
    .badge-proxima  { background:rgba(176,125,18,.12); color:var(--amber); }
    .badge-pasada   { background:rgba(153,153,153,.1); color:#777; }
    .badge-cancelada{ background:rgba(160,50,10,.1);  color:var(--danger); }

    .rc-evento { display:flex; align-items:center; gap:6px; font-size:12.5px; color:var(--purple); background:var(--purple-bg); border-radius:8px; padding:6px 12px; margin-top:2px; }
    .rc-nota { font-size:12px; color:var(--muted); font-style:italic; margin-top:2px; }
    .rc-mesa-tag { font-size:11px; font-weight:700; background:var(--verde3); color:#fff; padding:2px 9px; border-radius:6px; }

    .rc-actions { display:flex; gap:8px; padding-top:8px; border-top:1px solid var(--border); }
    .btn-rc { padding:6px 12px; border-radius:7px; border:1px solid var(--border); background:var(--bg); cursor:pointer; font-size:12px; font-weight:600; color:var(--text); transition:all .15s; display:inline-flex; align-items:center; gap:5px; }
    .btn-rc:hover { background:var(--border); }
    .btn-rc-danger { border-color:var(--danger); color:var(--danger); }
    .btn-rc-danger:hover { background:rgba(160,50,10,.08); }

    .empty-list { text-align:center; padding:48px 0; color:var(--muted); }

    .btn-limpiar-pasadas { margin:12px 0 4px; width:100%; padding:9px; background:#fff; border:1.5px solid #C0392B; color:#C0392B; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:background .15s; }
    .btn-limpiar-pasadas:hover { background:#fdf0ef; }

    /* ── Modal de confirmación limpiar ── */
    .modal-limpiar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:4000; align-items:center; justify-content:center; }
    .modal-limpiar-overlay.open { display:flex; }
    .modal-limpiar-box { background:#fff; border-radius:16px; padding:28px 28px 24px; max-width:360px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,.22); }
    .modal-limpiar-icon { font-size:36px; text-align:center; margin-bottom:12px; }
    .modal-limpiar-title { font-size:17px; font-weight:700; color:#C0392B; text-align:center; margin-bottom:8px; }
    .modal-limpiar-msg { font-size:13px; color:#555; text-align:center; line-height:1.6; margin-bottom:20px; }
    .modal-limpiar-btns { display:flex; gap:10px; }
    .modal-limpiar-btns button { flex:1; padding:10px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; border:none; }
    .btn-ml-cancel { background:#f0f0f0; color:#444; }
    .btn-ml-confirm { background:#C0392B; color:#fff; }
    .empty-list i { font-size:36px; display:block; margin-bottom:12px; opacity:.35; }
    .empty-list p { font-size:14px; }

    /* ═══ MODAL NUEVA RESERVA ════════════════════════════════ */
    .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; place-items:center; z-index:1000; backdrop-filter:blur(3px); }
    .modal-overlay.open { display:grid; }
    .modal-box { background:var(--card); border-radius:18px; width:860px; max-width:97vw; max-height:95vh; overflow-y:auto; box-shadow:0 28px 70px rgba(0,0,0,.25); animation:modIn .2s ease; }
    @keyframes modIn { from{transform:translateY(-14px) scale(.97);opacity:0} to{transform:none;opacity:1} }

    .modal-header { padding:22px 24px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
    .modal-header h3 { font-size:18px; font-weight:800; }
    .modal-header p { font-size:13px; color:var(--muted); margin-top:3px; }
    .modal-close { background:var(--bg); border:1px solid var(--border); border-radius:8px; padding:7px 10px; cursor:pointer; font-size:14px; color:var(--muted); transition:all .15s; }
    .modal-close:hover { background:var(--border); color:var(--text); }

    .modal-body { display:grid; grid-template-columns:1fr 1fr; gap:24px; padding:24px; }
    @media (max-width:640px) { .modal-body { grid-template-columns:1fr; } }

    /* Calendario del modal */
    .modal-cal { background:var(--bg); border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; }
    .modal-cal-header { padding:14px 18px 10px; border-bottom:1px solid var(--border); }
    .modal-cal-header h4 { font-size:13px; font-weight:700; }

    /* Mesas visuales */
    .mesas-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(80px,1fr)); gap:8px; margin-bottom:16px; }
    .mesa-pick {
        aspect-ratio:1; display:flex; flex-direction:column; align-items:center; justify-content:center;
        border-radius:10px; border:2px solid var(--border); cursor:pointer; font-size:12px; font-weight:700;
        background:var(--bg); color:var(--text); transition:all .18s; gap:4px;
    }
    .mesa-pick i { font-size:20px; color:var(--muted); }
    .mesa-pick:hover { border-color:var(--accent); color:var(--accent); }
    .mesa-pick:hover i { color:var(--accent); }
    .mesa-pick.seleccionada { background:var(--accent); border-color:var(--accent); color:#fff; }
    .mesa-pick.seleccionada i { color:#fff; }
    .mesa-pick.ocupada { opacity:.4; cursor:not-allowed; }
    .mesa-pick.reservada-hoy { border-color:var(--danger); color:var(--danger); background:rgba(160,50,10,.05); }
    .mesa-pick.reservada-hoy i { color:var(--danger); }

    /* Form modal */
    .form-field { margin-bottom:14px; }
    .form-label { display:block; font-size:11px; font-weight:700; color:var(--muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:.5px; }
    .form-input, .form-select, .form-textarea { width:100%; padding:9px 12px; border:1.5px solid var(--border); border-radius:9px; background:var(--bg); color:var(--text); font-size:14px; font-family:inherit; outline:none; transition:border-color .15s; }
    .form-input:focus,.form-select:focus,.form-textarea:focus { border-color:var(--accent); }
    .form-textarea { resize:vertical; min-height:70px; }
    .form-row { display:flex; gap:12px; }
    .form-row .form-field { flex:1; }

    .hora-row { display:flex; align-items:center; gap:8px; }
    .hora-pick { display:flex; flex-direction:column; align-items:center; gap:2px; }
    .hora-pick button { background:var(--bg); border:1px solid var(--border); border-radius:6px; padding:4px 10px; cursor:pointer; font-size:12px; color:var(--muted); transition:background .15s; }
    .hora-pick button:hover { background:var(--border); }
    .hora-val { font-size:22px; font-weight:800; color:var(--text); min-width:36px; text-align:center; font-family:monospace; }
    .hora-sep { font-size:22px; font-weight:800; color:var(--muted); }
    .ampm-btns { display:flex; flex-direction:column; gap:4px; }
    .ampm-btn { padding:5px 10px; border-radius:6px; border:1.5px solid var(--border); background:transparent; cursor:pointer; font-size:11px; font-weight:700; color:var(--muted); transition:all .15s; }
    .ampm-btn.activo { background:var(--accent); border-color:var(--accent); color:#fff; }

    .pax-row { display:flex; align-items:center; gap:10px; }
    .pax-btn { background:var(--bg); border:1.5px solid var(--border); border-radius:8px; padding:6px 12px; cursor:pointer; font-size:16px; font-weight:700; color:var(--text); transition:all .15s; }
    .pax-btn:hover { border-color:var(--accent); color:var(--accent); }
    .pax-val { font-size:20px; font-weight:800; min-width:32px; text-align:center; }
    .pax-lbl { font-size:12px; color:var(--muted); }

    .modal-footer { padding:16px 24px; border-top:1px solid var(--border); display:flex; gap:10px; justify-content:flex-end; }

    .msg-error { background:rgba(160,50,10,.08); color:var(--danger); border:1px solid rgba(160,50,10,.2); padding:9px 13px; border-radius:8px; font-size:12px; margin-bottom:12px; display:none; }

    /* ═══ TOAST ═══════════════════════════════════════════════ */
    #toast { position:fixed; bottom:24px; right:24px; background:var(--verde1); color:#fff; padding:12px 18px; border-radius:10px; font-size:13.5px; font-weight:600; box-shadow:0 8px 24px rgba(0,0,0,.2); z-index:9999; display:none; align-items:center; gap:10px; border-left:4px solid var(--light); }
    #toast.visible { display:flex; }
    #toast.err { border-left-color:var(--danger); }

    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="tb-left">
        <span class="tb-logo">🌿</span>
        <div>
            <span class="tb-title">Gestión de Reservas</span>
            <span class="tb-sub">Jardín POS</span>
        </div>
    </div>
    <div class="tb-right">
        <button class="btn-tb btn-tb-tema" onclick="toggleTema()"><i class="fas fa-moon"></i></button>
        <button class="btn-tb btn-tb-back" onclick="irAtras()">
            <i class="fas fa-arrow-left"></i> Volver
        </button>
        <button class="btn-tb btn-tb-salir" onclick="cerrarSesion()">
            <i class="fas fa-sign-out-alt"></i> Salir
        </button>
    </div>
</div>

<!-- BODY -->
<div class="page-body">

    <div class="page-header">
        <div class="page-header-left">
            <h1><i class="fas fa-calendar-days" style="color:var(--purple);margin-right:10px;"></i>Reservas del Restaurante</h1>
            <p>Todas las reservas almacenadas — solo las del día aparecen en las mesas</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn-sec" onclick="cargarReservas()">
                <i class="fas fa-rotate-right"></i> Actualizar
            </button>
            <button class="btn-prim" onclick="abrirModalNueva()">
                <i class="fas fa-plus"></i> Nueva Reserva
            </button>
        </div>
    </div>

    <div class="reservas-grid">

        <!-- CALENDARIO LATERAL -->
        <div class="cal-panel">
            <div class="cal-panel-header">
                <h3>Calendario de Reservas</h3>
                <p>Los puntos rojos indican días con reservas</p>
            </div>
            <div class="cal-nav">
                <button class="cal-nav-btn" onclick="calCambiarMes(-1)"><i class="fas fa-chevron-left"></i></button>
                <span class="cal-mes-lbl" id="cal-mes-lbl">—</span>
                <button class="cal-nav-btn" onclick="calCambiarMes(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="cal-semana">
                <span>Dom</span><span>Lun</span><span>Mar</span>
                <span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span>
            </div>
            <div class="cal-grid" id="cal-grid-main"></div>
            <div class="cal-leyenda">
                <div class="cal-ley-item"><div class="cal-ley-dot" style="background:var(--accent)"></div> Hoy</div>
                <div class="cal-ley-item"><div class="cal-ley-dot" style="background:var(--danger)"></div> Con reservas</div>
                <div class="cal-ley-item"><div class="cal-ley-dot" style="background:var(--amber)"></div> Seleccionado</div>
            </div>
        </div>

        <!-- LISTA DE RESERVAS -->
        <div class="lista-panel">
            <div class="lista-tabs">
                <button class="lista-tab activo" data-filtro="todas" onclick="selFiltro('todas',this)">Todas</button>
                <button class="lista-tab" data-filtro="hoy" onclick="selFiltro('hoy',this)">Hoy</button>
                <button class="lista-tab" data-filtro="proximas" onclick="selFiltro('proximas',this)">Próximas</button>
                <button class="lista-tab" data-filtro="pasadas" onclick="selFiltro('pasadas',this)">Pasadas</button>
            </div>
            <div id="lista-reservas">
                <div class="empty-list"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL: NUEVA RESERVA ════════════════════════════════════ -->
<div class="modal-overlay" id="modal-nueva" onclick="cerrarModalSi(event)">
<div class="modal-box">
    <div class="modal-header">
        <div>
            <h3><i class="fas fa-calendar-plus" style="color:var(--purple);margin-right:8px;"></i>Nueva Reserva</h3>
            <p>Elige la mesa, fecha, hora y datos del cliente</p>
        </div>
        <button class="modal-close" onclick="cerrarModal()"><i class="fas fa-times"></i></button>
    </div>

    <div class="modal-body">

        <!-- Columna izquierda: Mesa + Calendario -->
        <div>
            <!-- Selección visual de mesa -->
            <div class="form-field">
                <label class="form-label"><i class="fas fa-table" style="margin-right:5px;"></i>Seleccionar Mesa</label>
                <div class="mesas-grid" id="mesas-picker"></div>
                <input type="hidden" id="inp-mesa-id">
            </div>

            <!-- Calendario mini -->
            <div class="form-field">
                <label class="form-label"><i class="fas fa-calendar" style="margin-right:5px;"></i>Fecha de la Reserva</label>
                <div class="modal-cal">
                    <div class="modal-cal-header">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <button class="cal-nav-btn" onclick="mCalCambiarMes(-1)" style="font-size:11px;padding:4px 8px;"><i class="fas fa-chevron-left"></i></button>
                            <span style="font-size:13px;font-weight:700;" id="mcal-mes-lbl">—</span>
                            <button class="cal-nav-btn" onclick="mCalCambiarMes(1)" style="font-size:11px;padding:4px 8px;"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);padding:6px 10px 2px;">
                        <span style="text-align:center;font-size:9px;font-weight:700;color:var(--muted);">Do</span>
                        <span style="text-align:center;font-size:9px;font-weight:700;color:var(--muted);">Lu</span>
                        <span style="text-align:center;font-size:9px;font-weight:700;color:var(--muted);">Ma</span>
                        <span style="text-align:center;font-size:9px;font-weight:700;color:var(--muted);">Mi</span>
                        <span style="text-align:center;font-size:9px;font-weight:700;color:var(--muted);">Ju</span>
                        <span style="text-align:center;font-size:9px;font-weight:700;color:var(--muted);">Vi</span>
                        <span style="text-align:center;font-size:9px;font-weight:700;color:var(--muted);">Sa</span>
                    </div>
                    <div class="cal-grid" id="mcal-grid" style="padding:4px 10px 12px;gap:1px;"></div>
                </div>
            </div>
        </div>

        <!-- Columna derecha: Datos cliente -->
        <div>
            <div id="msg-nueva" class="msg-error"></div>

            <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:12px 14px;margin-bottom:16px;min-height:48px;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-info-circle" style="color:var(--accent);font-size:16px;flex-shrink:0;"></i>
                <div>
                    <div id="resumen-sel-mesa" style="font-size:13px;font-weight:700;color:var(--text);">Selecciona una mesa y una fecha</div>
                    <div id="resumen-sel-fecha" style="font-size:12px;color:var(--muted);margin-top:2px;"></div>
                </div>
            </div>

            <!-- Hora -->
            <div class="form-field">
                <label class="form-label"><i class="fas fa-clock" style="margin-right:5px;"></i>Hora de llegada</label>
                <div class="hora-row">
                    <div class="hora-pick">
                        <button onclick="cambiarHora('h',1)"><i class="fas fa-chevron-up"></i></button>
                        <span class="hora-val" id="inp-hora">12</span>
                        <button onclick="cambiarHora('h',-1)"><i class="fas fa-chevron-down"></i></button>
                    </div>
                    <span class="hora-sep">:</span>
                    <div class="hora-pick">
                        <button onclick="cambiarHora('m',1)"><i class="fas fa-chevron-up"></i></button>
                        <span class="hora-val" id="inp-min">00</span>
                        <button onclick="cambiarHora('m',-1)"><i class="fas fa-chevron-down"></i></button>
                    </div>
                    <div class="ampm-btns">
                        <button class="ampm-btn" id="ampm-am" onclick="setAmPm('AM')">AM</button>
                        <button class="ampm-btn activo" id="ampm-pm" onclick="setAmPm('PM')">PM</button>
                    </div>
                </div>
            </div>

            <!-- Datos del cliente -->
            <div class="form-field">
                <label class="form-label">Nombre del cliente</label>
                <input type="text" id="inp-nombre-cliente" class="form-input" maxlength="80" placeholder="Ej: García, María">
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label class="form-label">Número de personas</label>
                    <div class="pax-row">
                        <button class="pax-btn" onclick="cambiarPax(-1)">−</button>
                        <span class="pax-val" id="inp-pax">2</span>
                        <button class="pax-btn" onclick="cambiarPax(1)">+</button>
                        <span class="pax-lbl">personas</span>
                    </div>
                </div>
            </div>

            <!-- Tipo de evento -->
            <div class="form-field">
                <label class="form-label"><i class="fas fa-star" style="color:var(--purple);margin-right:4px;"></i>Motivo / Evento (opcional)</label>
                <select id="inp-evento" class="form-select">
                    <option value="">— Sin motivo especial —</option>
                    <option value="Cumpleaños">🎂 Cumpleaños</option>
                    <option value="Aniversario">💑 Aniversario</option>
                    <option value="Reunión de negocios">💼 Reunión de negocios</option>
                    <option value="Graduación">🎓 Graduación</option>
                    <option value="Boda / Evento nupcial">💍 Boda / Evento nupcial</option>
                    <option value="Cena romántica">🌹 Cena romántica</option>
                    <option value="Reunión familiar">👨‍👩‍👧‍👦 Reunión familiar</option>
                    <option value="Fiesta / Celebración">🎉 Fiesta / Celebración</option>
                    <option value="Otro">✨ Otro</option>
                </select>
            </div>

            <!-- Nota -->
            <div class="form-field">
                <label class="form-label">Nota adicional (opcional)</label>
                <textarea id="inp-nota" class="form-textarea" placeholder="Decoración, preferencias, alergias..." rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button class="btn-sec" onclick="cerrarModal()">Cancelar</button>
        <button class="btn-prim" id="btn-confirmar" onclick="guardarReserva()">
            <i class="fas fa-calendar-check"></i> Confirmar Reserva
        </button>
    </div>
</div>
</div>

<div id="toast"><i class="fas fa-check-circle"></i><span id="toast-msg"></span></div>

<!-- Modal confirmar limpiar pasadas -->
<div class="modal-limpiar-overlay" id="modal-limpiar" onclick="if(event.target===this)cerrarModalLimpiar()">
    <div class="modal-limpiar-box">
        <div class="modal-limpiar-icon">🗑️</div>
        <div class="modal-limpiar-title">¿Limpiar reservas pasadas?</div>
        <div class="modal-limpiar-msg" id="msg-limpiar-detalle">
            Esta acción cancelará permanentemente todas las reservas pasadas del período seleccionado. No se pueden recuperar.
        </div>
        <div class="modal-limpiar-btns">
            <button class="btn-ml-cancel" onclick="cerrarModalLimpiar()">Cancelar</button>
            <button class="btn-ml-confirm" onclick="confirmarLimpiar()">Sí, limpiar</button>
        </div>
    </div>
</div>

<script>
// ═══ ESTADO ════════════════════════════════════════════════════
const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
               'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const MESES_C = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

let todasReservas = [];
let todasMesas    = [];
let filtroActivo  = 'todas';
let calEstado     = { anio: new Date().getFullYear(), mes: new Date().getMonth(), selDia: null };
let mCalEstado    = { anio: new Date().getFullYear(), mes: new Date().getMonth(), selDia: null };
let horaEstado    = { hora: 12, minuto: 0, ampm: 'PM' };
let paxActual     = 2;
let mesaSelId     = null;

// ── Verificar sesión ──────────────────────────────────────────
(function(){
    const ses = JSON.parse(sessionStorage.getItem('pos_usuario') || '{}');
    if (!ses || !ses.rol) { window.location.replace('index.php'); }
    // Todos los roles pueden ver, pero solo admin puede crear/cancelar
    const isAdmin = ses.rol === 'admin';
    const btnNueva = document.querySelector('.btn-prim');
    if (btnNueva && !isAdmin) btnNueva.style.display = 'none';
})();

function toggleTema() {
    const d = document.body.getAttribute('data-dark') !== 'true';
    document.body.setAttribute('data-dark', d);
    localStorage.setItem('pos_tema', d ? 'oscuro' : 'claro');
}
if (localStorage.getItem('pos_tema') === 'oscuro') document.body.setAttribute('data-dark','true');

function cerrarSesion() { sessionStorage.removeItem('pos_usuario'); window.location.href='index.php'; }
function irAtras() {
    const ses = JSON.parse(sessionStorage.getItem('pos_usuario') || '{}');
    if (ses.rol === 'admin') {
        localStorage.setItem('admin_volver_panel','1');
        window.location.href = 'index.php';
    } else {
        localStorage.setItem('ir_a_mesas','1');
        window.location.href = 'index.php';
    }
}

// ═══ CARGAR DATOS ══════════════════════════════════════════════
async function cargarReservas() {
    try {
        const [rRes, mRes] = await Promise.all([
            fetch('reservas.php?accion=todas'),
            fetch('mesas.php?accion=listar')
        ]);
        const rData = await rRes.json();
        const mData = await mRes.json();
        if (rData.ok) todasReservas = rData.reservas || [];
        if (mData.ok) todasMesas    = mData.mesas    || [];
    } catch(e) {
        todasReservas = [];
        todasMesas = [{id_mesa:1,numero_mesa:1},{id_mesa:2,numero_mesa:2},{id_mesa:3,numero_mesa:3},
                      {id_mesa:4,numero_mesa:4},{id_mesa:5,numero_mesa:5},{id_mesa:6,numero_mesa:6},
                      {id_mesa:7,numero_mesa:7},{id_mesa:8,numero_mesa:8}];
    }
    renderCalendario();
    renderLista();
}

// ═══ CALENDARIO PRINCIPAL ══════════════════════════════════════
function renderCalendario() {
    const { anio, mes, selDia } = calEstado;
    document.getElementById('cal-mes-lbl').textContent = `${MESES[mes]} ${anio}`;

    const grid = document.getElementById('cal-grid-main');
    const hoy  = new Date();
    const primerDia = new Date(anio, mes, 1).getDay();
    const diasMes   = new Date(anio, mes+1, 0).getDate();

    // Días con reservas en este mes
    const diasConReservas = new Set();
    todasReservas.forEach(r => {
        if (!r.fecha_reserva || !r.activa) return;
        const f = new Date(r.fecha_reserva.replace(' ','T'));
        if (f.getFullYear() === anio && f.getMonth() === mes)
            diasConReservas.add(f.getDate());
    });

    let html = '';
    for (let i = 0; i < primerDia; i++) html += '<div class="cal-dia cal-vacio"></div>';

    for (let d = 1; d <= diasMes; d++) {
        const esHoy   = anio === hoy.getFullYear() && mes === hoy.getMonth() && d === hoy.getDate();
        const esPas   = new Date(anio,mes,d) < new Date(hoy.getFullYear(),hoy.getMonth(),hoy.getDate());
        const esRes   = diasConReservas.has(d);
        const esSel   = d === selDia;

        let cls = 'cal-dia';
        if (esPas)  cls += ' cal-pasado';
        if (esHoy)  cls += ' cal-hoy';
        if (esSel)  cls += ' cal-selec';
        if (esRes && !esSel) cls += ' cal-reservado';

        const dot = esRes ? '<span class="cal-dot"></span>' : '';
        const onclick = esPas ? '' : `onclick="calSelecDia(${d})"`;
        html += `<div class="${cls}" ${onclick}>${d}${dot}</div>`;
    }
    grid.innerHTML = html;
}

function calCambiarMes(delta) {
    calEstado.mes += delta;
    if (calEstado.mes > 11) { calEstado.mes = 0; calEstado.anio++; }
    if (calEstado.mes < 0)  { calEstado.mes = 11; calEstado.anio--; }
    calEstado.selDia = null;
    renderCalendario();
    renderLista();
}

function calSelecDia(d) {
    calEstado.selDia = d;
    renderCalendario();
    // Filtrar lista por ese día
    filtroActivo = 'dia';
    renderLista();
}

// ═══ FILTRO Y LISTA ════════════════════════════════════════════
function selFiltro(filtro, btn) {
    filtroActivo = filtro;
    calEstado.selDia = null;
    renderCalendario();
    document.querySelectorAll('.lista-tab').forEach(b => b.classList.remove('activo'));
    btn.classList.add('activo');
    renderLista();
}

function renderLista() {
    const hoy = new Date();
    hoy.setHours(0,0,0,0);

    let lista = todasReservas.filter(r => r.activa == 1 || r.activa === '1');

    if (filtroActivo === 'hoy') {
        lista = lista.filter(r => {
            const f = new Date(r.fecha_reserva.replace(' ','T'));
            f.setHours(0,0,0,0);
            return f.getTime() === hoy.getTime();
        });
    } else if (filtroActivo === 'proximas') {
        lista = lista.filter(r => {
            // Próxima = la hora exacta aún no ha llegado
            const f = new Date(r.fecha_reserva.replace(' ','T'));
            return f.getTime() > Date.now();
        });
    } else if (filtroActivo === 'pasadas') {
        const hace7dias = Date.now() - (7 * 24 * 60 * 60 * 1000);
        lista = todasReservas.filter(r => {
            if (parseInt(r.activa) !== 1) return false; // solo activas
            const f = new Date(r.fecha_reserva.replace(' ','T'));
            return f.getTime() < Date.now() && f.getTime() >= hace7dias;
        });
    } else if (filtroActivo === 'dia' && calEstado.selDia !== null) {
        const selF = new Date(calEstado.anio, calEstado.mes, calEstado.selDia);
        selF.setHours(0,0,0,0);
        lista = todasReservas.filter(r => {
            const f = new Date(r.fecha_reserva.replace(' ','T'));
            f.setHours(0,0,0,0);
            return f.getTime() === selF.getTime();
        });
    }

    lista.sort((a,b) => new Date(a.fecha_reserva) - new Date(b.fecha_reserva));

    const ses = JSON.parse(sessionStorage.getItem('pos_usuario') || '{}');
    const isAdmin = ses.rol === 'admin';

    const contenedor = document.getElementById('lista-reservas');
    if (lista.length === 0) {
        contenedor.innerHTML = `<div class="empty-list">
            <i class="fas fa-calendar-xmark"></i>
            <p>No hay reservas para mostrar en este filtro.</p>
        </div>`;
        return;
    }

    // Botón limpiar solo en filtro pasadas
    const btnLimpiar = filtroActivo === 'pasadas' && lista.length > 0
        ? `<button class="btn-limpiar-pasadas" onclick="abrirModalLimpiar()">
               <i class="fas fa-trash-alt"></i> Limpiar reservas pasadas
           </button>`
        : '';

    contenedor.innerHTML = (btnLimpiar ? `<div style="padding:0 0 8px;">${btnLimpiar}</div>` : '') + lista.map(r => {
        const f = new Date(r.fecha_reserva.replace(' ','T'));
        const f0 = new Date(f); f0.setHours(0,0,0,0);
        const esHoy   = f0.getTime() === hoy.getTime();
        const esFut   = f0.getTime() > hoy.getTime();
        const esPas   = f0.getTime() < hoy.getTime();

        const dia  = f.getDate();
        const mes  = MESES_C[f.getMonth()];
        const hora = f.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });

        const cardCls = esHoy ? 'reserva-card card-hoy' : esFut ? 'reserva-card card-proxima' : 'reserva-card card-pasada';
        const badgeCls = esHoy ? 'rc-estado-badge badge-hoy' : esFut ? 'rc-estado-badge badge-proxima' : 'rc-estado-badge badge-pasada';
        const badgeTxt = esHoy ? '✅ Hoy' : esFut ? '📅 Próxima' : '⏪ Pasada';

        const evento = r.evento ? `<div class="rc-evento"><i class="fas fa-star"></i>${r.evento}</div>` : '';
        const nota   = r.nota   ? `<div class="rc-nota">"${esc(r.nota)}"</div>` : '';
        const mesa   = r.numero_mesa ? `<span class="rc-mesa-tag">Mesa ${r.numero_mesa}</span>` : '<span class="rc-mesa-tag" style="background:var(--muted);">Sin mesa asignada</span>';

        const acciones = isAdmin && r.activa == 1 ? `
        <div class="rc-actions">
            <button class="btn-rc btn-rc-danger" onclick="cancelarReserva(${r.id_reserva},'${esc(r.nombre_cliente)}')">
                <i class="fas fa-calendar-xmark"></i> Cancelar reserva
            </button>
        </div>` : '';

        return `<div class="${cardCls}">
            <div class="rc-top">
                <div class="rc-fecha-grande">
                    <div class="rc-dia">${dia}</div>
                    <div class="rc-mes">${mes}</div>
                </div>
                <div class="rc-info">
                    <div class="rc-cliente">${esc(r.nombre_cliente)}</div>
                    <div class="rc-meta">
                        <span class="rc-chip"><i class="fas fa-clock"></i> ${hora}</span>
                        <span class="rc-chip"><i class="fas fa-users"></i> ${r.num_personas} pax</span>
                        ${mesa}
                    </div>
                </div>
                <span class="${badgeCls}">${badgeTxt}</span>
            </div>
            ${evento}
            ${nota}
            ${acciones}
        </div>`;
    }).join('');
}

// ═══ MODAL NUEVA RESERVA ══════════════════════════════════════
function abrirModalNueva() {
    mesaSelId = null;
    mCalEstado = { anio: new Date().getFullYear(), mes: new Date().getMonth(), selDia: null };
    horaEstado = { hora: 12, minuto: 0, ampm: 'PM' };
    paxActual  = 2;

    document.getElementById('inp-nombre-cliente').value = '';
    document.getElementById('inp-nota').value  = '';
    document.getElementById('inp-evento').value = '';
    document.getElementById('inp-pax').textContent = '2';
    document.getElementById('msg-nueva').style.display = 'none';
    document.getElementById('resumen-sel-mesa').textContent = 'Selecciona una mesa y una fecha';
    document.getElementById('resumen-sel-fecha').textContent = '';

    syncHora();
    renderMesasPicker();
    mCalRender();
    document.getElementById('modal-nueva').classList.add('open');
}

function cerrarModal() { document.getElementById('modal-nueva').classList.remove('open'); }

// ── Limpiar pasadas ─────────────────────────────────────────
function abrirModalLimpiar() {
    const ahora    = Date.now();
    const hace7dias = ahora - (7 * 24 * 60 * 60 * 1000);
    // Filtrar: activas Y cuya hora ya pasó Y dentro de 7 días
    // Comparar activa con == (loose) para cubrir int 1, string "1", boolean true
    const pasadas = todasReservas.filter(r => {
        if (!r.fecha_reserva) return false;
        const activa = parseInt(r.activa);
        if (activa !== 1) return false;
        const ts = new Date(r.fecha_reserva.replace(' ','T')).getTime();
        return ts < ahora && ts >= hace7dias;
    });
    console.log('[Limpiar] todasReservas:', todasReservas.length,
                '| pasadas encontradas:', pasadas.length,
                '| ahora:', new Date(ahora).toLocaleTimeString());
    todasReservas.forEach(r => {
        const ts = new Date((r.fecha_reserva||'').replace(' ','T')).getTime();
        console.log('  reserva', r.id_reserva, '| activa:', r.activa,
                    '| fecha:', r.fecha_reserva, '| ts<ahora:', ts < ahora,
                    '| ts>=hace7d:', ts >= hace7dias);
    });
    document.getElementById('msg-limpiar-detalle').textContent =
        pasadas.length > 0
            ? `Se cancelarán ${pasadas.length} reserva${pasadas.length !== 1 ? 's' : ''} pasada${pasadas.length !== 1 ? 's' : ''} de los últimos 7 días. Esta acción no se puede deshacer.`
            : 'No se encontraron reservas pasadas activas en los últimos 7 días.';
    document.getElementById('modal-limpiar').classList.add('open');
}

function cerrarModalLimpiar() {
    document.getElementById('modal-limpiar').classList.remove('open');
}

async function confirmarLimpiar() {
    cerrarModalLimpiar();
    const hace7dias = Date.now() - (7 * 24 * 60 * 60 * 1000);
    // Solo cancelar las que están activas Y cuya hora ya pasó
    const pasadas = todasReservas.filter(r => {
        if (r.activa != 1 && r.activa !== '1') return false; // ya canceladas
        const f = new Date(r.fecha_reserva.replace(' ','T'));
        return f.getTime() < Date.now() && f.getTime() >= hace7dias;
    });
    if (pasadas.length === 0) {
        alert('No hay reservas pasadas activas para limpiar.');
        return;
    }
    let borradas = 0;
    for (const r of pasadas) {
        try {
            const res = await fetch('reservas.php?accion=cancelar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_reserva: parseInt(r.id_reserva) })
            });
            const data = await res.json();
            if (data.ok) borradas++;
        } catch(e) {}
    }
    await cargarReservas();
    renderLista();
    renderCalendario();
    alert(`✅ Se limpiaron ${borradas} reserva${borradas !== 1 ? 's' : ''} pasadas correctamente.`);
}
function cerrarModalSi(e) { if (e.target.classList.contains('modal-overlay')) cerrarModal(); }

// Mesas visuales
function renderMesasPicker() {
    const hoy = new Date(); hoy.setHours(0,0,0,0);
    const picker = document.getElementById('mesas-picker');
    picker.innerHTML = todasMesas.map(m => {
        const estaOcupada   = m.estado === 'ocupada' || m.estado === 'cobro';
        const tieneReservaHoy = todasReservas.some(r => {
            if (!r.activa || r.id_mesa_fk != m.id_mesa) return false;
            const f = new Date(r.fecha_reserva.replace(' ','T')); f.setHours(0,0,0,0);
            return f.getTime() === hoy.getTime();
        });

        let cls = 'mesa-pick';
        let title = `Mesa ${m.numero_mesa}`;
        if (estaOcupada)    { cls += ' ocupada'; title += ' (ocupada)'; }
        if (tieneReservaHoy){ cls += ' reservada-hoy'; title += ' (reserva hoy)'; }
        if (mesaSelId === m.id_mesa) cls += ' seleccionada';

        return `<div class="${cls}" title="${title}" onclick="${estaOcupada ? '' : `selMesa(${m.id_mesa},${m.numero_mesa})`}">
            <i class="fas fa-utensils"></i>
            <span>Mesa ${m.numero_mesa}</span>
        </div>`;
    }).join('');
}

function selMesa(id, num) {
    mesaSelId = id;
    document.getElementById('inp-mesa-id').value = id;
    renderMesasPicker();
    actualizarResumen();
}

// Mini calendario modal
function mCalRender() {
    const { anio, mes, selDia } = mCalEstado;
    document.getElementById('mcal-mes-lbl').textContent = `${MESES[mes]} ${anio}`;
    const grid = document.getElementById('mcal-grid');
    const hoy = new Date(); hoy.setHours(0,0,0,0);
    const primerDia = new Date(anio,mes,1).getDay();
    const diasMes = new Date(anio,mes+1,0).getDate();

    const diasRes = new Set();
    todasReservas.forEach(r => {
        if (!r.activa) return;
        const f = new Date(r.fecha_reserva.replace(' ','T'));
        if (f.getFullYear()===anio && f.getMonth()===mes && r.id_mesa_fk==mesaSelId)
            diasRes.add(f.getDate());
    });

    let html = '';
    for (let i=0; i<primerDia; i++) html += '<div class="cal-dia cal-vacio" style="font-size:11px;"></div>';
    for (let d=1; d<=diasMes; d++) {
        const esPas = new Date(anio,mes,d) < hoy;
        const esHoy = !esPas && new Date(anio,mes,d).getTime()===hoy.getTime();
        const esSel = d === selDia;
        const esRes = diasRes.has(d);
        let cls = 'cal-dia';
        if (esPas) cls += ' cal-pasado';
        if (esHoy) cls += ' cal-hoy';
        if (esSel) cls += ' cal-selec';
        const dot = esRes ? '<span class="cal-dot"></span>' : '';
        const onclick = esPas ? '' : `onclick="mCalSelDia(${d})"`;
        html += `<div class="${cls}" ${onclick} style="font-size:11px;">${d}${dot}</div>`;
    }
    grid.innerHTML = html;
}

function mCalCambiarMes(delta) {
    mCalEstado.mes += delta;
    if (mCalEstado.mes > 11) { mCalEstado.mes=0; mCalEstado.anio++; }
    if (mCalEstado.mes < 0)  { mCalEstado.mes=11; mCalEstado.anio--; }
    mCalEstado.selDia = null;
    mCalRender(); actualizarResumen();
}
function mCalSelDia(d) { mCalEstado.selDia = d; mCalRender(); actualizarResumen(); }

function actualizarResumen() {
    const mesaEl = document.getElementById('resumen-sel-mesa');
    const fechaEl = document.getElementById('resumen-sel-fecha');
    mesaEl.textContent = mesaSelId
        ? `Mesa ${todasMesas.find(m=>m.id_mesa==mesaSelId)?.numero_mesa ?? mesaSelId} seleccionada ✓`
        : 'Selecciona una mesa';
    fechaEl.textContent = mCalEstado.selDia
        ? `${mCalEstado.selDia} de ${MESES[mCalEstado.mes]} ${mCalEstado.anio} — ${document.getElementById('inp-hora').textContent}:${document.getElementById('inp-min').textContent} ${horaEstado.ampm}`
        : 'Selecciona una fecha';
}

// Hora
function cambiarHora(tipo, delta) {
    if (tipo === 'h') {
        horaEstado.hora += delta;
        if (horaEstado.hora > 12) horaEstado.hora = 1;
        if (horaEstado.hora < 1)  horaEstado.hora = 12;
    } else {
        horaEstado.minuto += delta * 15;
        if (horaEstado.minuto >= 60) horaEstado.minuto = 0;
        if (horaEstado.minuto < 0)   horaEstado.minuto = 45;
    }
    syncHora(); actualizarResumen();
}
function setAmPm(v) {
    horaEstado.ampm = v;
    document.getElementById('ampm-am').classList.toggle('activo', v==='AM');
    document.getElementById('ampm-pm').classList.toggle('activo', v==='PM');
    actualizarResumen();
}
function syncHora() {
    document.getElementById('inp-hora').textContent = horaEstado.hora;
    document.getElementById('inp-min').textContent  = String(horaEstado.minuto).padStart(2,'0');
    document.getElementById('ampm-am').classList.toggle('activo', horaEstado.ampm==='AM');
    document.getElementById('ampm-pm').classList.toggle('activo', horaEstado.ampm==='PM');
}

function cambiarPax(d) {
    paxActual = Math.max(1, Math.min(30, paxActual + d));
    document.getElementById('inp-pax').textContent = paxActual;
}

// Guardar reserva
async function guardarReserva() {
    const nombre = document.getElementById('inp-nombre-cliente').value.trim();
    if (!mesaSelId) { mostrarError('Selecciona una mesa'); return; }
    if (!mCalEstado.selDia) { mostrarError('Selecciona una fecha'); return; }
    if (!nombre) { mostrarError('Escribe el nombre del cliente'); return; }

    let h24 = horaEstado.hora;
    if (horaEstado.ampm === 'PM' && h24 !== 12) h24 += 12;
    if (horaEstado.ampm === 'AM' && h24 === 12) h24 = 0;

    const mes2 = String(mCalEstado.mes + 1).padStart(2,'0');
    const dia2 = String(mCalEstado.selDia).padStart(2,'0');
    const h2   = String(h24).padStart(2,'0');
    const m2   = String(horaEstado.minuto).padStart(2,'0');
    const fechaISO = `${mCalEstado.anio}-${mes2}-${dia2} ${h2}:${m2}:00`;

    const evento = document.getElementById('inp-evento').value;
    const nota   = document.getElementById('inp-nota').value.trim();
    const notaFinal = [evento, nota].filter(Boolean).join(' — ');

    setBtnLoading(true);
    try {
        const res  = await fetch('reservas.php?accion=guardar', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                nombre: nombre, fecha_reserva: fechaISO,
                num_personas: paxActual, nota: notaFinal,
                notif_minutos: 15, id_mesa: mesaSelId,
                evento: evento
            })
        });
        const data = await res.json();
        if (data.ok) {
            cerrarModal();
            await cargarReservas();
            toast(`Reserva confirmada para ${nombre} ✓`);
        } else {
            mostrarError(data.error || 'Error al guardar');
        }
    } catch(e) {
        mostrarError('Sin conexión al servidor');
    }
    setBtnLoading(false);
}

async function cancelarReserva(id, nombre) {
    if (!confirm(`¿Cancelar la reserva de "${nombre}"?`)) return;
    try {
        const res  = await fetch('reservas.php?accion=cancelar', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ id_reserva: id })
        });
        const data = await res.json();
        if (data.ok) { await cargarReservas(); toast('Reserva cancelada'); }
        else toast(data.error || 'Error', true);
    } catch(e) { toast('Sin conexión', true); }
}

// Utils
function mostrarError(txt) {
    const el = document.getElementById('msg-nueva');
    el.textContent = txt; el.style.display = 'block';
}
function setBtnLoading(on) {
    const b = document.getElementById('btn-confirmar');
    b.disabled = on;
    b.innerHTML = on ? '<i class="fas fa-spinner fa-spin"></i> Guardando...' : '<i class="fas fa-calendar-check"></i> Confirmar Reserva';
}
function toast(msg, err=false) {
    const el = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = msg;
    el.className = 'visible' + (err ? ' err' : '');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('visible'), 3200);
}
function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/'/g,'&#39;');
}

document.addEventListener('keydown', e => { if (e.key==='Escape') cerrarModal(); });

// Init
cargarReservas();
</script>
</body>
</html>
