CREATE DATABASE OptiRH_DB;

CREATE TABLE Users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    motDePasse VARCHAR(255) NOT NULL,
    role ENUM('Administrateur', 'Chef_Projet', 'Employe', 'Candidat', 'Gestionnaire_Parc_auto', 'DQHS'),
    address VARCHAR(255)
);

CREATE TABLE OffreEmploi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poste VARCHAR(100),
    description TEXT,
    status VARCHAR(50),
    utilisateur_id INT,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(id),
    ON DELETE CASCADE,
    ON UPDATE CASCADE
);
-- ############################################################
-- # Mise à jour :
-- # Auteur : mariem-jls
-- # Description : Changements dans la table OffresEmploi 
-- ############################################################
CREATE TABLE Offre (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poste VARCHAR(100),
    description TEXT,
    statut VARCHAR(50));
-- ############################################################
-- # Mise à jour :
-- # Auteur : mariem-jls
-- # Description : Ajout d'une colonne
-- ############################################################
ALTER TABLE Offre ADD COLUMN date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

CREATE TABLE Demande (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status VARCHAR(50),
    date DATE,
    description TEXT,
    utilisateur_id INT,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(id),
    ON DELETE CASCADE,
    ON UPDATE CASCADE
);
-- ############################################################
-- # Mise à jour :
-- # Auteur : mariem-jls
-- # Description : Changements dans la table Demande 
-- ############################################################
    ALTER TABLE demande MODIFY COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
-- ############################################################
    ALTER TABLE demande CHANGE COLUMN status statut ENUM('En attente', 'Acceptée', 'Refusée') DEFAULT 'En attente';
-- ############################################################
    ALTER TABLE demande MODIFY statut ENUM('EN_ATTENTE', 'ACCEPTEE', 'REFUSEE');
-- ############################################################
    ALTER TABLE demande ADD COLUMN fichier_piece_jointe VARCHAR(255) NULL;
-- ############################################################
    ALTER TABLE demande ADD COLUMN offre_id INT NOT NULL;
    ALTER TABLE demande ADD CONSTRAINT fk_demande_offre FOREIGN KEY (offre_id) REFERENCES offre(id) ON DELETE CASCADE;
-- ############################################################
-- # Mise à jour :
-- # Auteur : mariem-jls
-- # Description : Ajouts de colonnes dans la table Offre 
-- ############################################################
    ALTER TABLE Offre ADD COLUMN mode_travail VARCHAR(50);  -- Présentiel, Hybride, Télétravail
    ALTER TABLE Offre ADD COLUMN type_contrat VARCHAR(50);   -- CDI, CDD, Stage, Freelance...
    ALTER TABLE Offre ADD COLUMN localisation VARCHAR(100);  -- Ville, pays ou télétravail
    ALTER TABLE Offre ADD COLUMN niveau_experience VARCHAR(50); -- Débutant, Junior, Senior...
    ALTER TABLE Offre ADD COLUMN nb_postes INT;             -- Nombre de postes ouverts
    ALTER TABLE Offre ADD COLUMN date_expiration DATE;      -- Date limite de candidature
-- ############################################################
-- # Mise à jour :
-- # Auteur : mariem-jls
-- # Description : Ajouts de colonnes dans la table Demande 
-- ############################################################
    ALTER TABLE demande  
    ADD COLUMN nom_complet VARCHAR(255) NOT NULL,  
    ADD COLUMN email VARCHAR(255) NOT NULL,  
    ADD COLUMN telephone VARCHAR(20) NOT NULL,  
    ADD COLUMN adresse VARCHAR(255),  
    ADD COLUMN date_debut_disponible DATE,  
    ADD COLUMN situation_actuelle VARCHAR(100);

CREATE TABLE `reclamation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` text COLLATE utf8mb4_general_ci,
  `date` date DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`)
)
CREATE TABLE reponse (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` text COLLATE utf8mb4_general_ci,
  `date` date DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reclamation_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reclamation_id` (`reclamation_id`)
)

CREATE TABLE Conges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dateDebut DATE,
    dateFin DATE,
    status VARCHAR(50),
    utilisateur_id INT,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(id) ON DELETE CASCADE
);
CREATE TABLE `missions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('To Do','In Progress','Done') DEFAULT 'To Do',
  `project_id` INT(11) DEFAULT NULL,
  `assigned_to` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_terminer` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `missions_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `missions_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `projects` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
