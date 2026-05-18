<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            try {
                $this->sendVerificationEmail($user);
            } catch (TransportExceptionInterface) {
                $this->addFlash('verify_email_error', 'Hesabınız oluşturuldu ama doğrulama e-postası gönderilemedi. Lütfen Mailcatcher çalışıyorsa tekrar doğrulama maili isteyin.');

                return $this->redirectToRoute('app_verify_resend_email', [
                    'email' => $user->getEmail(),
                ]);
            }

            $this->addFlash('success', 'Kayıt tamamlandı. Siteye girebilmek için e-posta adresinize gelen doğrulama linkine tıklayın.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $user = null;
        $userId = $request->query->get('id');

        if ($userId) {
            $user = $userRepository->find($userId);
        } elseif ($this->getUser() instanceof User) {
            $user = $this->getUser();
        }

        if (!$user) {
            $this->addFlash('verify_email_error', 'Doğrulama linki kullanıcı bilgisi içermiyor. Lütfen yeni bir doğrulama maili isteyin.');

            return $this->redirectToRoute('app_verify_resend_email');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            // translator->trans satırını siliyoruz ve kendi mesajımızı yazıyoruz
            $this->addFlash('verify_email_error', 'Doğrulama linkinin süresi dolmuş veya geçersiz. Lütfen tekrar kayıt olmayı deneyin veya yeni bir link talep edin.');

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'E-posta adresiniz başarıyla doğrulandı! Şimdi giriş yapabilirsiniz.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/verify/resend', name: 'app_verify_resend_email')]
    public function resendVerificationEmail(Request $request, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user && $request->isMethod('GET')) {
            return $this->render('registration/resend_verification.html.twig', [
                'email' => $request->query->get('email', ''),
            ]);
        }

        if (!$user) {
            if (!$this->isCsrfTokenValid('resend_verification_email', (string) $request->request->get('_csrf_token'))) {
                throw $this->createAccessDeniedException('Geçersiz güvenlik anahtarı.');
            }

            $email = trim((string) $request->request->get('email'));
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('verify_email_error', 'Bu e-posta adresiyle kayıtlı bir hesap bulunamadı.');

                return $this->redirectToRoute('app_verify_resend_email', [
                    'email' => $email,
                ]);
            }
        }

        if ($user->isVerified()) {
            $this->addFlash('success', 'Hesabınız zaten doğrulanmış. Giriş yapabilirsiniz.');

            return $this->redirectToRoute('app_login');
        }

        try {
            $this->sendVerificationEmail($user, 'Yeni Doğrulama Linkiniz');
        } catch (TransportExceptionInterface) {
            $this->addFlash('verify_email_error', 'Doğrulama e-postası gönderilemedi. Mailcatcher servisinin çalıştığından emin olup tekrar deneyin.');

            return $this->redirectToRoute('app_verify_resend_email', [
                'email' => $user->getEmail(),
            ]);
        }

        $this->addFlash('success', 'Yeni doğrulama linki e-posta adresinize gönderildi.');

        return $this->redirectToRoute('app_login');
    }

    private function sendVerificationEmail(User $user, string $subject = 'Please Confirm your Email'): void
    {
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('accounts@micropost.com', 'MicroPost'))
                ->to((string) $user->getEmail())
                ->subject($subject)
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }
}
