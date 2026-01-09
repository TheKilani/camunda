<?php

declare(strict_types=1);

namespace App;

final class Http
{
    /** @return array<string,mixed> */
    public static function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            self::json(400, ['error' => 'Invalid JSON body.']);
            exit;
        }

        return $data;
    }

    /** @param array<string,mixed> $payload */
    public static function json(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function html(int $status, string $html): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    public static function text(int $status, string $text): void
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo $text;
    }

    public static function binary(int $status, string $mime, string $bytes): void
    {
        http_response_code($status);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
    }
}
