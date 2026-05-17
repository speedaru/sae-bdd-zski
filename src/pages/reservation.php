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
        <div class="card p-5 shadow border-0 max-width-600 mx-auto bg-white">
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
            <p class="text-muted mb-0">Remplissez les détails, associez les membres de votre tribu aux chambres et validez.</p>
        </div>
    </div>

    <?php if ($success) echo alert($success, 'success'); ?>
    <?php if ($error) echo alert($error, 'danger'); ?>
    
    <div id="js-error-container" class="mb-4"></div>

    <form method="POST" action="../actions/valider_sejour.php" id="form-reservation">
        
        <!-- BLOC 1 : PÉRIODE & CHOIX DE LA TRIBU -->
        <div class="card shadow-sm border-0 p-4 mb-4 bg-white">
            <h4 class="fw-bold mb-3 text-primary border-bottom pb-2">
                <i class="fas fa-calendar-alt me-2"></i>1. Période & Tribu
            </h4>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Date de début du séjour</label>
                    <input type="date" name="date_debut" id="date_debut" class="form-control" required>
                    <div class="form-text text-muted"><i class="fas fa-info-circle me-1"></i>Les séjours commencent obligatoirement un samedi.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Groupe de séjour</label>
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

                <!-- Liste simple verticale des participants -->
                <div class="col-12 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold mb-0">Sélectionnez les membres de votre tribu qui partent :</label>
                        <button class="btn btn-link btn-sm text-decoration-none" type="button" data-bs-toggle="modal" data-bs-target="#modalVoyageur">
                            <i class="fas fa-plus-circle me-1"></i>Enregistrer un proche
                        </button>
                    </div>

                    <div id="no-voyageurs-alert" class="alert alert-warning border-0 <?php echo !empty($voyageurs) ? 'd-none' : ''; ?>">
                        <i class="fas fa-info-circle me-2"></i>Votre tribu est vide. Enregistrez des proches pour démarrer.
                    </div>

                    <!-- Liste simple verticale empilée -->
                    <div class="p-3 bg-light rounded-3 border <?php echo empty($voyageurs) ? 'd-none' : ''; ?>" id="participants-selection-grid" style="max-height: 250px; overflow-y: auto;">
                        <?php foreach ($voyageurs as $voy): ?>
                            <div class="form-check py-2 border-bottom border-light participant-checkbox-container">
                                <input class="form-check-input participant-checkbox" 
                                       type="checkbox" 
                                       name="voyageurs_selectionnes[]"
                                       value="<?php echo $voy['id_client']; ?>" 
                                       id="part_<?php echo $voy['id_client']; ?>"
                                       data-id="<?php echo $voy['id_client']; ?>"
                                       data-fullname="<?php echo h($voy['prenom'] . ' ' . $voy['nom']); ?>"
                                       data-dob="<?php echo h($voy['date_naissance']); ?>">
                                <label class="form-check-label ms-2 text-dark fw-bold" for="part_<?php echo $voy['id_client']; ?>">
                                    <?php echo h($voy['prenom'] . ' ' . $voy['nom']); ?>
                                    <span class="text-muted fw-normal small ms-2">(Né le <?php echo date_fr($voy['date_naissance']); ?>)</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOC 2 : CHOIX FORMULES ET REPARTITION DES CHAMBRES -->
        <div class="card shadow-sm border-0 p-4 mb-4 bg-white">
            <h4 class="fw-bold mb-3 text-primary border-bottom pb-2">
                <i class="fas fa-utensils me-2"></i>2. Choix formules et répartition des chambres
            </h4>
            <p class="text-muted small">Sélectionnez la formule et la chambre d'affectation pour chaque voyageur participant.</p>
            
            <div id="repartition-voyageurs-list" class="border rounded p-3 bg-light" style="max-height: 350px; overflow-y: auto;">
                <p class="text-muted text-center py-4 mb-0 italic" id="empty-repartition-msg">Cochez des participants dans la section 1 pour configurer leur formule et leur chambre.</p>
            </div>
        </div>

        <!-- BLOC 3 : RÉCAPITULATIF PAR CHAMBRE (SITUÉ EN BAS) -->
        <div class="card shadow-sm border-0 p-4 mb-4 bg-white">
            <h4 class="fw-bold mb-3 text-primary border-bottom pb-2">
                <i class="fas fa-file-invoice-dollar me-2"></i>3. Récapitulatif du séjour
            </h4>
            
            <div class="row g-3" id="recapitulatif-chambres-container">
                <!-- Grille horizontale des chambres du panier -->
                <?php foreach ($chambres as $ch): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="chambre-recap-box border rounded p-3 bg-light h-100" id="recap_room_<?php echo $ch['num_chambre']; ?>" data-num="<?php echo $ch['num_chambre']; ?>" data-lits="<?php echo $ch['nb_lits']; ?>" data-batiment="<?php echo h($ch['batiment']); ?>">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2 bg-white px-2 py-1 rounded">
                                <span class="fw-bold text-dark"><i class="fas fa-bed me-1 text-secondary"></i>Chambre <?php echo $ch['num_chambre']; ?></span>
                                <span class="badge bg-secondary"><?php echo $ch['nb_lits']; ?> lits</span>
                            </div>
                            <div class="occupants-list mb-2 small text-muted px-1">
                                <span class="italic text-xs text-muted">Aucun skieur affecté</span>
                            </div>
                            <div class="empty-penalty-info text-warning text-xs border-top pt-2 d-none px-1"></div>
                            <div class="room-total-cost text-end fw-bold text-dark mt-2 text-sm px-1 border-top pt-2">
                                Total chambre : 0 €
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Total général de la réservation -->
            <div class="p-3 bg-light rounded-3 border mt-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Total global estimé</h5>
                    <small class="text-muted">Calculé d'après vos formules et la tarification de la station (incluant la pénalité de lits vides).</small>
                </div>
                <div class="text-end">
                    <span id="summary-grand-total" class="h3 fw-bold text-primary mb-0 d-block">0 €</span>
                </div>
            </div>

            <!-- Soumission finale -->
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-success btn-lg px-5 py-3 fw-bold shadow-sm" id="btn-submit-res">
                    <i class="fas fa-check-double me-2"></i>Confirmer ma réservation
                </button>
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
                <div class="modal-ajax-errors alert alert-danger d-none"></div>
                <?php 
                $form_action = "../actions/ajouter_groupe.php";
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
                <div class="modal-ajax-errors alert alert-danger d-none"></div>
                <?php 
                $form_action = "../actions/ajouter_voyageur.php";
                $submit_label = "Ajouter à ma tribu";
                $voyageur = null;
                include __DIR__ . '/../forms/form_voyageur.php'; 
                ?>
            </div>
        </div>
    </div>
