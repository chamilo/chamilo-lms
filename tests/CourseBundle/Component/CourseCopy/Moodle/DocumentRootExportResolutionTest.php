<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy\Moodle;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Entity\CDocument;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DocumentRootExportResolutionTest extends TestCase
{
    public function testDirectCourseDocumentUsesCourseRootWhenDocumentsRootIsTheDocumentItself(): void
    {
        $courseRoot = new ResourceNode();
        $documentNode = (new ResourceNode())->setParent($courseRoot);

        $document = (new CDocument())
            ->setTitle('Module 1')
            ->setFiletype('file')
            ->setResourceNode($documentNode)
        ;

        $base = $this->resolveBaseNode($document, $documentNode, $courseRoot);

        self::assertSame($courseRoot, $base);
    }

    public function testDirectCourseDocumentUsesCourseRootWhenDedicatedDocumentsRootIsASibling(): void
    {
        $courseRoot = new ResourceNode();
        $documentsRoot = (new ResourceNode())->setParent($courseRoot);
        $documentNode = (new ResourceNode())->setParent($courseRoot);

        $document = (new CDocument())
            ->setTitle('Module 1')
            ->setFiletype('file')
            ->setResourceNode($documentNode)
        ;

        $base = $this->resolveBaseNode($document, $documentsRoot, $courseRoot);

        self::assertSame($courseRoot, $base);
    }

    public function testDocumentBelowDedicatedDocumentsRootUsesThatRoot(): void
    {
        $courseRoot = new ResourceNode();
        $documentsRoot = (new ResourceNode())->setParent($courseRoot);
        $folderNode = (new ResourceNode())->setParent($documentsRoot);
        $documentNode = (new ResourceNode())->setParent($folderNode);

        $document = (new CDocument())
            ->setTitle('LP document')
            ->setFiletype('file')
            ->setResourceNode($documentNode)
        ;

        $base = $this->resolveBaseNode($document, $documentsRoot, $courseRoot);

        self::assertSame($documentsRoot, $base);
    }

    public function testFirstRootLevelHtmlDocumentKeepsANonEmptyExportPath(): void
    {
        $courseRoot = new ResourceNode();
        $documentNode = (new ResourceNode())->setParent($courseRoot);

        $this->setNodePath($courseRoot, 'localhost-4/USINGCHAMILO-2330/');
        $this->setNodePath(
            $documentNode,
            'localhost-4/USINGCHAMILO-2330/Module 1: Finding Your Way Around Chamilo 3.0-2360/'
        );

        $resourceFile = (new ResourceFile())
            ->setOriginalName('Module 1: Finding Your Way Around Chamilo 3.0.html')
            ->setMimeType('text/html')
        ;
        $documentNode->addResourceFile($resourceFile);

        $document = (new CDocument())
            ->setTitle('Module 1: Finding Your Way Around Chamilo 3.0')
            ->setFiletype('file')
            ->setResourceNode($documentNode)
        ;

        $builder = (new ReflectionClass(CourseBuilder::class))->newInstanceWithoutConstructor();
        $builderReflection = new ReflectionClass($builder);

        $resolveBase = $builderReflection->getMethod('resolveDocumentExportBaseNode');
        $resolveBase->setAccessible(true);
        $base = $resolveBase->invoke($builder, $document, $documentNode, $courseRoot);

        $resolvePath = $builderReflection->getMethod('getLogicalDocumentRelativePath');
        $resolvePath->setAccessible(true);
        $path = $resolvePath->invoke($builder, $document, $base);

        $applyFilename = $builderReflection->getMethod('applyDocumentOriginalFilename');
        $applyFilename->setAccessible(true);
        $path = $applyFilename->invoke($builder, $document, $path);

        self::assertSame(
            'Module 1: Finding Your Way Around Chamilo 3.0.html',
            $path
        );
    }

    public function testFileExportPathUsesResourceFileOriginalName(): void
    {
        $resourceFile = (new ResourceFile())
            ->setOriginalName('Module 1: Finding Your Way Around Chamilo 3.0.html')
            ->setMimeType('text/html')
        ;

        $documentNode = new ResourceNode();
        $documentNode->addResourceFile($resourceFile);

        $document = (new CDocument())
            ->setTitle('Module 1: Finding Your Way Around Chamilo 3.0')
            ->setFiletype('file')
            ->setResourceNode($documentNode)
        ;

        $builder = (new ReflectionClass(CourseBuilder::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass($builder))->getMethod('applyDocumentOriginalFilename');
        $method->setAccessible(true);

        $path = $method->invoke(
            $builder,
            $document,
            'Learning paths/Module 1: Finding Your Way Around Chamilo 3.0'
        );

        self::assertSame(
            'Learning paths/Module 1: Finding Your Way Around Chamilo 3.0.html',
            $path
        );
    }

    private function setNodePath(ResourceNode $node, string $path): void
    {
        $property = (new ReflectionClass(ResourceNode::class))->getProperty('path');
        $property->setAccessible(true);
        $property->setValue($node, $path);
    }

    private function resolveBaseNode(
        CDocument $document,
        ?ResourceNode $documentsRoot,
        ?ResourceNode $courseRoot
    ): ?ResourceNode {
        $builder = (new ReflectionClass(CourseBuilder::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass($builder))->getMethod('resolveDocumentExportBaseNode');
        $method->setAccessible(true);

        /** @var ResourceNode|null $base */
        return $method->invoke($builder, $document, $documentsRoot, $courseRoot);
    }
}
