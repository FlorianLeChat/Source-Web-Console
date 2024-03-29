<?php

//
// Tests du contrôleur de la page de l'espace utilisateur.
//
namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UserControllerTest extends WebTestCase
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
	// Création d'un compte à usage unique.
	//
	public function testOneTimeAccountRegistration(): void
	{
		// Accès à la page d'accueil.
		$crawler = $this->client->request("GET", $this->router->generate("index_page"));

		// Envoi d'une requête de création de compte.
		$this->client->xmlHttpRequest("POST", $this->router->generate("user_register"), [
			"token" => $crawler->filter("#register")->attr("data-token"),
			"server_address" => "123.123.123.123",
			"server_port" => "27015",
			"server_password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// Récupération du lien de connexion et accès.
		$link = json_decode($this->client->getResponse()->getContent(), true)["link"];

		$this->client->request("GET", $link);
		$this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

		// Test de l'accès à la page d'accueil.
		$this->client->request("GET", "/");

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains("header a", "Dashboard");

		// Test de l'accès à la page du compte utilisateur.
		$crawler = $this->client->request("GET", $this->router->generate("user_page"));

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête de déconnexion.
		$this->client->xmlHttpRequest("POST", $this->router->generate("user_logout"), [
			"token" => $crawler->filter("input[data-action = logout]")->attr("data-token")
		]);

		$this->assertResponseIsSuccessful();

		// Accès de nouveau au lien de connexion.
		$this->client->request("GET", $link);

		$this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

		// Test de l'accès à la page d'accueil.
		//  Note : le lien n'est plus valide (usage unique).
		$this->client->request("GET", "/");

		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains("header button:first-of-type", "Register");
	}

	//
	// Création d'un compte utilisateur permanent.
	//
	public function testPermanentAccountRegistration(): void
	{
		// Accès à la page du compte utilisateur pour déconnecter
		//  l'utilisateur actuellement authentifié.
		$crawler = $this->client->request("GET", $this->router->generate("user_page"));

		$this->client->xmlHttpRequest("POST", $this->router->generate("user_logout"), [
			"token" => $crawler->filter("input[data-action = logout]")->attr("data-token")
		]);

		// Accès à la page d'accueil.
		$crawler = $this->client->request("GET", $this->router->generate("index_page"));

		// Envoi d'une première requête de création de compte (échec).
		//  Note : le nom d'utilisateur est déjà utilisé.
		$this->client->xmlHttpRequest("POST", $this->router->generate("user_register"), [
			"token" => $crawler->filter("#register")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4016",
			"server_address" => "123.123.123.123",
			"server_port" => "27015",
			"server_password" => "florian4016"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

		// Test de l'accès au tableau de bord (échec).
		//  Note : l'utilisateur n'est pas authentifié.
		$this->client->request("GET", $this->router->generate("dashboard_page"));

		$this->assertResponseRedirects("/");

		// Envoi d'une deuxième requête de création de compte (réussite).
		$this->client->xmlHttpRequest("POST", $this->router->generate("user_register"), [
			"token" => $crawler->filter("#register")->attr("data-token"),
			"username" => "florian4017",
			"password" => "florian4017",
			"server_address" => "123.123.123.123",
			"server_port" => "27015",
			"server_password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// Test de l'accès au tableau de bord (réussite).
		$this->client->request("GET", $this->router->generate("dashboard_page"));

		$this->assertResponseIsSuccessful();
	}

	//
	// Authentification à un compte utilisateur.
	//
	public function testAccountLogin(): void
	{
		// Accès à la page d'accueil.
		$this->router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $this->client->request("GET", $this->router->generate("index_page"));

		// Envoi d'une première requête d'authentification (échec).
		//  Note : le mot de passe est incorrect.
		$this->client->xmlHttpRequest("POST", $this->router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4017"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

		// Envoi d'une deuxième requête d'authentification (réussite).
		$this->client->xmlHttpRequest("POST", $this->router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// Test de l'accès à la page du compte utilisateur.
		$crawler = $this->client->request("GET", $this->router->generate("user_page"));

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête de déconnexion.
		$this->client->xmlHttpRequest("POST", $this->router->generate("user_logout"), [
			"token" => $crawler->filter("input[data-action = logout]")->attr("data-token")
		]);

		$this->assertResponseIsSuccessful();
	}

	//
	// Envoi d'un message de contact aux administrateurs du site.
	//
	public function testContactMessageSending(): void
	{
		// Accès à la page d'accueil.
		$crawler = $this->client->request("GET", $this->router->generate("index_page"));

		// Envoi d'un premier message de contact (échec).
		//  Note : les données envoyées ne respectent pas les contraintes.
		$this->client->xmlHttpRequest("POST", $this->router->generate("user_contact"), [
			"token" => $crawler->filter("#contact")->attr("data-token"),
			"email" => "florian",
			"subject" => "florian",
			"content" => "Lorem ipsum"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

		// Envoi d'un deuxième message de contact (réussite).
		//  Note : le serveur SMTP n'est pas renseigné.
		$translator = $this->container->get(TranslatorInterface::class);

		$this->client->xmlHttpRequest("POST", $this->router->generate("user_contact"), [
			"token" => $crawler->filter("#contact")->attr("data-token"),
			"email" => "florian@gmail.com",
			"subject" => $translator->trans("form.contact.subject.1"),
			"content" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit,
				sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

		// Envoi d'un troisième message de contact (échec).
		//  Note : un seul message de contact autorisé par jour.
		$this->client->xmlHttpRequest("POST", $this->router->generate("user_contact"), [
			"token" => $crawler->filter("#contact")->attr("data-token"),
			"email" => "florian@gmail.com",
			"subject" => $translator->trans("form.contact.subject.1"),
			"content" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit,
				sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
	}

	//
	// Mis à jour des informations d'un compte utilisateur.
	//
	public function testAccountUpdate(): void
	{
		// Test de l'accès à la page du compte utilisateur.
		$crawler = $this->client->request("GET", $this->router->generate("user_page"));

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête de mise à jour.
		$this->client->xmlHttpRequest("PUT", $this->router->generate("user_update"), [
			"token" => $crawler->filter("input[data-action = update]")->attr("data-token"),
			"username" => "florian4018",
			"password" => "florian4018"
		]);

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête de déconnexion.
		$this->client->xmlHttpRequest("POST", $this->router->generate("user_logout"), [
			"token" => $crawler->filter("input[data-action = logout]")->attr("data-token")
		]);

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête d'authentification.
		$crawler = $this->client->request("GET", "/");

		$this->client->xmlHttpRequest("POST", $this->router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4018",
			"password" => "florian4018"
		]);

		$this->assertResponseIsSuccessful();
	}

	//
	// Récupération du mot de passe d'un compte utilisateur.
	//
	public function testAccountPasswordRecover(): void
	{
		// Accès à la page d'accueil.
		$this->router = static::getContainer()->get(UrlGeneratorInterface::class);
		$crawler = $this->client->request("GET", $this->router->generate("index_page"));

		// Envoi d'une première requête de réinitialisation de mot de passe (échec).
		//  Note : le nom d'utilisateur n'existe pas.
		$this->client->xmlHttpRequest("PUT", $this->router->generate("user_recover"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4017",
			"password" => "florian4017"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

		// Envoi d'une deuxième requête de réinitialisation de mot de passe (échec).
		//  Note : l'adresse IP enregistré ne correspond pas à celle de la requête.
		$this->client->xmlHttpRequest("PUT", $this->router->generate("user_recover"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4016"
		], server: [
			// Modification de l'adresse IP de la requête.
			"REMOTE_ADDR" => "123.123.123.123"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

		// Envoi d'une troisième requête de réinitialisation de mot de passe (réussite).
		$this->client->xmlHttpRequest("PUT", $this->router->generate("user_recover"), [
			"token" => $crawler->filter("#login em[data-token]")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4017"
		]);

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête d'authentification.
		$this->client->xmlHttpRequest("POST", $this->router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4017"
		]);

		$this->assertResponseIsSuccessful();

		// Test de l'accès à la page du compte utilisateur.
		$crawler = $this->client->request("GET", $this->router->generate("user_page"));

		$this->assertResponseIsSuccessful();
	}

	//
	// Suppression d'un compte utilisateur.
	//
	public function testAccountDeletion(): void
	{
		// Test de l'accès à la page du compte utilisateur.
		$crawler = $this->client->request("GET", $this->router->generate("user_page"));

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête de suppression de compte.
		$this->client->xmlHttpRequest("DELETE", $this->router->generate("user_remove"), [
			"token" => $crawler->filter("input[data-action = remove]")->attr("data-token")
		]);

		$this->assertResponseIsSuccessful();

		// Test de l'accès au tableau de bord.
		//  Note : l'utilisateur n'a plus de compte.
		$this->client->request("GET", $this->router->generate("dashboard_page"));

		$this->assertResponseRedirects("/");
	}

	//
	// Ajout d'un nouveau serveur à un compte utilisateur.
	//
	public function testServerRegistration(): void
	{
		// Test de l'accès à la page du compte utilisateur.
		$crawler = $this->client->request("GET", $this->router->generate("user_page"));

		$this->assertResponseIsSuccessful();

		// Envoi d'une requête d'ajout de serveur (échec).
		//  Note : un utilisateur standard ne peut pas ajouter plus de 3 serveurs.
		$this->client->xmlHttpRequest("POST", $this->router->generate("server_new"), [
			"token" => $crawler->filter("#register")->attr("data-token"),
			"server_address" => "123.123.123.123",
			"server_port" => "27015",
			"server_password" => "florian4016"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);

		// Modification du rôle de l'utilisateur.
		$repository = $this->container->get(UserRepository::class);

		$user = $repository->findOneBy(["username" => "florian4016"]);
		$user->setRoles(["ROLE_DONOR"]);

		$repository->save($user, true);

		// Retour à la page d'accueil avant une nouvelle authentification.
		$crawler = $this->client->request("GET", $this->router->generate("index_page"));

		$this->client->xmlHttpRequest("POST", $this->router->generate("user_login"), [
			"token" => $crawler->filter("#login")->attr("data-token"),
			"username" => "florian4016",
			"password" => "florian4016"
		]);

		$this->assertResponseIsSuccessful();

		// Envoi de 6 requêtes d'ajout de serveur.
		//  Note : l'utilisateur atteint la limite autorisée (10).
		$crawler = $this->client->request("GET", $this->router->generate("user_page"));
		$token = $crawler->filter("#register")->attr("data-token");
		$route = $this->router->generate("server_new");

		for ($i = 0; $i < 6; $i++)
		{
			$this->client->xmlHttpRequest("POST", $route, [
				"token" => $token,
				"server_address" => "123.123.123.$i",
				"server_port" => "27015",
				"server_password" => "florian4016"
			]);

			$this->assertResponseIsSuccessful();
		}

		// Envoi d'une requête pour ajouter un 11ème serveur.
		//  Note : un utilisateur donateur ne peut pas ajouter plus de 10 serveurs.
		$this->client->xmlHttpRequest("POST", $route, [
			"token" => $token,
			"server_address" => "123.123.123.123",
			"server_port" => "27015",
			"server_password" => "florian4016"
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
	}
}