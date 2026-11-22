<?php

//
// Tests de l'extension et des fonctions Twig personnalisées.
//
namespace App\Tests\Twig;

use App\Twig\AppRuntime;
use App\Twig\AppExtension;
use App\Service\ServerManager;
use PHPUnit\Framework\TestCase;
use xPaw\SourceQuery\SourceQuery;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Translation\Translator;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;

final class AppRuntimeTest extends TestCase
{
    //
    // Création des fonctions Twig avec des dépendances simulées.
    //
    private function createRuntime(
        HttpClientInterface $client,
        ?TranslatorInterface $translator = null
    ): AppRuntime {
        $parameters = $this->createStub(ContainerBag::class);
        $parameters->method("get")->willReturn("passphrase");

        $serverManager = new ServerManager(new SourceQuery(), new ArrayAdapter(), $client, $parameters);

        if ($translator === null)
        {
            // Service de traduction simulé par défaut.
            $translator = $this->createStub(TranslatorInterface::class);
        }

        return new AppRuntime(
            $translator,
            $client,
            new ArrayAdapter(),
            $serverManager
        );
    }

    //
    // Création d'une réponse JSON simulée.
    //
    /** @param array<string, mixed> $body */
    private function createResponse(array $body, int $status = 200): MockResponse
    {
        return new MockResponse(json_encode($body), [
            "http_code" => $status,
            "response_headers" => ["content-type" => "application/json"]
        ]);
    }

    //
    // Déclaration des fonctions Twig personnalisées.
    //
    public function testExtensionFunctions(): void
    {
        $names = array_map(
            fn ($function) => $function->getName(),
            (new AppExtension())->getFunctions()
        );

        self::assertSame(["get_metadata", "get_languages", "get_language_name", "get_name_by_game_id"], $names);
    }

    //
    // Récupération des méta-données du site.
    //
    public function testGetMetadata(): void
    {
        $client = new MockHttpClient([
            $this->createResponse([
                "homepage" => "https://console.florian-dev.fr",
                "name" => "Source-Web-Console",
                "full_name" => "FlorianLeChat/Source-Web-Console",
                "html_url" => "https://github.com/FlorianLeChat/Source-Web-Console",
                "topics" => ["php", "symfony"],
                "description" => "Description"
            ]),
            $this->createResponse([
                "name" => "Florian Trayon",
                "html_url" => "https://github.com/FlorianLeChat",
                "twitter_username" => "FlorianLeChat"
            ]),
            $this->createResponse(["sha" => "abcdef"])
        ]);

        $metadata = $this->createRuntime($client)->getMetadata();

        self::assertSame("https://console.florian-dev.fr", $metadata["url"]);
        self::assertSame("Source-Web-Console", $metadata["title"]);
        self::assertSame(
            ["name" => "Florian Trayon", "url" => "https://github.com/FlorianLeChat"],
            $metadata["author"]
        );
        self::assertSame(
            "https://opengraph.githubassets.com/abcdef/FlorianLeChat/Source-Web-Console",
            $metadata["banner"]
        );
        self::assertSame("https://github.com/FlorianLeChat/Source-Web-Console", $metadata["source"]);
        self::assertSame("@FlorianLeChat", $metadata["twitter"]);
        self::assertSame("php,symfony", $metadata["keywords"]);
        self::assertSame("Description", $metadata["description"]);
    }

    //
    // Récupération des méta-données du site (erreur de l'API).
    //
    public function testGetMetadataApiError(): void
    {
        $client = new MockHttpClient([
            $this->createResponse([], 404),
            $this->createResponse([]),
            $this->createResponse([])
        ]);

        self::assertSame([], $this->createRuntime($client)->getMetadata());
    }

    //
    // Récupération des méta-données du site (réponses vides).
    //
    public function testGetMetadataEmptyResponses(): void
    {
        $client = new MockHttpClient([
            $this->createResponse([]),
            $this->createResponse([]),
            $this->createResponse([])
        ]);

        self::assertSame([], $this->createRuntime($client)->getMetadata());
    }

    //
    // Récupération des langues disponibles.
    //
    public function testGetLanguages(): void
    {
        // Service de traduction compatible.
        $translator = new Translator("en");
        $translator->setFallbackLocales(["en", "fr"]);

        self::assertSame(["en", "fr"], $this->createRuntime(new MockHttpClient(), $translator)->getLanguages());

        // Service de traduction incompatible.
        self::assertSame([], $this->createRuntime(new MockHttpClient())->getLanguages());
    }

    //
    // Récupération du nom d'une langue.
    //
    public function testGetLanguageName(): void
    {
        $runtime = $this->createRuntime(new MockHttpClient());

        self::assertSame(Languages::getName("fr"), $runtime->getLanguageName("fr"));
    }

    //
    // Récupération du nom d'un jeu.
    //
    public function testGetNameByGameID(): void
    {
        $client = new MockHttpClient([
            new MockResponse("<div id=\"appHubAppName\">Garry's Mod</div>"),
            new MockResponse("", ["http_code" => 404])
        ]);

        $runtime = $this->createRuntime($client);

        self::assertSame("Garry's Mod", $runtime->getNameByGameID(4000));
        self::assertSame("Unknown", $runtime->getNameByGameID(1, "Unknown"));
    }
}
