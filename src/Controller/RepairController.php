<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RepairController extends AbstractController
{
    #[Route('/reparation', name: 'reparation_index')]
    public function index(): Response
    {
        return $this->render('reparation/index.html.twig', [
            'pageTitle' => 'Reparation d\'électroménager',
            'metaDescription' => 'Service de réparation rapide pour tous vos appareils électroménagers. Diagnostic gratuit, intervention 7j/7 à Paris et alentours.',
        ]);
    }
}
