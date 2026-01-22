<?php

namespace App\Repository;

use App\Entity\CartItem;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    /**
     * Trouve tous les items d'un panier avec les produits préchargés
     *
     * @return CartItem[]
     */
    public function findByCartWithProduct(int $cartId): array
    {
        return $this->createQueryBuilder('ci')
            ->leftJoin('ci.product', 'p')
            ->addSelect('p')
            ->leftJoin('p.images', 'img')
            ->addSelect('img')
            ->andWhere('ci.cart = :cartId')
            ->setParameter('cartId', $cartId)
            ->orderBy('ci.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime tous les items d'un panier
     */
    public function deleteByCart(int $cartId): int
    {
        return $this->createQueryBuilder('ci')
            ->delete()
            ->andWhere('ci.cart = :cartId')
            ->setParameter('cartId', $cartId)
            ->getQuery()
            ->execute();
    }

    /**
     * Compte le nombre de fois qu'un produit est dans des paniers actifs
     */
    public function countProductInActiveCarts(Product $product): int
    {
        return (int) $this->createQueryBuilder('ci')
            ->select('COUNT(ci.id)')
            ->leftJoin('ci.cart', 'c')
            ->andWhere('ci.product = :product')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('product', $product)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
