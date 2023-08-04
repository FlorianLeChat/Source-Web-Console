<?php

//
// Contrôleur des opérations CRUD des serveurs de jeu.
//
namespace App\Controller\Admin;

use App\Entity\Server;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

final class ServerCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return Server::class;
	}

	public function configureFields(string $pageName): iterable
	{
		// Ajout de la relation avec le propriétaire du serveur
		//  dans le formulaire de création et de modification des serveurs.
		$fields = parent::configureFields($pageName);

		array_splice($fields, 1, 0, [AssociationField::new("user")]);

		return $fields;
	}
}