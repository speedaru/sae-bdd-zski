<?php
/**
 * Partial : Formulaire d'ajout/édition de groupe - Zarza-Ski
 * Emplacement : src/includes/forms/form_groupe.php
 * Variables attendues : 
 * - $form_action : URL cible du formulaire
 * - $submit_label : Texte du bouton
 * - $groupe : (Optionnel) Array contenant le groupe à éditer
 */
$g_name = isset($groupe) ? $groupe['nom_groupe'] : '';
?>
<form action="<?php echo $form_action; ?>" method="POST" class="needs-validation">
    <?php if (isset($groupe)): ?>
        <!-- Clé d'origine pour cibler l'ancien nom de groupe lors de l'Update -->
        <input type="hidden" name="ancien_nom_groupe" value="<?php echo h($groupe['nom_groupe']); ?>">
    <?php endif; ?>
    
    <div class="mb-3">
        <label for="nom_groupe" class="form-label fw-bold">Nom du groupe de séjour</label>
        <input type="text" 
               name="nom_groupe" 
               id="nom_groupe" 
               class="form-control" 
               placeholder="Ex: Famille Durand, Ski Club..." 
               maxlength="48" 
               value="<?php echo h($g_name); ?>"
               required>
        <div class="form-text">Maximum 48 caractères. Le nom doit être unique dans la station.</div>
    </div>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1 py-2 shadow-sm">
            <i class="fas fa-save me-2"></i><?php echo $submit_label; ?>
        </button>
        <?php if (isset($groupe)): ?>
            <a href="groupes.php" class="btn btn-outline-secondary py-2">Annuler</a>
        <?php endif; ?>
    </div>
</form>