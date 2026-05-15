<?php

namespace App\Controller;

use App\Entity\User; // User Entity'si için
use App\Entity\UserProfile; // UserProfile Entity'si için
use App\Form\ProfileImageType;
use App\Form\UserProfileType; // Form sınıfı için
use App\Repository\UserProfileRepository; // Repository sınıfı için
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

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

    #[Route('/settings/profile-image', name: 'app_settings_profile_image')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function profileImage(
        Request $request,
        SluggerInterface $slugger,
        EntityManagerInterface $entityManager // UserRepository yerine bunu ekledik
    ): Response {
        $form = $this->createForm(ProfileImageType::class);
        /** @var User $user */
        $user = $this->getUser();
        $form->handleRequest($request);

        // FORM GÖNDERİLDİYSE BU BLOK ÇALIŞIR
        if ($form->isSubmitted()) {
            
            // Eğer form KURALLARA UYUYORSA (dosya boyutu ve uzantısı doğruysa)
            if ($form->isValid()) {
                $profileImageFile = $form->get('profileImage')->getData();

                if ($profileImageFile) {
                    $originalFileName = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFileName);
                    $newFileName = $safeFilename . '-' . uniqid() . '.' . $profileImageFile->guessExtension();

                    try {
                        $profileImageFile->move(
                            $this->getParameter('profiles_directory'),
                            $newFileName
                        );
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Dosya yüklenirken sistemsel bir hata oluştu.');
                        return $this->redirectToRoute('app_settings_profile_image');
                    }

                    $profile = $user->getUserProfile() ?? new UserProfile();
                    $profile->setImage($newFileName);
                    
                    // İlişkiyi garantiye alalım (yeni profilse kullanıcı atanmamış olabilir)
                    if (null === $profile->getUser()) {
                        $profile->setUser($user);
                    }

                    // YENİ KAYIT MANTIĞI: EntityManager ile veritabanına işliyoruz
                    $entityManager->persist($profile);
                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Profil resminiz güncellendi.');
                    return $this->redirectToRoute('app_settings_profile_image');
                }
            } 
            // EĞER FORM GEÇERSİZSE (Kurallara uymayan bir durum varsa)
            else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
            }
        }

        // Sayfa ilk açıldığında veya form hatalıysa bu blok çalışır
        return $this->render('settings_profile/profile_image.html.twig', [
            'form' => $form->createView(),
        ]);

    }
}