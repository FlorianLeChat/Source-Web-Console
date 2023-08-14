<?php

//
// Données de test des statistiques.
//
namespace App\DataFixtures;

use App\Entity\Stats;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class StatsFixture extends Fixture
{
	public function load(ObjectManager $manager): void
	{
		// Récupération d'une référence à un serveur.
		$server = $this->getReference("server1");

		// Création de 24 statistiques (1 par heure, sur 24 heures).
		for ($i = 0; $i < 24; $i++)
		{
			$stats = new Stats();
			$stats->setServer($server);
			$stats->setDate(new \DateTime("+$i hours"));
			$stats->setPlayerCount(mt_rand(0, 128));
			$stats->setCpuUsage(mt_rand(0, 10000) / 100);
			$stats->setTickRate(mt_rand(1, 24));

			$manager->persist($stats);
		}

		// Sauvegarde des statistiques.
		$manager->persist($stats);
		$manager->flush();
	}
}