<?php

declare(strict_types=1);

namespace App;

final class App
{
    private Database $db;
    private PictureRepository $pictures;
    private AnimalImageClient $client;
    private string $basePath;

    public function __construct()
    {
        $this->basePath = self::detectBasePath();

        $dbPath = getenv('APP_DB_PATH');
        if ($dbPath === false || $dbPath === '') {
            $dbPath = dirname(__DIR__) . '/data/app.sqlite';
        }

        $this->db = new Database($dbPath);
        $this->db->migrate();

        $this->pictures = new PictureRepository($this->db->pdo());
        $this->client = new AnimalImageClient();
    }

    public function handle(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = self::stripBasePath($path, $this->basePath);

        // Common when hosted behind Apache without rewrites and users hit index.php explicitly.
        if ($path === '/index.php') {
            $path = '/';
        }

        // UI
        if ($method === 'GET' && $path === '/') {
            $this->renderHome();
            return;
        }

        // API
        if (str_starts_with($path, '/api/')) {
            $this->handleApi($method, $path);
            return;
        }

        Http::text(404, 'Not Found');
    }

    private function handleApi(string $method, string $path): void
    {
        if ($method === 'POST' && $path === '/api/pictures/fetch') {
            $this->apiFetchAndSave();
            return;
        }

        if ($method === 'POST' && $path === '/api/pictures/clear') {
            $this->apiClearPictures();
            return;
        }

        if ($method === 'GET' && $path === '/api/stats') {
            $this->apiStats();
            return;
        }

        if ($method === 'GET' && $path === '/api/pictures/last') {
            $animal = (string)($_GET['animal'] ?? '');
            $this->apiLastPicture($animal);
            return;
        }

        if ($method === 'GET' && preg_match('#^/api/pictures/(\d+)/image$#', $path, $m) === 1) {
            $id = (int)$m[1];
            $this->apiPictureImage($id);
            return;
        }

        Http::json(404, ['error' => 'Not Found']);
    }

    private function apiClearPictures(): void
    {
        $deleted = $this->pictures->deleteAll();
        Http::json(200, ['deleted' => $deleted]);
    }

    private function apiFetchAndSave(): void
    {
        $body = Http::readJsonBody();
        $animal = is_string($body['animal'] ?? null) ? strtolower($body['animal']) : '';
        $countRaw = $body['count'] ?? 1;

        if (!in_array($animal, ['cat', 'dog', 'bear'], true)) {
            Http::json(400, ['error' => 'Invalid animal. Expected one of: cat, dog, bear.']);
            return;
        }

        if (!is_int($countRaw) && !is_float($countRaw) && !is_string($countRaw)) {
            Http::json(400, ['error' => 'Invalid count.']);
            return;
        }

        $count = (int)$countRaw;
        if ($count < 1 || $count > 25) {
            Http::json(400, ['error' => 'Invalid count. Must be between 1 and 25.']);
            return;
        }

        $saved = [];
        for ($i = 0; $i < $count; $i++) {
            $img = $this->client->fetch($animal);
            $id = $this->pictures->insert($animal, $img->mime, $img->bytes, $img->sourceUrl, $img->fetchedAtIso);
            $saved[] = [
                'id' => $id,
                'animal' => $animal,
                'imageUrl' => $this->url('/api/pictures/' . $id . '/image'),
            ];
        }

        Http::json(200, [
            'animal' => $animal,
            'count' => $count,
            'saved' => $saved,
        ]);
    }

    private function apiLastPicture(string $animal): void
    {
        $animal = strtolower($animal);
        if (!in_array($animal, ['cat', 'dog', 'bear'], true)) {
            Http::json(400, ['error' => 'Invalid animal. Expected one of: cat, dog, bear.']);
            return;
        }

        $pic = $this->pictures->findLastByAnimal($animal);
        if ($pic === null) {
            Http::json(404, ['error' => 'No pictures stored for this animal yet.']);
            return;
        }

        Http::json(200, [
            'id' => $pic['id'],
            'animal' => $pic['animal'],
            'created_at' => $pic['created_at'],
            'source_url' => $pic['source_url'],
            'imageUrl' => $this->url('/api/pictures/' . $pic['id'] . '/image'),
        ]);
    }

    private function apiPictureImage(int $id): void
    {
        $pic = $this->pictures->findById($id);
        if ($pic === null) {
            Http::text(404, 'Not Found');
            return;
        }

        Http::binary(200, $pic['mime'], $pic['data']);
    }

    private function apiStats(): void
    {
        $stats = $this->pictures->countByAnimal();
        Http::json(200, $stats);
    }

    private function renderHome(): void
    {
        $html = Ui::homePageHtml($this->basePath);
        Http::html(200, $html);
    }

    private function url(string $path): string
    {
        if ($this->basePath === '') {
            return $path;
        }
        return $this->basePath . $path;
    }

    private static function detectBasePath(): string
    {
        $env = getenv('APP_BASE_PATH');
        if (is_string($env) && $env !== '') {
            $bp = '/' . trim($env, '/');
            return $bp === '/' ? '' : $bp;
        }

        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($dir === '' || $dir === '.') {
            return '';
        }
        return $dir === '/' ? '' : $dir;
    }

    private static function stripBasePath(string $path, string $basePath): string
    {
        if ($basePath === '') {
            return $path;
        }
        if ($path === $basePath) {
            return '/';
        }
        if (str_starts_with($path, $basePath . '/')) {
            $stripped = substr($path, strlen($basePath));
            return $stripped === '' ? '/' : $stripped;
        }
        return $path;
    }
}
