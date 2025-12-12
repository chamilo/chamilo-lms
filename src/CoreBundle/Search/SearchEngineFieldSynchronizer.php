<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search;

use Chamilo\CoreBundle\Entity\SearchEngineField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

use const JSON_ERROR_NONE;

final class SearchEngineFieldSynchronizer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * Applies JSON-defined search fields to the search_engine_field table.
     *
     * Non-destructive by default:
     * - Creates missing fields
     * - Updates titles
     * - Does NOT delete fields that disappeared from JSON
     *
     * @return array{created:int, updated:int, deleted:int}
     */
    public function syncFromJson(?string $json, bool $allowDeletes = false): array
    {
        $json = trim((string) $json);

        if ('' === $json) {
            return ['created' => 0, 'updated' => 0, 'deleted' => 0];
        }

        $desired = $this->parseJsonToCodeTitleMap($json); // code => title

        /** @var SearchEngineField[] $existing */
        $existing = $this->entityManager->getRepository(SearchEngineField::class)->findAll();

        $existingByCode = [];
        foreach ($existing as $field) {
            $existingByCode[$field->getCode()] = $field;
        }

        $created = 0;
        $updated = 0;
        $deleted = 0;

        foreach ($desired as $code => $title) {
            if (isset($existingByCode[$code])) {
                $field = $existingByCode[$code];

                if ($field->getTitle() !== $title) {
                    $field->setTitle($title);
                    $this->entityManager->persist($field);
                    $updated++;
                }
            } else {
                $field = (new SearchEngineField())
                    ->setCode($code)
                    ->setTitle($title)
                ;

                $this->entityManager->persist($field);
                $created++;
            }
        }

        if ($allowDeletes) {
            foreach ($existingByCode as $code => $field) {
                if (!isset($desired[$code])) {
                    $this->entityManager->remove($field);
                    $deleted++;
                }
            }
        }

        if ($created > 0 || $updated > 0 || $deleted > 0) {
            $this->entityManager->flush();
        }

        return ['created' => $created, 'updated' => $updated, 'deleted' => $deleted];
    }

    /**
     * Supported formats:
     *
     * 1) Canonical (recommended):
     *    {"fields":[{"code":"C","title":"Course"},{"code":"S","title":"Session"}], "options": {...}}
     *
     * Backward-compatible formats:
     * 2) {"course":{"prefix":"C","title":"Course"}}
     * 3) {"c":"Course"}
     * 4) [{"code":"c","title":"Course"}]
     *
     * @return array<string,string> code => title
     */
    private function parseJsonToCodeTitleMap(string $json): array
    {
        $decoded = json_decode($json, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new ValidatorException('Invalid JSON for search engine fields.');
        }

        if (!\is_array($decoded)) {
            throw new ValidatorException('Search engine fields JSON must be an object or an array.');
        }

        // Canonical wrapper: {"fields":[...], ...}
        if (isset($decoded['fields'])) {
            if (!\is_array($decoded['fields'])) {
                throw new ValidatorException('"fields" must be an array.');
            }

            return $this->parseListOfFields($decoded['fields']);
        }

        // List format: [{"code":"c","title":"Course"}]
        if ($this->isList($decoded)) {
            return $this->parseListOfFields($decoded);
        }

        // Object formats:
        // - {"c":"Course"}
        // - {"course":{"prefix":"C","title":"Course"}}
        $map = [];

        foreach ($decoded as $key => $value) {
            if (\is_string($value)) {
                $code = $this->normalizeCode((string) $key);
                $title = $this->normalizeTitle($value);
                $map[$code] = $title;

                continue;
            }

            if (\is_array($value) && isset($value['prefix'], $value['title'])) {
                $code = $this->normalizeCode((string) $value['prefix']);
                $title = $this->normalizeTitle($value['title']);
                $map[$code] = $title;

                continue;
            }

            throw new ValidatorException('Invalid search fields JSON structure.');
        }

        return $map;
    }

    /**
     * @param array<int, mixed> $rows
     *
     * @return array<string,string>
     */
    private function parseListOfFields(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            if (!\is_array($row)) {
                throw new ValidatorException('Each field entry must be an object with "code" and "title".');
            }

            // Accept "code" (canonical) and "prefix" (backward compatibility)
            $rawCode = $row['code'] ?? $row['prefix'] ?? '';
            $rawTitle = $row['title'] ?? null;

            $code = $this->normalizeCode((string) $rawCode);
            $title = $this->normalizeTitle($rawTitle);

            $map[$code] = $title;
        }

        return $map;
    }

    private function normalizeCode(string $code): string
    {
        $code = trim($code);

        if ('' === $code) {
            throw new ValidatorException('Field code cannot be empty.');
        }

        // Keep ONLY the first character.
        $code = mb_substr($code, 0, 1);

        // Keep DB consistent with current data (c/s/f/g...)
        return strtolower($code);
    }

    private function normalizeTitle(mixed $title): string
    {
        $title = trim((string) $title);

        if ('' === $title) {
            throw new ValidatorException('Field title cannot be empty.');
        }

        return $title;
    }

    private function isList(array $arr): bool
    {
        $i = 0;
        foreach ($arr as $k => $_) {
            if ($k !== $i) {
                return false;
            }
            $i++;
        }

        return true;
    }
}
