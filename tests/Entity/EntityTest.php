<?php

//
// Tests des accesseurs et mutateurs des entités.
//
namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Event;
use App\Entity\Stats;
use App\Entity\Server;
use App\Entity\Command;
use App\Entity\Contact;
use App\Entity\Storage;
use PHPUnit\Framework\TestCase;

final class EntityTest extends TestCase
{
    //
    // Entité des commandes personnalisées.
    //
    public function testCommand(): void
    {
        $user = new User();
        $command = new Command();

        self::assertNull($command->getId());
        self::assertSame($command, $command->setUser($user));
        self::assertSame($command, $command->setTitle("Command #1"));
        self::assertSame($command, $command->setContent("say Hello"));

        self::assertSame($user, $command->getUser());
        self::assertSame("Command #1", $command->getTitle());
        self::assertSame("say Hello", $command->getContent());
    }

    //
    // Entité des messages de contact.
    //
    public function testContact(): void
    {
        $date = new \DateTime();
        $contact = new Contact();

        self::assertNull($contact->getId());
        self::assertSame($contact, $contact->setDate($date));
        self::assertSame($contact, $contact->setEmail("florian@gmail.com"));
        self::assertSame($contact, $contact->setSubject("Subject"));
        self::assertSame($contact, $contact->setContent("Content"));

        self::assertSame($date, $contact->getDate());
        self::assertSame("florian@gmail.com", $contact->getEmail());
        self::assertSame("Subject", $contact->getSubject());
        self::assertSame("Content", $contact->getContent());
    }

    //
    // Entité des événements journalisés.
    //
    public function testEvent(): void
    {
        $date = new \DateTime();
        $server = new Server();
        $event = new Event();

        self::assertNull($event->getId());
        self::assertSame($event, $event->setServer($server));
        self::assertSame($event, $event->setDate($date));
        self::assertSame($event, $event->setAction("restart"));

        self::assertSame($server, $event->getServer());
        self::assertSame($date, $event->getDate());
        self::assertSame("restart", $event->getAction());
    }

    //
    // Entité des statistiques.
    //
    public function testStats(): void
    {
        $date = new \DateTime();
        $server = new Server();
        $stats = new Stats();

        self::assertNull($stats->getId());
        self::assertSame($stats, $stats->setServer($server));
        self::assertSame($stats, $stats->setDate($date));
        self::assertSame($stats, $stats->setPlayerCount(12));
        self::assertSame($stats, $stats->setCpuUsage(42.5));
        self::assertSame($stats, $stats->setTickRate(66.6));

        self::assertSame($server, $stats->getServer());
        self::assertSame($date, $stats->getDate());
        self::assertSame(12, $stats->getPlayerCount());
        self::assertSame(42.5, $stats->getCpuUsage());
        self::assertSame(66.6, $stats->getTickRate());
    }

    //
    // Entité des serveurs de stockage.
    //
    public function testStorage(): void
    {
        $server = new Server();
        $storage = new Storage();

        self::assertNull($storage->getId());
        self::assertSame($storage, $storage->setServer($server));
        self::assertSame($storage, $storage->setAddress("123.123.123.123"));
        self::assertSame($storage, $storage->setPort(22));
        self::assertSame($storage, $storage->setProtocol(Storage::PROTOCOL_SFTP));
        self::assertSame($storage, $storage->setUsername("florian4016"));
        self::assertSame($storage, $storage->setPassword("password"));

        self::assertSame($server, $storage->getServer());
        self::assertSame("123.123.123.123", $storage->getAddress());
        self::assertSame(22, $storage->getPort());
        self::assertSame(Storage::PROTOCOL_SFTP, $storage->getProtocol());
        self::assertSame("florian4016", $storage->getUsername());
        self::assertSame("password", $storage->getPassword());
    }

    //
    // Entité des tâches planifiées.
    //
    public function testTask(): void
    {
        $date = new \DateTime();
        $server = new Server();
        $task = new Task();

        self::assertNull($task->getId());
        self::assertSame($task, $task->setServer($server));
        self::assertSame($task, $task->setDate($date));
        self::assertSame($task, $task->setAction(Server::ACTION_RESTART));
        self::assertSame($task, $task->setState(Task::STATE_WAITING));

        self::assertSame($server, $task->getServer());
        self::assertSame($date, $task->getDate());
        self::assertSame(Server::ACTION_RESTART, $task->getAction());
        self::assertSame(Task::STATE_WAITING, $task->getState());
    }

