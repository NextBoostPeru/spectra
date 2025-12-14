-- Índices para tablas volcadas
--

--
-- Indices de la tabla `access_provisions`
--
ALTER TABLE `access_provisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_access_provisions_assignment` (`assignment_id`,`status`),
  ADD KEY `fk_access_provisions_requested_by` (`requested_by_company_user_id`),
  ADD KEY `fk_access_provisions_provisioned_by` (`provisioned_by_company_user_id`);

--
-- Indices de la tabla `approval_policies`
--
ALTER TABLE `approval_policies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_approval_policies_company` (`company_id`,`object_type`,`is_active`);

--
-- Indices de la tabla `approval_requests`
--
ALTER TABLE `approval_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_approval_requests_lookup` (`company_id`,`object_type`,`object_id`),
  ADD KEY `ix_approval_requests_status` (`company_id`,`status`),
  ADD KEY `fk_approval_requests_created_by` (`created_by_company_user_id`);

--
-- Indices de la tabla `approval_rules`
--
ALTER TABLE `approval_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_approval_rules_policy` (`policy_id`,`sequence_order`),
  ADD KEY `fk_approval_rules_currency` (`currency_id`),
  ADD KEY `fk_approval_rules_role` (`required_role_id`);

--
-- Indices de la tabla `approval_steps`
--
ALTER TABLE `approval_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_approval_steps_req` (`approval_request_id`,`sequence_order`),
  ADD KEY `fk_approval_steps_required_role` (`required_role_id`),
  ADD KEY `fk_approval_steps_assigned_to` (`assigned_to_company_user_id`),
  ADD KEY `fk_approval_steps_acted_by` (`acted_by_company_user_id`);

--
-- Indices de la tabla `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_assignments_company` (`company_id`,`status`),
  ADD KEY `ix_assignments_project` (`project_id`),
  ADD KEY `ix_assignments_freelancer` (`freelancer_id`),
  ADD KEY `fk_assignments_contract` (`contract_id`);

--
-- Indices de la tabla `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attachments_object` (`storage_provider`,`object_key`),
  ADD KEY `ix_attachments_company` (`company_id`),
  ADD KEY `fk_attachments_created_by` (`created_by_user_id`);

--
-- Indices de la tabla `attachment_links`
--
ALTER TABLE `attachment_links`
  ADD PRIMARY KEY (`attachment_id`,`object_type`,`object_id`),
  ADD KEY `ix_attachment_links_object` (`object_type`,`object_id`);

--
-- Indices de la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_audit_company_time` (`company_id`,`created_at`),
  ADD KEY `ix_audit_actor` (`actor_user_id`,`created_at`),
  ADD KEY `ix_audit_object` (`object_type`,`object_id`),
  ADD KEY `fk_audit_actor_company_user` (`actor_company_user_id`);

--
-- Indices de la tabla `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_bank_identifier_hash` (`account_identifier_hash`),
  ADD KEY `ix_bank_owner` (`owner_type`,`owner_id`),
  ADD KEY `fk_bank_country` (`country_id`),
  ADD KEY `fk_bank_currency` (`currency_id`);

--
-- Indices de la tabla `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_companies_country` (`country_id`),
  ADD KEY `ix_companies_status` (`status`),
  ADD KEY `fk_companies_currency` (`default_currency_id`);

--
-- Indices de la tabla `company_contacts`
--
ALTER TABLE `company_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_company_contacts_company` (`company_id`),
  ADD KEY `ix_company_contacts_type` (`company_id`,`type`);

--
-- Indices de la tabla `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`company_id`);

--
-- Indices de la tabla `company_users`
--
ALTER TABLE `company_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_user_membership` (`company_id`,`user_id`),
  ADD KEY `ix_company_users_company` (`company_id`,`status`),
  ADD KEY `ix_company_users_user` (`user_id`);

