<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationController extends AbstractController
{
    #[Route("/configuration", name: "app_configuration")]
    public function index(): Response
    {
        return $this->render("configuration.html.twig");
    }
}