<?php

//
// Tests du contrôleur de la page des tâches planifiées.
//
namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TasksControllerTest extends WebTestCase
{
	//
	// Ajout d'une tâche planifiée.
	//
	public function testAddScheduledTasks()
	{
		// Initialisation du client et du conteneur de services.
		$client = static::createClient();
		$router = static::getContainer()->get(UrlGeneratorInterface::class);

		// Accès à la page d'accueil.
		$crawler = $client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête d'authentification.
		$client->xmlHttpRequest("POST", $router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// Test de l'accès à la page des tâches planifiées.
		$crawler = $client->request("GET", $router->generate("tasks_page"));

		$this->assertResponseIsSuccessful();

		// Envoi de 5 requêtes d'ajout de tâches planifiées.
		//  Note : l'utilisateur atteint la limite autorisée (10).
		$date = date("c");
		$token = $crawler->filter("#tasks")->attr("data-token");
		$server = $crawler->filter("#server option:first-of-type")->attr("data-server");

		for ($i = 0; $i < 5; $i++)
		{
			$client->xmlHttpRequest("POST", $router->generate("tasks_add"), [
				"token" => $token,
				"date" => $date,
				"server" => $server,
				"action" => "restart"
			]);

			$this->assertResponseIsSuccessful();
		}

		// Envoi d'une requête pour ajouter une 11ème tâche planifiée.
		//  Note : un utilisateur ne peut pas ajouter plus de 10 tâches planifiées.
		$client->xmlHttpRequest("POST", $router->generate("tasks_add"), [
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
		// Initialisation du client et du conteneur de services.
		$client = static::createClient();
		$router = static::getContainer()->get(UrlGeneratorInterface::class);

		// Accès à la page d'accueil.
		$crawler = $client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête d'authentification.
		$client->xmlHttpRequest("POST", $router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// Test de l'accès à la page des tâches planifiées.
		$crawler = $client->request("GET", $router->generate("tasks_page"));

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête pour supprimer une tâche planifiée.
		$task = $crawler->filter("tbody tr:not([class = finished])")->first();

		$client->xmlHttpRequest("DELETE", $router->generate("tasks_remove"), [
			"token" => $crawler->filter("#tasks")->attr("data-token"),
			"task" => $task->attr("data-task"),
			"server" => $task->filter("em")->attr("data-server")
		]);

		$this->assertResponseIsSuccessful();
	}
}