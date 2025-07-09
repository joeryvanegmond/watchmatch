<?php

namespace App\Services;

use App\Models\Watch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ImageKit\ImageKit;
use Illuminate\Support\Str;

class ImageKitService
{
    protected ImageKit $imageKit;

    public function __construct()
    {
        $this->imageKit = new ImageKit(
            publicKey: config('services.imagekit.public_key'),
            privateKey: config('services.imagekit.private_key'),
            urlEndpoint: config('services.imagekit.url_endpoint')
        );
    }

    public function uploadFromUrl(Watch $watch, ?string $fileName = null): ?string
    {
        try {
            $url = $this->cleanUrl($watch->image_url);
            $response = Http::withHeaders($this->getRandomHeaders($watch->image_url))->timeout(30)->get($url);
            if (!$response->ok()) {
                throw new \Exception("Statuscode: {$response->status()} Kan afbeelding niet downloaden: " . $url . ' ' . $response->headers());
            }

            // Map van MIME types naar extensies
            $mimeToExt = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                // voeg hier eventueel meer toe
            ];

            // Pak content-type header
            $contentType = $response->header('Content-Type');
            $ext = $mimeToExt[$contentType] ?? 'jpg'; // fallback naar jpg

            // Bouw bestandsnaam als die niet meegegeven is
            if (!$fileName) {
                $fileName = Str::random(20) . '.' . $ext;
            } else {
                // Check of fileName al extensie heeft, zo niet: toevoegen
                if (!str_contains($fileName, '.')) {
                    $fileName .= '.' . $ext;
                }
            }

            $tempPath = storage_path("app/temp/{$fileName}");

            // Zorg dat de folder bestaat
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            file_put_contents($tempPath, $response->body());

            $upload = $this->imageKit->upload([
                'file' => fopen($tempPath, 'r'),
                'fileName' => "watchmatch_{$fileName}",
                'folder' => 'watches'
            ]);

            // Verwijder tijdelijk bestand
            unlink($tempPath);

            return $upload->result->url ?? null;
        } catch (\Throwable $e) {
            logger()->error("ImageKit upload error: " . $e->getMessage());
            $watch->image_url = null;
            $watch->save();
            return null;
        }
    }


    private function cleanUrl(string $url): string
    {
        $parts = parse_url($url);
        return $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
    }

    private function getRandomHeaders($url): array
    {
        $userAgents = [
            // Chrome op Windows
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
            // Firefox op Windows
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
            // Safari op macOS
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.1 Safari/605.1.15',
            // Chrome op Android
            'Mozilla/5.0 (Linux; Android 11; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Mobile Safari/537.36',
            // Safari op iPhone
            'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1'
        ];

        $host = parse_url($url, PHP_URL_HOST);

        return [
            'Cache-Control' => 'no-cache',
            // 'User-Agent' => $userAgents[array_rand($userAgents)],
            'User-Agent' => 'PostmanRuntime/7.36.0',
            'Referer' => 'https://' . $host . '/',
            'Accept' => '*/*',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive'
        ];
    }
}
