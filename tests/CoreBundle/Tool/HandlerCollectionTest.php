<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Tool;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Tool\AbstractTool;
use Chamilo\CoreBundle\Tool\Agenda;
use Chamilo\CoreBundle\Tool\HandlerCollection;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use InvalidArgumentException;

class HandlerCollectionTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetCollection(): void
    {
        self::bootKernel();

        $handler = self::getContainer()->get(HandlerCollection::class);

        $collection = $handler->getCollection();

        $this->assertNotEmpty($collection);
    }

    public function testGetHandler(): void
    {
        self::bootKernel();

        $handler = self::getContainer()->get(HandlerCollection::class);

        $this->expectException(InvalidArgumentException::class);
        $handler->getHandler('bla bla');

        $tool = $handler->getHandler('agenda');

        $this->assertInstanceOf(AbstractTool::class, $tool);
        $this->assertInstanceOf(Agenda::class, $tool);
    }

    public function testRepositoryHandlers(): void
    {
        self::bootKernel();

        $handler = self::getContainer()->get(HandlerCollection::class);
        $collection = $handler->getCollection();

        foreach ($collection as $tool) {
            $name = $tool->getName();
            $this->assertNotEmpty($name);

            $types = $tool->getResourceTypes();
            //$icon = $tool->getIcon();
            //$this->assertNotEmpty($icon, sprintf("Icons for tool %s doesnt exists", $name));
            $em = $this->getManager();
            /*if (!empty($types)) {
                foreach ($types as $entityName) {
                    $repo = $em->getRepository($entityName);
                    //var_dump($repo->getClassName());
                    $msg = sprintf(
                        'Error in tool %s, entity: %s repo: %s not instance of ResourceRepository',
                        $name,
                        $entityName,
                        \get_class($repo)
                    );
                    $this->assertInstanceOf(ResourceRepository::class, $repo, $msg);
                }
            }*/
        }
    }
}
