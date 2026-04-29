<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HelloController extends AbstractController
{   
    private array $mesajlar = [
        1 => 'Hello',
        2 => 'Hi',
        3 => 'Merhaba',
        4 => 'Selam (Gizli)'
    ];

    #[Route('/hello', name: 'app_hello')]
public function index(): Response
{
    
    return $this->render('hello/index.html.twig', [
        'user_name' => 'Sümeyye',
        'mesaj_listesi' => array_slice($this->mesajlar, 0, 3, true),
    ]);
}
    #[Route('/hello/{id}', name: 'app_hello_show', requirements: ['id' => '\d+'])]
public function show(int $id): Response
    {
    // 1. Elimizde 4 adet mesaj olduğunu varsayalım
    $mesajlar = [
        1 => 'Hello',
        2 => 'Hi',
        3 => 'Merhaba',
        4 => 'Selam (Gizli)'
    ];

    // 2. array_slice ile sadece ilk 3 mesajı alıyoruz. 
    // Parametreler: (dizi, başlangıç_indeksi, adet, anahtarları_koru)
    $izinVerilenMesajlar = array_slice($mesajlar, 0, 3, true);

    // 3. Hata Kontrolü: Eğer ID bu sınırlı listede yoksa 404 fırlat
    if (!isset($izinVerilenMesajlar[$id])) {
        throw $this->createNotFoundException('Bu ID numarasına sahip bir mesaj bulunamadı veya erişiminiz kısıtlı!');
    }

    return $this->render('hello/show.html.twig', [
        'mesaj' => $izinVerilenMesajlar[$id],
        'id' => $id
    ]);
    }
}
