-- Script de création de la base de données Zarza-Ski
-- Version PostgreSQL (Banalisée pour rendu SAE 2-04)
-- Binôme : Anis ZIANE / Iegor TAFTAI

-- ==========================================================
-- 1. RÉINITIALISATION (Nettoyage avant création)
-- ==========================================================
DROP TABLE IF EXISTS preference CASCADE;
DROP TABLE IF EXISTS tarif_formule CASCADE;
DROP TABLE IF EXISTS attribution_chambre CASCADE;
DROP TABLE IF EXISTS sejour CASCADE;
DROP TABLE IF EXISTS facturation CASCADE;
DROP TABLE IF EXISTS chambre CASCADE;
DROP TABLE IF EXISTS client CASCADE;
DROP TABLE IF EXISTS semaine CASCADE;
DROP TABLE IF EXISTS groupe CASCADE;
DROP TABLE IF EXISTS formule CASCADE;
DROP TABLE IF EXISTS compte_utilisateur CASCADE;

-- Suppression des types personnalisés s'ils existent
DROP TYPE IF EXISTS pref_level CASCADE;
DROP TYPE IF EXISTS ski_level CASCADE;
DROP TYPE IF EXISTS vue_type CASCADE;
DROP TYPE IF EXISTS role_compte CASCADE;

-- ==========================================================
-- 2. CRÉATION DES TYPES (ENUMERATIONS)
-- ==========================================================
CREATE TYPE pref_level AS ENUM ('impératif', 'Souhaitable', 'Pas souhaitable', 'Interdit');
CREATE TYPE ski_level AS ENUM ('débutant', 'moyen', 'confirmé');
CREATE TYPE vue_type AS ENUM ('parking', 'pistes');
CREATE TYPE role_compte AS ENUM ('admin', 'gestionnaire', 'client');

-- ==========================================================
-- 3. CRÉATION DES TABLES
-- ==========================================================

CREATE TABLE groupe (
    nom_groupe VARCHAR(48) PRIMARY KEY
);

CREATE TABLE semaine (
    debut DATE PRIMARY KEY,
    fin DATE NOT NULL
);

CREATE TABLE chambre (
    num_chambre BIGINT PRIMARY KEY,
    etage SMALLINT NOT NULL,
    batiment CHARACTER(1) NOT NULL,
    nb_lits SMALLINT NOT NULL,
    superficie SMALLINT NOT NULL,
    type_vue vue_type NOT NULL,
    balcon_present BOOLEAN NOT NULL
);

CREATE TABLE client (
    id_client SERIAL PRIMARY KEY,
    nom VARCHAR(48) NOT NULL,
    prenom VARCHAR(48) NOT NULL,
    date_naissance DATE NOT NULL,
    adresse VARCHAR(64) NOT NULL,
    num_tel VARCHAR(14) NOT NULL,
    niveau_ski ski_level NOT NULL,
    taille NUMERIC(3,2) NOT NULL,
    poids SMALLINT NOT NULL,
    pointure NUMERIC(3,1) NOT NULL
);

CREATE TABLE formule (
    type_formule VARCHAR(24) PRIMARY KEY,
    prix_base INTEGER NOT NULL
);

CREATE TABLE compte_utilisateur (
    id_user SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    mdp_hash VARCHAR(255) NOT NULL,
    role role_compte NOT NULL,
    id_client BIGINT REFERENCES client(id_client) ON DELETE SET NULL
);

-- ==========================================================
-- 4. TABLES DE RELATIONS ET CONTRAINTES
-- ==========================================================

CREATE TABLE sejour (
    id_client BIGINT REFERENCES client(id_client),
    nom_groupe VARCHAR(48) REFERENCES groupe(nom_groupe),
    debut DATE REFERENCES semaine(debut),
    PRIMARY KEY (id_client, nom_groupe, debut)
);

CREATE TABLE attribution_chambre (
    id_client BIGINT REFERENCES client(id_client),
    num_chambre BIGINT REFERENCES chambre(num_chambre),
    debut DATE REFERENCES semaine(debut),
    PRIMARY KEY (id_client, num_chambre, debut)
);

CREATE TABLE tarif_formule (
    id_client BIGINT REFERENCES client(id_client),
    debut DATE REFERENCES semaine(debut),
    type_formule VARCHAR(24) REFERENCES formule(type_formule),
    formule_prix_final INTEGER NOT NULL,
    PRIMARY KEY (id_client, debut, type_formule)
);

CREATE TABLE preference (
    fk_id_client_emmeteur BIGINT REFERENCES client(id_client),
    fk_id_client_receveur BIGINT REFERENCES client(id_client),
    niveau_preference pref_level,
    PRIMARY KEY (fk_id_client_emmeteur, fk_id_client_receveur)
);

CREATE TABLE facturation (
    nom_groupe VARCHAR(48) REFERENCES groupe(nom_groupe),
    num_chambre BIGINT REFERENCES chambre(num_chambre),
    debut DATE REFERENCES semaine(debut),
    montant_total INTEGER NOT NULL,
    PRIMARY KEY (nom_groupe, num_chambre, debut)
);

-- ==========================================================
-- 5. INSERTION DU JEU D'ESSAI
-- ==========================================================

INSERT INTO groupe VALUES ('Famille Durand');

INSERT INTO semaine VALUES 
('2020-12-20', '2020-12-26'), 
('2020-12-27', '2021-01-02');

INSERT INTO chambre VALUES 
(105, 1, 'B', 2, 18, 'parking', false), 
(227, 2, 'A', 4, 25, 'pistes', true);

INSERT INTO client (nom, prenom, date_naissance, adresse, num_tel, niveau_ski, taille, poids, pointure) 
VALUES 
('Durand', 'Jean', '1985-05-15', '12 rue des Pins, Paris', '0601020304', 'moyen', 1.80, 75, 43.0),
('Durand', 'Marie', '1987-09-20', '12 rue des Pins, Paris', '0605060708', 'confirmé', 1.65, 60, 38.0),
('Durand', 'Lucas', '2012-03-10', '12 rue des Pins, Paris', '0609101112', 'débutant', 1.40, 35, 34.0);

INSERT INTO formule VALUES 
('Non skieur', 420), 
('Tout compris', 510);

INSERT INTO attribution_chambre VALUES 
(1, 227, '2020-12-20'), 
(2, 227, '2020-12-20'), 
(3, 227, '2020-12-20');

INSERT INTO sejour VALUES 
(1, 'Famille Durand', '2020-12-20'), 
(2, 'Famille Durand', '2020-12-20'), 
(3, 'Famille Durand', '2020-12-20');

INSERT INTO tarif_formule VALUES 
(1, '2020-12-20', 'Tout compris', 510), 
(2, '2020-12-20', 'Tout compris', 510), 
(3, '2020-12-20', 'Tout compris', 408);

INSERT INTO compte_utilisateur (username, mdp_hash, role, id_client) VALUES
('j.durand', '$2y$10$e0MYzXy..6L.88H1L7L9e.lE5QO.mX5I.mX5I.mX5I.mX5I.mX5I.', 'client', 1);
