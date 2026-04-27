// --- VARIABLES GLOBALES ---
let pin = "";
let mesas = []; // Se cargan desde BD en cargarMesasDesdeBD()
let mesaActiva = null;
let productoArmando = null;

// --- ALERTAS DE TIEMPO ---
const ALERTA_MINUTOS = 45; // Alerta si la mesa lleva más de este tiempo
let intervalTiempo = null;

// Variables para división de cuentas
let tipoDivision = null;
let totalPersonas = 0;
let personas = [];
let personaActual = 1;
let productosAsignados = {};
let personasCompletadas = [];

// --- DATOS DEL MENÚ JARDÍN (COMPLETO CON SECCIONES) ---
// Cada categoría puede tener secciones: [{ titulo, items: [...] }]
// o un array plano de productos (compatibilidad hacia atrás)

// Menú cargado dinámicamente desde BD
// Se llena al inicio con cargarMenuDesdeBD()
let menuSecciones = {};

// Compatibilidad: menuDatos como referencia plana para enviarPedido
let menuDatos = {};

async function cargarMenuDesdeBD() {
    try {
        const res  = await fetch('menu_api.php?accion=listar');
        const data = await res.json();
        if (!data.ok) return;

        // Agrupar por categoria → subcategoria
        const secs = {};
        data.productos.filter(p => p.activo == 1).forEach(p => {
            const cat = p.categoria || 'otros';
            const sub = p.subcategoria || 'General';
            if (!secs[cat]) secs[cat] = {};
            if (!secs[cat][sub]) secs[cat][sub] = [];
            secs[cat][sub].push({
                id:           parseInt(p.id_producto),
                nombre:       p.nombre,
                precio:       parseFloat(p.precio),
                tipo:         p.tipo,
                ruta_defecto: p.ruta_defecto
            });
        });

        // Convertir a formato menuSecciones: { cat: [{titulo, items}] }
        menuSecciones = {};
        for (const cat in secs) {
            menuSecciones[cat] = Object.entries(secs[cat]).map(([sub, items]) => ({
                titulo: sub,
                items
            }));
        }

        // Reconstruir menuDatos plano
        menuDatos = {};
        for (const cat in menuSecciones) {
            menuDatos[cat] = menuSecciones[cat].flatMap(s => s.items);
        }

        // Re-renderizar el menú si ya hay una mesa activa abierta
        if (mesaActiva) {
            const catActiva = document.querySelector('.cat-btn.activa');
            const cat = catActiva
                ? (catActiva.getAttribute('onclick') || '').match(/'(\w+)'/)?.[1] || 'calientes'
                : 'calientes';
            filtrarCat(cat, catActiva || document.querySelector('.cat-btn'));
        }

    } catch(e) {
        console.warn('Error cargando menú desde BD:', e);
    }
}

// menuDatos se construye dinámicamente en cargarMenuDesdeBD()

// ── CARGA DESDE localStorage ──────────────────────────────────
(function cargarTemporadaGuardada() {
    try {
        // Cargar postres de temporada desde BD (y localStorage como fallback)
        const postresGuardados = JSON.parse(localStorage.getItem('postresTemporada') || '[]');
        menuSecciones.postres[0].items = postresGuardados;
        // Sincronizar con BD en background
        fetch('menu_api.php?accion=temporada_listar')
            .then(r=>r.json()).then(d=>{
                if(d.ok && d.productos) {
                    const postDB = d.productos.filter(p=>p.categoria==='postres');
                    const bevDB  = d.productos.filter(p=>p.categoria==='bebidasTemp');
                    if(postDB.length > 0) {
                        menuSecciones.postres[0].items = postDB.map(p=>({
                            id:p.id_producto, nombre:p.nombre, precio:parseFloat(p.precio), tipo:p.tipo||'comida'
                        }));
                        menuDatos.postres = menuSecciones.postres[0].items;
                    }
                    if(bevDB.length > 0) {
                        menuSecciones.bebidasTemp[0].items = bevDB.map(p=>({
                            id:p.id_producto, nombre:p.nombre, precio:parseFloat(p.precio), tipo:p.tipo||'bebida'
                        }));
                        menuDatos.bebidasTemp = menuSecciones.bebidasTemp[0].items;
                    }
                }
            }).catch(()=>{});
        menuDatos.postres = postresGuardados;
    } catch(e) {}
    try {
        const bebidasGuardadas = JSON.parse(localStorage.getItem('bebidasTemporada') || '[]');
        menuSecciones.bebidasTemp[0].items = bebidasGuardadas;
        menuDatos.bebidasTemp = bebidasGuardadas;
    } catch(e) {}
})();

// --- FUNCIÓN PARA CAMBIAR TEMA ---
// Ciclo: Claro (☀) → Oscuro (🌙) → Daltónico (👁) → Claro
function toggleTema() {
    const body = document.body;
    const tema = body.getAttribute('data-theme');

    if (!tema) {
        body.setAttribute('data-theme', 'dark');
        document.querySelectorAll('.icono-tema').forEach(i => {
            i.className = 'fas fa-moon icono-tema';
            i.title = 'Modo oscuro activo';
        });
    } else if (tema === 'dark') {
        body.setAttribute('data-theme', 'daltonico');
        document.querySelectorAll('.icono-tema').forEach(i => {
            i.className = 'fas fa-eye icono-tema';
            i.title = 'Modo accesible activo';
        });
    } else {
        body.removeAttribute('data-theme');
        document.querySelectorAll('.icono-tema').forEach(i => {
            i.className = 'fas fa-sun icono-tema';
            i.title = 'Modo claro activo';
        });
    }
}

// --- NAVEGACIÓN ---
function mostrarPantalla(id) {
    document.querySelectorAll('.pantalla').forEach(p => p.classList.remove('activa'));
    document.getElementById(id).classList.add('activa');
}

// --- LOGIN ---
let pinIngresado = "";
let intentosFallidos = 0;
const MAX_INTENTOS = 5;
const BLOQUEO_SEG  = 30;
let _bloqueadoHasta = 0;
let _timerBloqueo   = null;

function teclear(valor) {
    if (Date.now() < _bloqueadoHasta) return;
    if (intentosFallidos >= MAX_INTENTOS) return;
    if (valor === 'C') { pinIngresado = ""; }
    else if (pinIngresado.length < 4) { pinIngresado += valor; }
    actualizarDisplayPin();
}

function actualizarDisplayPin() {
    const d = document.getElementById("pin-display");
    if (d) d.innerText = "*".repeat(pinIngresado.length);
}

function _actualizarPips() {
    for (let i = 0; i < MAX_INTENTOS; i++) {
        const p = document.getElementById('pip-' + i);
        if (!p) continue;
        p.className = 'intento-pip' + (i < intentosFallidos ? ' fallido' : '');
    }
}

function _loginMsg(txt, warn) {
    const el = document.getElementById('login-msg');
    if (!el) return;
    if (!txt) { el.style.display = 'none'; return; }
    el.style.display = 'block';
    el.textContent = txt;
    el.style.color      = warn ? '#B8860B' : '#A0320A';
    el.style.background = warn ? 'rgba(184,134,11,.1)' : 'rgba(160,50,10,.08)';
    el.style.borderColor= warn ? 'rgba(184,134,11,.25)' : 'rgba(160,50,10,.2)';
}

function _setBloqueado(on) {
    document.querySelectorAll('.teclado button').forEach(b => {
        b.disabled = on;
        b.style.opacity = on ? '0.45' : '';
        b.style.cursor  = on ? 'not-allowed' : '';
    });
    const inp = document.getElementById('login-nombre');
    if (inp) inp.disabled = on;
}

function _iniciarBloqueo() {
    _bloqueadoHasta = Date.now() + BLOQUEO_SEG * 1000;
    _setBloqueado(true);
    // Mostrar link de recuperación después de agotar intentos
    const linkRec = document.getElementById('link-recuperar');
    if (linkRec) linkRec.style.display = 'block';

    clearInterval(_timerBloqueo);
    _timerBloqueo = setInterval(() => {
        const seg = Math.ceil((_bloqueadoHasta - Date.now()) / 1000);
        if (seg <= 0) {
            clearInterval(_timerBloqueo);
            intentosFallidos = 0;
            pinIngresado = '';
            actualizarDisplayPin();
            _actualizarPips();
            _setBloqueado(false);
            _loginMsg('');
            // Ocultar link de recuperación al desbloquear
            if (linkRec) linkRec.style.display = 'none';
        } else {
            _loginMsg(`⏳ Bloqueado — espera ${seg}s para intentar de nuevo`, true);
        }
    }, 500);
}

// ════════════════════════════════════════════════════════════════
//  LOGIN — conectado a login_check.php y base de datos real
//  Fallback local por si XAMPP no está corriendo
// ════════════════════════════════════════════════════════════════

// Usuarios de emergencia (solo si el servidor no responde)
const _USUARIOS_DEMO = [
    { nombre: 'Admin', pin: '9999', rol: 'admin' },
];

async function validarLogin() {
    if (Date.now() < _bloqueadoHasta) return;
    if (intentosFallidos >= MAX_INTENTOS) return;
    if (!pinIngresado) return;

    const pinEnv    = pinIngresado;
    const nombre    = (document.getElementById('login-nombre')?.value || '').trim();
    pinIngresado    = '';
    actualizarDisplayPin();

    if (!nombre) { _loginMsg('Escribe tu nombre para continuar'); return; }

    let loginOk = false, rol = null, nombreFinal = nombre, id_usuario = 0;
    let mensajeError = 'Nombre o PIN incorrecto';

    // ── 1. Intentar con la base de datos real ─────────────────
    try {
        const ctrl  = new AbortController();
        const timer = setTimeout(() => ctrl.abort(), 4000);
        const res   = await fetch('login_check.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ nombre, pin: pinEnv }),
            signal:  ctrl.signal
        });
        clearTimeout(timer);
        const data = await res.json();

        if (data.ok) {
            loginOk     = true;
            rol         = data.rol;
            nombreFinal = data.nombre;
            id_usuario  = data.id_usuario;
        } else {
            mensajeError = data.error || 'Nombre o PIN incorrecto';
        }
    } catch(e) {
        // ── 2. Sin servidor → fallback de emergencia ──────────
        const match = _USUARIOS_DEMO.find(u =>
            u.nombre.toLowerCase() === nombre.toLowerCase() && u.pin === pinEnv
        );
        if (match) {
            loginOk     = true;
            rol         = match.rol;
            nombreFinal = match.nombre;
        } else {
            mensajeError = 'No se puede conectar al servidor. Verifica XAMPP.';
        }
    }

    // ── Resultado ─────────────────────────────────────────────
    if (loginOk) {
        intentosFallidos = 0;
        _actualizarPips();
        _loginMsg('');

        try {
            sessionStorage.setItem('pos_usuario', JSON.stringify({
                id: id_usuario, nombre: nombreFinal, rol
            }));
        } catch(e) {}

        // ── Redirigir según rol ───────────────────────────────
        document.getElementById('login').classList.remove('activa');

        if (rol === 'admin') {
            // Admin → panel de bienvenida hermoso dentro de index.php
            _mostrarPanelAdminBienvenida(nombreFinal);

        } else if (rol === 'mesero') {
            // Mesero → pantalla de mesas
            const hdr = document.getElementById('operador-nombre');
            if (hdr) hdr.textContent = nombreFinal;
            document.getElementById('mesas').classList.add('activa');
            // Limpiar localStorage — nunca mostrar botón admin a mesero
            try {
                localStorage.removeItem('pos_mesas_estado');
                localStorage.removeItem('admin_en_mesas');
            } catch(e) {}
            // Ocultar explícitamente el botón volver a admin
            const btnAdmin = document.getElementById('btn-volver-admin-mesas');
            if (btnAdmin) btnAdmin.classList.remove('visible');
            mesas = [];
            renderizarMesas();
            if (typeof cargarMesasDesdeBD === 'function') cargarMesasDesdeBD();
            _iniciarAutoRefresh();

        } else {
            // Cajero, cocina, barra → ir a su página exclusiva
            const destinos = { cajero: 'cobro.php', cocina: 'cocina.php', barra: 'barra.php' };
            window.location.href = destinos[rol] || 'index.php';
        }

    } else {
        intentosFallidos++;
        _actualizarPips();
        if (intentosFallidos >= MAX_INTENTOS) {
            _iniciarBloqueo();
        } else {
            const r = MAX_INTENTOS - intentosFallidos;
            _loginMsg(`${mensajeError} — te quedan ${r} intento${r !== 1 ? 's' : ''}`);
        }
    }
}

// ── Panel de bienvenida del administrador ─────────────────────
function _mostrarPanelAdminBienvenida(nombre) {
    document.querySelectorAll('.pantalla').forEach(p => p.classList.remove('activa'));
    const panel = document.getElementById('pantalla-admin-bienvenida');
    if (panel) {
        panel.classList.add('activa');
        // Actualizar nombre en todos los spans
        panel.querySelectorAll('.admin-bienvenida-nombre').forEach(el => {
            el.textContent = nombre;
        });
        const hdrName = document.getElementById('admin-header-nombre');
        if (hdrName) hdrName.textContent = nombre;
    } else {
        window.location.href = 'admin.php';
    }
}

// ── Ir a mesas desde el panel admin ──────────────────────────
function adminVerMesas() {
    const ses = _getSesion();
    const hdr = document.getElementById('operador-nombre');
    if (hdr) hdr.textContent = ses.nombre || 'Admin';
    // Marcar que el admin vino de su panel (para mostrar botón Volver a Admin)
    try { localStorage.setItem('admin_en_mesas', '1'); } catch(e) {}
    document.querySelectorAll('.pantalla').forEach(p => p.classList.remove('activa'));
    document.getElementById('mesas').classList.add('activa');
    _mostrarBtnVolverAdmin(true);
    try { localStorage.removeItem('pos_mesas_estado'); } catch(e) {}
    mesas = [];
    renderizarMesas();
    if (typeof cargarMesasDesdeBD === 'function') cargarMesasDesdeBD();
    _iniciarAutoRefresh();
}

// ── Volver al panel de bienvenida admin ───────────────────────
function volverAlPanelAdmin() {
    try { localStorage.removeItem('admin_en_mesas'); } catch(e) {}
    const ses = _getSesion();
    _mostrarPanelAdminBienvenida(ses.nombre || 'Admin');
    _mostrarBtnVolverAdmin(false);
}

// ── Mostrar/ocultar el botón Volver a Admin ───────────────────
function _mostrarBtnVolverAdmin(mostrar) {
    const btn = document.getElementById('btn-volver-admin-mesas');
    if (!btn) return;
    if (mostrar) {
        btn.classList.add('visible');
    } else {
        btn.classList.remove('visible');
    }
}

function cerrarSesion() {
    intentosFallidos = 0;
    pinIngresado = '';
    _bloqueadoHasta = 0;
    clearInterval(_timerBloqueo);
    _actualizarPips();
    _loginMsg('');
    _setBloqueado(false);
    actualizarDisplayPin();
    // Limpiar campo nombre
    const inp = document.getElementById('login-nombre');
    if (inp) inp.value = '';
    // Limpiar sesión y flags de admin
    try {
        sessionStorage.removeItem('pos_usuario');
        localStorage.removeItem('admin_en_mesas');
        localStorage.removeItem('pos_mesas_estado');
    } catch(e) {}
    // Ocultar botón admin
    const btnAdmin = document.getElementById('btn-volver-admin-mesas');
    if (btnAdmin) btnAdmin.classList.remove('visible');
    mesas = [];
    mostrarPantalla('login');
}

// --- GESTIÓN DE MESAS ---
async function agregarMesa() {
    // Guardar directo en BD y luego recargar todas las mesas desde BD
    try {
        const res  = await fetch('mesas.php?accion=agregar', { method: 'POST' });
        const data = await res.json();
        if (data.ok && data.id_mesa) {
            // Recargar mesas desde BD para que todo quede sincronizado
            await cargarMesasDesdeBD();
            renderizarMesas();
        } else {
            alert('No se pudo agregar la mesa: ' + (data.error || 'error desconocido'));
        }
    } catch(e) {
        alert('Sin conexión a BD — no se puede agregar la mesa.');
        console.warn('agregarMesa error:', e);
    }
}

async function quitarMesa() {
    if (mesas.length === 0) return alert("No hay mesas para quitar.");
    // Quitar en BD primero y luego recargar
    try {
        const res  = await fetch('mesas.php?accion=quitar', { method: 'POST' });
        const data = await res.json();
        if (data.ok) {
            await cargarMesasDesdeBD();
            renderizarMesas();
        } else {
            alert('No se puede quitar: ' + (data.error || 'mesa con pedidos activos'));
        }
    } catch(e) {
        alert('Sin conexión a BD — no se puede quitar la mesa.');
    }
}

function abrirKDS(cual) {
    // Guardar estado de mesas antes de navegar
    _guardarEstadoMesas();
    if (cual === 'cocina') {
        window.location.href = 'cocina.php';
    } else {
        window.location.href = 'barra.php';
    }
}

// --- TIEMPO DE OCUPACIÓN ---
function calcularTiempoOcupacion(horaOcupacion) {
    if (!horaOcupacion) return null;
    const ahora = Date.now();
    const diffMs = ahora - horaOcupacion;
    const minutos = Math.floor(diffMs / 60000);
    if (minutos < 60) return `${minutos} min`;
    const horas = Math.floor(minutos / 60);
    const mins = minutos % 60;
    return mins > 0 ? `${horas}h ${mins}min` : `${horas}h`;
}

function getMinutosOcupacion(horaOcupacion) {
    if (!horaOcupacion) return 0;
    return Math.floor((Date.now() - horaOcupacion) / 60000);
}

