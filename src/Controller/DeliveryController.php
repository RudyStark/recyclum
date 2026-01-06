<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeliveryController extends AbstractController
{
    #[Route('/livraison', name: 'app_delivery')]
    public function index(): Response
    {
        return $this->render('delivery/index.html.twig');
    }
}
