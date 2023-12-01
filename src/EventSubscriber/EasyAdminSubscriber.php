<?php

//
// Écouteur d'événements pour l'administration de EasyAdmin.
//
namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class EasyAdminSubscriber implements EventSubscriberInterface
{
	//
	// Initialisation de certaines dépendances de l'écouteur.
	//
	public function __construct(private readonly UserPasswordHasherInterface $hasher) {}

	//
	// Hashage du mot de passe de l'utilisateur avant sa persistance
	//  ou son actualisation.
	//
	public function hashUserPassword(BeforeEntityUpdatedEvent|BeforeEntityPersistedEvent $event): void
	{
		// On vérifie que l'entité concernée est bien de classe
		//  des utilisateurs.
		$entity = $event->getEntityInstance();

		if (!($entity instanceof User))
		{
			return;
		}

		// Dans ce cas, on hash enfin le mot de passe de l'utilisateur
		//  comme lors d'une inscription ou d'une modification.
		$entity->setPassword($this->hasher->hashPassword($entity, $entity->getPassword()));
	}

	//
	// Déclaration des écouteurs d'événements.
	//
	public static function getSubscribedEvents()
	{
		return [
			BeforeEntityUpdatedEvent::class => ["hashUserPassword"],
			BeforeEntityPersistedEvent::class => ["hashUserPassword"],
		];
	}
}