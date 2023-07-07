<?php

//
// Contrôleur des opérations CRUD des serveurs de jeu.
//
namespace App\Controller\Admin;

use App\Entity\Server;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ServerCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return Server::class;
	}
}