<?php
/**
 * page de reservation / validation du panier
 */

require_once __DIR__ . '/../includes/header.php';

// protection
require_login("../pages/reservation.php");

$user_id = $_SESSION['id_user'];
$panier = $_SESSION['panier'] ?? [];

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);

// gestion du panier vide
if (empty($panier)) {
    ?>
    <link rel="stylesheet" href="../assets/css/reservation.css">
    <div class="empty-panier-container">
        <h3>Votre panier est vide</h3>
        <p>Selectionnez une ou plusieurs chambres pour configurer votre sejour à la montagne.</p>
        <p><a href="recherche.php" class="btn-submit">Rechercher une chambre</a></p>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// recuperation des donnees
try {
    // details des chambres dans le panier
    $placeholders = implode(',', array_fill(0, count($panier), '?'));
    $stmtChambres = $pdo->prepare("SELECT * FROM chambre WHERE num_chambre IN ($placeholders) ORDER BY num_chambre ASC");
    $stmtChambres->execute($panier);
    $chambres = $stmtChambres->fetchAll(PDO::FETCH_ASSOC);

    // groupes de l'utilisateur
    $stmtGroupes = $pdo->prepare("SELECT * FROM groupe WHERE id_user = ? ORDER BY nom_groupe ASC");
    $stmtGroupes->execute([$user_id]);
    $groupes = $stmtGroupes->fetchAll(PDO::FETCH_ASSOC);

    // affiche des membres du carnet de voyageurs
    $stmtVoyageurs = $pdo->prepare("
        SELECT c.* FROM client c
        JOIN gestion_voyageurs gv ON c.id_client = gv.id_client
        WHERE gv.id_user = ?
        ORDER BY c.nom ASC, c.prenom ASC
    ");
    $stmtVoyageurs->execute([$user_id]);
    $voyageurs = $stmtVoyageurs->fetchAll(PDO::FETCH_ASSOC);

    // liste des formules pour attribution tarifaire
    $stmtFormules = $pdo->query("SELECT * FROM formule ORDER BY prix_base ASC");
    $formules = $stmtFormules->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur technique de chargement : " . $e->getMessage());
}
?>

<div class="reservation-container">
    
    <div class="reservation-header-box">
        <h1>Validation du sejour</h1>
        <p class="subtitle">Affectez vos proches à vos chambres d'hôtel de station de ski.</p>
    </div>

    <!-- alertes de retour -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo h($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="../actions/valider_sejour.php" class="reservation-form">
        
        <!-- periode et groupe -->
        <div class="section-box">
            <h2 class="section-title">1. Periode & Groupe</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="date_debut">Date de debut de sejour (Dimanche obligatoire)</label>
                    <input type="date" name="date_debut" id="date_debut" class="form-input" required>
                    <span class="field-help">La semaine se termine le samedi suivant à 10h.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nom_groupe">Selectionner votre groupe</label>
                    <select name="nom_groupe" id="nom_groupe" class="form-select" required>
                        <option value="" disabled selected>-- Choisir le groupe --</option>
                        <?php foreach ($groupes as $g): ?>
                            <option value="<?php echo h($g['nom_groupe']); ?>"><?php echo h($g['nom_groupe']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="action-tribu-buttons">
                        <button type="button" class="btn-action-tribu" onclick="toggleModal('modal-groupe', true)">Creer un groupe</button>
                        <button type="button" class="btn-action-tribu" onclick="toggleModal('modal-voyageur', true)">Creer un voyageur</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- hebergements, affectations et suppression -->
        <div class="section-box">
            <h2 class="section-title">2. Hebergements & Affectation de la Tribu</h2>
            <p class="description-text">Cochez chaque personne participant à ce sejour dans les chambres de votre selection.</p>

            <?php foreach ($chambres as $ch): ?>
                <div class="chambre-card">
                    <div class="chambre-header">
                        <div class="chambre-info">
                            <strong>Chambre n°<?php echo $ch['num_chambre']; ?></strong> — Bâtiment <?php echo h($ch['batiment']); ?>, etage <?php echo $ch['etage']; ?> 
                            <span class="chambre-capacity"><?php echo $ch['nb_lits']; ?> lits disponibles</span>
                        </div>
                        <a href="../actions/supprimer_panier.php?id=<?php echo $ch['num_chambre']; ?>" class="btn-delete-chambre" onclick="return confirm('Retirer cette chambre du panier ?');">
                            Supprimer la chambre
                        </a>
                    </div>
                    
                    <div class="table-scroll-wrapper">
                        <table class="table-affectation">
                            <thead>
                                <tr>
                                    <th class="col-affect">Affecter</th>
                                    <th>Nom & Prenom</th>
                                    <th>Formule choisie</th>
                                </tr>
                            </thead>
                            <tbody class="liste-voyageurs-chambre" data-room="<?php echo $ch['num_chambre']; ?>">
                                <?php if (empty($voyageurs)): ?>
                                    <tr class="no-voyageurs-row">
                                        <td colspan="3" class="text-center italic">
                                            Aucun voyageur disponible dans votre tribu. Enregistrez vos proches pour demarrer.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($voyageurs as $voy): ?>
                                        <tr>
                                            <td class="col-affect">
                                                <input type="checkbox" name="chambres[<?php echo $ch['num_chambre']; ?>][voyageurs][]" value="<?php echo $voy['id_client']; ?>">
                                            </td>
                                            <td>
                                                <span class="skier-name"><?php echo h($voy['prenom'] . ' ' . $voy['nom']); ?></span>
                                                <span class="skier-dob">(ne le <?php echo date_fr($voy['date_naissance']); ?>)</span>
                                            </td>
                                            <td>
                                                <select name="chambres[<?php echo $ch['num_chambre']; ?>][formules][<?php echo $voy['id_client']; ?>]" class="form-select select-formule-room">
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

        <!-- soumission du formulaire -->
        <div class="section-box">
            <h2 class="section-title">3. Validation & Tarification</h2>
            <div class="billing-notices">
                <p class="notice-title">Informations sur la facturation de la station :</p>
                <ul class="notice-list">
                    <li>Bebes (moins de 2 ans au depart) : gratuit (n'occupent pas de lits physiques).</li>
                    <li>Enfants (moins de 12 ans au depart) : reduction de 20% sur la formule choisie.</li>
                    <li>Amende de lit inoccupe : 150 € par lit vide par rapport à la capacite de la chambre louee.</li>
                </ul>
                <p class="notice-footer">Le montant final exact de votre sejour sera calcule et facture à la confirmation.</p>
            </div>
            
            <div class="btn-submit-container">
                <button type="submit" class="btn-submit-main">
                    Confirmer ma reservation
                </button>
            </div>
        </div>

    </form>
</div>

<!-- --------------- modale groupe --------------- -->
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
            $submit_label = "Creer le groupe";
            include __DIR__ . '/../forms/form_groupe.php'; 
            ?>
        </div>
    </div>
</div>

<!-- --------------- modale voyageur --------------- -->
<div id="modal-voyageur" class="custom-modal-overlay hidden">
    <div class="custom-modal-box modal-wide">
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

<!-- --------------- script js ajax --------------- -->
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
    // interception de l'enregistrement de groupe
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

    // interception de l'enregistrement de skieur
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
                    // injecter instantanement ce skieur dans les tables de chaque chambre
                    document.querySelectorAll(".liste-voyageurs-chambre").forEach(tbody => {
                        const roomNum = tbody.dataset.room;
                        
                        // retirer le message vide si present
                        const emptyRow = tbody.querySelector(".no-voyageurs-row");
                        if (emptyRow) {
                            emptyRow.remove();
                        }

                        // creation du markup de la ligne
                        const tr = document.createElement("tr");
                        let optionsHtml = listFormules.map(f => `
                            <option value="${f.type_formule}">${f.type_formule} (${f.prix_base} €)</option>
                        `).join('');

                        tr.innerHTML = `
                            <td class="col-affect">
                                <input type="checkbox" name="chambres[${roomNum}][voyageurs][]" value="${data.id_client}">
                            </td>
                            <td>
                                <span class="skier-name">${data.prenom} ${data.nom}</span>
                                <span class="skier-dob">(ne le ${formatDateFR(data.date_naissance)})</span>
                            </td>
                            <td>
                                <select name="chambres[${roomNum}][formules][${data.id_client}]" class="form-select select-formule-room">
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
