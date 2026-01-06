<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_legal')]
    public function index(): Response
    {
        return $this->render('legal/mention_legal/index.html.twig');
    }

    #[Route('/confidentialite', name: 'app_privacy')]
    public function privacy(): Response
    {
        return $this->render('legal/privacy/index.html.twig');
    }

    #[Route('/conditions-generales-vente', name: 'app_cgv')]
    public function cgv(): Response
    {
        return $this->render('legal/cgv/index.html.twig');
    }

    #[Route('/politique-cookies', name: 'app_cookies')]
    public function cookies(): Response
    {
        return $this->render('legal/cookies/index.html.twig');
    }
}
