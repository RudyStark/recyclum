<?php

namespace App\Controller;

use App\Entity\Product;
use App\Enum\EnergyLabel;
use App\Repository\BrandRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/produits', name: 'product_')]
final class CatalogController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $products,
        CategoryRepository $categories,
        BrandRepository $brands
    ): Response {
        $page = max(1, (int) $request->query->get('page', 1));

        $criteria = [
            'q'        => $request->query->get('q'),
            'category' => $request->query->get('category'),
            'brand'    => $request->query->get('brand'),
            'label'    => $request->query->get('label'),
            'min'      => $request->query->get('min'),
            'max'      => $request->query->get('max'),
            'sort'     => $request->query->get('sort', 'date_desc'),
        ];

        // À adapter à ta méthode repo
        [$items, $total] = $products->searchPaginated($criteria, $page, 12);

        return $this->render('catalog/index.html.twig', [
            'items'      => $items,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => 12,
            'criteria'   => $criteria,
            'categories' => $categories->findBy([], ['name' => 'ASC']),
            'brands'     => $brands->findBy([], ['name' => 'ASC']),
            'labels'     => EnergyLabel::cases(),
        ]);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(
        #[MapEntity(expr: 'repository.findOneBy(["slug" => slug])')]
        ?Product $product
    ): Response {
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        return $this->render('catalog/show.html.twig', [
            'product' => $product,
        ]);
    }
}
