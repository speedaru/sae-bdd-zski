-- ==========================================================
-- Script de création de la base de données Zarza-Ski
-- ==========================================================

-- NETTOYAGE
DROP TABLE IF EXISTS preference CASCADE;
DROP TABLE IF EXISTS facturation CASCADE;
DROP TABLE IF EXISTS reserver CASCADE;
DROP TABLE IF EXISTS compte_utilisateur CASCADE;
DROP TABLE IF EXISTS reservation CASCADE;
DROP TABLE IF EXISTS chambre CASCADE;
DROP TABLE IF EXISTS formule CASCADE;
DROP TABLE IF EXISTS groupe CASCADE;
DROP TABLE IF EXISTS client CASCADE;

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
    id_client SERIAL PRIMARY KEY, -- Identifiant auto-incrémenté
    nom VARCHAR(48) NOT NULL,
    prenom VARCHAR(48) NOT NULL,
    adresse VARCHAR(128) NOT NULL,
    num_tel VARCHAR(14) NOT NULL,
    niveau_ski ski_level NOT NULL,
    taille NUMERIC(3,2) NOT NULL,
    poids SMALLINT NOT NULL,
    pointure NUMERIC(3,1) NOT NULL
);

CREATE TABLE groupe (
    nom_groupe VARCHAR(48) PRIMARY KEY
);

CREATE TABLE formule (
    type_formule VARCHAR(24) PRIMARY KEY,
    prix_base INTEGER NOT NULL
);

CREATE TABLE chambre (
    num_chambre SERIAL PRIMARY KEY, -- Changé de BIGINT à SERIAL
    etage SMALLINT NOT NULL,
    batiment CHAR(1) NOT NULL,
    nb_lits SMALLINT NOT NULL,
    superficie SMALLINT NOT NULL,
    type_vue vue_type NOT NULL,
    balcon_present BOOLEAN NOT NULL
);

CREATE TABLE reservation (
    id_reservation SERIAL PRIMARY KEY, -- Identifiant auto-incrémenté
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    CONSTRAINT check_dates CHECK (date_fin > date_debut)
);

-- 4. TABLES DÉPENDANTES

CREATE TABLE compte_utilisateur (
    id_user SERIAL PRIMARY KEY, -- Identifiant auto-incrémenté
    username VARCHAR(50) UNIQUE NOT NULL,
    mdp_hash VARCHAR(255) NOT NULL,
    role role_compte NOT NULL DEFAULT 'client',
    id_client INTEGER REFERENCES client(id_client) ON DELETE SET NULL
);

-- Table centrale 'reserver'
CREATE TABLE reserver (
    id_client INTEGER NOT NULL REFERENCES client(id_client),
    nom_groupe VARCHAR(48) NOT NULL REFERENCES groupe(nom_groupe),
    num_chambre INTEGER NOT NULL REFERENCES chambre(num_chambre), -- Changé en INTEGER pour correspondre au SERIAL
    type_formule VARCHAR(24) NOT NULL REFERENCES formule(type_formule),
    id_reservation INTEGER NOT NULL REFERENCES reservation(id_reservation),
    occupe_lit BOOLEAN DEFAULT TRUE,
    formule_prix_final INTEGER NOT NULL,
    PRIMARY KEY (id_client, id_reservation)
);

CREATE TABLE facturation (
    id_facture SERIAL PRIMARY KEY, -- Identifiant auto-incrémenté
    montant_total INTEGER NOT NULL,
    date_emission DATE DEFAULT CURRENT_DATE,
    id_reservation INTEGER NOT NULL REFERENCES reservation(id_reservation),
    num_chambre INTEGER NOT NULL REFERENCES chambre(num_chambre) -- Changé en INTEGER
);

CREATE TABLE preference (
    id_client_emetteur INTEGER REFERENCES client(id_client),
    id_client_receveur INTEGER REFERENCES client(id_client),
    niveau_preference pref_level NOT NULL,
    PRIMARY KEY (id_client_emetteur, id_client_receveur),
    CONSTRAINT no_self_pref CHECK (id_client_emetteur <> id_client_receveur)
);

-- ==========================================================
-- 5. JEU D'ESSAI
-- ==========================================================

INSERT INTO groupe (nom_groupe) VALUES ('Famille Durand');

INSERT INTO formule (type_formule, prix_base) VALUES
('Non skieur', 420),
('Tout compris', 510);

-- Insertion avec numéros de chambre explicites (PostgreSQL gère l'insertion dans un SERIAL)
INSERT INTO chambre (num_chambre, etage, batiment, nb_lits, superficie, type_vue, balcon_present) VALUES
(105, 1, 'B', 2, 18, 'parking', false),
(227, 2, 'A', 4, 25, 'pistes', true);

-- Mise à jour de la séquence de num_chambre pour éviter les conflits lors des futurs inserts auto
SELECT setval(pg_get_serial_sequence('chambre', 'num_chambre'), MAX(num_chambre)) FROM chambre;

INSERT INTO client (nom, prenom, adresse, num_tel, niveau_ski, taille, poids, pointure) VALUES
('Durand', 'Jean', '12 rue des Pins, Paris', '0601020304', 'moyen', 1.80, 75, 43.0),
('Durand', 'Marie', '12 rue des Pins, Paris', '0605060708', 'confirmé', 1.65, 60, 38.0);

INSERT INTO reservation (date_debut, date_fin) VALUES
('2025-12-20', '2025-12-27');

INSERT INTO reserver (id_client, nom_groupe, num_chambre, type_formule, id_reservation, occupe_lit, formule_prix_final) VALUES
(1, 'Famille Durand', 227, 'Tout compris', 1, true, 510),
(2, 'Famille Durand', 227, 'Tout compris', 1, true, 510);

INSERT INTO facturation (montant_total, id_reservation, num_chambre) VALUES
(1020, 1, 227);

INSERT INTO compte_utilisateur (username, mdp_hash, role, id_client) VALUES
('j.durand', '$2y$10$e0MYzXy..6L.88H1L7L9e.lE5QO.mX5I.mX5I.mX5I.mX5I.mX5I.', 'client', 1);
