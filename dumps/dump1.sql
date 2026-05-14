-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 29, 2025 at 04:33 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `anis.ziane_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attribution_chambre`
--

CREATE TABLE `attribution_chambre` (
  `id_client` bigint(20) UNSIGNED DEFAULT NULL,
  `num_chambre` bigint(20) DEFAULT NULL,
  `debut` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attribution_chambre`
--

INSERT INTO `attribution_chambre` (`id_client`, `num_chambre`, `debut`) VALUES
(1, 227, '2020-12-20'),
(2, 227, '2020-12-20'),
(3, 227, '2020-12-20');

-- --------------------------------------------------------

--
-- Table structure for table `chambre`
--

CREATE TABLE `chambre` (
  `num_chambre` bigint(20) NOT NULL,
  `etage` tinyint(4) NOT NULL,
  `batiment` char(1) NOT NULL,
  `nb_lits` tinyint(4) NOT NULL,
  `superficie` smallint(6) NOT NULL,
  `type_vue` enum('parking','pistes') NOT NULL,
  `balcon_present` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chambre`
--

INSERT INTO `chambre` (`num_chambre`, `etage`, `batiment`, `nb_lits`, `superficie`, `type_vue`, `balcon_present`) VALUES
(105, 1, 'B', 2, 18, 'parking', 0),
(227, 2, 'A', 4, 25, 'pistes', 1);

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `id_client` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(48) NOT NULL,
  `prenom` varchar(48) NOT NULL,
  `date_naissance` date NOT NULL,
  `adresse` varchar(64) NOT NULL,
  `num_tel` varchar(14) NOT NULL,
  `niveau_ski` enum('débutant','moyen','confirmé') NOT NULL,
  `taille` decimal(3,2) UNSIGNED NOT NULL,
  `poids` smallint(6) UNSIGNED NOT NULL,
  `pointure` decimal(3,1) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`id_client`, `nom`, `prenom`, `date_naissance`, `adresse`, `num_tel`, `niveau_ski`, `taille`, `poids`, `pointure`) VALUES
(1, 'Durand', 'Jean', '1985-05-15', '12 rue des Pins, Paris', '0601020304', 'moyen', 1.80, 75, 43.0),
(2, 'Durand', 'Marie', '1987-09-20', '12 rue des Pins, Paris', '0605060708', 'confirmé', 1.65, 60, 38.0),
(3, 'Durand', 'Lucas', '2012-03-10', '12 rue des Pins, Paris', '0609101112', 'débutant', 1.40, 35, 34.0);

-- --------------------------------------------------------

--
-- Table structure for table `facturation`
--

