<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Controller\Api\BaseResourceFileAction;
use Chamilo\CoreBundle\Dto\ResourceFileInput;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use const JSON_THROW_ON_ERROR;

/**
 * BaseResourceFileAction::handleCreateFile() takes a ResourceFileInput now, and
 * the API actions get theirs from resourceFileInputFromRequest(). That mapper is
 * the only thing standing between the HTTP callers and a behaviour change, so it
 * is pinned here: every quirk of the reading it replaced is a case below.
 */
final class ResourceFileInputFromRequestTest extends TestCase
{
    private object $action;

    protected function setUp(): void
    {
        $this->action = new class extends BaseResourceFileAction {
            /**
             * @param ?array<int, mixed> $override
             */
            public function expose(Request $request, ?array $override = null): ResourceFileInput
            {
                return $this->resourceFileInputFromRequest($request, $override);
            }
        };
    }

    public function testReadsAJsonBody(): void
    {
        $request = Request::create('/api/documents', 'POST', [], [], [], [], json_encode([
            'title' => 'From JSON',
            'comment' => 'a comment',
            'filetype' => 'file',
            'parentResourceNodeId' => 42,
            'resourceLinkList' => [['cid' => 7, 'visibility' => 2]],
        ], JSON_THROW_ON_ERROR));

        $input = $this->action->expose($request);

        self::assertSame('From JSON', $input->title);
        self::assertSame('a comment', $input->comment);
        self::assertSame('file', $input->filetype);
        self::assertSame(42, $input->parentResourceNodeId);
        self::assertSame([['cid' => 7, 'visibility' => 2]], $input->resourceLinkList);
    }

    public function testReadsPostFields(): void
    {
        $request = Request::create('/api/documents', 'POST', [
            'title' => 'From fields',
            'comment' => 'another comment',
            'filetype' => 'folder',
            'parentResourceNodeId' => '42',
            'resourceLinkList' => '[{"cid":7,"visibility":2}]',
        ]);

        $input = $this->action->expose($request);

        self::assertSame('From fields', $input->title);
        self::assertSame('folder', $input->filetype);
        self::assertSame(42, $input->parentResourceNodeId);
        self::assertSame([['cid' => 7, 'visibility' => 2]], $input->resourceLinkList);
    }

    /**
     * A single link may arrive without its enclosing brackets.
     */
    public function testAcceptsALinkListWithoutBrackets(): void
    {
        $request = Request::create('/api/documents', 'POST', [
            'filetype' => 'file',
            'parentResourceNodeId' => '42',
            'resourceLinkList' => '{"cid":7,"visibility":2}',
        ]);

        self::assertSame(
            [['cid' => 7, 'visibility' => 2]],
            $this->action->expose($request)->resourceLinkList
        );
    }

    public function testRejectsALinkListThatIsNotJson(): void
    {
        $request = Request::create('/api/documents', 'POST', [
            'filetype' => 'file',
            'parentResourceNodeId' => '42',
            'resourceLinkList' => 'not json at all',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->action->expose($request);
    }

    /**
     * The controller forces the link context from the gated course, so whatever
     * the body sent is discarded. This is the IDOR fix of #8486.
     */
    public function testTheOverrideReplacesTheBodyLinkList(): void
    {
        $request = Request::create('/api/documents', 'POST', [
            'filetype' => 'file',
            'parentResourceNodeId' => '42',
            'resourceLinkList' => '[{"cid":999,"visibility":2}]',
        ]);

        self::assertSame(
            [['cid' => 7, 'visibility' => 2]],
            $this->action->expose($request, [['cid' => 7, 'visibility' => 2]])->resourceLinkList
        );
    }

    /**
     * An HTML editor save sends contentFile even when the document is empty, and
     * that is a real create -- so "absent" and "empty" cannot be the same value.
     */
    public function testAnEmptyContentFileIsNotTheSameAsNoContentFile(): void
    {
        $absent = Request::create('/api/documents', 'POST', [
            'filetype' => 'file',
            'parentResourceNodeId' => '42',
        ]);
        self::assertNull($this->action->expose($absent)->contentFile);

        $empty = Request::create('/api/documents', 'POST', [
            'filetype' => 'file',
            'parentResourceNodeId' => '42',
            'contentFile' => '',
        ]);
        self::assertSame('', $this->action->expose($empty)->contentFile);
    }

    /**
     * Same distinction for the language: absent leaves the resource alone, empty
     * means "the course's".
     */
    public function testAnEmptyLanguageIsNotTheSameAsNoLanguage(): void
    {
        $absent = Request::create('/api/documents', 'POST', [
            'filetype' => 'file',
            'parentResourceNodeId' => '42',
        ]);
        self::assertNull($this->action->expose($absent)->language);

        $empty = Request::create('/api/documents', 'POST', [
            'filetype' => 'file',
            'parentResourceNodeId' => '42',
            'language' => '',
        ]);
        self::assertSame('', $this->action->expose($empty)->language);
    }

    public function testContentFileTypeDefaultsToHtml(): void
    {
        $request = Request::create('/api/documents', 'POST', [
            'filetype' => 'file',
            'parentResourceNodeId' => '42',
            'contentFile' => '<p>hello</p>',
        ]);

        $input = $this->action->expose($request);

        self::assertSame('html', $input->contentFileExtension);
        self::assertSame('text/html', $input->contentFileMimeType);
    }

    public function testAParentGivenAsAnIriIsResolved(): void
    {
        $request = Request::create('/api/documents', 'POST', [
            'filetype' => 'file',
            'parentResourceNodeId' => '/api/resource_nodes/42',
        ]);

        self::assertSame(42, $this->action->expose($request)->parentResourceNodeId);
    }

    public function testPicksUpTheUploadedFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rfi');
        file_put_contents($path, 'contents');

        $request = Request::create(
            '/api/documents',
            'POST',
            ['filetype' => 'file', 'parentResourceNodeId' => '42'],
            [],
            ['uploadFile' => new UploadedFile($path, 'upload.txt', 'text/plain', null, true)]
        );

        $input = $this->action->expose($request);

        self::assertInstanceOf(UploadedFile::class, $input->uploadFile);
        self::assertSame('upload.txt', $input->uploadFile->getClientOriginalName());

        unlink($path);
    }
}
