<?php
require_once '../classes/Livre.php';
require_once '../classes/Membre.php';
require_once '../classes/Bibliotheque.php';

session_start();

// Récupérer la bibliothèque existante depuis la session
if (!isset($_SESSION['bibliotheque'])) {
    $_SESSION['bibliotheque'] = new Bibliotheque("Bibliothèque ISI");
}
$biblio = $_SESSION['bibliotheque'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $auteur = trim($_POST['auteur']);

    if ($titre && $auteur) {
        $livre = new Livre($titre, $auteur);
        if ($biblio->ajouterLivre($livre)) {
            $message = "<p class='succes'>📚 Livre ajouté : <em>" . htmlspecialchars($titre) . "</em></p>";
        } else {
            $message = "<p class='erreur'>❌ Le livre <em>" . htmlspecialchars($titre) . "</em> existe déjà.</p>";
        }
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

    <!-- Bouton retour -->
    <div style="text-align:center; margin-top:2rem;">
        <a href="http://localhost/bibliothequepoo/" class="btn-retour">⬅️ Retour à l'accueil</a>
    </div>
</body>
</html>
