<?php
// ================================================================
//  correo_config.php — Envío de correo vía SMTP
//  Las credenciales se leen de la tabla config_sistema (BD).
//  El administrador las configura desde el panel de Admin.
// ================================================================

function _cargarConfigSMTP(): void {
    if (defined('SMTP_USER')) return;
    if (!isset($GLOBALS['dp'])) require_once __DIR__ . '/conexion.php';
    global $dp;

    // ── CORRECCIÓN: crear la tabla si aún no existe ───────────
    $dp->query("
        CREATE TABLE IF NOT EXISTS config_sistema (
            clave   VARCHAR(80)   NOT NULL PRIMARY KEY,
            valor   TEXT          NOT NULL,
            ts      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conf = [];
    $res  = $dp->query("SELECT clave, valor FROM config_sistema");
    if ($res) while ($r = $res->fetch_assoc()) $conf[$r['clave']] = $r['valor'];

    define('SMTP_HOST',      $conf['smtp_host']      ?? 'smtp.gmail.com');
    define('SMTP_PORT',      (int)($conf['smtp_port'] ?? 587));
    define('SMTP_USER',      $conf['smtp_user']       ?? '');
    define('SMTP_PASS',      $conf['smtp_pass']       ?? '');
    define('SMTP_FROM',      $conf['smtp_user']       ?? '');
    define('SMTP_FROM_NAME', $conf['smtp_from_name']  ?? 'Jardín POS');
}

function enviarCorreo(string $destinatario, string $asunto, string $cuerpoHtml): array {
    _cargarConfigSMTP();
    if (!SMTP_USER || !SMTP_PASS) {
        return ['ok' => false, 'error' => 'El correo de envío no está configurado. Ve al Panel Admin → Configuración de Correo.'];
    }
    $vendorPaths = [__DIR__ . '/vendor/autoload.php', __DIR__ . '/../vendor/autoload.php'];
    foreach ($vendorPaths as $path) {
        if (file_exists($path)) { require_once $path; return _enviarConPHPMailer($destinatario, $asunto, $cuerpoHtml); }
    }
    return _enviarSMTPNativo($destinatario, $asunto, $cuerpoHtml);
}

function _enviarConPHPMailer(string $to, string $subject, string $html): array {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP(); $mail->Host = SMTP_HOST; $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER; $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT; $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME); $mail->addAddress($to);
        $mail->isHTML(true); $mail->Subject = $subject; $mail->Body = $html;
        $mail->AltBody = strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $html));
        $mail->send();
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) { return ['ok' => false, 'error' => 'PHPMailer: ' . $e->getMessage()]; }
}

function _enviarSMTPNativo(string $to, string $subject, string $html): array {
    try {
        $ctx  = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
        $sock = @stream_socket_client('tcp://' . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$sock) return ['ok' => false, 'error' => "No se pudo conectar ({$errno}): {$errstr}. Verifica que php_openssl esté activo en php.ini."];
        stream_set_timeout($sock, 15);
        _smtpLeer($sock);
        _smtpEscribir($sock, "EHLO localhost"); _smtpLeer($sock);
        _smtpEscribir($sock, "STARTTLS");
        $r = _smtpLeer($sock);
        if (strpos($r, '220') === false) { fclose($sock); return ['ok' => false, 'error' => 'STARTTLS rechazado: ' . trim($r)]; }
        if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($sock); return ['ok' => false, 'error' => 'No se pudo activar TLS. Habilita extension=openssl en php.ini de XAMPP.'];
        }
        _smtpEscribir($sock, "EHLO localhost"); _smtpLeer($sock);
        _smtpEscribir($sock, "AUTH LOGIN"); _smtpLeer($sock);
        _smtpEscribir($sock, base64_encode(SMTP_USER)); _smtpLeer($sock);
        _smtpEscribir($sock, base64_encode(SMTP_PASS));
        $auth = _smtpLeer($sock);
        if (strpos($auth, '235') === false) {
            fclose($sock); return ['ok' => false, 'error' => 'Autenticación fallida. Usa una Contraseña de Aplicación de Google (16 letras sin espacios), no tu contraseña normal de Gmail.'];
        }
        _smtpEscribir($sock, "MAIL FROM:<" . SMTP_FROM . ">"); _smtpLeer($sock);
        _smtpEscribir($sock, "RCPT TO:<{$to}>");
        $rcpt = _smtpLeer($sock);
        if (strpos($rcpt, '250') === false) { fclose($sock); return ['ok' => false, 'error' => "Destinatario rechazado: {$to}"]; }
        _smtpEscribir($sock, "DATA"); _smtpLeer($sock);
        $texto = strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $html));
        $msg  = "From: =?UTF-8?B?" . base64_encode(SMTP_FROM_NAME) . "?= <" . SMTP_FROM . ">\r\n";
        $msg .= "To: <{$to}>\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $msg .= "Date: " . date('r') . "\r\nMIME-Version: 1.0\r\n";
        $msg .= "Content-Type: multipart/alternative; boundary=\"_b_\"\r\n\r\n";
        $msg .= "--_b_\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$texto}\r\n\r\n";
        $msg .= "--_b_\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n\r\n--_b_--\r\n\r\n.";
        _smtpEscribir($sock, $msg);
        $resp = _smtpLeer($sock);
        _smtpEscribir($sock, "QUIT"); fclose($sock);
        if (strpos($resp, '250') !== false) return ['ok' => true, 'error' => ''];
        return ['ok' => false, 'error' => 'Servidor rechazó el mensaje: ' . trim($resp)];
    } catch (Throwable $e) { return ['ok' => false, 'error' => $e->getMessage()]; }
}

function _smtpEscribir($sock, string $cmd): void { fwrite($sock, $cmd . "\r\n"); }
function _smtpLeer($sock): string {
    $resp = '';
    while (!feof($sock)) {
        $line = fgets($sock, 515);
        if ($line === false) break;
        $resp .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') break;
    }
    return $resp;
}
