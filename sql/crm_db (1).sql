-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 02 Şub 2026, 12:16:38
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `crm_db`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ai_prompt_templates`
--

CREATE TABLE `ai_prompt_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `template_key` varchar(64) NOT NULL,
  `title` varchar(160) NOT NULL,
  `system_prompt` text NOT NULL,
  `user_prompt` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ai_prompt_templates`
--

INSERT INTO `ai_prompt_templates` (`id`, `tenant_id`, `template_key`, `title`, `system_prompt`, `user_prompt`, `is_active`, `created_at`) VALUES
(1, NULL, 'last_message_to_sale', 'Son Mesajı Satışa Bağla', 'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.', 'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nSon mesaja satışa bağlayan, ikna edici ama baskıcı olmayan cevap yaz.', 1, '2026-01-25 14:41:06'),
(2, NULL, 'objection_handle', 'İtiraz Kırma', 'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.', 'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nMüşterinin itirazını satış lehine çevir. Net, profesyonel ve satışa götüren bir yanıt yaz.', 1, '2026-01-25 14:41:06'),
(3, NULL, 'offer_generate', 'Teklif Üret', 'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.', 'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nMüşteriye özel teklif hazırla. Kısa, anlaşılır, fiyat/teslimat/garanti gibi kritik bilgileri ekle ve bir sonraki adımı netleştir.', 1, '2026-01-25 14:41:06'),
(4, NULL, 'continue_chat', 'Sohbet Devam', 'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.', 'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nSohbeti doğal şekilde devam ettir. Bir sonraki soruyu sor ve satın almaya götürecek mikro-CTA ekle.', 1, '2026-01-25 14:41:06'),
(5, NULL, 'warm_sales', 'Samimi Satış', 'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.', 'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nDaha sıcak ama hedef odaklı mesaj yaz. Müşterinin diline göre ton ayarla.', 1, '2026-01-25 14:41:06'),
(6, NULL, 'professional_sales', 'Profesyonel Satış', 'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.', 'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nKurumsal ve net satış dili kullan. Kapanış için uygun bir sonraki adımı öner.', 1, '2026-01-25 14:41:06');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ai_rules`
--

CREATE TABLE `ai_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `sector` varchar(120) NOT NULL,
  `tone` varchar(64) NOT NULL,
  `forbidden_phrases` text DEFAULT NULL,
  `sales_focus` tinyint(1) NOT NULL DEFAULT 1,
  `language` varchar(5) NOT NULL DEFAULT 'tr',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ai_rules`
--

INSERT INTO `ai_rules` (`id`, `tenant_id`, `sector`, `tone`, `forbidden_phrases`, `sales_focus`, `language`, `created_at`) VALUES
(1, 1, 'inşaat', 'profesyonel', 'rakiplerle kıyaslama yapma; aşağılayıcı/alaycı ifade kullanma', 1, 'tr', '2026-01-25 14:41:06');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ai_suggestions`
--

CREATE TABLE `ai_suggestions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `thread_id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `template_key` varchar(64) NOT NULL,
  `input_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`input_snapshot_json`)),
  `output_text` text NOT NULL,
  `model` varchar(64) DEFAULT NULL,
  `tokens` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(190) NOT NULL,
  `entity_type` varchar(190) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `tenant_id`, `actor_user_id`, `action`, `entity_type`, `entity_id`, `ip`, `user_agent`, `metadata_json`, `created_at`) VALUES
