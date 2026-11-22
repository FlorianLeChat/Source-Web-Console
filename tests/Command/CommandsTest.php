<?php

//
// Tests complémentaires des commandes Symfony.
//
namespace App\Tests\Command;

use App\Entity\Task;
use App\Entity\Stats;
use App\Entity\Server;
use App\Service\ServerManager;
use App\Tests\TestHelperTrait;
use App\Command\UdpServerCreator;
use App\Repository\UserRepository;
use App\Tests\Mock\FakeSourceQuery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;

final class CommandsTest extends KernelTestCase
{
    use TestHelperTrait;

    // Application de la console.
    private Application $application;

    // Gestionnaire des entités.
    private EntityManagerInterface $entityManager;

    // Dossiers et fichiers créés pendant les tests.
    /** @var string[] */
    private array $createdPaths = [];

    //
    // Chargement des données de test et des services requis.
    //
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        self::loadFixtures($kernel->getProjectDir());
        self::resetFakeServices();

        $this->application = new Application($kernel);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    //
    // Suppression des fichiers créés et réinitialisation des services simulés.
    //
    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->createdPaths);

        self::resetFakeServices();

        parent::tearDown();
    }

    //
    // Création d'un serveur joignable avec un mot de passe administrateur.
    //
    private function createReachableServer(): Server
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(["username" => "florian4016"]);

        $server = new Server();
        $server->setUser($user);
        $server->setAddress("145.239.211.148");
        $server->setPort(27015);
        $server->setGame(4000);
        $server->setPassword(self::getContainer()->get(ServerManager::class)->encryptPassword("florian4016"));

        $this->entityManager->persist($server);
        $this->entityManager->flush();

        return $server;
    }

    //
    // Exécution réussie des tâches planifiées.
    //
    public function testScheduledTasksExecution(): void
    {
        $server = $this->createReachableServer();
        $actions = [
            Server::ACTION_SHUTDOWN,
            Server::ACTION_RESTART,
            Server::ACTION_UPDATE,
            Server::ACTION_SERVICE,
            "flashlight"
        ];

        foreach ($actions as $action)
        {
            $task = new Task();
            $task->setServer($server);
            $task->setDate(new \DateTime("-1 minute"));
            $task->setAction($action);
            $task->setState(Task::STATE_WAITING);

            $this->entityManager->persist($task);
        }

        $this->entityManager->flush();

        $tester = new CommandTester($this->application->find("app:tasks-executor"));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();

        self::assertStringContainsString("Shutting down server...", $output);
        self::assertStringContainsString("Restarting server...", $output);
        self::assertStringContainsString("Updating server...", $output);
        self::assertStringContainsString("Locking server...", $output);
        self::assertStringContainsString("Unknown action, nothing to send.", $output);
        self::assertStringContainsString("[OK] Executed 5 task(s).", $output);

        // Chaque action n'envoie qu'une seule commande au serveur.
        self::assertCount(4, FakeSourceQuery::$commands);
        self::assertSame(["sv_shutdown", "_restart", "svc_update"], array_slice(FakeSourceQuery::$commands, 0, 3));
        self::assertStringStartsWith("sv_password \"", FakeSourceQuery::$commands[3]);

        // Les tâches exécutées sont marquées comme terminées.
        $this->entityManager->clear();

        self::assertSame(5, $this->entityManager->getRepository(Task::class)->count([
            "server" => $server->getId(), "state" => Task::STATE_FINISHED
        ]));
    }

    //
    // Collecte réussie des statistiques et suppression des anciennes données.
    //
    public function testStatisticsCollection(): void
    {
        // Passage de l'utilisateur en donateur.
        $repository = self::getContainer()->get(UserRepository::class);
        $user = $repository->findOneBy(["username" => "florian4016"]);
        $user->setRoles(["ROLE_DONOR"]);
        $repository->save($user, true);

        // Création d'anciennes statistiques.
        $server = $this->createReachableServer();

        foreach (["-2 days", "-4 days"] as $date)
        {
            $stats = new Stats();
            $stats->setServer($server);
            $stats->setDate(new \DateTime($date));
            $stats->setPlayerCount(1);
            $stats->setCpuUsage(1.0);
            $stats->setTickRate(1.0);

            $this->entityManager->persist($stats);
        }

        $this->entityManager->flush();

        // Réponse simulée de la commande « stats ».
        FakeSourceQuery::$rconResponse = "CPU In Out Uptime Map FPS 12 Players 34.5\nConnects a b c 66.6";

        $tester = new CommandTester($this->application->find("app:statistics-collector"));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();

        self::assertStringContainsString("Removing old statistics from server \"145.239.211.148\"", $output);
        self::assertStringContainsString("Skipping statistics deletion from server \"145.239.211.148\"", $output);
        self::assertStringContainsString("Gathering statistics from server \"145.239.211.148\"", $output);
        self::assertStringContainsString("[OK] Gathered statistics from 1 server(s).", $output);

        // Vérification des statistiques enregistrées.
        $this->entityManager->clear();

        $stats = $this->entityManager->getRepository(Stats::class)->findBy(
            ["server" => $server->getId()],
            ["date" => "DESC"]
        );

        self::assertCount(2, $stats);
        self::assertSame(12, $stats[0]->getPlayerCount());
        self::assertSame(34.5, $stats[0]->getCpuUsage());
        self::assertSame(66.6, $stats[0]->getTickRate());
    }

    //
    // Création réussie du serveur UDP sur un port aléatoire.
    //
    public function testUdpServerCreation(): void
    {
        $tester = new CommandTester($this->application->find("app:udp-server"));
        $tester->execute(["address" => "127.0.0.1:0"]);

        $tester->assertCommandIsSuccessful();

        self::assertStringContainsString("UDP server successfully created.", $tester->getDisplay());
    }

    //
    // Échec de la création du serveur UDP sur une adresse non locale.
    //
    public function testUdpServerCreationFailure(): void
    {
        $tester = new CommandTester($this->application->find("app:udp-server"));
        $tester->execute(["address" => "8.8.8.8:27015"]);

        self::assertStringContainsString("An error occurred while creating the UDP server", $tester->getDisplay());
    }

    //
    // Récupération de la commande de création du serveur UDP.
    //
    private function getUdpCommand(): UdpServerCreator
    {
        $command = $this->application->find("app:udp-server");

        $command = $command instanceof LazyCommand ? $command->getCommand() : $command;

        self::assertInstanceOf(UdpServerCreator::class, $command);

        return $command;
    }

    //
    // Traitement des messages reçus par le serveur UDP.
    //
    public function testUdpMessageHandling(): void
    {
        $command = $this->getUdpCommand();
        $output = new BufferedOutput();
        $io = new SymfonyStyle(new ArrayInput([]), $output);

        $logDir = self::$kernel->getLogDir();
        $path = sprintf("%s/145-239-211-147-27015", $logDir);
        $this->createdPaths[] = $path;

        // Message provenant d'un serveur inconnu.
        $command->handleMessage("\xFF\xFF\xFF\xFFRL Hello", "1.2.3.4:27015", $io);

        self::assertStringContainsString("New message from unknown remote server \"1.2.3.4:27015\"", $output->fetch());

        // Message provenant d'un serveur connu.
        $command->handleMessage("\xFF\xFF\xFF\xFFRL Hello World\0\r", "145.239.211.147:27015", $io);

        self::assertStringContainsString("New message from remote server \"145.239.211.147:27015\"", $output->fetch());
        self::assertSame("Hello World", file_get_contents(sprintf("%s/%s.log", $path, date("Y-m-d"))));

        // Suppression des anciens fichiers de journalisation.
        file_put_contents(sprintf("%s/2000-01-01.log", $path), "Old");

        $command->handleMessage("\xFF\xFF\xFF\xFFRL Hello", "145.239.211.147:27015", $io);

        self::assertCount(0, glob(sprintf("%s/*.log", $path)));
    }

    //
    // Erreur lors de l'enregistrement d'un message reçu par le serveur UDP.
    //
    public function testUdpMessageHandlingFailure(): void
    {
        $command = $this->getUdpCommand();
        $output = new BufferedOutput();
        $io = new SymfonyStyle(new ArrayInput([]), $output);

        // Création d'un fichier à la place du dossier de journalisation.
        $path = sprintf("%s/123-123-123-0-27015", self::$kernel->getLogDir());
        $this->createdPaths[] = $path;

        (new Filesystem())->dumpFile($path, "");

        $command->handleMessage("\xFF\xFF\xFF\xFFRL Hello", "123.123.123.0:27015", $io);

        self::assertStringContainsString("An error occurred while creating log directory", $output->fetch());
    }
}
