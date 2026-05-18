<?php
// inclusion du header
include_once 'includes/header.php';

// 1. On récupère les éventuels messages d'erreur ou de succès mis en session
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
$redirect_target = $_SESSION['redirect_target'] ?? null;

// 2. IMPORTANT : On les supprime tout de suite de la session pour le prochain refresh
unset($_SESSION['error'], $_SESSION['success']);
?>

<div class="main-container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">

    <?php if ($error): ?>
        <div class="alert-error" style="background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-family: sans-serif;">
            <strong><?php echo h($error); ?></strong>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-family: sans-serif;">
            <strong>✅ <?php echo h($success); ?></strong>
        </div>
    <?php endif; ?>
    
    <?php if ($redirect_target): ?>
        <div class="alert-success" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-family: sans-serif;">
            <strong><?php echo h($redirect_target); ?></strong>
        </div>
    <?php endif; ?>

    <h1>Bienvenue à la station Zarza-Ski !</h1>
    <p>Découvrez nos séjours low-cost à la montagne</p>

</div>

<?php 
// inclusion du footer
include_once 'includes/footer.php'; 
?>
