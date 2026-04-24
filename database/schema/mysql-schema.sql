/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `aarfs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aarfs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `onboarding_id` bigint unsigned DEFAULT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `aarf_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `it_manager_acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `it_manager_acknowledged_at` timestamp NULL DEFAULT NULL,
  `it_manager_user_id` bigint unsigned DEFAULT NULL,
  `it_manager_remarks` text COLLATE utf8mb4_unicode_ci,
  `it_notes` text COLLATE utf8mb4_unicode_ci,
  `asset_changes` text COLLATE utf8mb4_unicode_ci,
  `pending_asset_ids` json DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledgement_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aarfs_aarf_reference_unique` (`aarf_reference`),
  UNIQUE KEY `aarfs_acknowledgement_token_unique` (`acknowledgement_token`),
  KEY `aarfs_onboarding_id_foreign` (`onboarding_id`),
  KEY `aarfs_employee_id_foreign` (`employee_id`),
  CONSTRAINT `aarfs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `aarfs_onboarding_id_foreign` FOREIGN KEY (`onboarding_id`) REFERENCES `onboardings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_ai_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_ai_chat_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `role` enum('user','assistant','system') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `tokens_used` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_ai_chat_messages_session_id_foreign` (`session_id`),
  CONSTRAINT `acc_ai_chat_messages_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `acc_ai_chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_ai_chat_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_ai_chat_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New Conversation',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_ai_chat_sessions_user_id_foreign` (`user_id`),
  CONSTRAINT `acc_ai_chat_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_ai_invoice_scans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_ai_invoice_scans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','processing','completed','failed','reviewed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `extracted_data` json DEFAULT NULL,
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `bill_id` bigint unsigned DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_ai_invoice_scans_bill_id_foreign` (`bill_id`),
  KEY `acc_ai_invoice_scans_reviewed_by_foreign` (`reviewed_by`),
  KEY `acc_ai_invoice_scans_created_by_foreign` (`created_by`),
  CONSTRAINT `acc_ai_invoice_scans_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `acc_bills` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_ai_invoice_scans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_ai_invoice_scans_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_asset_depreciation_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_asset_depreciation_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fixed_asset_id` bigint unsigned NOT NULL,
  `period_date` date NOT NULL,
  `depreciation_amount` decimal(14,2) NOT NULL,
  `accumulated_depreciation` decimal(14,2) NOT NULL,
  `net_book_value` decimal(14,2) NOT NULL,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_asset_depreciation_entries_fixed_asset_id_foreign` (`fixed_asset_id`),
  KEY `acc_asset_depreciation_entries_journal_entry_id_foreign` (`journal_entry_id`),
  CONSTRAINT `acc_asset_depreciation_entries_fixed_asset_id_foreign` FOREIGN KEY (`fixed_asset_id`) REFERENCES `acc_fixed_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acc_asset_depreciation_entries_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `acc_journal_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_audit_trail` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` enum('create','update','delete','post','void','approve','print','export') COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_id` bigint unsigned NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_audit_trail_user_id_foreign` (`user_id`),
  KEY `acc_audit_trail_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `acc_audit_trail_company_created_at_index` (`company`,`created_at`),
  CONSTRAINT `acc_audit_trail_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_bank_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_branch` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `swift_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MYR',
  `opening_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `opening_balance_date` date DEFAULT NULL,
  `chart_of_account_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_bank_accounts_chart_of_account_id_foreign` (`chart_of_account_id`),
  CONSTRAINT `acc_bank_accounts_chart_of_account_id_foreign` FOREIGN KEY (`chart_of_account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_bank_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_bank_reconciliations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bank_account_id` bigint unsigned NOT NULL,
  `statement_date` date NOT NULL,
  `statement_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `reconciled_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `difference` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('in_progress','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_progress',
  `completed_by` bigint unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_bank_reconciliations_bank_account_id_foreign` (`bank_account_id`),
  KEY `acc_bank_reconciliations_completed_by_foreign` (`completed_by`),
  CONSTRAINT `acc_bank_reconciliations_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acc_bank_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acc_bank_reconciliations_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_bank_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_bank_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bank_account_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `debit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `running_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `is_reconciled` tinyint(1) NOT NULL DEFAULT '0',
  `reconciliation_id` bigint unsigned DEFAULT NULL,
  `source_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_bank_transactions_bank_account_id_date_index` (`bank_account_id`,`date`),
  KEY `acc_bank_transactions_source_type_source_id_index` (`source_type`,`source_id`),
  KEY `acc_bank_transactions_reconciliation_id_foreign` (`reconciliation_id`),
  CONSTRAINT `acc_bank_transactions_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acc_bank_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acc_bank_transactions_reconciliation_id_foreign` FOREIGN KEY (`reconciliation_id`) REFERENCES `acc_bank_reconciliations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_bank_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_bank_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_bank_account_id` bigint unsigned NOT NULL,
  `to_bank_account_id` bigint unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `date` date NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_bank_transfers_from_bank_account_id_foreign` (`from_bank_account_id`),
  KEY `acc_bank_transfers_to_bank_account_id_foreign` (`to_bank_account_id`),
  KEY `acc_bank_transfers_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `acc_bank_transfers_created_by_foreign` (`created_by`),
  CONSTRAINT `acc_bank_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_bank_transfers_from_bank_account_id_foreign` FOREIGN KEY (`from_bank_account_id`) REFERENCES `acc_bank_accounts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acc_bank_transfers_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `acc_journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_bank_transfers_to_bank_account_id_foreign` FOREIGN KEY (`to_bank_account_id`) REFERENCES `acc_bank_accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_bill_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_bill_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bill_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_code_id` bigint unsigned DEFAULT NULL,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_bill_items_bill_id_foreign` (`bill_id`),
  KEY `acc_bill_items_account_id_foreign` (`account_id`),
  KEY `acc_bill_items_tax_code_id_foreign` (`tax_code_id`),
  CONSTRAINT `acc_bill_items_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_bill_items_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `acc_bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acc_bill_items_tax_code_id_foreign` FOREIGN KEY (`tax_code_id`) REFERENCES `acc_tax_codes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_bills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `bill_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendor_bill_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date NOT NULL,
  `due_date` date NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(14,2) NOT NULL DEFAULT '0.00',
  `balance_due` decimal(14,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MYR',
  `exchange_rate` decimal(14,6) NOT NULL DEFAULT '1.000000',
  `status` enum('draft','received','partial','paid','overdue','void') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_bills_company_bill_number_unique` (`company`,`bill_number`),
  KEY `acc_bills_vendor_id_foreign` (`vendor_id`),
  KEY `acc_bills_created_by_foreign` (`created_by`),
  KEY `acc_bills_approved_by_foreign` (`approved_by`),
  KEY `acc_bills_company_status_index` (`company`,`status`),
  CONSTRAINT `acc_bills_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_bills_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_bills_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `acc_vendors` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_budget_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_budget_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `budget_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `fiscal_period_id` bigint unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_budget_lines_budget_id_account_id_fiscal_period_id_unique` (`budget_id`,`account_id`,`fiscal_period_id`),
  KEY `acc_budget_lines_fiscal_period_id_foreign` (`fiscal_period_id`),
  KEY `acc_budget_lines_account_id_foreign` (`account_id`),
  CONSTRAINT `acc_budget_lines_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acc_budget_lines_budget_id_foreign` FOREIGN KEY (`budget_id`) REFERENCES `acc_budgets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acc_budget_lines_fiscal_period_id_foreign` FOREIGN KEY (`fiscal_period_id`) REFERENCES `acc_fiscal_periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_budgets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fiscal_year_id` bigint unsigned NOT NULL,
  `status` enum('draft','active','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_budgets_fiscal_year_id_foreign` (`fiscal_year_id`),
  KEY `acc_budgets_created_by_foreign` (`created_by`),
  KEY `acc_budgets_approved_by_foreign` (`approved_by`),
  CONSTRAINT `acc_budgets_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_budgets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_budgets_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `acc_fiscal_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_chart_of_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('asset','liability','equity','revenue','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_type` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `normal_balance` enum('debit','credit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `opening_balance_date` date DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MYR',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `allow_direct_posting` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_chart_of_accounts_company_account_code_unique` (`company`,`account_code`),
  KEY `acc_chart_of_accounts_parent_id_foreign` (`parent_id`),
  KEY `acc_chart_of_accounts_company_type_index` (`company`,`type`),
  CONSTRAINT `acc_chart_of_accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_credit_note_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_credit_note_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_code_id` bigint unsigned DEFAULT NULL,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_credit_note_items_credit_note_id_foreign` (`credit_note_id`),
  KEY `acc_credit_note_items_account_id_foreign` (`account_id`),
  KEY `acc_credit_note_items_tax_code_id_foreign` (`tax_code_id`),
  CONSTRAINT `acc_credit_note_items_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_credit_note_items_credit_note_id_foreign` FOREIGN KEY (`credit_note_id`) REFERENCES `acc_credit_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acc_credit_note_items_tax_code_id_foreign` FOREIGN KEY (`tax_code_id`) REFERENCES `acc_tax_codes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_credit_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `credit_note_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('draft','applied','void') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_credit_notes_company_credit_note_number_unique` (`company`,`credit_note_number`),
  KEY `acc_credit_notes_customer_id_foreign` (`customer_id`),
  KEY `acc_credit_notes_invoice_id_foreign` (`invoice_id`),
  KEY `acc_credit_notes_created_by_foreign` (`created_by`),
  CONSTRAINT `acc_credit_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_credit_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `acc_customers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acc_credit_notes_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `acc_sales_invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exchange_rate` decimal(14,6) NOT NULL DEFAULT '1.000000',
  `is_base` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_currencies_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_customer_payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_customer_payment_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_payment_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_customer_payment_allocations_customer_payment_id_foreign` (`customer_payment_id`),
  KEY `acc_customer_payment_allocations_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `acc_customer_payment_allocations_customer_payment_id_foreign` FOREIGN KEY (`customer_payment_id`) REFERENCES `acc_customer_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acc_customer_payment_allocations_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `acc_sales_invoices` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_customer_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_customer_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `payment_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','cheque','credit_card','online','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bank_transfer',
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_customer_payments_company_payment_number_unique` (`company`,`payment_number`),
  KEY `acc_customer_payments_customer_id_foreign` (`customer_id`),
  KEY `acc_customer_payments_created_by_foreign` (`created_by`),
  KEY `acc_customer_payments_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `acc_customer_payments_bank_account_id_foreign` (`bank_account_id`),
  CONSTRAINT `acc_customer_payments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acc_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_customer_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_customer_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `acc_customers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acc_customer_payments_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `acc_journal_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Malaysia',
  `tax_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_limit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `payment_terms_days` smallint unsigned NOT NULL DEFAULT '30',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MYR',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_customers_company_customer_code_unique` (`company`,`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_fiscal_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_fiscal_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fiscal_year_id` bigint unsigned NOT NULL,
  `period_number` tinyint unsigned NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('open','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_fiscal_periods_fiscal_year_id_foreign` (`fiscal_year_id`),
  CONSTRAINT `acc_fiscal_periods_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `acc_fiscal_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_fiscal_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_fiscal_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('open','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_fixed_asset_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_fixed_asset_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `depreciation_method` enum('straight_line','declining_balance','sum_of_years') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'straight_line',
  `useful_life_years` smallint unsigned NOT NULL DEFAULT '5',
  `asset_account_id` bigint unsigned DEFAULT NULL,
  `depreciation_expense_account_id` bigint unsigned DEFAULT NULL,
  `accumulated_depreciation_account_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_fixed_asset_categories_asset_account_id_foreign` (`asset_account_id`),
  KEY `acc_fac_depr_expense_acct_foreign` (`depreciation_expense_account_id`),
  KEY `acc_fac_accum_depr_acct_foreign` (`accumulated_depreciation_account_id`),
  CONSTRAINT `acc_fac_accum_depr_acct_foreign` FOREIGN KEY (`accumulated_depreciation_account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_fac_depr_expense_acct_foreign` FOREIGN KEY (`depreciation_expense_account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_fixed_asset_categories_asset_account_id_foreign` FOREIGN KEY (`asset_account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_fixed_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_fixed_assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` bigint unsigned NOT NULL,
  `asset_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `purchase_date` date NOT NULL,
  `purchase_cost` decimal(14,2) NOT NULL,
  `residual_value` decimal(14,2) NOT NULL DEFAULT '0.00',
  `useful_life_months` smallint unsigned NOT NULL,
  `depreciation_method` enum('straight_line','declining_balance','sum_of_years') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'straight_line',
  `status` enum('active','disposed','fully_depreciated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `disposal_date` date DEFAULT NULL,
  `disposal_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `accumulated_depreciation` decimal(14,2) NOT NULL DEFAULT '0.00',
  `net_book_value` decimal(14,2) NOT NULL DEFAULT '0.00',
  `vendor_id` bigint unsigned DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_fixed_assets_company_asset_code_unique` (`company`,`asset_code`),
  KEY `acc_fixed_assets_category_id_foreign` (`category_id`),
  KEY `acc_fixed_assets_vendor_id_foreign` (`vendor_id`),
  CONSTRAINT `acc_fixed_assets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `acc_fixed_asset_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acc_fixed_assets_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `acc_vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_journal_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entry_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','posted','void') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `source_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `posted_by` bigint unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `reversed_by_entry_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_journal_entries_company_entry_number_unique` (`company`,`entry_number`),
  KEY `acc_journal_entries_posted_by_foreign` (`posted_by`),
  KEY `acc_journal_entries_created_by_foreign` (`created_by`),
  KEY `acc_journal_entries_company_date_index` (`company`,`date`),
  KEY `acc_journal_entries_source_type_source_id_index` (`source_type`,`source_id`),
  CONSTRAINT `acc_journal_entries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_journal_entries_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_journal_entry_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_journal_entry_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `debit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_code_id` bigint unsigned DEFAULT NULL,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MYR',
  `exchange_rate` decimal(14,6) NOT NULL DEFAULT '1.000000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_journal_entry_lines_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `acc_journal_entry_lines_tax_code_id_foreign` (`tax_code_id`),
  KEY `acc_journal_entry_lines_account_id_index` (`account_id`),
  CONSTRAINT `acc_journal_entry_lines_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acc_journal_entry_lines_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `acc_journal_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acc_journal_entry_lines_tax_code_id_foreign` FOREIGN KEY (`tax_code_id`) REFERENCES `acc_tax_codes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_purchase_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_code_id` bigint unsigned DEFAULT NULL,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `received_quantity` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `acc_purchase_order_items_account_id_foreign` (`account_id`),
  KEY `acc_purchase_order_items_tax_code_id_foreign` (`tax_code_id`),
  CONSTRAINT `acc_purchase_order_items_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `acc_purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acc_purchase_order_items_tax_code_id_foreign` FOREIGN KEY (`tax_code_id`) REFERENCES `acc_tax_codes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_purchase_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `po_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('draft','sent','partial','received','closed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_purchase_orders_company_po_number_unique` (`company`,`po_number`),
  KEY `acc_purchase_orders_vendor_id_foreign` (`vendor_id`),
  KEY `acc_purchase_orders_created_by_foreign` (`created_by`),
  KEY `acc_purchase_orders_approved_by_foreign` (`approved_by`),
  CONSTRAINT `acc_purchase_orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_purchase_orders_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `acc_vendors` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_recurring_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_recurring_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('invoice','bill','journal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` enum('daily','weekly','monthly','quarterly','yearly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `next_run_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `template_data` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_recurring_templates_created_by_foreign` (`created_by`),
  CONSTRAINT `acc_recurring_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_sales_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_sales_invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sales_invoice_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(6,2) NOT NULL DEFAULT '0.00',
  `tax_code_id` bigint unsigned DEFAULT NULL,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_sales_invoice_items_sales_invoice_id_foreign` (`sales_invoice_id`),
  KEY `acc_sales_invoice_items_account_id_foreign` (`account_id`),
  KEY `acc_sales_invoice_items_tax_code_id_foreign` (`tax_code_id`),
  CONSTRAINT `acc_sales_invoice_items_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_sales_invoice_items_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `acc_sales_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acc_sales_invoice_items_tax_code_id_foreign` FOREIGN KEY (`tax_code_id`) REFERENCES `acc_tax_codes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_sales_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_sales_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `invoice_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `due_date` date NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `discount_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(14,2) NOT NULL DEFAULT '0.00',
  `balance_due` decimal(14,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MYR',
  `exchange_rate` decimal(14,6) NOT NULL DEFAULT '1.000000',
  `status` enum('draft','sent','partial','paid','overdue','void') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `terms` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_sales_invoices_company_invoice_number_unique` (`company`,`invoice_number`),
  KEY `acc_sales_invoices_created_by_foreign` (`created_by`),
  KEY `acc_sales_invoices_approved_by_foreign` (`approved_by`),
  KEY `acc_sales_invoices_company_status_index` (`company`,`status`),
  KEY `acc_sales_invoices_customer_id_status_index` (`customer_id`,`status`),
  CONSTRAINT `acc_sales_invoices_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_sales_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_sales_invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `acc_customers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fiscal_year_start_month` tinyint unsigned NOT NULL DEFAULT '1',
  `base_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MYR',
  `tax_registration_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_type` enum('sst','gst') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sst',
  `invoice_prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INV-',
  `credit_note_prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CN-',
  `bill_prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BILL-',
  `po_prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PO-',
  `journal_prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'JE-',
  `payment_prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PAY-',
  `receipt_prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'REC-',
  `next_invoice_number` bigint unsigned NOT NULL DEFAULT '1',
  `next_credit_note_number` bigint unsigned NOT NULL DEFAULT '1',
  `next_bill_number` bigint unsigned NOT NULL DEFAULT '1',
  `next_po_number` bigint unsigned NOT NULL DEFAULT '1',
  `next_journal_number` bigint unsigned NOT NULL DEFAULT '1',
  `next_payment_number` bigint unsigned NOT NULL DEFAULT '1',
  `next_receipt_number` bigint unsigned NOT NULL DEFAULT '1',
  `default_payment_terms_days` smallint unsigned NOT NULL DEFAULT '30',
  `default_tax_code_id` bigint unsigned DEFAULT NULL,
  `default_sales_account_id` bigint unsigned DEFAULT NULL,
  `default_purchase_account_id` bigint unsigned DEFAULT NULL,
  `retained_earnings_account_id` bigint unsigned DEFAULT NULL,
  `enable_multi_currency` tinyint(1) NOT NULL DEFAULT '0',
  `ai_provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'openai',
  `ai_api_key` text COLLATE utf8mb4_unicode_ci,
  `ai_model` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gpt-4o',
  `ollama_base_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_settings_company_unique` (`company`),
  KEY `acc_settings_default_tax_code_id_foreign` (`default_tax_code_id`),
  KEY `acc_settings_default_sales_account_id_foreign` (`default_sales_account_id`),
  KEY `acc_settings_default_purchase_account_id_foreign` (`default_purchase_account_id`),
  KEY `acc_settings_retained_earnings_account_id_foreign` (`retained_earnings_account_id`),
  CONSTRAINT `acc_settings_default_purchase_account_id_foreign` FOREIGN KEY (`default_purchase_account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_settings_default_sales_account_id_foreign` FOREIGN KEY (`default_sales_account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_settings_default_tax_code_id_foreign` FOREIGN KEY (`default_tax_code_id`) REFERENCES `acc_tax_codes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_settings_retained_earnings_account_id_foreign` FOREIGN KEY (`retained_earnings_account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_tax_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_tax_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` decimal(6,3) NOT NULL DEFAULT '0.000',
  `type` enum('sst_sales','sst_service','gst','wht','income_tax','exempt','zero_rated','out_of_scope') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `purchase_account_id` bigint unsigned DEFAULT NULL,
  `sales_account_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_tax_codes_company_code_unique` (`company`,`code`),
  KEY `acc_tax_codes_purchase_account_id_foreign` (`purchase_account_id`),
  KEY `acc_tax_codes_sales_account_id_foreign` (`sales_account_id`),
  CONSTRAINT `acc_tax_codes_purchase_account_id_foreign` FOREIGN KEY (`purchase_account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_tax_codes_sales_account_id_foreign` FOREIGN KEY (`sales_account_id`) REFERENCES `acc_chart_of_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_tax_return_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_tax_return_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tax_return_id` bigint unsigned NOT NULL,
  `tax_code_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `taxable_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_tax_return_lines_tax_return_id_foreign` (`tax_return_id`),
  KEY `acc_tax_return_lines_tax_code_id_foreign` (`tax_code_id`),
  CONSTRAINT `acc_tax_return_lines_tax_code_id_foreign` FOREIGN KEY (`tax_code_id`) REFERENCES `acc_tax_codes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_tax_return_lines_tax_return_id_foreign` FOREIGN KEY (`tax_return_id`) REFERENCES `acc_tax_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_tax_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_tax_returns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `return_type` enum('sst02','cp204','cp207','wht','pcb_monthly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `total_output_tax` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_input_tax` decimal(14,2) NOT NULL DEFAULT '0.00',
  `net_tax_payable` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('draft','filed','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `filed_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_tax_returns_created_by_foreign` (`created_by`),
  KEY `acc_tax_returns_company_return_type_period_start_index` (`company`,`return_type`,`period_start`),
  CONSTRAINT `acc_tax_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_vendor_payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_vendor_payment_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_payment_id` bigint unsigned NOT NULL,
  `bill_id` bigint unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_vendor_payment_allocations_vendor_payment_id_foreign` (`vendor_payment_id`),
  KEY `acc_vendor_payment_allocations_bill_id_foreign` (`bill_id`),
  CONSTRAINT `acc_vendor_payment_allocations_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `acc_bills` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acc_vendor_payment_allocations_vendor_payment_id_foreign` FOREIGN KEY (`vendor_payment_id`) REFERENCES `acc_vendor_payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_vendor_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_vendor_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `payment_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','cheque','credit_card','online','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bank_transfer',
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_vendor_payments_company_payment_number_unique` (`company`,`payment_number`),
  KEY `acc_vendor_payments_vendor_id_foreign` (`vendor_id`),
  KEY `acc_vendor_payments_created_by_foreign` (`created_by`),
  KEY `acc_vendor_payments_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `acc_vendor_payments_bank_account_id_foreign` (`bank_account_id`),
  CONSTRAINT `acc_vendor_payments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acc_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_vendor_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_vendor_payments_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `acc_journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acc_vendor_payments_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `acc_vendors` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acc_vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acc_vendors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Malaysia',
  `tax_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_terms_days` smallint unsigned NOT NULL DEFAULT '30',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MYR',
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_swift` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_vendors_company_vendor_code_unique` (`company`,`vendor_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `companies` json DEFAULT NULL,
  `attachment_paths` json DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_created_by_foreign` (`created_by`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `onboarding_id` bigint unsigned DEFAULT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `asset_inventory_id` bigint unsigned NOT NULL,
  `assigned_date` date NOT NULL,
  `returned_date` date DEFAULT NULL,
  `status` enum('assigned','returned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assigned',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_assignments_onboarding_id_foreign` (`onboarding_id`),
  KEY `asset_assignments_asset_inventory_id_foreign` (`asset_inventory_id`),
  KEY `asset_assignments_employee_id_foreign` (`employee_id`),
  CONSTRAINT `asset_assignments_asset_inventory_id_foreign` FOREIGN KEY (`asset_inventory_id`) REFERENCES `asset_inventories` (`id`),
  CONSTRAINT `asset_assignments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_assignments_onboarding_id_foreign` FOREIGN KEY (`onboarding_id`) REFERENCES `onboardings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_tag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asset_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `brand` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `processor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ram_size` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operating_system` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `screen_size` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spec_others` text COLLATE utf8mb4_unicode_ci,
  `purchase_date` date DEFAULT NULL,
  `purchase_vendor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `warranty_expiry_date` date DEFAULT NULL,
  `invoice_document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_documents` json DEFAULT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_supplied_to` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ownership_type` enum('company','rental') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'company',
  `rental_vendor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rental_vendor_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rental_cost_per_month` decimal(10,2) DEFAULT NULL,
  `rental_start_date` date DEFAULT NULL,
  `rental_end_date` date DEFAULT NULL,
  `rental_contract_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rental_contract_documents` json DEFAULT NULL,
  `assigned_employee_id` bigint unsigned DEFAULT NULL,
  `asset_assigned_date` date DEFAULT NULL,
  `expected_return_date` date DEFAULT NULL,
  `asset_condition` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'good',
  `maintenance_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `asset_photos` json DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `asset_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_inventories_asset_tag_unique` (`asset_tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_provisionings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_provisionings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `onboarding_id` bigint unsigned NOT NULL,
  `laptop_provision` tinyint(1) NOT NULL DEFAULT '0',
  `monitor_set` tinyint(1) NOT NULL DEFAULT '0',
  `converter` tinyint(1) NOT NULL DEFAULT '0',
  `company_phone` tinyint(1) NOT NULL DEFAULT '0',
  `sim_card` tinyint(1) NOT NULL DEFAULT '0',
  `access_card_request` tinyint(1) NOT NULL DEFAULT '0',
  `office_keys` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `others` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_provisionings_onboarding_id_foreign` (`onboarding_id`),
  CONSTRAINT `asset_provisionings_onboarding_id_foreign` FOREIGN KEY (`onboarding_id`) REFERENCES `onboardings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `clock_in` timestamp NULL DEFAULT NULL,
  `clock_out` timestamp NULL DEFAULT NULL,
  `work_hours` decimal(5,2) DEFAULT NULL,
  `overtime_hours` decimal(5,2) NOT NULL DEFAULT '0.00',
  `break_duration` decimal(5,2) NOT NULL DEFAULT '0.00',
  `status` enum('present','absent','late','half_day','on_leave','holiday') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `clock_in_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_out_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `work_schedule_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_records_employee_id_date_unique` (`employee_id`,`date`),
  KEY `attendance_records_work_schedule_id_foreign` (`work_schedule_id`),
  CONSTRAINT `attendance_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_records_work_schedule_id_foreign` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `registration_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kwsp_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tin_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `socso_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eis_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dispose_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispose_assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_inventory_id` bigint unsigned NOT NULL,
  `asset_tag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asset_condition` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_good',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disposed_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disposed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dispose_assets_asset_inventory_id_foreign` (`asset_inventory_id`),
  CONSTRAINT `dispose_assets_asset_inventory_id_foreign` FOREIGN KEY (`asset_inventory_id`) REFERENCES `asset_inventories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ea_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ea_forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `year` smallint unsigned NOT NULL,
  `employer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_tax_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_tax_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_ic_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_epf_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_socso_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_start_date` date DEFAULT NULL,
  `employment_end_date` date DEFAULT NULL,
  `gross_salary` decimal(14,2) NOT NULL DEFAULT '0.00',
  `overtime_pay` decimal(14,2) NOT NULL DEFAULT '0.00',
  `commission` decimal(14,2) NOT NULL DEFAULT '0.00',
  `allowances` decimal(14,2) NOT NULL DEFAULT '0.00',
  `gross_remuneration` decimal(14,2) NOT NULL DEFAULT '0.00',
  `benefits_in_kind` decimal(14,2) NOT NULL DEFAULT '0.00',
  `value_of_living_accommodation` decimal(14,2) NOT NULL DEFAULT '0.00',
  `pension_or_annuity` decimal(14,2) NOT NULL DEFAULT '0.00',
  `gratuity` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_remuneration` decimal(14,2) NOT NULL DEFAULT '0.00',
  `epf_employee` decimal(14,2) NOT NULL DEFAULT '0.00',
  `socso_employee` decimal(14,2) NOT NULL DEFAULT '0.00',
  `eis_employee` decimal(14,2) NOT NULL DEFAULT '0.00',
  `pcb_paid` decimal(14,2) NOT NULL DEFAULT '0.00',
  `zakat` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(14,2) NOT NULL DEFAULT '0.00',
  `epf_employer` decimal(14,2) NOT NULL DEFAULT '0.00',
  `socso_employer` decimal(14,2) NOT NULL DEFAULT '0.00',
  `eis_employer` decimal(14,2) NOT NULL DEFAULT '0.00',
  `hrdf_employer` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('draft','finalized') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `generated_by` bigint unsigned DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ea_forms_employee_id_year_unique` (`employee_id`,`year`),
  KEY `ea_forms_generated_by_foreign` (`generated_by`),
  CONSTRAINT `ea_forms_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ea_forms_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_child_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_child_registrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `cat_a_100` tinyint unsigned NOT NULL DEFAULT '0',
  `cat_a_50` tinyint unsigned NOT NULL DEFAULT '0',
  `cat_b_100` tinyint unsigned NOT NULL DEFAULT '0',
  `cat_b_50` tinyint unsigned NOT NULL DEFAULT '0',
  `cat_c_100` tinyint unsigned NOT NULL DEFAULT '0',
  `cat_c_50` tinyint unsigned NOT NULL DEFAULT '0',
  `cat_d_100` tinyint unsigned NOT NULL DEFAULT '0',
  `cat_d_50` tinyint unsigned NOT NULL DEFAULT '0',
  `cat_e_100` tinyint unsigned NOT NULL DEFAULT '0',
  `cat_e_50` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_child_registrations_employee_id_unique` (`employee_id`),
  CONSTRAINT `employee_child_registrations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `uploaded_by` bigint unsigned NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `notes` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_contracts_employee_id_foreign` (`employee_id`),
  KEY `employee_contracts_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `employee_contracts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_contracts_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_edit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_edit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `edited_by_user_id` bigint unsigned DEFAULT NULL,
  `edited_by_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edited_by_role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sections_changed` json DEFAULT NULL,
  `change_notes` text COLLATE utf8mb4_unicode_ci,
  `consent_required` tinyint(1) NOT NULL DEFAULT '0',
  `consent_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consent_token_expires_at` timestamp NULL DEFAULT NULL,
  `consent_requested_at` timestamp NULL DEFAULT NULL,
  `consent_sent_to_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acknowledged_by_user_id` bigint unsigned DEFAULT NULL,
  `acknowledged_by_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledgement_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_edit_logs_consent_token_unique` (`consent_token`),
  KEY `employee_edit_logs_employee_id_foreign` (`employee_id`),
  KEY `employee_edit_logs_edited_by_user_id_foreign` (`edited_by_user_id`),
  KEY `employee_edit_logs_acknowledged_by_user_id_foreign` (`acknowledged_by_user_id`),
  CONSTRAINT `employee_edit_logs_acknowledged_by_user_id_foreign` FOREIGN KEY (`acknowledged_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_edit_logs_edited_by_user_id_foreign` FOREIGN KEY (`edited_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_edit_logs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_education_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_education_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `qualification` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `institution` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_graduated` year DEFAULT NULL,
  `years_experience` smallint unsigned DEFAULT NULL,
  `certificate_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_paths` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_education_histories_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_education_histories_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_emergency_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `contact_order` tinyint unsigned NOT NULL DEFAULT '1',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationship` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_emergency_contacts_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_emergency_contacts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned DEFAULT NULL,
  `onboarding_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `official_document_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('male','female') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `race` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `residential_address` text COLLATE utf8mb4_unicode_ci,
  `personal_contact_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personal_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reporting_manager` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `exit_date` date DEFAULT NULL,
  `employment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exit_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exit_remarks` text COLLATE utf8mb4_unicode_ci,
  `archived_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_salaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bank_transfer',
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_until` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `working_days_per_month` smallint unsigned NOT NULL DEFAULT '26',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_salaries_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_salaries_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_salary_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_salary_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_salary_id` bigint unsigned NOT NULL,
  `payroll_item_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_salary_items_employee_salary_id_foreign` (`employee_salary_id`),
  KEY `employee_salary_items_payroll_item_id_foreign` (`payroll_item_id`),
  CONSTRAINT `employee_salary_items_employee_salary_id_foreign` FOREIGN KEY (`employee_salary_id`) REFERENCES `employee_salaries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_salary_items_payroll_item_id_foreign` FOREIGN KEY (`payroll_item_id`) REFERENCES `payroll_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_spouse_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_spouse_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `nric_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `income_tax_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_working` tinyint(1) NOT NULL DEFAULT '0',
  `is_disabled` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_spouse_details_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_spouse_details_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `onboarding_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `active_from` date NOT NULL,
  `active_until` date DEFAULT NULL,
  `employee_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `official_document_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('male','female') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `race` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_disabled` tinyint(1) NOT NULL DEFAULT '0',
  `residential_address` text COLLATE utf8mb4_unicode_ci,
  `personal_contact_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `house_tel_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personal_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `epf_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `income_tax_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `socso_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `epf_category` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '1',
  `is_resident` tinyint(1) NOT NULL DEFAULT '1',
  `nationality` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nric_file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nric_file_paths` json DEFAULT NULL,
  `consent_given_at` timestamp NULL DEFAULT NULL,
  `consent_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reporting_manager` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_id` bigint unsigned DEFAULT NULL,
  `reporting_manager_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `exit_date` date DEFAULT NULL,
  `last_salary_date` date DEFAULT NULL,
  `confirmation_date` date DEFAULT NULL,
  `employment_status` enum('active','resigned','terminated','contract_ended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `resignation_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `employment_type` enum('permanent','intern','contract') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aarf_file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `handbook_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orientation_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_onboarding_id_foreign` (`onboarding_id`),
  KEY `employees_user_id_foreign` (`user_id`),
  KEY `employees_manager_id_foreign` (`manager_id`),
  CONSTRAINT `employees_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_onboarding_id_foreign` FOREIGN KEY (`onboarding_id`) REFERENCES `onboardings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `monthly_limit` decimal(10,2) DEFAULT NULL,
  `requires_receipt` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `keywords` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_categories_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expense_claim_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_claim_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_claim_id` bigint unsigned NOT NULL,
  `expense_category_id` bigint unsigned NOT NULL,
  `expense_date` date NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_client` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gst_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_with_gst` decimal(10,2) NOT NULL,
  `receipt_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA-256 hash of receipt file for duplicate detection',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_claim_items_expense_claim_id_foreign` (`expense_claim_id`),
  KEY `expense_claim_items_expense_category_id_foreign` (`expense_category_id`),
  KEY `expense_claim_items_receipt_hash_index` (`receipt_hash`),
  CONSTRAINT `expense_claim_items_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`),
  CONSTRAINT `expense_claim_items_expense_claim_id_foreign` FOREIGN KEY (`expense_claim_id`) REFERENCES `expense_claims` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expense_claim_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_claim_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submission_deadline_day` tinyint unsigned NOT NULL DEFAULT '20',
  `require_manager_approval` tinyint(1) NOT NULL DEFAULT '1',
  `require_hr_approval` tinyint(1) NOT NULL DEFAULT '1',
  `auto_approve_below` decimal(10,2) DEFAULT NULL,
  `reminder_days_before` tinyint unsigned NOT NULL DEFAULT '3',
  `gst_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `gst_rate` decimal(5,2) NOT NULL DEFAULT '8.00',
  `general_rules` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expense_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_claims` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `claim_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` year NOT NULL,
  `month` tinyint unsigned NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_gst` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_with_gst` decimal(12,2) NOT NULL DEFAULT '0.00',
  `item_count` int unsigned NOT NULL DEFAULT '0',
  `status` enum('draft','submitted','manager_approved','manager_rejected','hr_approved','hr_rejected','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `submitted_at` date DEFAULT NULL,
  `submission_deadline` date DEFAULT NULL,
  `manager_id` bigint unsigned DEFAULT NULL,
  `manager_approved_by` bigint unsigned DEFAULT NULL,
  `manager_approved_at` timestamp NULL DEFAULT NULL,
  `manager_remarks` text COLLATE utf8mb4_unicode_ci,
  `hr_approved_by` bigint unsigned DEFAULT NULL,
  `hr_approved_at` timestamp NULL DEFAULT NULL,
  `hr_remarks` text COLLATE utf8mb4_unicode_ci,
  `payslip_id` bigint unsigned DEFAULT NULL,
  `pay_run_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_claims_claim_number_unique` (`claim_number`),
  KEY `expense_claims_manager_id_foreign` (`manager_id`),
  KEY `expense_claims_manager_approved_by_foreign` (`manager_approved_by`),
  KEY `expense_claims_hr_approved_by_foreign` (`hr_approved_by`),
  KEY `expense_claims_payslip_id_foreign` (`payslip_id`),
  KEY `expense_claims_pay_run_id_foreign` (`pay_run_id`),
  KEY `expense_claims_employee_id_index` (`employee_id`),
  KEY `expense_claims_employee_id_year_month_index` (`employee_id`,`year`,`month`),
  CONSTRAINT `expense_claims_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expense_claims_hr_approved_by_foreign` FOREIGN KEY (`hr_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_claims_manager_approved_by_foreign` FOREIGN KEY (`manager_approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_claims_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_claims_pay_run_id_foreign` FOREIGN KEY (`pay_run_id`) REFERENCES `pay_runs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_claims_payslip_id_foreign` FOREIGN KEY (`payslip_id`) REFERENCES `payslips` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `it_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `it_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `onboarding_id` bigint unsigned DEFAULT NULL,
  `offboarding_id` bigint unsigned DEFAULT NULL,
  `assigned_to` bigint unsigned NOT NULL,
  `assigned_by` bigint unsigned NOT NULL,
  `task_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','in_progress','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `it_tasks_onboarding_id_foreign` (`onboarding_id`),
  KEY `it_tasks_assigned_to_foreign` (`assigned_to`),
  KEY `it_tasks_assigned_by_foreign` (`assigned_by`),
  KEY `it_tasks_offboarding_id_foreign` (`offboarding_id`),
  CONSTRAINT `it_tasks_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `it_tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `it_tasks_offboarding_id_foreign` FOREIGN KEY (`offboarding_id`) REFERENCES `offboardings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `it_tasks_onboarding_id_foreign` FOREIGN KEY (`onboarding_id`) REFERENCES `onboardings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `leave_type_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(5,1) NOT NULL,
  `is_half_day` tinyint(1) NOT NULL DEFAULT '0',
  `half_day_period` enum('morning','afternoon') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint unsigned DEFAULT NULL,
  `manager_approved_by` bigint unsigned DEFAULT NULL,
  `manager_approved_at` datetime DEFAULT NULL,
  `manager_remarks` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_applications_employee_id_foreign` (`employee_id`),
  KEY `leave_applications_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_applications_approved_by_foreign` (`approved_by`),
  CONSTRAINT `leave_applications_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_applications_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_applications_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `leave_type_id` bigint unsigned NOT NULL,
  `year` year NOT NULL,
  `entitled` decimal(5,1) NOT NULL DEFAULT '0.0',
  `taken` decimal(5,1) NOT NULL DEFAULT '0.0',
  `carry_forward` decimal(5,1) NOT NULL DEFAULT '0.0',
  `adjustment` decimal(5,1) NOT NULL DEFAULT '0.0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_balances_employee_id_leave_type_id_year_unique` (`employee_id`,`leave_type_id`,`year`),
  KEY `leave_balances_leave_type_id_foreign` (`leave_type_id`),
  CONSTRAINT `leave_balances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_balances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_entitlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_entitlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `leave_type_id` bigint unsigned NOT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_tenure_months` int NOT NULL DEFAULT '0',
  `max_tenure_months` int DEFAULT NULL,
  `entitled_days` decimal(5,1) NOT NULL,
  `carry_forward_limit` decimal(5,1) NOT NULL DEFAULT '0.0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_entitlements_leave_type_id_foreign` (`leave_type_id`),
  CONSTRAINT `leave_entitlements_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_paid` tinyint(1) NOT NULL DEFAULT '1',
  `requires_attachment` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `offboardings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `offboardings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `onboarding_id` bigint unsigned DEFAULT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reporting_manager_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personal_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exit_date` date DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `calendar_reminder_status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `exiting_email_status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `aarf_status` enum('pending','in_progress','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `asset_cleaning_status` enum('pending','in_progress','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `deactivation_status` enum('pending','in_progress','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notice_email_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reminder_email_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `week_reminder_email_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `sendoff_email_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `assigned_pic_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offboardings_employee_id_foreign` (`employee_id`),
  KEY `offboardings_onboarding_id_foreign` (`onboarding_id`),
  KEY `offboardings_assigned_pic_user_id_foreign` (`assigned_pic_user_id`),
  CONSTRAINT `offboardings_assigned_pic_user_id_foreign` FOREIGN KEY (`assigned_pic_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offboardings_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offboardings_onboarding_id_foreign` FOREIGN KEY (`onboarding_id`) REFERENCES `onboardings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `onboarding_edit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_edit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `onboarding_id` bigint unsigned NOT NULL,
  `edited_by_user_id` bigint unsigned DEFAULT NULL,
  `edited_by_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `edited_by_role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sections_changed` json DEFAULT NULL,
  `change_notes` text COLLATE utf8mb4_unicode_ci,
  `consent_required` tinyint(1) NOT NULL DEFAULT '0',
  `consent_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consent_token_expires_at` timestamp NULL DEFAULT NULL,
  `consent_requested_at` timestamp NULL DEFAULT NULL,
  `consent_sent_to_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acknowledged_by_user_id` bigint unsigned DEFAULT NULL,
  `acknowledged_by_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledgement_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `onboarding_edit_logs_consent_token_unique` (`consent_token`),
  KEY `onboarding_edit_logs_onboarding_id_foreign` (`onboarding_id`),
  KEY `onboarding_edit_logs_edited_by_user_id_foreign` (`edited_by_user_id`),
  KEY `onboarding_edit_logs_acknowledged_by_user_id_foreign` (`acknowledged_by_user_id`),
  CONSTRAINT `onboarding_edit_logs_acknowledged_by_user_id_foreign` FOREIGN KEY (`acknowledged_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `onboarding_edit_logs_edited_by_user_id_foreign` FOREIGN KEY (`edited_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `onboarding_edit_logs_onboarding_id_foreign` FOREIGN KEY (`onboarding_id`) REFERENCES `onboardings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `onboardings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboardings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `invite_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invite_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invite_expires_at` timestamp NULL DEFAULT NULL,
  `invite_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `hr_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hr_emails` json DEFAULT NULL,
  `it_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `it_emails` json DEFAULT NULL,
  `calendar_invite_sent` tinyint(1) NOT NULL DEFAULT '0',
  `welcome_email_sent` tinyint(1) NOT NULL DEFAULT '0',
  `assigned_pic_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User ID of assigned IT staff member',
  `asset_preparation_status` enum('pending','in_progress','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `work_email_status` enum('pending','in_progress','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `assigned_pic_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `overtime_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overtime_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `multiplier` decimal(3,1) NOT NULL DEFAULT '1.5',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `overtime_requests_employee_id_foreign` (`employee_id`),
  KEY `overtime_requests_approved_by_foreign` (`approved_by`),
  CONSTRAINT `overtime_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `overtime_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pay_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pay_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` year NOT NULL,
  `month` tinyint unsigned NOT NULL,
  `pay_date` date NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` enum('draft','processing','approved','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `total_gross` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_net` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_employer_cost` decimal(14,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pay_runs_reference_unique` (`reference`),
  UNIQUE KEY `pay_runs_company_year_month_unique` (`company`,`year`,`month`),
  KEY `pay_runs_created_by_foreign` (`created_by`),
  KEY `pay_runs_approved_by_foreign` (`approved_by`),
  CONSTRAINT `pay_runs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pay_runs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payroll_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `epf_employee_rate` decimal(5,2) NOT NULL DEFAULT '11.00',
  `epf_employer_rate` decimal(5,2) NOT NULL DEFAULT '13.00',
  `epf_employer_rate_high` decimal(5,2) NOT NULL DEFAULT '12.00',
  `epf_employer_salary_threshold` decimal(10,2) NOT NULL DEFAULT '5000.00',
  `epf_employee_rate_senior` decimal(5,2) NOT NULL DEFAULT '5.50',
  `epf_employer_rate_senior` decimal(5,2) NOT NULL DEFAULT '6.50',
  `epf_foreign_employee_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `epf_foreign_employer_flat` decimal(10,2) NOT NULL DEFAULT '5.00',
  `socso_employee_rate` decimal(5,4) NOT NULL DEFAULT '0.5000',
  `socso_employer_rate` decimal(5,4) NOT NULL DEFAULT '1.7500',
  `socso_wage_ceiling` decimal(10,2) NOT NULL DEFAULT '5000.00',
  `socso_foreign_employer_rate` decimal(5,4) NOT NULL DEFAULT '1.2500',
  `eis_rate` decimal(5,4) NOT NULL DEFAULT '0.2000',
  `eis_wage_ceiling` decimal(10,2) NOT NULL DEFAULT '5000.00',
  `eis_foreign_exempt` tinyint(1) NOT NULL DEFAULT '1',
  `pcb_nonresident_rate` decimal(5,2) NOT NULL DEFAULT '30.00',
  `minimum_wage` decimal(10,2) NOT NULL DEFAULT '1700.00',
  `minimum_wage_effective_date` date DEFAULT NULL,
  `hrdf_rate` decimal(5,2) NOT NULL DEFAULT '1.00',
  `hrdf_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `default_working_days` smallint unsigned NOT NULL DEFAULT '26',
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lhdn_employer_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `epf_employer_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `socso_employer_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eis_employer_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_configs_company_unique` (`company`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payroll_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('earning','deduction') COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_amount` decimal(12,2) DEFAULT NULL COMMENT 'Pre-filled when adding this item to an employee salary',
  `is_statutory` tinyint(1) NOT NULL DEFAULT '0',
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_items_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payroll_regulatory_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_regulatory_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `authority` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_law` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effective_date` date NOT NULL,
  `announced_date` date DEFAULT NULL,
  `severity` enum('info','warning','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `status` enum('pending','acknowledged','implemented') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `config_fields_affected` text COLLATE utf8mb4_unicode_ci,
  `acknowledged_by` bigint unsigned DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_regulatory_alerts_acknowledged_by_foreign` (`acknowledged_by`),
  CONSTRAINT `payroll_regulatory_alerts_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payslip_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslip_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payslip_id` bigint unsigned NOT NULL,
  `payroll_item_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('earning','deduction') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `is_statutory` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payslip_items_payslip_id_foreign` (`payslip_id`),
  KEY `payslip_items_payroll_item_id_foreign` (`payroll_item_id`),
  CONSTRAINT `payslip_items_payroll_item_id_foreign` FOREIGN KEY (`payroll_item_id`) REFERENCES `payroll_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payslip_items_payslip_id_foreign` FOREIGN KEY (`payslip_id`) REFERENCES `payslips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslips` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pay_run_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `payslip_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_earnings` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net_pay` decimal(12,2) NOT NULL DEFAULT '0.00',
  `epf_employee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `socso_employee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `eis_employee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pcb_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `epf_employer` decimal(10,2) NOT NULL DEFAULT '0.00',
  `socso_employer` decimal(10,2) NOT NULL DEFAULT '0.00',
  `eis_employer` decimal(10,2) NOT NULL DEFAULT '0.00',
  `hrdf_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unpaid_leave_days` decimal(5,1) NOT NULL DEFAULT '0.0',
  `unpaid_leave_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `overtime_hours` decimal(6,2) NOT NULL DEFAULT '0.00',
  `overtime_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `claim_reimbursement` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Approved expense claim reimbursement — excluded from statutory calculations',
  `status` enum('draft','finalized','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payslips_pay_run_id_employee_id_unique` (`pay_run_id`,`employee_id`),
  UNIQUE KEY `payslips_payslip_number_unique` (`payslip_number`),
  KEY `payslips_employee_id_foreign` (`employee_id`),
  CONSTRAINT `payslips_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payslips_pay_run_id_foreign` FOREIGN KEY (`pay_run_id`) REFERENCES `pay_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `onboarding_id` bigint unsigned NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `official_document_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `race` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_disabled` tinyint(1) NOT NULL DEFAULT '0',
  `residential_address` text COLLATE utf8mb4_unicode_ci,
  `personal_contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `house_tel_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personal_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `epf_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `income_tax_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `socso_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nric_file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nric_file_paths` json DEFAULT NULL,
  `consent_given_at` timestamp NULL DEFAULT NULL,
  `consent_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invite_staging_json` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `personal_details_onboarding_id_foreign` (`onboarding_id`),
  CONSTRAINT `personal_details_onboarding_id_foreign` FOREIGN KEY (`onboarding_id`) REFERENCES `onboardings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `public_holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `public_holidays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `year` year NOT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_holidays_company_date_unique` (`company`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salary_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `adjusted_by` bigint unsigned DEFAULT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_salary` decimal(12,2) NOT NULL,
  `new_salary` decimal(12,2) NOT NULL,
  `effective_date` date NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salary_adjustments_employee_id_foreign` (`employee_id`),
  KEY `salary_adjustments_adjusted_by_foreign` (`adjusted_by`),
  CONSTRAINT `salary_adjustments_adjusted_by_foreign` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `salary_adjustments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `work_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `emailed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `resource` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_level` enum('full','view','edit','none') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_permissions_user_id_resource_unique` (`user_id`,`resource`),
  CONSTRAINT `user_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `work_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `role` enum('hr_manager','hr_executive','hr_intern','it_manager','it_executive','it_intern','superadmin','system_admin','employee','finance_manager','finance_executive') COLLATE utf8mb4_unicode_ci DEFAULT 'employee',
  `kb_password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `login_attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `deactivation_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deactivated_at` timestamp NULL DEFAULT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_work_email_unique` (`work_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `work_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `onboarding_id` bigint unsigned NOT NULL,
  `employee_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reporting_manager` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reporting_manager_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `exit_date` date DEFAULT NULL,
  `last_salary_date` date DEFAULT NULL,
  `confirmation_date` date DEFAULT NULL,
  `company_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('manager','senior_executive','executive_associate','director_hod','hr_manager','hr_executive','hr_intern','it_manager','it_executive','it_intern','superadmin','system_admin','others') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_details_onboarding_id_foreign` (`onboarding_id`),
  CONSTRAINT `work_details_onboarding_id_foreign` FOREIGN KEY (`onboarding_id`) REFERENCES `onboardings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `work_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `work_hours_per_day` decimal(4,2) NOT NULL,
  `working_days` json DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2024_01_01_000001_create_onboarding_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2024_01_02_000001_refine_schema_v2',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2024_01_03_000001_update_role_enums_v3',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2024_01_04_000001_v4_final_schema',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_03_09_000001_onboarding_requirements_update',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_03_09_100000_add_email_tracking_and_avatar',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_03_10_000001_it_tasks_and_onboarding_statuses',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_03_10_100000_offboarding_and_employee_enhancements',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_03_11_000001_employee_History_and_Lifecycle',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_03_11_000002_add_preferred_name_to_employees',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_03_11_000003_create_employee_contracts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_03_11_100001_asset_ownership_and_remarks_log',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_03_11_151302_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_03_11_200000_reset_orphaned_asset_status',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_03_11_300000_asset_remarks_and_company_name',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_03_11_400000_aarf_asset_changes_log',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_03_11_500000_normalise_asset_status',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_03_11_600000_add_asset_location',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_03_11_700000_drop_asset_name',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_03_11_800000_add_employee_id_to_aarfs_and_assignments',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_03_12_900000_add_handbook_orientation__to_employees',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_03_12_950000_add_employee_id_to_offboardings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_03_12_960000_make_offboardings_onboarding_id_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_03_13_000001_add_remarks_to_employees_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_03_14_000001_create_dispose_assets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_03_14_100000_update_asset_condition_and_maintenance_status_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_03_14_200000_convert_asset_status_to_string',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_03_15_000001_add_offboarding_notifications_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_03_16_000001_add_reason_to_dispose_assets',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_03_16_100000_offboarding_reporting_and_week_reminder',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_03_19_000001_make_offboardings_exit_date_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_03_19_000001_offboarding_it_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_03_19_000002_backfill_resigned_employee_offboardings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_03_24_000001_create_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_03_24_000002_replace_asset_photo_with_asset_photos',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_03_25_000001_add_invite_columns_to_onboardings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_03_25_000002_make_personal_details_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_03_27_000001_add_hr_form_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_03_27_100000_add_house_tel_no',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_03_27_200000_allow_multiple_spouses',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_03_27_300000_add_nric_file_paths',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_03_30_000001_add_logo_path_to_companies',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_03_30_200000_create_onboarding_edit_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_03_30_210000_create_employee_edit_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_03_31_000001_add_certificate_paths_to_education_histories',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_04_04_000001_create_leave_management_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_04_04_000002_create_payroll_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_04_04_000003_create_attendance_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_04_01_090534_backfill_not_good_assets_to_dispose_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_04_02_000001_add_pending_asset_ids_to_aarfs',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_04_02_000002_create_user_permissions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_04_02_100000_add_last_salary_date',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_04_03_000001_create_announcements_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_04_03_100000_refactor_announcement_company_to_json',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_04_03_200000_make_announcement_title_body_nullable',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_04_03_300000_add_login_lockout_columns_to_users',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_04_03_400000_add_session_token_to_users',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_04_03_500000_create_security_audit_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_04_04_100000_enhance_saas_modules',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_04_05_100000_create_ea_forms_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_04_05_200000_add_manager_approval_to_leave',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_04_06_000001_create_expense_claims_tables',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_04_05_000001_create_accounting_module',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_04_06_203236_seed_malaysian_ea_leave_entitlements',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_04_06_204606_seed_malaysian_public_holidays_2026',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_04_07_000001_enhance_payroll_config_statutory',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_04_07_000002_add_claim_reimbursement_to_payslips',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_04_07_000003_add_default_amount_to_payroll_items',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_04_07_000004_add_receipt_hash_and_flex_claims',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_04_07_000005_add_kb_password_to_users',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_04_07_215043_add_ollama_base_url_to_acc_settings',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_04_08_000001_add_company_supplied_to_asset_inventories',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_04_09_151609_add_phone_to_companies_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_04_09_155212_add_statutory_numbers_to_companies_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_04_09_174057_add_eis_number_to_companies_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_04_09_214917_add_two_factor_columns_to_users_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_04_10_223029_add_asset_category_and_expand_type_to_asset_inventories',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_04_13_120809_add_rental_contract_documents_to_asset_inventories',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_04_14_100000_add_confirmation_date_to_work_details_and_employees',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_04_14_200000_add_employee_number_to_work_details_and_employees',22);
