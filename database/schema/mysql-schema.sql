/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `staff_member_id` bigint(20) unsigned DEFAULT NULL,
  `visitor_name` varchar(255) NOT NULL,
  `visitor_email` varchar(255) NOT NULL,
  `visitor_phone` varchar(50) DEFAULT NULL,
  `appointment_type` enum('showing','consultation','follow_up','other') NOT NULL DEFAULT 'showing',
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 60,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `source` enum('website','chatbot','phone','other') NOT NULL DEFAULT 'website',
  `visitor_ip` varchar(45) DEFAULT NULL,
  `confirmation_token` varchar(64) DEFAULT NULL,
  `token_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `appointments_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `appointments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `role` enum('user','assistant') NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_logs_tenant_id_session_id_index` (`tenant_id`,`session_id`),
  CONSTRAINT `chat_logs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dashboard_widget_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_widget_order` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `section` varchar(50) NOT NULL,
  `widget_key` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `dashboard_widget_order_tenant_id_section_index` (`tenant_id`,`section`),
  CONSTRAINT `dashboard_widget_order_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status` enum('new','reviewed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedback_user_id_foreign` (`user_id`),
  KEY `feedback_tenant_id_index` (`tenant_id`),
  KEY `feedback_status_index` (`status`),
  CONSTRAINT `feedback_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `integrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `integration_type` varchar(50) NOT NULL,
  `api_key` text DEFAULT NULL,
  `provider` varchar(100) DEFAULT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `webhook_token` varchar(64) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `integrations_tenant_id_integration_type_index` (`tenant_id`,`integration_type`),
  CONSTRAINT `integrations_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `legal_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `page_type` enum('privacy','terms') NOT NULL,
  `content` longtext DEFAULT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `legal_pages_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `legal_pages_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `sender_name` varchar(255) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `sender_phone` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `source` enum('contact_form','chatbot','phone','other') NOT NULL DEFAULT 'contact_form',
  `status` enum('new','read','replied','archived','spam') NOT NULL DEFAULT 'new',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_starred` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `messages_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `properties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(300) DEFAULT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `staff_member_id` bigint(20) unsigned DEFAULT NULL,
  `listing_status` enum('active','pending','sold','featured','withdrawn') NOT NULL DEFAULT 'active',
  `property_type` enum('house','condo','townhouse','land','commercial','multi_family','other') NOT NULL DEFAULT 'house',
  `price` decimal(12,2) DEFAULT NULL,
  `address_street` varchar(255) DEFAULT NULL,
  `address_city` varchar(100) DEFAULT NULL,
  `address_state` varchar(100) DEFAULT NULL,
  `address_zip` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `bedrooms` tinyint(3) unsigned DEFAULT NULL,
  `bathrooms` decimal(4,1) DEFAULT NULL,
  `half_baths` tinyint(4) DEFAULT NULL,
  `square_feet` int(11) DEFAULT NULL,
  `lot_size` varchar(100) DEFAULT NULL,
  `year_built` smallint(5) unsigned DEFAULT NULL,
  `stories` tinyint(3) unsigned DEFAULT NULL,
  `condition` enum('excellent','good','fair','needs_work') DEFAULT NULL,
  `school_district` varchar(255) DEFAULT NULL,
  `hoa_fee` decimal(8,2) DEFAULT NULL,
  `garage` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `has_pool` tinyint(1) NOT NULL DEFAULT 0,
  `has_basement` tinyint(1) NOT NULL DEFAULT 0,
  `has_fireplace` tinyint(1) NOT NULL DEFAULT 0,
  `near_school` tinyint(1) NOT NULL DEFAULT 0,
  `near_hospital` tinyint(1) NOT NULL DEFAULT 0,
  `near_shopping` tinyint(1) NOT NULL DEFAULT 0,
  `near_transit` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `mls_number` varchar(50) DEFAULT NULL,
  `virtual_tour_url` text DEFAULT NULL,
  `video_tour_url` text DEFAULT NULL,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `properties_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `properties_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `image_url` text NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `label` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_images_property_id_foreign` (`property_id`),
  KEY `property_images_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `property_images_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `property_images_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `property_views_tenant_id_foreign` (`tenant_id`),
  KEY `property_views_property_id_ip_address_index` (`property_id`,`ip_address`),
  CONSTRAINT `property_views_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `property_views_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `site_title` varchar(255) DEFAULT NULL,
  `tagline` varchar(500) DEFAULT NULL,
  `logo_image` text DEFAULT NULL,
  `primary_color` varchar(20) NOT NULL DEFAULT '#3B82F6',
  `secondary_color` varchar(20) NOT NULL DEFAULT '#1E40AF',
  `accent_color` varchar(20) NOT NULL DEFAULT '#F59E0B',
  `header_display_mode` enum('logo_only','text_only','both') NOT NULL DEFAULT 'both',
  `header_mode` enum('hero','default') NOT NULL DEFAULT 'hero',
  `title_font` varchar(100) NOT NULL DEFAULT 'Poppins',
  `body_font` varchar(100) NOT NULL DEFAULT 'Inter',
  `site_title_font_size` varchar(20) NOT NULL DEFAULT '3xl',
  `site_title_font_weight` varchar(10) NOT NULL DEFAULT '800',
  `site_title_letter_spacing` varchar(20) NOT NULL DEFAULT '-0.5px',
  `title_color_type` enum('solid','gradient') NOT NULL DEFAULT 'gradient',
  `title_color_solid` varchar(20) NOT NULL DEFAULT '#3B82F6',
  `title_gradient_start` varchar(20) NOT NULL DEFAULT '#3B82F6',
  `title_gradient_via` varchar(20) NOT NULL DEFAULT '#8B5CF6',
  `title_gradient_end` varchar(20) NOT NULL DEFAULT '#1E40AF',
  `homepage_sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`homepage_sections`)),
  `features_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features_items`)),
  `services_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`services_items`)),
  `testimonials_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`testimonials_items`)),
  `stats_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stats_items`)),
  `faq_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`faq_items`)),
  `dashboard_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dashboard_config`)),
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_address` text DEFAULT NULL,
  `social_facebook` text DEFAULT NULL,
  `social_instagram` text DEFAULT NULL,
  `social_twitter` text DEFAULT NULL,
  `social_linkedin` text DEFAULT NULL,
  `chatbot_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `chatbot_name` varchar(100) NOT NULL DEFAULT 'Assistant',
  `chatbot_personality` enum('professional','friendly','casual') NOT NULL DEFAULT 'professional',
  `chatbot_expiration` int(11) NOT NULL DEFAULT 24,
  `chatbot_welcome` text DEFAULT NULL,
  `chatbot_bio` text DEFAULT NULL,
  `enable_email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `notify_on_contact` tinyint(1) NOT NULL DEFAULT 1,
  `notify_on_appointment` tinyint(1) NOT NULL DEFAULT 1,
  `notification_email` varchar(255) DEFAULT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int(11) NOT NULL DEFAULT 587,
  `smtp_username` varchar(255) DEFAULT NULL,
  `smtp_password` text DEFAULT NULL,
  `smtp_encryption` varchar(10) NOT NULL DEFAULT 'tls',
  `smtp_from_email` varchar(255) DEFAULT NULL,
  `smtp_from_name` varchar(255) DEFAULT NULL,
  `site_description` text DEFAULT NULL,
  `default_share_image` text DEFAULT NULL,
  `google_site_verification` varchar(255) DEFAULT NULL,
  `search_engine_visibility` tinyint(1) NOT NULL DEFAULT 1,
  `timezone` varchar(100) NOT NULL DEFAULT 'America/New_York',
  `show_login_link` tinyint(1) NOT NULL DEFAULT 1,
  `dark_mode_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `hero_background_type` varchar(50) NOT NULL DEFAULT 'preset',
  `hero_preset` varchar(100) DEFAULT NULL,
  `hero_image` varchar(500) DEFAULT NULL,
  `hero_gradient_start` varchar(20) DEFAULT NULL,
  `hero_gradient_end` varchar(20) DEFAULT NULL,
  `hero_title` varchar(300) DEFAULT NULL,
  `hero_subtitle` varchar(500) DEFAULT NULL,
  `cta_primary_text` varchar(100) DEFAULT NULL,
  `cta_primary_link` varchar(300) DEFAULT NULL,
  `cta_secondary_text` varchar(100) DEFAULT NULL,
  `cta_secondary_link` varchar(300) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_settings_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `site_settings_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `photo_url` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `display_on_homepage` tinyint(1) NOT NULL DEFAULT 0,
  `accepts_appointments` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_members_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `staff_members_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `registrations_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `site_locked` tinyint(1) NOT NULL DEFAULT 0,
  `lock_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `plan` enum('trial','starter','pro') NOT NULL DEFAULT 'trial',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_status` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenants_slug_unique` (`slug`),
  UNIQUE KEY `tenants_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `users_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2026_01_01_000001_create_tenants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2026_01_01_000002_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2026_01_01_000003_create_site_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_01_01_000004_create_properties_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_01_01_000005_create_property_images_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_01_01_000006_create_property_views_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_01_01_000007_create_staff_members_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_01_01_000008_create_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_01_01_000009_create_appointments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_01_01_000010_create_integrations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_01_01_000011_create_legal_pages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_01_01_000012_create_dashboard_widget_order_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_01_01_000013_create_chat_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_01_01_000014_create_password_reset_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_01_01_000015_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_01_01_000016_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_02_23_000001_add_missing_columns_to_site_settings',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_02_23_000002_add_title_to_properties',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_02_24_060313_add_hero_gradient_to_site_settings',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_03_01_000001_create_system_settings_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_03_01_000002_make_property_address_nullable',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_03_01_000003_create_feedback_table',7);
