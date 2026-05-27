<?php
session_start();
include __DIR__ . '/../includes/auth.php';
checkAdmin();
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/nosql_db.php';
$active_page = 'stats';

$nosql      = new NoSQLStore();
$statsSync  = new StatsSync($pdo, $nosql);

// resynchroniser

if(isset($_GET['sync'])){
    $statsSync->syncAll();
    header('Location: stats.php?synced=1');
    exit;
}

// paramètre filtres
$menu_filter    = isset($_GET['menu_id'])   ? (int)$_GET['menu_id'] : 0;
$date_from      = $_GET['date_from']  ?? date('Y-m-01'); //1er du mois courant
$date_to        = $_GET['date_to']    ?? date('Y-m-d'); //aujourd'hui
$periode        = $_GET['periode']    ?? 'custom';

// raccourci periode

switch ($periode) {
    case '7j';
        $date_from = date('Y-m-d', strtotime('-7 days'));
        $date_to   = date('Y-m-d');
        break;
    case '30j';
        $date_from = date('Y-m-d', strtotime('-30 days'));
        $date_to   = date('Y-m-d');
        break;
    case '3m';
        $date_from = date('Y-m-d', strtotime('-3 months'));
        $date_to   = date('Y-m-d');
        break;
    case 'annee';
        $date_from = date('Y-01-01');
        $date_to   = date('Y-12-31');
        break;
    case 'tout':
        $date_from = '2000-01-01';
        $date_to   = date('Y-m-d');
        break;
}

// lecture données

// tous les menus (filtre)
$all_menus_stats = $nosql->find('stats_menu');
usort($all_menus_stats, fn($a,$b) => $b['nb_commandes'] <=> $a['nb_commandes']);

// stats journalières filtrées par date
$daily_filter = [
    'jour' => ['$gte' => $date_from, '$lte' => $date_to],
];
if ($menu_filter > 0) {
    $daily_filter['menu_id'] = $menu_filter;
}
$daily_docs = $nosql->find('stats_daily', $daily_filter);

// agrégation manuelle des daily pour KPIs
$kpi_ca      = 0.0;
$kpi_qty     = 0;
$ca_by_menu  = [];    // [menu_name => ca]
$qty_by_menu = [];    // [menu_name => qty]
$ca_by_day   = [];    // [date => ca]
$qty_by_day  = [];    // [date => qty]

foreach ($daily_docs as $doc) {
    $kpi_ca  += $doc['chiffre_affaires'];
    $kpi_qty += $doc['nb_commandes'];

    $nom = $doc['nom_menu'];
    $ca_by_menu[$nom]  = ($ca_by_menu[$nom]  ?? 0) + $doc['chiffre_affaires'];
    $qty_by_menu[$nom] = ($qty_by_menu[$nom] ?? 0) + $doc['nb_commandes'];

    $ca_by_day[$doc['jour']]  = ($ca_by_day[$doc['jour']]  ?? 0) + $doc['chiffre_affaires'];
    $qty_by_day[$doc['jour']] = ($qty_by_day[$doc['jour']] ?? 0) + $doc['nb_commandes'];
}

arsort($ca_by_menu);
arsort($qty_by_menu);
ksort($ca_by_day);
ksort($qty_by_day);

// données pour graphique js

// graphique 1 : comparaison commandes par menu

$chart_bar_labels = array_keys($qty_by_menu);
$chart_bar_qty    = array_values($qty_by_menu);
$chart_bar_ca     = array_map(fn($l) => round($ca_by_menu[$l] ?? 0, 2), $chart_bar_labels);

// grapghique 2 : CA et cmd dans le temps

$chart_line_dates  = array_keys($ca_by_day);
$chart_line_ca     = array_values($ca_by_day);
$chart_line_qty    = array_map(fn($d) => $qty_by_day[$d] ?? 0, $chart_line_dates);

