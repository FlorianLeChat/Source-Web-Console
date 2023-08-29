<?php

//
// Tests du contrôleur de la page des actions et des commandes.
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

final class ActionsControllerTest extends WebTestCase
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
	// Exécution d'une action à distance.
	//
	public function testServerAction(): void
	{
		// Test de l'accès à la page du tableau de bord.
		$crawler = $this->client->request("GET", $this->router->generate("dashboard_page"));

		$this->assertResponseIsSuccessful();

		// Sélection du premier serveur enregistré dans la liste.
		$server = $crawler->filter("button[name = server_connect]")->first()->form();

		$this->client->click($server);

		// Test de l'accès à la page des actions et des commandes.
		$crawler = $this->client->request("GET", $this->router->generate("actions_page"));

		$this->assertResponseIsSuccessful();

		// Test de redémarrage du serveur (échec).
		//  Note : le serveur ne possède pas de mot de passe administrateur.
		$this->client->xmlHttpRequest("POST", $this->router->generate("server_action"), [
			"token" => $crawler->filter("li[data-action = restart]")->attr("data-token"),
			"action" => "restart"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
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

		$this->assertResponseIsSuccessful();
	}

	//
	// Suppression d'une commande personnalisée.
	//
	public function testRemoveCustomCommand(): void
	{
		// Test de l'accès à la page des actions et des commandes.
		$crawler = $this->client->request("GET", $this->router->generate("actions_page"));

		$this->assertResponseIsSuccessful();

		// Test de suppression de la première commande personnalisée.
		$button = $crawler->filter("button[data-action = remove]")->first();

		$this->client->xmlHttpRequest("POST", $this->router->generate("command_remove"), [
			"token" => $button->attr("data-token"),
			"id" => $button->ancestors()->first()->attr("data-command"),
		]);

		$this->assertResponseIsSuccessful();
	}
}