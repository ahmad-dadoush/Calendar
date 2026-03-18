<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'E-Mail',
                'constraints' => [
                    new NotBlank(message: 'Bitte gib eine E-Mail-Adresse ein.'),
                    new Email(message: 'Bitte gib eine gueltige E-Mail-Adresse ein.'),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Die Passwoerter muessen uebereinstimmen.',
                'mapped' => false,
                'first_options' => ['label' => 'Passwort'],
                'second_options' => ['label' => 'Passwort bestaetigen'],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib ein Passwort ein.'),
                    new Length(
                        min: 8,
                        max: 4096,
                        minMessage: 'Dein Passwort muss mindestens {{ limit }} Zeichen lang sein.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}