<?php

//
// Fabrique de réponses HTTP simulées pour l'environnement de test.
//  Note : elle remplace les appels vers Steam, GitHub et les autres services externes.
//
namespace App\Tests\Mock;

use Symfony\Component\HttpClient\Response\MockResponse;

final class HttpClientResponseFactory
{
    // Noms des jeux connus par la boutique Steam simulée.
    public const GAMES = [
        240 => "Counter-Strike: Source",
        730 => "Counter-Strike 2",
        4000 => "Garry's Mod"
    ];

    // Code de statut HTTP forcé pour toutes les réponses (0 = désactivé).
    public static int $forcedStatus = 0;

    //
    // Réinitialisation de l'état partagé entre les tests.
    //
    public static function reset(): void
    {
        self::$forcedStatus = 0;
    }

    //
    // Création d'une réponse simulée en fonction de l'URL demandée.
    //
    public function __invoke(string $method, string $url): MockResponse
    {
        if (self::$forcedStatus !== 0)
        {
            return new MockResponse("", ["http_code" => self::$forcedStatus]);
        }

        // Boutique Steam (nom d'un jeu).
        if (preg_match("#^https://store\.steampowered\.com/app/(\d+)/#", $url, $matches))
        {
            return $this->getSteamStoreResponse((int) $matches[1]);
        }

        // API Steam (jeu monté sur un serveur).
        if (str_starts_with($url, "https://api.steampowered.com/ISteamApps/GetServersAtAddress/"))
        {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return $this->getSteamServerResponse((string) ($query["addr"] ?? ""));
        }

        // API GitHub (méta-données du site).
        if (str_starts_with($url, "https://api.github.com/"))
        {
            return $this->getGitHubResponse($url);
        }

        // Autres services (Have I Been Pwned, etc.).
        return new MockResponse("");
    }

    //
    // Réponse de la boutique Steam.
    //
    private function getSteamStoreResponse(int $identifier): MockResponse
    {
        if (isset(self::GAMES[$identifier]))
        {
            return new MockResponse(sprintf(
                "<html><body><div id=\"appHubAppName\">%s</div></body></html>",
                htmlspecialchars(self::GAMES[$identifier])
            ));
        }

        if ($identifier === 1)
        {
            // Page valide mais sans nom de jeu.
            return new MockResponse("<html><body></body></html>");
        }

        return new MockResponse("", ["http_code" => 404]);
    }

    //
    // Réponse de l'API Steam pour les serveurs à une adresse donnée.
    //  Note : l'adresse détermine le scénario simulé.
    //
    private function getSteamServerResponse(string $address): MockResponse
    {
        $body = match (true)
        {
            str_starts_with($address, "10.0.0.1:") => null,
            str_starts_with($address, "10.0.0.2:") => [],
            str_starts_with($address, "10.0.0.3:") => ["response" => ["success" => false, "servers" => []]],
            str_starts_with($address, "10.0.0.4:") => ["response" => ["success" => true, "servers" => []]],
            default => ["response" => ["success" => true, "servers" => [["appid" => 4000]]]]
        };

        if ($body === null)
        {
            return new MockResponse("", ["http_code" => 500]);
        }

        return new MockResponse(json_encode($body), ["response_headers" => ["content-type" => "application/json"]]);
    }

    //
    // Réponse de l'API GitHub.
    //
    private function getGitHubResponse(string $url): MockResponse
    {
        $body = match (true)
        {
            str_ends_with($url, "/commits/master") => ["sha" => "0123456789abcdef"],
            str_starts_with($url, "https://api.github.com/users/") => [
                "name" => "Florian Trayon",
                "html_url" => "https://github.com/FlorianLeChat",
                "twitter_username" => "FlorianLeChat"
            ],
            default => [
                "homepage" => "https://console.florian-dev.fr",
                "name" => "Source-Web-Console",
                "full_name" => "FlorianLeChat/Source-Web-Console",
                "html_url" => "https://github.com/FlorianLeChat/Source-Web-Console",
                "topics" => ["php", "symfony"],
                "description" => "Source Web Console"
            ]
        };

        return new MockResponse(json_encode($body), ["response_headers" => ["content-type" => "application/json"]]);
    }
}
