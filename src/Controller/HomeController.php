<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\UploaderPostPicture;
use App\Repository\FriendshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/post', name: 'post')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function index( Request $request, EntityManagerInterface $em,
    PostRepository $repository, UserRepository $userRepository,
    UploaderPostPicture $uploaderPostPicture,
    FriendshipRepository $friendshipRepository): Response
    {

        $currentUser = $this->getUser();
        

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
                    ->setStatus(Post::STATUS_PUBLIC); 
                    

            // $em = gestion des entités et de la communication avec la base de données
            $em->persist($newPost);  // preparation de l'entity à entrer en basse de donné
            $em->flush();  // entre en base de donné

            // redirige lutilisateur dans la méme page en lactualisant
            return $this->redirect($request->getUri());
        }

        $posts = $repository->findPosteWithUsers();  // récuperer tous les post en basse de donné 1 requéte

        $addFriends = $userRepository->findUserNoConnectedWithRequestsPending($currentUser);  // 

        $fiends = $friendshipRepository->findAcceptedFriendships($currentUser);  // les demande accepter 
        
        $friendShips = $friendshipRepository->findAddUsers($currentUser);  //  les demande d'ami

        $iffriendship = count($friendShips) >0; // pour savoir si il y a une notificatication 
        

        return $this->render('home/post.html.twig', [
            'postForm' => $postForm->createView(),  // pour l'appelle de mon formulaire
            'posts' => $posts,        // pour afficher mes posts
            'user' => $currentUser,  // utilisateur connecter 
            'addfriends' => $addFriends,     // tous les utilisateurs
            'friendships' => $friendShips,  // notifications 
            'iffriendship' => $iffriendship,   // si il y a une notification 
            'friends' => $fiends, // liste des amis
            
        ]);
    }

    #[Route('/post/comment/{id}', name: 'post_show')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function show(int $id, Post $post, PostRepository $postRepository,
    Request $request, EntityManagerInterface $em, UserRepository $userRepository,
    FriendshipRepository $friendshipRepository): Response
    {
        $user = $this->getUser();

        $post = $postRepository->findPosteWithCommentsAndUsers($id);
        $users = $userRepository->findAll();
        $friendShips = $friendshipRepository->findAddUsers($user);
        $iffriendship = count($friendShips) >0;
        $addFriends = $userRepository->findUserNoConnectedWithRequestsPending($user);
        $fiends = $friendshipRepository->findAcceptedFriendships($user);



        $options = [
            'post' =>$post,
            'users' => $users,
            'friendships' => $friendShips,
            'addfriends' => $addFriends,
            'iffriendship' => $iffriendship,
            'friends' => $fiends, // liste des amis

        ];

        if($user){
            $comment = new Comment();
            $commentForm = $this->createForm(CommentType::class, $comment);
            $commentForm->handleRequest($request);

            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                $comment->setRating(0)
                        ->setAuthor($user)
                        ->setPost($post);

                $post->setNbresonse(($post->getNbresonse() +1));

                $em->persist($comment);
                $em->flush();

                // redirige lutilisateur dans la méme page en lactualisant
                return $this->redirect($request->getUri());

            }
            $options['form'] = $commentForm->createView();
         
        }
        
        return $this->render('home/comment.html.twig', $options );
    }
    
   
}