--
-- Indices de la tabla `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_contracts_company` (`company_id`,`status`),
  ADD KEY `ix_contracts_freelancer` (`freelancer_id`),
  ADD KEY `ix_contracts_project` (`project_id`),
  ADD KEY `fk_contracts_template` (`template_id`),
  ADD KEY `fk_contracts_jurisdiction` (`jurisdiction_country_id`),
  ADD KEY `fk_contracts_rate_currency` (`rate_currency_id`),
  ADD KEY `ix_contracts_current_version` (`current_version_id`),
  ADD KEY `ix_contracts_counterparty` (`counterparty_email`),
  ADD KEY `fk_contracts_legal_approver` (`legal_approved_by_company_user_id`);

--
-- Indices de la tabla `contract_templates`
--
ALTER TABLE `contract_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_templates_company` (`company_id`,`status`,`version`),
  ADD KEY `ix_templates_country` (`country_id`);

--
-- Indices de la tabla `contract_versions`
--
ALTER TABLE `contract_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_contract_versions` (`contract_id`,`version_number`),
  ADD KEY `ix_contract_versions_contract` (`contract_id`),
  ADD KEY `ix_contract_versions_template` (`template_id`),
  ADD KEY `ix_contract_versions_envelope` (`docusign_envelope_id`),
  ADD KEY `fk_contract_versions_created_by` (`created_by_company_user_id`);

--
-- Indices de la tabla `contract_signers`
--
ALTER TABLE `contract_signers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_contract_signers_version` (`contract_version_id`,`status`),
  ADD KEY `ix_contract_signers_email` (`email`);

--
-- Indices de la tabla `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_countries_iso2` (`iso2`);

--
-- Indices de la tabla `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_currencies_code` (`code`);

--
-- Indices de la tabla `deliverables`
--
ALTER TABLE `deliverables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_deliverables_company` (`company_id`,`status`),
  ADD KEY `ix_deliverables_project` (`project_id`),
  ADD KEY `fk_deliverables_assignment` (`assignment_id`),
  ADD KEY `fk_deliverables_created_by` (`created_by_company_user_id`);

--
-- Indices de la tabla `deliverable_reviews`
--
ALTER TABLE `deliverable_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_deliverable_reviews_deliverable` (`deliverable_id`),
  ADD KEY `fk_deliverable_reviews_reviewer` (`reviewed_by_company_user_id`);

--
-- Indices de la tabla `docusign_envelopes`
--
ALTER TABLE `docusign_envelopes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_docusign_envelope_id` (`envelope_id`),
  ADD KEY `ix_docusign_contract` (`contract_id`),
  ADD KEY `ix_docusign_version` (`contract_version_id`);

--
-- Indices de la tabla `docusign_webhook_events`
--
ALTER TABLE `docusign_webhook_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_webhooks_envelope` (`envelope_id`),
  ADD KEY `ix_webhooks_version` (`contract_version_id`);

--
-- Indices de la tabla `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fx_pair_day` (`base_currency_id`,`quote_currency_id`,`rate_date`),
  ADD KEY `ix_fx_date` (`rate_date`),
  ADD KEY `fk_fx_quote_currency` (`quote_currency_id`);

--
-- Indices de la tabla `freelancers`
--
ALTER TABLE `freelancers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_freelancers_email` (`email`),
  ADD KEY `ix_freelancers_status` (`status`);

--
-- Indices de la tabla `freelancer_profiles`
--
ALTER TABLE `freelancer_profiles`
  ADD PRIMARY KEY (`freelancer_id`),
  ADD KEY `fk_freelancer_profiles_country` (`country_id`),
  ADD KEY `fk_freelancer_profiles_currency` (`primary_currency_id`);

--
-- Indices de la tabla `freelancer_skills`
--
ALTER TABLE `freelancer_skills`
  ADD PRIMARY KEY (`freelancer_id`,`skill`);

