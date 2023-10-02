<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use DateTimeImmutable;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/post', name: 'home')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function index( Request $request, EntityManagerInterface $em, PostRepository $repository): Response
    {

        $user = $this->getUser();

        $newPost = new Post();  // crée un nouveau post
        $postForm = $this->createForm(PostType::class, $newPost);  // création du formulaire
        $postForm->handleRequest($request);  // analyse et verifie la requete de mon formulaire

        if ($postForm->isSubmitted() && $postForm->isValid()) { // verifie que mon formulaire est soummi et valid
            // les attributs qui ne sont pas dans notre formulaire
            $newPost->setRating(0)  // les likes sont mit a 0 lors de la crétion 
                    ->setNbresonse(0)  // le nonbre de reponse est mit a O lors de la création 
                    ->setAuthor($user)  // defini l'autheur du post
                    ->setCreatedAt(new DateTimeImmutable());  // la date de crétion 

            // $em = gestion des entités et de la communication avec la base de données
            $em->persist($newPost);  // preparation de l'entity à entrer en basse de donné
            $em->flush();  // entre en base de donné

            // redirige lutilisateur dans la méme page en lactualisant
            return $this->redirect($request->getUri());
        }

        $posts = $repository->findPosteWithUsers();  // récuperer tous les post en basse de donné 1 requéte

        return $this->render('home/index.html.twig', [
            'postForm' => $postForm->createView(),  // pour l'appelle de mon formulaire
            'posts' => $posts,        // pour afficher mes posts
            'user' => $user
            
        ]);
    }

    #[Route('/post/{id}', name: 'post_show')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function show(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        $options = [
            'post' =>$post,
        ];
        $user = $this->getUser();

        if($user){
            $comment = new Comment();
            $commentForm = $this->createForm(CommentType::class, $comment);
            $commentForm->handleRequest($request);

            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                $comment->setCreatedAt(new DateTimeImmutable())
                        ->setRating(0)
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
        

        return $this->render('home/show.html.twig', $options );
    }
}