function renderizarMesas() {
    const contenedor = document.getElementById("contenedor-mesas");
    if (!contenedor) return;

    let html = '';

    mesas.forEach(mesa => {
        let totalMesa = mesa.pedido.reduce((sum, item) => sum + ((item.precio + (item.extraPrecio||0)) * item.cantidad), 0);
        const tienePedidos = mesa.pedido && mesa.pedido.length > 0;

        // ── Estado visual — usa mesa.estado como fuente principal ──
        let estadoClase = 'libre';
        let estadoLabel = 'DISPONIBLE';
        let estadoIcon  = '<i class="fas fa-circle-check"></i>';

        // Verificar si la reserva es para HOY y si ya estamos dentro del umbral
        const _hoy = new Date();
        const _reservaEsHoy = mesa.reserva && mesa.reserva.anio !== undefined &&
            mesa.reserva.anio === _hoy.getFullYear() &&
            mesa.reserva.mesIndex === _hoy.getMonth() &&
            mesa.reserva.dia === _hoy.getDate();

        // Pintar morado solo si faltan <= notif_minutos O ya pasó la hora
        const _tsRes      = _reservaEsHoy && mesa.reserva.timestamp ? mesa.reserva.timestamp : null;
        const _notifMs    = ((mesa.reserva && mesa.reserva.notifMin) || 15) * 60 * 1000;
        const _enVentana  = _tsRes !== null && ((_tsRes - Date.now()) <= _notifMs);

        if (mesa.estado === 'cobro') {
            estadoClase = 'en-cobro';
            estadoLabel = 'EN COBRO';
            estadoIcon  = '<i class="fas fa-cash-register"></i>';
        } else if (mesa.estado === 'reservada' || (!tienePedidos && _enVentana && mesa.estado !== 'ocupada')) {
            // Morado SOLO si: BD ya la marcó reservada, O estamos dentro de los notif_minutos
            estadoClase = 'reservada';
            estadoLabel = 'RESERVADA';
            estadoIcon  = '<i class="fas fa-calendar-check"></i>';
        } else if (mesa.estado === 'ocupada' || tienePedidos || totalMesa > 0) {
            // Ocupada si: BD dice ocupada, O tiene pedidos locales, O tiene consumo
            estadoClase = 'ocupada';
            estadoLabel = 'OCUPADA';
            estadoIcon  = '<i class="fas fa-user-check"></i>';
        }

        // Tiempo de ocupación
        let tiempoHTML = '';
        let alertaClase = '';
        if (mesa.horaOcupacion && estadoClase === 'ocupada') {
            const tiempo = calcularTiempoOcupacion(mesa.horaOcupacion);
            const minutos = getMinutosOcupacion(mesa.horaOcupacion);
            alertaClase = minutos >= ALERTA_MINUTOS ? ' alerta-tiempo' : '';
            tiempoHTML = `<div class="mesa-tiempo${minutos >= ALERTA_MINUTOS ? ' mesa-tiempo-alerta' : ''}">
                <i class="fas fa-clock"></i> ${tiempo}${minutos >= ALERTA_MINUTOS ? ' <i class="fas fa-triangle-exclamation"></i>' : ''}
            </div>`;
        }

        // Info de reserva — solo mostrar en la card si es HOY
        let reservaHTML = '';
        if (_reservaEsHoy) {
            const r = mesa.reserva;
            const diaSem  = DIAS_SEMANA[new Date(r.anio, r.mesIndex, r.dia).getDay()] || '';
            const mesNom  = MESES[r.mesIndex] ? MESES[r.mesIndex].substring(0,3) : '';
            const horaStr = `${r.hora12 || 12}:${String(r.minuto || 0).padStart(2,'0')} ${r.ampm || 'PM'}`;
            const evento  = r.evento ? `<div class="mesa-reserva-evento"><i class="fas fa-star"></i> ${r.evento}</div>` : '';
            reservaHTML = `
            <div class="mesa-reserva-info">
                <i class="fas fa-calendar-check"></i>
                <span>${horaStr} · ${r.nombre || '–'} · ${r.pax || 2} pax</span>
            </div>${evento}`;
        }

        // Resumen de consumo
        let consumoHTML = '';
        if (totalMesa > 0 && mesa.pedido.length > 0) {
            const items = mesa.pedido.slice(0, 2);
            const extra = mesa.pedido.length > 2 ? `<span class="mesa-consumo-mas">+${mesa.pedido.length - 2} más</span>` : '';
            consumoHTML = `<div class="mesa-consumo">
                ${items.map(i => `<span>${i.cantidad}× ${i.nombre}</span>`).join('')}
                ${extra}
            </div>`;
        }

        // ── Acción al hacer clic según estado ────────────────────
        let clickAccion = `abrirMesa(${mesa.id})`;
        if (estadoClase === 'reservada') {
            // Mesa reservada para HOY → preguntar si llegó el cliente
            clickAccion = `abrirModalLlegadaCliente(${mesa.id})`;
        } else if (estadoClase === 'ocupada' || mesa.estado === 'entregado') {
            clickAccion = `abrirResumenMesa(${mesa.id})`;
        } else if (estadoClase === 'en-cobro') {
            clickAccion = `abrirResumenMesa(${mesa.id})`;
        }

        // ── Mini-lista de consumo mejorada ────────────────────
        let consumoDetalleHTML = '';
        if (mesa.pedido && mesa.pedido.length > 0) {
            const itemsAMostrar = mesa.pedido.slice(0, 3);
            const masItems = mesa.pedido.length > 3 ? mesa.pedido.length - 3 : 0;
            consumoDetalleHTML = `<div class="mesa-consumo-lista">
                ${itemsAMostrar.map(i => `
                    <div class="mesa-consumo-item">
                        <span class="mesa-consumo-qty">${i.cantidad}×</span>
                        <span class="mesa-consumo-nom">${i.nombre}</span>
                        <span class="mesa-consumo-sub estado-${i.estado||'pendiente'}">${i.estado === 'listo' ? '✓' : i.estado === 'preparando' ? '⏳' : '·'}</span>
                    </div>`).join('')}
                ${masItems > 0 ? `<div class="mesa-consumo-mas">+${masItems} más</div>` : ''}
            </div>`;
        }

        html += `
        <div class="mesa mesa-${estadoClase}${alertaClase}" onclick="${clickAccion}">
            <div class="mesa-header">
                <span class="mesa-numero">MESA ${mesa.numero || mesa.id}</span>
                <span class="mesa-badge badge-${estadoClase}">${estadoIcon} ${estadoLabel}</span>
            </div>
            ${tiempoHTML}
            ${reservaHTML}
            <div class="mesa-total">
                ${(() => {
                    const consumo = totalMesa > 0 ? totalMesa : (mesa._totalConsumoBD || 0);
                    if (consumo > 0) {
                        return `<span class="mesa-monto">$${consumo.toFixed(2)}</span>`;
                    } else if (estadoClase === 'ocupada' || estadoClase === 'en-cobro') {
                        // Mostrar "Cargando" solo si la mesa tiene pedidos en tránsito
                        return mesa.pedido && mesa.pedido.length > 0
                            ? `<span class="mesa-disponible" style="color:var(--accent,#4A7A3D);font-style:italic;font-size:12px;">Cargando consumo...</span>`
                            : `<span class="mesa-disponible" style="font-style:italic;font-size:12px;">Sin consumo registrado</span>`;
                    } else {
                        return `<span class="mesa-disponible">Sin consumo</span>`;
                    }
                })()}
            </div>
            ${consumoDetalleHTML}
            <div class="mesa-acciones" onclick="event.stopPropagation()">
                ${(estadoClase === 'ocupada' || mesa.estado === 'entregado') ? `
                <button class="btn-mesa-accion btn-agregar-mas" onclick="event.stopPropagation(); abrirMesa(${mesa.id})" title="Agregar productos">
                    <i class="fas fa-plus"></i>
                </button>` : ''}
            </div>
        </div>`;
    });

    // Asignar TODO el HTML de una sola vez al DOM
    contenedor.innerHTML = html;
}

// =============================================================
// SISTEMA DE RESERVAS — CALENDARIO COMPLETO
// =============================================================

const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
               'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const DIAS_SEMANA = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

// Estado del calendario
let calEstado = {
    anio:      new Date().getFullYear(),
    mes:       new Date().getMonth(),
    diaSelec:  null,
    hora:      12,
    minuto:    0,
    ampm:      'PM',
    pax:       2,
    notifMin:  15
};

function abrirModalReserva(idMesa) {
    const idNum = parseInt(idMesa, 10);
    const mesa  = mesas.find(m => m.id === idNum);
    if (!mesa) return;

    document.getElementById('reserva-mesa-titulo').innerText = `RESERVAR — MESA ${idNum}`;
    document.getElementById('reserva-mesa-id').value = idNum;

    if (mesa.reserva && mesa.reserva.anio) {
        // Reserva con formato correcto del modal
        const r = mesa.reserva;
        calEstado.anio     = r.anio     || new Date().getFullYear();
        calEstado.mes      = r.mesIndex !== undefined ? r.mesIndex : new Date().getMonth();
        calEstado.diaSelec = r.dia      || null;
        calEstado.hora     = r.hora12   || 12;
        calEstado.minuto   = r.minuto   !== undefined ? r.minuto : 0;
        calEstado.ampm     = r.ampm     || 'PM';
        calEstado.pax      = r.pax      || 2;
        calEstado.notifMin = r.notifMin !== undefined ? r.notifMin : 15;

        document.getElementById('reserva-nombre').value = r.nombre || '';
        document.getElementById('reserva-nota').value   = r.nota   || '';

        const info = document.getElementById('reserva-actual-info');
        info.style.display = 'flex';
        const diaSem = DIAS_SEMANA[new Date(r.anio, r.mesIndex, r.dia).getDay()] || '';
        info.innerHTML = `<i class="fas fa-calendar-check"></i>
            Reserva actual: <strong>${r.nombre || 'Sin nombre'}</strong>
            — ${diaSem} ${r.dia} de ${MESES[r.mesIndex] || ''}
            ${r.hora12}:${String(r.minuto).padStart(2,'0')} ${r.ampm}`;
    } else {
        // Nueva reserva — valores por defecto
        const hoy = new Date();
        calEstado.anio     = hoy.getFullYear();
        calEstado.mes      = hoy.getMonth();
        calEstado.diaSelec = null;
        calEstado.hora     = 12;
        calEstado.minuto   = 0;
        calEstado.ampm     = 'PM';
        calEstado.pax      = 2;
        calEstado.notifMin = 15;
        document.getElementById('reserva-nombre').value = '';
        document.getElementById('reserva-nota').value   = '';
        document.getElementById('reserva-actual-info').style.display = 'none';
    }

    _calSyncControles();
    _calRenderGrid();
    _calActualizarResumen();
    document.getElementById('modal-reserva').classList.add('activo');
}

function cerrarModalReserva() {
    document.getElementById('modal-reserva').classList.remove('activo');
}

// ═══════════════════════════════════════════════════════════════
//  MODAL LLEGADA DE CLIENTE (mesa reservada)
// ═══════════════════════════════════════════════════════════════
let _llegadaMesaId = null;

function abrirModalLlegadaCliente(idMesa) {
    const mesa = mesas.find(m => m.id === idMesa);
    if (!mesa || !mesa.reserva) { abrirMesa(idMesa); return; }

    _llegadaMesaId = idMesa;
    const r = mesa.reserva;
    const horaStr = `${r.hora12 || 12}:${String(r.minuto||0).padStart(2,'0')} ${r.ampm||'PM'}`;

    document.getElementById('llegada-mesa-num').textContent  = idMesa;
    document.getElementById('llegada-cliente-nombre').textContent = r.nombre || '—';
    document.getElementById('llegada-info').textContent =
        `${r.pax || 2} personas · ${horaStr}${r.evento ? ' · ' + r.evento : ''}`;

    document.getElementById('modal-llegada-cliente').classList.add('activo');
}

function confirmarLlegadaCliente() {
    if (!_llegadaMesaId) return;
    document.getElementById('modal-llegada-cliente').classList.remove('activo');
    // Poner mesa como ocupada y abrir el POS
    const mesa = mesas.find(m => m.id === _llegadaMesaId);
    if (mesa) {
        mesa.estado = 'ocupada';
        mesa.horaOcupacion = Date.now();
        // Mantener la reserva en el objeto pero no en la pantalla
        _guardarEstadoMesas();
        try {
            fetch('mesas.php?accion=estado', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_mesa: _llegadaMesaId, estado: 'ocupada' })
            });
        } catch(e) {}
    }
    abrirMesa(_llegadaMesaId);
}

function cancelarReservaMesa() {
    if (!_llegadaMesaId) return;
    document.getElementById('modal-llegada-cliente').classList.remove('activo');
    limpiarReserva(_llegadaMesaId);
}

// ═══════════════════════════════════════════════════════════════
//  MODAL RESERVA GENERAL (botón RESERVAS en pantalla de mesas)
//  - Mesero/todo el personal lo puede abrir
//  - Muestra grid visual de mesas y mini calendario
// ═══════════════════════════════════════════════════════════════
let _grvEstado = { anio: new Date().getFullYear(), mes: new Date().getMonth(), dia: null, hora: 12, minuto: 0, ampm: 'PM', pax: 2, mesaId: null };
let _reservasBD = []; // reservas cargadas desde BD para saber disponibilidad

async function abrirModalReservaGeneral() {
    _grvEstado = { anio: new Date().getFullYear(), mes: new Date().getMonth(), dia: null, hora: 12, minuto: 0, ampm: 'PM', pax: 2, mesaId: null };
    document.getElementById('grv-nombre').value  = '';
    document.getElementById('grv-nota').value    = '';
    document.getElementById('grv-evento').value  = '';
    document.getElementById('grv-pax').textContent = '2';
    document.getElementById('grv-error').style.display = 'none';
    document.getElementById('grv-res-mesa').textContent = 'Selecciona una mesa y una fecha';
    document.getElementById('grv-res-fecha').textContent = '';
    document.getElementById('grv-hora').textContent = '12';
    document.getElementById('grv-min').textContent  = '00';
    document.getElementById('grv-am').classList.remove('activo');
    document.getElementById('grv-pm').classList.add('activo');

    // Cargar reservas Y estados de mesas desde BD (siempre frescos, nunca del localStorage)
    try {
        const [rRes, mRes] = await Promise.all([
            fetch('reservas.php?accion=listar'),
            fetch('mesas.php?accion=listar')
        ]);
        const rData = await rRes.json();
        const mData = await mRes.json();

        // Actualizar _reservasBD con solo las activas
        if (rData.ok) _reservasBD = (rData.reservas || []).filter(r => r.activa == 1);

        // Sincronizar estados de mesas desde BD para que no haya estados obsoletos del localStorage
        if (mData.ok && Array.isArray(mData.mesas)) {
            mData.mesas.forEach(bdMesa => {
                const id = parseInt(bdMesa.id_mesa, 10);
                const local = mesas.find(m => m.id === id);
                if (local) {
                    // Siempre tomar el estado real de BD — elimina estados 'reservada' fantasma
                    local.estado = bdMesa.estado || 'libre';
                    local.numero = parseInt(bdMesa.numero_mesa || id);
                }
            });
            _guardarEstadoMesas(); // Guardar estados corregidos en localStorage
        }
    } catch(e) { _reservasBD = []; }

    _grvRenderMesas();
    _grvRenderCal();
    document.getElementById('modal-reserva-general').classList.add('activo');
}

function _grvRenderMesas() {
    const grid = document.getElementById('grv-mesas-grid');
    if (!grid) return;

    grid.innerHTML = mesas.map(m => {
        const esSelec       = _grvEstado.mesaId === m.id;
        // En el picker de reservas NUNCA bloqueamos — cualquier mesa se puede reservar
        // (ocupada ahora puede tener reserva para más tarde; cobro también)
        // Solo mostramos indicadores visuales del estado actual
        const estaOcupada   = m.estado === 'ocupada';
        const estaEnCobro   = m.estado === 'cobro';
        const estaReservada = m.estado === 'reservada';

        // ¿Ya tiene reserva activa en la fecha elegida?
        const tieneResEseDia = _grvEstado.dia !== null && _reservasBD.some(r => {
            if (!r.activa) return false;
            const rid = parseInt(r.id_mesa_fk || r.id_mesa);
            if (rid !== m.id) return false;
            const f   = new Date(r.fecha_reserva.replace(' ','T')); f.setHours(0,0,0,0);
            const sel = new Date(_grvEstado.anio, _grvEstado.mes, _grvEstado.dia); sel.setHours(0,0,0,0);
            return f.getTime() === sel.getTime();
        });

        // ── Estilos 100% inline — nunca dependen de estilos.css ─────
        let bg      = 'var(--bg-app,#F0F4EE)';
        let border  = '2px solid var(--border-color,#D4E0CF)';
        let color   = 'var(--text-main,#1C2B18)';
        let iconClr = 'var(--text-muted,#6B7F65)';
        let shadow  = 'none';
        let scale   = '';

        if (esSelec) {
            bg      = 'var(--accent,#4A7A3D)';
            border  = '2px solid var(--accent,#4A7A3D)';
            color   = '#fff';
            iconClr = '#fff';
            shadow  = '0 4px 14px rgba(74,122,61,.45)';
            scale   = 'scale(1.07)';
        } else if (tieneResEseDia) {
            bg      = 'rgba(176,125,18,.09)';
            border  = '2px solid #B07D12';
            color   = '#7A5000';
            iconClr = '#B07D12';
        } else if (estaEnCobro) {
            bg      = 'rgba(107,47,170,.07)';
            border  = '2px dashed rgba(107,47,170,.4)';
            iconClr = 'rgba(107,47,170,.6)';
        } else if (estaOcupada) {
            bg      = 'rgba(160,50,10,.06)';
            border  = '2px dashed rgba(160,50,10,.35)';
            iconClr = 'rgba(160,50,10,.55)';
        } else if (estaReservada) {
            bg      = 'rgba(26,90,138,.06)';
            border  = '2px dashed rgba(26,90,138,.35)';
            iconClr = 'rgba(26,90,138,.55)';
        }

        // TODAS las mesas son clickeables en el picker de reservas
        const estilo = [
            `background:${bg}`, `border:${border}`, `color:${color}`,
            'opacity:1', 'cursor:pointer', 'pointer-events:auto',
            `box-shadow:${shadow}`, `transform:${scale}`,
            'aspect-ratio:1', 'display:flex', 'flex-direction:column',
            'align-items:center', 'justify-content:center',
            'border-radius:10px', 'gap:4px', 'font-size:12px', 'font-weight:700',
            'transition:all .18s', 'user-select:none', 'position:relative',
        ].join(';');

        // Badge de estado informativo
        let badge = '';
        if (esSelec) {
            badge = `<span style="position:absolute;top:3px;right:4px;font-size:9px;background:rgba(255,255,255,.3);color:#fff;border-radius:4px;padding:1px 4px;font-weight:800;">✓</span>`;
        } else if (tieneResEseDia) {
            badge = `<span style="position:absolute;top:3px;right:3px;font-size:8px;background:#B07D12;color:#fff;border-radius:4px;padding:1px 4px;">Res</span>`;
        } else if (estaEnCobro) {
            badge = `<span style="position:absolute;top:3px;right:3px;font-size:8px;background:rgba(107,47,170,.7);color:#fff;border-radius:4px;padding:1px 4px;">Cobro</span>`;
        } else if (estaOcupada) {
            badge = `<span style="position:absolute;top:3px;right:3px;font-size:8px;background:rgba(160,50,10,.7);color:#fff;border-radius:4px;padding:1px 4px;">Ocup.</span>`;
        } else if (estaReservada) {
            badge = `<span style="position:absolute;top:3px;right:3px;font-size:8px;background:rgba(26,90,138,.7);color:#fff;border-radius:4px;padding:1px 4px;">Res.hoy</span>`;
        }

        return `<div style="${estilo}" onclick="_grvSelMesa(${m.id})">
            ${badge}
            <i class="fas fa-utensils" style="font-size:18px;color:${iconClr};"></i>
            <span>Mesa ${m.numero}</span>
        </div>`;
    }).join('');
}

