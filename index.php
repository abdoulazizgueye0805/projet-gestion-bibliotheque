<?php
require_once 'classes/Bibliotheque.php';
require_once 'classes/SimulateurEmprunt.php';

$messages = [];
$simulationResultat = null;
$listeMembres = [];

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=bibliothequepoo;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $biblio = new Bibliotheque("Bibliothèque Centrale ISI", $pdo);
} catch (Exception $e) {
    $messages[] = ['type' => 'erreur', 'texte' => 'Erreur de connexion à la base : ' . $e->getMessage()];
}

if (isset($biblio)) {
    $listeMembres = $biblio->getMembres();
}

if (isset($biblio) && isset($_GET['action'], $_GET['titre'])) {
    $action = $_GET['action'];
    $titre = trim((string) $_GET['titre']);
    $membreSelection = trim((string) ($_GET['membre'] ?? ''));
    [$prenom, $nom] = array_pad(explode('|', $membreSelection, 2), 2, '');

    try {
        if ($membreSelection === '' || $prenom === '' || $nom === '') {
            throw new Exception("Choisissez un membre pour effectuer un emprunt/retour.");
        }

        if ($action === 'emprunter') {
            $biblio->enregistrerEmprunt($titre, $prenom, $nom);
            $messages[] = ['type' => 'succes', 'texte' => "Emprunt enregistré avec succès."];
        } elseif ($action === 'retourner') {
            $biblio->enregistrerRetour($titre, $prenom, $nom);
            $messages[] = ['type' => 'succes', 'texte' => "Retour enregistré avec succès."];
        }
    } catch (Exception $e) {
        $messages[] = ['type' => 'erreur', 'texte' => $e->getMessage()];
    }
}

