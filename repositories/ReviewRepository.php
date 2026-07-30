<?php

class ReviewRepository
{
    public function __construct(private PDO $pdo) {}

    public function getLatestApproved(int $limit = 6): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM avis
            WHERE statut = 'approuvé'
            ORDER BY created_at DESC
            LIMIT :limite
        ");

        $stmt->bindValue(':limite', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(
        int $userId,
        string $nom,
        string $contenu,
        int $note
    ): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO avis
            (user_id, nom, contenu, note, statut)
            VALUES (?, ?, ?, ?, 'en attente')
        ");

        $stmt->execute([
            $userId,
            $nom,
            $contenu,
            $note
        ]);
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM avis
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // avis en attente
    public function getPending(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM avis
            WHERE statut = 'en attente'
            ORDER BY created_at DESC
            LIMIT :limite
        ");

        $stmt->bindValue(':limite', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function approve(int $id): void
{
    $stmt = $this->pdo->prepare("
        UPDATE avis
        SET statut='approuvé'
        WHERE id=?
    ");

    $stmt->execute([$id]);
}

public function reject(int $id): void
{
    $stmt = $this->pdo->prepare("
        UPDATE avis
        SET statut='refusé'
        WHERE id=?
    ");

    $stmt->execute([$id]);
}

public function delete(int $id): void
{
    $stmt = $this->pdo->prepare("
        DELETE FROM avis
        WHERE id=?
    ");

    $stmt->execute([$id]);
}
// compteurs
public function getCounts(): array
{
    $counts = [];

    $counts['tous'] = (int)$this->pdo
        ->query("SELECT COUNT(*) FROM avis")
        ->fetchColumn();

    foreach (['en attente', 'approuvé', 'refusé'] as $statut) {

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM avis
            WHERE statut = ?
        ");

        $stmt->execute([$statut]);

        $counts[$statut] = (int)$stmt->fetchColumn();
    }

    return $counts;
}
// ts les avis
public function getAllWithUsers(?string $statut = null): array
{
    $where = $statut ? "WHERE a.statut = ?" : "";

    $stmt = $this->pdo->prepare("
        SELECT
            a.*,
            u.email
        FROM avis a
        LEFT JOIN users u
            ON u.id = a.user_id
        $where
        ORDER BY a.created_at DESC
    ");

    if ($statut) {
        $stmt->execute([$statut]);
    } else {
        $stmt->execute();
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function countPending(): int
{
    $stmt = $this->pdo->prepare("
        SELECT COUNT(*)
        FROM avis
        WHERE statut = 'en attente'
    ");

    $stmt->execute();

    return (int)$stmt->fetchColumn();
}
}