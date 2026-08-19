<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST is allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password are required.']);
    exit;
}

$select_stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
$select_stmt->execute(['username' => $username]);
$user = $select_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid username or password.']);
    exit;
}

$token = generate_jwt($user['id'], $user['username']);

echo json_encode([
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
    ],
]);