function _grvSelMesa(id) {
    _grvEstado.mesaId = id;
    _grvRenderMesas();
    _grvActResumen();
}

function _grvRenderCal() {
    const { anio, mes, dia } = _grvEstado;
    const lbl = document.getElementById('grv-cal-mes-label');
    if (lbl) lbl.textContent = `${['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][mes]} ${anio}`;
    const grid = document.getElementById('grv-cal-grid');
    if (!grid) return;
    const hoy = new Date(); hoy.setHours(0,0,0,0);
    const primerDia = new Date(anio,mes,1).getDay();
    const diasMes   = new Date(anio,mes+1,0).getDate();

    // Días con reservas de la mesa seleccionada (de BD)
    const diasRes = new Set();
    if (_grvEstado.mesaId) {
        _reservasBD.forEach(r => {
            const mesaId = r.id_mesa_fk || r.id_mesa;
            if (!r.activa || mesaId != _grvEstado.mesaId) return;
            const f = new Date(r.fecha_reserva.replace(' ','T'));
            if (f.getFullYear()===anio && f.getMonth()===mes) diasRes.add(f.getDate());
        });
    }

    let html = '';
    for (let i=0; i<primerDia; i++) html += '<div style="aspect-ratio:1;"></div>';
    for (let d=1; d<=diasMes; d++) {
        const esPas = new Date(anio,mes,d) < hoy;
        const esHoy = !esPas && new Date(anio,mes,d).getTime()===hoy.getTime();
        const esSel = d === dia;
        const esRes = diasRes.has(d);

        // Estilos inline para que no dependan de estilos.css externo
        let bg      = 'transparent';
        let clrDay  = esPas ? 'var(--text-muted,#9aaa94)' : 'var(--text-main,#1C2B18)';
        let fontW   = 'normal';
        let opacity = esPas ? '0.38' : '1';
        let border  = 'none';
        let borderR = '8px';
        let cursor  = esPas ? 'default' : 'pointer';

        if (esSel) {
            bg     = 'var(--accent,#4A7A3D)';
            clrDay = '#fff';
            fontW  = '800';
            border = 'none';
        } else if (esHoy) {
            bg     = 'rgba(74,122,61,.15)';
            clrDay = 'var(--accent,#4A7A3D)';
            fontW  = '700';
            border = '2px solid var(--accent,#4A7A3D)';
        } else if (esRes) {
            bg    = 'rgba(176,125,18,.1)';
            border = '1px solid rgba(176,125,18,.4)';
            fontW = '700';
        }

        const dot = esRes && !esSel
            ? '<span style="position:absolute;bottom:2px;width:4px;height:4px;border-radius:50%;background:' + (esSel?'#fff':'#B07D12') + ';"></span>'
            : '';

        const stiloDia = [
            `background:${bg}`, `color:${clrDay}`, `font-weight:${fontW}`,
            `opacity:${opacity}`, `border:${border}`, `border-radius:${borderR}`,
            `cursor:${cursor}`, 'aspect-ratio:1', 'display:flex', 'flex-direction:column',
            'align-items:center', 'justify-content:center',
            'font-size:11px', 'position:relative', 'user-select:none',
            'transition:background .15s'
        ].join(';');

        const onclick = esPas ? '' : `onclick="grvSelDia(${d})"`;
        html += `<div style="${stiloDia}" ${onclick}>${d}${dot}</div>`;
    }
    grid.innerHTML = html;
}

function grvCalMes(delta) {
    _grvEstado.mes += delta;
    if (_grvEstado.mes > 11) { _grvEstado.mes = 0; _grvEstado.anio++; }
    if (_grvEstado.mes < 0)  { _grvEstado.mes = 11; _grvEstado.anio--; }
    _grvEstado.dia = null;
    _grvRenderCal(); _grvRenderMesas(); _grvActResumen();
}

function grvSelDia(d) {
    _grvEstado.dia = d;
    _grvRenderCal(); _grvRenderMesas(); _grvActResumen();
}

function grvHora(tipo, delta) {
    if (tipo === 'h') {
        _grvEstado.hora += delta;
        if (_grvEstado.hora > 12) _grvEstado.hora = 1;
        if (_grvEstado.hora < 1)  _grvEstado.hora = 12;
    } else {
        _grvEstado.minuto += delta * 15;
        if (_grvEstado.minuto >= 60) _grvEstado.minuto = 0;
        if (_grvEstado.minuto < 0)   _grvEstado.minuto = 45;
    }
    document.getElementById('grv-hora').textContent = _grvEstado.hora;
    document.getElementById('grv-min').textContent  = String(_grvEstado.minuto).padStart(2,'0');
    _grvActResumen();
}

function grvAmPm(v) {
    _grvEstado.ampm = v;
    document.getElementById('grv-am').classList.toggle('activo', v==='AM');
    document.getElementById('grv-pm').classList.toggle('activo', v==='PM');
    _grvActResumen();
}

function grvPax(d) {
    _grvEstado.pax = Math.max(1, Math.min(30, _grvEstado.pax + d));
    document.getElementById('grv-pax').textContent = _grvEstado.pax;
}

function _grvActResumen() {
    const mesaEl = document.getElementById('grv-res-mesa');
    const fechaEl = document.getElementById('grv-res-fecha');
    if (!mesaEl) return;
    const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    if (_grvEstado.mesaId) {
        const mesaObj = mesas.find(m => m.id === _grvEstado.mesaId);
        const numMesa = mesaObj ? mesaObj.numero : _grvEstado.mesaId;
        mesaEl.textContent = `Mesa ${numMesa} seleccionada ✓`;
    } else {
        mesaEl.textContent = 'Selecciona una mesa';
    }
    fechaEl.textContent = _grvEstado.dia
        ? `${_grvEstado.dia} de ${MESES[_grvEstado.mes]} ${_grvEstado.anio} · ${_grvEstado.hora}:${String(_grvEstado.minuto).padStart(2,'0')} ${_grvEstado.ampm}`
        : 'Selecciona una fecha';
}

async function grvGuardarReserva() {
    const nombre = document.getElementById('grv-nombre').value.trim();
    const errEl  = document.getElementById('grv-error');
    errEl.style.display = 'none';

    if (!_grvEstado.mesaId) { errEl.textContent = 'Selecciona una mesa'; errEl.style.display = 'block'; return; }
    if (!_grvEstado.dia)    { errEl.textContent = 'Selecciona una fecha'; errEl.style.display = 'block'; return; }
    if (!nombre)            { errEl.textContent = 'Escribe el nombre del cliente'; errEl.style.display = 'block'; return; }

    let h24 = _grvEstado.hora;
    if (_grvEstado.ampm === 'PM' && h24 !== 12) h24 += 12;
    if (_grvEstado.ampm === 'AM' && h24 === 12) h24 = 0;

    const mes2 = String(_grvEstado.mes + 1).padStart(2,'0');
    const dia2 = String(_grvEstado.dia).padStart(2,'0');
    const h2   = String(h24).padStart(2,'0');
    const m2   = String(_grvEstado.minuto).padStart(2,'0');
    const fechaISO = `${_grvEstado.anio}-${mes2}-${dia2} ${h2}:${m2}:00`;
    const evento   = document.getElementById('grv-evento').value;
    const nota     = document.getElementById('grv-nota').value.trim();
    const notaFinal = [evento, nota].filter(Boolean).join(' — ');

    try {
        const res  = await fetch('reservas.php?accion=guardar', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre, fecha_reserva: fechaISO,
                num_personas: _grvEstado.pax,
                nota: notaFinal, notif_minutos: 15,
                id_mesa: _grvEstado.mesaId, evento
            })
        });
        const data = await res.json();
        if (data.ok) {
            // Si la reserva es para HOY → marcar mesa como reservada (cambia color en grid)
            // Si es para otro día → solo guardar en BD, NO cambiar color de la mesa
            const hoy = new Date(); hoy.setHours(0,0,0,0);
            const sel = new Date(_grvEstado.anio, _grvEstado.mes, _grvEstado.dia); sel.setHours(0,0,0,0);
            const esParaHoy = sel.getTime() === hoy.getTime();
            const mesa = mesas.find(m => m.id === _grvEstado.mesaId);
            const numMesa = mesa ? mesa.numero : _grvEstado.mesaId;
            if (esParaHoy && mesa) {
                mesa.reserva = {
                    anio: _grvEstado.anio, mesIndex: _grvEstado.mes,
                    dia: _grvEstado.dia, hora12: _grvEstado.hora,
                    minuto: _grvEstado.minuto, ampm: _grvEstado.ampm,
                    hora24: h24, pax: _grvEstado.pax,
                    nombre, nota: notaFinal, evento,
                    timestamp: new Date(_grvEstado.anio, _grvEstado.mes, _grvEstado.dia, h24, _grvEstado.minuto).getTime()
                };
                mesa.estado = 'reservada';
                _guardarEstadoMesas();
            }
            document.getElementById('modal-reserva-general').classList.remove('activo');
            renderizarMesas();
            const cuandoTxt = esParaHoy ? 'para hoy' : `para el ${_grvEstado.dia}/${_grvEstado.mes+1}/${_grvEstado.anio}`;
            _mostrarToastReserva(`Reserva confirmada — Mesa ${numMesa} · ${nombre} ✅ (${cuandoTxt})`);
        } else {
            errEl.textContent = data.error || 'Error al guardar';
            errEl.style.display = 'block';
        }
    } catch(e) {
        errEl.textContent = 'Sin conexión al servidor. La reserva no se guardó.';
        errEl.style.display = 'block';
    }
}

// ── Render del grid de días ──────────────────────────────────
function _calRenderGrid() {
    const grid = document.getElementById('cal-grid');
    const label = document.getElementById('cal-mes-label');
    const { anio, mes, diaSelec } = calEstado;

    label.textContent = `${MESES[mes]} ${anio}`;

    const primerDia   = new Date(anio, mes, 1).getDay();
    const diasEnMes   = new Date(anio, mes + 1, 0).getDate();
    const hoy         = new Date();
    const hoyAnio     = hoy.getFullYear();
    const hoyMes      = hoy.getMonth();
    const hoyDia      = hoy.getDate();

    let html = '';

    // Celdas vacías antes del primer día
    for (let i = 0; i < primerDia; i++) {
        html += '<div class="cal-dia cal-dia-vacio"></div>';
    }

    for (let d = 1; d <= diasEnMes; d++) {
        const esPasado = (anio < hoyAnio) ||
                         (anio === hoyAnio && mes < hoyMes) ||
                         (anio === hoyAnio && mes === hoyMes && d < hoyDia);
        const esHoy     = anio === hoyAnio && mes === hoyMes && d === hoyDia;
        const esSelec   = d === diaSelec;

        // Buscar si hay reservas de otras mesas ese día
        const tieneReserva = mesas.some(m =>
            m.reserva && m.reserva.anio === anio &&
            m.reserva.mesIndex === mes && m.reserva.dia === d &&
            m.id !== parseInt(document.getElementById('reserva-mesa-id').value)
        );

        let clases = 'cal-dia';
        if (esPasado)      clases += ' cal-dia-pasado';
        if (esHoy)         clases += ' cal-dia-hoy';
        if (esSelec)       clases += ' cal-dia-selec';
        if (tieneReserva)  clases += ' cal-dia-reservado';

        const onclick = esPasado ? '' : `onclick="calSelecDia(${d})"`;
        const dot = tieneReserva ? '<span class="cal-dia-dot"></span>' : '';
        html += `<div class="${clases}" ${onclick}>${d}${dot}</div>`;
    }

    grid.innerHTML = html;
}

function calSelecDia(d) {
    calEstado.diaSelec = d;
    _calRenderGrid();
    _calActualizarResumen();
}

function calCambiarMes(delta) {
    calEstado.mes += delta;
    if (calEstado.mes > 11) { calEstado.mes = 0;  calEstado.anio++; }
    if (calEstado.mes < 0)  { calEstado.mes = 11; calEstado.anio--; }
    calEstado.diaSelec = null;
    _calRenderGrid();
    _calActualizarResumen();
}

// ── Controles hora ───────────────────────────────────────────
function calCambiarHora(tipo, delta) {
    if (tipo === 'h') {
        calEstado.hora += delta;
        if (calEstado.hora > 12) calEstado.hora = 1;
        if (calEstado.hora < 1)  calEstado.hora = 12;
    } else {
        calEstado.minuto += delta * 15;
        if (calEstado.minuto >= 60) calEstado.minuto = 0;
        if (calEstado.minuto < 0)   calEstado.minuto = 45;
    }
    _calSyncControles();
    _calActualizarResumen();
}

function calSetAmPm(val) {
    calEstado.ampm = val;
    document.getElementById('cal-am').classList.toggle('activo', val === 'AM');
    document.getElementById('cal-pm').classList.toggle('activo', val === 'PM');
    _calActualizarResumen();
}

function calCambiarPax(delta) {
    calEstado.pax = Math.max(1, Math.min(20, calEstado.pax + delta));
    document.getElementById('reserva-pax').textContent = calEstado.pax;
}

function calSelNotif(btn, minutos) {
    document.querySelectorAll('.reserva-notif-btn').forEach(b => b.classList.remove('activo'));
    btn.classList.add('activo');
    calEstado.notifMin = minutos;
}

function _calSyncControles() {
    document.getElementById('cal-hora').textContent = calEstado.hora;
    document.getElementById('cal-min').textContent  = String(calEstado.minuto).padStart(2, '0');
    document.getElementById('cal-am').classList.toggle('activo', calEstado.ampm === 'AM');
    document.getElementById('cal-pm').classList.toggle('activo', calEstado.ampm === 'PM');
    document.getElementById('reserva-pax').textContent = calEstado.pax;
}

function _calActualizarResumen() {
    const { diaSelec, mes, anio, hora, minuto, ampm } = calEstado;
    const fechaTxt = document.getElementById('rres-fecha-txt');
    const horaTxt  = document.getElementById('rres-hora-txt');

    if (diaSelec) {
        const fecha = new Date(anio, mes, diaSelec);
        const diaSem = DIAS_SEMANA[fecha.getDay()];
        fechaTxt.textContent = `${diaSem} ${diaSelec} de ${MESES[mes]} ${anio}`;
        fechaTxt.parentElement.classList.add('resumen-ok');
    } else {
        fechaTxt.textContent = 'Selecciona una fecha';
        fechaTxt.parentElement.classList.remove('resumen-ok');
    }

    horaTxt.textContent = `${hora}:${String(minuto).padStart(2,'0')} ${ampm}`;
}

// ── Guardar reserva ──────────────────────────────────────────
function guardarReserva() {
    const idMesa = parseInt(document.getElementById('reserva-mesa-id').value);
    const mesa   = mesas.find(m => m.id === idMesa);
    if (!mesa) return;

    if (!calEstado.diaSelec) {
        _mostrarErrorReserva('Selecciona un día en el calendario');
        return;
    }

    const nombre = document.getElementById('reserva-nombre').value.trim();
    if (!nombre) {
        _mostrarErrorReserva('Escribe el nombre del cliente');
        return;
    }

    // Convertir a hora 24 para calcular timestamp
    let h24 = calEstado.hora;
    if (calEstado.ampm === 'PM' && h24 !== 12) h24 += 12;
    if (calEstado.ampm === 'AM' && h24 === 12) h24 = 0;

    const fechaReserva = new Date(
        calEstado.anio, calEstado.mes, calEstado.diaSelec,
        h24, calEstado.minuto, 0
    );

    const _evtReserva = document.getElementById('reserva-evento') ? document.getElementById('reserva-evento').value : '';
    const _notaReserva = document.getElementById('reserva-nota').value.trim();
    mesa.reserva = {
        anio:      calEstado.anio,
        mesIndex:  calEstado.mes,
        dia:       calEstado.diaSelec,
        hora12:    calEstado.hora,
        minuto:    calEstado.minuto,
        ampm:      calEstado.ampm,
        hora24:    h24,
        timestamp: fechaReserva.getTime(),
        pax:       calEstado.pax,
        nombre,
        nota:      [_evtReserva, _notaReserva].filter(Boolean).join(' — '),
        evento:    _evtReserva,
        notifMin:  calEstado.notifMin
    };

    // Guardar en localStorage para persistencia y polling de notificaciones
    try {
        const reservas = JSON.parse(localStorage.getItem('reservas_jardín') || '[]')
            .filter(r => !(r.idMesa === idMesa));
        reservas.push({ idMesa, ...mesa.reserva });
        localStorage.setItem('reservas_jardín', JSON.stringify(reservas));
    } catch(e) {}

    // ── GUARDAR EN BD: tabla reserva ─────────────────────────
    _guardarReservaEnBD(idMesa, mesa, h24);

    cerrarModalReserva();
    renderizarMesas();
    _mostrarToastReserva(`✅ Reserva guardada — Mesa ${idMesa} · ${nombre}`);
}

