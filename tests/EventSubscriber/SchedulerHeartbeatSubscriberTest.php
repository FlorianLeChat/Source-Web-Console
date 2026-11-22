<?php

//
// Tests de l'abonné de signal d'activité du planificateur de tâches.
//
namespace App\Tests\EventSubscriber;

use PHPUnit\Framework\TestCase;
use App\EventSubscriber\SchedulerHeartbeatSubscriber;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;

final class SchedulerHeartbeatSubscriberTest extends TestCase
{
    //
    // Événements écoutés par l'abonné.
    //
    public function testSubscribedEvents(): void
    {
        self::assertSame(
            [WorkerRunningEvent::class => "onWorkerRunning"],
            SchedulerHeartbeatSubscriber::getSubscribedEvents()
        );
    }

    //
    // Mise à jour du fichier de signal d'activité.
    //
    public function testWorkerRunning(): void
    {
        $file = sys_get_temp_dir() . "/scheduler-heartbeat-test";

        if (is_file($file))
        {
            unlink($file);
        }

        (new SchedulerHeartbeatSubscriber($file))->onWorkerRunning();

        self::assertFileExists($file);
        self::assertGreaterThanOrEqual(time() - 5, filemtime($file));

        unlink($file);
    }

    //
    // Emplacement par défaut du fichier de signal d'activité.
    //
    public function testDefaultFile(): void
    {
        $file = sys_get_temp_dir() . "/scheduler.heartbeat";

        (new SchedulerHeartbeatSubscriber())->onWorkerRunning();

        self::assertFileExists($file);
    }
}
