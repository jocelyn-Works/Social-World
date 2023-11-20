<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Friendship;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Friendship>
 *
 * @method Friendship|null find($id, $lockMode = null, $lockVersion = null)
 * @method Friendship|null findOneBy(array $criteria, array $orderBy = null)
 * @method Friendship[]    findAll()
 * @method Friendship[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friendship::class);
    }

    public function findFriendship($currentUser, $profilUser): ?Friendship
    {
        return $this ->createQueryBuilder('f')
                     ->where('f.requester = :currentUser AND f.receiver = :profilUser')
                     ->orwhere('f.requester = :profilUser AND f.receiver = :currentUser')
                     ->setParameter('currentUser', $currentUser)
                     ->setParameter('profilUser', $profilUser)
                     ->getQuery()
                     ->getOneOrNullResult();
                    
    }

    public function findAddUsers($currentUser)
    {
        return $this ->createQueryBuilder('f')
            ->where('f.receiver = :currentUser')
            ->andWhere('f.status = :status')
            ->setParameter('currentUser', $currentUser)
            ->setParameter('status', Friendship::STATUS_PENDING)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

    }

    public function findExistingFriendship(User $requester, User $receiver): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->where('(f.requester = :requester AND f.receiver = :receiver) OR (f.requester = :receiver AND f.receiver = :requester)')
            ->setParameter('requester', $requester)
            ->setParameter('receiver', $receiver)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function findAcceptedFriendships($currentUserId)
    {
        return $this->createQueryBuilder('f')
            ->where('f.status = :status')
            ->andWhere('(f.requester = :currentUserId OR f.receiver = :currentUserId)')
            ->setParameter('status', Friendship::STATUS_ACCEPTED)
            ->setParameter('currentUserId', $currentUserId)
            ->getQuery()
            ->getResult();
    }

    public function getFriendshipStatus(User $user, User $friend): ?string
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->andWhere('(f.requester = :user AND f.receiver = :friend) OR (f.requester = :friend AND f.receiver = :user)')
            ->setParameter('user', $user)
            ->setParameter('friend', $friend);

        $friendship = $queryBuilder->getQuery()->getOneOrNullResult();

        if ($friendship instanceof Friendship) {
            return $friendship->getStatus();
        }

        return null;
    }

    


  

//    /**
//     * @return Friendship[] Returns an array of Friendship objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Friendship
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
