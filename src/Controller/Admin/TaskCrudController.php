<?php

//
// Contrôleur des opérations CRUD des tâches planifiées.
//
namespace App\Controller\Admin;

use App\Entity\Task;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

final class TaskCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return Task::class;
	}

	public function configureFields(string $pageName): iterable
	{
		// Ajout de la relation avec le serveur concerné dans le
		//  formulaire de création et de modification des tâches planifiées.
		$fields = parent::configureFields($pageName);

		array_splice($fields, 1, 0, [AssociationField::new("server")]);

		return $fields;
	}
}