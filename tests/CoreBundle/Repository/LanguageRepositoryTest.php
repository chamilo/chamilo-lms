<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class LanguageRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetAllAvailable(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(LanguageRepository::class);

        // LanguageFixtures enables every language it ships, so the filter only has
        // something to leave out once an unavailable language exists.
        $availableCountBefore = \count($repo->getAllAvailable()->getQuery()->getResult());

        $language = (new Language())
            ->setAvailable(false)
            ->setOriginalName('unavailable')
            ->setEnglishName('unavailable')
            ->setIsocode('una')
        ;
        $em->persist($language);
        $em->flush();

        $this->assertCount($availableCountBefore, $repo->getAllAvailable()->getQuery()->getResult());
        $this->assertCount($availableCountBefore, $repo->getAllAvailableToArray(true));
    }

    public function testFindAllSubLanguages(): void
    {
        $repo = self::getContainer()->get(LanguageRepository::class);
        $languages = $repo->findAllSubLanguages();

        $this->assertNotNull($languages);
        $this->assertCount(0, $languages);
    }
}
