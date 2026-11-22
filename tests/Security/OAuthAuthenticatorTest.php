<?php

//
// Tests de l'authentificateur OAuth (Google et GitHub).
//
namespace App\Tests\Security;

use App\Entity\User;
use App\Tests\TestHelperTrait;
use App\Repository\UserRepository;
use App\Security\OAuthAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class OAuthAuthenticatorTest extends KernelTestCase
{
    use TestHelperTrait;

    //
    // Chargement des données de test.
    //
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        self::loadFixtures(self::$kernel->getProjectDir());
    }

    //
    // Création de l'authentificateur pour un utilisateur OAuth donné.
    //
    private function createAuthenticator(ResourceOwnerInterface $oauthUser): OAuthAuthenticator
    {
        $client = $this->createStub(OAuth2ClientInterface::class);
        $client->method("getAccessToken")->willReturn(new AccessToken(["access_token" => "token"]));
        $client->method("fetchUserFromToken")->willReturn($oauthUser);

        $registry = $this->createMock(ClientRegistry::class);
        $registry->expects($this->once())->method("getClient")->with("google")->willReturn($client);

        $container = self::getContainer();

        return new OAuthAuthenticator(
            $registry,
            $container->get(RouterInterface::class),
            $container->get(EntityManagerInterface::class),
            $container->get(UserPasswordHasherInterface::class)
        );
    }

    //
    // Création d'une requête de vérification OAuth.
    //
    private function createRequest(): Request
    {
        $request = Request::create("/oauth/google/check", "GET", [], [], [], ["REMOTE_ADDR" => "127.0.0.1"]);
        $request->attributes->set("_route", "user_oauth_check");
        $request->attributes->set("name", "google");

        return $request;
    }

    //
    // Détection des requêtes supportées.
    //
    public function testSupports(): void
    {
        $authenticator = new OAuthAuthenticator(
            $this->createStub(ClientRegistry::class),
            $this->createStub(RouterInterface::class),
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(UserPasswordHasherInterface::class)
        );

        self::assertTrue($authenticator->supports($this->createRequest()));
        self::assertFalse($authenticator->supports(Request::create("/dashboard")));
    }

    //
    // Création d'un nouveau compte à partir d'un compte OAuth.
    //
    public function testAuthenticateNewUser(): void
    {
        $oauthUser = new GoogleUser(["sub" => "oauth-123", "email" => "florian.oauth@gmail.com"]);
        $passport = $this->createAuthenticator($oauthUser)->authenticate($this->createRequest());

        /** @var User $user */
        $user = $passport->getUser();

        self::assertSame("florian.oauth", $user->getUsername());
        self::assertSame("oauth-123", $user->getToken());
        self::assertSame(["ROLE_USER"], $user->getRoles());
        self::assertNotNull($user->getId());
    }

    //
    // Connexion à un compte existant à partir d'un compte OAuth.
    //
    public function testAuthenticateExistingUser(): void
    {
        $repository = self::getContainer()->get(UserRepository::class);

        $existing = $repository->findOneBy(["username" => "florian4016"]);
        $existing->setToken("oauth-456");
        $repository->save($existing, true);

        $oauthUser = new GoogleUser(["sub" => "oauth-456", "email" => "florian4016@gmail.com"]);
        $passport = $this->createAuthenticator($oauthUser)->authenticate($this->createRequest());

        /** @var User $user */
        $user = $passport->getUser();

        self::assertSame($existing->getId(), $user->getId());
        self::assertSame("127.0.0.0", $user->getAddress());
    }

    //
    // Refus d'un fournisseur OAuth non supporté.
    //
    public function testAuthenticateUnsupportedProvider(): void
    {
        $oauthUser = new GenericResourceOwner(["id" => "oauth-789"], "id");
        $passport = $this->createAuthenticator($oauthUser)->authenticate($this->createRequest());

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage("Unsupported OAuth provider.");

        $passport->getUser();
    }

    //
    // Réponses après une authentification réussie ou échouée.
    //
    public function testAuthenticationResponses(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $router->method("generate")->willReturn("/user?oauth=1");

        $authenticator = new OAuthAuthenticator(
            $this->createStub(ClientRegistry::class),
            $router,
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(UserPasswordHasherInterface::class)
        );

        // Authentification réussie.
        $response = $authenticator->onAuthenticationSuccess(
            $this->createRequest(),
            $this->createStub(TokenInterface::class),
            "main"
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame("/user?oauth=1", $response->headers->get("Location"));

        // Authentification échouée.
        $response = $authenticator->onAuthenticationFailure(
            $this->createRequest(),
            new AuthenticationException("Error")
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame("An authentication exception occurred.", $response->getContent());
    }
}
