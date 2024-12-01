<?php

//
// Données de test des statistiques.
//
namespace App\DataFixtures;

use App\Entity\Stats;
use App\Entity\Server;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class StatsFixture extends Fixture implements DependentFixtureInterface
{
	public function load(ObjectManager $manager): void
	{
		// Récupération d'une référence à un serveur.
		$server = $this->getReference("server0", Server::class);

		// Création de 24 statistiques (1 par heure, sur 24 heures).
		for ($i = 0; $i < 24; $i++)
		{
			$stats = new Stats();
			$stats->setServer($server);
			$stats->setDate(new \DateTime("+$i hours"));
			$stats->setPlayerCount(rand(0, 128));
			$stats->setCpuUsage(rand(0, 10000) / 100);
			$stats->setTickRate(rand(1, 2400) / 100);

			$manager->persist($stats);
		}

		// Sauvegarde des statistiques.
		$manager->persist($stats);
		$manager->flush();
	}

	public function getDependencies(): array
	{
		return [
			ServerFixture::class
		];
	}
}