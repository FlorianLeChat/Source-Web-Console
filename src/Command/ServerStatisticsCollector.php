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

#[AsCommand("app:statistics-collector", "Gathers statistical data from all servers.")]
final class ServerStatisticsCollector extends Command
{
	//
	// Initialisation de certaines dépendances de la commande.
	//
	public function __construct(
		private readonly ServerManager $serverManager,
		private readonly EntityManagerInterface $entityManager
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
		$past = new \DateTime("-3 days");

		foreach ($query->getQuery()->getResult() as $stats)
		{
			$server = $stats->getServer();
			$user = $server->getUser();
			$address = $server->getAddress();

			if (in_array("ROLE_DONOR", $user->getRoles()) && $stats->getDate() <= $past)
			{
				$io->info("Skipping statistics deletion from server \"$address\" because expiration date is longer.");
				continue;
			}

			$io->info("Removing old statistics from server \"$address\"...");
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
				});

				// On arrange les valeurs des données pour les rendre plus
				//  exploitables pour après.
				$data = array_values($data);

				// On créé également une nouvelle entité de statistiques avant
				//  de la sauvegarder dans la base de données via Doctrine.
				$stats = new Stats();
				$stats->setServer($server);
				$stats->setDate(new \DateTime());
				$stats->setPlayerCount(intval($data[6]));
				$stats->setCpuUsage(floatval($data[8]));
				$stats->setTickRate(floatval($data[13]));

				$io->text(sprintf("Gathering statistics from server \"%s\"...", $server->getAddress()));
				$repository->save($stats);

				// On incrémente dans ce cas le compteur de serveurs traités.
				$count++;
			}
			catch (\Exception $error)
			{
				// Si une erreur survient, on l'affiche dans la console.
				$io->error(sprintf(
					"An error occurred while gathering statistics from server \"%s\". Message: \"%s\".",
					$server->getAddress(), $error->getMessage()
				));
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

		$io->success("Gathered statistics from $count server(s).");

		// On retourne enfin le code de succès de la commande.
		return Command::SUCCESS;
	}
}