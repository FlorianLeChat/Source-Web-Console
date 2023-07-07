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
use App\Entity\Contact;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Locale;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
	//
	// Route vers la page de l'administration.
	//
	#[Route("/admin", name: "admin_page")]
	#[IsGranted("ROLE_ADMIN")]
	public function index(): Response
	{
		return $this->render("admin.html.twig");
	}

	//
	// Paramètres de configuration du tableau de bord.
	//
	public function configureDashboard(): Dashboard
	{
		return Dashboard::new()
			->setTitle("Source Web Console")
			->setLocales(
				[
					Locale::new("en", icon: "fa fa-language"),
					Locale::new("fr", icon: "fa fa-language")
				]
			)
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
		return [
			// Utilisateurs.
			MenuItem::section("admin.users"),
			MenuItem::linkToCrud("footer.contact", "fa-solid fa-envelope", Contact::class),
			MenuItem::linkToCrud("dashboard.players", "fa-solid fa-users", User::class),

			// Serveurs.
			MenuItem::section("admin.servers"),
			MenuItem::linkToCrud("dashboard.servers", "fa-solid fa-server", Server::class),
			MenuItem::linkToCrud("header.subtitle.events", "fa fa-comment", Event::class),
			MenuItem::linkToCrud("header.subtitle.statistics", "fa-solid fa-chart-line", Stats::class),
			MenuItem::linkToCrud("header.subtitle.tasks", "fa-solid fa-list-check", Task::class),

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
			MenuItem::linkToUrl("admin.public_website", "fa-solid fa-globe", "https://console.florian-dev.fr/"),
			MenuItem::linkToUrl("admin.code_source", "fa-brands fa-github", "https://github.com/FlorianLeChat/Source-Web-Console"),
			MenuItem::linkToUrl("user.disconnect", "fa-solid fa-right-from-bracket", "api/user/logout"),
		];
	}
}