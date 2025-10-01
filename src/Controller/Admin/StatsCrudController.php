<?php

//
// Contrôleur des opérations CRUD des statistiques.
//
namespace App\Controller\Admin;

use App\Entity\Stats;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * @extends AbstractCrudController<Stats>
 */
final class StatsCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return Stats::class;
	}

	public function configureFields(string $pageName): iterable
	{
		// Ajout de la relation avec le serveur concerné dans le
		//  formulaire de création et de modification des statistiques.
		$fields = parent::configureFields($pageName);

		array_splice($fields, 1, 0, [AssociationField::new("server")]);

		return $fields;
	}
}