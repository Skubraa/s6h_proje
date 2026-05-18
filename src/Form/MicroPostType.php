<?php

// src/Form/MicroPostType.php
namespace App\Form; 

use App\Entity\MicroPost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MicroPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', ChoiceType::class, [
                'label' => 'Paylaşım Türü',
                'choices'  => [
                    'Öğrenme Günlüğü' => 'learning',
                    'Staj Günlüğü' => 'internship',
                    'İş Günlüğü' => 'work',
                    'Normal Günlük' => 'personal',
                ],
                'attr' => [
                    'class' => 'w-full mb-4 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors'
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Başlık',
                'attr' => [
                    'placeholder' => 'Örn: Bugün Symfony Öğrendim / Stajda 1. Gün / İş Yerinde Başarı'
                ],
                'required' => true, // Tarayıcı (HTML5) zorunluluğunu kaldırıyoruz ki Symfony devreye girsin
                
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Açıklama',
                'attr' => [
                    'placeholder' => 'Neler hissettiğini buraya yazabilirsin...',
                    'rows' => 5
                ],
                'required' => true, // Aynı şekilde burayı da serbest bırakıyoruz
            ])
            ->add('extraPrivacy', CheckboxType::class, [
                'label' => 'Bu paylaşımı ekstra gizli yap (Sadece takipçilerim görebilir)',
                'required' => false, 
            ]);
        ;
    }

    // src/Form/MicroPostType.php
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MicroPost::class,
            'csrf_protection' => true,
            
        ]);
    }
}
