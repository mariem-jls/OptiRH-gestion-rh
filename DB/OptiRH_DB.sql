-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 05 mars 2025 à 10:14
-- Version du serveur : 8.2.0
-- Version de PHP : 8.1.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `optirh`
--

-- --------------------------------------------------------

--
-- Structure de la table `demande`
--

DROP TABLE IF EXISTS `demande`;
CREATE TABLE IF NOT EXISTS `demande` (
  `id` int NOT NULL,
  `statut` enum('EN_ATTENTE','ACCEPTEE','REFUSEE') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` text COLLATE utf8mb4_general_ci,
  `offre_id` int NOT NULL,
  `fichier_piece_jointe` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nom_complet` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_debut_disponible` date DEFAULT NULL,
  `situation_actuelle` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evenement`
--

DROP TABLE IF EXISTS `evenement`;
CREATE TABLE IF NOT EXISTS `evenement` (
  `id_evenement` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `lieu` varchar(255) NOT NULL,
  `description` text,
  `prix` decimal(10,2) DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `heure` time NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `latitude` decimal(10,6) NOT NULL,
  `status` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_evenement`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `missions`
--

DROP TABLE IF EXISTS `missions`;
CREATE TABLE IF NOT EXISTS `missions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `status` enum('To Do','In Progress','Done') COLLATE utf8mb4_general_ci DEFAULT 'To Do',
  `project_id` int DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_terminer` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `offre`
--

DROP TABLE IF EXISTS `offre`;
CREATE TABLE IF NOT EXISTS `offre` (
  `id` int NOT NULL,
  `poste` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `statut` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mode_travail` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type_contrat` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `localisation` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `niveau_experience` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nb_postes` int DEFAULT NULL,
  `date_expiration` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE IF NOT EXISTS `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reclamation`
--

DROP TABLE IF EXISTS `reclamation`;
CREATE TABLE IF NOT EXISTS `reclamation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date` date DEFAULT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reponse`
--

DROP TABLE IF EXISTS `reponse`;
CREATE TABLE IF NOT EXISTS `reponse` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date` date DEFAULT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reclamation_id` int DEFAULT NULL,
  `rating` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `reclamation_id` (`reclamation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reservationtrajet`
--

DROP TABLE IF EXISTS `reservationtrajet`;
CREATE TABLE IF NOT EXISTS `reservationtrajet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `disponibilite` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `users_id` int DEFAULT NULL,
  `trajet_id` int DEFAULT NULL,
  `vehicule_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `trajet_id` (`trajet_id`),
  KEY `fk_vehicule_id` (`vehicule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reservation_evenement`
--

DROP TABLE IF EXISTS `reservation_evenement`;
CREATE TABLE IF NOT EXISTS `reservation_evenement` (
  `id_participation` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `id_evenement` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `date_reservation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_participation`),
  KEY `fk_reservation_evenement` (`id_evenement`),
  KEY `fk_reservation_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `resultat_analyse`
--

DROP TABLE IF EXISTS `resultat_analyse`;
CREATE TABLE IF NOT EXISTS `resultat_analyse` (
  `id` int NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `experience` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `technologies` text COLLATE utf8mb4_general_ci,
  `linkedin` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `trajet`
--

DROP TABLE IF EXISTS `trajet`;
CREATE TABLE IF NOT EXISTS `trajet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `station` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `depart` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `arrive` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `longitude_depart` double DEFAULT NULL,
  `latitude_depart` double DEFAULT NULL,
  `longitude_arrivee` double DEFAULT NULL,
  `latitude_arrivee` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `motDePasse` varchar(255) NOT NULL,
  `role` enum('Administrateur','Chef_Projet','Employe','Candidat','Gestionnaire_Parc_auto','DQHS') DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `vehicule`
--

DROP TABLE IF EXISTS `vehicule`;
CREATE TABLE IF NOT EXISTS `vehicule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nbrPlace` int DEFAULT NULL,
  `disponibilite` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `trajet_id` int DEFAULT NULL,
  `nbrReservation` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_trajet_vehicule` (`trajet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `missions`
--
ALTER TABLE `missions`
  ADD CONSTRAINT `missions_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  ADD CONSTRAINT `missions_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD CONSTRAINT `reclamation_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `reponse`
--
ALTER TABLE `reponse`
  ADD CONSTRAINT `reponse_ibfk_1` FOREIGN KEY (`reclamation_id`) REFERENCES `reclamation` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `reservationtrajet`
--
ALTER TABLE `reservationtrajet`
  ADD CONSTRAINT `fk_vehicule_id` FOREIGN KEY (`vehicule_id`) REFERENCES `vehicule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reservationtrajet_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reservationtrajet_ibfk_2` FOREIGN KEY (`trajet_id`) REFERENCES `trajet` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `reservation_evenement`
--
ALTER TABLE `reservation_evenement`
  ADD CONSTRAINT `fk_reservation_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reservation_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `vehicule`
--
ALTER TABLE `vehicule`
  ADD CONSTRAINT `fk_trajet_vehicule` FOREIGN KEY (`trajet_id`) REFERENCES `trajet` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