(1, 1, NULL, 'lead.create_webhook', 'lead', 2, NULL, NULL, '{\"source\":\"instagram\",\"contact_id\":2}', '2026-01-25 19:37:53'),
(2, 1, NULL, 'lead.create_webhook', 'lead', 3, NULL, NULL, '{\"source\":\"instagram\",\"contact_id\":3}', '2026-01-25 19:45:15'),
(3, 1, NULL, 'lead.create_webhook', 'lead', 4, NULL, NULL, '{\"source\":\"instagram\",\"contact_id\":2}', '2026-01-25 19:51:59'),
(4, 1, NULL, 'lead.create_webhook', 'lead', 5, NULL, NULL, '{\"source\":\"instagram\",\"contact_id\":4}', '2026-01-26 19:07:15'),
(5, 1, 2, 'user.login', 'user', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0 (Edition std-2)', '{\"email\":\"admin@tenant1.local\",\"host\":\"127.0.0.1\"}', '2026-01-29 18:09:49'),
(6, 1, 3, 'user.login', 'user', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0 (Edition std-2)', '{\"email\":\"staff@tenant1.local\",\"host\":\"127.0.0.1\"}', '2026-01-29 18:29:10'),
(7, 1, 3, 'lead.claim', 'lead', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0 (Edition std-2)', '{\"assigned_user_id\":3}', '2026-01-29 18:29:22'),
(8, 1, 2, 'user.login', 'user', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0 (Edition std-2)', '{\"email\":\"admin@tenant1.local\",\"host\":\"127.0.0.1\"}', '2026-01-29 18:29:31'),
(9, 1, 2, 'calendar.event_create', 'calendar_event', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0 (Edition std-2)', '{\"title\":\"sdafdsaffdsafsd\",\"starts_at\":\"2026-01-29 22:22:00\",\"ends_at\":\"2026-01-30 23:22:00\",\"urgency\":\"high\",\"parts_count\":2}', '2026-01-29 19:22:44'),
(10, 1, 2, 'calendar.event_delete', 'calendar_event', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0 (Edition std-2)', '{\"title\":\"sdafdsaffdsafsd\",\"starts_at\":\"2026-01-29 22:22:00\",\"ends_at\":\"2026-01-29 23:59:59\",\"urgency\":\"high\"}', '2026-01-29 19:22:47'),
(11, 1, 2, 'calendar.event_delete', 'calendar_event', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0 (Edition std-2)', '{\"title\":\"sdafdsaffdsafsd\",\"starts_at\":\"2026-01-30 00:00:00\",\"ends_at\":\"2026-01-30 23:22:00\",\"urgency\":\"high\"}', '2026-01-29 19:22:50'),
(12, 1, 3, 'user.login', 'user', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0 (Edition std-2)', '{\"email\":\"staff@tenant1.local\",\"host\":\"127.0.0.1\"}', '2026-01-29 19:25:03'),
(13, 1, 3, 'lead.release', 'lead', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0 (Edition std-2)', '{\"assigned_user_id\":null}', '2026-01-29 19:25:11'),
(14, 1, 3, 'lead.claim', 'lead', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0 (Edition std-2)', '{\"assigned_user_id\":3}', '2026-01-29 19:25:13');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `owner_user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(190) DEFAULT NULL,
  `urgency` enum('low','medium','high') NOT NULL DEFAULT 'low',
  `starts_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ends_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `external_id` varchar(190) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `provider` varchar(32) DEFAULT NULL,
  `instagram_user_id` varchar(190) DEFAULT NULL,
  `username` varchar(190) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `contacts`
--

INSERT INTO `contacts` (`id`, `tenant_id`, `name`, `phone`, `email`, `external_id`, `created_at`, `updated_at`, `provider`, `instagram_user_id`, `username`, `profile_picture`) VALUES
(1, 1, 'Ahmet Yılmaz', '+90 5xx xxx xx xx', 'ahmet@example.com', NULL, '2026-01-25 14:41:06', '2026-01-25 14:41:06', NULL, NULL, NULL, NULL),
(4, 1, 'Instagram User 362764', NULL, NULL, '1597718291362764', '2026-01-26 19:07:15', '2026-01-26 19:07:15', 'instagram', '1597718291362764', NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `domains`
--

CREATE TABLE `domains` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `host` varchar(255) NOT NULL,
  `panel` enum('super','tenant') NOT NULL DEFAULT 'tenant',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `domains`
--

INSERT INTO `domains` (`id`, `tenant_id`, `host`, `panel`, `is_primary`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'superadmin.localhost', 'super', 1, 'active', '2026-01-25 14:41:06', '2026-01-25 14:41:06'),
(2, 1, 'tenant1.localhost', 'tenant', 1, 'active', '2026-01-25 14:41:06', '2026-01-25 14:41:06'),
(3, 2, 'tenant2', 'tenant', 0, 'disabled', '2026-01-25 14:42:54', '2026-01-25 14:44:38'),
(4, 2, 'tenant2.localhost', 'tenant', 1, 'active', '2026-01-25 14:43:25', '2026-01-25 14:44:13');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `integration_accounts`
--

CREATE TABLE `integration_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(32) NOT NULL,
  `name` varchar(160) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_json`)),
  `webhook_secret` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `integration_accounts`
--

INSERT INTO `integration_accounts` (`id`, `tenant_id`, `provider`, `name`, `status`, `config_json`, `webhook_secret`, `created_at`, `updated_at`) VALUES
(2, 1, 'instagram', 'Instagram', 'active', '\"{\\\"page_id\\\":\\\"17841478140523860\\\",\\\"page_access_token\\\":\\\"EAAhDZCHZApryQBQkVVPYKBZCIxHQwpl9DEKGtZBNwvWuZC8Wj78zM4AdU5SvqTYz0bZAyBFuODscifZA4sAH5u0EwU5iz32n0Ic0uE8UBtLJxLBerZB3hojS4G3UJ2615FWFXEZAULfeL7LElJJF0c9SIcTBcZCWP4SEm6DEYVbbd04p7GeIPMWynirSFX5ZBvbu1Sf373WVkcLox4jPob1jGmX4wOC6sPKY2UlYWrZCvevX1pQtVtkOKnYWOy4ZD\\\"}\"', NULL, '2026-01-25 19:12:31', '2026-01-26 19:15:41');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `job_batches`
--

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
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `knowledge_base_articles`
--

CREATE TABLE `knowledge_base_articles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT 'knowledge',
  `title` varchar(190) NOT NULL,
  `content` longtext NOT NULL,
  `language` varchar(5) NOT NULL DEFAULT 'tr',
  `tags_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `knowledge_base_articles`
--

INSERT INTO `knowledge_base_articles` (`id`, `tenant_id`, `type`, `title`, `content`, `language`, `tags_json`, `created_at`, `updated_at`) VALUES
(1, 1, 'knowledge', 'Sık Sorulan Sorular', 'Bu alan bilgi bankası içerikleri için tasarlanmıştır.', 'tr', NULL, '2026-01-25 14:41:06', '2026-01-25 14:41:06'),
(2, 1, 'res_ad_copy', 'Res (Reklam metnisi) - Örnek', 'Başlık: ...\nAçıklama: ...\nCTA: ...', 'tr', NULL, '2026-01-25 14:41:06', '2026-01-25 14:41:06');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `leads`
--

CREATE TABLE `leads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `owner_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `stage_id` bigint(20) UNSIGNED DEFAULT NULL,
  `source` varchar(64) NOT NULL DEFAULT 'manual',
  `status` varchar(32) NOT NULL DEFAULT 'open',
  `score` int(11) NOT NULL DEFAULT 0,
  `name` varchar(160) NOT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `company` varchar(160) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `tags_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags_json`)),
  `last_contact_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `leads`
--

INSERT INTO `leads` (`id`, `tenant_id`, `owner_user_id`, `assigned_user_id`, `contact_id`, `stage_id`, `source`, `status`, `score`, `name`, `phone`, `email`, `company`, `notes`, `tags_json`, `last_contact_at`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 3, 1, 2, 'instagram', 'open', 55, 'Ahmet Yılmaz', '+90 5xx xxx xx xx', 'ahmet@example.com', 'Yılmaz İnşaat', 'İlk temas yapıldı.', '[\"sıcak\", \"instagram\"]', '2026-01-25 14:41:06', '2026-01-25 14:41:06', '2026-01-25 14:41:06'),
(5, 1, NULL, 3, 4, 1, 'instagram', 'open', 45, 'Instagram User 362764', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-26 19:07:15', '2026-01-29 19:25:13');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `lead_notes`
--

CREATE TABLE `lead_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `lead_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `note_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `lead_notes`
--

INSERT INTO `lead_notes` (`id`, `tenant_id`, `lead_id`, `user_id`, `note_text`, `created_at`) VALUES
(1, 1, 1, 2, 'agfdgfdagfdagfda', '2026-01-29 19:22:22');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `lead_stages`
--

CREATE TABLE `lead_stages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `color` varchar(32) DEFAULT NULL,
  `is_won` tinyint(1) NOT NULL DEFAULT 0,
  `is_lost` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `lead_stages`
--

INSERT INTO `lead_stages` (`id`, `tenant_id`, `name`, `sort_order`, `color`, `is_won`, `is_lost`, `created_at`, `updated_at`) VALUES
(1, 1, 'Yeni', 10, '#ff7a00', 0, 0, '2026-01-25 14:41:06', '2026-01-25 14:41:06'),
(2, 1, 'İletişimde', 20, '#f59e0b', 0, 0, '2026-01-25 14:41:06', '2026-01-25 14:41:06'),
(3, 1, 'Teklif', 30, '#60a5fa', 0, 0, '2026-01-25 14:41:06', '2026-01-25 14:41:06'),
(4, 1, 'Kazanıldı', 40, '#34d399', 1, 0, '2026-01-25 14:41:06', '2026-01-25 14:41:06'),
(5, 1, 'Kaybedildi', 50, '#f87171', 0, 1, '2026-01-25 14:41:06', '2026-01-25 14:41:06'),
(6, 2, 'Yeni', 10, '#ff7a00', 0, 0, '2026-01-25 14:42:54', '2026-01-25 14:42:54'),
(7, 2, 'İletişimde', 20, '#f59e0b', 0, 0, '2026-01-25 14:42:54', '2026-01-25 14:42:54'),
(8, 2, 'Teklif', 30, '#60a5fa', 0, 0, '2026-01-25 14:42:54', '2026-01-25 14:42:54'),
(9, 2, 'Kazanıldı', 40, '#34d399', 1, 0, '2026-01-25 14:42:54', '2026-01-25 14:42:54'),
(10, 2, 'Kaybedildi', 50, '#f87171', 0, 1, '2026-01-25 14:42:54', '2026-01-25 14:42:54');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `lead_stage_events`
--

CREATE TABLE `lead_stage_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `lead_id` bigint(20) UNSIGNED NOT NULL,
  `from_stage_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_stage_id` bigint(20) UNSIGNED DEFAULT NULL,
  `moved_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `lists`
--

CREATE TABLE `lists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(190) NOT NULL,
  `type` enum('lead','contact') NOT NULL DEFAULT 'lead',
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `list_items`
--

CREATE TABLE `list_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `list_id` bigint(20) UNSIGNED NOT NULL,
  `entity_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `live_ai_coach_sessions`
--

CREATE TABLE `live_ai_coach_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `thread_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `mail_messages`
--

CREATE TABLE `mail_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `lead_id` bigint(20) UNSIGNED DEFAULT NULL,
  `contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `direction` enum('in','out') NOT NULL DEFAULT 'out',
  `status` varchar(32) NOT NULL DEFAULT 'queued',
  `provider` varchar(64) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` longtext NOT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `thread_id` bigint(20) UNSIGNED NOT NULL,
  `sender_type` enum('user','contact','system') NOT NULL DEFAULT 'user',
  `sender_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sender_contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message_type` enum('text','file','image','voice') NOT NULL DEFAULT 'text',
  `body_text` text DEFAULT NULL,
  `file_path` varchar(512) DEFAULT NULL,
  `file_mime` varchar(160) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `voice_duration_ms` int(11) DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `messages`
--

INSERT INTO `messages` (`id`, `tenant_id`, `thread_id`, `sender_type`, `sender_user_id`, `sender_contact_id`, `message_type`, `body_text`, `file_path`, `file_mime`, `file_size`, `voice_duration_ms`, `metadata_json`, `created_at`) VALUES
(18, 1, 5, 'contact', NULL, 4, 'text', 'Sa', NULL, NULL, NULL, NULL, '{\"provider\":\"instagram\",\"raw\":{\"sender\":{\"id\":\"1597718291362764\"},\"recipient\":{\"id\":\"17841478140523860\"},\"timestamp\":1769454437020,\"message\":{\"mid\":\"aWdfZAG1faXRlbToxOklHTWVzc2FnZAUlEOjE3ODQxNDc4MTQwNTIzODYwOjM0MDI4MjM2Njg0MTcxMDMwMTI0NDI1OTk4MTAxOTA2Nzg3ODAwMjozMjY0MDY3MzE0OTc5OTc2NjczMjc4OTk0NTU2NTQ0NjE0NAZDZD\",\"text\":\"Sa\"}}}', '2026-01-26 19:07:15'),
(19, 1, 5, 'user', 2, NULL, 'text', 'as', NULL, NULL, NULL, NULL, '{\"delivery_error\":\"Instagram gönderim hatası: Page Access Token eksik. Settings > Entegrasyonlar > Instagram > Page Access Token girip Kaydet.\"}', '2026-01-26 19:07:20'),
(20, 1, 5, 'user', 2, NULL, 'text', 'as', NULL, NULL, NULL, NULL, '{\"delivery_error\":\"Instagram gönderim hatası: Page Access Token eksik. Settings > Entegrasyonlar > Instagram > Page Access Token girip Kaydet.\"}', '2026-01-26 19:09:21'),
(21, 1, 5, 'user', 2, NULL, 'text', 'da', NULL, NULL, NULL, NULL, '{\"delivery_error\":\"Instagram gönderim hatası: Page Access Token eksik. Settings > Entegrasyonlar > Instagram > Page Access Token girip Kaydet.\"}', '2026-01-26 19:10:55'),
(22, 1, 5, 'user', 2, NULL, 'text', 'fads', NULL, NULL, NULL, NULL, '{\"delivery_error\":\"Instagram gönderim hatası: Page Access Token eksik. Settings > Entegrasyonlar > Instagram > Page Access Token girip Kaydet.\"}', '2026-01-26 19:15:50'),
(23, 1, 5, 'user', 2, NULL, 'text', 'dsa', NULL, NULL, NULL, NULL, '{\"delivery_error\":\"Instagram gönderim hatası: Page Access Token eksik. Settings > Entegrasyonlar > Instagram > Page Access Token girip Kaydet.\"}', '2026-01-26 19:43:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(64) NOT NULL DEFAULT 'info',
  `title` varchar(190) NOT NULL,
  `body` text DEFAULT NULL,
  `entity_type` varchar(190) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `notifications`
--

INSERT INTO `notifications` (`id`, `tenant_id`, `user_id`, `type`, `title`, `body`, `entity_type`, `entity_id`, `is_read`, `read_at`, `created_at`) VALUES
(4, 1, 3, 'lead_created', 'Yeni lead (entegrasyon)', 'Instagram User 362764 • instagram • Durum: open', 'lead', 5, 1, '2026-01-29 19:25:13', '2026-01-26 19:07:15');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `key` varchar(64) NOT NULL,
  `name_tr` varchar(120) NOT NULL,
  `name_en` varchar(120) NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `roles`
--

INSERT INTO `roles` (`id`, `tenant_id`, `key`, `name_tr`, `name_en`, `is_system`, `created_at`) VALUES
(1, NULL, 'superadmin', 'Süperadmin', 'Super Admin', 1, '2026-01-25 14:41:06'),
(2, 1, 'tenant_admin', 'Danışan (Admin)', 'Tenant Admin', 1, '2026-01-25 14:41:06'),
(3, 1, 'staff', 'Çalışan', 'Staff', 1, '2026-01-25 14:41:06'),
(4, 1, 'customer', 'Müşteri', 'Customer', 1, '2026-01-25 14:41:06'),
(5, 2, 'tenant_admin', 'Danışan (Admin)', 'Tenant Admin', 1, '2026-01-25 14:42:54'),
(6, 2, 'staff', 'Çalışan', 'Staff', 1, '2026-01-25 14:42:54'),
(7, 2, 'customer', 'Müşteri', 'Customer', 1, '2026-01-25 14:42:54');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tenants`
--

CREATE TABLE `tenants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `tenants`
--

INSERT INTO `tenants` (`id`, `name`, `slug`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Tenant 1', 'tenant1', 'active', '2026-01-25 14:41:06', '2026-01-25 14:41:06'),
(2, 'Tenant 2', 'tenant2', 'active', '2026-01-25 14:42:54', '2026-01-25 14:42:54');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tenant_settings`
--

CREATE TABLE `tenant_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(120) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `threads`
--

CREATE TABLE `threads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `lead_id` bigint(20) UNSIGNED DEFAULT NULL,
  `contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `channel` varchar(32) NOT NULL DEFAULT 'internal',
  `integration_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject` varchar(190) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'open',
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `threads`
--

INSERT INTO `threads` (`id`, `tenant_id`, `lead_id`, `contact_id`, `channel`, `integration_account_id`, `subject`, `status`, `last_message_at`, `created_at`, `updated_at`) VALUES
(5, 1, 5, 4, 'instagram', 2, NULL, 'open', '2026-01-26 19:43:00', '2026-01-26 19:07:15', '2026-01-26 19:43:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `language` varchar(5) NOT NULL DEFAULT 'tr',
  `timezone` varchar(64) NOT NULL DEFAULT 'Europe/Istanbul',
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `tenant_id`, `role_id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `language`, `timezone`, `status`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 'Super Admin', 'super@marka.local', NULL, '$2y$10$7rgZcQJdYNqw2EE/vsDLouoem9urL2mHIbRp4dz.YD8R0Z7M.Uj0m', 'xEnJFmOjlStp7DopGWapa4y19AZKkiDVagzssryXlViaHz3J7RCTMwcVhifG', 'tr', 'Europe/Istanbul', 'active', NULL, '2026-01-25 14:41:06', '2026-01-29 18:16:49'),
(2, 1, 2, 'Tenant Admin', 'admin@tenant1.local', NULL, '$2y$10$7rgZcQJdYNqw2EE/vsDLouoem9urL2mHIbRp4dz.YD8R0Z7M.Uj0m', 'uVmOGMfHOojYiUomuXOjT2D7YY731EuuwiohsCopsTNWsvEqsM8e3NkRTMRX', 'tr', 'Europe/Istanbul', 'active', NULL, '2026-01-25 14:41:06', '2026-01-29 19:25:00'),
(3, 1, 3, 'Çalışan 1', 'staff@tenant1.local', NULL, '$2y$10$7rgZcQJdYNqw2EE/vsDLouoem9urL2mHIbRp4dz.YD8R0Z7M.Uj0m', 'KFiGHf3SSOQT2nblj1xMVcR25Yw5PqusUOqT9go050CXS1us1o6kpEW7dX80', 'tr', 'Europe/Istanbul', 'active', NULL, '2026-01-25 14:41:06', '2026-01-29 18:29:27'),
(4, 2, 5, 'Admin Tenant', 'admin@tenant2.com', NULL, '$2y$10$OiqqcJ/8Yc5ongTIltNPLuFq7b/uUF6m7ZpOqx49wFix4cdKokyGi', '1z90CdM2IZ05S67xpVtCdvDSV8owBrl8dcMo2M7VmGsqEYxgJjAJaUF3VoMp', 'tr', 'Europe/Istanbul', 'active', NULL, '2026-01-25 14:42:54', '2026-01-25 14:43:40');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_tenants`
--

CREATE TABLE `user_tenants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `user_tenants`
--

INSERT INTO `user_tenants` (`id`, `user_id`, `tenant_id`, `role_id`, `status`, `created_at`) VALUES
(1, 1, 1, 2, 'active', '2026-01-25 14:41:06');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `video_calls`
--

CREATE TABLE `video_calls` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `thread_id` bigint(20) UNSIGNED NOT NULL,
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `join_token` varchar(190) NOT NULL,
  `join_url` varchar(512) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `webhook_events`
--

CREATE TABLE `webhook_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(32) NOT NULL,
  `integration_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `direction` enum('in','out') NOT NULL DEFAULT 'in',
  `signature_valid` tinyint(1) NOT NULL DEFAULT 0,
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload_json`)),
  `received_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `webhook_events`
--

INSERT INTO `webhook_events` (`id`, `tenant_id`, `provider`, `integration_account_id`, `direction`, `signature_valid`, `payload_json`, `received_at`, `processed_at`) VALUES
(3, 1, 'instagram', 2, 'in', 0, '\"{\\\"object\\\":\\\"instagram\\\",\\\"entry\\\":[{\\\"time\\\":1769370720137,\\\"id\\\":\\\"17841478140523860\\\",\\\"messaging\\\":[{\\\"sender\\\":{\\\"id\\\":\\\"1597718291362764\\\"},\\\"recipient\\\":{\\\"id\\\":\\\"17841478140523860\\\"},\\\"timestamp\\\":1769370719788,\\\"message\\\":{\\\"mid\\\":\\\"aWdfZAG1faXRlbToxOklHTWVzc2FnZAUlEOjE3ODQxNDc4MTQwNTIzODYwOjM0MDI4MjM2Njg0MTcxMDMwMTI0NDI1OTk4MTAxOTA2Nzg3ODAwMjozMjYzOTEyODgzOTQ0NDgyNDcxMTQ0NTk4NTM0OTMzNzA4OAZDZD\\\",\\\"text\\\":\\\"selam knk\\\"}}]}]}\"', '2026-01-25 19:51:59', NULL),
(4, 1, 'instagram', 2, 'in', 0, '\"{\\\"object\\\":\\\"instagram\\\",\\\"entry\\\":[{\\\"time\\\":1769454437814,\\\"id\\\":\\\"17841478140523860\\\",\\\"messaging\\\":[{\\\"sender\\\":{\\\"id\\\":\\\"1597718291362764\\\"},\\\"recipient\\\":{\\\"id\\\":\\\"17841478140523860\\\"},\\\"timestamp\\\":1769454437020,\\\"message\\\":{\\\"mid\\\":\\\"aWdfZAG1faXRlbToxOklHTWVzc2FnZAUlEOjE3ODQxNDc4MTQwNTIzODYwOjM0MDI4MjM2Njg0MTcxMDMwMTI0NDI1OTk4MTAxOTA2Nzg3ODAwMjozMjY0MDY3MzE0OTc5OTc2NjczMjc4OTk0NTU2NTQ0NjE0NAZDZD\\\",\\\"text\\\":\\\"Sa\\\"}}]}]}\"', '2026-01-26 19:07:15', NULL);

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `ai_prompt_templates`
--
ALTER TABLE `ai_prompt_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_ai_templates_key_tenant` (`tenant_id`,`template_key`),
  ADD KEY `ix_ai_templates_tenant` (`tenant_id`);

--
-- Tablo için indeksler `ai_rules`
--
ALTER TABLE `ai_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_ai_rules_tenant` (`tenant_id`);

--
-- Tablo için indeksler `ai_suggestions`
--
ALTER TABLE `ai_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_ai_suggestions_tenant` (`tenant_id`),
  ADD KEY `ix_ai_suggestions_thread` (`tenant_id`,`thread_id`),
  ADD KEY `ix_ai_suggestions_user` (`tenant_id`,`user_id`),
  ADD KEY `fk_ai_suggestions_thread` (`thread_id`),
  ADD KEY `fk_ai_suggestions_message` (`message_id`),
  ADD KEY `fk_ai_suggestions_user` (`user_id`);

--
-- Tablo için indeksler `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_audit_tenant` (`tenant_id`),
  ADD KEY `ix_audit_actor` (`tenant_id`,`actor_user_id`),
  ADD KEY `ix_audit_action` (`tenant_id`,`action`),
  ADD KEY `fk_audit_actor` (`actor_user_id`);

--
-- Tablo için indeksler `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_calendar_tenant` (`tenant_id`),
  ADD KEY `ix_calendar_owner` (`tenant_id`,`owner_user_id`),
  ADD KEY `ix_calendar_starts` (`tenant_id`,`starts_at`),
  ADD KEY `fk_calendar_owner` (`owner_user_id`);

--
-- Tablo için indeksler `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_contacts_tenant` (`tenant_id`),
  ADD KEY `ix_contacts_external` (`external_id`),
  ADD KEY `ix_contacts_instagram` (`tenant_id`,`provider`,`instagram_user_id`);

--
-- Tablo için indeksler `domains`
--
ALTER TABLE `domains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_domains_host` (`host`),
  ADD KEY `ix_domains_tenant` (`tenant_id`);

--
-- Tablo için indeksler `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_failed_jobs_uuid` (`uuid`);

--
-- Tablo için indeksler `integration_accounts`
--
ALTER TABLE `integration_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_integration_accounts_tenant` (`tenant_id`),
  ADD KEY `ix_integration_accounts_provider` (`tenant_id`,`provider`);

--
-- Tablo için indeksler `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_jobs_queue` (`queue`);

--
-- Tablo için indeksler `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `knowledge_base_articles`
--
ALTER TABLE `knowledge_base_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_kb_tenant` (`tenant_id`),
  ADD KEY `ix_kb_type` (`tenant_id`,`type`);

--
-- Tablo için indeksler `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_leads_tenant` (`tenant_id`),
  ADD KEY `ix_leads_stage` (`tenant_id`,`stage_id`),
  ADD KEY `ix_leads_owner` (`tenant_id`,`owner_user_id`),
  ADD KEY `ix_leads_assigned` (`tenant_id`,`assigned_user_id`),
  ADD KEY `ix_leads_contact` (`tenant_id`,`contact_id`),
  ADD KEY `ix_leads_status` (`tenant_id`,`status`),
  ADD KEY `fk_leads_owner` (`owner_user_id`),
  ADD KEY `fk_leads_assigned` (`assigned_user_id`),
  ADD KEY `fk_leads_contact` (`contact_id`),
  ADD KEY `fk_leads_stage` (`stage_id`);

--
-- Tablo için indeksler `lead_notes`
--
ALTER TABLE `lead_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_lead_notes_tenant` (`tenant_id`),
  ADD KEY `ix_lead_notes_lead` (`tenant_id`,`lead_id`),
  ADD KEY `ix_lead_notes_user` (`tenant_id`,`user_id`),
  ADD KEY `fk_lead_notes_lead` (`lead_id`),
  ADD KEY `fk_lead_notes_user` (`user_id`);

--
-- Tablo için indeksler `lead_stages`
--
ALTER TABLE `lead_stages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_lead_stages_tenant` (`tenant_id`),
  ADD KEY `ix_lead_stages_sort` (`tenant_id`,`sort_order`);

--
-- Tablo için indeksler `lead_stage_events`
--
ALTER TABLE `lead_stage_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_lead_events_tenant` (`tenant_id`),
  ADD KEY `ix_lead_events_lead` (`tenant_id`,`lead_id`),
  ADD KEY `ix_lead_events_to_stage` (`tenant_id`,`to_stage_id`),
  ADD KEY `fk_lead_events_lead` (`lead_id`),
  ADD KEY `fk_lead_events_from_stage` (`from_stage_id`),
  ADD KEY `fk_lead_events_to_stage` (`to_stage_id`),
  ADD KEY `fk_lead_events_moved_by` (`moved_by_user_id`);

--
-- Tablo için indeksler `lists`
--
ALTER TABLE `lists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_lists_tenant` (`tenant_id`),
  ADD KEY `ix_lists_type` (`tenant_id`,`type`),
  ADD KEY `fk_lists_created_by` (`created_by_user_id`);

--
-- Tablo için indeksler `list_items`
--
ALTER TABLE `list_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_list_items_unique` (`list_id`,`entity_id`),
  ADD KEY `ix_list_items_tenant` (`tenant_id`),
  ADD KEY `ix_list_items_list` (`tenant_id`,`list_id`);

--
-- Tablo için indeksler `live_ai_coach_sessions`
--
ALTER TABLE `live_ai_coach_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_coach_tenant` (`tenant_id`),
  ADD KEY `ix_coach_thread` (`tenant_id`,`thread_id`),
  ADD KEY `fk_coach_thread` (`thread_id`),
  ADD KEY `fk_coach_user` (`user_id`);

--
-- Tablo için indeksler `mail_messages`
--
ALTER TABLE `mail_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_mail_tenant` (`tenant_id`),
  ADD KEY `ix_mail_status` (`tenant_id`,`status`),
  ADD KEY `ix_mail_lead` (`tenant_id`,`lead_id`),
  ADD KEY `ix_mail_contact` (`tenant_id`,`contact_id`),
  ADD KEY `fk_mail_lead` (`lead_id`),
  ADD KEY `fk_mail_contact` (`contact_id`);

--
-- Tablo için indeksler `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_messages_tenant` (`tenant_id`),
  ADD KEY `ix_messages_thread` (`tenant_id`,`thread_id`),
  ADD KEY `ix_messages_created` (`tenant_id`,`created_at`),
  ADD KEY `fk_messages_thread` (`thread_id`),
  ADD KEY `fk_messages_sender_user` (`sender_user_id`),
  ADD KEY `fk_messages_sender_contact` (`sender_contact_id`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_notifications_tenant` (`tenant_id`),
  ADD KEY `ix_notifications_user` (`tenant_id`,`user_id`,`is_read`,`created_at`),
  ADD KEY `fk_notifications_user` (`user_id`);

--
-- Tablo için indeksler `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_permissions_key` (`key`);

--
-- Tablo için indeksler `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_personal_access_tokens_token` (`token`),
  ADD KEY `ix_personal_access_tokens_tokenable` (`tokenable_type`,`tokenable_id`);

--
-- Tablo için indeksler `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_roles_tenant_key` (`tenant_id`,`key`),
  ADD KEY `ix_roles_tenant` (`tenant_id`);

--
-- Tablo için indeksler `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `ix_role_permissions_permission` (`permission_id`);

--
-- Tablo için indeksler `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_tenants_slug` (`slug`);

--
-- Tablo için indeksler `tenant_settings`
--
ALTER TABLE `tenant_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_tenant_settings_key` (`tenant_id`,`key`);

--
-- Tablo için indeksler `threads`
--
ALTER TABLE `threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_threads_tenant` (`tenant_id`),
  ADD KEY `ix_threads_lead` (`tenant_id`,`lead_id`),
  ADD KEY `ix_threads_contact` (`tenant_id`,`contact_id`),
  ADD KEY `ix_threads_last_message` (`tenant_id`,`last_message_at`),
  ADD KEY `fk_threads_lead` (`lead_id`),
  ADD KEY `fk_threads_contact` (`contact_id`),
  ADD KEY `fk_threads_integration_account` (`integration_account_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_users_email` (`email`),
  ADD KEY `ix_users_tenant` (`tenant_id`),
  ADD KEY `ix_users_role` (`role_id`);

--
-- Tablo için indeksler `user_tenants`
--
ALTER TABLE `user_tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_user_tenants_user_tenant` (`user_id`,`tenant_id`),
  ADD KEY `ix_user_tenants_tenant` (`tenant_id`),
  ADD KEY `ix_user_tenants_role` (`role_id`);

--
-- Tablo için indeksler `video_calls`
--
ALTER TABLE `video_calls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_video_calls_join_token` (`join_token`),
  ADD KEY `ix_video_calls_tenant` (`tenant_id`),
  ADD KEY `ix_video_calls_thread` (`tenant_id`,`thread_id`),
  ADD KEY `fk_video_calls_thread` (`thread_id`),
  ADD KEY `fk_video_calls_created_by` (`created_by_user_id`);

--
-- Tablo için indeksler `webhook_events`
--
ALTER TABLE `webhook_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_webhook_events_tenant` (`tenant_id`),
  ADD KEY `ix_webhook_events_provider` (`tenant_id`,`provider`),
  ADD KEY `ix_webhook_events_account` (`integration_account_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `ai_prompt_templates`
--
ALTER TABLE `ai_prompt_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `ai_rules`
--
ALTER TABLE `ai_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `ai_suggestions`
--
ALTER TABLE `ai_suggestions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Tablo için AUTO_INCREMENT değeri `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `domains`
--
ALTER TABLE `domains`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `integration_accounts`
--
ALTER TABLE `integration_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `knowledge_base_articles`
--
ALTER TABLE `knowledge_base_articles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `leads`
--
ALTER TABLE `leads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `lead_notes`
--
ALTER TABLE `lead_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `lead_stages`
--
ALTER TABLE `lead_stages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `lead_stage_events`
--
ALTER TABLE `lead_stage_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `lists`
--
ALTER TABLE `lists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `list_items`
--
ALTER TABLE `list_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `live_ai_coach_sessions`
--
ALTER TABLE `live_ai_coach_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `mail_messages`
--
ALTER TABLE `mail_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `tenant_settings`
--
ALTER TABLE `tenant_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `threads`
--
ALTER TABLE `threads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `user_tenants`
--
ALTER TABLE `user_tenants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `video_calls`
--
ALTER TABLE `video_calls`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `webhook_events`
--
ALTER TABLE `webhook_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `ai_prompt_templates`
--
ALTER TABLE `ai_prompt_templates`
  ADD CONSTRAINT `fk_ai_templates_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ai_rules`
--
ALTER TABLE `ai_rules`
  ADD CONSTRAINT `fk_ai_rules_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ai_suggestions`
--
ALTER TABLE `ai_suggestions`
  ADD CONSTRAINT `fk_ai_suggestions_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ai_suggestions_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ai_suggestions_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ai_suggestions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_audit_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `fk_calendar_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_calendar_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `fk_contacts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `domains`
--
ALTER TABLE `domains`
  ADD CONSTRAINT `fk_domains_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `integration_accounts`
--
ALTER TABLE `integration_accounts`
  ADD CONSTRAINT `fk_integration_accounts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `knowledge_base_articles`
--
ALTER TABLE `knowledge_base_articles`
  ADD CONSTRAINT `fk_kb_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `fk_leads_assigned` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_leads_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_leads_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_leads_stage` FOREIGN KEY (`stage_id`) REFERENCES `lead_stages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_leads_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `lead_notes`
--
ALTER TABLE `lead_notes`
  ADD CONSTRAINT `fk_lead_notes_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lead_notes_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lead_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `lead_stages`
--
ALTER TABLE `lead_stages`
  ADD CONSTRAINT `fk_lead_stages_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `lead_stage_events`
--
ALTER TABLE `lead_stage_events`
  ADD CONSTRAINT `fk_lead_events_from_stage` FOREIGN KEY (`from_stage_id`) REFERENCES `lead_stages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lead_events_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lead_events_moved_by` FOREIGN KEY (`moved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lead_events_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lead_events_to_stage` FOREIGN KEY (`to_stage_id`) REFERENCES `lead_stages` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `lists`
--
ALTER TABLE `lists`
  ADD CONSTRAINT `fk_lists_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lists_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `list_items`
--
ALTER TABLE `list_items`
  ADD CONSTRAINT `fk_list_items_list` FOREIGN KEY (`list_id`) REFERENCES `lists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_list_items_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `live_ai_coach_sessions`
--
ALTER TABLE `live_ai_coach_sessions`
  ADD CONSTRAINT `fk_coach_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_coach_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_coach_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `mail_messages`
--
ALTER TABLE `mail_messages`
  ADD CONSTRAINT `fk_mail_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mail_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mail_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_sender_contact` FOREIGN KEY (`sender_contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_messages_sender_user` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_messages_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `fk_roles_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `tenant_settings`
--
ALTER TABLE `tenant_settings`
  ADD CONSTRAINT `fk_tenant_settings_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `threads`
--
ALTER TABLE `threads`
  ADD CONSTRAINT `fk_threads_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_threads_integration_account` FOREIGN KEY (`integration_account_id`) REFERENCES `integration_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_threads_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_threads_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `user_tenants`
--
ALTER TABLE `user_tenants`
  ADD CONSTRAINT `fk_user_tenants_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_user_tenants_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_tenants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `video_calls`
--
ALTER TABLE `video_calls`
  ADD CONSTRAINT `fk_video_calls_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_video_calls_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_video_calls_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `webhook_events`
--
ALTER TABLE `webhook_events`
  ADD CONSTRAINT `fk_webhook_events_account` FOREIGN KEY (`integration_account_id`) REFERENCES `integration_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_webhook_events_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
