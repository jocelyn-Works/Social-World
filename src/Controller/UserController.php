<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Form\UserType;
use DateTimeImmutable;
use App\Service\UploaderPicture;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\UploaderPostPicture;
use App\Repository\FriendshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    #[Route('/user', name: 'current_user')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]

    public function currentUser(UserInterface $user, Request $request,
    EntityManagerInterface $em, PostRepository $repository,
    UploaderPostPicture $uploaderPostPicture,
    UserRepository $userRepository,
    FriendshipRepository $friendshiprepository): Response
    {

        $currentUser = $this ->getUser();

        $newPost = new Post();  // crée un nouveau post
        $postForm = $this->createForm(PostType::class, $newPost);  // création du formulaire
        $postForm->handleRequest($request);  // analyse et verifie la requete de mon formulaire

        if ($postForm->isSubmitted() && $postForm->isValid()) { // verifie que mon formulaire est soummi et valid
            
            $picture = $postForm->get('picture')->getData();
            
            if ($picture) {
                $uploadedPicturePath = $uploaderPostPicture->uploadPostImage($picture);

                $newPost->setPicture($uploadedPicturePath);
            }
            // les attributs qui ne sont pas dans notre formulaire
            $newPost->setRating(0)  // les likes sont mit a 0 lors de la crétion 
                    ->setNbresonse(0)  // le nonbre de reponse est mit a O lors de la création 
                    ->setAuthor($user)  // defini l'autheur du post
                    ->setCreatedAt(new DateTimeImmutable())  // la date de crétion 
                    ->setStatus(Post::STATUS_PRIVATE)
                    ->setReceiver($user);

            // $em = gestion des entités et de la communication avec la base de données
            $em->persist($newPost);  // preparation de l'entity à entrer en basse de donné
            $em->flush();  // entre en base de donné

            // redirige lutilisateur dans la méme page en lactualisant
            return $this->redirect($request->getUri());
        }
        $posts = $repository->findPostsOffUser($user);

        $friendShips = $friendshiprepository->findAddUsers($user);
        $iffriendship = count($friendShips) >0;

        $fiends = $friendshiprepository->findAcceptedFriendships($user);
        $addFriends = $userRepository->findUserNoConnectedWithRequestsPending($user);

        return $this->render('user/currentUser.html.twig', [
            'postForm' => $postForm->createView(),
            'user' => $user,
            'posts' => $posts,
            'friendships' => $friendShips,
            'iffriendship' => $iffriendship,
            'friends' => $fiends,
            'addfriends' => $addFriends,
        ]);
    }

   

    #[Route('/user/change-profil', name: 'changeProfil')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]

    public function changeProfil(Request $request,EntityManagerInterface $em,
    UploaderPicture $uploaderPicture, UserRepository $userRepository,
    FriendshipRepository $friendshiprepository): Response
    {
        /**
         * @var User
         */
        $user =$this->getUser();

        $userForm =$this->createForm(UserType::class, $user, ['new_user' => false]);
        $userForm->remove('password');
        
        $userForm->handleRequest($request);
        if($userForm->isSubmitted() && $userForm->isValid()){

        //  image de profil  //
        $picture = $userForm->get('picture')->getData();
        if ($picture) {
            $user->setPicture(($uploaderPicture->uploadProfilImage($picture, $user->getPicture())));
        }
        

        $em->flush();
        return $this->redirectToRoute('current_user');

        }
        $users = $userRepository->findAll();
        $fiends = $friendshiprepository->findAcceptedFriendships($user);
        $friendShips = $friendshiprepository->findAddUsers($user);
        $iffriendship = count($friendShips) >0;
        $addFriends = $userRepository->findUserNoConnectedWithRequestsPending($user);


        return $this->render('user/changeProfil.html.twig', [
            'form' => $userForm->createView(),
            'users' => $users,
            'friends' => $fiends,
            'friendships' => $friendShips,
            'iffriendship' => $iffriendship,
            'addfriends' => $addFriends,
        ]);
    }

    #[Route('/user/change-password', name: 'changePassword')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]

    public function changePassword(Request $request, EntityManagerInterface $em,
     UserPasswordHasherInterface $passwordHasher,UserRepository $userRepository,
     FriendshipRepository $friendshiprepository): Response
    {
        /**
         * @var User
         */
        $user =$this->getUser();

        
        $userForm = $this->createFormBuilder()
                         ->add('newPassword' , RepeatedType::class, [
                            'type' => PasswordType::class,
                            'constraints' => [
                                new NotBlank(['message' => 'Veuillez entrez deux mot de passe Identique']),
                                new Length([
                                    'min' => 5,
                                    'minMessage' => 'Veuillez entrez plus de 5 catactéres'])
                                ],
                            'invalid_message' => 'Les champs des mots de passe doivent correspondre.',
                            'required' => false,
                            'first_options'  => ['label' => 'Nouveau mot de passe'],
                            'second_options' => ['label' => 'Confirmer le nouveau mot de passe'],
                        ])
                        ->getForm();
        
        $userForm->handleRequest($request);
        if($userForm->isSubmitted() && $userForm->isValid()){
            $newPassword = $userForm->get('newPassword')->getData();
            
            if ($newPassword) {
                $hash = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hash);
            }
            $em->flush();

            return $this->redirectToRoute('current_user');
            
        }
        $users = $userRepository->findAll();
        $fiends = $friendshiprepository->findAcceptedFriendships($user);
        $friendShips = $friendshiprepository->findAddUsers($user);
        $iffriendship = count($friendShips) >0;
        $addFriends = $userRepository->findUserNoConnectedWithRequestsPending($user);




        return $this->render('user/changePassword.html.twig', [
            'form' => $userForm->createView(),
             'users' => $users,
             'friends' => $fiends,
             'friendships' => $friendShips,
            'iffriendship' => $iffriendship,
            'addfriends' => $addFriends,


        ]);
    }

    #[Route('/user/{id}', name: 'user')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]

    public function userProfil(User $user, Request $request,
    EntityManagerInterface $em, UserRepository $userRepository,
    PostRepository $repository,
    UploaderPostPicture $uploaderPostPicture,
    FriendshipRepository $friendshiprepository): Response
    {
    
        $currentUser = $this ->getUser();
        if ($currentUser === $user) {
            return $this->redirectToRoute('current_user');
        }

        

        $newPost = new Post();  // crée un nouveau post
        $postForm = $this->createForm(PostType::class, $newPost);  // création du formulaire
        $postForm->handleRequest($request);  // analyse et verifie la requete de mon formulaire

        if ($postForm->isSubmitted() && $postForm->isValid()) { // verifie que mon formulaire est soummi et valid
            $picture = $postForm->get('picture')->getData();
            
            if ($picture) {
                $uploadedPicturePath = $uploaderPostPicture->uploadPostImage($picture);
                $newPost->setPicture($uploadedPicturePath);

            }
            // les attributs qui ne sont pas dans notre formulaire
            $newPost->setRating(0)  // les likes sont mit a 0 lors de la crétion 
                    ->setNbresonse(0)  // le nonbre de reponse est mit a O lors de la création 
                    ->setAuthor($currentUser)  // defini l'autheur du post
                    ->setCreatedAt(new DateTimeImmutable())  // la date de crétion 
                    ->setStatus(Post::STATUS_PRIVATE)
                    ->setReceiver($user);

            // $em = gestion des entités et de la communication avec la base de données
            $em->persist($newPost);  // preparation de l'entity à entrer en basse de donné
            $em->flush();  // entre en base de donné

            
            // redirige lutilisateur dans la méme page en lactualisant
            return $this->redirect($request->getUri());
        }
        
        $addFriends = $userRepository->findUserNoConnectedWithRequestsPending($currentUser);

        $friendShips = $friendshiprepository->findAddUsers($currentUser);
        $iffriendship = count($friendShips) >0;


        $userFriendshipStatus = $friendshiprepository->getFriendshipStatus($currentUser, $user);

        $fiends = $friendshiprepository->findAcceptedFriendships($currentUser);
        
        $posts = $repository->findPostsOffUser($user);

        return $this->render('user/user.html.twig', [
            'postForm' => $postForm->createView(),
            'curentUser' => $currentUser,
            'user' => $user,
            'friendships' => $friendShips,
            'addfriends' => $addFriends,
            'iffriendship' => $iffriendship,
            'userFriendshipStatus' => $userFriendshipStatus,
            'posts' => $posts,
            'friends' => $fiends,

            
        ]);
    }

}
