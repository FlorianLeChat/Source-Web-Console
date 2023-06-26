<?php

//
// Contrôleur de la page de l'espace utilisateur.
//
namespace App\Controller;

use App\Entity\User;
use App\Entity\Server;
use App\Entity\Contact;
use App\Service\ServerManager;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\IpUtils;
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
		private ServerManager $serverManager,
		private ValidatorInterface $validator,
		private TranslatorInterface $translator,
		private EntityManagerInterface $entityManager,
		private UserPasswordHasherInterface $hasher
	) {}

	//
	// Route vers la page de l'espace utilisateur.
	//
	#[Route("/user", name: "app_user_page")]
	public function index(): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("app_index_page");
		}

		// On affiche enfin la page de l'espace utilisateur.
		return $this->render("user.html.twig");
	}

	//
	// API vers le mécanisme de création de compte.
	//  Source : https://symfony.com/doc/current/security.html#registering-the-user-hashing-passwords
	//
	#[Route("/api/user/register", name: "app_user_register", methods: ["POST"])]
	public function register(Request $request): Response
	{
		// TODO : imposer une limite de création par IP.
		// TODO : ajouter la possibilité de créer un compte via Google.
		// TODO : ajouter la possibilité de se souvenir de la connexion après création de compte.

		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_register", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.register.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On enregistre les informations de l'utilisateur ainsi que celle du serveur.
		$user = new User();
		$server = new Server();

		$user->setUsername($username = $request->request->get("username"));
		$user->setPassword($this->hasher->hashPassword($user, $request->request->get("password", "")));
		$user->setAddress($request->getClientIp());

		$server->setAddress($address = $request->request->get("server_address"));
		$server->setPort($port = intval($request->request->get("server_port")));
		$server->setPassword($password = $request->request->get("server_password"));
		$server->setGame($this->serverManager->getGameIDByAddress($address, $port));
		$server->setClient($user);

		// On vérifie ensuite si le nom d'utilisateur n'est pas déjà utilisé.
		$repository = $this->entityManager->getRepository(User::class);

		if ($repository->findOneBy(["username" => $username]))
		{
			return new Response(
				$this->translator->trans("form.register.duplication"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On chiffre le mot de passe administrateur s'il est renseigné
		//  pour des raisons de sécurité évidentes.
		if (!empty($password))
		{
			$server->setPassword($this->serverManager->encryptPassword($password));
		}

		// On vérifie également si les informations sont valides.
		if (count($this->validator->validate($user)) > 0 || count($this->validator->validate($server)) > 0)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On enregistre après les informations dans la base de données.
		$repository->save($user);
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
	#[Route("/api/user/login", name: "app_user_login", methods: ["POST"])]
	public function login(Request $request): Response
	{
		// TODO : imposer une limite de connexion par IP (https://symfony.com/doc/current/security.html#limiting-login-attempts).
		// TODO : ajouter la possibilité de se connecter via Token (https://symfony.com/doc/current/security/access_token.html).
		// TODO : ajouter la possibilité de se connecter via lien de connexion (https://symfony.com/doc/current/security/login_link.html).
		// TODO : ajouter la possibilité de se connecter via Google.
		// TODO : tester le "souvenir de la connexion" après authentification (en production).

		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_login", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.login.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie ensuite si les informations sont valides.
		$user = new User();
		$user->setUsername($username = $request->request->get("username"));
		$user->setPassword($this->hasher->hashPassword($user, $password = $request->request->get("password", "")));

		if (count($this->validator->validate($user)) > 0)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie également les informations de l'utilisateur.
		$repository = $this->entityManager->getRepository(User::class);
		$user = $repository->findOneBy(["username" => $username]);

		if (!$user || !$this->hasher->isPasswordValid($user, $password))
		{
			return new Response(
				$this->translator->trans("form.login.invalid"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On authentifie alors l'utilisateur.
		$this->security->login($user);

		// On met à jour après l'adresse IP de l'utilisateur
		//  dans la base de données.
		/** @var User */
		$user = $this->getUser();
		$user->setAddress($request->getClientIp());

		$repository->save($user, true);

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
	#[Route("/api/user/logout", name: "app_user_logout", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function logout(Request $request, ): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_logout", $request->request->get("token")))
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
	#[Route("/api/user/contact", name: "app_user_contact", methods: ["POST"])]
	public function contact(Request $request, MailerInterface $mailer): Response
	{
		// TODO : imposer une limite d'envoi de messages par jour avec la même adresse IP.

		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_contact", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.contact.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On créé ensuite un nouvel objet de type "Contact".
		$contact = new Contact();
		$contact->setEmail($email = $request->request->get("email"));
		$contact->setSubject($subject = $request->request->get("subject"));
		$contact->setContent($content = $request->request->get("content"));
		$contact->setTimestamp(new \DateTime());

		// On vérifie alors si les informations sont valides.
		if (count($this->validator->validate($contact)) > 0)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On enregistre après le message dans la base de données.
		$this->entityManager->getRepository(Contact::class)->save($contact, true);

		// On génère le courriel de confirmation qui sera envoyé à l'utilisateur.
        $email = (new Email())
			->to($email)
			->text($this->translator->trans("form.contact.mailing", [$content]))
			->subject($subject);

		// On tente de signer également le courriel en utilisant DKIM.
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
	#[Route("/api/user/update", name: "app_user_update", methods: ["PUT"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function update(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_update", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.login.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie ensuite si les informations sont valides.
		$user = new User();
		$user->setUsername($username = $request->request->get("username"));
		$user->setPassword($password = $request->request->get("password"));

		if (count($this->validator->validate($user)) > 0)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On met à jour alors les informations de l'utilisateur
		//  dans la base de données.
		/** @var User */
		$user = $this->getUser();
		$user->setUsername($username);
		$user->setPassword($this->hasher->hashPassword($user, $password));
		$user->setAddress($request->getClientIp());

		$this->entityManager->getRepository(User::class)->save($user, true);

		// On envoie enfin la réponse au client.
		return new Response(
			$this->translator->trans("user.updated"),
			Response::HTTP_OK
		);
	}

	//
	// API vers le mécanisme de récupération du mot de passe de l'utilisateur.
	//
	#[Route("/api/user/recover", name: "app_user_recover", methods: ["PUT"])]
	public function recover(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_login", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.login.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie ensuite si les informations sont valides.
		$user = new User();
		$user->setUsername($username = $request->request->get("username"));
		$user->setPassword($password = $request->request->get("password"));

		if (count($this->validator->validate($user)) > 0)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie également les informations de l'utilisateur.
		$repository = $this->entityManager->getRepository(User::class);
		$user = $repository->findOneBy(["username" => $username]);

		if (!$user)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie après si l'adresse IP enregistrée dans la base de données
		//  correspond bien à celle de l'utilisateur.
		$address = $request->getClientIp();

		if (!IpUtils::checkIp(IpUtils::anonymize($address), $user->getAddress()))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On met à jour alors les informations de l'utilisateur
		//  dans la base de données.
		$user->setPassword($this->hasher->hashPassword($user, $password));
		$user->setAddress($address);

		$repository->save($user, true);

		// On envoie enfin la réponse au client.
		return new Response(
			$this->translator->trans("user.updated"),
			Response::HTTP_OK
		);
	}

	//
	// API vers le mécanisme de suppression du compte de l'utilisateur.
	//
	#[Route("/api/user/remove", name: "app_user_remove", methods: ["DELETE"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function remove(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_remove", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.login.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On supprime ensuite l'utilisateur de la base de données avant de le déconnecter.
		$this->security->logout();
		$this->entityManager->getRepository(User::class)->remove($this->getUser(), true);

		// On déconnecte enfin l'utilisateur.
		return new Response(
			$this->translator->trans("user.removed"),
			Response::HTTP_OK
		);
	}

	//
	// API vers le mécanisme d'ajout d'un nouveau serveur.
	//
	#[Route("/api/server/new", name: "app_server_new", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function new(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_register", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.register.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On enregistre ensuite les informations du nouveau serveur.
		$server = new Server();

		$server->setAddress($address = $request->request->get("server_address"));
		$server->setPort($port = intval($request->request->get("server_port")));
		$server->setPassword($password = $request->request->get("server_password"));
		$server->setGame($this->serverManager->getGameIDByAddress($address, $port));
		$server->setClient($this->getUser());

		// On chiffre le mot de passe administrateur s'il est renseigné
		//  pour des raisons de sécurité évidentes.
		if (!empty($password))
		{
			$server->setPassword($this->serverManager->encryptPassword($password));
		}

		// On vérifie également si les informations sont valides.
		if (count($this->validator->validate($server)) > 0)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On enregistre alors les informations dans la base de données.
		$this->entityManager->getRepository(Server::class)->save($server, true);

		// On envoie enfin la réponse au client.
		return new Response(
			$this->translator->trans("user.insert"),
			Response::HTTP_OK
		);
	}
}