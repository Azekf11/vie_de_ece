-- code.sql
-- Création de la base de données et des tables pour Vie d'ECE
CREATE DATABASE IF NOT EXISTS viedece
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE viedece;

-- Table des VdECE (anecdotes)
CREATE TABLE vdece (
  id INT AUTO_INCREMENT PRIMARY KEY,              -- identifiant unique
  pseudo VARCHAR(50) NOT NULL,                    -- pseudo de l'auteur
  content TEXT NOT NULL,                          -- contenu de la VdECE
  created_at DATETIME NOT NULL
    DEFAULT CURRENT_TIMESTAMP                     -- date de création
) ENGINE=InnoDB;

-- Table des commentaires
CREATE TABLE comments (
  id INT AUTO_INCREMENT PRIMARY KEY,              -- identifiant unique du commentaire
  vde_id INT NOT NULL,                            -- référence à la VdECE commentée
  pseudo VARCHAR(50) NOT NULL,                    -- pseudo du commentateur
  comment TEXT NOT NULL,                          -- texte du commentaire
  created_at DATETIME NOT NULL
    DEFAULT CURRENT_TIMESTAMP,                    -- date du commentaire
  FOREIGN KEY (vde_id) REFERENCES vdece(id)
    ON DELETE CASCADE                             -- suppression en cascade
) ENGINE=InnoDB;