<?php

//
// Contrôleur de la page de la console interactive.
//
namespace App\Controller;

use App\Entity\Server;
use App\Service\ServerManager;
use Symfony\Component\Filesystem\Path;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ConsoleController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private readonly Filesystem $filesystem,
		private readonly ServerManager $serverManager,
		private readonly KernelInterface $kernel,
		private readonly TranslatorInterface $translator,
		private readonly EntityManagerInterface $entityManager,
	) {}

	//
	// Route vers la page de configuration de la console interactive.
	//
	#[Route("/console", name: "console_page")]
	public function index(Request $request): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("index_page");
		}

		// On récupère ensuite les informations du serveur sélectionné précédemment
		//  par l'utilisateur.
		$address = $request->server->get("SERVER_ADDR", "127.0.0.1");
		$server = $this->entityManager->getRepository(Server::class)->findOneBy([
			"id" => intval($request->getSession()->get("serverId", 0)), "user" => $this->getUser()
		]);

		if ($server)
		{
			try
			{
				// On tente après d'établir une connexion avec le serveur.
				$this->serverManager->connect($server);

				// En cas de réussite, on définit le serveur comme étant
				//  capable d'envoyer ses journaux d'événements au site.
				$this->serverManager->query->Rcon("log on");
				$this->serverManager->query->Rcon("logaddress_add $address:2004");
			}
			catch (\Exception) {}
			finally
			{
				// Si tout se passe bien, on libère le socket réseau pour
				//  d'autres scripts du site.
				$this->serverManager->query->Disconnect();
			}
		}

		// On affiche enfin la page de la console interactive.
		return $this->render("console.html.twig");
	}

	//
	// API vers la récupération de la sortie en temps réel de la console interactive.
	//
	#[Route("/api/server/console", name: "console_live", methods: ["GET"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function storage(Request $request): Response|JsonResponse
	{
		// On récupère d'abord le serveur sélectionné par l'utilisateur.
		$serverId = intval($request->getSession()->get("serverId", 0));

		if ($serverId === 0)
		{
			return new Response(
				$this->translator->trans("form.no_selected_server"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie alors que le serveur existe bien et qu'il appartient à l'utilisateur.
		$server = $this->entityManager->getRepository(Server::class)->findOneBy([
			"id" => $serverId, "user" => $this->getUser()
		]);

		if (!$server)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On tente ensuite de vérifier si le site est capable de communiquer
		//  avec le serveur au travers du protocole RCON.
		try
		{
			// Tentative de connexion au serveur.
			$this->serverManager->connect($server);
		}
		catch (\Exception)
		{
			// En cas d'erreur quelconque, on indique à l'utilisateur que
			//  le serveur n'est pas en mesure de communiquer avec le site.
			return new Response(
				$this->translator->trans("console.no_logs"),
				Response::HTTP_INTERNAL_SERVER_ERROR
			);
		}
		finally
		{
			// On libère le socket réseau pour d'autres scripts du site.
			$this->serverManager->query->Disconnect();
		}

		// On récupère après le chemin du fichier des journaux d'événements
		//  du serveur et on le normalise pour éviter les problèmes de chemin.
		$address = str_replace(".", "-", $server->getAddress() . "-" . $server->getPort());
		$path = Path::normalize(sprintf("%s/var/log/%s/%s.log", $this->kernel->getProjectDir(), $address, date("Y-m-d")));

		if (!$this->filesystem->exists($path))
		{
			return new Response(
				$this->translator->trans("console.no_logs"),
				Response::HTTP_INTERNAL_SERVER_ERROR
			);
		}

		// On récupère enfin les 100 dernières lignes du fichier des journaux d'événements
		//  avant de les envoyer au client sous forme de réponse JSON.
		return new JsonResponse(
			array_slice(explode("\n", file_get_contents($path)), $this->isGranted("ROLE_DONOR") ? -100 : -50),
			Response::HTTP_OK
		);
	}
}