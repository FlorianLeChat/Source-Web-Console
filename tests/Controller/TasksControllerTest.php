<?php

//
// Tests du contrôleur de la page du tableau de bord.
//
namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Component\Process\Process;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TasksControllerTest extends WebTestCase
{
	// Client simplifié de navigation pour les tests.
	protected KernelBrowser $client;

	//
	// Réinitialisation de la base de données au début de chaque test
	//  afin de garantir un environnement de test propre.
	//
	protected function setUp(): void
	{
		// Appel de la méthode parente.
		parent::setUp();

		// Création du client.
		$this->client = static::createClient();

		// Exécution de la commande de réinitialisation.
		$php = new PhpExecutableFinder();
		$process = new Process([
			$php->find() ?? "php",
			sprintf("%s/bin/console", $this->client->getKernel()->getProjectDir()),
			"doctrine:fixtures:load",
			"--env=test",
			"--no-interaction"
		]);

		$process->disableOutput();
		$process->run();
	}

	//
	// Création réussie d'une tâche planifiée.
	//
	public function testAddTaskSuccess()
	{
		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $this->client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête d'authentification.
		$this->client->xmlHttpRequest("POST", $router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// Test de l'accès à la page des tâches planifiées.
		$crawler = $this->client->request("GET", $router->generate("tasks_page"));

		$this->assertResponseIsSuccessful();
	}
}