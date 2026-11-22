<?php

//
// Contrôleur de la page du tableau de bord.
//
namespace App\Controller;

use App\Entity\Event;
use App\Entity\Server;
use App\Service\ServerManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class DashboardController extends AbstractController
{
    // Durée du cache pour les données en temps réel du serveur.
    private const CACHE_LIFETIME = 10;

    //
    // Initialisation de certaines dépendances du contrôleur.
    //
    public function __construct(
        private readonly ServerManager $serverManager,
        private readonly CacheInterface $cache,
        private readonly KernelInterface $kernel,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    //
    // Route vers la représentation JSON des traductions Symfony.
    //
    #[Route("/translations/{language}", name: "translations_page")]
    public function translations(string $language): Response|JsonResponse
    {
        // On vérifie d'abord que le fichier de traduction existe bien.
        $finder = new Finder();
        $finder->files()->in($this->kernel->getProjectDir() . "/translations/")->name("messages.$language.yaml");

        if ($finder->count() === 0)
        {
            return new Response(status: Response::HTTP_NOT_FOUND);
        }

        // On charge ensuite le contenu du fichier YAML de traduction
        //  pour le convertir en tableau associatif.
        $translations = Yaml::parseFile(
            sprintf("%s/translations/messages.%s.yaml", $this->kernel->getProjectDir(), $language)
        );

        // On détermine si les services Google reCAPTCHA sont activés
        //  pour afficher les sections correspondantes dans les consentements des cookies.
        $isRecaptchaEnabled = $this->getParameter("app.recaptcha_enabled") === "true";

        // On filtre ensuite les sections des préférences des cookies en fonction
        //  de l'activation du service Google reCAPTCHA.
        $translations["preferencesModal"]["sections"] = array_filter(
            $translations["preferencesModal"]["sections"],
            function ($section) use ($isRecaptchaEnabled)
            {
                // Google reCAPTCHA.
                $category = $section["linkedCategory"] ?? "";

                if ($category === "security" && !$isRecaptchaEnabled)
                {
                    return false;
                }

                // Autres sections.
                return true;
            }
        );

        // On effectue par la même occasion une nouvelle indexation des sections
        //  pour éviter les clés manquantes dans le tableau associatif.
        $translations["preferencesModal"]["sections"] = array_values($translations["preferencesModal"]["sections"]);

        // On retourne enfin le contenu des traductions au format JSON.
        return new JsonResponse($translations, JsonResponse::HTTP_OK);
    }

    //
    // Route vers la page du tableau de bord.
    //
    #[Route("/dashboard", name: "dashboard_page")]
    public function index(Request $request): Response
    {
        // On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
        //  à la page, sinon on le redirige vers la page d'accueil.
        if (!$this->isGranted("IS_AUTHENTICATED"))
        {
            return $this->redirectToRoute("index_page");
        }

        // On récupère ensuite l'identifiant unique du serveur sélectionné
        //  précédemment par l'utilisateur.
        $user = $this->getUser();
        $cacheId = (int) $request->getSession()->get("serverId", 0);
        $repository = $this->entityManager->getRepository(Server::class);

        // On réalise après l'action demandée par l'utilisateur sur un serveur.
        $error = $request->isMethod("POST") ? $this->handleServerAction($request) : null;

        if ($error)
        {
            return $error;
        }

        // On inclut enfin les paramètres du moteur TWIG pour la création de la page.
        return $this->render("dashboard.html.twig", [
            // Récupération de l'historique des actions et commandes.
            "dashboard_logs" => $this->entityManager->getRepository(Event::class)->findBy(
                ["server" => $cacheId],
                ["id" => "DESC"],
            3),

            // Liste des serveurs depuis la base de données.
            "dashboard_servers" => $repository->findBy(["user" => $user])
        ]);
    }

    //
    // API vers la surveillance des constantes du serveur.
    //
    #[Route("/api/server/monitor", name: "server_monitor", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function monitor(Request $request): Response|JsonResponse
    {
        // On récupère d'abord le premier serveur lié au compte de l'utilisateur
        //  ou celui sélectionné par l'utilisateur.
        $user = $this->getUser();
        $serverId = (int) $request->getSession()->get("serverId", 0);
        $repository = $this->entityManager->getRepository(Server::class);

        $server = $repository->findOneBy(
            $serverId !== 0 ? ["id" => $serverId, "user" => $user] : ["user" => $user],
            ["id" => "ASC"]
        );

        // On vérifie dans ce cas qu'un serveur a bien été récupéré.
        if (!$server)
        {
            return new Response(
                $this->translator->trans("form.no_selected_server"),
                Response::HTTP_BAD_REQUEST
            );
        }

        // On enregistre ensuite l'identifiant du serveur dans la session
        //  de l'utilisateur pour l'utiliser dans les autres pages.
        $serverId = $server->getId();
        $request->getSession()->set("serverId", $serverId);

        // On vérifie également si les données en temps réel du serveur
        //  sont déjà dans le cache de données.
        return $this->cache->get("svc_data_$serverId", function (ItemInterface $item) use ($server): Response
        {
            // Si ce n'est pas le cas, on définit une durée de vie
            //  de persistance pour le cache.
            $item->expiresAfter(self::CACHE_LIFETIME);

            try
            {
                // On tente après d'établir une connexion avec le serveur.
                $this->serverManager->connect($server);

                // En cas de réussite, on récupère toutes les informations
                //  disponibles et fournies par le module d'administration.
                $details = $this->serverManager->query->GetInfo();

                // On encode alors certaines de ces informations pour les
                //  transmettre au client à travers le JavaScript.
                return new JsonResponse([

                    // État du serveur.
                    "state" => $this->translator->trans(
                        sprintf("dashboard.state.%s", $details["Password"] ? "service" : "running"),
                        ["%gamemode%" => $details["ModDesc"]]
                    ),

                    // Carte/environnement.
                    "map" => $details["Map"],

                    // Nombre de joueurs humains et robots.
                    "count" => sprintf("%d/%d [%d]", $details["Players"], $details["MaxPlayers"], $details["Bots"]),

                    // Liste des joueurs
                    "players" => $this->serverManager->query->GetPlayers()

                ], JsonResponse::HTTP_OK);
            }
            catch (\Exception $error)
            {
                // En cas d'erreur, on force l'expiration du cache avant
                //  de renvoyer le message d'erreur à l'utilisateur.
                $item->expiresAfter(1);

                return new Response(
                    $this->translator->trans("global.fatal_error", ["%error%" => $error->getMessage()]),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
            finally
            {
                // Si tout se passe bien, on libère enfin le socket réseau
                //  pour d'autres scripts du site.
                $this->serverManager->query->Disconnect();
            }
        });
    }

    //
    // Réalisation d'une action (connexion, édition ou suppression) sur un serveur
    //  depuis le tableau de bord, avec la réponse d'erreur éventuelle à renvoyer.
    //
    private function handleServerAction(Request $request): ?Response
    {
        // On vérifie d'abord qu'un serveur est ciblé et la validité du jeton CSRF.
        $serverId = (int) $request->request->get("id", 0);
        $action = $request->request->get("action", "none");

        if ($serverId === 0 || !$this->isCsrfTokenValid("server_$action", $request->request->get("token")))
        {
            return $serverId === 0 ? null : new Response(status: Response::HTTP_BAD_REQUEST);
        }

        // On vérifie ensuite l'existence et l'appartenance du serveur à l'utilisateur
        //  avant de réaliser l'action demandée.
        $server = $this->entityManager->getRepository(Server::class)->findOneBy([
            "id" => $serverId, "user" => $this->getUser()
        ]);

        $unauthorized = new Response(status: Response::HTTP_UNAUTHORIZED);

        return match ($action)
        {
            "connect" => $this->connectServer($request, $serverId),
            "edit" => $server ? $this->editServer($request, $server) : $unauthorized,
            "delete" => $server ? $this->deleteServer($server) : $unauthorized,
            default => null
        };
    }

    //
    // Enregistrement de l'identifiant du serveur dans la session de l'utilisateur.
    //
    private function connectServer(Request $request, int $serverId): null
    {
        $request->getSession()->set("serverId", $serverId);

        return null;
    }

    //
    // Enregistrement des modifications d'un serveur, avec la réponse d'erreur
    //  éventuelle si les nouvelles informations sont invalides.
    //
    private function editServer(Request $request, Server $server): ?Response
    {
        $address = $request->request->get("address", $server->getAddress());
        $port = $request->request->get("port", $server->getPort());

        $server->setAddress($address);
        $server->setPort($port);
        $server->setGame($this->serverManager->getGameIDByAddress($address, $port));

        // Chiffrement du nouveau mot de passe administrateur.
        $password = $request->request->get("password");

        if (!empty($password))
        {
            $server->setPassword($this->serverManager->encryptPassword($password));
        }

        // Vérification de la validité des nouvelles informations avant
        //  la sauvegarde dans la base de données.
        if (count($this->validator->validate($server)) > 0)
        {
            return new Response(status: Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->getRepository(Server::class)->save($server, true);

        return null;
    }

    //
    // Suppression définitive d'un serveur dans la base de données.
    //
    private function deleteServer(Server $server): null
    {
        $this->entityManager->getRepository(Server::class)->remove($server, true);

        return null;
    }
}
