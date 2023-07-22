<?php

//
// Commande pour collecter les données de statistiques de l'ensemble des serveurs.
//
namespace App\Command;

use App\Entity\Stats;
use App\Entity\Server;
use App\Service\ServerManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand("app:statistics:collector", "Gathers statistical data from all servers.")]
class ServerStatisticsCollector extends Command
{
	//
	// Initialisation de certaines dépendances de la commande.
	//
	public function __construct(
		private ServerManager $serverManager,
		private EntityManagerInterface $entityManager
	) {
		parent::__construct();
	}

	//
	// Exécution de la commande.
	//
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		// On récupère d'abord toutes les statistiques enregistrées
		//  datant d'au moins un jour.
		$repository = $this->entityManager->getRepository(Stats::class);
		$query = $repository->createQueryBuilder("s");
		$query->where($query->expr()->lte("s.date", ":past"))
			->setParameter("past", new \DateTime("-1 day"), \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE);

		// On itère ensuite sur chacune d'entre elles pour les supprimer.
		$io = new SymfonyStyle($input, $output);

		foreach ($query->getQuery()->getResult() as $stats)
		{
			$io->info(sprintf("Removing old statistics from server \"%s\"...", $stats->getServer()->getAddress()));
			$repository->remove($stats);
		}

		// On récupère alors tous les serveurs enregistrés.
		$servers = $this->entityManager->getRepository(Server::class)->findAll();

		// On itère après sur chacun d'entre eux.
		$count = 0;

		foreach ($servers as $server)
		{
			try
			{
				// On tente alors d'établir une connexion avec le serveur.
				$this->serverManager->connect($server);

				// On filtre les données reçues pour ne garder que celles
				//  qui nous intéressent pour les étapes suivantes.
				$data = $this->serverManager->query->Rcon("stats");
				$data = array_filter(explode(" ", str_replace("\n", " ", $data)), function($value)
					{
						return $value !== "" && $value !== "\n";
					}
				);

				// On arrange les valeurs des données pour les rendre plus
				//  exploitables pour après.
				$data = array_values($data);

				// On créé également une nouvelle entité de statistiques avant
				//  de la sauvegarder dans la base de données via Doctrine.
				$stats = new Stats();
				$stats->setServer($server);
				$stats->setDate(new \DateTime());
				$stats->setPlayerCount($data[6]);
				$stats->setCpuUsage($data[8]);
				$stats->setTickRate($data[13]);

				$io->text(sprintf("Gathering statistics from server \"%s\"...", $server->getAddress()));
				$repository->save($stats);

				// On incrémente dans ce cas le compteur de serveurs traités.
				$count++;
			}
			catch (\Exception $error)
			{
				// Si une erreur survient, on l'affiche dans la console.
				$io->error(sprintf("An error occurred while gathering statistics from server \"%s\". Message: \"%s\".", $server->getAddress(), $error->getMessage()));
			}
			finally
			{
				// Si tout se passe bien, on libère le socket réseau pour
				//  d'autres scripts du site.
				$this->serverManager->query->Disconnect();
			}
		}

		// On synchronise également les changements dans la base de données.
		$this->entityManager->flush();

		$io->success(sprintf("Gathered statistics from %d servers.", $count));

		// On retourne enfin le code de succès de la commande.
		return Command::SUCCESS;
	}
}