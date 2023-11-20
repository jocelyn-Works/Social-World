<?php

namespace App\Controller;

use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\ResetPasswordRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Validator\Constraints\Length;

class SecurityController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(private $formLoginAuthenticator, EntityManagerInterface $em)
    {  
        $this->em = $em;
    }

    #[Route('/', name: 'signup')]
    public function signup(Request $request,
    UserPasswordHasherInterface $passwordHasher,
    UserAuthenticatorInterface $userAuthenticator,
    MailerInterface $mailer): Response
    {     
        // Inscription d'un nouveaux utilisateur 
        
        $user = new User();
        // création du formulaire
        $userForm = $this->createForm(UserType::class, $user);
        $userForm->remove('picture');
        $userForm->handleRequest($request);
        
        if ($userForm->isSubmitted() && $userForm->isValid()) { 

            $user->setPicture("build/images/default_profiles.png"); 
            // hashage du mot de passe
            $hash = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hash);

            // Enregistrement dans la Base de Données
            $this->em->persist($user); 
            $this->em->flush();

            // Envoi d'un e-mail
            $email = new TemplatedEmail();
            $email->to($user->getEmail())
                  ->subject('Bienvenu sur Social World')  // objet de l'email
                  ->htmlTemplate('@email_templates/welcome.html.twig')
                  ->context([
                    'username' => $user->getFirstname()
                  ]);
            $mailer->send($email);  

                  // Connexion de l'utilisateur aprés inscription
            return $userAuthenticator->authenticateUser($user, $this->formLoginAuthenticator, $request);
        }
        return $this->render('security/signup.html.twig', [   
            'form' => $userForm->createView()  // Appele du formulaire
        ]); 
    }

    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {           // connexion 

        // erreur dauthentification 
        $error = $authenticationUtils->getLastAuthenticationError();
        // récupère le nom entré par l'utilisateur lors de la derniére connxion
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
 
    #[Route('/reset-password-request', name: 'reset-password-request')]
    public function resetPasswordRequest(Request $request, UserRepository $userRepository,
    ResetPasswordRepository $resetPasswordRepository,
    MailerInterface $mailer, RateLimiterFactory $passwordRecoveryLimiter)
        {

            // limitte les demande de réinitialisation // configurer dans rate-limitter.yaml 
            // recuperer l'adresse IP car l'utilisateurs n'est pas connecter
            $limiter = $passwordRecoveryLimiter->create($request->getClientIp());
            
            // creation du formulaire pour entrer l'email
            $emailForm = $this->createFormBuilder()
                ->add('email', EmailType::class, [
                'label' => 'Entrez votre email' ,       
                'constraints' => [
                        new NotBlank(['message' => 'Veuillez renseigner ce champ.']),
                        new Email(['message' => 'Veuillez entrer un email valide.'])
                    ],
                    'required' => false
                ])
                ->getForm();

            $emailForm->handleRequest($request);
            if ($emailForm->isSubmitted() && $emailForm->isValid()) {
                // décrementer le nombre de tentative 
                if(!$limiter->consume(1)->isAccepted()) {
                    $this->addFlash('error', 'vous devez attendre 1 heure pour refaire une demande.');
                    return $this->redirectToRoute('login');
                }

                $email = $emailForm->get('email')->getData(); // récupére l'email
                $user = $userRepository->findOneBy(['email' => $email]); // trouve un utilisateur qui corespont a mon email

                if($user) { // si jai un utilisateur
                    $olldResetPassword = $resetPasswordRepository->findOneBy(['user' => $user]); // ancienne demande
                    if($olldResetPassword) {  // si deja une demande
                        $this->em->remove($olldResetPassword);  // suprime la demande
                        $this->em->flush(); // enregiste en bdd
                    }
                    // création du token 
                    $token = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(40))), 0, 20);
                    $resetPassword = new ResetPassword(); // nouvelle entrée dans reset_password
                    $resetPassword->setUser($user)
                                  ->setExpiredAt(new \DateTimeImmutable('+ 2hours'))
                                  ->setToken(sha1($token));
                                   
                    // eregistre la demande
                    $this->em->persist($resetPassword); 
                    $this->em->flush();

                    // création d'un email
                    $resetEmail = new TemplatedEmail();
                    $resetEmail->to($user->getEmail())
                          ->subject('Demande de réinitialisation de mot de passe')
                          ->htmlTemplate('@email_templates/reset-password-request.html.twig')
                          ->context([
                            'username' => $user->getFirstname(),
                            'token' => $token
                          ]);
                    $mailer->send($resetEmail); 

                    $this->addFlash('Success', 'un email vous a ete envoye');

                    return $this->redirect($request->getUri());

                }

            }
            
        return $this->render('Security/reset-password-request.html.twig', [
            'form' => $emailForm->createView(),
        ]);
        }

        #[Route('/reset-password/{token}', name: 'reset-password')]
        public function resetPassword(string $token,
        ResetPasswordRepository $resetPasswordRepository,Request $request,
        UserPasswordHasherInterface $hasherPassword,RateLimiterFactory $passwordRecoveryLimiter,
        MailerInterface $mailer)
        {   
            // limitte les demande de réinitialisation // configurer dans rate_limitter.yaml 
            // recuperer l'adresse IP car l'utilisateurs n'est pas connecter
            $limiter = $passwordRecoveryLimiter->create($request->getClientIp());
            // décrementer le nombre de tentative 
            if(!$limiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'vous devez attendre 1 heure pour refaire une demande.');
                return $this->redirectToRoute('login');
            }
            

            // récupere le token et verifie en meme temps 
            $resetPassword = $resetPasswordRepository->findOneBy(['token' => sha1($token)]);

            //si il n'y a pas de jeton ou la date a expire, on le suprime
            if(!$resetPassword || $resetPassword->getExpiredAt() < new DateTime('now')) {
                if ($resetPassword) {
                    $resetPasswordRepository->remove($resetPassword, true); // supprime
                    $this->em->flush(); // enregistre
                }
                $this->addFlash('error', 'Votre demande a expire, Veuillez en refaire une.');

                return $this->redirectToRoute('login');
            }
            // creation du formulaire pour changer le mot de passe
            $resetPasswordForm = $this->createFormBuilder()
                                ->add('newPassword', RepeatedType::class, [
                                    'type' => PasswordType::class,
                                    'constraints' => [
                                        new NotBlank(['message' => 'Veuillez entrer un mot de passe.']),
                                        new Length([
                                            'min' => 5,
                                            'minMessage' => 'Veuillez entrer plus de 5 caracteres'
                                        ])
                                    ],
                                    'required' => false,
                                    'first_options' => ['label' => 'Nouveau mot de passe'],
                                    'second_options' => ['label' => 'Répéter le mot de passe'],
                                    'label' => 'Nouveau mot de passe'
                                    ])
                                ->getForm();

            $resetPasswordForm->handleRequest($request);

            if($resetPasswordForm->isSubmitted() && $resetPasswordForm->isValid()){

                $newPassword = $resetPasswordForm->get('newPassword')->getData();
                $user = $resetPassword->getUser();

                //hashPassword
                $hash = $hasherPassword->hashPassword($user, $newPassword);
                $user->setPassword($hash);

                $this->em->remove($resetPassword);
                $this->em->flush();

                $resetEmail = new TemplatedEmail();
                    $resetEmail->to($user->getEmail())
                          ->subject('Réinitialisation de mot de passe')
                          ->htmlTemplate('@email_templates/reset-password.html.twig')
                          ->context([
                            'username' => $user->getFirstname(),
                          ]);
                    $mailer->send($resetEmail);

                $this->addFlash('success', 'Votre mot de passe a ete modifie');
                return $this->redirectToRoute('login');
                

            }

            return $this->render('Security/reset-password.html.twig', [
                'form' => $resetPasswordForm->createView()
            ]);
        }
    
}
