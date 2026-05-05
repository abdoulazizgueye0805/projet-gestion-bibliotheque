<?php
// ============================================================
// classes/Livre.php
// Classe de base : Livre
// ============================================================

class Livre
{
    protected ?int $id;
    protected string $titre;
    protected string $auteur;
    protected bool $disponible;

    public function __construct(string $titre, string $auteur, ?int $id = null)
    {
        $this->id = $id;
        $this->titre = $titre;
        $this->auteur = $auteur;
        $this->disponible = true;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getTitre(): string { return $this->titre; }
    public function getAuteur(): string { return $this->auteur; }
    public function estDisponible(): bool { return $this->disponible; }
    public function setDisponible(bool $disponible): void { $this->disponible = $disponible; }

    public function emprunter(): void
    {
        if (!$this->disponible) {
            throw new Exception("Le livre \"{$this->titre}\" est déjà emprunté.");
        }
        $this->disponible = false;
    }

    public function retourner(): void
    {
        $this->disponible = true;
    }

    public function __toString(): string
    {
        $statut = $this->disponible ? "Disponible" : "Emprunté";
        return "\"{$this->titre}\" — {$this->auteur} [{$statut}]";
    }
}

// ============================================================
// Sous-classe LivrePhysique : hérite de Livre
// ============================================================

class LivrePhysique extends Livre
{
    // Comportement identique à Livre (un seul exemplaire)
    // On peut ajouter des attributs spécifiques (ex: localisation, état physique)
    private string $emplacement;

    public function __construct(string $titre, string $auteur, string $emplacement)
    {
        parent::__construct($titre, $auteur);
        $this->emplacement = $emplacement;
    }

    public function getEmplacement(): string
    {
        return $this->emplacement;
    }
}

// ============================================================
// Sous-classe LivreNumerique : hérite de Livre
// ============================================================

class LivreNumerique extends Livre
{
    // Un livre numérique peut être emprunté par plusieurs membres
    // donc on ignore la disponibilité unique
    public function __construct(string $titre, string $auteur)
    {
        parent::__construct($titre, $auteur);
        $this->disponible = true; // toujours considéré disponible
    }

    // Redéfinition : pas de limite d'emprunt
    public function emprunter(): void
    {
        // Pas de changement d'état : reste toujours disponible
        // On peut juste signaler l'emprunt
        echo "<p class='info'>💻 Livre numérique emprunté : <em>{$this->titre}</em> — {$this->auteur}</p>";
    }

    public function retourner(): void
    {
        // Rien à faire, car le numérique reste disponible
        echo "<p class='info'>💻 Livre numérique rendu (mais reste disponible).</p>";
    }

    public function estDisponible(): bool
    {
        return true; // toujours disponible
    }
}
