<?php

//
// Tests du contrôleur de la page du tableau de bord.
//
namespace App\Tests\Controller;

use App\Entity\User;
use App\Service\ServerManager;
use App\Tests\TestHelperTrait;
use App\Repository\UserRepository;
use App\Tests\Mock\FakeSourceQuery;
use App\Repository\ServerRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DashboardControllerTest extends WebTestCase
{
    use TestHelperTrait;

    // Client de navigation.
    protected KernelBrowser $client;

    // Conteneur de services.
    protected ContainerInterface $container;

    // Générateur de routes.
    protected UrlGeneratorInterface $router;

    //
    // Authentification de l'utilisateur au début de chaque test
    //  afin de réduire la redondance de code.
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

        // Réinitialisation des données, des services simulés et du cache.
        self::loadFixtures($this->client->getKernel()->getProjectDir());
        self::resetFakeServices();

        $this->container->get("cache.app")->clear();

        // Authentification de l'utilisateur.
        $repository = $this->container->get(UserRepository::class);

        $this->client->loginUser($repository->findOneBy(["username" => "florian4016"]));
    }


    //
    // Récupération des traductions d'une langue.
    //
    public function testFetchTranslations(): void
    {
        // Accès aux traductions anglaises.
        $this->client->request("GET", $this->router->generate("translations_page", [
            "language" => "en"
        ]));

        self::assertResponseIsSuccessful();

        // Accès aux traductions italiennes.
        //  Note : le fichier de traduction n'existe pas.
        $this->client->request("GET", $this->router->generate("translations_page", [
            "language" => "it"
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    //
    // Surveillance des données d'un serveur distant.
    //
    public function testServerMonitor(): void
    {
        // Test de l'accès à la page du tableau de bord.
        $crawler = $this->client->request("GET", $this->router->generate("dashboard_page"));

        self::assertResponseIsSuccessful();

        // Test de surveillance du serveur valide par défaut.
        //  Note : le serveur simulé renvoie toujours des données valides.
        $this->client->request("GET", $this->router->generate("server_monitor"));

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame("json");

        // Changement du serveur surveillé pour un serveur invalide.
        $server = $crawler->filter("button[name = server_connect]")->last()->form();

        $this->client->click($server);

        // Test de surveillance du nouveau serveur invalide.
        //  Note : le serveur n'existe pas donc la surveillance échoue.
        $this->client->request("GET", $this->router->generate("server_monitor"));

        self::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    //
    // Modification et suppression d'un serveur depuis le tableau de bord.
    //
    public function testServerManagement(): void
    {

        $server = self::createServer("145.239.211.148");
        $route = $this->router->generate("dashboard_page");
        $repository = $this->container->get(ServerRepository::class);
        $serverManager = $this->container->get(ServerManager::class);

        // Jeton CSRF invalide.
        $this->client->request("GET", $route);
        $this->client->request("POST", $route, ["id" => $server->getId(), "token" => "invalid", "action" => "edit"]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Modification d'un serveur inexistant.
        $editToken = self::generateCsrfToken($this->client, "server_edit");

        $this->client->request("POST", $route, ["id" => 999999, "token" => $editToken, "action" => "edit"]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // Modification avec des informations invalides.
        $this->client->request("POST", $route, [
            "id" => $server->getId(),
            "token" => $editToken,
            "action" => "edit",
            "address" => "invalid"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Modification réussie.
        $this->client->request("POST", $route, [
            "id" => $server->getId(),
            "token" => $editToken,
            "action" => "edit",
            "address" => "10.0.0.4",
            "port" => "27016",
            "password" => "florian4017"
        ]);

        self::assertResponseIsSuccessful();

        $repository = self::getContainer()->get(ServerRepository::class);
        $updated = $repository->find($server->getId());

        self::assertSame("10.0.0.4", $updated->getAddress());
        self::assertSame(27016, $updated->getPort());
        self::assertSame(0, $updated->getGame());
        self::assertSame("florian4017", $serverManager->decryptPassword($updated->getPassword()));

        // Suppression d'un serveur inexistant.
        $deleteToken = self::generateCsrfToken($this->client, "server_delete");

        $this->client->request("POST", $route, ["id" => 999999, "token" => $deleteToken, "action" => "delete"]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // Suppression réussie.
        $this->client->request("POST", $route, [
            "id" => $server->getId(),
            "token" => $deleteToken,
            "action" => "delete"
        ]);

        self::assertResponseIsSuccessful();
        $repository = self::getContainer()->get(ServerRepository::class);

        self::assertNull($repository->find($server->getId()));
    }

    //
    // Surveillance réussie d'un serveur joignable.
    //
    public function testServerMonitorSuccess(): void
    {
        $server = self::createServer("145.239.211.148");

        self::selectServer($this->client, $server->getId());

        $this->client->request("GET", $this->router->generate("server_monitor"));

        self::assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame("gm_construct", $data["map"]);
        self::assertSame("2/16 [1]", $data["count"]);
        self::assertSame(FakeSourceQuery::DEFAULT_PLAYERS, $data["players"]);
    }

    //
    // Surveillance impossible sans serveur enregistré.
    //
    public function testServerMonitorWithoutServer(): void
    {
        $repository = $this->container->get(UserRepository::class);

        $user = new User();
        $user->setUsername(sprintf("user_%s", bin2hex(random_bytes(5))));
        $user->setPassword("password");
        $user->setCreatedAt(new \DateTime());
        $user->setAddress("127.0.0.1");
        $user->setRoles(["ROLE_USER"]);

        $repository->save($user, true);

        $this->client->loginUser($user);
        $this->client->request("GET", $this->router->generate("server_monitor"));

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $repository->remove($repository->find($user->getId()), true);
    }
}
