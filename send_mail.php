<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

// ========================================================================
// VARIABLES DEL SERVIDOR DE SALIDA (SMTP)
// ========================================================================
$smtp_host = "srv1635072.hstgr.cloud";
$smtp_port = 465; // (SSL) o 587 (TLS)
$smtp_user = "contacto@kai-studios.com";
$smtp_pass = "k#u5czH*jbjogNoA";
// ========================================================================

$destinatario = "arturo.enriquez@kai-studios.com";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Capturar y sanear datos
    $nombre  = filter_var(trim($_POST["nombre"]), FILTER_SANITIZE_STRING);
    $empresa = filter_var(trim($_POST["empresa"]), FILTER_SANITIZE_STRING);
    $correo  = filter_var(trim($_POST["correo"]), FILTER_SANITIZE_EMAIL);
    $dolor   = filter_var(trim($_POST["dolor"]), FILTER_SANITIZE_STRING);
    
    // Capturar el idioma asignado por el formulario (por defecto 'es')
    $lang    = isset($_POST["lang"]) ? filter_var(trim($_POST["lang"]), FILTER_SANITIZE_STRING) : 'es';

    // Textos adaptables según el idioma para las respuestas del servidor
    if ($lang === 'en') {
        $msg_invalid_fields = "Please fill out all fields correctly.";
        $msg_success        = "Message sent successfully.";
        $msg_server_error   = "Server failure: Check mail_errors.log for details.";
        $asunto             = "🔥 New Lead - KAI Studios Landing Page";
        
        $email_title        = "New prospect from the Landing Page";
        $label_name         = "Name";
        $label_company      = "Company";
        $label_email        = "Corporate Email";
        $label_pain         = "Their biggest operational headache";
        $email_footer       = "This email was sent from the KAI Studios contact form.";
    } else {
        $msg_invalid_fields = "Por favor, completa todos los campos correctamente.";
        $msg_success        = "Mensaje enviado.";
        $msg_server_error   = "Falla del servidor: Verifica el archivo mail_errors.log para más detalles.";
        $asunto             = "🔥 Nuevo Lead - Landing Page KAI Studios";
        
        $email_title        = "Nuevo prospecto desde la Landing Page";
        $label_name         = "Nombre";
        $label_company      = "Empresa";
        $label_email        = "Correo Corporativo";
        $label_pain         = "Su mayor dolor operativo";
        $email_footer       = "Este correo fue enviado desde el formulario de contacto de KAI Studios.";
    }

    if (empty($nombre) || empty($empresa) || empty($correo) || empty($dolor) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => $msg_invalid_fields]);
        exit;
    }

    $cuerpo_correo = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
            .container { padding: 20px; border: 1px solid #eee; border-radius: 5px; }
            h2 { color: #0284c7; }
            .label { font-weight: bold; color: #555; }
            .content { margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-left: 4px solid #0284c7; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>$email_title</h2>
            <p><span class='label'>$label_name:</span> $nombre</p>
            <p><span class='label'>$label_company:</span> $empresa</p>
            <p><span class='label'>$label_email:</span> <a href='mailto:$correo'>$correo</a></p>
            <p class='label'>$label_pain:</p>
            <div class='content'>
                " . nl2br(htmlspecialchars($dolor)) . "
            </div>
            <hr>
            <small>$email_footer</small>
        </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Web KAI Studios <no-reply@kai-studios.com>" . "\r\n"; 
    $headers .= "Reply-To: $correo" . "\r\n";

    // Intentar enviar el correo
    if (mail($destinatario, $asunto, $cuerpo_correo, $headers)) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => $msg_success]);
    } else {
        // Log de errores
        $fecha = date("Y-m-d H:i:s");
        $log_mensaje  = "[$fecha] ERROR DE ENVÍO DE CORREO\n";
        $log_mensaje .= "Prospecto: $nombre ($correo) [Idioma: $lang]\n";
        $log_mensaje .= "Detalle: La función mail() de PHP devolvió FALSE. Posibles causas: 1) Entorno local sin servidor de correos. 2) El hosting requiere configuración SMTP. 3) El correo 'From' no está autorizado.\n";
        $log_mensaje .= "--------------------------------------------------------\n";
        
        file_put_contents("mail_errors.log", $log_mensaje, FILE_APPEND);

        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $msg_server_error]);
    }

} else {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Acceso denegado."]);
}
?>