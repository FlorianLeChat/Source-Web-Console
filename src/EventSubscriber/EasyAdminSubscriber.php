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

final readonly class EasyAdminSubscriber implements EventSubscriberInterface
{
	//
	// Initialisation de certaines dépendances de l'écouteur.
	//
	public function __construct(private UserPasswordHasherInterface $hasher) {}

	/**
     * Hashage du mot de passe de l'utilisateur avant persistance ou mise à jour.
     * @param BeforeEntityUpdatedEvent<User>|BeforeEntityPersistedEvent<User> $event
     */
	public function hashUserPassword(BeforeEntityUpdatedEvent|BeforeEntityPersistedEvent $event): void
	{
		// On hash enfin le mot de passe de l'utilisateur comme
		//  lors d'une inscription ou d'une modification.
		$entity = $event->getEntityInstance();
		$entity->setPassword($this->hasher->hashPassword($entity, $entity->getPassword()));
	}

	//
	// Déclaration des écouteurs d'événements.
	//
	public static function getSubscribedEvents(): array
	{
		return [
			BeforeEntityUpdatedEvent::class => ["hashUserPassword"],
			BeforeEntityPersistedEvent::class => ["hashUserPassword"],
		];
	}
}