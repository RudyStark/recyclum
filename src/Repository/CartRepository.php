<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    /**
     * Trouve le panier actif d'un utilisateur authentifié
     */
    public function findActiveByUser(User $user): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.items', 'i')
            ->addSelect('i')
            ->leftJoin('i.product', 'p')
            ->addSelect('p')
            ->andWhere('c.user = :user')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve le panier actif par session ID (invité)
     */
    public function findActiveBySessionId(string $sessionId): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.items', 'i')
            ->addSelect('i')
            ->leftJoin('i.product', 'p')
            ->addSelect('p')
            ->andWhere('c.sessionId = :sessionId')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprime les paniers expirés (tâche CRON)
     */
    public function deleteExpiredCarts(): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->andWhere('c.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime les paniers vides et expirés
     */
    public function deleteEmptyExpiredCarts(): int
    {
        $qb = $this->createQueryBuilder('c');

        return $qb->delete()
            ->andWhere('c.expiresAt < :now')
            ->andWhere($qb->expr()->not($qb->expr()->exists(
                $this->getEntityManager()->createQueryBuilder()
                    ->select('1')
                    ->from('App\Entity\CartItem', 'ci')
                    ->where('ci.cart = c')
                    ->getDQL()
            )))
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Fusionne le panier invité avec le panier utilisateur lors de la connexion
     */
    public function mergeGuestCartToUserCart(Cart $guestCart, User $user): Cart
    {
        $em = $this->getEntityManager();

        // Chercher le panier existant de l'utilisateur
        $userCart = $this->findActiveByUser($user);

        if (!$userCart) {
            // Pas de panier utilisateur, on transfère le panier invité
            $guestCart->setUser($user);
            $guestCart->setSessionId(null);
            $em->flush();
            return $guestCart;
        }

        // Fusionner les items du panier invité dans le panier utilisateur
        foreach ($guestCart->getItems() as $guestItem) {
            $existingItem = $userCart->findItemByProduct($guestItem->getProduct());

            if ($existingItem) {
                // Produit déjà dans le panier, on additionne les quantités
                $newQuantity = min(10, $existingItem->getQuantity() + $guestItem->getQuantity());
                $existingItem->setQuantity($newQuantity);
            } else {
                // Nouveau produit, on l'ajoute
                $guestItem->setCart($userCart);
                $userCart->addItem($guestItem);
            }
        }

        // Supprimer le panier invité
        $em->remove($guestCart);
        $em->flush();

        return $userCart;
    }

    /**
     * Compte le nombre total d'items dans tous les paniers actifs (stats admin)
     */
    public function countTotalActiveItems(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('SUM(ci.quantity)')
            ->leftJoin('c.items', 'ci')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
