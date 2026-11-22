<?php

//
// Tests du panneau d'administration.
//
namespace App\Tests\Controller;

use App\Tests\TestHelperTrait;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminControllerTest extends WebTestCase
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
    // Authentification d'un administrateur au début de chaque test.
    //
    protected function setUp(): void
    {
        parent::setUp();

        self::resetFakeServices();

        $this->client = self::createClient();

        $repository = self::getContainer()->get(UserRepository::class);
        $user = $repository->findOneBy(["username" => "florian4016"]);
        $user->setRoles(["ROLE_ADMIN"]);
        $repository->save($user, true);

        $this->client->loginUser($user);
    }

    //
    // Accès au tableau de bord de l'administration.
    //
    public function testDashboard(): void
    {
        $this->client->request("GET", "/admin");

        self::assertResponseIsSuccessful();
        self::assertSelectorExists("a[href = 'https://console.florian-dev.fr']");
        self::assertSelectorExists("a[href = 'https://github.com/FlorianLeChat/Source-Web-Console']");
        self::assertSelectorTextContains("body", "florian4016");
    }

    //
    // Déconnexion depuis le menu utilisateur de l'administration.
    //
    public function testLogout(): void
    {
        $crawler = $this->client->request("GET", "/admin");
        $link = $crawler->filter("a[href *= '/admin/logout?_csrf_token=']");

        self::assertNotCount(0, $link);

        $this->client->click($link->first()->link());

        self::assertResponseRedirects("/");

        $this->client->request("GET", "/admin");

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    //
    // Refus de la déconnexion sans jeton CSRF valide.
    //
    public function testLogoutInvalidToken(): void
    {
        $this->client->request("GET", "/admin/logout?_csrf_token=invalid");

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    //
    // Accès refusé aux utilisateurs standards.
    //
    public function testAccessDenied(): void
    {
        $repository = self::getContainer()->get(UserRepository::class);
        $user = $repository->findOneBy(["username" => "florian4016"]);
        $user->setRoles(["ROLE_USER"]);
        $repository->save($user, true);

        $this->client->loginUser($user);
        $this->client->request("GET", "/admin");

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    //
    // Liste des pages de gestion des entités.
    //
    /** @return array<string, array{string}> */
    public static function crudProvider(): array
    {
        return [
            "command" => ["command"],
            "contact" => ["contact"],
            "event" => ["event"],
            "server" => ["server"],
            "stats" => ["stats"],
            "storage" => ["storage"],
            "task" => ["task"],
            "user" => ["user"]
        ];
    }

    //
    // Accès aux pages de liste et de création des entités.
    //
    #[DataProvider("crudProvider")]
    public function testCrudPages(string $entity): void
    {
        $this->client->request("GET", "/admin/$entity");

        self::assertResponseIsSuccessful();

        $this->client->request("GET", "/admin/$entity/new");

        self::assertResponseIsSuccessful();
    }
}
