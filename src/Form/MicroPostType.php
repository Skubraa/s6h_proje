<?php

// src/Form/MicroPostType.php
namespace App\Form; 

use App\Entity\MicroPost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MicroPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Öğrenme Başlığı',
                'attr' => [
                    'placeholder' => 'Örn: Bugün Symfony Öğrendim!'
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
