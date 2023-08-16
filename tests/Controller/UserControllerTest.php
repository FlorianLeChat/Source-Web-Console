<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UserControllerTest extends WebTestCase
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

		// Lancement de l'application console.
		$application = new Application($this->client->getKernel());
		$application->setAutoExit(false);

		// Exécution de la commande de réinitialisation.
		$application->run(new ArrayInput([
			"command" => "doctrine:fixtures:load",
			"--env" => "test",
			"--quiet" => true,
			"--no-interaction" => true
		]));
	}

	//
	// Test de création d'un compte utilisateur à usage unique.
	//
	public function testOneTimeAccountRegistration()
	{
		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $this->client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête de création de compte.
		$this->client->xmlHttpRequest("POST", $router->generate("user_register"), [
			"token" => $crawler->filter("#register")->attr("data-token"),
			"server_address" => "123.123.123.123",
			"server_port" => "27015",
			"server_password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// TODO : utiliser les formulaires HTML pour envoyer des requêtes.
		// TODO : vérifier que le lien de confirmation est accessible.
	}

	//
	// Test de création valide d'un compte utilisateur permanent.
	//
	public function testValidPermanentAccountRegistration()
	{
		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $this->client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête de création de compte.
		$this->client->xmlHttpRequest("POST", $router->generate("user_register"), [
			"token" => $crawler->filter("#register")->attr("data-token"),
			"username" => "florian4017",
			"password" => "florian4017",
			"server_address" => "123.123.123.123",
			"server_port" => "27015",
			"server_password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// Test de l'accès au tableau de bord.
		$this->client->request("GET", $router->generate("dashboard_page"));

		$this->assertResponseIsSuccessful();
	}

	//
	// Test de création invalide d'un compte utilisateur permanent.
	//
	public function testInvalidPermanentAccountRegistration()
	{
		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $this->client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête de création de compte.
		//  Note : le nom d'utilisateur est déjà utilisé.
		$this->client->xmlHttpRequest("POST", $router->generate("user_register"), [
			"token" => $crawler->filter("#register")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4016",
			"server_address" => "123.123.123.123",
			"server_port" => "27015",
			"server_password" => "florian4016"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

		// Test de l'accès au tableau de bord.
		//  Note : l'utilisateur n'est pas authentifié.
		$this->client->request("GET", $router->generate("dashboard_page"));

		$this->assertResponseRedirects("/");
	}

	//
	// Test d'une authentification réussie à un compte utilisateur.
	//
	public function testValidAccountLogin()
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

		// Test de l'accès à la page du compte utilisateur.
		$crawler = $this->client->request("GET", "/user");

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête de déconnexion.
		$this->client->xmlHttpRequest("POST", $router->generate("user_logout"), [
			"token" => $crawler->filter("input[data-action = logout]")->attr("data-token")
		]);

		$this->assertResponseIsSuccessful();
	}

	//
	// Test d'une authentification échouée à un compte utilisateur.
	//
	public function testInvalidAccountLogin()
	{
		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $this->client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête d'authentification.
		//  Note : le mot de passe est incorrect.
		$this->client->xmlHttpRequest("POST", $router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4017"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

		// Envoi d'une requête de déconnexion.
		//  Note : l'utilisateur n'est pas authentifié.
		$this->client->xmlHttpRequest("POST", $router->generate("user_logout"));

		$this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
	}

	//
	// Test d'un envoi de message de contact valide.
	//
	public function testValidContactMessageSending()
	{
		// Initialisation du conteneur de services.
		$container = static::getContainer();

		// Accès à la page d'accueil.
		$router = $container->get(UrlGeneratorInterface::class);
		$crawler = $this->client->request("GET", $router->generate("index_page"));

		// Envoi d'un premier message de contact.
		//  Note : le serveur SMTP n'est pas renseigné.
		$translator = $container->get(TranslatorInterface::class);

		$this->client->xmlHttpRequest("POST", $router->generate("user_contact"), [
			"token" => $crawler->filter("#contact")->attr("data-token"),
			"email" => "florian@gmail.com",
			"subject" => $translator->trans("form.contact.subject.1"),
			"content" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

		// Envoi d'un deuxième message de contact.
		//  Note : un seul message de contact autorisé par jour.
		$this->client->xmlHttpRequest("POST", $router->generate("user_contact"), [
			"token" => $crawler->filter("#contact")->attr("data-token"),
			"email" => "florian@gmail.com",
			"subject" => $translator->trans("form.contact.subject.1"),
			"content" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
	}

	//
	// Test d'un envoi de message de contact invalide.
	//
	public function testInvalidContactMessageSending()
	{
		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $this->client->request("GET", $router->generate("index_page"));

		// Envoi d'un message de contact.
		//  Note : les données envoyées ne respectent pas les contraintes.
		$this->client->xmlHttpRequest("POST", $router->generate("user_contact"), [
			"token" => $crawler->filter("#contact")->attr("data-token"),
			"email" => "florian",
			"subject" => "florian",
			"content" => "Lorem ipsum"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
	}

	//
	// Test de mise à jour des informations d'un compte utilisateur.
	//
	public function testAccountUpdate()
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

		// Test de l'accès à la page du compte utilisateur.
		$crawler = $this->client->request("GET", $router->generate("user_page"));

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête de mise à jour.
		$this->client->xmlHttpRequest("PUT", $router->generate("user_update"), [
			"token" => $crawler->filter("input[data-action = update]")->attr("data-token"),
			"username" => "florian4018",
			"password" => "florian4018"
		]);

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête de déconnexion.
		$this->client->xmlHttpRequest("POST", $router->generate("user_logout"), [
			"token" => $crawler->filter("input[data-action = logout]")->attr("data-token")
		]);

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête d'authentification.
		$crawler = $this->client->request("GET", "/");

		$this->client->xmlHttpRequest("POST", $router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4018",
			"password" => "florian4018"
		]);

		$this->assertResponseIsSuccessful();
	}

	// TODO : #[Route("/api/user/recover", name: "user_recover", methods: ["PUT"])]
	// TODO : #[Route("/api/user/remove", name: "user_remove", methods: ["DELETE"])]
	// TODO : #[Route("/api/server/new", name: "server_new", methods: ["POST"])]
}