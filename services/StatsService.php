<?php

/**
 * StatsService
 *
 * Contient toute la logique métier de la page stats.php :
 * - résolution des périodes (raccourcis 7j/30j/3m/annee/tout)
 * - lecture des stats journalières filtrées (via NoSQLStore)
 * - agrégation manuelle (CA, quantités, par menu, par jour)
 * - préparation des données pour les graphiques JS
 * - génération des couleurs des graphiques
 *
 * stats.php ne doit plus faire que : lire les $_GET, appeler ce service,
 * et passer le résultat à la vue.
 */
class StatsService
{
    private PDO $pdo;
    private NoSQLStore $nosql;
    private StatsRepository $statsRepository;

    private const PALETTE = [
        "rgba(201,151,61,%s)",  "rgba(28,21,16,%s)",    "rgba(54,162,235,%s)",
        "rgba(75,192,192,%s)",  "rgba(255,99,132,%s)",  "rgba(153,102,255,%s)",
        "rgba(255,159,64,%s)",  "rgba(46,204,113,%s)",  "rgba(52,73,94,%s)",
        "rgba(231,76,60,%s)",   "rgba(155,89,182,%s)",  "rgba(26,188,156,%s)",
    ];

    public function __construct(PDO $pdo, NoSQLStore $nosql, StatsRepository $statsRepository)
    {
        $this->pdo             = $pdo;
        $this->nosql           = $nosql;
        $this->statsRepository = $statsRepository;
    }

    /**
     * Relance la synchronisation complète (bouton "resynchroniser").
     */
    public function syncAll(): void
    {
        $statsSync = new StatsSync($this->pdo, $this->nosql);
        $statsSync->syncAll();
    }

    /**
     * Résout les dates de début/fin à partir du paramètre "periode".
     * Si periode = 'custom' (ou inconnue), $date_from/$date_to sont conservées telles quelles.
     *
     * @return array{0:string,1:string} [date_from, date_to]
     */
    public function resolvePeriode(string $periode, string $date_from, string $date_to): array
    {
        switch ($periode) {
            case '7j':
                return [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')];
            case '30j':
                return [date('Y-m-d', strtotime('-30 days')), date('Y-m-d')];
            case '3m':
                return [date('Y-m-d', strtotime('-3 months')), date('Y-m-d')];
            case 'annee':
                return [date('Y-01-01'), date('Y-12-31')];
            case 'tout':
                return ['2000-01-01', date('Y-m-d')];
            default:
                return [$date_from, $date_to];
        }
    }

    /**
     * Construit toutes les données nécessaires à la page stats :
     * KPIs, données des 3 graphiques, couleurs, liste des menus, top menu.
     */
    public function getStatsData(string $date_from, string $date_to, int $menu_filter): array
    {
        // tous les menus (pour le select de filtre + classement global)
        $all_menus_stats = $this->nosql->find('stats_menu');
        usort($all_menus_stats, fn($a, $b) => $b['nb_commandes'] <=> $a['nb_commandes']);

        // stats journalières filtrées par date (et éventuellement par menu)
        $daily_filter = [
            'jour' => ['$gte' => $date_from, '$lte' => $date_to],
        ];
        if ($menu_filter > 0) {
            $daily_filter['menu_id'] = $menu_filter;
        }
        $daily_docs = $this->nosql->find('stats_daily', $daily_filter);

        $aggregated = $this->aggregateDaily($daily_docs);

        $charts = $this->buildChartsData($aggregated);

        return [
            'all_menus_stats'   => $all_menus_stats,
            'kpi_ca'            => $aggregated['kpi_ca'],
            'kpi_qty'           => $aggregated['kpi_qty'],
            'ca_by_menu'        => $aggregated['ca_by_menu'],
            'qty_by_menu'       => $aggregated['qty_by_menu'],
            'charts'            => $charts,
            'menus_list'        => $this->statsRepository->getMenusList(),
            'nb_cmd_periode'    => $this->statsRepository->countOrdersBetween(
                $date_from,
                $date_to,
                $menu_filter > 0 ? $menu_filter : null
            ),
            'top_menu_periode'  => $this->statsRepository->getBestMenu($aggregated['qty_by_menu']),
        ];
    }

    /**
     * Agrège manuellement les documents "stats_daily" :
     * totaux, répartition par menu, répartition par jour.
     */
    private function aggregateDaily(array $daily_docs): array
    {
        $kpi_ca      = 0.0;
        $kpi_qty     = 0;
        $ca_by_menu  = [];
        $qty_by_menu = [];
        $ca_by_day   = [];
        $qty_by_day  = [];

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

        return compact('kpi_ca', 'kpi_qty', 'ca_by_menu', 'qty_by_menu', 'ca_by_day', 'qty_by_day');
    }

    /**
     * Prépare les tableaux prêts à être json_encode() côté vue pour les 3 graphiques.
     */
    private function buildChartsData(array $agg): array
    {
        $ca_by_menu  = $agg['ca_by_menu'];
        $qty_by_menu = $agg['qty_by_menu'];
        $ca_by_day   = $agg['ca_by_day'];
        $qty_by_day  = $agg['qty_by_day'];

        // Graphique 1 : comparaison commandes/CA par menu
        $bar_labels = array_keys($qty_by_menu);
        $bar_qty    = array_values($qty_by_menu);
        $bar_ca     = array_map(fn($l) => round($ca_by_menu[$l] ?? 0, 2), $bar_labels);

        // Graphique 2 : CA et commandes dans le temps
        $line_dates = array_keys($ca_by_day);
        $line_ca    = array_values($ca_by_day);
        $line_qty   = array_map(fn($d) => $qty_by_day[$d] ?? 0, $line_dates);

        // Graphique 3 : top menus en camembert (CA)
        $top_labels = array_slice(array_keys($ca_by_menu), 0, 8);
        $top_values = array_map(fn($l) => round($ca_by_menu[$l], 2), $top_labels);

        return [
            'bar' => [
                'labels' => $bar_labels,
                'qty'    => $bar_qty,
                'ca'     => $bar_ca,
                'colors' => $this->generateColors(count($bar_labels)),
            ],
            'line' => [
                'dates' => $line_dates,
                'ca'    => $line_ca,
                'qty'   => $line_qty,
            ],
            'pie' => [
                'labels' => $top_labels,
                'values' => $top_values,
                'colors' => $this->generateColors(count($top_labels)),
            ],
        ];
    }

    /**
     * Génère une palette de N couleurs (cyclique) pour les graphiques.
     */
    public function generateColors(int $n, float $alpha = 0.8): array
    {
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[] = sprintf(self::PALETTE[$i % count(self::PALETTE)], $alpha);
        }
        return $out;
    }
}