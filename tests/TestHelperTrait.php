<?php

//
// Fonctions utilitaires partagées entre les tests.
//
namespace App\Tests;

use App\Entity\Server;
use App\Service\ServerManager;
use App\Tests\Mock\FakeMailer;
use App\Repository\UserRepository;
use App\Tests\Mock\FakeSourceQuery;
use App\Repository\ServerRepository;
use App\Tests\Mock\FakeStorageManager;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Request;
use App\Tests\Mock\HttpClientResponseFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Process\PhpExecutableFinder;

trait TestHelperTrait
{
    //
    // Chargement des données de test dans la base de données.
    //
    protected static function loadFixtures(string $projectDir): void
    {
        $php = new PhpExecutableFinder();
        $process = new Process([
            $php->find(),
            sprintf("%s/bin/console", $projectDir),
            "doctrine:fixtures:load",
            "--env=test",
            "--no-interaction"
        ]);

        $process->disableOutput();
        $process->mustRun();
    }

    //
    // Réinitialisation de l'état des services simulés.
    //
    protected static function resetFakeServices(): void
    {
        FakeMailer::reset();
        FakeSourceQuery::reset();
        FakeStorageManager::reset();
        HttpClientResponseFactory::reset();
    }

    //
    // Création d'un serveur de jeu pour l'utilisateur de test.
    //  Note : les adresses commençant par « 123.123.123. » sont injoignables.
    //
    protected static function createServer(string $address, ?string $password = "florian4016"): Server
    {
        $container = static::getContainer();
        $user = $container->get(UserRepository::class)->findOneBy(["username" => "florian4016"]);

        $server = new Server();
        $server->setUser($user);
        $server->setAddress($address);
        $server->setPort(27015);
        $server->setGame(4000);

        if ($password !== null)
        {
            $server->setPassword($container->get(ServerManager::class)->encryptPassword($password));
        }

        $container->get(ServerRepository::class)->save($server, true);

        return $server;
    }

    //
    // Sélection d'un serveur de jeu depuis le tableau de bord.
    //
    protected static function selectServer(KernelBrowser $client, int $serverId): void
    {
        $crawler = $client->request("GET", "/dashboard");
        $token = $crawler->filter("button[name = server_connect]")->first()->form()->get("token")->getValue();

        $client->request("POST", "/dashboard", [
            "id" => $serverId,
            "token" => $token,
            "action" => "connect"
        ]);
    }

    //
    // Génération d'un jeton CSRF lié à la session du client de navigation.
    //  Note : une première requête doit avoir été envoyée par le client.
    //
    protected static function generateCsrfToken(KernelBrowser $client, string $identifier): string
    {
        $container = $client->getContainer();
        $session = $client->getRequest()->getSession();

        $request = new Request();
        $request->setSession($session);

        $stack = $container->get("request_stack");
        $stack->push($request);

        $token = $container->get("security.csrf.token_manager")->getToken($identifier)->getValue();

        $session->save();
        $stack->pop();

        return $token;
    }
}
