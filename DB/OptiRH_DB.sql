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