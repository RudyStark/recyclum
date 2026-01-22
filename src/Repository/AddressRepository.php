<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    /**
     * Trouve toutes les adresses d'un utilisateur
     *
     * @return Address[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.isDefaultShipping', 'DESC')
            ->addOrderBy('a.isDefaultBilling', 'DESC')
            ->addOrderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve l'adresse de livraison par défaut d'un utilisateur
     */
    public function findDefaultShippingAddress(User $user): ?Address
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.isDefaultShipping = true')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve l'adresse de facturation par défaut d'un utilisateur
     */
    public function findDefaultBillingAddress(User $user): ?Address
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.isDefaultBilling = true')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Réinitialise toutes les adresses de livraison par défaut d'un utilisateur
     */
    public function resetDefaultShipping(User $user): void
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.isDefaultShipping', 'false')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Réinitialise toutes les adresses de facturation par défaut d'un utilisateur
     */
    public function resetDefaultBilling(User $user): void
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.isDefaultBilling', 'false')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
