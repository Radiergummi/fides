<?php

namespace App\Repository;

use App\Entity\CertificateAuthority;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * @method CertificateAuthority|null find($id, $lockMode = null, $lockVersion = null)
 * @method CertificateAuthority|null findOneBy(array $criteria, array $orderBy = null)
 * @method CertificateAuthority[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CertificateAuthorityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateAuthority::class);
    }

    /**
     * @return CertificateAuthority
     * @throws RuntimeException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getActive(): CertificateAuthority
    {
        $certificateAuthority = $this
            ->createQueryBuilder('ca')
            ->where('ca.revokedAt is null')
            ->orderBy('ca.createdAt', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        if ( ! $certificateAuthority) {
            throw new RuntimeException(
                'No valid certificate authority available: An ' .
                'authority must be generated before certificates can be issued.'
            );
        }

        return $certificateAuthority;
    }

    // /**
    //  * @return CertificateAuthority[] Returns an array of CertificateAuthority objects
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
    public function findOneBySomeField($value): ?CertificateAuthority
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findAll(): array
    {
        return $this
            ->createQueryBuilder('ca')
            ->orderBy('ca.createdAt', 'desc')
            ->getQuery()
            ->getResult();
    }
}
