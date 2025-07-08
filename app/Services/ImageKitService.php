<?php

namespace App\Services;

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

    public function uploadFromUrl(string $url, ?string $fileName = null): ?string
    {
        try {
            $response = Http::get($this->cleanUrl($url));
            if (!$response->ok()) {
                throw new \Exception('Kan afbeelding niet downloaden: ' . $url);
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
            return null;
        }
    }


    function cleanUrl(string $url): string
    {
        $parts = parse_url($url);
        return $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
    }
}
