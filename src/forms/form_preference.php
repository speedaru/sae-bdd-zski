<?php
/**
 * Partial : Formulaire d'ajout/édition de préférence de cohabitation - Zarza-Ski
 * Emplacement : src/includes/forms/form_preference.php
 * Variables attendues :
 * - $form_action : URL cible de soumission
 * - $submit_label : Libellé du bouton de soumission
 * - $voyageurs : Liste de tous les skieurs du carnet de l'utilisateur (requis en création)
 * - $pref_edit : (Optionnel) Données de la préférence en cours d'édition
 */

$is_edit = isset($pref_edit) && !empty($pref_edit);
?>
<form action="<?php echo $form_action; ?>" method="POST" class="needs-validation">
    
    <?php if ($is_edit): ?>
        <!-- Mode Édition : Noms statiques et clés primaires cachées -->
        <input type="hidden" name="id_client" value="<?php echo intval($pref_edit['id_client']); ?>">
        <input type="hidden" name="id_client_1" value="<?php echo intval($pref_edit['id_client_1']); ?>">
        
        <div class="mb-3">
            <label class="form-label fw-bold text-muted">Voyageur émetteur</label>
            <input type="text" class="form-control bg-light" value="<?php echo h($pref_edit['emetteur_prenom'] . ' ' . $pref_edit['emetteur_nom']); ?>" disabled>
        </div>

        <div class="mb-3 text-center">
            <span class="badge bg-light text-primary border p-2"><i class="fas fa-long-arrow-alt-down fa-lg"></i></span>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold text-muted">Voyageur récepteur</label>
            <input type="text" class="form-control bg-light" value="<?php echo h($pref_edit['recepteur_prenom'] . ' ' . $pref_edit['recepteur_nom']); ?>" disabled>
        </div>
        
    <?php else: ?>
        <!-- Mode Création : Menus déroulants émetteur/récepteur -->
        <div class="mb-3">
            <label for="id_client" class="form-label fw-bold">Voyageur émetteur (Qui s'exprime ?)</label>
            <select name="id_client" id="id_client" class="form-select" required>
                <option value="" disabled selected>-- Choisir le skieur --</option>
                <?php foreach ($voyageurs as $v): ?>
                    <option value="<?php echo $v['id_client']; ?>"><?php echo h($v['prenom'] . ' ' . $v['nom']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3 text-center">
            <span class="badge bg-light text-secondary border p-2"><i class="fas fa-long-arrow-alt-down fa-lg"></i></span>
        </div>

        <div class="mb-3">
            <label for="id_client_1" class="form-label fw-bold">Voyageur récepteur (Envers qui ?)</label>
            <select name="id_client_1" id="id_client_1" class="form-select" required>
                <option value="" disabled selected>-- Choisir l'affinité --</option>
                <?php foreach ($voyageurs as $v): ?>
                    <option value="<?php echo $v['id_client']; ?>"><?php echo h($v['prenom'] . ' ' . $v['nom']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <!-- Type de relation (niveau_preference ENUM pref_level) -->
    <div class="mb-4">
        <label for="niveau_preference" class="form-label fw-bold">Niveau d'affinité / Cohabitation</label>
        <select name="niveau_preference" id="niveau_preference" class="form-select" required>
            <?php
            $options = [
                'impératif'      => 'Impératif (Doivent cohabiter)',
                'Souhaitable'    => 'Souhaitable (Préfèrent cohabiter)',
                'Pas souhaitable'=> 'Pas souhaitable (Éviter la cohabitation)',
                'Interdit'       => 'Interdit (Ne doivent pas cohabiter)'
            ];
            foreach ($options as $key => $label):
                $selected = ($is_edit && $pref_edit['niveau_preference'] === $key) ? 'selected' : '';
            ?>
                <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1 py-2 shadow-sm">
            <i class="fas fa-save me-2"></i><?php echo $submit_label; ?>
        </button>
        <?php if ($is_edit): ?>
            <a href="preferences.php" class="btn btn-outline-secondary py-2">Annuler</a>
        <?php endif; ?>
    </div>
</form>