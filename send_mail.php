<?php
// Evitar que advertencias de PHP contaminen la respuesta JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('America/Mexico_City');

$destinatario = "arturo.enriquez@kai-studios.com";

/**
 * Sanea cadenas de texto de forma compatible con PHP 8.1+ / 8.2+ / 8.3+
 */
function clean_input($value) {
    if (!is_string($value)) {
        return '';
    }
    $value = trim($value);
    $value = strip_tags($value);
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Detectar idioma del formulario ('es' por defecto)
    $lang_raw = isset($_POST["lang"]) ? clean_input($_POST["lang"]) : 'es';
    $lang     = ($lang_raw === 'en') ? 'en' : 'es';

    // Textos ejecutivos según el idioma seleccionado
    if ($lang === 'en') {
        $msg_invalid_fields = "Please complete all fields with a valid corporate email address.";
        $msg_success        = "Thank you. We have received your request and our team will contact you within 24 business hours.";
        $msg_server_error   = "We couldn't process your request right now. Please try again in a few minutes or email us directly.";
        $asunto_texto       = "🔥 New Enterprise Lead - KAI Studios";

        $email_title        = "New Executive Lead from KAI Studios Website";
        $label_name         = "Contact Name";
        $label_company      = "Company";
        $label_email        = "Corporate Email";
        $label_pain         = "Primary Process to Optimize";
        $label_lang         = "Source Language";
        $email_footer       = "Sent automatically from the KAI Studios contact form (kai-studios.com/en/).";
    } else {
        $msg_invalid_fields = "Por favor, completa todos los campos con un correo electrónico válido.";
        $msg_success        = "Gracias. Hemos recibido tu solicitud y nuestro equipo te contactará en menos de 24 horas hábiles.";
        $msg_server_error   = "No pudimos procesar tu solicitud en este momento. Por favor inténtalo más tarde o escríbenos directamente.";
        $asunto_texto       = "🔥 Nuevo Lead Empresarial - KAI Studios";

        $email_title        = "Nuevo Prospecto desde el Sitio Web de KAI Studios";
        $label_name         = "Nombre del Contacto";
        $label_company      = "Empresa";
        $label_email        = "Correo Corporativo";
        $label_pain         = "Proceso Principal a Optimizar";
        $label_lang         = "Idioma de Origen";
        $email_footer       = "Enviado automáticamente desde el formulario de contacto de KAI Studios (kai-studios.com).";
    }

    // Validación Honeypot anti-bots (si el campo oculto tiene texto, simulamos éxito y descartamos)
    if (!empty($_POST["website_hp"])) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => $msg_success]);
        exit;
    }

    // Capturar y sanear datos de forma segura
    $nombre  = isset($_POST["nombre"])  ? clean_input($_POST["nombre"])  : '';
    $empresa = isset($_POST["empresa"]) ? clean_input($_POST["empresa"]) : '';
    $correo  = isset($_POST["correo"])  ? filter_var(trim($_POST["correo"]), FILTER_SANITIZE_EMAIL) : '';
    $dolor   = isset($_POST["dolor"])   ? clean_input($_POST["dolor"])   : '';

    // Prevenir inyección de cabeceras en el correo
    $correo = str_replace(["\r", "\n", "%0a", "%0d"], '', $correo);

    if (empty($nombre) || empty($empresa) || empty($correo) || empty($dolor) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => $msg_invalid_fields]);
        exit;
    }

    // Escapar valores para la plantilla HTML del correo
    $nombre_html  = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $empresa_html = htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8');
    $correo_html  = htmlspecialchars($correo, ENT_QUOTES, 'UTF-8');
    $dolor_html   = nl2br(htmlspecialchars($dolor, ENT_QUOTES, 'UTF-8'));
    $lang_upper   = strtoupper($lang);
    $fecha_envio  = date("d/m/Y H:i:s");

    $cuerpo_correo = "
    <!DOCTYPE html>
    <html lang='$lang'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f4f4f5; color: #18181b; line-height: 1.6; margin: 0; padding: 24px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 32px; border: 1px solid #e4e4e7; border-radius: 12px; }
            .badge { display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #0891b2; background: #ecfeff; padding: 4px 10px; border-radius: 999px; margin-bottom: 12px; }
            h2 { color: #09090b; margin: 0 0 20px 0; font-size: 20px; }
            .row { margin-bottom: 12px; font-size: 14px; }
            .label { font-weight: 600; color: #52525b; }
            .content { margin: 16px 0 24px 0; padding: 16px; background: #f8fafc; border-left: 4px solid #06b6d4; border-radius: 4px; font-size: 14px; color: #1e293b; }
            hr { border: none; border-top: 1px solid #e4e4e7; margin: 24px 0 16px 0; }
            small { color: #71717a; font-size: 12px; display: block; }
        </style>
    </head>
    <body>
        <div class='container'>
            <span class='badge'>KAI Studios • Lead ($lang_upper)</span>
            <h2>$email_title</h2>
            <div class='row'><span class='label'>$label_name:</span> $nombre_html</div>
            <div class='row'><span class='label'>$label_company:</span> $empresa_html</div>
            <div class='row'><span class='label'>$label_email:</span> <a href='mailto:$correo_html' style='color:#0284c7;text-decoration:none;'>$correo_html</a></div>
            <div class='row'><span class='label'>$label_lang:</span> $lang_upper ($fecha_envio CST)</div>
            <div class='row' style='margin-top:16px;'><span class='label'>$label_pain:</span></div>
            <div class='content'>$dolor_html</div>
            <hr>
            <small>$email_footer</small>
        </div>
    </body>
    </html>
    ";

    // Codificar asunto en UTF-8 para evitar caracteres rotos
    $asunto_codificado = "=?UTF-8?B?" . base64_encode($asunto_texto . " (" . $empresa . ")") . "?=";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Web KAI Studios <no-reply@kai-studios.com>\r\n";
    $headers .= "Reply-To: $nombre_html <$correo>\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    // Intentar enviar el correo
    if (@mail($destinatario, $asunto_codificado, $cuerpo_correo, $headers)) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => $msg_success]);
    } else {
        // Registro interno de errores (sin exponer detalles sensibles al navegador)
        $fecha = date("Y-m-d H:i:s");
        $log_mensaje  = "[$fecha] ERROR DE ENVÍO DE CORREO\n";
        $log_mensaje .= "Prospecto: $nombre | Empresa: $empresa | Correo: $correo [Idioma: $lang]\n";
        $log_mensaje .= "Detalle: La función mail() de PHP devolvió FALSE.\n";
        $log_mensaje .= "--------------------------------------------------------\n";

        @file_put_contents(__DIR__ . "/mail_errors.log", $log_mensaje, FILE_APPEND | LOCK_EX);

        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $msg_server_error]);
    }

} else {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Acceso no permitido."]);
}
?>