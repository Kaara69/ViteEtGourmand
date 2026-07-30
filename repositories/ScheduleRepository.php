<?php

class ScheduleRepository
{
    public function __construct(private PDO $pdo) {}

    public function getAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT *
            FROM horaires
            ORDER BY id
        ");

        $horaires = [];

        while ($h = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $horaires[$h['jour']] = $h;
        }

        return $horaires;
    }

    public function getByDay(string $jour): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM horaires
            WHERE jour = ?
        ");

        $stmt->execute([$jour]);

        $horaire = $stmt->fetch(PDO::FETCH_ASSOC);

        return $horaire ?: null;
    }

    public function update(
        string $jour,
        string $ouverture,
        string $fermeture,
        int $ferme
    ): void {
        $stmt = $this->pdo->prepare("
            UPDATE horaires
            SET heure_ouverture = ?,
                heure_fermeture = ?,
                ferme = ?
            WHERE jour = ?
        ");

        $stmt->execute([
            $ouverture,
            $fermeture,
            $ferme,
            $jour
        ]);
    }
}