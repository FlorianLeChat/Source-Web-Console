<?php

//
// Commande pour créer le serveur UDP utilisé pour communiquer avec les serveurs de jeu.
//  Sources : https://forums.nfoservers.com/viewtopic.php?t=10090 et https://github.com/koraktor/steam-condenser/issues/181#issuecomment-311964214
//
namespace App\Command;

use React\Datagram\Socket;
use React\Datagram\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand("app:udp-creator", "Creates the UDP server used to communicate with game servers.")]
class UdpServerCreator extends Command
{
	//
	// Initialisation de certaines dépendances de la commande.
	//
	public function __construct()
	{
		parent::__construct();
	}

	//
	// Paramètres d'exécution de la commande.
	//
	protected function configure(): void
	{
		$this->addArgument("address", InputArgument::REQUIRED, "The address of the UDP server to create.")
			->setDescription("Creates an UDP server used to communicate with game servers.")
			->setHelp("This command creates an UDP server used to communicate with game servers.");
	}

	//
	// Exécution de la commande.
	//
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		// On créé tout d'abord le serveur UDP.
		$io = new SymfonyStyle($input, $output);

		$factory = new Factory();
		$factory->createServer($input->getArgument("address"))->then(
			function (Socket $server) use ($io): void
			{
				// Si le serveur a bien été créé, on affiche un message de succès.
				$io->success("UDP server successfully created.");

				$server->on("message", function ($message, $address) use ($io): void
				{
					// Lorsque le serveur reçoit un message, on l'affiche dans la console.
					$message = substr($message, 7);
					$message = str_replace(["\0", "\r"], "", $message);

					$io->info(sprintf("New message from remote server \"%s\":\n%s", $address, $message));
				});
			},
			function (\Exception $error) use ($io)
			{
				// En cas d'erreur, on affiche un message d'erreur avant de signaler
				//  que la commande a échouée.
				$io->error(sprintf("An error occurred while creating the UDP server: %s", $error->getMessage()));

				return Command::FAILURE;
			}
		);

		// On retourne enfin le code de succès de la commande.
		return Command::SUCCESS;
	}
}