<?php

// ── 1. DEFINICIÓN DE COMANDOS Y OBJETOS DE TRANSFERENCIA
class CreateProductCommand {
    public function __construct(public readonly string $name, public readonly float $price) {}
}

class UpdateProductCommand {
    public function __construct(public readonly int $id, public readonly string $name, public readonly float $price) {}
}

// ── 2. IMPLEMENTACIÓN DEL COMMAND BUS GENÉRICO ─────────
class CommandBus {
    private array $handlers = [];

    // Registrar handlers asociando el nombre de clase del comando a una función ejecutable
    public function register(string $commandClass, callable $handler): void {
        $this->handlers[$commandClass] = $handler;
    }

    // Despacha el comando localizando dinámicamente su handler
    public function dispatch(object $command): void {
        $commandClass = get_class($command);

        if (!isset($this->handlers[$commandClass])) {
            throw new Exception("Excepción CQRS: No se encuentra ningún Handler registrado para el comando '$commandClass'.");
        }

        // Ejecutar el handler correspondiente pasando el objeto comando
        $handler = $this->handlers[$commandClass];
        $handler($command);
    }
}

// ── 3. SIMULACIÓN DE HANDLERS DE ESCRITURA (COMMAND SIDE) ──
class CreateProductHandler {
    public function __invoke(CreateProductCommand $cmd): void {
        echo "[WRITE DB] Registrando nuevo producto: '{$cmd->name}' con precio \${$cmd->price}\n";
        // Aquí se ejecutaría el SQL: INSERT INTO products ...
    }
}

class UpdateProductHandler {
    public function __invoke(UpdateProductCommand $cmd): void {
        echo "[WRITE DB] Actualizando producto ID: {$cmd->id} -> Nuevo Nombre: '{$cmd->name}' | Precio: \${$cmd->price}\n";
        // Aquí se ejecutaría el SQL: UPDATE products SET ... WHERE id = ...
    }
}

// ── 4. PRUEBA INTEGRAL DEL BUS DE COMANDOS ──────────────

try {
    $commandBus = new CommandBus();

    // Instanciar y registrar los Handlers en el Bus
    $createHandler = new CreateProductHandler();
    $updateHandler = new UpdateProductHandler();

    $commandBus->register(CreateProductCommand::class, $createHandler);
    $commandBus->register(UpdateProductCommand::class, $updateHandler);

    echo "--- Despachando CreateProductCommand ---\n";
    $cmd1 = new CreateProductCommand('Teclado Mecánico RGB', 89.99);
    $commandBus->dispatch($cmd1);

    echo "\n--- Despachando UpdateProductCommand ---\n";
    $cmd2 = new UpdateProductCommand(42, 'Teclado Mecánico Pro Wireless', 115.00);
    $commandBus->dispatch($cmd2);

    echo "\n--- Probando Comando No Registrado (Lanzamiento de error) ---\n";
    class DeleteProductCommand { public function __construct(public int $id) {} }
    
    $cmd3 = new DeleteProductCommand(42);
    $commandBus->dispatch($cmd3); // Provocará la excepción

} catch (Exception $e) {
    echo "⚠️ ERROR CAPTURADO: " . $e->getMessage() . "\n";
}