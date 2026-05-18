# **RAPPORT DE PROJET \- SAE 2-04 : MANUEL DE L'UTILISATEUR (ZARZA-SKI)**

## **INTRODUCTION & ACCÈS SÉCURISÉ À LA PLATEFORME**

L'accès aux différentes fonctionnalités de la station Zarza-Ski est protégé par un système d'authentification basé sur des sessions PHP sécurisées et des rôles d'utilisateurs en base de données.

### **1\. Inscription sur la plateforme**

Tout nouveau client doit s'enregistrer via la page d'inscription (bouton inscription en haut à droite). Il y saisit un identifiant unique (nom d'utilisateur), un mot de passe sécurisé (chiffré en backend avant insertion par l'algorithme password\_hash()), ainsi que son nom et prénom.

Lors de cette inscription, la base de données crée automatiquement deux entités associées au sein d'une même transaction :

* Un compte utilisateur générique dans la table des identifiants.  
* Une fiche skieur par défaut dans la table client, faisant de l'utilisateur le premier membre physique de sa propre tribu.

### **2\. Connexion**

La connexion s'effectue via un formulaire classique. Le système vérifie la correspondance du mot de passe hashé fourni par rapport au hash stocké en base de données. Une fois l'identité validée, la session est ouverte ($\_SESSION\['id\_user'\]) et le rôle (permissions d'accés) de l'utilisateur (client, gestionnaire ou administrateur) est mémorisé pour réguler dynamiquement les accès à certaines pages.

### **3\. Déconnexion et sécurité**

À tout moment, l'utilisateur peut mettre fin à sa session de manière sécurisée en cliquant sur le bouton **Déconnexion** situé en haut à droite. Le traitement backend détruit l'intégralité des variables de session actives, vide le panier en cours.

## **PARTIE 1 : L'ESPACE CLIENT (Le parcours du Chef de Tribu)**

L'Espace Client est conçu pour centraliser la planification familiale ou amicale d'un séjour aux sports d'hiver. L'utilisateur connecté endosse le rôle de "Chef de Tribu".

### **1.1. La Gestion de la Tribu & des Groupes (Carnet de Voyageurs)**

Afin d'éviter la saisie répétitive des informations de chaque participant à chaque nouvelle réservation, Zarza-Ski introduit le concept de **Carnet de Voyageurs**.

#### **Le Carnet de Voyageurs**

Depuis l'onglet **Carnet de Voyageurs**, le chef de tribu gère la liste de tous ses proches (famille, amis, collègues) susceptibles de l'accompagner aux sports d'hiver. Pour chaque proche, il enregistre :

* **Informations civiles d'état-major** : Nom, Prénom, Date de naissance (indispensable pour les calculs de tarifs en fonction de l'âge exact lors du séjour), Adresse et Numéro de téléphone de contact.  
* **Mensurations et caractéristiques de sécurité physique** :  
  * **La Taille (en m)** : Déterminante pour le choix de la longueur des skis en atelier.  
  * **Le Poids (en kg)** : Variable critique pour le réglage de la tension des fixations de sécurité des skis.  
  * **La Pointure (EU)** : Nécessaire pour la préparation instantanée des chaussures de ski.  
  * **Le Niveau de ski** (*Débutant*, *Moyen*, *Confirmé*) : Permet d'adapter la technicité des skis loués.

#### **La gestion des Groupes**

En parallèle, l'utilisateur peut créer des entités de **Groupes** (ex. : *"Famille Durand"*, *"Séjour Amis 2026"*). Ces groupes permettent de lier des réservations de chambres multiples sous un même pavillon décisionnel. Un groupe ne peut être créé que s'il porte un nom unique dans la base de données de la station.

### **1.2. La Configuration des Préférences de Cohabitation**

L'une des fonctionnalités les plus novatrices de la plateforme réside dans la modélisation des **Préférences de cohabitation** (liaison associative de la table preference en base de données).

Le chef de tribu peut déclarer, pour chaque paire de skieurs présents dans son carnet, des affinités ou des incompatibilités strictes. Quatre niveaux de relation sont disponibles :

1. **Impératif** : Les deux personnes *doivent* obligatoirement dormir dans la même chambre (ex. : un couple, ou de jeunes enfants avec leurs parents).  
2. **Souhaitable** : Les deux personnes aimeraient partager leur chambre dans la mesure du possible.  
3. **Pas souhaitable** : Il est recommandé d'éviter d'associer ces deux personnes dans une même chambre.  
4. **Interdit** : Les deux personnes ne *doivent sous aucun prétexte* cohabiter (ex. : antécédents conflictuels, ronflements extrêmes).

