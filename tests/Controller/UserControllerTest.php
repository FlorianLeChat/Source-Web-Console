<?php

//
// Tests du contrôleur de la page de l'espace utilisateur.
//
namespace App\Tests\Controller;

use App\Tests\TestHelperTrait;
use App\Tests\Mock\FakeMailer;
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
    use TestHelperTrait;

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
        $this->client = self::createClient();
        $this->container = self::getContainer();

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

        // Réinitialisation des services simulés.
        FakeMailer::reset();

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

        self::assertResponseIsSuccessful();

        // Récupération du lien de connexion et accès.
        $link = json_decode($this->client->getResponse()->getContent(), true)["link"];

        $this->client->request("GET", $link);
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        // Test de l'accès à la page d'accueil.
        $this->client->request("GET", "/");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains("header a", "Dashboard");

        // Test de l'accès à la page du compte utilisateur.
        $crawler = $this->client->request("GET", $this->router->generate("user_page"));

        self::assertResponseIsSuccessful();

        // Envoi d'une requête de déconnexion.
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_logout"), [
            "token" => $crawler->filter("input[data-action = logout]")->attr("data-token")
        ]);

        self::assertResponseIsSuccessful();

        // Accès de nouveau au lien de connexion.
        $this->client->request("GET", $link);

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        // Test de l'accès à la page d'accueil.
        //  Note : le lien n'est plus valide (usage unique).
        $this->client->request("GET", "/");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains("header button:first-of-type", "Register");
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

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Test de l'accès au tableau de bord (échec).
        //  Note : l'utilisateur n'est pas authentifié.
        $this->client->request("GET", $this->router->generate("dashboard_page"));

        self::assertResponseRedirects("/");

        // Envoi d'une deuxième requête de création de compte (réussite).
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_register"), [
            "token" => $crawler->filter("#register")->attr("data-token"),
            "username" => "florian4017",
            "password" => "florian4017",
            "server_address" => "123.123.123.123",
            "server_port" => "27015",
            "server_password" => "florian4016"
        ]);

        self::assertResponseIsSuccessful();

        // Test de l'accès au tableau de bord (réussite).
        $this->client->request("GET", $this->router->generate("dashboard_page"));

        self::assertResponseIsSuccessful();
    }

    //
    // Authentification à un compte utilisateur.
    //
    public function testAccountLogin(): void
    {
        // Accès à la page d'accueil.
        $this->router = self::getContainer()->get(UrlGeneratorInterface::class);
        $crawler = $this->client->request("GET", $this->router->generate("index_page"));

        // Envoi d'une première requête d'authentification (échec).
        //  Note : le mot de passe est incorrect.
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_login"), [
            "token" => $crawler->filter("#login")->attr("data-token"),
            "username" => "florian4016",
            "password" => "florian4017"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Envoi d'une deuxième requête d'authentification (réussite).
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_login"), [
            "token" => $crawler->filter("#login")->attr("data-token"),
            "username" => "florian4016",
            "password" => "florian4016"
        ]);

        self::assertResponseIsSuccessful();

        // Test de l'accès à la page du compte utilisateur.
        $crawler = $this->client->request("GET", $this->router->generate("user_page"));

        self::assertResponseIsSuccessful();

        // Envoi d'une requête de déconnexion.
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_logout"), [
            "token" => $crawler->filter("input[data-action = logout]")->attr("data-token")
        ]);

        self::assertResponseIsSuccessful();
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

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Envoi d'un deuxième message de contact (échec).
        //  Note : le serveur SMTP est injoignable.
        FakeMailer::$fail = true;

        $translator = $this->container->get(TranslatorInterface::class);

        $this->client->xmlHttpRequest("POST", $this->router->generate("user_contact"), [
            "token" => $crawler->filter("#contact")->attr("data-token"),
            "email" => "florian@gmail.com",
            "subject" => $translator->trans("form.contact.subject.1"),
            "content" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit,
                sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

        // Envoi d'un troisième message de contact (échec).
        //  Note : un seul message de contact autorisé par jour.
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_contact"), [
            "token" => $crawler->filter("#contact")->attr("data-token"),
            "email" => "florian@gmail.com",
            "subject" => $translator->trans("form.contact.subject.1"),
            "content" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit,
                sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);

        // Envoi d'un quatrième message de contact (réussite).
        //  Note : le serveur SMTP est de nouveau joignable.
        FakeMailer::$fail = false;

        $this->client->xmlHttpRequest("POST", $this->router->generate("user_contact"), [
            "token" => $crawler->filter("#contact")->attr("data-token"),
            "email" => "florian.trayon@gmail.com",
            "subject" => $translator->trans("form.contact.subject.2"),
            "content" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit,
                sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertCount(1, FakeMailer::$messages);

        // Envoi d'un message avec un jeton CSRF invalide.
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_contact"), [
            "token" => "invalid"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    //
    // Mis à jour des informations d'un compte utilisateur.
    //
    public function testAccountUpdate(): void
    {
        // Test de l'accès à la page du compte utilisateur.
        $crawler = $this->client->request("GET", $this->router->generate("user_page"));

        self::assertResponseIsSuccessful();

        // Envoi d'une requête de mise à jour.
        $this->client->xmlHttpRequest("PUT", $this->router->generate("user_update"), [
            "token" => $crawler->filter("input[data-action = update]")->attr("data-token"),
            "username" => "florian4018",
            "password" => "florian4018"
        ]);

        self::assertResponseIsSuccessful();

        // Envoi d'une requête de déconnexion.
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_logout"), [
            "token" => $crawler->filter("input[data-action = logout]")->attr("data-token")
        ]);

        self::assertResponseIsSuccessful();

        // Envoi d'une requête d'authentification.
        $crawler = $this->client->request("GET", "/");

        $this->client->xmlHttpRequest("POST", $this->router->generate("user_login"), [
            "token" => $crawler->filter("#login")->attr("data-token"),
            "username" => "florian4018",
            "password" => "florian4018"
        ]);

        self::assertResponseIsSuccessful();
    }

    //
    // Récupération du mot de passe d'un compte utilisateur.
    //
    public function testAccountPasswordRecover(): void
    {
        // Accès à la page d'accueil.
        $this->router = self::getContainer()->get(UrlGeneratorInterface::class);
        $crawler = $this->client->request("GET", $this->router->generate("index_page"));

        // Envoi d'une première requête de réinitialisation de mot de passe (échec).
        //  Note : le nom d'utilisateur n'existe pas.
        $this->client->xmlHttpRequest("PUT", $this->router->generate("user_recover"), [
            "token" => $crawler->filter("#login em[data-token]")->attr("data-token"),
            "username" => "florian4017",
            "password" => "florian4017"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Envoi d'une deuxième requête de réinitialisation de mot de passe (échec).
        //  Note : l'adresse IP enregistré ne correspond pas à celle de la requête.
        $this->client->xmlHttpRequest("PUT", $this->router->generate("user_recover"), [
            "token" => $crawler->filter("#login em[data-token]")->attr("data-token"),
            "username" => "florian4016",
            "password" => "florian4016"
        ], server: [
            // Modification de l'adresse IP de la requête.
            "REMOTE_ADDR" => "123.123.123.123"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Envoi d'une troisième requête de réinitialisation de mot de passe (réussite).
        $this->client->xmlHttpRequest("PUT", $this->router->generate("user_recover"), [
            "token" => $crawler->filter("#login em[data-token]")->attr("data-token"),
            "username" => "florian4016",
            "password" => "florian4017"
        ]);

        self::assertResponseIsSuccessful();

        // Envoi d'une requête d'authentification.
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_login"), [
            "token" => $crawler->filter("#login")->attr("data-token"),
            "username" => "florian4016",
            "password" => "florian4017"
        ]);

        self::assertResponseIsSuccessful();

        // Test de l'accès à la page du compte utilisateur.
        $crawler = $this->client->request("GET", $this->router->generate("user_page"));

        self::assertResponseIsSuccessful();
    }

    //
    // Suppression d'un compte utilisateur.
    //
    public function testAccountDeletion(): void
    {
        // Test de l'accès à la page du compte utilisateur.
        $crawler = $this->client->request("GET", $this->router->generate("user_page"));

        self::assertResponseIsSuccessful();

        // Envoi d'une requête de suppression de compte.
        $this->client->xmlHttpRequest("DELETE", $this->router->generate("user_remove"), [
            "token" => $crawler->filter("input[data-action = remove]")->attr("data-token")
        ]);

        self::assertResponseIsSuccessful();

        // Test de l'accès au tableau de bord.
        //  Note : l'utilisateur n'a plus de compte.
        $this->client->request("GET", $this->router->generate("dashboard_page"));

        self::assertResponseRedirects("/");
    }

    //
    // Ajout d'un nouveau serveur à un compte utilisateur.
    //
    public function testServerRegistration(): void
    {
        // Test de l'accès à la page du compte utilisateur.
        $crawler = $this->client->request("GET", $this->router->generate("user_page"));

        self::assertResponseIsSuccessful();

        // Envoi d'une requête d'ajout de serveur (échec).
        //  Note : un utilisateur standard ne peut pas ajouter plus de 3 serveurs.
        $this->client->xmlHttpRequest("POST", $this->router->generate("server_new"), [
            "token" => $crawler->filter("#register")->attr("data-token"),
            "server_address" => "123.123.123.123",
            "server_port" => "27015",
            "server_password" => "florian4016"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);

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

        self::assertResponseIsSuccessful();

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

            self::assertResponseIsSuccessful();
        }

        // Envoi d'une requête pour ajouter un 11ème serveur.
        //  Note : un utilisateur donateur ne peut pas ajouter plus de 10 serveurs.
        $this->client->xmlHttpRequest("POST", $route, [
            "token" => $token,
            "server_address" => "123.123.123.123",
            "server_port" => "27015",
            "server_password" => "florian4016"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
    }

    //
    // Refus des requêtes avec un jeton CSRF invalide.
    //
    public function testInvalidCsrfTokens(): void
    {
        $requests = [
            ["POST", "user_register"],
            ["POST", "user_login"],
            ["POST", "user_logout"],
            ["PUT", "user_update"],
            ["PUT", "user_recover"],
            ["DELETE", "user_remove"],
            ["POST", "server_new"]
        ];

        foreach ($requests as [$method, $route])
        {
            $this->client->xmlHttpRequest($method, $this->router->generate($route), ["token" => "invalid"]);

            self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, "Route: $route");
        }
    }

    //
    // Création d'un compte avec des identifiants invalides.
    //
    public function testRegistrationInvalidCredentials(): void
    {
        $this->client->request("GET", $this->router->generate("index_page"));
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_register"), [
            "token" => self::generateCsrfToken($this->client, "user_register"),
            "username" => "short",
            "password" => "short",
            "server_address" => "145.239.211.148",
            "server_port" => "27015"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertNull($this->container->get(UserRepository::class)->findOneBy(["username" => "short"]));
    }

    //
    // Authentification avec un mot de passe incorrect.
    //
    public function testAccountLoginInvalidCredentials(): void
    {
        $this->client->request("GET", $this->router->generate("index_page"));
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_login"), [
            "token" => self::generateCsrfToken($this->client, "user_login"),
            "username" => "florian4016",
            "password" => "florian4017"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    //
    // Mise à jour du hachage d'un mot de passe obsolète lors de l'authentification.
    //
    public function testAccountLoginPasswordRehash(): void
    {
        $repository = $this->container->get(UserRepository::class);
        $user = $repository->findOneBy(["username" => "florian4016"]);
        $user->setPassword($oldHash = password_hash("florian4016", PASSWORD_BCRYPT, ["cost" => 5]));
        $repository->save($user, true);

        $this->client->request("GET", $this->router->generate("index_page"));
        $this->client->xmlHttpRequest("POST", $this->router->generate("user_login"), [
            "token" => self::generateCsrfToken($this->client, "user_login"),
            "username" => "florian4016",
            "password" => "florian4016"
        ]);

        self::assertResponseIsSuccessful();

        $user = self::getContainer()->get(UserRepository::class)->findOneBy(["username" => "florian4016"]);

        self::assertNotSame($oldHash, $user->getPassword());
    }

    //
    // Authentification et récupération de compte avec des identifiants mal formés.
    //
    public function testMalformedCredentials(): void
    {
        $this->client->request("GET", $this->router->generate("index_page"));

        $requests = [
            ["POST", "user_login", "user_login"],
            ["PUT", "user_recover", "user_recover"]
        ];

        foreach ($requests as [$method, $route, $tokenId])
        {
            $this->client->xmlHttpRequest($method, $this->router->generate($route), [
                "token" => self::generateCsrfToken($this->client, $tokenId),
                "username" => "short",
                "password" => "short"
            ]);

            self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, "Route: $route");
        }
    }

    //
    // Mise à jour d'un compte avec des informations invalides.
    //
    public function testAccountUpdateInvalidData(): void
    {
        $this->client->request("GET", $this->router->generate("user_page"));
        $this->client->xmlHttpRequest("PUT", $this->router->generate("user_update"), [
            "token" => self::generateCsrfToken($this->client, "user_update"),
            "username" => "short",
            "password" => "short"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    //
    // Ajout d'un serveur avec des informations invalides.
    //
    public function testServerRegistrationInvalidData(): void
    {
        $repository = $this->container->get(UserRepository::class);
        $user = $repository->findOneBy(["username" => "florian4016"]);
        $user->setRoles(["ROLE_DONOR"]);
        $repository->save($user, true);

        $this->client->loginUser($user);
        $this->client->request("GET", $this->router->generate("user_page"));
        $this->client->xmlHttpRequest("POST", $this->router->generate("server_new"), [
            "token" => self::generateCsrfToken($this->client, "user_register"),
            "server_address" => "invalid",
            "server_port" => "27015"
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
