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
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

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
		if (null === null)
		{
			throw new CustomUserMessageAuthenticationException("No API token provided");
		}

		return new SelfValidatingPassport(new UserBadge("florian4016"));
	}

	//
	// Permet de gérer les succès d'authentification.
	//
	public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
	{
		return null;
	}
}