**Utilité pratique** : Ces informations sont capitalisées au fil de l'année. Lors des futures affectations réelles, elles servent de base documentaire indispensable pour que le chef de tribu ou la direction de l'hôtel n'effectue pas d'erreurs d'attribution de lits.

### **1.3. Le Processus de Réservation (Le Tunnel d'affectation épuré)**

Une fois qu'une ou plusieurs chambres ont été sélectionnées lors de la recherche et ajoutées au panier, l'utilisateur se rend sur la page **reservation.php** pour valider et configurer les détails de son séjour. Le formulaire global est structuré en trois blocs logiques très simples.

#### **Étape A : Période et Groupe (Bloc 1\)**

* **Date de début de séjour** : L'utilisateur doit obligatoirement choisir le dimanche de début de sa semaine de vacances. La semaine de location à la station Zarza-Ski est immuable : elle commence le dimanche à 14h et s'achève le samedi suivant à 10h (6 nuits de location).  
* **Choix du Groupe** : L'utilisateur sélectionne à quel groupe associer sa réservation générale.  
* **Création asynchrone (AJAX Fetch)** : Si le chef de tribu s'aperçoit qu'il a oublié de déclarer un nouveau proche ou de créer son groupe, il peut le faire instantanément grâce aux deux boutons de création à la volée. Des modales en CSS pur s'ouvrent à l'écran. Lors de la soumission, un appel Fetch asynchrone transmet les données au serveur PHP. Si la création réussit :  
  * Pour un groupe, le nouveau nom s'insère directement dans le menu déroulant principal de sélection et s'active.  
  * Pour un proche, le script injecte dynamiquement (via appendChild) une nouvelle ligne de tableau dans l'intégralité des tableaux d'affectation de chaque chambre présente à l'écran, sans aucune perte de saisie pour les formulaires déjà remplis.

#### **Étape B : Hébergements, Affectations & Suppression (Bloc 2\)**

Pour chaque chambre réservée dans le panier, l'utilisateur fait face à une carte récapitulative listant :

* Le numéro de chambre, son bâtiment, son étage, et sa capacité nominale en lits.  
* Un bouton standard **"Supprimer la chambre"** qui pointe vers l'action de suppression en session si l'utilisateur souhaite finalement renoncer à ce logement précis.
* Un tableau académique listant tous les membres de sa tribu. Pour chaque personne, le chef de groupe effectue deux choix simples :  
  1. **Une case à cocher (Checkbox)** pour déclarer si ce proche occupera physiquement cette chambre.  
  2. **Un menu déroulant de Formule** (*Tout compris*, *Demi-pension*, *Non-skieur*, etc.) à attribuer au skieur pour la semaine.

#### **Étape C : Soumission (Bloc 3\)**

Aucun calcul n'est fait côté client. L'utilisateur clique sur **"Confirmer ma réservation"**, ce qui transmet l'intégralité des tableaux d'affectations brutes au script de validation backend valider\_sejour.php.

### **1.4. La Consultation des Séjours & Factures**

Après validation, l'utilisateur est redirigé vers l'historique complet de ses séjours (mes\_reservations.php). Depuis cette interface :

