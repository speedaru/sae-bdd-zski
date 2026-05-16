<?php
/**
 * Partial : Formulaire d'ajout/édition d'un voyageur - Zarza-Ski
 * Emplacement : src/includes/forms/form_voyageur.php
 * Variables attendues : 
 * - $form_action : URL cible du formulaire
 * - $submit_label : Texte du bouton
 * - $voyageur : (Optionnel) Array contenant les données pré-remplies
 */

$v = $voyageur ?? [
    'nom' => '', 'prenom' => '', 'date_naissance' => '', 'adresse' => '', 'num_tel' => '',
    'niveau_ski' => 'débutant', 'taille' => '', 'poids' => '', 'pointure' => ''
];
?>
<form action="<?php echo $form_action; ?>" method="POST" class="row g-3">
    <?php if (isset($v['id_client']) && $v['id_client'] > 0): ?>
        <!-- Identifiant pour la clause WHERE du UPDATE -->
        <input type="hidden" name="id_client" value="<?php echo h($v['id_client']); ?>">
    <?php endif; ?>

    <div class="col-md-6">
        <label class="form-label fw-bold">Nom</label>
        <input type="text" name="nom" class="form-control" value="<?php echo h($v['nom']); ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold">Prénom</label>
        <input type="text" name="prenom" class="form-control" value="<?php echo h($v['prenom']); ?>" required>
    </div>

    <div class="col-12">
        <label class="form-label fw-bold">Adresse</label>
        <?php 
            $addr_val = $v['adresse'];
            $display_addr = ($addr_val === 'À renseigner') ? '' : $addr_val;
        ?>
        <input type="text" name="adresse" class="form-control" placeholder="Ex: 12 Rue de la Montagne, 75000 Paris" value="<?php echo h($display_addr); ?>" required>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Téléphone</label>
        <input type="text" name="num_tel" class="form-control" placeholder="06XXXXXXXX" value="<?php echo ($v['num_tel'] === '0000000000') ? '' : h($v['num_tel']); ?>" required>
    </div>

    <!-- NOUVEAU : Date de naissance -->
    <div class="col-md-6">
        <label class="form-label fw-bold">Date de naissance</label>
        <input type="date" name="date_naissance" class="form-control" value="<?php echo h($v['date_naissance']); ?>" required>
    </div>

    <div class="col-md-12">
        <label class="form-label fw-bold">Niveau de ski</label>
        <select name="niveau_ski" class="form-select">
            <?php 
            $niveaux = ['débutant', 'moyen', 'confirmé'];
            foreach($niveaux as $n): ?>
                <option value="<?php echo $n; ?>" <?php echo ($v['niveau_ski'] == $n) ? 'selected' : ''; ?>>
                    <?php echo ucfirst($n); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-bold">Taille (m)</label>
        <input type="number" step="0.01" name="taille" class="form-control" value="<?php echo (float)$v['taille'] ?: ''; ?>" required>
    </div>
    
    <div class="col-md-4">
        <label class="form-label fw-bold">Poids (kg)</label>
        <input type="number" name="poids" class="form-control" value="<?php echo (int)$v['poids'] ?: ''; ?>" required>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-bold">Pointure</label>
        <input type="number" step="0.5" name="pointure" class="form-control" value="<?php echo (float)$v['pointure'] ?: ''; ?>" required>
    </div>

    <div class="col-12 text-end mt-4 d-flex justify-content-end gap-2">
        <?php if (isset($v['id_client']) && $v['id_client'] > 0): ?>
            <a href="carnet.php" class="btn btn-outline-secondary px-4">Annuler</a>
        <?php endif; ?>
        <button type="submit" class="btn btn-success px-4">
            <i class="fas fa-save me-2"></i><?php echo $submit_label; ?>
        </button>
    </div>
</form>