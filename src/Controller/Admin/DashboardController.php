<?php

//
// Contrôleur du tableau de bord de l'administration.
//
namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
	#[Route("/admin", name: "admin_page")]
	#[IsGranted("ROLE_ADMIN")]
	public function index(): Response
	{
        return $this->render("admin.html.twig");
	}

	public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle("Source Web Console");
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard("Dashboard", "fa fa-home");
    }
}