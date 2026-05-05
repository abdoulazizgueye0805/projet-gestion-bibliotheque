<?php
require_once '../classes/Livre.php';
require_once '../classes/Membre.php';
require_once '../classes/Bibliotheque.php';

// Connexion à la base
$host = "localhost";
$dbname = "bibliothequepoo";
$username = "root";   // adapte selon ton environnement
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Erreur de connexion : " . $e->getMessage());
}

// Créer la bibliothèque avec PDO
$biblio = new Bibliotheque("Bibliothèque ISI", $pdo);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $auteur = trim($_POST['auteur']);

    if ($titre && $auteur) {
        $livre = new Livre($titre, $auteur, $pdo);
        $biblio->ajouterLivre($livre);
        $message = "<p class='succes'>📚 Livre ajouté : <em>" . htmlspecialchars($titre) . "</em></p>";
    } else {
        $message = "<p class='erreur'>Veuillez remplir tous les champs.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajout d’un livre</title>
    <link rel='stylesheet' href='../assets/css/style.css'>
</head>
<body>
    <h1>Ajouter un nouveau livre</h1>

    <!-- Formulaire -->
    <form method="post" action="">
        <label for="titre">Titre :</label>
        <input type="text" id="titre" name="titre" required><br><br>

        <label for="auteur">Auteur :</label>
        <input type="text" id="auteur" name="auteur" required><br><br>

        <button type="submit">Ajouter</button>
    </form>

    <!-- Message -->
    <?php if (isset($message)) echo $message; ?>

    <!-- Liste des livres -->
    <h2>📖 Catalogue actuel</h2>
    <?php $biblio->afficherLivres(); ?>

    <!-- Bouton retour -->
    <div style="text-align:center; margin-top:2rem;">
        <a href="http://localhost/bibliothequepoo/" class="btn-retour">⬅️ Retour à l'accueil</a>
    </div>
</body>
</html>
