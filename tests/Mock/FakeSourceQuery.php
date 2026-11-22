<?php

//
// Faux client du protocole Source Query utilisé dans l'environnement de test.
//  Note : il remplace le client réel pour éviter toute dépendance réseau.
//
namespace App\Tests\Mock;

use xPaw\SourceQuery\SourceQuery;
use xPaw\SourceQuery\Exception\SocketException;

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
//  Note : les noms des méthodes surchargées sont imposés par la bibliothèque.
/**
 * @SuppressWarnings("PHPMD.CamelCaseMethodName")
 */
class FakeSourceQuery extends SourceQuery
{
    // Préfixe des adresses IP considérées comme injoignables.
    public const UNREACHABLE_PREFIX = "123.123.123.";

    // Informations renvoyées par défaut par le serveur.
    public const DEFAULT_INFO = [
        "Password" => false,
        "ModDesc" => "Sandbox",
        "Map" => "gm_construct",
        "Players" => 2,
        "MaxPlayers" => 16,
        "Bots" => 1
    ];

    // Liste des joueurs renvoyée par défaut par le serveur.
    public const DEFAULT_PLAYERS = [["Name" => "Florian", "Frags" => 10, "Time" => 60]];

    // Règles (variables) renvoyées par défaut par le serveur.
    public const DEFAULT_RULES = ["mp_flashlight" => "1", "sv_cheats" => "0", "sv_voiceenable" => "1"];

    // Informations renvoyées par le serveur.
    /** @var array<string, mixed> */
    public static array $info = self::DEFAULT_INFO;

    // Liste des joueurs renvoyée par le serveur.
    /** @var list<array<string, mixed>> */
    public static array $players = self::DEFAULT_PLAYERS;

    // Règles (variables) renvoyées par le serveur.
    /** @var array<string, string> */
    public static array $rules = self::DEFAULT_RULES;

    // Réponse renvoyée par le serveur après une commande RCON.
    public static string $rconResponse = "";

    // Historique des commandes RCON exécutées.
    /** @var string[] */
    public static array $commands = [];

    // Adresse IP du serveur actuellement connecté.
    private ?string $address = null;

    // Indique si le mot de passe administrateur a été défini.
    private bool $authenticated = false;

    //
    // Réinitialisation de l'état partagé entre les tests.
    //
    public static function reset(): void
    {
        self::$info = self::DEFAULT_INFO;
        self::$players = self::DEFAULT_PLAYERS;
        self::$rules = self::DEFAULT_RULES;
        self::$rconResponse = "";
        self::$commands = [];
    }

    //
    // Simulation de l'ouverture d'une connexion.
    //
    public function Connect(string $address, int $port, int $timeout = 3, int $engine = self::SOURCE): void
    {
        $this->address = $address;
        $this->authenticated = false;
    }

    //
    // Simulation de la fermeture d'une connexion.
    //
    public function Disconnect(): void
    {
        $this->address = null;
        $this->authenticated = false;
    }

    //
    // Simulation de la récupération des informations du serveur.
    //
    /** @return array<string, mixed> */
    public function GetInfo(): array
    {
        $this->checkConnection();

        return self::$info;
    }

    //
    // Simulation de la récupération des joueurs du serveur.
    //
    /** @return list<array<string, mixed>> */
    public function GetPlayers(): array
    {
        $this->checkConnection();

        return self::$players;
    }

    //
    // Simulation de la récupération des règles du serveur.
    //
    /** @return array<string, string> */
    public function GetRules(): array
    {
        $this->checkConnection();

        return self::$rules;
    }

    //
    // Simulation de l'authentification RCON.
    //
    public function SetRconPassword(string $password): void
    {
        if ($this->address === null)
        {
            throw new SocketException("Not connected.", SocketException::NOT_CONNECTED);
        }

        if (str_starts_with($this->address, self::UNREACHABLE_PREFIX))
        {
            throw new SocketException(
                "Can't connect to RCON server: Connection timed out",
                SocketException::CONNECTION_FAILED
            );
        }

        $this->authenticated = true;
    }

    //
    // Simulation de l'exécution d'une commande RCON.
    //
    public function Rcon(string $command): string
    {
        if ($this->address === null)
        {
            throw new SocketException("Not connected.", SocketException::NOT_CONNECTED);
        }

        if (!$this->authenticated)
        {
            throw new SocketException(
                "You must set a RCON password before trying to execute a RCON command.",
                SocketException::NOT_CONNECTED
            );
        }

        self::$commands[] = $command;

        return self::$rconResponse;
    }

    //
    // Vérification de l'état de la connexion simulée.
    //
    private function checkConnection(): void
    {
        if ($this->address === null)
        {
            throw new SocketException("Not connected.", SocketException::NOT_CONNECTED);
        }

        if (str_starts_with($this->address, self::UNREACHABLE_PREFIX))
        {
            throw new SocketException("Failed to read any data from socket", SocketException::CONNECTION_FAILED);
        }
    }
}
