<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Search;

use Chamilo\CoreBundle\Search\Xapian\SearchIndexPathResolver;
use Chamilo\CoreBundle\Search\Xapian\XapianIndexService;
use Chamilo\CoreBundle\Search\Xapian\XapianSearchService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use XapianDatabase;
use XapianWritableDatabase;

final class XapianSpecificFieldsTest extends TestCase
{
    public function testSpecificFieldMatchesPlainAndPrefixedQueries(): void
    {
        if (!class_exists(XapianWritableDatabase::class) || !class_exists(XapianDatabase::class)) {
            self::markTestSkipped('Xapian PHP bindings are not loaded.');
        }

        $directory = sys_get_temp_dir().'/chamilo-xapian-test-'.bin2hex(random_bytes(8));
        $resolver = new SearchIndexPathResolver($directory);
        $indexer = new XapianIndexService($resolver, $this->createMock(EntityManagerInterface::class));
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([['code' => 'k'], ['code' => 't']]);
        $search = new XapianSearchService($resolver, $connection);

        try {
            $id = $indexer->indexDocument(['title' => 'Ordinary document', 'content' => 'Example content'], [], 'en', ['K' => 'quartzkeyword']);
            $indexer->indexDocument(['title' => 'Unrelated document'], [], 'en', ['k' => 'otherkeyword']);

            foreach (['quartzkeyword', 'k:quartzkeyword'] as $query) {
                $results = $search->search($query, extra: ['language' => 'en']);
                self::assertSame(1, $results['count']);
                self::assertSame($id, $results['results'][0]['doc_id']);
            }
            self::assertSame(0, $search->search('t:quartzkeyword', extra: ['language' => 'en'])['count']);
        } finally {
            (new Filesystem())->remove($directory);
        }
    }
}
