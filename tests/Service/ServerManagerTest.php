<?php

//
// Tests du service de gestion des serveurs.
//
namespace App\Tests;

use App\Entity\Server;
use App\Service\ServerManager;
use App\Tests\Mock\FakeSourceQuery;
use xPaw\SourceQuery\Exception\SocketException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServerManagerTest extends KernelTestCase
{
    use TestHelperTrait;

    // Gestionnaire de serveurs.
    protected ServerManager $serverManager;

    //
    // Initialisation des services requis début de chaque test
    //  afin de garantir un environnement de test propre.
    //
    protected function setUp(): void
    {
        // Appel de la méthode parente.
        parent::setUp();

        // Réinitialisation des services simulés et du cache.
        self::resetFakeServices();
        static::getContainer()->get("cache.app")->clear();

        // Création du service de gestion.
        /** @var ServerManager $serverManager */
        $serverManager = static::getContainer()->get(ServerManager::class);
        $this->serverManager = $serverManager;
    }

    //
    // Connexion à un serveur avec un mot de passe administrateur.
    //
    public function testServerConnectWithPassword(): void
    {
        $server = new Server();
        $server->setAddress("145.239.211.147");
        $server->setPort(27015);
        $server->setPassword($this->serverManager->encryptPassword("florian4016"));

        $this->serverManager->connect($server);
        $this->serverManager->query->Rcon("status");

        self::assertSame(["status"], FakeSourceQuery::$commands);
    }

    //
    // Déchiffrement d'un mot de passe altéré.
    //
    public function testTamperedPasswordDecryption(): void
    {
        $hash = base64_decode($this->serverManager->encryptPassword("florian4016"));
        $hash[strlen($hash) - 1] = chr(ord($hash[strlen($hash) - 1]) ^ 1);

        self::assertSame("", $this->serverManager->decryptPassword(base64_encode($hash)));
    }

    //
    // Récupération de l'identifiant du jeu monté sur un serveur.
    //
    public function testGetGameIDByAddress(): void
    {
        // Serveur hébergeant un jeu Steam.
        self::assertSame(4000, $this->serverManager->getGameIDByAddress("145.239.211.147", 27015));

        // Erreur de l'API Steam.
        self::assertSame(0, $this->serverManager->getGameIDByAddress("10.0.0.1", 27015));

        // Réponse vide de l'API Steam.
        self::assertSame(0, $this->serverManager->getGameIDByAddress("10.0.0.2", 27015));

        // Réponse en échec de l'API Steam.
        self::assertSame(0, $this->serverManager->getGameIDByAddress("10.0.0.3", 27015));

        // Aucun serveur trouvé par l'API Steam.
        self::assertSame(0, $this->serverManager->getGameIDByAddress("10.0.0.4", 27015));
    }

    public function testServerConnect(): void
    {
        // Création d'un serveur de jeu existant.
        $server = new Server();
        $server->setAddress("208.103.169.233");
        $server->setPort(27015);

        // Connexion au serveur et envoi d'une commande.
        //  Note : un mot de passe administrateur est requis.
        $this->expectException(SocketException::class);
        $this->expectExceptionMessage("You must set a RCON password before trying to execute a RCON command.");

        $this->serverManager->connect($server);
        $this->serverManager->query->Rcon("sv_shutdown");
    }

    //
    // Chiffrement et déchiffrement d'un mot de passe.
    //
    public function testPasswordEncryption(): void
    {
        // Comparaison avec un mot de passe identique.
        $hash = $this->serverManager->encryptPassword("florian4016");

        self::assertSame("florian4016", $this->serverManager->decryptPassword($hash));

        // Comparaison avec un mot de passe différent.
        $hash = $this->serverManager->encryptPassword("florian4016");

        self::assertNotSame("florian4017", $this->serverManager->decryptPassword($hash));
    }

    //
    // Récupération du nom d'un jeu à partir de son numéro
    //  d'identification unique.
    //
    public function testGetNameByGameID(): void
    {
        // Récupération de certains noms de jeux existants.
        self::assertSame("Counter-Strike: Source", $this->serverManager->getNameByGameID(240));
        self::assertSame("Counter-Strike 2", $this->serverManager->getNameByGameID(730));
        self::assertSame("Garry's Mod", $this->serverManager->getNameByGameID(4000));

        // Récupération d'un nom de jeu inexistant (avec et sans valeur par défaut).
        self::assertSame("", $this->serverManager->getNameByGameID(1));
        self::assertSame("Unknown", $this->serverManager->getNameByGameID(2, "Unknown"));
    }
}
