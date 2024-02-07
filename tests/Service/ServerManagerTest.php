<?php

//
// Tests du service de gestion des serveurs.
//
namespace App\Tests;

use App\Entity\Server;
use App\Service\ServerManager;
use xPaw\SourceQuery\Exception\SocketException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServerManagerTest extends KernelTestCase
{
	// Gestionnaire de serveurs.
	protected serverManager $serverManager;

	//
	// Initialisation des services requis début de chaque test
	//  afin de garantir un environnement de test propre.
	//
	protected function setUp(): void
	{
		// Appel de la méthode parente.
		parent::setUp();

		// Création du service de gestion.
		$this->serverManager = static::getContainer()->get(ServerManager::class);
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

		$this->assertTrue($this->serverManager->decryptPassword($hash) === "florian4016");

		// Comparaison avec un mot de passe différent.
		$hash = $this->serverManager->encryptPassword("florian4016");

		$this->assertNotTrue($this->serverManager->decryptPassword($hash) === "florian4017");
	}

	//
	// Récupération du nom d'un jeu à partir de son numéro
	//  d'identification unique.
	//
	public function testGetNameByGameID(): void
	{
		// Récupération de certains noms de jeux existants.
		$this->assertTrue($this->serverManager->getNameByGameID(240) === "Counter-Strike: Source");
		$this->assertTrue($this->serverManager->getNameByGameID(730) === "Counter-Strike 2");
		$this->assertTrue($this->serverManager->getNameByGameID(4000) === "Garry's Mod");

		// Récupération d'un nom de jeu inexistant (avec et sans valeur par défaut).
		$this->assertTrue($this->serverManager->getNameByGameID(1) === "");
		$this->assertTrue($this->serverManager->getNameByGameID(2, "Unknown") === "Unknown");
	}

	//
	// Récupération du numéro d'identification unique d'un jeu
	//  utilisé par l'adresse IP d'un serveur.
	//
	public function testGetGameIDByAddress(): void
	{
		// Récupération du jeu « Counter-Strike 2 ».
		$this->assertTrue($this->serverManager->getGameIDByAddress("92.119.148.31", 27015) === 730);

		// Récupération du jeu « Garry's Mod ».
		$this->assertTrue($this->serverManager->getGameIDByAddress("208.103.169.233", 27015) === 4000);

		// Récupération d'un jeu inexistant (le serveur n'existe pas).
		$this->assertTrue($this->serverManager->getGameIDByAddress("127.0.0.1", 27015) === 0);
	}
}