<?php

//
// Tests du contrôleur de la page de configuration des informations de stockage.
//
namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ConfigurationControllerTest extends WebTestCase
{
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
		$this->client = static::createClient();
		$this->container = static::getContainer();

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

		$this->assertResponseIsSuccessful();

		// Sélection du premier serveur enregistré dans la liste.
		$server = $crawler->filter("button[name = server_connect]")->first()->form();

		$this->client->click($server);

		// Test de l'accès à la page de configuration.
		$crawler = $this->client->request("GET", $this->router->generate("configuration_page"));

		$this->assertResponseIsSuccessful();

		// Test de mise à jour des informations de stockage.
		$this->client->xmlHttpRequest("POST", $this->router->generate("storage_update"), [
			"token" => $crawler->filter("#storage")->attr("data-token"),
			"address" => "123.123.123.123",
			"port" => 22,
			"protocol" => "sftp",
			"username" => "florian4016",
			"password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();
	}

	//
	// Mise à jour de la configuration du serveur.
	//
	public function testConfigurationUpdate(): void
	{
		// Test de l'accès à la page du tableau de bord.
		$crawler = $this->client->request("GET", $this->router->generate("dashboard_page"));

		$this->assertResponseIsSuccessful();

		// Sélection du premier serveur enregistré dans la liste.
		$server = $crawler->filter("button[name = server_connect]")->first()->form();

		$this->client->click($server);

		// Test de l'accès à la page de configuration.
		$crawler = $this->client->request("GET", $this->router->generate("configuration_page"));

		$this->assertResponseIsSuccessful();

		// Test de mise à jour de la configuration du serveur (échec).
		//  Note : le serveur de stockage n'existe pas.
		$this->client->xmlHttpRequest("POST", $this->router->generate("configuration_update"), [
			"token" => $crawler->filter("button[data-type = loading]")->attr("data-token"),
			"type" => "loading",
			"path" => "/home/gmodserver/serverfiles/garrysmod/cfg/gmodserver.cfg",
			"value" => "http://localhost:3000/loading"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
	}
}