<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserCertificate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;

/**
 * @method UserCertificate|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserCertificate|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserCertificate[]    findAll()
 * @method UserCertificate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserCertificateRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     *
     * @throws LogicException
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCertificate::class);
    }

    /**
     * @return UserCertificate[]
     */
    public function findRevoked(): array
    {
        return $this
            ->createQueryBuilder('uc')
            ->where('uc.revokedAt is not null')
            ->orderBy('uc.createdAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return UserCertificate[]
     */
    public function findValid(): array
    {
        return $this
            ->createQueryBuilder('uc')
            ->where('uc.revokedAt is null')
            ->orderBy('uc.createdAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Certificate[] Returns an array of Certificate objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Certificate
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    /**
     * @param User $user
     *
     * @return UserCertificate[]
     */
    public function findValidByUser(User $user): array
    {
        return $this
            ->createQueryBuilder('uc')
            ->leftJoin('uc.user', 'u')
            ->where('uc.revokedAt is null')
            ->andWhere('u = :user')
            ->orderBy('uc.createdAt', 'desc')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
