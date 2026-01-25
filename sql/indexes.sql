-- Mark-A CRM (multi-tenant) - indexes.sql
-- Index + Foreign Key tanımları.

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

SET FOREIGN_KEY_CHECKS = 0;

-- tenants / domains
ALTER TABLE tenants
  ADD UNIQUE KEY ux_tenants_slug (slug);

ALTER TABLE domains
  ADD UNIQUE KEY ux_domains_host (host),
  ADD KEY ix_domains_tenant (tenant_id),
  ADD CONSTRAINT fk_domains_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

-- RBAC
ALTER TABLE roles
  ADD KEY ix_roles_tenant (tenant_id),
  ADD UNIQUE KEY ux_roles_tenant_key (tenant_id, `key`),
  ADD CONSTRAINT fk_roles_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE permissions
  ADD UNIQUE KEY ux_permissions_key (`key`);

ALTER TABLE role_permissions
  ADD KEY ix_role_permissions_permission (permission_id),
  ADD CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE;

ALTER TABLE users
  ADD UNIQUE KEY ux_users_email (email),
  ADD KEY ix_users_tenant (tenant_id),
  ADD KEY ix_users_role (role_id),
  ADD CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

ALTER TABLE user_tenants
  ADD UNIQUE KEY ux_user_tenants_user_tenant (user_id, tenant_id),
  ADD KEY ix_user_tenants_tenant (tenant_id),
  ADD KEY ix_user_tenants_role (role_id),
  ADD CONSTRAINT fk_user_tenants_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_user_tenants_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_user_tenants_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

-- contacts
ALTER TABLE contacts
  ADD KEY ix_contacts_tenant (tenant_id),
  ADD KEY ix_contacts_external (external_id),
  ADD KEY ix_contacts_instagram (tenant_id, provider, instagram_user_id),
  ADD CONSTRAINT fk_contacts_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

-- pipeline / leads
ALTER TABLE lead_stages
  ADD KEY ix_lead_stages_tenant (tenant_id),
  ADD KEY ix_lead_stages_sort (tenant_id, sort_order),
  ADD CONSTRAINT fk_lead_stages_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE leads
  ADD KEY ix_leads_tenant (tenant_id),
  ADD KEY ix_leads_stage (tenant_id, stage_id),
  ADD KEY ix_leads_owner (tenant_id, owner_user_id),
  ADD KEY ix_leads_assigned (tenant_id, assigned_user_id),
  ADD KEY ix_leads_contact (tenant_id, contact_id),
  ADD KEY ix_leads_status (tenant_id, status),
  ADD CONSTRAINT fk_leads_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_leads_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_leads_assigned FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_leads_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_leads_stage FOREIGN KEY (stage_id) REFERENCES lead_stages(id) ON DELETE SET NULL;

