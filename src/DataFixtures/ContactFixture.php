<?php

//
// Données de test des messages de contact.
//
namespace App\DataFixtures;

use App\Entity\Contact;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ContactFixture extends Fixture
{
	public function __construct(private readonly TranslatorInterface $translator) {}

	public function load(ObjectManager $manager): void
	{
		// Création de 3 messages de contact.
		for ($i = 1; $i < 4; $i++)
		{
			$contact = new Contact();
			$contact->setDate(new \DateTime("+$i days"));
			$contact->setEmail("florian0$i@gmail.com");
			$contact->setSubject($this->translator->trans("form.contact.subject.$i"));
			$contact->setContent("Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.");

			$manager->persist($contact);
		}

		// Sauvegarde des messages de contact.
		$manager->flush();
	}
}