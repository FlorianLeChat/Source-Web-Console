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

class DashboardController extends AbstractDashboardController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(private TranslatorInterface $translator) {}

	//
	// Route vers la page de l'administration.
	//
	#[Route("/admin", name: "admin_page")]
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
			->setTitle($this->translator->trans("head.title"))
			->setLocales(
				[
					Locale::new("en", ucfirst(Languages::getName("en")), "fa fa-language"),
					Locale::new("fr", ucfirst(Languages::getName("fr")), "fa fa-language")
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
			MenuItem::linkToCrud("dashboard.users", "fa-solid fa-users", User::class),

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
			MenuItem::linkToUrl("admin.public_website", "fa-solid fa-globe", "https://console.florian-dev.fr/")->setLinkTarget("_blank"),
			MenuItem::linkToUrl("admin.code_source", "fa-brands fa-github", "https://github.com/FlorianLeChat/Source-Web-Console")->setLinkTarget("_blank"),
			MenuItem::linkToUrl("user.disconnect", "fa-solid fa-right-from-bracket", "api/user/logout")
		];
	}
}