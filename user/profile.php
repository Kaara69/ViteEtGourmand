<?php 
session_start();

include '../include/auth.php';
checkLogin('../login.php');

include ('../include/db.php');

$active_page = 'profile';

// Charger l'utilisateur connecté
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();


// Messages de retour
$msg = '';
$error = '';


// Mise à jour du profil (si formulaire envoyé)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Récupérer et sécuriser les champs
    $nom       = trim($_POST['nom']        ?? '');
    $prenom    = trim($_POST['prenom']     ?? '');
    $email     = trim(strtolower($_POST['email'] ?? ''));
    $telephone = trim($_POST['telephone']  ?? '');
    $adresse   = trim($_POST['adresse']    ?? '');
    $pass      = $_POST['new_password']    ?? '';
    $pass2     = $_POST['confirm_password'] ?? '';


    // 2) Validation basique
    if (empty($nom) || empty($email)) {
        $error = 'Le nom et l\'email sont obligatoires.';
    }
    elseif (!empty($pass) && strlen($pass) < 6) {
        $error = 'Le mot de passe doit faire au moins 6 caractères.';
    }
    elseif (!empty($pass) && $pass !== $pass2) {
        $error = 'Les deux mots de passe ne correspondent pas.';
    }
    // 3) Si tout est OK, on met à jour en BDD
    else {
        try {
            if (!empty($pass)) {
                // Mise à jour avec nouveau mot de passe
                $sql = "UPDATE users
                        SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, password = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $nom, $prenom, $email, $telephone, $adresse,
                    password_hash($pass, PASSWORD_DEFAULT),
                    $_SESSION['user_id']
                ]);
            } else {
                // Mise à jour sans changer le mot de passe
                $sql = "UPDATE users
                        SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $nom, $prenom, $email, $telephone, $adresse,
                    $_SESSION['user_id']
                ]);
            }

            // Mettre à jour la session et la variable $user
            $_SESSION['nom']   = $nom;
            $_SESSION['email'] = $email;

            $user['nom']       = $nom;
            $user['prenom']    = $prenom;
            $user['email']     = $email;
            $user['telephone'] = $telephone;
            $user['adresse']   = $adresse;

            $msg = 'Profil mis à jour avec succès !';
        }
        catch (PDOException $e) {
            $error = 'Cette adresse email est déjà utilisée par un autre compte.';
        }
    }
}


// Soumettre un avis (si formulaire envoyé)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_avis'])) {
    $contenu = trim($_POST['contenu'] ?? '');
    $note = max(1, min(5, (int)($_POST['note'] ?? 5)));

    if (empty($contenu) || strlen($contenu) < 20) {
        $error = 'Votre commentaire doit faire au moins 20 caractères.';
    }
    else {
        $stmt = $pdo->prepare("
            INSERT INTO avis (user_id, nom, contenu, note, statut)
            VALUES (?, ?, ?, ?, 'en attente')
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $user['nom'],
            $contenu,
            $note
        ]);

        $msg = 'Votre avis a été soumis et sera publié après validation par notre équipe.';
    }
}