--
-- Indices de la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_invoice_company_number` (`company_id`,`invoice_number`),
  ADD KEY `ix_invoices_company` (`company_id`,`status`),
  ADD KEY `fk_invoices_currency` (`currency_id`),
  ADD KEY `fk_invoices_pdf_attachment` (`pdf_attachment_id`);

--
-- Indices de la tabla `invoice_lines`
--
ALTER TABLE `invoice_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_invoice_lines_invoice` (`invoice_id`);

--
-- Indices de la tabla `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_kb_company` (`company_id`,`status`),
  ADD KEY `fk_kb_created_by` (`created_by_user_id`);

--
-- Indices de la tabla `kyb_requests`
--
ALTER TABLE `kyb_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_kyb_company` (`company_id`,`status`),
  ADD KEY `fk_kyb_reviewed_by` (`reviewed_by_company_user_id`);

--
-- Indices de la tabla `kyc_requests`
--
ALTER TABLE `kyc_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_kyc_freelancer` (`freelancer_id`,`status`),
  ADD KEY `fk_kyc_reviewed_by` (`reviewed_by_company_user_id`);

--
-- Indices de la tabla `legal_approvals`
--
ALTER TABLE `legal_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_legal_approvals_company` (`company_id`,`status`),
  ADD KEY `ix_legal_approvals_contract` (`contract_id`),
  ADD KEY `ix_legal_approvals_version` (`contract_version_id`),
  ADD KEY `fk_legal_approvals_reviewer` (`reviewed_by_company_user_id`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_notifications_user` (`user_id`,`status`),
  ADD KEY `ix_notifications_company` (`company_id`);

--
-- Indices de la tabla `nps_responses`
--
ALTER TABLE `nps_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_nps_responses_survey` (`survey_id`),
  ADD KEY `fk_nps_responses_project` (`project_id`),
  ADD KEY `fk_nps_responses_assignment` (`assignment_id`),
  ADD KEY `fk_nps_responses_responded_by` (`responded_by_company_user_id`);

--
-- Indices de la tabla `nps_surveys`
--
ALTER TABLE `nps_surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_nps_surveys_company` (`company_id`,`status`);

--
-- Indices de la tabla `onboarding_checklists`
--
ALTER TABLE `onboarding_checklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_onboarding_checklists_company` (`company_id`,`is_active`);

--
-- Indices de la tabla `onboarding_items`
--
ALTER TABLE `onboarding_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_onboarding_items_assignment` (`assignment_id`,`status`),
  ADD KEY `fk_onboarding_items_completed_by` (`completed_by_company_user_id`);

--
-- Indices de la tabla `onboarding_item_templates`
--
ALTER TABLE `onboarding_item_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_onboarding_item_templates` (`checklist_id`,`order_index`);

--
-- Indices de la tabla `payment_cycles`
--
ALTER TABLE `payment_cycles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_payment_cycles_company` (`company_id`,`is_active`),
  ADD KEY `fk_payment_cycles_currency` (`currency_id`);

--
-- Indices de la tabla `payouts`
--
ALTER TABLE `payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_payouts_company` (`company_id`,`status`),
  ADD KEY `ix_payouts_payroll` (`payroll_run_id`),
  ADD KEY `ix_payouts_freelancer` (`freelancer_id`),
  ADD KEY `fk_payouts_currency_original` (`currency_original_id`),
  ADD KEY `fk_payouts_company_currency` (`company_currency_id`),
  ADD KEY `fk_payouts_fx_rate` (`fx_rate_id`),
  ADD KEY `fk_payouts_bank_account` (`bank_account_id`);

--
-- Indices de la tabla `payroll_runs`
--
ALTER TABLE `payroll_runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_payroll_runs_company` (`company_id`,`status`),
  ADD KEY `ix_payroll_runs_period` (`company_id`,`period_start`,`period_end`),
  ADD KEY `fk_payroll_runs_created_by` (`created_by_company_user_id`),
  ADD KEY `fk_payroll_runs_currency` (`currency_id`);

--
-- Indices de la tabla `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payslips_payout` (`payout_id`),
  ADD KEY `fk_payslips_attachment` (`pdf_attachment_id`);

--
-- Indices de la tabla `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permissions_code` (`code`),
  ADD KEY `ix_permissions_scope` (`scope`);

--
-- Indices de la tabla `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_projects_company` (`company_id`,`status`),
  ADD KEY `ix_projects_country` (`country_id`),
  ADD KEY `fk_projects_currency` (`currency_id`),
  ADD KEY `fk_projects_created_by` (`created_by_company_user_id`);

