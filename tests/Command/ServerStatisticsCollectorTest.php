<?php

//
// Tests de la commande de récupération des statistiques des serveurs.
//
namespace App\Tests\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class ServerStatisticsCollectorTest extends KernelTestCase
{
	public function testExecute(): void
	{
		// Démarrage du noyau de l'application.
		$kernel = self::bootKernel();
		$application = new Application($kernel);

		// Réinitialisation de la base de données.
		$php = new PhpExecutableFinder();
		$process = new Process([
			$php->find(),
			sprintf("%s/bin/console", $kernel->getProjectDir()),
			"doctrine:fixtures:load",
			"--env=test",
			"--no-interaction"
		]);

		$process->disableOutput();
		$process->run();

		// Exécution de la commande.
		$command = $application->find("app:statistics-collector");
		$tester = new CommandTester($command);
		$tester->execute([]);

		// Vérification de l'état de la commande.
		$tester->assertCommandIsSuccessful();

		// Vérification de la sortie de la commande.
		//  Note : les serveurs enregistrés n'existent pas.
		$output = $tester->getDisplay();

		$this->assertStringContainsString("Can't connect to", $output);
		$this->assertStringContainsString("[OK] Gathered statistics from 0 server(s).", $output);
	}
}