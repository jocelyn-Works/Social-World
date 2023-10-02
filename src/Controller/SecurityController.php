<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class SecurityController extends AbstractController
{

    public function __construct(private $formLoginAuthenticator)
    {  
    }

    #[Route('/', name: 'signup')]
    public function signup(Request $request, EntityManagerInterface $em,
    UserPasswordHasherInterface $passwordHasher,
    UserAuthenticatorInterface $userAuthenticator): Response
    {
        $user = new User();
        $userForm = $this->createForm(UserType::class, $user);
        $userForm->remove('picture');
        $userForm->handleRequest($request);
        
        if ($userForm->isSubmitted() && $userForm->isValid()) { 

            $user->setCreatedAt(new DateTimeImmutable())
                 ->setPicture("build/images/default_profiles.png");

            $hash = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hash);

            $em->persist($user);
            $em->flush();

            return $userAuthenticator->authenticateUser($user, $this->formLoginAuthenticator, $request);
        }
        return $this->render('security/signup.html.twig', [
            'form' => $userForm->createView()
        ]); 
    }

    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $username = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'error' => $error,
            'username' => $username
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout()
    {
    }
}
