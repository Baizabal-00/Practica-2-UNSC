<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Fallo de enlace con la base de datos: ' . $e->getMessage()
    ]);
    exit;
}

$action = $_GET['action'] ?? '';

// 1. Obtener requisiciones
if ($action === 'listar') {
    try {
        $stmt = $pdo->query("SELECT * FROM ordenes_compra ORDER BY id DESC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 2. Registrar orden
if ($action === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $precio = (float)($_POST['precio_unitario'] ?? 0);
        $subtotal = $cantidad * $precio;

        $sql = "INSERT INTO ordenes_compra 
                (proveedor, punto_contacto, correo, telefono, nombre_producto, sku, cantidad, precio_unitario, subtotal, fecha_entrega, prioridad_envio, direccion_entrega) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['proveedor'] ?? '',
            $_POST['punto_contacto'] ?? '',
            $_POST['correo'] ?? '',
            $_POST['telefono'] ?? '',
            $_POST['nombre_producto'] ?? '',
            $_POST['sku'] ?? '',
            $cantidad,
            $precio,
            $subtotal,
            $_POST['fecha_entrega'] ?? '',
            $_POST['prioridad_envio'] ?? '',
            $_POST['direccion_entrega'] ?? ''
        ]);

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 3. Eliminar orden
if ($action === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM ordenes_compra WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'invalid_action']);