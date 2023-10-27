<?php

//
// Tests de la commande de création d'un serveur UDP.
//
namespace App\Tests\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class UdpServerCreatorTest extends KernelTestCase
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
		$command = $application->find("app:udp-server");
		$tester = new CommandTester($command);
		$tester->execute([
			"address" => "127.0.0.1:81"
		]);

		// Vérification de l'état de la commande.
		$tester->assertCommandIsSuccessful();

		// Vérification de la sortie de la commande.
		$output = $tester->getDisplay();

		$this->assertMatchesRegularExpression("/(successfully created)|(Permission denied)/", $output);
	}
}