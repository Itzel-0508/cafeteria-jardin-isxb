<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🍳 Cocina — Jardín</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap');

:root {
    --bg-app:#F0F4EE; --bg-panel:#F8FAF7; --bg-card:#FFFFFF;
    --text-main:#1C2B18; --text-muted:#728A69;
    --accent:#4A7A3D; --accent-light:#E6EFE4; --accent-hover:#376030; --accent-2:#7A5C1E;
    --border:#D4E0CF; --shadow:0 1px 3px rgba(28,43,24,.07);
    --warn:#B8860B; --warn-bg:rgba(184,134,11,.1);
    --danger:#A0320A; --danger-bg:rgba(160,50,10,.1);
    --ok:#2E7040; --ok-bg:rgba(46,112,64,.1);
    --prep:#1A5A8A; --prep-bg:rgba(26,90,138,.1);
    --cobro:#6B2FAA; --cobro-bg:rgba(107,47,170,.1);
}
[data-theme="dark"] {
    --bg-app:#0D1410; --bg-panel:#141C11; --bg-card:#1B2618;
    --text-main:#CDE0C6; --text-muted:#567A4D;
    --accent:#6BBF58; --accent-light:#1A2D16; --accent-hover:#83D46E; --accent-2:#C4A84A;
    --border:#202E1C; --shadow:0 1px 4px rgba(0,0,0,.55);
    --warn:#D4A020; --warn-bg:rgba(212,160,32,.12);
    --danger:#C84040; --danger-bg:rgba(200,64,64,.15);
    --ok:#3DAF3D; --ok-bg:rgba(61,175,61,.12);
    --prep:#5A9ADF; --prep-bg:rgba(90,154,223,.12);
    --cobro:#A06AE8; --cobro-bg:rgba(160,106,232,.15);
}
[data-theme="daltonico"] {
    --bg-app:#F3F5FA; --bg-panel:#FFFFFF; --bg-card:#FFFFFF;
    --text-main:#18202E; --text-muted:#506088;
    --accent:#1A5FAB; --accent-light:#E0EAF8; --accent-hover:#134A88; --accent-2:#C48A0A;
    --border:#C8D0E4; --shadow:0 1px 3px rgba(24,32,46,.08);
    --warn:#C48A0A; --warn-bg:rgba(196,138,10,.1);
    --danger:#18202E; --danger-bg:rgba(24,32,46,.1);
    --ok:#1A5FAB; --ok-bg:rgba(26,95,171,.1);
    --prep:#C48A0A; --prep-bg:rgba(196,138,10,.1);
    --cobro:#7A2E8A; --cobro-bg:rgba(122,46,138,.1);
}

*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'DM Sans',system-ui,sans-serif; background:var(--bg-app); color:var(--text-main); min-height:100vh; transition:background .3s,color .3s; -webkit-font-smoothing:antialiased; }
body::before { content:''; display:block; height:3px; background:linear-gradient(90deg,var(--accent),var(--accent-2)); position:fixed; top:0; left:0; right:0; z-index:9999; }