function limpiarReserva(idMesa) {
    const mesa = mesas.find(m => m.id === idMesa);
    if (!mesa) return;

    // Cancelar en BD: tabla reserva → activa=0, mesa → libre
    if (mesa.reserva && mesa.reserva._id_bd) {
        fetch('reservas.php?accion=cancelar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_reserva: mesa.reserva._id_bd })
        })
        .then(r => r.json())
        .then(data => { if (!data.ok) console.warn('⚠ No se canceló reserva en BD:', data.error); })
        .catch(() => {});
    }

    mesa.reserva = null;
    try {
        const reservas = JSON.parse(localStorage.getItem('reservas_jardín') || '[]')
            .filter(r => r.idMesa !== idMesa);
        localStorage.setItem('reservas_jardín', JSON.stringify(reservas));
    } catch(e) {}
    renderizarMesas();
}

function _mostrarErrorReserva(msg) {
    const info = document.getElementById('reserva-actual-info');
    info.style.display = 'flex';
    info.className = 'reserva-actual-banner reserva-error-banner';
    info.innerHTML = `<i class="fas fa-triangle-exclamation"></i> ${msg}`;
    setTimeout(() => {
        info.style.display = 'none';
        info.className = 'reserva-actual-banner';
    }, 3000);
}

function _mostrarToastReserva(msg) {
    let toast = document.getElementById('toast-reserva');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-reserva';
        toast.className = 'toast-reserva';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}

// ── POLLING: notificaciones automáticas de reservas próximas ─
let _notifReservasVistas = new Set();

function _iniciarPollingReservas() {
    setInterval(() => {
        const ahora = Date.now();
        mesas.forEach(mesa => {
            if (!mesa.reserva) return;
            const { timestamp, notifMin, idMesa, nombre, dia, mesIndex, anio, hora12, minuto, ampm } = mesa.reserva;
            if (!notifMin || notifMin === 0) return;

            const msAntes  = notifMin * 60 * 1000;
            const diffMs   = timestamp - ahora;
            const clave    = `${mesa.id}-${timestamp}`;

            // Mostrar si faltan ≤ notifMin minutos y no se ha mostrado aún
            if (diffMs > 0 && diffMs <= msAntes && !_notifReservasVistas.has(clave)) {
                _notifReservasVistas.add(clave);
                const minFaltan = Math.ceil(diffMs / 60000);
                _dispararNotifReserva(
                    `Mesa ${mesa.id} — ${mesa.reserva.nombre}`,
                    `Reserva en ${minFaltan} min · ${hora12}:${String(minuto).padStart(2,'0')} ${ampm} · ${mesa.reserva.pax} personas`,
                    mesa.id
                );
            }

            // Recordatorio: reserva exactamente ahora (±30 s)
            if (Math.abs(diffMs) <= 30000) {
                const claveAhora = `ahora-${mesa.id}-${timestamp}`;
                if (!_notifReservasVistas.has(claveAhora)) {
                    _notifReservasVistas.add(claveAhora);
                    _dispararNotifReserva(
                        `¡AHORA! Mesa ${mesa.id} — ${mesa.reserva.nombre}`,
                        `La reserva es en este momento · ${mesa.reserva.pax} personas`,
                        mesa.id
                    );
                }
            }
        });
    }, 30000); // Revisar cada 30 segundos
}

function _dispararNotifReserva(titulo, desc, idMesa) {
    const el   = document.getElementById('notif-reserva');
    const tit  = document.getElementById('notif-reserva-titulo');
    const des  = document.getElementById('notif-reserva-desc');

    tit.textContent = titulo;
    des.textContent = desc;
    el.style.display = 'block';

    // Notificación nativa del navegador si está permitida
    if (Notification && Notification.permission === 'granted') {
        new Notification(`📅 ${titulo}`, { body: desc, icon: '' });
    }

    clearTimeout(window._notifReservaTimer);
    window._notifReservaTimer = setTimeout(cerrarNotifReserva, 20000);
}

function cerrarNotifReserva() {
    document.getElementById('notif-reserva').style.display = 'none';
    clearTimeout(window._notifReservaTimer);
}

// Pedir permiso de notificaciones al login
function _pedirPermisoNotificaciones() {
    if (Notification && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

// Arrancar polling al cargar
document.addEventListener('DOMContentLoaded', () => {
    cargarMenuDesdeBD(); // carga menú desde BD al iniciar
    _iniciarPollingReservas();
    _pedirPermisoNotificaciones();
});

function abrirMesa(id) {
    const idNum = parseInt(id, 10);  // siempre número
    mesaActiva = mesas.find(m => m.id === idNum);
    if (!mesaActiva) {
        console.error('Mesa no encontrada:', idNum, 'mesas:', mesas.map(m=>m.id));
        alert('Error: mesa ' + idNum + ' no encontrada. Recarga la página.');
        return;
    }
    if (!mesaActiva.horaOcupacion && mesaActiva.pedido.length === 0) {
        mesaActiva.horaOcupacion = Date.now();
    }
    document.getElementById("titulo-mesa").innerText = "MESA " + (mesaActiva.numero || mesaActiva.id);
    actualizarComandaUI();
    mostrarPantalla('pos');
    // Si el menú ya cargó, renderizar inmediatamente
    // Si no, cargarMenuDesdeBD() lo hará al terminar
    if (menuSecciones && Object.keys(menuSecciones).length > 0) {
        filtrarCat('calientes', document.querySelector('.cat-btn'));
    } else {
        const cont = document.getElementById('lista-productos');
        if (cont) cont.innerHTML = '<p style="grid-column:1/-1;padding:32px;text-align:center;color:var(--text-muted,#888)"><i class="fas fa-spinner fa-spin"></i> Cargando menú...</p>';
        cargarMenuDesdeBD();
    }
}

function volverMesas() {
    mesaActiva = null;
    mostrarPantalla('mesas');
    renderizarMesas();
    _iniciarAutoRefresh();
}

// ── Auto-refrescar estados desde BD cada 10 segundos ──────────
// Así todos los operadores ven los cambios en tiempo real
function _iniciarAutoRefresh() {
    if (intervalTiempo) clearInterval(intervalTiempo);
    intervalTiempo = setInterval(function() {
        // Solo refrescar si la pantalla de mesas está activa
        const pantallaMesas = document.getElementById('mesas');
        if (pantallaMesas && pantallaMesas.classList.contains('activa')) {
            cargarMesasDesdeBD();
        }
    }, 4000); // cada 4 segundos — detecta cobros rápido
}

// --- BÚSQUEDA DE PRODUCTOS ---
function buscarProductos() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const productos = document.querySelectorAll('.prod-card');

    productos.forEach(producto => {
        const nombre = producto.querySelector('h4').innerText.toLowerCase();
        if (nombre.includes(searchTerm) || searchTerm === '') {
            producto.style.display = 'flex';
        } else {
            producto.style.display = 'none';
        }
    });
}

// --- FILTRAR CATEGORÍAS (con secciones e inline admin) ---
function filtrarCat(categoria, btnElement) {
    document.querySelectorAll('.cat-btn').forEach(btn => btn.classList.remove('activa'));
    if (btnElement) btnElement.classList.add('activa');

    const contenedor = document.getElementById("lista-productos");
    contenedor.innerHTML = "";

    const secciones = menuSecciones[categoria];
    if (!secciones) return;

    // Panel de edición solo para admin
    const sesRol = (() => { try { return JSON.parse(sessionStorage.getItem('pos_usuario')||'{}').rol || ''; } catch(e) { return ''; } })();
    const esAdmin = (categoria === 'postres' || categoria === 'bebidasTemp') && sesRol === 'admin';
    const esTemporadaSinEditar = (categoria === 'postres' || categoria === 'bebidasTemp') && sesRol !== 'admin';
    const storageKey = categoria === 'postres' ? 'postresTemporada' : 'bebidasTemporada';
    const labelSingular = categoria === 'postres' ? 'postre' : 'bebida';
    const iconoAdmin = categoria === 'postres' ? 'fa-cookie-bite' : 'fa-glass-whiskey';

    // Mesero/cajero ve temporada pero sin panel de edición
    if (esTemporadaSinEditar) {
        const items = secciones[0].items;
        if (!items || items.length === 0) {
            contenedor.innerHTML = '<p style="padding:24px;color:var(--text-muted);text-align:center;">Sin productos de temporada disponibles.</p>';
            return;
        }
        contenedor.innerHTML = items.map(prod => {
            const nombre = prod.nombre.replace(/'/g, "\'");
            return `<div class="prod-card" onclick="agregarAlPedido(${prod.id},'${nombre}',${prod.precio},'${prod.tipo || 'normal'}')">
                <h4>${prod.nombre}</h4>
                <p>$${prod.precio.toFixed(2)}</p>
            </div>`;
        }).join('');
        return;
    }

    if (esAdmin) {
        const items = secciones[0].items;

        // Admin: solo muestra aviso + productos (no formulario de agregar — usar menu.php)
        const avisoHtml = `
            <div class="admin-panel-inline" id="panel-admin-${categoria}" style="margin-bottom:12px;">
                <div class="admin-panel-header">
                    <i class="fas ${iconoAdmin}"></i>
                    <span>${secciones[0].titulo}</span>
                    <span class="admin-badge">ADMIN</span>
                </div>
                <div style="padding:10px 14px;font-size:12.5px;color:var(--text-muted,#6B7F65);display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-circle-info" style="color:var(--accent,#4A7A3D);"></i>
                    Para agregar o quitar productos de temporada usa
                    <a href="menu.php" style="color:var(--accent,#4A7A3D);font-weight:700;margin-left:4px;">Gestionar Menú</a>
                </div>
            </div>`;

        let cardsHtml = '';
        if (items.length === 0) {
            cardsHtml = '<p style="padding:20px;color:var(--text-muted,#6B7F65);text-align:center;">Sin productos de temporada. Agrégalos desde Gestionar Menú.</p>';
        } else {
            cardsHtml = items.map(prod => {
                const nombre = prod.nombre.replace(/'/g, "\'");
                return `<div class="prod-card" onclick="agregarAlPedido(${prod.id},'${nombre}',${prod.precio},'${prod.tipo || 'normal'}')">
                    <h4>${prod.nombre}</h4>
                    <p>$${prod.precio.toFixed(2)}</p>
                </div>`;
            }).join('');
        }

        contenedor.innerHTML = avisoHtml + cardsHtml;
        return;
    }

    // --- Categorías normales con secciones ---
    let htmlFinal = '';
    secciones.forEach(seccion => {
        if (!seccion.items || seccion.items.length === 0) return;
        htmlFinal += `<div class="seccion-header"><span>${seccion.titulo}</span></div>`;
        seccion.items.forEach(prod => {
            const nombre = prod.nombre.replace(/'/g, "\\'");
            const descHtml = prod.desc
                ? `<small style="font-size:11px;color:var(--text-muted);line-height:1.3;display:block;margin-top:4px;">${prod.desc}</small>`
                : '';
            htmlFinal += `<div class="prod-card" onclick="agregarAlPedido(${prod.id},'${nombre}',${prod.precio},'${prod.tipo || 'normal'}')">
                    <h4>${prod.nombre}</h4>
                    <p>$${prod.precio.toFixed(2)}</p>
                    ${descHtml}
                </div>`;
        });
    });
    if (htmlFinal) {
        contenedor.innerHTML = htmlFinal;
    } else {
        contenedor.innerHTML = '<p style="grid-column:1/-1;padding:24px;color:var(--text-muted);text-align:center;">Sin productos en esta categoría.</p>';
    }
}

// --- AGREGAR ITEM DE TEMPORADA ---
async function guardarItemTemporada(categoria, storageKey, label) {
    const nombre = document.getElementById(`admin-${categoria}-nombre`).value.trim();
    const precio = parseFloat(document.getElementById(`admin-${categoria}-precio`).value);
    if (!nombre || isNaN(precio) || precio <= 0) {
        alert(`Ingresa un nombre y precio válidos para la ${label}.`);
        return;
    }

    // Guardar en BD primero
    let nuevoId = 900 + Date.now() % 9000;
    try {
        const tipo_temp = categoria === 'bebidasTemp' ? 'bebida' : 'postre';
        const res  = await fetch('menu_api.php?accion=temporada_crear', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, precio, tipo_temp })
        });
        const data = await res.json();
        if (data.ok && data.id_producto) nuevoId = data.id_producto;
    } catch(e) { /* Si no hay BD, usar ID temporal */ }

    const nuevoItem = { id: nuevoId, nombre, precio, tipo: categoria === 'bebidasTemp' ? 'bebida' : 'comida' };
    menuSecciones[categoria][0].items.push(nuevoItem);
    menuDatos[categoria] = menuSecciones[categoria][0].items;
    try { localStorage.setItem(storageKey, JSON.stringify(menuSecciones[categoria][0].items)); } catch(e) {}
    filtrarCat(categoria, document.querySelector('.cat-btn.activa'));
}

// --- ELIMINAR ITEM DE TEMPORADA ---
async function eliminarItemTemporada(index, categoria, storageKey) {
    const item = menuSecciones[categoria][0].items[index];
    // Desactivar en BD si tiene ID real
    if (item && item.id && item.id > 900) {
        try {
            await fetch('menu_api.php?accion=eliminar', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_producto: item.id })
            });
        } catch(e) {}
    }
    menuSecciones[categoria][0].items.splice(index, 1);
    menuDatos[categoria] = menuSecciones[categoria][0].items;
    try { localStorage.setItem(storageKey, JSON.stringify(menuSecciones[categoria][0].items)); } catch(e) {}
    filtrarCat(categoria, document.querySelector('.cat-btn.activa'));
}

// --- AGREGAR AL PEDIDO ---
function agregarAlPedido(id, nombre, precio, tipo) {
    if (tipo === 'armable' || tipo === 'bebida' || tipo === 'coctel' || tipo === 'comida-extra') {
        productoArmando = { id, nombre, precio, tipo };
        abrirModalOpciones(nombre, tipo);
        return;
    }
    let item = mesaActiva.pedido.find(i => i.id === id && i.nota === "");
    if (item) item.cantidad++;
    else mesaActiva.pedido.push({ id: id, uniqueId: Date.now(), nombre: nombre, precio: precio, cantidad: 1, nota: "", extraPrecio: 0 });
    actualizarComandaUI();
}

// --- MODAL DE OPCIONES (extras como botones chip) ---
function abrirModalOpciones(nombre, tipo) {
    document.getElementById("titulo-opciones").innerText = "OPCIONES: " + nombre.toUpperCase();
    // Restaurar botones del modal por si venían del admin
    const btnConfirmar = document.querySelector("#modal-opciones .btn-principal[onclick='confirmarOpciones()']");
    const btnCancelar = document.querySelector("#modal-opciones .btn-secundario[onclick='cerrarModalOpciones()']");
    if (btnConfirmar) btnConfirmar.style.display = '';
    if (btnCancelar) btnCancelar.innerText = 'CANCELAR';

    let contenedor = document.getElementById("contenedor-opciones");

    if (tipo === 'armable') {
        const basicos = ["Nutella","Queso Crema","Lechera","Merm. Zarzamora","Merm. Fresa","Cajeta"];
        const frutales = ["Durazno","Fresa","Plátano","Kiwi","Manzana Verde"];
        const extras = [
            { label:"Nuez +$15",    val:"Nuez",    precio:15 },
            { label:"Helado +$25",  val:"Helado",  precio:25 },
            { label:"Almendra +$15",val:"Almendra",precio:15 },
            { label:"Fresas/Duraznos con crema +$40", val:"Fresas/Duraznos con crema", precio:40 }
        ];
        contenedor.innerHTML = `
            <p style="font-size:12px; color:var(--text-muted); margin-bottom:10px;">Elige hasta 2 ingredientes incluidos</p>
            <p style="font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:6px;">Básicos</p>
            <div class="chips-group" id="chips-base">
                ${basicos.map(b=>`<button class="chip" data-group="base" data-val="${b}" onclick="toggleChip(this,'base',2)">${b}</button>`).join('')}
            </div>
            <p style="font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.8px; margin:12px 0 6px;">Frutales</p>
            <div class="chips-group" id="chips-frutal">
                ${frutales.map(f=>`<button class="chip" data-group="frutal" data-val="${f}" onclick="toggleChip(this,'frutal',2)">${f}</button>`).join('')}
            </div>
            <p style="font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.8px; margin:12px 0 6px;">Complementos extra</p>
            <div class="chips-group" id="chips-extra">
                ${extras.map(e=>`<button class="chip chip-extra" data-val="${e.val}" data-precio="${e.precio}" onclick="toggleChip(this,'extra',99)">${e.label}</button>`).join('')}
            </div>`;

    } else if (tipo === 'bebida') {
        const mods = [
            { label:"Almendra +$12",       val:"Leche Almendra",    precio:12 },
            { label:"Coco +$12",            val:"Leche Coco",        precio:12 },
            { label:"Carga Extra +$15",     val:"Carga Extra",       precio:15 },
            { label:"Jarabe Extra +$15",    val:"Jarabe Extra",      precio:15 },
            { label:"Perlas Explosivas +$12",val:"Perlas Explosivas",precio:12 },
            { label:"Jelly's +$12",         val:"Jellys",            precio:12 }
        ];
        contenedor.innerHTML = `
            <p style="font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:8px;">Modificadores de bebida</p>
            <div class="chips-group" id="chips-extra">
                ${mods.map(m=>`<button class="chip chip-extra" data-val="${m.val}" data-precio="${m.precio}" onclick="toggleChip(this,'extra',99)">${m.label}</button>`).join('')}
            </div>`;

    } else if (tipo === 'comida-extra') {
        const extras = [
            { label:"Guacamole +$30", val:"Extra Guacamole", precio:30 },
            { label:"Tocino +$15",    val:"Extra Tocino",    precio:15 },
            { label:"Carne +$25",     val:"Extra Carne",     precio:25 }
        ];
        contenedor.innerHTML = `
            <p style="font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:8px;">Extras opcionales</p>
            <div class="chips-group" id="chips-extra">
                ${extras.map(e=>`<button class="chip chip-extra" data-val="${e.val}" data-precio="${e.precio}" onclick="toggleChip(this,'extra',99)">${e.label}</button>`).join('')}
            </div>`;

    } else if (tipo === 'coctel') {
        const alcs = [
            { label:"Ron Blanco Extra +$20", val:"Extra Ron Blanco", precio:20 },
            { label:"Ginebra Extra +$30",    val:"Extra Ginebra",    precio:30 }
        ];
        contenedor.innerHTML = `
            <p style="font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:8px;">Extras de alcohol</p>
            <div class="chips-group" id="chips-extra">
                ${alcs.map(a=>`<button class="chip chip-extra" data-val="${a.val}" data-precio="${a.precio}" onclick="toggleChip(this,'extra',99)">${a.label}</button>`).join('')}
            </div>`;
    }

    document.getElementById("modal-opciones").classList.add("activo");
}

