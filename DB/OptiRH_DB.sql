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

CREATE TABLE Reclamation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    description TEXT,
    date DATE,
    status VARCHAR(50),
    utilisateur_id INT,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(id),
    ON DELETE CASCADE,
    ON UPDATE CASCADE
);

CREATE TABLE Conges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dateDebut DATE,
    dateFin DATE,
    status VARCHAR(50),
    utilisateur_id INT,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(id) ON DELETE CASCADE
);
