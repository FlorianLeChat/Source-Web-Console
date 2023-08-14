<?php

//
// Données de test des tâches planifiées.
//
namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class TaskFixture extends Fixture
{
	public function load(ObjectManager $manager): void
	{
		// Récupération d'une référence à un serveur.
		$server = $this->getReference("server1");

		// Création de 10 tâches planifiées (1 par minute, sur 10 minutes).
		for ($i = 0; $i < 10; $i++)
		{
			$task = new Task();
			$task->setServer($server);
			$task->setDate(new \DateTime("+$i minutes"));
			$task->setAction("flashlight");
			$task->setState(Task::STATE_WAITING);

			$manager->persist($task);
		}

		// Sauvegarde des tâches planifiées.
		$manager->flush();
	}
}