<?php

declare(strict_types=1);

namespace App;

final class AnimalImageClient
{
    public function fetch(string $animal): FetchedImage
    {
        $url = match ($animal) {
            'cat' => 'https://cataas.com/cat',
            'dog' => 'https://place.dog/300/200',
            'bear' => 'https://placebear.com/200/300',
            default => throw new \InvalidArgumentException('Unsupported animal: ' . $animal),
        };

        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('Failed to init cURL');
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'php-animal-picture-app/1.0',
            CURLOPT_HEADER => false,
        ]);

        $bytes = curl_exec($ch);
        if ($bytes === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Failed to fetch image: ' . $err);
        }

        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Upstream returned HTTP ' . $status);
        }

        $mime = $this->detectMime((string)$bytes);

        return new FetchedImage(
            bytes: (string)$bytes,
            mime: $mime,
            sourceUrl: $url,
            fetchedAtIso: gmdate('c')
        );
    }

    private function detectMime(string $bytes): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($bytes);
        if (!is_string($mime) || $mime === '') {
            return 'application/octet-stream';
        }

        return $mime;
    }
}
