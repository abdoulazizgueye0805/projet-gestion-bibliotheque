<?php
// ============================================================
// classes/Bibliotheque.php
// Classe principale : gère les livres et les membres.
// ============================================================

require_once 'Livre.php';
require_once 'Membre.php';

class Bibliotheque
{
    private string $nom;
    private array $livres = [];   // collection de tous les livres
    private array $membres = [];  // liste de tous les membres inscrits

    public function __construct(string $nom)
    {
        $this->nom = $nom;
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
    // Vérifier si le livre existe déjà (par titre)
    foreach ($this->livres as $existant) {
        if (strtolower($existant->getTitre()) === strtolower($livre->getTitre())) {
            echo "<p class='erreur'>❌ Le livre <em>{$livre->getTitre()}</em> existe déjà dans la bibliothèque.</p>";
            return; // on arrête la méthode, pas d'ajout
        }
    }

    // Sinon, on ajoute le livre
    $this->livres[] = $livre;
    echo "<p class='info'>📚 Livre ajouté : <em>{$livre->getTitre()}</em></p>";
}


    public function retirerLivre(string $titre): void
    {
        $livre = $this->rechercherLivre($titre);

        if (!$livre->estDisponible()) {
            throw new Exception("Impossible de retirer \"{$titre}\" : il est actuellement emprunté.");
        }

        $this->livres = array_values(
            array_filter($this->livres, fn($l) => $l->getTitre() !== $titre)
        );

        echo "<p class='info'>🗑️ Livre retiré : <em>{$titre}</em></p>";
    }

    public function rechercherLivre(string $titre): Livre
    {
        foreach ($this->livres as $livre) {
            if (strtolower($livre->getTitre()) === strtolower($titre)) {
                return $livre;
            }
        }
        throw new Exception("Livre introuvable : \"{$titre}\".");
    }

    // --------------------------------------------------------
    // GESTION DES MEMBRES
    // --------------------------------------------------------

    public function inscrireMembre(Membre $membre): void
    {
        $this->membres[] = $membre;
        echo "<p class='info'>👤 Membre inscrit : <em>{$membre->getNomComplet()}</em></p>";
    }

    // --------------------------------------------------------
    // AFFICHAGE
    // --------------------------------------------------------

    public function afficherLivres(): void
    {
        echo "<h2>📖 Catalogue — {$this->nom}</h2>";

        if (empty($this->livres)) {
            echo "<p class='vide'>Aucun livre dans la bibliothèque.</p>";
            return;
        }

        echo "<table>";
        echo "<thead><tr><th>Titre</th><th>Auteur</th><th>Statut</th></tr></thead>";
        echo "<tbody>";

        foreach ($this->livres as $livre) {
            $statut = $livre->estDisponible() ? "Disponible" : "Emprunté";
            $classe = $livre->estDisponible() ? "disponible" : "emprunte";

            echo "<tr>";
            echo "<td>{$livre->getTitre()}</td>";
            echo "<td>{$livre->getAuteur()}</td>";
            echo "<td class='{$classe}'>{$statut}</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
    }

    public function afficherMembres(): void
    {
        echo "<h2>👥 Membres inscrits</h2>";

        if (empty($this->membres)) {
            echo "<p class='vide'>Aucun membre inscrit.</p>";
            return;
        }

        foreach ($this->membres as $membre) {
            echo "<div class='membre-carte'>";
            echo "<strong>{$membre->getNomComplet()}</strong>";

            $emprunts = $membre->getLivresEmpruntes();

            if (empty($emprunts)) {
                echo "<p>Aucun emprunt en cours.</p>";
            } else {
                echo "<ul>";
                foreach ($emprunts as $livre) {
                    echo "<li>{$livre->getTitre()} — {$livre->getAuteur()}</li>";
                }
                echo "</ul>";
            }

            echo "</div>";
        }
    }

    public function afficherLivresDisponibles(): void
    {
        $disponibles = array_filter($this->livres, fn($l) => $l->estDisponible());

        echo "<h2>📖 Livres disponibles</h2>";

        if (empty($disponibles)) {
            echo "<p class='vide'>Tous les livres sont actuellement empruntés.</p>";
            return;
        }

        echo "<ul>";
        foreach ($disponibles as $livre) {
            echo "<li>{$livre->getTitre()} — {$livre->getAuteur()}</li>";
        }
        echo "</ul>";
    }
}