// Activa/desactiva chips respetando el máximo por grupo
function toggleChip(btn, group, maxSelec) {
    const activos = document.querySelectorAll(`.chip[data-group="${group}"].chip-on`);
    if (!btn.classList.contains('chip-on') && activos.length >= maxSelec) {
        // Quitar el más antiguo si llegó al límite
        activos[0].classList.remove('chip-on');
    }
    btn.classList.toggle('chip-on');
}

function cerrarModalOpciones() { document.getElementById("modal-opciones").classList.remove("activo"); }

function confirmarOpciones() {
    let notaFinal = ""; let costoAdicional = 0;

    if (productoArmando.tipo === 'armable') {
        let bases = [];
        document.querySelectorAll('.chip[data-group="base"].chip-on, .chip[data-group="frutal"].chip-on')
            .forEach(c => bases.push(c.getAttribute('data-val')));
        if (bases.length > 2) return alert("Solo puedes elegir hasta 2 ingredientes incluidos.");
        notaFinal = bases.join(", ");
    }
    let extras = [];
    document.querySelectorAll('.chip-extra.chip-on').forEach(c => {
        extras.push(c.getAttribute('data-val'));
        costoAdicional += parseFloat(c.getAttribute('data-precio'));
    });
    if (extras.length > 0) notaFinal += (notaFinal !== "" ? " | Extra: " : "Extra: ") + extras.join(", ");
    if (notaFinal === "") notaFinal = "Preparación Regular";
    mesaActiva.pedido.push({
        id: productoArmando.id,
        uniqueId: Date.now(),
        nombre: productoArmando.nombre,
        precio: productoArmando.precio,
        cantidad: 1,
        nota: notaFinal,
        extraPrecio: costoAdicional
    });
    cerrarModalOpciones();
    actualizarComandaUI();
}

// --- GESTIÓN DE CANTIDADES ---
function cambiarCantidad(index, delta) {
    mesaActiva.pedido[index].cantidad += delta;
    if (mesaActiva.pedido[index].cantidad <= 0) mesaActiva.pedido.splice(index, 1);
    actualizarComandaUI();
}

// --- MODAL DE NOTAS ---
function abrirModalNota(index) {
    let item = mesaActiva.pedido[index];
    document.getElementById("item-index-editar").value = index;
    document.getElementById("input-nota").value = item.nota === "Preparación Regular" ? "" : item.nota;
    document.getElementById("input-extra").value = item.extraPrecio;
    document.getElementById("modal-nota").classList.add("activo");
}

function cerrarModalNota() { document.getElementById("modal-nota").classList.remove("activo"); }

function guardarNota() {
    let index = document.getElementById("item-index-editar").value;
    let n = document.getElementById("input-nota").value;
    mesaActiva.pedido[index].nota = n === "" ? "Preparación Regular" : n;
    mesaActiva.pedido[index].extraPrecio = parseFloat(document.getElementById("input-extra").value) || 0;
    cerrarModalNota(); actualizarComandaUI();
}

// --- ACTUALIZAR UI DE LA COMANDA ---
// Busca tu función actualizarComanda() y modifícala para que incluya esto:

// --- FUNCIÓN PARA DIBUJAR LA COMANDA  ---
function actualizarComandaUI() {
    try {
        let contenedor = document.getElementById("lista-comanda");
        if (!contenedor) return; // Seguro por si no encuentra el panel
        
        contenedor.innerHTML = ""; // Limpiamos la lista
        let total = 0;

        // Si no hay mesa o el pedido está vacío, el total es 0
        if (!mesaActiva || !mesaActiva.pedido || mesaActiva.pedido.length === 0) {
            let totalVisual = document.getElementById("total-comanda");
            if (totalVisual) totalVisual.innerText = "0.00";
            return;
        }
        // Recorremos todos los productos
        mesaActiva.pedido.forEach((item, index) => {
            // ESCUDOS DE SEGURIDAD: Convertimos todo a números reales para evitar que se congele
            let precio = parseFloat(item.precio) || 0;
            let extra = parseFloat(item.extraPrecio) || 0;
            let cant = parseInt(item.cantidad) || 1;
            
            let subtotal = (precio + extra) * cant;
            total += subtotal;

            // 1. Relojito de estado pendiente
            let etiquetaEstado = "";
            if (item.estado === 'pendiente') {
                etiquetaEstado = `<span style="font-size: 10px; background: #f39c12; color: white; padding: 2px 6px; border-radius: 4px; margin-left: 8px;"><i class="fas fa-clock"></i> Pendiente</span>`;
            }

            // 2. Notas y cobros extra
            let textoNota = item.nota ? `<div style="font-size: 12px; color: #7f8c8d; margin-top: 4px;"><i class="fas fa-comment-dots"></i> ${item.nota}</div>` : "";
            let textoExtra = extra > 0 ? `<div style="font-size: 12px; color: #27ae60; margin-top: 2px;"><i class="fas fa-plus"></i> Extra: $${extra.toFixed(2)}</div>` : "";

            // 3. Dibujamos el producto con sus botones
            contenedor.innerHTML += `
                <div class="comanda-item" style="border-bottom: 1px solid var(--border-color); padding: 15px 0;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <div>
                            <strong style="color: var(--text-main); font-size: 14px;">
                                ${cant}x ${item.nombre} ${etiquetaEstado}
                            </strong>
                            ${textoNota}
                            ${textoExtra}
                        </div>
                        <span style="color: var(--accent); font-weight: bold; font-size: 16px;">$${subtotal.toFixed(2)}</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; gap: 8px;">
                            <button onclick="cambiarCantidad(${index}, -1)" style="width: 28px; height: 28px; border-radius: 50%; border: 1px solid var(--border-color); background: var(--bg-app); color: var(--text-main); cursor: pointer; font-weight: bold;">-</button>
                            <button onclick="cambiarCantidad(${index}, 1)" style="width: 28px; height: 28px; border-radius: 50%; border: 1px solid var(--border-color); background: var(--bg-app); color: var(--text-main); cursor: pointer; font-weight: bold;">+</button>
                        </div>
                        <button onclick="abrirModalNota(${index})" style="background: transparent; border: 1px solid #3498db; color: #3498db; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 11px;">
                            <i class="fas fa-pencil-alt"></i> EDITAR
                        </button>
                    </div>
                </div>
            `;
        });

        // Actualizamos el total general
        let totalVisual = document.getElementById("total-comanda");
        if (totalVisual) totalVisual.innerText = total.toFixed(2);
        
    } catch (error) {
        // Si algo más falla, lo atrapamos aquí para que no bloquee tu pantalla
        console.error("Hubo un error al dibujar la comanda: ", error);
    }
}

// =============================================================
// SISTEMA DE ASIGNACIÓN MANUAL BARRA / COCINA
// =============================================================

// destinoRuta[uniqueId] = 'barra' | 'cocina'
let destinoRuta = {};
// destinosItems[uniqueId] = 'aqui' | 'llevar'  (para el cliente)
let destinosItems = {};

// Categorías que van por defecto a barra
const CAT_BARRA = ['calientes','frias','cocteles','bebidasTemp'];

function _esBebidaPorDefecto(item) {
    for (let cat in menuSecciones) {
        const secciones = menuSecciones[cat];
        for (let sec of secciones) {
            const items = sec.items || [];
            if (items.find(p => p.id === item.id || p.nombre === item.nombre)) {
                return CAT_BARRA.includes(cat);
            }
        }
    }
    return false;
}

// ABRIR MODAL DE CONFIRMACIÓN (nuevo diseño barra/cocina)
function abrirModalConfirmacion() {
    if (!mesaActiva || !mesaActiva.pedido || mesaActiva.pedido.length === 0) {
        alert("⚠️ La orden está vacía. Agrega productos primero.");
        return;
    }
    const productosNuevos = mesaActiva.pedido.filter(item => item.estado !== 'pendiente');
    if (productosNuevos.length === 0) {
        alert("⏳ Todos los productos ya fueron enviados. Agrega algo nuevo.");
        return;
    }

    // Resetear
    destinoRuta    = {};
    destinosItems  = {};

    // Asignar destino de ruta automático según categoría
    productosNuevos.forEach(item => {
        destinoRuta[item.uniqueId]   = _esBebidaPorDefecto(item) ? 'barra' : 'cocina';
        destinosItems[item.uniqueId] = 'aqui';
    });

    // Botones globales aquí/llevar
    document.getElementById('btn-aqui').classList.add('activo');
    document.getElementById('btn-llevar').classList.remove('activo');

    // Renderizar columnas
    _renderColumnas(productosNuevos);

    document.getElementById("modal-confirmacion").style.display = "flex";
}

function _renderColumnas(productos) {
    const listaBarra  = document.getElementById('lista-barra');
    const listaCocina = document.getElementById('lista-cocina');
    listaBarra.innerHTML  = '';
    listaCocina.innerHTML = '';

    let countBarra = 0, countCocina = 0;

    productos.forEach(item => {
        const uid   = item.uniqueId;
        const ruta  = destinoRuta[uid] || 'cocina';
        const dest  = destinosItems[uid] || 'aqui';
        const nota  = (item.nota && item.nota !== 'Preparación Regular') ? item.nota : '';
        const extra = item.extraPrecio > 0 ? ` +$${item.extraPrecio.toFixed(2)}` : '';
        const esAqui = dest === 'aqui';

        const card = `
        <div class="orden-prod-card en-${ruta}" 
             id="opcard-${uid}"
             draggable="true"
             ondragstart="dragStart(event,'${uid}')"
             ondragend="dragEnd(event)">
            <div class="orden-prod-top">
                <span class="orden-prod-cant">${item.cantidad}×</span>
                <div class="orden-prod-info">
                    <div class="orden-prod-nombre">${item.nombre}${extra}</div>
                    ${nota ? `<div class="orden-prod-nota"><i class="fas fa-comment-dots"></i> ${nota}</div>` : ''}
                </div>
                <div class="orden-prod-toggle">
                    <button class="btn-mover btn-mover-barra" title="Mover a Barra"
                        onclick="moverProducto('${uid}','barra')">
                        <i class="fas fa-glass-water-droplet"></i>
                    </button>
                    <button class="btn-mover btn-mover-cocina" title="Mover a Cocina"
                        onclick="moverProducto('${uid}','cocina')">
                        <i class="fas fa-fire-burner"></i>
                    </button>
                </div>
            </div>
            <div class="orden-dest-row">
                <button class="btn-dest-item ${esAqui ? 'activo-aqui' : ''}" 
                        onclick="toggleDestinoProducto('${uid}','aqui')">
                    <i class="fas fa-utensils"></i> Aquí
                </button>
                <button class="btn-dest-item ${!esAqui ? 'activo-llevar' : ''}" 
                        onclick="toggleDestinoProducto('${uid}','llevar')">
                    <i class="fas fa-bag-shopping"></i> Llevar
                </button>
            </div>
        </div>`;

        if (ruta === 'barra') { listaBarra.innerHTML  += card; countBarra++;  }
        else                  { listaCocina.innerHTML += card; countCocina++; }
    });

    // Hints de drop
    if (!countBarra)  listaBarra.innerHTML  = '<div class="orden-drop-hint"><i class="fas fa-arrow-down"></i> Arrastra aquí bebidas</div>';
    if (!countCocina) listaCocina.innerHTML = '<div class="orden-drop-hint"><i class="fas fa-arrow-down"></i> Arrastra aquí alimentos</div>';

    document.getElementById('badge-barra').textContent  = countBarra;
    document.getElementById('badge-cocina').textContent = countCocina;

    const aviso = document.getElementById('aviso-coordinacion');
    aviso.style.display = (countBarra > 0 && countCocina > 0) ? 'flex' : 'none';
}

// Toggle destino individual (Aquí / Llevar) por producto
function toggleDestinoProducto(uid, dest) {
    destinosItems[uid] = dest;
    // Re-renderizar solo las tarjetas
    const productosNuevos = mesaActiva.pedido.filter(i => i.estado !== 'pendiente');
    _renderColumnas(productosNuevos);
}

// Mover producto a otro destino
function moverProducto(uid, ruta) {
    destinoRuta[uid] = ruta;
    const productosNuevos = mesaActiva.pedido.filter(i => i.estado !== 'pendiente');
    _renderColumnas(productosNuevos);
}

// Drag & Drop
let _draggingUid = null;

function dragStart(e, uid) {
    _draggingUid = uid;
    e.currentTarget.style.opacity = '0.45';
    e.dataTransfer.effectAllowed = 'move';
}

function dragEnd(e) {
    e.currentTarget.style.opacity = '1';
    document.querySelectorAll('.orden-columna').forEach(c => c.classList.remove('drag-over'));
    _draggingUid = null;
}

function dropEnColumna(e, ruta) {
    e.preventDefault();
    if (_draggingUid) moverProducto(_draggingUid, ruta);
    document.querySelectorAll('.orden-columna').forEach(c => c.classList.remove('drag-over'));
}

// Resaltar columna al arrastrar encima
document.addEventListener('dragover', e => {
    const col = e.target.closest('.orden-columna');
    document.querySelectorAll('.orden-columna').forEach(c => c.classList.remove('drag-over'));
    if (col) col.classList.add('drag-over');
});

// Selector aquí/llevar global
function seleccionarDestino(dest) {
    Object.keys(destinosItems).forEach(uid => { destinosItems[uid] = dest; });
    document.getElementById('btn-aqui').classList.toggle('activo', dest === 'aqui');
    document.getElementById('btn-llevar').classList.toggle('activo', dest === 'llevar');
}

function cerrarModal() {
    document.getElementById("modal-confirmacion").style.display = "none";
}

// ── NOTIFICACIONES EN POS (polling localStorage) ──────────
let _notifPosTimer = null;
let _ultimaNotifVista = 0;

function _iniciarPollingNotifPos() {
    setInterval(() => {
        try {
            // Barra lista → avisa al mesero
            const nb = JSON.parse(localStorage.getItem('notif_bebidas') || 'null');
            if (nb && nb.ts > _ultimaNotifVista && nb.ts > Date.now() - 30000) {
                _ultimaNotifVista = nb.ts;
                mostrarNotifPos('barra', `Mesa ${nb.mesa} — Bebidas listas`, 'La barra terminó las bebidas 🥤');
            }
            // Cocina lista → avisa al mesero
            const nc = JSON.parse(localStorage.getItem('notif_cocina_lista') || 'null');
            if (nc && nc.ts > _ultimaNotifVista && nc.ts > Date.now() - 30000) {
                _ultimaNotifVista = nc.ts;
                mostrarNotifPos('cocina', `Mesa ${nc.mesa} — Comida lista`, 'La cocina terminó los platillos 🍽');
            }
        } catch(e) {}
    }, 1000); // FIX: bajar de 2s a 1s — respuesta más inmediata en mismo dispositivo
}

function mostrarNotifPos(tipo, titulo, desc) {
    const el    = document.getElementById('notif-pos');
    const icon  = document.getElementById('notif-pos-icon');
    const tit   = document.getElementById('notif-pos-titulo');
    const des   = document.getElementById('notif-pos-desc');

    icon.className = `notif-pos-icon notif-${tipo}`;
    icon.innerHTML = tipo === 'barra'
        ? '<i class="fas fa-glass-water-droplet"></i>'
        : '<i class="fas fa-fire-burner"></i>';
    tit.textContent = titulo;
    des.textContent = desc;

    el.style.display = 'block';
    // Auto-cerrar en 12 s
    clearTimeout(_notifPosTimer);
    _notifPosTimer = setTimeout(cerrarNotifPos, 12000);
}

function cerrarNotifPos() {
    document.getElementById('notif-pos').style.display = 'none';
    clearTimeout(_notifPosTimer);
}

// Arrancar polling cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', _iniciarPollingNotifPos);

