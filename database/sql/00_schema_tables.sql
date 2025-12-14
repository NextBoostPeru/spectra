-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 14-12-2025 a las 15:32:23
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u560058480_spectrabderp`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `access_provisions`
--

CREATE TABLE `access_provisions` (
  `id` char(36) NOT NULL,
  `assignment_id` char(36) NOT NULL,
  `system_name` varchar(80) NOT NULL,
  `access_level` varchar(80) DEFAULT NULL,
  `status` enum('requested','provisioned','revoked') NOT NULL DEFAULT 'requested',
  `requested_by_company_user_id` char(36) DEFAULT NULL,
  `provisioned_by_company_user_id` char(36) DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  `provisioned_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `approval_policies`
--

CREATE TABLE `approval_policies` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `name` varchar(120) NOT NULL,
  `object_type` enum('contract','requisition','purchase_order','payroll_run','payout','invoice') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `approval_requests`
--

CREATE TABLE `approval_requests` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `object_type` enum('contract','requisition','purchase_order','payroll_run','payout','invoice') NOT NULL,
  `object_id` char(36) NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `created_by_company_user_id` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `approval_rules`
--

CREATE TABLE `approval_rules` (
  `id` char(36) NOT NULL,
  `policy_id` char(36) NOT NULL,
  `min_amount` decimal(12,2) DEFAULT NULL,
  `max_amount` decimal(12,2) DEFAULT NULL,
  `currency_id` int(10) UNSIGNED DEFAULT NULL,
  `condition_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`condition_json`)),
  `sequence_order` int(10) UNSIGNED NOT NULL,
  `required_role_id` char(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `approval_steps`
--

CREATE TABLE `approval_steps` (
  `id` char(36) NOT NULL,
  `approval_request_id` char(36) NOT NULL,
  `sequence_order` int(10) UNSIGNED NOT NULL,
  `required_role_id` char(36) NOT NULL,
  `assigned_to_company_user_id` char(36) DEFAULT NULL,
  `status` enum('pending','approved','rejected','skipped') NOT NULL DEFAULT 'pending',
  `acted_by_company_user_id` char(36) DEFAULT NULL,
  `acted_at` timestamp NULL DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `assignments`
--

CREATE TABLE `assignments` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `project_id` char(36) NOT NULL,
  `freelancer_id` char(36) NOT NULL,
  `contract_id` char(36) DEFAULT NULL,
  `role_title` varchar(120) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','paused','ended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `attachments`
--

CREATE TABLE `attachments` (
  `id` char(36) NOT NULL,
  `company_id` char(36) DEFAULT NULL,
  `storage_provider` enum('s3','gcs','local') NOT NULL DEFAULT 's3',
  `object_key` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `size_bytes` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `checksum_sha256` char(64) NOT NULL,
  `created_by_user_id` char(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `attachment_links`
--

CREATE TABLE `attachment_links` (
  `attachment_id` char(36) NOT NULL,
  `object_type` varchar(60) NOT NULL,
  `object_id` char(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` char(36) NOT NULL,
  `company_id` char(36) DEFAULT NULL,
  `actor_user_id` char(36) NOT NULL,
  `actor_company_user_id` char(36) DEFAULT NULL,
  `action` varchar(120) NOT NULL,
  `object_type` varchar(60) DEFAULT NULL,
  `object_id` char(36) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` char(36) NOT NULL,
  `owner_type` enum('company','freelancer') NOT NULL,
  `owner_id` char(36) NOT NULL,
  `country_id` int(10) UNSIGNED NOT NULL,
  `currency_id` int(10) UNSIGNED NOT NULL,
  `bank_name` varchar(120) NOT NULL,
  `account_number_masked` varchar(50) DEFAULT NULL,
  `account_identifier_hash` char(64) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `companies`
--

CREATE TABLE `companies` (
  `id` char(36) NOT NULL,
  `legal_name` varchar(200) NOT NULL,
  `trade_name` varchar(200) DEFAULT NULL,
  `country_id` int(10) UNSIGNED NOT NULL,
  `default_currency_id` int(10) UNSIGNED NOT NULL,
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_contacts`
--

CREATE TABLE `company_contacts` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `type` enum('primary','billing','legal','other') NOT NULL DEFAULT 'primary',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_settings`
--

CREATE TABLE `company_settings` (
  `company_id` char(36) NOT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `tax_regime` varchar(80) DEFAULT NULL,
  `billing_address` varchar(255) DEFAULT NULL,
  `invoice_series` varchar(20) DEFAULT NULL,
  `invoice_number_next` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `default_language` varchar(10) NOT NULL DEFAULT 'es',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_users`
--

CREATE TABLE `company_users` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `status` enum('active','invited','disabled') NOT NULL DEFAULT 'active',
  `department` varchar(80) DEFAULT NULL,
  `job_title` varchar(120) DEFAULT NULL,
  `active_company` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contracts`
--

CREATE TABLE `contracts` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `project_id` char(36) DEFAULT NULL,
  `freelancer_id` char(36) NOT NULL,
  `template_id` char(36) NOT NULL,
  `jurisdiction_country_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `counterparty_name` varchar(190) DEFAULT NULL,
  `counterparty_email` varchar(190) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `notice_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `payment_type` enum('hourly','retainer','fixed') NOT NULL,
  `rate_amount` decimal(12,2) DEFAULT NULL,
  `rate_currency_id` int(10) UNSIGNED NOT NULL,
  `retainer_amount` decimal(12,2) DEFAULT NULL,
  `current_version_id` char(36) DEFAULT NULL,
  `status` enum('draft','pending_signature','active','expiring','terminated','declined') NOT NULL DEFAULT 'draft',
  `legal_approved_by_company_user_id` char(36) DEFAULT NULL,
  `legal_approved_at` timestamp NULL DEFAULT NULL,
  `last_expiration_notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contract_templates`
--

CREATE TABLE `contract_templates` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `type` enum('hourly','retainer','fixed','project') NOT NULL,
  `country_id` int(10) UNSIGNED NOT NULL,
  `language_code` varchar(10) NOT NULL DEFAULT 'es',
  `title` varchar(200) NOT NULL,
  `body` longtext NOT NULL,
  `variables_schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables_schema`)),
  `version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contract_versions`
--

CREATE TABLE `contract_versions` (
  `id` char(36) NOT NULL,
  `contract_id` char(36) NOT NULL,
  `template_id` char(36) DEFAULT NULL,
  `template_version` int(10) UNSIGNED DEFAULT NULL,
  `version_number` int(10) UNSIGNED NOT NULL,
  `body_snapshot` longtext NOT NULL,
  `storage_path` varchar(255) DEFAULT NULL,
  `document_hash` char(64) DEFAULT NULL,
  `status` enum('draft','pending_signature','completed','declined','expired','voided','superseded') NOT NULL DEFAULT 'draft',
  `docusign_envelope_id` varchar(120) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `created_by_company_user_id` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contract_signers`
--

CREATE TABLE `contract_signers` (
  `id` char(36) NOT NULL,
  `contract_version_id` char(36) NOT NULL,
  `role` enum('internal','counterparty') NOT NULL DEFAULT 'counterparty',
  `name` varchar(190) NOT NULL,
  `email` varchar(190) NOT NULL,
  `signer_type` enum('company_user','freelancer','contact') DEFAULT NULL,
  `signer_id` char(36) DEFAULT NULL,
  `docusign_recipient_id` varchar(120) DEFAULT NULL,
  `status` enum('pending','viewed','signed','declined','failed') NOT NULL DEFAULT 'pending',
  `signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `countries`
--

CREATE TABLE `countries` (
  `id` int(10) UNSIGNED NOT NULL,
  `iso2` char(2) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `currencies`
--

CREATE TABLE `currencies` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` char(3) NOT NULL,
  `name` varchar(60) NOT NULL,
  `symbol` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deliverables`
--

CREATE TABLE `deliverables` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `project_id` char(36) NOT NULL,
  `assignment_id` char(36) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','in_review','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_by_company_user_id` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deliverable_reviews`
--

CREATE TABLE `deliverable_reviews` (
  `id` char(36) NOT NULL,
  `deliverable_id` char(36) NOT NULL,
  `reviewed_by_company_user_id` char(36) NOT NULL,
  `status` enum('approved','rejected','changes_requested') NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docusign_envelopes`
--

CREATE TABLE `docusign_envelopes` (
  `id` char(36) NOT NULL,
  `contract_id` char(36) NOT NULL,
  `contract_version_id` char(36) DEFAULT NULL,
  `envelope_id` varchar(120) NOT NULL,
  `status` enum('created','sent','viewed','completed','voided','declined') NOT NULL DEFAULT 'created',
  `last_event_at` timestamp NULL DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `webhook_key` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docusign_webhook_events`
--

CREATE TABLE `docusign_webhook_events` (
  `id` char(36) NOT NULL,
  `envelope_id` varchar(120) NOT NULL,
  `contract_version_id` char(36) DEFAULT NULL,
  `event_type` varchar(80) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `signature_valid` tinyint(1) NOT NULL DEFAULT 0,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `received_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exchange_rates`
--

CREATE TABLE `exchange_rates` (
  `id` char(36) NOT NULL,
  `base_currency_id` int(10) UNSIGNED NOT NULL,
  `quote_currency_id` int(10) UNSIGNED NOT NULL,
  `rate` decimal(18,8) NOT NULL,
  `rate_date` date NOT NULL,
  `source` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `freelancers`
--

CREATE TABLE `freelancers` (
  `id` char(36) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `status` enum('active','blocked','pending') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `freelancer_profiles`
--

CREATE TABLE `freelancer_profiles` (
  `freelancer_id` char(36) NOT NULL,
  `country_id` int(10) UNSIGNED NOT NULL,
  `primary_currency_id` int(10) UNSIGNED NOT NULL,
  `headline` varchar(160) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `hourly_rate_min` decimal(12,2) DEFAULT NULL,
  `hourly_rate_max` decimal(12,2) DEFAULT NULL,
  `seniority_level` varchar(40) DEFAULT NULL,
  `availability_status` enum('available','partially_available','unavailable') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `freelancer_skills`
--

CREATE TABLE `freelancer_skills` (
  `freelancer_id` char(36) NOT NULL,
  `skill` varchar(80) NOT NULL,
  `level` tinyint(3) UNSIGNED NOT NULL DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoices`
--

CREATE TABLE `invoices` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `invoice_number` varchar(40) NOT NULL,
  `series` varchar(20) DEFAULT NULL,
  `currency_id` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','issued','paid','voided') NOT NULL DEFAULT 'draft',
  `issued_at` timestamp NULL DEFAULT NULL,
  `due_at` timestamp NULL DEFAULT NULL,
  `pdf_attachment_id` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoice_lines`
--

CREATE TABLE `invoice_lines` (
  `id` char(36) NOT NULL,
  `invoice_id` char(36) NOT NULL,
  `concept` varchar(200) NOT NULL,
  `quantity` decimal(14,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(6,4) NOT NULL DEFAULT 0.0000,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kb_articles`
--

CREATE TABLE `kb_articles` (
  `id` char(36) NOT NULL,
  `company_id` char(36) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `content` longtext NOT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_by_user_id` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kyb_requests`
--

CREATE TABLE `kyb_requests` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_by_company_user_id` char(36) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kyc_requests`
--

CREATE TABLE `kyc_requests` (
  `id` char(36) NOT NULL,
  `freelancer_id` char(36) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_by_company_user_id` char(36) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `legal_approvals`
--

CREATE TABLE `legal_approvals` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `contract_id` char(36) NOT NULL,
  `contract_version_id` char(36) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by_company_user_id` char(36) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `company_id` char(36) DEFAULT NULL,
  `user_id` char(36) NOT NULL,
  `channel` enum('email','in_app') NOT NULL DEFAULT 'in_app',
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `status` enum('queued','sent','read','failed') NOT NULL DEFAULT 'queued',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nps_responses`
--

CREATE TABLE `nps_responses` (
  `id` char(36) NOT NULL,
  `survey_id` char(36) NOT NULL,
  `project_id` char(36) DEFAULT NULL,
  `assignment_id` char(36) DEFAULT NULL,
  `score` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `responded_by_company_user_id` char(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nps_surveys`
--

CREATE TABLE `nps_surveys` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `onboarding_checklists`
--

CREATE TABLE `onboarding_checklists` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `applies_to` varchar(120) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `onboarding_items`
--

CREATE TABLE `onboarding_items` (
  `id` char(36) NOT NULL,
  `assignment_id` char(36) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('todo','in_progress','done','blocked') NOT NULL DEFAULT 'todo',
  `order_index` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `due_at` timestamp NULL DEFAULT NULL,
  `completed_by_company_user_id` char(36) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `onboarding_item_templates`
--

CREATE TABLE `onboarding_item_templates` (
  `id` char(36) NOT NULL,
  `checklist_id` char(36) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `order_index` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `sla_hours` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_cycles`
--

CREATE TABLE `payment_cycles` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `frequency` enum('biweekly','monthly') NOT NULL,
  `cutoff_day` tinyint(3) UNSIGNED DEFAULT NULL,
  `currency_id` int(10) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payouts`
--

CREATE TABLE `payouts` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `payroll_run_id` char(36) NOT NULL,
  `freelancer_id` char(36) NOT NULL,
  `amount_original` decimal(14,2) NOT NULL,
  `currency_original_id` int(10) UNSIGNED NOT NULL,
  `amount_company_currency` decimal(14,2) NOT NULL,
  `company_currency_id` int(10) UNSIGNED NOT NULL,
  `fx_rate_id` char(36) DEFAULT NULL,
  `withholdings_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','paid','failed','observed') NOT NULL DEFAULT 'pending',
  `bank_account_id` char(36) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payroll_runs`
--

CREATE TABLE `payroll_runs` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` enum('draft','pending_approvals','approved','processed','failed') NOT NULL DEFAULT 'draft',
  `created_by_company_user_id` char(36) DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payslips`
--

CREATE TABLE `payslips` (
  `id` char(36) NOT NULL,
  `payout_id` char(36) NOT NULL,
  `pdf_attachment_id` char(36) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permissions`
--

CREATE TABLE `permissions` (
  `id` char(36) NOT NULL,
  `code` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `scope` enum('platform','company') NOT NULL DEFAULT 'company'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `projects`
--

CREATE TABLE `projects` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `country_id` int(10) UNSIGNED NOT NULL,
  `currency_id` int(10) UNSIGNED NOT NULL,
  `status` enum('active','on_hold','closed') NOT NULL DEFAULT 'active',
  `created_by_company_user_id` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `project_members`
--

CREATE TABLE `project_members` (
  `project_id` char(36) NOT NULL,
  `company_user_id` char(36) NOT NULL,
  `role_in_project` enum('owner','manager','viewer') NOT NULL DEFAULT 'viewer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `requisition_id` char(36) DEFAULT NULL,
  `vendor_id` char(36) NOT NULL,
  `po_number` varchar(60) NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency_id` int(10) UNSIGNED NOT NULL,
  `status` enum('issued','sent','acknowledged','closed','cancelled') NOT NULL DEFAULT 'issued',
  `pdf_attachment_id` char(36) DEFAULT NULL,
  `issued_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requisitions`
--

CREATE TABLE `requisitions` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `project_id` char(36) DEFAULT NULL,
  `requested_by_company_user_id` char(36) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency_id` int(10) UNSIGNED NOT NULL,
  `status` enum('draft','submitted','approved','rejected','converted_to_po') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `name` varchar(80) NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` char(36) NOT NULL,
  `permission_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` char(36) NOT NULL,
  `company_id` char(36) DEFAULT NULL,
  `created_by_user_id` char(36) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `assigned_to_user_id` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `timesheets`
--

CREATE TABLE `timesheets` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `assignment_id` char(36) NOT NULL,
  `work_date` date NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected','locked') NOT NULL DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_by_company_user_id` char(36) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `translations`
--

CREATE TABLE `translations` (
  `id` char(36) NOT NULL,
  `language_code` varchar(10) NOT NULL,
  `i18n_key` varchar(120) NOT NULL,
  `value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` char(36) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `status` enum('active','locked','disabled') NOT NULL DEFAULT 'active',
  `platform_role` enum('none','super_admin','support','finance_ops','legal_ops') NOT NULL DEFAULT 'none',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_identities`
--

CREATE TABLE `user_identities` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `provider` enum('google','microsoft') NOT NULL,
  `provider_subject` varchar(255) NOT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_roles`
--

CREATE TABLE `user_roles` (
  `company_user_id` char(36) NOT NULL,
  `role_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `refresh_token_hash` char(64) NOT NULL,
  `status` enum('active','revoked','expired') NOT NULL DEFAULT 'active',
  `last_ip` varchar(45) DEFAULT NULL,
  `last_user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `revoked_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vendors`
--

CREATE TABLE `vendors` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `name` varchar(200) NOT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wallets`
--

CREATE TABLE `wallets` (
  `company_id` char(36) NOT NULL,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency_id` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `type` enum('topup','fee','debit','adjustment','refund') NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency_id` int(10) UNSIGNED NOT NULL,
  `related_object_type` varchar(50) DEFAULT NULL,
  `related_object_id` char(36) DEFAULT NULL,
  `status` enum('pending','posted','voided') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
