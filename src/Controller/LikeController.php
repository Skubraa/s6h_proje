<?php

namespace App\Controller;

use App\Entity\MicroPost;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class LikeController extends AbstractController
{
    #[Route('/like/{id}', name: 'app_micro_post_like')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function like(
        MicroPost $microPost, 
        EntityManagerInterface $em, 
        Request $request
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Senin mantığın: Beğenmişse kaldır, beğenmemişse ekle (Toggle)
        if ($microPost->getLikedBy()->contains($currentUser)) {
            $microPost->removeLikedBy($currentUser);
        } else {
            $microPost->addLikedBy($currentUser);
        }

        $em->flush();

        // Kullanıcıyı geldiği sayfaya geri gönderir
        return $this->redirect($request->headers->get('referer'));
    }

    // Eğer 'unlike' için ayrı bir rota istersen (opsiyonel):
    #[Route('/unlike/{id}', name: 'app_micro_post_unlike')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function unlike(
        MicroPost $microPost, 
        EntityManagerInterface $em, 
        Request $request
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        $microPost->removeLikedBy($currentUser);
        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }
}