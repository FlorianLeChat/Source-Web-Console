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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
	//
	// Route vers la page de l'espace utilisateur.
	//
	#[Route("/user")]
	public function index(): Response
	{
		// On vérifie d'abord que l'utilisateur est bien authentifié.
		$this->denyAccessUnlessGranted("IS_AUTHENTICATED");

		// Si c'est le cas, on le redirige enfin vers l'espace utilisateur.
		return $this->render("user.html.twig");
	}

	//
	// API vers le mécanisme de création de compte.
	//  Source : https://symfony.com/doc/current/security.html#registering-the-user-hashing-passwords
	//
	#[Route("/api/user/register", methods: ["POST"], condition: "request.isXmlHttpRequest()")]
	public function register(Request $request, Security $security, TranslatorInterface $translator, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): JsonResponse
	{
		// TODO : imposer une limite de création par IP.
		// TODO : vérifier les champs du formulaire.
		// TODO : ajouter la protection CSRF (https://symfony.com/doc/current/security.html#csrf-protection-in-login-forms).
		// TODO : ajouter une vérification contre les noms d'utilisateurs dupliqués.
		// TODO : ajouter la possibilité de créer un compte via Google.
		// TODO : ajouter la possibilité de se souvenir de la connexion après création de compte.
		// TODO : ajouter une vérification avec Google reCAPTCHA.

		// On récupère d'abord toutes les informations de la requête.
		$username = $request->request->get("username");
		$password = $request->request->get("password");
		$serverAddress = $request->request->get("server_address");
		$serverPort = $request->request->get("server_port");
		$serverPassword = $request->request->get("server_password");

		// On enregistre ensuite les informations de l'utilisateur ainsi que celle du serveur.
		$user = new User();
		$server = new Server();

		$user->setUsername($username);
		$user->setPassword($hasher->hashPassword($user, $password));

		$server->setAddress($serverAddress);
		$server->setPassword($serverPassword);
		$server->setPort($serverPort);
		$server->setClient($user);

		// On enregistre après les informations dans la base de données.
		$entityManager->getRepository(User::class)->save($user);
		$entityManager->getRepository(Server::class)->save($server, true);

		// On authentifie alors l'utilisateur.
		$security->login($user, "form_login");

		// On envoie enfin la réponse au client.
		return new JsonResponse([
			"code" => 2,
			"message" => $translator->trans("form.register.success")
		]);
	}

	//
	// API vers le mécanisme d'authentification de l'utilisateur.
	//  Source : https://symfony.com/doc/current/security.html#form-login
	//
	#[Route("/api/user/login", condition: "request.isXmlHttpRequest()")]
	public function login(AuthenticationUtils $authenticationUtils, TranslatorInterface $translator): Response|JsonResponse
	{
		// TODO : imposer une limite de connexion par IP (https://symfony.com/doc/current/security.html#limiting-login-attempts).
		// TODO : vérifier les champs du formulaire.
		// TODO : ajouter la possibilité de se connecter via Token (https://symfony.com/doc/current/security/access_token.html).
		// TODO : ajouter la possibilité de se connecter via lien de connexion (https://symfony.com/doc/current/security/login_link.html).
		// TODO : ajouter la possibilité de se connecter via Google.
		// TODO : tester le "souvenir de la connexion" après authentification (en production).
		// TODO : ajouter une vérification avec Google reCAPTCHA.

		// On vérifie si l'authentification a réussie ou non.
		if ($authenticationUtils->getLastAuthenticationError())
		{
			return new Response($translator->trans("form.login.invalid"));
		}

		return new JsonResponse([
			"code" => 2,
			"message" => $translator->trans("form.login.success")
		]);
	}

	//
	// API vers le mécanisme de déconnexion de l'utilisateur.
	//  Source : https://symfony.com/doc/current/security.html#logout-programmatically
	//
	#[Route("/api/user/logout", methods: ["POST"], condition: "request.isXmlHttpRequest()")]
	public function logout(): void
	{
		throw new \Exception("This method can be blank - it will be intercepted by the logout key on the firewall.");
	}

	//
	// API vers le mécanisme des messages de contact.
	//
	#[Route("/api/user/contact", methods: ["POST"], condition: "request.isXmlHttpRequest()")]
	public function contact(Request $request, TranslatorInterface $translator, EntityManagerInterface $entityManager): JsonResponse
	{
		// TODO : imposer une limite d'envoi de messages par jour.
		// TODO : vérifier les champs du formulaire.
		// TODO : ajouter la protection CSRF (https://symfony.com/doc/current/security.html#csrf-protection-in-login-forms).
		// TODO : ajouter une vérification avec Google reCAPTCHA.

		// On récupère d'abord toutes les informations de la requête.
		$email = $request->request->get("email");
		$subject = $request->request->get("subject");
		$content = $request->request->get("content");

		// On créé alors un nouvel objet de type "Contact".
		$contact = new Contact();
		$contact->setTimestamp(new \DateTime());
		$contact->setEmail($email);
		$contact->setSubject($subject);
		$contact->setContent($content);

		// TODO : vérifier si Doctrine ne signale pas d'erreur (https://symfony.com/doc/current/doctrine.html#validating-objects).

		// On enregistre ensuite le message dans la base de données.
		$entityManager->getRepository(Contact::class)->save($contact, true);

		// TODO : envoyer un courriel à l'administrateur du site.

		// On envoie enfin la réponse au client.
		return new JsonResponse([
			"code" => 2,
			"message" => $translator->trans("form.contact.success")
		]);
	}
}