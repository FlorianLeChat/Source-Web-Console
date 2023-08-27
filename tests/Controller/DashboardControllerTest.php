<?php

//
// Tests du contrôleur de la page du tableau de bord.
//
namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DashboardControllerTest extends WebTestCase
{
	//
	// Récupération des traductions d'une langue.
	//
	public function testFetchTranslations()
	{
		// Initialisation du client et du conteneur de services.
		$client = static::createClient();
		$router = static::getContainer()->get(UrlGeneratorInterface::class);

		// Accès aux traductions anglaises.
		$client->request("GET", $router->generate("translations_page", [
			"language" => "en"
		]));

		$this->assertResponseIsSuccessful();

		// Accès aux traductions italiennes.
		//  Note : le fichier de traduction n'existe pas.
		$client->request("GET", $router->generate("translations_page", [
			"language" => "it"
		]));

		$this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
	}

	//
	// Surveillance des données d'un serveur distant.
	//
	public function testServerMonitor()
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

		// Test de l'accès à la page du tableau de bord.
		$crawler = $client->request("GET", $router->generate("dashboard_page"));

		$this->assertResponseIsSuccessful();

		// Test de surveillance du serveur valide par défaut.
		$client->request("GET", $router->generate("server_monitor"));

		$this->assertResponseIsSuccessful();
		$this->assertResponseHeaderSame("Content-Type", "application/json");

		// Changement du serveur surveillé pour un serveur invalide.
		$server = $crawler->filter("button[name = server_connect]")->last()->form();

		$client->click($server);

		// Test de surveillance du nouveau serveur invalide.
		//  Note : le serveur n'existe pas donc la surveillance échoue.
		$client->request("GET", $router->generate("server_monitor"));

		$this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
	}
}