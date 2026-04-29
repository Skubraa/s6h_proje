<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           ->add('email', EmailType::class, [
            'constraints' => [
                new NotBlank(
                    message: 'Lütfen bir e-posta adresi girin'
                ),
                new Email(
                    message: 'Lütfen geçerli bir e-posta adresi girin.'
                ),
            ],
        ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Şartlarımızı kabul etmelisiniz.',
                    ),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => [
                    'label' => 'Şifre',
                    'attr' => [
                        'placeholder' => '••••••••',
                        'class' => 'w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition duration-200'
                    ],
                ],
                'second_options' => [
                    'label' => 'Şifre Tekrar',
                    'attr' => [
                        'placeholder' => '••••••••',
                        'class' => 'w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition duration-200'
                    ],
                ],
                'invalid_message' => 'Girdiğiniz şifreler birbiriyle eşleşmiyor.',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Lütfen bir şifre girin'),
                    new Length(
                        min: 6,
                        minMessage: 'Şifreniz en az {{ limit }} karakter olmalıdır',
                        max: 4096,
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
