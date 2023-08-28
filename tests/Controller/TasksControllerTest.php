<?php

//
// Tests du contrôleur de la page des tâches planifiées.
//
namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TasksControllerTest extends WebTestCase
{
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
		$this->client = static::createClient();
		$this->container = static::getContainer();

		// Création des services.
		$this->router = $this->container->get(UrlGeneratorInterface::class);

		// Authentification de l'utilisateur.
		$repository = $this->container->get(UserRepository::class);

		$this->client->loginUser($repository->findOneBy(["username" => "florian4016"]));
	}

	//
	// Ajout d'une tâche planifiée.
	//
	public function testAddScheduledTasks()
	{
		// Test de l'accès à la page des tâches planifiées.
		$crawler = $this->client->request("GET", $this->router->generate("tasks_page"));

		$this->assertResponseIsSuccessful();

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

			$this->assertResponseIsSuccessful();
		}

		// Envoi d'une requête pour ajouter une 11ème tâche planifiée.
		//  Note : un utilisateur ne peut pas ajouter plus de 10 tâches planifiées.
		$this->client->xmlHttpRequest("POST", $this->router->generate("tasks_add"), [
			"token" => $token,
			"date" => $date,
			"server" => $server,
			"action" => "service"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
	}

	//
	// Suppression d'une tâche planifiée.
	//
	public function testDeleteScheduledTasks()
	{
		// Test de l'accès à la page des tâches planifiées.
		$crawler = $this->client->request("GET", $this->router->generate("tasks_page"));

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête pour supprimer une tâche planifiée.
		$task = $crawler->filter("tbody tr:not([class = finished])")->first();

		$this->client->xmlHttpRequest("DELETE", $this->router->generate("tasks_remove"), [
			"token" => $crawler->filter("#tasks")->attr("data-token"),
			"task" => $task->attr("data-task"),
			"server" => $task->filter("em")->attr("data-server")
		]);

		$this->assertResponseIsSuccessful();
	}
}