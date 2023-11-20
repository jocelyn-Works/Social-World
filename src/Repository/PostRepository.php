<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findPosteWithUsers() {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->where('p.status = :status')
            ->setParameter('status', 'public')  
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    

    public function findPosteWithCommentsAndUsers(int $id) {
        return $this->createQueryBuilder('p')
                    ->where('p.id = :id')
                    ->setParameter('id' , $id)
                    ->leftJoin('p.author', 'a')
                    ->addSelect('a')
                    ->leftJoin('p.comments', 'c')
                    ->addSelect('c')
                    ->leftJoin('c.author', 'ca')
                    ->addSelect('ca')
                    ->orderBy('c.createdAt', 'DESC')
                    ->getQuery()
                    ->getOneOrNullResult();

    }

    // récuperer tous les post public et les post inscrit sur leur profil = en private
    public function findPostsOffUser(User $author) {
        return $this->createQueryBuilder('p')
            ->where('(p.author = :author AND p.status = :publicStatus) OR p.receiver = :authorId')
            ->setParameter('author', $author)
            ->setParameter('publicStatus', 'public')
            ->setParameter('authorId', $author->getId())
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }


//    /**
//     * @return Post[] Returns an array of Post objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Post
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
