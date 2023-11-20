<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Entity\Vote;
use App\Repository\VoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RatingController extends AbstractController
{
    #[Route('/post/rating/{id}/{score}', name: 'post_rating')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function ratepost(Request $request, Post $post, VoteRepository $voteRepository,
     int $score, EntityManagerInterface $em): Response
    {   
        $user = $this->getUser();
        // verifie que lutilisateurs est pas propriétaire du post
        if ($user !== $post->getAuthor()) {
            // verifie si user a deja voter en recuperant le vote
            $vote = $voteRepository->findOneBy([
                'author' => $user,
                'post' => $post
            ]) ?? null;

            if ($vote) {
        // s'il avait aimer la question et qu'il reclique sur la fleche du haut c'est pour enlever son vote
        // s'il n'avait pas aimer et qu'il reclique sur la fleche du bas c'est pour enlever son vote
                if (($vote->isIsLiked() && $score > 0 || (!$vote->isIsLiked() && $score < 0 ))){
                    // on suprime le vote
                    $em->remove($vote);
                    $post->setRating($post->getRating() + ($score > 0 ? -1 : 1));   
                }else{
                    // on met l'inverse
                    $vote->setIsLiked(!$vote->isIsliked());
                    $post->setRating($post->getRating() + ($score > 0 ? 2 : -2));  
                }
            }else {
            // si pas de vote alors on en creer un
                $vote = new Vote();
                $vote->setAuthor($user)
                     ->setPost($post)
                     ->setisLiked($score > 0 ? true : false);// si le score est positif c'est qu'il aime sinon c'est false
                $em->persist($vote);
                $post->setRating($post->getRating() + $score);
            }
            

            $em->flush();
        }
        // rediriger l'utilisateur d'ou il vient
        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirect(('post'));
    }

    #[Route('/comment/rating/{id}/{score}', name: 'comment_rating')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function ratecomment(Request $request, Comment $comment, VoteRepository $voteRepository, int $score, EntityManagerInterface $em): Response
    {   
        $user = $this->getUser();
        // verifie que lutilisateurs est pas propriétaire du post
        if ($user !== $comment->getAuthor()) {
            // verifie si user a deja voter en recuperant le vote
            $vote = $voteRepository->findOneBy([
                'author' => $user,
                'comment' => $comment
            ]) ?? null;

            if ($vote) {
        // s'il avait aimer la question et qu'il reclique sur la fleche du haut c'est pour enlever son vote
        // s'il n'avait pas aimer et qu'il reclique sur la fleche du basc'est pour enlever son vote
                if (($vote->isIsLiked() && $score > 0 || (!$vote->isIsLiked() && $score < 0 ))){
                    // on suprime le vote
                    $em->remove($vote);
                    $comment->setRating($comment->getRating() + ($score > 0 ? -1 : 1));   
                }else{
                    // on met l'inverse
                    $vote->setIsLiked(!$vote->isIsliked());
                    $comment->setRating($comment->getRating() + ($score > 0 ? 2 : -2));  
                }
            }else {
            // si pas de vote alors on en creer un
                $vote = new Vote();
                $vote->setAuthor($user)
                     ->setComment($comment)
                     ->setisLiked($score > 0 ? true : false);// si le score est positif c'est qu'il aime sinon c'est false
                $em->persist($vote);
                $comment->setRating($comment->getRating() + $score);
            }
            

            $em->flush();
        }
        // rediriger l'utilisateur d'ou il vient
        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirect(('post'));
    }
}
