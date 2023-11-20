<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Friendship;
use App\Repository\FriendshipRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FriendShipController extends AbstractController
{

    private EntityManagerInterface $em;
    private FriendshipRepository $friendshipRepository;
    public function __construct(EntityManagerInterface $em, FriendshipRepository $friendshipRepository)
    {
        $this->em = $em;
        $this->friendshipRepository = $friendshipRepository;
    }

    #[Route('/add-friend/{id}', name: 'add-friend')]
    public function addfriend( User $friend, Request $request): Response
    {
        $currentUser = $this->getUser();

        if(!$currentUser){
            return $this->redirectToRoute('signup');
        }

        $existingFriendship = $this->friendshipRepository->findExistingFriendship($currentUser, $friend);

        if ($existingFriendship) {
            
            $this->addFlash('error', 'Une demande à deja ete faite');

            $referer = $request->server->get('HTTP_REFERER');
            return $referer ? $this->redirect($referer) : $this->redirect('post');
        }

        $freiendship = new Friendship();
        $freiendship->setRequester($currentUser)
                    ->setReceiver($friend)
                    ->setStatus(Friendship::STATUS_PENDING);

        $this->em->persist($freiendship);
        $this->em->flush();

        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirect(('post'));
    }

    #[Route('/accept-friend/{id}', name: 'accept-friend-request')]
    public function acceptFriendRequest(User $user, Request $request):Response
    {
        $currentUser = $this->getUser();

        if(!$currentUser){
            return $this->redirectToRoute('signup');
        }

        $friendship = $this->friendshipRepository->findFriendship($currentUser, $user);

        if ($friendship) {
            $friendship->setStatus(Friendship::STATUS_ACCEPTED)
                       ->setFrienddAt(new DateTimeImmutable());

            $this->em->flush();
            
        }else{
            $freiendship = new Friendship();
            $freiendship->setRequester($currentUser)
                        ->setReceiver($user)
                        ->setStatus(Friendship::STATUS_ACCEPTED);

        $this->em->persist($freiendship);
        $this->em->flush(); 
        }

        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirect(('post'));
    }


    #[Route('/remove-friend/{id}', name: 'remove-friend')]
    public function removeFriend(User $user, Request $request):Response
    {
        $currentUser = $this->getUser();

        if(!$currentUser){
            return $this->redirectToRoute('signup');
        }

        $friendship = $this->friendshipRepository->findFriendship($currentUser, $user);

        if ($friendship) {
            $this->em->remove($friendship);
            $this->em->flush();
        }
        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirect(('post'));
    }

   



    #[Route('/block-user/{id}', name: 'block-user')]
    public function blockUser(User $user, Request $request):Response
    {
        $currentUser = $this->getUser();

        if(!$currentUser){
            return $this->redirectToRoute('signup');
        }

        $friendship = $this->friendshipRepository->findFriendship($currentUser, $user);

        if ($friendship) {
            $friendship->setStatus(Friendship::STATUS_BLOCKED)
                       ->setUpdatedAt(new DateTimeImmutable());
                      

            $this->em->flush();
            
        }else{
            $freiendship = new Friendship();
            $freiendship->setRequester($currentUser)
                        ->setReceiver($user)
                        ->setStatus(Friendship::STATUS_BLOCKED);
                        

        $this->em->persist($freiendship);
        $this->em->flush(); 
        }

        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirect(('post'));
    }
}
