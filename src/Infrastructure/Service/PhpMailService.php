<?php
namespace Infrastructure\Service;

use Domain\Service\MailerInterface;

class PhpMailService implements MailerInterface {
    public function sendCredentials(string $username, string $password, string $email, string $firstName, string $lastName): void {
        $asunto = "✅ Acceso a tu panel en Creawebes";
            $panelUrl = $_ENV['PANEL_URL'] ?? 'https://contenido.creawebes.com';
            $mensaje = '
        <html><head><meta charset="UTF-8"><style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f5f7; color: #333; line-height: 1.6; }
            .container { background-color: #ffffff; padding: 30px; margin: 20px auto; max-width: 600px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
            h2 { color: #3949ab; margin-top: 0; }
            .credenciales { background-color: #f0f2f5; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 16px; border-left: 4px solid #3949ab; }
            a.boton { display: inline-block; padding: 12px 25px; background-color: #3949ab; color: white !important; text-decoration: none; border-radius: 8px; margin-top: 20px; font-weight: bold; }
            .footer { font-size: 12px; color: #777; margin-top: 30px; text-align: center; border-top: 1px solid #e8e8e8; padding-top: 20px;}
        </style></head><body><div class="container">
            <h2>¡Tu cuenta en Creawebes ha sido creada!</h2><p>Hola <strong>' . htmlspecialchars($firstName) . ' ' . htmlspecialchars($lastName) . '</strong>,</p>
            <p>Ya puedes acceder a tu panel con las siguientes credenciales:</p>
            <div class="credenciales"><strong>👤 Usuario:</strong> ' . htmlspecialchars($username) . '<br><strong>🔒 Contraseña:</strong> ' . htmlspecialchars($password) . '</div>
            <a class="boton" href="' . htmlspecialchars($panelUrl) . '" target="_blank">Acceder al panel</a>
            <div class="footer">© ' . date('Y') . ' Creawebes.com</div>
        </div></body></html>';
        
        $mailFrom = $_ENV['MAIL_FROM'] ?? 'info@creawebes.com';
        $cabeceras  = "MIME-Version: 1.0\r\n";
        $cabeceras .= "Content-type: text/html; charset=UTF-8\r\n";
        $cabeceras .= "From: Creawebes <$mailFrom>\r\n";
        $cabeceras .= "Reply-To: $mailFrom\r\n";
        
        @mail($email, $asunto, $mensaje, $cabeceras);
    }
}
