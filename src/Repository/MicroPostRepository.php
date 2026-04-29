<?php

namespace App\Repository;

use App\Entity\MicroPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MicroPost>
 */
class MicroPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MicroPost::class);
    }

        // src/Repository/MicroPostRepository.php

    public function findAllPosts(): array
    {
        return $this->createQueryBuilder('m') // 'm' MicroPost entity'si için bir alias (takma ad)
            ->orderBy('m.created', 'DESC')    // En yeni postları en üstte getirir
            ->getQuery()                      // Sorguyu oluştur
            ->getResult();                    // Sonuçları bir dizi (array) olarak döndür
    }

    public function findAllWithComments(): array
{
    return $this->createQueryBuilder('p') // 'p' post için takma ad
        ->addSelect('c')                  // 'c' yorumları da seç (Select içine al)
        ->leftJoin('p.comments', 'c')     // Postun içindeki comments ilişkisine 'c' diyerek bağlan
        ->orderBy('p.created', 'DESC')
        ->getQuery()
        ->getResult();
}
    //    /**
    //     * @return MicroPost[] Returns an array of MicroPost objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MicroPost
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
