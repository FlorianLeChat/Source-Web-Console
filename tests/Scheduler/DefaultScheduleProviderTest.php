<?php

//
// Tests du fournisseur de planification par défaut.
//
namespace App\Tests\Scheduler;

use PHPUnit\Framework\TestCase;
use App\Scheduler\DefaultScheduleProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class DefaultScheduleProviderTest extends TestCase
{
    //
    // Création de la planification.
    //
    public function testGetSchedule(): void
    {
        $provider = new DefaultScheduleProvider(new ArrayAdapter());
        $schedule = $provider->getSchedule();

        self::assertNotNull($schedule->getState());
        self::assertTrue($schedule->shouldProcessOnlyLastMissedRun());
    }
}
