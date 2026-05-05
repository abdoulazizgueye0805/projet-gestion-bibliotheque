<?php
require_once 'Livre.php';
require_once 'Membre.php';

class Bibliotheque
{
    private string $nom;
    private PDO $pdo;
    private array $livres = [];   // [titre => Livre]
    private array $membres = [];  // [prenom|nom => Membre]

    public function __construct(string $nom, PDO $pdo)
    {
        $this->nom = $nom;
        $this->pdo = $pdo;
        $this->chargerDepuisBase();
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function ajouterLivre(Livre $livre): void
    {
        $titreKey = mb_strtolower($livre->getTitre());
        if (isset($this->livres[$titreKey])) {
            throw new Exception("Le livre \"{$livre->getTitre()}\" existe déjà dans la bibliothèque.");
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO livres (titre, auteur, disponible) VALUES (:titre, :auteur, 1)'
        );
        $stmt->execute([
            ':titre' => $livre->getTitre(),
            ':auteur' => $livre->getAuteur(),
        ]);

        $livre->setDisponible(true);
        $this->livres[$titreKey] = $livre;
    }

    public function inscrireMembre(Membre $membre): void
    {
        $key = $this->membreKey($membre->getPrenom(), $membre->getNom());
        if (isset($this->membres[$key])) {
            throw new Exception("Le membre {$membre->getNomComplet()} existe déjà.");
        }

        $stmt = $this->pdo->prepare('INSERT INTO membres (nom, prenom) VALUES (:nom, :prenom)');
        $stmt->execute([
            ':nom' => $membre->getNom(),
            ':prenom' => $membre->getPrenom(),
        ]);

        $this->membres[$key] = $membre;
    }

    public function rechercherLivre(string $titre): Livre
    {
        $key = mb_strtolower($titre);
        if (!isset($this->livres[$key])) {
            throw new Exception("Livre introuvable : \"{$titre}\".");
        }
        return $this->livres[$key];
    }

    public function getMembre(string $prenom, string $nom): Membre
    {
        $key = $this->membreKey($prenom, $nom);
        if (!isset($this->membres[$key])) {
            throw new Exception("Membre introuvable : {$prenom} {$nom}.");
        }
        return $this->membres[$key];
    }

    public function enregistrerEmprunt(string $titreLivre, string $prenom, string $nom): void
    {
        $livre = $this->rechercherLivre($titreLivre);
        $membre = $this->getMembre($prenom, $nom);

        $membre->emprunterLivre($livre);

        $stmt = $this->pdo->prepare(
            'INSERT INTO emprunts (membre_nom, membre_prenom, livre_titre, date_emprunt, date_retour)
             VALUES (:nom, :prenom, :titre, CURDATE(), NULL)'
        );
        $stmt->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':titre' => $livre->getTitre(),
        ]);

        $this->pdo
            ->prepare('UPDATE livres SET disponible = 0 WHERE LOWER(titre) = LOWER(:titre)')
            ->execute([':titre' => $livre->getTitre()]);
    }

    public function enregistrerRetour(string $titreLivre, string $prenom, string $nom): void
    {
        $livre = $this->rechercherLivre($titreLivre);
        $membre = $this->getMembre($prenom, $nom);

        $membre->retournerLivre($livre);

        $stmt = $this->pdo->prepare(
            'UPDATE emprunts
             SET date_retour = CURDATE()
             WHERE membre_nom = :nom
               AND membre_prenom = :prenom
               AND livre_titre = :titre
               AND date_retour IS NULL'
        );
        $stmt->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':titre' => $livre->getTitre(),
        ]);

        $this->pdo
            ->prepare('UPDATE livres SET disponible = 1 WHERE LOWER(titre) = LOWER(:titre)')
            ->execute([':titre' => $livre->getTitre()]);
    }

    public function getLivres(): array
    {
        return $this->livres;
    }

    public function getMembres(): array
    {
        return $this->membres;
    }

    public function getEmpruntsActifs(): array
    {
        $stmt = $this->pdo->query(
            'SELECT membre_prenom, membre_nom, livre_titre, date_emprunt
             FROM emprunts
             WHERE date_retour IS NULL
             ORDER BY date_emprunt DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function afficherMembres(): void
    {
        echo "<h2>👥 Membres inscrits</h2>";

        if (empty($this->membres)) {
            echo "<p class='vide'>Aucun membre inscrit.</p>";
            return;
        }

        $empruntsParMembre = $this->getEmpruntsActifsParMembre();

        foreach ($this->membres as $membre) {
            echo "<div class='membre-carte'>";
            echo "<strong>{$membre->getNomComplet()}</strong>";

            $key = $this->membreKey($membre->getPrenom(), $membre->getNom());
            $emprunts = $empruntsParMembre[$key] ?? [];

            if (empty($emprunts)) {
                echo "<p>Aucun emprunt en cours.</p>";
            } else {
                echo "<ul>";
                foreach ($emprunts as $emprunt) {
                    echo "<li>{$emprunt['livre_titre']}</li>";
                }
                echo "</ul>";
            }
            echo "</div>";
        }
    }

    private function chargerDepuisBase(): void
    {
        $this->livres = [];
        $this->membres = [];

        $livresRows = $this->pdo->query(
            'SELECT titre, auteur, disponible FROM livres ORDER BY titre ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($livresRows as $row) {
            $livre = new Livre($row['titre'], $row['auteur']);
            $livre->setDisponible((int) $row['disponible'] === 1);
            $this->livres[mb_strtolower($row['titre'])] = $livre;
        }

        $membresRows = $this->pdo->query(
            'SELECT nom, prenom FROM membres ORDER BY prenom ASC, nom ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($membresRows as $row) {
            $membre = new Membre($row['nom'], $row['prenom']);
            $this->membres[$this->membreKey($row['prenom'], $row['nom'])] = $membre;
        }

        $actifs = $this->pdo->query(
            'SELECT membre_nom, membre_prenom, livre_titre FROM emprunts WHERE date_retour IS NULL'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($actifs as $row) {
            $livreKey = mb_strtolower($row['livre_titre']);
            $membreKey = $this->membreKey($row['membre_prenom'], $row['membre_nom']);
            if (isset($this->livres[$livreKey], $this->membres[$membreKey])) {
                $this->livres[$livreKey]->setDisponible(false);
                $this->membres[$membreKey]->hydraterEmpruntActif($this->livres[$livreKey]);
            }
        }
    }

    private function membreKey(string $prenom, string $nom): string
    {
        return mb_strtolower(trim($prenom) . '|' . trim($nom));
    }

    private function getEmpruntsActifsParMembre(): array
    {
        $rows = $this->pdo->query(
            'SELECT membre_prenom, membre_nom, livre_titre
             FROM emprunts
             WHERE date_retour IS NULL'
        )->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $key = $this->membreKey($row['membre_prenom'], $row['membre_nom']);
            $result[$key][] = $row;
        }

        return $result;
    }
}
