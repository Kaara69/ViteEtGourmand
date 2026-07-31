<?php

class StatsRepository
{
    public function __construct(private PDO $pdo) {}

    public function getMenusList(): array
    {
        return $this->pdo
            ->query("
                SELECT id, nom
                FROM menus
                ORDER BY nom
            ")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countOrdersBetween(
        string $dateFrom,
        string $dateTo,
        ?int $menuId = null
    ): int {

        $sql = "
            SELECT COUNT(DISTINCT c.id)
            FROM commandes c
            JOIN commande_items ci
                ON ci.commande_id = c.id
            WHERE c.statut <> 'annulé'
              AND DATE(c.created_at) BETWEEN ? AND ?
        ";

        $params = [$dateFrom, $dateTo];

        if ($menuId !== null) {
            $sql .= " AND ci.menu_id = ?";
            $params[] = $menuId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getBestMenu(array $qtyByMenu): string
    {
        return empty($qtyByMenu)
            ? '—'
            : array_key_first($qtyByMenu);
    }
}