    //
    // Entité des serveurs de jeu.
    //
    public function testServer(): void
    {
        $user = new User();
        $server = new Server();

        self::assertNull($server->getId());
        self::assertSame($server, $server->setUser($user));
        self::assertSame($server, $server->setAddress("123.123.123.123"));
        self::assertSame($server, $server->setPort(27015));
        self::assertSame($server, $server->setPassword("password"));
        self::assertSame($server, $server->setGame(4000));

        self::assertSame($user, $server->getUser());
        self::assertSame("123.123.123.123", $server->getAddress());
        self::assertSame(27015, $server->getPort());
        self::assertSame("password", $server->getPassword());
        self::assertSame(4000, $server->getGame());
        self::assertSame("[#] 123.123.123.123:27015", (string) $server);
    }

    //
    // Relations entre un serveur et ses tâches planifiées.
    //
    public function testServerTasks(): void
    {
        $server = new Server();
        $task = new Task();

        $server->addTask($task);
        $server->addTask($task);

        self::assertCount(1, $server->getTasks());
        self::assertSame($server, $task->getServer());

        $server->removeTask($task);

        self::assertCount(0, $server->getTasks());
        self::assertSame($server, $task->getServer());
    }

    //
    // Relations entre un serveur et ses événements journalisés.
    //
    public function testServerEvents(): void
    {
        $server = new Server();
        $event = new Event();

        $server->addEvent($event);
        $server->addEvent($event);

        self::assertCount(1, $server->getEvents());
        self::assertSame($server, $event->getServer());

        $server->removeEvent($event);

        self::assertCount(0, $server->getEvents());
        self::assertSame($server, $event->getServer());
    }

    //
    // Relations entre un serveur et ses statistiques.
    //
    public function testServerStats(): void
    {
        $server = new Server();
        $stats = new Stats();

        $server->addStat($stats);
        $server->addStat($stats);

        self::assertCount(1, $server->getStats());
        self::assertSame($server, $stats->getServer());

        $server->removeStat($stats);

        self::assertCount(0, $server->getStats());
        self::assertSame($server, $stats->getServer());
    }

    //
    // Relation entre un serveur et son serveur de stockage.
    //
    public function testServerStorage(): void
    {
        $server = new Server();
        $storage = new Storage();

        self::assertNull($server->getStorage());

        // Liaison d'un serveur de stockage sans serveur associé.
        $server->setStorage($storage);

        self::assertSame($storage, $server->getStorage());
        self::assertSame($server, $storage->getServer());

        // Liaison d'un serveur de stockage déjà associé.
        $server->setStorage($storage);

        self::assertSame($storage, $server->getStorage());
    }

    //
    // Entité des utilisateurs.
    //
    public function testUser(): void
    {
        $date = new \DateTime();
        $user = new User();

        self::assertNull($user->getId());
        self::assertSame($user, $user->setUsername("florian4016"));
        self::assertSame($user, $user->setPassword("password"));
        self::assertSame($user, $user->setCreatedAt($date));
        // Note : l'adresse IP est anonymisée.
        self::assertSame($user, $user->setAddress("127.0.0.1"));
        self::assertSame($user, $user->setToken("token"));
        self::assertSame($user, $user->setRoles(["ROLE_USER", "ROLE_USER", "ROLE_DONOR"]));

        self::assertSame("florian4016", $user->getUsername());
        self::assertSame("florian4016", $user->getUserIdentifier());
        self::assertSame("password", $user->getPassword());
        self::assertSame($date, $user->getCreatedAt());
        self::assertSame("127.0.0.0", $user->getAddress());
        self::assertSame("token", $user->getToken());
        self::assertSame(["ROLE_USER", "ROLE_DONOR"], array_values($user->getRoles()));
        self::assertSame("[#] florian4016", (string) $user);

        // Effacement des informations sensibles (aucun effet).
        $user->eraseCredentials();

        self::assertSame("password", $user->getPassword());
    }

    //
    // Relations entre un utilisateur et ses serveurs.
    //
    public function testUserServers(): void
    {
        $user = new User();
        $server = new Server();

        $user->addServer($server);
        $user->addServer($server);

        self::assertCount(1, $user->getServers());
        self::assertSame($user, $server->getUser());

        $user->removeServer($server);

        self::assertCount(0, $user->getServers());
        self::assertSame($user, $server->getUser());
    }

    //
    // Relations entre un utilisateur et ses commandes personnalisées.
    //
    public function testUserCommands(): void
    {
        $user = new User();
        $command = new Command();

        $user->addCommand($command);
        $user->addCommand($command);

        self::assertCount(1, $user->getCommands());
        self::assertSame($user, $command->getUser());

        $user->removeCommand($command);

        self::assertCount(0, $user->getCommands());
        self::assertSame($user, $command->getUser());
    }
}
