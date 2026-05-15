<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfileImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('profileImage', FileType::class, [
                'label' => 'Profile image (JPG or PNG file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                // YENİ YAZIM ŞEKLİ: Dizi ([]) yerine doğrudan isimlendirilmiş argümanlar kullanıyoruz
                    new File(
                        maxSize: '2M',
                        maxSizeMessage: 'Seçtiğiniz dosya çok büyük. Lütfen en fazla 2MB boyutunda bir görsel yükleyin.',
                        mimeTypes: [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                        ],
                        mimeTypesMessage: 'Geçersiz dosya uzantısı! Lütfen sadece JPEG, JPG veya PNG formatında bir resim yükleyin.'
                    )
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
