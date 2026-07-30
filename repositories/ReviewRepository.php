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
}