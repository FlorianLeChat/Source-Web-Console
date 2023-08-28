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

		// Création d'une commande personnalisée.
		$command = new Command();
		$command->setUser($user);
		$command->setTitle("Command #1");
		$command->setContent("say Hello World!");

		// Sauvegarde de la commande personnalisée.
		$manager->persist($command);
		$manager->flush();
	}

	public function getDependencies()
	{
		return [
			UserFixture::class
		];
	}
}