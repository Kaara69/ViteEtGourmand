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

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM users
            WHERE id = ?
        ");

        $stmt->execute([$id]);

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

    public function updateProfile(
        int $id,
        string $nom,
        string $prenom,
        string $email,
        string $telephone,
        string $adresse
    ): void {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET nom=?, prenom=?, email=?, telephone=?, adresse=?
            WHERE id=?
        ");

        $stmt->execute([
            $nom,
            $prenom,
            $email,
            $telephone,
            $adresse,
            $id
        ]);
    }
    
    public function updateProfileWithPassword(
        int $id,
        string $nom,
        string $prenom,
        string $email,
        string $telephone,
        string $adresse,
        string $password
    ): void {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET nom=?, prenom=?, email=?, telephone=?, adresse=?, password=?
            WHERE id=?
        ");

        $stmt->execute([
            $nom,
            $prenom,
            $email,
            $telephone,
            $adresse,
            password_hash($password, PASSWORD_DEFAULT),
            $id
        ]);
    }
    public function countClients(): int
    {
        return (int)$this->pdo
            ->query("
                SELECT COUNT(*)
                FROM users
                WHERE role = 'client'
            ")
            ->fetchColumn();
    }
    
}