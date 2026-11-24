<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Controller\Api\ExportCLinksAction;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\Tests\ChamiloTestTrait;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A link-category title is plain text typed by a teacher. The PDF export must print it as text, never
 * hand it to the PDF renderer as markup: an image tag pointing to a server-local file would otherwise
 * make the renderer read that file and embed it in the PDF returned to the teacher.
 */
final class ExportCLinksActionTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const MARKER = 'LINK_CATEGORY_LOCAL_FILE_MARKER';

    private string $localFile;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::$twig = $container->get('twig');
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/');
        $request->setSession($session);
        Container::setRequest($request);

        $this->localFile = sys_get_temp_dir().'/'.uniqid('link_cat_', true).'.svg';
        file_put_contents(
            $this->localFile,
            '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="40"><text x="2" y="25">'.self::MARKER.'</text></svg>'
        );
    }

    protected function tearDown(): void
    {
        @unlink($this->localFile);

        parent::tearDown();
    }

    public function testCategoryTitleCannotEmbedALocalFile(): void
    {
        $pdf = $this->exportWithCategoryTitle('<img src="file://'.$this->localFile.'">');

        self::assertFalse($this->pdfContains($pdf, self::MARKER), 'The local file was embedded in the PDF.');
    }

    public function testPlainCategoryTitleIsPrinted(): void
    {
        $pdf = $this->exportWithCategoryTitle('Plain category title');

        self::assertStringStartsWith('%PDF', $pdf);
        self::assertTrue($this->pdfContains($pdf, 'Plain category title'));
    }

    private function exportWithCategoryTitle(string $title): string
    {
        $category = (new CLinkCategory())->setTitle($title);
        (new ReflectionProperty(CLinkCategory::class, 'iid'))->setValue($category, 1);

        $action = new ExportCLinksAction(self::getContainer()->get(TranslatorInterface::class));
        $method = new ReflectionMethod(ExportCLinksAction::class, 'generatePdfFile');
        $file = $method->invoke($action, $this->createCourse('link_export_pdf'), null, [], [$category], []);

        $content = (string) file_get_contents($file);
        @unlink($file);

        return $content;
    }

    private function pdfContains(string $pdf, string $needle): bool
    {
        if (str_contains($pdf, $needle)) {
            return true;
        }

        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
        foreach ($streams[1] as $stream) {
            $decoded = @gzuncompress($stream);
            if (false !== $decoded && str_contains($decoded, $needle)) {
                return true;
            }
        }

        return false;
    }
}
