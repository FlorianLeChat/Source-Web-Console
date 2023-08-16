<?php

//
// Données de test des utilisateurs.
//
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixture extends Fixture
{
	public function __construct(private readonly UserPasswordHasherInterface $hasher) {}

	public function load(ObjectManager $manager): void
	{
		// Création d'un utilisateur de test.
		$user = new User();
		$user->setUsername("florian4016");
		$user->setPassword($this->hasher->hashPassword($user, "florian4016"));
		$user->setCreatedAt(new \DateTime());
		$user->setAddress("127.0.0.0");
		$user->setRoles(["ROLE_USER"]);

		// Sauvegarde de l'utilisateur.
		$manager->persist($user);
		$manager->flush();

		// Ajout d'une référence à l'utilisateur.
		$this->addReference("user", $user);
	}
}