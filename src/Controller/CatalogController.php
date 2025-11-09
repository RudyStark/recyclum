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
use Symfony\Component\Routing\Attribute\Route;

#[Route('/produits', name: 'catalog_')]
final class CatalogController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        BrandRepository $brandRepository
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));

        // Récupération ULTRA-SÉCURISÉE des critères
        $minValue = $request->query->get('min');
        $maxValue = $request->query->get('max');

        $criteria = [
            'q' => trim((string) $request->query->get('q', '')),
            'category' => trim((string) $request->query->get('category', '')),
            'brand' => trim((string) $request->query->get('brand', '')),
            'label' => trim((string) $request->query->get('label', '')),
            'min' => ($minValue !== '' && $minValue !== null && is_numeric($minValue)) ? (int) $minValue : null,
            'max' => ($maxValue !== '' && $maxValue !== null && is_numeric($maxValue)) ? (int) $maxValue : null,
            'sort' => $request->query->get('sort', 'date_desc'),
        ];

        // Recherche paginée
        [$products, $totalProducts] = $productRepository->searchPaginated($criteria, $page);
        $stats = $productRepository->getFilterStats();

        return $this->render('catalog/index.html.twig', [
            'products' => $products,
            'totalProducts' => $totalProducts,
            'totalPages' => (int) ceil($totalProducts / 12),
            'currentPage' => $page,
            'criteria' => $criteria,
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
            'brands' => $brandRepository->findBy([], ['name' => 'ASC']),
            'energyLabels' => EnergyLabel::cases(),
            'stats' => $stats,
        ]);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(
        #[MapEntity(expr: 'repository.findOneBy({"slug": slug, "isPublished": true})')]
        ?Product $product,
        ProductRepository $productRepository
    ): Response {
        if (!$product) {
            throw $this->createNotFoundException('Ce produit n\'est pas disponible.');
        }

        $relatedProducts = $productRepository->findSimilar($product);

        return $this->render('catalog/show.html.twig', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
