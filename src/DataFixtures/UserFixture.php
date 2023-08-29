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
		// Création et sauvegarde d'un compte utilisateur
		//  permanent pour les tests unitaires.
		$user = new User();
		$user->setUsername("florian4016");
		$user->setPassword($this->hasher->hashPassword($user, "florian4016"));
		$user->setCreatedAt(new \DateTime());
		$user->setAddress("127.0.0.0");
		$user->setRoles(["ROLE_USER"]);

		$manager->persist($user);

		// Ajout d'une référence à l'utilisateur permanent.
		$this->addReference("user", $user);

		// Création et sauvegarde d'un compte utilisateur
		//  temporaire pour les tests des commandes.
		$user = new User();
		$user->setUsername(sprintf("temp_%s", bin2hex(random_bytes(10))));
		$user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(30))));
		$user->setCreatedAt(new \DateTime("-1 week"));
		$user->setAddress("127.0.0.0");
		$user->setRoles(["ROLE_USER"]);

		$manager->persist($user);

		// Sauvegarde dans la base de données.
		$manager->flush();
	}
}