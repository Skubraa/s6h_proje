<?php

namespace App\Security\Voter;

use App\Entity\MicroPost;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class MicroPostVoter extends Voter
{
    public function __construct(
        private Security $security
    ) {

    }
    

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [MicroPost::EDIT, MicroPost::VIEW])
            && $subject instanceof MicroPost;
    }

    /** 
     * @param MicroPost $subject
    */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {   
        
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        // if (!$user instanceof UserInterface) {
        //     $vote?->addReason('The user must be logged in to access this resource.');

        //     return false;
        // }

        // 1. Kullanıcı giriş yapmamışsa zaten hayır
        if (!$user instanceof UserInterface) {
            return false;
        }

        // 2. ADMIN ise sorgusuz sualsiz evet
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        
        /** @var User $user */
        /** @var MicroPost $subject */
        
        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case MicroPost::EDIT:
            case 'POST_DELETE': // Silme yetkisi
                return $subject->getAuthor()->getId() === $user->getId();

            case MicroPost::VIEW:
                // 1. ERKEN ÇIKIŞ: Eğer post ekstra gizli değilse, herkes görebilir.
                if (!$subject->isExtraPrivacy()) {
                    return true;
                }

                // 2. GÜVENLİK: Post gizli ve kişi giriş yapmamışsa (anonimse), asla göremez.
                if (!$user instanceof UserInterface) { // (veya kursun $isAuth değişkeni)
                    return false;
                }

                // 3. İŞ MANTIĞI (Senin kararın): Yazarın kendisi Mİ, yoksa TAKİPÇİLERİNDEN biri mi?
                return $subject->getAuthor()->getId() === $user->getId() 
                    || $subject->getAuthor()->getFollowers()->contains($user);
        }

        return false;
    }
}
