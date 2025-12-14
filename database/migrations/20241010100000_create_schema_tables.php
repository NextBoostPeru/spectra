<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use RuntimeException;

final class CreateSchemaTables extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;');
        $this->execute($this->loadSql(__DIR__ . '/../sql/00_schema_tables.sql'));
    }

    public function down(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'access_provisions', 'approval_policies', 'approval_requests', 'approval_rules', 'approval_steps',
            'assignments', 'attachments', 'attachment_links', 'audit_logs', 'bank_accounts', 'companies',
            'company_contacts', 'company_settings', 'company_users', 'contracts', 'contract_templates',
            'contract_versions', 'countries', 'currencies', 'deliverables', 'deliverable_reviews',
            'docusign_envelopes', 'exchange_rates', 'freelancers', 'freelancer_profiles', 'freelancer_skills',
            'invoices', 'invoice_lines', 'kb_articles', 'kyb_requests', 'kyc_requests', 'legal_approvals',
            'notifications', 'nps_responses', 'nps_surveys', 'onboarding_checklists', 'onboarding_items',
            'onboarding_item_templates', 'payment_cycles', 'payouts', 'payroll_runs', 'payslips', 'permissions',
            'projects', 'project_members', 'purchase_orders', 'requisitions', 'roles', 'role_permissions',
            'support_tickets', 'timesheets', 'translations', 'users', 'user_identities', 'user_roles',
            'user_sessions', 'vendors', 'wallets', 'wallet_transactions'
        ];

        foreach ($tables as $table) {
            $this->execute(sprintf('DROP TABLE IF EXISTS `%s`;', $table));
        }

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function loadSql(string $path): string
    {
        $sql = file_get_contents($path);

        if ($sql === false) {
            throw new RuntimeException(sprintf('No se pudo leer el archivo SQL: %s', $path));
        }

        return $sql;
    }
}
