<?php

//
// Contrôleur de la page de l'espace utilisateur.
//
namespace App\Controller;

use App\Entity\User;
use App\Entity\Server;
use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private Security $security,
		private ValidatorInterface $validator,
		private TranslatorInterface $translator,
		private EntityManagerInterface $entityManager,
	)
	{
		$this->security = $security;
		$this->translator = $translator;
		$this->entityManager = $entityManager;
		$this->validator = $validator;
	}

	//
	// Route vers la page de l'espace utilisateur.
	//
	#[Route("/user")]
	#[IsGranted("IS_AUTHENTICATED")]
	public function index(): Response
	{
		// On affiche la page de l'espace utilisateur.
		return $this->render("user.html.twig");
	}

	//
	// API vers le mécanisme de création de compte.
	//  Source : https://symfony.com/doc/current/security.html#registering-the-user-hashing-passwords
	//
	#[Route("/api/user/register", methods: ["POST"], condition: "request.isXmlHttpRequest()")]
	public function register(Request $request, UserPasswordHasherInterface $hasher): Response
	{
		// TODO : imposer une limite de création par IP.
		// TODO : ajouter une vérification contre les noms d'utilisateurs dupliqués.
		// TODO : ajouter la possibilité de créer un compte via Google.
		// TODO : ajouter la possibilité de se souvenir de la connexion après création de compte.
		// TODO : ajouter une vérification avec Google reCAPTCHA.

		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_register", $request->request->get("token")))
		{
			return new Response($this->translator->trans("form.register.failed"), Response::HTTP_BAD_REQUEST);
		}

		// On récupère après toutes les informations de la requête.
		$username = $request->get("username");
		$password = $request->get("password");
		$serverAddress = $request->get("server_address");
		$serverPort = $request->get("server_port");
		$serverPassword = $request->get("server_password");

		// On enregistre ensuite les informations de l'utilisateur ainsi que celle du serveur.
		$user = new User();
		$server = new Server();

		$user->setUsername($username);
		$user->setPassword($hasher->hashPassword($user, $password ?? ""));

		$server->setAddress($serverAddress);
		$server->setPassword($serverPassword);
		$server->setPort($serverPort);
		$server->setClient($user);

		// On vérifie également si les informations sont valides.
		if (count($this->validator->validate($user)) > 0 || count($this->validator->validate($server)) > 0)
		{
			return new Response($this->translator->trans("form.server_check_failed"), Response::HTTP_BAD_REQUEST);
		}

		// On enregistre après les informations dans la base de données.
		$this->entityManager->getRepository(User::class)->save($user);
		$this->entityManager->getRepository(Server::class)->save($server, true);

		// On authentifie alors l'utilisateur.
		$this->security->login($user, "form_login");

		// On envoie enfin la réponse au client.
		return new Response($this->translator->trans("form.register.success"), Response::HTTP_OK);
	}

	//
	// API vers le mécanisme d'authentification de l'utilisateur.
	//  Source : https://symfony.com/doc/current/security.html#login-programmatically
	//
	#[Route("/api/user/login", methods: ["POST"], condition: "request.isXmlHttpRequest()")]
	public function login(Request $request, UserPasswordHasherInterface $hasher): Response
	{
		// TODO : imposer une limite de connexion par IP (https://symfony.com/doc/current/security.html#limiting-login-attempts).
		// TODO : ajouter la possibilité de se connecter via Token (https://symfony.com/doc/current/security/access_token.html).
		// TODO : ajouter la possibilité de se connecter via lien de connexion (https://symfony.com/doc/current/security/login_link.html).
		// TODO : ajouter la possibilité de se connecter via Google.
		// TODO : tester le "souvenir de la connexion" après authentification (en production).
		// TODO : ajouter une vérification avec Google reCAPTCHA.

		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_login", $request->request->get("token")))
		{
			return new Response($this->translator->trans("form.login.failed"), Response::HTTP_BAD_REQUEST);
		}

		// On récupère après toutes les informations de la requête.
		$username = $request->get("username");
		$password = $request->get("password");

		// On vérifie également si les informations sont valides.
		$user = new User();
		$user->setUsername($username);
		$user->setPassword($hasher->hashPassword($user, $password ?? ""));

		if (count($this->validator->validate($user)) > 0)
		{
			return new Response($this->translator->trans("form.server_check_failed"), Response::HTTP_BAD_REQUEST);
		}

		// On vérifie ensuite les informations de l'utilisateur.
		$user = $this->entityManager->getRepository(User::class)->findOneBy(["username" => $username]);

		if (!$user || !$hasher->isPasswordValid($user, $password))
		{
			return new Response($this->translator->trans("form.login.invalid"), Response::HTTP_BAD_REQUEST);
		}

		// On authentifie alors l'utilisateur.
		$this->security->login($user);

		// On envoie enfin la réponse au client.
		return new Response($this->translator->trans("form.login.success"), Response::HTTP_OK);
	}

	//
	// API vers le mécanisme de déconnexion de l'utilisateur.
	//  Source : https://symfony.com/doc/current/security.html#logout-programmatically
	//
	#[Route("/api/user/logout", methods: ["POST"], condition: "request.isXmlHttpRequest()")]
	#[IsGranted("IS_AUTHENTICATED")]
	public function logout(Request $request, ): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_logout", $request->request->get("token")))
		{
			return new Response($this->translator->trans("form.login.failed"), Response::HTTP_BAD_REQUEST);
		}

		// On déconnecte alors l'utilisateur.
		$this->security->logout(false);

		// On envoie enfin la réponse au client.
		return new Response($this->translator->trans("user.disconnected"), Response::HTTP_OK);
	}

	//
	// API vers le mécanisme des messages de contact.
	//
	#[Route("/api/user/contact", methods: ["POST"], condition: "request.isXmlHttpRequest()")]
	public function contact(Request $request): Response
	{
		// TODO : imposer une limite d'envoi de messages par jour.
		// TODO : ajouter une vérification avec Google reCAPTCHA.

		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_contact", $request->request->get("token")))
		{
			return new Response($this->translator->trans("form.contact.failed"), Response::HTTP_BAD_REQUEST);
		}

		// On récupère après toutes les informations de la requête.
		$email = $request->get("email");
		$subject = $request->get("subject");
		$content = $request->get("content");

		// On créé alors un nouvel objet de type "Contact".
		$contact = new Contact();
		$contact->setTimestamp(new \DateTime());
		$contact->setEmail($email);
		$contact->setSubject($subject);
		$contact->setContent($content);

		// On vérifie également si les informations sont valides.
		if (count($this->validator->validate($contact)) > 0)
		{
			return new Response($this->translator->trans("form.server_check_failed"), Response::HTTP_BAD_REQUEST);
		}

		// TODO : vérifier si Doctrine ne signale pas d'erreur (https://symfony.com/doc/current/doctrine.html#validating-objects).

		// On enregistre ensuite le message dans la base de données.
		$this->entityManager->getRepository(Contact::class)->save($contact, true);

		// TODO : envoyer un courriel à l'administrateur du site.

		// On envoie enfin la réponse au client.
		return new Response($this->translator->trans("form.contact.success"), Response::HTTP_OK);
	}

	//
	// API vers le mécanisme de mise à jour des informations de l'utilisateur.
	//
	#[Route("/api/user/update", methods: ["POST"], condition: "request.isXmlHttpRequest()")]
	#[IsGranted("IS_AUTHENTICATED")]
	public function update(Request $request, UserPasswordHasherInterface $hasher): Response
	{
		// TODO : vérifier les champs du formulaire.
		// TODO : ajouter une vérification avec Google reCAPTCHA.

		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_update", $request->request->get("token")))
		{
			return new Response($this->translator->trans("form.login.failed"), Response::HTTP_BAD_REQUEST);
		}

		// On tente de récupérer ensuite les informations de l'utilisateur.
		$user = $this->getUser();
		$repository = $this->entityManager->getRepository(User::class);
		$entity = $repository->findOneBy(["username" => $user->getUserIdentifier()]);

		if (!$entity)
		{
			return new Response($this->translator->trans("form.login.failed"), Response::HTTP_BAD_REQUEST);
		}

		// On récupère après toutes les informations de la requête.
		$username = $request->get("username");
		$password = $request->get("password");

		// On vérifie également si les informations sont valides.
		$user = new User();
		$user->setUsername($username);
		$user->setPassword($hasher->hashPassword($user, $password ?? ""));

		if (count($this->validator->validate($user)) > 0)
		{
			return new Response($this->translator->trans("form.server_check_failed"), Response::HTTP_BAD_REQUEST);
		}

		// On met à jour ensuite les informations de l'utilisateur.
		$entity->setUsername($username);
		$repository->upgradePassword($entity, $hasher->hashPassword($entity, $password));

		// On envoie enfin la réponse au client.
		return new Response($this->translator->trans("user.updated"), Response::HTTP_OK);
	}

	//
	// API vers le mécanisme de suppression du compte de l'utilisateur.
	//
	#[Route("/api/user/remove", methods: ["POST"], condition: "request.isXmlHttpRequest()")]
	#[IsGranted("IS_AUTHENTICATED")]
	public function remove(Request $request): Response
	{
		// TODO : ajouter une vérification avec Google reCAPTCHA.

		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_remove", $request->request->get("token")))
		{
			return new Response($this->translator->trans("form.login.failed"), Response::HTTP_BAD_REQUEST);
		}

		// On tente de récupérer ensuite les informations de l'utilisateur.
		$user = $this->getUser();
		$repository = $this->entityManager->getRepository(User::class);
		$entity = $repository->findOneBy(["username" => $user->getUserIdentifier()]);

		if (!$entity)
		{
			return new Response($this->translator->trans("form.login.failed"), Response::HTTP_BAD_REQUEST);
		}

		// On supprime ensuite l'utilisateur de la base de données avant de le déconnecter.
		$this->security->logout();
		$repository->remove($entity, true);

		// On déconnecte enfin l'utilisateur.
		return new Response($this->translator->trans("user.removed"), Response::HTTP_OK);
	}
}