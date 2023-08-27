<?php

//
// Tests du contrôleur de la page du tableau de bord.
//
namespace App\Tests\Controller;

use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DashboardControllerTest extends WebTestCase
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
			$php->find(),
			sprintf("%s/bin/console", $this->client->getKernel()->getProjectDir()),
			"doctrine:fixtures:load",
			"--env=test",
			"--no-interaction"
		]);

		$process->disableOutput();
		$process->run();
	}

	//
	// Récupération des traductions d'une langue.
	//
	public function testFetchTranslations()
	{
		// Accès aux traductions anglaises.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$this->client->request("GET", $router->generate("translations_page", [
			"language" => "en"
		]));

		$this->assertResponseIsSuccessful();

		// Accès aux traductions italiennes.
		//  Note : le fichier de traduction n'existe pas.
		$this->client->request("GET", $router->generate("translations_page", [
			"language" => "it"
		]));

		$this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
	}

	//
	// Surveillance réussie d'un serveur distant.
	//
	public function testServerMonitorSuccess()
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

		// Test de l'accès à la page du tableau de bord.
		$crawler = $this->client->request("GET", $router->generate("dashboard_page"));

		$this->assertResponseIsSuccessful();

		// Test de surveillance du serveur par défaut (valide).
		$this->client->request("GET", $router->generate("server_monitor"));

		$this->assertResponseIsSuccessful();
		$this->assertResponseHeaderSame("Content-Type", "application/json");

		// Changement du serveur surveillé pour un serveur invalide.
		$server = $crawler->filter("button[name = server_connect]")->last()->form();

		$this->client->click($server);

		// Test de surveillance du nouveau serveur (invalide).
		//  Note : le serveur n'existe pas donc la surveillance échoue.
		$this->client->request("GET", $router->generate("server_monitor"));

		$this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
	}
}