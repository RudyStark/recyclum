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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function searchPaginated(array $criteria, int $page, int $perPage = 12): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.brand', 'b')->addSelect('b')
            ->andWhere('p.isPublished = :pub')->setParameter('pub', true)
            ->orderBy('p.createdAt', 'DESC');

        if (!empty($criteria['q'])) {
            $qb->andWhere('p.title LIKE :q OR p.shortDescription LIKE :q')
                ->setParameter('q', '%'.$criteria['q'].'%');
        }
        if (!empty($criteria['category'])) {
            $qb->andWhere('c.id = :cid')->setParameter('cid', (int) $criteria['category']);
        }
        if (!empty($criteria['brand'])) {
            $qb->andWhere('b.id = :bid')->setParameter('bid', (int) $criteria['brand']);
        }
        if (!empty($criteria['label'])) {
            // energyLabel est un backed enum en DB → on filtre par sa valeur
            $qb->andWhere('p.energyLabel = :lbl')->setParameter('lbl', $criteria['label']);
        }
        if (isset($criteria['min'])) {
            $qb->andWhere('p.price >= :pmin')->setParameter('pmin', (int) $criteria['min'] * 100);
        }
        if (isset($criteria['max'])) {
            $qb->andWhere('p.price <= :pmax')->setParameter('pmax', (int) $criteria['max'] * 100);
        }

        // tri
        $sort = $criteria['sort'] ?? null;
        switch ($sort) {
            case 'price_asc':
                $qb->orderBy('p.price', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('p.price', 'DESC');
                break;
            case 'alpha_asc':
                $qb->orderBy('p.title', 'ASC');
                break;
            case 'alpha_desc':
                $qb->orderBy('p.title', 'DESC');
                break;
            default:
                $qb->orderBy('p.createdAt', 'DESC');
        }

        $first = ($page - 1) * $perPage;
        $qb->setFirstResult($first)->setMaxResults($perPage);

        $paginator = new Paginator($qb);
        $total = count($paginator);

        return [iterator_to_array($paginator), $total];
    }
}
