<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RachatController extends AbstractController
{
    #[Route('/rachat', name: 'app_rachat')]
    public function index(): Response
    {
        return $this->render('rachat/index.html.twig', [
            'phone' => '01 43 07 63 63',
        ]);
    }
}
