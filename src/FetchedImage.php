<?php

declare(strict_types=1);

namespace App;

final class FetchedImage
{
    public function __construct(
        public readonly string $bytes,
        public readonly string $mime,
        public readonly string $sourceUrl,
        public readonly string $fetchedAtIso,
    ) {
    }
}
