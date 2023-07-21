<?php

//
// Contrôleur de la page de configuration des informations de stockage.
//
namespace App\Controller;

use App\Entity\Server;
use App\Entity\Storage;
use App\Service\ServerManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConfigurationController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private ServerManager $serverManager,
		private ValidatorInterface $validator,
		private TranslatorInterface $translator,
		private EntityManagerInterface $entityManager,
	) {}

	//
	// Route vers la page de configuration des informations de stockage.
	//
	#[Route("/configuration", name: "configuration_page")]
	public function index(Request $request): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("index_page");
		}

		// On inclut enfin les paramètres du moteur TWIG pour la création de la page.
		$repository = $this->entityManager->getRepository(Storage::class);

		return $this->render("configuration.html.twig", [
			// Identifiants du serveur de stockage.
			"configuration_credentials" => $repository->findOneBy([
				"server" => intval($request->getSession()->get("serverId", 0))
			])
		]);
	}

	//
	// API vers l'ajout ou la mise à jour des informations de stockage.
	//
	#[Route("/api/server/storage", name: "configuration_update", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function add(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("configuration_update", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie alors que le serveur existe bien et qu'il appartient à l'utilisateur.
		$repository = $this->entityManager->getRepository(Storage::class);
		$server = $this->entityManager->getRepository(Server::class)->findOneBy([
			"id" => intval($request->getSession()->get("serverId", 0)),
			"user" => $this->getUser()
		]);

		if (!$server)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie après que les informations de stockage sont valides.
		$storage = $repository->findOneBy(["server" => $server]) ?? new Storage();
		$storage->setServer($server);

		if (!empty($username = $request->request->get("address")))
		{
			// Adresse IP.
			$storage->setAddress($username);
		}

		if (!empty($port = $request->request->get("port")))
		{
			// Port de communication.
			$storage->setPort($port);
		}

		if (!empty($protocol = $request->request->get("protocol")))
		{
			// Protocole d'accès.
			$storage->setProtocol($protocol);
		}

		if (!empty($username = $request->request->get("username")))
		{
			// Nom d'utilisateur.
			$storage->setUsername($username);
		}

		// Mot de passe.
		$password = $request->request->get("password");
		$storage->setPassword(!empty($password) ? $this->serverManager->encryptPassword($password) : null);

		if (count($this->validator->validate($storage)) > 0)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On sauvegarde enfin les informations dans la base de données
		//  et on retourne une réponse de succès.
		$repository->save($storage, true);

		return new Response(
			$this->translator->trans("configuration.updated"),
			Response::HTTP_CREATED
		);
	}
}