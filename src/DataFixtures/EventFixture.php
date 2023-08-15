<?php

//
// Données de test des événements journalisés.
//
namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class EventFixture extends Fixture implements DependentFixtureInterface
{
	public function load(ObjectManager $manager): void
	{
		// Création de 100 événements journalisés.
		for ($i = 0; $i < 100; $i++)
		{
			$event = new Event();
			$event->setServer($this->getReference("server" . rand(0, 2)));
			$event->setDate(new \DateTime());
			$event->setAction("Event #$i");

			$manager->persist($event);
		}

		// Sauvegarde des événements journalisés.
		$manager->flush();
	}

	public function getDependencies()
	{
		return [
			ServerFixture::class
		];
	}
}