<?php

// ── 1. MODELO ──────────────────────────────────────────
class TaskModel {
    // Simulación de base de datos en memoria usando sesiones para mantener el estado del CRUD
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['tasks'])) {
            $_SESSION['tasks'] = [
                ['id' => 1, 'title' => 'Estudiar Arquitectura Hexagonal', 'done' => true],
                ['id' => 2, 'title' => 'Resolver el taller de MVC en PHP', 'done' => false],
            ];
        }
    }

    public function all(): array {
        return $_SESSION['tasks'];
    }

    public function save(string $title): void {
        $id = count($_SESSION['tasks']) > 0 ? max(array_column($_SESSION['tasks'], 'id')) + 1 : 1;
        $_SESSION['tasks'][] = [
            'id' => $id,
            'title' => $title,
            'done' => false
        ];
    }

    public function toggleStatus(int $id): void {
        foreach ($_SESSION['tasks'] as &$task) {
            if ($task['id'] === $id) {
                $task['done'] = !$task['done'];
                break;
            }
        }
    }

    public function delete(int $id): void {
        $_SESSION['tasks'] = array_filter($_SESSION['tasks'], fn($task) => $task['id'] !== $id);
    }
}

// ── 2. VISTA ───────────────────────────────────────────
class TaskView {
    public function renderList(array $tasks, string $errorMessage = ''): string {
        $html = '<h2>Mi Listado de Tareas (MVC)</h2>';
        
        if (!empty($errorMessage)) {
            $html .= "<p style='color:red; font-weight:bold;'>⚠️ $errorMessage</p>";
        }

        $html .= '<ul style="list-style:none; padding:0;">';
        foreach ($tasks as $task) {
            $statusIcon = $task['done'] ? '✓ <span style="color:green;">[Completada]</span>' : '✗ <span style="color:orange;">[Pendiente]</span>';
            $html .= "<li style='margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom:5px;'>";
            $html .= "<strong>{$statusIcon}</strong> - " . htmlspecialchars($task['title']);
            $html .= " | <a href='?action=toggle&id={$task['id']}'>Cambiar Estado</a>";
            $html .= " | <a href='?action=delete&id={$task['id']}' style='color:red;'>Eliminar</a>";
            $html .= "</li>";
        }
        $html .= '</ul>';
        return $html;
    }

    public function renderForm(): string {
        return '<h3>Crear Nueva Tarea</h3>
        <form action="?action=create" method="POST">
            <label for="title">Descripción de la Tarea:</label><br>
            <input type="text" id="title" name="title" style="width:250px;">
            <button type="submit">Agregar Tarea</button>
        </form>';
    }
}

// ── 3. CONTROLADOR ─────────────────────────────────────
class TaskController {
    private TaskModel $model;
    private TaskView $view;

    public function __construct() {
        $this->model = new TaskModel();
        $this->view = new TaskView();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';
        $errorMessage = '';

        // Enrutamiento interno del controlador y orquestación
        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            
            // Validación requerida en el controlador
            if (empty($title)) {
                $errorMessage = "El título de la tarea es requerido y no puede estar vacío.";
            } else {
                $this->model->save($title);
                header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
                exit;
            }
        }

        if ($action === 'toggle' && isset($_GET['id'])) {
            $this->model->toggleStatus((int)$_GET['id']);
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }

        if ($action === 'delete' && isset($_GET['id'])) {
            $this->model->delete((int)$_GET['id']);
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }

        // Renderización de la interfaz unificada
        $tasks = $this->model->all();
        echo $this->view->renderList($tasks, $errorMessage);
        echo $this->view->renderForm();
    }
}

// Inicialización de la aplicación Web MVC
// (new TaskController())->handleRequest();