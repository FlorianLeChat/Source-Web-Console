<?php

//
// Authentification pour les requêtes AJAX.
//  Source : https://symfony.com/doc/current/security/custom_authenticator.html
//
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class AjaxAuthenticator extends AbstractLoginFormAuthenticator
{
	private $urlGenerator;

	//
	// Permet d'initialiser les dépendances du contrôleur.
	//
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

	//
	// Permet de récupérer l'URL vers l'API de connexion.
	//
	protected function getLoginUrl(Request $request): string
	{
		return $this->urlGenerator->generate("app_user_login");
	}

	//
	// Permet de récupérer les informations d'authentification.
	//
	public function authenticate(Request $request): Passport
	{
		$username = $request->request->get("username");
		$password = $request->request->get("password");

		if (!$username || !$password)
		{
			throw new CustomUserMessageAuthenticationException("Identifiants manquants");
		}

		return new Passport(new UserBadge($username), new PasswordCredentials($password));
	}

	//
	// Permet de gérer les succès d'authentification.
	//
	public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
	{
		return null;
	}
}