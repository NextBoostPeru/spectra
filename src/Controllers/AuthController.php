<?php

namespace App\Controllers;

use App\Database;
use App\Support\Response;
use PDO;

class AuthController
{
    private PDO $pdo;

    public function __construct(Database $database)
    {
        $this->pdo = $database->pdo();
    }

    public function login(): void
    {
        $payload = $this->readJson();
        if ($payload === null) {
            Response::error('JSON inválido o ausente', 400);
            return;
        }

        $email = trim($payload['email'] ?? '');
        $password = $payload['password'] ?? '';

        if ($email === '' || $password === '') {
            Response::error('Correo y contraseña son requeridos', 422);
            return;
        }

        $stmt = $this->pdo->prepare('SELECT id, full_name, email, password_hash, status, platform_role FROM users WHERE email = :email LIMIT 1');
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user || empty($user['password_hash'])) {
            Response::error('Credenciales inválidas', 401);
            return;
        }

        if (!in_array($user['status'], ['active'], true)) {
            Response::error('La cuenta no está activa', 403);
            return;
        }

        if (!password_verify($password, $user['password_hash'])) {
            Response::error('Credenciales inválidas', 401);
            return;
        }

        $this->touchLastLogin($user['id']);

        $token = bin2hex(random_bytes(16));
        Response::json([
            'message' => 'Inicio de sesión exitoso',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'platform_role' => $user['platform_role'],
            ],
        ]);
    }

    private function touchLastLogin(string $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->bindValue(':id', $userId);
        $stmt->execute();
    }

    private function readJson(): ?array
    {
        $input = file_get_contents('php://input');
        $decoded = json_decode($input, true);
        return is_array($decoded) ? $decoded : null;
    }
}
