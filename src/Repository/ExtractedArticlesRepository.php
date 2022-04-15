<?php

namespace App\Repository;

use App\Entity\ExtractedArticles;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExtractedArticlesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtractedArticles::class);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findArticleById($id){
        return  $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findArticleByUrl($url){
        return  $this->createQueryBuilder('a')
            ->andWhere('a.url = :url')
            ->setParameter('url', $url)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
