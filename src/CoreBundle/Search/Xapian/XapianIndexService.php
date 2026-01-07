<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CoreBundle\Entity\SearchEngineRef;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Throwable;
use Xapian;
use XapianDocument;
use XapianStem;
use XapianTermGenerator;
use XapianWritableDatabase;

use const DATE_ATOM;

/**
 * Service responsible for indexing documents into the Xapian database.
 */
final class XapianIndexService
{
    private const DEFAULT_LANGUAGE = 'english';

    public function __construct(
        private readonly SearchIndexPathResolver $indexPathResolver,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Indexes a simple demo document so we can verify that search works end-to-end.
     *
     * @return int The Xapian internal document id
     *
     * @throws RuntimeException When indexing fails
     */
    public function indexDemoDocument(): int
    {
        $now = new DateTimeImmutable('now');

        $fields = [
            'title' => 'Demo test document',
            'content' => 'This is a test document indexed from XapianIndexService in Chamilo 2.',
            'created_at' => $now->format(DATE_ATOM),
        ];

        $terms = [
            'XTdemo',
            'XTchamilo',
        ];

        return $this->indexDocument($fields, $terms);
    }

    /**
     * Indexes a generic document.
     *
     * @param array<string,mixed> $fields            Arbitrary data to store and index as free-text
     * @param string[]            $terms             Optional list of additional terms to add to the document
     * @param string|null         $language          Language used for stemming (defaults to english)
     * @param array<string,mixed> $fieldValuesByCode Fielded values, e.g. ['k' => '...', 't' => '...']
     *
     * @return int The Xapian internal document id
     *
     * @throws RuntimeException When Xapian fails during indexing
     */
    public function indexDocument(
        array $fields,
        array $terms = [],
        ?string $language = null,
        array $fieldValuesByCode = [] // e.g. ['t'=>'...', 'd'=>'...', 'k'=>'...']
    ): int {
        if (!class_exists(XapianWritableDatabase::class)) {
            throw new RuntimeException('Xapian PHP extension is not loaded.');
        }

        $db = $this->openWritableDatabase();

        $doc = new XapianDocument();
        $termGen = new XapianTermGenerator();

        // normalize ISO/code into a Xapian stemmer language string
        $xapianLanguage = $this->mapLanguageToXapianStemmer($language);

        try {
            $stemmer = new XapianStem($xapianLanguage);
        } catch (Throwable $e) {
            error_log(
                '[Xapian] indexDocument: failed to init stemmer for lang='
                .var_export($xapianLanguage, true)
                .', fallback=english, error='.$e->getMessage()
            );

            $stemmer = new XapianStem(self::DEFAULT_LANGUAGE);
        }

        $termGen->set_stemmer($stemmer);
        $termGen->set_document($doc);

        // Unprefixed free-text (general search)
        foreach ($fields as $value) {
            if (null === $value) {
                continue;
            }
            $value = \is_string($value) ? $value : (string) $value;
            $value = trim($value);
            if ('' === $value) {
                continue;
            }
            $termGen->index_text($value, 1);
        }

        // Prefixed dynamic fields: t:, d:, k:, etc.
        if (!empty($fieldValuesByCode)) {
            error_log('[Xapian] indexDocument: fieldValuesByCode='.json_encode(array_keys($fieldValuesByCode)));

            foreach ($fieldValuesByCode as $code => $val) {
                $code = strtolower(trim((string) $code));
                if ('' === $code) {
                    continue;
                }

                $val = \is_string($val) ? $val : (string) $val;
                $val = trim($val);
                if ('' === $val) {
                    continue;
                }

                // Must match query parser convention: F + strtoupper(code)
                $prefix = 'F'.strtoupper($code);

                // This is what makes t: / d: / k: work
                $termGen->index_text($val, 1, $prefix);
            }

            // Optional: keep it in stored data for debugging
            $fields['searchFieldValues'] = $fieldValuesByCode;
        }

        // Extra terms (Tdocument, Cxx, Sxx...)
        foreach ($terms as $term) {
            $term = (string) $term;
            if ('' === $term) {
                continue;
            }
            $doc->add_term($term, 1);
        }

        $doc->set_data(serialize($fields));

        try {
            $docId = $db->add_document($doc);
            $db->flush();

            error_log('[Xapian] indexDocument: added docId='.$docId);

            return $docId;
        } catch (Throwable $e) {
            throw new RuntimeException(\sprintf('Failed to index document in Xapian: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * Deletes a document from the Xapian index using its internal document id.
     *
     * @throws RuntimeException When Xapian fails during deletion
     */
    public function deleteDocument(int $docId): void
    {
        if (!class_exists(XapianWritableDatabase::class)) {
            throw new RuntimeException('Xapian PHP extension is not loaded.');
        }

        $db = $this->openWritableDatabase();

        try {
            error_log(
                '[Xapian] XapianIndexService::deleteDocument: deleting docId='
                .var_export($docId, true)
            );

            $db->delete_document($docId);
            $db->flush();
        } catch (Throwable $e) {
            throw new RuntimeException(\sprintf('Failed to delete document in Xapian: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * Opens the writable Xapian database using DB_CREATE_OR_OPEN.
     */
    private function openWritableDatabase(): XapianWritableDatabase
    {
        $indexDir = $this->indexPathResolver->getIndexDir();

        return new XapianWritableDatabase($indexDir, Xapian::DB_CREATE_OR_OPEN);
    }

    private function mapLanguageToXapianStemmer(?string $language): string
    {
        if (null === $language) {
            return self::DEFAULT_LANGUAGE;
        }

        $raw = strtolower(trim($language));
        if ('' === $raw) {
            return self::DEFAULT_LANGUAGE;
        }

        // If caller already provides a Xapian language name, accept it
        $known = [
            'english', 'spanish', 'french', 'portuguese', 'italian', 'german', 'dutch',
            'swedish', 'norwegian', 'danish', 'finnish', 'russian', 'arabic', 'greek',
            'turkish', 'romanian', 'hungarian', 'indonesian',
        ];

        if (\in_array($raw, $known, true)) {
            return $raw;
        }

        // Normalize ISO variants: es_ES, pt-BR, en_US -> es, pt, en
        $iso = $raw;
        if (str_contains($iso, '_')) {
            $iso = explode('_', $iso, 2)[0];
        }
        if (str_contains($iso, '-')) {
            $iso = explode('-', $iso, 2)[0];
        }
        $iso = strtolower(trim($iso));

        $map = [
            'en' => 'english',
            'es' => 'spanish',
            'fr' => 'french',
            'pt' => 'portuguese',
            'it' => 'italian',
            'de' => 'german',
            'nl' => 'dutch',
            'sv' => 'swedish',
            'no' => 'norwegian',
            'da' => 'danish',
            'fi' => 'finnish',
            'ru' => 'russian',
            'ar' => 'arabic',
            'el' => 'greek',
            'tr' => 'turkish',
            'ro' => 'romanian',
            'hu' => 'hungarian',
            'id' => 'indonesian',
        ];

        return $map[$iso] ?? self::DEFAULT_LANGUAGE;
    }

    public function purgeCourseIndex(int $courseId): void
    {
        // Get all Xapian document ids (search_did) linked to this course
        $rows = $this->em->createQueryBuilder()
            ->select('DISTINCT ser.searchDid AS searchDid')
            ->from(SearchEngineRef::class, 'ser')
            ->join('ser.resourceNode', 'rn')
            ->join('rn.resourceLinks', 'rl')
            ->join('rl.course', 'c')
            ->where('c.id = :courseId')
            ->setParameter('courseId', $courseId)
            ->getQuery()
            ->getScalarResult()
        ;

        foreach ($rows as $row) {
            $did = (int) ($row['searchDid'] ?? 0);
            if ($did <= 0) {
                continue;
            }

            // Delete the Xapian entry by its search_did (Xapian docid)
            $this->deleteBySearchDid($did);
        }
    }

    private function deleteBySearchDid(int $did): void
    {
        if ($did <= 0) {
            return;
        }

        try {
            // search_did == Xapian internal docid
            $this->deleteDocument($did);
        } catch (Throwable $e) {
            error_log(
                '[Xapian] deleteBySearchDid: delete failed for search_did='.$did.': '.
                $e->getMessage().' in '.$e->getFile().':'.$e->getLine()
            );
        }
    }
}
