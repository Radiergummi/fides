<?php

namespace App\Repository;

use App\Entity\AuthConnector;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AuthConnector|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthConnector|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthConnector[]    findAll()
 * @method AuthConnector[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthConnectorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthConnector::class);
    }

    // /**
    //  * @return AuthConnector[] Returns an array of AuthConnector objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AuthConnector
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
