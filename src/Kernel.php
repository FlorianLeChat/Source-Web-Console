<?php

namespace App;

use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

final class Kernel extends BaseKernel
{
	use MicroKernelTrait;

	public function boot(): void
	{
		// Appel de la méthode provenant de la classe parente.
		parent::boot();

		// Définition du fuseau horaire pour l'ensemble de l'application.
		date_default_timezone_set($this->getContainer()->getParameter("app.timezone"));
	}

	public function getLogDir(): string
	{
		// Modification de l'emplacement des journaux d'événements.
		return $this->getProjectDir() . "/logs";
	}
}