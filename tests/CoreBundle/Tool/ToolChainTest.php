<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Tool;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Tool\AbstractTool;
use Chamilo\CoreBundle\Tool\GlobalTool;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class ToolChainTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetToolFromName(): void
    {
        self::bootKernel();

        $toolChain = self::getContainer()->get(ToolChain::class);

        $tool = $toolChain->getToolFromName('global');

        $this->assertInstanceOf(AbstractTool::class, $tool);
        $this->assertInstanceOf(GlobalTool::class, $tool);
    }

    public function testResourceType(): void
    {
        self::bootKernel();

        $toolChain = self::getContainer()->get(ToolChain::class);

        $tool = $toolChain->getToolFromName('global');
        $entity = $tool->getEntityByResourceType('urls');
        $this->assertSame($entity, AccessUrl::class);

        $typeName = 'urls';
        $type = $tool->getTypeNameByEntity(AccessUrl::class);
        $this->assertSame($typeName, $type);

        $type = $toolChain->getResourceTypeNameByEntity(AccessUrl::class);
        $this->assertSame($typeName, $type);

        $typeName = 'files';

        $tool = $toolChain->getToolFromName('user');
        $type = $tool->getTypeNameByEntity(PersonalFile::class);
        $this->assertSame($typeName, $type);

        $type = $toolChain->getResourceTypeNameByEntity(PersonalFile::class);
        $this->assertSame($typeName, $type);
    }

    public function testGetTools(): void
    {
        self::bootKernel();

        $toolChain = self::getContainer()->get(ToolChain::class);

        $count = $toolChain->getTools();

        $this->assertTrue(\count($count) > 0);
    }

    public function testCreateTools(): void
    {
        self::bootKernel();

        $toolChain = self::getContainer()->get(ToolChain::class);
        $countBefore = \count($toolChain->getTools());

        $toolChain->createTools();

        $tools = $toolChain->getTools();

        $this->assertSame($countBefore, \count($tools));

        $em = $this->getManager();

        // Delete BranchSync
        $branchRepo = $em->getRepository(BranchSync::class);
        $items = $branchRepo->findAll();
        foreach ($items as $item) {
            $em->remove($item);
        }
        $em->flush();

        // Delete AccessUrl
        $urlRepo = $em->getRepository(AccessUrl::class);
        $items = $urlRepo->findAll();
        foreach ($items as $item) {
            $em->remove($item);
        }
        $em->flush();

        $resourceTypeRepo = $em->getRepository(ResourceType::class);

        $items = $resourceTypeRepo->findAll();
        foreach ($items as $item) {
            $em->remove($item);
        }
        $em->flush();

        $items = $resourceTypeRepo->findAll();
        $this->assertSame([], $items);

        $toolChain = self::getContainer()->get(ToolChain::class);
        $toolChain->createTools();

        $items = $resourceTypeRepo->findAll();
        $this->assertNotEmpty($items);
    }
}
