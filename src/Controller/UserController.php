<?php

//
// Contrôleur de la page de l'espace utilisateur.
//
namespace App\Controller;

use App\Entity\User;
use App\Entity\Server;
use App\Entity\Contact;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
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
		$this->validator = $validator;
		$this->translator = $translator;
		$this->entityManager = $entityManager;
	}

	//
	// Route vers la page de l'espace utilisateur.
	//
	#[Route("/user")]
	public function index(): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("app_index_index");
		}

		// On affiche enfin la page de l'espace utilisateur.
		return $this->render("user.html.twig");
	}

	//
	// API vers le mécanisme de création de compte.
	//  Source : https://symfony.com/doc/current/security.html#registering-the-user-hashing-passwords
	//
	#[Route("/api/user/register", methods: ["POST"])]
	public function register(Request $request, UserPasswordHasherInterface $hasher): Response
	{
		// TODO : imposer une limite de création par IP.
		// TODO : ajouter la possibilité de créer un compte via Google.
		// TODO : ajouter la possibilité de se souvenir de la connexion après création de compte.

		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_register", $request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.register.failed"),
				Response::HTTP_BAD_REQUEST
			);
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
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie si le nom d'utilisateur n'est pas déjà utilisé.
		$userRepository = $this->entityManager->getRepository(User::class);

		if ($userRepository->findOneBy(["username" => $username]))
		{
			return new Response(
				$this->translator->trans("form.register.duplication"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On enregistre les informations dans la base de données.
		$userRepository->save($user);
		$this->entityManager->getRepository(Server::class)->save($server, true);

		// On authentifie alors l'utilisateur.
		$this->security->login($user, "form_login");

		// On envoie enfin la réponse au client.
		return new Response(
			$this->translator->trans("form.register.success"),
			Response::HTTP_OK
		);
	}

	//
	// API vers le mécanisme d'authentification de l'utilisateur.
	//  Source : https://symfony.com/doc/current/security.html#login-programmatically
	//
	#[Route("/api/user/login", methods: ["POST"])]
	public function login(Request $request, UserPasswordHasherInterface $hasher): Response
	{
		// TODO : imposer une limite de connexion par IP (https://symfony.com/doc/current/security.html#limiting-login-attempts).
		// TODO : ajouter la possibilité de se connecter via Token (https://symfony.com/doc/current/security/access_token.html).
		// TODO : ajouter la possibilité de se connecter via lien de connexion (https://symfony.com/doc/current/security/login_link.html).
		// TODO : ajouter la possibilité de se connecter via Google.
		// TODO : tester le "souvenir de la connexion" après authentification (en production).

		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_login", $request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.login.failed"),
				Response::HTTP_BAD_REQUEST
			);
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
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie ensuite les informations de l'utilisateur.
		$user = $this->entityManager->getRepository(User::class)->findOneBy(["username" => $username]);

		if (!$user || !$hasher->isPasswordValid($user, $password))
		{
			return new Response(
				$this->translator->trans("form.login.invalid"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On authentifie alors l'utilisateur.
		$this->security->login($user);

		// On envoie enfin la réponse au client.
		return new Response(
			$this->translator->trans("form.login.success"),
			Response::HTTP_OK
		);
	}

	//
	// API vers le mécanisme de déconnexion de l'utilisateur.
	//  Source : https://symfony.com/doc/current/security.html#logout-programmatically
	//
	#[Route("/api/user/logout", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function logout(Request $request, ): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_logout", $request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.login.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On déconnecte alors l'utilisateur.
		$this->security->logout(false);

		// On envoie enfin la réponse au client.
		return new Response(
			$this->translator->trans("user.disconnected"),
			Response::HTTP_OK
		);
	}

	//
	// API vers le mécanisme des messages de contact.
	//
	#[Route("/api/user/contact", methods: ["POST"])]
	public function contact(Request $request, MailerInterface $mailer): Response
	{
		// TODO : imposer une limite d'envoi de messages par jour avec la même adresse IP.

		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_contact", $request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.contact.failed"),
				Response::HTTP_BAD_REQUEST
			);
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
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On enregistre ensuite le message dans la base de données.
		$this->entityManager->getRepository(Contact::class)->save($contact, true);

		// On génère le courriel de confirmation qui sera envoyé à l'utilisateur.
        $email = (new Email())
			->to($email)
			->text($this->translator->trans("form.contact.mailing", [$content]))
			->subject($subject);

		// On tente de signer le courriel en utilisant DKIM.
		//  Source : https://symfony.com/doc/current/mailer.html#signing-messages
		if (is_file($path = $this->getParameter("app.dkim_private_key")))
		{
			$signer = new DkimSigner($path, $this->getParameter("app.dkim_domain"), $this->getParameter("app.dkim_selector"));
			$signedEmail = $signer->sign($email);
		}

		try
		{
			// On envoie le courriel à l'utilisateur.
			$mailer->send($signedEmail ?? $email);
		}
		catch (TransportExceptionInterface $error)
		{
			// En cas d'erreur, on renvoie le message d'erreur à l'utilisateur.
			return new Response(
				$error->getMessage(),
				Response::HTTP_INTERNAL_SERVER_ERROR
			);
		}

		// On envoie enfin la réponse au client.
		return new Response(
			$this->translator->trans("form.contact.success"),
			Response::HTTP_OK
		);
	}

	//
	// API vers le mécanisme de mise à jour des informations de l'utilisateur.
	//
	#[Route("/api/user/update", methods: ["PUT"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function update(Request $request, UserPasswordHasherInterface $hasher): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_update", $request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.login.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On tente de récupérer ensuite les informations de l'utilisateur.
		$repository = $this->entityManager->getRepository(User::class);

		if (!$entity = $repository->findOneBy(["username" => $this->getUser()->getUserIdentifier()]))
		{
			return new Response(
				$this->translator->trans("form.login.failed"),
				Response::HTTP_BAD_REQUEST
			);
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
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On met à jour ensuite les informations de l'utilisateur.
		$entity->setUsername($username);
		$repository->upgradePassword($entity, $hasher->hashPassword($entity, $password));

		// On envoie enfin la réponse au client.
		return new Response(
			$this->translator->trans("user.updated"),
			Response::HTTP_OK
		);
	}

	//
	// API vers le mécanisme de suppression du compte de l'utilisateur.
	//
	#[Route("/api/user/remove", methods: ["DELETE"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function remove(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_remove", $request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.login.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On tente de récupérer ensuite les informations de l'utilisateur.
		$user = $this->getUser();
		$repository = $this->entityManager->getRepository(User::class);
		$entity = $repository->findOneBy(["username" => $user->getUserIdentifier()]);

		if (!$entity)
		{
			return new Response(
				$this->translator->trans("form.login.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On supprime ensuite l'utilisateur de la base de données avant de le déconnecter.
		$this->security->logout();
		$repository->remove($entity, true);

		// On déconnecte enfin l'utilisateur.
		return new Response(
			$this->translator->trans("user.removed"),
			Response::HTTP_OK
		);
	}
}