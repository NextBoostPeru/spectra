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
        $payload = $this->readPayload();
        if ($payload === null) {
            Response::error('JSON/Form inválido o ausente', 400);
            return;
        }

        $email = trim($payload['email'] ?? '');
        $password = $payload['password'] ?? '';

        if ($email === '' || $password === '') {
            Response::error('Correo y contraseña son requeridos', 422);
            return;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, full_name, email, password_hash, status, platform_role FROM users WHERE email = :email LIMIT 1'
        );
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

    public function registerAdmin(): void
    {
        $payload = $this->readPayload();
        if ($payload === null) {
            Response::error('JSON/Form inválido o ausente', 400);
            return;
        }

        $fullName = trim($payload['full_name'] ?? '');
        $email = trim($payload['email'] ?? '');
        $password = $payload['password'] ?? '';

        if ($fullName === '' || $email === '' || $password === '') {
            Response::error('Nombre, correo y contraseña son requeridos', 422);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Correo inválido', 422);
            return;
        }

        if (strlen($password) < 8) {
            Response::error('La contraseña debe tener al menos 8 caracteres', 422);
            return;
        }

        if ($this->emailExists($email)) {
            Response::error('Ya existe un usuario con ese correo', 409);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $id = $this->generateUuid();

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (id, full_name, email, password_hash, status, platform_role, created_at) ' .
            'VALUES (:id, :full_name, :email, :password_hash, :status, :platform_role, NOW())'
        );

        $stmt->execute([
            ':id' => $id,
            ':full_name' => $fullName,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':status' => 'active',
            ':platform_role' => 'super_admin',
        ]);

        Response::json([
            'message' => 'Administrador temporal creado',
            'user' => [
                'id' => $id,
                'full_name' => $fullName,
                'email' => $email,
                'platform_role' => 'super_admin',
            ],
        ], 201);
    }

    private function touchLastLogin(string $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->bindValue(':id', $userId);
        $stmt->execute();
    }

    private function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function readPayload(): ?array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $input = file_get_contents('php://input');

        if (stripos($contentType, 'application/json') === 0) {
            $decoded = json_decode($input, true);
            return is_array($decoded) ? $decoded : null;
        }

        if (stripos($contentType, 'application/x-www-form-urlencoded') === 0) {
            parse_str($input, $parsed);
            return $parsed ?: null;
        }

        if (!empty($_POST)) {
            return $_POST;
        }

        return null;
    }
}
