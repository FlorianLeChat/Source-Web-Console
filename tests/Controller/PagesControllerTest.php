<?php

//
// Tests des pages publiques et des redirections des pages protégées.
//
namespace App\Tests\Controller;

use App\Tests\TestHelperTrait;
use App\Controller\UserController;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PagesControllerTest extends WebTestCase
{
    use TestHelperTrait;

    // Client de navigation.
    private KernelBrowser $client;

    //
    // Chargement des données de test une seule fois pour toute la classe.
    //
    public static function setUpBeforeClass(): void
    {
        self::bootKernel();
        self::loadFixtures(self::$kernel->getProjectDir());
        self::ensureKernelShutdown();
    }

    //
    // Création du client de navigation.
    //
    protected function setUp(): void
    {
        parent::setUp();

        self::resetFakeServices();

        $this->client = self::createClient();
    }

    //
    // Liste des pages publiques.
    //
    /** @return array<string, array{string}> */
    public static function publicPageProvider(): array
    {
        return [
            "index" => ["/"],
            "legal" => ["/legal"],
            "help" => ["/help"]
        ];
    }

    //
    // Accès aux pages publiques.
    //
    #[DataProvider("publicPageProvider")]
    public function testPublicPages(string $url): void
    {
        $this->client->request("GET", $url);

        self::assertResponseIsSuccessful();
    }

    //
    // Liste des pages protégées.
    //
    /** @return array<string, array{string}> */
    public static function protectedPageProvider(): array
    {
        return [
            "dashboard" => ["/dashboard"],
            "statistics" => ["/statistics"],
            "configuration" => ["/configuration"],
            "actions" => ["/actions"],
            "console" => ["/console"],
            "tasks" => ["/tasks"],
            "user" => ["/user"]
        ];
    }

    //
    // Redirection des visiteurs non authentifiés vers la page d'accueil.
    //
    #[DataProvider("protectedPageProvider")]
    public function testProtectedPagesRedirect(string $url): void
    {
        $this->client->request("GET", $url);

        self::assertResponseRedirects("/");
    }

    //
    // Accès à la page des statistiques avec et sans serveur sélectionné.
    //
    public function testStatisticsPage(): void
    {
        $repository = self::getContainer()->get(UserRepository::class);
        $user = $repository->findOneBy(["username" => "florian4016"]);

        $this->client->loginUser($user);

        // Aucun serveur sélectionné.
        $this->client->request("GET", "/statistics");

        self::assertResponseIsSuccessful();
        self::assertStringContainsString("window.time_data = [];", $this->client->getResponse()->getContent());

        // Sélection du serveur possédant des statistiques.
        $crawler = $this->client->request("GET", "/dashboard");
        $form = $crawler->filter("button[name = server_connect]")->eq(1)->form();

        $this->client->submit($form);

        $this->client->request("GET", "/statistics");

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString("window.time_data = [];", $this->client->getResponse()->getContent());
    }

    //
    // Redirection vers le fournisseur OAuth.
    //
    public function testOAuthConnect(): void
    {
        $this->client->request("GET", "/oauth/github/connect");

        self::assertResponseRedirects();
        self::assertStringStartsWith(
            "https://github.com/login/oauth/authorize",
            $this->client->getResponse()->headers->get("Location")
        );

        $this->client->request("GET", "/oauth/google/connect");

        self::assertResponseRedirects();
        self::assertStringStartsWith(
            "https://accounts.google.com/",
            $this->client->getResponse()->headers->get("Location")
        );
    }

    //
    // Routes de vérification interceptées par le pare-feu.
    //  Note : les authentificateurs interceptent ces routes, les actions du contrôleur
    //   sont donc appelées directement.
    //
    public function testInterceptedRoutes(): void
    {
        $this->client->request("GET", "/");

        $controller = self::getContainer()->get(UserController::class);

        self::assertSame(Response::HTTP_FOUND, $controller->oneTime()->getStatusCode());
        self::assertSame(Response::HTTP_FOUND, $controller->oAuthCheck()->getStatusCode());
    }
}
