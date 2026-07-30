<?php

class OrderRepository
{
    public function __construct(private PDO $pdo) {}

    public function getLatestByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM commandes
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM commandes
            WHERE user_id = ?
        ");

        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO commandes
            (
                user_id,
                total,
                notes,
                nb_personnes,
                adresse_livraison,
                km_livraison,
                frais_livraison,
                remise,
                date_evenement,
                heure_evenement
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['user_id'],
            $data['total'],
            $data['notes'],
            $data['nb_personnes'],
            $data['adresse_livraison'],
            $data['km_livraison'],
            $data['frais_livraison'],
            $data['remise'],
            $data['date_evenement'],
            $data['heure_evenement']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function addItem(
        int $commandeId,
        array $item
): void
    {
    $stmt = $this->pdo->prepare("
        INSERT INTO commande_items
        (
            commande_id,
            menu_id,
            nom_menu,
            quantite,
            prix_unitaire,
            personnes_min
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $commandeId,
        $item['id'],
        $item['nom'],
        $item['qty'],
        $item['prix'],
        $item['personnes_min'] ?? 1
    ]);
    }
    public function getItems(int $commandeId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT nom_menu, quantite, prix_unitaire
            FROM commande_items
            WHERE commande_id = ?
        ");

        $stmt->execute([$commandeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}