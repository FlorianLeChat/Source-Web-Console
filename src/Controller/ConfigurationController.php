<?php

//
// Contrôleur de la page de configuration des informations de stockage.
//
namespace App\Controller;

use App\Entity\Server;
use App\Entity\Storage;
use App\Service\ServerManager;
use App\Service\StorageManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ConfigurationController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private readonly ServerManager $serverManager,
		private readonly StorageManager $storageManager,
		private readonly ValidatorInterface $validator,
		private readonly TranslatorInterface $translator,
		private readonly EntityManagerInterface $entityManager,
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
	#[Route("/api/server/storage", name: "storage_update", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function storage(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("storage_update", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.csrf_token"),
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

		if (count($violations = $this->validator->validate($storage)) > 0)
		{
			return new Response(
				$violations->get(0)->getMessage(),
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

	//
	// API vers la mise à jour de la configuration du serveur.
	//
	#[Route("/api/server/configuration", name: "configuration_update", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function configuration(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		$type = $request->request->get("type", "none");

		if (!$this->isCsrfTokenValid("configuration_$type", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.csrf_token"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie alors que le serveur existe bien et qu'il appartient à l'utilisateur.
		$repository = $this->entityManager->getRepository(Storage::class);
		$server = $this->entityManager->getRepository(Server::class)->findOneBy([
			"id" => intval($request->getSession()->get("serverId", 0)),
			"user" => $this->getUser()
		]);

		if (!$server || !$storage = $repository->findOneBy(["server" => $server]))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On tente après de se connecter au serveur de stockage avec les informations
		//  de connexion fournies par l'utilisateur.
		$stream = $this->storageManager->openConnection($storage);

		if (!$stream)
		{
			return new Response(
				$this->translator->trans("configuration.failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On tente alors de récupérer le contenu du fichier de configuration.
		$value = $request->request->get("value", "none");
		$content = $this->storageManager->getFileContents($stream, $path = $request->request->get("path", "none"));

		switch ($type)
		{
			case "hostname":
			{
				// Édition du nom du serveur.
				$content = preg_replace("/hostname \"(.*)\"/i", "hostname \"$value\"", $content);
				break;
			}

			case "loading":
			{
				// Édition de l'écran de chargement.
				$content = preg_replace("/sv_loadingurl \"(.*)\"/i", "sv_loadingurl \"$value\"", $content);
				break;
			}

			case "password":
			{
				// Édition du mot de passe d'accès.
				$content = preg_replace("/rcon_password \"(.*)\"/i", "rcon_password \"$value\"", $content);
				break;
			}
		}

		// On insère ensuite le nouveau contenu du fichier de configuration.
		$this->storageManager->putFileContents($stream, $path, $content);

		// On retourne enfin une réponse de succès à l'utilisateur.
		return new Response(
			$this->translator->trans("configuration.updated"),
			Response::HTTP_CREATED
		);
	}
}