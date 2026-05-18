<?php
/**
 * formulaire d'ajout/édition de groupe
 */
$g_name = isset($groupe) ? $groupe['nom_groupe'] : '';
?>
<link rel="stylesheet" href="/assets/css/form_groupe.css">

<form action="<?php echo $form_action; ?>" method="POST" class="academic-form">
    <?php if (isset($groupe)): ?>
        <input type="hidden" name="ancien_nom_groupe" value="<?php echo h($groupe['nom_groupe']); ?>">
    <?php endif; ?>
    
    <div class="form-field">
        <label for="nom_groupe">Nom du groupe de séjour</label>
        <input type="text" 
               name="nom_groupe" 
               id="nom_groupe" 
               placeholder="Ex: Famille Durand, Ski Club..." 
               maxlength="48" 
               value="<?php echo h($g_name); ?>"
               required>
        <span class="field-help">Maximum 48 caractères. Le nom doit être unique au sein de la station.</span>
    </div>
    
    <div class="form-actions">
        <?php if (isset($groupe)): ?>
            <a href="groupes.php" class="btn-cancel">Annuler</a>
        <?php endif; ?>
        <button type="submit" class="btn-submit">
            <?php echo $submit_label; ?>
        </button>
    </div>
</form>