--
-- Indices de la tabla `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`project_id`,`company_user_id`),
  ADD KEY `ix_project_members_user` (`company_user_id`);

--
-- Indices de la tabla `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_po_company_number` (`company_id`,`po_number`),
  ADD KEY `ix_po_company` (`company_id`,`status`),
  ADD KEY `fk_po_requisition` (`requisition_id`),
  ADD KEY `fk_po_vendor` (`vendor_id`),
  ADD KEY `fk_po_currency` (`currency_id`),
  ADD KEY `fk_po_pdf_attachment` (`pdf_attachment_id`);

--
-- Indices de la tabla `requisitions`
--
ALTER TABLE `requisitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_requisitions_company` (`company_id`,`status`),
  ADD KEY `ix_requisitions_project` (`project_id`),
  ADD KEY `fk_requisitions_requested_by` (`requested_by_company_user_id`),
  ADD KEY `fk_requisitions_currency` (`currency_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_company_name` (`company_id`,`name`),
  ADD KEY `ix_roles_company` (`company_id`);

--
-- Indices de la tabla `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `ix_role_permissions_perm` (`permission_id`);

--
-- Indices de la tabla `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_tickets_status` (`status`,`priority`),
  ADD KEY `ix_tickets_company` (`company_id`),
  ADD KEY `fk_tickets_created_by` (`created_by_user_id`),
  ADD KEY `fk_tickets_assigned_to` (`assigned_to_user_id`);

--
-- Indices de la tabla `timesheets`
--
ALTER TABLE `timesheets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_timesheets_company` (`company_id`,`status`),
  ADD KEY `ix_timesheets_assignment_date` (`assignment_id`,`work_date`),
  ADD KEY `fk_timesheets_approved_by` (`approved_by_company_user_id`);

