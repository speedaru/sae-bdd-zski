-- ==========================================================
-- Script de création de la base de données Zarza-Ski
-- ==========================================================

-- 1. NETTOYAGE
DROP TABLE IF EXISTS preference CASCADE;
DROP TABLE IF EXISTS facturation CASCADE;
DROP TABLE IF EXISTS reserver CASCADE;
DROP TABLE IF EXISTS reservation CASCADE;
DROP TABLE IF EXISTS gestion_voyageurs CASCADE;
DROP TABLE IF EXISTS groupe CASCADE;
DROP TABLE IF EXISTS compte_utilisateur CASCADE;
DROP TABLE IF EXISTS client CASCADE;
DROP TABLE IF EXISTS chambre CASCADE;
DROP TABLE IF EXISTS formule CASCADE;

DROP TYPE IF EXISTS pref_level CASCADE;
DROP TYPE IF EXISTS ski_level CASCADE;
DROP TYPE IF EXISTS vue_type CASCADE;
DROP TYPE IF EXISTS role_compte CASCADE;

-- 2. TYPES ENUMÉRÉS
CREATE TYPE pref_level AS ENUM ('impératif', 'Souhaitable', 'Pas souhaitable', 'Interdit');
CREATE TYPE ski_level AS ENUM ('débutant', 'moyen', 'confirmé');
CREATE TYPE vue_type AS ENUM ('parking', 'pistes');
CREATE TYPE role_compte AS ENUM ('admin', 'gestionnaire', 'client');

-- 3. TABLES INDÉPENDANTES

CREATE TABLE client (
    id_client SERIAL PRIMARY KEY,
    nom VARCHAR(48) NOT NULL,
    prenom VARCHAR(48) NOT NULL,
    adresse VARCHAR(128) NOT NULL,
    num_tel VARCHAR(14) NOT NULL,
    niveau_ski ski_level NOT NULL,
    taille NUMERIC(3,2) NOT NULL,
    poids SMALLINT NOT NULL,
    pointure NUMERIC(3,1) NOT NULL,
    date_naissance DATE NOT NULL
);

CREATE TABLE formule (
    type_formule VARCHAR(24) PRIMARY KEY,
    prix_base INTEGER NOT NULL
);

CREATE TABLE chambre (
    num_chambre SERIAL PRIMARY KEY,
    etage SMALLINT NOT NULL,
    batiment CHAR(1) NOT NULL,
    nb_lits SMALLINT NOT NULL,
    superficie SMALLINT NOT NULL,
    type_vue vue_type NOT NULL,
    balcon_present BOOLEAN NOT NULL
);

-- 4. TABLES DÉPENDANTES (HIÉRARCHIQUES)

-- Compte lié au client principal
CREATE TABLE compte_utilisateur (
    id_user SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    mdp_hash VARCHAR(255) NOT NULL,
    role role_compte NOT NULL DEFAULT 'client',
    id_client INTEGER REFERENCES client(id_client) ON DELETE SET NULL
);

-- Carnet d'adresses : Quels clients sont gérés par quel compte ?
CREATE TABLE gestion_voyageurs (
    id_user INTEGER REFERENCES compte_utilisateur(id_user) ON DELETE CASCADE,
    id_client INTEGER REFERENCES client(id_client) ON DELETE CASCADE,
    PRIMARY KEY (id_user, id_client)
);

-- Un groupe appartient à un compte (chef de groupe)
CREATE TABLE groupe (
    nom_groupe VARCHAR(48) PRIMARY KEY,
    id_user INTEGER NOT NULL REFERENCES compte_utilisateur(id_user) ON DELETE CASCADE
);

-- Une réservation appartient à un groupe
CREATE TABLE reservation (
    id_reservation SERIAL PRIMARY KEY,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    nom_groupe VARCHAR(48) NOT NULL REFERENCES groupe(nom_groupe) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT check_dates CHECK (date_fin > date_debut)
);

