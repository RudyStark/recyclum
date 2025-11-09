<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    private const PRODUCTS_PER_PAGE = 12;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Recherche paginée avec filtres multiples
     *
     * @return array{0: array<Product>, 1: int} [items, total]
     */
    public function searchPaginated(array $criteria, int $page = 1, int $perPage = self::PRODUCTS_PER_PAGE): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isPublished = :published')
            ->setParameter('published', true);

        // Filtre recherche textuelle
        if (!empty($criteria['q'])) {
            $qb->andWhere('p.title LIKE :search OR p.shortDescription LIKE :search')
                ->setParameter('search', '%' . $criteria['q'] . '%');
        }

        // Filtre catégorie
        if (!empty($criteria['category'])) {
            $qb->innerJoin('p.category', 'c')
                ->andWhere('c.slug = :categorySlug')
                ->setParameter('categorySlug', $criteria['category']);
        }

        // Filtre marque
        if (!empty($criteria['brand'])) {
            $qb->innerJoin('p.brand', 'b')
                ->andWhere('b.slug = :brandSlug')
                ->setParameter('brandSlug', $criteria['brand']);
        }

        // Filtre prix minimum (vérification stricte)
        if (isset($criteria['min']) && is_numeric($criteria['min']) && $criteria['min'] > 0) {
            $qb->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', (int)$criteria['min'] * 100);
        }

        // Filtre prix maximum (vérification stricte)
        if (isset($criteria['max']) && is_numeric($criteria['max']) && $criteria['max'] > 0) {
            $qb->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', (int)$criteria['max'] * 100);
        }

        // Filtre étiquette énergie
        if (!empty($criteria['label'])) {
            $qb->andWhere('p.energyLabel = :energyLabel')
                ->setParameter('energyLabel', $criteria['label']);
        }

        // Tri
        $this->applySorting($qb, $criteria['sort'] ?? 'date_desc');

        // Pagination
        $query = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        $paginator = new Paginator($query, fetchJoinCollection: true);

        return [
            iterator_to_array($paginator),
            count($paginator)
        ];
    }

    /**
     * Produits similaires (même catégorie, excluant le produit actuel)
     */
    public function findSimilar(Product $product, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.category = :category')
            ->andWhere('p.id != :currentId')
            ->andWhere('p.isPublished = :published')
            ->setParameter('category', $product->getCategory())
            ->setParameter('currentId', $product->getId())
            ->setParameter('published', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques pour filtres (min/max prix, compteurs)
     */
    public function getFilterStats(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('MIN(p.price) as minPrice', 'MAX(p.price) as maxPrice', 'COUNT(p.id) as totalProducts')
            ->where('p.isPublished = :published')
            ->setParameter('published', true);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'minPrice' => (int) ceil(($result['minPrice'] ?? 0) / 100),
            'maxPrice' => (int) floor(($result['maxPrice'] ?? 0) / 100),
            'totalProducts' => (int) $result['totalProducts'],
        ];
    }

    /**
     * Applique le tri selon le critère
     */
    private function applySorting($qb, string $sort): void
    {
        match ($sort) {
            'price_asc' => $qb->orderBy('p.price', 'ASC'),
            'price_desc' => $qb->orderBy('p.price', 'DESC'),
            'name_asc' => $qb->orderBy('p.title', 'ASC'),
            'name_desc' => $qb->orderBy('p.title', 'DESC'),
            'date_asc' => $qb->orderBy('p.createdAt', 'ASC'),
            default => $qb->orderBy('p.createdAt', 'DESC'), // date_desc
        };
    }
}
