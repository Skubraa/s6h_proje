<?php

namespace App\Controller;

use App\Entity\UserProfile; // UserProfile Entity'si için
use App\Form\UserProfileType; // Form sınıfı için
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserProfileRepository; // Repository sınıfı için
use App\Entity\User; // User Entity'si için

final class SettingsProfileController extends AbstractController
{
    

    #[Route('/settings/profile', name: 'app_settings_profile')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')] 
    public function profile(
        Request $request,
        EntityManagerInterface $entityManager, // Buraya virgül eklendi
        UserProfileRepository $profileRepository // Buraya virgül eklendi
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Güvenlik önlemi (İyi bir pratik)
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // DUPLICATE ENTRY HATASINI ÖNLEYEN MANTIK:
        // Önce Repository üzerinden veritabanında bu kullanıcıya ait profil var mı diye bakıyoruz
        $userProfile = $profileRepository->findOneBy(['user' => $user]);

        // Eğer veritabanında yoksa, yeni bir nesne oluşturup kullanıcıyı bağlıyoruz
        if (!$userProfile) {
            $userProfile = new UserProfile();
            $userProfile->setUser($user);
        }

        $form = $this->createForm(UserProfileType::class, $userProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Formdan gelen güncel verileri nesneye işliyoruz
            $userProfile = $form->getData();
            
            // İlişkiyi tekrar garantiye alıyoruz (Yeni profilse null kalmasın)
            if (null === $userProfile->getUser()) {
                $userProfile->setUser($user);
            }

            $entityManager->persist($userProfile);
            $entityManager->flush();

            $this->addFlash('success', 'Profil bilgileriniz başarıyla güncellendi.');

            return $this->redirectToRoute('app_settings_profile');
        }

        return $this->render('settings_profile/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}