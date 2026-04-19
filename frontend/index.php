<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Cafetería - Equipo 17</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <div id="login" class="pantalla activa" style="overflow-y:auto; align-items:center; justify-content:flex-start; padding:40px 20px;">
        <div style="display:flex; flex-direction:column; align-items:center; width:100%; max-width:360px; margin:0 auto;">
        <div class="login-box" style="width:100%;">
            <h2 style="color: var(--text-main);">ACCESO AL SISTEMA</h2>
            <p style="color: var(--text-muted); margin-bottom: 16px;">Ingresa tu nombre y PIN de acceso</p>

            <!-- Campo nombre -->
            <input
                type="text"
                id="login-nombre"
                placeholder="Tu nombre "
                maxlength="30"
                autocomplete="off"
                style="
                    width:100%; padding:10px 14px; margin-bottom:16px;
                    border:2px solid var(--border-color,#D4E0CF); border-radius:8px;
                    font-size:14px; font-family:inherit;
                    background:var(--bg-app,#F0F4EE); color:var(--text-main,#1C2B18);
                    outline:none; box-sizing:border-box;
                "
                onfocus="this.style.borderColor='var(--accent,#4A7A3D)'"
                onblur="this.style.borderColor='var(--border-color,#D4E0CF)'"
            >

            <div id="pin-display" class="pin-display"></div>

            <div class="teclado">
                <button onclick="teclear('1')">1</button>
                <button onclick="teclear('2')">2</button>
                <button onclick="teclear('3')">3</button>
                <button onclick="teclear('4')">4</button>
                <button onclick="teclear('5')">5</button>
                <button onclick="teclear('6')">6</button>
                <button onclick="teclear('7')">7</button>
                <button onclick="teclear('8')">8</button>
                <button onclick="teclear('9')">9</button>
                <button onclick="teclear('C')" class="btn-clear">C</button>
                <button onclick="teclear('0')">0</button>
                <button onclick="validarLogin()" class="btn-ok">OK</button>
            </div>

            <!-- Indicador de intentos -->
            <div id="login-intentos" style="display:flex; gap:6px; margin-top:14px; justify-content:center;">
                <span class="intento-pip" id="pip-0"></span>
                <span class="intento-pip" id="pip-1"></span>
                <span class="intento-pip" id="pip-2"></span>
                <span class="intento-pip" id="pip-3"></span>
                <span class="intento-pip" id="pip-4"></span>
            </div>

            <!-- Mensaje de error / bloqueo -->
            <p id="login-msg" style="
                display:none; margin-top:10px; font-size:12px;
                font-weight:600; text-align:center; padding:8px 12px;
                border-radius:6px; color:#A0320A;
                background:rgba(160,50,10,.08); border:1px solid rgba(160,50,10,.2);
            "></p>

        </div><!-- /login-box -->

        <!-- Link recuperar contraseña — FUERA de la caja, visible al bloquearse -->
        <div id="link-recuperar" style="
            display:none; margin-top:16px; text-align:center; width:360px;
        ">
            <a href="recuperar.php" style="
                display:inline-flex; align-items:center; justify-content:center; gap:8px;
                width:100%; font-size:13px; font-weight:700; color:#4A7A3D;
                text-decoration:none; padding:11px 18px;
                background:rgba(74,122,61,.10); border:1.5px solid rgba(74,122,61,.3);
                border-radius:10px; transition:all .18s; box-sizing:border-box;
            "
            onmouseover="this.style.background='rgba(74,122,61,.18)';this.style.borderColor='#4A7A3D'"
            onmouseout="this.style.background='rgba(74,122,61,.10)';this.style.borderColor='rgba(74,122,61,.3)'">
                <i class="fas fa-key"></i> ¿Olvidaste tu PIN? Recupéralo aquí
            </a>
        </div>

        </div><!-- /login-wrapper -->

        <style>
        .intento-pip {
            display:inline-block; width:10px; height:10px;
            border-radius:50%; background:var(--border-color,#D4E0CF);
            transition:background .2s;
        }
        .intento-pip.fallido { background:#A0320A; }
        </style>
    </div>

    <div id="mesas" class="pantalla">
        <header>
            <div class="header-title">SISTEMA POS | CONTROL DE MESAS</div>
            <div class="header-user">
                <button class="btn btn-header" onclick="toggleTema()" title="Cambiar Tema"><i
                        class="fas fa-moon icono-tema"></i></button>
                OPERADOR: <span id="operador-nombre">Itzel&Ximena</span>
                <!-- Botón Volver a Admin — solo visible si el usuario es admin -->
                <button id="btn-volver-admin-mesas" class="btn btn-header btn-volver-admin"
                        onclick="volverAlPanelAdmin()"
                        style="display:none;"
                        title="Volver al Panel de Administrador">
                    VOLVER A ADMIN
                </button>
                <button class="btn btn-header" onclick="cerrarSesion()"><i class="fas fa-sign-out-alt"></i>
                    SALIR</button>
            </div>
        </header>

        <div style="padding: 16px 32px 0 32px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <button class="btn btn-secundario" onclick="agregarMesa()">
                <i class="fas fa-plus-circle"></i> NUEVA MESA
            </button>
            <button class="btn" onclick="quitarMesa()" style="background-color: rgb(88, 53, 14); color: white;">
                <i class="fas fa-minus-circle"></i> QUITAR MESA
            </button>
            <button class="btn btn-reservas-general" onclick="abrirModalReservaGeneral()">
                <i class="fas fa-calendar-days"></i> RESERVAS
            </button>
            <div style="flex:1;"></div>
            <!-- Leyenda de estados -->
            <div class="leyenda-mesas">
                <div class="leyenda-item"><span class="leyenda-dot leyenda-dot-libre"></span>Disponible</div>
                <div class="leyenda-item"><span class="leyenda-dot leyenda-dot-ocupada"></span>Ocupada</div>
                <div class="leyenda-item"><span class="leyenda-dot leyenda-dot-cobro"></span>En cobro</div>
                <div class="leyenda-item"><span class="leyenda-dot leyenda-dot-reservada"></span>Reservada</div>
            </div>
        </div>

        <div class="grid-mesas" id="contenedor-mesas"></div>
    </div>

    <div id="pos" class="pantalla">
        <header>
            <div class="header-title">
                <button class="btn btn-header" onclick="volverMesas()" style="margin-left: 0; margin-right: 15px;"><i
                        class="fas fa-arrow-left"></i> VOLVER</button>
                <span id="titulo-mesa">MESA SELECCIONADA</span>
            </div>
            <div style="display: flex; align-items: center;">
                <div class="search-box">
                    <i class="bi bi-search me-2"></i>
                    <input type="text" id="searchInput" placeholder="Buscar producto..." onkeyup="buscarProductos()">
                </div>
                
                <div class="header-user">
                    <button class="btn btn-header" onclick="toggleTema()"><i class="fas fa-moon icono-tema"></i></button>
                    TOMA DE ORDEN
                </div>
            </div>
        </header>
        <div class="layout-pos">
            <div class="categorias">
                <button class="cat-btn activa" onclick="filtrarCat('calientes', this)"><i class="fas fa-mug-hot"></i> CALIENTES</button>
                <button class="cat-btn" onclick="filtrarCat('frias', this)"><i class="fas fa-glass-whiskey"></i> BEBIDAS FRÍAS</button>
                <button class="cat-btn" onclick="filtrarCat('crepas', this)"><i class="fas fa-utensils"></i> CREPAS Y WAFFLES</button>
                <button class="cat-btn" onclick="filtrarCat('snacks', this)"><i class="fas fa-leaf"></i> SNACKS Y ENSALADAS</button>
                <button class="cat-btn" onclick="filtrarCat('chapatas', this)"><i class="fas fa-bread-slice"></i> CHAPATAS Y CROISSANTS</button>
                <button class="cat-btn" onclick="filtrarCat('comida', this)"><i class="fas fa-burger"></i> BURRITOS Y HAMBURGUESAS</button>
                <button class="cat-btn" onclick="filtrarCat('cocteles', this)"><i class="fas fa-cocktail"></i> COCTELERÍA</button>
                <div style="border-top:1px solid var(--border-color); margin:10px 0;"></div>
                <button class="cat-btn cat-btn-postre" onclick="filtrarCat('postres', this)"><i class="fas fa-cookie-bite"></i> POSTRES TEMPORADA</button>
                <button class="cat-btn cat-btn-postre" onclick="filtrarCat('bebidasTemp', this)"><i class="fas fa-glass-water-droplet"></i> BEBIDAS TEMPORADA</button>
            </div>
            <div class="productos" id="lista-productos"></div>
            <div class="comanda">
                <div class="comanda-header">CUENTA ACTUAL</div>
                <div class="comanda-lista" id="lista-comanda"></div>
                <div class="comanda-total">
                    <div class="total-texto"><span>TOTAL</span><span>$<span id="total-comanda">0.00</span></span></div>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-secundario" style="flex: 1;" onclick="cobrarMesa()"><i
                                class="fas fa-cash-register"></i> COBRAR</button>
                        <button class="btn btn-principal" style="flex: 2;" onclick="abrirModalConfirmacion()"><i
                                class="fas fa-paper-plane"></i> ENVIAR</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MÓDULO DE COBRO: se abre en ventana separada (cobro.html) -->

    <!-- MODAL PARA DIVIDIR CUENTA: migrado a cobro.html -->

    <div id="modal-nota" class="modal-overlay">
        <div class="modal-caja">
            <h2>MODIFICAR ITEM</h2>
            <label style="display:block; margin-bottom:5px;">Añade notas para cocina:</label>
            <input type="text" id="input-nota" placeholder="Ej: Sin cebolla...">
            <label style="display:block; margin-bottom:5px;">Cobro Extra ($):</label>
            <input type="number" id="input-extra" value="0">
            <input type="hidden" id="item-index-editar">
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button class="btn btn-secundario" style="flex: 1;" onclick="cerrarModalNota()">CANCELAR</button>
                <button class="btn btn-principal" style="flex: 1;" onclick="guardarNota()">GUARDAR</button>
            </div>
        </div>
    </div>

<div id="modal-confirmacion" class="modal-overlay">
    <div class="modal-caja modal-orden-caja">

        <!-- ENCABEZADO -->
        <div class="modal-orden-header">
            <div class="modal-orden-titulo">
                <i class="fas fa-paper-plane"></i> CONFIRMAR Y ENRUTAR ORDEN
            </div>
            <div class="modal-orden-subtitulo">Arrastra o asigna cada producto a <strong>Barra</strong> o <strong>Cocina</strong></div>
        </div>

        <!-- DESTINO GLOBAL (aquí / llevar) -->
        <div class="modal-destino-global">
            <span class="modal-destino-label">¿Dónde consume?</span>
            <div style="display:flex;gap:8px;">
                <button id="btn-aqui" class="btn-destino activo" onclick="seleccionarDestino('aqui')">
                    <i class="fas fa-utensils"></i> Aquí
                </button>
                <button id="btn-llevar" class="btn-destino" onclick="seleccionarDestino('llevar')">
                    <i class="fas fa-bag-shopping"></i> Llevar <small>+$5</small>
                </button>
            </div>
        </div>

        <!-- ZONA DE ASIGNACIÓN: DOS COLUMNAS -->
        <div class="modal-orden-columnas">

            <!-- COLUMNA BARRA -->
            <div class="orden-columna orden-col-barra" id="col-barra" 
                 ondragover="event.preventDefault()" 
                 ondrop="dropEnColumna(event,'barra')">
                <div class="orden-col-header col-header-barra">
                    <i class="fas fa-glass-water-droplet"></i>
                    <span>BARRA</span>
                    <span class="orden-col-badge" id="badge-barra">0</span>
                </div>
                <div class="orden-col-lista" id="lista-barra">
                    <div class="orden-drop-hint" id="hint-barra">
                        <i class="fas fa-arrow-down"></i> Suelta aquí bebidas
                    </div>
                </div>
            </div>

            <!-- COLUMNA COCINA -->
            <div class="orden-columna orden-col-cocina" id="col-cocina"
                 ondragover="event.preventDefault()"
                 ondrop="dropEnColumna(event,'cocina')">
                <div class="orden-col-header col-header-cocina">
                    <i class="fas fa-fire-burner"></i>
                    <span>COCINA</span>
                    <span class="orden-col-badge" id="badge-cocina">0</span>
                </div>
                <div class="orden-col-lista" id="lista-cocina">
                    <div class="orden-drop-hint" id="hint-cocina">
                        <i class="fas fa-arrow-down"></i> Suelta aquí alimentos
                    </div>
                </div>
            </div>

        </div>

        <!-- AVISO COORDINACIÓN -->
        <div class="modal-coordinacion" id="aviso-coordinacion" style="display:none;">
            <i class="fas fa-link"></i>
            Orden mixta — Barra avisará a Cocina cuando las bebidas estén listas y viceversa
        </div>

        <!-- BOTONES -->
        <div style="display:flex;gap:10px;margin-top:16px;">
            <button class="btn btn-secundario" style="flex:1;" onclick="cerrarModal()">CANCELAR</button>
            <button class="btn btn-principal" style="flex:2;" onclick="enviarPedido()">
                <i class="fas fa-paper-plane"></i> ENVIAR ORDEN
            </button>
        </div>
    </div>
</div>

<!-- NOTIFICACIÓN FLOTANTE EN POS (cuando barra o cocina avisan) -->
<div id="notif-pos" class="notif-pos" style="display:none;">
    <div class="notif-pos-inner">
        <div class="notif-pos-icon" id="notif-pos-icon"><i class="fas fa-bell"></i></div>
        <div class="notif-pos-texto">
            <strong id="notif-pos-titulo">Mesa lista</strong>
            <span id="notif-pos-desc"></span>
        </div>
        <button class="notif-pos-close" onclick="cerrarNotifPos()"><i class="fas fa-times"></i></button>
    </div>
</div>

    <div id="modal-opciones" class="modal-overlay">
        <div class="modal-caja" style="text-align: left;">
            <h2 id="titulo-opciones">OPCIONES</h2>
            <div id="contenedor-opciones"></div>
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button class="btn btn-secundario" style="flex: 1;" onclick="cerrarModalOpciones()">CANCELAR</button>
                <button class="btn btn-principal" style="flex: 1;" onclick="confirmarOpciones()">AGREGAR A
                    COMANDA</button>
            </div>
        </div>
    </div>
    <!-- Modal ticket: migrado a cobro.html -->

    <!-- MODAL DE RESERVA — CALENDARIO COMPLETO -->
    <div id="modal-reserva" class="modal-overlay">
        <div class="modal-caja reserva-modal-caja">

            <!-- Encabezado -->
            <div class="reserva-header">
                <div class="reserva-header-icon"><i class="fas fa-calendar-plus"></i></div>
                <div>
                    <div class="reserva-header-titulo" id="reserva-mesa-titulo">RESERVAR MESA</div>
                    <div class="reserva-header-sub">Selecciona fecha, hora y datos del cliente</div>
                </div>
                <button class="reserva-close-btn" onclick="cerrarModalReserva()"><i class="fas fa-times"></i></button>
            </div>

            <input type="hidden" id="reserva-mesa-id">

            <!-- Reserva existente -->
            <div id="reserva-actual-info" class="reserva-actual-banner" style="display:none;"></div>

            <div class="reserva-body">

                <!-- COLUMNA IZQUIERDA: Calendario -->
                <div class="reserva-col-cal">

                    <!-- Navegación mes -->
                    <div class="cal-nav">
                        <button class="cal-nav-btn" onclick="calCambiarMes(-1)"><i class="fas fa-chevron-left"></i></button>
                        <span class="cal-mes-label" id="cal-mes-label"></span>
                        <button class="cal-nav-btn" onclick="calCambiarMes(1)"><i class="fas fa-chevron-right"></i></button>
                    </div>

                    <!-- Días de la semana -->
                    <div class="cal-semana">
                        <span>Dom</span><span>Lun</span><span>Mar</span>
                        <span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span>
                    </div>

                    <!-- Grid días -->
                    <div class="cal-grid" id="cal-grid"></div>

                    <!-- Selector de hora -->
                    <div class="cal-hora-section">
                        <div class="cal-hora-label"><i class="fas fa-clock"></i> Hora de la reserva</div>
                        <div class="cal-hora-controles">
                            <div class="cal-hora-picker">
                                <button class="cal-hora-btn" onclick="calCambiarHora('h', 1)"><i class="fas fa-chevron-up"></i></button>
                                <span class="cal-hora-valor" id="cal-hora">12</span>
                                <button class="cal-hora-btn" onclick="calCambiarHora('h', -1)"><i class="fas fa-chevron-down"></i></button>
                            </div>
                            <span class="cal-hora-sep">:</span>
                            <div class="cal-hora-picker">
                                <button class="cal-hora-btn" onclick="calCambiarHora('m', 1)"><i class="fas fa-chevron-up"></i></button>
                                <span class="cal-hora-valor" id="cal-min">00</span>
                                <button class="cal-hora-btn" onclick="calCambiarHora('m', -1)"><i class="fas fa-chevron-down"></i></button>
                            </div>
                            <div class="cal-ampm-picker">
                                <button class="cal-ampm-btn" id="cal-am" onclick="calSetAmPm('AM')">AM</button>
                                <button class="cal-ampm-btn" id="cal-pm" onclick="calSetAmPm('PM')">PM</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Info cliente + resumen -->
                <div class="reserva-col-info">

                    <!-- Resumen selección -->
                    <div class="reserva-resumen" id="reserva-resumen">
                        <div class="reserva-resumen-item" id="rres-fecha">
                            <i class="fas fa-calendar-day"></i>
                            <span id="rres-fecha-txt">Selecciona una fecha</span>
                        </div>
                        <div class="reserva-resumen-item" id="rres-hora">
                            <i class="fas fa-clock"></i>
                            <span id="rres-hora-txt">12:00 PM</span>
                        </div>
                    </div>

                    <!-- Datos del cliente -->
                    <div class="reserva-form">
                        <label class="reserva-label">Nombre del cliente</label>
                        <input type="text" id="reserva-nombre" class="reserva-input"
                               placeholder="Ej: García, Juan..." maxlength="40">

                        <label class="reserva-label">Número de personas</label>
                        <div class="reserva-personas-row">
                            <button class="reserva-pax-btn" onclick="calCambiarPax(-1)"><i class="fas fa-minus"></i></button>
                            <span class="reserva-pax-val" id="reserva-pax">2</span>
                            <button class="reserva-pax-btn" onclick="calCambiarPax(1)"><i class="fas fa-plus"></i></button>
                            <span class="reserva-pax-label">personas</span>
                        </div>

                        <label class="reserva-label">Motivo / Evento (opcional)</label>
                        <select id="reserva-evento" class="reserva-input" style="margin-bottom:10px;">
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

                        <label class="reserva-label">Nota adicional (opcional)</label>
                        <textarea id="reserva-nota" class="reserva-input reserva-textarea"
                                  placeholder="Decoración, preferencias, alergias..." rows="2"></textarea>
                    </div>

                    <!-- Notificación de aviso -->
                    <div class="reserva-notif-config">
                        <div class="reserva-label" style="margin-bottom:8px;">
                            <i class="fas fa-bell"></i> Avisar antes de la reserva
                        </div>
                        <div class="reserva-notif-opciones">
                            <button class="reserva-notif-btn activo" data-min="15" onclick="calSelNotif(this,15)">15 min</button>
                            <button class="reserva-notif-btn" data-min="30" onclick="calSelNotif(this,30)">30 min</button>
                            <button class="reserva-notif-btn" data-min="60" onclick="calSelNotif(this,60)">1 hora</button>
                            <button class="reserva-notif-btn" data-min="0" onclick="calSelNotif(this,0)">No avisar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="reserva-footer">
                <button class="btn btn-secundario" onclick="cerrarModalReserva()">CANCELAR</button>
                <button class="btn btn-principal" onclick="guardarReserva()">
                    <i class="fas fa-calendar-check"></i> CONFIRMAR RESERVA
                </button>
            </div>
        </div>
    </div>


    <!-- MODAL: LLEGÓ EL CLIENTE (mesa reservada) -->
    <div id="modal-llegada-cliente" class="modal-overlay">
        <div class="modal-caja" style="max-width:420px; text-align:center; padding:32px 28px;">
            <div style="font-size:44px; margin-bottom:16px;">🪑</div>
            <h2 style="font-size:18px; font-weight:800; margin-bottom:8px;">Mesa <span id="llegada-mesa-num">?</span> — Reservada</h2>
            <p style="font-size:14px; color:var(--text-muted,#6B7F65); margin-bottom:6px;">
                <strong id="llegada-cliente-nombre">—</strong>
            </p>
            <p style="font-size:13px; color:var(--text-muted,#6B7F65); margin-bottom:24px;" id="llegada-info">—</p>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <button class="btn btn-principal" style="width:100%; font-size:15px; padding:14px;"
                        onclick="confirmarLlegadaCliente()">
                    <i class="fas fa-check-circle"></i> ✅ El cliente llegó — Abrir mesa
                </button>
                <button class="btn btn-secundario" style="width:100%; font-size:13px;"
                        onclick="document.getElementById('modal-llegada-cliente').classList.remove('activo')">
                    <i class="fas fa-clock"></i> Aún no llega
                </button>
                <button class="btn" style="width:100%; font-size:13px; background:rgba(160,50,10,.1); color:#A0320A;"
                        onclick="cancelarReservaMesa()">
                    <i class="fas fa-calendar-xmark"></i> Cancelar reserva
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: NUEVA RESERVA GENERAL (desde botón RESERVAS en mesas) -->
    <div id="modal-reserva-general" class="modal-overlay">
        <div class="modal-caja" style="max-width:900px; padding:0; overflow:hidden; border-radius:16px;">
            <div style="padding:20px 24px 16px; border-bottom:1px solid var(--border-color,#D4E0CF); display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <h2 style="font-size:18px; font-weight:800;"><i class="fas fa-calendar-plus" style="color:#5A2090; margin-right:8px;"></i>Nueva Reserva</h2>
                    <p style="font-size:13px; color:var(--text-muted,#6B7F65); margin-top:3px;">Elige la mesa, fecha, hora y datos del cliente</p>
                </div>
                <button onclick="document.getElementById('modal-reserva-general').classList.remove('activo')"
                        style="background:var(--bg-app,#F0F4EE); border:1px solid var(--border-color,#D4E0CF); border-radius:8px; padding:7px 10px; cursor:pointer; font-size:14px; color:var(--text-muted,#6B7F65);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0; max-height:80vh; overflow-y:auto;">

                <!-- Columna izquierda: Mesas + calendario -->
                <div style="padding:20px; border-right:1px solid var(--border-color,#D4E0CF);">
                    <p style="font-size:11px; font-weight:700; color:var(--text-muted,#6B7F65); letter-spacing:.8px; text-transform:uppercase; margin-bottom:12px;">
                        <i class="fas fa-table"></i> Seleccionar Mesa
                    </p>
                    <div id="grv-mesas-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:20px;"></div>
                    <input type="hidden" id="grv-mesa-id">

                    <p style="font-size:11px; font-weight:700; color:var(--text-muted,#6B7F65); letter-spacing:.8px; text-transform:uppercase; margin-bottom:10px;">
                        <i class="fas fa-calendar"></i> Fecha de la Reserva
                    </p>
                    <!-- Mini calendario reutiliza funciones del modal principal -->
                    <div style="background:var(--bg-app,#F0F4EE); border-radius:12px; border:1px solid var(--border-color,#D4E0CF); overflow:hidden;">
                        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px;">
                            <button class="cal-nav-btn" onclick="grvCalMes(-1)"><i class="fas fa-chevron-left"></i></button>
                            <span style="font-size:13px; font-weight:700;" id="grv-cal-mes-label">—</span>
                            <button class="cal-nav-btn" onclick="grvCalMes(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(7,1fr); padding:0 10px 2px;">
                            <span style="text-align:center; font-size:9px; font-weight:700; color:var(--text-muted,#6B7F65);">Do</span>
                            <span style="text-align:center; font-size:9px; font-weight:700; color:var(--text-muted,#6B7F65);">Lu</span>
                            <span style="text-align:center; font-size:9px; font-weight:700; color:var(--text-muted,#6B7F65);">Ma</span>
                            <span style="text-align:center; font-size:9px; font-weight:700; color:var(--text-muted,#6B7F65);">Mi</span>
                            <span style="text-align:center; font-size:9px; font-weight:700; color:var(--text-muted,#6B7F65);">Ju</span>
                            <span style="text-align:center; font-size:9px; font-weight:700; color:var(--text-muted,#6B7F65);">Vi</span>
                            <span style="text-align:center; font-size:9px; font-weight:700; color:var(--text-muted,#6B7F65);">Sa</span>
                        </div>
                        <div class="cal-grid" id="grv-cal-grid" style="padding:4px 10px 12px; gap:1px;"></div>
                    </div>
                </div>

                <!-- Columna derecha: Datos -->
                <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                    <!-- Resumen selección -->
                    <div style="background:var(--bg-app,#F0F4EE); border:1px solid var(--border-color,#D4E0CF); border-radius:10px; padding:12px 14px; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-info-circle" style="color:var(--accent,#4A7A3D); font-size:16px; flex-shrink:0;"></i>
                        <div>
                            <div id="grv-res-mesa" style="font-size:13px; font-weight:700; color:var(--text-main,#1C2B18);">Selecciona una mesa y una fecha</div>
                            <div id="grv-res-fecha" style="font-size:12px; color:var(--text-muted,#6B7F65); margin-top:2px;"></div>
                        </div>
                    </div>

                    <!-- Hora -->
                    <div>
                        <label style="font-size:11px; font-weight:700; color:var(--text-muted,#6B7F65); text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:8px;">Hora de llegada</label>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:2px;">
                                <button class="cal-hora-btn" onclick="grvHora('h',1)"><i class="fas fa-chevron-up"></i></button>
                                <span style="font-size:22px; font-weight:800; min-width:36px; text-align:center; font-family:monospace;" id="grv-hora">12</span>
                                <button class="cal-hora-btn" onclick="grvHora('h',-1)"><i class="fas fa-chevron-down"></i></button>
                            </div>
                            <span style="font-size:22px; font-weight:800; color:var(--text-muted,#6B7F65);">:</span>
                            <div style="display:flex; flex-direction:column; align-items:center; gap:2px;">
                                <button class="cal-hora-btn" onclick="grvHora('m',1)"><i class="fas fa-chevron-up"></i></button>
                                <span style="font-size:22px; font-weight:800; min-width:36px; text-align:center; font-family:monospace;" id="grv-min">00</span>
                                <button class="cal-hora-btn" onclick="grvHora('m',-1)"><i class="fas fa-chevron-down"></i></button>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <button class="cal-ampm-btn" id="grv-am" onclick="grvAmPm('AM')">AM</button>
                                <button class="cal-ampm-btn activo" id="grv-pm" onclick="grvAmPm('PM')">PM</button>
                            </div>
                        </div>
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label style="font-size:11px; font-weight:700; color:var(--text-muted,#6B7F65); text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:6px;">Nombre del cliente</label>
                        <input type="text" id="grv-nombre" class="reserva-input" maxlength="80" placeholder="Ej: García, María">
                    </div>

                    <!-- Personas -->
                    <div>
                        <label style="font-size:11px; font-weight:700; color:var(--text-muted,#6B7F65); text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:8px;">Número de personas</label>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <button class="reserva-pax-btn" onclick="grvPax(-1)"><i class="fas fa-minus"></i></button>
                            <span style="font-size:20px; font-weight:800; min-width:32px; text-align:center;" id="grv-pax">2</span>
                            <button class="reserva-pax-btn" onclick="grvPax(1)"><i class="fas fa-plus"></i></button>
                            <span style="font-size:12px; color:var(--text-muted,#6B7F65);">personas</span>
                        </div>
                    </div>

                    <!-- Evento -->
                    <div>
                        <label style="font-size:11px; font-weight:700; color:var(--text-muted,#6B7F65); text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:6px;">Motivo / Evento (opcional)</label>
                        <select id="grv-evento" class="reserva-input">
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
                    <div>
                        <label style="font-size:11px; font-weight:700; color:var(--text-muted,#6B7F65); text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:6px;">Nota adicional (opcional)</label>
                        <textarea id="grv-nota" class="reserva-input reserva-textarea" rows="2" placeholder="Decoración, preferencias, alergias..."></textarea>
                    </div>

                    <div id="grv-error" style="display:none; background:rgba(160,50,10,.08); color:#A0320A; border:1px solid rgba(160,50,10,.2); padding:9px 13px; border-radius:8px; font-size:12px;"></div>

                    <button class="btn btn-principal" style="width:100%; padding:13px; font-size:15px;" onclick="grvGuardarReserva()">
                        <i class="fas fa-calendar-check"></i> Confirmar Reserva
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- NOTIFICACIÓN DE RESERVA PRÓXIMA -->
    <div id="notif-reserva" class="notif-reserva" style="display:none;">
        <div class="notif-reserva-inner">
            <div class="notif-reserva-icono"><i class="fas fa-calendar-check"></i></div>
            <div class="notif-reserva-texto">
                <strong id="notif-reserva-titulo">Reserva próxima</strong>
                <span id="notif-reserva-desc"></span>
            </div>
            <button class="notif-reserva-close" onclick="cerrarNotifReserva()"><i class="fas fa-times"></i></button>
        </div>
    </div>


    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- ESTILOS: Resumen de mesa + estados de consumo              -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <style>

    /* ── Mini-lista de consumo en tarjeta de mesa ──────────────── */
    .mesa-consumo-lista {
        margin-top: 6px;
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    .mesa-consumo-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11.5px;
        color: var(--text-muted, #6B7F65);
        padding: 1px 0;
    }
    .mesa-consumo-qty {
        font-weight: 700;
        color: var(--accent, #4A7A3D);
        min-width: 18px;
    }
    .mesa-consumo-nom {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .mesa-consumo-sub {
        font-size: 11px;
        font-weight: 700;
    }
    .estado-listo    { color: #2E7040; }
    .estado-preparando { color: #1A5A8A; }
    .estado-pendiente  { color: #999; }
    .mesa-consumo-mas {
        font-size: 10.5px;
        color: var(--text-muted);
        font-style: italic;
        margin-top: 2px;
    }
    .btn-mesa-accion.btn-agregar-mas {
        background: rgba(74,122,61,.12);
        color: var(--accent, #4A7A3D);
        border-radius: 6px;
        border: 1px solid rgba(74,122,61,.25);
        padding: 5px 9px;
        font-size: 12px;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-mesa-accion.btn-agregar-mas:hover {
        background: rgba(74,122,61,.22);
    }

    /* ── Modal de resumen de mesa ──────────────────────────────── */
    .modal-resumen-caja {
        background: var(--bg-card, #fff);
        border-radius: 16px;
        width: 480px;
        max-width: 96vw;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 24px 64px rgba(0,0,0,.22);
        animation: resumenIn .2s ease;
        display: flex;
        flex-direction: column;
    }
    @keyframes resumenIn {
        from { transform: translateY(-18px) scale(.97); opacity: 0; }
        to   { transform: none; opacity: 1; }
    }

    /* Header del resumen */
    .resumen-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px 16px;
        border-bottom: 1px solid var(--border-color, #D4E0CF);
        position: sticky; top: 0;
        background: var(--bg-card, #fff);
        border-radius: 16px 16px 0 0;
        z-index: 1;
    }
    .resumen-mesa-num {
        font-size: 20px;
        font-weight: 800;
        color: var(--accent, #4A7A3D);
        letter-spacing: .5px;
    }
    .resumen-tiempo {
        font-size: 12px;
        color: var(--text-muted, #6B7F65);
        margin-left: 8px;
    }
    .resumen-btn-cerrar {
        background: var(--bg-app, #F0F4EE);
        border: none;
        border-radius: 8px;
        padding: 8px 10px;
        cursor: pointer;
        color: var(--text-muted);
        font-size: 14px;
        transition: background .15s;
    }
    .resumen-btn-cerrar:hover { background: var(--border-color, #D4E0CF); }

    /* Aviso en cobro */
    .resumen-cobro-aviso {
        display: flex;
        align-items: center;
        gap: 14px;
        background: rgba(107,47,170,.07);
        border-left: 4px solid #6B2FAA;
        border-radius: 0;
        padding: 14px 24px;
        font-size: 13px;
        color: #6B2FAA;
    }
    .resumen-cobro-aviso i { font-size: 22px; flex-shrink: 0; }
    .resumen-cobro-aviso strong { display: block; font-size: 14px; margin-bottom: 2px; }
    .resumen-cobro-aviso p { margin: 0; opacity: .8; font-size: 12px; }

    /* Cuerpo con las secciones */
    .resumen-cuerpo {
        padding: 16px 24px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .resumen-vacio {
        text-align: center;
        padding: 40px 0;
        color: var(--text-muted);
    }
    .resumen-vacio i { font-size: 32px; margin-bottom: 10px; display: block; }
    .resumen-vacio p { font-size: 14px; }

    /* Secciones por estado */
    .resumen-seccion {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid transparent;
    }
    .sec-listo   { border-color: rgba(46,112,64,.2);  background: rgba(46,112,64,.04); }
    .sec-prep    { border-color: rgba(26,90,138,.2);  background: rgba(26,90,138,.04); }
    .sec-pend    { border-color: rgba(153,153,153,.2);background: rgba(153,153,153,.04); }

    .resumen-sec-titulo {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .7px;
        padding: 7px 14px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .sec-listo  .resumen-sec-titulo { color: #2E7040; background: rgba(46,112,64,.06); }
    .sec-prep   .resumen-sec-titulo { color: #1A5A8A; background: rgba(26,90,138,.06); }
    .sec-pend   .resumen-sec-titulo { color: #777;    background: rgba(153,153,153,.06); }

    .resumen-sec-badge {
        background: rgba(0,0,0,.08);
        border-radius: 20px;
        padding: 1px 7px;
        font-size: 10px;
    }

    /* Filas de ítems */
    .resumen-item-fila {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-bottom: 1px solid rgba(0,0,0,.04);
        font-size: 13px;
    }
    .resumen-item-fila:last-child { border-bottom: none; }
    .resumen-item-icon { font-size: 14px; flex-shrink: 0; }
    .resumen-item-qty  { font-weight: 700; color: var(--accent, #4A7A3D); min-width: 22px; }
    .resumen-item-nom  { flex: 1; color: var(--text-main, #1C2B18); }
    .resumen-item-nota { color: var(--text-muted, #6B7F65); font-size: 11.5px; font-style: italic; }
    .resumen-item-precio {
        font-weight: 700;
        color: var(--text-main, #1C2B18);
        min-width: 60px;
        text-align: right;
    }

    /* Total */
    .resumen-total-fila {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 24px;
        background: var(--bg-app, #F0F4EE);
        border-top: 2px solid var(--border-color, #D4E0CF);
        font-weight: 700;
        font-size: 15px;
    }
    .resumen-total-monto {
        font-size: 22px;
        font-weight: 800;
        color: var(--accent, #4A7A3D);
    }

    /* Botones de acción */
    .resumen-acciones {
        padding: 14px 24px 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .resumen-btn-agregar {
        width: 100%;
        padding: 12px;
        background: rgba(74,122,61,.08);
        border: 1.5px solid rgba(74,122,61,.3);
        border-radius: 10px;
        color: var(--accent, #4A7A3D);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background .15s;
    }
    .resumen-btn-agregar:hover { background: rgba(74,122,61,.16); }

    .resumen-btn-cobrar {
        width: 100%;
        padding: 13px;
        background: var(--accent, #4A7A3D);
        border: none;
        border-radius: 10px;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background .15s;
        box-shadow: 0 4px 12px rgba(74,122,61,.3);
    }
    .resumen-btn-cobrar:hover { background: #2E5226; }

    .resumen-btn-secundario {
        width: 100%;
        padding: 12px;
        background: transparent;
        border: 1.5px solid var(--border-color, #D4E0CF);
        border-radius: 10px;
        color: var(--text-muted, #6B7F65);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background .15s;
    }
    .resumen-btn-secundario:hover { background: var(--bg-app, #F0F4EE); }

    /* ── Tabs pedidos / historial ──────────────────────────────── */
    .resumen-tabs {
        display: flex;
        border-bottom: 2px solid var(--border-color, #D4E0CF);
        padding: 0 24px;
        gap: 4px;
        background: var(--bg-card, #fff);
        flex-shrink: 0;
    }
    .resumen-tab {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px;
        border: none;
        background: transparent;
        color: var(--text-muted, #6B7F65);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: color .15s, border-color .15s;
        font-family: inherit;
        white-space: nowrap;
    }
    .resumen-tab:hover { color: var(--accent, #4A7A3D); }
    .resumen-tab.activo {
        color: var(--accent, #4A7A3D);
        border-bottom-color: var(--accent, #4A7A3D);
    }
    .resumen-tab-badge {
        background: var(--accent, #4A7A3D);
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
    }

    /* ── Historial entregado ───────────────────────────────────── */
    .historial-cuerpo { padding: 12px 24px; display: flex; flex-direction: column; gap: 8px; }
    .hist-entrada {
        border: 1px solid var(--border-color, #D4E0CF);
        border-radius: 8px;
        padding: 10px 14px;
        background: var(--bg-app, #F0F4EE);
        animation: resumenIn .15s ease;
    }
    .hist-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
    }
    .hist-area {
        font-size: 11px;
        font-weight: 700;
        color: var(--accent, #4A7A3D);
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .hist-hora {
        font-size: 11px;
        color: var(--text-muted, #6B7F65);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .hist-items {
        font-size: 12.5px;
        color: var(--text-main, #1C2B18);
        line-height: 1.6;
    }

    </style>


    <!-- ════════════════════════════════════════════════════════════
         PANTALLA: BIENVENIDA ADMINISTRADOR — diseño premium
    ════════════════════════════════════════════════════════════ -->
    <div id="pantalla-admin-bienvenida" class="pantalla">

        <!-- ── Topbar ──────────────────────────────────────────── -->
        <div class="adm-topbar">
            <div class="adm-topbar-left">
                <div class="adm-logo-leaf">🌿</div>
                <div class="adm-logo-text">
                    <span class="adm-logo-sub">PANEL DE ADMINISTRADOR</span>
                </div>
            </div>
            <div class="adm-topbar-right">
                <button class="adm-btn-tema" onclick="toggleTema()" title="Cambiar tema">
                    <i class="fas fa-moon icono-tema"></i>
                </button>
                <div class="adm-user-chip">
                    <div class="adm-user-avatar" id="adm-avatar">A</div>
                    <div class="adm-user-info">
                        <span class="adm-user-label">Administrador</span>
                        <strong id="admin-header-nombre">Admin</strong>
                    </div>
                </div>
                <button class="adm-btn-salir" onclick="cerrarSesion()">
                    <i class="fas fa-sign-out-alt"></i> SALIR
                </button>
            </div>
        </div>

        <!-- ── Área scrollable ──────────────────────────────────── -->
        <div class="adm-scroll-area">

            <!-- ── HERO ─────────────────────────────────────────── -->
            <div class="adm-hero">
                <div class="adm-hero-particles">
                    <div class="adm-p adm-p1"></div>
                    <div class="adm-p adm-p2"></div>
                    <div class="adm-p adm-p3"></div>
                    <div class="adm-p adm-p4"></div>
                </div>
                <div class="adm-hero-left">
                    <p class="adm-hero-eyebrow">
                        <i class="fas fa-shield-halved"></i> Acceso total al sistema
                    </p>
                    <h1 class="adm-hero-title">
                        Bienvenido,<br>
                        <span class="admin-bienvenida-nombre adm-hero-name">Admin</span>
                    </h1>
                    <p class="adm-hero-sub">
                        Gestiona el equipo, supervisa las operaciones<br>
                        y controla cada área del restaurante.
                    </p>
                    <div class="adm-hero-chips">
                        <span class="adm-hchip"><i class="fas fa-users-gear"></i> Personal</span>
                        <span class="adm-hchip"><i class="fas fa-table-cells-large"></i> Mesas</span>
                        <span class="adm-hchip"><i class="fas fa-chart-line"></i> Reportes</span>
                    </div>
                </div>

            </div>

            <!-- ── ACCIONES RÁPIDAS ──────────────────────────────── -->
            <div class="adm-body">

                <div class="adm-sec-header">
                    <i class="fas fa-bolt adm-sec-icon"></i>
                    <h2>Acciones rápidas</h2>
                </div>

                <div class="adm-grid-acciones">

                    <!-- Gestionar usuarios — tarjeta destacada -->
                    <a href="admin.php" class="adm-card adm-card-destacada">
                        <div class="adm-card-bg-pattern"></div>
                        <div class="adm-card-ico adm-ico-white">
                            <i class="fas fa-users-gear"></i>
                        </div>
                        <div class="adm-card-text">
                            <h3>Gestionar Usuarios</h3>
                            <p>Crear · Editar · Activar o desactivar<br>los integrantes del equipo</p>
                        </div>
                        <div class="adm-card-chevron"><i class="fas fa-chevron-right"></i></div>
                    </a>

                    <!-- Ver mesas -->
                    <div class="adm-card" onclick="adminVerMesas()">
                        <div class="adm-card-ico adm-ico-teal">
                            <i class="fas fa-table-cells-large"></i>
                        </div>
                        <div class="adm-card-text">
                            <h3>Ver Estado del Restaurante</h3>
                            <p>Mesas · pedidos activos · tiempo real</p>
                        </div>
                        <div class="adm-card-chevron"><i class="fas fa-chevron-right"></i></div>
                    </div>

                    <!-- Módulo de Caja -->
                    <a href="cobro.php" class="adm-card">
                        <div class="adm-card-ico adm-ico-amber">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <div class="adm-card-text">
                            <h3>Módulo de Caja</h3>
                            <p>Cobros activos · historial · corte del día</p>
                        </div>
                        <div class="adm-card-chevron"><i class="fas fa-chevron-right"></i></div>
                    </a>

                    <!-- Cocina -->
                    <a href="cocina.php" class="adm-card">
                        <div class="adm-card-ico adm-ico-red">
                            <i class="fas fa-fire-burner"></i>
                        </div>
                        <div class="adm-card-text">
                            <h3>Pantalla Cocina</h3>
                            <p>Comandas activas · estado de preparación</p>
                        </div>
                        <div class="adm-card-chevron"><i class="fas fa-chevron-right"></i></div>
                    </a>

                    <!-- Barra -->
                    <a href="barra.php" class="adm-card">
                        <div class="adm-card-ico adm-ico-purple">
                            <i class="fas fa-martini-glass"></i>
                        </div>
                        <div class="adm-card-text">
                            <h3>Pantalla Barra</h3>
                            <p>Bebidas y cocteles en preparación</p>
                        </div>
                        <div class="adm-card-chevron"><i class="fas fa-chevron-right"></i></div>
                    </a>

                    <!-- Ver Reservas -->
                    <a href="gestion_reservas.php" class="adm-card">
                        <div class="adm-card-ico adm-ico-reservas">
                            <i class="fas fa-calendar-days"></i>
                        </div>
                        <div class="adm-card-text">
                            <h3>Gestionar Reservas</h3>
                            <p>Ver, crear y cancelar reservas<br>del restaurante</p>
                        </div>
                        <div class="adm-card-chevron"><i class="fas fa-chevron-right"></i></div>
                    </a>

                    <!-- Gestionar Menú -->
                    <a href="menu.php" class="adm-card">
                        <div class="adm-card-ico adm-ico-menu">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="adm-card-text">
                            <h3>Gestionar Menú</h3>
                            <p>Agregar, editar precios y desactivar<br>productos del menú</p>
                        </div>
                        <div class="adm-card-chevron"><i class="fas fa-chevron-right"></i></div>
                    </a>

                </div>

                <!-- ── ROLES ──────────────────────────────────────── -->
                <div class="adm-sec-header" style="margin-top:32px;">
                    <i class="fas fa-id-badge adm-sec-icon"></i>
                    <h2>Qué puede hacer cada rol</h2>
                </div>
                <p class="adm-sec-desc">
                    Al crear un usuario en
                    <a href="admin.php" class="adm-link">Gestionar Usuarios</a>,
                    elige su rol — define a qué pantalla tendrá acceso exclusivamente.
                </p>

                <div class="adm-roles">
                    <div class="adm-rol adm-rol-c1">
                        <div class="adm-rol-dot"></div>
                        <div class="adm-rol-emoji">🧑‍🍳</div>
                        <div class="adm-rol-body">
                            <strong>Mesero</strong>
                            <span>Ve las mesas · toma órdenes · envía a cobro cuando la mesa quiere pagar</span>
                        </div>
                        <code class="adm-rol-file">index.php</code>
                    </div>
                    <div class="adm-rol adm-rol-c2">
                        <div class="adm-rol-dot"></div>
                        <div class="adm-rol-emoji">💳</div>
                        <div class="adm-rol-body">
                            <strong>Cajero</strong>
                            <span>Ve las mesas enviadas a cobro · procesa el pago · libera la mesa</span>
                        </div>
                        <code class="adm-rol-file adm-rf-red">cobro.php</code>
                    </div>
                    <div class="adm-rol adm-rol-c3">
                        <div class="adm-rol-dot"></div>
                        <div class="adm-rol-emoji">🍳</div>
                        <div class="adm-rol-body">
                            <strong>Cocina</strong>
                            <span>Ve las comandas de su área · marca los platillos como listos</span>
                        </div>
                        <code class="adm-rol-file adm-rf-amber">cocina.php</code>
                    </div>
                    <div class="adm-rol adm-rol-c4">
                        <div class="adm-rol-dot"></div>
                        <div class="adm-rol-emoji">☕</div>
                        <div class="adm-rol-body">
                            <strong>Barra</strong>
                            <span>Ve las comandas de bebidas · marca las bebidas como listas</span>
                        </div>
                        <code class="adm-rol-file adm-rf-purple">barra.php</code>
                    </div>
                    <div class="adm-rol adm-rol-c5">
                        <div class="adm-rol-dot"></div>
                        <div class="adm-rol-emoji">⚙️</div>
                        <div class="adm-rol-body">
                            <strong>Admin</strong>
                            <span>Crea usuarios · asigna roles · ve el estado general · acceso a todo</span>
                        </div>
                        <code class="adm-rol-file adm-rf-green">admin.php</code>
                    </div>
                </div>

                <div style="height:48px;"></div>
            </div><!-- fin adm-body -->

        </div><!-- fin adm-scroll-area -->
    </div><!-- fin pantalla-admin-bienvenida -->

    <!-- ════════════════════════════════════════════════════════════
         ESTILOS DEL PANEL ADMIN — DISEÑO PREMIUM
    ════════════════════════════════════════════════════════════ -->
    <style>
    /* ═══ VARIABLES ══════════════════════════════════════════════ */
    #pantalla-admin-bienvenida {
        --adm-green1: #0F2014;
        --adm-green2: #1C3A20;
        --adm-green3: #2E5226;
        --adm-accent: #4A7A3D;
        --adm-light:  #7DB56A;
        display: flex; flex-direction: column;
        height: 100vh; overflow: hidden;
        background: var(--bg-app, #F0F4EE);
        font-family: 'Segoe UI', system-ui, sans-serif;
    }

    /* ═══ TOPBAR ═════════════════════════════════════════════════ */
    .adm-topbar {
        position: sticky; top: 0; z-index: 200;
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 24px; height: 58px; flex-shrink: 0;
        background: var(--adm-green1, #0F2014);
        border-bottom: 1px solid rgba(255,255,255,.05);
    }
    .adm-topbar-left  { display: flex; align-items: center; gap: 10px; }
    .adm-topbar-right { display: flex; align-items: center; gap: 10px; }
    .adm-logo-leaf { font-size: 20px; }
    .adm-logo-sub {
        font-size: 10px; font-weight: 700; letter-spacing: 2px;
        color: rgba(255,255,255,.4); text-transform: uppercase;
    }
    .adm-user-chip {
        display: flex; align-items: center; gap: 9px;
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 30px; padding: 4px 14px 4px 4px;
    }
    .adm-user-avatar {
        width: 30px; height: 30px; border-radius: 50%;
        background: linear-gradient(135deg, var(--adm-green3), var(--adm-accent));
        display: grid; place-items: center;
        font-size: 13px; font-weight: 800; color: #fff;
        border: 2px solid rgba(255,255,255,.15);
    }
    .adm-user-info { display: flex; flex-direction: column; }
    .adm-user-label { font-size: 9px; color: rgba(255,255,255,.4); letter-spacing: .8px; text-transform: uppercase; }
    .adm-user-info strong { font-size: 13px; color: #fff; font-weight: 700; line-height: 1.2; }

    .adm-btn-tema {
        background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08);
        border-radius: 8px; padding: 7px 10px; color: rgba(255,255,255,.55);
        cursor: pointer; font-size: 13px; transition: all .15s;
    }
    .adm-btn-tema:hover { background: rgba(255,255,255,.12); color: #fff; }
    .adm-btn-salir {
        display: flex; align-items: center; gap: 6px;
        background: rgba(160,50,10,.3); border: 1px solid rgba(160,50,10,.4);
        border-radius: 8px; padding: 7px 14px;
        color: #ff9f80; font-size: 12px; font-weight: 700; cursor: pointer;
        transition: all .15s;
    }
    .adm-btn-salir:hover { background: rgba(160,50,10,.55); }

    /* ═══ SCROLL ════════════════════════════════════════════════ */
    .adm-scroll-area { flex: 1; overflow-y: auto; overflow-x: hidden; }

    /* ═══ HERO ══════════════════════════════════════════════════ */
    .adm-hero {
        position: relative; overflow: hidden;
        background: linear-gradient(135deg,
            var(--adm-green1) 0%,
            var(--adm-green2) 45%,
            var(--adm-green3) 100%);
        padding: 44px 48px 44px;
        display: flex; align-items: center; justify-content: space-between;
        gap: 32px; flex-wrap: wrap; min-height: 220px;
    }

    /* Partículas decorativas */
    .adm-hero-particles { position: absolute; inset: 0; pointer-events: none; }
    .adm-p {
        position: absolute; border-radius: 50%;
        background: rgba(255,255,255,.04);
    }
    .adm-p1 { width: 320px; height: 320px; top: -120px; right: -80px; }
    .adm-p2 { width: 180px; height: 180px; bottom: -60px; right: 200px; background: rgba(255,255,255,.03); }
    .adm-p3 { width: 80px;  height: 80px;  top: 30px; right: 280px; background: rgba(255,255,255,.05); }
    .adm-p4 { width: 50px;  height: 50px;  bottom: 20px; left: 60%; background: rgba(255,255,255,.04); }

    .adm-hero-left { position: relative; z-index: 1; max-width: 500px; }
    .adm-hero-eyebrow {
        display: inline-flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 20px; padding: 5px 14px;
        font-size: 11.5px; color: rgba(255,255,255,.8); font-weight: 600;
        margin-bottom: 16px; letter-spacing: .3px;
    }
    .adm-hero-title {
        font-size: clamp(28px, 3.5vw, 40px);
        font-weight: 800; color: #fff; margin: 0 0 12px; line-height: 1.1;
    }
    .adm-hero-name {
        background: linear-gradient(90deg, #A8E6A0, #7DB56A);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .adm-hero-sub {
        font-size: 14px; color: rgba(255,255,255,.6);
        line-height: 1.7; margin: 0 0 20px;
    }
    .adm-hero-chips { display: flex; flex-wrap: wrap; gap: 8px; }
    .adm-hchip {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.14);
        color: rgba(255,255,255,.8); padding: 5px 13px;
        border-radius: 20px; font-size: 12px; font-weight: 600;
        backdrop-filter: blur(4px);
    }



    /* ═══ BODY ══════════════════════════════════════════════════ */
    .adm-body {
        max-width: 1080px; margin: 0 auto;
        padding: 32px 28px 0; width: 100%; box-sizing: border-box;
    }
    .adm-sec-header {
        display: flex; align-items: center; gap: 9px;
        margin-bottom: 16px;
    }
    .adm-sec-header h2 {
        font-size: 17px; font-weight: 800;
        color: var(--text-main, #1C2B18); margin: 0;
    }
    .adm-sec-icon { color: var(--adm-accent, #4A7A3D); font-size: 16px; }
    .adm-sec-desc {
        font-size: 13px; color: var(--text-muted, #6B7F65);
        margin: -8px 0 18px; line-height: 1.6;
    }
    .adm-link { color: var(--adm-accent, #4A7A3D); font-weight: 600; }

    /* ═══ TARJETAS ACCIONES ══════════════════════════════════════ */
    .adm-grid-acciones {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 14px; margin-bottom: 8px;
    }
    .adm-card {
        display: flex; align-items: center; gap: 14px;
        background: var(--bg-card, #fff);
        border: 1.5px solid var(--border-color, #E0EBD9);
        border-radius: 16px; padding: 18px 16px;
        text-decoration: none; color: inherit; cursor: pointer;
        transition: transform .18s, box-shadow .18s, border-color .18s;
        position: relative; overflow: hidden;
    }
    .adm-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(28,43,24,.12);
        border-color: var(--adm-accent, #4A7A3D);
    }
    .adm-card-destacada {
        background: linear-gradient(135deg, var(--adm-green3), var(--adm-accent));
        border-color: transparent; color: #fff !important;
        box-shadow: 0 6px 24px rgba(74,122,61,.4);
        grid-column: span 2;
    }
    @media (max-width: 560px) { .adm-card-destacada { grid-column: span 1; } }
    .adm-card-destacada:hover { box-shadow: 0 12px 36px rgba(74,122,61,.55); }
    .adm-card-destacada .adm-card-text h3 { color: #fff; font-size: 16px; }
    .adm-card-destacada .adm-card-text p  { color: rgba(255,255,255,.75); }
    .adm-card-destacada .adm-card-chevron { color: rgba(255,255,255,.6); }

    /* Patrón decorativo en tarjeta destacada */
    .adm-card-bg-pattern {
        position: absolute; right: -20px; top: -20px;
        width: 120px; height: 120px; border-radius: 50%;
        background: rgba(255,255,255,.07); pointer-events: none;
    }

    .adm-card-ico {
        width: 48px; height: 48px; border-radius: 13px; flex-shrink: 0;
        display: grid; place-items: center; font-size: 20px;
        position: relative; z-index: 1;
    }
    .adm-ico-white  { background: rgba(255,255,255,.18); color: #fff; }
    .adm-ico-teal   { background: rgba(20,150,150,.12);  color: #0E7A7A; }
    .adm-ico-amber  { background: rgba(176,125,18,.12);  color: #6B4800; }
    .adm-ico-red    { background: rgba(160,50,10,.10);   color: #7A1C00; }
    .adm-ico-purple { background: rgba(107,47,170,.10);  color: #4A1890; }

    .adm-card-text { flex: 1; position: relative; z-index: 1; }
    .adm-card-text h3 {
        font-size: 14px; font-weight: 700;
        color: var(--text-main, #1C2B18); margin: 0 0 3px;
    }
    .adm-card-text p {
        font-size: 12px; color: var(--text-muted, #6B7F65); margin: 0; line-height: 1.5;
    }
    .adm-card-chevron {
        color: var(--text-muted, #AAAAAA); font-size: 12px;
        position: relative; z-index: 1; transition: transform .18s; flex-shrink: 0;
    }
    .adm-card:hover .adm-card-chevron { transform: translateX(4px); }

    /* ═══ LISTA DE ROLES ════════════════════════════════════════ */
    .adm-roles { display: flex; flex-direction: column; gap: 8px; }
    .adm-rol {
        display: flex; align-items: center; gap: 14px;
        background: var(--bg-card, #fff);
        border-radius: 12px; padding: 14px 18px;
        box-shadow: 0 1px 3px rgba(28,43,24,.06);
        border-left: 3px solid transparent;
        transition: transform .15s, box-shadow .15s;
    }
    .adm-rol:hover { transform: translateX(5px); box-shadow: 0 4px 16px rgba(28,43,24,.1); }
    .adm-rol-c1 { border-left-color: #4A7A3D; }
    .adm-rol-c2 { border-left-color: #A0320A; }
    .adm-rol-c3 { border-left-color: #B07D12; }
    .adm-rol-c4 { border-left-color: #6B2FAA; }
    .adm-rol-c5 { border-left-color: #1C2B18; }

    .adm-rol-dot {
        width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
        background: currentColor;
    }
    .adm-rol-c1 .adm-rol-dot { background: #4A7A3D; }
    .adm-rol-c2 .adm-rol-dot { background: #A0320A; }
    .adm-rol-c3 .adm-rol-dot { background: #B07D12; }
    .adm-rol-c4 .adm-rol-dot { background: #6B2FAA; }
    .adm-rol-c5 .adm-rol-dot { background: #1C2B18; }

    .adm-rol-emoji { font-size: 22px; flex-shrink: 0; }
    .adm-rol-body { flex: 1; }
    .adm-rol-body strong {
        display: block; font-size: 14px; font-weight: 700;
        color: var(--text-main, #1C2B18); margin-bottom: 2px;
    }
    .adm-rol-body span { font-size: 12px; color: var(--text-muted, #6B7F65); line-height: 1.5; }
    .adm-rol-file {
        font-family: 'Courier New', monospace;
        font-size: 10px; font-weight: 700; letter-spacing: .4px;
        padding: 4px 9px; border-radius: 6px; white-space: nowrap;
        background: #2E5226; color: #fff;
    }
    .adm-rf-red    { background: #A0320A; }
    .adm-rf-amber  { background: #7A5000; }
    .adm-rf-purple { background: #5A2090; }
    .adm-rf-green  { background: #1C2B18; }

    /* ═══ BOTÓN VOLVER A ADMIN (en mesas) ═══════════════════════ */
    .btn-volver-admin {
        background: linear-gradient(135deg, #2E5226, #4A7A3D) !important;
        color: #fff !important; border: none !important;
        font-weight: 700 !important; padding: 7px 14px !important;
        border-radius: 8px !important;
        display: inline-flex !important; align-items: center; gap: 6px;
    }
    .btn-volver-admin:hover {
        background: linear-gradient(135deg, #1C2B18, #2E5226) !important;
        transform: translateY(-1px);
    }

    /* ═══ RESPONSIVE ════════════════════════════════════════════ */
    @media (max-width: 700px) {
        .adm-hero { padding: 28px 20px; flex-direction: column; }
        .adm-hero-right { display: none; }
        .adm-body { padding: 20px 14px 0; }
        .adm-grid-acciones { grid-template-columns: 1fr; }
        .adm-card-destacada { grid-column: span 1; }
        .adm-topbar { padding: 0 14px; }
        .adm-user-info .adm-user-label { display: none; }
    }
    </style>

    <script src="js/app.js"></script>
    <script>
    (function(){
        try{
            // ── Sanear localStorage de mesas ──────────────────────
            var raw = localStorage.getItem('pos_mesas_estado');
            if(raw){
                try{
                    var arr = JSON.parse(raw);
                    if(Array.isArray(arr) && arr.length > 0){
                        var vistos = {};
                        arr = arr
                            .map(function(m){ m.id = parseInt(m.id,10); return m; })
                            .filter(function(m){
                                if(!m.id || vistos[m.id]) return false;
                                vistos[m.id] = true; return true;
                            });
                        localStorage.setItem('pos_mesas_estado', JSON.stringify(arr));
                    } else {
                        localStorage.removeItem('pos_mesas_estado');
                    }
                }catch(e){ localStorage.removeItem('pos_mesas_estado'); }
            }

            // ── Volver desde cocina/barra/cobro ───────────────────
            if(localStorage.getItem('ir_a_mesas')==='1'){
                localStorage.removeItem('ir_a_mesas');

                // Verificar rol — admin vuelve a su panel
                try {
                    var ses = JSON.parse(sessionStorage.getItem('pos_usuario')||'{}');
                    if(ses && ses.rol) {
                        if(ses.rol==='cajero'){ window.location.replace('cobro.php'); return; }
                        if(ses.rol==='cocina'){ window.location.replace('cocina.php'); return; }
                        if(ses.rol==='barra') { window.location.replace('barra.php');  return; }
                    }
                } catch(e) {}

                // 1. Restaurar estado local INMEDIATAMENTE
                if(typeof _restaurarEstadoMesas === 'function') _restaurarEstadoMesas();

                // 2. Mostrar pantalla mesas
                document.querySelectorAll('.pantalla').forEach(function(p){ p.classList.remove('activa'); });
                var m = document.getElementById('mesas');
                if(m) m.classList.add('activa');

                // 3. Renderizar con estado local (mesas con pedidos, reservas, etc.)
                if(typeof renderizarMesas === 'function') renderizarMesas();

                // 4. Refrescar estados desde BD sin borrar pedidos locales
                if(typeof cargarMesasDesdeBD === 'function') cargarMesasDesdeBD();

                // 5. Auto-refrescar desde BD cada 10 segundos
                if(window.intervalTiempo) clearInterval(window.intervalTiempo);
                window.intervalTiempo = setInterval(function(){
                    var pantallaMesas = document.getElementById('mesas');
                    if(pantallaMesas && pantallaMesas.classList.contains('activa')){
                        if(typeof cargarMesasDesdeBD === 'function') cargarMesasDesdeBD();
                    }
                }, 10000);
            }
        }catch(e){ console.error('Restauracion error:', e); }

        // ── Admin viene de admin.php → mostrar panel admin sin re-login ─
        try {
            // Flag nuevo: admin_volver_panel → volver al panel de bienvenida admin
            if(localStorage.getItem('admin_volver_panel')==='1'){
                localStorage.removeItem('admin_volver_panel');
                var sesAdm = JSON.parse(sessionStorage.getItem('pos_usuario')||'{}');
                if(sesAdm && sesAdm.rol === 'admin') {
                    // Mostrar panel admin inline, no el login
                    document.querySelectorAll('.pantalla').forEach(function(p){ p.classList.remove('activa'); });
                    var panelAdm = document.getElementById('pantalla-admin-bienvenida');
                    if(panelAdm) panelAdm.classList.add('activa');
                }
            }
            // Flag viejo: admin_ir_mesas → ir a mesas con botón volver admin
            if(localStorage.getItem('admin_ir_mesas')==='1'){
                localStorage.removeItem('admin_ir_mesas');
                var sesAdmin = JSON.parse(sessionStorage.getItem('pos_usuario')||'{}');
                // Solo admin puede tener botón volver
                if(sesAdmin && sesAdmin.rol === 'admin') {
                    localStorage.setItem('admin_en_mesas','1');
                    document.querySelectorAll('.pantalla').forEach(function(p){ p.classList.remove('activa'); });
                    var mesasDiv = document.getElementById('mesas');
                    if(mesasDiv) mesasDiv.classList.add('activa');
                    var hdrN = document.getElementById('operador-nombre');
                    if(hdrN) hdrN.textContent = sesAdmin.nombre || 'Admin';
                    var btnVolver = document.getElementById('btn-volver-admin-mesas');
                    if(btnVolver) btnVolver.style.display = 'inline-flex';
                    if(typeof _restaurarEstadoMesas === 'function') _restaurarEstadoMesas();
                    if(typeof renderizarMesas === 'function') renderizarMesas();
                    if(typeof cargarMesasDesdeBD === 'function') cargarMesasDesdeBD();
                    if(typeof _iniciarAutoRefresh === 'function') _iniciarAutoRefresh();
                } else {
                    // Cualquier otro rol: limpiar flag y ocultar botón
                    localStorage.removeItem('admin_en_mesas');
                    var btnV = document.getElementById('btn-volver-admin-mesas');
                    if(btnV) btnV.style.display = 'none';
                }
            }
        } catch(e2) { console.error('admin nav error:', e2); }

    })();
    </script>
    <script>
    // ── Sincronizar avatar y nombre del admin al cargar ──────────
    (function(){
        try {
            var ses = JSON.parse(sessionStorage.getItem('pos_usuario')||'{}');
            if(ses && ses.nombre) {
                var av = document.getElementById('adm-avatar');
                if(av) av.textContent = ses.nombre[0].toUpperCase();
                var hn = document.getElementById('admin-header-nombre');
                if(hn) hn.textContent = ses.nombre;
                document.querySelectorAll('.admin-bienvenida-nombre').forEach(function(el){
                    el.textContent = ses.nombre;
                });
            }
            // Botón Volver a Admin — SOLO admin, nunca mesero ni otro rol
            var btn = document.getElementById('btn-volver-admin-mesas');
            if(btn) {
                var esAdmin = ses && ses.rol === 'admin';
                var tieneFlag = localStorage.getItem('admin_en_mesas') === '1';
                if(esAdmin && tieneFlag) {
                    btn.style.display = 'inline-flex';
                } else {
                    btn.style.display = 'none';
                    if(!esAdmin) localStorage.removeItem('admin_en_mesas');
                }
            }
        } catch(e){}
    })();
    </script>
</body>

</html>