</div>

<!-- ================= LOGIQUE JAVASCRIPT AJAX & DYNAMIQUE COMPLET ================= -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const listFormules = <?php echo json_encode($formules); ?>;
    const listChambres = <?php echo json_encode($chambres); ?>;
    
    const repartitionList = document.getElementById("repartition-voyageurs-list");
    const selectionGrid = document.getElementById("participants-selection-grid");
    const dateInput = document.getElementById("date_debut");
    const recapBoxes = document.querySelectorAll(".chambre-recap-box");
    const summaryGrandTotal = document.getElementById("summary-grand-total");

    // Recalculer lors du changement de date
    dateInput.addEventListener("change", updateLayout);

    // Écouteur de sélection des participants
    if (selectionGrid) {
        selectionGrid.addEventListener("change", function(e) {
            if (e.target.classList.contains("participant-checkbox")) {
                updateLayout();
            }
        });
    }

    function updateLayout() {
        const checkboxes = document.querySelectorAll(".participant-checkbox:checked");
        
        if (checkboxes.length === 0) {
            repartitionList.innerHTML = `<p class="text-muted text-center py-4 mb-0 italic" id="empty-repartition-msg">Cochez des participants dans la section 1 pour configurer leur formule et leur chambre.</p>`;
            calculateFinancials();
            return;
        }

        // Sauvegarder l'état actuel des sélections pour les conserver d'un clic à l'autre
        const currentSelections = {};
        document.querySelectorAll(".repartition-row").forEach(row => {
            const vId = row.dataset.id;
            const formSel = row.querySelector(".formule-select");
            const roomSel = row.querySelector(".chambre-select");
            currentSelections[vId] = {
                formule: formSel ? formSel.value : "",
                chambre: roomSel ? roomSel.value : ""
            };
        });

        // Générer la liste verticale
        let html = "";
        checkboxes.forEach(cb => {
            const vId = cb.dataset.id;
            const name = cb.dataset.fullname;
            const dob = cb.dataset.dob;
            
            const prevFormule = currentSelections[vId] ? currentSelections[vId].formule : "";
            const prevChambre = currentSelections[vId] ? currentSelections[vId].chambre : "";

            html += `
            <div class="row align-items-center mb-3 p-2 bg-white rounded border repartition-row" data-id="${vId}" data-fullname="${name}" data-dob="${dob}">
                <div class="col-md-4 col-sm-12 fw-bold text-dark mb-2 mb-md-0">
                    <i class="fas fa-user-circle me-1 text-muted"></i>${name}
                </div>
                <!-- Formule -->
                <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-0 text-muted">Formule</span>
                        <select name="formules[${vId}]" class="form-select formule-select" required>
                            ${listFormules.map(f => `
                                <option value="${f.type_formule}" data-price="${f.prix_base}" ${f.type_formule === prevFormule ? 'selected' : ''}>
                                    ${f.type_formule} (${f.prix_base} €)
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
                <!-- Chambre -->
                <div class="col-md-4 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-0 text-muted">Chambre</span>
                        <select name="affectations[${vId}]" class="form-select chambre-select" required data-prev="${prevChambre}">
                            <option value="" disabled ${prevChambre === "" ? 'selected' : ''}>-- Choisir --</option>
                            ${listChambres.map(c => `
                                <option value="${c.num_chambre}" ${String(c.num_chambre) === String(prevChambre) ? 'selected' : ''}>
                                    Chambre ${c.num_chambre} (Capacité: ${c.nb_lits} lits)
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
            </div>`;
        });

        repartitionList.innerHTML = html;

        // Liaison d'écouteurs sur les modifications des selects
        document.querySelectorAll(".formule-select").forEach(el => {
            el.addEventListener("change", calculateFinancials);
        });

        document.querySelectorAll(".chambre-select").forEach(el => {
            el.addEventListener("change", handleRoomAssignment);
        });

        calculateFinancials();
    }

    // fonction utilitaire pour afficher l'erreur en rouge sur la page
    function showInlineError(message) {
        const container = document.getElementById("js-error-container");
        if (container) {
            container.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                    <div>
                        <strong class="d-block">Ajustement impossible</strong>
                        ${message}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            // Défilement fluide automatique de l'écran vers le message d'erreur
            container.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Fonction de gestion de l'affectation mise à jour sans alert()
    function handleRoomAssignment(e) {
        const select = e.target;
        const targetRoomNum = select.value;
        const previousRoomNum = select.dataset.prev;
        const row = select.closest(".repartition-row");
        const dob = row.dataset.dob;
        const startDate = dateInput.value;

        if (!targetRoomNum) return;

        // 1. Un bébé de moins de 2 ans n'occupe pas de lit
        const age = calculateAgeJS(dob, startDate);
        const consumesBed = (age >= 2);

        if (consumesBed) {
            // 2. Compter le nombre de lits occupés dans la chambre choisie
            let occupiedBeds = 0;
            const targetRoomCapacity = parseInt(document.getElementById("recap_room_" + targetRoomNum).dataset.lits);

            // Parcourir toutes les lignes de répartition pour compter l'occupation de la chambre cible
            document.querySelectorAll(".repartition-row").forEach(otherRow => {
                const otherSelect = otherRow.querySelector(".chambre-select");
                if (otherSelect && otherSelect.value === targetRoomNum) {
                    const otherDob = otherRow.dataset.dob;
                    const otherAge = calculateAgeJS(otherDob, startDate);
                    if (otherAge >= 2) {
                        occupiedBeds++;
                    }
                }
            });

            // Si dépassement : afficher l'erreur en rouge sur la page et annuler
            if (occupiedBeds > targetRoomCapacity) {
                showInlineError(`La Chambre n°${targetRoomNum} est complète (${targetRoomCapacity} lits disponibles maximum). Impossible d'y ajouter un nouvel occupant.`);
                select.value = previousRoomNum; // Retour à la valeur précédente
                return;
            }
        }

        // Effacer le message d'erreur si la nouvelle affectation est valide
        const errorContainer = document.getElementById("js-error-container");
        if (errorContainer) {
            errorContainer.innerHTML = "";
        }

        // Mettre à jour l'ancienne valeur stockée pour les futurs contrôles
        select.dataset.prev = targetRoomNum;
        calculateFinancials();
    }

    function calculateFinancials() {
        const rows = document.querySelectorAll(".repartition-row");
        const startDate = dateInput.value;

        // Dictionnaire des occupants par chambre du panier
        const occupantsByRoom = {};
        recapBoxes.forEach(box => {
            occupantsByRoom[box.dataset.num] = [];
        });

        // Classer les occupants sélectionnés par chambre
        rows.forEach(row => {
            const name = row.dataset.fullname;
            const dob = row.dataset.dob;
            const formuleSelect = row.querySelector(".formule-select");
            const chambreSelect = row.querySelector(".chambre-select");

            const selectedFormule = formuleSelect.value;
            const selectedFormulePrice = parseFloat(formuleSelect.options[formuleSelect.selectedIndex].dataset.price);
            const selectedChambre = chambreSelect.value;

            if (selectedChambre && occupantsByRoom[selectedChambre]) {
                const age = calculateAgeJS(dob, startDate);
                let finalPrice = selectedFormulePrice;
                let priceLabel = "";

                if (age < 2) {
                    finalPrice = 0;
                    priceLabel = " (Tarif Bébé Gratuit)";
                } else if (age < 12) {
                    finalPrice = Math.round(selectedFormulePrice * 0.8);
                    priceLabel = " (Tarif Enfant -20%)";
                }

                occupantsByRoom[selectedChambre].push({
                    name: name,
                    formule: selectedFormule,
                    price: finalPrice,
                    label: priceLabel,
                    occupiesBed: age >= 2
                });
            }
        });

        let grandTotal = 0;

        // Remplir les boîtes récapitulatives en bas de page
        recapBoxes.forEach(box => {
            const roomNum = box.dataset.num;
            const capacity = parseInt(box.dataset.lits);
            const listContainer = box.querySelector(".occupants-list");
            const penaltyInfo = box.querySelector(".empty-penalty-info");
            const costContainer = box.querySelector(".room-total-cost");

            const occupants = occupantsByRoom[roomNum] || [];

            let roomTotal = 0;
            let occupiedBeds = 0;

            if (occupants.length === 0) {
                listContainer.innerHTML = `<span class="italic text-xs text-muted d-block py-1">Aucun skieur affecté</span>`;
                penaltyInfo.classList.add("d-none");
                costContainer.textContent = "Total chambre : 0 €";
                return;
            }

            let html = '<ul class="list-unstyled mb-2">';
            occupants.forEach(occ => {
                html += `
                <li class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom border-light">
                    <span>${occ.name} <small class="text-secondary font-normal">${occ.label}</small></span>
                    <span class="fw-bold">${occ.price} €</span>
                </li>`;
                roomTotal += occ.price;
                if (occ.occupiesBed) {
                    occupiedBeds++;
                }
            });
            html += '</ul>';
            listContainer.innerHTML = html;

            // Calcul de l'amende pour lits vacants (150 € / lit)
            const emptyBeds = capacity - occupiedBeds;
            if (emptyBeds > 0) {
                const penalty = emptyBeds * 150;
                roomTotal += penalty;
                penaltyInfo.innerHTML = `
                    <div class="d-flex justify-content-between text-warning">
                        <span><i class="fas fa-exclamation-circle me-1"></i>${emptyBeds} lit(s) vide(s) (150€/lit)</span>
                        <span>+ ${penalty} €</span>
                    </div>`;
                penaltyInfo.classList.remove("d-none");
            } else {
                penaltyInfo.classList.add("d-none");
            }

            costContainer.textContent = `Total chambre : ${roomTotal} €`;
            grandTotal += roomTotal;
        });

        summaryGrandTotal.textContent = grandTotal.toLocaleString('fr-FR') + " €";
    }

    function calculateAgeJS(dobString, startDateString) {
        if (!dobString) return 0;
        const refDate = startDateString ? new Date(startDateString) : new Date();
        const birthDate = new Date(dobString);
        let age = refDate.getFullYear() - birthDate.getFullYear();
        const m = refDate.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && refDate.getDate() < birthDate.getDate())) {
            age--;
        }
        return age;
    }

    // =================================================================
    // ENREGISTREMENT DE GROUPE EN AJAX (FETCH)
    // =================================================================
    const formGroupe = document.querySelector("#modalGroupe form");
    if (formGroupe) {
        formGroupe.addEventListener("submit", function(e) {
            e.preventDefault();
            const errorDiv = document.querySelector("#modalGroupe .modal-ajax-errors");
            errorDiv.classList.add("d-none");

            const formData = new FormData(this);
            fetch(this.action, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const selectGroupe = document.querySelector("select[name='nom_groupe']");
                    if (selectGroupe) {
                        const newOption = document.createElement("option");
                        newOption.value = data.nom_groupe;
                        newOption.textContent = data.nom_groupe;
                        newOption.selected = true;
                        selectGroupe.appendChild(newOption);
                    }
                    const modalInst = bootstrap.Modal.getInstance(document.getElementById('modalGroupe'));
                    if (modalInst) modalInst.hide();
                    formGroupe.reset();
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.classList.remove("d-none");
                }
            })
            .catch(() => {
                errorDiv.textContent = "Erreur réseau.";
                errorDiv.classList.remove("d-none");
            });
        });
    }

    // =================================================================
    // ENREGISTREMENT DE PROCHE EN AJAX (FETCH)
    // =================================================================
    const formVoyageur = document.querySelector("#modalVoyageur form");
    if (formVoyageur) {
        formVoyageur.addEventListener("submit", function(e) {
            e.preventDefault();
            const errorDiv = document.querySelector("#modalVoyageur .modal-ajax-errors");
            errorDiv.classList.add("d-none");

            const formData = new FormData(this);
            fetch(this.action, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const alertEmpty = document.getElementById("no-voyageurs-alert");
                    if (alertEmpty) alertEmpty.classList.add("d-none");

                    if (selectionGrid) {
                        selectionGrid.classList.remove("d-none");

                        // Ajouter le nouveau venu à la liste verticale de l'étape 1
                        const container = document.createElement("div");
                        container.className = "form-check py-2 border-bottom border-light participant-checkbox-container";
                        
                        container.innerHTML = `
                        <input class="form-check-input participant-checkbox" 
                               type="checkbox" 
                               name="voyageurs_selectionnes[]"
                               value="${data.id_client}" 
                               id="part_${data.id_client}"
                               data-id="${data.id_client}"
                               data-fullname="${data.prenom} ${data.nom}"
                               data-dob="${data.date_naissance}"
                               checked>
                        <label class="form-check-label ms-2 text-dark fw-bold" for="part_${data.id_client}">
                            ${data.prenom} ${data.nom}
                            <span class="text-muted fw-normal small ms-2">(Né le ${formatDateJS(data.date_naissance)})</span>
                        </label>`;
                        
                        selectionGrid.appendChild(container);
                    }

                    // Mettre à jour l'étape 2 d'affectation directement
                    updateLayout();

                    const modalInst = bootstrap.Modal.getInstance(document.getElementById('modalVoyageur'));
                    if (modalInst) modalInst.hide();
                    formVoyageur.reset();
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.classList.remove("d-none");
                }
            })
            .catch(() => {
                errorDiv.textContent = "Erreur réseau.";
                errorDiv.classList.remove("d-none");
            });
        });
    }

    function formatDateJS(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return dateStr;
    }

    // Premier calcul initial
    updateLayout();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>