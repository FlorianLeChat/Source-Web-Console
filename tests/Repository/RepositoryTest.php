<?php

//
// Tests des méthodes d'enregistrement et de suppression des dépôts.
//
namespace App\Tests\Repository;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Event;
use App\Entity\Stats;
use App\Entity\Server;
use App\Entity\Command;
use App\Entity\Contact;
use App\Entity\Storage;
use App\Tests\TestHelperTrait;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use App\Repository\EventRepository;
use App\Repository\StatsRepository;
use App\Repository\ServerRepository;
use App\Repository\CommandRepository;
use App\Repository\ContactRepository;
use App\Repository\StorageRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RepositoryTest extends KernelTestCase
{
    use TestHelperTrait;

    // Gestionnaire des entités.
    private EntityManagerInterface $entityManager;

    //
    // Chargement des données de test une seule fois pour toute la classe.
    //
    public static function setUpBeforeClass(): void
    {
        self::bootKernel();
        self::loadFixtures(self::$kernel->getProjectDir());
        self::ensureKernelShutdown();
    }

    //
    // Récupération du gestionnaire des entités.
    //
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    //
    // Liste des entités testées.
    //
    /** @return array<string, array{class-string}> */
    public static function entityProvider(): array
    {
        return [
            "command" => [Command::class],
            "contact" => [Contact::class],
            "event" => [Event::class],
            "server" => [Server::class],
            "stats" => [Stats::class],
            "storage" => [Storage::class],
            "task" => [Task::class],
            "user" => [User::class]
        ];
    }

    //
    // Création d'une entité valide pour chaque type.
    //
    private function createEntity(string $class): object
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(["username" => "florian4016"]);
        $server = $this->entityManager->getRepository(Server::class)->findOneBy(["user" => $user]);

        return match ($class)
        {
            Command::class => (new Command())->setUser($user)->setTitle("Title")->setContent("say Hello"),
            Contact::class => (new Contact())->setDate(new \DateTime())->setEmail("florian@gmail.com")
                ->setSubject("Subject")->setContent("Content"),
            Event::class => (new Event())->setServer($server)->setDate(new \DateTime())->setAction("restart"),
            Server::class => (new Server())->setUser($user)->setAddress("123.123.123.200")
                ->setPort(27015)->setGame(4000),
            Stats::class => (new Stats())->setServer($server)->setDate(new \DateTime())->setPlayerCount(1)
                ->setCpuUsage(1.0)->setTickRate(66.0),
            Storage::class => (new Storage())->setServer(
                (new Server())->setUser($user)->setAddress("123.123.123.201")->setPort(27015)->setGame(4000)
            )->setAddress("123.123.123.123")->setPort(22)->setProtocol(Storage::PROTOCOL_SFTP)
                ->setUsername("florian4016"),
            Task::class => (new Task())->setServer($server)->setDate(new \DateTime())
                ->setAction(Server::ACTION_RESTART)
                ->setState(Task::STATE_WAITING),
            User::class => (new User())->setUsername(sprintf("user_%s", bin2hex(random_bytes(5))))
                ->setPassword("password")
                ->setCreatedAt(new \DateTime())->setAddress("127.0.0.1")->setRoles(["ROLE_USER"]),
            default => throw new \InvalidArgumentException("Unknown entity: $class")
        };
    }

    //
    // Enregistrement et suppression d'une entité avec et sans écriture immédiate.
    //
    #[DataProvider("entityProvider")]
    public function testSaveAndRemove(string $class): void
    {
        /**
         * @var CommandRepository|ContactRepository|EventRepository|ServerRepository
         *     |StatsRepository|StorageRepository|TaskRepository|UserRepository $repository
         */
        $repository = $this->entityManager->getRepository($class);

        $entity = $this->createEntity($class);

        if ($class === Storage::class)
        {
            // Le serveur associé au stockage doit exister au préalable.
            $this->entityManager->persist($entity->getServer());
        }

        // Enregistrement sans écriture immédiate.
        $repository->save($entity);

        self::assertNull($entity->getId());

        $this->entityManager->flush();

        self::assertNotNull($entity->getId());

        // Enregistrement avec écriture immédiate.
        $second = $this->createEntity($class);

        if ($class === Storage::class)
        {
            $this->entityManager->persist($second->getServer());
        }

        $repository->save($second, true);

        self::assertNotNull($second->getId());

        // Suppression sans écriture immédiate.
        $id = $entity->getId();
        $repository->remove($entity);

        self::assertNotNull($repository->find($id));

        $this->entityManager->flush();
        $this->entityManager->clear();

        self::assertNull($repository->find($id));

        // Suppression avec écriture immédiate.
        $id = $second->getId();
        $repository->remove($repository->find($id), true);

        self::assertNull($repository->find($id));
    }
}
