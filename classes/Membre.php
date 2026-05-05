<?php
// ============================================================
// classes/Membre.php
// Représente un membre inscrit à la bibliothèque.
// Un membre peut emprunter jusqu'à 3 livres en même temps.
// Historique des emprunts conservé avec date.
// ============================================================

require_once 'Livre.php';

class Membre
{
    private ?int $id;
    private string $nom;
    private string $prenom;

    private array $livresEmpruntes = [];   // Livres actuellement empruntés
    private array $historique = [];        // Historique de tous les emprunts

    private const MAX_EMPRUNTS = 3;

    public function __construct(string $nom, string $prenom, ?int $id = null)
    {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
    }

    // --- Getters ---
    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getNom(): string { return $this->nom; }
    public function getPrenom(): string { return $this->prenom; }
    public function getLivresEmpruntes(): array { return $this->livresEmpruntes; }
    public function getNomComplet(): string { return $this->prenom . ' ' . $this->nom; }

    // Retourne l’historique complet des emprunts
    public function getHistorique(): array
    {
        return $this->historique;
    }

    // --- Méthodes d'action ---
    public function emprunterLivre(Livre $livre): void
    {
        if (count($this->livresEmpruntes) >= self::MAX_EMPRUNTS) {
            throw new Exception("{$this->getNomComplet()} a atteint la limite de " . self::MAX_EMPRUNTS . " emprunts.");
        }

        $livre->emprunter();

        $this->livresEmpruntes[] = $livre;

        // Ajout dans l’historique avec date
        $this->historique[] = [
            'titre' => $livre->getTitre(),
            'auteur' => $livre->getAuteur(),
            'date' => date('d/m/Y')
        ];

        echo "<p class='succes'>
                {$this->getNomComplet()} a emprunté <em>{$livre->getTitre()}</em>.
              </p>";
    }

    public function retournerLivre(Livre $livre): void
    {
        $this->livresEmpruntes = array_values(
            array_filter(
                $this->livresEmpruntes,
                fn($l) => $l->getTitre() !== $livre->getTitre()
            )
        );

        $livre->retourner();

        echo "<p class='succes'>↩️
                {$this->getNomComplet()} a retourné <em>{$livre->getTitre()}</em>.
              </p>";
    }

    public function hydraterEmpruntActif(Livre $livre): void
    {
        $this->livresEmpruntes[] = $livre;
    }
}

class Reservation
{
    private Membre $membre;
    private Livre $livre;
    private string $dateReservation;
    private bool $active = true; // réservation active tant que non signalée

    public function __construct(Membre $membre, Livre $livre)
    {
        if ($livre->estDisponible()) {
            throw new Exception("Le livre \"{$livre->getTitre()}\" est disponible, inutile de le réserver.");
        }

        $this->membre = $membre;
        $this->livre = $livre;
        $this->dateReservation = date('d/m/Y');

        echo "<p class='info'>📌 Réservation créée : 
                <em>{$livre->getTitre()}</em> pour {$membre->getNomComplet()} 
                le {$this->dateReservation}.
              </p>";
    }

    public function getMembre(): Membre
    {
        return $this->membre;
    }

    public function getLivre(): Livre
    {
        return $this->livre;
    }

    public function estActive(): bool
    {
        return $this->active;
    }

    // Méthode appelée quand le livre est retourné
    public function signalerRetour(): void
    {
        if ($this->active) {
            $this->active = false;
            echo "<p class='succes'>✅ 
                    Réservation signalée : <em>{$this->livre->getTitre()}</em> 
                    est maintenant disponible pour {$this->membre->getNomComplet()}.
                  </p>";
        }
    }
}