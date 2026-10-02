-- Revente d'actions EMOTION entre actionnaires
-- A exécuter une seule fois sur la base de app_apis.

CREATE TABLE IF NOT EXISTS `vente_actions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendeur` BIGINT UNSIGNED NOT NULL COMMENT 'customer.id du vendeur',
  `vendeur_phone` VARCHAR(30) NULL,
  `projet_action_id` BIGINT UNSIGNED NOT NULL COMMENT 'projet_actions.id détenu par le vendeur',
  `projet` BIGINT UNSIGNED NOT NULL COMMENT 'projets.id',
  `nbr_action` INT NOT NULL,
  `nbr_action_vendu` INT NOT NULL DEFAULT 0,
  `price_action` INT NOT NULL COMMENT 'prix unitaire = projets.price_action',
  `statut` VARCHAR(20) NOT NULL DEFAULT 'en_vente' COMMENT 'en_vente | vendu | annule',
  `param1` VARCHAR(255) NULL,
  `param2` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `vente_actions_statut` (`statut`),
  KEY `vente_actions_vendeur` (`vendeur`),
  KEY `vente_actions_projet_action` (`projet_action_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vente_action_achats` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vente_action` BIGINT UNSIGNED NOT NULL COMMENT 'vente_actions.id',
  `acheteur` BIGINT UNSIGNED NOT NULL COMMENT 'customer.id de l acheteur',
  `acheteur_phone` VARCHAR(30) NULL,
  `nbr_action` INT NOT NULL,
  `price_action` INT NOT NULL,
  `montant` INT NOT NULL COMMENT 'nbr_action x price_action, hors frais',
  `statut` VARCHAR(20) NOT NULL DEFAULT 'reserve' COMMENT 'reserve | paye | annule | a_rembourser',
  `id_trans` BIGINT UNSIGNED NULL COMMENT 'historiquetrans.id du paiement',
  `paye_at` TIMESTAMP NULL,
  `param1` VARCHAR(255) NULL,
  `param2` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `vente_action_achats_vente` (`vente_action`),
  KEY `vente_action_achats_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
