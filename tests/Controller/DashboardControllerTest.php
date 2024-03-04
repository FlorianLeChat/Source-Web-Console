<?php

//
// Tests du contrôleur de la page du tableau de bord.
//
namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DashboardControllerTest extends WebTestCase
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
	// Récupération des traductions d'une langue.
	//
	public function testFetchTranslations(): void
	{
		// Accès aux traductions anglaises.
		$this->client->request("GET", $this->router->generate("translations_page", [
			"language" => "en"
		]));

		$this->assertResponseIsSuccessful();

		// Accès aux traductions italiennes.
		//  Note : le fichier de traduction n'existe pas.
		$this->client->request("GET", $this->router->generate("translations_page", [
			"language" => "it"
		]));

		$this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
	}

	//
	// Surveillance des données d'un serveur distant.
	//
	public function testServerMonitor(): void
	{
		// Test de l'accès à la page du tableau de bord.
		$crawler = $this->client->request("GET", $this->router->generate("dashboard_page"));

		$this->assertResponseIsSuccessful();

		// Test de surveillance du serveur valide par défaut.
		//  Note : le serveur peut renvoyer des données JSON valides ou une erreur
		//   indiquant que les données sont partiellement incorrectes.
		$this->client->request("GET", $this->router->generate("server_monitor"));

		$response = $this->client->getResponse();

		if ($response->getStatusCode() == 200)
		{
			// Toutes les données sont valides et sont données sous format JSON.
			$this->assertResponseIsSuccessful();
			$this->assertResponseFormatSame("json");
		}
		else
		{
			// Certaines données sont invalides à cause du protocole Source RCON.
			$this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
			$this->assertStringContainsString("mismatch", $response->getContent());
		}

		// Changement du serveur surveillé pour un serveur invalide.
		$server = $crawler->filter("button[name = server_connect]")->last()->form();

		$this->client->click($server);

		// Test de surveillance du nouveau serveur invalide.
		//  Note : le serveur n'existe pas donc la surveillance échoue.
		$this->client->request("GET", $this->router->generate("server_monitor"));

		$this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
	}
}