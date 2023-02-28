<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConsoleController extends AbstractController
{
    #[Route("/console", name: "app_console")]
    public function index(): Response
    {
        return $this->render("console.html.twig");
    }
}