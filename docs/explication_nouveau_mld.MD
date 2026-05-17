Voici une section complète, rédigée de manière professionnelle et structurée, que tu peux directement intégrer ou adapter dans ton **rapport de SAE**. Le ton est académique et met en avant des arguments techniques (normalisation, optimisation de l'expérience utilisateur, intégrité référentielle) qui sauront convaincre tes enseignants de BUT Informatique.

---

# Justification de l'Évolution du Modèle Logique des Données (MLD)

L’analyse approfondie du sujet de la station **Zarza-Ski** et les contraintes de développement logiciel nous ont amenés à restructurer en profondeur notre Modèle Logique des Données (MLD) initial. Le passage de l'ancien modèle au nouveau répond à des impératifs d’**authentification**, de **simplification des requêtes**, de **normalisation** et de **sécurisation de l’historique financier**.

Voici les trois axes majeurs qui justifient cette transition architecturale :

---

### 1. Centralisation de la dimension temporelle et suppression des redondances

* **Ancien Modèle :** La gestion du temps et des occupations était fragmentée à l'excès. Les tables `sejour`, `tarif_formule` et `attribution_chambre` utilisaient toutes une clé primaire composite basée sur la date de `debut`. Cette structure multipliait les risques d'incohérence et rendait les jointures SQL extrêmement lourdes.
* **Nouveau Modèle :** Nous avons centralisé la notion de séjour au sein d'une entité unique : **`reservation`**, dotée d'une clé primaire auto-incrémentée (`id_reservation`). La table de liaison **`reserver`** fusionne désormais l'attribution de la chambre, le choix de la formule et l'identité du client en une seule ligne logique.
* **Le choix du "Snapshot" :** Le champ `formule_prix_final` a été intégré directement dans la table `reserver`. Cette dénormalisation volontaire permet de figer le prix de la transaction au moment de l'achat. Si la station modifie le tarif de base d'une formule l'année suivante, l'historique comptable des anciennes réservations reste intact.

---

### 2. Implémentation de l'Espace Client (Authentification & Structure B2C)

* **Ancien Modèle :** Le modèle d'origine ne prévoyait aucune distinction entre un client physique (skieur) et un compte utilisateur (identifiants de connexion), rendant impossible la mise en œuvre d'un espace web sécurisé.
* **Nouveau Modèle :** L'introduction de la table **`compte_utilisateur`** découple la sécurité (hash du mot de passe, rôles applicatifs) des données physiques du client.
* **Le Carnet de Voyageurs :** La création de la table de pivot **`gestion_voyageurs`** répond à une forte contrainte métier : *permettre à un chef de groupe de gérer sa famille ou ses amis depuis un compte unique*. Ainsi, un utilisateur peut enregistrer des proches (enfants, conjoints) dans son carnet d'adresses sans que ces derniers n'aient besoin de posséder des identifiants ou un compte personnel sur l'application.

---

### 3. Automatisation des règles métiers et Intégrité Financière

* **Ajout de la date de naissance :** Nous avons enrichi la table `client` avec l'attribut `date_naissance`. Cette donnée est indispensable à notre système backend pour automatiser l'application des tarifs de Zarza-Ski lors de la réservation (gratuité totale pour les bébés de moins de 2 ans et réduction immédiate de **20%** pour les enfants de moins de 12 ans).
* **Sécurisation comptable de la Facturation :** La table `facturation` est désormais directement rattachée à une `reservation` et à un `num_chambre`. De plus, afin d'assurer la conformité avec les règles de comptabilité d'entreprise, la contrainte de clé étrangère liant les réservations aux groupes a été configurée avec la clause **`ON DELETE RESTRICT`**. Cela empêche l'effacement accidentel d'un groupe ou d'une réservation dès lors qu'une facture y est adossée, protégeant ainsi l'intégrité des données financières de la station.

---

### Tableau comparatif des gains architecturaux

| Objectif technique | Ancien Modèle (`mld_ancien.jpg`) | Nouveau Modèle (`mld_nouveau.jpg`) | Bénéfice pour le projet |
| --- | --- | --- | --- |
| **Gestion du temps** | Multiples clés composites basées sur la date | Entité transactionnelle centralisée (`reservation`) | Code PHP plus simple, requêtes SQL optimisées. |
| **Expérience Utilisateur** | Chaque skieur doit être indépendant | Carnet de voyageurs (`gestion_voyageurs`) | Un seul compte peut réserver pour toute une famille. |
| **Comptabilité** | Risque de perte des montants facturés en cas de modification | Snapshot des prix (`reserver`) + `ON DELETE RESTRICT` | Traçabilité légale et historique financier inviolable. |

---

*Ce document démontre aux correcteurs que chaque table de ton projet PHP n'est pas là par hasard, mais pour répondre rigoureusement à des patterns de conception de bases de données et à une UX efficace.*