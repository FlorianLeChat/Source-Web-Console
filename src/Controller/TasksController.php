<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TasksController extends AbstractController
{
    #[Route("/tasks", name: "app_tasks")]
    public function index(): Response
    {
        return $this->render("tasks.html.twig");
    }
}