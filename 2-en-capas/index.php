<?php

// ── 1. CAPA DE DOMINIO ─────────────────────────────────
class Product {
    public function __construct(
        public readonly ?int   $id,
        public readonly string $name,
        public readonly float  $price
    ) {}
}

// ── 2. CAPA DE INFRAESTRUCTURA ─────────────────────────
class ProductRepository {
    private PDO $db;

    public function __construct() {
        $this->db = new PDO('mysql:host=localhost;dbname=tienda', 'root', '');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function findAll(): array {
        $stmt = $this->db->query("SELECT * FROM products");
        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = new Product((int)$row['id'], $row['name'], (float)$row['price']);
        }
        return $products;
    }

    public function save(Product $product): void {
        $stmt = $this->db->prepare("INSERT INTO products (name, price) VALUES (:name, :price)");
        $stmt->execute([
            ':name' => $product->name,
            ':price' => $product->price
        ]);
    }
}

// Exception personalizada para control de flujo
class InvalidPriceException extends Exception {}

// ── 3. CAPA DE APLICACIÓN ──────────────────────────────
class CreateProductUseCase {
    public function __construct(private ProductRepository $repo) {}

    public function execute(string $name, float $price): Product {
        // Regla de negocio / Validación de aplicación
        if ($price <= 0) {
            throw new InvalidPriceException("El precio del producto debe ser mayor a 0.");
        }

        $product = new Product(null, $name, $price);
        $this->repo->save($product);
        return $product;
    }
}

// ── 4. CAPA DE PRESENTACIÓN ────────────────────────────
class ProductController {
    public function store(array $data): array {
        try {
            $repo = new ProductRepository();
            $useCase = new CreateProductUseCase($repo);

            $name = $data['name'] ?? '';
            $price = (float)($data['price'] ?? 0);

            $product = $useCase->execute($name, $price);

            return [
                'status' => 'success',
                'message' => 'Producto creado con éxito',
                'data' => ['name' => $product->name, 'price' => $product->price]
            ];
        } catch (InvalidPriceException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Error interno del servidor.'];
        }
    }
}

// Ejemplo de Uso:
// $controller = new ProductController();
// print_r($controller->store(['name' => 'Mouse Gamer', 'price' => 45.50]));