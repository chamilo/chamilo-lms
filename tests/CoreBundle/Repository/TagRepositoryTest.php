<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Entity\UserRelTag;
use Chamilo\CoreBundle\Repository\TagRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class TagRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(TagRepository::class);
        $defaultCount = $repo->count([]);

        $extraField = (new ExtraField())
            ->setDisplayText('test')
            ->setVariable('test')
            ->setExtraFieldType(ExtraField::USER_FIELD_TYPE)
            ->setFieldType(\ExtraField::FIELD_TYPE_TEXT)
        ;
        $em->persist($extraField);
        $em->flush();

        $tag = (new Tag())
            ->setTag('php')
            ->setCount(1)
            ->setField($extraField)
        ;
        $this->assertHasNoEntityViolations($tag);
        $em->persist($tag);

        $user = $this->createUser('test');

        $userRelTag = (new UserRelTag())
            ->setUser($user)
            ->setTag($tag)
        ;
        $em->persist($userRelTag);

        $em->flush();

        $this->assertSame($defaultCount + 1, $repo->count([]));

        $tags = $repo->findTagsByField('php', $extraField->getId());

        $this->assertSame(1, \count($tags));
    }
}
