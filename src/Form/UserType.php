<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

       class UserType extends AbstractType 
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $User = $builder->getData();
        $builder
        
        ->add('lastname',TextType::class,[
            'label' => 'Nom :'
            ])
            ->add('firstname',TextType::class,[
                'label' => 'Prénom :'
            ])
            ->add('email',EmailType::class,[
                'label' => 'Email :'
            ])
            
            ->add('picture', FileType::class,[
                'label' => 'Image de Profile :',
                'required' => $options['new_user'],
                'mapped' => false,
                'constraints' => [
                    new Image([
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => "Veuillez télécharger une image valide",
                        "maxSize" => '2M',
                        'maxSizeMessage' => "Votre image fait {{size}} {{suffix}}, La limite est de {{ limit }} {{suffix}}"
                        ]),
                    ]
                ])
            ->add('password',  PasswordType::class,[
                'label' => 'Mot de Passe :',
               
            ]);
            
                // ->add('roles')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'new_user' => true
        ]);
    }
}
