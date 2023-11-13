<?php

//
// Contrôleur du tableau de bord de l'administration.
//
namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Task;
use App\Entity\Event;
use App\Entity\Stats;
use App\Entity\Server;
use App\Entity\Storage;
use App\Entity\Contact;
use App\Entity\Command;
use App\Twig\AppRuntime;
use Symfony\Component\Intl\Languages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Locale;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

final class DashboardController extends AbstractDashboardController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private readonly AppRuntime $runtime,
		private readonly TranslatorInterface $translator
	) {}

	//
	// Route vers la page de l'administration.
	//
	#[Route("/admin", name: "admin_page")]
	public function index(): Response
	{
		// Récupération des méta-données du site.
		$metadata = $this->runtime->getMetadata();

		// Affichage de la page.
		return $this->render("admin.html.twig", [
			"title" => $metadata["title"]
		]);
	}

	//
	// Paramètres de configuration du tableau de bord.
	//
	public function configureDashboard(): Dashboard
	{
		// Récupération des méta-données du site.
		$metadata = $this->runtime->getMetadata();

		// Génération du tableau de bord.
		return Dashboard::new()
			->setTitle($metadata["title"])
			->setLocales([
				Locale::new("en", ucfirst(Languages::getName("en")), "fa fa-language"),
				Locale::new("fr", ucfirst(Languages::getName("fr")), "fa fa-language")
			])
			->setFaviconPath("build/favicons/512x512.webp")
			->generateRelativeUrls();
	}

	//
	// Paramètres de configuration du menu de navigation.
	//
	public function configureUserMenu(UserInterface $user): UserMenu
	{
		return UserMenu::new()
			->setName($user->getUserIdentifier())
			->setMenuItems([
				MenuItem::section(),
				MenuItem::linkToUrl("user.disconnect", "fa-solid fa-right-from-bracket", "api/user/logout"),
			])
			->displayUserName();
	}

	//
	// Paramètres de configuration des éléments du menu de navigation.
	//
	public function configureMenuItems(): iterable
	{
		// Récupération des méta-données du site.
		$metadata = $this->runtime->getMetadata();

		return [
			// Utilisateurs.
			MenuItem::section("admin.users"),
			MenuItem::linkToCrud("footer.contact", "fa-solid fa-envelope", Contact::class),
			MenuItem::linkToCrud("dashboard.users", "fa-solid fa-users", User::class),
			MenuItem::linkToCrud("header.subtitle.commands", "fa-solid fa-bolt", Command::class),

			// Serveurs.
			MenuItem::section("admin.servers"),
			MenuItem::linkToCrud("dashboard.servers", "fa-solid fa-server", Server::class),
			MenuItem::linkToCrud("header.subtitle.events", "fa fa-comment", Event::class),
			MenuItem::linkToCrud("header.subtitle.statistics", "fa-solid fa-chart-line", Stats::class),
			MenuItem::linkToCrud("header.subtitle.tasks", "fa-solid fa-list-check", Task::class),
			MenuItem::linkToCrud("header.subtitle.storage", "fa-solid fa-cloud-arrow-down", Storage::class),

			// Pages.
			MenuItem::section("admin.pages"),
			MenuItem::linkToUrl("header.subtitle.dashboard", "fa-solid fa-house-user", "dashboard"),
			MenuItem::linkToUrl("header.subtitle.statistics", "fa-solid fa-chart-line", "statistics"),
			MenuItem::linkToUrl("header.subtitle.configuration", "fa-solid fa-screwdriver-wrench", "configuration"),
			MenuItem::linkToUrl("header.subtitle.actions", "fa-solid fa-bolt", "actions"),
			MenuItem::linkToUrl("header.subtitle.console", "fa-solid fa-terminal", "console"),
			MenuItem::linkToUrl("header.subtitle.tasks", "fa-solid fa-list-check", "tasks"),
			MenuItem::linkToUrl("header.subtitle.user", "fa-solid fa-user", "user"),
			MenuItem::linkToUrl("header.subtitle.admin", "fa-solid fa-database", "admin"),

			// Divers.
			MenuItem::section("admin.misc"),
			MenuItem::linkToUrl("admin.public_website", "fa-solid fa-globe", $metadata["url"])
				->setLinkTarget("_blank"),
			MenuItem::linkToUrl("admin.code_source", "fa-brands fa-github", $metadata["source"])
				->setLinkTarget("_blank"),
			MenuItem::linkToUrl("user.disconnect", "fa-solid fa-right-from-bracket", "user")
		];
	}
}