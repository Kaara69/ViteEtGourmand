<?php

class MenuRepository
{
    public function __construct(private PDO $pdo) {}

    public function getMaxPersonnes(): int
    {
        try {
            $max = (int) $this->pdo
                ->query("SELECT MAX(personnes_min) FROM menus WHERE disponible = 1")
                ->fetchColumn();

            return max(1, $max);
        } catch (Exception $e) {
            return 1;
        }
    }

    public function getAvailableMenus(): array
    {
        return $this->pdo
            ->query("
                SELECT *
                FROM menus
                WHERE disponible = 1
                ORDER BY categorie, nom
            ")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableById(int $id): ?array
    {
    $stmt = $this->pdo->prepare("
        SELECT id, nom, prix, personnes_min
        FROM menus
        WHERE id = ? AND disponible = 1
    ");

    $stmt->execute([$id]);

    $menu = $stmt->fetch(PDO::FETCH_ASSOC);

    return $menu ?: null;
    }
    public function getAllAvailable(): array
    {
        $stmt = $this->pdo->query("
            SELECT *
            FROM menus
            WHERE disponible = 1
            ORDER BY categorie, nom
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}