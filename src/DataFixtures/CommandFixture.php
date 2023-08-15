<?php

//
// Données de test des commandes personnalisées.
//
namespace App\DataFixtures;

use App\Entity\Command;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class CommandFixture extends Fixture implements DependentFixtureInterface
{
	public function load(ObjectManager $manager): void
	{
		// Récupération d'une référence à l'utilisateur.
		$user = $this->getReference("user");

		// Création de 2 commandes personnalisées.
		for ($i = 0; $i < 2; $i++)
		{
			$command = new Command();
			$command->setUser($user);
			$command->setTitle("Command #$i");
			$command->setContent("say $i");

			$manager->persist($command);
		}

		// Sauvegarde des commandes personnalisées.
		$manager->flush();
	}

	public function getDependencies()
	{
		return [
			UserFixture::class
		];
	}
}