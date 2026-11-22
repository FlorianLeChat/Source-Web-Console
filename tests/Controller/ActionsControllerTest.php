<?php

//
// Tests du contrôleur de la page des actions et des commandes.
//
namespace App\Tests\Controller;

use App\Entity\Event;
use App\Tests\TestHelperTrait;
use App\Repository\UserRepository;
use App\Tests\Mock\FakeSourceQuery;
use App\Repository\CommandRepository;
use Symfony\Component\Process\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ActionsControllerTest extends WebTestCase
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
    // Exécution d'une action à distance.
    //
    public function testServerAction(): void
    {
        // Test de l'accès à la page du tableau de bord.
        $crawler = $this->client->request("GET", $this->router->generate("dashboard_page"));

        self::assertResponseIsSuccessful();

        // Sélection du premier serveur enregistré dans la liste.
        $server = $crawler->filter("button[name = server_connect]")->first()->form();

        $this->client->click($server);

        // Test de l'accès à la page des actions et des commandes.
        $crawler = $this->client->request("GET", $this->router->generate("actions_page"));

        self::assertResponseIsSuccessful();

        // Test de redémarrage du serveur (échec).
        //  Note : le serveur ne possède pas de mot de passe administrateur.
        $this->client->xmlHttpRequest("POST", $this->router->generate("server_action"), [
            "token" => $crawler->filter("button[data-action = restart]")->attr("data-token"),
            "action" => "restart"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    //
    // Ajout d'une commande personnalisée.
    //
    public function testAddCustomCommand(): void
    {
        // Modification du rôle de l'utilisateur.
        $repository = $this->container->get(UserRepository::class);

        $user = $repository->findOneBy(["username" => "florian4016"]);
        $user->setRoles(["ROLE_DONOR"]);

        $repository->save($user, true);

        // Nouvelle authentification de l'utilisateur.
        $repository = $this->container->get(UserRepository::class);

        $this->client->loginUser($repository->findOneBy(["username" => "florian4016"]));

        // Test d'ajout d'une deuxième commande personnalisée (réussite).
        $crawler = $this->client->request("GET", $this->router->generate("actions_page"));

        $this->client->xmlHttpRequest("POST", $this->router->generate("command_add"), [
            "token" => $crawler->filter("li[data-action = add]")->attr("data-token"),
            "title" => "Command #2",
            "content" => "say Hello World!"
        ]);

        self::assertResponseIsSuccessful();
    }

    //
    // Suppression d'une commande personnalisée.
    //
    public function testRemoveCustomCommand(): void
    {
        // Test de l'accès à la page des actions et des commandes.
        $crawler = $this->client->request("GET", $this->router->generate("actions_page"));

        self::assertResponseIsSuccessful();

        // Test de suppression de la première commande personnalisée.
        $button = $crawler->filter("button[data-action = remove]")->first();

        $this->client->xmlHttpRequest("POST", $this->router->generate("command_remove"), [
            "token" => $button->attr("data-token"),
            "id" => $button->ancestors()->first()->attr("data-command"),
        ]);

        self::assertResponseIsSuccessful();
    }

    //
    // Liste des actions à distance et des commandes attendues.
    //
    /** @return array<string, array{string, string, string}> */
    public static function actionProvider(): array
    {
        return [
            "shutdown" => ["shutdown", "", "sv_shutdown"],
            "force" => ["force", "", "quit"],
            "restart" => ["restart", "", "_restart"],
            "update" => ["update", "", "svc_update"],
            "service" => ["service", "", "/^sv_password \"[a-f0-9]{30}\"$/"],
            "flashlight" => ["flashlight", "", "toggle mp_flashlight"],
            "cheats" => ["cheats", "", "toggle sv_cheats"],
            "voice" => ["voice", "", "toggle sv_voiceenable"],
            "console" => ["console", "status", "status"],
            "level" => ["level", "gm_flatgrass", "changelevel \"gm_flatgrass\""],
            "password" => ["password", "secret", "sv_password \"secret\""],
            "gravity" => ["gravity", "600", "sv_gravity \"600\""],
            "unknown" => ["999999", "status", "status"]
        ];
    }

    //
    // Exécution réussie d'une action à distance.
    //
    #[DataProvider("actionProvider")]
    public function testServerActionSuccess(string $action, string $value, string $expected): void
    {
        // Sélection d'un serveur joignable avec un mot de passe administrateur.
        $server = self::createServer("145.239.211.148");

        self::selectServer($this->client, $server->getId());

        // Envoi de l'action.
        $this->client->xmlHttpRequest("POST", $this->router->generate("server_action"), [
            "token" => self::generateCsrfToken($this->client, "server_$action"),
            "action" => $action,
            "value" => $value
        ]);

        self::assertResponseIsSuccessful();

        // Note : les commandes aléatoires sont vérifiées par une expression régulière.
        self::assertCount(1, FakeSourceQuery::$commands);
        self::assertMatchesRegularExpression(
            str_starts_with($expected, "/") ? $expected : sprintf("/^%s$/", preg_quote($expected, "/")),
            FakeSourceQuery::$commands[0]
        );

        // Vérification de la journalisation de l'action.
        $event = $this->container->get("doctrine")->getRepository(Event::class)->findOneBy(
            ["server" => $server->getId()],
            ["id" => "DESC"]
        );

        self::assertSame(is_numeric($action) ? "console" : $action, $event->getAction());
    }

    //
    // Exécution d'une commande personnalisée.
    //
    public function testCustomCommandExecution(): void
    {
        $server = self::createServer("145.239.211.148");

        self::selectServer($this->client, $server->getId());

        $command = $this->container->get(CommandRepository::class)->findOneBy([]);

        $this->client->xmlHttpRequest("POST", $this->router->generate("server_action"), [
            "token" => self::generateCsrfToken($this->client, "server_" . $command->getId()),
            "action" => $command->getId(),
            "value" => "Florian"
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame(["say Hello World! \"Florian\""], FakeSourceQuery::$commands);

        $event = $this->container->get("doctrine")->getRepository(Event::class)->findOneBy(
            ["server" => $server->getId()],
            ["id" => "DESC"]
        );

        self::assertSame("Command #1", $event->getAction());
    }

    //
    // Échecs de l'exécution d'une action à distance.
    //
    public function testServerActionFailures(): void
    {
        $route = $this->router->generate("server_action");

        // Jeton CSRF invalide.
        $this->client->xmlHttpRequest("POST", $route, ["token" => "invalid", "action" => "restart"]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Aucun serveur sélectionné.
        $this->client->xmlHttpRequest("POST", $route, [
            "token" => self::generateCsrfToken($this->client, "server_restart"),
            "action" => "restart"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Serveur inexistant ou n'appartenant pas à l'utilisateur.
        self::selectServer($this->client, 999999);

        $this->client->xmlHttpRequest("POST", $route, [
            "token" => self::generateCsrfToken($this->client, "server_restart"),
            "action" => "restart"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    //
    // Accès à la page des actions avec un serveur injoignable.
    //
    public function testActionsPageWithUnreachableServer(): void
    {
        $server = self::createServer("123.123.123.100");

        self::selectServer($this->client, $server->getId());

        $this->client->request("GET", $this->router->generate("actions_page"));

        self::assertResponseIsSuccessful();
    }

    //
    // Échecs de l'ajout d'une commande personnalisée.
    //
    public function testAddCustomCommandFailures(): void
    {
        $route = $this->router->generate("command_add");

        // Jeton CSRF invalide.
        $this->client->request("GET", $this->router->generate("actions_page"));
        $this->client->xmlHttpRequest("POST", $route, ["token" => "invalid"]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Nombre maximal de commandes atteint (utilisateur standard).
        $this->client->xmlHttpRequest("POST", $route, [
            "token" => self::generateCsrfToken($this->client, "command_add"),
            "title" => "Command #2",
            "content" => "say Hello World!"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);

        // Données invalides (utilisateur donateur).
        $repository = $this->container->get(UserRepository::class);
        $user = $repository->findOneBy(["username" => "florian4016"]);
        $user->setRoles(["ROLE_DONOR"]);
        $repository->save($user, true);

        $this->client->loginUser($user);
        $this->client->request("GET", $this->router->generate("actions_page"));
        $this->client->xmlHttpRequest("POST", $route, [
            "token" => self::generateCsrfToken($this->client, "command_add"),
            "title" => "",
            "content" => "say Hello World!"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertSame(1, $this->container->get(CommandRepository::class)->count([]));
    }

    //
    // Échecs de la suppression d'une commande personnalisée.
    //
    public function testRemoveCustomCommandFailures(): void
    {
        $route = $this->router->generate("command_remove");

        // Jeton CSRF invalide.
        $this->client->request("GET", $this->router->generate("actions_page"));
        $this->client->xmlHttpRequest("POST", $route, ["token" => "invalid"]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Commande inexistante.
        $this->client->xmlHttpRequest("POST", $route, [
            "token" => self::generateCsrfToken($this->client, "command_remove"),
            "id" => 999999
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertSame(1, $this->container->get(CommandRepository::class)->count([]));
    }
}
