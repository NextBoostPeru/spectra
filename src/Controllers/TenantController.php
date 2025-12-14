<?php

namespace App\Controllers;

use App\Database;
use App\Support\Response;
use PDO;

class TenantController
{
    private PDO $pdo;

    public function __construct(Database $database)
    {
        $this->pdo = $database->pdo();
    }

    public function handle(array $segments, string $method): void
    {
        $tenantId = $segments[2] ?? null;
        $subResource = $segments[3] ?? null;
        $subId = $segments[4] ?? null;

        if ($tenantId === null) {
            if ($method === 'GET') {
                $this->index();
                return;
            }

            if ($method === 'POST') {
                $this->store();
                return;
            }

            Response::error('Método no soportado para tenants', 405);
            return;
        }

        switch ($subResource) {
            case 'status':
                if ($method !== 'POST') {
                    Response::error('Solo se permite POST para cambiar estado', 405);
                    return;
                }
                $this->updateStatus($tenantId);
                return;
            case 'contacts':
                $this->handleContacts($tenantId, $subId, $method);
                return;
            case 'impersonate':
                if ($method !== 'POST') {
                    Response::error('Solo se permite POST para impersonar', 405);
                    return;
                }
                $this->impersonate($tenantId);
                return;
            default:
                // fall through to main tenant actions
        }

        if ($method === 'GET') {
            $this->show($tenantId);
            return;
        }

        if (in_array($method, ['PUT', 'PATCH'], true)) {
            $this->update($tenantId);
            return;
        }

        Response::error('Método no soportado para el recurso de tenant', 405);
    }

    private function index(): void
    {
        $sql = "SELECT c.id, c.legal_name, c.trade_name, c.country_id, c.default_currency_id, c.timezone, c.status, cs.default_language, cs.tax_regime AS fee_rules, w.balance AS wallet_balance, w.currency_id AS wallet_currency\n                FROM companies c\n                LEFT JOIN company_settings cs ON cs.company_id = c.id\n                LEFT JOIN wallets w ON w.company_id = c.id\n                WHERE c.deleted_at IS NULL\n                ORDER BY c.created_at DESC";

        $stmt = $this->pdo->query($sql);
        $companies = $stmt->fetchAll();

        foreach ($companies as &$company) {
            $company['counters'] = $this->buildCounters($company['id']);
        }

        Response::json(['data' => $companies]);
    }

    private function show(string $tenantId): void
    {
        $sql = "SELECT c.id, c.legal_name, c.trade_name, c.country_id, c.default_currency_id, c.timezone, c.status, c.created_at, c.updated_at, cs.default_language, cs.tax_regime AS fee_rules, cs.billing_address, cs.invoice_series, cs.invoice_number_next, cs.tax_id, cs.tax_regime\n                FROM companies c\n                LEFT JOIN company_settings cs ON cs.company_id = c.id\n                WHERE c.id = :id LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $tenantId);
        $stmt->execute();
        $company = $stmt->fetch();

        if (!$company) {
            Response::error('Empresa no encontrada', 404);
            return;
        }

        $company['contacts'] = $this->getContacts($tenantId);
        $company['wallet'] = $this->getWallet($tenantId);
        $company['invoices'] = $this->getInvoices($tenantId);
        $company['payrolls'] = $this->getPayrolls($tenantId);
        $company['projects'] = $this->getProjects($tenantId);
        $company['contracts'] = $this->getContracts($tenantId);
        $company['payment_cycle'] = $this->getDefaultPaymentCycle($tenantId);
        $company['counters'] = $this->buildCounters($tenantId);

        Response::json(['data' => $company]);
    }

