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
            <h3 class="fw-bold">Votre panier est vide</h3>
            <p class="text-muted">Sélectionnez une ou plusieurs chambres pour configurer votre séjour à la montagne.</p>
            <div class="mt-4">
                <a href="recherche.php" class="btn btn-primary px-4 py-2">Rechercher une chambre</a>
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

<!-- Liaison CSS externe épurée -->
<link rel="stylesheet" href="../assets/css/reservation.css">

<div class="reservation-container">
    
    <div style="margin-bottom: 24px;">
        <h1 style="font-weight: 800; margin-bottom: 4px;">Validation du séjour</h1>
        <p style="color: #64748b; margin: 0;">Affectez vos proches à vos chambres d'hôtel de station de ski.</p>
    </div>

    <!-- Alertes de retour PHP standard -->
    <?php if ($success) echo alert($success, 'success'); ?>
    <?php if ($error) echo alert($error, 'danger'); ?>

    <form method="POST" action="../actions/valider_sejour.php">
        
        <!-- BLOC 1 : PÉRIODE & GROUPE -->
        <div class="section-box">
            <h2 class="section-title">1. Période & Groupe</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="date_debut">Date de début de séjour (Dimanche obligatoire)</label>
                    <input type="date" name="date_debut" id="date_debut" class="form-input" required>
                    <span style="font-size: 0.8rem; color: #64748b;">La semaine se termine le samedi suivant à 10h.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nom_groupe">Sélectionner votre groupe</label>
                    <select name="nom_groupe" id="nom_groupe" class="form-select" required>
                        <option value="" disabled selected>-- Choisir le groupe --</option>
                        <?php foreach ($groupes as $g): ?>
                            <option value="<?php echo h($g['nom_groupe']); ?>"><?php echo h($g['nom_groupe']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="display: flex; gap: 8px; margin-top: 6px;">
                        <button type="button" class="btn-action-tribu" onclick="toggleModal('modal-groupe', true)">+ Créer un groupe</button>
                        <button type="button" class="btn-action-tribu" onclick="toggleModal('modal-voyageur', true)">+ Créer un voyageur</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOC 2 : HÉBERGEMENTS, AFFECTATIONS & SUPPRESSION -->
        <div class="section-box">
            <h2 class="section-title">2. Hébergements & Affectation de la Tribu</h2>
            <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 20px;">Cochez chaque personne participant à ce séjour dans les chambres de votre sélection.</p>

            <?php foreach ($chambres as $ch): ?>
                <div class="chambre-card">
                    <div class="chambre-header">
                        <div class="chambre-info">
                            Chambre n°<?php echo $ch['num_chambre']; ?> — Bâtiment <?php echo h($ch['batiment']); ?>, Étage <?php echo $ch['etage']; ?> 
                            <span class="chambre-capacity"><?php echo $ch['nb_lits']; ?> lits</span>
                        </div>
                        <a href="../actions/supprimer_panier.php?id=<?php echo $ch['num_chambre']; ?>" class="btn-delete-chambre" onclick="return confirm('Retirer cette chambre du panier ?');">
                            Supprimer la chambre
                        </a>
                    </div>
                    
                    <div style="padding: 16px; overflow-x: auto;">
                        <table class="table-affectation">
                            <thead>
                                <tr>
                                    <th style="width: 80px; text-align: center;">Affecter</th>
                                    <th>Nom & Prénom</th>
                                    <th>Formule choisie</th>
                                </tr>
                            </thead>
                            <tbody class="liste-voyageurs-chambre" data-room="<?php echo $ch['num_chambre']; ?>">
                                <?php if (empty($voyageurs)): ?>
                                    <tr class="no-voyageurs-row">
                                        <td colspan="3" style="text-align: center; color: #94a3b8; font-style: italic;">
                                            Aucun voyageur disponible dans votre tribu. Enregistrez vos proches pour démarrer.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($voyageurs as $voy): ?>
                                        <tr>
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="chambres[<?php echo $ch['num_chambre']; ?>][voyageurs][]" value="<?php echo $voy['id_client']; ?>">
                                            </td>
                                            <td style="font-weight: 600; color: #1e293b;">
                                                <?php echo h($voy['prenom'] . ' ' . $voy['nom']); ?>
                                                <span style="font-size: 0.8rem; font-weight: normal; color: #64748b; margin-left: 10px;">
                                                    (né le <?php echo date_fr($voy['date_naissance']); ?>)
                                                </span>
                                            </td>
                                            <td>
                                                <select name="chambres[<?php echo $ch['num_chambre']; ?>][formules][<?php echo $voy['id_client']; ?>]" class="form-select" style="max-width: 250px;">
                                                    <?php foreach ($formules as $f): ?>
                                                        <option value="<?php echo h($f['type_formule']); ?>">
                                                            <?php echo h($f['type_formule']); ?> (<?php echo $f['prix_base']; ?> €)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- BLOC 3 : SOUMISSION -->
        <div class="section-box">
            <h2 class="section-title">3. Validation & Tarification</h2>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; margin-bottom: 20px; font-size: 0.95rem;">
                <p style="margin-bottom: 8px; font-weight: 700; color: #334155;">Informations sur la facturation de la station :</p>
                <ul style="margin: 0; padding-left: 20px; color: #475569; line-height: 1.6;">
                    <li>Bébés (moins de 2 ans au départ) : gratuit (n'occupent pas de lits physiques).</li>
                    <li>Enfants (moins de 12 ans au départ) : réduction de 20% sur la formule choisie.</li>
                    <li>Amende de lit inoccupé : 150 € par lit vide par rapport à la capacité de la chambre louée.</li>
                </ul>
                <p style="margin-top: 12px; margin-bottom: 0; font-style: italic; color: #64748b;">Le montant final exact de votre séjour sera calculé et facturé à la confirmation.</p>
            </div>
            
            <div class="btn-submit-container">
                <button type="submit" class="btn-submit-main">
                    Confirmer ma réservation
                </button>
            </div>
        </div>

    </form>
</div>

<!-- ================= MODALE GROUPE ÉPURÉE ================= -->
<div id="modal-groupe" class="custom-modal-overlay hidden">
    <div class="custom-modal-box">
        <div class="custom-modal-header">
            <h3 class="custom-modal-title">Nouveau Groupe</h3>
            <button type="button" class="custom-modal-close" onclick="toggleModal('modal-groupe', false)">&times;</button>
        </div>
        <div class="custom-modal-body">
            <div class="modal-ajax-errors hidden" id="errors-groupe"></div>
            <?php 
            $form_action = "../actions/ajouter_groupe.php";
            $submit_label = "Créer le groupe";
            include __DIR__ . '/../forms/form_groupe.php'; 
            ?>
        </div>
    </div>
</div>

<!-- ================= MODALE VOYAGEUR ÉPURÉE ================= -->
<div id="modal-voyageur" class="custom-modal-overlay hidden">
    <div class="custom-modal-box" style="max-width: 700px;">
        <div class="custom-modal-header">
            <h3 class="custom-modal-title">Ajouter un skieur</h3>
            <button type="button" class="custom-modal-close" onclick="toggleModal('modal-voyageur', false)">&times;</button>
        </div>
        <div class="custom-modal-body">
            <div class="modal-ajax-errors hidden" id="errors-voyageur"></div>
            <?php 
            $form_action = "../actions/ajouter_voyageur.php";
            $submit_label = "Ajouter à ma tribu";
            $voyageur = null;
            include __DIR__ . '/../forms/form_voyageur.php'; 
            ?>
        </div>
    </div>
</div>

<!-- ================= SCRIPT JAVASCRIPT AJAX INTERNE ÉPURÉ ================= -->
<script>
const listFormules = <?php echo json_encode($formules); ?>;

function toggleModal(modalId, show) {
    const modal = document.getElementById(modalId);
    if (modal) {
        if (show) modal.classList.remove('hidden');
        else modal.classList.add('hidden');
    }
}

document.addEventListener("DOMContentLoaded", function() {
    // A. Interception de l'enregistrement de groupe (AJAX Fetch)
    const formGroup = document.querySelector("#modal-groupe form");
    if (formGroup) {
        formGroup.addEventListener("submit", function(e) {
            e.preventDefault();
            const errorDiv = document.getElementById("errors-groupe");
            errorDiv.classList.add("hidden");

            const formData = new FormData(this);
            fetch(this.action, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById("nom_groupe");
                    if (select) {
                        const opt = document.createElement("option");
                        opt.value = data.nom_groupe;
                        opt.textContent = data.nom_groupe;
                        opt.selected = true;
                        select.appendChild(opt);
                    }
                    toggleModal('modal-groupe', false);
                    formGroup.reset();
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.classList.remove("hidden");
                }
            })
            .catch(() => {
                errorDiv.textContent = "Erreur de connexion au serveur.";
                errorDiv.classList.remove("hidden");
            });
        });
    }

    // B. Interception de l'enregistrement de skieur (AJAX Fetch)
    const formVoyageur = document.querySelector("#modal-voyageur form");
    if (formVoyageur) {
        formVoyageur.addEventListener("submit", function(e) {
            e.preventDefault();
            const errorDiv = document.getElementById("errors-voyageur");
            errorDiv.classList.add("hidden");

            const formData = new FormData(this);
            fetch(this.action, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Injecter instantanément ce skieur dans les tables de chaque chambre
                    document.querySelectorAll(".liste-voyageurs-chambre").forEach(tbody => {
                        const roomNum = tbody.dataset.room;
                        
                        // Retirer le message vide s'il est présent
                        const emptyRow = tbody.querySelector(".no-voyageurs-row");
                        if (emptyRow) {
                            emptyRow.remove();
                        }

                        // Création du markup de la ligne
                        const tr = document.createElement("tr");
                        let optionsHtml = listFormules.map(f => `
                            <option value="${f.type_formule}">${f.type_formule} (${f.prix_base} €)</option>
                        `).join('');

                        tr.innerHTML = `
                            <td style="text-align: center;">
                                <input type="checkbox" name="chambres[${roomNum}][voyageurs][]" value="${data.id_client}">
                            </td>
                            <td style="font-weight: 600; color: #1e293b;">
                                ${data.prenom} ${data.nom}
                                <span style="font-size: 0.8rem; font-weight: normal; color: #64748b; margin-left: 10px;">
                                    (né le ${formatDateFR(data.date_naissance)})
                                </span>
                            </td>
                            <td>
                                <select name="chambres[${roomNum}][formules][${data.id_client}]" class="form-select" style="max-width: 250px;">
                                    ${optionsHtml}
                                </select>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    toggleModal('modal-voyageur', false);
                    formVoyageur.reset();
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.classList.remove("hidden");
                }
            })
            .catch(() => {
                errorDiv.textContent = "Erreur de communication avec le serveur.";
                errorDiv.classList.remove("hidden");
            });
        });
    }

    function formatDateFR(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return dateStr;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>