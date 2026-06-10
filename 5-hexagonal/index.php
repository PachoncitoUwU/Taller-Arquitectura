<?php

// ── 1. DOMINIO: PUERTO (INTERFAZ) ──────────────────────
interface NotificationPort {
    public function send(string $to, string $message): void;
}

// ── 2. DOMINIO: CASO DE USO ────────────────────────────
class SendWelcomeUseCase {
    // El caso de uso solo depende del Puerto (Abstracción)
    public function __construct(private NotificationPort $notificationPort) {}

    public function execute(string $userEmail, string $userName): void {
        $message = "Hola {$userName}, bienvenido a nuestra plataforma tecnológica.";
        $this->notificationPort->send($userEmail, $message);
    }
}

// ── 3. INFRAESTRUCTURA: ADAPTADORES DRIVEN ─────────────

// Adaptador 1: Correo electrónico simulado
class EmailNotificationAdapter implements NotificationPort {
    public function send(string $to, string $message): void {
        echo "[ADAPTADOR EMAIL] Despachando correo SMTP hacia <$to> | Contenido: $message\n";
    }
}

// Adaptador 2: Mensajería de texto SMS simulada
class SmsNotificationAdapter implements NotificationPort {
    public function send(string $to, string $message): void {
        echo "[ADAPTADOR SMS] Inyectando SMS a pasarela de telefonía celular al número <$to> | Contenido: $message\n";
    }
}

// Adaptador 3: Nulo (Patrón Null Object, óptimo para entornos de testing aislados)
class NullNotificationAdapter implements NotificationPort {
    public function send(string $to, string $message): void {
        // Operación nula intencional. No genera salidas ni efectos secundarios.
    }
}

// ── 4. DEMOSTRACIÓN DE INTERCAMBIABILIDAD ──────────────

$emailAdapter = new EmailNotificationAdapter();
$smsAdapter   = new SmsNotificationAdapter();
$nullAdapter  = new NullNotificationAdapter();

echo "--- Inyección con Adaptador de Email ---\n";
$useCase = new SendWelcomeUseCase($emailAdapter);
$useCase->execute('miguel@correo.com', 'Miguel Angel');

echo "\n--- Inyección con Adaptador de SMS ---\n";
$useCase = new SendWelcomeUseCase($smsAdapter);
$useCase->execute('+573001234567', 'Miguel Angel');

echo "\n--- Inyección con Adaptador Null (Pruebas unitarias) ---\n";
$useCase = new SendWelcomeUseCase($nullAdapter);
$useCase->execute('test@test.com', 'Test User'); // No imprime nada, ejecución limpia.