// ENVIAR PEDIDO → usa destinoRuta para separar barra/cocina
function enviarPedido() {
    let itemsCocina = [];
    let itemsBarra  = [];
    const productosNuevos = mesaActiva.pedido.filter(item => item.estado !== 'pendiente');
    if (productosNuevos.length === 0) { cerrarModal(); return; }

    productosNuevos.forEach(itemPedido => {
        const ruta  = destinoRuta[itemPedido.uniqueId] || 'cocina';
        const dest  = destinosItems[itemPedido.uniqueId] || 'aqui';
        const itemConDatos = {
            ...itemPedido,
            destinoItem: dest,       // 'aqui' | 'llevar'
            estadoItem: 'pendiente',
            tsEnvio: Date.now()      // timestamp de cuando se envió este producto
        };
        if (ruta === 'barra') itemsBarra.push(itemConDatos);
        else                  itemsCocina.push(itemConDatos);
    });

    // Marcar todos como pendientes Y la mesa como ocupada
    mesaActiva.pedido = mesaActiva.pedido.map(item =>
        item.estado !== 'pendiente' ? { ...item, estado: 'pendiente' } : item
    );
    mesaActiva.estado = 'ocupada'; // ← marcar mesa como ocupada inmediatamente

    const timestamp = Date.now();
    const horaStr   = new Date(timestamp).toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });
    const idComanda = `CMD-${mesaActiva.id}-${timestamp}`;

    const todosAqui   = Object.values(destinosItems).every(v => v === 'aqui');
    const todosLlevar = Object.values(destinosItems).every(v => v === 'llevar');
    const etiqueta    = todosLlevar ? '🛍 PARA LLEVAR' : todosAqui ? '🍽 AQUÍ' : '🍽🛍 MIXTO';
    const destino     = todosLlevar ? 'llevar' : todosAqui ? 'aqui' : 'mixto';

    const comandaBase = {
        id: idComanda,
        mesa: mesaActiva.id,
        destino,
        etiqueta,
        hora: horaStr,
        timestamp,
        _ts: timestamp,
        estadoGeneral: 'pendiente'
    };

    // Guardar en localStorage para cocina y barra
    if (itemsCocina.length > 0) _pushComanda('cocina_comandas', { ...comandaBase, items: itemsCocina });
    if (itemsBarra.length  > 0) _pushComanda('barra_comandas',  { ...comandaBase, items: itemsBarra });

    // Señal de actualización
    try { localStorage.setItem('ultima_actualizacion', String(timestamp)); } catch(e) {}

    // ── GUARDAR EN BD: tablas pedido + detalle_pedido ──────────
    const sesion = _getSesion();
    const todosItems = [...itemsCocina, ...itemsBarra];
    fetch('pedidos.php?accion=guardar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            mesa_id:    mesaActiva.id,
            usuario_id: sesion.id || 1,
            destino:    etiqueta,
            items:      todosItems.map(it => ({
                id:          it.id       || 0,
                nombre:      it.nombre,
                cantidad:    it.cantidad,
                precio:      it.precio,
                extraPrecio: it.extraPrecio || 0,
                nota:        it.nota        || '',
                destinoItem: it.destinoItem || 'aqui',
                ruta:        destinoRuta[it.uniqueId] || it.ruta || 'cocina'
            }))
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            console.log('✅ Pedido guardado en BD, id:', data.id_pedido);
            // FIX: actualizar id_pedido en las comandas del localStorage
            // para que el merge de barra/cocina haga match con lo que llega de BD
            const idBD = parseInt(data.id_pedido);
            if (idBD) {
                ['cocina_comandas','barra_comandas'].forEach(key => {
                    try {
                        const raw = JSON.parse(localStorage.getItem(key)||'[]');
                        let changed = false;
                        raw.forEach(c => {
                            if (c.id === idComanda && !c.id_pedido) {
                                c.id_pedido = idBD;
                                changed = true;
                            }
                        });
                        if (changed) localStorage.setItem(key, JSON.stringify(raw));
                    } catch(e) {}
                });
            }
            // Marcar mesa como ocupada en BD para que otros dispositivos la vean
            fetch('mesas.php?accion=estado', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_mesa: mesaActiva.id, estado: 'ocupada' })
            }).catch(() => {});
        }
        else console.warn('⚠ No se guardó pedido en BD:', data.error);
    })
    .catch(() => console.warn('PHP no disponible — pedido solo en localStorage'));

    // ── 1. Cerrar modal PRIMERO ────────────────────────────────
    cerrarModal();

    // ── 2. Actualizar la comanda en pantalla ───────────────────
    actualizarComandaUI();

    // ── 3. NAVEGAR al KDS (o volver a mesas si es mesero) ──────
    // Se hace AL FINAL para que cerrarModal y actualizarComandaUI
    // ya hayan corrido antes de cualquier cambio de pantalla
    _abrirVentanaKDS(itemsCocina.length > 0, itemsBarra.length > 0);
}

function _abrirVentanaKDS(tieneCocina, tieneBarra) {
    _guardarEstadoMesas();

    const sesion = _getSesion();
    const rol    = sesion.rol || '';

    if (rol === 'mesero') {
        // Mesero: comanda guardada en localStorage, cocina/barra la ven por polling
        mostrarPantalla('mesas');
        renderizarMesas();
        _iniciarAutoRefresh();
        return;
    }

    // Admin → navegar al KDS correspondiente
    if (tieneCocina) {
        window.location.href = 'cocina.php';
    } else if (tieneBarra) {
        window.location.href = 'barra.php';
    }
}

function _mostrarAvisoPopupBloqueado(cocina, barra) {
    // Crear modal de aviso si no existe
    let modal = document.getElementById('modal-kds-aviso');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modal-kds-aviso';
        modal.style.cssText = `
            position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);
            display:flex;align-items:center;justify-content:center;z-index:2000;
            animation:aparecer .2s ease;
        `;
        modal.innerHTML = `
            <div style="background:var(--bg-panel);border:1px solid var(--border-color);border-radius:8px;padding:28px;width:420px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.2);">
                <p style="font-size:11px;font-weight:600;letter-spacing:1.4px;text-transform:uppercase;color:var(--text-muted);margin-bottom:14px;border-bottom:1px solid var(--border-color);padding-bottom:12px;">
                    <i class="fas fa-triangle-exclamation" style="color:var(--accent-2);"></i> &nbsp;Orden enviada — Abre las pantallas
                </p>
                <p style="font-size:13px;color:var(--text-muted);line-height:1.6;margin-bottom:20px;">
                    Tu navegador bloqueó las ventanas automáticas.<br>
                    Haz clic en los botones para abrir cada pantalla:
                </p>
                <div id="kds-aviso-btns" style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;"></div>
                <p style="font-size:10px;color:var(--text-muted);margin-bottom:16px;line-height:1.5;">
                    <i class="fas fa-info-circle"></i> Consejo: Permite las ventanas emergentes en tu navegador para que se abran automáticamente la próxima vez. En Chrome: haz clic en el ícono de bloqueo en la barra de direcciones.
                </p>
                <button onclick="document.getElementById('modal-kds-aviso').remove()"
                    style="width:100%;padding:10px;border:1px solid var(--border-color);background:var(--bg-app);color:var(--text-main);border-radius:5px;cursor:pointer;font-size:12px;font-weight:600;letter-spacing:.5px;font-family:'DM Sans',sans-serif;">
                    CERRAR
                </button>
            </div>`;
        document.body.appendChild(modal);
    }

    const btnsContainer = modal.querySelector('#kds-aviso-btns');
    btnsContainer.innerHTML = '';

    if (cocina) {
        btnsContainer.innerHTML += `
            <a href="cocina.php" target="kds_cocina"
                style="display:flex;align-items:center;gap:10px;padding:13px 16px;background:var(--accent-light);border:1px solid var(--accent);color:var(--accent);border-radius:6px;text-decoration:none;font-size:13px;font-weight:600;letter-spacing:.5px;transition:all .15s;"
                onclick="document.getElementById('modal-kds-aviso').remove()">
                <i class="fas fa-fire-burner" style="font-size:18px;"></i>
                <span><span style="font-size:11px;display:block;opacity:.7;letter-spacing:.8px;text-transform:uppercase;">Pantalla</span>Cocina</span>
                <i class="fas fa-arrow-up-right-from-square" style="margin-left:auto;opacity:.6;"></i>
            </a>`;
    }
    if (barra) {
        btnsContainer.innerHTML += `
            <a href="barra.php" target="kds_barra"
                style="display:flex;align-items:center;gap:10px;padding:13px 16px;background:var(--prep-bg,rgba(26,90,138,.08));border:1px solid var(--prep,#1A5A8A);color:var(--prep,#1A5A8A);border-radius:6px;text-decoration:none;font-size:13px;font-weight:600;letter-spacing:.5px;transition:all .15s;"
                onclick="document.getElementById('modal-kds-aviso').remove()">
                <i class="fas fa-blender" style="font-size:18px;"></i>
                <span><span style="font-size:11px;display:block;opacity:.7;letter-spacing:.8px;text-transform:uppercase;">Pantalla</span>Barra</span>
                <i class="fas fa-arrow-up-right-from-square" style="margin-left:auto;opacity:.6;"></i>
            </a>`;
    }

    modal.style.display = 'flex';
}

function _pushComanda(key, comanda) {
    try {
        const existentes = JSON.parse(localStorage.getItem(key) || '[]');
        existentes.push(comanda);
        // Guardar máximo 50 comandas activas
        const activas = existentes.filter(c => c.estadoGeneral !== 'entregado').slice(-50);
        localStorage.setItem(key, JSON.stringify(activas));
    } catch(e) { console.error('Error guardando comanda:', e); }
}

// --- FUNCIONES PARA DIVIDIR CUENTA --- 

function abrirModalDivision() {
    // Reiniciar variables de división
    tipoDivision = null;
    totalPersonas = 0;
    personas = [];
    productosAsignados = {};
    personaActual = 1;
    personasCompletadas = [];

    document.getElementById("opcion-iguales").classList.remove("seleccionada");
    document.getElementById("opcion-por-productos").classList.remove("seleccionada");
    document.getElementById("seccion-num-personas-global").style.display = "none";
    document.getElementById("seccion-iguales-detalle").style.display = "none";
    document.getElementById("seccion-productos-detalle").style.display = "none";
    document.getElementById("resumen-division-modal").innerHTML = "";
    document.getElementById("btn-aplicar-division").disabled = true;
    document.getElementById("mensaje-confirmacion").style.display = "none";

    document.getElementById("modal-division").classList.add("activo");
}

function cerrarModalDivision() {
    document.getElementById("modal-division").classList.remove("activo");
}

function seleccionarTipoDivision(tipo) {
    tipoDivision = tipo;

    // Resetear estilos
    document.getElementById("opcion-iguales").classList.remove("seleccionada");
    document.getElementById("opcion-por-productos").classList.remove("seleccionada");

    if (tipo === 'iguales') {
        document.getElementById("opcion-iguales").classList.add("seleccionada");
    } else {
        document.getElementById("opcion-por-productos").classList.add("seleccionada");
    }

    // Mostrar sección para ingresar número de personas
    document.getElementById("seccion-num-personas-global").style.display = "block";
    document.getElementById("seccion-iguales-detalle").style.display = "none";
    document.getElementById("seccion-productos-detalle").style.display = "none";
}

function continuarConDivision() {
    totalPersonas = parseInt(document.getElementById("num-personas-global").value);

    if (totalPersonas < 2 || totalPersonas > 10) {
        alert("Número de personas inválido. Debe ser entre 2 y 10.");
        return;
    }

    // Inicializar array de personas
    personas = [];
    for (let i = 1; i <= totalPersonas; i++) {
        personas.push({
            numero: i,
            monto: 0,
            productos: []
        });
    }

    if (tipoDivision === 'iguales') {
        document.getElementById("seccion-iguales-detalle").style.display = "block";
        let total = parseFloat(document.getElementById("cobro-total").innerText);
        let montoPorPersona = total / totalPersonas;

        let resumenHtml = "";
        for (let i = 1; i <= totalPersonas; i++) {
            resumenHtml += `
                <div style="padding: 8px; border-bottom: 1px solid var(--border-color);">
                    <strong>Persona ${i}</strong>: $${montoPorPersona.toFixed(2)}
                </div>
            `;
        }
        document.getElementById("resumen-iguales").innerHTML = resumenHtml;
    } else {
        // Inicializar productosAsignados para cada persona
        productosAsignados = {};
        personasCompletadas = [];
        for (let i = 1; i <= totalPersonas; i++) {
            productosAsignados[i] = [];
        }

        document.getElementById("seccion-productos-detalle").style.display = "block";
        personaActual = 1;
        generarBotonesPersonas();
        cargarProductosParaPersona(1);
        actualizarProgreso();
    }
}

function generarBotonesPersonas() {
    let contenedor = document.getElementById("botones-personas");
    contenedor.innerHTML = "";

    for (let i = 1; i <= totalPersonas; i++) {
        let estaCompletada = personasCompletadas.includes(i);
        contenedor.innerHTML += `
            <button class="btn ${personaActual === i ? 'btn-principal' : (estaCompletada ? 'btn-success' : 'btn-secundario')}" 
                    onclick="seleccionarPersona(${i})" 
                    id="btn-persona-${i}">
                Persona ${i} ${estaCompletada ? '✓' : ''}
            </button>
        `;
    }
}

function seleccionarPersona(num) {
    personaActual = num;
    generarBotonesPersonas();
    cargarProductosParaPersona(num);
}

function actualizarProgreso() {
    let progreso = (personasCompletadas.length / totalPersonas) * 100;
    document.getElementById("progreso-productos").style.width = progreso + "%";

    if (personasCompletadas.length === totalPersonas) {
        document.getElementById("btn-aplicar-division").disabled = false;
    }
}

function cargarProductosParaPersona(persona) {
    let contenedor = document.getElementById("lista-productos-persona");
    let titulo = document.getElementById("titulo-persona-actual");
    titulo.innerHTML = `Persona ${persona} ${personasCompletadas.includes(persona) ? '✓' : ''}`;

    contenedor.innerHTML = "<h4 style='margin-bottom:10px;'>Selecciona los productos para esta persona (puedes elegir varios):</h4>";

    mesaActiva.pedido.forEach((item, index) => {
        let subtotal = (item.precio + item.extraPrecio) * item.cantidad;
        let checkboxId = "prod-" + persona + "-" + index;

        // Verificar si este producto ya está asignado a esta persona
        let isChecked = productosAsignados[persona] &&
            productosAsignados[persona].some(p => p.index === index);

        // Verificar si el producto ya está asignado a otra persona
        let asignadoAOtra = false;
        let otraPersonaNum = null;
        for (let i = 1; i <= totalPersonas; i++) {
            if (i !== persona && productosAsignados[i] && productosAsignados[i].some(p => p.index === index)) {
                asignadoAOtra = true;
                otraPersonaNum = i;
                break;
            }
        }

        let claseAdicional = asignadoAOtra ? 'asignado-otra' : '';
        let textoAdicional = asignadoAOtra ? `<br><small style="color:var(--danger);">(Ya asignado a Persona ${otraPersonaNum})</small>` : '';

        contenedor.innerHTML += `
            <div class="producto-seleccionable ${claseAdicional}">
                <input type="checkbox" id="${checkboxId}" 
                       ${isChecked ? 'checked' : ''} 
                       ${asignadoAOtra ? 'disabled' : ''}
                       onchange="seleccionarProducto(${persona}, ${index}, ${subtotal}, this.checked)">
                <label for="${checkboxId}" style="flex:1; margin-left:10px;">
                    <strong>${item.cantidad}x ${item.nombre}</strong><br>
                    <small style="color:var(--text-muted);">$${subtotal.toFixed(2)}</small>
                    ${textoAdicional}
                </label>
            </div>
        `;
    });

    // Mostrar subtotal actual de esta persona
    let totalPersona = productosAsignados[persona].reduce((sum, p) => sum + p.precio, 0);
    contenedor.innerHTML += `
        <div style="margin-top: 15px; padding: 10px; background: var(--bg-app); border-radius: 6px; text-align: right;">
            <strong>Subtotal Persona ${persona}: $${totalPersona.toFixed(2)}</strong>
        </div>
    `;

    // Mostrar mensaje si ya está completada
    if (personasCompletadas.includes(persona)) {
        contenedor.innerHTML += `
            <div style="margin-top: 10px; padding: 10px; background: var(--success); color: white; border-radius: 6px; text-align: center;">
                <i class="fas fa-check-circle"></i> Persona ${persona} ya ha sido configurada
            </div>
        `;
    }
}

function seleccionarProducto(persona, index, precio, seleccionado) {
    if (personasCompletadas.includes(persona)) {
        alert(`La Persona ${persona} ya ha sido guardada. No puedes modificar sus productos.`);
        cargarProductosParaPersona(persona);
        return;
    }

    if (seleccionado) {
        // Verificar que el producto no esté ya asignado a otra persona
        for (let i = 1; i <= totalPersonas; i++) {
            if (i !== persona && productosAsignados[i] && productosAsignados[i].some(p => p.index === index)) {
                alert("Este producto ya está asignado a otra persona");
                cargarProductosParaPersona(persona);
                return;
            }
        }

        productosAsignados[persona].push({
            index: index,
            precio: precio,
            producto: mesaActiva.pedido[index]
        });
    } else {
        productosAsignados[persona] = productosAsignados[persona].filter(p => p.index !== index);
    }

    // Recargar productos para actualizar checkboxes y subtotales
    cargarProductosParaPersona(persona);
}

function guardarSeleccionPersona() {
    if (personasCompletadas.includes(personaActual)) {
        alert(`La Persona ${personaActual} ya ha sido guardada.`);
        return;
    }

    // Verificar que haya seleccionado al menos un producto
    if (!productosAsignados[personaActual] || productosAsignados[personaActual].length === 0) {
        alert("Debes seleccionar al menos un producto para esta persona.");
        return;
    }

    // Marcar persona como completada
    personasCompletadas.push(personaActual);

    // Mostrar mensaje de confirmación
    let mensaje = document.getElementById("mensaje-confirmacion");
    mensaje.style.display = "block";
    mensaje.innerHTML = `<i class="fas fa-check-circle"></i> Persona ${personaActual} guardada correctamente`;

    setTimeout(() => {
        mensaje.style.display = "none";
    }, 2000);

    // Actualizar resumen
    let resumen = document.getElementById("resumen-division-modal");

    // Limpiar resumen si es la primera persona
    if (personaActual === 1 || personasCompletadas.length === 1) {
        resumen.innerHTML = "<h3 style='margin-bottom:10px; color: var(--accent); border-bottom: 1px solid var(--border-color); padding-bottom: 5px;'>Resumen de División</h3>";
    }

    // Calcular el total considerando precio base y extras
    let totalPersona = productosAsignados[personaActual].reduce((sum, p) => {
        let precioItem = (p.precio + (p.extraPrecio || 0)) * (p.cantidad || 1);
        return sum + precioItem;
    }, 0);

    // AQUÍ ESTABA EL ERROR: Se corrigió la forma de leer el nombre y cantidad del producto
    let listaNombres = productosAsignados[personaActual].map(p => {
        let cant = p.cantidad ? p.cantidad + 'x ' : '';
        return cant + p.nombre;
    }).join(', ');

    resumen.innerHTML += `
        <div style="padding: 10px 0; border-bottom: 1px dashed var(--border-color);">
            <strong style="color: var(--text-main);">Persona ${personaActual}</strong>: <span style="font-weight: bold; color: var(--accent);">$${totalPersona.toFixed(2)}</span><br>
            <small style="color:var(--text-muted); display: block; margin-top: 4px;">
                ${listaNombres}
            </small>
        </div>
    `;

    // Actualizar botones y progreso
    if (typeof generarBotonesPersonas === "function") generarBotonesPersonas();
    if (typeof actualizarProgreso === "function") actualizarProgreso();

    // Avanzar a la siguiente persona disponible
    let siguientePersona = null;
    for (let i = 1; i <= totalPersonas; i++) {
        if (!personasCompletadas.includes(i)) {
            siguientePersona = i;
            break;
        }
    }

    if (siguientePersona) {
        // Cambiar automáticamente a la siguiente persona
        if (typeof seleccionarPersona === "function") seleccionarPersona(siguientePersona);
    } else {
        // Todas las personas han sido configuradas (¡AQUÍ SE DESBLOQUEA EL BOTÓN!)
        document.getElementById("btn-aplicar-division").disabled = false;
        document.getElementById("btn-guardar-persona").disabled = true;

        // Mostrar mensaje de completado
        let contenedor = document.getElementById("lista-productos-persona");
        contenedor.innerHTML = `
            <div style="text-align: center; padding: 20px; background: rgba(39, 174, 96, 0.1); border: 1px solid #27AE60; color: #27AE60; border-radius: 6px;">
                <i class="fas fa-check-circle" style="font-size: 32px; margin-bottom: 10px;"></i>
                <h4 style="margin-bottom: 5px;">¡Configuración completa!</h4>
                <p style="font-size: 13px;">Haz clic en "APLICAR DIVISIÓN" para terminar.</p>
            </div>
        `;
    }
}

