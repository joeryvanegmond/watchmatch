<?php

namespace App\Services;

use App\Models\BlacklistedDomain;
use App\Models\Watch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use ImageKit\ImageKit;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

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

            // Map van MIME types naar extensies
            $mimeToExt = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                // evt. uitbreiden
            ];

            // Zorg dat de temp folder bestaat
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Als geen filename is gegeven, probeer een extensie te bepalen via URL
            // Let op: copy() downloadt de file, maar geeft geen headers terug,
            // dus content-type weten we niet direct. We kunnen dat achteraf ophalen met mime_content_type.
            if (!$fileName) {
                // probeer extensie uit url te halen (fallback)
                $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
                $ext = $pathInfo['extension'] ?? 'jpg';
                $fileName = Str::random(20) . '.' . $ext;
            } else {
                if (!str_contains($fileName, '.')) {
                    $fileName .= '.jpg'; // fallback extensie
                }
            }

            $tempPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

            // Download bestand lokaal
            if (!copy($url, $tempPath)) {
                throw new \Exception("Kon afbeelding niet downloaden via copy() van $url");
            }

            // === Intervention Image Manager aanmaken ===
            $manager = ImageManager::gd();

            // Afbeelding laden
            $img = $manager->read($tempPath);

            $percentage = 25;
            $width = $img->width();
            $height = $img->height();
            $newWidth = intval($width * ($percentage / 100));
            $newHeight = intval($height * ($percentage / 100));
            
            if ($width >= 2000 || $height >= 2000) {
                $img->resize($newWidth, $newHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Opslaan met compressie (kwaliteit 75%)
            $img->save($tempPath, 75);

            // Probeer mime-type lokaal te bepalen en eventueel extensie corrigeren
            $detectedMime = mime_content_type($tempPath);
            if (isset($mimeToExt[$detectedMime])) {
                $correctExt = $mimeToExt[$detectedMime];
                // Check of file extension klopt, anders rename bestand
                if (pathinfo($tempPath, PATHINFO_EXTENSION) !== $correctExt) {
                    $newTempPath = $tempDir . DIRECTORY_SEPARATOR . pathinfo($fileName, PATHINFO_FILENAME) . '.' . $correctExt;
                    rename($tempPath, $newTempPath);
                    $tempPath = $newTempPath;
                    $fileName = basename($newTempPath);
                }
            }

            $upload = $this->imageKit->upload([
                'file' => fopen($tempPath, 'r'),
                'fileName' => "watchmatch_{$fileName}",
                'folder' => config('services.imagekit.folder'),
                'useUniqueFileName' => false,
                'overwriteFile' => true,
            ]);

            // Verwijder tijdelijk bestand
            unlink($tempPath);

            return $upload->result->url ?? null;
        } catch (\Throwable $e) {
            logger()->error("ImageKit upload error: " . $e->getMessage());
            $this->addDomainToBlacklist($url);
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

    function addDomainToBlacklist(string $imageUrl)
    {
        $host = parse_url($imageUrl, PHP_URL_HOST);
        if ($host && !BlacklistedDomain::where('domain', $host)->exists()) {
            BlacklistedDomain::create(['domain' => $host]);
        }
    }
}
