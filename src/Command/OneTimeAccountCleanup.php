<?php

//
// Commande pour supprimer les comptes temporaires expirés.
//
namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand("app:account-cleanup", "Deletes expired temporary accounts from the database")]
final class OneTimeAccountCleanup extends Command
{
	//
	// Initialisation de certaines dépendances de la commande.
	//
	public function __construct(private readonly EntityManagerInterface $entityManager)
	{
		parent::__construct();
	}

	//
	// Exécution de la commande.
	//
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		// On récupère d'abord tous les comptes utilisateurs temporaires expirés.
		$repository = $this->entityManager->getRepository(User::class);
		$query = $repository->createQueryBuilder("u");
		$query->where($query->expr()->like("u.username", ":name"))
			->setParameter("name", "temp\_%");
		$query->andWhere($query->expr()->lte("u.createdAt", ":past"))
			->setParameter("past", new \DateTime("-1 day"), \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE);

		// On itère ensuite sur chaque compte utilisateur.
		$io = new SymfonyStyle($input, $output);
		$count = 0;

		foreach ($query->getQuery()->getResult() as $account)
		{
			// On supprime le compte utilisateur via Doctrine.
			$io->text(sprintf("Deleting account \"%s (%d)\"...", $account->getUsername(), $account->getId()));
			$repository->remove($account);

			// On incrémente alors le compteur de comptes supprimés.
			$count++;
		}

		// On sauvegarde après les changements dans la base de données.
		$this->entityManager->flush();

		$io->success(sprintf("Deleted %d expired temporary accounts.", $count));

		// On retourne enfin le code de succès de la commande.
		return Command::SUCCESS;
	}
}