    private function store(): void
    {
        $payload = $this->readPayload();
        if ($payload === null) {
            Response::error('JSON/Form inválido o ausente', 400);
            return;
        }

        $legalName = trim($payload['legal_name'] ?? '');
        $tradeName = trim($payload['trade_name'] ?? '');
        $countryId = (int)($payload['country_id'] ?? 0);
        $currencyId = (int)($payload['default_currency_id'] ?? 0);
        $timezone = $payload['timezone'] ?? 'UTC';
        $language = $payload['default_language'] ?? 'es';
        $feeRules = $payload['fee_rules'] ?? null;

        if ($legalName === '' || $countryId === 0 || $currencyId === 0) {
            Response::error('legal_name, country_id y default_currency_id son requeridos', 422);
            return;
        }

        $tenantId = $this->generateUuid();

        $insertCompany = $this->pdo->prepare(
            'INSERT INTO companies (id, legal_name, trade_name, country_id, default_currency_id, timezone, status, created_at)\n             VALUES (:id, :legal_name, :trade_name, :country_id, :currency_id, :timezone, :status, NOW())'
        );

        $insertCompany->execute([
            ':id' => $tenantId,
            ':legal_name' => $legalName,
            ':trade_name' => $tradeName ?: null,
            ':country_id' => $countryId,
            ':currency_id' => $currencyId,
            ':timezone' => $timezone,
            ':status' => 'active',
        ]);

        $insertSettings = $this->pdo->prepare(
            'INSERT INTO company_settings (company_id, default_language, tax_regime, created_at) VALUES (:company_id, :lang, :fee_rules, NOW())'
        );
        $insertSettings->execute([
            ':company_id' => $tenantId,
            ':lang' => $language,
            ':fee_rules' => $feeRules,
        ]);

        if (!empty($payload['payment_cycle']) && is_array($payload['payment_cycle'])) {
            $this->upsertPaymentCycle($tenantId, $payload['payment_cycle']);
        }

        if (!empty($payload['contacts']) && is_array($payload['contacts'])) {
            foreach ($payload['contacts'] as $contact) {
                $this->createContact($tenantId, $contact);
            }
        }

        Response::json([
            'message' => 'Empresa creada',
            'data' => ['id' => $tenantId],
        ], 201);
    }

    private function update(string $tenantId): void
    {
        $payload = $this->readPayload();
        if ($payload === null) {
            Response::error('JSON/Form inválido o ausente', 400);
            return;
        }

        $fields = [];
        $values = [':id' => $tenantId];

        $updatable = ['legal_name', 'trade_name', 'country_id', 'default_currency_id', 'timezone'];
        foreach ($updatable as $field) {
            if (array_key_exists($field, $payload)) {
                $fields[] = "$field = :$field";
                $values[":" . $field] = $payload[$field];
            }
        }

        if ($fields) {
            $sql = 'UPDATE companies SET ' . implode(',', $fields) . ', updated_at = NOW() WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
        }

        $settingsFields = [];
        $settingsValues = [':company_id' => $tenantId];
        if (array_key_exists('default_language', $payload)) {
            $settingsFields[] = 'default_language = :lang';
            $settingsValues[':lang'] = $payload['default_language'];
        }
        if (array_key_exists('fee_rules', $payload)) {
            $settingsFields[] = 'tax_regime = :fee';
            $settingsValues[':fee'] = $payload['fee_rules'];
        }
        if ($settingsFields) {
            $sql = 'UPDATE company_settings SET ' . implode(',', $settingsFields) . ', updated_at = NOW() WHERE company_id = :company_id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($settingsValues);
        }

        if (!empty($payload['payment_cycle']) && is_array($payload['payment_cycle'])) {
            $this->upsertPaymentCycle($tenantId, $payload['payment_cycle']);
        }

        Response::json(['message' => 'Empresa actualizada']);
    }

    private function updateStatus(string $tenantId): void
    {
        $payload = $this->readPayload();
        $status = $payload['status'] ?? null;
        $reason = trim($payload['reason'] ?? '');

        if (!in_array($status, ['active', 'suspended'], true)) {
            Response::error('Estado inválido, use active o suspended', 422);
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE companies SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':id' => $tenantId,
        ]);

