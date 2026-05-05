<?php
// ============================================================
// index.php
// Point d'entrée de l'application avec connexion MySQL.
// ============================================================

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

// --- 1. Création de la bibliothèque ---
$nomBibliotheque = "Bibliothèque Centrale ISI";
$pdo->exec("INSERT IGNORE INTO bibliotheques (nom) VALUES ('$nomBibliotheque')");

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Bibliothèque ISI</title>
    <link rel='stylesheet' href='assets/css/style.css'>
</head>
<body>
<header>
    <h1>📚 $nomBibliotheque</h1>
</header>
<main>";

// --- 2. Ajout des livres ---
echo "<section><h2><a href=\"classes/ajouter_livre.php\" style=\"text-decoration:none; color:inherit;\">➕ Ajout des livres</a></h2></section>";

$livres = [
    ["Le Petit Prince", "Antoine de Saint-Exupéry"],
    ["1984", "George Orwell"],
    ["Les Misérables", "Victor Hugo"],
    ["L'Étranger", "Albert Camus"],
    ["Germinal", "Émile Zola"]
];

foreach ($livres as [$titre, $auteur]) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO livres (titre, auteur, disponible) VALUES (?, ?, TRUE)");
    $stmt->execute([$titre, $auteur]);
}

echo "</section>";

// --- 3. Inscription des membres ---
echo "<section><h2>👤 Inscription des membres</h2>";

$membres = [
    ["Ndiaye", "Amadou"],
    ["Sow", "Mariama"],
    ["Sarr", "Ibrahima"]
];

foreach ($membres as [$nom, $prenom]) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO membres (nom, prenom) VALUES (?, ?)");
    $stmt->execute([$nom, $prenom]);
}

echo "</section>";

// --- 4. Emprunts ---
echo "<section><h2>📤 Emprunts</h2>";

try {
    // Amadou emprunte Le Petit Prince
    $pdo->exec("INSERT INTO emprunts (membre_nom, membre_prenom, livre_titre) VALUES ('Ndiaye','Amadou','Le Petit Prince')");
    $pdo->exec("UPDATE livres SET disponible = FALSE WHERE titre='Le Petit Prince'");

    // Amadou emprunte 1984
    $pdo->exec("INSERT INTO emprunts (membre_nom, membre_prenom, livre_titre) VALUES ('Ndiaye','Amadou','1984')");
    $pdo->exec("UPDATE livres SET disponible = FALSE WHERE titre='1984'");

    // Mariama emprunte Les Misérables
    $pdo->exec("INSERT INTO emprunts (membre_nom, membre_prenom, livre_titre) VALUES ('Sow','Mariama','Les Misérables')");
    $pdo->exec("UPDATE livres SET disponible = FALSE WHERE titre='Les Misérables'");

    // Mariama essaie d’emprunter Le Petit Prince (déjà pris → erreur attendue)
    $dispo = $pdo->query("SELECT disponible FROM livres WHERE titre='Le Petit Prince'")->fetchColumn();
    if (!$dispo) {
        throw new Exception("Le Petit Prince est déjà emprunté.");
    }
} catch (Exception $e) {
    echo "<p class='erreur'>⚠️ " . $e->getMessage() . "</p>";
}
echo "</section>";

// --- 5. Catalogue après emprunts ---
echo "<section><h2>📖 Catalogue</h2>";
$stmt = $pdo->query("SELECT titre, auteur, disponible FROM livres");
echo "<table><tr><th>Titre</th><th>Auteur</th><th>Statut</th></tr>";
foreach ($stmt as $livre) {
    $statut = $livre['disponible'] ? "Disponible" : "Emprunté";
    echo "<tr><td>{$livre['titre']}</td><td>{$livre['auteur']}</td><td>{$statut}</td></tr>";
}
echo "</table></section>";

// --- 6. Livres disponibles ---
echo "<section><h2>📖 Livres disponibles</h2>";
$stmt = $pdo->query("SELECT titre, auteur FROM livres WHERE disponible=TRUE");
foreach ($stmt as $livre) {
    echo "<p>{$livre['titre']} — {$livre['auteur']}</p>";
}
echo "</section>";

// --- 7. Retour d'un livre ---
echo "<section><h2>📥 Retours</h2>";
$pdo->exec("UPDATE emprunts SET date_retour=CURRENT_DATE WHERE membre_nom='Ndiaye' AND membre_prenom='Amadou' AND livre_titre='Le Petit Prince' AND date_retour IS NULL");
$pdo->exec("UPDATE livres SET disponible=TRUE WHERE titre='Le Petit Prince'");
echo "<p class='succes'>Amadou a retourné Le Petit Prince</p>";
echo "</section>";

// --- 8. Retrait du catalogue ---
echo "<section><h2>🗑️ Retrait du catalogue</h2>";
$pdo->exec("DELETE FROM livres WHERE titre='Germinal' AND disponible=TRUE");
$dispo = $pdo->query("SELECT disponible FROM livres WHERE titre='1984'")->fetchColumn();
if (!$dispo) {
    echo "<p class='erreur'>⚠️ Impossible de retirer 1984 : il est emprunté.</p>";
}
echo "</section>";

// --- 9. État final ---
echo "<section><h2>📖 État final du catalogue</h2>";
$stmt = $pdo->query("SELECT titre, auteur, disponible FROM livres");
foreach ($stmt as $livre) {
    $statut = $livre['disponible'] ? "Disponible" : "Emprunté";
    echo "<p>{$livre['titre']} — {$livre['auteur']} ({$statut})</p>";
}
echo "</section>";

// --- 10. Récapitulatif des membres ---
echo "<section><h2>👥 Membres inscrits</h2>";
$stmt = $pdo->query("SELECT m.prenom, m.nom, e.livre_titre, e.date_emprunt, e.date_retour
                     FROM membres m
                     LEFT JOIN emprunts e ON m.nom=e.membre_nom AND m.prenom=e.membre_prenom");
foreach ($stmt as $row) {
    echo "<p>{$row['prenom']} {$row['nom']} — Livre : {$row['livre_titre']} (Emprunté le {$row['date_emprunt']}, Retour : {$row['date_retour']})</p>";
}
echo "</section>";

echo "</main>
<footer>
    <p>Atelier POO PHP — Licence 2 — Institut Supérieur d'Informatique</p>
</footer>
</body>
</html>";
