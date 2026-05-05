<?php
// ============================================================
// classes/Membre.php
// Représente un membre inscrit à la bibliothèque avec PDO.
// ============================================================

require_once 'Livre.php';

class Membre
{
    private string $nom;
    private string $prenom;
    private PDO $pdo;

    // Limite maximale de livres empruntables simultanément
    private const MAX_EMPRUNTS = 3;

    public function __construct(string $nom, string $prenom, PDO $pdo)
    {
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->pdo = $pdo;

        // Inscrire le membre en BD s'il n'existe pas déjà
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO membres (nom, prenom) VALUES (?, ?)");
        $stmt->execute([$nom, $prenom]);
    }

    // --- Getters ---
    public function getNom(): string
    {
        return $this->nom;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    // Retourne les livres empruntés par ce membre (depuis la BD)
    public function getLivresEmpruntes(): array
    {
        $stmt = $this->pdo->prepare("SELECT livre_titre FROM emprunts WHERE membre_nom=? AND membre_prenom=? AND date_retour IS NULL");
        $stmt->execute([$this->nom, $this->prenom]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Méthodes d'action ---
    public function emprunterLivre(Livre $livre): void
    {
        // Condition 1 : vérifier la limite
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM emprunts WHERE membre_nom=? AND membre_prenom=? AND date_retour IS NULL");
        $stmt->execute([$this->nom, $this->prenom]);
        $nbEmprunts = $stmt->fetchColumn();

        if ($nbEmprunts >= self::MAX_EMPRUNTS) {
            throw new Exception("{$this->getNomComplet()} a atteint la limite de " . self::MAX_EMPRUNTS . " emprunts.");
        }

        // Condition 2 : vérifier disponibilité du livre
        if (!$livre->estDisponible()) {
            throw new Exception("Le livre \"{$livre->getTitre()}\" est déjà emprunté.");
        }

        // Enregistrer l’emprunt
        $stmt = $this->pdo->prepare("INSERT INTO emprunts (membre_nom, membre_prenom, livre_titre, date_emprunt) VALUES (?, ?, ?, CURRENT_DATE)");
        $stmt->execute([$this->nom, $this->prenom, $livre->getTitre()]);

        // Mettre à jour l’état du livre
        $livre->emprunter();

        echo "<p class='succes'>{$this->getNomComplet()} a emprunté <em>{$livre->getTitre()}</em>.</p>";
    }

    public function retournerLivre(Livre $livre): void
    {
        // Mettre à jour l’emprunt en BD
        $stmt = $this->pdo->prepare("UPDATE emprunts SET date_retour=CURRENT_DATE WHERE membre_nom=? AND membre_prenom=? AND livre_titre=? AND date_retour IS NULL");
        $stmt->execute([$this->nom, $this->prenom, $livre->getTitre()]);

        // Mettre à jour l’état du livre
        $livre->retourner();

        echo "<p class='succes'>↩️ {$this->getNomComplet()} a retourné <em>{$livre->getTitre()}</em>.</p>";
    }
}
