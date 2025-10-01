<?php

//
// Contrôleur des opérations CRUD des demandes de contact.
//
namespace App\Controller\Admin;

use App\Entity\Contact;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * @extends AbstractCrudController<Contact>
 */
final class ContactCrudController extends AbstractCrudController
{
	public static function getEntityFqcn(): string
	{
		return Contact::class;
	}
}