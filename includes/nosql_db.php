<?php

use MongoDB\Client;

class NoSQLStore
{
    private \MongoDB\Database $db;

    public function __construct()
    {
        $host   = getenv('MONGO_HOST') ?: 'localhost';
        $port   = getenv('MONGO_PORT') ?: '27017';
        $dbName = getenv('MONGO_DB')   ?: 'vite_gourmand';

        $client   = new Client("mongodb://{$host}:{$port}");
        $this->db = $client->selectDatabase($dbName);
    }

    private function newId(): string
    {
        return bin2hex(random_bytes(16));
    }

    // Convertit un objet BSON (retourné par le driver) en tableau PHP classique
    private function toArray($doc): array
    {
        if ($doc === null) {
            return [];
        }
        return json_decode(json_encode($doc), true);
    }

    // ── API publique (inchangée pour le reste du code) ──

    public function find(string $col, array $filter = []): array
    {
        $cursor = $this->db->selectCollection($col)->find($filter);
        $docs = [];
        foreach ($cursor as $doc) {
            $docs[] = $this->toArray($doc);
        }
        return $docs;
    }

    public function findOne(string $col, array $filter = []): ?array
    {
        $doc = $this->db->selectCollection($col)->findOne($filter);
        return $doc ? $this->toArray($doc) : null;
    }

    public function insertOne(string $col, array $doc): array
    {
        $doc['_id']         = $doc['_id'] ?? $this->newId();
        $doc['_created_at'] = date('Y-m-d H:i:s');

        $this->db->selectCollection($col)->insertOne($doc);

        return $doc;
    }

    public function updateOne(string $col, array $filter, array $update): bool
    {
        unset($update['_id']);
        $update['_updated_at'] = date('Y-m-d H:i:s');

        $result = $this->db->selectCollection($col)->updateOne(
            $filter,
            ['$set' => $update]
        );

        return $result->getMatchedCount() > 0;
    }

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

    public function deleteMany(string $col, array $filter = []): int
    {
        $result = $this->db->selectCollection($col)->deleteMany($filter);
        return $result->getDeletedCount();
    }

    public function count(string $col, array $filter = []): int
    {
        return $this->db->selectCollection($col)->countDocuments($filter);
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