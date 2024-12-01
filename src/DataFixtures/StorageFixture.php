<?php

//
// Données de test des informations de stockage.
//
namespace App\DataFixtures;

use App\Entity\Server;
use App\Entity\Storage;
use App\Service\ServerManager;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class StorageFixture extends Fixture implements DependentFixtureInterface
{
	public function __construct(private readonly ServerManager $serverManager) {}

	public function load(ObjectManager $manager): void
	{
		// Création des informations de stockage.
		$storage = new Storage();
		$storage->setServer($this->getReference("server0", Server::class));
		$storage->setAddress("123.123.123.123");
		$storage->setPort(22);
		$storage->setProtocol(Storage::PROTOCOL_SFTP);
		$storage->setUsername("florian4016");
		$storage->setPassword($this->serverManager->encryptPassword("florian4016"));

		// Sauvegarde des informations de stockage.
		$manager->persist($storage);
		$manager->flush();
	}

	public function getDependencies(): array
	{
		return [
			ServerFixture::class
		];
	}
}