<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\UserProfile; // UserProfile Entity'sini dahil etmeyi unutmuyoruz
use Doctrine\ORM\EntityManagerInterface; // Veritabanına kaydetmek için gerekli
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Yeni bir kullanıcı hesabı oluşturmak için komut',
)]
class CreateUserCommand extends Command
{
    private UserPasswordHasherInterface $userPasswordHasher;
    private EntityManagerInterface $entityManager; // UserRepository yerine doğrudan Yöneticimizi alıyoruz
    
    public function __construct(UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->userPasswordHasher = $userPasswordHasher;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Kullanıcı e-postası')
            ->addArgument('password', InputArgument::REQUIRED, 'Kullanıcı şifresi')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        // 1. Yeni Kullanıcıyı Oluştur
        $user = new User();
        $user->setEmail($email);
        $user->setPassword(
            $this->userPasswordHasher->hashPassword($user, $password)
        );

        // 2. KRİTİK ADIM: Profil Oluştur ve Bağla
        $userProfile = new UserProfile();
        // İsim girmedikleri için e-postanın '@' işaretinden önceki kısmını geçici isim yapalım
        $geciciIsim = explode('@', $email)[0]; 
        $userProfile->setName(ucfirst($geciciIsim)); 
        
        // Profili kullanıcıya bağla
        $user->setUserProfile($userProfile);

        // 3. Veritabanına Gönder (Flush)
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('%s e-posta adresiyle kullanıcı (ve profili) başarıyla oluşturuldu!', $email));

        return Command::SUCCESS;
    }
}