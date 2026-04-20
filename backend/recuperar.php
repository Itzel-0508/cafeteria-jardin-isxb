<?php
ob_start();

if (!empty($_GET['accion']) || !empty($_POST['accion'])) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

    function salirJson(array $d): void {
        ob_clean();
        echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

    if ($accion === 'verificar_correo') {
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $correo = strtolower(trim($body['correo'] ?? ''));

        if (!$correo)
            salirJson(['ok' => false, 'error' => 'Escribe tu correo electrónico.']);
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL))
            salirJson(['ok' => false, 'error' => 'El formato del correo no es válido.']);

        require_once __DIR__ . '/conexion.php';

        if (!isset($dp) || $dp->connect_error)
            salirJson(['ok' => false, 'error' => 'No se pudo conectar a la base de datos.']);

        // Asegurar columnas
        foreach (['correo VARCHAR(120)', 'pin_visible VARCHAR(20)'] as $col) {
            $nom = explode(' ', $col)[0];
            $ck  = $dp->query("SHOW COLUMNS FROM usuario LIKE '$nom'");
            if ($ck && $ck->num_rows === 0)
                $dp->query("ALTER TABLE usuario ADD COLUMN $col NULL");
        }

        // Buscar usuario
        $stmt = $dp->prepare(
            "SELECT nombre, activo, rol, IFNULL(pin_visible,'') AS pin_visible
             FROM usuario WHERE LOWER(correo) = ? LIMIT 1"
        );
        if (!$stmt) salirJson(['ok' => false, 'error' => 'Error BD: ' . $dp->error]);
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row)
            salirJson(['ok' => false, 'error' => '❌ Ese correo no está registrado. Pide al administrador que lo agregue en tu perfil.']);
        if (!$row['activo'])
            salirJson(['ok' => false, 'error' => '⚠️ Tu cuenta está desactivada. Comunícate con el administrador.']);
        if ($row['pin_visible'] === '')
            salirJson(['ok' => false, 'error' => '⚠️ Tu perfil no tiene PIN guardado. Pide al administrador que edite tu usuario y guarde el PIN.']);

        // Cargar SMTP desde BD
        $dp->query("
            CREATE TABLE IF NOT EXISTS config_sistema (
                clave VARCHAR(80) NOT NULL PRIMARY KEY,
                valor TEXT NOT NULL,
                ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $conf = [];
        $res2 = $dp->query("SELECT clave, valor FROM config_sistema");
        if ($res2) while ($r = $res2->fetch_assoc()) $conf[$r['clave']] = $r['valor'];

        $smtp_user = $conf['smtp_user'] ?? '';
        $smtp_pass = $conf['smtp_pass'] ?? '';

        if (!$smtp_user || !$smtp_pass)
            salirJson(['ok' => false, 'error' => '⚙️ El correo de envío no está configurado. Ve al Panel Admin → Config. Correo.']);

        require_once __DIR__ . '/correo_config.php';

        $nombre   = $row['nombre'];
        $pin      = $row['pin_visible'];
        $rol      = $row['rol'] ?? 'usuario';

        $rolesLabel = [
            'admin'  => '👑 Administrador',
            'cajero' => '💰 Cajero',
            'mesero' => '🍽️ Mesero',
            'cocina' => '👨‍🍳 Cocina',
            'barra'  => '🍹 Barra',
        ];
        $rolesColor = [
            'admin'  => '#7B2D8B',
            'cajero' => '#1A6B3C',
            'mesero' => '#2E5226',
            'cocina' => '#8B4513',
            'barra'  => '#1A4A7A',
        ];
        $rolLabel = $rolesLabel[$rol] ?? ucfirst($rol);
        $rolColor = $rolesColor[$rol] ?? '#2E5226';
        $negocio  = $conf['negocio_nombre'] ?? $conf['smtp_from_name'] ?? 'Jardín POS';

        $html = '<!DOCTYPE html>
<html><body style="margin:0;background:#F0F4EE;font-family:Arial,sans-serif;">
<div style="max-width:500px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.12);">
  <div style="background:linear-gradient(135deg,#2E5226,#4A7A3D);padding:28px 24px;text-align:center;">
    <div style="font-size:40px;margin-bottom:8px;">🌿</div>
    <h2 style="color:#fff;margin:0;font-size:22px;font-weight:800;">' . htmlspecialchars($negocio) . '</h2>
    <p style="color:rgba(255,255,255,.75);font-size:13px;margin:6px 0 0;">Recuperación de contraseña</p>
  </div>
  <div style="padding:32px 28px;">
    <p style="font-size:16px;color:#1C2B18;margin:0 0 6px;">Hola, <strong>' . htmlspecialchars($nombre) . '</strong> 👋</p>
    <p style="color:#6B7F65;font-size:13px;margin:0 0 20px;">Recibimos una solicitud para recuperar tu PIN de acceso.</p>
    <div style="text-align:center;margin-bottom:20px;">
      <span style="display:inline-block;background:' . $rolColor . '18;color:' . $rolColor . ';border:1.5px solid ' . $rolColor . '44;border-radius:20px;padding:6px 18px;font-size:13px;font-weight:700;">' . $rolLabel . '</span>
    </div>
    <div style="background:#F0F4EE;border:2px solid #4A7A3D;border-radius:12px;padding:24px;text-align:center;margin-bottom:24px;">
      <p style="font-size:11px;font-weight:700;color:#6B7F65;text-transform:uppercase;letter-spacing:1.5px;margin:0 0 12px;">Tu PIN de acceso</p>
      <div style="font-size:48px;font-weight:900;color:#2E5226;letter-spacing:16px;font-family:monospace;">' . htmlspecialchars($pin) . '</div>
    </div>
    <div style="background:#F8FAF7;border-left:3px solid #4A7A3D;border-radius:0 8px 8px 0;padding:12px 14px;margin-bottom:20px;">
      <p style="font-size:12px;color:#2E5226;margin:0;font-weight:600;">¿Cómo usarlo?</p>
      <p style="font-size:12px;color:#6B7F65;margin:4px 0 0;line-height:1.6;">Abre el sistema, escribe tu nombre <strong>' . htmlspecialchars($nombre) . '</strong> y luego ingresa este PIN en el teclado numérico.</p>
    </div>
    <p style="color:#A0320A;font-size:12px;background:rgba(160,50,10,.06);padding:12px;border-radius:8px;margin:0;">⚠️ Por seguridad, pide al administrador que cambie tu PIN después de ingresar.</p>
    <hr style="border:none;border-top:1px solid #D4E0CF;margin:22px 0 14px;">
    <p style="color:#6B7F65;font-size:11px;text-align:center;margin:0;">Si no solicitaste esto, ignora este mensaje.</p>
  </div>
  <div style="background:#F0F4EE;padding:14px 24px;text-align:center;border-top:1px solid #D4E0CF;">
    <p style="font-size:10px;color:#9BAF94;margin:0;">' . htmlspecialchars($negocio) . ' · Generado automáticamente</p>
  </div>
</div></body></html>';

        $res = enviarCorreo($correo, '🔑 Tu PIN de acceso — ' . $negocio, $html);

        if ($res['ok']) {
            salirJson(['ok' => true, 'nombre' => $nombre, 'rol' => $rolLabel]);
        } else {
            $det = $res['error'] ?? '';
            if (stripos($det, 'auth') !== false || stripos($det, '535') !== false)
                $msg = '❌ Contraseña SMTP incorrecta. Usa una Contraseña de Aplicación de Google de 16 letras.';
            elseif (stripos($det, 'openssl') !== false || stripos($det, 'TLS') !== false)
                $msg = '❌ SSL no habilitado. En XAMPP abre php.ini y quita el ";" de: extension=openssl';
            elseif (stripos($det, 'connect') !== false || stripos($det, 'socket') !== false)
                $msg = '❌ No se pudo conectar a Gmail. Verifica tu internet.';
            else
                $msg = '❌ ' . $det;
            salirJson(['ok' => false, 'error' => $msg]);
        }
    }

    salirJson(['ok' => false, 'error' => "Acción desconocida: '$accion'"]);
}

ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar Contraseña — Jardín POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700;800&display=swap');
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,#0f1f0c,#1a3015 50%,#0d1a0a);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border-radius:20px;padding:40px;width:100%;max-width:440px;box-shadow:0 24px 60px rgba(0,0,0,.4);}
.logo{display:flex;align-items:center;gap:12px;margin-bottom:28px;justify-content:center;}
.logo-icon{width:48px;height:48px;background:linear-gradient(135deg,#2E5226,#4A7A3D);border-radius:14px;display:grid;place-items:center;font-size:24px;box-shadow:0 4px 12px rgba(46,82,38,.35);}
.logo-txt{font-size:20px;font-weight:800;color:#1C2B18;}
h1{font-size:22px;font-weight:800;color:#1C2B18;margin-bottom:8px;text-align:center;}
.sub{font-size:13px;color:#6B7F65;text-align:center;margin-bottom:28px;line-height:1.6;}
.paso{display:none;}.paso.activo{display:block;}
.form-group{margin-bottom:16px;}
.lbl{font-size:11px;font-weight:700;color:#6B7F65;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:7px;}
.inp-wrap{position:relative;}
.inp{width:100%;padding:12px 42px 12px 15px;border:1.5px solid #D4E0CF;border-radius:10px;font-size:14px;font-family:inherit;outline:none;transition:all .2s;color:#1C2B18;background:#F8FAF7;}
.inp:focus{border-color:#4A7A3D;background:#fff;box-shadow:0 0 0 3px rgba(74,122,61,.1);}
.inp.valid{border-color:#4A7A3D;}
.inp.invalid{border-color:#c0392b;}
.inp-ico{position:absolute;right:13px;top:50%;transform:translateY(-50%);font-size:14px;pointer-events:none;display:none;}
.inp-ico.show{display:block;}
.ico-ok{color:#4A7A3D;}
.ico-er{color:#c0392b;}
.btn{width:100%;padding:14px;border:none;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;transition:all .15s;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-p{background:linear-gradient(135deg,#2E5226,#4A7A3D);color:#fff;box-shadow:0 4px 14px rgba(46,82,38,.3);}
.btn-p:hover{filter:brightness(1.08);}
.btn-p:disabled{opacity:.5;cursor:not-allowed;box-shadow:none;}
.aviso{padding:13px 15px;border-radius:10px;font-size:13px;margin-bottom:18px;line-height:1.6;display:none;}
.ok-cls{background:rgba(46,112,64,.08);border:1px solid rgba(46,112,64,.25);color:#2E7040;}
.er-cls{background:rgba(160,50,10,.07);border:1px solid rgba(160,50,10,.22);color:#A0320A;}
.back{display:block;text-align:center;margin-top:18px;font-size:12px;color:#6B7F65;text-decoration:none;}
.back:hover{color:#4A7A3D;}
.ok-icon{width:76px;height:76px;background:rgba(46,112,64,.1);border:2px solid #4A7A3D;border-radius:50%;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;font-size:34px;color:#4A7A3D;}
.hint{font-size:11px;color:#6B7F65;margin-top:6px;line-height:1.5;}
.spin{width:20px;height:20px;border:2.5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:sp .7s linear infinite;flex-shrink:0;}
@keyframes sp{to{transform:rotate(360deg)}}
.rol-badge{display:inline-block;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;background:rgba(46,82,38,.1);color:#2E5226;border:1px solid rgba(46,82,38,.2);margin-bottom:12px;}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">🌿</div>
    <span class="logo-txt">Jardín POS</span>
  </div>

  <div class="paso activo" id="paso1">
    <h1>Recuperar contraseña</h1>
    <p class="sub">Ingresa el correo que tu administrador registró en tu perfil y te enviaremos tu PIN de acceso.</p>
    <div id="av1" class="aviso"></div>
    <div class="form-group">
      <label class="lbl" for="inp-correo">Correo electrónico</label>
      <div class="inp-wrap">
        <input type="email" id="inp-correo" class="inp" placeholder="ejemplo@gmail.com"
          autocomplete="email" oninput="validarCampo()" onkeydown="if(event.key==='Enter') enviar()">
        <span class="inp-ico ico-ok" id="ico-ok"><i class="fas fa-check-circle"></i></span>
        <span class="inp-ico ico-er" id="ico-er"><i class="fas fa-times-circle"></i></span>
      </div>
      <p class="hint"><i class="fas fa-info-circle" style="color:#4A7A3D;margin-right:3px;"></i>El correo que el administrador guardó en tu perfil de usuario.</p>
    </div>
    <button class="btn btn-p" id="btn-env" onclick="enviar()">
      <i class="fas fa-paper-plane"></i> Enviar PIN por correo
    </button>
    <a href="index.php" class="back"><i class="fas fa-arrow-left" style="margin-right:5px;"></i>Volver al inicio de sesión</a>
  </div>

  <div class="paso" id="paso2">
    <div class="ok-icon"><i class="fas fa-envelope-open-text"></i></div>
    <h1>¡PIN enviado! 🎉</h1>
    <div style="text-align:center;margin-bottom:16px;"><span class="rol-badge" id="res-rol"></span></div>
    <p class="sub">Hola, <strong id="res-nom">—</strong><br><br>Tu PIN fue enviado a tu correo registrado.<br>Si no lo ves en unos minutos, revisa la carpeta de <strong>Spam</strong>.</p>
    <a href="index.php">
      <button class="btn btn-p" style="margin-bottom:12px;">
        <i class="fas fa-sign-in-alt"></i> Ir a iniciar sesión
      </button>
    </a>
    <a href="recuperar.php" class="back"><i class="fas fa-redo" style="margin-right:5px;"></i>Reenviar correo</a>
  </div>
</div>

<script>
function validarCampo() {
  const val = document.getElementById('inp-correo').value.trim();
  const inp = document.getElementById('inp-correo');
  const ok  = document.getElementById('ico-ok');
  const er  = document.getElementById('ico-er');
  const esEmail = val.length > 4 && val.includes('@') && val.includes('.');
  inp.classList.remove('valid','invalid'); ok.classList.remove('show'); er.classList.remove('show');
  if (!val) return;
  if (esEmail) { inp.classList.add('valid'); ok.classList.add('show'); }
  else         { inp.classList.add('invalid'); er.classList.add('show'); }
}

async function enviar() {
  const correo = document.getElementById('inp-correo').value.trim().toLowerCase();
  const btn    = document.getElementById('btn-env');
  ocultarAviso();
  if (!correo) { mostrarAviso('Escribe tu correo electrónico.', false); document.getElementById('inp-correo').focus(); return; }
  if (!correo.includes('@') || !correo.includes('.')) { mostrarAviso('El formato del correo no es válido.', false); return; }
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Verificando...';
  try {
    const resp  = await fetch('recuperar.php?accion=verificar_correo', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ correo })
    });
    const texto = await resp.text();
    let data;
    try { data = JSON.parse(texto); }
    catch(e) {
      const preview = texto.slice(0,600).replace(/</g,'&lt;').replace(/>/g,'&gt;');
      mostrarAviso('❌ Error PHP:<br><pre style="font-size:10px;margin-top:6px;white-space:pre-wrap;background:rgba(0,0,0,.04);padding:8px;border-radius:6px;">' + preview + '</pre>', false);
      resetBtn(); return;
    }
    if (data.ok) {
      document.getElementById('res-nom').textContent = data.nombre;
      document.getElementById('res-rol').textContent = data.rol || '';
      document.querySelectorAll('.paso').forEach(p => p.classList.remove('activo'));
      document.getElementById('paso2').classList.add('activo');
    } else {
      mostrarAviso(data.error || 'Error desconocido.', false);
      resetBtn();
    }
  } catch(e) {
    mostrarAviso('Sin conexión con el servidor. Verifica que XAMPP esté activo.', false);
    resetBtn();
  }
}

function resetBtn() {
  const btn = document.getElementById('btn-env');
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar PIN por correo';
}
function mostrarAviso(msg, ok) {
  const el = document.getElementById('av1');
  el.className = 'aviso ' + (ok ? 'ok-cls' : 'er-cls');
  el.innerHTML = msg;
  el.style.display = 'block';
}
function ocultarAviso() { document.getElementById('av1').style.display = 'none'; }
document.getElementById('inp-correo').focus();
</script>
</body>
</html>
