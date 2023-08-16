<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserControllerTest extends WebTestCase
{
	//
	// Test de création d'un compte utilisateur à usage unique.
	//
	public function testOneTimeAccountRegistration()
	{
		// Initialisation du client.
		$client = static::createClient();

		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête de création de compte.
		$client->xmlHttpRequest("POST", $router->generate("user_register"), [
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
		// Initialisation du client.
		$client = static::createClient();

		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête de création de compte.
		$client->xmlHttpRequest("POST", $router->generate("user_register"), [
			"token" => $crawler->filter("#register")->attr("data-token"),
			"username" => "florian4017",
			"password" => "florian4017",
			"server_address" => "123.123.123.123",
			"server_port" => "27015",
			"server_password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// Test de l'accès au tableau de bord.
		$client->request("GET", $router->generate("dashboard_page"));

		$this->assertResponseIsSuccessful();
	}

	//
	// Test de création invalide d'un compte utilisateur permanent.
	//
	public function testInvalidPermanentAccountRegistration()
	{
		// Initialisation du client.
		$client = static::createClient();

		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête de création de compte.
		//  Note : le nom d'utilisateur est déjà utilisé.
		$client->xmlHttpRequest("POST", $router->generate("user_register"), [
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
		$client->request("GET", $router->generate("dashboard_page"));

		$this->assertResponseRedirects("/");
	}

	//
	// Test d'une authentification réussie à un compte utilisateur.
	//
	public function testValidAccountLogin()
	{
		// Initialisation du client.
		$client = static::createClient();

		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête d'authentification.
		$client->xmlHttpRequest("POST", $router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// Test de l'accès à la page du compte utilisateur.
		$crawler = $client->request("GET", "/user");

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête de déconnexion.
		$client->xmlHttpRequest("POST", $router->generate("user_logout"), [
			"token" => $crawler->filter("input[data-action = logout]")->attr("data-token")
		]);

		$this->assertResponseIsSuccessful();
	}

	//
	// Test d'une authentification échouée à un compte utilisateur.
	//
	public function testInvalidAccountLogin()
	{
		// Initialisation du client.
		$client = static::createClient();

		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête d'authentification.
		//  Note : le mot de passe est incorrect.
		$client->xmlHttpRequest("POST", $router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4017"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

		// Envoi d'une requête de déconnexion.
		//  Note : l'utilisateur n'est pas authentifié.
		$client->xmlHttpRequest("POST", $router->generate("user_logout"));

		$this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
	}

	//
	// Test d'un envoi de message de contact valide.
	//
	public function testValidContactMessageSending()
	{
		// Initialisation du client.
		$client = static::createClient();
		$container = static::getContainer();

		// Accès à la page d'accueil.
		$router = $container->get(UrlGeneratorInterface::class);
		$crawler = $client->request("GET", $router->generate("index_page"));

		// Envoi d'un premier message de contact.
		//  Note : le serveur SMTP n'est pas renseigné.
		$translator = $container->get(TranslatorInterface::class);

		$client->xmlHttpRequest("POST", $router->generate("user_contact"), [
			"token" => $crawler->filter("#contact")->attr("data-token"),
			"email" => "florian@gmail.com",
			"subject" => $translator->trans("form.contact.subject.1"),
			"content" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

		// Envoi d'un deuxième message de contact.
		//  Note : un seul message de contact autorisé par jour.
		$client->xmlHttpRequest("POST", $router->generate("user_contact"), [
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
		// Initialisation du client.
		$client = static::createClient();

		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $client->request("GET", $router->generate("index_page"));

		// Envoi d'un message de contact.
		//  Note : les données envoyées ne respectent pas les contraintes.
		$client->xmlHttpRequest("POST", $router->generate("user_contact"), [
			"token" => $crawler->filter("#contact")->attr("data-token"),
			"email" => "florian",
			"subject" => "florian",
			"content" => "Lorem ipsum"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
	}

	//
	// Test d'une mise à jour des informations du compte utilisateur.
	//
	public function testAccountUpdate()
	{
		// Initialisation du client.
		$client = static::createClient();

		// Accès à la page d'accueil.
		$router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $client->request("GET", $router->generate("index_page"));

		// Envoi d'une requête d'authentification.
		$client->xmlHttpRequest("POST", $router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// Test de l'accès à la page du compte utilisateur.
		$crawler = $client->request("GET", $router->generate("user_page"));

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête de mise à jour.
		$client->xmlHttpRequest("PUT", $router->generate("user_update"), [
			"token" => $crawler->filter("input[data-action = update]")->attr("data-token"),
			"username" => "florian4018",
			"password" => "florian4018"
		]);

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête de déconnexion.
		$client->xmlHttpRequest("POST", $router->generate("user_logout"), [
			"token" => $crawler->filter("input[data-action = logout]")->attr("data-token")
		]);

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête d'authentification.
		$crawler = $client->request("GET", "/");

		$client->xmlHttpRequest("POST", $router->generate("user_login"), [
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