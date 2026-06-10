<?php

class EventBus {
    private array $listeners = [];
    private array $onceListeners = [];

    // Registrar listeners persistentes
    public function subscribe(string $event, callable $handler): void {
        $this->listeners[$event][] = $handler;
    }

    // [NUEVO] Registrar listeners que se ejecutan una sola vez
    public function subscribeOnce(string $event, callable $handler): void {
        $this->onceListeners[$event][] = $handler;
    }

    // [NUEVO] Eliminar un listener específico
    public function unsubscribe(string $event, callable $handler): void {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $index => $listener) {
                if ($listener === $handler) {
                    unset($this->listeners[$event][$index]);
                }
            }
            // Reindexar array
            $this->listeners[$event] = array_values($this->listeners[$event]);
        }
        
        if (isset($this->onceListeners[$event])) {
            foreach ($this->onceListeners[$event] as $index => $listener) {
                if ($listener === $handler) {
                    unset($this->onceListeners[$event][$index]);
                }
            }
            $this->onceListeners[$event] = array_values($this->onceListeners[$event]);
        }
    }

    public function publish(string $event, array $payload): void {
        // 1. Ejecutar listeners normales
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $handler) {
                $handler($payload);
            }
        }

        // 2. Ejecutar listeners de un solo uso y limpiarlos inmediatamente
        if (isset($this->onceListeners[$event])) {
            $handlers = $this->onceListeners[$event];
            // Se limpia la lista antes de ejecutar por seguridad ante reentradas
            unset($this->onceListeners[$event]); 
            
            foreach ($handlers as $handler) {
                $handler($payload);
            }
        }
    }
}

// ── PRUEBA DEL EVENT BUS EXTENDIDO ──────────────────────

$bus = new EventBus();

// Definición de handlers como funciones con nombre o variables para poder desuscribirlos
$enviarEmail = function(array $user) {
    echo "📧 [EMAIL] Enviando correo de bienvenida a: {$user['email']}\n";
};

$crearPerfil = function(array $user) {
    echo "💾 [DATABASE] Creando registro de perfil para ID: {$user['id']}\n";
};

$analyticsOnce = function(array $user) {
    echo "📊 [ANALYTICS] Registrando primer evento único de usuario para: {$user['name']}\n";
};

// Suscripciones
$bus->subscribe('user.registered', $enviarEmail);
$bus->subscribe('user.registered', $crearPerfil);
$bus->subscribeOnce('user.registered', $analyticsOnce);

echo "--- Primera Publicación ---\n";
$bus->publish('user.registered', ['id' => 101, 'email' => 'miguel@example.com', 'name' => 'Miguel Angel']);

echo "\n--- Segunda Publicación (Analytics no debería salir, es Once) ---\n";
$bus->publish('user.registered', ['id' => 101, 'email' => 'miguel@example.com', 'name' => 'Miguel Angel']);

// Desuscripción de un listener persistente
$bus->unsubscribe('user.registered', $enviarEmail);

echo "\n--- Tercera Publicación (Email desuscripto, solo DB) ---\n";
$bus->publish('user.registered', ['id' => 101, 'email' => 'miguel@example.com', 'name' => 'Miguel Angel']);