<?php

//
// Données de test des informations de stockage.
//
namespace App\DataFixtures;

use App\Entity\Storage;
use App\Service\ServerManager;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class StatsFixture extends Fixture
{
	public function __construct(private readonly ServerManager $serverManager) {}

	public function load(ObjectManager $manager): void
	{
		// Récupération d'une référence à un serveur.
		$server = $this->getReference("server1");

		// Création des informations de stockage.
		$storage = new Storage();
		$storage->setServer($server);
		$storage->setAddress("florian4016");
		$storage->setPort("22");
		$storage->setProtocol("sftp");
		$storage->setUsername("florian4016");
		$storage->setPassword($this->serverManager->encryptPassword("florian4016"));

		// Sauvegarde des informations de stockage.
		$manager->persist($storage);
		$manager->flush();
	}
}