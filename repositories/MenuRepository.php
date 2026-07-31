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
    // suppression
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM menus
            WHERE id = ?
        ");

        $stmt->execute([$id]);
    }

    // activer/desactiver
    public function toggleAvailability(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE menus
            SET disponible = CASE
                WHEN disponible = 1 THEN 0
                ELSE 1
            END
            WHERE id = ?
        ");

        $stmt->execute([$id]);
}

public function getAll(): array
{
    return $this->pdo
        ->query("
            SELECT *
            FROM menus
            ORDER BY categorie, nom
        ")
        ->fetchAll(PDO::FETCH_ASSOC);
}

public function findById(int $id): ?array
{
    $stmt = $this->pdo->prepare("
        SELECT *
        FROM menus
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $menu = $stmt->fetch(PDO::FETCH_ASSOC);

    return $menu ?: null;
}

public function create(
    string $nom,
    string $description,
    float $prix,
    string $categorie,
    int $disponible,
    string $imageUrl
): void {
    $stmt = $this->pdo->prepare("
        INSERT INTO menus
        (nom, description, prix, categorie, disponible, image_url)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $nom,
        $description,
        $prix,
        $categorie,
        $disponible,
        $imageUrl
    ]);
}

public function update(
    int $id,
    string $nom,
    string $description,
    float $prix,
    string $categorie,
    int $disponible,
    string $imageUrl
): void {
    $stmt = $this->pdo->prepare("
        UPDATE menus
        SET nom=?,
            description=?,
            prix=?,
            categorie=?,
            disponible=?,
            image_url=?
        WHERE id=?
    ");

    $stmt->execute([
        $nom,
        $description,
        $prix,
        $categorie,
        $disponible,
        $imageUrl,
        $id
    ]);
}

public function getCategories(): array
{
    return $this->pdo
        ->query("
            SELECT DISTINCT categorie
            FROM menus
            ORDER BY categorie
        ")
        ->fetchAll(PDO::FETCH_COLUMN);
}

// recup ancienne img si pas de nvl
public function getImageUrl(int $id): string
{
    $stmt = $this->pdo->prepare("
        SELECT image_url
        FROM menus
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    return $stmt->fetchColumn() ?: '';
}
}