<?php

//
// Contrôleur des opérations CRUD des statistiques.
//
namespace App\Controller\Admin;

use App\Entity\Stats;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class StatsCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return Stats::class;
	}
}