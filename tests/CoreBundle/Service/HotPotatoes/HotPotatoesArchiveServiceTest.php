<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\HotPotatoes;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesArchiveService;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class HotPotatoesArchiveServiceTest extends TestCase
{
    public function testAnOrdinaryZipDocumentIsNotMisidentifiedAsAnActivity(): void
    {
        $file = $this->createMock(ResourceFile::class);
        $file->method('getMetadata')->willReturn([]);
        $node = $this->createMock(ResourceNode::class);
        $node->method('getResourceFiles')->willReturn(new ArrayCollection([$file]));
        $document = $this->createMock(CDocument::class);
        $document->method('getResourceNode')->willReturn($node);

        self::assertFalse(HotPotatoesArchiveService::isHotPotatoesDocument($document));
    }

    public function testDocumentWithoutAResourceNodeIsNotAnActivity(): void
    {
        $document = $this->createMock(CDocument::class);
        $document->method('getResourceNode')->willReturn(null);

        self::assertFalse(HotPotatoesArchiveService::isHotPotatoesDocument($document));
    }

    public function testAnImportedArchiveIsRecognizedWithoutCreatingAQuiz(): void
    {
        $file = $this->createMock(ResourceFile::class);
        $file->method('getMetadata')->willReturn([
            'chamilo_hotpotatoes' => [
                'version' => 1,
                'entrypoints' => ['index.htm', 'jquiz.htm'],
            ],
        ]);
        $node = $this->createMock(ResourceNode::class);
        $node->method('getResourceFiles')->willReturn(new ArrayCollection([$file]));
        $document = $this->createMock(CDocument::class);
        $document->method('getResourceNode')->willReturn($node);

        self::assertTrue(HotPotatoesArchiveService::isHotPotatoesDocument($document));
    }
}
