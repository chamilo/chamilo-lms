<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Sequence;
use Chamilo\CoreBundle\Repository\SequenceRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class SequenceRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(SequenceRepository::class);

        $sequence = (new Sequence())
            ->setTitle('Sequence 1')
            ->setGraph('')
        ;
        $this->assertHasNoEntityViolations($sequence);
        $em->persist($sequence);
        $em->flush();

        $this->assertFalse($sequence->hasGraph());
        $this->assertSame('Sequence 1', (string) $sequence);
        $this->assertFalse($sequence->getUnSerializeGraph());
        $this->assertSame(1, $repo->count([]));
    }
}
