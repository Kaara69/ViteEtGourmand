<?php
/**
 * Store NoSQL-like dans MySQL (table `nosql_documents`)
 * 
 * Table:
 *   id           INT AUTO_INCREMENT PRIMARY KEY
 *   collection   VARCHAR(100)
 *   doc_id       VARCHAR(40)  (id du document)
 *   data         JSON
 *   created_at   DATETIME
 *   updated_at   DATETIME
 */
class NoSQLStore
{
    private PDO $pdo;

    public function __construct()
    {
        // Utilise la connexion globale $pdo si elle existe
        global $pdo;
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
        } else {
            // Sinon, créer une connexion PDO (comme dans config.php)
            require_once dirname(__DIR__) . '/config.php';
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_PORT, DB_NAME
            );
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
    }

    // Génération d'ID simple
    private function newId(): string
    {
        return bin2hex(random_bytes(16));
    }

    // Récupère tous les docs d'une collection
    private function fetchAll(string $col): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT doc_id, data FROM nosql_documents WHERE collection = ? ORDER BY created_at ASC"
        );
        $stmt->execute([$col]);
        $rows = $stmt->fetchAll();

        $docs = [];
        foreach ($rows as $row) {
            $data = json_decode($row['data'], true) ?? [];
            $data['_id'] = $row['doc_id'];
            $docs[] = $data;
        }
        return $docs;
    }

    // Filtre par clé/valeur (PHP)
    private function matches(array $doc, array $filter): bool
    {
        foreach ($filter as $key => $value) {
            if (!isset($doc[$key])) {
                return false;
            }

            if (is_array($value)) {
                foreach ($value as $op => $operand) {
                    switch ($op) {
                        case '$gte':
                            if ($doc[$key] < $operand) return false;
                            break;
                        case '$lte':
                            if ($doc[$key] > $operand) return false;
                            break;
                        case '$gt':
                            if ($doc[$key] <= $operand) return false;
                            break;
                        case '$lt':
                            if ($doc[$key] >= $operand) return false;
                            break;
                        case '$ne':
                            if ($doc[$key] === $operand) return false;
                            break;
                        case '$in':
                            if (!in_array($doc[$key], $operand, true)) return false;
                            break;
                    }
                }
            } else {
                if ($doc[$key] !== $value) {
                    return false;
                }
            }
        }
        return true;
    }

    // ── API publique

     // Récupère tous les documents d'une collection qui correspondent au filtre
    public function find(string $col, array $filter = []): array
    {
        $docs = $this->fetchAll($col);
        if (empty($filter)) {
            return $docs;
        }

        $results = [];
        foreach ($docs as $d) {
            if ($this->matches($d, $filter)) {
                $results[] = $d;
            }
        }
        return $results;
    }

    /* Récupère le premier document qui correspond au filtre */

    public function findOne(string $col, array $filter = []): ?array
    {
        $results = $this->find($col, $filter);
        return $results[0] ?? null;
    }

    /* Insère un nouveau document */

    public function insertOne(string $col, array $doc): array
    {
        $id  = $this->newId();
        $now = date('Y-m-d H:i:s');

        $doc['_created_at'] = $now;
        $doc['_id']          = $id;

        $stmt = $this->pdo->prepare(
            "INSERT INTO nosql_documents (collection, doc_id, data, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $col,
            $id,
            json_encode($doc, JSON_UNESCAPED_UNICODE),
            $now,
            $now,
        ]);

        return $doc;
    }

    /* Met à jour le premier document trouvé */
    public function updateOne(string $col, array $filter, array $update): bool
    {
        $docs = $this->fetchAll($col);

        foreach ($docs as $doc) {
            if ($this->matches($doc, $filter)) {
                $id = $doc['_id'];
                $now = date('Y-m-d H:i:s');

                // Fusionne le doc original avec les modifications
                $updated = $doc; // copie
                foreach ($update as $k => $v) {
                    $updated[$k] = $v;
                }
                $updated['_updated_at'] = $now;

                unset($updated['_id']);

                $stmt = $this->pdo->prepare(
                    "UPDATE nosql_documents
                     SET data = ?, updated_at = ?
                     WHERE collection = ? AND doc_id = ?"
                );
                $stmt->execute([
                    json_encode($updated, JSON_UNESCAPED_UNICODE),
                    $now,
                    $col,
                    $id,
                ]);

                return true; // on ne met à jour qu’un seul doc
            }
        }

        return false;
    }

    /* Insert si n’existe pas, sinon met à jour */
    
    public function upsertOne(string $col, array $filter, array $doc): array
    {
        $existing = $this->findOne($col, $filter);
        if ($existing) {
            unset($existing['_id'], $existing['_created_at']);
            $merged = array_merge($existing, $doc);
            $this->updateOne($col, $filter, $merged);
            return $merged;
        }

        return $this->insertOne($col, array_merge($filter, $doc));
    }

    /**
     * Supprime tous les docs qui correspondent au filtre
     */
    public function deleteMany(string $col, array $filter = []): int
    {
        if (empty($filter)) {
            $stmt = $this->pdo->prepare("DELETE FROM nosql_documents WHERE collection = ?");
            $stmt->execute([$col]);
            return $stmt->rowCount();
        }

        $docs = $this->fetchAll($col);
        $deleted = 0;

        $stmt = $this->pdo->prepare(
            "DELETE FROM nosql_documents WHERE collection = ? AND doc_id = ?"
        );

        foreach ($docs as $doc) {
            if ($this->matches($doc, $filter)) {
                $stmt->execute([$col, $doc['_id']]);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Compte les documents
     */
    public function count(string $col, array $filter = []): int
    {
        if (empty($filter)) {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM nosql_documents WHERE collection = ?"
            );
            $stmt->execute([$col]);
            return (int)$stmt->fetchColumn();
        }

        return count($this->find($col, $filter));
    }
}

/**
 * Synchronise les stats depuis MySQL vers NoSQLStore
 */
class StatsSync
{
    private PDO $pdo;
    private NoSQLStore $nosql;

    public function __construct(PDO $pdo, NoSQLStore $nosql)
    {
        $this->pdo   = $pdo;
        $this->nosql = $nosql;
    }

    /**
     * Calcule toutes les stats à partir de MySQL et les stocke dans NoSQL
     */
    public function syncAll(): void
    {
        // Vide les stats existantes
        $this->nosql->deleteMany('stats_menu');
        $this->nosql->deleteMany('stats_daily');

        // stats_menu : par menu
        $stmt = $this->pdo->query("
            SELECT
                ci.menu_id,
                ci.nom_menu,
                m.categorie,
                SUM(ci.quantite)                   AS nb_commandes,
                SUM(ci.quantite * ci.prix_unitaire) AS chiffre_affaires,
                AVG(ci.prix_unitaire)              AS prix_moyen,
                COUNT(DISTINCT ci.commande_id)     AS nb_commandes_distinctes,
                MIN(c.created_at)                  AS premiere_commande,
                MAX(c.created_at)                  AS derniere_commande
            FROM commande_items ci
            JOIN commandes c ON c.id = ci.commande_id
            LEFT JOIN menus m ON m.id = ci.menu_id
            WHERE c.statut NOT IN ('annulé')
            GROUP BY ci.menu_id, ci.nom_menu, m.categorie
            ORDER BY nb_commandes DESC
        ");

        while ($row = $stmt->fetch()) {
            $this->nosql->insertOne('stats_menu', [
                'menu_id'               => (int)$row['menu_id'],
                'nom_menu'              => $row['nom_menu'],
                'categorie'             => $row['categorie'] ?? 'N/A',
                'nb_commandes'          => (int)$row['nb_commandes'],
                'chiffre_affaires'      => (float)round($row['chiffre_affaires'], 2),
                'prix_moyen'            => (float)round($row['prix_moyen'], 2),
                'nb_commandes_distinctes' => (int)$row['nb_commandes_distinctes'],
                'premiere_commande'     => $row['premiere_commande'],
                'derniere_commande'     => $row['derniere_commande'],
            ]);
        }

        // stats_daily : par menu et par jour
        $stmt2 = $this->pdo->query("
            SELECT
                ci.menu_id,
                ci.nom_menu,
                DATE(c.created_at)                     AS jour,
                SUM(ci.quantite)                       AS nb_commandes,
                SUM(ci.quantite * ci.prix_unitaire)    AS chiffre_affaires
            FROM commande_items ci
            JOIN commandes c ON c.id = ci.commande_id
            WHERE c.statut NOT IN ('annulé')
            GROUP BY ci.menu_id, ci.nom_menu, jour
            ORDER BY jour DESC
        ");

        while ($row = $stmt2->fetch()) {
            $this->nosql->insertOne('stats_daily', [
                'menu_id'          => (int)$row['menu_id'],
                'nom_menu'         => $row['nom_menu'],
                'jour'             => $row['jour'],
                'nb_commandes'     => (int)$row['nb_commandes'],
                'chiffre_affaires' => (float)round($row['chiffre_affaires'], 2),
            ]);
        }
    }

    /**
     * Mise à jour incrémentale après une commande
     */
    public function syncOrder(int $commande_id): void
    {
        $stmt = $this->pdo->prepare("
            SELECT ci.*, c.created_at, c.statut
            FROM commande_items ci
            JOIN commandes c ON c.id = ci.commande_id
            WHERE ci.commande_id = ?
        ");
        $stmt->execute([$commande_id]);

        while ($item = $stmt->fetch()) {
            if ($item['statut'] === 'annulé') {
                continue;
            }

            $jour = date('Y-m-d', strtotime($item['created_at']));

            // stats_menu : mise à jour par menu
            $existing = $this->nosql->findOne('stats_menu', [
                'menu_id' => (int)$item['menu_id'],
            ]);

            $quantite  = (int)$item['quantite'];
            $caItem    = $quantite * $item['prix_unitaire'];

            if ($existing) {
                $this->nosql->updateOne('stats_menu', [
                    'menu_id' => (int)$item['menu_id'],
                ], [
                    'nb_commandes'      => $existing['nb_commandes'] + $quantite,
                    'chiffre_affaires'  => (float)round($existing['chiffre_affaires'] + $caItem, 2),
                    'derniere_commande' => $item['created_at'],
                ]);
            } else {
                $this->nosql->insertOne('stats_menu', [
                    'menu_id'           => (int)$item['menu_id'],
                    'nom_menu'          => $item['nom_menu'],
                    'nb_commandes'      => $quantite,
                    'chiffre_affaires'  => (float)round($caItem, 2),
                    'prix_moyen'        => (float)$item['prix_unitaire'],
                    'premiere_commande' => $item['created_at'],
                    'derniere_commande' => $item['created_at'],
                ]);
            }

            // ── stats_daily : par menu + jour ────────────
            $existingDay = $this->nosql->findOne('stats_daily', [
                'menu_id' => (int)$item['menu_id'],
                'jour'    => $jour,
            ]);

            if ($existingDay) {
                $this->nosql->updateOne('stats_daily', [
                    'menu_id' => (int)$item['menu_id'],
                    'jour'    => $jour,
                ], [
                    'nb_commandes'     => $existingDay['nb_commandes'] + $quantite,
                    'chiffre_affaires' => (float)round($existingDay['chiffre_affaires'] + $caItem, 2),
                ]);
            } else {
                $this->nosql->insertOne('stats_daily', [
                    'menu_id'          => (int)$item['menu_id'],
                    'nom_menu'         => $item['nom_menu'],
                    'jour'             => $jour,
                    'nb_commandes'     => $quantite,
                    'chiffre_affaires' => (float)round($caItem, 2),
                ]);
            }
        }
    }
}