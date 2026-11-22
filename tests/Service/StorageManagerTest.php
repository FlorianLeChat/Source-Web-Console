<?php

//
// Tests du service de gestion des serveurs de stockage.
//
namespace App\Tests\Service;

use App\Entity\Storage;
use phpseclib3\Net\SFTP;
use App\Service\ServerManager;
use App\Service\StorageManager;
use PHPUnit\Framework\TestCase;
use xPaw\SourceQuery\SourceQuery;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;

final class StorageManagerTest extends TestCase
{
    // Gestionnaire de serveurs.
    private ServerManager $serverManager;

    // Gestionnaire de stockage.
    private StorageManager $storageManager;

    //
    // Création des services testés.
    //
    protected function setUp(): void
    {
        $parameters = $this->createStub(ContainerBag::class);
        $parameters->method("get")->willReturn("passphrase");

        $this->serverManager = new ServerManager(
            new SourceQuery(),
            new ArrayAdapter(),
            new MockHttpClient(),
            $parameters
        );
        $this->storageManager = new StorageManager($this->serverManager);
    }

    //
    // Création d'un serveur de stockage.
    //
    private function createStorage(string $protocol): Storage
    {
        $storage = new Storage();
        $storage->setAddress("127.0.0.1");
        $storage->setPort(1);
        $storage->setProtocol($protocol);
        $storage->setUsername("florian4016");
        $storage->setPassword($this->serverManager->encryptPassword("florian4016"));

        return $storage;
    }

    //
    // Ouverture d'une connexion avec un protocole inconnu.
    //
    public function testOpenConnectionUnknownProtocol(): void
    {
        self::assertNull($this->storageManager->openConnection($this->createStorage("http")));
    }

    //
    // Ouverture d'une connexion SFTP vers un serveur injoignable.
    //
    public function testOpenConnectionSftpFailure(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->storageManager->openConnection($this->createStorage(Storage::PROTOCOL_SFTP));
    }

    //
    // Lecture du contenu d'un fichier.
    //
    public function testGetFileContents(): void
    {
        // Connexion SFTP.
        $stream = $this->createMock(SFTP::class);
        $stream->expects($this->once())->method("get")->with("/server.cfg")->willReturn("hostname \"Test\"");

        self::assertSame("hostname \"Test\"", $this->storageManager->getFileContents($stream, "/server.cfg"));

        // Connexion inconnue.
        self::assertSame("", $this->storageManager->getFileContents(null, "/server.cfg"));
    }

    //
    // Écriture du contenu d'un fichier.
    //
    public function testPutFileContents(): void
    {
        // Connexion SFTP.
        $stream = $this->createMock(SFTP::class);
        $stream->expects($this->once())->method("put")->with("/server.cfg", "hostname \"Test\"");

        $this->storageManager->putFileContents($stream, "/server.cfg", "hostname \"Test\"");

        // Connexion inconnue (aucune action).
        $this->storageManager->putFileContents(null, "/server.cfg", "hostname \"Test\"");
    }
}
