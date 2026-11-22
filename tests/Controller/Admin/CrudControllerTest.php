<?php

//
// Tests des contrôleurs de gestion des entités du panneau d'administration.
//
namespace App\Tests\Controller\Admin;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Event;
use App\Entity\Stats;
use App\Entity\Server;
use App\Entity\Command;
use App\Entity\Contact;
use App\Entity\Storage;
use PHPUnit\Framework\TestCase;
use App\Controller\Admin\TaskCrudController;
use App\Controller\Admin\UserCrudController;
use App\Controller\Admin\EventCrudController;
use App\Controller\Admin\StatsCrudController;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Controller\Admin\ServerCrudController;
use App\Controller\Admin\CommandCrudController;
use App\Controller\Admin\ContactCrudController;
use App\Controller\Admin\StorageCrudController;

final class CrudControllerTest extends TestCase
{
    //
    // Liste des contrôleurs et des entités associées.
    //
    /** @return array<string, array{class-string, class-string}> */
    public static function crudProvider(): array
    {
        return [
            "command" => [CommandCrudController::class, Command::class],
            "contact" => [ContactCrudController::class, Contact::class],
            "event" => [EventCrudController::class, Event::class],
            "server" => [ServerCrudController::class, Server::class],
            "stats" => [StatsCrudController::class, Stats::class],
            "storage" => [StorageCrudController::class, Storage::class],
            "task" => [TaskCrudController::class, Task::class],
            "user" => [UserCrudController::class, User::class]
        ];
    }

    //
    // Entité gérée par chaque contrôleur.
    //  Note : EasyAdmin lit cette valeur lors de la compilation du conteneur,
    //   elle n'est donc jamais appelée pendant les tests fonctionnels.
    //
    #[DataProvider("crudProvider")]
    public function testEntityFqcn(string $controller, string $entity): void
    {
        self::assertSame($entity, $controller::getEntityFqcn());
    }
}
