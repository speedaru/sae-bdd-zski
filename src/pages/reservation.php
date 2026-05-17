<?php
/**
 * Page de Réservation / Validation du Panier - Zarza-Ski
 * Emplacement : src/pages/reservation.php
 */

require_once __DIR__ . '/../includes/header.php';

// Protection d'accès
require_login("../pages/reservation.php");

$user_id = $_SESSION['id_user'];
$panier = $_SESSION['panier'] ?? [];

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);

// 1. GESTION DU PANIER VIDE
if (empty($panier)) {
    ?>
    <div class="container py-5 text-center">
        <div class="card p-5 shadow border-0 max-width-600 mx-auto">
            <div class="mb-4">
                <i class="fas fa-shopping-basket fa-4x text-muted opacity-50"></i>
            </div>
            <h3 class="fw-bold">Votre panier est vide</h3>
            <p class="text-muted">Sélectionnez une ou plusieurs chambres pour configurer votre séjour à la montagne.</p>
            <div class="mt-4">
                <a href="recherche.php" class="btn btn-primary px-4 py-2">
                    <i class="fas fa-search me-2"></i>Rechercher une chambre
                </a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// 2. RÉCUPÉRATION DES DONNÉES EN BDD
try {
    // A. Détails des chambres dans le panier
    $placeholders = implode(',', array_fill(0, count($panier), '?'));
    $stmtChambres = $pdo->prepare("SELECT * FROM chambre WHERE num_chambre IN ($placeholders) ORDER BY num_chambre ASC");
    $stmtChambres->execute($panier);
    $chambres = $stmtChambres->fetchAll(PDO::FETCH_ASSOC);

    // B. Groupes de l'utilisateur
    $stmtGroupes = $pdo->prepare("SELECT * FROM groupe WHERE id_user = ? ORDER BY nom_groupe ASC");
    $stmtGroupes->execute([$user_id]);
    $groupes = $stmtGroupes->fetchAll(PDO::FETCH_ASSOC);

    // C. Membres de la tribu (Carnet de voyageurs)
    $stmtVoyageurs = $pdo->prepare("
        SELECT c.* FROM client c
        JOIN gestion_voyageurs gv ON c.id_client = gv.id_client
        WHERE gv.id_user = ?
        ORDER BY c.nom ASC, c.prenom ASC
    ");
    $stmtVoyageurs->execute([$user_id]);
    $voyageurs = $stmtVoyageurs->fetchAll(PDO::FETCH_ASSOC);

    // D. Liste des formules pour attribution tarifaire
    $stmtFormules = $pdo->query("SELECT * FROM formule ORDER BY prix_base ASC");
    $formules = $stmtFormules->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur technique de chargement : " . $e->getMessage());
}
?>

<div class="container py-4">
    <!-- Entête de page -->
    <div class="d-flex align-items-center mb-4">
        <div class="bg-primary text-white rounded-circle p-3 me-3 shadow-sm">
            <i class="fas fa-cash-register fa-lg"></i>
        </div>
        <div>
            <h1 class="h2 mb-0 fw-bold">Finaliser ma réservation</h1>
            <p class="text-muted mb-0">Configurez les séjours de votre tribu, attribuez les chambres et validez vos vacances.</p>
        </div>
    </div>

    <?php if ($success) echo alert($success, 'success'); ?>
    <?php if ($error) echo alert($error, 'danger'); ?>

    <form method="POST" action="../actions/valider_sejour.php" id="form-reservation">
        
        <div class="row g-4">
            <!-- COLONNE GAUCHE : CONFIGURATION & REPARTITION -->
            <div class="col-lg-8">
                
                <!-- BLOC 1 : PÉRIODE & CONSTITUTION DE LA TRIBU -->
                <div class="card shadow-sm border-0 p-4 mb-4">
                    <h4 class="fw-bold mb-3 text-primary"><i class="fas fa-calendar-alt me-2"></i>1. Ma Période & Ma Tribu</h4>
                    
                    <div class="row g-3">
                        <!-- Date de début (Samedi obligatoire) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date de début du séjour</label>
                            <input type="date" name="date_debut" class="form-control" id="date_debut" required>
                            <div class="form-text text-muted"><i class="fas fa-info-circle me-1"></i>Les séjours commencent obligatoirement un samedi.</div>
                        </div>

                        <!-- Sélection du groupe -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Groupe associé</label>
                            <div class="input-group">
                                <select name="nom_groupe" class="form-select" required>
                                    <option value="" disabled selected>Sélectionnez un groupe</option>
                                    <?php foreach ($groupes as $g): ?>
                                        <option value="<?php echo h($g['nom_groupe']); ?>"><?php echo h($g['nom_groupe']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalGroupe">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Liste des participants (Cases à cocher) -->
                        <div class="col-12 mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold mb-0">Qui part avec vous ? (Sélectionnez les participants)</label>
                                <button class="btn btn-link btn-sm text-decoration-none" type="button" data-bs-toggle="modal" data-bs-target="#modalVoyageur">
                                    <i class="fas fa-plus-circle me-1"></i>Enregistrer un proche
                                </button>
                            </div>

                            <div id="tribu-conteneur">
                                <?php if (empty($voyageurs)): ?>
                                    <div class="alert alert-warning border-0" id="alert-tribu-vide">
                                        <i class="fas fa-info-circle me-2"></i>Votre tribu est vide. Enregistrez des proches pour les associer au séjour.
                                    </div>
                                <?php else: ?>
                                    <div class="p-3 bg-light rounded-3 border row g-2 max-height-200 overflow-y-auto" id="zone-participants">
                                        <?php foreach ($voyageurs as $voy): ?>
                                            <div class="col-sm-6 col-md-4">
                                                <div class="form-check p-2 bg-white rounded border h-100 d-flex align-items-center">
                                                    <input class="form-check-input ms-1 participant-checkbox" 
                                                           type="checkbox" 
                                                           value="<?php echo $voy['id_client']; ?>" 
                                                           id="part_<?php echo $voy['id_client']; ?>"
                                                           data-id="<?php echo $voy['id_client']; ?>"
                                                           data-fullname="<?php echo h($voy['prenom'] . ' ' . $voy['nom']); ?>"
                                                           data-dob="<?php echo h($voy['date_naissance']); ?>">
                                                    <label class="form-check-label ms-3 small text-dark fw-bold" for="part_<?php echo $voy['id_client']; ?>">
                                                        <?php echo h($voy['prenom'] . ' ' . $voy['nom']); ?>
                                                        <span class="d-block text-muted text-xs font-normal">Né le <?php echo date_fr($voy['date_naissance']); ?></span>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BLOC 2 : RÉPARTITION DANS LES CHAMBRES -->
                <div class="card shadow-sm border-0 p-4 mb-4">
                    <h4 class="fw-bold mb-3 text-primary"><i class="fas fa-hotel me-2"></i>2. Affectation des chambres</h4>
                    <p class="text-muted small">Affectez vos skieurs sélectionnés dans les lits de chaque chambre de votre panier.</p>

                    <?php foreach ($chambres as $ch): ?>
                        <div class="card border border-2 mb-3 shadow-none room-card" id="room_card_<?php echo $ch['num_chambre']; ?>" data-num="<?php echo $ch['num_chambre']; ?>" data-lits="<?php echo $ch['nb_lits']; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                    <h5 class="fw-bold mb-0 text-dark">
                                        <i class="fas fa-bed text-secondary me-2"></i>Chambre <?php echo $ch['num_chambre']; ?>
                                    </h5>
                                    <span class="badge bg-secondary">
                                        Capacité : <?php echo $ch['nb_lits']; ?> lits (Bâtiment <?php echo h($ch['batiment']); ?>)
                                    </span>
                                </div>
                                
                                <div class="assigned-list" data-room="<?php echo $ch['num_chambre']; ?>">
                                    <!-- Inséré en JS à la sélection des participants -->
                                    <p class="text-muted small italic text-center py-2 mb-0 no-occupant-msg">Sélectionnez d'abord des voyageurs ci-dessus pour les affecter ici.</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- COLONNE DROITE : FORMULES & RÉSUMÉ FINANCIER -->
            <div class="col-lg-4">
                
                <!-- BLOC 3 : FORMULES & PAIEMENT -->
                <div class="card shadow-sm border-0 p-4 sticky-top offset-top-100">
                    <h4 class="fw-bold mb-3 text-primary"><i class="fas fa-receipt me-2"></i>3. Détails & Tarifs</h4>
                    
                    <div id="formule-selection-zone" class="mb-4">
                        <p class="text-muted text-center py-3 italic mb-0">Aucun voyageur n'est encore affecté à une chambre.</p>
                    </div>

                    <!-- Résumé financier dynamique -->
                    <div class="p-3 bg-light rounded-3 border mb-4 shadow-none">
                        <h6 class="fw-bold mb-3">Résumé financier estimé</h6>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Chambres réservées :</span>
                            <span class="fw-bold text-dark"><?php echo count($panier); ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Membres à loger :</span>
                            <span class="fw-bold text-dark" id="summary-participant-count">0</span>
                        </div>
                        
                        <hr class="my-2">
                        
                        <div id="summary-details" class="mb-2">
                            <!-- Rempli dynamiquement par JS -->
                        </div>

                        <div class="d-flex justify-content-between h5 fw-bold text-primary mb-0 pt-2 border-top">
                            <span>TOTAL ESTIMÉ :</span>
                            <span id="summary-grand-total">0 €</span>
                        </div>
                    </div>

                    <!-- Validation finale -->
                    <button type="submit" class="btn btn-success btn-lg w-100 py-3 fw-bold shadow-sm" id="btn-submit-res">
                        <i class="fas fa-credit-card me-2"></i>Confirmer et payer
                    </button>
                    
                    <a href="recherche.php" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="fas fa-search me-1"></i>Modifier ma recherche
                    </a>
                </div>

            </div>
        </div>

    </form>
</div>

<!-- ================= MODALE GROUPE ================= -->
<div class="modal fade" id="modalGroupe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-users-cog me-2"></i>Nouveau Groupe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <?php 
                $form_action = "../actions/ajouter_groupe.php?redirect=" . urlencode("../pages/reservation.php");
                $submit_label = "Créer le groupe";
                include __DIR__ . '/../forms/form_groupe.php'; 
                ?>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODALE VOYAGEUR ================= -->
<div class="modal fade" id="modalVoyageur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Ajouter un proche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <?php 
                $form_action = "../actions/ajouter_voyageur.php?redirect=" . urlencode("../pages/reservation.php");
                $submit_label = "Ajouter à ma tribu";
                $voyageur = null;
                include __DIR__ . '/../forms/form_voyageur.php'; 
                ?>
            </div>
        </div>
    </div>
</div>

<!-- ================= LOGIQUE JS INTERACTIVE DE GESTION DU PANIER ================= -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const listFormules = <?php echo json_encode($formules); ?>;
    const roomCards = document.querySelectorAll(".room-card");
    const summaryCount = document.getElementById("summary-participant-count");
    const summaryDetails = document.getElementById("summary-details");
    const summaryGrandTotal = document.getElementById("summary-grand-total");
    const formuleZone = document.getElementById("formule-selection-zone");

    // 1. Ecouteur sur les participants du Bloc 1
    const checkboxes = document.querySelectorAll(".participant-checkbox");
    checkboxes.forEach(cb => {
        cb.addEventListener("change", renderLayout);
    });

    function renderLayout() {
        // Obtenir tous les participants cochés
        const selected = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => ({
                id: cb.dataset.id,
                name: cb.dataset.fullname,
                dob: cb.dataset.dob
            }));

        summaryCount.textContent = selected.length;

        if (selected.length === 0) {
            // Vue vide
            roomCards.forEach(card => {
                const list = card.querySelector(".assigned-list");
                list.innerHTML = `<p class="text-muted small italic text-center py-2 mb-0 no-occupant-msg">Sélectionnez d'abord des voyageurs ci-dessus pour les affecter ici.</p>`;
            });
            formuleZone.innerHTML = `<p class="text-muted text-center py-3 italic mb-0">Aucun voyageur n'est encore affecté à une chambre.</p>`;
            updateFinancials();
            return;
        }

        // Pour chaque chambre, générer la liste des personnes assignables (cochées dans le Bloc 1)
        roomCards.forEach(card => {
            const rNum = card.dataset.num;
            const rLits = parseInt(card.dataset.lits);
            const listContainer = card.querySelector(".assigned-list");
            
            // Conserver l'état de sélection actuel pour cette chambre
            const currentlyChecked = Array.from(listContainer.querySelectorAll("input[type='checkbox']:checked")).map(i => i.value);

            let html = `<div class="row g-2">`;
            selected.forEach(v => {
                const isChecked = currentlyChecked.includes(v.id) ? "checked" : "";
                html += `
                <div class="col-sm-6">
                    <div class="form-check p-2 rounded border h-100 d-flex align-items-center bg-light">
                        <input class="form-check-input assignment-checkbox ms-1" 
                               type="checkbox" 
                               name="assignments[${rNum}][]" 
                               value="${v.id}" 
                               id="assign_${rNum}_${v.id}"
                               data-room="${rNum}"
                               data-voyageur-id="${v.id}"
                               data-fullname="${v.name}"
                               data-dob="${v.dob}"
                               ${isChecked}>
                        <label class="form-check-label ms-3 small text-dark" for="assign_${rNum}_${v.id}">
                            ${v.name}
                        </label>
                    </div>
                </div>`;
            });
            html += `</div>`;
            listContainer.innerHTML = html;
        });

        // Liaison d'écouteurs sur les affectations de chambres
        bindAssignmentEvents();
        updateFormulesAndFinancials();
    }

    function bindAssignmentEvents() {
        const assignCbs = document.querySelectorAll(".assignment-checkbox");
        assignCbs.forEach(cb => {
            cb.addEventListener("change", function() {
                const voyId = this.dataset.voyageurId;
                const rNum = this.dataset.room;
                const parentCard = document.getElementById("room_card_" + rNum);
                const maxCapacity = parseInt(parentCard.dataset.lits);

                if (this.checked) {
                    // SÉCURITÉ A : Un voyageur ne peut pas être dans deux chambres en même temps
                    assignCbs.forEach(otherCb => {
                        if (otherCb.dataset.voyageurId === voyId && otherCb.dataset.room !== rNum) {
                            otherCb.checked = false;
                            otherCb.disabled = true;
                        }
                    });

                    // SÉCURITÉ B : Respecter la capacité de lits de la chambre
                    const checkedInRoom = parentCard.querySelectorAll(".assignment-checkbox:checked");
                    if (checkedInRoom.length > maxCapacity) {
                        alert("La capacité maximale de la Chambre " + rNum + " (" + maxCapacity + " lits) est dépassée !");
                        this.checked = false;
                    }
                } else {
                    // Réactiver le voyageur dans les autres chambres
                    assignCbs.forEach(otherCb => {
                        if (otherCb.dataset.voyageurId === voyId) {
                            otherCb.disabled = false;
                        }
                    });
                }

                updateFormulesAndFinancials();
            });
        });
    }

    function updateFormulesAndFinancials() {
        const assignCbs = document.querySelectorAll(".assignment-checkbox:checked");
        
        if (assignCbs.length === 0) {
            formuleZone.innerHTML = `<p class="text-muted text-center py-3 italic mb-0">Aucun voyageur n'est encore affecté à une chambre.</p>`;
            updateFinancials();
            return;
        }

        // Construire le formulaire d'attribution des formules au Bloc 3
        let htmlFormules = `<h5 class="fw-bold mb-3"><i class="fas fa-utensils me-2 text-secondary"></i>Formules par voyageur</h5>`;
        assignCbs.forEach(cb => {
            const voyId = cb.dataset.voyageurId;
            const rNum = cb.dataset.room;
            const name = cb.dataset.fullname;
            const dob = cb.dataset.dob;

            // Déterminer l'âge pour adapter l'étiquette (Bébé, Enfant, Adulte)
            const age = calculateAgeJS(dob);
            let ageLabel = "Adulte";
            if (age < 2) ageLabel = "Bébé (Gratuit)";
            else if (age < 12) ageLabel = "Enfant (-20%)";

            htmlFormules += `
            <div class="p-3 bg-white border rounded-3 mb-2 shadow-none">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark text-truncate d-inline-block max-w-180">${name}</span>
                    <span class="badge bg-light text-primary border">${ageLabel}</span>
                </div>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-0">Formule (Ch. ${rNum})</span>
                    <select name="formules[${rNum}][${voyId}]" class="form-select formula-select" data-dob="${dob}" data-room="${rNum}" data-voy-id="${voyId}" required>
                        ${listFormules.map(f => `<option value="${f.type_formule}" data-price="${f.prix_base}">${f.type_formule} (${f.prix_base}€)</option>`).join('')}
                    </select>
                </div>
            </div>`;
        });

        formuleZone.innerHTML = htmlFormules;

        // Liaison d'écouteur sur les changements de formule pour recalculer le tarif instantanément
        document.querySelectorAll(".formula-select").forEach(sel => {
            sel.addEventListener("change", updateFinancials);
        });

        updateFinancials();
    }

    function updateFinancials() {
        const selectElements = document.querySelectorAll(".formula-select");
        let grandTotal = 0;
        let detailsHtml = "";

        // Dictionnaire pour suivre les voyageurs par chambre afin de déduire les lits vides
        const roomOccupants = {};
        roomCards.forEach(card => {
            roomOccupants[card.dataset.num] = {
                capacity: parseInt(card.dataset.lits),
                occupants: 0
            };
        });

        selectElements.forEach(select => {
            const dob = select.dataset.dob;
            const rNum = select.dataset.room;
            const option = select.options[select.selectedIndex];
            const basePrice = parseFloat(option.dataset.price);

            const age = calculateAgeJS(dob);
            let finalPrice = basePrice;
            
            if (age < 2) {
                finalPrice = 0; // Bébé gratuit et n'occupe pas de lit
            } else {
                roomOccupants[rNum].occupants++; // Occupe un lit
                if (age < 12) {
                    finalPrice = Math.round(basePrice * 0.8); // Enfant -20%
                }
            }

            grandTotal += finalPrice;
        });

        // Calcul des lits vides pour chaque chambre
        detailsHtml += `<div class="mb-3 small">`;
        for (const [rNum, data] of Object.entries(roomOccupants)) {
            const emptyBeds = data.capacity - data.occupants;
            if (emptyBeds > 0) {
                const penalty = emptyBeds * 150;
                grandTotal += penalty;
                detailsHtml += `
                <div class="d-flex justify-content-between text-warning mb-1">
                    <span>Chambre ${rNum} : ${emptyBeds} lit(s) vide(s)</span>
                    <span>+ ${penalty} €</span>
                </div>`;
            } else {
                detailsHtml += `
                <div class="d-flex justify-content-between text-success mb-1">
                    <span>Chambre ${rNum} : Remplie</span>
                    <span>0 €</span>
                </div>`;
            }
        }
        detailsHtml += `</div>`;

        summaryDetails.innerHTML = detailsHtml;
        summaryGrandTotal.textContent = grandTotal.toLocaleString('fr-FR') + " €";
    }

    function calculateAgeJS(dobString) {
        if (!dobString) return 0;
        const today = new Date();
        const birthDate = new Date(dobString);
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age;
    }
});

