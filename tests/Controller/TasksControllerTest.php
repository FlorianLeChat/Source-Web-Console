<?php

//
// Tests du contrôleur de la page des tâches planifiées.
//
namespace App\Tests\Controller;

use App\Tests\TestHelperTrait;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TasksControllerTest extends WebTestCase
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

        // Réinitialisation des données de test.
        self::loadFixtures($this->client->getKernel()->getProjectDir());

        // Authentification de l'utilisateur.
        $repository = $this->container->get(UserRepository::class);

        $this->client->loginUser($repository->findOneBy(["username" => "florian4016"]));
    }

    //
    // Ajout d'une tâche planifiée.
    //
    public function testAddScheduledTasks(): void
    {
        // Test de l'accès à la page des tâches planifiées.
        $crawler = $this->client->request("GET", $this->router->generate("tasks_page"));

        self::assertResponseIsSuccessful();

        // Envoi de 5 requêtes d'ajout de tâches planifiées.
        //  Note : l'utilisateur atteint la limite autorisée (10).
        $date = date("c");
        $token = $crawler->filter("#tasks")->attr("data-token");
        $server = $crawler->filter("#server option:first-of-type")->attr("data-server");

        for ($i = 0; $i < 5; $i++)
        {
            $this->client->xmlHttpRequest("POST", $this->router->generate("tasks_add"), [
                "token" => $token,
                "date" => $date,
                "server" => $server,
                "action" => "restart"
            ]);

            self::assertResponseIsSuccessful();
        }

        // Envoi d'une requête pour ajouter une 11ème tâche planifiée.
        //  Note : un utilisateur ne peut pas ajouter plus de 10 tâches planifiées.
        $this->client->xmlHttpRequest("POST", $this->router->generate("tasks_add"), [
            "token" => $token,
            "date" => $date,
            "server" => $server,
            "action" => "service"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
    }

    //
    // Suppression d'une tâche planifiée.
    //
    public function testDeleteScheduledTasks(): void
    {
        // Test de l'accès à la page des tâches planifiées.
        $crawler = $this->client->request("GET", $this->router->generate("tasks_page"));

        self::assertResponseIsSuccessful();

        // Envoi d'une requête pour supprimer une tâche planifiée.
        $task = $crawler->filter("tbody tr:not([class = finished])")->first();

        $this->client->xmlHttpRequest("DELETE", $this->router->generate("tasks_remove"), [
            "token" => $crawler->filter("#tasks")->attr("data-token"),
            "task" => $task->attr("data-task"),
            "server" => $task->filter("em")->attr("data-server")
        ]);

        self::assertResponseIsSuccessful();
    }

    //
    // Échecs de l'ajout d'une tâche planifiée.
    //
    public function testAddScheduledTaskFailures(): void
    {
        $crawler = $this->client->request("GET", $this->router->generate("tasks_page"));
        $token = $crawler->filter("#tasks")->attr("data-token");
        $server = $crawler->filter("#server option:first-of-type")->attr("data-server");
        $route = $this->router->generate("tasks_add");

        // Jeton CSRF invalide.
        $this->client->xmlHttpRequest("POST", $route, ["token" => "invalid"]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Serveur inexistant ou n'appartenant pas à l'utilisateur.
        $this->client->xmlHttpRequest("POST", $route, [
            "token" => $token,
            "date" => date("c"),
            "server" => 999999,
            "action" => "restart"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Action invalide.
        $this->client->xmlHttpRequest("POST", $route, [
            "token" => $token,
            "date" => date("c"),
            "server" => $server,
            "action" => "invalid"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    //
    // Échecs de la suppression d'une tâche planifiée.
    //
    public function testDeleteScheduledTaskFailures(): void
    {
        $crawler = $this->client->request("GET", $this->router->generate("tasks_page"));
        $route = $this->router->generate("tasks_remove");

        // Jeton CSRF invalide.
        $this->client->xmlHttpRequest("DELETE", $route, ["token" => "invalid"]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Tâche inexistante.
        $this->client->xmlHttpRequest("DELETE", $route, [
            "token" => $crawler->filter("#tasks")->attr("data-token"),
            "task" => 999999,
            "server" => 999999
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
