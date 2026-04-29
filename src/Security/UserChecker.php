<?php
// src/Security/UserChecker.php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // 1. KONTROL: Mail Onayı
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Hesabınız henüz doğrulanmamış. Lütfen e-posta adresinizi kontrol edin.'
            );
        }

        // 2. KONTROL: Ban Durumu (bannedUntil)
        if ($user->getBannedUntil() && $user->getBannedUntil() > new \DateTime()) {
            $formattedDate = $user->getBannedUntil()->format('d.m.Y H:i');
            throw new CustomUserMessageAccountStatusException(
                "Hesabınız kısıtlanmıştır. Erişim engeliniz şu tarihe kadar sürecektir: $formattedDate"
            );
        } 
    }

        /**
         * @param User $user
         * @param TokenInterface|null $token // Yeni parametre
         */
        public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
        {
            // Artık metodun imzası Symfony'nin beklediği standartta.
        }
}