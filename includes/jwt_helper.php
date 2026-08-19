<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwt_secret.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Pravi token za datog korisnika, važi 2 sata
function generate_jwt(int $user_id, string $username): string
{
    $payload = [
        'user_id' => $user_id,
        'username' => $username,
        'iat' => time(),               // issued at — kad je token napravljen
        'exp' => time() + (2 * 60 * 60), // expires — ističe za 2 sata
    ];

    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

// Provjerava token; vraća payload niz ako je validan, ili null ako nije
function verify_jwt(string $token): ?array
{
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return null;
    }
}