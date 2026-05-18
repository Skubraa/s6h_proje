<?php

namespace App\Service;

use App\Entity\MicroPost;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InternshipReportGenerator
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(GEMINI_API_KEY)%')]
        private string $apiKey,
        #[Autowire('%env(GEMINI_MODEL)%')]
        private string $model,
    ) {
    }

    public function generate(MicroPost $post): string
    {
        if ('' === trim($this->apiKey)) {
            throw new \RuntimeException('Gemini API anahtarı tanımlı değil. Lütfen .env dosyasındaki GEMINI_API_KEY satırına yeni API anahtarınızı ekleyin.');
        }

        $date = $post->getCreated()?->format('d.m.Y') ?? date('d.m.Y');
        $prompt = sprintf(
            "Tarih: %s\nBaşlık: %s\nÖğrencinin notu: %s",
            $date,
            $post->getTitle(),
            $post->getText()
        );

        $response = $this->httpClient->request('POST', sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            $this->model ?: 'gemini-2.5-flash'
        ), [
            'headers' => [
                'x-goog-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'systemInstruction' => [
                    'parts' => [
                        [
                            'text' => 'Sen profesyonel bir staj defteri asistanısın. Görevin, verilen kısa notları, staj yapılan alana uygun, resmi bir dille özetlenmiş, KISA VE ÖZ bir staj raporuna dönüştürmektir.

KESİN KURALLAR:
1. BAŞLIK YASAĞI: Çıktıya KESİNLİKLE "Merhaba" veya "İşte raporunuz" gibi ifadelerle başlama. Ayrıca "# Günlük Staj Raporu" veya "## Yapılan Çalışmalar" gibi HİÇBİR Markdown başlığı (#, ##, ###) KULLANMA. Çıktı tamamen başlıksız, düz paragraflar halinde olmalıdır.
2. DİL: Anlatımı daima üçüncü tekil şahıs ve edilgen çatıda yaz ("öğrenildi", "incelenmiştir", "gerçekleştirildi" kullan).
3. BİÇİMLENDİRME YASAĞI: Çıktıda KESİNLİKLE üçlü (```) veya tekli (`) ters tırnak kullanma. Hiçbir metni veya komutu kod bloğu formatına sokma. 
4. SINIRLANDIRMA: Raporu bir kullanım kılavuzuna çevirme. Uzun ansiklopedik tanımlardan kaçın. Raporu en fazla 150-200 kelime ile sınırlandır.
5. ZENGİNLEŞTİRME VE LİSTELEME: Temel konuyu analiz et. Öğrenilen teknik detayları (örneğin Route, Controller kavramları ve alt parametreleri) açıklarken sadece tek bir madde imi listesi (*) kullan. Liste dışında gereksiz listeleme yapma.

RAPOR YAPISI (BAŞLIKSIZ):
Metni tam olarak şu sırayla ve formatla oluştur:
- 1. Paragraf: İşlenen ana konuların ve yapılan çalışmaların akademik, edilgen çatılı ve kısa bir özeti.
- Madde İmleri (*): Öğrenilen teknik kavramların, yöntemlerin veya işlemlerin madde imleriyle alt alta özetlenmesi.
- Son Paragraf: Öğrenilenlerin mesleki gelişime, sistem mimarisini kavramaya ve ileriki proje süreçlerine doğrudan katkısını belirten objektif bir gün sonu değerlendirmesi.'
                        ]
                    ],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $prompt,
                            ]
                        ],
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 3000,
                    'temperature' => 0.4,
                ],
            ],
        ]);

        $data = $response->toArray(false);

        if (isset($data['error'])) {
            $message = $data['error']['message'] ?? 'Gemini isteği başarısız oldu.';
            throw new \RuntimeException($message);
        }

        $text = $this->extractOutputText($data);
        $text = trim((string) $text);

        if ('' === $text || mb_strlen($text) < 120) {
            throw new \RuntimeException('AI raporu yeterli içerikle oluşturulamadı. Lütfen post açıklamasına öğrendiğiniz kavramları biraz daha açık yazıp tekrar deneyin.');
        }

        return $text;
    }

    private function extractOutputText(array $data): string
    {
        $parts = [];

        foreach ($data['candidates'] ?? [] as $candidate) {
            foreach ($candidate['content']['parts'] ?? [] as $part) {
                if (isset($part['text'])) {
                    $parts[] = $part['text'];
                }
            }
        }

        return implode("\n", $parts);
    }
}
