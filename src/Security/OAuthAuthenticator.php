<?php

//
// Authentification via le protocole OAuth2.
//
namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class OAuthAuthenticator extends OAuth2Authenticator
{
	//
	// Initialisation de certaines dépendances du service.
	//
	public function __construct(
		private readonly ClientRegistry $clientRegistry,
		private readonly RouterInterface $router,
		private readonly EntityManagerInterface $entityManager,
		private readonly UserPasswordHasherInterface $hasher
	) {}

	//
	// Définition de la route à utiliser pour l'authentification.
	//
	public function supports(Request $request): ?bool
	{
		return $request->attributes->get("_route") === "user_oauth_check";
	}

	//
	// Mécanisme d'authentification.
	//
	public function authenticate(Request $request): Passport
	{
		// On récupère d'abord le jeton d'accès fourni par Google.
		$api = $request->attributes->get("name");
		$client = $this->clientRegistry->getClient($api);
		$accessToken = $this->fetchAccessToken($client);

		// On retourne alors le droit d'accès à l'utilisateur.
		return new SelfValidatingPassport(
			new UserBadge($accessToken->getToken(), function () use ($accessToken, $request, $client)
			{
				// On tente de récupérer un utilisateur existant.
				/** @var GoogleUser */
				$user = $client->fetchUserFromToken($accessToken);
				$repository = $this->entityManager->getRepository(User::class);
				$existingUser = $repository->findOneBy(["token" => $user->getId()]);
				$clientAddress = $request->getClientIp();

				if ($existingUser)
				{
					// Si l'utilisateur existe déjà, on met à jour son adresse IP
					//  dans la base de données et on le retourne.
					return $existingUser->setAddress($clientAddress);
				}

				// Dans le cas contraire, on crée un nouvel utilisateur
				//  avec les informations fournies par l'API.
				$email = explode("@", $user->getEmail());

				$existingUser = new User();
				$existingUser->setUsername(mb_substr($email[0], 0, 30));
				$existingUser->setPassword($this->hasher->hashPassword($existingUser, $email[0]));
				$existingUser->setCreatedAt(new \DateTime());
				$existingUser->setAddress($clientAddress);
				$existingUser->setRoles(["ROLE_USER"]);
				$existingUser->setToken($user->getId());

				// On enregistre les modifications dans la base de données
				//  et on retourne enfin l'utilisateur.
				$repository->save($existingUser, true);

				return $existingUser;
			})
		);
	}

	//
	// Redirection de l'utilisateur après authentification.
	//
	public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
	{
		return new RedirectResponse($this->router->generate("user_page", ["OAuth" => true]));
	}

	//
	// Réponse d'échec d'authentification.
	//
	public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
	{
		return new Response(
			strtr($exception->getMessageKey(), $exception->getMessageData()),
			Response::HTTP_FORBIDDEN
		);
	}
}