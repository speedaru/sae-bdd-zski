<?php
/**
 * Partial : Formulaire d'ajout/édition d'un voyageur
 * Variables attendues : 
 * - $form_action : URL cible du formulaire
 * - $submit_label : Texte du bouton (ex: "Ajouter au carnet")
 * - $voyageur : (Optionnel) Array contenant les données pré-remplies
 */

$v = $voyageur ?? [
    'nom' => '', 'prenom' => '', 'adresse' => '', 'num_tel' => '',
    'niveau_ski' => 'débutant', 'taille' => '', 'poids' => '', 'pointure' => ''
];
?>

<form action="<?php echo $form_action; ?>" method="POST" class="row g-3">
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
        <input type="text" name="adresse" class="form-control" value="<?php echo h($v['adresse']); ?>" required>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Téléphone</label>
        <input type="text" name="num_tel" class="form-control" placeholder="06XXXXXXXX" value="<?php echo h($v['num_tel']); ?>" required>
    </div>

    <div class="col-md-6">
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
        <input type="number" step="0.01" name="taille" class="form-control" value="<?php echo (float)$v['taille']; ?>" required>
    </div>
    
    <div class="col-md-4">
        <label class="form-label fw-bold">Poids (kg)</label>
        <input type="number" name="poids" class="form-control" value="<?php echo (int)$v['poids']; ?>" required>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-bold">Pointure</label>
        <input type="number" step="0.5" name="pointure" class="form-control" value="<?php echo (float)$v['pointure']; ?>" required>
    </div>

    <div class="col-12 text-end mt-4">
        <button type="submit" class="btn btn-success px-4">
            <i class="fas fa-save me-2"></i><?php echo $submit_label; ?>
        </button>
    </div>
</form>