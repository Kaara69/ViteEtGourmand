<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
$active_page = '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentions Légales — Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/public.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'includes/partials/public_nav.php'; ?>
    
    <!-- Hero -->
    <section style="background:var(--dark);padding:4rem 0 2.5rem;">
        <div class="container">
            <p class="mb-1" style="color:var(--gold);font-size:.75rem;letter-spacing:3px;text-transform:uppercase;">Légal</p>
            <h1 style="font-family:'Playfair Display',serif;color:#fff;font-size:clamp(1.8rem,4vw,2.6rem);">Mentions Légales</h1>
            <p class="mt-2 mb-0" style="color:rgba(255,255,255,.5);font-size:.9rem;">
                Conformément à la loi n°2004-575 du 21 juin 2004 pour la confiance dans l'économie numérique
            </p>
        </div>
    </section>
    
    <main class="py-5" style="background:var(--cream);">
        <div class="container" style="max-whidth:860px">
            <div class="card border-0 shadow-sm p-4 p-md-5 mb-4">

                <!-- editeur -->
                <h2 class="ml-h2">Éditeur du site</h2>
                <table class="table table-sm ml-table">
                    <tbody>
                        <tr><th>Dénomination</th><td>Vite &amp; Gourmand</td></tr>
                        <tr><th>Forme juridique</th><td>Entreprise individuelle</td></tr>
                        <tr><th>Siège social</th><td>7 rue des Saveurs, 33000 Bordeaux</td></tr>
                        <tr><th>Téléphone</th><td>05 12 34 56 78</td></tr>
                        <tr><th>Email</th><td>contact@vitegourmand.fr</td></tr>
                        <tr><th>Responsable de publication</th><td>José Silva</td></tr>
                        <tr><th>N° SIRET</th><td>123 456 789 00012</td></tr>
                        <tr><th>N° TVA intracommunautaire</th><td>FR 12 123456789</td></tr>
                    </tbody>
                </table>

                <hr class="ml-hr">

                    <!-- Hébergement -->
                <h2 class="ml-h2">Hébergement</h2>
                <table class="table table-sm ml-table">
                    <tbody>
                        <tr><th>Hébergeur</th><td>InfinityFree</td></tr>
                        <tr><th>Site</th><td><a href="https://infinityfree.com" target="_blank" rel="noopener" style="color:var(--gold);">https://infinityfree.com</a></td></tr>
                        <tr><th>Adresse</th><td>InfinityFree, International House, 36-38 Cornhill, Londres, EC3V 3NG, Royaume-Uni</td></tr>
                    </tbody>
                </table>

                <hr class="ml-hr">

                <!-- Propriété intellectuelle -->
                <h2 class="ml-h2">Propriété intellectuelle</h2>
                <p>L'ensemble du contenu de ce site (textes, images, graphismes, logo, icônes, structure) est la propriété exclusive de <strong>Vite &amp; Gourmand</strong>, sauf mentions contraires. Toute reproduction, distribution, modification ou utilisation à des fins commerciales est interdite sans autorisation écrite préalable.</p>
                <p>Les images de menus utilisées sur ce site proviennent de la banque d'images <a href="https://unsplash.com" target="_blank" rel="noopener" style="color:var(--gold);">Unsplash</a>, sous licence libre d'utilisation.</p>
            
                <hr class="ml-hr">

                <!-- Données personnelles -->
                <h2 class="ml-h2">Protection des données personnelles (RGPD)</h2>
                <p>Conformément au Règlement Général sur la Protection des Données (RGPD) n°2016/679 et à la loi Informatique et Libertés n°78-17 du 6 janvier 1978 modifiée, vous disposez des droits suivants sur vos données :</p>
                <ul style="line-height:2.2;">
                    <li><strong>Droit d'accès</strong> : obtenir une copie de vos données personnelles</li>
                    <li><strong>Droit de rectification</strong> : corriger des données inexactes ou incomplètes</li>
                    <li><strong>Droit à l'effacement</strong> : demander la suppression de votre compte et de vos données</li>
                    <li><strong>Droit à la portabilité</strong> : recevoir vos données dans un format structuré</li>
                    <li><strong>Droit d'opposition</strong> : vous opposer au traitement de vos données</li>
                </ul>
                <p>Pour exercer ces droits, envoyez une demande par email à <strong>contact@vitegourmand.fr</strong> en indiquant votre nom et l'objet de votre demande. Vous pouvez également introduire une réclamation auprès de la <a href="https://www.cnil.fr" target="_blank" rel="noopener" style="color:var(--gold);">CNIL</a>.</p>
            
                <hr class="ml-hr">

                 <!-- Données collectées -->
                <h2 class="ml-h2">Données collectées et finalités</h2>
                <table class="table table-sm ml-table">
                    <thead><tr><th>Donnée</th><th>Finalité</th><th>Durée de conservation</th></tr></thead>
                    <tbody>
                        <tr><td>Nom, prénom</td><td>Identification du compte client</td><td>Durée du compte + 3 ans</td></tr>
                        <tr><td>Adresse email</td><td>Connexion, communication</td><td>Durée du compte + 3 ans</td></tr>
                        <tr><td>Adresse postale</td><td>Calcul et livraison des commandes</td><td>Durée du compte + 3 ans</td></tr>
                        <tr><td>Téléphone</td><td>Contact en cas de problème de livraison</td><td>Durée du compte + 3 ans</td></tr>
                        <tr><td>Historique commandes</td><td>Suivi, litige, comptabilité</td><td>5 ans (obligation légale)</td></tr>
                        <tr><td>Mot de passe</td><td>Authentification (hashé bcrypt, non lisible)</td><td>Durée du compte</td></tr>
                    </tbody>
                </table>
                <p class="small mt-2" style="color:var(--grey);">Aucune donnée personnelle n'est vendue ou cédée à des tiers à des fins commerciales.</p>

                <hr class="ml-hr">

                <!-- Cookies -->
                <h2 class="ml-h2">Cookies</h2>
                <p>Ce site utilise uniquement des <strong>cookies de session</strong> strictement nécessaires au fonctionnement du site (maintien de la connexion, gestion du panier). Aucun cookie publicitaire ou de traçage tiers n'est utilisé.</p>
                <p>Ces cookies sont temporaires et sont supprimés à la fermeture du navigateur. Ils ne nécessitent pas de consentement conformément à la directive ePrivacy (exemption pour les cookies techniques).</p>

                <hr class="ml-hr">

                <!-- Responsabilité -->
                <h2 class="ml-h2">Limitation de responsabilité</h2>
                <p>Vite &amp; Gourmand s'efforce d'assurer l'exactitude et la mise à jour des informations diffusées sur ce site. Cependant, il ne peut garantir l'exhaustivité ni l'absence d'erreur. Il se réserve le droit de corriger le contenu à tout moment.</p>
                <p>Vite &amp; Gourmand ne saurait être tenu responsable des dommages directs ou indirects résultant de l'utilisation du site ou de l'impossibilité d'y accéder.</p>

                <hr class="ml-hr">

                <!-- Droit applicable -->
                <h2 class="ml-h2">Droit applicable</h2>
                <p>Le présent site et les présentes mentions légales sont soumis au droit français. En cas de litige, les tribunaux compétents de <strong>Bordeaux</strong> seront seuls compétents.</p>
            </div> <!-- card -->

             <p class="text-center small" style="color:var(--grey);">
                <a href="index.php" style="color:var(--gold);text-decoration:none;">← Retour à l'accueil</a>
                 &nbsp;·&nbsp;
                <a href="cgv.php" style="color:var(--gold);text-decoration:none;">Conditions générales de vente</a>
            </p>
        </div> <!-- container -->
    </main>

<?php include 'includes/partials/footer.php'; ?>

<style>
.ml-h2 {
  font-family: 'Playfair Display', serif;
  font-size: 1.15rem;
  color: var(--dark);
  margin-top: 1.5rem;
  margin-bottom: .75rem;
  padding-bottom: .4rem;
  border-bottom: 2px solid var(--gold);
  display: inline-block;
}
.ml-hr {
  border: none;
  border-top: 1px solid #e8e0d4;
  margin: 1.75rem 0;
}
.ml-table th { color: var(--dark); font-weight: 600; width: 35%; background: var(--light); }
.ml-table td { color: #444; }
p, li { color: #444; line-height: 1.8; font-size: .95rem; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>