// graphique §3 : mtop menus en camembert (CA)
$top_labels = array_slice(array_keys($ca_by_menu), 0, 8);
$top_values = array_map(fn($l) => round($ca_by_menu[$l], 2), $top_labels);

// Menus liste pour le select
$menus_list = $pdo->query("SELECT id, nom FROM menus ORDER BY nom")->fetchAll();

// Calcul nombre commandes total sur période (hors doublons)
$nb_cmd_periode = $pdo->prepare("
    SELECT COUNT(DISTINCT c.id) FROM commandes c
    JOIN commande_items ci ON ci.commande_id = c.id
    WHERE c.statut NOT IN ('annulé')
      AND DATE(c.created_at) BETWEEN ? AND ?
      " . ($menu_filter > 0 ? "AND ci.menu_id = ?" : "") . "
");
$params = [$date_from, $date_to];
if ($menu_filter > 0) $params[] = $menu_filter;
$nb_cmd_periode->execute($params);
$nb_cmd_periode = $nb_cmd_periode->fetchColumn();

// Meilleur menu sur la période
$top_menu_periode = $qty_by_menu ? array_key_first($qty_by_menu) : '—';

// Couleurs pour les graphiques
function generateColors(int $n, float $alpha = 0.8): array {
    $palette = [
        "rgba(201,151,61,$alpha)",  "rgba(28,21,16,$alpha)",   "rgba(54,162,235,$alpha)",
        "rgba(75,192,192,$alpha)",  "rgba(255,99,132,$alpha)",  "rgba(153,102,255,$alpha)",
        "rgba(255,159,64,$alpha)",  "rgba(46,204,113,$alpha)",  "rgba(52,73,94,$alpha)",
        "rgba(231,76,60,$alpha)",   "rgba(155,89,182,$alpha)",  "rgba(26,188,156,$alpha)",
    ];
    $out = [];
    for ($i = 0; $i < $n; $i++) $out[] = $palette[$i % count($palette)];
    return $out;
}

$bar_colors = generateColors(count($chart_bar_labels));
$pie_colors = generateColors(count($top_labels));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques – Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/espace.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/includes/partials/admin_nav.php'; ?>

    <div class="container-fluid py-4 px-4">
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1">
                    📊 Statistiques &amp; Chiffre d'affaires
                    <span class="nosql-badge ms-2">NoSQL JSON Store</span>
                </h4>
                <p class="text-muted small mb-0">
                    Les analytics sont stockées dans une base de données <strong>non relationnelle (docuement JSON)</strong>,
                    indépendante de la base SQlite transactionnelle.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="?sync=1&<?= http_build_query(array_filter(['menu_id'=>$menu_filter,'date_from'=>$date_from,'date_to'=>$date_to,'periode'=>$periode])) ?>"
                    class="btn btn-sm btn-outline-secondary"
                    onclick="return confirm('Resynchroniser toutes les stats depuis SQLite ?')">
                    🔄 Resync NoSQL
                </a>
                <?php if (isset($_GET['synced'])): ?>
                    <span class="badge bg-success align-self-center">✓ Synchronisé</span>
                    <?php endif; ?>
            </div>
        </div>

        <!-- barre de filtres -->
        <div class="filter-bar mb-4">
            <form method="get" id="filter-form">
                <div class="row g-3 align-items-end">

                    <!-- raccourci periode -->
                    <div class="col-12 col-md-auto">
                        <label class="form-label fw-semibold small">Période rapide</label>
                        <div class="btn-group btn-group-sm flew-wrap" role="group">
                            <?php
                            $periodes = ['7j'=>'7 jours','30j'=>'30 jours','3m'=>'3 mois','annee'=>'Cette année','tout'=>'Tout'];
                            foreach ($periode as $val => $label):
                            ?>
                            <a href="?periode=<?= $val ?>&menu_id=<?= $menu_filter ?>"
                                class="btn btn-outline-secondary periode-btn <?= $periode===$val?'active':'' ?>">
                                <?= $label ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>


                    <!-- dates personnalisés -->
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold small">Du</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $date_from ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold small">Au</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $date_to ?>">
                    </div>

                    <!-- Filtre par menu -->
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold small">Menu spécifique</label>
                        <select name="menu_id" class="form-select form-select-sm">
                            <option value="0">— Tous les menus —</option>
                                <?php foreach ($menus_list as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $menu_filter===$m['id']?'selected':'' ?>>
                                <?= htmlspecialchars($m['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm fw-bold text-white" style="background:var(--gold);">Filtrer</button>
                        <a href="stats.php" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
                    </div>
                </div>
                <input type="hidden" name="periode" value="custom">
            </form>
        </div>

        <!-- kpi card -->
        <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="card kpi-card h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="kpi-icon" style="background:#FF3DC;"><span>💰</span></div>
                            <div class="fs-4 fw-bold" style="color:var(--gold);"><?= number_format($kpi_ca, 2, ',', ' ') ?> €</div>
                        </div>
                    </div>
                </div>
            
            <div class="col-6 col-lg-3">
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="kpi-icon" style="background:#E8F4FD"><span>🛒</span></div>
                        <div>
                            <div class="text-muted small">Articles vendus</div>
                            <div class="fs-4 fw-bold text-primary"><?= number_format($kpi_qty) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="kpi-icon" style="background:#EAF7F0;"><span>📋</span></div>
                        <div>
                            <div class="text-muted small">Commandes</div>
                            <div class="fs-4 fw-bold text-success"><?= number_format($nb_cmd_periode) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card kpi-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="kpi-icon" style="background:#FDE8F0;"><span>🏆</span></div>
                        <div>
                            <div class="text-muted small">Top menu</div>
                            <div class="fw-bold text-truncate" style="max-width:140px;font-size:.95rem;"><?= htmlspecialchars($top_menu_periode) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- row -->

        <?php if (empty($daily_docs) && empty($all_menus_stats)): ?>
        <!-- État vide -->
        <div class="text-center py-5">
            <div class="fs-1 mb-3">📭</div>
            <h5 class="text-muted">Aucune donnée analytique disponible</h5>
            <p class="text-muted small">La base NoSQL est vide. Cliquez sur <strong>Resync NoSQL</strong> pour l'initialiser depuis les commandes existantes.</p>
            <a href="?sync=1" class="btn fw-bold text-white mt-2" style="background:var(--gold);">🔄 Initialiser les stats NoSQL</a>
        </div>
        <?php else: ?>


      <!-- Ligne 1 : Comparaison commandes & CA par menu -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="chart-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Comparaison par menu</h6>
                        <div class="btn-group btn-group-sm" id="bar-toggle">
                            <button class="btn btn-dark active" data-chart="qty">Commandes</button>
                            <button class="btn btn-outline-secondary" data-chart="ca">CA (€)</button>
                        </div>
                    </div>
                    <canvas id="barChart" height="260"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card p-4 h-100">
                    <h6 class="fw-bold mb-3">Répartition du CA</h6>
                    <?php if (!empty($top_labels)): ?>
                    <canvas id="pieChart" height="260"></canvas>
                    <?php else: ?>
                    <p class="text-muted text-center py-5 small">Aucune donnée</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

          <!-- Ligne 2 : Évolution temporelle -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="chart-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Évolution dans le temps</h6>
                        <div class="btn-group btn-group-sm" id="line-toggle">
                            <button class="btn btn-dark active" data-chart="ca">CA journalier (€)</button>
                            <button class="btn btn-outline-secondary" data-chart="qty">Volume d'articles</button>
                        </div>
                    </div>
                    <?php if (!empty($chart_line_dates)): ?>
                    <canvas id="lineChart" height="120"></canvas>
                    <?php else: ?>
                    <p class="text-muted text-center py-4 small">Aucune donnée sur la période sélectionnée.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

          <!-- Ligne 3 : Tableau détaillé -->
        <div class="chart-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    Tableau de synthèse
                    <span class="text-muted fw-normal small ms-2">(données NoSQL)</span>
                </h6>
                <span class="badge" style="background:var(--dark);color:var(--gold);">
                    <?= count($qty_by_menu) ?> menu<?= count($qty_by_menu)>1?'s':'' ?> actifs
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Menu</th>
                            <th>Catégorie</th>
                            <th>Articles vendus</th>
                            <th>Chiffre d'affaires</th>
                            <th>Prix unitaire moy.</th>
                            <th>Part du CA</th>
                            <th>Dernière vente</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        foreach ($ca_by_menu as $nom => $ca):
                            $qty   = $qty_by_menu[$nom] ?? 0;
                            $part  = $kpi_ca > 0 ? round($ca / $kpi_ca * 100, 1) : 0;
                            $prixM = $qty > 0 ? round($ca / $qty, 2) : 0;
                            // Cherche la dernière vente dans nosql
                            $doc   = $nosql->findOne('stats_menu', ['nom_menu' => $nom]);
                            $cat   = $doc['categorie'] ?? '—';
                            $last  = $doc ? date('d/m/Y', strtotime($doc['derniere_commande'])) : '—';
                            $medals = ['🥇','🥈','🥉'];
                        ?>
                        <tr>
                            <td class="fw-bold text-muted"><?= $medals[$rank-1] ?? '#'.$rank ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($nom) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($cat) ?></span></td>
                            <td>
                            <div class="d-flex align-items-center gap-2">
                                <strong><?= number_format($qty) ?></strong>
                                <div class="progress flex-grow-1" style="height:6px;">
                                <div class="progress-bar" style="width:<?= $kpi_qty>0?round($qty/$kpi_qty*100):0 ?>%;background:var(--gold);"></div>
                                </div>
                            </div>
                            </td>
                            <td class="fw-bold" style="color:var(--gold);"><?= number_format($ca, 2, ',', ' ') ?> €</td>
                            <td class="text-muted"><?= number_format($prixM, 2, ',', ' ') ?> €</td>
                            <td>
                            <div class="d-flex align-items-center gap-2">
                                <span><?= $part ?> %</span>
                                <div class="progress flex-grow-1" style="height:6px;">
                                <div class="progress-bar bg-success" style="width:<?= $part ?>%;"></div>
                                </div>
                            </div>
                            </td>
                            <td class="small text-muted"><?= $last ?></td>
                        </tr>
                        <?php $rank++; endforeach; ?>
                        <?php if (empty($ca_by_menu)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Aucune donnée sur cette période.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if ($kpi_ca > 0): ?>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3">TOTAL</td>
                            <td><?= number_format($kpi_qty) ?> articles</td>
                            <td style="color:var(--gold);"><?= number_format($kpi_ca, 2, ',', ' ') ?> €</td>
                            <td><?= $kpi_qty > 0 ? number_format($kpi_ca / $kpi_qty, 2, ',', ' ') : '0,00' ?> €</td>
                            <td>100 %</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Info architecture -->
        <div class="card mt-4 border-0" style="background:#1C1510;color:rgba(255,255,255,.8);">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-auto"><span style="color:var(--gold);font-size:1.5rem;">🗄️</span></div>
                    <div class="col">
                        <strong style="color:var(--gold);">Architecture bi-base</strong>
                        <span class="ms-2 small">
                            Les <strong>commandes &amp; menus</strong> sont stockés dans <code>MySQL</code> (relationnel) —
                            Les <strong>statistiques analytiques</strong> sont agrégées et stockées dans
                            <code>nosql_documents</code> (orienté document / JSON) via la classe <code>NoSQLStore</code>.
                        </span>
                    </div>
                    <div class="col-auto">
                        <a href="stats.php?sync=1" class="btn btn-sm btn-outline-light">🔄 Resync NoSQL →</a>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- container -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Données injectées depuis PHP
const barLabels  = <?= json_encode($chart_bar_labels, JSON_UNESCAPED_UNICODE) ?>;
const barQty     = <?= json_encode($chart_bar_qty) ?>;
const barCA      = <?= json_encode($chart_bar_ca) ?>;
const barColors  = <?= json_encode($bar_colors) ?>;

const lineLabels = <?= json_encode($chart_line_dates) ?>;
const lineCA     = <?= json_encode($chart_line_ca) ?>;
const lineQty    = <?= json_encode($chart_line_qty) ?>;

const pieLabels  = <?= json_encode($top_labels, JSON_UNESCAPED_UNICODE) ?>;
const pieValues  = <?= json_encode($top_values) ?>;
const pieColors  = <?= json_encode($pie_colors) ?>;

Chart.defaults.font.family = "'Nunito', sans-serif";

// Graphique 1 : Barres (comparaison menus)
const barCtx = document.getElementById('barChart');
let barMode  = 'qty';
let barChart;

function buildBarChart(mode) {
  if (barChart) barChart.destroy();
  barChart = new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: barLabels,
      datasets: [{
        label: mode === 'qty' ? 'Articles vendus' : 'Chiffre d\'affaires (€)',
        data:  mode === 'qty' ? barQty : barCA,
        backgroundColor: barColors,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => mode === 'ca'
              ? ctx.parsed.y.toFixed(2).replace('.',',') + ' €'
              : ctx.parsed.y + ' article(s)'
          }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { maxRotation: 30 } },
        y: {
          beginAtZero: true,
          ticks: {
            callback: v => mode === 'ca' ? v + ' €' : v
          }
        }
      }
    }
  });
}

buildBarChart('qty');

document.querySelectorAll('#bar-toggle button').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('#bar-toggle button').forEach(b => b.className='btn btn-outline-secondary');
    this.className = 'btn btn-dark active';
    buildBarChart(this.dataset.chart);
  });
});

