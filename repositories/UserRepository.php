<?php

class UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
    public function getEmailAndFirstname(int $id): ?array
    {
    $stmt = $this->pdo->prepare("
        SELECT email, prenom
        FROM users
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
    }
    public function getAddress(int $id): string
    {
        $stmt = $this->pdo->prepare("
            SELECT adresse
            FROM users
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        return $stmt->fetchColumn() ?: '';
    }
}