<?php

//
// Contrôleur des opérations CRUD des informations de stockage.
//
namespace App\Controller\Admin;

use App\Entity\Storage;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class StorageCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return Storage::class;
	}
}