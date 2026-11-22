<?php

//
// Tests du contrôleur de la page de configuration des informations de stockage.
//
namespace App\Tests\Controller;

use phpseclib3\Net\SFTP;
use App\Tests\TestHelperTrait;
use App\Repository\UserRepository;
use App\Repository\ServerRepository;
use App\Tests\Mock\FakeStorageManager;
use Symfony\Component\Process\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ConfigurationControllerTest extends WebTestCase
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
    // Mise à jour des informations de stockage.
    //
    public function testStorageUpdate(): void
    {
        // Test de l'accès à la page du tableau de bord.
        $crawler = $this->client->request("GET", $this->router->generate("dashboard_page"));

        self::assertResponseIsSuccessful();

        // Sélection du premier serveur enregistré dans la liste.
        $server = $crawler->filter("button[name = server_connect]")->first()->form();

        $this->client->click($server);

        // Test de l'accès à la page de configuration.
        $crawler = $this->client->request("GET", $this->router->generate("configuration_page"));

        self::assertResponseIsSuccessful();

        // Test de mise à jour des informations de stockage.
        $this->client->xmlHttpRequest("POST", $this->router->generate("storage_update"), [
            "token" => $crawler->filter("#storage")->attr("data-token"),
            "address" => "123.123.123.123",
            "port" => 22,
            "protocol" => "sftp",
            "username" => "florian4016",
            "password" => "florian4016"
        ]);

        self::assertResponseIsSuccessful();
    }

    //
    // Mise à jour de la configuration du serveur.
    //
    public function testConfigurationUpdate(): void
    {
        // Test de l'accès à la page du tableau de bord.
        $crawler = $this->client->request("GET", $this->router->generate("dashboard_page"));

        self::assertResponseIsSuccessful();

        // Sélection du premier serveur enregistré dans la liste.
        $server = $crawler->filter("button[name = server_connect]")->first()->form();

        $this->client->click($server);

        // Test de l'accès à la page de configuration.
        $crawler = $this->client->request("GET", $this->router->generate("configuration_page"));

        self::assertResponseIsSuccessful();

        // Test de mise à jour de la configuration du serveur (échec).
        //  Note : le serveur de stockage n'existe pas.
        $this->client->xmlHttpRequest("POST", $this->router->generate("configuration_update"), [
            "token" => $crawler->filter("button[data-type = loading]")->attr("data-token"),
            "type" => "loading",
            "path" => "/home/gmodserver/serverfiles/garrysmod/cfg/gmodserver.cfg",
            "value" => "http://localhost:3000/loading"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    //
    // Échecs de la mise à jour des informations de stockage.
    //
    public function testStorageUpdateFailures(): void
    {
        $route = $this->router->generate("storage_update");

        // Jeton CSRF invalide.
        $this->client->request("GET", $this->router->generate("configuration_page"));
        $this->client->xmlHttpRequest("POST", $route, ["token" => "invalid"]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Aucun serveur sélectionné.
        $token = self::generateCsrfToken($this->client, "storage_update");

        $this->client->xmlHttpRequest("POST", $route, ["token" => $token]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Protocole invalide.
        $server = $this->container->get(ServerRepository::class)->findOneBy(["address" => "123.123.123.0"]);

        self::selectServer($this->client, $server->getId());

        $this->client->xmlHttpRequest("POST", $route, [
            "token" => self::generateCsrfToken($this->client, "storage_update"),
            "protocol" => "http"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    //
    // Échecs de la mise à jour d'un fichier de configuration.
    //
    public function testConfigurationUpdateFailures(): void
    {
        $route = $this->router->generate("configuration_update");

        // Jeton CSRF invalide.
        $this->client->request("GET", $this->router->generate("configuration_page"));
        $this->client->xmlHttpRequest("POST", $route, ["token" => "invalid", "type" => "hostname"]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Connexion impossible au serveur de stockage.
        $server = $this->container->get(ServerRepository::class)->findOneBy(["address" => "123.123.123.0"]);

        self::selectServer($this->client, $server->getId());

        $this->client->xmlHttpRequest("POST", $route, [
            "token" => self::generateCsrfToken($this->client, "configuration_hostname"),
            "type" => "hostname"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    //
    // Liste des paramètres de configuration modifiables.
    //
    /** @return array<string, array{string, string}> */
    public static function configurationProvider(): array
    {
        return [
            "hostname" => ["hostname", "hostname \"New\""],
            "loading" => ["loading", "sv_loadingurl \"New\""],
            "password" => ["password", "rcon_password \"New\""],
            "unknown" => ["unknown", "hostname \"Old\"\nsv_loadingurl \"Old\"\nrcon_password \"Old\""]
        ];
    }

    //
    // Mise à jour réussie d'un fichier de configuration.
    //
    #[DataProvider("configurationProvider")]
    public function testConfigurationUpdateSuccess(string $type, string $expected): void
    {
        $content = "hostname \"Old\"\nsv_loadingurl \"Old\"\nrcon_password \"Old\"";
        $path = "/garrysmod/cfg/server.cfg";

        // Connexion simulée au serveur de stockage.
        $stream = $this->createMock(SFTP::class);
        $stream->expects($this->once())->method("get")->with($path)->willReturn($content);
        $stream->expects($this->once())->method("put")->with(
            $path,
            $this->stringContains($expected)
        );

        FakeStorageManager::$stream = $stream;

        // Sélection du serveur possédant un serveur de stockage.
        $server = $this->container->get(ServerRepository::class)->findOneBy(["address" => "123.123.123.0"]);

        self::selectServer($this->client, $server->getId());

        $this->client->xmlHttpRequest("POST", $this->router->generate("configuration_update"), [
            "token" => self::generateCsrfToken($this->client, "configuration_$type"),
            "type" => $type,
            "path" => $path,
            "value" => "New"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
}
