<?php
/**
 * formulaire d'ajout/édition d'un voyageur
 * s'adapte aux contextes d'ajout et de modification.
 */

$v = $voyageur ?? [
    'nom' => '', 'prenom' => '', 'date_naissance' => '', 'adresse' => '', 'num_tel' => '',
    'niveau_ski' => 'débutant', 'taille' => '', 'poids' => '', 'pointure' => ''
];
?>
<link rel="stylesheet" href="/assets/css/form_voyageur.css">

<form action="<?php echo $form_action; ?>" method="POST" class="academic-form">
    
    <?php if (isset($v['id_client']) && $v['id_client'] > 0): ?>
        <input type="hidden" name="id_client" value="<?php echo h($v['id_client']); ?>">
    <?php endif; ?>

    <!-- nom, prénom -->
    <div class="form-row split-2">
        <div class="form-field">
            <label for="f_nom">Nom</label>
            <input type="text" name="nom" id="f_nom" value="<?php echo h($v['nom']); ?>" required>
        </div>
        <div class="form-field">
            <label for="f_prenom">Prénom</label>
            <input type="text" name="prenom" id="f_prenom" value="<?php echo h($v['prenom']); ?>" required>
        </div>
    </div>

    <!-- adresse contact -->
    <div class="form-field">
        <label for="f_adresse">Adresse</label>
        <?php 
            $addr_val = $v['adresse'];
            $display_addr = ($addr_val === 'À renseigner') ? '' : $addr_val;
        ?>
        <input type="text" name="adresse" id="f_adresse" placeholder="Ex: 12 Rue de la Montagne, 75000 Paris" value="<?php echo h($display_addr); ?>" required>
    </div>

    <!-- num tel et date naissance -->
    <div class="form-row split-2">
        <div class="form-field">
            <label for="f_num_tel">Téléphone</label>
            <input type="text" name="num_tel" id="f_num_tel" placeholder="06XXXXXXXX" value="<?php echo ($v['num_tel'] === '0000000000') ? '' : h($v['num_tel']); ?>" required>
        </div>
        <div class="form-field">
            <label for="f_date_naissance">Date de naissance</label>
            <input type="date" name="date_naissance" id="f_date_naissance" value="<?php echo h($v['date_naissance']); ?>" required>
        </div>
    </div>

    <!-- niveau ski -->
    <div class="form-field">
        <label for="f_niveau_ski">Niveau de ski</label>
        <select name="niveau_ski" id="f_niveau_ski">
            <?php 
            $niveaux = ['débutant', 'moyen', 'confirmé'];
            foreach($niveaux as $n): ?>
                <option value="<?php echo $n; ?>" <?php echo ($v['niveau_ski'] == $n) ? 'selected' : ''; ?>>
                    <?php echo ucfirst($n); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- taille, poids, pointure -->
    <div class="form-row split-3">
        <div class="form-field">
            <label for="f_taille">Taille (m)</label>
            <input type="number" step="0.01" name="taille" id="f_taille" placeholder="Ex: 1.75" value="<?php echo (float)$v['taille'] ?: ''; ?>" required>
        </div>
        <div class="form-field">
            <label for="f_poids">Poids (kg)</label>
            <input type="number" name="poids" id="f_poids" placeholder="Ex: 70" value="<?php echo (int)$v['poids'] ?: ''; ?>" required>
        </div>
        <div class="form-field">
            <label for="f_pointure">Pointure (EU)</label>
            <input type="number" step="0.5" name="pointure" id="f_pointure" placeholder="Ex: 42" value="<?php echo (float)$v['pointure'] ?: ''; ?>" required>
        </div>
    </div>

    <!-- actions annuler / soumettre -->
    <div class="form-actions">
        <?php if (isset($cancel_label)) {
            echo "<a href=\"carnet.php\" class=\"btn-cancel\">" . $cancel_label . "</a>";
        }?>
        <button type="submit" class="btn-submit">
            <?php echo $submit_label; ?>
        </button>
    </div>
</form>
