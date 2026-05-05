<?php
// ============================================================
// classes/Bibliotheque.php
// Classe principale : gère les livres et les membres avec PDO.
// ============================================================

require_once 'Livre.php';
require_once 'Membre.php';

class Bibliotheque
{
    private string $nom;
    private PDO $pdo;

    public function __construct(string $nom, PDO $pdo)
    {
        $this->nom = $nom;
        $this->pdo = $pdo;

        // Créer la bibliothèque si elle n'existe pas
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO bibliotheques (nom) VALUES (?)");
        $stmt->execute([$nom]);
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    // --------------------------------------------------------
    // GESTION DES LIVRES
    // --------------------------------------------------------

    public function ajouterLivre(Livre $livre): void
    {
        // Vérifier si le livre existe déjà
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM livres WHERE LOWER(titre) = LOWER(?)");
        $stmt->execute([$livre->getTitre()]);
        if ($stmt->fetchColumn() > 0) {
            echo "<p class='erreur'>❌ Le livre <em>{$livre->getTitre()}</em> existe déjà.</p>";
            return;
        }

        // Ajouter le livre
        $stmt = $this->pdo->prepare("INSERT INTO livres (titre, auteur, disponible) VALUES (?, ?, TRUE)");
        $stmt->execute([$livre->getTitre(), $livre->getAuteur()]);

        echo "<p class='info'>📚 Livre ajouté : <em>{$livre->getTitre()}</em></p>";
    }

    public function retirerLivre(string $titre): void
    {
        $stmt = $this->pdo->prepare("SELECT disponible FROM livres WHERE titre = ?");
        $stmt->execute([$titre]);
        $dispo = $stmt->fetchColumn();

        if ($dispo === null) {
            throw new Exception("Livre introuvable : \"{$titre}\".");
        }
        if (!$dispo) {
            throw new Exception("Impossible de retirer \"{$titre}\" : il est actuellement emprunté.");
        }

        $stmt = $this->pdo->prepare("DELETE FROM livres WHERE titre = ?");
        $stmt->execute([$titre]);

        echo "<p class='info'>🗑️ Livre retiré : <em>{$titre}</em></p>";
    }

    // --------------------------------------------------------
    // GESTION DES MEMBRES
    // --------------------------------------------------------

    public function inscrireMembre(Membre $membre): void
    {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO membres (nom, prenom) VALUES (?, ?)");
        $stmt->execute([$membre->getNom(), $membre->getPrenom()]);

        echo "<p class='info'>👤 Membre inscrit : <em>{$membre->getNomComplet()}</em></p>";
    }

    // --------------------------------------------------------
    // AFFICHAGE
    // --------------------------------------------------------

    public function afficherLivres(): void
    {
        echo "<h2>📖 Catalogue — {$this->nom}</h2>";

        $stmt = $this->pdo->query("SELECT titre, auteur, disponible FROM livres");
        $livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($livres)) {
            echo "<p class='vide'>Aucun livre dans la bibliothèque.</p>";
            return;
        }

        echo "<table><thead><tr><th>Titre</th><th>Auteur</th><th>Statut</th></tr></thead><tbody>";
        foreach ($livres as $livre) {
            $statut = $livre['disponible'] ? "Disponible" : "Emprunté";
            $classe = $livre['disponible'] ? "disponible" : "emprunte";
            echo "<tr><td>{$livre['titre']}</td><td>{$livre['auteur']}</td><td class='{$classe}'>{$statut}</td></tr>";
        }
        echo "</tbody></table>";
    }

    public function afficherMembres(): void
    {
        echo "<h2>👥 Membres inscrits</h2>";

        $stmt = $this->pdo->query("SELECT nom, prenom FROM membres");
        $membres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($membres)) {
            echo "<p class='vide'>Aucun membre inscrit.</p>";
            return;
        }

        foreach ($membres as $membre) {
            echo "<div class='membre-carte'>";
            echo "<strong>{$membre['prenom']} {$membre['nom']}</strong>";

            // Récupérer les emprunts
            $stmt = $this->pdo->prepare("SELECT livre_titre FROM emprunts WHERE membre_nom=? AND membre_prenom=? AND date_retour IS NULL");
            $stmt->execute([$membre['nom'], $membre['prenom']]);
            $emprunts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($emprunts)) {
                echo "<p>Aucun emprunt en cours.</p>";
            } else {
                echo "<ul>";
                foreach ($emprunts as $livre) {
                    echo "<li>{$livre['livre_titre']}</li>";
                }
                echo "</ul>";
            }

            echo "</div>";
        }
    }

    public function afficherLivresDisponibles(): void
    {
        echo "<h2>📖 Livres disponibles</h2>";

        $stmt = $this->pdo->query("SELECT titre, auteur FROM livres WHERE disponible=TRUE");
        $disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($disponibles)) {
            echo "<p class='vide'>Tous les livres sont actuellement empruntés.</p>";
            return;
        }

        echo "<ul>";
        foreach ($disponibles as $livre) {
            echo "<li>{$livre['titre']} — {$livre['auteur']}</li>";
        }
        echo "</ul>";
    }
}
