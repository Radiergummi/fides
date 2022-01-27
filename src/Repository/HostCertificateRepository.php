<?php

namespace App\Repository;

use App\Entity\HostCertificate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HostCertificate|null find($id, $lockMode = null, $lockVersion = null)
 * @method HostCertificate|null findOneBy(array $criteria, array $orderBy = null)
 * @method HostCertificate[]    findAll()
 * @method HostCertificate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HostCertificateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HostCertificate::class);
    }

    // /**
    //  * @return HostCertificate[] Returns an array of HostCertificate objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?HostCertificate
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
