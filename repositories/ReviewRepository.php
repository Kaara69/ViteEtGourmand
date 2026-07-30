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
}