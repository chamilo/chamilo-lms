<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Security\CourseToolSvgSanitizer;
use Chamilo\CoreBundle\Service\CourseToolCustomizationService;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CourseToolCustomizationServiceTest extends TestCase
{
    private function courseTool(string $title = 'document'): CTool
    {
        return (new CTool())
            ->setTitle($title)
            ->setTool((new Tool())->setTitle('document'))
        ;
    }

    public function testDefaultNameIsNotCustom(): void
    {
        self::assertNull(CourseToolCustomizationService::getCustomTitle($this->courseTool()));
    }

    public function testCustomNameIsStoredPerCourseToolWithoutRenamingPlatformTool(): void
    {
        $tool = $this->courseTool('Course materials');

        self::assertSame('Course materials', CourseToolCustomizationService::getCustomTitle($tool));
        self::assertSame('document', $tool->getTool()->getTitle());
    }

    public function testDefaultNameMatchingIgnoresCase(): void
    {
        self::assertNull(CourseToolCustomizationService::getCustomTitle($this->courseTool('Document')));
    }

    public function testAbsentIconDoesNotPretendAnotherResourceFileIsTheIcon(): void
    {
        $tool = $this->courseTool()->setResourceNode(new ResourceNode());
        $tool->getResourceNode()->addResourceFile(new ResourceFile());

        self::assertNull(CourseToolCustomizationService::getCustomIcon($tool));
    }

    public function testOnlyMarkedResourceFileIsUsedAsCustomIcon(): void
    {
        $node = new ResourceNode();
        $node->addResourceFile((new ResourceFile())->setMetadata(['unrelated' => true]));
        $icon = (new ResourceFile())->setMetadata(['course_home_custom_icon' => true]);
        $node->addResourceFile($icon);
        $tool = $this->courseTool()->setResourceNode($node);

        self::assertSame($icon, CourseToolCustomizationService::getCustomIcon($tool));
    }

    public function testChangingNameKeepsPlatformToolIdentifier(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');
        $service = new CourseToolCustomizationService(
            $em,
            $this->createMock(ResourceNodeRepository::class),
            new CourseToolSvgSanitizer(),
        );
        $tool = $this->courseTool()->setResourceNode(new ResourceNode());

        $result = $service->update($tool, '  Course materials  ', null);

        self::assertSame('Course materials', $result['customTitle']);
        self::assertNull($result['customIconUrl']);
        self::assertSame('document', $tool->getTool()->getTitle());
    }

    public function testUploadedInkscapeSvgIsSanitizedBeforePersistence(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::callback(
            static function (ResourceFile $resourceFile): bool {
                $file = $resourceFile->getFile();
                $content = $file instanceof UploadedFile ? file_get_contents($file->getPathname()) : false;

                return false !== $content
                    && str_contains($content, 'fill="#606060"')
                    && !str_contains($content, 'inkscape:')
                    && !str_contains($content, 'style=');
            }
        ));
        $em->expects(self::once())->method('flush');

        $repository = $this->createMock(ResourceNodeRepository::class);
        $repository->expects(self::once())->method('getResourceFileUrl')->willReturn('/icon.svg');
        $service = new CourseToolCustomizationService($em, $repository, new CourseToolSvgSanitizer());
        $tool = $this->courseTool()
            ->setCourse(new Course())
            ->setResourceNode(new ResourceNode())
        ;
        $filePath = tempnam(sys_get_temp_dir(), 'chamilo_svg_test_');
        $source = file_get_contents(\dirname(__DIR__, 2).'/Fixtures/CourseTool/chameleon.svg');
        self::assertIsString($source);
        file_put_contents($filePath, $source);

        try {
            $icon = new UploadedFile($filePath, 'chameleon.svg', 'image/svg+xml', null, true);
            $result = $service->update($tool, 'Course materials', $icon);

            self::assertSame('Course materials', $result['customTitle']);
            self::assertSame('/icon.svg', $result['customIconUrl']);
            self::assertSame('document', $tool->getTool()->getTitle());
        } finally {
            @unlink($filePath);
        }
    }

    public function testUnsafeSvgIsRejectedBeforeSavingTitle(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');
        $em->expects(self::never())->method('remove');
        $service = new CourseToolCustomizationService(
            $em,
            $this->createMock(ResourceNodeRepository::class),
            new CourseToolSvgSanitizer(),
        );
        $node = new ResourceNode();
        $previousIcon = (new ResourceFile())->setMetadata(['course_home_custom_icon' => true]);
        $node->addResourceFile($previousIcon);
        $tool = $this->courseTool()->setResourceNode($node);
        $filePath = tempnam(sys_get_temp_dir(), 'chamilo_svg_test_');
        file_put_contents($filePath, '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><script>alert(1)</script></svg>');

        try {
            $file = new UploadedFile($filePath, 'icon.svg', 'image/svg+xml', null, true);
            $this->expectException(BadRequestHttpException::class);
            $service->update($tool, 'New title', $file);
        } finally {
            @unlink($filePath);
            self::assertSame('document', $tool->getTitle());
            self::assertSame($previousIcon, CourseToolCustomizationService::getCustomIcon($tool));
        }
    }
}
