<?php

namespace App\Controller;

use App\Entity\MicroPost;
use App\Entity\Comment;
use App\Entity\User;
use App\Repository\MicroPostRepository;
use App\Service\InternshipReportGenerator;
use App\Form\MicroPostType;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


final class MicroPostController extends AbstractController
{
    #[Route('/micro/post', name: 'app_micro_post')]
    public function index(MicroPostRepository $posts): Response
    {   
        return $this->render('micro_post/index.html.twig', [
            'posts' => $posts->findAllWithComments(), 
            'active_category' => 'all'
        ]);
    }

    #[Route('/micro/post/category/{category}', name: 'app_micro_post_category')]
    public function category(string $category, MicroPostRepository $posts): Response
    {   
        return $this->render('micro_post/index.html.twig', [
            'posts' => $posts->findAllByCategory($category), 
            'active_category' => $category
        ]);
    }

   #[Route('/micro/post/add', name: 'app_micro_post_add')]
    #[IsGranted('ROLE_WRITER')] // Sadece e-postası onaylı olanlar girebilir
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {   
        // 1. KAPI KONTROLÜ: Kullanıcı giriş yapmamışsa, onu anında Login sayfasına fırlat!
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $post = new MicroPost();
        
        // Eğer URL'den category parametresi geliyorsa, varsayılan olarak onu seç
        $categoryParam = $request->query->get('category');
        if ($categoryParam && in_array($categoryParam, ['learning', 'internship', 'work', 'personal'])) {
            $post->setCategory($categoryParam);
        }
        
        $form = $this->createForm(MicroPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $post = $form->getData();
            
            // --- YENİ EKLENEN KRİTİK GÜVENLİK SATIRI ---
            // Sisteme giriş yapmış kullanıcıyı (app.user) al ve postun yazarı olarak belirle
            $post->setCreated(new \DateTime());
            $post->setAuthor($this->getUser()); 
            
            // ------------------------------------------
           
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Harika! Yeni postun yayınlandı.');
            
            return $this->redirectToRoute('app_micro_post_show', ['id' => $post->getId()]);
        }
        
        return $this->render('micro_post/add.html.twig', [
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 
            Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/micro/post/top-liked', name: 'app_micro_post_topliked')]
    public function topLiked(MicroPostRepository $posts): Response
    {
        return $this->render(
            'micro_post/top_liked.html.twig',
            [
                'posts' => $posts->findAllWithMinLikes(2),
            ]
        );
    }

    #[Route('/micro/post/follows', name: 'app_micro_post_follows')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')] // Giriş zorunluluğu
    public function follows(MicroPostRepository $posts): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser(); // Giriş yapan kullanıcıyı al

        return $this->render(
            'micro_post/follows.html.twig',
            [
                // findAllWithComments yerine yazar filtresi kullanmalısın
                'posts' => $posts->findAllByAuthor($currentUser->getFollows()),
            ]
        );
    }

    #[Route('/micro/post/{id}/delete', name: 'app_micro_post_delete')]
    #[IsGranted(MicroPost::EDIT, subject: 'post')]
    public function delete(MicroPost $post, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', 'Gönderi başarıyla silindi!');
        return $this->redirectToRoute('app_micro_post');
    }

#[Route('/micro/post/{id}/edit', name: 'app_micro_post_edit')]
#[IsGranted(MicroPost::EDIT, subject: 'post')]
public function edit(MicroPost $post, Request $request, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(MicroPostType::class, $post);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush(); 
        return $this->redirectToRoute('app_micro_post');
    }

    return $this->render('micro_post/edit.html.twig', [
        'form' => $form->createView(),
    ]);
}


#[Route('/micro/post/{id}', name: 'app_micro_post_show')]
public function show(int $id, MicroPostRepository $repository, Request $request, EntityManagerInterface $entityManager): Response
{
    // 1. Nesneyi ara
    $post = $repository->find($id);

   // Eğer id 7 silinmişse ve $post boş (null) dönerse:
    if (!$post) {
        // Kullanıcıya bilgi verelim
        $this->addFlash('warning', 'Aradığınız gönderi silinmiş veya mevcut değil.');
        
        // Hata sayfası yerine ana sayfaya (akışa) geri gönderelim
        return $this->redirectToRoute('app_micro_post');
    }

    // #[IsGranted] ile aynı işi yapar
    if (!$this->isGranted('POST_VIEW', $post)) { 
    // Not: 'POST_VIEW' yerine kendi yazdığın Voter izninin adını yazmalısın
    
    // 1. Kullanıcıya hata bildirimi (Flash Message) oluştur
    $this->addFlash('error', 'Bu paylaşım gizlidir. Sadece yazarın takipçileri görebilir.');

    // 2. Kullanıcıyı akış sayfasına (ana sayfaya) geri yönlendir
    return $this->redirectToRoute('app_micro_post');
    }
    
    // 1. Yeni bir Comment nesnesi oluştur ve formu buna bağla
    $comment = new Comment();
    $form = $this->createForm(CommentType::class, $comment);

    // 2. Gelen isteği (request) formun içine çek
    $form->handleRequest($request);
    
    // 3. Form gönderildi mi ve kurallara (Assert) uygun mu kontrol et
    if ($form->isSubmitted() && $form->isValid()) {
        $comment->setPost($post); // Yorumu bu post ile ilişkilendir
        $comment->setAuthor($this->getUser()); // Yorumu yazan kullanıcıyı ata

        $entityManager->persist($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Yorumunuz başarıyla eklendi!');

        // Başarılı işlemden sonra sayfayı yönlendir (Formun tekrar gönderilmesini önler)
        return $this->redirectToRoute('app_micro_post_show', ['id' => $id]);
    }
    
    return $this->render('micro_post/show.html.twig', [
        'post' => $post,
        'comment_form' => $form->createView(),
        'internship_report' => $request->getSession()->get('internship_report_'.$post->getId()),
    ],
        new Response(null, $form->isSubmitted() && !$form->isValid() ? 
        Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));

}

#[Route('/micro/post/{id}/internship-report', name: 'app_micro_post_internship_report', methods: ['POST'])]
#[IsGranted(MicroPost::EDIT, subject: 'post')]
public function internshipReport(
    MicroPost $post,
    Request $request,
    InternshipReportGenerator $reportGenerator
): Response {
    if (!$this->isCsrfTokenValid('internship_report'.$post->getId(), (string) $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Geçersiz güvenlik anahtarı.');
    }

    if ('internship' !== $post->getCategory()) {
        $this->addFlash('error', 'AI staj raporu yalnızca Staj Günlüğü paylaşımları için oluşturulabilir.');

        return $this->redirectToRoute('app_micro_post_show', ['id' => $post->getId()]);
    }

    try {
        $report = $reportGenerator->generate($post);
    } catch (\RuntimeException $exception) {
        $this->addFlash('error', $exception->getMessage());

        return $this->redirectToRoute('app_micro_post_show', ['id' => $post->getId()]);
    }

    $request->getSession()->set('internship_report_'.$post->getId(), $report);
    $this->addFlash('success', 'Staj raporu oluşturuldu. Çıktı butonun hemen altında görünüyor.');

    return $this->redirectToRoute('app_micro_post_show', [
        'id' => $post->getId(),
        '_fragment' => 'internship-report-output',
    ]);
}

#[Route('/micro/post/{id}/comment', name: 'app_comment_add', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function addComment(
    MicroPost $post, 
    Request $request, 
    EntityManagerInterface $entityManager
): Response {
    $comment = new Comment();
    $form = $this->createForm(CommentType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
    
    // 1. Kritik Adım: Yorumun yazarı olarak o anki kullanıcıyı ata
    $comment->setAuthor($this->getUser());
    
    // 2. Kritik Adım: Yorumun hangi posta ait olduğunu ata (eğer atanmadıysa)
    $comment->setPost($post);
    

    $entityManager->persist($comment);
    $entityManager->flush();

    $this->addFlash('success', 'Yorumun başarıyla eklendi!');
    
    // Postun detay sayfasına geri dön
    return $this->redirectToRoute('app_micro_post_show', ['id' => $post->getId()],  Response::HTTP_SEE_OTHER);
    
    }
    
// DÜZELTME: Form hatalıysa veya GET ile gelindiyse sayfayı tekrar render etmeli
    return $this->render('micro_post/comment.html.twig', [
        'form' => $form->createView(),
        'post' => $post
    ]);

}

#[Route('/micro/post/comment/{id}/edit', name: 'app_comment_edit')]
public function editComment(
    Comment $comment, 
    Request $request, 
    EntityManagerInterface $entityManager
): Response {
    $form = $this->createForm(CommentType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        $this->addFlash('success', 'Yorum güncellendi.');

        return $this->redirectToRoute('app_micro_post_show', ['id' => $comment->getPost()->getId()]);
    }

    return $this->render('micro_post/comment_edit.html.twig', [
        'form' => $form->createView(),
        'comment' => $comment
    ], new Response(
        null, 
        $form->isSubmitted() && !$form->isValid() ? 
            Response::HTTP_UNPROCESSABLE_ENTITY : 
            Response::HTTP_OK
    ));
}

#[Route('/micro/post/comment/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
public function deleteComment(Comment $comment, EntityManagerInterface $entityManager): Response
{
    $postId = $comment->getPost()->getId();
    $entityManager->remove($comment);
    $entityManager->flush();

    $this->addFlash('info', 'Yorum silindi.');
    return $this->redirectToRoute('app_micro_post_show', ['id' => $postId]);
}

#[Route('/test-user', name: 'app_test_user')]
public function createTestUser(EntityManagerInterface $em): Response
{
    $user = new User();
    $user->setEmail('test@example.com');
    $user->setPassword('123456'); // Şimdilik düz yazı, güvenlik sistemi yok
    // Eğer User entity'sinde başka zorunlu alanlar (username vb.) varsa onları da ekle:
    // $user->setUsername('testuser');

    $em->persist($user);
    $em->flush();

    return new Response("Kullanıcı oluşturuldu! ID: " . $user->getId());
}

}
