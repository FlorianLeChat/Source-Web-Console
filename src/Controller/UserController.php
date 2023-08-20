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
use Symfony\Component\Process\Process;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

final class UserController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private readonly Security $security,
		private readonly ServerManager $serverManager,
		private readonly KernelInterface $kernel,
		private readonly ValidatorInterface $validator,
		private readonly TranslatorInterface $translator,
		private readonly EntityManagerInterface $entityManager,
		private readonly UserPasswordHasherInterface $hasher
	) {}

	//
	// Route vers la page de l'espace utilisateur.
	//
	#[Route("/user", name: "user_page")]
	public function index(): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("index_page");
		}

		// On affiche enfin la page de l'espace utilisateur.
		return $this->render("user.html.twig");
	}

	//
	// Route vers le mécanisme de création d'un accès unique.
	//
	#[Route("/onetime", name: "user_onetime", methods: ["GET"])]
	public function oneTime()
	{
		// Note : cette fonction ne doit pas être appelée directement par l'utilisateur,
		//  mais par le mécanisme de connexion à usage unique.
		return $this->redirectToRoute("index_page");
	}

	//
	// Routes vers le mécanisme de connexion de l'utilisateur via le protocole OAuth2.
	//  Note : https://github.com/thephpleague/oauth2-github/issues/24#issue-1689888969 (GitHub)
	//
	#[Route("/oauth/{name}/connect", name: "user_oauth_connect")]
	public function OAuthConnect(string $name, ClientRegistry $clientRegistry): RedirectResponse
	{
		$scopes = $name === "github" ? ["user"] : [];

		return $clientRegistry->getClient($name)->redirect($scopes, []);
	}

	#[Route("/oauth/{name}/check", name: "user_oauth_check")]
	public function OAuthCheck(): RedirectResponse
	{
		// Note : cette fonction ne doit pas être appelée directement par l'utilisateur,
		//  mais par le mécanisme de vérification du protocole OAuth2.
		return $this->redirectToRoute("index_page");
	}

	//
	// API vers le mécanisme de création de compte.
	//
	#[Route("/api/user/register", name: "user_register", methods: ["POST"])]
	public function register(Request $request, LoginLinkHandlerInterface $loginLinkHandler): Response|JsonResponse
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_register", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On enregistre les informations de l'utilisateur ainsi que celle du serveur.
		$user = new User();
		$server = new Server();

		$user->setUsername($username = $request->request->get("username"));
		$user->setPassword($this->hasher->hashPassword($user, $password = $request->request->get("password", "")));
		$user->setCreatedAt(new \DateTime());
		$user->setAddress($request->getClientIp());
		$user->setRoles(["ROLE_USER"]);

		$server->setAddress($serverAddress = $request->request->get("server_address"));
		$server->setPort($serverPort = $request->request->get("server_port"));
		$server->setPassword($serverPassword = $request->request->get("server_password"));
		$server->setUser($user);

		// On vérifie ensuite si le nom d'utilisateur n'est pas déjà utilisé.
		$repository = $this->entityManager->getRepository(User::class);

		if ($repository->findOneBy(["username" => $username]))
		{
			return new Response(
				$this->translator->trans("form.register.duplication"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie également si les informations sont valides.
		$oneTime = false;
		$userValidated = count($violations = $this->validator->validate($user)) === 0;
		$serverValidated = count($this->validator->validate($server)) === 0;

		if ($serverValidated)
		{
			// On réalise certaines modifications sur les données reçues
			//  après avoir passé la validation de Doctrine.
			if (!empty($serverAddress) && !empty($serverPort))
			{
				// Récupération de l'identifiant unique du jeu.
				$server->setGame($this->serverManager->getGameIDByAddress($serverAddress, $serverPort));
			}
			elseif (!empty($serverPassword))
			{
				// Chiffrement du mot de passe administrateur.
				$server->setPassword($this->serverManager->encryptPassword($serverPassword));
			}

			// On vérifie si l'utilisateur tente de créer un compte à usage unique.
			if (empty($username) && empty($password))
			{
				// Si c'est le cas et si les informations utilisateur sont vides,
				//  on indique que l'utilisateur cherche à créer un compte à usage unique.
				$oneTime = true;

				// On génère alors un nom d'utilisateur et un mot de passe aléatoire.
				//  Note : le mot de passe ne sera jamais utilisé, mais il est nécessaire
				//   pour que le mécanisme de connexion fonctionne.
				$user->setUsername(sprintf("temp_%s", bin2hex(random_bytes(10))));
				$user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(30))));

				// On génère un lien de connexion à usage unique.
				$details = $loginLinkHandler->createLoginLink($user);
				$link = $details->getUrl();
			}
			elseif (!$userValidated)
			{
				// Dans le cas inverse, on renvoie l'erreur traditionnelle.
				return new Response(
					$violations->get(0)->getMessage(),
					Response::HTTP_BAD_REQUEST
				);
			}
		}

		// On lance un processus de nettoyage des comptes inactifs en
		//  arrière-plan le plus tôt possible.
		$php = new PhpExecutableFinder();
		$process = new Process([
			$php->find(),
			sprintf("%s/bin/console", $this->kernel->getProjectDir()),
			"app:account-cleanup"
		]);

		$process->disableOutput();
		$process->run();

		// On enregistre après les informations dans la base de données.
		$repository->save($user);
		$this->entityManager->getRepository(Server::class)->save($server, true);

		// On envoie enfin la réponse au client.
		if ($oneTime)
		{
			// Réponse spécifique pour les comptes à usage unique.
			return new JsonResponse([
				"link" => $link ?? "",
				"message" => $this->translator->trans("form.register.onetime.success")
			], Response::HTTP_ACCEPTED);
		}
		else
		{
			// Authentification de l'utilisateur.
			$this->security->login($user, "remember_me");

			// Réponse classique pour les comptes normaux.
			return new Response(
				$this->translator->trans("form.register.success"),
				Response::HTTP_CREATED
			);
		}
	}

	//
	// API vers le mécanisme d'authentification de l'utilisateur.
	//
	#[Route("/api/user/login", name: "user_login", methods: ["POST"])]
	public function login(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_login", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie ensuite si les informations sont valides.
		$user = new User();
		$user->setUsername($username = $request->request->get("username"));
		$user->setPassword($this->hasher->hashPassword($user, $password = $request->request->get("password", "")));

		if (count($violations = $this->validator->validate($user)) > 0)
		{
			return new Response(
				$violations->get(0)->getMessage(),
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
		$this->security->login($user, "remember_me");

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
	//
	#[Route("/api/user/logout", name: "user_logout", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function logout(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_logout", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
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
	#[Route("/api/user/contact", name: "user_contact", methods: ["POST"])]
	public function contact(Request $request, MailerInterface $mailer): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_contact", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On créé ensuite un nouvel objet de type "Contact".
		$contact = new Contact();
		$contact->setDate(new \DateTime());
		$contact->setEmail($email = $request->request->get("email"));
		$contact->setSubject($subject = $request->request->get("subject"));
		$contact->setContent($content = $request->request->get("content"));

		// On vérifie alors si les informations sont valides.
		if (count($violations = $this->validator->validate($contact)) > 0)
		{
			return new Response(
				$violations->get(0)->getMessage(),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie si l'utilisateur n'a pas déjà envoyé un message aujourd'hui.
		$repository = $this->entityManager->getRepository(Contact::class);
		$query = $repository->createQueryBuilder("c");
		$query->where($query->expr()->eq("c.email", ":email"))
			->setParameter("email", $email);
		$query->andWhere($query->expr()->gte("c.date", ":past"))
			->setParameter("past", new \DateTime("-1 day"), \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE);

		if ($query->getQuery()->getResult())
		{
			return new Response(
				$this->translator->trans("form.contact.too_much"),
				Response::HTTP_TOO_MANY_REQUESTS
			);
		}

		// On enregistre après le message dans la base de données.
		$repository->save($contact, true);

		// On génère le courriel de confirmation qui sera envoyé à l'utilisateur.
		$email = (new Email())
			->to($email)
			->text($this->translator->trans("form.contact.mailing", [$content]))
			->subject($subject);

		// On tente de signer également le courriel en utilisant DKIM.
		//  Source : https://symfony.com/doc/current/mailer.html#signing-messages
		if (is_file($path = $this->getParameter("app.dkim_private_key")))
		{
			$signer = new DkimSigner(
				$path,
				$this->getParameter("app.dkim_domain"),
				$this->getParameter("app.dkim_selector")
			);

			$signedEmail = $signer->sign($email);
		}

		try
		{
			// On envoie le courriel à l'utilisateur.
			$mailer->send($signedEmail ?? $email);
		}
		catch (\Exception $error)
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
			Response::HTTP_CREATED
		);
	}

	//
	// API vers le mécanisme de mise à jour des informations de l'utilisateur.
	//
	#[Route("/api/user/update", name: "user_update", methods: ["PUT"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function update(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_update", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie ensuite si les informations sont valides.
		$user = new User();
		$user->setUsername($username = $request->request->get("username"));
		$user->setPassword($password = $request->request->get("password"));

		if (count($violations = $this->validator->validate($user)) > 0)
		{
			return new Response(
				$violations->get(0)->getMessage(),
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
			Response::HTTP_CREATED
		);
	}

	//
	// API vers le mécanisme de récupération du mot de passe de l'utilisateur.
	//
	#[Route("/api/user/recover", name: "user_recover", methods: ["PUT"])]
	public function recover(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_login", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie ensuite si les informations sont valides.
		$user = new User();
		$user->setUsername($username = $request->request->get("username"));
		$user->setPassword($password = $request->request->get("password"));

		if (count($violations = $this->validator->validate($user)) > 0)
		{
			return new Response(
				$violations->get(0)->getMessage(),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie également les informations de l'utilisateur.
		$repository = $this->entityManager->getRepository(User::class);

		if (!$user = $repository->findOneBy(["username" => $username]))
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
			Response::HTTP_CREATED
		);
	}

	//
	// API vers le mécanisme de suppression du compte de l'utilisateur.
	//
	#[Route("/api/user/remove", name: "user_remove", methods: ["DELETE"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function remove(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_remove", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On supprime ensuite l'utilisateur de la base de données avant de le déconnecter.
		/** @var User */
		$user = $this->getUser();
		$this->entityManager->getRepository(User::class)->remove($user, true);
		$this->security->logout(false);

		// On déconnecte enfin l'utilisateur.
		return new Response(
			$this->translator->trans("user.removed"),
			Response::HTTP_OK
		);
	}

	//
	// API vers le mécanisme d'ajout d'un nouveau serveur.
	//
	#[Route("/api/server/new", name: "server_new", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function new(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("user_register", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie après si l'utilisateur n'a pas déjà atteint
		//  la limite de serveurs pour son compte.
		/** @var User */
		$user = $this->getUser();
		$repository = $this->entityManager->getRepository(Server::class);

		if ($repository->count(["user" => $user]) >= ($this->isGranted("ROLE_DONOR") ? 10 : 3))
		{
			return new Response(
				$this->translator->trans("user.too_much"),
				Response::HTTP_TOO_MANY_REQUESTS
			);
		}

		// On enregistre ensuite les informations du nouveau serveur.
		$server = new Server();
		$server->setAddress($address = $request->request->get("server_address"));
		$server->setPort($port = $request->request->get("server_port"));
		$server->setPassword($password = $request->request->get("server_password"));
		$server->setUser($user);

		// On vérifie également si les informations sont valides.
		if (count($violations = $this->validator->validate($server)) > 0)
		{
			return new Response(
				$violations->get(0)->getMessage(),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On réalise certaines modifications sur les données reçues
		//  après avoir passé la validation de Doctrine.
		if (!empty($address) && !empty($port))
		{
			// Récupération de l'identifiant unique du jeu.
			$server->setGame($this->serverManager->getGameIDByAddress($address, $port));
		}
		elseif (!empty($password))
		{
			// Chiffrement du mot de passe administrateur.
			$server->setPassword($this->serverManager->encryptPassword($password));
		}

		// On enregistre alors les informations dans la base de données.
		$repository->save($server, true);

		// On envoie enfin la réponse au client.
		return new Response(
			$this->translator->trans("user.insert"),
			Response::HTTP_CREATED
		);
	}
}