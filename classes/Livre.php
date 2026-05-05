<?php
// ============================================================
// classes/Livre.php
// Représente un livre dans la bibliothèque.
// Chaque livre a un titre, un auteur, et un état de disponibilité.
// ============================================================

class Livre
{
    // Attributs privés : on ne peut pas les modifier directement
    // depuis l'extérieur de la classe. On passe obligatoirement
    // par les méthodes définies ci-dessous.
    private string $titre;
    private string $auteur;
    private bool $disponible; // true = disponible, false = emprunté

    // Le constructeur est appelé automatiquement lors du new Livre(...)
    // Il initialise les attributs dès la création de l'objet.
    public function __construct(string $titre, string $auteur)
    {
        $this->titre = $titre;
        $this->auteur = $auteur;
        $this->disponible = true; // un livre est disponible par défaut à l'ajout
    }

    // --- Getters : permettent de LIRE les attributs privés ---
    // Retourne le titre du livre
    public function getTitre(): string
    {
        return $this->titre;
    }

    // Retourne le nom de l'auteur
    public function getAuteur(): string
    {
        return $this->auteur;
    }

    // Retourne true si le livre est disponible, false sinon
    public function estDisponible(): bool
    {
        return $this->disponible;
    }

    // --- Méthodes d'action : modifient l'état du livre ---
    // Marque le livre comme emprunté (disponible = false)
    // Lève une exception si le livre est déjà emprunté
    public function emprunter(): void
    {
        if (!$this->disponible) {
            // throw crée une exception : si personne ne la "attrape",
            // le programme s'arrête avec un message d'erreur.
            throw new Exception("Le livre \"{$this->titre}\" est déjà emprunté.");
        }
        $this->disponible = false;
    }

    // Marque le livre comme retourné (disponible = true)
    public function retourner(): void
    {
        $this->disponible = true;
    }

    // Retourne une représentation textuelle du livre
    // Utile pour l'affichage dans les listes
    public function __toString(): string
    {
        $statut = $this->disponible ? "Disponible" : "Emprunté";
        return "\"{$this->titre}\" — {$this->auteur} [{$statut}]";
    }

    
}
