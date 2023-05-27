<?php

//
// Contrôleur de la page de l'espace utilisateur.
//
namespace App\Controller;

use App\Entity\User;
use App\Entity\Server;
use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
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
	//
	#[Route("/api/user/register", methods: ["POST"], condition: "request.isXmlHttpRequest()")]
    public function register(Request $request, TranslatorInterface $translator, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): JsonResponse
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

		// On enregistre ensuite les informations de l'utilisateur
		//  ainsi que celle du serveur.
		$user = new User();
		$server = new Server();

		$hashedPassword = $hasher->hashPassword(
			$user,
			$password
		);

		$user->setUsername($username);
		$user->setPassword($hashedPassword);

		// TODO : chiffrer le mot de passe du serveur avant de l'enregistrer.

		$server->setAddress($serverAddress);
		$server->setPassword($serverPassword);
		$server->setPort($serverPort);
		$server->setClient($user);

		// On enregistre après les informations dans la base de données.
		$entityManager->persist($user);
		$entityManager->persist($server);
		$entityManager->flush();

		// On envoie enfin la réponse au client.
		return new JsonResponse([$translator->trans("form.register.success"), 2]);
    }

	//
	// API vers le mécanisme d'authentification.
	//
	#[Route("/api/user/login", methods: ["POST"], condition: "request.isXmlHttpRequest()")]
    public function login(AuthenticationUtils $authenticationUtils, TranslatorInterface $translator): Response
    {
		// TODO : vérifier si l'utilisateur est déjà connecté.
		// TODO : imposer une limite de connexion par IP (https://symfony.com/doc/current/security.html#limiting-login-attempts).
		// TODO : vérifier les champs du formulaire.
		// TODO : ajouter la protection CSRF (https://symfony.com/doc/current/security.html#csrf-protection-in-login-forms).
		// TODO : ajouter la possibilité de se connecter via Token (https://symfony.com/doc/current/security/access_token.html).
		// TODO : ajouter la possibilité de se connecter via lien de connexion (https://symfony.com/doc/current/security/login_link.html).
		// TODO : ajouter la possibilité de se connecter via Google.
		// TODO : ajouter la possibilité de se souvenir de la connexion.
		// TODO : ajouter une vérification avec Google reCAPTCHA.

		// On vérifie si l'authentification a réussie ou non.
		$error = $authenticationUtils->getLastAuthenticationError();

		if ($error)
		{
			return new JsonResponse([$translator->trans("form.login.invalid"), 1]);
		}

		return new JsonResponse([$translator->trans("form.contact.success"), 2]);
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
		$entityManager->persist($contact);
		$entityManager->flush();

		// TODO : envoyer un courriel à l'administrateur du site.

		// On envoie enfin la réponse au client.
		return new JsonResponse([$translator->trans("form.contact.success"), 2]);
	}
}