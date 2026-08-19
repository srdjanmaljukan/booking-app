<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/jwt_helper.php';

header('Content-Type: application/json');

// Izvlači token iz "Authorization: Bearer <token>" header-a i vraća
// podatke o ulogovanom korisniku, ili odmah prekida zahtjev sa 401 greškom
function authenticate_request(): array
{
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';

    if (!str_starts_with($auth_header, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing or invalid Authorization header.']);
        exit;
    }

    // "Bearer eyJhbGc..." -> uzimamo samo dio poslije "Bearer "
    $token = substr($auth_header, 7);
    $payload = verify_jwt($token);

    if ($payload === null) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token.']);
        exit;
    }

    return $payload;
}