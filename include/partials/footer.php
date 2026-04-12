<?php
// Récupère les horaires
if (!isset($horaires) || !isset($today_fr)) {
    $today_fr = ['Sunday'=>'Dimanche','Monday'=>'Lundi','Tuesday'=>'Mardi',
                 'Wednesday'=>'Mercredi','Thursday'=>'Jeudi','Friday'=>'Vendredi',
                 'Saturday'=>'Samedi'][date('l')] ?? date('l');
    $horaires = [];
    if (isset($pdo)) {
        foreach ($pdo->query("SELECT * FROM horaires ORDER BY id")->fetchAll() as $h)
            $horaires[$h['jour']] = $h;
    }
}
?>

<footer class="py-5" id="horaires" style="background:var(--dark);">
    <div class="container">
        <div class="row g-5">
                            <!-- branding -->
             <div class="col-md-3">
                <h5 style="color:var(--gold);font-family:'Playfair Display',serif;font-size:1.3rem;">Vite &amp; Gourmand</h5>
                <p class="small mt-2" style="color:rgba(255,255,255,.55);line-height:1.7;">
                Cuisine artisanale d'exception depuis 2008. Nous mettons notre passion au service de vos événements.
                </p>
            </div>

                            <!-- navigation -->
             <div class="col-md-2">
                <h6 class="fw-bold mb-3" style="color:var(--gold);font-size:.75rem;letter-spacing:2px;text-transform:uppercase;">Navigation</h6>
                <ul class="list-unstyled small" style="color:rgba(255,255,255,.6);">
                    <li class="mb-1"><a href="<?= $base_path ?? '' ?>index.php"   class="footer-link">Accueil</a></li>
                    <li class="mb-1"><a href="<?= $base_path ?? '' ?>menus.php"   class="footer-link">Nos menus</a></li>
                    <li class="mb-1"><a href="<?= $base_path ?? '' ?>contact.php" class="footer-link">Contact</a></li>
                    <li class="mb-1"><a href="<?= $base_path ?? '' ?>login.php"   class="footer-link">Espace client</a></li>
                </ul>
            </div>

                            <!-- infos légalse -->
             <div class="col-md-2">
                <h6 class="fw-bold mb-3" style="color:var(--gold);font-size:.75rem;letter-spacing:2px;text-transform:uppercase;">Informations</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><a href="<?= $base_path ?? '' ?>cgv.php"               class="footer-link">CGV</a></li>
                    <li class="mb-1"><a href="<?= $base_path ?? '' ?>mentions-legales.php"  class="footer-link">Mentions légales</a></li>
                </ul>
            </div>

                            <!-- contact -->
            <div class="col-md-2">
                <h6 class="fw-bold mb-3" style="color:var(--gold);font-size:.75rem;letter-spacing:2px;text-transform:uppercase;">Contact</h6>
                <ul class="list-unstyled small" style="color:rgba(255,255,255,.6);line-height:2;">
                    <li>12 rue des Saveurs, 33000 Bordeaux</li>
                    <li>05 12 34 56 78</li>
                    <li>contact@vitegourmand.fr</li>
                </ul>
            </div>

                            <!-- hpraires dynamiques -->

            <div class="col-md-3">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color:var(--gold);font-size:.75rem;letter-spacing:2px;text-transform:uppercase;">
                    Horaires d'ouverture
                    <?php if (!empty($horaires)):
                    $h_today = $horaires[$today_fr] ?? null;
                        if ($h_today && !$h_today['ferme']): ?>
                    <span class="badge ms-1" style="background:rgba(25,135,84,.25);color:#6EE7A5;font-size:.65rem;letter-spacing:0;padding:3px 8px;border-radius:50px;">
                    <span style="display:inline-block;width:6px;height:6px;background:#6EE7A5;border-radius:50%;margin-right:4px;animation:blink 1.5s infinite;"></span>
                    Ouvert
                    </span>
                    <?php else: ?>
                    <span class="badge ms-1" style="background:rgba(220,53,69,.2);color:#ff8a8a;font-size:.65rem;letter-spacing:0;padding:3px 8px;border-radius:50px;">Fermé</span>
                    <?php endif;
                        endif; ?>
                </h6>

                <?php if (!empty($horaires)): ?>
                <div style="color:rgba(255,255,255,.7);">
                    <?php foreach ($horaires as $jour => $h):
                        $isToday = ($jour === $today_fr); ?>
                    <div class="d-flex justify-content-between align-items-center py-1" style="border-bottom:1px solid rgba(255,255,255,.06);<?= $isToday ? 'color:#fff;' : '' ?>">
                        <span class="small" style="font-weight:<?= $isToday ? '700' : '400' ?>;">
                            <?php if ($isToday): ?><span style="color:var(--gold);">▶</span><?php endif; ?>
                            <?= htmlspecialchars($jour) ?>
                            <?php if ($isToday): ?><span style="color:var(--gold);font-size:.68rem;margin-left:4px;">Aujourd'hui</span><?php endif; ?>
                        </span>
                        <?php if ($h['ferme']): ?>
                        <span class="small" style="color:rgba(255,138,138,.8);">Fermé</span>
                        <?php else: ?>
                        <span class="small" style="font-weight:<?= $isToday ? '700' : '400' ?>;color:<?= $isToday ? 'var(--gold)' : 'rgba(255,255,255,.6)' ?>;">
                            <?= $h['heure_ouverture'] ?> – <?= $h['heure_fermeture'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="small" style="color:rgba(255,255,255,.4);">Horaires non disponibles</p>
                <?php endif; ?>
            </div>                
        </div>

        <hr style="border-color:rgba(255,255,255,.08);margin-top:2.5rem;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <p class="small mb-0" style="color:rgba(255,255,255,.3);">© <?= date('Y') ?> Vite &amp; Gourmand — Tous droits réservés</p>
            <div class="small" style="color:rgba(255,255,255,.3);">
                <a href="<?= $base_path ?? '' ?>cgv.php"              class="footer-link-sm me-3">CGV</a>
                <a href="<?= $base_path ?? '' ?>mentions-legales.php" class="footer-link-sm">Mentions légales</a>
            </div>
        </div>
    </div>
</footer>

<style>
.footer-link { 
    color:rgba(255,255,255,.6);
    text-decoration:none;
    transition:color .2s; 
}
.footer-link:hover { 
    color:var(--gold); 
}
.footer-link-sm { 
    color:rgba(255,255,255,.3);
    text-decoration:none;
    transition:color .2s; 
}
.footer-link-sm:hover { 
    color:var(--gold); 
    }
</style>