--
-- Indices de la tabla `translations`
--
ALTER TABLE `translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_translations_lang_key` (`language_code`,`i18n_key`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `ix_users_status` (`status`),
  ADD KEY `ix_users_platform_role` (`platform_role`);

--
-- Indices de la tabla `user_identities`
--
ALTER TABLE `user_identities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_identity_provider_subject` (`provider`,`provider_subject`),
  ADD KEY `ix_user_identities_user` (`user_id`);

--
-- Indices de la tabla `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`company_user_id`,`role_id`),
  ADD KEY `ix_user_roles_role` (`role_id`);

--
-- Indices de la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sessions_refresh_hash` (`refresh_token_hash`),
  ADD KEY `ix_sessions_user` (`user_id`),
  ADD KEY `ix_sessions_status` (`status`);

--
-- Indices de la tabla `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_vendors_company` (`company_id`,`status`);

--
-- Indices de la tabla `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`company_id`),
  ADD KEY `fk_wallets_currency` (`currency_id`);

--
-- Indices de la tabla `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_wallet_tx_company` (`company_id`,`status`),
  ADD KEY `ix_wallet_tx_related` (`related_object_type`,`related_object_id`),
  ADD KEY `fk_wallet_tx_currency` (`currency_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `access_provisions`
--
ALTER TABLE `access_provisions`
  ADD CONSTRAINT `fk_access_provisions_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  ADD CONSTRAINT `fk_access_provisions_provisioned_by` FOREIGN KEY (`provisioned_by_company_user_id`) REFERENCES `company_users` (`id`),
  ADD CONSTRAINT `fk_access_provisions_requested_by` FOREIGN KEY (`requested_by_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Filtros para la tabla `approval_policies`
--
ALTER TABLE `approval_policies`
  ADD CONSTRAINT `fk_approval_policies_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Filtros para la tabla `approval_requests`
--
ALTER TABLE `approval_requests`
  ADD CONSTRAINT `fk_approval_requests_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_approval_requests_created_by` FOREIGN KEY (`created_by_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Filtros para la tabla `approval_rules`
--
ALTER TABLE `approval_rules`
  ADD CONSTRAINT `fk_approval_rules_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_approval_rules_policy` FOREIGN KEY (`policy_id`) REFERENCES `approval_policies` (`id`),
  ADD CONSTRAINT `fk_approval_rules_role` FOREIGN KEY (`required_role_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `approval_steps`
--
ALTER TABLE `approval_steps`
  ADD CONSTRAINT `fk_approval_steps_acted_by` FOREIGN KEY (`acted_by_company_user_id`) REFERENCES `company_users` (`id`),
  ADD CONSTRAINT `fk_approval_steps_assigned_to` FOREIGN KEY (`assigned_to_company_user_id`) REFERENCES `company_users` (`id`),
  ADD CONSTRAINT `fk_approval_steps_request` FOREIGN KEY (`approval_request_id`) REFERENCES `approval_requests` (`id`),
  ADD CONSTRAINT `fk_approval_steps_required_role` FOREIGN KEY (`required_role_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `fk_assignments_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_assignments_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`),
  ADD CONSTRAINT `fk_assignments_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `freelancers` (`id`),
  ADD CONSTRAINT `fk_assignments_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

--
-- Filtros para la tabla `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `fk_attachments_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_attachments_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `attachment_links`
--
ALTER TABLE `attachment_links`
  ADD CONSTRAINT `fk_attachment_links_attachment` FOREIGN KEY (`attachment_id`) REFERENCES `attachments` (`id`);

--
-- Filtros para la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_actor_company_user` FOREIGN KEY (`actor_company_user_id`) REFERENCES `company_users` (`id`),
  ADD CONSTRAINT `fk_audit_actor_user` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_audit_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Filtros para la tabla `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD CONSTRAINT `fk_bank_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  ADD CONSTRAINT `fk_bank_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`);

--
-- Filtros para la tabla `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `fk_companies_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  ADD CONSTRAINT `fk_companies_currency` FOREIGN KEY (`default_currency_id`) REFERENCES `currencies` (`id`);

--
-- Filtros para la tabla `company_contacts`
--
ALTER TABLE `company_contacts`
  ADD CONSTRAINT `fk_company_contacts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Filtros para la tabla `company_settings`
--
ALTER TABLE `company_settings`
  ADD CONSTRAINT `fk_company_settings_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Filtros para la tabla `company_users`
--
ALTER TABLE `company_users`
  ADD CONSTRAINT `fk_company_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_company_users_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `fk_contracts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_contracts_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `freelancers` (`id`),
  ADD CONSTRAINT `fk_contracts_jurisdiction` FOREIGN KEY (`jurisdiction_country_id`) REFERENCES `countries` (`id`),
  ADD CONSTRAINT `fk_contracts_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  ADD CONSTRAINT `fk_contracts_rate_currency` FOREIGN KEY (`rate_currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_contracts_template` FOREIGN KEY (`template_id`) REFERENCES `contract_templates` (`id`),
  ADD CONSTRAINT `fk_contracts_current_version` FOREIGN KEY (`current_version_id`) REFERENCES `contract_versions` (`id`),
  ADD CONSTRAINT `fk_contracts_legal_approver` FOREIGN KEY (`legal_approved_by_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Filtros para la tabla `contract_templates`
--
ALTER TABLE `contract_templates`
  ADD CONSTRAINT `fk_templates_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_templates_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`);

--
-- Filtros para la tabla `contract_versions`
--
ALTER TABLE `contract_versions`
  ADD CONSTRAINT `fk_contract_versions_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`),
  ADD CONSTRAINT `fk_contract_versions_template` FOREIGN KEY (`template_id`) REFERENCES `contract_templates` (`id`),
  ADD CONSTRAINT `fk_contract_versions_created_by` FOREIGN KEY (`created_by_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Filtros para la tabla `contract_signers`
--
ALTER TABLE `contract_signers`
  ADD CONSTRAINT `fk_contract_signers_version` FOREIGN KEY (`contract_version_id`) REFERENCES `contract_versions` (`id`);

--
-- Filtros para la tabla `deliverables`
--
ALTER TABLE `deliverables`
  ADD CONSTRAINT `fk_deliverables_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  ADD CONSTRAINT `fk_deliverables_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_deliverables_created_by` FOREIGN KEY (`created_by_company_user_id`) REFERENCES `company_users` (`id`),
  ADD CONSTRAINT `fk_deliverables_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

--
-- Filtros para la tabla `deliverable_reviews`
--
ALTER TABLE `deliverable_reviews`
  ADD CONSTRAINT `fk_deliverable_reviews_deliverable` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`),
  ADD CONSTRAINT `fk_deliverable_reviews_reviewer` FOREIGN KEY (`reviewed_by_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Filtros para la tabla `docusign_envelopes`
--
ALTER TABLE `docusign_envelopes`
  ADD CONSTRAINT `fk_docusign_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`),
  ADD CONSTRAINT `fk_docusign_version` FOREIGN KEY (`contract_version_id`) REFERENCES `contract_versions` (`id`);

--
-- Filtros para la tabla `docusign_webhook_events`
--
ALTER TABLE `docusign_webhook_events`
  ADD CONSTRAINT `fk_webhooks_version` FOREIGN KEY (`contract_version_id`) REFERENCES `contract_versions` (`id`);

--
-- Filtros para la tabla `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD CONSTRAINT `fk_fx_base_currency` FOREIGN KEY (`base_currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_fx_quote_currency` FOREIGN KEY (`quote_currency_id`) REFERENCES `currencies` (`id`);

--
-- Filtros para la tabla `freelancer_profiles`
--
ALTER TABLE `freelancer_profiles`
  ADD CONSTRAINT `fk_freelancer_profiles_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  ADD CONSTRAINT `fk_freelancer_profiles_currency` FOREIGN KEY (`primary_currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_freelancer_profiles_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `freelancers` (`id`);

--
-- Filtros para la tabla `freelancer_skills`
--
ALTER TABLE `freelancer_skills`
  ADD CONSTRAINT `fk_freelancer_skills_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `freelancers` (`id`);

--
-- Filtros para la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_invoices_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_invoices_pdf_attachment` FOREIGN KEY (`pdf_attachment_id`) REFERENCES `attachments` (`id`);

--
-- Filtros para la tabla `invoice_lines`
--
ALTER TABLE `invoice_lines`
  ADD CONSTRAINT `fk_invoice_lines_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);

--
-- Filtros para la tabla `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD CONSTRAINT `fk_kb_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_kb_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `kyb_requests`
--
ALTER TABLE `kyb_requests`
  ADD CONSTRAINT `fk_kyb_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_kyb_reviewed_by` FOREIGN KEY (`reviewed_by_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Filtros para la tabla `kyc_requests`
--
ALTER TABLE `kyc_requests`
  ADD CONSTRAINT `fk_kyc_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `freelancers` (`id`),
  ADD CONSTRAINT `fk_kyc_reviewed_by` FOREIGN KEY (`reviewed_by_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Filtros para la tabla `legal_approvals`
--
ALTER TABLE `legal_approvals`
  ADD CONSTRAINT `fk_legal_approvals_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_legal_approvals_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`),
  ADD CONSTRAINT `fk_legal_approvals_version` FOREIGN KEY (`contract_version_id`) REFERENCES `contract_versions` (`id`),
  ADD CONSTRAINT `fk_legal_approvals_reviewer` FOREIGN KEY (`reviewed_by_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Filtros para la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `nps_responses`
--
ALTER TABLE `nps_responses`
  ADD CONSTRAINT `fk_nps_responses_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  ADD CONSTRAINT `fk_nps_responses_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  ADD CONSTRAINT `fk_nps_responses_responded_by` FOREIGN KEY (`responded_by_company_user_id`) REFERENCES `company_users` (`id`),
  ADD CONSTRAINT `fk_nps_responses_survey` FOREIGN KEY (`survey_id`) REFERENCES `nps_surveys` (`id`);

--
-- Filtros para la tabla `nps_surveys`
--
ALTER TABLE `nps_surveys`
  ADD CONSTRAINT `fk_nps_surveys_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Filtros para la tabla `onboarding_checklists`
--
ALTER TABLE `onboarding_checklists`
  ADD CONSTRAINT `fk_onboarding_checklists_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Filtros para la tabla `onboarding_items`
--
ALTER TABLE `onboarding_items`
  ADD CONSTRAINT `fk_onboarding_items_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  ADD CONSTRAINT `fk_onboarding_items_completed_by` FOREIGN KEY (`completed_by_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Filtros para la tabla `onboarding_item_templates`
--
ALTER TABLE `onboarding_item_templates`
  ADD CONSTRAINT `fk_onboarding_item_templates_checklist` FOREIGN KEY (`checklist_id`) REFERENCES `onboarding_checklists` (`id`);

--
-- Filtros para la tabla `payment_cycles`
--
ALTER TABLE `payment_cycles`
  ADD CONSTRAINT `fk_payment_cycles_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_payment_cycles_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`);

--
-- Filtros para la tabla `payouts`
--
ALTER TABLE `payouts`
  ADD CONSTRAINT `fk_payouts_bank_account` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`),
  ADD CONSTRAINT `fk_payouts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_payouts_company_currency` FOREIGN KEY (`company_currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_payouts_currency_original` FOREIGN KEY (`currency_original_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_payouts_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `freelancers` (`id`),
  ADD CONSTRAINT `fk_payouts_fx_rate` FOREIGN KEY (`fx_rate_id`) REFERENCES `exchange_rates` (`id`),
  ADD CONSTRAINT `fk_payouts_payroll` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`);

--
-- Filtros para la tabla `payroll_runs`
--
ALTER TABLE `payroll_runs`
  ADD CONSTRAINT `fk_payroll_runs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_payroll_runs_created_by` FOREIGN KEY (`created_by_company_user_id`) REFERENCES `company_users` (`id`),
  ADD CONSTRAINT `fk_payroll_runs_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`);

--
-- Filtros para la tabla `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `fk_payslips_attachment` FOREIGN KEY (`pdf_attachment_id`) REFERENCES `attachments` (`id`),
  ADD CONSTRAINT `fk_payslips_payout` FOREIGN KEY (`payout_id`) REFERENCES `payouts` (`id`);

--
-- Filtros para la tabla `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_projects_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  ADD CONSTRAINT `fk_projects_created_by` FOREIGN KEY (`created_by_company_user_id`) REFERENCES `company_users` (`id`),
  ADD CONSTRAINT `fk_projects_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`);

--
-- Filtros para la tabla `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `fk_project_members_company_user` FOREIGN KEY (`company_user_id`) REFERENCES `company_users` (`id`),
  ADD CONSTRAINT `fk_project_members_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

--
-- Filtros para la tabla `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_po_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_po_pdf_attachment` FOREIGN KEY (`pdf_attachment_id`) REFERENCES `attachments` (`id`),
  ADD CONSTRAINT `fk_po_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`),
  ADD CONSTRAINT `fk_po_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`);

--
-- Filtros para la tabla `requisitions`
--
ALTER TABLE `requisitions`
  ADD CONSTRAINT `fk_requisitions_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_requisitions_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_requisitions_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  ADD CONSTRAINT `fk_requisitions_requested_by` FOREIGN KEY (`requested_by_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Filtros para la tabla `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `fk_roles_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Filtros para la tabla `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`),
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `fk_tickets_assigned_to` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_tickets_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_tickets_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `timesheets`
--
ALTER TABLE `timesheets`
  ADD CONSTRAINT `fk_timesheets_approved_by` FOREIGN KEY (`approved_by_company_user_id`) REFERENCES `company_users` (`id`),
  ADD CONSTRAINT `fk_timesheets_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  ADD CONSTRAINT `fk_timesheets_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Filtros para la tabla `user_identities`
--
ALTER TABLE `user_identities`
  ADD CONSTRAINT `fk_user_identities_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_company_user` FOREIGN KEY (`company_user_id`) REFERENCES `company_users` (`id`),
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `vendors`
--
ALTER TABLE `vendors`
  ADD CONSTRAINT `fk_vendors_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Filtros para la tabla `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `fk_wallets_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_wallets_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`);

--
-- Filtros para la tabla `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `fk_wallet_tx_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_wallet_tx_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`);

--
-- Indices para la tabla `onboarding_checklists`
--
ALTER TABLE `onboarding_checklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_onboarding_checklists_company` (`company_id`),
  ADD CONSTRAINT `fk_onboarding_checklists_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Indices para la tabla `onboarding_items`
--
ALTER TABLE `onboarding_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_onboarding_items_checklist` (`checklist_id`),
  ADD CONSTRAINT `fk_onboarding_items_checklist` FOREIGN KEY (`checklist_id`) REFERENCES `onboarding_checklists` (`id`);

--
-- Indices para la tabla `onboarding_assignments`
--
ALTER TABLE `onboarding_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_onboarding_assignments_company` (`company_id`),
  ADD KEY `idx_onboarding_assignments_checklist` (`checklist_id`),
  ADD KEY `idx_onboarding_assignments_subject` (`subject_id`),
  ADD CONSTRAINT `fk_onboarding_assignments_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_onboarding_assignments_checklist` FOREIGN KEY (`checklist_id`) REFERENCES `onboarding_checklists` (`id`);

--
-- Indices para la tabla `onboarding_assignment_items`
--
ALTER TABLE `onboarding_assignment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_onboarding_assignment_items_assignment` (`assignment_id`),
  ADD KEY `idx_onboarding_assignment_items_item` (`item_id`),
  ADD KEY `idx_onboarding_assignment_items_assignee` (`assignee_company_user_id`),
  ADD CONSTRAINT `fk_onboarding_assignment_items_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `onboarding_assignments` (`id`),
  ADD CONSTRAINT `fk_onboarding_assignment_items_item` FOREIGN KEY (`item_id`) REFERENCES `onboarding_items` (`id`),
  ADD CONSTRAINT `fk_onboarding_assignment_items_assignee` FOREIGN KEY (`assignee_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Indices para la tabla `onboarding_access_provisions`
--
ALTER TABLE `onboarding_access_provisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_onboarding_access_assignment_item` (`assignment_item_id`),
  ADD KEY `idx_onboarding_access_granted_by` (`granted_by_company_user_id`),
  ADD CONSTRAINT `fk_onboarding_access_assignment_item` FOREIGN KEY (`assignment_item_id`) REFERENCES `onboarding_assignment_items` (`id`),
  ADD CONSTRAINT `fk_onboarding_access_granted_by` FOREIGN KEY (`granted_by_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Indices para la tabla `deliverables`
--
ALTER TABLE `deliverables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deliverables_company` (`company_id`),
  ADD KEY `idx_deliverables_project` (`project_id`),
  ADD KEY `idx_deliverables_assignment` (`assignment_id`),
  ADD CONSTRAINT `fk_deliverables_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_deliverables_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  ADD CONSTRAINT `fk_deliverables_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`);

--
-- Indices para la tabla `deliverable_reviews`
--
ALTER TABLE `deliverable_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deliverable_reviews_deliverable` (`deliverable_id`),
  ADD KEY `idx_deliverable_reviews_reviewer` (`reviewer_company_user_id`),
  ADD CONSTRAINT `fk_deliverable_reviews_deliverable` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`),
  ADD CONSTRAINT `fk_deliverable_reviews_reviewer` FOREIGN KEY (`reviewer_company_user_id`) REFERENCES `company_users` (`id`);

--
-- Indices para la tabla `nps_responses`
--
ALTER TABLE `nps_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nps_responses_company` (`company_id`),
  ADD KEY `idx_nps_responses_project` (`project_id`),
  ADD KEY `idx_nps_responses_user` (`respondent_company_user_id`),
  ADD CONSTRAINT `fk_nps_responses_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_nps_responses_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  ADD CONSTRAINT `fk_nps_responses_user` FOREIGN KEY (`respondent_company_user_id`) REFERENCES `company_users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