// Graphique 2 : Camembert (répartition CA)
if (pieLabels.length > 0) {
  new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
      labels: pieLabels,
      datasets: [{ data: pieValues, backgroundColor: pieColors, borderWidth: 2, borderColor: '#fff' }]
    },
    options: {
      cutout: '55%',
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { size: 11 } } },
        tooltip: {
          callbacks: {
            label: ctx => ' ' + ctx.parsed.toFixed(2).replace('.',',') + ' €'
          }
        }
      }
    }
  });
}

// Graphique 3 : Ligne (évolution temporelle)
const lineCtx = document.getElementById('lineChart');
let lineMode  = 'ca';
let lineChart;

function buildLineChart(mode) {
  if (lineChart) lineChart.destroy();
  if (!lineCtx) return;
  lineChart = new Chart(lineCtx, {
    type: 'line',
    data: {
      labels: lineLabels,
      datasets: [{
        label: mode === 'ca' ? 'CA (€)' : 'Articles vendus',
        data:  mode === 'ca' ? lineCA  : lineQty,
        borderColor: '#C9973D',
        backgroundColor: 'rgba(201,151,61,.1)',
        tension: 0.35,
        fill: true,
        pointBackgroundColor: '#C9973D',
        pointRadius: lineLabels.length > 30 ? 2 : 5,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => mode === 'ca'
              ? ' ' + ctx.parsed.y.toFixed(2).replace('.',',') + ' €'
              : ' ' + ctx.parsed.y + ' article(s)'
          }
        }
      },
      scales: {
        x: { grid: { display: false } },
        y: {
          beginAtZero: true,
          ticks: { callback: v => mode === 'ca' ? v + ' €' : v }
        }
      }
    }
  });
}

if (lineLabels.length > 0) buildLineChart('ca');

document.querySelectorAll('#line-toggle button').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('#line-toggle button').forEach(b => b.className='btn btn-outline-secondary');
    this.className = 'btn btn-dark active';
    buildLineChart(this.dataset.chart);
  });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>