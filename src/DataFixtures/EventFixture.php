<?php

//
// Données de test des événements journalisés.
//
namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class EventFixture extends Fixture
{
	public function load(ObjectManager $manager): void
	{
		// Récupération d'une référence à un serveur.
		$server = $this->getReference("server1");

		// Création de 100 événements journalisés.
		for ($i = 0; $i < 100; $i++)
		{
			$event = new Event();
			$event->setServer($server);
			$event->setDate(new \DateTime());
			$event->setAction("Event #$i");

			$manager->persist($event);
		}

		// Sauvegarde des événements journalisés.
		$manager->flush();
	}
}