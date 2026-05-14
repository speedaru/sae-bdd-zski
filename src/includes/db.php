<?php
$host = "localhost";
$port = "5432";
$db   = "sae_ski_db";
$user = "postgres"; // Votre utilisateur Postgres
$pass = "zarza2026";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;user=$user;password=$pass";
    $pdo = new PDO($dsn);
    // activer les erreurs pour debug
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
