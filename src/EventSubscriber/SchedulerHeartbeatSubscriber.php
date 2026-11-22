<?php

//
// Écouteur d'événements pour signaler l'activité du planificateur de tâches.
//  Le fichier mis à jour est surveillé par la vérification de santé du conteneur Docker.
//
namespace App\EventSubscriber;

use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class SchedulerHeartbeatSubscriber implements EventSubscriberInterface
{
    // Chemin vers le fichier de signal d'activité.
    private string $file;

    //
    // Initialisation de certaines dépendances de l'écouteur.
    //
    public function __construct(?string $file = null)
    {
        $this->file = $file ?? sys_get_temp_dir() . "/scheduler.heartbeat";
    }

    //
    // Mise à jour du fichier à chaque itération du processus.
    //
    public function onWorkerRunning(): void
    {
        touch($this->file);
    }

    //
    // Déclaration des écouteurs d'événements.
    //
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerRunningEvent::class => "onWorkerRunning"
        ];
    }
}
