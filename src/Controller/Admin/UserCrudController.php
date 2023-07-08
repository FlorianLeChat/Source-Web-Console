<?php

//
// Contrôleur des opérations CRUD des utilisateurs.
//
namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class UserCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return User::class;
	}

	public function configureFields(string $pageName): iterable
	{
		// Ajout du champ « roles » dans le formulaire de création
		//  et de modification des utilisateurs.
		$fields = parent::configureFields($pageName);
		$fields[] = ArrayField::new("roles");

		return $fields;
	}
}