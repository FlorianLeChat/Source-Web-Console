<?php

//
// Données de test des serveurs distants.
//
namespace App\DataFixtures;

use App\Entity\Server;
use App\Service\ServerManager;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class ServerFixture extends Fixture implements DependentFixtureInterface
{
	public function __construct(private readonly ServerManager $serverManager) {}

	public function load(ObjectManager $manager): void
	{
		// Récupération d'une référence à l'utilisateur.
		$user = $this->getReference("user");

		// Création de 3 serveurs de test.
		for ($i = 0; $i < 3; $i++)
		{
			$server = new Server();
			$server->setAddress("123.123.123.$i");
			$server->setPort("27015");
			$server->setPassword($this->serverManager->encryptPassword("florian4016"));
			$server->setGame(4000);
			$server->setUser($user);

			$manager->persist($server);

			// Ajout d'une référence aux serveurs.
			$this->addReference("server$i", $server);
		}

		// Sauvegarde des serveurs.
		$manager->flush();
	}

	public function getDependencies()
	{
		return [
			UserFixture::class
		];
	}
}