<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\SettingsOptions;
use Chamilo\CoreBundle\Repository\SettingsCurrentRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class SettingsCurrentRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(SettingsCurrentRepository::class);
        $count = $repo->count([]);

        $setting = (new SettingsCurrent())
            ->setTitle('test')
            ->setVariable('test')
            ->setUrl($this->getAccessUrl())
            ->setCategory('cat')
            ->setAccessUrlChangeable(1)
            ->setSubkey('sub')
            ->setType('type')
            ->setComment('comment')
            ->setSubkeytext('setSubkeytext')
            ->setAccessUrlLocked(1)
        ;
        $this->assertHasNoEntityViolations($setting);
        $em->persist($setting);

        $option = (new SettingsOptions())
            ->setValue('value')
            ->setDisplayText('option1')
            ->setVariable('variable')
        ;
        $em->persist($option);
        $this->assertHasNoEntityViolations($option);
        $em->flush();

        // By default, there's a root branch.
        $this->assertSame($count + 1, $repo->count([]));
    }
}
