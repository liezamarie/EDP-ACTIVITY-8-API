<?php
header("Content-Type: application/json");

$host = 'localhost';
$db = 'students';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $user, $pass, $options);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT s.id, s.password, s.name, s.year, c.course_name FROM students s INNER JOIN course c ON c.student_id = s.id");
    $users = $stmt->fetchAll();
    echo json_encode($users);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['password'], $input['name'], $input['year'], $input['course_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO students (password, name, year) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$input['password'], $input['name'], $input['year']]);

        $student_id = $pdo->lastInsertId();

        $sql = "INSERT INTO course (student_id, course_name) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $input['course_name']]);

        $pdo->commit();

        echo json_encode(['message' => 'User and course added successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add user and course: ' . $e->getMessage()]);
    }
}
?>
