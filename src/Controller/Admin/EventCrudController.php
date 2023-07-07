<?php

//
// Contrôleur des opérations CRUD des événements journalisés.
//
namespace App\Controller\Admin;

use App\Entity\Event;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class EventCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return Event::class;
	}
}