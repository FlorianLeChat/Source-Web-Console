<?php

//
// Tests du contrôleur de la page de configuration des informations de stockage.
//
namespace App\Tests\Controller;

use App\Tests\TestHelperTrait;
use App\Repository\UserRepository;
use App\Tests\Mock\FakeSourceQuery;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ConsoleControllerTest extends WebTestCase
{
    use TestHelperTrait;

    // Client de navigation.
    protected KernelBrowser $client;

    // Conteneur de services.
    protected ContainerInterface $container;

    // Générateur de routes.
    protected UrlGeneratorInterface $router;

    //
    // Exécution de certaines actions au début de chaque test
    //  afin de garantir un environnement de test propre.
    //
    protected function setUp(): void
    {
        // Appel de la méthode parente.
        parent::setUp();

        // Création du client et du conteneur de services.
        $this->client = self::createClient();
        $this->container = self::getContainer();

        // Création des services.
        $this->router = $this->container->get(UrlGeneratorInterface::class);

        // Exécution de la commande de réinitialisation.
        $php = new PhpExecutableFinder();
        $process = new Process([
            $php->find(),
            sprintf("%s/bin/console", $this->client->getKernel()->getProjectDir()),
            "doctrine:fixtures:load",
            "--env=test",
            "--no-interaction"
        ]);

        $process->disableOutput();
        $process->run();

        // Réinitialisation des services simulés.
        self::resetFakeServices();

        // Authentification de l'utilisateur.
        $repository = $this->container->get(UserRepository::class);

        $this->client->loginUser($repository->findOneBy(["username" => "florian4016"]));
    }

    //
    // Surveillance de la console interactive.
    //
    public function testConsoleWatch(): void
    {
        // Test de l'accès à la page du tableau de bord.
        $crawler = $this->client->request("GET", $this->router->generate("dashboard_page"));

        self::assertResponseIsSuccessful();

        // Sélection du premier serveur enregistré dans la liste.
        $server = $crawler->filter("button[name = server_connect]")->first()->form();

        $this->client->click($server);

        // Test de l'accès à la page de la console interactive.
        $this->client->request("GET", $this->router->generate("console_page"));

        self::assertResponseIsSuccessful();

        // Test de mise à jour des informations de stockage.
        $this->client->xmlHttpRequest("GET", $this->router->generate("console_live"));

        self::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    //
    // Activation de la journalisation sur un serveur joignable.
    //
    public function testConsolePageWithReachableServer(): void
    {
        $server = self::createServer("145.239.211.148");

        self::selectServer($this->client, $server->getId());

        $this->client->request("GET", $this->router->generate("console_page"));

        self::assertResponseIsSuccessful();

        [$logCommand, $addressCommand] = FakeSourceQuery::$commands;

        self::assertSame("log on", $logCommand);
        self::assertStringStartsWith("logaddress_add ", $addressCommand);
        self::assertStringEndsWith(":8443", $addressCommand);
    }

    //
    // Échecs de la récupération des journaux de la console.
    //
    public function testConsoleLiveFailures(): void
    {
        $route = $this->router->generate("console_live");

        // Aucun serveur sélectionné.
        $this->client->xmlHttpRequest("GET", $route);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Serveur inexistant ou n'appartenant pas à l'utilisateur.
        self::selectServer($this->client, 999999);

        $this->client->xmlHttpRequest("GET", $route);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Serveur injoignable.
        $server = self::createServer("123.123.123.100");

        self::selectServer($this->client, $server->getId());

        $this->client->xmlHttpRequest("GET", $route);

        self::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    //
    // Récupération réussie des journaux de la console.
    //
    public function testConsoleLiveLogs(): void
    {
        $server = self::createServer("145.239.211.149", null);

        self::selectServer($this->client, $server->getId());

        // Création d'un fichier de journalisation de 60 lignes.
        $directory = sprintf("%s/145-239-211-149-27015", $this->client->getKernel()->getLogDir());
        $filesystem = new Filesystem();
        $filesystem->dumpFile(
            sprintf("%s/%s.log", $directory, date("Y-m-d")),
            implode("\n", array_map(fn ($i) => "Line $i", range(1, 60)))
        );

        try
        {
            $this->client->xmlHttpRequest("GET", $this->router->generate("console_live"));

            self::assertResponseIsSuccessful();

            // Seules les 50 dernières lignes sont renvoyées aux utilisateurs standards.
            $lines = json_decode($this->client->getResponse()->getContent(), true);

            self::assertCount(50, $lines);
            self::assertSame("Line 11", $lines[0]);
            self::assertSame("Line 60", $lines[49]);
        }
        finally
        {
            $filesystem->remove($directory);
        }
    }
}
