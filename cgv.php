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
    <title>Conditions Générales de Vente — Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/public.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/partials/public_nav.php'; ?>

    <section style="background:var(--dark);padding:4rem 0 2.5rem;">
        <div class="container">
            <p class="mb-1" style="color:var(--gold);font-size:.75rem;letter-spacing:3px;text-transform:uppercase;">Légal</p>
            <h1 style="font-family:'Playfair Display',serif;color:#fff;font-size:clamp(1.8rem,4vw,2.6rem);">
                Conditions Générales de Vente
            </h1>
            <p class="mt-2 mb-0" style="color:rgba(255,255,255,.5);font-size:.9rem;">
                En vigueur au <?= date('d/m/Y') ?> — Applicables à toute commande passée sur le site
            </p>
        </div>
    </section>
    
    <main class="py-5" style="background:var(--cream);">
        <div class="container" style="max-width:860px;">

            <div class="card border-0 shadow-sm p-4 p-md-5 mb-4">

                <!-- Article 1 -->
                <h2 class="cgv-h2">Article 1 — Objet</h2>
                <p>Les présentes Conditions Générales de Vente (CGV) régissent les relations contractuelles entre la société <strong>Vite &amp; Gourmand</strong>, dont le siège social est situé au 12 rue des Saveurs, 33000 Bordeaux (ci-après "le Traiteur"), et toute personne physique ou morale passant commande via le site internet (ci-après "le Client").</p>
                <p>Toute commande implique l'acceptation pleine et entière des présentes CGV. Le Traiteur se réserve le droit de modifier ses CGV à tout moment ; les CGV applicables sont celles en vigueur à la date de la commande.</p>

                <hr class="cgv-hr">

                <!-- Article 2 -->
                <h2 class="cgv-h2">Article 2 — Produits et services</h2>
                <p>Vite &amp; Gourmand propose des prestations de traiteur pour particuliers et professionnels : plateaux-repas, buffets, cocktails dinatoires, repas de mariage et menus de fêtes. Les prestations sont décrites sur le site avec leur composition, prix et nombre de personnes minimum.</p>
                <p>Les photographies présentées sont non contractuelles. La composition exacte des menus peut être ajustée en fonction des arrivages et de la saisonnalité des produits, dans le respect des allergènes indiqués.</p>

                <hr class="cgv-hr">

                <!-- Article 3 -->
                <h2 class="cgv-h2">Article 3 — Prix</h2>
                <p>Les prix sont indiqués en euros TTC (TVA incluse au taux en vigueur). Ils comprennent la préparation et le conditionnement des plats.</p>
                <p><strong>Frais de livraison :</strong> Un forfait de base de <strong>5,00 €</strong> est appliqué, auquel s'ajoutent <strong>0,54 € par kilomètre</strong> calculé depuis notre établissement (12 rue des Saveurs, 33000 Bordeaux) jusqu'à l'adresse de livraison, via la formule de calcul Haversine.</p>
                <p><strong>Remise :</strong> Une remise de <strong>10 %</strong> sur le montant des menus (hors livraison) est accordée automatiquement lorsque le nombre de personnes commandées est supérieur ou égal au nombre minimum requis par le menu augmenté de cinq (5) personnes.</p>
                <p>Le Traiteur se réserve le droit de modifier ses tarifs à tout moment. Les prix affichés au moment de la validation de la commande sont définitifs.</p>

                <hr class="cgv-hr">

                <!-- Article 4 -->
                <h2 class="cgv-h2">Article 4 — Commandes</h2>
                <p>Toute commande est effectuée via le site internet après création d'un compte client. La commande est définitivement enregistrée lors de sa validation par le Client, qui reçoit une confirmation à l'écran.</p>
                <p>Le Traiteur se réserve le droit d'accepter ou de refuser toute commande, notamment en cas de disponibilité insuffisante ou de non-respect des présentes CGV. Le Client est informé du statut de sa commande en temps réel via la page de suivi.</p>

                <div class="alert" style="background:rgba(201,151,61,.1);border-left:4px solid var(--gold);border-radius:0 8px 8px 0;color:var(--dark);">
                    <strong>⚠️ Délai de commande :</strong> Toute commande doit être passée au minimum <strong>48 heures</strong> avant la date de livraison souhaitée, afin de permettre la préparation dans les meilleures conditions.
                </div>

                <hr class="cgv-hr">

                <!-- Article 5 -->
                <h2 class="cgv-h2">Article 5 — Paiement</h2>
                <p>Le règlement s'effectue selon les modalités convenues avec le Traiteur (virement bancaire, chèque ou espèces selon l'accord). Le paiement en ligne via le site n'est pas disponible à ce jour.</p>
                <p>En cas de non-paiement à l'échéance, le Traiteur se réserve le droit de suspendre toute prestation en cours et d'engager les procédures de recouvrement nécessaires.</p>

                <hr class="cgv-hr">

                <!-- Article 6 -->
                <h2 class="cgv-h2">Article 6 — Livraison</h2>
                <p>Le Traiteur assure la livraison à l'adresse indiquée par le Client lors de la commande. Il est de la responsabilité du Client de vérifier l'exactitude de l'adresse renseignée.</p>
                <p>En cas d'absence du Client au moment de la livraison, les frais de second passage seront facturés au tarif en vigueur. Le Traiteur ne pourra être tenu responsable d'un retard lié à des événements extérieurs (conditions météorologiques, circulations perturbées, etc.).</p>

                <hr class="cgv-hr">

                <!-- Article 7 — Retour de matériel -->
                <h2 class="cgv-h2">Article 7 — Matériel prêté et retour</h2>
                <p>Pour certaines prestations, le Traiteur peut être amené à prêter du matériel de service (plats, couverts, équipements de présentation). Ce matériel reste la propriété du Traiteur.</p>
                <p>Le Client s'engage à restituer le matériel prêté dans un délai de <strong>10 jours ouvrés</strong> à compter de la date de livraison de la commande. Le statut de la commande est alors indiqué comme "En attente du retour de matériel" sur le site.</p>

                <div class="alert" style="background:rgba(220,53,69,.08);border-left:4px solid #dc3545;border-radius:0 8px 8px 0;color:var(--dark);">
                    <strong>🚨 Pénalité de non-restitution :</strong> En l'absence de retour du matériel dans les délais impartis, des frais de remplacement d'un montant de <strong>600,00 €</strong> seront facturés au Client, sans mise en demeure préalable. Pour organiser le retour, le Client doit contacter le Traiteur par email à <strong>contact@vitegourmand.fr</strong>.
                </div>

                <hr class="cgv-hr">

                <!-- Article 8 -->
                <h2 class="cgv-h2">Article 8 — Annulation et rétractation</h2>
                <p>Toute annulation de commande doit être signalée par email à <strong>contact@vitegourmand.fr</strong> dans les délais suivants :</p>
                <ul style="line-height:2;">
                    <li>Annulation <strong>plus de 72h</strong> avant la livraison : aucuns frais d'annulation</li>
                    <li>Annulation entre <strong>24h et 72h</strong> avant la livraison : 30 % du montant facturé</li>
                    <li>Annulation <strong>moins de 24h</strong> avant la livraison : 100 % du montant facturé</li>
                </ul>
                <p>Conformément à l'article L.221-28 du Code de la consommation, le droit de rétractation de 14 jours ne s'applique pas aux denrées alimentaires périssables.</p>

                <hr class="cgv-hr">

                <!-- Article 9 -->
                <h2 class="cgv-h2">Article 9 — Allergènes et régimes alimentaires</h2>
                <p>Le Traiteur indique les allergènes présents dans chaque menu sur le site. Il appartient au Client de vérifier la composition des menus avant commande et de signaler tout régime alimentaire particulier ou allergie grave lors de la passation de commande via le champ "Instructions spéciales".</p>
                <p>Le Traiteur ne pourra être tenu responsable d'une réaction allergique non signalée préalablement à la commande.</p>

                <hr class="cgv-hr">

                <!-- Article 10 -->
                <h2 class="cgv-h2">Article 10 — Responsabilité</h2>
                <p>Le Traiteur est soumis à une obligation de moyens et s'engage à mettre en œuvre tous les moyens nécessaires pour assurer la qualité de ses prestations. Sa responsabilité ne pourra être engagée en cas de force majeure, d'accident imprévisible ou d'utilisation non conforme du matériel livré.</p>

                <hr class="cgv-hr">

                <!-- Article 11 -->
                <h2 class="cgv-h2">Article 11 — Données personnelles</h2>
                <p>Les données personnelles collectées (nom, email, adresse, téléphone) sont nécessaires au traitement des commandes. Conformément au RGPD et à la loi Informatique et Libertés, le Client dispose d'un droit d'accès, de rectification et de suppression de ses données en envoyant une demande à <strong>contact@vitegourmand.fr</strong>.</p>
                <p>Ces données ne sont jamais cédées à des tiers à des fins commerciales.</p>

                <hr class="cgv-hr">

                <!-- Article 12 -->
                <h2 class="cgv-h2">Article 12 — Litiges et droit applicable</h2>
                <p>Les présentes CGV sont soumises au droit français. En cas de litige, le Client est invité à contacter le Traiteur en premier lieu pour résolution amiable. À défaut d'accord, les tribunaux compétents de Bordeaux seront seuls compétents.</p>

            </div>
                <p class="text-center small" style="color:var(--grey);">
                    <a href="index.php" style="color:var(--gold);text-decoration:none;">← Retour à l'accueil</a>
                    &nbsp;·&nbsp;
                    <a href="mentions-legales.php" style="color:var(--gold);text-decoration:none;">Mentions légales</a>
                </p>
        </div>
    </main>
    <?php include 'includes/partials/footer.php'; ?>

<style>
.cgv-h2 {
  font-family: 'Playfair Display', serif;
  font-size: 1.15rem;
  color: var(--dark);
  margin-top: 1.5rem;
  margin-bottom: .75rem;
  padding-bottom: .4rem;
  border-bottom: 2px solid var(--gold);
  display: inline-block;
}
.cgv-hr {
  border: none;
  border-top: 1px solid #e8e0d4;
  margin: 1.75rem 0;
}
p, li { color: #444; line-height: 1.8; font-size: .95rem; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>