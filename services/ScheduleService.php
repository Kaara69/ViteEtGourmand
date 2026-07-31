<?php

require_once __DIR__ . '/../repositories/ScheduleRepository.php';

class ScheduleService
{
    public function __construct(
        private ScheduleRepository $scheduleRepository
    ) {
    }

    public function save(array $horaires): string
    {
        foreach ($horaires as $jour => $data) {

            $ferme = isset($data['ferme']) ? 1 : 0;
            $ouverture = $ferme ? '00:00' : $data['ouverture'];
            $fermeture = $ferme ? '00:00' : $data['fermeture'];

            $this->scheduleRepository->update(
                $jour,
                $ouverture,
                $fermeture,
                $ferme
            );
        }

        return 'Horaires enregistrés avec succès.';
    }
}