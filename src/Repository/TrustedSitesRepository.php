<?php

namespace App\Repository;

use App\Entity\TrustedSites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TrustedSites|null find($id, $lockMode = null, $lockVersion = null)
 * @method TrustedSites|null findOneBy(array $criteria, array $orderBy = null)
 * @method TrustedSites[]    findAll()
 * @method TrustedSites[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrustedSitesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrustedSites::class);
    }

    /**
     * @param TrustedSites $entity
     * @param bool $flush
     */
    public function add(TrustedSites $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @param TrustedSites $entity
     * @param bool $flush
     */
    public function remove(TrustedSites $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findTrustedSiteByDomain($domain)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.domain = :val')
            ->setParameter('val', $domain)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function updateTrustedSite($id, $trueHits, $falseHits, $totalHits)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->set('a.trueHits', $trueHits)
            ->set('a.falseHits', $falseHits)
            ->set('a.totalHits', $totalHits)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    // /**
    //  * @return TrustedSites[] Returns an array of TrustedSites objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TrustedSites
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
