<?php

//
// Contrôleur des opérations CRUD des commandes personnalisées.
//
namespace App\Controller\Admin;

use App\Entity\Command;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * @extends AbstractCrudController<Command>
 */
final class CommandCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return Command::class;
	}

	public function configureFields(string $pageName): iterable
	{
		// Ajout de la relation avec l'utilisateur concerné dans le
		//  formulaire de création et de modification des commandes.
		$fields = parent::configureFields($pageName);

		array_splice($fields, 1, 0, [AssociationField::new("user")]);

		return $fields;
	}
}