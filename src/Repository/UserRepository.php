<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Friendship;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;


/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
        
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findUserNoConnectedWithRequestsPending($currentUserId)
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.receiverFreindShips', 'fr', 'WITH', 'fr.requester = :currentUserId')
            ->leftJoin('u.friendships', 'fs', 'WITH', 'fs.receiver = :currentUserId')
            ->where('u.id != :id')
            ->andWhere('fr.id IS NULL OR fr.status NOT IN (:statuses)')
            ->andWhere('fs.id IS NULL OR fs.status NOT IN (:statuses)')
            ->setParameter('id', $currentUserId)
            ->setParameter('currentUserId', $currentUserId)
            ->setParameter('statuses', [Friendship::STATUS_ACCEPTED, Friendship::STATUS_BLOCKED, Friendship::STATUS_PENDING])
            ->getQuery()
            ->getResult();
    }
    
   
    // public function findUserNoConnected($users) {  // 
    //         return $this->createQueryBuilder('u')
    //                     ->where('u.id != :id')
    //                     ->setParameter('id', $users)
    //                     ->getQuery()
    //                     ->getResult();
    // }
    
       

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
