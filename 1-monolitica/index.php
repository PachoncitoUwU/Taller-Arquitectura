<?php
// index.php — Todo en un solo archivo/proyecto

class BlogApp {
    private PDO $db;

    public function __construct() {
        // Ajusta las credenciales según tu entorno local
        $this->db = new PDO('mysql:host=localhost;dbname=blog', 'root', '');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // UI: renderiza HTML
    public function render($posts): void {
        echo "<h1>Mi Blog Monolítico</h1>";
        foreach ($posts as $post) {
            echo "<article>";
            echo "<h2>" . htmlspecialchars($post['title']) . "</h2>";
            echo "<p>" . htmlspecialchars($post['body']) . "</p>";
            echo "<small>Estado: " . htmlspecialchars($post['status']) . "</small>";
            echo "<form action='' method='POST' style='display:inline;'>";
            echo "  <input type='hidden' name='action' value='delete'>";
            echo "  <input type='hidden' name='id' value='{$post['id']}'>";
            echo "  <button type='submit'>Eliminar</button>";
            echo "</form>";
            echo "</article><hr>";
        }
        
        // Formulario simple de creación integrado en la UI
        echo '<h3>Crear Nuevo Post</h3>
        <form action="" method="POST">
            <input type="hidden" name="action" value="create">
            <label>Título:</label><br>
            <input type="text" name="title"><br>
            <label>Contenido:</label><br>
            <textarea name="body"></textarea><br><br>
            <button type="submit">Guardar Post</button>
        </form>';
    }

    // Lógica de negocio
    public function getPublishedPosts(): array {
        $posts = $this->fetchFromDb();
        return array_filter($posts, fn($p) => $p['status'] === 'published');
    }

    // [NUEVO] Método para insertar en la DB con validaciones
    public function createPost(string $title, string $body): bool {
        // Validaciones requeridas
        $title = trim($title);
        if (empty($title)) {
            throw new Exception("Error de Validación: El título no puede estar vacío.");
        }
        if (strlen($title) > 100) {
            throw new Exception("Error de Validación: El título debe tener menos de 100 caracteres.");
        }

        $stmt = $this->db->prepare('INSERT INTO posts (title, body, status) VALUES (:title, :body, :status)');
        return $stmt->execute([
            ':title' => $title,
            ':body' => $body,
            ':status' => 'published'
        ]);
    }

    // [NUEVO] Método para eliminar un post por ID
    public function deletePost(int $id): bool {
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // Acceso a datos
    private function fetchFromDb(): array {
        return $this->db->query('SELECT * FROM posts ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Lógica de enrutamiento y ejecución del Monolito
$app = new BlogApp();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $app->createPost($_POST['title'] ?? '', $_POST['body'] ?? '');
        } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $app->deletePost((int)$_POST['id']);
        }
        // Redirección para evitar reenvío de formulario
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
} catch (Exception $e) {
    echo "<div style='color:red; font-weight:bold;'>Error: " . $e->getMessage() . "</div><hr>";
}

$app->render($app->getPublishedPosts());