* Il suit l'état de ses vacances passées, en cours et futures.  
* Pour chaque séjour, il observe le groupe de rattachement, la période exacte, ainsi que le **montant financier cumulé de toutes ses chambres**.  
* Il dispose d'un bouton d'annulation globale qui supprime proprement le séjour, libère les lits et efface les factures en base de données.  
* Pour chaque réservation, une table académique récapitule de manière transparente :  
  * La répartition de ses proches chambre par chambre.  
  * La formule attribuée à chacun d'entre eux.  
  * Le montant final exact facturé pour chaque personne (tenant compte des remises d'âges).

## **PARTIE 2 : L'ESPACE GESTIONNAIRE (Le suivi décisionnel)**

L'accès à la page de statistiques et d'analyse **vues.php** est strictement réservé aux utilisateurs disposant du rôle de **gestionnaire** ou d'**administrateur** de la station. Toute tentative d'accès par un client classique se solde par un blocage immédiat et une redirection sécurisée vers l'espace de connexion.

La page s'articule autour de trois sous-onglets, chacun interrogeant une vue PostgreSQL optimisée pour l'analyse de données de la direction.

### **2.1. Onglet "Fréquentation Hebdomadaire"**

Cet onglet s'appuie sur la vue PostgreSQL vue\_frequentation\_semaine.

* **Objectif** : Il affiche sous forme d'un tableau, le volume exact de personnes présentes sur les pistes, semaine par semaine.  
* **Champs analysés** : Semaine de début, Semaine de fin, et le nombre de skieurs comptabilisés sur la station.  
* **Utilité pour la direction** : Permet d'anticiper l'affluence, d'adapter les plannings des pisteurs et des moniteurs ESF, et de planifier l'approvisionnement des restaurants d'altitude.

### **2.2. Onglet "Recherche par Chambre"**

Cet onglet permet d'effectuer des recherches chirurgicales sur la vue vue\_details\_occupants\_chambre.

* **Objectif** : Permettre à la direction de répondre instantanément aux contrôles réglementaires de sécurité ou de police. Il permet de savoir à une date précise qui occupait quelle chambre physique.  
* **Cas pratique d'évaluation (Sujet page 2\)** : Pour tester et valider le fonctionnement et l'intégrité de la base de données, saisissez le numéro de chambre **227** et la date du dimanche de la semaine de Noël (ex. : **2020-12-20** ou **2025-12-21** selon votre jeu d'essai). Le système affiche immédiatement les noms, prénoms et types de formule des vacanciers qui y résidaient.

### **2.3. Onglet "Liste des Occupants"**

Cet onglet interroge de manière globale la vue vue\_details\_occupants\_chambre.

* **Objectif** : Il dresse un listing d'occupation global et trié de la station pour toutes les chambres louées et toutes les semaines confondues.  
* **Données affichées** : Semaine début, Numéro de chambre, Bâtiment, Étage, Nom, Prénom, et Formule.  
* **Utilité pour les équipes au sol** : Indispensable pour les services de ménage (gouvernantes) afin de savoir quelles chambres doivent être nettoyées et préparées pour les arrivées du dimanche, ainsi que pour les skimen en atelier afin d'anticiper la préparation du matériel de glisse.

## LES RÈGLES DE FACTURATION & CONTRÔLES MÉTIER (Backend)**

Lors de la validation d'un séjour par le client, le backend PHP de la station exécute une série de tests logiques stricts au sein d'une **transaction SQL atomique unique** (garantissant que si une seule étape échoue, aucune écriture n'est enregistrée en base de données) :

### **1\. Contrôle d'inviolabilité des dates**

Le script vérifie que la date choisie par le client est bien un **dimanche** (le code 'N' de la date PHP ou PostgreSQL doit valoir 7). La date de fin de séjour est alors fixée automatiquement à date\_debut \+ 6 jours (départ le samedi matin suivant).

### **2\. Contrôle de l'unicité physique (Anti-Ubiquité)**

Un skieur ne peut pas se trouver à deux endroits différents le même jour. Le script parcourt l'intégralité des chambres validées et lève immédiatement une exception bloquante si un même identifiant de voyageur (id\_client) a été coché dans deux chambres différentes pour la même période.

### **3\. Contrôle de capacité des chambres (Lits réels)**

Le script calcule l'âge de chaque skieur au premier jour du séjour.

* Les bébés de **moins de 2 ans** n'occupent pas de lit physique (ils dorment dans un lit parapluie apporté par les parents) : ils ne consomment pas la capacité de la chambre.  
* Les personnes âgées de **2 ans ou plus** consomment un lit physique.  
* Si le nombre d'occupants consommant un lit dépasse la capacité nominale nb\_lits de la chambre concernée, la transaction est annulée et un message d'erreur est renvoyé à l'utilisateur.

### **4\. Algorithme de Tarification et Pénalités**

Pour chaque chambre louée, la facture est calculée de la manière suivante :

* **Calcul du tarif individuel des formules** :  
  * **Moins de 2 ans** : Gratuit (0 €) peu importe la formule choisie.  
  * **Entre 2 ans et moins de 12 ans** : Application d'une réduction familiale stricte de **\-20%** sur le prix de base de la formule souscrite.  
  * **12 ans et plus** : Plein tarif appliqué sur la formule.  
* **Pénalité de lit inoccupé** :  
  La station applique un malus pour éviter le gaspillage d'hébergement. Si une chambre de 4 lits n'est occupée que par 2 personnes consommant un lit, le système détecte 4 - 2 = 2 lits inoccupés. Une amende forfaitaire réglementaire de **150 € par lit vide** est automatiquement ajoutée à la facture de cette chambre.

