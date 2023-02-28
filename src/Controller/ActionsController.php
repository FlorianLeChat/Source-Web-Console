<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActionsController extends AbstractController
{
	#[Route("/actions", name: "app_actions")]
	public function index(): Response
	{
		return $this->render("actions.html.twig");
	}
}