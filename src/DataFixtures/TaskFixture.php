<?php

//
// Données de test des tâches planifiées.
//
namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class TaskFixture extends Fixture implements DependentFixtureInterface
{
	public function load(ObjectManager $manager): void
	{
		// Création de 10 tâches planifiées (1 par minute, sur 10 minutes).
		for ($i = 0; $i < 10; $i++)
		{
			$task = new Task();
			$task->setServer($this->getReference("server" . rand(0, 2)));
			$task->setDate(new \DateTime("+$i minutes"));
			$task->setAction("flashlight");
			$task->setState(Task::STATE_WAITING);

			$manager->persist($task);
		}

		// Sauvegarde des tâches planifiées.
		$manager->flush();
	}

	public function getDependencies()
	{
		return [
			ServerFixture::class
		];
	}
}