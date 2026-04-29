<?php

// src/DataFixtures/AppFixtures.php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserProfile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    // Constructor ile şifreleme servisini içeri alıyoruz
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // 1. Bir User oluştur
        $user1 = new User();
        $user1->setEmail('sumeyye@test.com');
        // Şifreyi hashleyerek set et
        $user1->setPassword(
            $this->userPasswordHasher->hashPassword($user1, '123456')
        );

        // 2. Bu kullanıcı için bir UserProfile oluştur
        $profile1 = new UserProfile();
        $profile1->setName('Sümeyye');
        $profile1->setBio('Bilgisayar Mühendisi ve Symfony Geliştiricisi');

        // 3. İlişkiyi bağla (One-to-One)
        $user1->setUserProfile($profile1);

        $manager->persist($user1);
        $manager->persist($profile1);

        $manager->flush();
    }
}