function cancelarSeleccionProductos() {
    // Volver a la selección de tipo de división
    document.getElementById("seccion-productos-detalle").style.display = "none";
    document.getElementById("seccion-num-personas-global").style.display = "block";
}

function calcularDivisionIgual() {
    let total = parseFloat(document.getElementById("cobro-total").innerText);
    let montoPorPersona = total / totalPersonas;

    for (let i = 0; i < personas.length; i++) {
        personas[i].monto = montoPorPersona;
    }

    mostrarResumenDivision();
    document.getElementById("btn-aplicar-division").disabled = false;
}

function mostrarResumenDivision() {
    let resumen = document.getElementById("resumen-division-modal");
    resumen.innerHTML = "<h3 style='margin-bottom:10px;'>Resumen de División</h3>";

    if (tipoDivision === 'iguales') {
        personas.forEach(p => {
            resumen.innerHTML += `
                <div style="padding: 8px; border-bottom: 1px solid var(--border-color);">
                    <strong>Persona ${p.numero}</strong>: $${p.monto.toFixed(2)}
                </div>
            `;
        });
    }
}

function aplicarDivision() {
    // Mostrar resumen en la pantalla de cobro
    let resumenDiv = document.getElementById("resumen-division");
    let detalleDiv = document.getElementById("detalle-division");
    detalleDiv.innerHTML = "";

    if (tipoDivision === 'iguales') {
        personas.forEach(p => {
            detalleDiv.innerHTML += `
                <div class="split-item">
                    <span>Persona ${p.numero}</span>
                    <span>$${p.monto.toFixed(2)}</span>
                </div>
            `;
        });
    } else {
        for (let i = 1; i <= totalPersonas; i++) {
            if (productosAsignados[i] && productosAsignados[i].length > 0) {
                let total = productosAsignados[i].reduce((sum, p) => sum + p.precio, 0);
                detalleDiv.innerHTML += `
                    <div class="split-item">
                        <span>Persona ${i}</span>
                        <span>$${total.toFixed(2)}</span>
                    </div>
                `;
            }
        }
    }

    resumenDiv.style.display = "block";
    cerrarModalDivision();
}

function cancelarDivision() {
    document.getElementById("resumen-division").style.display = "none";
    tipoDivision = null;
    totalPersonas = 0;
    personas = [];
    productosAsignados = {};
    personasCompletadas = [];
}

// --- COBRO — navega en la misma pestaña ---
function cobrarMesa() {
    if (mesaActiva.pedido.length === 0) return alert("LA MESA ESTÁ VACÍA");
    mesaActiva.estado = 'cobro';

    // Actualizar estado mesa en BD
    fetch('mesas.php?accion=estado', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_mesa: mesaActiva.id, estado: 'cobro' })
    }).catch(() => {});

    const sesion = _getSesion();
    try {
        localStorage.setItem('cobro_datos_pendiente', JSON.stringify({
            tipo:     'iniciar_cobro',
            mesaId:   mesaActiva.id,
            mesaNum:  mesaActiva.numero,
            pedido:   mesaActiva.pedido,
            usuario:  sesion,
            ts:       Date.now()
        }));
    } catch(e) {}
    _guardarEstadoMesas();
    window.location.href = 'cobro.php';
}

// Polling: detecta cuando cobro.html confirma el pago
(function _pollingCobro() {
    setInterval(() => {
        try {
            const raw = localStorage.getItem('cobro_completado');
            if (!raw) return;
            const data = JSON.parse(raw);
            if (data.ts < Date.now() - 60000) {
                localStorage.removeItem('cobro_completado');
                return;
            }
            localStorage.removeItem('cobro_completado');

            const mesa = mesas.find(m => m.id === data.mesaId);
            if (mesa) {
                mesa.pedido        = [];
                mesa.estado        = 'libre';
                mesa.horaOcupacion = null;
                mesa._totalConsumoBD = 0;
            }
            _guardarEstadoMesas();
            renderizarMesas();
            // Refresh único desde BD
            cargarMesasDesdeBD();
        } catch(e) {}
    }, 2000);
})();

// ── Sincronizar estados de ítems desde cocina/barra → mesas mesero ──
(function _pollingEstadosItems() {
    setInterval(async () => {
        try {
            // Lee estados de ítems directamente de BD (cocina/barra actualizan BD)
            const res  = await fetch('pedidos.php?accion=estados_items');
            const data = await res.json();
            if (!data.ok || !data.items) return;

            // data.items = [{id_detalle, id_mesa_fk, nombre, estado_item}, ...]
            // Construir Set de id_detalle presentes en BD para detectar ausentes
            const bdIds = new Set(data.items.map(b => b.id_detalle));

            let cambio = false;
            mesas.forEach(mesa => {
                if (!mesa.pedido || !mesa.pedido.length) return;
                mesa.pedido.forEach(item => {
                    // 1) Buscar por id_detalle exacto (uniqueId = 'bd-X')
                    const idDetalle = (item.uniqueId && item.uniqueId.startsWith('bd-'))
                        ? parseInt(item.uniqueId.replace('bd-', ''), 10)
                        : null;

                    const bdItem = data.items.find(b =>
                        (idDetalle && b.id_detalle === idDetalle) ||
                        (b.id_mesa_fk == mesa.id && b.nombre === item.nombre)
                    );

                    if (bdItem) {
                        // Item encontrado en BD → actualizar estado normalmente
                        const nuevoEstado = bdItem.estado_item || 'pendiente';
                        if (item.estado !== nuevoEstado) {
                            item.estado = nuevoEstado;
                            cambio = true;
                        }
                    } else if (idDetalle && !bdIds.has(idDetalle) && item.estado !== 'listo') {
                        // BUG FIX 3: el item tenía uniqueId 'bd-X' pero ya no está en BD activa
                        // Significa que el pedido fue entregado por cocina/barra
                        // → marcarlo como listo para que mesas muestre ✓ en vez de ·
                        item.estado = 'listo';
                        cambio = true;
                    }
                });
            });

            if (cambio) {
                _guardarEstadoMesas();
                renderizarMesas();
            }
        } catch(e) {}
    }, 3000);
})();

// ── Polling de notificaciones de BD para el mesero ───────────
// (Reemplazado por _pollingMensajesBD al final del archivo)


function _mostrarToastMesero(msg, numeroMesa) {
    if (numeroMesa) {
        document.querySelectorAll('.mesa').forEach(el => {
            const header = el.querySelector('.mesa-numero');
            if (header && header.textContent.trim().includes(String(numeroMesa))) {
                el.classList.add('mesa-notif-flash');
                setTimeout(() => el.classList.remove('mesa-notif-flash'), 3000);
            }
        });
    }
    let toast = document.getElementById('toast-notif-mesero');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-notif-mesero';
        toast.style.cssText = [
            'position:fixed', 'bottom:24px', 'left:50%', 'transform:translateX(-50%)',
            'background:#1E6E42', 'color:#fff', 'padding:12px 22px',
            'border-radius:10px', 'font-size:14px', 'font-weight:600',
            'box-shadow:0 4px 20px rgba(0,0,0,0.3)', 'z-index:9999',
            'max-width:360px', 'text-align:center', 'transition:opacity 0.3s ease'
        ].join(';');
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    toast.style.display = 'block';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => { toast.style.display = 'none'; }, 300);
    }, 4000);
}

// --- GESTIÓN DE PROPINAS (manejado en cobro.html) ---
// Las funciones propinaManual, agregarPropina, actualizarTotales,
// finalizarPago, cerrarMesaSinTicket, mostrarTicketModal, imprimirTicketYCerrar
// fueron migradas a cobro.html
// ── Guardar / Restaurar estado de mesas (para navegación en misma pestaña) ──
function _guardarEstadoMesas() {
    try { localStorage.setItem('pos_mesas_estado', JSON.stringify(mesas)); } catch(e) {}
}
function _restaurarEstadoMesas() {
    try {
        const raw = localStorage.getItem('pos_mesas_estado');
        if (raw) {
            const g = JSON.parse(raw);
            if (Array.isArray(g) && g.length > 0) {
                mesas.length = 0;
                g.forEach(m => { m.id = parseInt(m.id, 10); mesas.push(m); });
            }
        }
    } catch(e) {}
}

// ── Helper sesión ─────────────────────────────────────────────
function _getSesion() {
    try { return JSON.parse(sessionStorage.getItem('pos_usuario') || '{}'); }
    catch(e) { return {}; }
}

// ── Cargar mesas desde MySQL al hacer login ───────────────────
async function cargarMesasDesdeBD() {
    try {
        const res  = await fetch('mesas.php?accion=listar');
        const data = await res.json();

        if (!data.ok || !Array.isArray(data.mesas) || data.mesas.length === 0) return;

        // Reconstruir mesas desde BD preservando pedidos y reservas locales
        const mesasLocalesPrevias = [...mesas];
        mesas = data.mesas.map(m => {
            const id     = parseInt(m.id_mesa, 10);
            const local  = mesasLocalesPrevias.find(x => x.id === id);
            return {
                id,
                numero:        parseInt(m.numero_mesa || id),
                estado:        local?.estado        || 'libre',
                pedido:        local?.pedido        || [],
                horaOcupacion: local?.horaOcupacion || null,
                reserva:       local?.reserva       || null,
                _totalConsumoBD: local?._totalConsumoBD || 0
            };
        });

        // Actualizar cada mesa con datos reales de BD
        data.mesas.forEach(m => {
            const id      = parseInt(m.id_mesa, 10);
            const local   = mesas.find(x => x.id === id);
            if (!local) return;

            // Siempre sincronizar el número visible desde BD
            local.numero = parseInt(m.numero_mesa || id);

            const estadoBD = m.estado || 'libre';

            // ── Si BD dice LIBRE → mesa cobrada, limpiar TODO ──────
            if (estadoBD === 'libre') {
                local.estado        = 'libre';
                local.pedido        = [];
                local.horaOcupacion = null;
                local._totalConsumoBD = 0;
                // NO borrar local.reserva aquí — si BD devuelve una reserva activa
                // para esta mesa, la procesamos abajo aunque el estado sea libre
                if (!m.reserva) {
                    local.reserva = null;
                    return;
                }
                // Tiene reserva → no hacer return, seguir para procesar la reserva
            }

            // ── Si BD dice COBRO pero sin pedidos activos ni consumo
            //    → probablemente el trigger falló; forzar a libre ──
            if (estadoBD === 'cobro' &&
                (!m.pedidos || m.pedidos.length === 0) &&
                (!m.total_consumo || parseFloat(m.total_consumo) === 0)) {
                // Forzar en BD y limpiar local
                fetch('mesas.php?accion=estado', {
                    method:'POST', headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({ id_mesa: parseInt(m.id_mesa), estado: 'libre' })
                }).catch(()=>{});
                local.estado        = 'libre';
                local.pedido        = [];
                local.horaOcupacion = null;
                local._totalConsumoBD = 0;
                return;
            }

            // ── Estado, hora y reserva desde BD ───────────────────
            local.estado        = estadoBD;
            // FIX: hora_ocupacion ya llega como timestamp ms desde mesas.php
            // new Date(string_sin_tz) daba diferencia de 6h con UTC
            local.horaOcupacion = m.hora_ocupacion
                ? (typeof m.hora_ocupacion === 'number'
                    ? m.hora_ocupacion
                    : new Date(m.hora_ocupacion).getTime())
                : null;

            // Convertir reserva MySQL → formato JS del modal (PRIMERO para que timestamp esté disponible)
            if (m.reserva && m.reserva.fecha_reserva) {
                const fechaStr = m.reserva.fecha_reserva || '';
                const fParts   = fechaStr.replace('T', ' ').split(' ');
                const datePart = (fParts[0] || '').split('-');
                const timePart = (fParts[1] || '12:00:00').split(':');
                const anio   = parseInt(datePart[0]) || new Date().getFullYear();
                const mes    = (parseInt(datePart[1]) || 1) - 1;
                const dia    = parseInt(datePart[2]) || 1;
                const h24    = parseInt(timePart[0]) || 12;
                const min    = parseInt(timePart[1]) || 0;
                const ampm   = h24 >= 12 ? 'PM' : 'AM';
                const hora12 = h24 === 0 ? 12 : h24 > 12 ? h24 - 12 : h24;
                local.reserva = {
                    anio, mesIndex: mes, dia, hora12,
                    minuto: min, ampm, hora24: h24,
                    timestamp: new Date(anio, mes, dia, h24, min).getTime(),
                    pax:      parseInt(m.reserva.num_personas)  || 2,
                    nombre:   m.reserva.nombre_cliente          || '',
                    nota:     m.reserva.nota                    || '',
                    notifMin: parseInt(m.reserva.notif_minutos) || 15,
                    _id_bd:   parseInt(m.reserva.id_reserva)    || 0,
                };
            } else if (m.reserva && m.reserva.anio !== undefined) {
                local.reserva = m.reserva;
            }

            // Si hay reserva para HOY y faltan <= notif_minutos → pintar morada en BD
            // Si falta más tiempo → dejar libre (se puede usar normalmente)
            if (local.reserva && local.reserva.timestamp) {
                const hoy     = new Date();
                const esHoy   = local.reserva.anio === hoy.getFullYear() &&
                                local.reserva.mesIndex === hoy.getMonth() &&
                                local.reserva.dia === hoy.getDate();
                const ahora   = Date.now();
                const tsRes   = local.reserva.timestamp;
                const notifMs = (local.reserva.notifMin || 15) * 60 * 1000;
                const dentroVentana = (tsRes - ahora) <= notifMs; // faltan <=15min O ya pasó

                if (esHoy && dentroVentana && estadoBD === 'libre') {
                    // Ya es hora — marcar en BD para que TODOS los dispositivos la vean morada
                    fetch('mesas.php?accion=estado', {
                        method: 'POST', headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ id_mesa: parseInt(m.id_mesa), estado: 'reservada' })
                    }).catch(()=>{});
                    local.estado = 'reservada';
                } else if (esHoy && !dentroVentana && estadoBD === 'reservada') {
                    // La reserva fue guardada antes de nuestra fix y quedó morada en BD
                    // pero aún falta mucho tiempo → liberar para que se pueda usar
                    fetch('mesas.php?accion=estado', {
                        method: 'POST', headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ id_mesa: parseInt(m.id_mesa), estado: 'libre' })
                    }).catch(()=>{});
                    local.estado = 'libre';
                }
            }

            // ── Pedidos activos desde BD ─────────────────────────────────
            // Solo sobreescribir si BD trae datos — NUNCA borrar pedidos locales
            // con array vacío (puede ser race condition o retraso en query)
            if (m.pedidos && m.pedidos.length > 0) {
                const seen = new Set();
                local.pedido = m.pedidos.filter(p => {
                    const key = p.uniqueId || (p.nombre + '_' + p.cantidad);
                    if (seen.has(key)) return false;
                    seen.add(key);
                    return true;
                });
                if (local.estado === 'libre') local.estado = 'ocupada';
            }
            // Guardar total consumo de BD — usarlo también cuando pedidos local está vacío
            if (m.total_consumo !== undefined) {
                local._totalConsumoBD = parseFloat(m.total_consumo) || 0;
            }
            // Si BD no tiene pedidos pero local sí → conservar (recién enviado, aún no en BD)
        });

        mesas.sort((a, b) => a.id - b.id);
        _guardarEstadoMesas();
        renderizarMesas();

    } catch(e) {
        console.warn('mesas.php no disponible — usando estado local');
    }
}

// ── GUARDAR RESERVA en BD ─────────────────────────────────────
// (se llama desde guardarReserva() en el modal de reserva)
function _guardarReservaEnBD(idMesa, mesa, h24) {
    const mesNum = String(calEstado.mes + 1).padStart(2, '0');
    const diaNum = String(calEstado.diaSelec).padStart(2, '0');
    const horNum = String(h24).padStart(2, '0');
    const minNum = String(calEstado.minuto).padStart(2, '0');
    const fechaISO = `${calEstado.anio}-${mesNum}-${diaNum} ${horNum}:${minNum}:00`;

    fetch('reservas.php?accion=guardar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            nombre:        mesa.reserva.nombre,
            fecha_reserva: fechaISO,
            num_personas:  calEstado.pax,
            nota:          mesa.reserva.nota || '',
            notif_minutos: calEstado.notifMin,
            id_mesa:       idMesa,
            id_reserva:    mesa.reserva._id_bd || 0
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            mesa.reserva._id_bd = data.id_reserva;
            console.log('✅ Reserva en BD id:', data.id_reserva);
        } else console.warn('⚠ Reserva BD error:', data.error);
    })
    .catch(() => console.warn('PHP no disponible — reserva solo en localStorage'));
}

