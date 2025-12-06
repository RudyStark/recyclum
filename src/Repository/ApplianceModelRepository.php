<?php

namespace App\Repository;

use App\Entity\ApplianceModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplianceModel>
 */
class ApplianceModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplianceModel::class);
    }

    /**
     * Recherche un modèle par référence exacte (insensible à la casse et aux espaces)
     */
    public function findByModelReference(string $modelReference): ?ApplianceModel
    {
        // Nettoyer la référence : supprimer espaces, tirets, underscores
        $cleanRef = strtoupper(preg_replace('/[\s\-_]/', '', $modelReference));

        return $this->createQueryBuilder('m')
            ->where('UPPER(m.modelReference) = :ref')
            ->andWhere('m.isActive = :active')
            ->setParameter('ref', $cleanRef)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche des modèles par catégorie et marque
     */
    public function findByCategoryAndBrand(string $category, string $brand): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.category = :category')
            ->andWhere('m.brand = :brand')
            ->andWhere('m.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('brand', strtolower($brand))
            ->setParameter('active', true)
            ->orderBy('m.releaseYear', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Autocomplete pour recherche de modèles
     */
    public function searchModels(string $query, ?string $category = null, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.isActive = :active')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('UPPER(m.modelReference)', ':query'),
                    $qb->expr()->like('UPPER(m.modelName)', ':query'),
                    $qb->expr()->like('UPPER(m.brand)', ':query')
                )
            )
            ->setParameter('active', true)
            ->setParameter('query', '%' . strtoupper($query) . '%')
            ->setMaxResults($limit)
            ->orderBy('m.releaseYear', 'DESC');

        if ($category) {
            $qb->andWhere('m.category = :category')
                ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les modèles actifs par catégorie
     */
    public function findActiveByCategory(string $category): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.category = :category')
            ->andWhere('m.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('m.brand', 'ASC')
            ->addOrderBy('m.releaseYear', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par catégorie
     */
    public function getStatsByCategory(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.category, COUNT(m.id) as total')
            ->where('m.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('m.category')
            ->getQuery()
            ->getResult();
    }
}