if (isset($biblio) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeFormulaire = $_POST['form_type'] ?? '';

    try {
        if ($typeFormulaire === 'livre') {
            $titre = trim($_POST['titre'] ?? '');
            $auteur = trim($_POST['auteur'] ?? '');
            if ($titre === '' || $auteur === '') {
                throw new Exception("Veuillez renseigner le titre et l'auteur.");
            }
            $biblio->ajouterLivre(new Livre($titre, $auteur));
            $messages[] = ['type' => 'succes', 'texte' => "Le livre {$titre} a été ajouté avec succès."];
        }

        if ($typeFormulaire === 'membre') {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            if ($nom === '' || $prenom === '') {
                throw new Exception("Veuillez renseigner le nom et le prénom.");
            }
            $biblio->inscrireMembre(new Membre($nom, $prenom));
            $messages[] = ['type' => 'succes', 'texte' => "Le membre {$prenom} {$nom} a été ajouté avec succès."];
        }

        if ($typeFormulaire === 'simulation') {
            $montant = (float) ($_POST['montant'] ?? 0);
            $duree = (int) ($_POST['duree'] ?? 0);
            $taux = (float) ($_POST['taux'] ?? 5);
            if ($montant <= 0 || $duree <= 0 || $taux <= 0) {
                throw new Exception("Veuillez saisir des valeurs valides pour la simulation.");
            }

            $simulationResultat = [
                'interets' => SimulateurEmprunt::calculerInterets($montant, $taux, $duree),
                'cout_total' => SimulateurEmprunt::calculerCoutTotal($montant, $taux, $duree),
            ];
        }
    } catch (Exception $e) {
        $messages[] = ['type' => 'erreur', 'texte' => $e->getMessage()];
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque ISI</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <h1>📚 <?php echo isset($biblio) ? htmlspecialchars($biblio->getNom()) : 'Bibliothèque'; ?></h1>
</header>
<main>
    <?php foreach ($messages as $message): ?>
        <p class="<?php echo htmlspecialchars($message['type']); ?>"><?php echo htmlspecialchars($message['texte']); ?></p>
    <?php endforeach; ?>

    <section>
        <h2>➕ Ajouter des éléments</h2>
        <div class="form-grid">
            <form method="post" class="card-form">
                <input type="hidden" name="form_type" value="livre">
                <h3>Ajouter un livre</h3>
                <label for="titre">Titre</label>
                <input type="text" id="titre" name="titre" required>
                <label for="auteur">Auteur</label>
                <input type="text" id="auteur" name="auteur" required>
                <button type="submit">Ajouter le livre</button>
            </form>

            <form method="post" class="card-form">
                <input type="hidden" name="form_type" value="membre">
                <h3>Ajouter un membre</h3>
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" required>
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required>
                <button type="submit">Ajouter le membre</button>
            </form>
        </div>
    </section>

    <section>
        <h2>💰 Simulateur d'emprunt payant</h2>
        <form method="post" class="card-form">
            <input type="hidden" name="form_type" value="simulation">
            <div class="form-grid-inline">
                <div>
                    <label for="montant">Montant de la caution</label>
                    <input type="number" id="montant" name="montant" min="1" step="0.01" required>
                </div>
                <div>
                    <label for="duree">Durée (mois)</label>
                    <input type="number" id="duree" name="duree" min="1" step="1" required>
                </div>
                <div>
                    <label for="taux">Taux annuel (%)</label>
                    <input type="number" id="taux" name="taux" min="0.1" step="0.1" value="5" required>
                </div>
            </div>
            <button type="submit">Simuler</button>
        </form>
        <?php if ($simulationResultat !== null): ?>
            <p class="info">
                Intérêts = <?php echo number_format($simulationResultat['interets'], 2, ',', ' '); ?>
                | Coût total = <?php echo number_format($simulationResultat['cout_total'], 2, ',', ' '); ?>
            </p>
        <?php endif; ?>
    </section>

    <section>
        <h2>📖 Catalogue interactif</h2>
        <?php if (!isset($biblio) || empty($biblio->getLivres())): ?>
            <p class="vide">Aucun livre dans la bibliothèque.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Auteur</th>
                        <th>Statut</th>
                        <th>Emprunt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($biblio->getLivres() as $livre): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($livre->getTitre()); ?></td>
                            <td><?php echo htmlspecialchars($livre->getAuteur()); ?></td>
                            <td class="<?php echo $livre->estDisponible() ? 'disponible' : 'emprunte'; ?>">
                                <?php echo $livre->estDisponible() ? 'Disponible' : 'Emprunté'; ?>
                            </td>
                            <td>
                                <form method="get" class="inline-form">
                                    <input type="hidden" name="action" value="<?php echo $livre->estDisponible() ? 'emprunter' : 'retourner'; ?>">
                                    <input type="hidden" name="titre" value="<?php echo htmlspecialchars($livre->getTitre()); ?>">
                                    <select name="membre" required>
                                        <option value="">Choisir membre</option>
                                        <?php foreach ($listeMembres as $membre): ?>
                                            <option value="<?php echo htmlspecialchars($membre->getPrenom() . '|' . $membre->getNom()); ?>">
                                                <?php echo htmlspecialchars($membre->getPrenom() . ' ' . $membre->getNom()); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit"><?php echo $livre->estDisponible() ? 'Emprunter' : 'Retourner'; ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section>
        <?php if (isset($biblio)): ?>
            <?php $biblio->afficherMembres(); ?>
        <?php endif; ?>
    </section>

    <section>
        <h2>📤 Emprunts actifs</h2>
        <?php if (!isset($biblio) || empty($biblio->getEmpruntsActifs())): ?>
            <p class="vide">Aucun emprunt actif.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Livre</th>
                        <th>Membre</th>
                        <th>Date emprunt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($biblio->getEmpruntsActifs() as $emprunt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($emprunt['livre_titre']); ?></td>
                            <td><?php echo htmlspecialchars($emprunt['membre_prenom'] . ' ' . $emprunt['membre_nom']); ?></td>
                            <td><?php echo htmlspecialchars($emprunt['date_emprunt']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
<footer>
    <p>Atelier POO PHP — Licence 2 — Institut Supérieur d'Informatique</p>
</footer>
</body>
</html>
