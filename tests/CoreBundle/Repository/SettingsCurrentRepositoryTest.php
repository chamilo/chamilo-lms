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
            ->setAccessUrlLocked(1)
            ->setAccessUrlChangeable(1)
            ->setSubkey('sub')
            ->setType('type')
            ->setComment('comment')
            ->setSubkeytext('setSubkeytext')
            ->setScope('scope')
        ;
        $this->assertHasNoEntityViolations($setting);
        $em->persist($setting);

        $option = (new SettingsOptions())
            ->setValue('value')
            ->setDisplayText('option1')
            ->setVariable('variable')
        ;
        $this->assertHasNoEntityViolations($option);
        $em->persist($option);
        $em->flush();

        $this->assertNotNull($setting->getId());
        $this->assertSame('test', $setting->getTitle());
        $this->assertSame('sub', $setting->getSubkey());
        $this->assertSame('type', $setting->getType());
        $this->assertSame('comment', $setting->getComment());
        $this->assertSame('scope', $setting->getScope());
        $this->assertSame(1, $setting->getAccessUrlChangeable());
        $this->assertSame(1, $setting->getAccessUrlLocked());

        $this->assertNotNull($option->getId());
        $this->assertSame('variable', $option->getVariable());
        $this->assertSame('value', $option->getValue());
        $this->assertSame('option1', $option->getDisplayText());

        // By default, there's a root branch.
        $this->assertSame($count + 1, $repo->count([]));
    }
}
