### Rapport de Conception : Le Tunnel de Réservation Zarza-Ski

Ce document récapitule la logique implémentée pour l'étape la plus complexe du projet : la réservation multi-chambres et multi-personnes.

#### 1. Architecture du Groupe et du Carnet

* **Propriété Technique** : Chaque utilisateur possède un "Carnet de Voyageurs" (table `client`) lié à son compte.
* 
**Abstraction du Groupe** : Un groupe est identifié par un nom. Il n'est pas une liste fixe de clients, mais un conteneur pour une réservation. Le "Chef de groupe" pioche dans son carnet pour constituer le groupe de *cette* année.


* **Historique Garanti** : Même si la composition du groupe change l'année suivante, l'historique reste intact dans la table `reserver`, car chaque client est lié à un `id_reservation` unique et définitif.

#### 2. Le Tunnel de Réservation (3 Étapes Carrées)

**Étape 1 : Configuration Globale**

* **Identification** : Choix d'un groupe existant (détenu par le chef) ou création d'un nouveau.
* 
**Période** : Sélection de la semaine (Dimanche 11h au Samedi 10h).


* **Appel des Voyageurs** : Sélection des membres du carnet qui participent à ce séjour spécifique.

**Étape 2 : Hébergement et Placement**

* 
**Panier de Chambres** : Sélection d'une ou plusieurs chambres (capacité 2, 4 ou 6).


* **Affectation** : Pour chaque chambre, le chef assigne les membres sélectionnés.
* **Contrainte d'Unicité** : Le système empêche techniquement qu'une personne soit affectée à deux chambres différentes sur la même période.
* 
**Intégrité Zarza-Ski** : Le système vérifie qu'aucun autre groupe n'occupe déjà la chambre.



**Étape 3 : Options Individuelles et Automatisation Backend**
Pour chaque occupant, le système demande le type de formule. Le backend prend ensuite le relais pour automatiser les calculs imposés par le sujet:

* 
**Calcul de l'Âge** : Détermine automatiquement si le client est un Bébé ($< 2$ ans), un Enfant ($< 12$ ans) ou un Adulte.


* 
**Flag `occupe_lit**` : Automatiquement mis à `FALSE` pour les bébés, permettant de libérer visuellement un lit pour la facturation des lits vides.


* 
**Tarification Dynamique** : Application des -20% pour les enfants et gratuité pour les bébés.



#### 3. Finalisation Financière (La Facturation)

La facture est générée par chambre. Le montant mémorisé dans la table `facturation` suit la formule métier :


$$Montant = \sum(\text{prix\_formules}) + (\text{lits\_vides} \times 150)$$

.


la table gestion_voyageurs permet de répondre à la contrainte métier : « Un utilisateur peut gérer les profils de sa famille sans qu'ils aient besoin de se créer un compte personnel »