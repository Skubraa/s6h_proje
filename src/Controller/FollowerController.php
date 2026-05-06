<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class FollowerController extends AbstractController
{
    #[Route('/follow/{id}', name: 'app_follow')]
    #[IsGranted('ROLE_USER')]
    public function follow(
        User $userToFollow,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // HATA BURADAYDI: Kullanıcı kendisi DEĞİLSE takip edebilmeli
        if ($userToFollow->getId() !== $currentUser->getId()) {
            $currentUser->follow($userToFollow);
            $em->flush();
            $this->addFlash('success', $userToFollow->getUserProfile()->getName() . ' takibe alındı.');
        }

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/unfollow/{id}', name: 'app_unfollow')]
    #[IsGranted('ROLE_USER')]
    public function unfollow(
        User $userToUnfollow,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Kullanıcı kendisi DEĞİLSE takibi bırakabilmeli
        if ($userToUnfollow->getId() !== $currentUser->getId()) {
            $currentUser->unfollow($userToUnfollow);
            $em->flush();
            $this->addFlash('info', 'Takibi bıraktınız.');
        }

        return $this->redirect($request->headers->get('referer'));
    }
}