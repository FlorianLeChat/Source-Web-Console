<?php

//
// Données de test des commandes personnalisées.
//
namespace App\DataFixtures;

use App\Entity\Command;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class CommandFixture extends Fixture
{
	public function load(ObjectManager $manager): void
	{
		// Récupération d'une référence à l'utilisateur.
		$user = $this->getReference("user");

		// Création de 2 commandes personnalisées.
		for ($i = 1; $i < 3; $i++)
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
}