-- BizMi CRM Database Schema
-- Created by: Amrullah Khan
-- Email: amrulzlionheart@gmail.com
-- Date: November 11, 2025
-- Version: 1.0.0

-- Set up database
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Database charset
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('super_admin','admin','manager','user','view_only') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive','pending') NOT NULL DEFAULT 'pending',
  `phone` varchar(20),
  `avatar` varchar(255),
  `timezone` varchar(50) DEFAULT 'UTC',
  `language` varchar(10) DEFAULT 'en',
  `last_login` timestamp NULL,
  `email_verified` boolean DEFAULT FALSE,
  `email_verification_token` varchar(255),
  `password_reset_token` varchar(255),
  `password_reset_expires` timestamp NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `organizations`
-- --------------------------------------------------------

CREATE TABLE `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('prospect','customer','partner','vendor','competitor') DEFAULT 'prospect',
  `industry` varchar(100),
  `website` varchar(255),
  `phone` varchar(20),
  `fax` varchar(20),
  `email` varchar(100),
  `description` text,
  `logo` varchar(255),
  `employees_count` int(11),
  `annual_revenue` decimal(15,2),
  `currency` varchar(3) DEFAULT 'USD',
  `tax_id` varchar(50),
  `billing_address_street` varchar(255),
  `billing_address_city` varchar(100),
  `billing_address_state` varchar(100),
  `billing_address_zip` varchar(20),
  `billing_address_country` varchar(100),
  `shipping_address_street` varchar(255),
  `shipping_address_city` varchar(100),
  `shipping_address_state` varchar(100),
  `shipping_address_zip` varchar(20),
  `shipping_address_country` varchar(100),
  `status` enum('active','inactive') DEFAULT 'active',
  `rating` enum('hot','warm','cold') DEFAULT 'warm',
  `source` varchar(100),
  `tags` text,
  `custom_fields` json,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11),
  `assigned_to` int(11),
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_assigned_to` (`assigned_to`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `contacts`
-- --------------------------------------------------------

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `full_name` varchar(101) GENERATED ALWAYS AS (CONCAT(first_name, ' ', last_name)) STORED,
  `title` varchar(100),
  `email` varchar(100),
  `phone` varchar(20),
  `mobile` varchar(20),
  `fax` varchar(20),
  `organization_id` int(11),
  `department` varchar(100),
  `lead_source` varchar(100),
  `type` enum('lead','prospect','customer','partner') DEFAULT 'lead',
  `status` enum('active','inactive','converted','lost') DEFAULT 'active',
  `rating` enum('hot','warm','cold') DEFAULT 'warm',
  `do_not_call` boolean DEFAULT FALSE,
  `do_not_email` boolean DEFAULT FALSE,
  `email_opt_out` boolean DEFAULT FALSE,
  `photo` varchar(255),
  `date_of_birth` date,
  `assistant` varchar(100),
  `assistant_phone` varchar(20),
  `reports_to` int(11),
  `mailing_address_street` varchar(255),
  `mailing_address_city` varchar(100),
  `mailing_address_state` varchar(100),
  `mailing_address_zip` varchar(20),
  `mailing_address_country` varchar(100),
  `other_address_street` varchar(255),
  `other_address_city` varchar(100),
  `other_address_state` varchar(100),
  `other_address_zip` varchar(20),
  `other_address_country` varchar(100),
  `description` text,
  `tags` text,
  `social_linkedin` varchar(255),
  `social_twitter` varchar(255),
  `social_facebook` varchar(255),
  `custom_fields` json,
  `last_contacted` timestamp NULL,
  `next_followup` timestamp NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11),
  `assigned_to` int(11),
  PRIMARY KEY (`id`),
  KEY `idx_full_name` (`full_name`),
  KEY `idx_email` (`email`),
  KEY `idx_phone` (`phone`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_organization_id` (`organization_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_lead_source` (`lead_source`),
  FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`reports_to`) REFERENCES `contacts`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `pipeline_stages`
-- --------------------------------------------------------

CREATE TABLE `pipeline_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `probability` decimal(5,2) DEFAULT 0.00,
  `stage_order` int(11) NOT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `is_closed` boolean DEFAULT FALSE,
  `is_won` boolean DEFAULT FALSE,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stage_order` (`stage_order`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `deals`
-- --------------------------------------------------------

CREATE TABLE `deals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `amount` decimal(15,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'USD',
  `expected_close_date` date,
  `actual_close_date` date,
  `probability` decimal(5,2) DEFAULT 0.00,
  `contact_id` int(11),
  `organization_id` int(11),
  `pipeline_stage_id` int(11) NOT NULL,
  `type` enum('new_business','existing_business','renewal') DEFAULT 'new_business',
  `status` enum('open','won','lost','abandoned') DEFAULT 'open',
  `lead_source` varchar(100),
  `competitor` varchar(255),
  `lost_reason` varchar(255),
  `won_reason` varchar(255),
  `next_step` varchar(255),
  `tags` text,
  `custom_fields` json,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11),
  `assigned_to` int(11),
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_status` (`status`),
  KEY `idx_pipeline_stage_id` (`pipeline_stage_id`),
  KEY `idx_contact_id` (`contact_id`),
  KEY `idx_organization_id` (`organization_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_expected_close_date` (`expected_close_date`),
  KEY `idx_amount` (`amount`),
  FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`pipeline_stage_id`) REFERENCES `pipeline_stages`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `activity_types`
-- --------------------------------------------------------

CREATE TABLE `activity_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `icon` varchar(50),
  `color` varchar(7) DEFAULT '#007bff',
  `is_default` boolean DEFAULT FALSE,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `activities`
-- --------------------------------------------------------

CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL,
  `description` text,
  `activity_type_id` int(11) NOT NULL,
  `contact_id` int(11),
  `organization_id` int(11),
  `deal_id` int(11),
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `status` enum('planned','in_progress','completed','cancelled','deferred') DEFAULT 'planned',
  `start_datetime` timestamp NULL,
  `end_datetime` timestamp NULL,
  `due_date` date,
  `duration_minutes` int(11),
  `location` varchar(255),
  `outcome` text,
  `follow_up_required` boolean DEFAULT FALSE,
  `follow_up_date` date,
  `is_billable` boolean DEFAULT FALSE,
  `billable_rate` decimal(10,2),
  `tags` text,
  `custom_fields` json,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11),
  `assigned_to` int(11),
  PRIMARY KEY (`id`),
  KEY `idx_subject` (`subject`),
  KEY `idx_activity_type_id` (`activity_type_id`),
  KEY `idx_contact_id` (`contact_id`),
  KEY `idx_organization_id` (`organization_id`),
  KEY `idx_deal_id` (`deal_id`),
  KEY `idx_status` (`status`),
  KEY `idx_start_datetime` (`start_datetime`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_assigned_to` (`assigned_to`),
  FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`deal_id`) REFERENCES `deals`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `products`
-- --------------------------------------------------------

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `product_code` varchar(50) UNIQUE,
  `description` text,
  `category` varchar(100),
  `type` enum('product','service') DEFAULT 'product',
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'USD',
  `unit_of_measure` varchar(50),
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `image` varchar(255),
  `weight` decimal(10,3),
  `dimensions` varchar(100),
  `stock_quantity` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 0,
  `vendor_id` int(11),
  `vendor_part_number` varchar(100),
  `manufacturer` varchar(255),
  `model_number` varchar(100),
  `warranty_period` varchar(100),
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `tags` text,
  `custom_fields` json,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11),
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_product_code` (`product_code`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`vendor_id`) REFERENCES `organizations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `quotes`
-- --------------------------------------------------------

CREATE TABLE `quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_number` varchar(50) UNIQUE NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text,
  `contact_id` int(11),
  `organization_id` int(11),
  `deal_id` int(11),
  `quote_date` date NOT NULL,
  `valid_until` date,
  `status` enum('draft','sent','accepted','declined','expired','revised') DEFAULT 'draft',
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'USD',
  `terms_and_conditions` text,
  `notes` text,
  `billing_address_street` varchar(255),
  `billing_address_city` varchar(100),
  `billing_address_state` varchar(100),
  `billing_address_zip` varchar(20),
  `billing_address_country` varchar(100),
  `shipping_address_street` varchar(255),
  `shipping_address_city` varchar(100),
  `shipping_address_state` varchar(100),
  `shipping_address_zip` varchar(20),
  `shipping_address_country` varchar(100),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11),
  `assigned_to` int(11),
  PRIMARY KEY (`id`),
  KEY `idx_quote_number` (`quote_number`),
  KEY `idx_contact_id` (`contact_id`),
  KEY `idx_organization_id` (`organization_id`),
  KEY `idx_deal_id` (`deal_id`),
  KEY `idx_status` (`status`),
  KEY `idx_quote_date` (`quote_date`),
  FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`deal_id`) REFERENCES `deals`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `quote_items`
-- --------------------------------------------------------

CREATE TABLE `quote_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `product_id` int(11),
  `product_name` varchar(255) NOT NULL,
  `description` text,
  `quantity` decimal(10,3) NOT NULL DEFAULT 1.000,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `tax_percent` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `line_total` decimal(15,2) GENERATED ALWAYS AS ((quantity * unit_price) - discount_amount + tax_amount) STORED,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_quote_id` (`quote_id`),
  KEY `idx_product_id` (`product_id`),
  FOREIGN KEY (`quote_id`) REFERENCES `quotes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `documents`
-- --------------------------------------------------------

CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100),
  `mime_type` varchar(255),
  `folder` varchar(255),
  `contact_id` int(11),
  `organization_id` int(11),
  `deal_id` int(11),
  `quote_id` int(11),
  `activity_id` int(11),
  `is_public` boolean DEFAULT FALSE,
  `download_count` int(11) DEFAULT 0,
  `tags` text,
  `version` decimal(3,1) DEFAULT 1.0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11),
  PRIMARY KEY (`id`),
  KEY `idx_title` (`title`),
  KEY `idx_contact_id` (`contact_id`),
  KEY `idx_organization_id` (`organization_id`),
  KEY `idx_deal_id` (`deal_id`),
  KEY `idx_quote_id` (`quote_id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_file_type` (`file_type`),
  FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`deal_id`) REFERENCES `deals`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`quote_id`) REFERENCES `quotes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`activity_id`) REFERENCES `activities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notes`
-- --------------------------------------------------------

CREATE TABLE `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255),
  `content` text NOT NULL,
  `contact_id` int(11),
  `organization_id` int(11),
  `deal_id` int(11),
  `activity_id` int(11),
  `is_private` boolean DEFAULT FALSE,
  `tags` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11),
  PRIMARY KEY (`id`),
  KEY `idx_contact_id` (`contact_id`),
  KEY `idx_organization_id` (`organization_id`),
  KEY `idx_deal_id` (`deal_id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`deal_id`) REFERENCES `deals`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`activity_id`) REFERENCES `activities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` varchar(255),
  `is_system` boolean DEFAULT FALSE,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11),
  PRIMARY KEY (`id`),
  KEY `idx_setting_key` (`setting_key`),
  FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `audit_log`
-- --------------------------------------------------------

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11),
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11),
  `old_values` json,
  `new_values` json,
  `ip_address` varchar(45),
  `user_agent` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert default data
-- --------------------------------------------------------

-- Default Pipeline Stages
INSERT INTO `pipeline_stages` (`name`, `description`, `probability`, `stage_order`, `color`, `is_closed`, `is_won`) VALUES
('Lead', 'Initial lead captured', 10.00, 1, '#6c757d', 0, 0),
('Qualified', 'Lead has been qualified', 25.00, 2, '#17a2b8', 0, 0),
('Proposal', 'Proposal sent to prospect', 50.00, 3, '#ffc107', 0, 0),
('Negotiation', 'In active negotiation', 75.00, 4, '#fd7e14', 0, 0),
('Closed Won', 'Deal successfully closed', 100.00, 5, '#28a745', 1, 1),
('Closed Lost', 'Deal was lost', 0.00, 6, '#dc3545', 1, 0);

-- Default Activity Types
INSERT INTO `activity_types` (`name`, `icon`, `color`, `is_default`) VALUES
('Call', 'fa-phone', '#007bff', 1),
('Email', 'fa-envelope', '#28a745', 1),
('Meeting', 'fa-users', '#ffc107', 1),
('Task', 'fa-tasks', '#6c757d', 1),
('Note', 'fa-sticky-note', '#17a2b8', 1),
('Demo', 'fa-desktop', '#fd7e14', 1),
('Follow-up', 'fa-clock', '#e83e8c', 1);

-- Default System Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_system`) VALUES
('company_name', 'BizMi CRM', 'string', 'Company name for the system', 1),
('company_website', '', 'string', 'Company website URL', 0),
('company_phone', '', 'string', 'Company main phone number', 0),
('company_email', '', 'string', 'Company main email address', 0),
('company_address', '', 'string', 'Company address', 0),
('default_currency', 'USD', 'string', 'Default currency for the system', 1),
('default_timezone', 'UTC', 'string', 'Default timezone for the system', 1),
('date_format', 'Y-m-d', 'string', 'Default date format', 1),
('time_format', 'H:i:s', 'string', 'Default time format', 1),
('records_per_page', '20', 'integer', 'Number of records to show per page', 1),
('session_timeout', '3600', 'integer', 'Session timeout in seconds', 1),
('max_file_size', '10485760', 'integer', 'Maximum file upload size in bytes (10MB)', 1),
('allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt', 'string', 'Allowed file types for upload', 1),
('email_notifications', '1', 'boolean', 'Enable email notifications', 1),
('audit_log_enabled', '1', 'boolean', 'Enable audit logging', 1),
('system_version', '1.0.0', 'string', 'Current system version', 1),
('installation_date', '', 'string', 'System installation date', 1);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;