document.querySelector('#modalGroupe form').addEventListener('submit', function(e) {
    e.preventDefault(); // 1. Bloque le rechargement de la page

    fetch(this.action, {
        method: 'POST',
        body: new FormData(this), // 2. Envoie automatiquement les champs du formulaire
        headers: { 'X-Requested-With': 'XMLHttpRequest' } // 3. Dit au PHP que c'est du AJAX
    })
    .then(res => res.json()) // 4. Convertit la réponse du serveur en objet JS
    .then(data => {
        if (data.success) {
            // 5. Ajoute le nouveau groupe dans le <select> et le sélectionne
            const select = document.querySelector('select[name="nom_groupe"]');
            select.add(new Option(data.nom_groupe, data.nom_groupe, true, true));
            
            // 6. Ferme la modale Bootstrap et vide le formulaire
            bootstrap.Modal.getInstance(document.getElementById('modalGroupe')).hide();
            this.reset();
        } else {
            alert(data.message); // Affiche l'erreur (ex: groupe déjà existant)
        }
    });
});

document.querySelector('#modalVoyageur form').addEventListener('submit', function(e) {
    e.preventDefault(); // Bloque le rechargement

    fetch(this.action, {
        method: 'POST',
        body: new FormData(this),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const conteneurGlobal = document.getElementById('tribu-conteneur');
            let zoneParticipants = document.getElementById('zone-participants');
            
            // 1. Si la tribu était vide, on vire l'alerte et on crée la structure de la grille
            const alertVide = document.getElementById('alert-tribu-vide');
            if (alertVide) {
                alertVide.remove();
                conteneurGlobal.innerHTML = `<div class="p-3 bg-light rounded-3 border row g-2 max-height-200 overflow-y-auto" id="zone-participants"></div>`;
                zoneParticipants = document.getElementById('zone-participants');
            }

            // 2. Formatage rapide de la date YYYY-MM-DD en DD/MM/YYYY pour l'affichage
            const dateFr = data.date_naissance.split('-').reverse().join('/');

            // 3. On crée la nouvelle carte HTML (strictement identique à ton template PHP)
            const nouvelleCarte = document.createElement('div');
            nouvelleCarte.className = 'col-sm-6 col-md-4';
            nouvelleCarte.innerHTML = `
                <div class="form-check p-2 bg-white rounded border h-100 d-flex align-items-center">
                    <input class="form-check-input ms-1 participant-checkbox" 
                           type="checkbox" 
                           value="${data.id_client}" 
                           id="part_${data.id_client}"
                           data-id="${data.id_client}"
                           data-fullname="${data.prenom} ${data.nom}"
                           data-dob="${data.date_naissance}">
                    <label class="form-check-label ms-3 small text-dark fw-bold" for="part_${data.id_client}">
                        ${data.prenom} ${data.nom}
                        <span class="d-block text-muted text-xs font-normal">Né le ${dateFr}</span>
                    </label>
                </div>
            `;

            // 4. On l'injecte dans la liste
            zoneParticipants.appendChild(nouvelleCarte);

            // 5. Fermeture et reset de la modale
            bootstrap.Modal.getInstance(document.getElementById('modalVoyageur')).hide();
            this.reset();
        } else {
            alert(data.message);
        }
    });
});

</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>