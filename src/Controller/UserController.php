<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\UserType;
use App\Service\UploaderPicture;
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

    public function currentUser(UserInterface $user): Response
    {
        
        return $this->render('user/currentUser.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/user/change-profil', name: 'changeProfil')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]

    public function changeProfil(Request $request,EntityManagerInterface $em, UploaderPicture $uploaderPicture): Response
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

        return $this->render('user/changeProfil.html.twig', [
            'form' => $userForm->createView()
        ]);
    }

    #[Route('/user/change-password', name: 'changePassword')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]

    public function changePassword(Request $request, EntityManagerInterface $em,
     UserPasswordHasherInterface $passwordHasher): Response
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
        return $this->render('user/changePassword.html.twig', [
            'form' => $userForm->createView()
        ]);
    }

    #[Route('/user/{id}', name: 'user')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]

    public function userProfil(User $user): Response
    {
        

        $currentUser = $this ->getUser();
        if ($currentUser === $user) {
            return $this->redirectToRoute('current_user');
        }
        
        return $this->render('user/user.html.twig', [
            'user' => $user
            
        ]);
    }
}
