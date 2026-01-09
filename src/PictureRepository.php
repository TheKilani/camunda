<?php

declare(strict_types=1);

namespace App;

use PDO;

final class PictureRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function deleteAll(): int
    {
        $countStmt = $this->pdo->query('SELECT COUNT(*) AS c FROM pictures');
        $row = $countStmt === false ? null : $countStmt->fetch();
        $count = is_array($row) ? (int)($row['c'] ?? 0) : 0;

        $this->pdo->exec('DELETE FROM pictures');

        return $count;
    }

    public function insert(string $animal, string $mime, string $bytes, string $sourceUrl, string $createdAtIso): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pictures (animal, mime, data, source_url, created_at)
             VALUES (:animal, :mime, :data, :source_url, :created_at)'
        );

        $stmt->bindValue(':animal', $animal, PDO::PARAM_STR);
        $stmt->bindValue(':mime', $mime, PDO::PARAM_STR);
        $stmt->bindValue(':data', $bytes, PDO::PARAM_LOB);
        $stmt->bindValue(':source_url', $sourceUrl, PDO::PARAM_STR);
        $stmt->bindValue(':created_at', $createdAtIso, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array{id:int,animal:string,mime:string,data:string,source_url:string,created_at:string}|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, animal, mime, data, source_url, created_at FROM pictures WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    /** @return array{id:int,animal:string,mime:string,source_url:string,created_at:string}|null */
    public function findLastByAnimal(string $animal): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, animal, mime, source_url, created_at
             FROM pictures
             WHERE animal = :animal
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->bindValue(':animal', $animal, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    /** @return array{cat:int,dog:int,bear:int,total:int} */
    public function countByAnimal(): array
    {
        $animals = ['cat', 'dog', 'bear'];
        $counts = ['cat' => 0, 'dog' => 0, 'bear' => 0, 'total' => 0];

        foreach ($animals as $animal) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) AS c FROM pictures WHERE animal = :animal');
            $stmt->bindValue(':animal', $animal, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch();
            $counts[$animal] = (int)($row['c'] ?? 0);
            $counts['total'] += $counts[$animal];
        }

        return $counts;
    }
}