CREATE TABLE `facturation` (
  `nom_groupe` varchar(48) DEFAULT NULL,
  `num_chambre` bigint(20) DEFAULT NULL,
  `debut` date DEFAULT NULL,
  `montant_total` smallint(6) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `formule`
--

CREATE TABLE `formule` (
  `type_formule` varchar(24) NOT NULL,
  `prix_base` smallint(6) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `formule`
--

INSERT INTO `formule` (`type_formule`, `prix_base`) VALUES
('Non skieur', 420),
('Tout compris', 510);

-- --------------------------------------------------------

--
-- Table structure for table `groupe`
--

CREATE TABLE `groupe` (
  `nom_groupe` varchar(48) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groupe`
--

INSERT INTO `groupe` (`nom_groupe`) VALUES
('Famille Durand');

-- --------------------------------------------------------

--
-- Table structure for table `preference`
--

CREATE TABLE `preference` (
  `fk_id_client_emmeteur` bigint(20) UNSIGNED NOT NULL,
  `fk_id_client_receveur` bigint(20) UNSIGNED NOT NULL,
  `niveau_preference` enum('impératif','Souhaitable','Pas souhaitable','Interdit') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sejour`
--

CREATE TABLE `sejour` (
  `id_client` bigint(20) UNSIGNED DEFAULT NULL,
  `nom_groupe` varchar(48) DEFAULT NULL,
  `debut` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sejour`
--

INSERT INTO `sejour` (`id_client`, `nom_groupe`, `debut`) VALUES
(1, 'Famille Durand', '2020-12-20'),
(2, 'Famille Durand', '2020-12-20'),
(3, 'Famille Durand', '2020-12-20');

-- --------------------------------------------------------

--
-- Table structure for table `semaine`
--

CREATE TABLE `semaine` (
  `debut` date NOT NULL,
  `fin` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semaine`
--

INSERT INTO `semaine` (`debut`, `fin`) VALUES
('2020-12-20', '2020-12-26'),
('2020-12-27', '2021-01-02');

-- --------------------------------------------------------

--
-- Table structure for table `tarif_formule`
--

CREATE TABLE `tarif_formule` (
  `id_client` bigint(20) UNSIGNED DEFAULT NULL,
  `debut` date DEFAULT NULL,
  `type_formule` varchar(24) DEFAULT NULL,
  `formule_prix_final` smallint(6) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tarif_formule`
--

INSERT INTO `tarif_formule` (`id_client`, `debut`, `type_formule`, `formule_prix_final`) VALUES
(1, '2020-12-20', 'Tout compris', 510),
(2, '2020-12-20', 'Tout compris', 510),
(3, '2020-12-20', 'Tout compris', 408);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attribution_chambre`
--
ALTER TABLE `attribution_chambre`
  ADD KEY `attribution_chambre_ibfk_1` (`id_client`),
  ADD KEY `attribution_chambre_ibfk_2` (`num_chambre`),
  ADD KEY `attribution_chambre_ibfk_3` (`debut`);

--
-- Indexes for table `chambre`
--
ALTER TABLE `chambre`
  ADD PRIMARY KEY (`num_chambre`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id_client`);

--
-- Indexes for table `facturation`
--
ALTER TABLE `facturation`
  ADD KEY `nom_groupe` (`nom_groupe`),
  ADD KEY `num_chambre` (`num_chambre`),
  ADD KEY `debut` (`debut`);

--
-- Indexes for table `formule`
--
ALTER TABLE `formule`
  ADD PRIMARY KEY (`type_formule`);

--
-- Indexes for table `groupe`
--
ALTER TABLE `groupe`
  ADD PRIMARY KEY (`nom_groupe`);

--
-- Indexes for table `preference`
--
ALTER TABLE `preference`
  ADD KEY `id_client` (`fk_id_client_emmeteur`),
  ADD KEY `id_client_1` (`fk_id_client_receveur`);

--
-- Indexes for table `sejour`
--
ALTER TABLE `sejour`
  ADD KEY `id_client` (`id_client`),
  ADD KEY `nom_groupe` (`nom_groupe`),
  ADD KEY `debut` (`debut`);

--
-- Indexes for table `semaine`
--
ALTER TABLE `semaine`
  ADD PRIMARY KEY (`debut`);

--
-- Indexes for table `tarif_formule`
--
ALTER TABLE `tarif_formule`
  ADD KEY `id_client` (`id_client`),
  ADD KEY `debut` (`debut`),
  ADD KEY `type_formule` (`type_formule`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `id_client` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attribution_chambre`
--
ALTER TABLE `attribution_chambre`
  ADD CONSTRAINT `attribution_chambre_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id_client`),
  ADD CONSTRAINT `attribution_chambre_ibfk_2` FOREIGN KEY (`num_chambre`) REFERENCES `chambre` (`num_chambre`),
  ADD CONSTRAINT `attribution_chambre_ibfk_3` FOREIGN KEY (`debut`) REFERENCES `semaine` (`debut`);

--
-- Constraints for table `facturation`
--
ALTER TABLE `facturation`
  ADD CONSTRAINT `facturation_ibfk_1` FOREIGN KEY (`nom_groupe`) REFERENCES `groupe` (`nom_groupe`),
  ADD CONSTRAINT `facturation_ibfk_2` FOREIGN KEY (`num_chambre`) REFERENCES `chambre` (`num_chambre`),
  ADD CONSTRAINT `facturation_ibfk_3` FOREIGN KEY (`debut`) REFERENCES `semaine` (`debut`);

--
-- Constraints for table `preference`
--
ALTER TABLE `preference`
  ADD CONSTRAINT `preference_ibkf_1` FOREIGN KEY (`fk_id_client_emmeteur`) REFERENCES `client` (`id_client`),
  ADD CONSTRAINT `preference_ibkf_2` FOREIGN KEY (`fk_id_client_receveur`) REFERENCES `client` (`id_client`);

--
-- Constraints for table `sejour`
--
ALTER TABLE `sejour`
  ADD CONSTRAINT `sejour_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id_client`),
  ADD CONSTRAINT `sejour_ibfk_2` FOREIGN KEY (`nom_groupe`) REFERENCES `groupe` (`nom_groupe`),
  ADD CONSTRAINT `sejour_ibfk_3` FOREIGN KEY (`debut`) REFERENCES `semaine` (`debut`);

--
-- Constraints for table `tarif_formule`
--
ALTER TABLE `tarif_formule`
  ADD CONSTRAINT `tarif_formule_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id_client`),
  ADD CONSTRAINT `tarif_formule_ibfk_2` FOREIGN KEY (`debut`) REFERENCES `semaine` (`debut`),
  ADD CONSTRAINT `tarif_formule_ibfk_3` FOREIGN KEY (`type_formule`) REFERENCES `formule` (`type_formule`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
