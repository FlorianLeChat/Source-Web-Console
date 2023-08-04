<?php

//
// Commande pour l'exécution des tâches planifiées.
//
namespace App\Command;

use App\Entity\Task;
use App\Entity\Server;
use App\Service\ServerManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand("app:tasks-executor", "Runs scheduled tasks waiting to be executed")]
class ScheduledTaskExecutor extends Command
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
		// On récupère d'abord toutes les tâches planifiées en attente d'exécution
		//  et dont la date est inférieure à la date actuelle.
		$repository = $this->entityManager->getRepository(Task::class);
		$query = $repository->createQueryBuilder("t");
		$query->where($query->expr()->eq("t.state", ":state"))
			->setParameter("state", Task::STATE_WAITING);
		$query->andWhere($query->expr()->lte("t.date", ":today"))
			->setParameter("today", new \DateTime(), \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE);

		// On itère ensuite sur chaque tâche planifiée.
		$io = new SymfonyStyle($input, $output);
		$count = 0;

		foreach ($query->getQuery()->getResult() as $task)
		{
			// On signale à Doctrine que la tâche est en cours d'exécution.
			$io->text(sprintf("Executing task \"%d\"...", $task->getId()));
			$task->setState(Task::STATE_RUNNING);
			$repository->save($task);

			try
			{
				// On tente alors de se connecter au serveur distant.
				$this->serverManager->connect($task->getServer());

				$io->text("Connected to remote server.");

				// On détermine l'action demandée dans la tâche planifiée.
				switch ($task->getAction())
				{
					case Server::ACTION_SHUTDOWN:
					{
						// Requête d'arrêt classique.
						$io->text("Shutting down server...");
						$this->serverManager->query->Rcon("sv_shutdown");
					}

					case Server::ACTION_RESTART:
					{
						// Requête de redémarrage.
						$io->text("Restarting server...");
						$this->serverManager->query->Rcon("_restart");
						break;
					}

					case Server::ACTION_UPDATE:
					{
						// Requête de mise à jour.
						$io->text("Updating server...");
						$this->serverManager->query->Rcon("svc_update");
						break;
					}

					case Server::ACTION_SERVICE:
					{
						// Requête de mise en maintenance/verrouillage.
						$io->text("Locking server...");
						$this->serverManager->query->Rcon(sprintf("sv_password \"%s\"", bin2hex(random_bytes(15))));
						break;
					}
				}

				// On signale à Doctrine que la tâche a été exécutée avec succès.
				$io->text(sprintf("Task \"%d\" executed successfully.", $task->getId()));
				$task->setState(Task::STATE_FINISHED);
				$repository->save($task);

				// On incrémente après le compteur de tâches exécutées.
				$count++;
			}
			catch (\Exception $error)
			{
				// On signale à Doctrine que la tâche a échouée avec une erreur.
				$io->error(sprintf("An error occurred while executing task \"%d\". Message: \"%s\".", $task->getId(), $error->getMessage()));
				$task->setState(Task::STATE_ERROR);
				$repository->save($task);
			}
			finally
			{
				// On se déconnecte après du serveur distant une fois la tâche exécutée.
				$this->serverManager->query->Disconnect();
			}
		}

		// On sauvegarde les changements dans la base de données.
		$this->entityManager->flush();

		$io->success(sprintf("Executed %d task(s).", $count));

		// On retourne enfin le code de succès de la commande.
		return Command::SUCCESS;
	}
}