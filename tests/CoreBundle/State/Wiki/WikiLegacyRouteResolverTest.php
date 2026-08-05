<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Wiki;

use Chamilo\CoreBundle\State\Wiki\WikiLegacyRouteResolver;
use PHPUnit\Framework\TestCase;

final class WikiLegacyRouteResolverTest extends TestCase
{
    private WikiLegacyRouteResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new WikiLegacyRouteResolver();
    }

    public function testShowPageKeepsCourseSessionGroupAndStudentView(): void
    {
        self::assertSame(
            '/resources/wiki/734/?title=test_page&cid=16&sid=4&gid=7&isStudentView=true',
            $this->resolver->resolve(734, 16, 4, 7, 'showpage', 'test_page', 12, null, [
                'isStudentView' => 'true',
            ]),
        );
    }

    public function testHistoricalViewRedirectsToHistoryPreview(): void
    {
        self::assertSame(
            '/resources/wiki/734/history/12?cid=16&versionIid=91',
            $this->resolver->resolve(734, 16, 0, 0, 'showpage', 'test_page', 12, 91),
        );
    }

    public function testHistoryComparisonPreservesSelectedVersions(): void
    {
        self::assertSame(
            '/resources/wiki/734/history/12?cid=16&oldIid=80&newIid=91&mode=word',
            $this->resolver->resolve(734, 16, 0, 0, 'history', 'test_page', 12, null, [
                'old' => '80',
                'new' => '91',
                'HistoryDifferences2' => 'HistoryDifferences2',
            ]),
        );
    }

    public function testSearchParametersAreMappedToModernReport(): void
    {
        self::assertSame(
            '/resources/wiki/734/reports?report=search&search=policy&searchContent=1&allVersions=1&categoryIds=3%2C5&matchAllCategories=1&cid=16',
            $this->resolver->resolve(734, 16, 0, 0, 'searchpages', 'index', null, null, [
                'search_term' => 'policy',
                'search_content' => '1',
                'all_vers' => '1',
                'categories' => ['3', '5', '3'],
                'match_all_categories' => 'on',
            ]),
        );
    }

    public function testLegacyStatisticsAndBacklinksMapToReports(): void
    {
        self::assertSame(
            '/resources/wiki/734/reports?report=statistics&cid=16&sid=9',
            $this->resolver->resolve(734, 16, 9, 0, 'statistics', 'index', null, null),
        );
        self::assertSame(
            '/resources/wiki/734/reports?report=backlinks&target=policy&cid=16',
            $this->resolver->resolve(734, 16, 0, 0, 'links', 'policy', 8, null),
        );
    }

    public function testPageScopedRoutesUseResolvedLogicalPageId(): void
    {
        self::assertSame(
            '/resources/wiki/734/edit/12?cid=16',
            $this->resolver->resolve(734, 16, 0, 0, 'edit', 'test_page', 12, null),
        );
        self::assertSame(
            '/resources/wiki/734/discussion/12?cid=16',
            $this->resolver->resolve(734, 16, 0, 0, 'discuss', 'test_page', 12, null),
        );
    }

    public function testPdfBookmarkMapsToBinaryEndpoint(): void
    {
        self::assertSame(
            '/api/wiki/page/12/export.pdf?node=734&cid=16&sid=4&gid=7',
            $this->resolver->resolve(734, 16, 4, 7, 'export_to_pdf', 'test_page', 12, 91),
        );
    }

    public function testLegacyWriteActionsDoNotExecuteAutomatically(): void
    {
        self::assertSame(
            '/resources/wiki/734/?title=test_page&cid=16',
            $this->resolver->resolve(734, 16, 0, 0, 'delete', 'test_page', 12, null),
        );
        self::assertSame(
            '/resources/wiki/734/reports?report=all&cid=16',
            $this->resolver->resolve(734, 16, 0, 0, 'deletewiki', 'index', null, null),
        );
        self::assertSame(
            '/resources/wiki/734/?title=test_page&cid=16',
            $this->resolver->resolve(734, 16, 0, 0, 'export2doc', 'test_page', 12, null),
        );
    }
}
