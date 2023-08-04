<?php

//
// Répertoire des méthodes pour les serveurs distants.
//
namespace App\Repository;

use App\Entity\Server;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Server>
 *
 * @method Server|null find($id, $lockMode = null, $lockVersion = null)
 * @method Server|null findOneBy(array $criteria, array $orderBy = null)
 * @method Server[]    findAll()
 * @method Server[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class ServerRepository extends ServiceEntityRepository
{
	public function __construct(private readonly ManagerRegistry $registry)
	{
		parent::__construct($registry, Server::class);
	}

	public function save(Server $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush)
		{
			$this->getEntityManager()->flush();
		}
	}

	public function remove(Server $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush)
		{
			$this->getEntityManager()->flush();
		}
	}
}