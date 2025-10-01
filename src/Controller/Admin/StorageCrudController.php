<?php

//
// Contrôleur des opérations CRUD des informations de stockage.
//
namespace App\Controller\Admin;

use App\Entity\Storage;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * @extends AbstractCrudController<Storage>
 */
final class StorageCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return Storage::class;
	}

	public function configureFields(string $pageName): iterable
	{
		// Ajout de la relation avec le serveur concerné dans le
		//  formulaire de création et de modification des événements.
		$fields = parent::configureFields($pageName);

		array_splice($fields, 1, 0, [AssociationField::new("server")]);

		return $fields;
	}
}