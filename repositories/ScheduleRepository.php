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
}