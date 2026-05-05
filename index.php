<?php
// ============================================================
// index.php
// Point d'entrée de l'application.
// Ce fichier utilise les trois classes pour simuler
// le fonctionnement d'une bibliothèque.
// ============================================================

require_once 'classes/Bibliotheque.php'; 
// Bibliotheque charge Livre et Membre automatiquement

// --- 1. Création de la bibliothèque ---
$biblio = new Bibliotheque("Bibliothèque Centrale ISI");

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Bibliothèque ISI</title>
    <link rel='stylesheet' href='assets/css/style.css'>
</head>
<body>
<header>
    <h1>📚 {$biblio->getNom()}</h1>
</header>
<main>";

// --- 2. Ajout des livres ---
echo "<section><h2><a href=\"classes/ajouter_livre.php\" style=\"text-decoration:none; color:inherit;\">➕ Ajout des livres</a></h2></section>";

$l1 = new Livre("Le Petit Prince", "Antoine de Saint-Exupéry");
$l2 = new Livre("1984", "George Orwell");
$l3 = new Livre("Les Misérables", "Victor Hugo");
$l4 = new Livre("L'Étranger", "Albert Camus");
$l5 = new Livre("Germinal", "Émile Zola");

$biblio->ajouterLivre($l1);
$biblio->ajouterLivre($l2);
$biblio->ajouterLivre($l3);
$biblio->ajouterLivre($l4);
$biblio->ajouterLivre($l5);

echo "</section>";

// --- 3. Inscription des membres ---
echo "<section><h2>👤 Inscription des membres</h2>";

$m1 = new Membre("Ndiaye", "Amadou");
$m2 = new Membre("Sow", "Mariama");
$m3 = new Membre("Sarr", "Ibrahima");

$biblio->inscrireMembre($m1);
$biblio->inscrireMembre($m2);
$biblio->inscrireMembre($m3);

echo "</section>";

// --- 4. Emprunts ---
echo "<section><h2>📤 Emprunts</h2>";
try {
    $m1->emprunterLivre($l1); // Amadou emprunte Le Petit Prince
    $m1->emprunterLivre($l2); // Amadou emprunte 1984
    $m2->emprunterLivre($l3); // Mariama emprunte Les Misérables
    $m2->emprunterLivre($l1); // ❌ déjà emprunté → exception attendue
} catch (Exception $e) {
    echo "<p class='erreur'>⚠️ " . $e->getMessage() . "</p>";
}
echo "</section>";

// --- 5. Catalogue après emprunts ---
echo "<section>";
$biblio->afficherLivres();
echo "</section>";

// --- 6. Livres disponibles ---
echo "<section>";
$biblio->afficherLivresDisponibles();
echo "</section>";

// --- 7. Retour d'un livre ---
echo "<section><h2>📥 Retours</h2>";
try {
    $m1->retournerLivre($l1); // Amadou retourne Le Petit Prince
} catch (Exception $e) {
    echo "<p class='erreur'>⚠️ " . $e->getMessage() . "</p>";
}
echo "</section>";

// --- 8. Retrait du catalogue ---
echo "<section><h2>🗑️ Retrait du catalogue</h2>";
try {
    $biblio->retirerLivre("Germinal"); // disponible → succès attendu
    $biblio->retirerLivre("1984");     // emprunté → exception attendue
} catch (Exception $e) {
    echo "<p class='erreur'>⚠️ " . $e->getMessage() . "</p>";
}
echo "</section>";

// --- 9. État final ---
echo "<section>";
$biblio->afficherLivres();
echo "</section>";

// --- 10. Récapitulatif des membres ---
echo "<section>";
$biblio->afficherMembres();
echo "</section>";

echo "</main>
<footer>
    <p>Atelier POO PHP — Licence 2 — Institut Supérieur d'Informatique</p>
</footer>
</body>
</html>";