// Charger les avis de cet utilisateur
$stmt = $pdo->prepare("SELECT * FROM avis WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$mes_avis = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil – Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/espace.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../include/partials/user_nav.php'; ?>

    <div class="container py-4" style="max-width:900px">
        <!-- affiche le message de succès -->
        <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
        <?php endif; ?>

        <!-- affiche le message d'erreur -->
        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

                            <!-- en tête profil -->
        <div class="card card-section mb-4">
            <div class="card-body d-flex align-items-center gap-4 py-4">
                <!-- affiche la première lettre du nom de l’utilisateur en majuscule -->
                <div class="avatar"><?= strtoupper(substr($user['nom'], 0, 1)) ?></div>
                <div>
                    <h5 class="fw-bold mb-0">
                        <?= htmlspecialchars(($user['prenom'] ? $user['prenom'].' ' : '') . $user['nom']) ?>
                    </h5>
                    <p class="text-muted mb-1 small">
                        <i class="bi bi-envelope me-1"></i>
                        <?= htmlspecialchars($user['email']) ?>
                    </p>
                    <?php if (!empty($user['telephone'])): ?>
                    <p class="text-muted mb-1 small">
                        <i class="bi bi-telephone me-1"></i>
                        <?= htmlspecialchars($user['telephone']) ?>
                    </p>
                    <?php endif; ?>
                    <span class="badge bg-secondary small">Membre depuis le 
                        <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                    </span>
                </div>
            </div> <!-- card-body -->
        </div> <!-- card -->

        <div class="row g-4">
            
                             <!--infos perso  -->
            <div class="col-lg-6">
                <div class="card card-section">
                    <div class="section-header">
                        <i class="bi pi-person-fill me-2"></i>Mes informations
                    </div>
                    <div class="card-body p-4">
                        <form method="post">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label fw-semebold small">Prénom</label>
                                    <input type="text" name="prenom" class="form-control" placeholder="Votre prénom"
                                    value="<?= htmlspecialchars($user['prenom'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                     <label class="form-label fw-semibold small">Nom <span class="text-danger">*</span></label>
                                    <input type="text" name="nom" class="form-control" required
                                    value="<?= htmlspecialchars($user['nom']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Adresse email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" required
                                    value="<?= htmlspecialchars($user['email']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Téléphone</label>
                                    <input type="tel" name="telephone" class="form-control"
                                    placeholder="06 01 01 02 02" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Adresse postale</label>
                                    <textarea name="adresse" class="form-control" rows="2"
                                    placeholder="Numéro, rue, code postal, ville"><?= htmlspecialchars($user['adresse'] ?? '') ?></textarea>
                                </div>
                            </div> <!-- row -->
                            
                            <hr class="my-3">
                            <p class="text-muted small mb-2">
                                <i class="bi bi-lock me-1"></i>
                                Modifier le mot de passe <span class="text-muted">(laisser vide pour ne pas changer)</span>
                            </p>
                            <div class="row g-2">
                                <div class="col-12">
                                    <input type="password" name="new_password" class="form-control" min-length="6"
                                    placehorder="Nouveau mot de passe (min. 6car.)">
                                </div>
                                <div class="col-12">
                                    <input type="password" name="confirm_password" class="form-control"
                                    placeholder="Confirmer le nouveau mot de passe">
                                </div>
                            </div>

                            <button type="submit" class="btn w-100 fw-bold text-white mt-3" style="background:var(--gold);">
                                <i class="bi bi-check2 me-1"></i> Enregistrer les modifications
                            </button>
                        </form>
                    </div> <!-- card-body -->
                </div> <!-- card -->
            </div> <!-- col -->

                            <!-- Laisser un avis -->
            <div class="col-lg-6">
                <div class="card card-section mb-4" id="avis">
                    <div class="section-header"><i class="bi bi-star-fill me-2"></i>Laisser un avis</div>
                    <div class="card-body p-4">
                        <form method="post">
                            <input type="hidden" name="submit_avis" value="1">
                            <input type="hidden" name="note" id="note-val" value="5">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Votre note</label>
                                <div id="star-picker" class="d-flex gap-1 flex-row-reverse justify-content-end">
                                    <?php for ($i=5;$i>=1;$i--): ?>
                                    <label class="star-label <?= $i===5?'active':'' ?>" data-val="<?= $i ?>">★</label>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-muted small mt-1">Note : <span id="note-text">5/5 – Excellent</span></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Votre commentaire</label>
                                <textarea name="contenu" class="form-control" rows="4" required minlength="20"
                                            placeholder="Partagez votre expérience (min. 20 caractères)..."></textarea>
                            </div>
                            <div class="alert alert-info py-2 px-3 small mb-3">
                                <i class="bi bi-info-circle me-1"></i>Votre avis sera publié après validation par notre équipe.
                            </div>
                            <button type="submit" class="btn w-100 fw-bold text-white" style="background:var(--gold);">
                                <i class="bi bi-send me-1"></i>Soumettre mon avis
                            </button>
                        </form>
                    </div> <!-- card-body -->
                </div> <!-- card -->

                            <!-- Mes avis précédents -->
                <?php if ($mes_avis): ?>
                <div class="card card-section">
                    <div class="section-header"><i class="bi bi-chat-left-text-fill me-2"></i>Mes avis soumis</div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr><th>Note</th><th>Commentaire</th><th>Date</th><th>Statut</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mes_avis as $a):
                                $sc = ['en attente'=>'warning','approuvé'=>'success','approuve'=>'success','refusé'=>'danger','refuse'=>'danger'];
                                ?>
                                <tr>
                                    <td class="text-nowrap small" style="color:var(--gold);"><?= stars($a['note']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars(mb_substr($a['contenu'],0,55)) ?><?= mb_strlen($a['contenu'])>55?'…':'' ?></td>
                                    <td class="small text-nowrap"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                                    <td><span class="badge bg-<?= $sc[$a['statut']] ?? 'secondary' ?>"><?= $a['statut'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div> <!-- col -->
        </div> <!-- row -->
    </div> <!-- container -->

<script>
const labels = ['Très mauvais','Mauvais','Moyen','Bien','Excellent'];
const stars  = document.querySelectorAll('.star-label');
const noteInput = document.getElementById('note-val');
const noteText  = document.getElementById('note-text');

function setNote(val) {
  noteInput.value = val;
  noteText.textContent = val + '/5 – ' + labels[val-1];
  stars.forEach(s => s.classList.toggle('active', parseInt(s.dataset.val) <= val));
}
stars.forEach(s => {
  s.addEventListener('click',      () => setNote(parseInt(s.dataset.val)));
  s.addEventListener('mouseenter', () => stars.forEach(x => x.classList.toggle('active', parseInt(x.dataset.val) <= parseInt(s.dataset.val))));
  s.addEventListener('mouseleave', () => setNote(parseInt(noteInput.value)));
});
setNote(5);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
