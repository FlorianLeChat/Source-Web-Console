<?php

//
// Planificateur des tâches récurrentes de l'application.
//
namespace App\Scheduler;

use Symfony\Component\Scheduler\Schedule;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule]
final class DefaultScheduleProvider implements ScheduleProviderInterface
{
    //
    // Initialisation de certaines dépendances du planificateur.
    //
    public function __construct(
        private readonly CacheInterface $cache
    ) {}

    //
    // Récupération de la planification des tâches.
    //
    public function getSchedule(): Schedule
    {
        // L'état est conservé dans le cache pour rattraper la dernière
        //  exécution manquée lors d'un redémarrage du service.
        return (new Schedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true);
    }
}