ALTER TABLE lead_notes
  ADD KEY ix_lead_notes_tenant (tenant_id),
  ADD KEY ix_lead_notes_lead (tenant_id, lead_id),
  ADD KEY ix_lead_notes_user (tenant_id, user_id),
  ADD CONSTRAINT fk_lead_notes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_lead_notes_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_lead_notes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE lead_stage_events
  ADD KEY ix_lead_events_tenant (tenant_id),
  ADD KEY ix_lead_events_lead (tenant_id, lead_id),
  ADD KEY ix_lead_events_to_stage (tenant_id, to_stage_id),
  ADD CONSTRAINT fk_lead_events_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_lead_events_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_lead_events_from_stage FOREIGN KEY (from_stage_id) REFERENCES lead_stages(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_lead_events_to_stage FOREIGN KEY (to_stage_id) REFERENCES lead_stages(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_lead_events_moved_by FOREIGN KEY (moved_by_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- integrations
ALTER TABLE integration_accounts
  ADD KEY ix_integration_accounts_tenant (tenant_id),
  ADD KEY ix_integration_accounts_provider (tenant_id, provider),
  ADD CONSTRAINT fk_integration_accounts_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE webhook_events
  ADD KEY ix_webhook_events_tenant (tenant_id),
  ADD KEY ix_webhook_events_provider (tenant_id, provider),
  ADD KEY ix_webhook_events_account (integration_account_id),
  ADD CONSTRAINT fk_webhook_events_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_webhook_events_account FOREIGN KEY (integration_account_id) REFERENCES integration_accounts(id) ON DELETE SET NULL;

-- chats
ALTER TABLE threads
  ADD KEY ix_threads_tenant (tenant_id),
  ADD KEY ix_threads_lead (tenant_id, lead_id),
  ADD KEY ix_threads_contact (tenant_id, contact_id),
  ADD KEY ix_threads_last_message (tenant_id, last_message_at),
  ADD CONSTRAINT fk_threads_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_threads_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_threads_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_threads_integration_account FOREIGN KEY (integration_account_id) REFERENCES integration_accounts(id) ON DELETE SET NULL;

ALTER TABLE messages
  ADD KEY ix_messages_tenant (tenant_id),
  ADD KEY ix_messages_thread (tenant_id, thread_id),
  ADD KEY ix_messages_created (tenant_id, created_at),
  ADD CONSTRAINT fk_messages_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_messages_thread FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_messages_sender_user FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_messages_sender_contact FOREIGN KEY (sender_contact_id) REFERENCES contacts(id) ON DELETE SET NULL;

-- video_calls
ALTER TABLE video_calls
  ADD KEY ix_video_calls_tenant (tenant_id),
  ADD KEY ix_video_calls_thread (tenant_id, thread_id),
  ADD UNIQUE KEY ux_video_calls_join_token (join_token),
  ADD CONSTRAINT fk_video_calls_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_video_calls_thread FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_video_calls_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE;

-- AI
ALTER TABLE ai_rules
  ADD KEY ix_ai_rules_tenant (tenant_id),
  ADD CONSTRAINT fk_ai_rules_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE ai_prompt_templates
  ADD KEY ix_ai_templates_tenant (tenant_id),
  ADD UNIQUE KEY ux_ai_templates_key_tenant (tenant_id, template_key),
  ADD CONSTRAINT fk_ai_templates_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

ALTER TABLE ai_suggestions
  ADD KEY ix_ai_suggestions_tenant (tenant_id),
  ADD KEY ix_ai_suggestions_thread (tenant_id, thread_id),
  ADD KEY ix_ai_suggestions_user (tenant_id, user_id),
  ADD CONSTRAINT fk_ai_suggestions_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_ai_suggestions_thread FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_ai_suggestions_message FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_ai_suggestions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Knowledge Base
ALTER TABLE knowledge_base_articles
  ADD KEY ix_kb_tenant (tenant_id),
  ADD KEY ix_kb_type (tenant_id, type),
  ADD CONSTRAINT fk_kb_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

-- Live coach
ALTER TABLE live_ai_coach_sessions
  ADD KEY ix_coach_tenant (tenant_id),
  ADD KEY ix_coach_thread (tenant_id, thread_id),
  ADD CONSTRAINT fk_coach_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_coach_thread FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_coach_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Calendar
ALTER TABLE calendar_events
  ADD KEY ix_calendar_tenant (tenant_id),
  ADD KEY ix_calendar_owner (tenant_id, owner_user_id),
  ADD KEY ix_calendar_starts (tenant_id, starts_at),
  ADD CONSTRAINT fk_calendar_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_calendar_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Lists
ALTER TABLE lists
  ADD KEY ix_lists_tenant (tenant_id),
  ADD KEY ix_lists_type (tenant_id, type),
  ADD CONSTRAINT fk_lists_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_lists_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE list_items
  ADD KEY ix_list_items_tenant (tenant_id),
  ADD KEY ix_list_items_list (tenant_id, list_id),
  ADD UNIQUE KEY ux_list_items_unique (list_id, entity_id),
  ADD CONSTRAINT fk_list_items_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_list_items_list FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE;

-- Mail
ALTER TABLE mail_messages
  ADD KEY ix_mail_tenant (tenant_id),
  ADD KEY ix_mail_status (tenant_id, status),
  ADD KEY ix_mail_lead (tenant_id, lead_id),
  ADD KEY ix_mail_contact (tenant_id, contact_id),
  ADD CONSTRAINT fk_mail_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_mail_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_mail_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL;

-- Settings
ALTER TABLE tenant_settings
  ADD UNIQUE KEY ux_tenant_settings_key (tenant_id, `key`),
  ADD CONSTRAINT fk_tenant_settings_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

-- Notifications
ALTER TABLE notifications
  ADD KEY ix_notifications_tenant (tenant_id),
  ADD KEY ix_notifications_user (tenant_id, user_id, is_read, created_at),
  ADD CONSTRAINT fk_notifications_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Audit
ALTER TABLE audit_logs
  ADD KEY ix_audit_tenant (tenant_id),
  ADD KEY ix_audit_actor (tenant_id, actor_user_id),
  ADD KEY ix_audit_action (tenant_id, action),
  ADD CONSTRAINT fk_audit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Queue + Sanctum
ALTER TABLE jobs
  ADD KEY ix_jobs_queue (queue);

ALTER TABLE failed_jobs
  ADD UNIQUE KEY ux_failed_jobs_uuid (uuid);

ALTER TABLE personal_access_tokens
  ADD UNIQUE KEY ux_personal_access_tokens_token (token),
  ADD KEY ix_personal_access_tokens_tokenable (tokenable_type, tokenable_id);

SET FOREIGN_KEY_CHECKS = 1;

