<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\HotPotatoes;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesEntrypoints;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class HotPotatoesEntrypointsTest extends TestCase
{
    public function testSeparatesValidHtmlEntriesWithoutDuplicatingTheArchive(): void
    {
        $document = $this->documentWithEntries(['index.htm', 'jquiz.htm', 'jcloze.html', 'jquiz.htm']);

        self::assertSame(
            ['index.htm', 'jquiz.htm', 'jcloze.html'],
            HotPotatoesEntrypoints::forDocument($document),
        );
    }

    public function testRejectsInvalidPathsEvenIfMetadataHasBeenAltered(): void
    {
        $document = $this->documentWithEntries([
            '../secret.htm', '/root.htm', 'folder/../bad.html', 'nested/valid.htm',
            'scripts/evil.js', 'bad\name.html', "bad\0name.htm", 'bad%2ehtm',
        ]);

        self::assertSame(['nested/valid.htm'], HotPotatoesEntrypoints::forDocument($document));
    }

    public function testOrdinaryDocumentHasNoHotPotatoesEntrypoints(): void
    {
        $document = $this->createMock(CDocument::class);
        $document->method('getResourceNode')->willReturn(null);

        self::assertSame([], HotPotatoesEntrypoints::forDocument($document));
    }

    /**
     * @param array<int, string> $entrypoints
     */
    private function documentWithEntries(array $entrypoints): CDocument
    {
        $file = $this->createMock(ResourceFile::class);
        $file->method('getMetadata')->willReturn([
            'chamilo_hotpotatoes' => ['version' => 1, 'entrypoints' => $entrypoints],
        ]);
        $node = $this->createMock(ResourceNode::class);
        $node->method('getResourceFiles')->willReturn(new ArrayCollection([$file]));
        $document = $this->createMock(CDocument::class);
        $document->method('getResourceNode')->willReturn($node);

        return $document;
    }
}