/* ── NAV BAR ── */
.nav-bar {
    position:sticky; top:3px; z-index:200;
    background:var(--bg-panel); border-bottom:1px solid var(--border);
    padding:0 16px; height:48px;
    display:flex; align-items:center; gap:8px;
    box-shadow:var(--shadow); overflow-x:auto;
}
.nav-title { font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); margin-right:6px; flex-shrink:0; }
.nav-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 14px; border-radius:6px; border:1px solid var(--border);
    background:transparent; color:var(--text-muted);
    font-size:11px; font-weight:600; letter-spacing:.5px; text-transform:uppercase;
    cursor:pointer; text-decoration:none; transition:all .15s; white-space:nowrap; flex-shrink:0;
    font-family:'DM Sans',sans-serif;
}
.nav-btn:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-light); }
.nav-btn.active { background:var(--warn); color:#fff; border-color:var(--warn); }
.nav-btn.nav-barra { border-color:var(--prep); color:var(--prep); }
.nav-btn.nav-barra:hover { background:var(--prep); color:#fff; }
.nav-btn.nav-cobro { border-color:var(--cobro); color:var(--cobro); }
.nav-btn.nav-cobro:hover { background:var(--cobro); color:#fff; }
.nav-btn.nav-back { border-color:var(--border); }
.nav-sep { width:1px; height:24px; background:var(--border); flex-shrink:0; margin:0 4px; }

/* ── TOPBAR ── */
.topbar { position:sticky; top:51px; z-index:100; background:var(--bg-panel); border-bottom:1px solid var(--border); padding:0 22px; height:56px; display:flex; align-items:center; justify-content:space-between; box-shadow:var(--shadow); transition:background .3s; }
.tb-left { display:flex; align-items:center; gap:10px; }
.tb-icon { font-size:22px; }
.tb-name { font-size:12px; font-weight:600; letter-spacing:2px; text-transform:uppercase; color:var(--accent); }
.tb-sub  { font-size:9px; color:var(--text-muted); letter-spacing:.8px; text-transform:uppercase; }
.tb-stats { display:flex; gap:16px; }
.tb-stat .n   { font-size:20px; font-weight:700; line-height:1; color:var(--accent); font-family:'DM Mono',monospace; }
.tb-stat .n.w { color:var(--warn); }
.tb-stat .n.g { color:var(--ok); }
.tb-stat .l   { font-size:9px; letter-spacing:.8px; color:var(--text-muted); text-transform:uppercase; }
.tb-right { display:flex; align-items:center; gap:10px; }
.tb-clock { font-family:'DM Mono',monospace; font-size:16px; color:var(--accent); letter-spacing:2px; }
.btn-tema { background:transparent; border:1px solid var(--border); color:var(--text-muted); padding:6px 12px; border-radius:4px; cursor:pointer; font-size:13px; transition:all .15s; }
.btn-tema:hover { background:var(--accent-light); color:var(--accent); border-color:var(--accent); }

/* ── FILTROS ── */
.filtros { display:flex; gap:8px; padding:10px 22px; background:var(--bg-app); flex-wrap:wrap; border-bottom:1px solid var(--border); transition:background .3s; }
.fil { padding:4px 13px; border-radius:20px; border:1px solid var(--border); font-size:10px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; cursor:pointer; color:var(--text-muted); background:transparent; transition:all .15s; font-family:'DM Sans',sans-serif; }
.fil:hover { border-color:var(--accent); color:var(--accent); }
.fil.on  { background:var(--accent);  color:var(--bg-panel); border-color:var(--accent); }
.fil.w   { border-color:var(--warn);  color:var(--warn); }
.fil.w.on{ background:var(--warn);    color:var(--bg-panel); }
.fil.g   { border-color:var(--ok);    color:var(--ok); }
.fil.g.on{ background:var(--ok);      color:var(--bg-panel); }

/* ── GRID / CARD ── */
.grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:14px; padding:16px 22px 40px; }
.card { background:var(--bg-card); border:2px solid var(--border); border-radius:8px; overflow:hidden; display:flex; flex-direction:column; animation:slideIn .3s ease; transition:border-color .3s,box-shadow .3s,background .3s; box-shadow:var(--shadow); }
@keyframes slideIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.card.s-pendiente  { border-color:var(--border); }
.card.s-preparando { border-color:var(--warn);    box-shadow:0 0 14px var(--warn-bg); }
.card.s-listo      { border-color:var(--ok);      box-shadow:0 0 18px var(--ok-bg); }
.card.s-demorado   { border-color:var(--danger);   box-shadow:0 0 14px var(--danger-bg); animation:pulseCard 2s infinite; }
@keyframes pulseCard{0%,100%{box-shadow:0 0 8px var(--danger-bg)}50%{box-shadow:0 0 22px var(--danger-bg)}}

.ch { padding:11px 13px 9px; display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid var(--border); background:var(--bg-panel); transition:background .3s; }
.ch.bg-r { background:var(--warn-bg); }
.ch.bg-l { background:var(--ok-bg); }
.ch.bg-d { background:var(--danger-bg); }

.mesa    { font-size:16px; font-weight:700; color:var(--text-main); line-height:1; }
.cid     { font-family:'DM Mono',monospace; font-size:8px; color:var(--text-muted); margin-top:3px; }
.destino { font-size:9px; font-weight:700; padding:2px 8px; border-radius:10px; background:var(--accent-light); color:var(--accent); margin-top:4px; display:inline-block; letter-spacing:.5px; }
.destino.llevar { background:var(--warn-bg); color:var(--warn); }
.badge { font-size:8px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; padding:2px 8px; border-radius:10px; margin-top:5px; display:inline-block; }
.bd-p { background:var(--accent-light); color:var(--accent); }
.bd-r { background:var(--warn-bg); color:var(--warn); }
.bd-l { background:var(--ok-bg); color:var(--ok); }
.bd-d { background:var(--danger-bg); color:var(--danger); }
.hora  { font-size:10px; color:var(--text-muted); }
.timer { font-family:'DM Mono',monospace; font-size:24px; font-weight:700; line-height:1; }
.timer.ok { color:var(--accent); } .timer.w { color:var(--warn); } .timer.danger { color:var(--danger); }

/* Timer de envío */
.timer-envio-wrap { display:flex; align-items:center; gap:5px; margin-top:3px; }
.timer-envio-lbl { font-size:9px; color:var(--text-muted); letter-spacing:.5px; }
.timer-envio { font-family:'DM Mono',monospace; font-size:11px; font-weight:600; color:var(--text-muted); }

/* Banners cruzados */
.banner-cruzado { margin:0 12px 4px; padding:8px 12px; border-radius:6px; font-size:11px; display:flex; align-items:center; gap:8px; font-weight:500; }
.banner-barra-ok  { background:rgba(26,90,138,.1); border:1px solid var(--prep); color:var(--prep); }
.banner-barra-apurar { background:var(--danger-bg); border:1px solid var(--danger); color:var(--danger); animation:pulseBanner 1.5s infinite; }
@keyframes pulseBanner{0%,100%{opacity:1}50%{opacity:.65}}

/* Progreso */
.prog-wrap { margin:6px 13px 2px; height:6px; background:var(--border); border-radius:4px; position:relative; overflow:hidden; }
.prog-bar  { height:100%; background:var(--ok); border-radius:4px; transition:width .4s ease; }
.prog-label{ position:absolute; right:0; top:-16px; font-size:9px; color:var(--text-muted); font-family:'DM Mono',monospace; }

/* Items */
.item { padding:9px 13px; border-bottom:1px solid var(--border); transition:background .2s; }
.item:last-child { border-bottom:none; }
.item-listo    { background:var(--ok-bg); }
.item-preparando { background:var(--warn-bg); }
.item-left  { display:flex; align-items:flex-start; gap:9px; }
.item-right { display:flex; align-items:center; gap:8px; margin-top:6px; justify-content:flex-end; }
.icant  { font-family:'DM Mono',monospace; font-size:15px; font-weight:700; color:var(--accent); min-width:22px; line-height:1.3; flex-shrink:0; }
.iinfo  { flex:1; }
.iinfo-top { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.inombre{ font-size:13px; font-weight:500; color:var(--text-main); line-height:1.3; }
.inota  { font-size:10px; color:var(--text-muted); margin-top:2px; font-style:italic; }
.item-dest-badge { font-size:9px; font-weight:700; letter-spacing:.4px; padding:2px 7px; border-radius:10px; white-space:nowrap; display:inline-flex; align-items:center; gap:3px; flex-shrink:0; }
.item-aqui   { background:var(--accent-light); color:var(--accent); }
.item-llevar { background:rgba(122,92,30,.12); color:var(--accent-2); border:1px solid var(--accent-2); }
.item-tiempo { font-family:'DM Mono',monospace; font-size:11px; font-weight:600; color:var(--text-muted); background:var(--bg-app); padding:2px 7px; border-radius:6px; border:1px solid var(--border); white-space:nowrap; flex-shrink:0; }
.iestado { font-size:9px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:4px 10px; border-radius:20px; white-space:nowrap; cursor:pointer; border:none; font-family:'DM Sans',sans-serif; transition:all .15s; display:inline-flex; align-items:center; gap:5px; }
.iestado:hover { filter:brightness(0.9) saturate(1.2); transform:scale(1.03); }
.ie-p { background:var(--accent-light); color:var(--accent); }
.ie-r { background:var(--warn-bg); color:var(--warn); border:1px solid var(--warn); }
.ie-l { background:var(--ok-bg); color:var(--ok); border:1px solid var(--ok); }

/* Acciones */
.actions { padding:9px 13px; display:flex; gap:7px; border-top:1px solid var(--border); flex-wrap:wrap; }
.kbtn { flex:1; padding:10px 7px; border:none; border-radius:6px; font-size:10px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; cursor:pointer; transition:all .15s; display:flex; align-items:center; justify-content:center; gap:6px; font-family:'DM Sans',sans-serif; min-width:100px; }
.kbtn:active { transform:scale(.97); }
.btn-prep     { background:var(--accent-light); color:var(--accent); border:1px solid var(--accent); }
.btn-prep:hover { background:var(--accent); color:#fff; }
.btn-listo    { background:var(--ok); color:#fff; }
.btn-listo:hover { filter:brightness(1.1); }
.btn-avisa    { background:var(--warn-bg); color:var(--warn); border:1px solid var(--warn); }
.btn-avisa:hover { background:var(--warn); color:#fff; }
.btn-entregado { background:var(--prep-bg); color:var(--prep); border:1px solid var(--prep); }
.btn-entregado:hover { background:var(--prep); color:#fff; }
/* Botón apurar barra */
.btn-apurar-barra { background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger); }
.btn-apurar-barra:hover { background:var(--danger); color:#fff; }
/* Botón avisar barra (lista comida) */
.btn-avisa-barra { background:var(--prep-bg); color:var(--prep); border:1px solid var(--prep); }
.btn-avisa-barra:hover { background:var(--prep); color:#fff; }
/* Cobro */
.btn-cobro { background:var(--cobro); color:#fff; }
.btn-cobro:hover { filter:brightness(1.12); }
.btn-cobro:disabled { opacity:.45; cursor:not-allowed; filter:none; }

/* ── Sección comanda lista ── */
.seccion-comanda-lista {
    margin:0 13px 8px;
    border:1px solid var(--ok);
    border-radius:8px;
    overflow:hidden;
    background:var(--ok-bg);
}
.scl-header { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:rgba(46,112,64,.15); border-bottom:1px solid var(--ok); }
.scl-title { font-size:11px; font-weight:700; color:var(--ok); letter-spacing:.5px; display:flex; align-items:center; gap:6px; }
.scl-body { padding:8px 12px; }
.scl-checklist { list-style:none; display:flex; flex-direction:column; gap:5px; }
.scl-item { display:flex; align-items:center; gap:8px; font-size:11px; color:var(--text-main); cursor:pointer; padding:4px 6px; border-radius:5px; transition:background .15s; }
.scl-item:hover { background:rgba(46,112,64,.1); }
.scl-item input[type=checkbox] { width:14px; height:14px; accent-color:var(--ok); flex-shrink:0; cursor:pointer; }
.scl-item.checked { text-decoration:line-through; color:var(--text-muted); }
.scl-add { display:flex; gap:6px; margin-top:8px; }
.scl-add input { flex:1; padding:5px 9px; border-radius:5px; border:1px solid var(--border); background:var(--bg-app); color:var(--text-main); font-size:11px; font-family:'DM Sans',sans-serif; outline:none; }
.scl-add input:focus { border-color:var(--ok); }
.scl-add button { padding:5px 10px; border:none; border-radius:5px; background:var(--ok); color:#fff; font-size:11px; font-weight:700; cursor:pointer; }
.scl-listo-badge { font-size:10px; font-weight:700; padding:2px 9px; border-radius:10px; background:var(--ok); color:#fff; }
.scl-pendiente-badge { font-size:10px; font-weight:700; padding:2px 9px; border-radius:10px; background:var(--warn-bg); color:var(--warn); border:1px solid var(--warn); }

/* ── Notif badge bebidas ── */
#badge-beb { position:fixed; top:3px; left:50%; transform:translateX(-50%); background:var(--prep); color:#fff; padding:10px 24px; font-size:12px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; border-radius:0 0 12px 12px; display:none; z-index:300; box-shadow:0 6px 20px rgba(0,0,0,.25); animation:dropIn .4s ease; align-items:center; gap:12px; }
@keyframes dropIn{from{opacity:0;transform:translateX(-50%) translateY(-20px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
#badge-beb.show { display:flex; }

/* ── TOAST NOTIFICACIÓN ── */
.notif-toast { position:fixed; bottom:22px; right:22px; background:var(--bg-card); border-radius:12px; box-shadow:0 8px 28px rgba(0,0,0,.18); padding:14px 16px; display:none; z-index:500; min-width:280px; max-width:360px; animation:slideInRight .3s ease; border-left:4px solid var(--ok); }
.notif-toast.show { display:flex; align-items:flex-start; gap:12px; }
.notif-toast.tipo-barra { border-left-color:var(--prep); }
.notif-toast.tipo-mesero { border-left-color:var(--ok); }
.notif-toast.tipo-warn   { border-left-color:var(--warn); }
.notif-toast.tipo-apurar { border-left-color:var(--danger); }
.nt-icon { font-size:20px; flex-shrink:0; margin-top:2px; }
.nt-body { flex:1; }
.nt-title { font-size:12px; font-weight:700; color:var(--text-main); margin-bottom:2px; }
.nt-desc  { font-size:11px; color:var(--text-muted); line-height:1.4; }
.nt-close { background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:14px; padding:2px 5px; }
@keyframes slideInRight{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}

.empty { grid-column:1/-1; display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:280px; gap:14px; color:var(--text-muted); }
.empty i { font-size:56px; opacity:.25; }
.empty p { font-size:12px; letter-spacing:1.5px; text-transform:uppercase; opacity:.4; }
#toast { position:fixed; bottom:22px; left:50%; transform:translateX(-50%) translateY(100px); background:var(--ok); color:#fff; padding:10px 20px; border-radius:5px; font-size:12px; font-weight:600; transition:transform .3s; z-index:9999; pointer-events:none; box-shadow:0 4px 16px rgba(0,0,0,.2); }
#toast.show { transform:translateX(-50%) translateY(0); }

/* Panel lateral barra */
#panel-barra-estado { position:fixed; right:0; top:107px; bottom:0; width:220px; background:var(--bg-panel); border-left:1px solid var(--border); transform:translateX(100%); transition:transform .3s ease; z-index:150; overflow-y:auto; display:flex; flex-direction:column; }
#panel-barra-estado.visible { transform:translateX(0); }
.panel-barra-header { padding:12px 14px; border-bottom:1px solid var(--border); background:rgba(26,90,138,0.08); display:flex; align-items:center; gap:8px; font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--prep); position:sticky; top:0; }
.panel-barra-toggle { position:fixed; right:0; top:170px; background:var(--prep); color:#fff; border:none; border-radius:8px 0 0 8px; padding:10px 8px; cursor:pointer; z-index:151; box-shadow:-2px 2px 8px rgba(0,0,0,.15); transition:right .3s ease; font-size:9px; font-weight:700; font-family:'DM Sans',sans-serif; display:flex; flex-direction:column; align-items:center; gap:4px; letter-spacing:1px; }
.panel-barra-item { padding:9px 14px; border-bottom:1px solid var(--border); font-size:11px; }
.pbi-mesa { font-weight:700; color:var(--text-main); margin-bottom:3px; }
.pbi-items { color:var(--text-muted); font-size:10px; line-height:1.5; }
.pbi-status { font-size:9px; font-weight:700; letter-spacing:.5px; padding:2px 7px; border-radius:8px; display:inline-block; margin-top:4px; }
.pbi-pendiente { background:var(--accent-light); color:var(--accent); }
.pbi-preparando { background:var(--warn-bg); color:var(--warn); }
.pbi-listo { background:var(--ok-bg); color:var(--ok); }

[data-theme="daltonico"] .card.s-preparando { border-left-width:5px; }
[data-theme="daltonico"] .card.s-listo      { border-left-width:5px; }
[data-theme="daltonico"] .card.s-demorado   { border-left-width:5px; border-style:dashed; }
.btn-cancelar { background:rgba(100,100,100,.08); color:var(--text-muted); border:1px solid var(--border); flex:0 0 auto; min-width:90px; max-width:120px; }
.btn-cancelar:hover { background:var(--danger-bg); color:var(--danger); border-color:var(--danger); }
.hist-panel { position:fixed; inset:0; z-index:300; background:rgba(0,0,0,.48); display:none; justify-content:flex-end; backdrop-filter:blur(4px); }
.hist-panel.visible { display:flex; }
.hist-inner { width:420px; max-width:100vw; background:var(--bg-panel); display:flex; flex-direction:column; animation:slideInRight .28s ease; box-shadow:-8px 0 32px rgba(0,0,0,.22); }
.hist-header { padding:18px 20px 14px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; background:linear-gradient(135deg,#8B2000,#B07D12); color:#fff; }
.hist-header-left { display:flex; align-items:center; gap:10px; }
.hist-header-ico { font-size:26px; }
.hist-header-title { font-size:15px; font-weight:700; }
.hist-header-sub { font-size:10px; opacity:.75; letter-spacing:.5px; margin-top:1px; }
.hist-close { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.2); border-radius:8px; padding:7px 10px; cursor:pointer; color:#fff; font-size:14px; }
.hist-close:hover { background:rgba(255,255,255,.28); }
.hist-toolbar { display:flex; gap:8px; padding:12px 16px; border-bottom:1px solid var(--border); background:var(--bg-app); }
.hist-fil { padding:4px 12px; border-radius:16px; border:1px solid var(--border); background:transparent; font-size:10px; font-weight:600; color:var(--text-muted); cursor:pointer; transition:all .15s; font-family:'DM Sans',sans-serif; }
.hist-fil.on { background:var(--accent); border-color:var(--accent); color:#fff; }
.hist-summary { padding:12px 16px 8px; display:flex; gap:12px; border-bottom:1px solid var(--border); }
.hist-sum-card { flex:1; background:var(--bg-card); border:1px solid var(--border); border-radius:10px; padding:10px 12px; text-align:center; }
.hist-sum-n { font-size:22px; font-weight:800; font-family:'DM Mono',monospace; color:var(--accent); line-height:1; }
.hist-sum-l { font-size:9px; color:var(--text-muted); letter-spacing:.6px; text-transform:uppercase; margin-top:3px; }
.hist-scroll { flex:1; overflow-y:auto; padding:14px 16px; }
.hist-empty { text-align:center; padding:56px 0; color:var(--text-muted); }
.hist-empty i { font-size:42px; display:block; margin-bottom:14px; opacity:.25; }
.hist-entry { border-left:3px solid var(--ok); background:var(--bg-card); border-radius:0 10px 10px 0; padding:11px 14px; margin-bottom:8px; box-shadow:var(--shadow); transition:transform .15s; }
.hist-entry:hover { transform:translateX(3px); }
.hist-entry.cancelado { border-left-color:var(--danger); }
.hist-entry-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:5px; }
.hist-entry-mesa { font-size:14px; font-weight:700; }
.hist-entry-hora { font-family:'DM Mono',monospace; font-size:11px; color:var(--text-muted); }
.hist-entry-items { font-size:12px; color:var(--text-muted); line-height:1.5; }
.hist-badge-ok { background:var(--ok-bg); color:var(--ok); font-size:9px; font-weight:700; padding:2px 8px; border-radius:10px; }
.hist-badge-can { background:var(--danger-bg); color:var(--danger); font-size:9px; font-weight:700; padding:2px 8px; border-radius:10px; }
.hist-btn-clear { display:block; width:calc(100% - 32px); margin:4px 16px 16px; padding:10px; border-radius:8px; border:1px solid var(--border); background:transparent; color:var(--text-muted); font-size:11px; font-weight:600; cursor:pointer; transition:all .15s; font-family:'DM Sans',sans-serif; }
.hist-btn-clear:hover { background:var(--danger-bg); color:var(--danger); border-color:var(--danger); }
</style>
</head>
<body>
    <script>
    // ── Guardia de acceso por rol ─────────────────────────────
    (function() {
        try {
            var ses = JSON.parse(sessionStorage.getItem('pos_usuario') || '{}');
            var rolesPermitidos = ["cocina", "admin", "mesero"];
            if (!ses || !ses.rol || !rolesPermitidos.includes(ses.rol)) {
                // Sin sesión o rol no permitido → volver al login
                sessionStorage.removeItem('pos_usuario');
                window.location.replace('index.php');
            }
        } catch(e) {
            window.location.replace('index.php');
        }
    })();
    </script>


<!-- Badge bebidas desde barra -->
<div id="badge-beb">
    <i class="fas fa-glass-water-droplet" style="font-size:16px;"></i>
    <span>🥤 BARRA LISTA — Mesa <strong id="beb-mesa">–</strong> — Bebidas terminadas</span>
    <button onclick="cerrarBadge()" style="background:rgba(255,255,255,.2);border:none;color:#fff;cursor:pointer;font-size:13px;padding:3px 8px;border-radius:4px;margin-left:4px;">✕</button>
</div>

<!-- Panel lateral barra -->
<button class="panel-barra-toggle" id="btn-panel-barra" onclick="togglePanelBarra()">
    <i class="fas fa-glass-water-droplet"></i>
    BARRA
</button>
<div id="panel-barra-estado">
    <div class="panel-barra-header">
        <i class="fas fa-glass-water-droplet"></i> Estado Barra
        <button onclick="togglePanelBarra()" style="margin-left:auto;background:none;border:none;color:var(--prep);cursor:pointer;font-size:14px;">✕</button>
    </div>
    <div id="panel-barra-lista">
        <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:11px;">Sin órdenes activas en barra</div>
    </div>
</div>

<!-- PANEL HISTORIAL COCINA -->
<div class="hist-panel" id="panel-historial-cocina" onclick="if(event.target===this)cerrarHistorialCocina()">
    <div class="hist-inner">
        <div class="hist-header">
            <div class="hist-header-left">
                <span class="hist-header-ico">🔥</span>
                <div>
                    <div class="hist-header-title">Historial de Comandas</div>
                    <div class="hist-header-sub">Cocina — platos entregados hoy</div>
                </div>
            </div>
            <button class="hist-close" onclick="cerrarHistorialCocina()"><i class="fas fa-times"></i></button>
        </div>
        <div class="hist-toolbar">
            <button class="hist-fil on" onclick="filtrarHistorialCocina('todos',this)">Todos</button>
            <button class="hist-fil" onclick="filtrarHistorialCocina('entregado',this)">Entregados</button>
            <button class="hist-fil" onclick="filtrarHistorialCocina('cancelado',this)">Cancelados</button>
        </div>
        <div class="hist-summary" id="hist-summary-cocina"></div>
        <div class="hist-scroll" id="hist-lista-cocina">
            <div class="hist-empty"><i class="fas fa-fire-burner"></i><p>Cargando...</p></div>
        </div>
        <button class="hist-btn-clear" onclick="limpiarHistorialCocina()">
            <i class="fas fa-trash-can"></i> Borrar historial de hoy
        </button>
    </div>
</div>

<!-- Barra de navegación dinámica según rol -->
<div class="nav-bar" id="nav-bar-cocina">
    <span class="nav-title">🍳 Cocina</span>

    <!-- Bloque admin: solo visible si rol=admin -->
    <div id="nav-admin-cocina-btns" style="display:none; gap:6px; align-items:center;">
        <a class="nav-btn nav-barra" href="barra.php"><i class="fas fa-glass-water-droplet"></i> Barra</a>
        <a class="nav-btn active" href="#"><i class="fas fa-fire-burner"></i> Cocina</a>
        <div class="nav-sep"></div>
        <a class="nav-btn nav-back" id="btn-volver-admin-cocina" href="#" onclick="volverAdminDesdeCocina(); return false;"
           style="background:linear-gradient(135deg,#2E5226,#4A7A3D); color:#fff; font-weight:700; border-radius:8px; padding:8px 14px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-size:13px;">
            Volver a Admin
        </a>
        <a class="nav-btn nav-back" id="btn-volver-cocina" href="#" onclick="volverAMesas(); return false;" style="display:inline-flex;">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <div class="nav-sep"></div>
    </div>

    <!-- Botón activo visible para todos -->
    <a class="nav-btn active" id="btn-cocina-solo" href="#"><i class="fas fa-fire-burner"></i> Cocina</a>
    <div class="nav-sep"></div>
    <button class="nav-btn" onclick="abrirHistorialCocina()" style="border-color:var(--prep);color:var(--prep);">
        <i class="fas fa-clock-rotate-left"></i> Historial
    </button>
    <div class="nav-sep"></div>
    <button class="nav-btn" onclick="cerrarSesionCocina()" style="border-color:var(--danger);color:var(--danger);">
        <i class="fas fa-sign-out-alt"></i> Salir
    </button>
</div>

<!-- Topbar -->
<div class="topbar">
    <div class="tb-left">
        <span class="tb-icon">🍳</span>
        <div><div class="tb-name">Cocina — Jardín</div><div class="tb-sub">Display Comandas</div></div>
    </div>
    <div class="tb-stats">
        <div class="tb-stat"><div class="n" id="sn">0</div><div class="l">Pendientes</div></div>
        <div class="tb-stat"><div class="n w" id="sr">0</div><div class="l">En Prep.</div></div>
        <div class="tb-stat"><div class="n g" id="sl">0</div><div class="l">Listos</div></div>
    </div>
    <div class="tb-right">
        <div class="tb-clock" id="reloj">00:00:00</div>
        <button class="btn-tema" onclick="toggleTema()" title="Cambiar tema"><i class="fas fa-sun icono-tema"></i></button>
    </div>
</div>

<!-- Filtros -->
<div class="filtros">
    <button class="fil on"  onclick="setFil('todos',this)">Todos</button>
    <button class="fil"     onclick="setFil('pendiente',this)">Pendientes</button>
    <button class="fil w"   onclick="setFil('preparando',this)">En preparación</button>
    <button class="fil g"   onclick="setFil('listo',this)">Listos</button>
</div>

<div class="grid" id="grid"></div>
<div id="toast"><i class="fas fa-check"></i> <span id="tmsg"></span></div>
<div id="notif-toast" class="notif-toast">
    <div class="nt-icon" id="nt-icon">🔔</div>
    <div class="nt-body">
        <div class="nt-title" id="nt-title">Notificación</div>
        <div class="nt-desc"  id="nt-desc"></div>
    </div>
    <button class="nt-close" onclick="cerrarNotifToast()"><i class="fas fa-times"></i></button>
</div>

<script>
/* ── TEMA ── */
function toggleTema(){
    const body=document.body,tema=body.getAttribute('data-theme');
    if(!tema){ body.setAttribute('data-theme','dark'); document.querySelectorAll('.icono-tema').forEach(i=>i.className='fas fa-moon icono-tema'); }
    else if(tema==='dark'){ body.setAttribute('data-theme','daltonico'); document.querySelectorAll('.icono-tema').forEach(i=>i.className='fas fa-eye icono-tema'); }
    else{ body.removeAttribute('data-theme'); document.querySelectorAll('.icono-tema').forEach(i=>i.className='fas fa-sun icono-tema'); }
}
(function(){ const t=localStorage.getItem('jardin_tema'); if(t){ document.body.setAttribute('data-theme',t); const ic=t==='dark'?'fa-moon':t==='daltonico'?'fa-eye':'fa-sun'; document.querySelectorAll('.icono-tema').forEach(i=>i.className=`fas ${ic} icono-tema`); } })();
const _origToggle=toggleTema;
window.toggleTema=function(){ _origToggle(); try{ const t=document.body.getAttribute('data-theme')||''; localStorage.setItem('jardin_tema',t); }catch(e){}};

/* ── ESTADO ── */
let comandas=[], filtro='todos', _lp=0, _badgeTimer=null;
let _fetchEnCurso = false; // BUG FIX: bloquear polling mientras hay un fetch de estado activo
// Set de id_pedido entregados localmente — evita que el polling los reintroduzca desde BD
let _entregados = new Set(JSON.parse(localStorage.getItem('cocina_entregados')||'[]'));
let _lastNotifBarra=0;
let _checklists={};
let _avisadas=new Set();

setInterval(()=>{ document.getElementById('reloj').textContent=new Date().toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit',second:'2-digit'}); },1000);

function cargar(){
    // BUG FIX 4: restaurar desde localStorage para mostrar estado inmediato antes del primer polling
    try {
        const raw = JSON.parse(localStorage.getItem('cocina_comandas') || '[]');
        raw.forEach(nc => {
            // No restaurar pedidos que ya fueron marcados como entregados
            if(nc.id_pedido && _entregados.has(nc.id_pedido)) return;
            const ex = comandas.find(c => c.id_pedido === nc.id_pedido);
            if (!ex) {
                nc._ts = nc.timestamp || nc._ts || Date.now();
                nc.estadoGeneral = _calcEstadoLocal(nc.items);
                comandas.push(nc);
            } else {
                Object.assign(ex, nc);
                ex._ts = nc.timestamp || nc._ts || ex._ts;
                ex.estadoGeneral = _calcEstadoLocal(ex.items);
            }
        });
        // Filtrar también los _entregados del array final
        comandas = comandas.filter(c =>
            raw.find(n => n.id_pedido === c.id_pedido) &&
            (!c.id_pedido || !_entregados.has(c.id_pedido))
        );
    } catch(e){}
    try{ _checklists=JSON.parse(localStorage.getItem('cocina_checklists')||'{}'); }catch(e){}
}

// ── Sincronización con base de datos ──────────────────────────
async function cargarDesdeServidor(){
    if (_fetchEnCurso) return; // BUG FIX: no pisar estados si hay un fetch activo
    try{
        const res = await fetch('comandas.php?area=cocina');
        if(!res.ok) return;
        const data = await res.json();
        if(!data.ok || !Array.isArray(data.comandas)) return;

        const bdComandas = data.comandas;
        const ordenEst = {pendiente:0, preparando:1, listo:2, entregado:3};

        // Merge: solo los ITEMS vienen de BD. El estadoGeneral lo calcula cocina
        // exclusivamente desde sus propios items para no contaminarse con barra.
        bdComandas.forEach(nc => {
            nc._ts = nc.timestamp;
            // Si ya fue marcado como entregado localmente, ignorarlo — no reintroducirlo
            if(nc.id_pedido && _entregados.has(nc.id_pedido)) return;
            const ex = comandas.find(c => c.id_pedido === nc.id_pedido);
            if (!ex) {
                // Comanda nueva: calcular estadoGeneral desde sus items
                nc.estadoGeneral = _calcEstadoLocal(nc.items);
                comandas.push(nc);
            } else {
                // Merge de items: tomar el estado más avanzado item a item
                const itemsMerged = nc.items.map(bdIt => {
                    const exIt = ex.items.find(i => i.id_detalle === bdIt.id_detalle);
                    if (!exIt) return bdIt;
                    return {...bdIt, estadoItem:
                        (ordenEst[bdIt.estadoItem]??0) > (ordenEst[exIt.estadoItem]??0)
                            ? bdIt.estadoItem : exIt.estadoItem};
                });
                Object.assign(ex, nc);
                ex._ts   = nc.timestamp;
                ex.items = itemsMerged;
                // CORRECCIÓN: estadoGeneral calculado SOLO de los items de cocina,
                // ignorando el estado_general de BD que mezcla cocina + barra
                ex.estadoGeneral = _calcEstadoLocal(ex.items);
            }
        });

        // Fuente de verdad = BD: quitar solo las que definitivamente ya no están
        // Quitar las que ya no están en BD O las que ya fueron entregadas localmente
        comandas = comandas.filter(c =>
            (!c.id_pedido || bdComandas.find(n => n.id_pedido === c.id_pedido)) &&
            (!c.id_pedido || !_entregados.has(c.id_pedido))
        );

        // Limpiar _entregados de pedidos que ya no existen en BD (cajero los cobró y liberó)
        // Así no se acumula basura y el siguiente turno empieza limpio
        _entregados.forEach(pid => {
            if(!bdComandas.find(n => n.id_pedido === pid)){
                _entregados.delete(pid);
            }
        });
        try{ localStorage.setItem('cocina_entregados', JSON.stringify([..._entregados])); }catch(e){}

        // BUG FIX 4: persistir en localStorage para resistir recargas de página
        try { localStorage.setItem('cocina_comandas', JSON.stringify(comandas)); } catch(e){}

        render();
    }catch(e){ console.warn('Error al cargar desde BD:', e); }
}

// Calcula el estadoGeneral de UNA comanda basado SOLO en sus items propios.
// Evita que el estado_general de BD (mezcla cocina + barra) contamine la vista.
function _calcEstadoLocal(items){
    if (!items || !items.length) return 'pendiente';
    const listos = items.filter(i => i.estadoItem === 'listo').length;
    const prep   = items.filter(i => i.estadoItem === 'preparando').length;
    if (listos === items.length) return 'listo';
    if (prep > 0 || listos > 0) return 'preparando';
    return 'pendiente';
}

function guardar(){
    // BUG FIX 4: persistir estado en localStorage (igual que barra)
    try { localStorage.setItem('cocina_comandas', JSON.stringify(comandas)); } catch(e){}
}
function guardarChecklists(){ try{ localStorage.setItem('cocina_checklists',JSON.stringify(_checklists)); }catch(e){} }

// Polling al servidor cada 3 segundos para sincronización entre dispositivos
setInterval(cargarDesdeServidor, 3000);

setInterval(()=>{
    try{
        // Solo notificaciones y tema — NO llamar cargar() para no pisar datos de BD
        const nb=JSON.parse(localStorage.getItem('notif_bebidas')||'null');
        if(nb&&nb.ts>Date.now()-60000) mostrarBadge(nb.mesa);
        actualizarPanelBarra();
        const tGuardado=localStorage.getItem('jardin_tema')||''; const tActual=document.body.getAttribute('data-theme')||'';
        if(tGuardado!==tActual){ if(tGuardado) document.body.setAttribute('data-theme',tGuardado); else document.body.removeAttribute('data-theme'); }
    }catch(e){}
},1500);

function setFil(f,btn){ filtro=f; document.querySelectorAll('.fil').forEach(b=>b.classList.remove('on')); btn.classList.add('on'); render(); }
function tt(ts){ const d=Math.floor((Date.now()-ts)/1000),m=Math.floor(d/60),s=d%60; return{d,str:`${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`}; }
function ttItem(tsEnvio){ if(!tsEnvio) return ''; const d=Math.floor((Date.now()-tsEnvio)/1000),m=Math.floor(d/60),s=d%60; return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`; }

/* ── CHECKLIST ── */
function _getChecklist(id,c){
    if(!_checklists[id]){
        const items=c?c.items.map(it=>`${it.cantidad}× ${it.nombre}`):[]; 
        _checklists[id]=[...items.map(t=>({texto:t,checked:false})),{texto:'Presentación y temperatura correcta',checked:false}];
    }
    return _checklists[id];
}
function toggleChecklistItem(id,idx){ if(!_checklists[id]) return; _checklists[id][idx].checked=!_checklists[id][idx].checked; guardarChecklists(); render(); }
function agregarChecklistItem(id){ const inp=document.getElementById(`scl-input-${id}`); if(!inp||!inp.value.trim()) return; if(!_checklists[id]) _checklists[id]=[]; _checklists[id].push({texto:inp.value.trim(),checked:false}); inp.value=''; guardarChecklists(); render(); }
function _checklistCompleta(id){ const cl=_checklists[id]; if(!cl||cl.length===0) return false; return cl.every(i=>i.checked); }

/* ── RENDER ── */
function render(){
    const fil=comandas.filter(c=>c.estadoGeneral!=='entregado'&&(filtro==='todos'||c.estadoGeneral===filtro));
    document.getElementById('sn').textContent=comandas.filter(c=>c.estadoGeneral==='pendiente').length;
    document.getElementById('sr').textContent=comandas.filter(c=>c.estadoGeneral==='preparando').length;
    document.getElementById('sl').textContent=comandas.filter(c=>c.estadoGeneral==='listo').length;
    const grid=document.getElementById('grid');
    if(!fil.length){ grid.innerHTML=`<div class="empty"><i class="fas fa-check-circle"></i><p>Sin comandas${filtro!=='todos'?' en este estado':''}</p></div>`; return; }
    fil.sort((a,b)=>(a._ts||a.timestamp)-(b._ts||b.timestamp));
    grid.innerHTML=fil.map(buildCard).join('');
}

function buildCard(c){
    const ti=tt(c._ts||c.timestamp);
    const dem=ti.d>=600, war=ti.d>=300;
    const est=c.estadoGeneral==='listo'?'listo':dem?'demorado':c.estadoGeneral==='preparando'?'preparando':'pendiente';
    const bgMap={pendiente:'',preparando:'bg-r',listo:'bg-l',demorado:'bg-d'};
    const bdMap={pendiente:'bd-p',preparando:'bd-r',listo:'bd-l',demorado:'bd-d'};
    const lblMap={pendiente:'Pendiente',preparando:'En preparación',listo:'✓ Listo',demorado:'⚠ Demorado'};
    const tc=c.estadoGeneral==='listo'?'ok':dem?'danger':war?'w':'ok';

    // Timer de envío del pedido
    const timerEnvioHtml=`<div class="timer-envio-wrap">
        <span class="timer-envio-lbl"><i class="fas fa-clock"></i> Pedido hace</span>
        <span class="timer-envio" id="tenv-${c.id}">${ti.str}</span>
    </div>`;

    // Banner: bebidas de barra listas
    let notifBeb='';
    try{
        const nb=JSON.parse(localStorage.getItem('notif_bebidas')||'null');
        if(nb&&nb.mesa===c.mesa&&nb.ts>Date.now()-60000){
            notifBeb=`<div class="banner-cruzado banner-barra-ok">
                <i class="fas fa-glass-water-droplet"></i>
                <span>🥤 <strong>Barra lista</strong> — Bebidas Mesa ${c.mesa} · ¿lista la comida?</span>
            </div>`;
        }
        // Banner si barra solicita apurarse
        const na=JSON.parse(localStorage.getItem('notif_barra_a_cocina')||'null');
        if(na&&na.mesa===c.mesa&&na.tipo==='apurar'&&na.ts>Date.now()-120000){
            notifBeb+=`<div class="banner-cruzado banner-barra-apurar">
                <i class="fas fa-exclamation-triangle"></i>
                <span>⚡ <strong>BARRA PIDE APURARSE</strong> — Mesa ${c.mesa} · bebidas casi listas</span>
            </div>`;
        }
    }catch(e){}

    // Progreso
    const listos=c.items.filter(i=>i.estadoItem==='listo').length;
    const total=c.items.length;
    const pct=total>0?Math.round((listos/total)*100):0;

    const items=c.items.map((it,i)=>{
        const nota=it.nota&&it.nota!=='Preparación Regular'?`<div class="inota"><i class="fas fa-comment-dots"></i> ${it.nota}</div>`:'';
        const es=it.estadoItem||'pendiente';
        const icoMap={pendiente:'fa-circle-dot',preparando:'fa-fire',listo:'fa-circle-check'};
        const lblMap2={pendiente:'Pendiente',preparando:'Preparando…',listo:'✓ Listo'};
        const ecMap={pendiente:'ie-p',preparando:'ie-r',listo:'ie-l'};
        const dest=it.destinoItem||'aqui';
        const destBadge=dest==='llevar'?`<span class="item-dest-badge item-llevar"><i class="fas fa-bag-shopping"></i> Llevar</span>`:`<span class="item-dest-badge item-aqui"><i class="fas fa-utensils"></i> Aquí</span>`;
        const tiempoItem=it.tsEnvio?`<span class="item-tiempo" id="itm-${c.id}-${i}">${ttItem(it.tsEnvio)}</span>`:'';
        return `<div class="item item-${es}">
            <div class="item-left">
                <span class="icant">${it.cantidad}</span>
                <div class="iinfo">
                    <div class="iinfo-top"><span class="inombre">${it.nombre}</span>${destBadge}</div>
                    ${nota}
                </div>
            </div>
            <div class="item-right">
                ${tiempoItem}
                <button class="iestado ${ecMap[es]}" onclick="avanzarItem('${c.id}',${i})" title="Cambiar estado">
                    <i class="fas ${icoMap[es]}"></i> ${lblMap2[es]}
                </button>
            </div>
        </div>`;
    }).join('');

    const progreso=`<div class="prog-wrap">
        <div class="prog-bar" style="width:${pct}%"></div>
        <span class="prog-label">${listos}/${total} listos</span>
    </div>`;

    let sectionComandaLista='';
    const avisado=_avisadas.has(c.id);
    if(c.estadoGeneral==='listo'){
        sectionComandaLista=`<div style="margin:6px 12px 6px;background:linear-gradient(135deg,rgba(46,112,64,.12),rgba(46,112,64,.06));border:2px solid var(--ok);border-radius:10px;overflow:hidden;">
            <div style="background:var(--ok);padding:8px 14px;display:flex;align-items:center;gap:8px;">
                <span style="font-size:18px;">🍽</span>
                <span style="font-size:12px;font-weight:800;color:#fff;letter-spacing:.5px;">¡COMANDA LISTA!</span>
                <span style="margin-left:auto;font-size:10px;color:rgba(255,255,255,.8);">Mesero y barra notificados</span>
            </div>
            <div style="padding:8px 14px;display:flex;gap:8px;align-items:center;">
                <button class="kbtn btn-avisa" style="flex:1;font-size:10px;padding:7px 8px;"
                    onclick="notificarMesero('${c.id}')">
                    <i class="fas fa-bell"></i> ${avisado?'Avisar mesero de nuevo':'Avisar mesero — a recoger'}
                </button>
            </div>
        </div>`;
    }

    // Botones cocina:
    // Pendiente  → solo items individualmente
    // Preparando → "Avisar barra" + cancelar (items se avanzan individualmente)
    // Listo      → "Mesero recogió" + cancelar (avisar mesero está en el banner)
    let btns='';
    if(c.estadoGeneral==='preparando'){
        btns=`<button class="kbtn btn-avisa-barra" onclick="avisarBarraCasiListo('${c.id}','${c.mesa}')">
                <i class="fas fa-glass-water-droplet"></i> Avisar barra
              </button>`;
    } else if(c.estadoGeneral==='listo'){
        btns=`<button class="kbtn btn-entregado" onclick="marcarEntregado('${c.id}')">
                <i class="fas fa-person-walking-arrow-right"></i> Mesero recogió
              </button>`;
    }
    btns += `<button class="kbtn btn-cancelar" onclick="cancelarPedidoCocina('${c.id}','${c.mesa}')">
        <i class="fas fa-xmark"></i> Cancelar
    </button>`;

    return `<div class="card s-${est}" id="card-${c.id}">
        <div class="ch ${bgMap[est]}">
            <div>
                <div class="mesa">MESA ${c.mesa}<span class="cid"> ${c.id}</span></div>
                <span class="destino${c.destino==='llevar'?' llevar':''}">${c.etiqueta||'🍽 Aquí'}</span>

            </div>
            <div style="text-align:right">
                <div class="hora">${c.hora}</div>
                <div class="timer ${tc}" id="tmr-${c.id}">${ti.str}</div>
                ${timerEnvioHtml}
            </div>
        </div>
        ${notifBeb}
        ${progreso}
        <div class="items">${items}</div>
        ${sectionComandaLista}
        <div class="actions">${btns}</div>
    </div>`;
}

/* ── ACCIONES ── */
function avanzarItem(id,idx){
    const c=comandas.find(x=>x.id===id); if(!c) return;
    const ciclo=['pendiente','preparando','listo'];
    const cur=c.items[idx].estadoItem||'pendiente';
    c.items[idx].estadoItem=ciclo[(ciclo.indexOf(cur)+1)%ciclo.length];

    // Recalcular estadoGeneral SOLO de los items de cocina (no usar el de BD)
    const estadoAntes = c.estadoGeneral;
    c.estadoGeneral = _calcEstadoLocal(c.items);

    // Notificaciones según transiciones de estado
    if(estadoAntes === 'pendiente' && c.estadoGeneral === 'preparando'){
        _notificarBarra(c,'iniciando');
        toast('🔥 Cocina iniciando — Barra informada');
    } else if(c.estadoGeneral === 'listo'){
        _notificarTodos(c,'cocina_lista');
        toast('🎉 ¡Todo listo! Mesero y Barra notificados automáticamente');
    } else if(c.estadoGeneral === 'preparando' && estadoAntes === 'preparando'){
        const listos=c.items.filter(i=>i.estadoItem==='listo').length;
        const total=c.items.length;
        if(listos>0 && listos<total) {
            _notificarBarra(c,'parcial');
            toast(`🔥 ${listos}/${total} platos listos — Barra informada`);
        }
    }

    // Sincronizar SOLO el estado_item individual con BD (nunca el estado_general)
    // Cada área gestiona su propio estado; el trigger trg_pedido_listo en BD
    // es el único que cambia estado_general cuando AMBAS áreas terminan.
    const itemActual = c.items[idx];
    if(itemActual.id_detalle){
        _fetchEnCurso = true;
        fetch('comandas.php?accion=item_estado',{
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({id_detalle: itemActual.id_detalle, estado_item: itemActual.estadoItem})
        }).catch(()=>{}).finally(()=>{ _fetchEnCurso = false; });
    }
    guardar(); render();
}

function cambiarEstado(id,nuevo){
    const c=comandas.find(x=>x.id===id); if(!c) return;
    c.estadoGeneral=nuevo;
    if(nuevo==='preparando'){
        c.items.forEach(i=>{ if(i.estadoItem==='pendiente') i.estadoItem='preparando'; });
        _notificarBarra(c,'iniciando');
        toast('🔥 Preparación iniciada — Barra informada');
    }
    if(nuevo==='listo'){
        c.items.forEach(i=>i.estadoItem='listo');
        _notificarTodos(c,'cocina_lista');
        toast('✅ ¡Todo listo! Mesero y Barra notificados');
    }
    guardar(); render();
}

function notificarMesero(id){
    const c=comandas.find(x=>x.id===id); if(!c) return;
    _notificarMesero(c,'cocina_lista');
    toast(`🔔 Mesero avisado — Mesa ${c.mesa}`);
}

// Avisar barra que ya casi están listos los alimentos
function avisarBarraCasiListo(id,mesa){
    const c=comandas.find(x=>x.id===id); if(!c) return;
    try{
        localStorage.setItem('notif_cocina_a_barra',JSON.stringify({
            mesa:parseInt(mesa), ts:Date.now(), tipo:'parcial',
            msg:`🍳 Cocina casi lista Mesa ${mesa} — prepara bebidas para salir juntos`
        }));
        localStorage.setItem('ultima_actualizacion',String(Date.now()));
    }catch(e){}
    mostrarNotifToast('🥤','Barra avisada',`Mesa ${mesa}: alimentos casi listos, prepara las bebidas.`,'barra');
    toast(`🥤 Barra avisada — Mesa ${mesa} casi lista`);
}

function marcarEntregado(id){
    const c=comandas.find(x=>x.id===id); if(!c) return;
    _agregarHistorialCocina(c);
    c.estadoGeneral='entregado';
    // Registrar en _entregados para que el polling no reintroduzca este pedido desde BD
    if(c.id_pedido){
        _entregados.add(c.id_pedido);
        try{ localStorage.setItem('cocina_entregados', JSON.stringify([..._entregados])); }catch(e){}
    }
    try{ localStorage.removeItem('notif_cocina_lista'); }catch(e){}
    guardar(); toast('✓ Entregado — guardado en historial'); render();
}

// ── Cancelar pedido cocina ───────────────────────────────────
function cancelarPedidoCocina(id, mesa){
    
    const c = comandas.find(x=>x.id===id); if(!c) return;
    try{
        let hist=JSON.parse(localStorage.getItem('historial_cocina')||'[]');
        hist.push({ mesa:c.mesa, hora:new Date().toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'}),
            items:c.items.map(i=>({nombre:i.nombre,cantidad:i.cantidad})), ts:Date.now(), cancelado:true });
        if(hist.length>200) hist=hist.slice(-200);
        localStorage.setItem('historial_cocina',JSON.stringify(hist));
    }catch(e){}
    comandas=comandas.filter(x=>x.id!==id);
    _avisadas.delete(id);
    guardar();
    toast('🚫 Pedido cancelado — Mesa '+mesa);
    render();
    // BUG FIX: avisar al servidor para que no reaparezca en el próximo polling
    if(c.id_pedido){
        fetch('comandas.php?accion=cancelar',{
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({id_pedido: c.id_pedido})
        }).catch(()=>{});
    }
}

// ── Panel historial cocina ────────────────────────────────────
let _histFiltroCocina = 'todos';
function abrirHistorialCocina(){
    _histFiltroCocina='todos';
    document.querySelectorAll('#panel-historial-cocina .hist-fil').forEach(b=>b.classList.remove('on'));
    const first=document.querySelector('#panel-historial-cocina .hist-fil');
    if(first) first.classList.add('on');
    _renderHistorialCocinaPanel();
    document.getElementById('panel-historial-cocina').classList.add('visible');
}
function cerrarHistorialCocina(){
    document.getElementById('panel-historial-cocina').classList.remove('visible');
}
function filtrarHistorialCocina(filtro,btn){
    _histFiltroCocina=filtro;
    document.querySelectorAll('#panel-historial-cocina .hist-fil').forEach(b=>b.classList.remove('on'));
    btn.classList.add('on');
    _renderHistorialCocinaPanel();
}
function _renderHistorialCocinaPanel(){
    let hist=[];
    try{ hist=JSON.parse(localStorage.getItem('historial_cocina')||'[]'); }catch(e){}
    hist=hist.slice().reverse();
    if(_histFiltroCocina==='entregado') hist=hist.filter(h=>!h.cancelado);
    if(_histFiltroCocina==='cancelado') hist=hist.filter(h=>h.cancelado);
    const all=JSON.parse(localStorage.getItem('historial_cocina')||'[]');
    const ent=all.filter(h=>!h.cancelado).length;
    const can=all.filter(h=>h.cancelado).length;
    const plat=all.filter(h=>!h.cancelado).reduce((s,h)=>s+h.items.reduce((ss,i)=>ss+i.cantidad,0),0);
    const sumEl=document.getElementById('hist-summary-cocina');
    if(sumEl) sumEl.innerHTML=`
        <div class="hist-sum-card"><div class="hist-sum-n" style="color:var(--ok)">${ent}</div><div class="hist-sum-l">Entregados</div></div>
        <div class="hist-sum-card"><div class="hist-sum-n" style="color:var(--danger)">${can}</div><div class="hist-sum-l">Cancelados</div></div>
        <div class="hist-sum-card"><div class="hist-sum-n">${plat}</div><div class="hist-sum-l">Platos</div></div>
    `;
    const lista=document.getElementById('hist-lista-cocina');
    if(!lista) return;
    if(!hist.length){
        lista.innerHTML=`<div class="hist-empty"><i class="fas fa-fire-burner"></i><p style="font-size:14px;">Sin resultados</p></div>`; return;
    }
    lista.innerHTML=hist.map((h,i)=>{
        const esCan=h.cancelado===true;
        const itmStr=h.items.map(it=>`${it.cantidad}× ${it.nombre}`).join(', ');
        const badge=esCan?`<span class="hist-badge-can">🚫 CANCELADO</span>`:`<span class="hist-badge-ok">✅ ENTREGADO</span>`;
        return `<div class="hist-entry${esCan?' cancelado':''}" style="animation:slideIn .18s ease ${i*0.02}s both;">
            <div class="hist-entry-top"><div style="display:flex;align-items:center;gap:8px;"><span class="hist-entry-mesa">Mesa ${h.mesa}</span>${badge}</div>
            <span class="hist-entry-hora">${h.hora}</span></div>
            <p class="hist-entry-items">${itmStr}</p></div>`;
    }).join('');
}
function limpiarHistorialCocina(){
    if(!confirm('¿Borrar todo el historial de cocina?')) return;
    localStorage.removeItem('historial_cocina');
    _renderHistorialCocinaPanel();
    toast('🗑 Historial borrado');
}

// Historial de comida entregada por mesa
function _agregarHistorialCocina(c){
    try{
        let hist=JSON.parse(localStorage.getItem('historial_cocina')||'[]');
        const hora=new Date().toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'});
        hist.push({
            mesa: c.mesa, hora,
            items: c.items.map(i=>({nombre:i.nombre,cantidad:i.cantidad})),
            ts: Date.now()
        });
        if(hist.length>200) hist=hist.slice(-200);
        localStorage.setItem('historial_cocina',JSON.stringify(hist));
    }catch(e){}
}

/* ── NOTIFICACIONES CRUZADAS ── */
function _notificarBarra(c,tipo){
    const msgs={
        iniciando:`🍳 Cocina inició preparación Mesa ${c.mesa} — prepara las bebidas`,
        parcial:`🔥 Cocina avanzando Mesa ${c.mesa} — coordina entrega de bebidas`,
    };
    const msg = msgs[tipo] || 'Actualización cocina';
    try{
        localStorage.setItem('notif_cocina_a_barra',JSON.stringify({mesa:c.mesa,ts:Date.now(),tipo,msg}));
        localStorage.setItem('ultima_actualizacion',String(Date.now()));
    }catch(e){}
    // ── Guardar en BD: tabla notificacion ──
    fetch('comandas.php?accion=notificar',{
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({mesa:parseInt(c.mesa), origen:'cocina', destino:'barra', tipo, mensaje:msg})
    }).catch(()=>{});
}
function _notificarMesero(c,tipo){
    const msg = `🍽 Comida lista — Mesa ${c.mesa}`;
    try{
        localStorage.setItem('notif_cocina_lista',JSON.stringify({mesa:c.mesa,ts:Date.now(),tipo,msg}));
        localStorage.setItem('ultima_actualizacion',String(Date.now()));
    }catch(e){}
    // ── Guardar en BD: tabla notificacion ──
    fetch('comandas.php?accion=notificar',{
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({mesa:parseInt(c.mesa), origen:'cocina', destino:'mesero', tipo:'cocina_lista', mensaje:msg})
    }).catch(()=>{});
}
function _notificarTodos(c,tipo){
    // Solo avisar a barra si hay bebidas pendientes para esta mesa
    let hayItemsBarra = false;
    try {
        const bar = JSON.parse(localStorage.getItem('barra_comandas') || '[]');
        hayItemsBarra = bar.some(b =>
            b.mesa == c.mesa &&
            b.estadoGeneral !== 'entregado' &&
            b.estadoGeneral !== 'listo' &&
            b.items && b.items.length > 0
        );
    } catch(e) {}

    if (hayItemsBarra) {
        // Pedido mixto: avisar a barra para coordinar
        _notificarMesero(c, tipo);
        const msg = `🍽 Comida lista Mesa ${c.mesa} — sincroniza entrega de bebidas`;
        try {
            localStorage.setItem('notif_cocina_a_barra', JSON.stringify({
                mesa: c.mesa, ts: Date.now(), tipo:'cocina_lista', msg
            }));
            localStorage.setItem('ultima_actualizacion', String(Date.now()));
        } catch(e) {}
        fetch('comandas.php?accion=notificar', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                mesa: parseInt(c.mesa), origen:'cocina', destino:'barra',
                tipo:'cocina_lista', mensaje: msg
            })
        }).catch(()=>{});
    } else {
        // Solo cocina: avisar directo al mesero sin molestar a barra
        _notificarMesero(c, tipo);
    }
}

function mostrarBadge(mesa){
    const b=document.getElementById('badge-beb');
    document.getElementById('beb-mesa').textContent=mesa;
    b.classList.add('show'); clearTimeout(_badgeTimer);
    _badgeTimer=setTimeout(()=>b.classList.remove('show'),20000);
    try{
        const nb=JSON.parse(localStorage.getItem('notif_bebidas')||'null');
        if(nb&&nb.ts>_lastNotifBarra+5000){
            _lastNotifBarra=nb.ts;
            mostrarNotifToast('🥤','Bebidas listas — Barra',`Mesa ${mesa}: bebidas ya servidas. ¿Lista la comida para salir juntos?`,'barra');
        }
    }catch(e){}
}
function cerrarBadge(){ document.getElementById('badge-beb').classList.remove('show'); try{localStorage.removeItem('notif_bebidas');}catch(e){} }

let _panelBarraVisible=false;
function togglePanelBarra(){
    _panelBarraVisible=!_panelBarraVisible;
    document.getElementById('panel-barra-estado').classList.toggle('visible',_panelBarraVisible);
    document.getElementById('btn-panel-barra').style.right=_panelBarraVisible?'220px':'0';
    if(_panelBarraVisible) actualizarPanelBarra();
}
function actualizarPanelBarra(){
    if(!_panelBarraVisible) return;
    try{
        const raw=JSON.parse(localStorage.getItem('barra_comandas')||'[]');
        const activas=raw.filter(c=>c.estadoGeneral!=='entregado');
        const lista=document.getElementById('panel-barra-lista');
        if(!activas.length){ lista.innerHTML='<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:11px;">Sin órdenes activas en barra</div>'; return; }
        lista.innerHTML=activas.map(c=>{
            const lbls={pendiente:'Pendiente',preparando:'Preparando…',listo:'✓ Lista'};
            const cls={pendiente:'pbi-pendiente',preparando:'pbi-preparando',listo:'pbi-listo'};
            const iLst=c.items.map(i=>`${i.cantidad}× ${i.nombre}`).join('<br>');
            return `<div class="panel-barra-item">
                <div class="pbi-mesa">Mesa ${c.mesa}</div>
                <div class="pbi-items">${iLst}</div>
                <span class="pbi-status ${cls[c.estadoGeneral]||'pbi-pendiente'}">${lbls[c.estadoGeneral]||'Pendiente'}</span>
            </div>`;
        }).join('');
    }catch(e){}
}

function toast(msg){ const el=document.getElementById('toast'); document.getElementById('tmsg').textContent=msg; el.classList.add('show'); setTimeout(()=>el.classList.remove('show'),3000); }
let _notifToastTimer=null;
function mostrarNotifToast(icono,titulo,desc,tipo='ok'){
    const el=document.getElementById('notif-toast');
    document.getElementById('nt-icon').textContent=icono;
    document.getElementById('nt-title').textContent=titulo;
    document.getElementById('nt-desc').textContent=desc;
    el.className=`notif-toast show tipo-${tipo}`;
    clearTimeout(_notifToastTimer);
    _notifToastTimer=setTimeout(cerrarNotifToast,10000);
}
function cerrarNotifToast(){ document.getElementById('notif-toast').classList.remove('show'); clearTimeout(_notifToastTimer); }

/* ── TIMERS ── */
setInterval(()=>{
    comandas.forEach(c=>{
        if(c.estadoGeneral==='entregado') return;
        const el=document.getElementById(`tmr-${c.id}`); if(!el) return;
        const ti=tt(c._ts||c.timestamp);
        el.textContent=ti.str;
        el.className='timer '+(c.estadoGeneral==='listo'?'ok':ti.d>=600?'danger':ti.d>=300?'w':'ok');
        const ev=document.getElementById(`tenv-${c.id}`); if(ev) ev.textContent=ti.str;
        c.items.forEach((it,i)=>{
            const iel=document.getElementById(`itm-${c.id}-${i}`);
            if(iel&&it.tsEnvio) iel.textContent=ttItem(it.tsEnvio);
        });
    });
},1000);

cargarDesdeServidor(); // BD es la fuente de verdad

/* ── CONTROL DE ACCESO Y NAVEGACIÓN POR ROL ──────────────────
   admin  → ve botones Barra, Cocina y Volver
   cocina → ve solo botón Cocina (activo, sin links a otras áreas)
   mesero → llegó aquí por enviar pedido → ve solo botón Volver
   otros  → redirigir al login
─────────────────────────────────────────────────────────────── */
(function _configurarPorRol(){
    try {
        const sesion = JSON.parse(sessionStorage.getItem('pos_usuario') || '{}');
        const rol = sesion.rol || '';

        if (rol === 'admin') {
            // Admin: mostrar todo — Barra, Cocina, Volver
            document.getElementById('nav-admin-cocina-btns').style.display = 'flex';
            document.getElementById('btn-volver-cocina').style.display = 'inline-flex';
            document.getElementById('btn-cocina-solo').style.display = 'none';

        } else if (rol === 'cocina') {
            // Cocina: solo su botón activo, sin navegación cruzada
            document.getElementById('nav-admin-cocina-btns').style.display = 'none';
            document.getElementById('btn-volver-cocina').style.display = 'none';
            document.getElementById('btn-cocina-solo').style.display = 'inline-flex';

        } else if (rol === 'mesero') {
            // Mesero: llegó aquí por enviar pedido → solo botón Volver a mesas
            document.getElementById('nav-admin-cocina-btns').style.display = 'none';
            document.getElementById('btn-volver-cocina').style.display = 'inline-flex';
            document.getElementById('btn-cocina-solo').style.display = 'none';

        } else {
            // Sin sesión o rol no autorizado → login
            window.location.replace('index.php');
        }
    } catch(e) {
        window.location.replace('index.php');
    }
})();

function volverAMesas(){
    try{ localStorage.setItem('ir_a_mesas','1'); }catch(e){}
    window.location.href='index.php';
}

// ── Volver al panel admin desde cocina ─────────────────────
function volverAdminDesdeCocina() {
    try { localStorage.removeItem('admin_en_mesas'); localStorage.setItem('admin_volver_panel','1'); } catch(e){}
    window.location.href = 'index.php';
}
// Mostrar bloque admin solo si rol=admin
(function(){
    try {
        var ses = JSON.parse(sessionStorage.getItem('pos_usuario')||'{}');
        if(ses && ses.rol === 'admin') {
            var blk = document.getElementById('nav-admin-cocina-btns');
            if(blk) blk.style.display = 'inline-flex';
            // Ocultar botón cocina-solo (ya está en el bloque admin)
            var bs = document.getElementById('btn-cocina-solo');
            if(bs) bs.style.display = 'none';
        }
    } catch(e){}
})();

function cerrarSesionCocina() {
    try {
        const ses = JSON.parse(sessionStorage.getItem('pos_usuario') || '{}');
        if (ses && ses.id_usuario) {
            fetch('login_check.php?accion=logout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_usuario: ses.id_usuario })
            }).catch(() => {});
        }
    } catch(e) {}
    sessionStorage.removeItem('pos_usuario');
    window.location.replace('index.php');
}
</script>
</body>
</html>