        $this->logAudit($tenantId, 'company_status_change', [
            'new_status' => $status,
            'reason' => $reason,
        ]);

        Response::json(['message' => 'Estado actualizado']);
    }

    private function handleContacts(string $tenantId, ?string $contactId, string $method): void
    {
        if ($method === 'GET') {
            Response::json(['data' => $this->getContacts($tenantId)]);
            return;
        }

        if ($method === 'POST' && !$contactId) {
            $payload = $this->readPayload();
            if ($payload === null) {
                Response::error('Datos de contacto inválidos', 400);
                return;
            }
            $id = $this->createContact($tenantId, $payload);
            Response::json(['message' => 'Contacto creado', 'data' => ['id' => $id]], 201);
            return;
        }

        if (in_array($method, ['PUT', 'PATCH'], true) && $contactId) {
            $payload = $this->readPayload();
            if ($payload === null) {
                Response::error('Datos de contacto inválidos', 400);
                return;
            }
            $this->updateContact($tenantId, $contactId, $payload);
            Response::json(['message' => 'Contacto actualizado']);
            return;
        }

        Response::error('Operación no soportada para contactos', 405);
    }

    private function impersonate(string $tenantId): void
    {
        $token = bin2hex(random_bytes(12));
        $expiresAt = date('c', time() + 3600);

        $this->logAudit($tenantId, 'impersonation_token_issued', [
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        Response::json([
            'message' => 'Token de soporte emitido',
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);
    }

    private function getContacts(string $tenantId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, phone, type, is_primary, created_at FROM company_contacts WHERE company_id = :id ORDER BY created_at DESC');
        $stmt->bindValue(':id', $tenantId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getWallet(string $tenantId): array
    {
        $stmt = $this->pdo->prepare('SELECT balance, currency_id, updated_at FROM wallets WHERE company_id = :id LIMIT 1');
        $stmt->bindValue(':id', $tenantId);
        $stmt->execute();
        $wallet = $stmt->fetch();

        return $wallet ?: ['balance' => 0, 'currency_id' => null, 'updated_at' => null];
    }

    private function getInvoices(string $tenantId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, invoice_number, total, currency_id, status, issued_at, due_at FROM invoices WHERE company_id = :id ORDER BY created_at DESC LIMIT 5');
        $stmt->bindValue(':id', $tenantId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getPayrolls(string $tenantId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, period_start, period_end, status, total_amount, currency_id, created_at FROM payroll_runs WHERE company_id = :id ORDER BY created_at DESC LIMIT 5');
        $stmt->bindValue(':id', $tenantId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getProjects(string $tenantId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, status, currency_id, created_at FROM projects WHERE company_id = :id ORDER BY created_at DESC LIMIT 5');
        $stmt->bindValue(':id', $tenantId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getContracts(string $tenantId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, status, start_date, end_date, currency_id, created_at FROM contracts WHERE company_id = :id ORDER BY created_at DESC LIMIT 5');
        $stmt->bindValue(':id', $tenantId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getDefaultPaymentCycle(string $tenantId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, frequency, cutoff_day, currency_id, is_active FROM payment_cycles WHERE company_id = :id AND is_active = 1 ORDER BY created_at DESC LIMIT 1');
        $stmt->bindValue(':id', $tenantId);
        $stmt->execute();
        $cycle = $stmt->fetch();
        return $cycle ?: null;
    }

    private function upsertPaymentCycle(string $tenantId, array $payload): void
    {
        $existing = $this->getDefaultPaymentCycle($tenantId);
        $frequency = $payload['frequency'] ?? null;
        $cutoff = isset($payload['cutoff_day']) ? (int)$payload['cutoff_day'] : null;
        $currencyId = isset($payload['currency_id']) ? (int)$payload['currency_id'] : null;

        if (!$frequency || !$currencyId) {
            return;
        }

        if ($existing) {
            $stmt = $this->pdo->prepare('UPDATE payment_cycles SET frequency = :frequency, cutoff_day = :cutoff, currency_id = :currency_id WHERE id = :id');
            $stmt->execute([
                ':frequency' => $frequency,
                ':cutoff' => $cutoff,
                ':currency_id' => $currencyId,
                ':id' => $existing['id'],
            ]);
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO payment_cycles (id, company_id, frequency, cutoff_day, currency_id, is_active, created_at) VALUES (:id, :company_id, :frequency, :cutoff, :currency_id, 1, NOW())');
        $stmt->execute([
            ':id' => $this->generateUuid(),
            ':company_id' => $tenantId,
            ':frequency' => $frequency,
            ':cutoff' => $cutoff,
            ':currency_id' => $currencyId,
        ]);
    }

    private function createContact(string $tenantId, array $payload): string
    {
        $name = trim($payload['name'] ?? '');
        $email = trim($payload['email'] ?? '');
        $phone = trim($payload['phone'] ?? '');
        $type = $this->normalizeContactType($payload['type'] ?? 'primary');
        $isPrimary = (int)($payload['is_primary'] ?? 0);

        if ($name === '' || $email === '') {
            return '';
        }

        $id = $this->generateUuid();
        $stmt = $this->pdo->prepare('INSERT INTO company_contacts (id, company_id, name, email, phone, type, is_primary, created_at) VALUES (:id, :company_id, :name, :email, :phone, :type, :is_primary, NOW())');
        $stmt->execute([
            ':id' => $id,
            ':company_id' => $tenantId,
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone ?: null,
            ':type' => $type,
            ':is_primary' => $isPrimary,
        ]);

        return $id;
    }

    private function updateContact(string $tenantId, string $contactId, array $payload): void
    {
        $fields = [];
        $values = [
            ':company_id' => $tenantId,
            ':id' => $contactId,
        ];

        $allowed = ['name', 'email', 'phone', 'type', 'is_primary'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $payload)) {
                $placeholder = ':' . $field;
                $fields[] = "$field = $placeholder";
                $values[$placeholder] = $field === 'type' ? $this->normalizeContactType($payload[$field]) : $payload[$field];
            }
        }

        if (!$fields) {
            return;
        }

        $sql = 'UPDATE company_contacts SET ' . implode(',', $fields) . ' WHERE id = :id AND company_id = :company_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
    }

    private function buildCounters(string $tenantId): array
    {
        $counters = [];

        $tables = [
            'invoices' => 'company_id',
            'payroll_runs' => 'company_id',
            'projects' => 'company_id',
            'contracts' => 'company_id',
            'company_contacts' => 'company_id',
        ];

        foreach ($tables as $table => $column) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = :id");
            $stmt->bindValue(':id', $tenantId);
            $stmt->execute();
            $counters[$table] = (int)$stmt->fetchColumn();
        }

        return $counters;
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

    private function normalizeContactType(string $type): string
    {
        $type = strtolower($type);
        $allowed = ['primary', 'billing', 'legal', 'other'];
        if ($type === 'operaciones' || $type === 'operations') {
            return 'other';
        }
        return in_array($type, $allowed, true) ? $type : 'other';
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function logAudit(string $tenantId, string $action, array $metadata = []): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO audit_logs (id, company_id, actor_user_id, action, object_type, object_id, metadata, created_at) VALUES (:id, :company_id, :actor_user_id, :action, :object_type, :object_id, :metadata, NOW())');
        $stmt->execute([
            ':id' => $this->generateUuid(),
            ':company_id' => $tenantId,
            ':actor_user_id' => '00000000-0000-0000-0000-000000000000',
            ':action' => $action,
            ':object_type' => 'company',
            ':object_id' => $tenantId,
            ':metadata' => json_encode($metadata),
        ]);
    }
}
