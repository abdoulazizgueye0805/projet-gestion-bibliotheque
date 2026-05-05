<?php
// ============================================================
// classes/Membre.php
// Représente un membre inscrit à la bibliothèque.
// Un membre peut emprunter jusqu'à 3 livres en même temps.
// ============================================================

require_once 'Livre.php'; // Membre a besoin de connaître la classe Livre

class Membre
{
    private string $nom;
    private string $prenom;

    // $livresEmpruntes est un tableau d'objets Livre
    // Il stocke tous les livres actuellement empruntés par ce membre.
    private array $livresEmpruntes = [];

    // Limite maximale de livres empruntables simultanément
    private const MAX_EMPRUNTS = 3;

    public function __construct(string $nom, string $prenom)
    {
        $this->nom = $nom;
        $this->prenom = $prenom;
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

    // Retourne le tableau de tous les livres empruntés
    public function getLivresEmpruntes(): array
    {
        return $this->livresEmpruntes;
    }

    // Retourne le nom complet du membre (prénom + nom)
    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    // --- Méthodes d'action ---
    // Emprunter un livre : vérifie les conditions avant d'agir
    public function emprunterLivre(Livre $livre): void
    {
        // Condition 1 : le membre n'a pas dépassé sa limite
        if (count($this->livresEmpruntes) >= self::MAX_EMPRUNTS) {
            throw new Exception(
                "{$this->getNomComplet()} a atteint la limite de " .
                self::MAX_EMPRUNTS . " emprunts."
            );
        }

        // Condition 2 : le livre est disponible (Livre::emprunter() lève une exception sinon)
        $livre->emprunter();
        // Si le livre est déjà emprunté, une exception est levée ici

        // Si tout va bien, on ajoute le livre au tableau des emprunts du membre
        $this->livresEmpruntes[] = $livre;

        echo "<p class='succes'>
                {$this->getNomComplet()} a emprunté <em>{$livre->getTitre()}</em>.
              </p>";
    }

    // Retourner un livre : le retire de la liste des emprunts du membre
    public function retournerLivre(Livre $livre): void
    {
        // array_filter parcourt le tableau et garde uniquement les livres
        // dont le titre est DIFFÉRENT du livre retourné.
        // On utilise array_values pour réindexer le tableau après le filtre.
        $this->livresEmpruntes = array_values(
            array_filter(
                $this->livresEmpruntes,
                fn($l) => $l->getTitre() !== $livre->getTitre()
            )
        );

        // On signale au livre qu'il est de nouveau disponible
        $livre->retourner();

        echo "<p class='succes'>↩️
                {$this->getNomComplet()} a retourné <em>{$livre->getTitre()}</em>.
              </p>";
    }
}
