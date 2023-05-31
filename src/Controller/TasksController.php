<?php

//
// Contrôleur de la page des tâches planifiées.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TasksController extends AbstractController
{
    public function index(): Response
    {
        return $this->render("tasks.html.twig");
    }
	#[Route("/tasks")]
	#[IsGranted("IS_AUTHENTICATED")]
}