// ════════════════════════════════════════════════════════════════
//  RF-01 / RF-06 / RF-07 / RF-16
//  RESUMEN DE MESA — ver consumo, agregar más, enviar a cobro
// ════════════════════════════════════════════════════════════════

function abrirResumenMesa(id) {
    const idNum = parseInt(id, 10);
    const mesa  = mesas.find(m => m.id === idNum);
    if (!mesa) return;

    // Si está en cobro, solo mostrar info de espera
    if (mesa.estado === 'cobro') {
        _mostrarModalResumen(mesa, true);
        return;
    }
    _mostrarModalResumen(mesa, false);
}

function _mostrarModalResumen(mesa, enCobro) {
    const viejo = document.getElementById('modal-resumen-mesa');
    if (viejo) viejo.remove();

    const total = mesa.pedido.reduce((s, i) =>
        s + ((i.precio + (i.extraPrecio || 0)) * i.cantidad), 0);

    // Separar por estado
    const listos     = mesa.pedido.filter(i => i.estado === 'listo');
    const preparando = mesa.pedido.filter(i => i.estado === 'preparando');
    const pendientes = mesa.pedido.filter(i => !i.estado || i.estado === 'pendiente');

    function filaItem(item) {
        const sub = ((item.precio + (item.extraPrecio||0)) * item.cantidad).toFixed(2);
        const icon = item.estado === 'listo' ? '✅' : item.estado === 'preparando' ? '⏳' : '🕐';
        const rutaBadge = item.ruta === 'barra'
            ? `<span style="font-size:10px;padding:1px 6px;border-radius:8px;background:rgba(26,90,138,.1);color:#1A5A8A;margin-left:4px;">🥤 Barra</span>`
            : `<span style="font-size:10px;padding:1px 6px;border-radius:8px;background:rgba(74,122,61,.1);color:#4A7A3D;margin-left:4px;">🍳 Cocina</span>`;
        return `<div class="resumen-item-fila">
            <span class="resumen-item-icon">${icon}</span>
            <span class="resumen-item-qty">${item.cantidad}×</span>
            <span class="resumen-item-nom">${item.nombre}${rutaBadge}${item.nota ? `<span class="resumen-item-nota"> · ${item.nota}</span>` : ''}</span>
            <span class="resumen-item-precio">$${sub}</span>
        </div>`;
    }

    function seccion(titulo, items, colorClass) {
        if (!items.length) return '';
        return `<div class="resumen-seccion ${colorClass}">
            <div class="resumen-sec-titulo">${titulo} <span class="resumen-sec-badge">${items.length}</span></div>
            ${items.map(filaItem).join('')}
        </div>`;
    }

    // Historial de consumo de esta mesa (cocina + barra)
    function _buildHistorialTab(idMesa) {
        let histC = [], histB = [];
        try { histC = JSON.parse(localStorage.getItem('historial_cocina')||'[]').filter(h=>h.mesa==idMesa); } catch(e){}
        try { histB = JSON.parse(localStorage.getItem('historial_barra')||'[]').filter(h=>h.mesa==idMesa); } catch(e){}
        // Unir y ordenar cronológico
        const todos = [...histC.map(h=>({...h,area:'🍳 Cocina'})), ...histB.map(h=>({...h,area:'🥤 Barra'}))]
            .sort((a,b) => a.ts - b.ts);
        if (!todos.length) return `<div class="resumen-vacio"><i class="fas fa-history"></i><p>Sin historial de entregas para esta mesa.</p></div>`;
        return todos.map(h => {
            const items = h.items.map(i=>`<span>${i.cantidad}× ${i.nombre}</span>`).join(', ');
            return `<div class="hist-entrada">
                <div class="hist-meta">
                    <span class="hist-area">${h.area}</span>
                    <span class="hist-hora"><i class="fas fa-clock"></i> ${h.hora}</span>
                </div>
                <div class="hist-items">${items}</div>
            </div>`;
        }).join('');
    }

    const tiempoStr = mesa.horaOcupacion
        ? `· ${calcularTiempoOcupacion(mesa.horaOcupacion)} en mesa` : '';

    const modal = document.createElement('div');
    modal.id = 'modal-resumen-mesa';
    modal.className = 'modal-overlay activo';
    modal.innerHTML = `
    <div class="modal-resumen-caja" onclick="event.stopPropagation()">

        <!-- Header -->
        <div class="resumen-header">
            <div class="resumen-header-info">
                <span class="resumen-mesa-num">MESA ${mesa.id}</span>
                <span class="resumen-tiempo">${tiempoStr}</span>
            </div>
            <button class="resumen-btn-cerrar" onclick="cerrarResumenMesa()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        ${enCobro ? `
        <div class="resumen-cobro-aviso">
            <i class="fas fa-cash-register"></i>
            <div>
                <strong>Mesa enviada a cobro</strong>
                <p>El cajero está procesando el pago. En espera...</p>
            </div>
        </div>` : ''}

        <!-- Tabs: Pedidos Activos | Historial -->
        <div class="resumen-tabs" id="resumen-tabs-${mesa.id}">
            <button class="resumen-tab activo" onclick="_switchResumenTab('pedidos','${mesa.id}')">
                <i class="fas fa-list-check"></i> Pedidos activos
                ${mesa.pedido.length > 0 ? `<span class="resumen-tab-badge">${mesa.pedido.length}</span>` : ''}
            </button>
            <button class="resumen-tab" onclick="_switchResumenTab('historial','${mesa.id}')">
                <i class="fas fa-history"></i> Historial entregado
            </button>
        </div>

        <!-- Panel: Pedidos activos -->
        <div id="rpanel-pedidos-${mesa.id}" class="resumen-tab-panel activo">
            <div class="resumen-cuerpo">
                ${mesa.pedido.length === 0
                    ? `<div class="resumen-vacio"><i class="fas fa-utensils"></i><p>Esta mesa no tiene pedidos activos.</p></div>`
                    : `
                    ${seccion('✅ Listos — esperando mesero', listos, 'sec-listo')}
                    ${seccion('⏳ En preparación (cocina/barra)', preparando, 'sec-prep')}
                    ${seccion('🕐 Pendientes de envío', pendientes, 'sec-pend')}
                    `
                }
            </div>
            ${total > 0 ? `
            <div class="resumen-total-fila">
                <span>TOTAL CONSUMIDO</span>
                <span class="resumen-total-monto">$${total.toFixed(2)}</span>
            </div>` : ''}
        </div>

        <!-- Panel: Historial entregado -->
        <div id="rpanel-historial-${mesa.id}" class="resumen-tab-panel" style="display:none;">
            <div class="resumen-cuerpo historial-cuerpo">
                ${_buildHistorialTab(mesa.id)}
            </div>
        </div>

        <!-- Acciones -->
        ${!enCobro ? `
        <div class="resumen-acciones">
            <button class="resumen-btn-agregar" onclick="cerrarResumenMesa(); abrirMesa(${mesa.id})">
                <i class="fas fa-plus-circle"></i> Agregar más productos
            </button>
            ${mesa.pedido.length > 0 ? `
            <button class="resumen-btn-cobrar" onclick="cerrarResumenMesa(); _enviarMesaACobro(${mesa.id})">
                <i class="fas fa-cash-register"></i> Enviar a cobro
            </button>` : ''}
        </div>` : `
        <div class="resumen-acciones">
            <button class="resumen-btn-secundario" onclick="cerrarResumenMesa()">
                <i class="fas fa-arrow-left"></i> Volver a mesas
            </button>
        </div>`}
    </div>`;

    modal.addEventListener('click', cerrarResumenMesa);
    document.body.appendChild(modal);
}

function _switchResumenTab(tab, mesaId) {
    const panelPedidos  = document.getElementById(`rpanel-pedidos-${mesaId}`);
    const panelHistorial = document.getElementById(`rpanel-historial-${mesaId}`);
    const tabs = document.querySelectorAll(`#resumen-tabs-${mesaId} .resumen-tab`);
    tabs.forEach(t => t.classList.remove('activo'));
    if (tab === 'pedidos') {
        panelPedidos.style.display = '';
        panelHistorial.style.display = 'none';
        tabs[0].classList.add('activo');
    } else {
        panelPedidos.style.display = 'none';
        panelHistorial.style.display = '';
        tabs[1].classList.add('activo');
    }
}

function cerrarResumenMesa() {
    const m = document.getElementById('modal-resumen-mesa');
    if (m) m.remove();
}

// RF-06: Enviar mesa a cobro (el mesero la manda, él se queda en sus mesas)
function _enviarMesaACobro(id) {
    const mesa = mesas.find(m => m.id === id);
    if (!mesa) return;
    if (mesa.pedido.length === 0) {
        alert('Esta mesa no tiene pedidos para cobrar.');
        return;
    }

    // Confirmar
    const total = mesa.pedido.reduce((s, i) =>
        s + ((i.precio + (i.extraPrecio || 0)) * i.cantidad), 0);

    if (!confirm(`¿Enviar Mesa ${mesa.id} a cobro?\nTotal: $${total.toFixed(2)}`)) return;

    // Marcar como 'cobro' localmente
    mesa.estado = 'cobro';
    _guardarEstadoMesas();

    // Actualizar en BD
    fetch('mesas.php?accion=estado', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_mesa: mesa.id, estado: 'cobro' })
    }).catch(() => {});

    // Guardar datos para cobro.php (el cajero los lee)
    const sesion = _getSesion();
    try {
        localStorage.setItem('cobro_datos_pendiente', JSON.stringify({
            tipo:    'iniciar_cobro',
            mesaId:  mesa.id,
            mesaNum: mesa.id,
            pedido:  mesa.pedido,
            usuario: sesion,
            ts:      Date.now()
        }));
    } catch(e) {}

    renderizarMesas();
    _toastMesero(`Mesa ${mesa.id} enviada a cobro ✓`);
}
// ════════════════════════════════════════════════════════════════
// SISTEMA DE MENSAJES — Notificaciones de Cocina/Barra al Mesero
// ════════════════════════════════════════════════════════════════

let _mensajesGuardados = []; // Todos los mensajes recibidos en esta sesión
let _mensajesSinLeer   = 0;

// ── Abrir panel de mensajes ──────────────────────────────────
function abrirPanelMensajes() {
    const panel   = document.getElementById('panel-mensajes');
    const overlay = document.getElementById('overlay-mensajes');
    if (!panel) return;
    panel.style.display = 'flex';
    if (overlay) overlay.style.display = 'block';
    _mensajesSinLeer = 0;
    _actualizarBadgeMensajes();
    _renderMensajes();
}

function cerrarPanelMensajes() {
    const panel   = document.getElementById('panel-mensajes');
    const overlay = document.getElementById('overlay-mensajes');
    if (panel)   panel.style.display   = 'none';
    if (overlay) overlay.style.display = 'none';
}

// ── Actualizar badge del botón ─────────────────────────────
function _actualizarBadgeMensajes() {
    const badge = document.getElementById('badge-mensajes');
    if (!badge) return;
    if (_mensajesSinLeer > 0) {
        badge.textContent = _mensajesSinLeer > 9 ? '9+' : String(_mensajesSinLeer);
        badge.style.display = 'flex';
        badge.style.alignItems = 'center';
        badge.style.justifyContent = 'center';
    } else {
        badge.style.display = 'none';
    }
}

// ── Renderizar lista de mensajes ──────────────────────────
function _renderMensajes() {
    const lista = document.getElementById('lista-mensajes');
    if (!lista) return;

    if (_mensajesGuardados.length === 0) {
        lista.innerHTML = `<div style="text-align:center; padding:40px 0; color:var(--text-muted,#6B7F65);">
            <i class="fas fa-bell-slash" style="font-size:32px; display:block; margin-bottom:10px; opacity:.3;"></i>
            <p style="font-size:13px;">Sin mensajes nuevos</p>
        </div>`;
        return;
    }

    lista.innerHTML = _mensajesGuardados.slice().reverse().map((m, idx) => {
        const realIdx = _mensajesGuardados.length - 1 - idx;
        const esOrigen = m.origen === 'cocina' ? '🍳 Cocina' : '🥤 Barra';
        const colorOrigen = m.origen === 'cocina' ? '#B07D12' : '#6B2FAA';
        const bgOrigen    = m.origen === 'cocina' ? 'rgba(176,125,18,.1)' : 'rgba(107,47,170,.1)';

        const hora = new Date(m.ts).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });

        const yaConfirmado = m.confirmado;

        return `<div style="background:var(--bg-app,#F0F4EE); border-radius:12px; border:1px solid var(--border-color,#D4E0CF); padding:14px 16px; ${yaConfirmado ? 'opacity:0.55;' : ''}">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:11px; font-weight:700; padding:3px 9px; border-radius:20px; background:${bgOrigen}; color:${colorOrigen};">${esOrigen}</span>
                <span style="font-size:11px; color:var(--text-muted,#6B7F65);">${hora}</span>
            </div>
            <div style="font-size:13px; font-weight:700; color:var(--text-main,#1C2B18); margin-bottom:4px;">
                🪑 Mesa ${m.numero_mesa}
            </div>
            <div style="font-size:13px; color:var(--text-main,#1C2B18); margin-bottom:12px; line-height:1.5;">
                ${_escMsj(m.mensaje)}
            </div>
            ${yaConfirmado
                ? `<div style="font-size:12px; color:var(--text-muted,#6B7F65); display:flex; align-items:center; gap:6px;">
                       <i class="fas fa-check-circle" style="color:#2E7040;"></i> Entrega confirmada
                   </div>`
                : `<button onclick="_confirmarEntrega(${realIdx})" style="width:100%; padding:9px; background:var(--accent,#4A7A3D); color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                       <i class="fas fa-check"></i> Confirmar entrega a Mesa ${m.numero_mesa}
                   </button>`
            }
        </div>`;
    }).join('');
}

// ── Confirmar entrega de un mensaje ──────────────────────
function _confirmarEntrega(idx) {
    if (!_mensajesGuardados[idx]) return;
    _mensajesGuardados[idx].confirmado = true;

    // Marcar como leída en BD si tiene id
    const notifId = _mensajesGuardados[idx].id_notif;
    if (notifId) {
        fetch('comandas.php?accion=marcar_leida', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_notif: parseInt(notifId) })
        }).catch(() => {});
    }

    // Flash visual en la tarjeta de la mesa correspondiente
    const numMesa = _mensajesGuardados[idx].numero_mesa;
    if (numMesa) {
        document.querySelectorAll('.mesa').forEach(el => {
            const header = el.querySelector('.mesa-numero');
            if (header && header.textContent.trim().includes(String(numMesa))) {
                el.style.transition = 'box-shadow 0.2s';
                el.style.boxShadow = '0 0 0 4px #2E7040';
                setTimeout(() => { el.style.boxShadow = ''; }, 1500);
            }
        });
    }

    _renderMensajes();
    _toastMesero(`✅ Entrega confirmada — Mesa ${numMesa}`);
}

// ── Limpiar mensajes ya confirmados ──────────────────────
function limpiarMensajesLeidos() {
    _mensajesGuardados = _mensajesGuardados.filter(m => !m.confirmado);
    _renderMensajes();
}

// ── Agregar mensaje nuevo y notificar ────────────────────
function _agregarMensajeNuevo(notif) {
    // Evitar duplicados
    if (_mensajesGuardados.some(m => m.id_notif && m.id_notif == notif.id_notif)) return;

    _mensajesGuardados.push({
        id_notif:    notif.id_notif,
        origen:      notif.origen || 'cocina',
        numero_mesa: notif.numero_mesa,
        mensaje:     notif.mensaje || notif.tipo,
        ts:          Date.now(),
        confirmado:  false
    });

    _mensajesSinLeer++;
    _actualizarBadgeMensajes();

    // Si el panel está abierto, re-renderizar
    const panel = document.getElementById('panel-mensajes');
    if (panel && panel.style.display !== 'none') {
        _mensajesSinLeer = 0;
        _actualizarBadgeMensajes();
        _renderMensajes();
    }

    // Animación en el botón de mensajes
    const btnMsj = document.getElementById('btn-mensajes');
    if (btnMsj) {
        btnMsj.style.transform = 'scale(1.12)';
        setTimeout(() => { btnMsj.style.transform = ''; }, 300);
    }
}

// ── Escape HTML ────────────────────────────────────────
function _escMsj(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ═══════════════════════════════════════════════════════════════
// POLLING MEJORADO — reemplaza el anterior, integra panel mensajes
// ═══════════════════════════════════════════════════════════════
(function _pollingMensajesBD() {
    const _vistasMsg = new Set();

    setInterval(async () => {
        const enMesas = document.getElementById('mesas')?.classList.contains('activa');
        const enPos   = document.getElementById('pos')?.classList.contains('activa');
        // FIX: procesar notificaciones siempre — no solo cuando la pantalla de mesas esté activa
        // El badge y el panel deben actualizarse aunque el mesero esté en otra sección

        try {
            const res  = await fetch('comandas.php?accion=notificaciones&destino=mesero');
            const data = await res.json();
            if (!data.ok || !data.notificaciones || !data.notificaciones.length) return;

            data.notificaciones.forEach(n => {
                if (_vistasMsg.has(n.id_notif)) return; // ya procesada
                _vistasMsg.add(n.id_notif);

                // Primero mostrar, luego marcar leída — evita perderla si falla el fetch
                _agregarMensajeNuevo(n);

                // Toast solo si está en pantalla de mesas
                if (enMesas || enPos) {
                    const origen = n.origen === 'barra' ? '🥤 Barra' : '🍳 Cocina';
                    _mostrarToastMesero(`${origen} — Mesa ${n.numero_mesa}: ${n.mensaje || n.tipo}`, n.numero_mesa);
                }

                // Marcar leída en BD después de procesarla
                fetch('comandas.php?accion=marcar_leida', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_notif: parseInt(n.id_notif) })
                }).catch(() => {});
            });
        } catch(e) {}
    }, 2000); // FIX: bajar de 4s a 2s para reducir el retraso percibido
})();
