<?php

//
// Contrôleur des opérations CRUD des tâches planifiées.
//
namespace App\Controller\Admin;

use App\Entity\Task;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class TaskCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return Task::class;
	}
}