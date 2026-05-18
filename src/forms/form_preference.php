<?php
/**
 * formulaire d'ajout/édition de préférence de cohabitation
 */

$is_edit = isset($pref_edit) && !empty($pref_edit);
?>
<link rel="stylesheet" href="/assets/css/form_preference.css">

<form action="<?php echo $form_action; ?>" method="POST" class="academic-form">
    
    <?php if ($is_edit): ?>
        <!-- mode édition, noms statiques d'identité et clés primaires cachées -->
        <input type="hidden" name="id_client" value="<?php echo intval($pref_edit['id_client']); ?>">
        <input type="hidden" name="id_client_1" value="<?php echo intval($pref_edit['id_client_1']); ?>">
        
        <div class="form-field">
            <label class="field-label-static">Voyageur émetteur</label>
            <input type="text" class="input-static" value="<?php echo h($pref_edit['emetteur_prenom'] . ' ' . $pref_edit['emetteur_nom']); ?>" disabled>
        </div>

        <div class="relation-arrow-indicator">&darr; (S'exprime envers)</div>

        <div class="form-field">
            <label class="field-label-static">Voyageur récepteur</label>
            <input type="text" class="input-static" value="<?php echo h($pref_edit['recepteur_prenom'] . ' ' . $pref_edit['recepteur_nom']); ?>" disabled>
        </div>
        
    <?php else: ?>
        <!-- Mode Création : Menus déroulants émetteur/récepteur -->
        <div class="form-field">
            <label for="id_client">Voyageur émetteur (Qui s'exprime ?)</label>
            <select name="id_client" id="id_client" required>
                <option value="" disabled selected>-- Choisir le skieur --</option>
                <?php foreach ($voyageurs as $v): ?>
                    <option value="<?php echo $v['id_client']; ?>"><?php echo h($v['prenom'] . ' ' . $v['nom']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="relation-arrow-indicator">&darr; (S'exprime envers)</div>

        <div class="form-field">
            <label for="id_client_1">Voyageur récepteur (Envers qui ?)</label>
            <select name="id_client_1" id="id_client_1" required>
                <option value="" disabled selected>-- Choisir l'affinité --</option>
                <?php foreach ($voyageurs as $v): ?>
                    <option value="<?php echo $v['id_client']; ?>"><?php echo h($v['prenom'] . ' ' . $v['nom']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <!-- Type de relation (niveau_preference ENUM pref_level) -->
    <div class="form-field">
        <label for="niveau_preference">Niveau d'affinité / Cohabitation</label>
        <select name="niveau_preference" id="niveau_preference" required>
            <?php
            $options = [
                'impératif' => 'Impératif (Doivent cohabiter)',
                'Souhaitable' => 'Souhaitable (Préfèrent cohabiter)',
                'Pas souhaitable'=> 'Pas souhaitable (Éviter la cohabitation)',
                'Interdit' => 'Interdit (Ne doivent pas cohabiter)'
            ];
            foreach ($options as $key => $label):
                $selected = ($is_edit && $pref_edit['niveau_preference'] === $key) ? 'selected' : '';
            ?>
                <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions">
        <?php if ($is_edit): ?>
            <a href="preferences.php" class="btn-cancel">Annuler</a>
        <?php endif; ?>
        <button type="submit" class="btn-submit">
            <?php echo $submit_label; ?>
        </button>
    </div>
</form>
