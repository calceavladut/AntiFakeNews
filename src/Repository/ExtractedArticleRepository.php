<?php

namespace App\Repository;

use App\Entity\ExtractedArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExtractedArticle|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExtractedArticle|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExtractedArticle[]    findAll()
 * @method ExtractedArticle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExtractedArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtractedArticle::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(ExtractedArticle $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(ExtractedArticle $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findArticleByUrl($url)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.url = :val')
            ->setParameter('val', $url)
            ->getQuery()
            ->getSingleResult()
            ;
    }

    public function updateScores($url, $real_score, $fake_score)
    {
        return $this->createQueryBuilder('s')
            ->update()
            ->set('s.real_score', $real_score)
            ->set('s.fake_score', $fake_score)
            ->where('s.url = :url')
            ->setParameter('url', $url)
            ->getQuery()
            ->execute()
            ;
    }

    // /**
    //  * @return ExtractedArticle[] Returns an array of ExtractedArticle objects
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
    public function findOneBySomeField($value): ?ExtractedArticle
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
