-- Création de la table des campagnes
CREATE TABLE `addon_drip_campaigns` (
  `_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `_uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendors__id` int(10) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1: Active, 2: Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`_id`),
  UNIQUE KEY `addon_drip_campaigns__uid_unique` (`_uid`),
  KEY `addon_drip_campaigns_vendors__id_index` (`vendors__id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Création de la table des étapes (avec la nouvelle structure de délai précis)
CREATE TABLE `addon_drip_steps` (
  `_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `_uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `addon_drip_campaigns__id` bigint(20) unsigned NOT NULL,
  `delay_value` int(11) NOT NULL DEFAULT 0 COMMENT '0 = immediate',
  `delay_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'days',
  `whatsapp_templates__id` int(10) unsigned DEFAULT NULL,
  `custom_message` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`_id`),
  UNIQUE KEY `addon_drip_steps__uid_unique` (`_uid`),
  KEY `addon_drip_steps_addon_drip_campaigns__id_index` (`addon_drip_campaigns__id`),
  CONSTRAINT `addon_drip_steps_addon_drip_campaigns__id_foreign` FOREIGN KEY (`addon_drip_campaigns__id`) REFERENCES `addon_drip_campaigns` (`_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Création de la table des abonnés
CREATE TABLE `addon_drip_subscribers` (
  `_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `_uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `addon_drip_campaigns__id` bigint(20) unsigned NOT NULL,
  `contacts__id` int(10) unsigned NOT NULL,
  `start_date` datetime NOT NULL,
  `last_step_id` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1: Active, 2: Completed, 3: Unsubscribed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`_id`),
  UNIQUE KEY `addon_drip_subscribers__uid_unique` (`_uid`),
  KEY `addon_drip_subscribers_addon_drip_campaigns__id_index` (`addon_drip_campaigns__id`),
  KEY `addon_drip_subscribers_contacts__id_index` (`contacts__id`),
  CONSTRAINT `addon_drip_subscribers_addon_drip_campaigns__id_foreign` FOREIGN KEY (`addon_drip_campaigns__id`) REFERENCES `addon_drip_campaigns` (`_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modification de la table existante bot_replies pour ajouter la liaison avec la campagne Drip
ALTER TABLE `bot_replies` 
ADD COLUMN `addon_drip_campaigns__id` bigint(20) unsigned DEFAULT NULL,
ADD CONSTRAINT `fk_bot_replies_drip_campaign_id` FOREIGN KEY (`addon_drip_campaigns__id`) REFERENCES `addon_drip_campaigns` (`_id`) ON DELETE SET NULL;
