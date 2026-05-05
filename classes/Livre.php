<?php
// ============================================================
// classes/Livre.php
// Représente un livre dans la bibliothèque avec PDO.
// ============================================================

class Livre
{
    private string $titre;
    private string $auteur;
    private bool $disponible;
    private PDO $pdo;

    // Le constructeur initialise les attributs et reçoit la connexion PDO
    public function __construct(string $titre, string $auteur, PDO $pdo)
    {
        $this->titre = $titre;
        $this->auteur = $auteur;
        $this->pdo = $pdo;

        // Vérifier si le livre existe déjà en BD
        $stmt = $this->pdo->prepare("SELECT disponible FROM livres WHERE titre = ?");
        $stmt->execute([$titre]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Si le livre existe déjà, on récupère son état
            $this->disponible = (bool)$result['disponible'];
        } else {
            // Sinon, on l’insère comme disponible par défaut
            $this->disponible = true;
            $stmt = $this->pdo->prepare("INSERT INTO livres (titre, auteur, disponible) VALUES (?, ?, TRUE)");
            $stmt->execute([$titre, $auteur]);
        }
    }

    // --- Getters ---
    public function getTitre(): string
    {
        return $this->titre;
    }

    public function getAuteur(): string
    {
        return $this->auteur;
    }

    public function estDisponible(): bool
    {
        return $this->disponible;
    }

    // --- Méthodes d’action ---
    public function emprunter(): void
    {
        if (!$this->disponible) {
            throw new Exception("Le livre \"{$this->titre}\" est déjà emprunté.");
        }

        // Mise à jour en BD
        $stmt = $this->pdo->prepare("UPDATE livres SET disponible = FALSE WHERE titre = ?");
        $stmt->execute([$this->titre]);

        $this->disponible = false;
    }

    public function retourner(): void
    {
        // Mise à jour en BD
        $stmt = $this->pdo->prepare("UPDATE livres SET disponible = TRUE WHERE titre = ?");
        $stmt->execute([$this->titre]);

        $this->disponible = true;
    }

    public function __toString(): string
    {
        $statut = $this->disponible ? "Disponible" : "Emprunté";
        return "\"{$this->titre}\" — {$this->auteur} [{$statut}]";
    }
}
