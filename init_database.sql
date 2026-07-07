-- ============================================================
--  Script d'initialisation de la base de données
--  Application : Gestion des Utilisateurs
--  Moteur      : MySQL 8.x
-- ============================================================

-- 1. Créer la base de données si elle n'existe pas
CREATE DATABASE IF NOT EXISTS agil_2026
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE agil_2026;

-- ============================================================
-- 2. Table des utilisateurs
-- ============================================================
CREATE TABLE IF NOT EXISTS utilisateurs (
    id           VARCHAR(36)  NOT NULL PRIMARY KEY COMMENT 'UUID généré côté application',
    nom          VARCHAR(100) NOT NULL              COMMENT 'Nom complet',
    email        VARCHAR(150) NOT NULL UNIQUE       COMMENT 'Adresse e-mail (identifiant de connexion)',
    mot_de_passe VARCHAR(64)  NOT NULL              COMMENT 'Hash SHA-256 du mot de passe',
    role         ENUM('ADMIN','GERANT','FOURNISSEUR','CLIENT')
                              NOT NULL DEFAULT 'CLIENT',
    statut       VARCHAR(20)  NOT NULL DEFAULT 'Actif' COMMENT 'Actif ou Inactif',
    cree_le      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Table des catégories de produits
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. Table des produits
-- ============================================================
CREATE TABLE IF NOT EXISTS produits (
    id              INT            NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reference       VARCHAR(50)    NOT NULL UNIQUE        COMMENT 'Référence unique du produit',
    designation     VARCHAR(200)   NOT NULL               COMMENT 'Nom du produit',
    description     TEXT,
    prix            DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    quantite_stock  INT            NOT NULL DEFAULT 0,
    seuil_alerte    INT            NOT NULL DEFAULT 5     COMMENT 'Alerte si stock <= seuil',
    categorie_id    INT,
    fournisseur_id  VARCHAR(36),
    cree_le         DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_produit_categorie
        FOREIGN KEY (categorie_id)   REFERENCES categories(id)   ON DELETE SET NULL,
    CONSTRAINT fk_produit_fournisseur
        FOREIGN KEY (fournisseur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. Table des commandes
-- ============================================================
CREATE TABLE IF NOT EXISTS commandes (
    id            INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    numero        VARCHAR(30)   NOT NULL UNIQUE         COMMENT 'Ex: CMD-2026-001',
    client_id     VARCHAR(36),
    montant_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    statut        ENUM('En attente','Confirmée','Expédiée','Livrée','Annulée')
                                NOT NULL DEFAULT 'En attente',
    cree_le       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_le    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_commande_client
        FOREIGN KEY (client_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. Table des lignes de commande
-- ============================================================
CREATE TABLE IF NOT EXISTS lignes_commande (
    id            INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    commande_id   INT           NOT NULL,
    produit_id    INT,
    quantite      INT           NOT NULL DEFAULT 1,
    prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_ligne_commande
        FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ligne_produit
        FOREIGN KEY (produit_id)  REFERENCES produits(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Notes :
--  - L'application crée automatiquement les tables au démarrage
--  - Les utilisateurs par défaut sont insérés automatiquement
--  - Pour réinitialiser :
--    DROP TABLE IF EXISTS lignes_commande, commandes, produits, categories, utilisateurs;
-- ============================================================