-- Table de liaison centrale : Qui occupe quelle chambre sous quelle formule ?
CREATE TABLE reserver (
    id_client INTEGER REFERENCES client(id_client) ON DELETE CASCADE,
    id_reservation INTEGER REFERENCES reservation(id_reservation) ON DELETE CASCADE,
    num_chambre INTEGER NOT NULL REFERENCES chambre(num_chambre),
    type_formule VARCHAR(24) NOT NULL REFERENCES formule(type_formule),
    occupe_lit BOOLEAN DEFAULT TRUE,
    formule_prix_final INTEGER NOT NULL,
    PRIMARY KEY (id_client, id_reservation)
);

CREATE TABLE facturation (
    id_facture SERIAL PRIMARY KEY,
    montant_total INTEGER NOT NULL,
    date_emission DATE DEFAULT CURRENT_DATE,
    id_reservation INTEGER NOT NULL REFERENCES reservation(id_reservation) ON DELETE CASCADE,
    num_chambre INTEGER NOT NULL REFERENCES chambre(num_chambre)
);

-- Préférences entre clients (ex: veut dormir avec / ne veut pas dormir avec)
CREATE TABLE preference (
    id_client INTEGER REFERENCES client(id_client) ON DELETE CASCADE,
    id_client_1 INTEGER REFERENCES client(id_client) ON DELETE CASCADE,
    niveau_preference pref_level NOT NULL,
    PRIMARY KEY (id_client, id_client_1),
    CONSTRAINT no_self_pref CHECK (id_client <> id_client_1)
);

-- ==========================================================
-- 5. JEU D'ESSAI RÉVISÉ
-- ==========================================================

-- 1. Création des clients physiques
INSERT INTO client (nom, prenom, adresse, num_tel, niveau_ski, taille, poids, pointure) VALUES
('Durand', 'Jean', '12 rue des Pins, Paris', '0601020304', 'moyen', 1.80, 75, 43.0, '1985-04-12'),
('Durand', 'Marie', '12 rue des Pins, Paris', '0605060708', 'confirmé', 1.65, 60, 38.0, '1990-11-23');

-- 2. Création du compte pour Jean
INSERT INTO compte_utilisateur (username, mdp_hash, role, id_client) VALUES
('j.durand', '$2y$10$e0MYzXy..6L.88H1L7L9e.lE5QO.mX5I.mX5I.mX5I.mX5I.mX5I.', 'client', 1);

-- 3. Jean ajoute Marie à son carnet de voyageurs
INSERT INTO gestion_voyageurs (id_user, id_client) VALUES (1, 2);

-- 4. Jean crée un groupe pour ses vacances
INSERT INTO groupe (nom_groupe, id_user) VALUES ('Ski Famille 2025', 1);

-- 5. Mise en place de l'environnement (Formules et Chambres)
INSERT INTO formule (type_formule, prix_base) VALUES ('Non skieur', 420), ('Tout compris', 510);
INSERT INTO chambre (num_chambre, etage, batiment, nb_lits, superficie, type_vue, balcon_present) VALUES
(105, 1, 'B', 2, 18, 'parking', false),
(227, 2, 'A', 4, 25, 'pistes', true);
SELECT setval(pg_get_serial_sequence('chambre', 'num_chambre'), MAX(num_chambre)) FROM chambre;

-- 6. Création de la réservation pour le groupe
INSERT INTO reservation (date_debut, date_fin, nom_groupe) VALUES ('2025-12-20', '2025-12-27', 'Ski Famille 2025');

-- 7. Détails des occupants (Jean et Marie dans la 227)
INSERT INTO reserver (id_client, id_reservation, num_chambre, type_formule, occupe_lit, formule_prix_final) VALUES
(1, 1, 227, 'Tout compris', true, 510),
(2, 1, 227, 'Tout compris', true, 510);

-- 8. Génération de la facture
INSERT INTO facturation (montant_total, id_reservation, num_chambre) VALUES (1020, 1, 227);
