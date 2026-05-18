-- script de création de la base de données zarza-ski

-- nettoyage
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

-- enums
CREATE TYPE pref_level AS ENUM ('impératif', 'Souhaitable', 'Pas souhaitable', 'Interdit');
CREATE TYPE ski_level AS ENUM ('débutant', 'moyen', 'confirmé');
CREATE TYPE vue_type AS ENUM ('parking', 'pistes');
CREATE TYPE role_compte AS ENUM ('admin', 'gestionnaire', 'client');

-- tables indépendantes

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

-- tables dépendantes (hiérarchiques)

-- compte lié au client principal
CREATE TABLE compte_utilisateur (
    id_user SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    mdp_hash VARCHAR(255) NOT NULL,
    role role_compte NOT NULL DEFAULT 'client',
    id_client INTEGER REFERENCES client(id_client) ON DELETE SET NULL
);

-- carnet d'adresses : quels clients sont gérés par quel compte ?
CREATE TABLE gestion_voyageurs (
    id_user INTEGER REFERENCES compte_utilisateur(id_user) ON DELETE CASCADE,
    id_client INTEGER REFERENCES client(id_client) ON DELETE CASCADE,
    PRIMARY KEY (id_user, id_client)
);

-- un groupe appartient à un compte (chef de groupe)
CREATE TABLE groupe (
    nom_groupe VARCHAR(48) PRIMARY KEY,
    id_user INTEGER REFERENCES compte_utilisateur(id_user) ON DELETE CASCADE
);

-- une réservation appartient à un groupe
CREATE TABLE reservation (
    id_reservation SERIAL PRIMARY KEY,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    nom_groupe VARCHAR(48) NOT NULL REFERENCES groupe(nom_groupe) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT check_dates CHECK (date_fin > date_debut)
);

-- table de liaison centrale : qui occupe quelle chambre sous quelle formule ?
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

-- préférences entre clients
CREATE TABLE preference (
    id_client INTEGER REFERENCES client(id_client) ON DELETE CASCADE,
    id_client_1 INTEGER REFERENCES client(id_client) ON DELETE CASCADE,
    niveau_preference pref_level NOT NULL,
    PRIMARY KEY (id_client, id_client_1),
    CONSTRAINT no_self_pref CHECK (id_client <> id_client_1)
);

-- création des vues postgres sql

-- nombre de personnes présentes par semaine
CREATE OR REPLACE VIEW vue_frequentation_semaine AS
SELECT 
    r.date_debut AS semaine_debut,
    r.date_fin AS semaine_fin,
    COUNT(re.id_client) AS total_skieurs_pistes
FROM reservation r
LEFT JOIN reserver re ON r.id_reservation = re.id_reservation
GROUP BY r.id_reservation, r.date_debut, r.date_fin;

-- liste complète et détails des occupants par chambre et par semaine
CREATE OR REPLACE VIEW vue_details_occupants_chambre AS
SELECT 
    r.date_debut AS semaine_debut, 
    re.num_chambre, 
    ch.batiment, 
    ch.etage, 
    c.nom AS client_nom, 
    c.prenom AS client_prenom, 
    re.type_formule 
FROM reserver re
INNER JOIN reservation r ON re.id_reservation = r.id_reservation
INNER JOIN client c ON re.id_client = c.id_client
INNER JOIN chambre ch ON re.num_chambre = ch.num_chambre;

-- jeu d'essai

-- les formules disponibles dans la station
INSERT INTO formule (type_formule, prix_base) VALUES 
('Non skieur', 420), 
('Tout compris', 510);

-- insertion de 10 chambres 
INSERT INTO chambre (num_chambre, etage, batiment, nb_lits, superficie, type_vue, balcon_present) VALUES
(101, 1, 'A', 2, 16, 'parking', false),
(102, 1, 'A', 3, 20, 'parking', false),
(105, 1, 'B', 2, 18, 'parking', false),
(110, 1, 'B', 4, 24, 'pistes', true),
(201, 2, 'A', 2, 17, 'parking', true),
(202, 2, 'A', 3, 21, 'pistes', true),
(205, 2, 'B', 2, 19, 'parking', false),
(210, 2, 'B', 4, 26, 'pistes', true),
(227, 2, 'A', 4, 25, 'pistes', true),
(302, 3, 'B', 4, 28, 'pistes', true);

-- synchronisation de la séquence pour éviter les conflits d'insertion serial
SELECT setval(pg_get_serial_sequence('chambre', 'num_chambre'), MAX(num_chambre)) FROM chambre;

INSERT INTO compte_utilisateur (username, mdp_hash, role, id_client) VALUES
('admin1', '$2y$10$K/Bl4e2rMmsn9EJIpiWFPeMMnfNThO/wFSbgXlnsJeLnmVLwvuNr.', 'admin', NULL);
