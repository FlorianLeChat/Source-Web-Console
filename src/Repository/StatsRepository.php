<?php

//
// Répertoire des méthodes pour les statistiques des serveurs distants.
//
namespace App\Repository;

use App\Entity\Stats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stats>
 *
 * @method Stats|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stats|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stats[]    findAll()
 * @method Stats[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class StatsRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Stats::class);
	}

	public function save(Stats $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush)
		{
			$this->getEntityManager()->flush();
		}
	}

	public function remove(Stats $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush)
		{
			$this->getEntityManager()->flush();
		}
	}
}